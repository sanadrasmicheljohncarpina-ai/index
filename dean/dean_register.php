<?php
// dean/dean_register.php
// Place this file inside: htdocs/index/dean/
session_start();
require_once 'db.php';

$error = '';
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== ($_SESSION['csrf_token'] ?? '')) {
        $error = 'Session expired. Please refresh and try again.';
    } else {
        $fullName = trim($_POST['full_name'] ?? '');
        $username = trim($_POST['username'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';

        if (empty($fullName) || empty($username) || empty($password)) {
            $error = 'Full name, username, and password are required.';
        } elseif ($password !== $confirmPassword) {
            $error = 'Passwords do not match.';
        } elseif (strlen($password) < 8) {
            $error = 'Password must be at least 8 characters.';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = 'Please enter a valid email address.';
        } else {
            $chk = $mysqli->prepare("SELECT id FROM users WHERE username = ? OR (email = ? AND email != '') LIMIT 1");
            if ($chk) {
                $chk->bind_param("ss", $username, $email);
                $chk->execute();
                $chk->store_result();
                if ($chk->num_rows > 0) {
                    $error = 'Username or email is already taken.';
                }
                $chk->close();
            }
        }

        // Optional profile photo, matching the shared registration layout.
        $photo_filename = null;
        if (empty($error) && isset($_FILES['photo']) && $_FILES['photo']['error'] !== UPLOAD_ERR_NO_FILE) {
            if ($_FILES['photo']['error'] !== UPLOAD_ERR_OK) {
                $error = 'Failed to upload profile photo.';
            } else {
                $allowed = ['image/jpeg','image/png','image/webp','image/gif'];
                $file_type = mime_content_type($_FILES['photo']['tmp_name']);
                $file_size = $_FILES['photo']['size'];

                if (!in_array($file_type, $allowed, true)) {
                    $error = 'Profile photo must be JPG, PNG, WebP, or GIF.';
                } elseif ($file_size > 10 * 1024 * 1024) {
                    $error = 'Profile photo must be under 10 MB.';
                } else {
                    $ext = pathinfo($_FILES['photo']['name'], PATHINFO_EXTENSION);
                    $photo_filename = uniqid('usr_', true) . '.' . strtolower($ext);
                    if (!is_dir(UPLOAD_DIR)) {
                        mkdir(UPLOAD_DIR, 0755, true);
                    }
                    if (!move_uploaded_file($_FILES['photo']['tmp_name'], UPLOAD_DIR . $photo_filename)) {
                        $error = 'Failed to save photo. Check folder permissions on /image/.';
                        $photo_filename = null;
                    }
                }
            }
        }

        if (empty($error)) {
            $result = ems_register_account($mysqli, 'dean', [
                'full_name'   => $fullName,
                'employee_id' => '',
                'username'    => $username,
                'email'       => $email,
                'password'    => $password,
                'department'  => '',
                'course'      => '',
                'designation' => '',
            ]);

            if ($result['ok']) {
                if ($photo_filename) {
                    $photoStmt = $mysqli->prepare("UPDATE users SET photo = ? WHERE username = ? AND role = 'dean' LIMIT 1");
                    if ($photoStmt) {
                        $photoStmt->bind_param("ss", $photo_filename, $username);
                        $photoStmt->execute();
                        $photoStmt->close();
                    }
                }
                $success = true;
            } else {
                if ($photo_filename && is_file(UPLOAD_DIR . $photo_filename)) {
                    @unlink(UPLOAD_DIR . $photo_filename);
                }
                $error = $result['error'];
            }
        }
    }
}

$_SESSION['csrf_token'] = bin2hex(random_bytes(32));
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"/>
<meta name="viewport" content="width=device-width, initial-scale=1.0"/>
<title>PBI — Dean Registration</title>
<link href="https://fonts.googleapis.com/css2?family=Rajdhani:wght@600;700&family=DM+Sans:wght@400;500;600&display=swap" rel="stylesheet"/>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css"/>
<style>
:root{
    --dark-blue:#0A192F; --blue-mid:#172A45; --blue-inner:#0F1F3D;
    --blue-accent:#2B6CB0; --violet:#7C5FD9; --violet-hover:#9C85F0;
    --light:#E0E6F0; --muted:#A0B3C6; --danger:#F05454; --radius:10px;
    --shadow:0 8px 32px rgba(0,0,0,.45);
}
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0;}
body{
    min-height:100vh;
    background:linear-gradient(rgba(5,18,36,.72),rgba(5,18,36,.84)),url('../background.png') center/cover no-repeat fixed;
    font-family:'DM Sans',sans-serif;color:var(--light);
    display:flex;align-items:center;justify-content:center;
    padding:40px 20px;position:relative;overflow-x:hidden;
}
.bg-grid{position:fixed;inset:0;z-index:0;background-image:linear-gradient(rgba(124,95,217,.045) 1px,transparent 1px),linear-gradient(90deg,rgba(124,95,217,.045) 1px,transparent 1px);background-size:48px 48px;animation:gridShift 22s linear infinite;}
@keyframes gridShift{0%{background-position:0 0}100%{background-position:48px 48px}}
.orb{position:fixed;border-radius:50%;filter:blur(90px);z-index:0;pointer-events:none;}
.orb-1{width:380px;height:380px;background:radial-gradient(circle,rgba(124,95,217,.13) 0%,transparent 70%);top:-80px;right:-80px;}
.orb-2{width:300px;height:300px;background:radial-gradient(circle,rgba(43,108,176,.15) 0%,transparent 70%);bottom:-60px;left:-60px;}
.reg-card{
    position:relative;z-index:10;width:100%;max-width:650px;
    background:rgba(23,42,69,.9);backdrop-filter:blur(20px);
    border:1px solid rgba(255,255,255,.09);border-radius:20px;
    padding:36px 40px 30px;box-shadow:var(--shadow),0 0 0 1px rgba(124,95,217,.12);
}
.card-header{text-align:center;margin-bottom:22px;}
.logo-ring{width:68px;height:68px;border-radius:50%;border:2.5px solid var(--violet);box-shadow:0 0 20px rgba(124,95,217,.4);margin:0 auto 14px;display:block;object-fit:cover;}
.card-title{font-family:'Rajdhani',sans-serif;font-size:26px;font-weight:700;letter-spacing:2px;color:#fff;text-transform:uppercase;}
.card-subtitle{font-size:12px;color:var(--muted);letter-spacing:1.1px;text-transform:uppercase;margin-top:4px;}
.divider{height:1px;background:linear-gradient(90deg,transparent,rgba(124,95,217,.42),transparent);margin:0 0 20px;}
.section-title{font-family:'Rajdhani',sans-serif;color:var(--violet-hover);font-size:17px;letter-spacing:1px;text-transform:uppercase;display:flex;align-items:center;gap:8px;margin-bottom:4px;}
.section-sub{font-size:12px;color:var(--muted);margin-bottom:18px;}
.photo-upload-area{display:flex;align-items:center;gap:18px;margin-bottom:20px;}
.photo-preview{width:78px;height:78px;border-radius:50%;background:var(--blue-inner);border:2px dashed rgba(124,95,217,.5);overflow:hidden;display:flex;align-items:center;justify-content:center;cursor:pointer;transition:border-color .2s;flex-shrink:0;}
.photo-preview:hover{border-color:var(--violet);}
.photo-preview img{width:100%;height:100%;object-fit:cover;display:none;}
.photo-preview .ph-icon{color:var(--muted);font-size:23px;}
.photo-info p{font-size:13px;color:var(--light);font-weight:600;margin-bottom:3px;}
.photo-info span{font-size:11px;color:var(--muted);}
.btn-photo{display:inline-flex;align-items:center;gap:6px;background:rgba(124,95,217,.12);border:1px solid rgba(124,95,217,.35);color:var(--violet-hover);padding:7px 14px;border-radius:7px;font-size:12px;font-weight:600;cursor:pointer;margin-top:7px;}
input[type=file]{display:none;}
.form-grid{display:grid;grid-template-columns:1fr 1fr;gap:16px;}
.form-grid .full{grid-column:1/-1;}
.form-group{display:flex;flex-direction:column;gap:6px;}
.form-label{font-size:11px;font-weight:600;letter-spacing:1.2px;text-transform:uppercase;color:var(--muted);}
.input-wrap{position:relative;}
.input-wrap .f-icon{position:absolute;left:13px;top:50%;transform:translateY(-50%);color:var(--muted);font-size:13px;pointer-events:none;}
.form-input{width:100%;padding:11px 13px 11px 38px;background:rgba(10,25,47,.7);border:1px solid rgba(255,255,255,.1);border-radius:var(--radius);color:var(--light);font-size:14px;font-family:'DM Sans',sans-serif;outline:none;transition:border-color .25s,box-shadow .25s;}
.form-input::placeholder{color:rgba(160,179,198,.42);}
.form-input:focus{border-color:var(--violet);box-shadow:0 0 0 3px rgba(124,95,217,.15);}
.toggle-pw{position:absolute;right:12px;top:50%;transform:translateY(-50%);background:none;border:none;color:var(--muted);cursor:pointer;font-size:13px;padding:0;}
.pw-strength{margin-top:6px;display:none;}
.strength-bar{height:4px;border-radius:2px;background:rgba(255,255,255,.08);overflow:hidden;margin-bottom:4px;}
.strength-fill{height:100%;border-radius:2px;width:0%;transition:width .3s,background .3s;}
.strength-label{font-size:11px;color:var(--muted);}
.alert{display:flex;align-items:flex-start;gap:9px;border-radius:8px;padding:11px 14px;font-size:13px;margin-bottom:16px;}
.alert-error{background:rgba(240,84,84,.12);border:1px solid rgba(240,84,84,.3);color:#fca5a5;}
.alert-success{background:rgba(34,197,94,.1);border:1px solid rgba(34,197,94,.28);color:#86efac;}
.info-box{background:rgba(43,108,176,.12);border:1px solid rgba(43,108,176,.25);border-radius:8px;padding:12px 14px;margin-top:16px;font-size:12px;color:var(--muted);display:flex;gap:8px;align-items:flex-start;line-height:1.5;}
.info-box i{color:var(--violet-hover);margin-top:1px;flex-shrink:0;}
.btn-register{width:100%;padding:13px;background:var(--violet);border:none;border-radius:var(--radius);color:#fff;font-size:15px;font-weight:700;font-family:'DM Sans',sans-serif;letter-spacing:.4px;cursor:pointer;margin-top:18px;transition:background .2s,transform .15s,box-shadow .2s;box-shadow:0 4px 16px rgba(124,95,217,.28);display:flex;align-items:center;justify-content:center;gap:8px;}
.btn-register:hover{background:var(--violet-hover);transform:translateY(-1px);box-shadow:0 6px 22px rgba(124,95,217,.38);}
.card-footer{text-align:center;margin-top:20px;font-size:12px;color:var(--muted);border-top:1px solid rgba(255,255,255,.06);padding-top:17px;}
.card-footer a{color:var(--violet-hover);text-decoration:none;font-weight:600;}
.secure-badge{display:inline-flex;align-items:center;gap:5px;font-size:11px;color:var(--muted);margin-top:10px;letter-spacing:.5px;}
.secure-badge i{color:#4ade80;font-size:10px;}
@media(max-width:600px){.reg-card{padding:28px 18px 24px}.form-grid{grid-template-columns:1fr}.form-grid .full{grid-column:1}.photo-upload-area{align-items:flex-start}}
</style>
</head>
<body>
<div class="bg-grid"></div><div class="orb orb-1"></div><div class="orb orb-2"></div>
<div class="reg-card">
    <div class="card-header">
        <img class="logo-ring" src="../image/pbi_logo" alt="PBI Logo"/>
        <div class="card-title">Dean Registration</div>
        <div class="card-subtitle">Create your dean account</div>
    </div>

    <div class="divider"></div>

    <?php if ($success): ?>
        <div class="alert alert-success">
            <i class="fa-solid fa-circle-check"></i>
            <span>Registration submitted successfully. Your account is <strong>pending administrator approval</strong>. You will be able to log in once your account is approved.</span>
        </div>
        <div class="card-footer" style="border-top:none;margin-top:8px;">
            <a href="dean_login.php">Back to Login</a>
        </div>
    <?php else: ?>
        <?php if ($error): ?>
            <div class="alert alert-error"><i class="fa-solid fa-circle-exclamation"></i><span><?= htmlspecialchars($error) ?></span></div>
        <?php endif; ?>

        <div class="section-title"><i class="fa-solid fa-user-plus"></i> Account Information</div>
        <div class="section-sub">Please provide your account details to register.</div>

        <form method="POST" action="dean_register.php" enctype="multipart/form-data" id="regForm">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">

            <div class="photo-upload-area">
                <div class="photo-preview" id="photoPreview" onclick="document.getElementById('photoFile').click()">
                    <img id="photoImg" src="" alt="Preview"/>
                    <i class="fa-solid fa-camera ph-icon" id="phIcon"></i>
                </div>
                <div class="photo-info">
                    <p>Profile Photo</p>
                    <span>Optional profile photo — max 10MB</span><br>
                    <label class="btn-photo" for="photoFile"><i class="fa-solid fa-upload"></i> Upload Photo</label>
                    <input type="file" id="photoFile" name="photo" accept="image/jpeg,image/png,image/webp,image/gif" onchange="previewPhoto(this)"/>
                </div>
            </div>

            <div class="form-grid">
                <div class="form-group full">
                    <label class="form-label" for="full_name">Full Name <span style="color:#f87171">*</span></label>
                    <div class="input-wrap">
                        <input class="form-input" type="text" id="full_name" name="full_name" placeholder="Last Name, First Name M.I." required value="<?= htmlspecialchars($_POST['full_name'] ?? '') ?>"/>
                        <i class="fa-solid fa-id-card f-icon"></i>
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label" for="username">Username <span style="color:#f87171">*</span></label>
                    <div class="input-wrap">
                        <input class="form-input" type="text" id="username" name="username" placeholder="Choose a username" required value="<?= htmlspecialchars($_POST['username'] ?? '') ?>"/>
                        <i class="fa-solid fa-user f-icon"></i>
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label" for="email">Email Address <span style="color:#f87171">*</span></label>
                    <div class="input-wrap">
                        <input class="form-input" type="email" id="email" name="email" placeholder="your@email.com" required value="<?= htmlspecialchars($_POST['email'] ?? '') ?>"/>
                        <i class="fa-solid fa-envelope f-icon"></i>
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label" for="password">Password <span style="color:#f87171">*</span></label>
                    <div class="input-wrap">
                        <input class="form-input" type="password" id="password" name="password" placeholder="Min. 8 characters" required minlength="8" oninput="checkStrength(this.value)"/>
                        <i class="fa-solid fa-lock f-icon"></i>
                        <button type="button" class="toggle-pw" onclick="togglePw('password','eye1')"><i class="fa-solid fa-eye" id="eye1"></i></button>
                    </div>
                    <div class="pw-strength" id="pwStrength">
                        <div class="strength-bar"><div class="strength-fill" id="strengthFill"></div></div>
                        <span class="strength-label" id="strengthLabel"></span>
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label" for="confirm_password">Confirm Password <span style="color:#f87171">*</span></label>
                    <div class="input-wrap">
                        <input class="form-input" type="password" id="confirm_password" name="confirm_password" placeholder="Re-enter password" required minlength="8"/>
                        <i class="fa-solid fa-lock f-icon"></i>
                        <button type="button" class="toggle-pw" onclick="togglePw('confirm_password','eye2')"><i class="fa-solid fa-eye" id="eye2"></i></button>
                    </div>
                </div>
            </div>

            <div class="info-box">
                <i class="fa-solid fa-circle-info"></i>
                <span>Your dean account will be submitted for <strong style="color:var(--light)">administrator approval</strong>. Additional employment details are not required during registration and can be managed later by an authorized administrator.</span>
            </div>

            <button type="submit" class="btn-register">
                <i class="fa-solid fa-user-plus"></i>
                <span>Create Dean Account</span>
            </button>
        </form>

        <div class="card-footer">
            Already have an account? <a href="dean_login.php">Sign in here</a><br>
            <span class="secure-badge"><i class="fa-solid fa-circle-check"></i> Secured &amp; Encrypted Connection</span>
        </div>
    <?php endif; ?>
</div>
<script>
function togglePw(id,ic){const e=document.getElementById(id),i=document.getElementById(ic);e.type=e.type==='password'?'text':'password';i.className=e.type==='password'?'fa-solid fa-eye':'fa-solid fa-eye-slash';}
function previewPhoto(input){if(input.files&&input.files[0]){const r=new FileReader();r.onload=e=>{const img=document.getElementById('photoImg'),ic=document.getElementById('phIcon');img.src=e.target.result;img.style.display='block';ic.style.display='none';};r.readAsDataURL(input.files[0]);}}
function checkStrength(v){const b=document.getElementById('strengthFill'),l=document.getElementById('strengthLabel'),w=document.getElementById('pwStrength');w.style.display=v?'block':'none';let s=0;if(v.length>=8)s++;if(/[A-Z]/.test(v))s++;if(/[0-9]/.test(v))s++;if(/[^A-Za-z0-9]/.test(v))s++;if(!s){b.style.width='0%';l.textContent='';return;}const lv=[{w:'20%',bg:'#f87171',lb:'Weak'},{w:'45%',bg:'#fb923c',lb:'Fair'},{w:'70%',bg:'#facc15',lb:'Good'},{w:'100%',bg:'#4ade80',lb:'Strong'}][Math.max(0,s-1)];b.style.width=lv.w;b.style.background=lv.bg;l.textContent=lv.lb;l.style.color=lv.bg;}
</script>
</body>
</html>
