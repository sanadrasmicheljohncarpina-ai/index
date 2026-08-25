<?php
// executive/executive_register.php
session_start();
require_once 'db.php';

// Ensure executive_assistant is in role ENUM
$mysqli->query("ALTER TABLE users MODIFY COLUMN role
    ENUM('superadmin','admin','executive_assistant','school_head','faculty','staff','student')
    NOT NULL DEFAULT 'student'");

// Ensure admin_permissions table supports EA
$mysqli->query("CREATE TABLE IF NOT EXISTS admin_permissions (
    id                INT UNSIGNED NOT NULL AUTO_INCREMENT,
    admin_user_id     INT UNSIGNED NOT NULL UNIQUE,
    can_user_mgmt     TINYINT(1) NOT NULL DEFAULT 0,
    can_questionnaire TINYINT(1) NOT NULL DEFAULT 0,
    can_personnel     TINYINT(1) NOT NULL DEFAULT 0,
    can_documents     TINYINT(1) NOT NULL DEFAULT 0,
    can_analytics     TINYINT(1) NOT NULL DEFAULT 0,
    can_eval_periods  TINYINT(1) NOT NULL DEFAULT 0,
    notes             TEXT NULL,
    updated_at        DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $full_name  = trim($_POST['full_name']        ?? '');
    $username   = trim($_POST['username']          ?? '');
    $email      = trim($_POST['email']             ?? '');
    $password   = $_POST['password']               ?? '';
    $confirm_pw = $_POST['confirm_password']        ?? '';

    // ── VALIDATION ────────────────────────────────────────────
    if (empty($full_name))             { $error = "Full name is required."; }
    elseif (empty($username))          { $error = "Username is required."; }
    elseif (empty($password))          { $error = "Password is required."; }
    elseif (strlen($password) < 8)     { $error = "Password must be at least 8 characters."; }
    elseif ($password !== $confirm_pw) { $error = "Passwords do not match."; }
    else {
        $chk = $mysqli->prepare("SELECT id FROM users WHERE username=? LIMIT 1");
        $chk->bind_param("s", $username); $chk->execute(); $chk->store_result();
        if ($chk->num_rows > 0) $error = "Username already taken.";
        $chk->close();

        if (empty($error) && !empty($email)) {
            $chk2 = $mysqli->prepare("SELECT id FROM users WHERE email=? LIMIT 1");
            $chk2->bind_param("s", $email); $chk2->execute(); $chk2->store_result();
            if ($chk2->num_rows > 0) $error = "Email already registered.";
            $chk2->close();
        }
    }

    // ── PHOTO UPLOAD ──────────────────────────────────────────
    $photo_filename = null;
    if (empty($error) && isset($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
        $allowed = ['image/jpeg','image/png','image/webp','image/gif'];
        $ftype   = mime_content_type($_FILES['photo']['tmp_name']);
        if (!in_array($ftype, $allowed)) {
            $error = "Photo must be JPG, PNG, WebP or GIF.";
        } elseif ($_FILES['photo']['size'] > 10 * 1024 * 1024) {
            $error = "Photo must be under 10MB.";
        } else {
            $ext            = strtolower(pathinfo($_FILES['photo']['name'], PATHINFO_EXTENSION));
            $photo_filename = uniqid('ea_', true) . '.' . $ext;
            if (!is_dir(UPLOAD_DIR)) mkdir(UPLOAD_DIR, 0755, true);
            if (!move_uploaded_file($_FILES['photo']['tmp_name'], UPLOAD_DIR . $photo_filename)) {
                $error = "Failed to save photo. Check /image/ folder permissions.";
                $photo_filename = null;
            }
        }
    }

    // ── INSERT ────────────────────────────────────────────────
    if (empty($error)) {
        $hash  = password_hash($password, PASSWORD_DEFAULT);
        $role  = 'executive_assistant';
        $desig = 'Executive Assistant';

        $stmt = $mysqli->prepare(
            "INSERT INTO users (full_name, username, email, password_hash, role, designation, photo, is_active, created_at)
             VALUES (?, ?, ?, ?, ?, ?, ?, 1, NOW())"
        );
        $stmt->bind_param("sssssss", $full_name, $username, $email, $hash, $role, $desig, $photo_filename);

        if ($stmt->execute()) {
            $new_id = $mysqli->insert_id;
            $stmt->close();

            // Insert permissions rows — ALL OFF by default, one row per feature.
            // System Admin must explicitly enable each feature via
            // admin/manage_permissions.php
            $feature_keys = [
                'user_management',
                'questionnaire',
                'personnel_registry',
                'documents',
                'reports_analytics',
                'eval_periods',
            ];
            $pi = $mysqli->prepare(
                "INSERT IGNORE INTO admin_permissions (admin_user_id, feature_key, admin_can_edit)
                 VALUES (?, ?, 0)"
            );
            foreach ($feature_keys as $fkey) {
                $pi->bind_param("is", $new_id, $fkey);
                $pi->execute();
            }
            $pi->close();

            $mysqli->close();
            $_SESSION['reg_success'] = "Account created! Awaiting System Admin activation. You may now log in.";
            header("Location: executive_login.php"); exit;
} else {
            $error = "Registration failed: " . $mysqli->error;
            $stmt->close();
        }
    } // <-- ADD: closes "if (empty($error))" (the INSERT block)
} // <-- ADD: closes "if ($_SERVER['REQUEST_METHOD'] === 'POST')"
?>
<!DOCTYPE html>
<html lang="en">
...
<head>
<meta charset="UTF-8"/>
<meta name="viewport" content="width=device-width, initial-scale=1.0"/>
<title>PBI — Executive Assistant Registration</title>
<link href="https://fonts.googleapis.com/css2?family=Rajdhani:wght@600;700&family=DM+Sans:wght@400;500;600&display=swap" rel="stylesheet"/>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css"/>
<style>
:root{--dark:#0A192F;--mid:#172A45;--inner:#0F1F3D;--violet:#7C3AED;--violet-h:#8B5CF6;--light:#E0E6F0;--muted:#A0B3C6;--border:rgba(255,255,255,0.08);--radius:10px;--shadow:0 8px 32px rgba(0,0,0,0.45);}
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0;}
body{min-height:100vh;background:var(--dark);font-family:'DM Sans',sans-serif;color:var(--light);display:flex;align-items:center;justify-content:center;padding:40px 20px;overflow-x:hidden;position:relative;}
.bg-grid{position:fixed;inset:0;z-index:0;background-image:linear-gradient(rgba(124,58,237,.06) 1px,transparent 1px),linear-gradient(90deg,rgba(124,58,237,.06) 1px,transparent 1px);background-size:48px 48px;animation:g 22s linear infinite;}
@keyframes g{0%{background-position:0 0}100%{background-position:48px 48px}}
.orb{position:fixed;border-radius:50%;filter:blur(90px);z-index:0;pointer-events:none;}
.orb-1{width:380px;height:380px;background:radial-gradient(circle,rgba(124,58,237,.18) 0%,transparent 70%);bottom:-80px;right:-80px;animation:o1 16s ease-in-out infinite;}
.orb-2{width:280px;height:280px;background:radial-gradient(circle,rgba(43,108,176,.14) 0%,transparent 70%);top:-60px;left:-60px;animation:o2 20s ease-in-out infinite;}
@keyframes o1{0%,100%{transform:translate(0,0)}50%{transform:translate(-22px,-18px)}}
@keyframes o2{0%,100%{transform:translate(0,0)}50%{transform:translate(18px,16px)}}
.reg-card{position:relative;z-index:10;background:rgba(23,42,69,.88);backdrop-filter:blur(20px);border:1px solid rgba(255,255,255,.09);border-radius:20px;padding:44px 44px 40px;width:100%;max-width:560px;box-shadow:var(--shadow),0 0 0 1px rgba(124,58,237,.12);animation:cardIn .65s cubic-bezier(.22,1,.36,1) both;}
@keyframes cardIn{from{opacity:0;transform:translateY(28px) scale(.97)}to{opacity:1;transform:none}}
.card-header{text-align:center;margin-bottom:28px;}
.logo-ring{width:72px;height:72px;border-radius:50%;display:block;object-fit:cover;border:2.5px solid var(--violet);box-shadow:0 0 22px rgba(124,58,237,.4);margin:0 auto 14px;}
.card-title{font-family:'Rajdhani',sans-serif;font-size:26px;font-weight:700;letter-spacing:2px;color:#fff;text-transform:uppercase;}
.card-subtitle{font-size:12px;color:var(--muted);letter-spacing:1.2px;text-transform:uppercase;margin-top:4px;}
.role-pill{display:inline-flex;align-items:center;gap:6px;background:rgba(124,58,237,.15);border:1px solid rgba(124,58,237,.35);color:var(--violet-h);font-size:11px;font-weight:700;padding:4px 14px;border-radius:20px;text-transform:uppercase;letter-spacing:.8px;margin-top:10px;}
.divider{height:1px;background:linear-gradient(90deg,transparent,rgba(124,58,237,.45),transparent);margin-bottom:24px;}
.alert{display:flex;align-items:flex-start;gap:9px;border-radius:8px;padding:11px 14px;font-size:13px;margin-bottom:18px;}
.alert-error{background:rgba(240,84,84,.12);border:1px solid rgba(240,84,84,.3);color:#fca5a5;}
/* PHOTO UPLOAD */
.photo-upload-area{display:flex;align-items:center;gap:20px;background:rgba(10,25,47,.5);border:1px dashed rgba(124,58,237,.35);border-radius:14px;padding:18px 20px;margin-bottom:22px;cursor:pointer;transition:border-color .2s,background .2s;}
.photo-upload-area:hover{border-color:var(--violet);background:rgba(124,58,237,.06);}
.photo-preview-circle{width:76px;height:76px;border-radius:50%;background:var(--inner);border:2.5px solid rgba(124,58,237,.4);display:flex;align-items:center;justify-content:center;overflow:hidden;flex-shrink:0;position:relative;transition:border-color .2s;}
.photo-preview-circle img{width:100%;height:100%;object-fit:cover;display:none;position:absolute;inset:0;}
.ph-icon{color:var(--muted);font-size:26px;transition:color .2s;}
.photo-upload-area:hover .ph-icon{color:var(--violet-h);}
.photo-upload-info p{font-size:14px;font-weight:700;color:#fff;margin-bottom:3px;}
.photo-upload-info span{font-size:12px;color:var(--muted);line-height:1.5;display:block;}
.btn-choose-photo{display:inline-flex;align-items:center;gap:6px;margin-top:10px;background:rgba(124,58,237,.15);border:1px solid rgba(124,58,237,.35);color:var(--violet-h);padding:7px 16px;border-radius:7px;font-size:12px;font-weight:600;cursor:pointer;transition:all .2s;font-family:'DM Sans',sans-serif;}
.btn-choose-photo:hover{background:rgba(124,58,237,.28);}
input[type="file"]{display:none;}
.photo-name-tag{font-size:11px;color:var(--violet-h);margin-top:6px;display:none;font-weight:600;}
.photo-name-tag.visible{display:block;}
/* INFO NOTE */
.info-note{background:rgba(124,58,237,.08);border:1px solid rgba(124,58,237,.2);border-radius:10px;padding:13px 16px;font-size:13px;color:var(--muted);display:flex;gap:10px;align-items:flex-start;margin-bottom:22px;}
.info-note i{color:var(--violet-h);flex-shrink:0;margin-top:1px;}
/* SECTION LABELS */
.section-label{font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:1px;color:var(--violet-h);margin-bottom:12px;margin-top:22px;display:flex;align-items:center;gap:7px;padding-bottom:6px;border-bottom:1px solid rgba(124,58,237,.15);}
.section-label:first-of-type{margin-top:0;}
/* FORM */
.form-grid{display:grid;grid-template-columns:1fr 1fr;gap:14px;margin-bottom:14px;}
.form-grid.full{grid-template-columns:1fr;}
.fg{display:flex;flex-direction:column;gap:5px;}
.fg label{font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.7px;color:var(--muted);}
.req{color:#f87171;}
.input-wrap{position:relative;}
.f-icon{position:absolute;left:13px;top:50%;transform:translateY(-50%);color:var(--muted);font-size:13px;pointer-events:none;}
.form-input{width:100%;padding:11px 13px 11px 38px;background:rgba(10,25,47,.7);border:1px solid rgba(255,255,255,.1);border-radius:var(--radius);color:var(--light);font-size:14px;font-family:'DM Sans',sans-serif;outline:none;transition:border-color .25s,box-shadow .25s;}
.form-input::placeholder{color:rgba(160,179,198,.42);}
.form-input:focus{border-color:var(--violet);box-shadow:0 0 0 3px rgba(124,58,237,.18);}
.toggle-pw{position:absolute;right:12px;top:50%;transform:translateY(-50%);background:none;border:none;color:var(--muted);cursor:pointer;font-size:13px;padding:0;}
.pw-strength-bar{height:3px;border-radius:2px;background:rgba(255,255,255,.08);margin-top:6px;overflow:hidden;}
.pw-strength-fill{height:100%;border-radius:2px;transition:width .3s,background .3s;width:0%;}
.pw-strength-hint{font-size:11px;margin-top:3px;color:var(--muted);}
.btn-register{width:100%;padding:13px;background:var(--violet);border:none;border-radius:var(--radius);color:#fff;font-size:15px;font-weight:600;font-family:'DM Sans',sans-serif;cursor:pointer;margin-top:22px;transition:background .2s,transform .15s;box-shadow:0 4px 16px rgba(124,58,237,.38);display:flex;align-items:center;justify-content:center;gap:8px;}
.btn-register:hover{background:var(--violet-h);transform:translateY(-1px);}
.card-footer{text-align:center;margin-top:20px;font-size:12px;color:var(--muted);border-top:1px solid rgba(255,255,255,.06);padding-top:18px;}
.card-footer a{color:var(--violet-h);text-decoration:none;font-weight:600;}
.card-footer a:hover{text-decoration:underline;}
.secure-badge{display:inline-flex;align-items:center;gap:5px;font-size:11px;color:var(--muted);margin-top:12px;}
.secure-badge i{color:#4ade80;font-size:10px;}
@media(max-width:540px){.reg-card{padding:28px 18px 24px;margin:12px;}.form-grid{grid-template-columns:1fr;}.photo-upload-area{flex-direction:column;text-align:center;}}
</style>
</head>
<body>
<div class="bg-grid"></div>
<div class="orb orb-1"></div>
<div class="orb orb-2"></div>

<div class="reg-card">
    <div class="card-header">
        <img class="logo-ring" src="../image/pbi_logo" alt="PBI Logo"/>
        <div class="card-title">Executive Assistant</div>
        <div class="card-subtitle">Pandan Bay Institute — Evaluation System</div>
        <div class="role-pill"><i class="fa-solid fa-briefcase"></i> Executive Assistant Portal</div>
    </div>
    <div class="divider"></div>

    <?php if ($error): ?>
    <div class="alert alert-error">
        <i class="fa-solid fa-circle-exclamation" style="flex-shrink:0;margin-top:1px"></i>
        <span><?= htmlspecialchars($error) ?></span>
    </div>
    <?php endif; ?>

    <div class="info-note">
        <i class="fa-solid fa-circle-info"></i>
        <span>After registering, your account starts with <strong style="color:#fff">no feature access</strong>. The <strong style="color:#fff">System Admin</strong> must enable the features you need before you can use them.</span>
    </div>

    <form method="POST" action="executive_register.php" enctype="multipart/form-data" autocomplete="off">

        <!-- PHOTO -->
        <div class="section-label" style="margin-top:0">
            <i class="fa-solid fa-camera"></i> Profile Photo
        </div>
        <div class="photo-upload-area" onclick="document.getElementById('photoFile').click()">
            <div class="photo-preview-circle" id="photoCircle">
                <i class="fa-solid fa-user-tie ph-icon" id="phIcon"></i>
                <img id="photoPreviewImg" src="" alt="Preview"/>
            </div>
            <div class="photo-upload-info">
                <p>Upload your profile photo</p>
                <span>Will appear on your dashboard sidebar after login.<br>JPG, PNG, WebP — max 10MB</span>
                <button type="button" class="btn-choose-photo"
                        onclick="event.stopPropagation();document.getElementById('photoFile').click()">
                    <i class="fa-solid fa-upload"></i> Choose Photo
                </button>
                <div class="photo-name-tag" id="photoNameTag"></div>
            </div>
        </div>
        <input type="file" id="photoFile" name="photo"
               accept="image/jpeg,image/png,image/webp,image/gif"
               onchange="previewPhoto(this)"/>

        <!-- PERSONAL INFO -->
        <div class="section-label">
            <i class="fa-solid fa-user"></i> Personal Information
        </div>
        <div class="form-grid full">
            <div class="fg">
                <label>Full Name <span class="req">*</span></label>
                <div class="input-wrap">
                    <input class="form-input" type="text" name="full_name"
                           placeholder="Last Name, First Name M.I." required
                           value="<?= htmlspecialchars($_POST['full_name'] ?? '') ?>"/>
                    <i class="fa-solid fa-id-card f-icon"></i>
                </div>
            </div>
        </div>

        <!-- ACCOUNT -->
        <div class="section-label">
            <i class="fa-solid fa-circle-user"></i> Account Credentials
        </div>
        <div class="form-grid">
            <div class="fg">
                <label>Username <span class="req">*</span></label>
                <div class="input-wrap">
                    <input class="form-input" type="text" name="username"
                           placeholder="Choose a username" required
                           value="<?= htmlspecialchars($_POST['username'] ?? '') ?>"/>
                    <i class="fa-solid fa-user f-icon"></i>
                </div>
            </div>
            <div class="fg">
                <label>Email Address</label>
                <div class="input-wrap">
                    <input class="form-input" type="email" name="email"
                           placeholder="your@email.com (optional)"
                           value="<?= htmlspecialchars($_POST['email'] ?? '') ?>"/>
                    <i class="fa-solid fa-envelope f-icon"></i>
                </div>
            </div>
        </div>

        <!-- PASSWORD -->
        <div class="section-label">
            <i class="fa-solid fa-lock"></i> Set Password
        </div>
        <div class="form-grid">
            <div class="fg">
                <label>Password <span class="req">*</span></label>
                <div class="input-wrap">
                    <input class="form-input" type="password" id="pw1" name="password"
                           placeholder="Min. 8 characters" required
                           oninput="checkStrength(this.value)"/>
                    <i class="fa-solid fa-lock f-icon"></i>
                    <button type="button" class="toggle-pw" onclick="togglePw('pw1','e1')">
                        <i class="fa-solid fa-eye" id="e1"></i>
                    </button>
                </div>
                <div class="pw-strength-bar"><div class="pw-strength-fill" id="strengthFill"></div></div>
                <div class="pw-strength-hint" id="strengthHint"></div>
            </div>
            <div class="fg">
                <label>Confirm Password <span class="req">*</span></label>
                <div class="input-wrap">
                    <input class="form-input" type="password" id="pw2" name="confirm_password"
                           placeholder="Re-enter password" required/>
                    <i class="fa-solid fa-lock f-icon"></i>
                    <button type="button" class="toggle-pw" onclick="togglePw('pw2','e2')">
                        <i class="fa-solid fa-eye" id="e2"></i>
                    </button>
                </div>
            </div>
        </div>

        <button type="submit" class="btn-register">
            <i class="fa-solid fa-user-plus"></i> Create Executive Assistant Account
        </button>
    </form>

    <div class="card-footer">
        Already have an account? <a href="executive_login.php">Sign in here</a><br><br>
        <span class="secure-badge">
            <i class="fa-solid fa-circle-check"></i> Secured &amp; Encrypted Connection
        </span>
    </div>
</div>

<script>
function previewPhoto(input) {
    if (!input.files || !input.files[0]) return;
    const reader = new FileReader();
    reader.onload = e => {
        const img    = document.getElementById('photoPreviewImg');
        const icon   = document.getElementById('phIcon');
        const tag    = document.getElementById('photoNameTag');
        const circle = document.getElementById('photoCircle');
        img.src = e.target.result;
        img.style.display = 'block';
        icon.style.display = 'none';
        circle.style.borderColor = 'var(--violet)';
        tag.textContent = input.files[0].name;
        tag.classList.add('visible');
    };
    reader.readAsDataURL(input.files[0]);
}
function togglePw(id, ic) {
    const e = document.getElementById(id), i = document.getElementById(ic);
    e.type = e.type === 'password' ? 'text' : 'password';
    i.className = e.type === 'password' ? 'fa-solid fa-eye' : 'fa-solid fa-eye-slash';
}
function checkStrength(val) {
    const fill = document.getElementById('strengthFill');
    const hint = document.getElementById('strengthHint');
    let s = 0;
    if (val.length >= 8)          s++;
    if (/[A-Z]/.test(val))        s++;
    if (/[0-9]/.test(val))        s++;
    if (/[^A-Za-z0-9]/.test(val)) s++;
    const pct    = ['0%','25%','50%','75%','100%'];
    const colors = ['','#f87171','#fb923c','#facc15','#4ade80'];
    const labels = ['','Weak','Fair','Good','Strong'];
    fill.style.width      = val ? pct[s] : '0%';
    fill.style.background = colors[s] || '#f87171';
    hint.textContent      = val ? (labels[s] || 'Weak') : '';
    hint.style.color      = colors[s] || '#f87171';
}
</script>
</body>
</html>