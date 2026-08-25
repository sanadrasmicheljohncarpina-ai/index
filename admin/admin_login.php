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
<title>EA System — Admin Login</title>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@500;600;700;800&family=DM+Sans:wght@400;500;600;700&display=swap" rel="stylesheet"/>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css"/>
<style>
:root{
  --navy:#0b1c3e; --navy-2:#132c5c; --navy-3:#081327;
  --blue:#2563eb; --blue-dark:#1d4ed8; --blue-soft:#eaf1ff;
  --text-dark:#0f172a; --text-gray:#64748b;
  --border:#e2e8f0; --field-bg:#f7f9fc;
  --radius:12px; --shadow:0 24px 60px -12px rgba(15,35,75,.25);
}
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0;}
body{font-family:'DM Sans',sans-serif;color:var(--text-dark);}

.page{min-height:100vh;display:flex;}

/* ===== LEFT BRAND PANEL ===== */
.panel-left{
  flex:0 0 44%; position:relative; overflow:hidden;
  background:linear-gradient(160deg,var(--navy) 0%,var(--navy-2) 55%,var(--navy-3) 100%);
  padding:48px 52px 0; display:flex; flex-direction:column;
}
.panel-left,.panel-left *{color:#fff;}
.wave{position:absolute;left:0;bottom:0;width:100%;line-height:0;z-index:0;}
.wave svg{width:100%;height:auto;display:block;}

.brand-row{display:flex;align-items:center;gap:14px;position:relative;z-index:2;}
.brand-icon{
  width:52px;height:52px;flex-shrink:0;border-radius:14px;
  background:rgba(255,255,255,.06); border:1.5px solid rgba(255,255,255,.35);
  display:flex;align-items:center;justify-content:center;
}
.brand-name{font-family:'Poppins',sans-serif;font-weight:800;font-size:22px;letter-spacing:.5px;line-height:1.1;}
.brand-tag{font-size:12px;color:#a9c0e8 !important;letter-spacing:.3px;margin-top:2px;}

.welcome-block{position:relative;z-index:2;margin-top:56px;max-width:400px;}
.welcome-block h1{font-family:'Poppins',sans-serif;font-weight:800;font-size:32px;margin-bottom:14px;}
.welcome-block p{font-size:14.5px;line-height:1.7;color:#c3d3ef !important;}

.illustration{position:relative;z-index:2;flex:1;display:flex;align-items:center;justify-content:center;min-height:180px;margin-top:12px;}
.illustration svg{width:100%;max-width:340px;height:auto;}

.quote-block{position:relative;z-index:2;padding-bottom:38px;max-width:380px;}
.quote-mark{font-family:'Poppins',sans-serif;font-size:34px;color:#5c7cbd !important;line-height:.4;display:block;margin-bottom:6px;}
.quote-block p{font-family:'Poppins',sans-serif;font-weight:600;font-size:16px;line-height:1.5;}

/* ===== RIGHT FORM PANEL ===== */
.panel-right{
  flex:1; position:relative; display:flex; align-items:center; justify-content:center;
  padding:40px 24px; background:#eef2f8; overflow:hidden;
}
.panel-right .bg-photo{
  position:absolute;inset:0;background:url('background.png') center/cover no-repeat;
  opacity:.16; filter:saturate(1.05);
}
.panel-right .bg-fade{
  position:absolute;inset:0;
  background:linear-gradient(180deg,rgba(238,242,248,.94),rgba(238,242,248,.98));
}

.form-card{
  position:relative;z-index:2; width:100%; max-width:440px;
  background:#fff; border-radius:20px; box-shadow:var(--shadow);
  border:1px solid rgba(15,35,75,.05);
  padding:40px 40px 34px;
}

.card-head{display:flex;align-items:center;gap:16px;margin-bottom:26px;}
.card-avatar{
  width:56px;height:56px;flex-shrink:0;border-radius:50%;
  background:var(--blue-soft); border:2px solid var(--blue);
  display:flex;align-items:center;justify-content:center;color:var(--blue);font-size:22px;
}
.card-head h1{font-family:'Poppins',sans-serif;font-weight:700;font-size:22px;}
.card-head p{font-size:13px;color:var(--text-gray);margin-top:2px;}

.alert{border-radius:8px;padding:10px 13px;font-size:13px;margin-bottom:16px;display:flex;align-items:center;gap:8px;}
.alert-error{background:#fef2f2;border:1px solid #fecaca;color:#dc2626 !important;}
.alert-error *{color:#dc2626 !important;}
.alert-success{background:#f0fdf4;border:1px solid #bbf7d0;color:#16a34a !important;}
.alert-success *{color:#16a34a !important;}

.form-group{margin-bottom:17px;}
.form-label{display:block;font-size:13px;font-weight:700;margin-bottom:7px;}
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
.toggle-pw{position:absolute;right:14px;top:50%;transform:translateY(-50%);background:none;border:none;color:#94a3b8;cursor:pointer;font-size:14px;padding:2px;}
.toggle-pw:hover{color:var(--text-dark);}

.row-between{display:flex;align-items:center;justify-content:space-between;margin:2px 0 22px;font-size:13px;}
.remember{display:flex;align-items:center;gap:8px;color:var(--text-gray) !important;cursor:pointer;user-select:none;}
.remember input{width:16px;height:16px;accent-color:var(--blue);cursor:pointer;}
.forgot-password-link{color:var(--blue) !important;text-decoration:none;font-weight:600;}
.forgot-password-link:hover{text-decoration:underline;}

.btn-main{
  width:100%;padding:14px;background:var(--blue);border:none;border-radius:var(--radius);
  color:#fff !important;font-size:15px;font-weight:700;font-family:'DM Sans',sans-serif;
  cursor:pointer;transition:background .2s,transform .15s;
  display:flex;align-items:center;justify-content:center;gap:9px;
  box-shadow:0 10px 24px -6px rgba(37,99,235,.55);
}
.btn-main *{color:#fff !important;}
.btn-main:hover{background:var(--blue-dark);transform:translateY(-1px);}

.divider-row{display:flex;align-items:center;gap:12px;margin:22px 0;}
.divider-row .line{flex:1;height:1px;background:var(--border);}
.divider-row span{font-size:12px;color:var(--text-gray) !important;}

.btn-sso{
  width:100%;padding:13px;background:#fff;border:1.5px solid var(--border);border-radius:var(--radius);
  color:var(--blue) !important;font-size:14px;font-weight:700;font-family:'DM Sans',sans-serif;
  cursor:not-allowed;opacity:.6;display:flex;align-items:center;justify-content:center;gap:9px;
}
.btn-sso *{color:var(--blue) !important;}

.register-row{text-align:center;margin-top:22px;font-size:13.5px;color:var(--text-gray) !important;}
.register-row a{color:var(--blue) !important;font-weight:700;text-decoration:none;}
.register-row a:hover{text-decoration:underline;}
.card-footer{text-align:center;font-size:12px;color:#9aa5b5 !important;margin-top:20px;}

@media(max-width:920px){
  .panel-left{display:none;}
  .panel-right{padding:32px 18px;}
}
@media(max-width:480px){
  .form-card{padding:30px 24px 26px;}
}
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

<!-- Left panel + form-card need to override the black-text rules above so the
     navy brand panel stays white and card copy keeps its intended color. These
     class-scoped rules are more specific than the "body ..." rules, so they win. -->
<style id="ea-scoped-color-overrides">
.panel-left, .panel-left h1, .panel-left h2, .panel-left p, .panel-left span,
.panel-left div, .panel-left .brand-name, .panel-left .card-title { color:#fff !important; }
.panel-left .brand-tag, .panel-left .welcome-block p { color:#c3d3ef !important; }
.panel-left .quote-mark { color:#5c7cbd !important; }
.form-card .card-head p, .form-card .divider-row span, .form-card .register-row,
.form-card .remember { color:var(--text-gray) !important; }
.form-card .register-row a, .form-card .forgot-password-link,
.form-card .btn-sso, .form-card .btn-sso * { color:var(--blue) !important; }
.form-card .btn-main, .form-card .btn-main * { color:#fff !important; }
.form-card .alert-error, .form-card .alert-error * { color:#dc2626 !important; }
.form-card .alert-success, .form-card .alert-success * { color:#16a34a !important; }
</style>
</head>
<body>

<div class="page">

  <!-- ===== LEFT BRAND PANEL ===== -->
  <div class="panel-left">
    <div class="brand-row">
      <div class="brand-icon">
        <svg width="26" height="26" viewBox="0 0 64 64" fill="none">
          <path d="M32 16 C25 11 15 10 7 12 V46 C15 44 25 45 32 50 C39 45 49 44 57 46 V12 C49 10 39 11 32 16 Z" stroke="#fff" stroke-width="2.4" stroke-linejoin="round"/>
          <line x1="32" y1="16" x2="32" y2="50" stroke="#fff" stroke-width="2.4"/>
          <circle cx="32" cy="27" r="6.5" fill="#fff"/>
          <path d="M23 39c2.5-4.2 6.3-6.2 9-6.2s6.5 2 9 6.2" stroke="#fff" stroke-width="2.4" fill="none" stroke-linecap="round"/>
        </svg>
      </div>
      <div>
        <div class="brand-name">EA SYSTEM</div>
        <div class="brand-tag">Evaluation &amp; Assessment System</div>
      </div>
    </div>

    <div class="welcome-block">
      <h1>Welcome Back!</h1>
      <p>Log in to your account to continue managing evaluations, questionnaires, and analytics.</p>
    </div>

    <div class="illustration">
      <svg viewBox="0 0 360 260" fill="none" xmlns="http://www.w3.org/2000/svg">
        <ellipse cx="180" cy="232" rx="150" ry="10" fill="#04122b"/>
        <!-- plant -->
        <g>
          <path d="M52 210c-4-22-2-40 10-52 8 14 10 32 4 52z" fill="#2f8f66"/>
          <path d="M52 210c-10-18-24-28-40-30 2 16 14 28 30 34z" fill="#256f4f"/>
          <rect x="34" y="206" width="36" height="26" rx="4" fill="#8fa3c7"/>
          <rect x="30" y="200" width="44" height="10" rx="3" fill="#a9bade"/>
        </g>
        <!-- books -->
        <g>
          <rect x="270" y="176" width="70" height="14" rx="2" fill="#3a5da0" transform="rotate(-3 270 176)"/>
          <rect x="266" y="190" width="76" height="14" rx="2" fill="#4c78c2" transform="rotate(2 266 190)"/>
          <rect x="270" y="204" width="70" height="16" rx="2" fill="#dfe7f5" transform="rotate(-1 270 204)"/>
        </g>
        <!-- laptop -->
        <g>
          <rect x="96" y="72" width="168" height="112" rx="10" fill="#13294f"/>
          <rect x="106" y="82" width="148" height="92" rx="4" fill="#2a5bd7"/>
          <circle cx="180" cy="112" r="14" fill="#dfe7f5"/>
          <path d="M164 132c4-9 10-13 16-13s12 4 16 13z" fill="#dfe7f5"/>
          <rect x="140" y="150" width="80" height="7" rx="3.5" fill="#7fa1ea"/>
          <rect x="152" y="163" width="56" height="7" rx="3.5" fill="#7fa1ea"/>
          <path d="M84 184h192l14 30a8 8 0 01-8 11H78a8 8 0 01-8-11z" fill="#c7d2e6"/>
          <rect x="150" y="184" width="60" height="6" rx="3" fill="#9fb0cf"/>
        </g>
      </svg>
    </div>

    <div class="quote-block">
      <span class="quote-mark">&ldquo;</span>
      <p>Empowering Better Evaluations, Building a Better Institution.</p>
    </div>

    <div class="wave">
      <svg viewBox="0 0 600 140" preserveAspectRatio="none" xmlns="http://www.w3.org/2000/svg">
        <path d="M0,70 C120,120 240,20 360,55 C460,85 540,110 600,70 L600,140 L0,140 Z" fill="rgba(255,255,255,0.05)"/>
        <path d="M0,95 C130,50 260,130 400,90 C480,68 540,95 600,80 L600,140 L0,140 Z" fill="rgba(255,255,255,0.09)"/>
      </svg>
    </div>
  </div>

  <!-- ===== RIGHT FORM PANEL ===== -->
  <div class="panel-right">
    <div class="bg-photo"></div>
    <div class="bg-fade"></div>

    <div class="form-card">
      <div class="card-head">
        <div class="card-avatar"><i class="fa-solid fa-user"></i></div>
        <div>
          <h1>Login to Your Account</h1>
          <p>Please enter your admin credentials to continue.</p>
        </div>
      </div>

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
                         placeholder="Enter your admin username" required autocomplete="off"/>
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

          <div class="row-between">
              <label class="remember">
                  <input type="checkbox" name="remember"/> Remember me
              </label>
              <a class="forgot-password-link" href="forgot_password.php">Forgot Password?</a>
          </div>

          <button type="submit" class="btn-main">
              <i class="fa-solid fa-right-to-bracket"></i> Login
          </button>
      </form>

      <div class="divider-row"><div class="line"></div><span>or</span><div class="line"></div></div>

      <button type="button" class="btn-sso" disabled title="SSO login is not configured for this system yet.">
          <i class="fa-solid fa-shield-halved"></i> Login with SSO
      </button>

      <div class="register-row">
          Don't have an account? <a href="admin_register.php">Register here</a>
      </div>
      <div class="card-footer">
          &copy; <?= date('Y') ?> EA System &mdash; Pandan Bay Institute. All rights reserved.
      </div>
    </div>
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
