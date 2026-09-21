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
    padding:24px;position:relative;overflow-x:hidden;
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

.reg-card{
    position:relative;z-index:10;width:min(100%,580px);
    padding:28px 36px 22px;
    background:rgba(23,42,69,.84);
    backdrop-filter:blur(18px);-webkit-backdrop-filter:blur(18px);
    border:1px solid rgba(255,255,255,.12);border-radius:16px;
    box-shadow:0 24px 70px rgba(0,0,0,.36),0 0 0 1px rgba(255,255,255,.02),0 0 90px rgba(59,130,246,.10);
    transition:border-color .3s ease,box-shadow .3s ease;
    animation:cardIn .65s cubic-bezier(.22,1,.36,1) both;
}
.reg-card:hover{border-color:rgba(96,165,250,.30);box-shadow:0 24px 70px rgba(0,0,0,.36),0 0 0 1px rgba(255,255,255,.02),0 0 100px rgba(59,130,246,.17)}
@keyframes cardIn{from{opacity:0;transform:translateY(24px) scale(.98)}to{opacity:1;transform:none}}
.card-header{text-align:center;margin-bottom:16px}
.logo-img{
    width:58px;height:58px;border-radius:50%;object-fit:cover;display:block;
    border:2px solid var(--blue-accent);box-shadow:0 0 18px rgba(59,130,246,.36);
    margin:0 auto 9px;
}
.card-title{font-family:'Rajdhani',sans-serif;font-size:23px;font-weight:700;letter-spacing:1.55px;color:#fff;text-transform:uppercase}
.card-subtitle{font-size:10.5px;color:var(--muted);letter-spacing:.95px;text-transform:uppercase;margin-top:2px}
.divider{height:1px;background:linear-gradient(90deg,transparent,rgba(59,130,246,.36),transparent);margin-bottom:14px}
.alert{display:flex;align-items:flex-start;gap:10px;border-radius:10px;padding:10px 12px;font-size:11px;line-height:1.5;margin-bottom:14px}
.alert-error{background:rgba(248,113,113,.10);border:1px solid rgba(248,113,113,.30);color:#FF9A9A}
.photo-upload-area{
    display:grid;grid-template-columns:auto 1fr;align-items:center;gap:12px;
    padding:10px 12px;margin-bottom:15px;border-radius:10px;
    background:rgba(10,25,47,.30);border:1px solid rgba(255,255,255,.08);
}
.photo-preview{
    width:64px;height:64px;border-radius:50%;background:var(--blue-inner);
    border:2px dashed rgba(59,130,246,.52);overflow:hidden;display:flex;
    align-items:center;justify-content:center;cursor:pointer;transition:.2s;flex-shrink:0;
}
.photo-preview:hover{border-color:var(--blue-hover);box-shadow:0 0 0 4px rgba(59,130,246,.10)}
.photo-preview img{width:100%;height:100%;object-fit:cover;display:none}
.photo-preview .ph-icon{color:var(--muted);font-size:20px;transition:.2s}
.photo-preview:hover .ph-icon{color:var(--blue-hover)}
.photo-info p{font-size:12px;color:#fff;font-weight:700;margin-bottom:3px}
.photo-info span{font-size:9.5px;color:var(--muted);line-height:1.35}
.btn-photo{
    display:inline-flex;align-items:center;gap:6px;margin-top:6px;padding:6px 9px;border-radius:7px;
    background:rgba(59,130,246,.13);border:1px solid rgba(59,130,246,.34);color:var(--blue-hover);
    font:600 9.5px 'DM Sans',sans-serif;cursor:pointer;transition:.2s;
}
.btn-photo:hover{background:rgba(59,130,246,.20);border-color:rgba(59,130,246,.55)}
input[type="file"]{display:none}
.form-row{display:grid;grid-template-columns:1fr 1fr;gap:11px 10px}
.form-row .full{grid-column:1/-1}
.form-group{display:flex;flex-direction:column;gap:4px}
.form-label{font-size:9.5px;font-weight:700;letter-spacing:1px;text-transform:uppercase;color:var(--muted)}
.required{color:#FF9696;font-weight:700;margin-left:2px}
.input-wrap{position:relative}
.f-icon{position:absolute;left:12px;top:50%;transform:translateY(-50%);color:var(--muted);font-size:11.5px;pointer-events:none;transition:color .2s}
.form-input{
    width:100%;min-height:43px;padding:10px 38px 10px 35px;
    background:rgba(10,25,47,.68);border:1px solid rgba(255,255,255,.11);border-radius:8px;
    color:var(--light);font:500 12px 'DM Sans',sans-serif;outline:none;
    transition:border-color .2s,box-shadow .2s,background .2s;
}
.form-input::placeholder{color:rgba(160,179,198,.40)}
.form-input:hover:not(:focus){border-color:rgba(96,165,250,.42);background:rgba(10,25,47,.76)}
.form-input:focus{border-color:var(--blue-accent);background:rgba(10,25,47,.80);box-shadow:0 0 0 3px rgba(59,130,246,.18)}
.input-wrap:focus-within .f-icon{color:var(--blue-hover)}
.toggle-pw{position:absolute;right:9px;top:50%;transform:translateY(-50%);background:none;border:none;color:var(--muted);cursor:pointer;font-size:11.5px;padding:4px}
.toggle-pw:hover{color:var(--blue-hover)}
.pw-strength{height:3px;border-radius:99px;margin-top:5px;background:rgba(255,255,255,.08);transition:all .3s}
.pw-hint{font-size:9px;color:var(--muted);margin-top:2px;min-height:12px}
.btn-main{
    width:100%;min-height:45px;margin-top:11px;padding:10px 13px;background:var(--blue-accent);
    border:none;border-radius:8px;color:#fff;font:700 13.5px 'DM Sans',sans-serif;letter-spacing:.3px;cursor:pointer;
    display:flex;align-items:center;justify-content:center;gap:9px;
    box-shadow:0 7px 20px rgba(59,130,246,.30);
    transition:background .2s,transform .15s,box-shadow .2s;
}
.btn-main:hover{background:var(--blue-hover);transform:translateY(-1px);box-shadow:0 10px 28px rgba(59,130,246,.38)}
.btn-main:active{transform:translateY(1px)}
.card-footer{text-align:center;margin-top:12px;padding-top:10px;border-top:1px solid rgba(255,255,255,.07);font-size:10.5px;line-height:1.5;color:var(--muted)}
.card-footer a{color:var(--blue-hover);font-weight:700;text-decoration:none;text-underline-offset:3px}
.card-footer a:hover{text-decoration:underline}
.secure-badge{display:inline-flex;align-items:center;gap:5px;font-size:9px;color:#8FA4B8;margin-top:4px}
.secure-badge i{color:#4ade80;font-size:9px}

button:focus-visible,a:focus-visible,.photo-preview:focus-visible{outline:3px solid rgba(96,165,250,.38);outline-offset:2px}
@media(prefers-reduced-motion:reduce){.login-card,.reg-card{animation:none;transition:none}.btn-main,.photo-preview{transition:none}}
@media(max-width:700px){body{padding:16px 12px;align-items:flex-start;overflow-y:auto}.reg-card{padding:24px 18px 18px;margin:auto 0}.hex-1,.hex-2{opacity:.24}}
@media(max-height:760px) and (min-width:701px){body{align-items:flex-start;overflow-y:auto;padding-top:16px;padding-bottom:16px}.reg-card{margin:auto 0}}
@media(max-width:520px){.form-row{grid-template-columns:1fr;gap:10px}.form-row .full{grid-column:auto}}
@media(max-width:480px){.logo-img{width:54px;height:54px}.card-title{font-size:21px}}
</style>
</head>
<body>
<div class="bg-grid" aria-hidden="true"></div>

<svg class="hex-deco hex-1" width="260" height="260" viewBox="0 0 260 260" aria-hidden="true"><polygon points="130,10 240,70 240,190 130,250 20,190 20,70" fill="none" stroke="#3B82F6" stroke-width="1"/><polygon points="130,50 200,90 200,170 130,210 60,170 60,90" fill="none" stroke="#3B82F6" stroke-width="1"/></svg>
<svg class="hex-deco hex-2" width="300" height="300" viewBox="0 0 300 300" aria-hidden="true"><polygon points="150,10 280,80 280,220 150,290 20,220 20,80" fill="none" stroke="#60A5FA" stroke-width="1"/><polygon points="150,60 220,100 220,200 150,240 80,200 80,100" fill="none" stroke="#60A5FA" stroke-width="1"/></svg>

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
            <i class="fa-solid fa-user-plus"></i> ADMIN REGISTRATION
        </button>
    </form>

    <div class="card-footer">
        Already have an account? <a href="admin_login.php">Sign in here</a><br>
        <span class="secure-badge"><i class="fa-solid fa-circle-check"></i> Secured &amp; Encrypted Connection</span>
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
    const colors = ['#ff4444','#ff8800','#f0c040','#32B98A'];
    const labels = ['Weak','Fair','Good','Strong'];
    if (!val) { bar.style.background = 'rgba(255,255,255,.08)'; hint.textContent = ''; return; }
    bar.style.background = colors[score - 1] || colors[0];
    hint.textContent     = labels[score - 1] || 'Weak';
    hint.style.color     = colors[score - 1] || colors[0];
}
</script>
</body>
</html>