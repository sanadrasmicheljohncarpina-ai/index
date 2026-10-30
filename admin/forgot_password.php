<?php
// admin/forgot_password.php
// Password recovery for Admin-portal accounts ONLY (admin, superadmin, registrar).
// Uses the 3 security questions set at registration (no email / reset link).
// Flow: 1) username  ->  2) answer the 3 security questions  ->  3) choose a new password.
require_once 'security.php';
if (function_exists('secure_session_start')) { secure_session_start(); }
elseif (session_status() !== PHP_SESSION_ACTIVE) { session_start(); }
require_once 'db.php';
security_ensure_tables($mysqli);

const REC_ROLES  = "'admin','superadmin','registrar'";   // roles allowed to recover here
const REC_KIND   = 'admin_recover';     // throttle bucket, separate from the other portals
const REC_LOGIN  = 'admin_login.php';
const REC_SKEY   = 'rec_admin';         // session key holding the in-progress recovery
const REC_TTL    = 900;                 // whole recovery expires after 15 minutes
const REC_RESET_TTL = 600;              // after correct answers: 10 minutes to choose the new password

$error = '';
$state = $_SESSION[REC_SKEY] ?? null;
if ($state && (time() - ($state['ts'] ?? 0)) > REC_TTL) { unset($_SESSION[REC_SKEY]); $state = null; }

if (($_GET['restart'] ?? '') === '1') {
    unset($_SESSION[REC_SKEY]);
    header('Location: forgot_password.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if (!csrf_valid($_POST['csrf_token'] ?? '')) {
        $error = 'Your session expired. Please try again.';

    // ── Step 1: username only. The questions are not shown until this passes. ──
    } elseif ($action === 'lookup') {
        $username = trim((string)($_POST['username'] ?? ''));
        if ($username === '') {
            $error = 'Please enter your username.';
        } elseif (mb_strlen($username) > 100) {
            $error = 'That username is too long.';
        } elseif (auth_is_locked($mysqli, REC_KIND, $username)) {
            $error = AUTH_LOCK_MESSAGE;
        } else {
            // BINARY = case-sensitive, same as the admin login.
            $st = $mysqli->prepare("SELECT id FROM users WHERE BINARY username = ? AND role IN (" . REC_ROLES . ") AND is_active = 1 LIMIT 1");
            $st->bind_param('s', $username);
            $st->execute();
            $u = $st->get_result()->fetch_assoc();
            $st->close();

            $uid = 0; $keys = [];
            if ($u) {
                $rows = sq_load($mysqli, (int)$u['id']);
                if (count($rows) === 3) {
                    $uid = (int)$u['id'];
                    foreach ($rows as $slot => $r) $keys[$slot] = $r['question_key'];
                }
            }
            if ($uid === 0) {
                // Unknown name (or no questions yet): show a believable made-up set so nobody can tell the difference.
                $fake = sq_fake_keys($username);
                $keys = [1 => $fake[0], 2 => $fake[1], 3 => $fake[2]];
            }
            session_regenerate_id(true);
            $_SESSION[REC_SKEY] = ['uid' => $uid, 'username' => $username, 'keys' => $keys, 'ts' => time(), 'verified' => 0];
            $state = $_SESSION[REC_SKEY];
        }

    // ── Step 2: check the 3 answers ─────────────────────────────────────
    } elseif ($action === 'answers' && $state && empty($state['verified'])) {
        $username = $state['username'];
        $answers  = is_array($_POST['sq_answer'] ?? null) ? $_POST['sq_answer'] : [];

        if (auth_is_locked($mysqli, REC_KIND, $username)) {
            $error = AUTH_LOCK_MESSAGE;
        } else {
            $ok = false;
            if ($state['uid'] > 0) {
                $rows = sq_load($mysqli, $state['uid']);
                $ok = count($rows) === 3;
                for ($slot = 1; $slot <= 3; $slot++) {
                    $raw   = (string)($answers[$slot] ?? '');
                    $given = mb_strlen($raw) <= 100 ? sq_normalize($raw) : '';
                    $hash  = $rows[$slot]['answer_hash'] ?? DUMMY_HASH;
                    // Always run every check so the response time does not reveal which answer was wrong.
                    if ($given === '' || !password_verify($given, $hash)) $ok = false;
                }
                // The account must still be a valid, active Admin account.
                if ($ok) {
                    $st = $mysqli->prepare("SELECT id FROM users WHERE id = ? AND role IN (" . REC_ROLES . ") AND is_active = 1 LIMIT 1");
                    $st->bind_param('i', $state['uid']);
                    $st->execute();
                    $ok = (bool)$st->get_result()->fetch_assoc();
                    $st->close();
                }
            } else {
                for ($i = 0; $i < 3; $i++) password_verify('x', DUMMY_HASH);   // same timing as a real account
            }

            if ($ok) {
                auth_clear($mysqli, REC_KIND, $username);
                session_regenerate_id(true);
                $_SESSION[REC_SKEY]['verified'] = time();
                $state = $_SESSION[REC_SKEY];
            } else {
                auth_record($mysqli, REC_KIND, $username, false);
                $error = auth_is_locked($mysqli, REC_KIND, $username)
                    ? AUTH_LOCK_MESSAGE
                    : 'One or more answers are incorrect. Please try again.';
            }
        }

    // ── Step 3: choose the new password (only after the answers were verified) ──
    } elseif ($action === 'reset' && $state && !empty($state['verified'])) {
        $pw  = (string)($_POST['password'] ?? '');
        $pw2 = (string)($_POST['confirm_password'] ?? '');

        if ((time() - (int)$state['verified']) > REC_RESET_TTL || (int)$state['uid'] <= 0) {
            unset($_SESSION[REC_SKEY]);
            $state = null;
            $error = 'Verification expired. Please start again.';
        } elseif (strlen($pw) < 8) {
            $error = 'Password must be at least 8 characters.';
        } elseif (strlen($pw) > 72) {
            $error = 'Password must be 72 characters or fewer.';
        } elseif ($pw !== $pw2) {
            $error = 'Passwords do not match.';
        } else {
            $newHash = password_hash($pw, PASSWORD_DEFAULT);
            $uid = (int)$state['uid'];
            $up = $mysqli->prepare("UPDATE users SET password_hash = ? WHERE id = ? AND role IN (" . REC_ROLES . ") AND is_active = 1");
            $up->bind_param('si', $newHash, $uid);
            $done = $up->execute();
            $up->close();
            if ($done) {
                auth_clear($mysqli, REC_KIND, $state['username']);
                unset($_SESSION[REC_SKEY]);
                session_regenerate_id(true);
                $_SESSION['reg_success'] = 'Password reset successfully. Please sign in.';
                header('Location: ' . REC_LOGIN);
                exit;
            }
            $error = 'Could not update the password. Please try again.';
        }
    }
}

// Which step to show
$step = 1;
if ($state) { $step = empty($state['verified']) ? 2 : 3; }
// admin_register.php uses sq_questions(); the faculty copy of security.php uses sq_all_questions().
$questions = function_exists('sq_all_questions') ? sq_all_questions() : sq_questions();
$subtitles = [
    1 => 'Enter your username and we will ask you the security questions you chose at registration.',
    2 => 'Answer your 3 security questions to verify it is you.',
    3 => 'Identity verified. Choose a new password.',
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"/>
<meta name="viewport" content="width=device-width, initial-scale=1.0"/>
<title>PBI Admin — Forgot Password</title>
<link href="https://fonts.googleapis.com/css2?family=Rajdhani:wght@600;700&family=DM+Sans:wght@400;500;600&display=swap" rel="stylesheet"/>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css"/>
<style>
:root{
    --navy-deep:#091727;--navy:#0F1E33;--navy-input:#0B1B2E;--border:#29405A;
    --text:#E7EEF7;--muted:#9AAFC5;--gold:#F2C94C;--gold-hover:#FFD866;
    --blue:#2F6EE2;--blue-hover:#3D7BF0;--blue-light:#8DB4FF;--radius:10px;--rgb:47,110,226;
}
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0;}
body{
    min-height:100vh;color:var(--text);font-family:'DM Sans',sans-serif;
    display:flex;align-items:center;justify-content:center;padding:24px;overflow-x:hidden;position:relative;
    background:
        radial-gradient(circle at 10% 8%,rgba(47,110,226,.13),transparent 30%),
        radial-gradient(circle at 90% 90%,rgba(242,201,76,.08),transparent 28%),
        var(--navy-deep);
    background-attachment:fixed;
}
.bg-grid{position:fixed;inset:0;z-index:0;pointer-events:none;background-image:
    repeating-linear-gradient(45deg,rgba(79,129,184,.055) 0,rgba(79,129,184,.055) 1px,transparent 1px,transparent 26px),
    repeating-linear-gradient(-45deg,rgba(79,129,184,.04) 0,rgba(79,129,184,.04) 1px,transparent 1px,transparent 26px);}
.card{
    position:relative;z-index:10;width:100%;max-width:460px;padding:36px 36px 28px;
    background:rgba(15,30,51,.96);border:1px solid rgba(152,178,207,.18);border-radius:18px;
    box-shadow:0 24px 70px rgba(0,0,0,.45),0 0 0 1px rgba(255,255,255,.025),0 0 80px rgba(var(--rgb),.07);
    animation:cardIn .7s cubic-bezier(.22,1,.36,1) both;
}
@keyframes cardIn{from{opacity:0;transform:translateY(32px) scale(.97)}to{opacity:1;transform:none}}
.card-header{text-align:center;margin-bottom:20px;}
.logo-img{width:72px;height:72px;border-radius:50%;object-fit:cover;display:block;border:2.5px solid #C9A227;box-shadow:0 0 22px rgba(201,162,39,.42);margin:0 auto 16px;}
.card-title{font-family:'Rajdhani',sans-serif;font-size:24px;font-weight:700;letter-spacing:2px;color:#F3F7FB;text-transform:uppercase;}
.role-pill{display:inline-flex;align-items:center;gap:6px;margin-top:10px;padding:4px 14px;border-radius:20px;background:rgba(var(--rgb),.14);border:1px solid rgba(98,144,230,.42);color:var(--blue-light);font-size:11px;font-weight:700;letter-spacing:.8px;text-transform:uppercase;}
.card-subtitle{font-size:12.5px;color:var(--muted);margin-top:10px;line-height:1.6;}
.divider{height:1px;background:linear-gradient(90deg,transparent,rgba(93,145,204,.4),transparent);margin-bottom:20px;}
.form-group{margin-bottom:16px;}
.form-label{display:block;font-size:11px;font-weight:600;letter-spacing:1.2px;text-transform:uppercase;color:#98ADC3;margin-bottom:6px;}
.q-text{font-size:13.5px;line-height:1.5;color:#fff;margin-bottom:7px;}
.input-wrap{position:relative;}
.f-icon{position:absolute;left:14px;top:50%;transform:translateY(-50%);color:#92A7BF;font-size:13px;pointer-events:none;z-index:2;width:18px;text-align:center;}
.form-input{width:100%;padding:12px 44px 12px 44px;min-height:48px;background:var(--navy-input);border:1px solid var(--border);border-radius:var(--radius);color:var(--text);font:500 14px 'DM Sans',sans-serif;outline:none;transition:border-color .25s,box-shadow .25s,background .25s;}
.form-input.plain{padding-left:14px;padding-right:14px;}
.form-input::placeholder{color:rgba(154,175,197,.5);}
.form-input:hover:not(:focus){background:#0D2035;border-color:#345473;}
.form-input:focus{background:#0D2035;border-color:var(--blue);box-shadow:0 0 0 3px rgba(var(--rgb),.2);}
.input-wrap:focus-within .f-icon{color:#B7C9DB;}
.toggle-pw{position:absolute;right:12px;top:50%;transform:translateY(-50%);background:none;border:none;color:#92A7BF;cursor:pointer;font-size:13px;width:26px;height:30px;display:flex;align-items:center;justify-content:center;z-index:3;}
.toggle-pw:hover{color:#fff;}
.alert{border-radius:8px;padding:10px 13px;font-size:13px;margin-bottom:16px;display:flex;align-items:flex-start;gap:8px;line-height:1.45;}
.alert i{margin-top:2px;}
.alert-error{background:rgba(248,113,113,.10);border:1px solid rgba(248,113,113,.28);color:#FCA5A5;}
.help{font-size:11.5px;color:var(--muted);line-height:1.55;margin-top:14px;text-align:center;}
.section-label{font-size:10.5px;font-weight:700;letter-spacing:1.2px;text-transform:uppercase;color:var(--blue-light);margin:20px 0 12px;display:flex;align-items:center;gap:8px;}
.section-label::after{content:"";flex:1;height:1px;background:linear-gradient(90deg,rgba(var(--rgb),.4),transparent);}
.btn-main{width:100%;min-height:50px;padding:13px;background:var(--blue);border:none;border-radius:var(--radius);color:#fff;font:600 15px 'DM Sans',sans-serif;cursor:pointer;display:flex;align-items:center;justify-content:center;gap:8px;box-shadow:0 8px 22px rgba(var(--rgb),.3);transition:background .2s,transform .15s,box-shadow .2s;}
.btn-main:hover{background:var(--blue-hover);transform:translateY(-1px);box-shadow:0 10px 28px rgba(var(--rgb),.38);}
.btn-main:active{transform:translateY(1px);}
.back-row{text-align:center;margin-top:18px;font-size:13px;color:var(--muted);}
.back-row a{color:var(--gold);font-weight:600;text-decoration:none;}
.back-row a:hover{color:var(--gold-hover);text-decoration:underline;}
button:focus-visible,a:focus-visible{outline:3px solid rgba(79,144,231,.35);outline-offset:2px;}
@media(prefers-reduced-motion:reduce){.card{animation:none;}.btn-main{transition:none;}}
@media(max-width:480px){.card{padding:28px 20px 22px;}}
.steps{display:flex;align-items:center;justify-content:center;gap:8px;margin:0 0 20px;}
.dot{width:26px;height:26px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:12px;font-weight:700;color:var(--muted);border:1px solid rgba(255,255,255,.15);}
.dot.active{background:var(--blue);border-color:var(--blue);color:#fff;box-shadow:0 0 14px rgba(var(--rgb),.5);}
.dot.done{background:rgba(74,222,128,.15);border-color:rgba(74,222,128,.5);color:#4ade80;}
.bar{width:28px;height:1px;background:rgba(255,255,255,.15);}
.back-row .restart{display:block;margin-bottom:8px;}
</style>
</head>
<body>
<div class="bg-grid" aria-hidden="true"></div>

<div class="card">
    <div class="card-header">
        <img class="logo-img" src="../image/pbi_logo" alt="PBI Logo" onerror="this.style.display='none'"/>
        <div class="card-title">Forgot Password</div>
        <div class="role-pill"><i class="fa-solid fa-user-shield"></i> Admin Access</div>
        <div class="card-subtitle"><?= htmlspecialchars($subtitles[$step]) ?></div>
    </div>

    <div class="steps" aria-label="Recovery progress">
        <div class="dot <?= $step === 1 ? 'active' : 'done' ?>"><?= $step > 1 ? '<i class="fa-solid fa-check"></i>' : '1' ?></div>
        <div class="bar"></div>
        <div class="dot <?= $step === 2 ? 'active' : ($step > 2 ? 'done' : '') ?>"><?= $step > 2 ? '<i class="fa-solid fa-check"></i>' : '2' ?></div>
        <div class="bar"></div>
        <div class="dot <?= $step === 3 ? 'active' : '' ?>">3</div>
    </div>
    <div class="divider"></div>

    <?php if ($error): ?>
    <div class="alert alert-error" role="alert"><i class="fa-solid fa-circle-exclamation"></i><span><?= htmlspecialchars($error) ?></span></div>
    <?php endif; ?>

    <?php if ($step === 1): ?>
    <form method="POST" action="forgot_password.php" autocomplete="off">
        <?= csrf_field() ?>
        <input type="hidden" name="action" value="lookup">
        <div class="form-group">
            <label class="form-label" for="username">Username</label>
            <div class="input-wrap">
                <i class="fa-solid fa-user f-icon"></i>
                <input class="form-input" type="text" id="username" name="username" placeholder="Enter admin username" maxlength="100" required autofocus/>
            </div>
        </div>
        <button type="submit" class="btn-main"><i class="fa-solid fa-arrow-right"></i> Continue</button>
    </form>
    <p class="help">Usernames are case-sensitive. If you never set security questions, you will need to reset the password directly in the database.</p>

    <?php elseif ($step === 2): ?>
    <form method="POST" action="forgot_password.php" autocomplete="off">
        <?= csrf_field() ?>
        <input type="hidden" name="action" value="answers">
        <?php foreach ($state['keys'] as $slot => $key): ?>
        <div class="form-group">
            <label class="form-label">Question <?= (int)$slot ?></label>
            <div class="q-text"><?= htmlspecialchars($questions[$key] ?? 'Security question') ?></div>
            <input class="form-input plain" type="text" name="sq_answer[<?= (int)$slot ?>]" maxlength="100" placeholder="Your answer" autocomplete="off" required <?= (int)$slot === 1 ? 'autofocus' : '' ?>/>
        </div>
        <?php endforeach; ?>
        <button type="submit" class="btn-main"><i class="fa-solid fa-shield-halved"></i> Verify Answers</button>
        <p class="help">Answers are not case-sensitive.</p>
    </form>

    <?php else: ?>
    <form method="POST" action="forgot_password.php" autocomplete="off" id="resetForm">
        <?= csrf_field() ?>
        <input type="hidden" name="action" value="reset">
        <div class="form-group">
            <label class="form-label" for="password">New Password</label>
            <div class="input-wrap">
                <i class="fa-solid fa-lock f-icon"></i>
                <input class="form-input" type="password" id="password" name="password" placeholder="Min. 8 characters" autocomplete="new-password" required autofocus/>
                <button type="button" class="toggle-pw" onclick="togglePw('password','e1')" aria-label="Show password"><i class="fa-solid fa-eye-slash" id="e1"></i></button>
            </div>
        </div>
        <div class="form-group">
            <label class="form-label" for="confirm_password">Confirm Password</label>
            <div class="input-wrap">
                <i class="fa-solid fa-lock f-icon"></i>
                <input class="form-input" type="password" id="confirm_password" name="confirm_password" placeholder="Re-enter password" autocomplete="new-password" required/>
                <button type="button" class="toggle-pw" onclick="togglePw('confirm_password','e2')" aria-label="Show password"><i class="fa-solid fa-eye-slash" id="e2"></i></button>
            </div>
        </div>
        <button type="submit" class="btn-main"><i class="fa-solid fa-rotate"></i> Reset Password</button>
    </form>
    <?php endif; ?>

    <div class="back-row">
        <?php if ($step > 1): ?><a class="restart" href="forgot_password.php?restart=1">Use a different username</a><?php endif; ?>
        <a href="<?= REC_LOGIN ?>"><i class="fa-solid fa-arrow-left"></i> Back to Login</a>
    </div>
</div>
<script>
function togglePw(id, ic){
    const e = document.getElementById(id), i = document.getElementById(ic);
    const showing = e.type === 'password';
    e.type = showing ? 'text' : 'password';
    i.className = showing ? 'fa-solid fa-eye' : 'fa-solid fa-eye-slash';
}
const rf = document.getElementById('resetForm');
if (rf) rf.addEventListener('submit', function(e){
    const p = document.getElementById('password'), c = document.getElementById('confirm_password');
    if (p.value.length < 8) { e.preventDefault(); alert('Password must be at least 8 characters.'); p.focus(); }
    else if (p.value !== c.value) { e.preventDefault(); alert('Passwords do not match.'); c.focus(); }
});
</script>
</body>
</html>