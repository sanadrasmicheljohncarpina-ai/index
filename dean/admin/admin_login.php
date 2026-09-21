<?php
// admin/admin_login.php
session_start();
require_once 'db.php';

// Already logged in → go straight to dashboard
if (!empty($_SESSION['user_id']) && in_array($_SESSION['role'] ?? '', ['admin','superadmin','registrar'])) {
    header("Location: admin_dashboard.php");
    exit;
}

$error   = '';
$success = $_SESSION['reg_success'] ?? '';
unset($_SESSION['reg_success']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password']     ?? '';

    if (empty($username) || empty($password)) {
        $error = "Please enter your username and password.";
    } else {
        $stmt = $mysqli->prepare(
            "SELECT id, full_name, email, password_hash, role, is_logged_in
             FROM users WHERE username = ? AND role IN ('admin','superadmin','registrar') AND is_active = 1 LIMIT 1"
        );
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $user = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if ($user && password_verify($password, $user['password_hash'])) {
            session_regenerate_id(true);
            $_SESSION['user_id']   = $user['id'];
            $_SESSION['full_name'] = $user['full_name'];
            $_SESSION['role']      = $user['role'];

            $upd = $mysqli->prepare("UPDATE users SET is_logged_in = 1 WHERE id = ?");
            $upd->bind_param("i", $user['id']);
            $upd->execute();
            $upd->close();

            $mysqli->close();
            header("Location: admin_dashboard.php");
            exit;
        } else {
            $error = "Incorrect username or password. Please try again.";
        }
    }
    if ($mysqli->ping()) $mysqli->close();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"/>
<meta name="viewport" content="width=device-width, initial-scale=1.0"/>
<title>PBI Admin — Secure Access</title>
<link href="https://fonts.googleapis.com/css2?family=Rajdhani:wght@600;700&family=DM+Sans:wght@400;500;600&display=swap" rel="stylesheet"/>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css"/>
<style>
:root{
    --dark-blue:#0B1F3A;
    --blue-mid:#123B78;
    --blue-inner:#0F294A;
    --blue-accent:#3B82F6;
    --blue-hover:#60A5FA;
    --light:#EAF2FC;
    --muted:#91A9C2;
    --radius:10px;
    --shadow:0 8px 32px rgba(0,0,0,.45);
}
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
html{min-height:100%;background:var(--dark-blue)}
body{
    min-height:100vh;
    background:
        radial-gradient(circle at 10% 10%,rgba(59,130,246,.16),transparent 32%),
        radial-gradient(circle at 90% 90%,rgba(96,165,250,.11),transparent 30%),
        var(--dark-blue);
    background-attachment:fixed;
    font-family:'DM Sans',sans-serif;color:var(--light);
    display:flex;align-items:center;justify-content:center;
    overflow:hidden;position:relative;padding:24px;
}

/* Decorative background + ambient lighting — same composition as the Student and
   Dean portals, kept in the Admin blue identity. */
body::before{
    content:"";position:fixed;inset:0;z-index:0;pointer-events:none;
    background:linear-gradient(180deg,rgba(255,255,255,.025),transparent 26%,rgba(0,0,0,.12));
}
.bg-grid{
    position:fixed;inset:0;z-index:0;pointer-events:none;
    background-image:
        repeating-linear-gradient(45deg,rgba(59,130,246,.075) 0,rgba(59,130,246,.075) 1px,transparent 1px,transparent 26px),
        repeating-linear-gradient(-45deg,rgba(59,130,246,.055) 0,rgba(59,130,246,.055) 1px,transparent 1px,transparent 26px);
}
.hex-deco{position:fixed;z-index:0;pointer-events:none;opacity:.5}
.hex-1{top:-60px;left:-60px}
.hex-2{bottom:-70px;right:-70px}

.login-card{
    position:relative;z-index:10;width:100%;max-width:420px;
    padding:48px 44px 40px;
    background:rgba(23,42,69,.84);
    backdrop-filter:blur(18px);-webkit-backdrop-filter:blur(18px);
    border:1px solid rgba(255,255,255,.12);border-radius:18px;
    box-shadow:0 24px 70px rgba(0,0,0,.36),0 0 0 1px rgba(255,255,255,.02),0 0 90px rgba(59,130,246,.10);
    transition:border-color .3s ease,box-shadow .3s ease;
    animation:cardIn .7s cubic-bezier(.22,1,.36,1) both;
}
.login-card:hover{border-color:rgba(96,165,250,.30);box-shadow:0 24px 70px rgba(0,0,0,.36),0 0 0 1px rgba(255,255,255,.02),0 0 100px rgba(59,130,246,.17)}
@keyframes cardIn{from{opacity:0;transform:translateY(32px) scale(.97)}to{opacity:1;transform:none}}
.card-header{text-align:center;margin-bottom:28px}
.logo-img{
    width:72px;height:72px;border-radius:50%;object-fit:cover;display:block;
    border:2.5px solid var(--blue-accent);box-shadow:0 0 22px rgba(59,130,246,.42);
    margin:0 auto 16px;
}
.card-title{font-family:'Rajdhani',sans-serif;font-size:26px;font-weight:700;letter-spacing:2px;color:#fff;text-transform:uppercase}
.card-subtitle{font-size:12px;color:var(--muted);letter-spacing:1.2px;text-transform:uppercase;margin-top:4px}
.divider{height:1px;background:linear-gradient(90deg,transparent,rgba(59,130,246,.40),transparent);margin-bottom:24px}
.form-group{margin-bottom:18px}
.form-label{display:block;font-size:11px;font-weight:600;letter-spacing:1.2px;text-transform:uppercase;color:var(--muted);margin-bottom:7px}
.input-wrap{position:relative}
.f-icon{position:absolute;left:14px;top:50%;transform:translateY(-50%);color:var(--muted);font-size:14px;pointer-events:none;transition:color .2s}
.form-input{
    width:100%;padding:12px 42px 12px 40px;min-height:50px;
    background:rgba(10,25,47,.70);border:1px solid rgba(255,255,255,.10);border-radius:var(--radius);
    color:var(--light);font:500 14px 'DM Sans',sans-serif;outline:none;
    transition:border-color .25s,box-shadow .25s,background .25s;
}
.form-input::placeholder{color:rgba(160,179,198,.45)}
.form-input:hover:not(:focus){border-color:rgba(96,165,250,.42)}
.form-input:focus{border-color:var(--blue-accent);background:rgba(10,25,47,.84);box-shadow:0 0 0 3px rgba(59,130,246,.20)}
.input-wrap:focus-within .f-icon{color:var(--blue-hover)}
.toggle-pw{position:absolute;right:13px;top:50%;transform:translateY(-50%);background:none;border:none;color:var(--muted);cursor:pointer;font-size:14px;padding:5px;line-height:1}
.toggle-pw:hover{color:var(--blue-hover)}
.alert{display:flex;align-items:flex-start;gap:8px;border-radius:8px;padding:11px 14px;font-size:13px;line-height:1.45;margin-bottom:18px}
.alert-error{background:rgba(240,84,84,.12);border:1px solid rgba(240,84,84,.35);color:#ff8a8a}
.alert-success{background:rgba(34,197,94,.10);border:1px solid rgba(34,197,94,.28);color:#86efac}
.forgot-row{text-align:right;margin-top:-10px;margin-bottom:14px}
.forgot-row a{font-size:12px;color:var(--blue-hover);text-decoration:none;font-weight:600}
.forgot-row a:hover{text-decoration:underline}
.btn-main{
    width:100%;min-height:52px;padding:13px;background:var(--blue-accent);border:none;border-radius:var(--radius);
    color:#fff;font:600 15px 'DM Sans',sans-serif;cursor:pointer;
    display:flex;align-items:center;justify-content:center;gap:8px;
    box-shadow:0 4px 16px rgba(59,130,246,.40);
    transition:background .2s,transform .15s,box-shadow .2s;
}
.btn-main:hover{background:var(--blue-hover);transform:translateY(-1px)}
.btn-main:active{transform:translateY(1px)}
.register-row{text-align:center;margin-top:16px;font-size:13px;color:var(--muted)}
.register-row a{color:var(--blue-hover);font-weight:600;text-decoration:none;text-underline-offset:3px}
.register-row a:hover{text-decoration:underline}
.card-footer{text-align:center;margin-top:18px;padding-top:14px;border-top:1px solid rgba(255,255,255,.06);font-size:12px;color:var(--muted)}
.secure-badge{display:inline-flex;align-items:center;gap:5px;font-size:11px;color:var(--muted)}
.secure-badge i{color:#4ade80;font-size:10px}

button:focus-visible,a:focus-visible,.photo-preview:focus-visible{outline:3px solid rgba(96,165,250,.38);outline-offset:2px}
@media(prefers-reduced-motion:reduce){.login-card,.reg-card{animation:none;transition:none}.btn-main,.photo-preview{transition:none}}
@media(max-width:480px){body{padding:16px}.login-card{padding:36px 22px 30px}.card-title{font-size:24px}}
@media(max-height:700px){body{overflow:auto}}
</style>
</head>
<body>
<div class="bg-grid" aria-hidden="true"></div>

<svg class="hex-deco hex-1" width="260" height="260" viewBox="0 0 260 260" aria-hidden="true"><polygon points="130,10 240,70 240,190 130,250 20,190 20,70" fill="none" stroke="#3B82F6" stroke-width="1"/><polygon points="130,50 200,90 200,170 130,210 60,170 60,90" fill="none" stroke="#3B82F6" stroke-width="1"/></svg>
<svg class="hex-deco hex-2" width="300" height="300" viewBox="0 0 300 300" aria-hidden="true"><polygon points="150,10 280,80 280,220 150,290 20,220 20,80" fill="none" stroke="#60A5FA" stroke-width="1"/><polygon points="150,60 220,100 220,200 150,240 80,200 80,100" fill="none" stroke="#60A5FA" stroke-width="1"/></svg>

<div class="login-card">
    <div class="card-header">
        <img class="logo-img" src="../image/pbi_logo" alt="PBI Logo"
             onerror="this.style.display='none'"/>
        <div class="card-title">ADMIN PORTAL</div>
        <div class="card-subtitle">Pandan Bay Institute &mdash; Control Panel</div>
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

    <form method="POST" action="admin_login.php" autocomplete="off">
        <div class="form-group">
            <label class="form-label">Username</label>
            <div class="input-wrap">
                <i class="fa-solid fa-user f-icon"></i>
                <input class="form-input" type="text" name="username"
                       placeholder="Enter admin username" required autocomplete="off"/>
            </div>
        </div>
        <div class="form-group">
            <label class="form-label">Password</label>
            <div class="input-wrap">
                <i class="fa-solid fa-lock f-icon"></i>
                <input class="form-input" type="password" id="lp" name="password"
                       placeholder="Enter your password" required autocomplete="new-password"/>
                <button type="button" class="toggle-pw" onclick="togglePw()">
                    <i class="fa-solid fa-eye" id="eyeIcon"></i>
                </button>
            </div>
        </div>
        
<div class="forgot-row">
    <a href="forgot_password.php" style="font-size:12px; color:var(--blue-hover); text-decoration:none; font-weight:600;">
        <i class="fa-solid fa-key"></i> Forgot Password?
    </a>
</div>
        <button type="submit" class="btn-main">
            <i class="fa-solid fa-shield-halved"></i> Sign In
        </button>
    </form>

    <div class="register-row">
        Don't have an account? <a href="admin_register.php">Register here</a>
    </div>
    <div class="card-footer">
            <span class="secure-badge"><i class="fa-solid fa-circle-check"></i> Secured &amp; Encrypted Connection</span>
    </div>
</div>

<script>
function togglePw() {
    const el = document.getElementById('lp'), ic = document.getElementById('eyeIcon');
    el.type = el.type === 'password' ? 'text' : 'password';
    ic.className = el.type === 'password' ? 'fa-solid fa-eye' : 'fa-solid fa-eye-slash';
}
</script>
</body>
</html>