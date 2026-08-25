<?php
// admin/admin_register.php
session_start();
require_once 'db.php';   // provides $mysqli + UPLOAD_DIR + UPLOAD_URL

// ── REGISTRATION LOCK ────────────────────────────────────────
// Once a superadmin account exists, this page closes itself automatically.
// To re-open it later (e.g. lost access, handing off to a second person),
// run this SQL once, register the new account, then flip it back to 0:
//   UPDATE system_settings SET setting_value=1 WHERE setting_key='superadmin_reg_open';
//   UPDATE system_settings SET setting_value=0 WHERE setting_key='superadmin_reg_open';
$reg_open = 0;
$tbl_check = $mysqli->query("SHOW TABLES LIKE 'system_settings'");
if ($tbl_check && $tbl_check->num_rows > 0) {
    $flag = $mysqli->query("SELECT setting_value FROM system_settings WHERE setting_key='superadmin_reg_open'");
    if ($flag && $flag->num_rows > 0) {
        $reg_open = (int)$flag->fetch_assoc()['setting_value'];
    }
}

$count_res = $mysqli->query("SELECT COUNT(*) as c FROM users WHERE role = 'superadmin'");
$superadmin_count = $count_res ? (int)$count_res->fetch_assoc()['c'] : 0;

if ($superadmin_count > 0 && !$reg_open) {
    http_response_code(403);
    die("Registration is closed. Contact your System Administrator.");
}

$error   = "";
$success = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $first_name = trim($_POST['first_name']       ?? '');
    $last_name  = trim($_POST['last_name']        ?? '');
    $email      = trim($_POST['email']            ?? '');
    $username   = trim($_POST['username']         ?? '');
    $password   = $_POST['password']              ?? '';
    $confirm    = $_POST['confirm_password']      ?? '';

    // ── VALIDATION ────────────────────────────────────────────
    if (empty($first_name) || empty($last_name) || empty($email) || empty($username) || empty($password)) {
        $error = "Please fill in all required fields.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Please enter a valid email address.";
    } elseif (strlen($password) < 8) {
        $error = "Password must be at least 8 characters.";
    } elseif ($password !== $confirm) {
        $error = "Passwords do not match.";
    } else {
        $chk = $mysqli->prepare("SELECT id FROM users WHERE username = ? OR email = ? LIMIT 1");
        $chk->bind_param("ss", $username, $email);
        $chk->execute();
        $chk->store_result();
        if ($chk->num_rows > 0) $error = "Username or email is already in use.";
        $chk->close();
    }

    // ── PHOTO UPLOAD ──────────────────────────────────────────
    $photo_filename = null;
    if (empty($error) && isset($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
        $allowed_types = ['image/jpeg','image/png','image/webp','image/gif'];
        $file_type     = mime_content_type($_FILES['photo']['tmp_name']);
        $file_size     = $_FILES['photo']['size'];

        if (!in_array($file_type, $allowed_types)) {
            $error = "Profile photo must be JPG, PNG, WebP, or GIF.";
        } elseif ($file_size > 10 * 1024 * 1024) {
            $error = "Profile photo must be under 10 MB.";
        } else {
            $ext            = pathinfo($_FILES['photo']['name'], PATHINFO_EXTENSION);
            $photo_filename = uniqid('adm_', true) . '.' . strtolower($ext);
            if (!is_dir(UPLOAD_DIR)) mkdir(UPLOAD_DIR, 0755, true);
            if (!move_uploaded_file($_FILES['photo']['tmp_name'], UPLOAD_DIR . $photo_filename)) {
                $error = "Failed to save photo. Check folder permissions on /image/.";
                $photo_filename = null;
            }
        }
    }

    // ── INSERT ────────────────────────────────────────────────
    if (empty($error)) {
        $full_name = $first_name . ' ' . $last_name;
        $hash      = password_hash($password, PASSWORD_DEFAULT);

        $ins = $mysqli->prepare(
            "INSERT INTO users (full_name, email, username, password_hash, photo, role, is_active, created_at)
             VALUES (?, ?, ?, ?, ?, 'superadmin', 1, NOW())"
        );
        $ins->bind_param("sssss", $full_name, $email, $username, $hash, $photo_filename);

        if ($ins->execute()) {
            $ins->close();
            $mysqli->close();
            $_SESSION['reg_success'] = "Admin account created! You can now sign in.";
            header("Location: admin_login.php");
            exit;
        } else {
            $error = "Registration failed: " . $mysqli->error;
        }
        $ins->close();
    }
    if ($mysqli->ping()) $mysqli->close();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"/>
<meta name="viewport" content="width=device-width, initial-scale=1.0"/>
<title>EA System — Create Admin Account</title>
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

.illustration{position:relative;z-index:2;flex:1;display:flex;align-items:center;justify-content:center;min-height:140px;margin-top:12px;}
.illustration svg{width:100%;max-width:300px;height:auto;}

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
  position:relative;z-index:2; width:100%; max-width:520px;
  background:#fff; border-radius:20px; box-shadow:var(--shadow);
  border:1px solid rgba(15,35,75,.05);
  padding:38px 40px 32px;
}

.card-head{display:flex;align-items:center;gap:16px;margin-bottom:22px;}
.card-avatar{
  width:56px;height:56px;flex-shrink:0;border-radius:50%;
  background:var(--blue-soft); border:2px solid var(--blue);
  display:flex;align-items:center;justify-content:center;color:var(--blue);font-size:22px;
}
.card-head h1{font-family:'Poppins',sans-serif;font-weight:700;font-size:21px;}
.card-head p{font-size:13px;color:var(--text-gray);margin-top:2px;}

.alert{border-radius:8px;padding:10px 13px;font-size:13px;margin-bottom:16px;display:flex;align-items:flex-start;gap:8px;}
.alert-error{background:#fef2f2;border:1px solid #fecaca;color:#dc2626 !important;}
.alert-error *{color:#dc2626 !important;}

/* Photo upload */
.photo-upload-area{display:flex;align-items:center;gap:16px;margin-bottom:18px;}
.photo-preview{
  width:74px;height:74px;border-radius:50%;background:var(--field-bg);
  border:2px dashed #b9c6dd; overflow:hidden;
  display:flex;align-items:center;justify-content:center;cursor:pointer;
  transition:border-color .2s; flex-shrink:0;
}
.photo-preview:hover{border-color:var(--blue);}
.photo-preview img{width:100%;height:100%;object-fit:cover;display:none;}
.photo-preview .ph-icon{color:#94a3b8;font-size:22px;}
.photo-info p{font-size:13px;font-weight:700;margin-bottom:3px;}
.photo-info span{font-size:11.5px;color:var(--text-gray) !important;}
.btn-photo{
  display:inline-flex;align-items:center;gap:6px;
  background:var(--blue-soft);border:1px solid #bcd3fb;
  color:var(--blue) !important;padding:6px 13px;border-radius:7px;
  font-size:12px;font-weight:700;cursor:pointer;transition:all .2s;margin-top:7px;
}
.btn-photo:hover{background:#dbe8fe;}
input[type="file"]{display:none;}

/* Form */
.form-row{display:grid;grid-template-columns:1fr 1fr;gap:14px;}
.form-group{margin-bottom:15px;}
.form-label{display:block;font-size:12.5px;font-weight:700;margin-bottom:6px;}
.required{color:#dc2626 !important;margin-left:2px;}
.input-wrap{position:relative;}
.f-icon{position:absolute;left:14px;top:50%;transform:translateY(-50%);color:#94a3b8;font-size:13px;pointer-events:none;}
.form-input{
  width:100%;padding:12px 13px 12px 38px;background:var(--field-bg);
  border:1.5px solid var(--border);border-radius:var(--radius);
  font-size:13.5px;font-family:'DM Sans',sans-serif;color:var(--text-dark);
  outline:none;transition:border-color .2s,box-shadow .2s,background .2s;
}
.form-input::placeholder{color:#98a2b3;}
.form-input:focus{border-color:var(--blue);background:#fff;box-shadow:0 0 0 4px rgba(37,99,235,.12);}
.toggle-pw{position:absolute;right:13px;top:50%;transform:translateY(-50%);background:none;border:none;color:#94a3b8;cursor:pointer;font-size:13px;padding:2px;}
.toggle-pw:hover{color:var(--text-dark);}

.pw-strength{height:3px;border-radius:2px;margin-top:6px;transition:all .3s;background:#e5eaf1;}
.pw-hint{font-size:11px;color:var(--text-gray) !important;margin-top:4px;}

.btn-main{
  width:100%;padding:14px;background:var(--blue);border:none;border-radius:var(--radius);
  color:#fff !important;font-size:15px;font-weight:700;font-family:'DM Sans',sans-serif;
  cursor:pointer;transition:background .2s,transform .15s;
  display:flex;align-items:center;justify-content:center;gap:9px;margin-top:4px;
  box-shadow:0 10px 24px -6px rgba(37,99,235,.55);
}
.btn-main *{color:#fff !important;}
.btn-main:hover{background:var(--blue-dark);transform:translateY(-1px);}

.login-row{text-align:center;margin-top:20px;font-size:13.5px;color:var(--text-gray) !important;}
.login-row a{color:var(--blue) !important;font-weight:700;text-decoration:none;}
.login-row a:hover{text-decoration:underline;}

@media(max-width:920px){
  .panel-left{display:none;}
  .panel-right{padding:32px 18px;}
}
@media(max-width:560px){
  .form-card{padding:28px 20px 24px;}
  .form-row{grid-template-columns:1fr;}
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
.form-card .card-head p, .form-card .photo-info span, .form-card .pw-hint,
.form-card .login-row { color:var(--text-gray) !important; }
.form-card .login-row a, .form-card .btn-photo, .form-card .btn-photo * { color:var(--blue) !important; }
.form-card .btn-main, .form-card .btn-main * { color:#fff !important; }
.form-card .alert-error, .form-card .alert-error * { color:#dc2626 !important; }
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
      <h1>Create Your Account</h1>
      <p>Set up an administrator account to start managing evaluations, questionnaires, and analytics.</p>
    </div>

    <div class="illustration">
      <svg viewBox="0 0 360 260" fill="none" xmlns="http://www.w3.org/2000/svg">
        <ellipse cx="180" cy="232" rx="150" ry="10" fill="#04122b"/>
        <g>
          <path d="M52 210c-4-22-2-40 10-52 8 14 10 32 4 52z" fill="#2f8f66"/>
          <path d="M52 210c-10-18-24-28-40-30 2 16 14 28 30 34z" fill="#256f4f"/>
          <rect x="34" y="206" width="36" height="26" rx="4" fill="#8fa3c7"/>
          <rect x="30" y="200" width="44" height="10" rx="3" fill="#a9bade"/>
        </g>
        <g>
          <rect x="270" y="176" width="70" height="14" rx="2" fill="#3a5da0" transform="rotate(-3 270 176)"/>
          <rect x="266" y="190" width="76" height="14" rx="2" fill="#4c78c2" transform="rotate(2 266 190)"/>
          <rect x="270" y="204" width="70" height="16" rx="2" fill="#dfe7f5" transform="rotate(-1 270 204)"/>
        </g>
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
        <div class="card-avatar"><i class="fa-solid fa-user-plus"></i></div>
        <div>
          <h1>Create Admin Account</h1>
          <p>Fill in the details below to register.</p>
        </div>
      </div>

      <?php if ($error): ?>
      <div class="alert alert-error">
          <i class="fa-solid fa-circle-exclamation" style="flex-shrink:0;margin-top:1px"></i>
          <span><?= htmlspecialchars($error) ?></span>
      </div>
      <?php endif; ?>

      <form method="POST" action="admin_register.php" enctype="multipart/form-data" autocomplete="off">

          <!-- Profile Photo -->
          <div class="photo-upload-area">
              <div class="photo-preview" id="photoPreview" onclick="document.getElementById('photoFile').click()">
                  <img id="photoImg" src="" alt="Preview"/>
                  <i class="fa-solid fa-camera ph-icon" id="phIcon"></i>
              </div>
              <div class="photo-info">
                  <p>Profile Photo</p>
                  <span>Shown on the admin panel &amp; audit logs — max 10 MB</span><br>
                  <label class="btn-photo" for="photoFile">
                      <i class="fa-solid fa-upload"></i> Upload Photo
                  </label>
                  <input type="file" id="photoFile" name="photo"
                         accept="image/jpeg,image/png,image/webp,image/gif"
                         onchange="previewPhoto(this)"/>
              </div>
          </div>

          <!-- Name -->
          <div class="form-row">
              <div class="form-group">
                  <label class="form-label">First Name<span class="required">*</span></label>
                  <div class="input-wrap">
                      <i class="fa-solid fa-user f-icon"></i>
                      <input class="form-input" type="text" name="first_name" placeholder="Juan"
                             value="<?= htmlspecialchars($_POST['first_name'] ?? '') ?>" required/>
                  </div>
              </div>
              <div class="form-group">
                  <label class="form-label">Last Name<span class="required">*</span></label>
                  <div class="input-wrap">
                      <i class="fa-solid fa-user f-icon"></i>
                      <input class="form-input" type="text" name="last_name" placeholder="dela Cruz"
                             value="<?= htmlspecialchars($_POST['last_name'] ?? '') ?>" required/>
                  </div>
              </div>
          </div>

          <!-- Email -->
          <div class="form-group">
              <label class="form-label">Email Address<span class="required">*</span></label>
              <div class="input-wrap">
                  <i class="fa-solid fa-envelope f-icon"></i>
                  <input class="form-input" type="email" name="email" placeholder="admin@pandanbay.edu.ph"
                         value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" required/>
              </div>
          </div>

          <!-- Username -->
          <div class="form-group">
              <label class="form-label">Username<span class="required">*</span></label>
              <div class="input-wrap">
                  <i class="fa-solid fa-at f-icon"></i>
                  <input class="form-input" type="text" name="username" placeholder="Choose a username"
                         value="<?= htmlspecialchars($_POST['username'] ?? '') ?>" required autocomplete="off"/>
              </div>
          </div>

          <!-- Password -->
          <div class="form-row">
              <div class="form-group">
                  <label class="form-label">Password<span class="required">*</span></label>
                  <div class="input-wrap">
                      <i class="fa-solid fa-lock f-icon"></i>
                      <input class="form-input" type="password" id="pw" name="password"
                             placeholder="Min. 8 characters" required oninput="checkStrength(this.value)"/>
                      <button type="button" class="toggle-pw" onclick="togglePw('pw','eye1')">
                          <i class="fa-solid fa-eye" id="eye1"></i>
                      </button>
                  </div>
                  <div class="pw-strength" id="pw-bar"></div>
                  <div class="pw-hint"     id="pw-hint"></div>
              </div>
              <div class="form-group">
                  <label class="form-label">Confirm Password<span class="required">*</span></label>
                  <div class="input-wrap">
                      <i class="fa-solid fa-lock f-icon"></i>
                      <input class="form-input" type="password" id="pw2" name="confirm_password"
                             placeholder="Repeat password" required/>
                      <button type="button" class="toggle-pw" onclick="togglePw('pw2','eye2')">
                          <i class="fa-solid fa-eye" id="eye2"></i>
                      </button>
                  </div>
              </div>
          </div>

          <button type="submit" class="btn-main">
              <i class="fa-solid fa-user-plus"></i> Create Admin Account
          </button>
      </form>

      <div class="login-row">
          Already have an account? <a href="admin_login.php">Sign in here</a>
      </div>
    </div>
  </div>

</div>

<script>
function togglePw(id, ic) {
    const el = document.getElementById(id), i = document.getElementById(ic);
    el.type = el.type === 'password' ? 'text' : 'password';
    i.className = el.type === 'password' ? 'fa-solid fa-eye' : 'fa-solid fa-eye-slash';
}
function previewPhoto(input) {
    if (input.files && input.files[0]) {
        const r = new FileReader();
        r.onload = e => {
            const img = document.getElementById('photoImg');
            const ic  = document.getElementById('phIcon');
            img.src = e.target.result;
            img.style.display = 'block';
            ic.style.display  = 'none';
        };
        r.readAsDataURL(input.files[0]);
    }
}
function checkStrength(val) {
    const bar = document.getElementById('pw-bar'), hint = document.getElementById('pw-hint');
    let score = 0;
    if (val.length >= 8)           score++;
    if (/[A-Z]/.test(val))         score++;
    if (/[0-9]/.test(val))         score++;
    if (/[^A-Za-z0-9]/.test(val))  score++;
    const colors = ['#ff4444','#ff8800','#f0c040','#16a34a'];
    const labels = ['Weak','Fair','Good','Strong'];
    if (!val) { bar.style.background = '#e5eaf1'; hint.textContent = ''; return; }
    bar.style.background = colors[score - 1] || colors[0];
    hint.textContent     = labels[score - 1] || 'Weak';
    hint.style.color     = colors[score - 1] || colors[0];
}
</script>
</body>
</html>
