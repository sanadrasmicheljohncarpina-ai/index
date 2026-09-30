<?php
// admin/admin_register.php
session_start();
require_once 'db.php';   // provides $mysqli + UPLOAD_DIR + UPLOAD_URL

// ── REGISTRATION LOCK ────────────────────────────────────────
// Once a superadmin account exists, this page closes itself automatically.
// To re-open it later (e.g. lost access, handing off to a second person),
// run this SQL once, register the new account, then flip it back to 0:
//   Set superadmin_reg_open=1 to keep registration enabled, or 0 to close it.
$reg_open = 1;
$tbl_check = $mysqli->query("SHOW TABLES LIKE 'system_settings'");
if ($tbl_check && $tbl_check->num_rows > 0) {
    $flag = $mysqli->query("SELECT setting_value FROM system_settings WHERE setting_key='superadmin_reg_open'");
    if ($flag && $flag->num_rows > 0) {
        // Registration is intentionally enabled in this build.
        // Keep it enabled even if an older database still contains a 0 flag.
        $reg_open = 1;
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

    $full_name  = trim($_POST['full_name']        ?? '');
    $email      = trim($_POST['email']            ?? '');
    $username   = trim($_POST['username']         ?? '');
    $password   = $_POST['password']              ?? '';
    $confirm    = $_POST['confirm_password']      ?? '';

    // ── VALIDATION ────────────────────────────────────────────
    if (empty($full_name) || empty($email) || empty($username) || empty($password)) {
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
<title>PBI Admin — Create Account</title>
<link href="https://fonts.googleapis.com/css2?family=Rajdhani:wght@600;700&family=DM+Sans:wght@400;500;600&display=swap" rel="stylesheet"/>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css"/>
<style>
:root{
    --dark-blue:#14100A;
    --blue-mid:#EFE6D0;
    --blue-inner:#E9DFC6;
    --blue-accent:#C9A227;
    --blue-hover:#9C7A12;
    --light:#2B2416;
    --muted:#7A6F58;
    --radius:10px;
    --shadow:0 8px 32px rgba(120,100,60,.18);
}
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
html{min-height:100%;background:var(--dark-blue)}
body{
    min-height:100vh;
    background:
        radial-gradient(circle at 10% 10%,rgba(201,162,39,.16),transparent 32%),
        radial-gradient(circle at 90% 90%,rgba(240,202,90,.11),transparent 30%),
        var(--dark-blue);
    background-attachment:fixed;
    font-family:'DM Sans',sans-serif;color:var(--light);
    display:flex;align-items:center;justify-content:center;
    padding:24px;position:relative;overflow-x:hidden;
}

/* Decorative background + ambient lighting — same composition as the Student and
   Dean portals, kept in the Admin blue identity. */
body::before{
    content:"";position:fixed;inset:0;z-index:0;pointer-events:none;
    background:linear-gradient(180deg,rgba(20,15,8,.05),transparent 26%,rgba(120,100,60,.06));
}
.bg-grid{
    position:fixed;inset:0;z-index:0;pointer-events:none;
    background-image:
        repeating-linear-gradient(45deg,rgba(201,162,39,.075) 0,rgba(201,162,39,.075) 1px,transparent 1px,transparent 26px),
        repeating-linear-gradient(-45deg,rgba(201,162,39,.055) 0,rgba(201,162,39,.055) 1px,transparent 1px,transparent 26px);
}
.hex-deco{position:fixed;z-index:0;pointer-events:none;opacity:.5}
.hex-1{top:-60px;left:-60px}
.hex-2{bottom:-70px;right:-70px}

.reg-card{
    position:relative;z-index:10;width:min(100%,580px);
    padding:28px 36px 22px;
    background:rgba(255,252,244,.92);
    backdrop-filter:blur(18px);-webkit-backdrop-filter:blur(18px);
    border:1px solid rgba(20,15,8,.14);border-radius:16px;
    box-shadow:0 24px 70px rgba(120,100,60,.14),0 0 0 1px rgba(20,15,8,.04),0 0 90px rgba(201,162,39,.10);
    transition:border-color .3s ease,box-shadow .3s ease;
    animation:cardIn .65s cubic-bezier(.22,1,.36,1) both;
}
.reg-card:hover{border-color:rgba(240,202,90,.30);box-shadow:0 24px 70px rgba(120,100,60,.14),0 0 0 1px rgba(20,15,8,.04),0 0 100px rgba(201,162,39,.17)}
@keyframes cardIn{from{opacity:0;transform:translateY(24px) scale(.98)}to{opacity:1;transform:none}}
.card-header{text-align:center;margin-bottom:16px}
.logo-img{
    width:58px;height:58px;border-radius:50%;object-fit:cover;display:block;
    border:2px solid var(--blue-accent);box-shadow:0 0 18px rgba(201,162,39,.36);
    margin:0 auto 9px;
}
.card-title{font-family:'Rajdhani',sans-serif;font-size:23px;font-weight:700;letter-spacing:1.55px;color:#1F1B12;text-transform:uppercase}
.card-subtitle{font-size:10.5px;color:var(--muted);letter-spacing:.95px;text-transform:uppercase;margin-top:2px}
.divider{height:1px;background:linear-gradient(90deg,transparent,rgba(201,162,39,.36),transparent);margin-bottom:14px}
.alert{display:flex;align-items:flex-start;gap:10px;border-radius:10px;padding:10px 12px;font-size:11px;line-height:1.5;margin-bottom:14px}
.alert-error{background:rgba(248,113,113,.10);border:1px solid rgba(248,113,113,.30);color:#C0392B}
.photo-upload-area{
    display:grid;grid-template-columns:auto 1fr;align-items:center;gap:12px;
    padding:10px 12px;margin-bottom:15px;border-radius:10px;
    background:rgba(0,0,0,.02);border:1px solid rgba(20,15,8,.10);
}
.photo-preview{
    width:64px;height:64px;border-radius:50%;background:var(--blue-inner);
    border:2px dashed rgba(201,162,39,.52);overflow:hidden;display:flex;
    align-items:center;justify-content:center;cursor:pointer;transition:.2s;flex-shrink:0;
}
.photo-preview:hover{border-color:var(--blue-hover);box-shadow:0 0 0 4px rgba(201,162,39,.10)}
.photo-preview img{width:100%;height:100%;object-fit:cover;display:none}
.photo-preview .ph-icon{color:var(--muted);font-size:20px;transition:.2s}
.photo-preview:hover .ph-icon{color:var(--blue-hover)}
.photo-info p{font-size:12px;color:#1F1B12;font-weight:700;margin-bottom:3px}
.photo-info span{font-size:9.5px;color:var(--muted);line-height:1.35}
.btn-photo{
    display:inline-flex;align-items:center;gap:6px;margin-top:6px;padding:6px 9px;border-radius:7px;
    background:rgba(201,162,39,.13);border:1px solid rgba(201,162,39,.34);color:var(--blue-hover);
    font:600 9.5px 'DM Sans',sans-serif;cursor:pointer;transition:.2s;
}
.btn-photo:hover{background:rgba(201,162,39,.20);border-color:rgba(201,162,39,.55)}
input[type="file"]{display:none}
.form-row{display:grid;grid-template-columns:1fr 1fr;gap:11px 10px}
.form-row .full{grid-column:1/-1}
.form-group{display:flex;flex-direction:column;gap:4px}
.form-label{font-size:9.5px;font-weight:700;letter-spacing:1px;text-transform:uppercase;color:var(--muted)}
.required{color:#C0392B;font-weight:700;margin-left:2px}
.input-wrap{position:relative}
.f-icon{position:absolute;left:12px;top:50%;transform:translateY(-50%);color:var(--muted);font-size:11.5px;pointer-events:none;transition:color .2s}
.form-input{
    width:100%;min-height:43px;padding:10px 38px 10px 35px;
    background:rgba(0,0,0,.045);border:1px solid rgba(20,15,8,.13);border-radius:8px;
    color:var(--light);font:500 12px 'DM Sans',sans-serif;outline:none;
    transition:border-color .2s,box-shadow .2s,background .2s;
}
.form-input::placeholder{color:rgba(122,111,88,.70)}
.form-input:hover:not(:focus){border-color:rgba(240,202,90,.42);background:rgba(0,0,0,.05)}
.form-input:focus{border-color:var(--blue-accent);background:rgba(0,0,0,.055);box-shadow:0 0 0 3px rgba(201,162,39,.18)}
.input-wrap:focus-within .f-icon{color:var(--blue-hover)}
.toggle-pw{position:absolute;right:9px;top:50%;transform:translateY(-50%);background:none;border:none;color:var(--muted);cursor:pointer;font-size:11.5px;padding:4px}
.toggle-pw:hover{color:var(--blue-hover)}
.pw-strength{height:3px;border-radius:99px;margin-top:5px;background:rgba(20,15,8,.10);transition:all .3s}
.pw-hint{font-size:9px;color:var(--muted);margin-top:2px;min-height:12px}
.btn-main{
    width:100%;min-height:45px;margin-top:11px;padding:10px 13px;background:var(--blue-accent);
    border:none;border-radius:8px;color:#fff;font:700 13.5px 'DM Sans',sans-serif;letter-spacing:.3px;cursor:pointer;
    display:flex;align-items:center;justify-content:center;gap:9px;
    box-shadow:0 7px 20px rgba(201,162,39,.30);
    transition:background .2s,transform .15s,box-shadow .2s;
}
.btn-main:hover{background:var(--blue-hover);transform:translateY(-1px);box-shadow:0 10px 28px rgba(201,162,39,.38)}
.btn-main:active{transform:translateY(1px)}
.card-footer{text-align:center;margin-top:12px;padding-top:10px;border-top:1px solid rgba(20,15,8,.09);font-size:10.5px;line-height:1.5;color:var(--muted)}
.card-footer a{color:var(--blue-hover);font-weight:700;text-decoration:none;text-underline-offset:3px}
.card-footer a:hover{text-decoration:underline}
.secure-badge{display:inline-flex;align-items:center;gap:5px;font-size:9px;color:#8A7C61;margin-top:4px}
.secure-badge i{color:#16A34A;font-size:9px}

button:focus-visible,a:focus-visible,.photo-preview:focus-visible{outline:3px solid rgba(240,202,90,.38);outline-offset:2px}
@media(prefers-reduced-motion:reduce){.login-card,.reg-card{animation:none;transition:none}.btn-main,.photo-preview{transition:none}}
@media(max-width:700px){body{padding:16px 12px;align-items:flex-start;overflow-y:auto}.reg-card{padding:24px 18px 18px;margin:auto 0}.hex-1,.hex-2{opacity:.24}}
@media(max-height:760px) and (min-width:701px){body{align-items:flex-start;overflow-y:auto;padding-top:16px;padding-bottom:16px}.reg-card{margin:auto 0}}
@media(max-width:520px){.form-row{grid-template-columns:1fr;gap:10px}.form-row .full{grid-column:auto}}
@media(max-width:480px){.logo-img{width:54px;height:54px}.card-title{font-size:21px}}

/* Final input icon alignment fix: keep icons inside their own visual space. */
.input-wrap .f-icon{
    left:14px!important;
    z-index:2!important;
    width:18px!important;
    text-align:center!important;
}
.input-wrap .form-input{
    padding-left:46px!important;
    padding-right:46px!important;
}
.input-wrap .toggle-pw{
    right:12px!important;
    z-index:3!important;
    width:26px!important;
    height:30px!important;
    display:flex!important;
    align-items:center!important;
    justify-content:center!important;
}
.input-wrap .toggle-pw i{display:block!important;}
</style>

<link rel="stylesheet" href="admin_appearance.css">
<script src="admin_appearance.js"></script>

<style id="pbi-auth-navy-theme">
/* PBI Admin authentication theme — matched to the Executive Assistant
   navigation/sidebar navy palette shown in the current UI reference. */
:root{
    --auth-navy:#0F1E33;
    --auth-navy-deep:#091727;
    --auth-navy-panel:#12263E;
    --auth-navy-input:#0B1B2E;
    --auth-border:#29405A;
    --auth-border-soft:rgba(152,178,207,.18);
    --auth-text:#E7EEF7;
    --auth-muted:#9AAFC5;
    --auth-gold:#F2C94C;
    --auth-gold-hover:#FFD866;
    --auth-blue:#2F6EE2;
    --auth-blue-hover:#3D7BF0;
}

html,body{background:var(--auth-navy-deep)!important;color:var(--auth-text)!important;}
html{color-scheme:dark!important;}
body{
    background:
        radial-gradient(circle at 10% 8%,rgba(47,110,226,.13),transparent 30%),
        radial-gradient(circle at 90% 90%,rgba(242,201,76,.08),transparent 28%),
        repeating-linear-gradient(45deg,rgba(79,129,184,.045) 0,rgba(79,129,184,.045) 1px,transparent 1px,transparent 26px),
        repeating-linear-gradient(-45deg,rgba(79,129,184,.035) 0,rgba(79,129,184,.035) 1px,transparent 1px,transparent 26px),
        var(--auth-navy-deep)!important;
}
body::before{background:linear-gradient(180deg,rgba(15,30,51,.12),transparent 30%,rgba(47,110,226,.035))!important;}
.bg-grid{
    background-image:
        repeating-linear-gradient(45deg,rgba(79,129,184,.055) 0,rgba(79,129,184,.055) 1px,transparent 1px,transparent 26px),
        repeating-linear-gradient(-45deg,rgba(79,129,184,.04) 0,rgba(79,129,184,.04) 1px,transparent 1px,transparent 26px)!important;
}

.login-card,.reg-card{
    background:rgba(15,30,51,.96)!important;
    border:1px solid rgba(152,178,207,.18)!important;
    box-shadow:0 24px 70px rgba(0,0,0,.45),0 0 0 1px rgba(255,255,255,.025),0 0 80px rgba(47,110,226,.07)!important;
    color:var(--auth-text)!important;
}
.login-card:hover,.reg-card:hover{
    border-color:rgba(98,144,201,.36)!important;
    box-shadow:0 24px 70px rgba(0,0,0,.48),0 0 0 1px rgba(255,255,255,.025),0 0 90px rgba(47,110,226,.11)!important;
}

.card-title{color:#F3F7FB!important;}
.card-subtitle{color:#92A7BF!important;}
.divider{background:linear-gradient(90deg,transparent,rgba(93,145,204,.36),transparent)!important;}
.form-label{color:#98ADC3!important;}
.required,.req{color:#F87171!important;}

.form-input{
    background:var(--auth-navy-input)!important;
    border-color:var(--auth-border)!important;
    color:var(--auth-text)!important;
}
.form-input::placeholder{color:rgba(154,175,197,.50)!important;}
.form-input:hover:not(:focus){
    background:#0D2035!important;
    border-color:#345473!important;
}
.form-input:focus{
    background:#0D2035!important;
    border-color:var(--auth-blue)!important;
    box-shadow:0 0 0 3px rgba(47,110,226,.20)!important;
}
.f-icon,.toggle-pw{color:#92A7BF!important;}
.input-wrap:focus-within .f-icon,.toggle-pw:hover{color:#B7C9DB!important;}

.forgot-row a,.register-row a,.card-footer a{color:var(--auth-gold)!important;}
.forgot-row a:hover,.register-row a:hover,.card-footer a:hover{color:var(--auth-gold-hover)!important;}

.btn-main{
    background:var(--auth-blue)!important;
    box-shadow:0 8px 22px rgba(47,110,226,.30)!important;
}
.btn-main:hover{
    background:var(--auth-blue-hover)!important;
    box-shadow:0 10px 28px rgba(47,110,226,.38)!important;
}

.alert-error{
    background:rgba(248,113,113,.10)!important;
    border-color:rgba(248,113,113,.28)!important;
    color:#FCA5A5!important;
}
.alert-success{
    background:rgba(34,197,94,.09)!important;
    border-color:rgba(34,197,94,.24)!important;
    color:#86EFAC!important;
}

.card-footer{border-top-color:rgba(152,178,207,.12)!important;color:var(--auth-muted)!important;}
.secure-badge{color:#8FA6BE!important;}
.secure-badge i{color:#4ADE80!important;}

.photo-upload-area{
    background:rgba(10,27,46,.78)!important;
    border-color:rgba(152,178,207,.17)!important;
}
.photo-preview{
    background:#0A1B2E!important;
    border-color:rgba(79,129,184,.55)!important;
}
.photo-preview:hover{border-color:var(--auth-blue)!important;box-shadow:0 0 0 4px rgba(47,110,226,.12)!important;}
.photo-preview .ph-icon{color:#91A8BF!important;}
.photo-preview:hover .ph-icon{color:#BDD0E4!important;}
.photo-info p{color:#E6EDF6!important;}
.photo-info span{color:#8FA6BE!important;}
.btn-photo{
    background:rgba(242,201,76,.08)!important;
    border-color:rgba(242,201,76,.30)!important;
    color:var(--auth-gold)!important;
}
.btn-photo:hover{
    background:rgba(242,201,76,.14)!important;
    border-color:rgba(242,201,76,.50)!important;
}

.pw-strength{background:rgba(152,178,207,.12)!important;}
.pw-hint{color:#8FA6BE!important;}

button:focus-visible,a:focus-visible,.photo-preview:focus-visible{
    outline:3px solid rgba(79,144,231,.35)!important;
    outline-offset:2px;
}
</style>

</head>
<body>
<div class="bg-grid" aria-hidden="true"></div>

<svg class="hex-deco hex-1" width="260" height="260" viewBox="0 0 260 260" aria-hidden="true"><polygon points="130,10 240,70 240,190 130,250 20,190 20,70" fill="none" stroke="#C9A227" stroke-width="1"/><polygon points="130,50 200,90 200,170 130,210 60,170 60,90" fill="none" stroke="#C9A227" stroke-width="1"/></svg>
<svg class="hex-deco hex-2" width="300" height="300" viewBox="0 0 300 300" aria-hidden="true"><polygon points="150,10 280,80 280,220 150,290 20,220 20,80" fill="none" stroke="#9C7A12" stroke-width="1"/><polygon points="150,60 220,100 220,200 150,240 80,200 80,100" fill="none" stroke="#9C7A12" stroke-width="1"/></svg>

<div class="reg-card">
    <div class="card-header">
        <img class="logo-img" src="../image/pbi_logo" alt="PBI Logo"/>
        <div class="card-title">ADMIN REGISTRATION</div>
        <div class="card-subtitle">Pandan Bay Institute &mdash; Control Panel</div>
    </div>
    <div class="divider"></div>

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
                <span>Clear photo for identification · max 10 MB</span><br>
                <label class="btn-photo" for="photoFile">
                    <i class="fa-solid fa-upload"></i> Choose Photo
                </label>
                <input type="file" id="photoFile" name="photo"
                       accept="image/jpeg,image/png,image/webp,image/gif"
                       onchange="previewPhoto(this)"/>
            </div>
        </div>

        <div class="form-row">
            <!-- Name -->
            <div class="form-group full">
                <label class="form-label">Full Name<span class="required">*</span></label>
                <div class="input-wrap">
                    <i class="fa-solid fa-user f-icon"></i>
                    <input class="form-input" type="text" name="full_name" placeholder="Juan dela Cruz"
                           value="<?= htmlspecialchars($_POST['full_name'] ?? '') ?>" required/>
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

            <!-- Email -->
            <div class="form-group">
                <label class="form-label">Email Address<span class="required">*</span></label>
                <div class="input-wrap">
                    <i class="fa-solid fa-envelope f-icon"></i>
                    <input class="form-input" type="email" name="email" placeholder="admin@pandanbay.edu.ph"
                           value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" required/>
                </div>
            </div>

            <!-- Password -->
            <div class="form-group">
                <label class="form-label">Password<span class="required">*</span></label>
                <div class="input-wrap">
                    <i class="fa-solid fa-lock f-icon"></i>
                    <input class="form-input" type="password" id="pw" name="password"
                           placeholder="Min. 8 characters" required oninput="checkStrength(this.value)"/>
                    <button type="button" class="toggle-pw" onclick="togglePw('pw','eye1',this)" aria-label="Show password" title="Show password">
                        <i class="fa-solid fa-eye-slash" id="eye1"></i>
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
                    <button type="button" class="toggle-pw" onclick="togglePw('pw2','eye2',this)" aria-label="Show password" title="Show password">
                        <i class="fa-solid fa-eye-slash" id="eye2"></i>
                    </button>
                </div>
            </div>
        </div>

        <button type="submit" class="btn-main">
            <i class="fa-solid fa-user-plus"></i> ADMIN REGISTRATION
        </button>
    </form>

    <div class="card-footer">
        Already have an account? <a href="admin_login.php">Sign in here</a><br>
        <span class="secure-badge"><i class="fa-solid fa-circle-check"></i> Secured &amp; Encrypted Connection</span>
    </div>
</div>

<script>
function togglePw(id, ic, btn) {
    const el = document.getElementById(id), i = document.getElementById(ic);
    const showing = el.type === 'password';
    el.type = showing ? 'text' : 'password';
    i.className = showing ? 'fa-solid fa-eye' : 'fa-solid fa-eye-slash';
    if (btn) {
        btn.setAttribute('aria-label', showing ? 'Hide password' : 'Show password');
        btn.setAttribute('title', showing ? 'Hide password' : 'Show password');
    }
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
    const colors = ['#ff4444','#ff8800','#f0c040','#32B98A'];
    const labels = ['Weak','Fair','Good','Strong'];
    if (!val) { bar.style.background = 'rgba(20,15,8,.10)'; hint.textContent = ''; return; }
    bar.style.background = colors[score - 1] || colors[0];
    hint.textContent     = labels[score - 1] || 'Weak';
    hint.style.color     = colors[score - 1] || colors[0];
}
</script>
</body>
</html>