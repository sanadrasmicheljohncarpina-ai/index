<?php
// faculty/faculty_forgot_password.php
// Password recovery for Faculty accounts ONLY (users.role = 'teacher').
// Flow: username -> answer the 3 security questions -> choose a new password.
require_once 'security.php';
secure_session_start();
require_once 'db.php';
security_ensure_tables($mysqli);

const REC_ROLE   = 'teacher';          // only accounts with this role can recover here
const REC_KIND   = 'faculty_recover';          // throttle bucket, separate from the other portal
const REC_LOGIN  = 'faculty_login.php';
const REC_SKEY   = 'rec_faculty';          // session key holding the in-progress recovery
const REC_TTL    = 900;                 // recovery step expires after 15 minutes

$error = '';
$state = $_SESSION[REC_SKEY] ?? null;
if ($state && (time() - ($state['ts'] ?? 0)) > REC_TTL) { unset($_SESSION[REC_SKEY]); $state = null; }

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if (!csrf_valid($_POST['csrf_token'] ?? '')) {
        $error = 'Your session expired. Please try again.';

    // ── Step 1: find the account and show its questions ─────────────────
    } elseif ($action === 'lookup') {
        $username = trim((string)($_POST['username'] ?? ''));
        if ($username === '') {
            $error = 'Please enter your username.';
        } elseif (mb_strlen($username) > 100) {
            $error = 'That username is too long.';
        } elseif (auth_is_locked($mysqli, REC_KIND, $username)) {
            $error = AUTH_LOCK_MESSAGE;
        } else {
            $st = $mysqli->prepare("SELECT id FROM users WHERE username = ? AND role = ? AND is_active = 1 AND account_status = 'approved' LIMIT 1");
            $role = REC_ROLE;
            $st->bind_param('ss', $username, $role);
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
            $_SESSION[REC_SKEY] = ['uid' => $uid, 'username' => $username, 'keys' => $keys, 'ts' => time()];
            $state = $_SESSION[REC_SKEY];
        }

    // ── Step 2: check the answers and set the new password ──────────────
    } elseif ($action === 'reset' && $state) {
        $username = $state['username'];
        $answers  = is_array($_POST['sq_answer'] ?? null) ? $_POST['sq_answer'] : [];
        $pw       = (string)($_POST['password'] ?? '');
        $pw2      = (string)($_POST['confirm_password'] ?? '');

        if (auth_is_locked($mysqli, REC_KIND, $username)) {
            $error = AUTH_LOCK_MESSAGE;
        } elseif (strlen($pw) < 8) {
            $error = 'Password must be at least 8 characters.';
        } elseif (strlen($pw) > 72) {
            $error = 'Password must be 72 characters or fewer.';
        } elseif ($pw !== $pw2) {
            $error = 'Passwords do not match.';
        } else {
            $ok = false;
            if ($state['uid'] > 0) {
                $rows = sq_load($mysqli, $state['uid']);
                $ok = count($rows) === 3;
                for ($slot = 1; $slot <= 3; $slot++) {
                    $given = sq_normalize((string)($answers[$slot] ?? ''));
                    $hash  = $rows[$slot]['answer_hash'] ?? DUMMY_HASH;
                    // Always run every check so the response time does not reveal which answer was wrong.
                    if (!password_verify($given, $hash)) $ok = false;
                }
                // The account must still be a valid, active, approved Faculty account.
                if ($ok) {
                    $st = $mysqli->prepare("SELECT id FROM users WHERE id = ? AND role = ? AND is_active = 1 AND account_status = 'approved' LIMIT 1");
                    $role = REC_ROLE;
                    $st->bind_param('is', $state['uid'], $role);
                    $st->execute();
                    $ok = (bool)$st->get_result()->fetch_assoc();
                    $st->close();
                }
            } else {
                for ($i = 0; $i < 3; $i++) password_verify('x', DUMMY_HASH);   // same timing as a real account
            }

            if ($ok) {
                $newHash = password_hash($pw, PASSWORD_DEFAULT);
                $up = $mysqli->prepare('UPDATE users SET password_hash = ? WHERE id = ? AND role = ?');
                $role = REC_ROLE;
                $up->bind_param('sis', $newHash, $state['uid'], $role);
                $up->execute();
                $up->close();
                auth_record($mysqli, REC_KIND, $username, true);
                auth_clear($mysqli, REC_KIND, $username);
                unset($_SESSION[REC_SKEY]);
                session_regenerate_id(true);
                $_SESSION['reg_success'] = 'Password reset successfully. Please sign in.';
                header('Location: ' . REC_LOGIN);
                exit;
            }
            auth_record($mysqli, REC_KIND, $username, false);
            $error = auth_is_locked($mysqli, REC_KIND, $username)
                ? AUTH_LOCK_MESSAGE
                : 'One or more answers are incorrect. Please try again.';
        }
    }
}
$questions = sq_all_questions();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"/>
<meta name="viewport" content="width=device-width, initial-scale=1.0"/>
<title>PBI — Faculty Forgot Password</title>
<link href="https://fonts.googleapis.com/css2?family=Rajdhani:wght@600;700&family=DM+Sans:wght@400;500;600&display=swap" rel="stylesheet"/>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css"/>
<style>
:root{--dark-blue:#0A192F;--teal:#2563EB;--teal-hover:#3B82F6;--teal-light:#60A5FA;--light:#E0E6F0;--muted:#A0B3C6;--radius:10px;--shadow:0 8px 32px rgba(0,0,0,0.45);--rgb:37,99,235;}
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0;}
body{min-height:100vh;background:var(--dark-blue);font-family:'DM Sans',sans-serif;color:var(--light);display:flex;align-items:center;justify-content:center;padding:24px;overflow-x:hidden;position:relative;}
.bg-grid{position:fixed;inset:0;z-index:0;background-image:linear-gradient(rgba(var(--rgb),.06) 1px,transparent 1px),linear-gradient(90deg,rgba(var(--rgb),.06) 1px,transparent 1px);background-size:48px 48px;}
.orb{position:fixed;border-radius:50%;filter:blur(90px);z-index:0;pointer-events:none;}
.orb-1{width:380px;height:380px;background:radial-gradient(circle,rgba(var(--rgb),.2) 0%,transparent 70%);top:-80px;right:-80px;}
.orb-2{width:300px;height:300px;background:radial-gradient(circle,rgba(43,108,176,.18) 0%,transparent 70%);bottom:-60px;left:-60px;}
.card{position:relative;z-index:10;background:linear-gradient(180deg,#1D3555 0%,#172A45 38%,#122238 100%);border:1px solid rgba(var(--rgb),.30);border-radius:18px;padding:36px 36px 28px;width:100%;max-width:460px;box-shadow:0 30px 80px rgba(0,0,0,.5),inset 0 1px 0 rgba(255,255,255,.07);animation:cardIn .7s cubic-bezier(.22,1,.36,1) both;}
@keyframes cardIn{from{opacity:0;transform:translateY(32px) scale(.97)}to{opacity:1;transform:none}}
.card-header{text-align:center;margin-bottom:20px;}
.icon-wrap{width:64px;height:64px;border-radius:50%;background:rgba(var(--rgb),.15);border:2px solid var(--teal);display:flex;align-items:center;justify-content:center;margin:0 auto 14px;font-size:26px;color:var(--teal-light);box-shadow:0 0 24px rgba(var(--rgb),.35);}
.card-title{font-family:'Rajdhani',sans-serif;font-size:24px;font-weight:700;letter-spacing:2px;color:#fff;text-transform:uppercase;}
.role-pill{display:inline-flex;align-items:center;gap:6px;margin-top:10px;padding:4px 14px;border-radius:20px;background:rgba(var(--rgb),.14);border:1px solid rgba(var(--rgb),.4);color:var(--teal-light);font-size:11px;font-weight:700;letter-spacing:.8px;text-transform:uppercase;}
.card-subtitle{font-size:12.5px;color:var(--muted);margin-top:10px;line-height:1.6;}
.divider{height:1px;background:linear-gradient(90deg,transparent,rgba(var(--rgb),.55),transparent);margin-bottom:20px;}
.form-group{margin-bottom:16px;}
.form-label{display:block;font-size:11px;font-weight:600;letter-spacing:1.2px;text-transform:uppercase;color:var(--muted);margin-bottom:6px;}
.q-text{font-size:13.5px;line-height:1.5;color:#fff;margin-bottom:7px;}
.input-wrap{position:relative;}
.f-icon{position:absolute;left:13px;top:50%;transform:translateY(-50%);color:var(--muted);font-size:13px;pointer-events:none;}
.form-input{width:100%;padding:12px 13px 12px 38px;background:#0B1A32;border:1px solid rgba(255,255,255,.12);border-radius:var(--radius);color:var(--light);font-size:14px;font-family:'DM Sans',sans-serif;outline:none;box-shadow:inset 0 2px 6px rgba(0,0,0,.30);transition:border-color .25s,box-shadow .25s;}
.form-input.plain{padding-left:13px;}
.form-input::placeholder{color:rgba(160,179,198,.4);}
.form-input:focus{border-color:var(--teal);box-shadow:inset 0 2px 6px rgba(0,0,0,.22),0 0 0 3px rgba(var(--rgb),.22);}
.toggle-pw{position:absolute;right:10px;top:50%;transform:translateY(-50%);background:none;border:none;color:var(--muted);cursor:pointer;font-size:13px;padding:4px;}
.toggle-pw:hover{color:#fff;}
.alert{border-radius:8px;padding:10px 13px;font-size:13px;margin-bottom:16px;display:flex;align-items:flex-start;gap:8px;line-height:1.45;}
.alert i{margin-top:2px;}
.alert-error{background:rgba(240,84,84,.12);border:1px solid rgba(240,84,84,.35);color:#ff8a8a;}
.help{font-size:11.5px;color:var(--muted);line-height:1.55;margin-top:14px;text-align:center;}
.section-label{font-size:10.5px;font-weight:700;letter-spacing:1.2px;text-transform:uppercase;color:var(--teal-light);margin:20px 0 12px;display:flex;align-items:center;gap:8px;}
.section-label::after{content:"";flex:1;height:1px;background:linear-gradient(90deg,rgba(var(--rgb),.4),transparent);}
.btn-main{width:100%;padding:13px;background:linear-gradient(180deg,#3B82F6 0%,var(--teal) 55%,#1D4ED8 100%);border:none;border-radius:var(--radius);color:#fff;font-size:15px;font-weight:600;font-family:'DM Sans',sans-serif;cursor:pointer;transition:transform .15s,filter .2s;box-shadow:inset 0 1px 0 rgba(255,255,255,.24),0 10px 26px rgba(var(--rgb),.36);display:flex;align-items:center;justify-content:center;gap:8px;}
.btn-main:hover{filter:brightness(1.08);transform:translateY(-1px);}
.back-row{text-align:center;margin-top:18px;font-size:13px;color:var(--muted);}
.back-row a{color:var(--teal-light);font-weight:600;text-decoration:none;}
.back-row a:hover{text-decoration:underline;}
@media(max-width:480px){.card{padding:28px 20px 22px;}}
</style>
</head>
<body>
<div class="bg-grid"></div>
<div class="orb orb-1"></div>
<div class="orb orb-2"></div>

<div class="card">
    <div class="card-header">
        <div class="icon-wrap"><i class="fa-solid fa-key"></i></div>
        <div class="card-title">Forgot Password</div>
        <div class="role-pill"><i class="fa-solid fa-id-badge"></i> Faculty Access</div>
        <div class="card-subtitle">
            <?= $state ? 'Answer your 3 security questions, then choose a new password.' : 'Enter your username and we will ask you the security questions you chose at registration.' ?>
        </div>
    </div>
    <div class="divider"></div>

    <?php if ($error): ?>
    <div class="alert alert-error" role="alert"><i class="fa-solid fa-circle-exclamation"></i><span><?= htmlspecialchars($error) ?></span></div>
    <?php endif; ?>

    <?php if (!$state): ?>
    <form method="POST" action="faculty_forgot_password.php" autocomplete="off">
        <?= csrf_field() ?>
        <input type="hidden" name="action" value="lookup">
        <div class="form-group">
            <label class="form-label" for="username">Username</label>
            <div class="input-wrap">
                <i class="fa-solid fa-user f-icon"></i>
                <input class="form-input" type="text" id="username" name="username" placeholder="Enter your username" maxlength="100" required autofocus/>
            </div>
        </div>
        <button type="submit" class="btn-main"><i class="fa-solid fa-arrow-right"></i> Continue</button>
    </form>
    <p class="help">Registered before security questions existed? Sign in and set them under <strong>Settings → Security</strong>, or ask your administrator to reset your password.</p>

    <?php else: ?>
    <form method="POST" action="faculty_forgot_password.php" autocomplete="off" id="resetForm">
        <?= csrf_field() ?>
        <input type="hidden" name="action" value="reset">
        <?php foreach ($state['keys'] as $slot => $key): ?>
        <div class="form-group">
            <label class="form-label">Question <?= (int)$slot ?></label>
            <div class="q-text"><?= htmlspecialchars($questions[$key] ?? 'Security question') ?></div>
            <input class="form-input plain" type="text" name="sq_answer[<?= (int)$slot ?>]" maxlength="100" placeholder="Your answer" autocomplete="off" required <?= $slot === 1 ? 'autofocus' : '' ?>/>
        </div>
        <?php endforeach; ?>

        <div class="section-label"><i class="fa-solid fa-lock"></i> New password</div>
        <div class="form-group">
            <label class="form-label" for="password">New Password</label>
            <div class="input-wrap">
                <i class="fa-solid fa-lock f-icon"></i>
                <input class="form-input" type="password" id="password" name="password" placeholder="Min. 8 characters" autocomplete="new-password" required/>
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
        <p class="help">Answers are not case-sensitive. Having trouble? Contact your administrator.</p>
    </form>
    <?php endif; ?>

    <div class="back-row">
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
