<?php
// student/student_login.php
require_once 'security.php';
secure_session_start();
require_once 'db.php';
security_ensure_tables($mysqli);

// Already signed in as a student -> straight to the dashboard
if (!empty($_SESSION['user_id']) && ($_SESSION['role'] ?? '') === 'student') {
    header("Location: student_dashboard.php");
    exit;
}

$error   = '';
$success = $_SESSION['reg_success'] ?? '';
unset($_SESSION['reg_success']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password']     ?? '';
    $ident    = mb_strtolower(mb_substr($username, 0, 100));

    if (!csrf_valid($_POST['csrf_token'] ?? '')) {
        $error = "Your session expired. Please try again.";
    } elseif ($username === '' || $password === '') {
        $error = "Please enter your username and password.";
    } elseif (mb_strlen($username) > 100 || strlen($password) > 1024) {
        $error = "Incorrect username or password. Please try again.";
    } elseif (auth_is_locked($mysqli, 'login', $ident)) {
        $error = AUTH_LOCK_MESSAGE;
    } else {
        $stmt = $mysqli->prepare(
            "SELECT id, full_name, password_hash, role, department, year_level, account_status
             FROM users WHERE username = ? AND role = 'student' AND is_active = 1 LIMIT 1"
        );
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $user = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        // Always run exactly one password_verify, so the response time does not reveal
        // whether the username exists.
        $passwordOk = password_verify($password, $user['password_hash'] ?? DUMMY_HASH);

        if ($user && $passwordOk) {
            if ($user['account_status'] !== 'approved') {
                // Correct password but the account cannot sign in yet. Not counted as a failed attempt.
                $error = $user['account_status'] === 'pending'
                    ? "Your account is awaiting admin approval. You'll be able to log in once it's approved."
                    : "Your account has been blocked. Please contact the administrator.";
            } else {
                auth_clear($mysqli, 'login', $ident);
                auth_record($mysqli, 'login', $ident, true);

                session_regenerate_id(true);
                $_SESSION['user_id']    = $user['id'];
                $_SESSION['full_name']  = $user['full_name'];
                $_SESSION['role']       = $user['role'];
                $_SESSION['department'] = $user['department'];
                $_SESSION['year_level'] = $user['year_level'];

                // Students with no security questions yet (registered before the feature existed),
                // or who still use a question that is no longer offered, are asked to set them up.
                $needsSetup = sq_needs_update(sq_load($mysqli, (int)$user['id']));
                header("Location: " . ($needsSetup ? "security_questions.php?setup=1" : "student_dashboard.php"));
                exit;
            }
        } else {
            auth_record($mysqli, 'login', $ident, false);
            $error = "Incorrect username or password. Please try again.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"/>
<meta name="viewport" content="width=device-width, initial-scale=1.0"/>
<title>PBI — Student Login</title>
<link href="https://fonts.googleapis.com/css2?family=Rajdhani:wght@600;700&family=DM+Sans:wght@400;500;600&display=swap" rel="stylesheet"/>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css"/>
<link rel="stylesheet" href="student_ui.css"/>
<style>
:root{--dark-blue:#0A192F;--blue-mid:#172A45;--gold:#D97706;--gold-hover:#F59E0B;--light:#E0E6F0;--muted:#A0B3C6;--radius:10px;--shadow:0 8px 32px rgba(0,0,0,0.45);}
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0;}
body{min-height:100vh;background:#0A192F;font-family:'DM Sans',sans-serif;color:var(--light);display:flex;align-items:center;justify-content:center;overflow:hidden;position:relative;}
.bg-grid{display:block;position:fixed;inset:0;z-index:0;background-image:repeating-linear-gradient(45deg,rgba(217,119,6,.07) 0px,rgba(217,119,6,.07) 1px,transparent 1px,transparent 26px),repeating-linear-gradient(-45deg,rgba(217,119,6,.05) 0px,rgba(217,119,6,.05) 1px,transparent 1px,transparent 26px);}
.hex-deco{position:fixed;z-index:0;pointer-events:none;opacity:.5;}
.hex-1{top:-60px;left:-60px;}
.hex-2{bottom:-70px;right:-70px;}
.login-card{position:relative;z-index:10;background:rgba(23,42,69,.82);backdrop-filter:blur(18px);border:1px solid rgba(255,255,255,.09);border-radius:18px;padding:48px 44px 40px;width:100%;max-width:420px;box-shadow:var(--shadow);animation:cardIn .7s cubic-bezier(.22,1,.36,1) both;}
@keyframes cardIn{from{opacity:0;transform:translateY(32px) scale(.97)}to{opacity:1;transform:none}}
.card-header{text-align:center;margin-bottom:28px;}
.logo-ring{width:72px;height:72px;border-radius:50%;display:block;object-fit:cover;border:2.5px solid var(--gold);box-shadow:0 0 22px rgba(217,119,6,.4);margin:0 auto 16px;}
.card-title{font-family:'Rajdhani',sans-serif;font-size:26px;font-weight:700;letter-spacing:2px;color:#fff;text-transform:uppercase;}
.card-subtitle{font-size:12px;color:var(--muted);letter-spacing:1.2px;text-transform:uppercase;margin-top:4px;}
.divider{height:1px;background:linear-gradient(90deg,transparent,rgba(217,119,6,.4),transparent);margin-bottom:24px;}
.form-group{margin-bottom:18px;}
.form-label{display:block;font-size:11px;font-weight:600;letter-spacing:1.2px;text-transform:uppercase;color:var(--muted);margin-bottom:7px;}
.input-wrap{position:relative;}
.f-icon{position:absolute;left:14px;top:50%;transform:translateY(-50%);color:var(--muted);font-size:14px;pointer-events:none;}
.form-input{width:100%;padding:12px 14px 12px 40px;background:rgba(10,25,47,.7);border:1px solid rgba(255,255,255,.1);border-radius:var(--radius);color:var(--light);font-size:14px;font-family:'DM Sans',sans-serif;outline:none;transition:border-color .25s,box-shadow .25s;}
.form-input::placeholder{color:rgba(160,179,198,.45);}
.form-input:focus{border-color:var(--gold);box-shadow:0 0 0 3px rgba(217,119,6,.2);}
.toggle-pw{position:absolute;right:13px;top:50%;transform:translateY(-50%);background:none;border:none;color:var(--muted);cursor:pointer;font-size:14px;padding:0;}
.alert{border-radius:8px;padding:11px 14px;font-size:13px;margin-bottom:18px;display:flex;align-items:center;gap:8px;}
.alert-error{background:rgba(240,84,84,.12);border:1px solid rgba(240,84,84,.35);color:#ff8a8a;}
.alert-success{background:rgba(34,197,94,.1);border:1px solid rgba(34,197,94,.28);color:#86efac;}
.btn-login{width:100%;padding:13px;background:var(--gold);border:none;border-radius:var(--radius);color:#fff;font-size:15px;font-weight:600;font-family:'DM Sans',sans-serif;cursor:pointer;transition:background .2s,transform .15s;box-shadow:0 4px 16px rgba(217,119,6,.4);display:flex;align-items:center;justify-content:center;gap:8px;}
.btn-login:hover{background:var(--gold-hover);transform:translateY(-1px);}
.register-row{text-align:center;margin-top:16px;font-size:13px;color:var(--muted);}
.register-row a{color:var(--gold-hover);font-weight:600;text-decoration:none;}
.register-row a:hover{text-decoration:underline;}
.card-footer{text-align:center;margin-top:14px;font-size:12px;color:var(--muted);border-top:1px solid rgba(255,255,255,.06);padding-top:14px;}
.secure-badge{display:inline-flex;align-items:center;gap:5px;font-size:11px;color:var(--muted);}
.secure-badge i{color:#4ade80;font-size:10px;}
</style>
</head>
<body>
<div class="bg-grid"></div>
<svg class="hex-deco hex-1" width="260" height="260" viewBox="0 0 260 260"><polygon points="130,10 240,70 240,190 130,250 20,190 20,70" fill="none" stroke="#D97706" stroke-width="1"/><polygon points="130,50 200,90 200,170 130,210 60,170 60,90" fill="none" stroke="#D97706" stroke-width="1"/></svg>
<svg class="hex-deco hex-2" width="300" height="300" viewBox="0 0 300 300"><polygon points="150,10 280,80 280,220 150,290 20,220 20,80" fill="none" stroke="#2B6CB0" stroke-width="1"/><polygon points="150,60 220,100 220,200 150,240 80,200 80,100" fill="none" stroke="#2B6CB0" stroke-width="1"/></svg>

<div class="login-card">
    <div class="card-header">
        <img class="logo-ring" src="../image/pbi_logo" alt="PBI Logo"/>
        <div class="card-title">Student Portal</div>
        <div class="card-subtitle">Pandan Bay Institute Inc.</div>
    </div>
    <div class="divider"></div>

    <?php if ($success): ?>
    <div class="alert alert-success">
        <i class="fa-solid fa-circle-check"></i>
        <?= htmlspecialchars($success) ?>
    </div>
    <?php endif; ?>

    <?php if ($error): ?>
    <div class="alert alert-error">
        <i class="fa-solid fa-circle-exclamation"></i>
        <?= htmlspecialchars($error) ?>
    </div>
    <?php endif; ?>

    <form method="POST" action="student_login.php" autocomplete="off">
        <?= csrf_field() ?>
        <div class="form-group">
            <label class="form-label" for="username">Username</label>
            <div class="input-wrap">
                <input class="form-input" type="text" id="username" name="username"
                       placeholder="Enter your username" required
                       autocomplete="username"/>
                <i class="fa-solid fa-user f-icon"></i>
            </div>
        </div>
        <div class="form-group">
            <label class="form-label" for="password">Password</label>
            <div class="input-wrap">
                <input class="form-input" type="password" id="password" name="password"
                       placeholder="Enter your password" required
                       autocomplete="current-password"/>
                <i class="fa-solid fa-lock f-icon"></i>
                <button type="button" class="toggle-pw" onclick="togglePw()">
                    <i class="fa-solid fa-eye" id="eyeIcon"></i>
                </button>
            </div>
        </div>
        <div style="text-align:right; margin-top:-10px; margin-bottom:14px;">
    <a href="forgot_password.php" style="font-size:12px; color:var(--gold-hover); text-decoration:none; font-weight:600;">
        <i class="fa-solid fa-key"></i> Forgot Password?
    </a>
</div>
        <button type="submit" class="btn-login" id="loginBtn">
            <i class="fa-solid fa-right-to-bracket"></i> <span>Sign In</span>
        </button>
    </form>

    <div class="register-row">
        New to the system? <a href="student_register.php">Register here</a>
    </div>

<div class="card-footer">
        <span class="secure-badge">
            <i class="fa-solid fa-circle-check"></i> Secured &amp; Encrypted Connection
        </span>
    </div>
</div>

<script>
function togglePw() {
    const pw = document.getElementById('password'), ic = document.getElementById('eyeIcon');
    pw.type = pw.type === 'password' ? 'text' : 'password';
    ic.className = pw.type === 'password' ? 'fa-solid fa-eye' : 'fa-solid fa-eye-slash';
}

const loginForm = document.querySelector('.login-card form');
const loginBtn = document.getElementById('loginBtn');
if (loginForm && loginBtn) {
    loginForm.addEventListener('submit', () => {
        loginBtn.disabled = true;
        loginBtn.querySelector('i').className = 'fa-solid fa-spinner fa-spin';
        loginBtn.querySelector('span').textContent = 'Signing In…';
    });
    // Back-button / bfcache: make the button usable again
    window.addEventListener('pageshow', (e) => {
        if (!e.persisted) return;
        loginBtn.disabled = false;
        loginBtn.querySelector('i').className = 'fa-solid fa-right-to-bracket';
        loginBtn.querySelector('span').textContent = 'Sign In';
    });
}
</script>
</body>
</html>