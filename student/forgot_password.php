<?php
// student/forgot_password.php
// Student password recovery: username -> security questions -> new password.
// Answers are CASE-SENSITIVE (see sq_normalize() in security.php).
require_once 'security.php';
secure_session_start();
require_once 'db.php';
security_ensure_tables($mysqli);

const FP_KIND      = 'stu_recover';       // auth_attempts.kind is VARCHAR(16)
const FP_STATE_TTL = 900;   // whole recovery attempt must finish within 15 minutes
const FP_RESET_TTL = 600;   // after answering correctly, 10 minutes to choose a new password

$error = '';
$state = $_SESSION['student_fp'] ?? null;

if ($state && (time() - (int)($state['started'] ?? 0)) > FP_STATE_TTL) {
    unset($_SESSION['student_fp']);
    $state = null;
}

// Opening this page (clicking "Forgot password?", reloading, going back) always starts at the username step.
// Questions are only shown after a username has been submitted in THIS visit.
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST' && $state) {
    unset($_SESSION['student_fp']);
    $state = null;
}

if (($_GET['restart'] ?? '') === '1') {
    unset($_SESSION['student_fp']);
    header('Location: forgot_password.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = (string)($_POST['action'] ?? '');

    if (!csrf_valid($_POST['csrf_token'] ?? '')) {
        $error = 'Session expired. Please try again.';

    // ── STEP 1: username ────────────────────────────────────────────────
    } elseif ($action === 'username') {
        $username = trim((string)($_POST['username'] ?? ''));
        if ($username === '') {
            $error = 'Please enter your username.';
        } elseif (mb_strlen($username) > 50) {
            $error = 'Username is too long.';
        } elseif (auth_is_locked($mysqli, FP_KIND, $username)) {
            $error = AUTH_LOCK_MESSAGE;
        } else {
            $uid  = 0;
            $keys = [];
            // Same exact (case-sensitive) match the Student login uses.
            $st = $mysqli->prepare("SELECT id FROM users WHERE BINARY username = ? AND role = 'student' AND is_active = 1 AND account_status = 'approved' LIMIT 1");
            $st->bind_param('s', $username);
            $st->execute();
            $row = $st->get_result()->fetch_assoc();
            $st->close();
            if ($row) {
                $rows = sq_load($mysqli, (int)$row['id']);
                if (count($rows) === 3) {
                    $uid = (int)$row['id'];
                    foreach ([1, 2, 3] as $slot) { $keys[] = $rows[$slot]['question_key'] ?? ''; }
                }
            }
            // Unknown usernames (or accounts without questions) get believable fake
            // questions, so the form never reveals whether an account exists.
            if (!$keys) { $keys = sq_fake_keys($username); $uid = 0; }

            session_regenerate_id(true);
            $_SESSION['student_fp'] = [
                'username' => $username,
                'uid'      => $uid,
                'keys'     => $keys,
                'started'  => time(),
                'verified' => 0,
            ];
            $state = $_SESSION['student_fp'];
        }

    // ── STEP 2: security answers ────────────────────────────────────────
    } elseif ($action === 'answers' && $state && empty($state['verified'])) {
        $username = (string)$state['username'];
        if (auth_is_locked($mysqli, FP_KIND, $username)) {
            $error = AUTH_LOCK_MESSAGE;
        } else {
            $given = is_array($_POST['answer'] ?? null) ? $_POST['answer'] : [];
            $rows  = $state['uid'] > 0 ? sq_load($mysqli, (int)$state['uid']) : [];
            $real  = $state['uid'] > 0 && count($rows) === 3;
            $allOk = $real;
            foreach ([1, 2, 3] as $slot) {
                $raw  = (string)($given[$slot] ?? '');
                $norm = mb_strlen($raw) <= 100 ? sq_normalize($raw) : '';
                // Always run a hash check so timing is similar for real and fake accounts.
                $hash  = $rows[$slot]['answer_hash'] ?? DUMMY_HASH;
                $match = password_verify($norm, $hash);
                if (!$real || $norm === '' || !$match) { $allOk = false; }
            }
            if ($allOk) {
                auth_clear($mysqli, FP_KIND, $username);
                session_regenerate_id(true);
                $_SESSION['student_fp']['verified'] = time();
                $state = $_SESSION['student_fp'];
            } else {
                auth_record($mysqli, FP_KIND, $username, false);
                $error = auth_is_locked($mysqli, FP_KIND, $username)
                    ? AUTH_LOCK_MESSAGE
                    : 'One or more answers are incorrect. Please try again.';
            }
        }

    // ── STEP 3: new password ────────────────────────────────────────────
    } elseif ($action === 'reset' && $state && !empty($state['verified'])) {
        if ((time() - (int)$state['verified']) > FP_RESET_TTL || (int)$state['uid'] <= 0) {
            unset($_SESSION['student_fp']);
            $state = null;
            $error = 'Verification expired. Please start again.';
        } else {
            $pw  = (string)($_POST['password'] ?? '');
            $pw2 = (string)($_POST['confirm_password'] ?? '');
            if (strlen($pw) < 8) {
                $error = 'Password must be at least 8 characters.';
            } elseif (strlen($pw) > 72) {
                $error = 'Password must be 72 characters or fewer.';
            } elseif ($pw !== $pw2) {
                $error = 'Passwords do not match.';
            } elseif (mb_strtolower($pw) === mb_strtolower((string)$state['username'])) {
                $error = 'Your password cannot be the same as your username.';
            } else {
                // Use whichever password column the users table has.
                $col = 'password';
                $c = $mysqli->query("SHOW COLUMNS FROM users LIKE 'password_hash'");
                if ($c && $c->num_rows > 0) { $col = 'password_hash'; }
                $hash = password_hash($pw, PASSWORD_DEFAULT);
                $uid  = (int)$state['uid'];
                $up = $mysqli->prepare("UPDATE users SET `$col` = ? WHERE id = ? AND role = 'student' AND is_active = 1 AND account_status = 'approved' LIMIT 1");
                $up->bind_param('si', $hash, $uid);
                $ok = $up->execute();
                $up->close();
                if ($ok) {
                    auth_clear($mysqli, FP_KIND, (string)$state['username']);
                    unset($_SESSION['student_fp']);
                    session_regenerate_id(true);
                    $_SESSION['reg_success'] = 'Password reset successfully. Please sign in.';
                    header('Location: student_login.php');
                    exit;
                }
                $error = 'Could not update the password. Please try again.';
            }
        }
    }
}

// Which step to show
$step = 1;
if ($state) {
    $step = !empty($state['verified']) ? 3 : 2;
}
$allQ = sq_all_questions();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"/>
<meta name="viewport" content="width=device-width, initial-scale=1.0"/>
<title>PBI — Student Password Recovery</title>
<link href="https://fonts.googleapis.com/css2?family=Rajdhani:wght@600;700&family=DM+Sans:wght@400;500;600&display=swap" rel="stylesheet"/>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css"/>
<style>
:root{--dark-blue:#0A192F;--violet:#D97706;--violet-hover:#F59E0B;--light:#E0E6F0;--muted:#A0B3C6;--radius:10px;}
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0;}
html{min-height:100%;background:var(--dark-blue);}
body{
    min-height:100vh;font-family:'DM Sans',sans-serif;color:var(--light);
    display:flex;align-items:center;justify-content:center;padding:24px;position:relative;
    background:
        radial-gradient(circle at 10% 10%, rgba(217,119,6,.16), transparent 32%),
        radial-gradient(circle at 90% 90%, rgba(43,108,176,.12), transparent 30%),
        var(--dark-blue);
    background-attachment:fixed;
}
.bg-grid{position:fixed;inset:0;z-index:0;pointer-events:none;
    background-image:
        repeating-linear-gradient(45deg,rgba(217,119,6,.075) 0,rgba(217,119,6,.075) 1px,transparent 1px,transparent 26px),
        repeating-linear-gradient(-45deg,rgba(217,119,6,.055) 0,rgba(217,119,6,.055) 1px,transparent 1px,transparent 26px);}
.card{
    position:relative;z-index:10;width:100%;max-width:440px;padding:44px 40px 34px;
    background:rgba(23,42,69,.84);backdrop-filter:blur(18px);-webkit-backdrop-filter:blur(18px);
    border:1px solid rgba(255,255,255,.12);border-radius:18px;
    box-shadow:0 24px 70px rgba(0,0,0,.36),0 0 90px rgba(217,119,6,.09);
    animation:cardIn .7s cubic-bezier(.22,1,.36,1) both;
}
@keyframes cardIn{from{opacity:0;transform:translateY(32px) scale(.97)}to{opacity:1;transform:none}}
.card-header{text-align:center;margin-bottom:22px;}
.logo-ring{width:72px;height:72px;border-radius:50%;display:block;object-fit:cover;border:2.5px solid var(--violet);
    box-shadow:0 0 22px rgba(217,119,6,.42);margin:0 auto 14px;}
.card-title{font-family:'Rajdhani',sans-serif;font-size:24px;font-weight:700;letter-spacing:2px;color:#fff;text-transform:uppercase;}
.card-subtitle{font-size:12px;color:var(--muted);line-height:1.5;margin-top:6px;}
.role-pill{display:inline-flex;align-items:center;gap:6px;margin-top:10px;padding:4px 14px;border-radius:20px;
    background:rgba(217,119,6,.14);border:1px solid rgba(217,119,6,.38);color:var(--violet-hover);
    font-size:11px;font-weight:700;letter-spacing:.8px;text-transform:uppercase;}
.steps{display:flex;align-items:center;justify-content:center;gap:8px;margin:0 0 22px;}
.dot{width:26px;height:26px;border-radius:50%;display:flex;align-items:center;justify-content:center;
    font-size:12px;font-weight:700;color:var(--muted);border:1px solid rgba(255,255,255,.15);}
.dot.active{background:var(--violet);border-color:var(--violet);color:#1A1204;box-shadow:0 0 14px rgba(217,119,6,.5);}
.dot.done{background:rgba(74,222,128,.15);border-color:rgba(74,222,128,.5);color:#4ade80;}
.bar{width:28px;height:1px;background:rgba(255,255,255,.15);}
.divider{height:1px;margin-bottom:22px;background:linear-gradient(90deg,transparent,rgba(217,119,6,.40),transparent);}
.alert{display:flex;align-items:flex-start;gap:8px;border-radius:8px;padding:11px 14px;margin-bottom:18px;font-size:13px;line-height:1.45;
    background:rgba(240,84,84,.12);border:1px solid rgba(240,84,84,.35);color:#ff8a8a;}
.form-group{margin-bottom:18px;}
.form-label{display:block;margin-bottom:7px;color:var(--muted);font-size:11px;font-weight:600;letter-spacing:1.2px;text-transform:uppercase;}
.q-text{display:block;margin-bottom:8px;color:var(--light);font-size:14px;font-weight:600;line-height:1.4;}
.q-num{color:var(--violet-hover);margin-right:4px;}
.input-wrap{position:relative;}
.f-icon{position:absolute;left:14px;top:50%;transform:translateY(-50%);color:var(--muted);font-size:14px;pointer-events:none;}
.form-input{width:100%;padding:12px 42px 12px 40px;min-height:50px;background:rgba(10,25,47,.70);
    border:1px solid rgba(255,255,255,.10);border-radius:var(--radius);color:var(--light);
    font:500 14px 'DM Sans',sans-serif;outline:none;transition:border-color .25s,box-shadow .25s,background .25s;}
.form-input.plain{padding-left:14px;}
.form-input::placeholder{color:rgba(160,179,198,.45);}
.form-input:hover:not(:focus){border-color:rgba(245,158,11,.42);}
.form-input:focus{border-color:var(--violet);background:rgba(10,25,47,.84);box-shadow:0 0 0 3px rgba(217,119,6,.20);}
.input-wrap:focus-within .f-icon{color:var(--violet-hover);}
.toggle-pw{position:absolute;right:13px;top:50%;transform:translateY(-50%);border:0;background:none;padding:5px;color:var(--muted);cursor:pointer;font-size:14px;}
.toggle-pw:hover{color:#fff;}
.btn{width:100%;min-height:52px;padding:13px 16px;display:flex;align-items:center;justify-content:center;gap:8px;border:0;
    border-radius:var(--radius);background:var(--violet);color:#1A1204;font:600 15px 'DM Sans',sans-serif;cursor:pointer;
    box-shadow:0 4px 16px rgba(217,119,6,.40);transition:background .2s,transform .15s,box-shadow .2s;}
.btn:hover{background:var(--violet-hover);transform:translateY(-1px);}
.btn:active{transform:translateY(1px);}
.btn:disabled{cursor:not-allowed;opacity:.78;transform:none;}
.hint{font-size:12px;color:var(--muted);line-height:1.5;margin-bottom:18px;text-align:center;}
.links{margin-top:16px;text-align:center;font-size:13px;color:var(--muted);display:flex;flex-direction:column;gap:8px;}
.links a{color:var(--violet-hover);font-weight:600;text-decoration:none;}
.links a:hover{text-decoration:underline;}
a:focus-visible,button:focus-visible{outline:3px solid rgba(245,158,11,.38);outline-offset:2px;}
@media(max-width:480px){body{padding:16px;}.card{padding:34px 20px 28px;}}
@media(prefers-reduced-motion:reduce){.card{animation:none;}}
</style>
</head>
<body>
<div class="bg-grid" aria-hidden="true"></div>

<div class="card">
    <div class="card-header">
        <img class="logo-ring" src="../image/pbi_logo" alt="PBI Logo"/>
        <div class="card-title">Password Recovery</div>
        <div class="role-pill"><i class="fa-solid fa-user-graduate"></i> Student Access</div>
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
    <div class="alert" role="alert">
        <i class="fa-solid fa-circle-exclamation"></i>
        <span><?= htmlspecialchars($error) ?></span>
    </div>
    <?php endif; ?>

<?php if ($step === 1): ?>
    <p class="hint">Enter your username to continue. You will then be asked your security questions.</p>
    <form method="POST" action="forgot_password.php" autocomplete="off">
        <?= csrf_field() ?>
        <input type="hidden" name="action" value="username">
        <div class="form-group">
            <label class="form-label" for="username">Username</label>
            <div class="input-wrap">
                <input class="form-input" type="text" id="username" name="username" placeholder="Enter your username"
                       required maxlength="50" autofocus value="<?= htmlspecialchars($_POST['username'] ?? '') ?>"/>
                <i class="fa-solid fa-user f-icon"></i>
            </div>
        </div>
        <button type="submit" class="btn"><i class="fa-solid fa-arrow-right"></i><span>Continue</span></button>
    </form>

<?php elseif ($step === 2): ?>
    <p class="hint">Answer all three questions for <strong><?= htmlspecialchars($state['username']) ?></strong>.
       Answers are case-sensitive: type them exactly as you saved them.</p>
    <form method="POST" action="forgot_password.php" autocomplete="off">
        <?= csrf_field() ?>
        <input type="hidden" name="action" value="answers">
        <?php foreach ($state['keys'] as $i => $key): $n = $i + 1; ?>
        <div class="form-group">
            <label class="q-text" for="a<?= $n ?>"><span class="q-num"><?= $n ?>.</span><?= htmlspecialchars($allQ[$key] ?? 'Security question') ?></label>
            <input class="form-input plain" type="text" id="a<?= $n ?>" name="answer[<?= $n ?>]" maxlength="100"
                   placeholder="Your answer" required autocomplete="off"/>
        </div>
        <?php endforeach; ?>
        <button type="submit" class="btn"><i class="fa-solid fa-shield-halved"></i><span>Verify Answers</span></button>
    </form>

<?php else: ?>
    <p class="hint">Identity verified. Choose a new password (at least 8 characters).</p>
    <form method="POST" action="forgot_password.php" autocomplete="off">
        <?= csrf_field() ?>
        <input type="hidden" name="action" value="reset">
        <div class="form-group">
            <label class="form-label" for="password">New Password</label>
            <div class="input-wrap">
                <input class="form-input" type="password" id="password" name="password" placeholder="New password"
                       required minlength="8" autocomplete="new-password"/>
                <i class="fa-solid fa-lock f-icon"></i>
                <button type="button" class="toggle-pw" data-target="password" aria-label="Show password"><i class="fa-solid fa-eye-slash"></i></button>
            </div>
        </div>
        <div class="form-group">
            <label class="form-label" for="confirm_password">Confirm Password</label>
            <div class="input-wrap">
                <input class="form-input" type="password" id="confirm_password" name="confirm_password" placeholder="Re-enter new password"
                       required minlength="8" autocomplete="new-password"/>
                <i class="fa-solid fa-lock f-icon"></i>
                <button type="button" class="toggle-pw" data-target="confirm_password" aria-label="Show password"><i class="fa-solid fa-eye-slash"></i></button>
            </div>
        </div>
        <button type="submit" class="btn"><i class="fa-solid fa-key"></i><span>Reset Password</span></button>
    </form>
<?php endif; ?>

    <div class="links">
        <?php if ($step > 1): ?><a href="forgot_password.php?restart=1">Use a different username</a><?php endif; ?>
        <a href="student_login.php"><i class="fa-solid fa-arrow-left"></i> Back to sign in</a>
    </div>
</div>

<script>
document.querySelectorAll('.toggle-pw').forEach(function (b) {
    b.addEventListener('click', function () {
        var input = document.getElementById(b.dataset.target);
        var show = input.type === 'password';
        input.type = show ? 'text' : 'password';
        b.querySelector('i').className = show ? 'fa-solid fa-eye' : 'fa-solid fa-eye-slash';
        b.setAttribute('aria-label', show ? 'Hide password' : 'Show password');
    });
});
</script>
</body>
</html>