<?php
// admin/forgot_password.php
session_start();
require_once 'db.php';

$error   = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');

    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Please enter a valid email address.";
    } else {
        $stmt = $mysqli->prepare(
            "SELECT id, full_name FROM users WHERE email = ? AND role IN ('admin','superadmin','registrar') AND is_active = 1 LIMIT 1"
        );
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $user = $stmt->get_result()->fetch_assoc();
        $stmt->close();

if ($user) {
    $token   = bin2hex(random_bytes(32));
$expires = date('Y-m-d H:i:s', strtotime('+1 hour'));
    $ins = $mysqli->prepare(
        "INSERT INTO password_resets (user_id, token, expires_at) VALUES (?, ?, ?)
         ON DUPLICATE KEY UPDATE token = VALUES(token), expires_at = VALUES(expires_at)"
    );
    $ins->bind_param("iss", $user['id'], $token, $expires);
    $ins->execute();
    $ins->close();

    $reset_link = "http://localhost/index/admin/reset_password.php?token=" . $token;

    @mail($email, 'PBI Admin — Password Reset', "Reset link: $reset_link", "From: no-reply@pandanbay.edu.ph");

    // TEMP: show link directly (remove in production!)
    $success = "Reset link (dev only): <a href='$reset_link' style='color:#16a34a'>Click here to reset</a>";

} else {
    $success = "If that email is registered, a reset link has been sent.";
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
<title>EA System — Forgot Password</title>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@500;600;700;800&family=DM+Sans:wght@400;500;600;700&display=swap" rel="stylesheet"/>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css"/>
<style>
:root{
  --navy:#0b1c3e; --blue:#2563eb; --blue-dark:#1d4ed8; --blue-soft:#eaf1ff;
  --text-dark:#0f172a; --text-gray:#64748b;
  --border:#e2e8f0; --field-bg:#f7f9fc;
  --radius:12px; --shadow:0 24px 60px -12px rgba(15,35,75,.25);
}
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0;}
body{
  min-height:100vh;font-family:'DM Sans',sans-serif;color:var(--text-dark);
  display:flex;align-items:center;justify-content:center;padding:28px;
  position:relative;overflow-x:hidden;background:#eef2f8;
}
.bg-photo{position:fixed;inset:0;background:url('background.png') center/cover no-repeat;opacity:.16;z-index:0;}
.bg-fade{position:fixed;inset:0;background:linear-gradient(180deg,rgba(238,242,248,.94),rgba(238,242,248,.98));z-index:0;}

.card{
  position:relative;z-index:2;width:100%;max-width:420px;
  background:#fff;border-radius:20px;box-shadow:var(--shadow);
  border:1px solid rgba(15,35,75,.05);
  padding:38px 38px 32px;
}
.brand-row{display:flex;align-items:center;gap:12px;margin-bottom:24px;}
.brand-icon{
  width:42px;height:42px;flex-shrink:0;border-radius:11px;background:var(--navy);
  display:flex;align-items:center;justify-content:center;
}
.brand-name{font-family:'Poppins',sans-serif;font-weight:800;font-size:15px;letter-spacing:.4px;color:var(--navy);}
.brand-tag{font-size:10.5px;color:var(--text-gray);letter-spacing:.2px;margin-top:1px;}

.card-header{text-align:center;margin-bottom:22px;}
.icon-wrap{
  width:60px;height:60px;border-radius:50%;background:var(--blue-soft);
  border:2px solid var(--blue);display:flex;align-items:center;justify-content:center;
  margin:0 auto 14px;font-size:24px;color:var(--blue);
}
.card-title{font-family:'Poppins',sans-serif;font-size:21px;font-weight:700;color:var(--text-dark);}
.card-subtitle{font-size:13px;color:var(--text-gray);margin-top:6px;line-height:1.55;}

.alert{border-radius:8px;padding:10px 13px;font-size:13px;margin-bottom:16px;display:flex;align-items:flex-start;gap:8px;text-align:left;}
.alert-error{background:#fef2f2;border:1px solid #fecaca;color:#dc2626;}
.alert-success{background:#f0fdf4;border:1px solid #bbf7d0;color:#16a34a;}
.alert-success a{color:#16a34a;font-weight:700;}

.form-group{margin-bottom:18px;text-align:left;}
.form-label{display:block;font-size:12.5px;font-weight:700;color:var(--text-dark);margin-bottom:7px;}
.input-wrap{position:relative;}
.f-icon{position:absolute;left:14px;top:50%;transform:translateY(-50%);color:#94a3b8;font-size:14px;pointer-events:none;}
.form-input{
  width:100%;padding:13px 14px 13px 40px;background:var(--field-bg);
  border:1.5px solid var(--border);border-radius:var(--radius);
  font-size:14px;font-family:'DM Sans',sans-serif;color:var(--text-dark);
  outline:none;transition:border-color .2s,box-shadow .2s,background .2s;
}
.form-input::placeholder{color:#98a2b3;}
.form-input:focus{border-color:var(--blue);background:#fff;box-shadow:0 0 0 4px rgba(37,99,235,.12);}

.btn-main{
  width:100%;padding:14px;background:var(--blue);border:none;border-radius:var(--radius);
  color:#fff;font-size:15px;font-weight:700;font-family:'DM Sans',sans-serif;
  cursor:pointer;transition:background .2s,transform .15s;
  display:flex;align-items:center;justify-content:center;gap:9px;
  box-shadow:0 10px 24px -6px rgba(37,99,235,.55);
}
.btn-main:hover{background:var(--blue-dark);transform:translateY(-1px);}

.back-row{text-align:center;margin-top:20px;font-size:13.5px;color:var(--text-gray);}
.back-row a{color:var(--blue);font-weight:700;text-decoration:none;}
.back-row a:hover{text-decoration:underline;}
</style>

<!-- Admin text color override: keep standard page text black for readability. -->
<style id="admin-black-text-override">
  body { color:#000 !important; }
  body p, body span, body label, body li, body td, body th,
  body h1, body h2, body h3, body h4, body h5, body h6,
  body .page-title, body .page-header, body .page-header *,
  body .page-sub, body .subtitle, body .description, body .helper,
  body .muted, body .hint, body .section-title, body .section-heading,
  body .card-title, body .card-subtitle, body .form-label,
  body .table-title, body .table-subtitle { color:#000 !important; }
  body a:not(.btn):not(.button):not([class*="btn-"]) { color:#000 !important; }
  body input, body select, body textarea { color:#000 !important; }
  body input::placeholder, body textarea::placeholder { color:#555 !important; }
</style>

<style id="admin-global-black-text">
/* Global admin text treatment: normal interface text is black throughout the admin side.
   Intentional semantic colors on buttons, badges, alerts, icons, and status indicators are preserved. */
body { color:#000 !important; }
body p, body h1, body h2, body h3, body h4, body h5, body h6,
body label, body li, body td, body th, body dt, body dd,
body .page-title, body .page-header, body .page-header p, body .page-sub,
body .subtitle, body .description, body .helper, body .hint, body .muted,
body .section-title, body .section-heading, body .card-title, body .card-subtitle,
body .table-title, body .table-subtitle, body .form-label, body .modal-title, body .modal-sub,
body .empty-state, body .empty-cta, body .field-label, body .stat-label, body .stat-value,
body .back, body .back-btn, body .nav-link, body .sidebar-text, body .content-text { color:#000 !important; }
body a:not(.btn):not(.button):not([class*="btn-"]):not(.badge):not(.status):not(.nav-item) { color:#000 !important; }
body input, body select, body textarea { color:#000 !important; }
body input::placeholder, body textarea::placeholder { color:#555 !important; }
</style>

<!-- Scoped overrides so intentional semantic colors (brand navy, gray subtitles,
     blue links/buttons, alert colors) survive the black-text rules above. -->
<style id="ea-scoped-color-overrides">
.brand-name, .card-title { color:var(--navy) !important; }
.brand-tag, .card-subtitle, .back-row { color:var(--text-gray) !important; }
.back-row a { color:var(--blue) !important; }
.btn-main, .btn-main * { color:#fff !important; }
.alert-error, .alert-error * { color:#dc2626 !important; }
.alert-success, .alert-success * { color:#16a34a !important; }
</style>
</head>
<body>
<div class="bg-photo"></div>
<div class="bg-fade"></div>

<div class="card">
    <div class="brand-row">
        <div class="brand-icon">
            <svg width="20" height="20" viewBox="0 0 64 64" fill="none">
                <path d="M32 16 C25 11 15 10 7 12 V46 C15 44 25 45 32 50 C39 45 49 44 57 46 V12 C49 10 39 11 32 16 Z" stroke="#fff" stroke-width="3" stroke-linejoin="round"/>
                <line x1="32" y1="16" x2="32" y2="50" stroke="#fff" stroke-width="3"/>
                <circle cx="32" cy="27" r="7" fill="#fff"/>
            </svg>
        </div>
        <div>
            <div class="brand-name">EA SYSTEM</div>
            <div class="brand-tag">Evaluation &amp; Assessment System</div>
        </div>
    </div>

    <div class="card-header">
        <div class="icon-wrap"><i class="fa-solid fa-key"></i></div>
        <div class="card-title">Forgot Password</div>
        <div class="card-subtitle">Enter your registered email and we'll send you a reset link.</div>
    </div>

    <?php if ($error): ?>
    <div class="alert alert-error"><i class="fa-solid fa-circle-exclamation"></i><span><?= $error ?></span></div>
    <?php endif; ?>
    <?php if ($success): ?>
    <div class="alert alert-success"><i class="fa-solid fa-circle-check"></i><span><?= $success ?></span></div>
    <?php endif; ?>

    <form method="POST" action="forgot_password.php">
        <div class="form-group">
            <label class="form-label">Email Address</label>
            <div class="input-wrap">
                <i class="fa-solid fa-envelope f-icon"></i>
                <input class="form-input" type="email" name="email" placeholder="Enter your registered email" required/>
            </div>
        </div>
        <button type="submit" class="btn-main">
            <i class="fa-solid fa-paper-plane"></i> Send Reset Link
        </button>
    </form>

    <div class="back-row">
        <a href="admin_login.php"><i class="fa-solid fa-arrow-left"></i> Back to Login</a>
    </div>
</div>
</body>
</html>
