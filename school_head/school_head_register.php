    <?php
// school_head/school_head_register.php
session_start();
require_once 'db.php';

// Auto-fix: ensure school_head exists in the role ENUM
$mysqli->query("ALTER TABLE users MODIFY COLUMN role ENUM('superadmin','admin','school_head','faculty','staff','student') NOT NULL DEFAULT 'student'");

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $full_name   = trim($_POST['full_name']       ?? '');
    $username    = trim($_POST['username']         ?? '');
    $email       = trim($_POST['email']            ?? '');
    $password    = $_POST['password']              ?? '';
    $confirm_pw  = $_POST['confirm_password']      ?? '';
    $school_name = trim($_POST['school_name']      ?? '');
    $department  = trim($_POST['department']       ?? '');

    // ── VALIDATION ────────────────────────────────────────────
    if (empty($full_name))             { $error = "Full name is required."; }
    elseif (empty($username))          { $error = "Username is required."; }
    elseif (empty($password))          { $error = "Password is required."; }
    elseif (strlen($password) < 8)     { $error = "Password must be at least 8 characters."; }
    elseif ($password !== $confirm_pw) { $error = "Passwords do not match."; }
    else {
        $chk = $mysqli->prepare("SELECT id FROM users WHERE username = ? LIMIT 1");
        $chk->bind_param("s", $username); $chk->execute(); $chk->store_result();
        if ($chk->num_rows > 0) $error = "Username already taken. Please choose another.";
        $chk->close();

        if (empty($error) && !empty($email)) {
            $chk2 = $mysqli->prepare("SELECT id FROM users WHERE email = ? LIMIT 1");
            $chk2->bind_param("s", $email); $chk2->execute(); $chk2->store_result();
            if ($chk2->num_rows > 0) $error = "Email already registered.";
            $chk2->close();
        }
    }

    // ── PHOTO UPLOAD ──────────────────────────────────────────
    $photo_filename = null;
    if (empty($error) && isset($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
        $allowed_types = ['image/jpeg','image/png','image/webp','image/gif'];
        $file_type     = mime_content_type($_FILES['photo']['tmp_name']);
        $file_size     = $_FILES['photo']['size'];

        if (!in_array($file_type, $allowed_types)) {
            $error = "Photo must be JPG, PNG, WebP, or GIF.";
        } elseif ($file_size > 10 * 1024 * 1024) {
            $error = "Photo must be under 10 MB.";
        } else {
            $ext            = strtolower(pathinfo($_FILES['photo']['name'], PATHINFO_EXTENSION));
            $photo_filename = uniqid('sh_', true) . '.' . $ext;
            if (!is_dir(UPLOAD_DIR)) mkdir(UPLOAD_DIR, 0755, true);
            if (!move_uploaded_file($_FILES['photo']['tmp_name'], UPLOAD_DIR . $photo_filename)) {
                $error = "Failed to save photo. Check folder permissions on /image/.";
                $photo_filename = null;
            }
        }
    }

    // ── INSERT ────────────────────────────────────────────────
    if (empty($error)) {
        $hash        = password_hash($password, PASSWORD_DEFAULT);
        $role        = 'school_head';
        $designation = 'School Head' . (!empty($department) ? " — $department" : '');

        $stmt = $mysqli->prepare(
            "INSERT INTO users (full_name, username, email, password_hash, role, designation, photo, is_active, created_at)
             VALUES (?, ?, ?, ?, ?, ?, ?, 1, NOW())"
        );
        $stmt->bind_param("sssssss",
            $full_name, $username, $email,
            $hash, $role, $designation,
            $photo_filename
        );

        if ($stmt->execute()) {
            $stmt->close();
            $mysqli->close();
            $_SESSION['reg_success'] = "Account created! You can now log in as School Head.";
            header("Location: school_head_login.php");
            exit;
        } else {
            $error = "Registration failed: " . $mysqli->error;
            $stmt->close();
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
<title>PBI — School Head Registration</title>
<link href="https://fonts.googleapis.com/css2?family=Rajdhani:wght@600;700&family=DM+Sans:wght@400;500;600&display=swap" rel="stylesheet"/>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css"/>
<style>
:root{
    --dark:#0A192F;--mid:#172A45;--inner:#0F1F3D;
    --emerald:#059669;--emerald-h:#10B981;
    --light:#E0E6F0;--muted:#A0B3C6;
    --danger:#F05454;--border:rgba(255,255,255,0.08);
    --radius:10px;--shadow:0 8px 32px rgba(0,0,0,0.45);
}
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0;}
body{min-height:100vh;background:var(--dark);font-family:'DM Sans',sans-serif;color:var(--light);display:flex;align-items:center;justify-content:center;padding:40px 20px;position:relative;overflow-x:hidden;}
.bg-grid{position:fixed;inset:0;z-index:0;background-image:linear-gradient(rgba(5,150,105,.06) 1px,transparent 1px),linear-gradient(90deg,rgba(5,150,105,.06) 1px,transparent 1px);background-size:48px 48px;animation:g 22s linear infinite;}
@keyframes g{0%{background-position:0 0}100%{background-position:48px 48px}}
.orb{position:fixed;border-radius:50%;filter:blur(90px);z-index:0;pointer-events:none;}
.orb-1{width:380px;height:380px;background:radial-gradient(circle,rgba(5,150,105,.18) 0%,transparent 70%);bottom:-80px;right:-80px;animation:o1 16s ease-in-out infinite;}
.orb-2{width:280px;height:280px;background:radial-gradient(circle,rgba(43,108,176,.14) 0%,transparent 70%);top:-60px;left:-60px;animation:o2 20s ease-in-out infinite;}
@keyframes o1{0%,100%{transform:translate(0,0)}50%{transform:translate(-22px,-18px)}}
@keyframes o2{0%,100%{transform:translate(0,0)}50%{transform:translate(18px,16px)}}

.reg-card{position:relative;z-index:10;background:rgba(23,42,69,.88);backdrop-filter:blur(20px);border:1px solid rgba(255,255,255,.09);border-radius:20px;padding:44px 44px 40px;width:100%;max-width:560px;box-shadow:var(--shadow),0 0 0 1px rgba(5,150,105,.12);animation:cardIn .65s cubic-bezier(.22,1,.36,1) both;}
@keyframes cardIn{from{opacity:0;transform:translateY(28px) scale(.97)}to{opacity:1;transform:none}}

/* HEADER */
.card-header{text-align:center;margin-bottom:28px;}
.logo-ring{width:72px;height:72px;border-radius:50%;display:block;object-fit:cover;border:2.5px solid var(--emerald);box-shadow:0 0 22px rgba(5,150,105,.4);margin:0 auto 14px;}
.card-title{font-family:'Rajdhani',sans-serif;font-size:26px;font-weight:700;letter-spacing:2px;color:#fff;text-transform:uppercase;}
.card-subtitle{font-size:12px;color:var(--muted);letter-spacing:1.2px;text-transform:uppercase;margin-top:4px;}
.role-pill{display:inline-flex;align-items:center;gap:6px;background:rgba(5,150,105,.15);border:1px solid rgba(5,150,105,.35);color:var(--emerald-h);font-size:11px;font-weight:700;padding:4px 14px;border-radius:20px;text-transform:uppercase;letter-spacing:.8px;margin-top:10px;}

.divider{height:1px;background:linear-gradient(90deg,transparent,rgba(5,150,105,.45),transparent);margin-bottom:24px;}

/* ALERTS */
.alert{display:flex;align-items:flex-start;gap:9px;border-radius:8px;padding:11px 14px;font-size:13px;margin-bottom:18px;}
.alert-error{background:rgba(240,84,84,.12);border:1px solid rgba(240,84,84,.3);color:#fca5a5;}

/* PHOTO UPLOAD */
.photo-upload-area{display:flex;align-items:center;gap:20px;background:rgba(10,25,47,.5);border:1px dashed rgba(5,150,105,.35);border-radius:14px;padding:18px 20px;margin-bottom:22px;cursor:pointer;transition:border-color .2s,background .2s;}
.photo-upload-area:hover{border-color:var(--emerald);background:rgba(5,150,105,.06);}
.photo-preview-circle{width:76px;height:76px;border-radius:50%;background:var(--inner);border:2.5px solid rgba(5,150,105,.4);display:flex;align-items:center;justify-content:center;overflow:hidden;flex-shrink:0;transition:border-color .2s;position:relative;}
.photo-preview-circle img{width:100%;height:100%;object-fit:cover;display:none;position:absolute;inset:0;}
.photo-preview-circle .ph-icon{color:var(--muted);font-size:26px;transition:color .2s;}
.photo-upload-area:hover .photo-preview-circle{border-color:var(--emerald);}
.photo-upload-area:hover .ph-icon{color:var(--emerald-h);}
.photo-upload-info p{font-size:14px;font-weight:700;color:#fff;margin-bottom:3px;}
.photo-upload-info span{font-size:12px;color:var(--muted);line-height:1.5;display:block;}
.btn-choose-photo{display:inline-flex;align-items:center;gap:6px;margin-top:10px;background:rgba(5,150,105,.15);border:1px solid rgba(5,150,105,.35);color:var(--emerald-h);padding:7px 16px;border-radius:7px;font-size:12px;font-weight:600;cursor:pointer;transition:all .2s;font-family:'DM Sans',sans-serif;}
.btn-choose-photo:hover{background:rgba(5,150,105,.28);}
input[type="file"]{display:none;}
.photo-name-tag{font-size:11px;color:var(--emerald-h);margin-top:6px;display:none;font-weight:600;}
.photo-name-tag.visible{display:block;}

/* SECTION LABELS */
.section-label{font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:1px;color:var(--emerald-h);margin-bottom:12px;margin-top:22px;display:flex;align-items:center;gap:7px;padding-bottom:6px;border-bottom:1px solid rgba(5,150,105,.15);}
.section-label:first-of-type{margin-top:0;}

/* FORM */
.form-grid{display:grid;grid-template-columns:1fr 1fr;gap:14px;margin-bottom:6px;}
.form-grid.full{grid-template-columns:1fr;}
.fg{display:flex;flex-direction:column;gap:5px;}
.fg label{font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.7px;color:var(--muted);}
.req{color:#f87171;}
.input-wrap{position:relative;}
.input-wrap .f-icon{position:absolute;left:13px;top:50%;transform:translateY(-50%);color:var(--muted);font-size:13px;pointer-events:none;}
.form-input{width:100%;padding:11px 13px 11px 38px;background:rgba(10,25,47,.7);border:1px solid rgba(255,255,255,.1);border-radius:var(--radius);color:var(--light);font-size:14px;font-family:'DM Sans',sans-serif;outline:none;transition:border-color .25s,box-shadow .25s;}
.form-input::placeholder{color:rgba(160,179,198,.42);}
.form-input:focus{border-color:var(--emerald);box-shadow:0 0 0 3px rgba(5,150,105,.18);}
.toggle-pw{position:absolute;right:12px;top:50%;transform:translateY(-50%);background:none;border:none;color:var(--muted);cursor:pointer;font-size:13px;padding:0;transition:color .2s;}
.toggle-pw:hover{color:var(--light);}

/* PASSWORD STRENGTH */
.pw-strength-bar{height:3px;border-radius:2px;background:rgba(255,255,255,.08);margin-top:6px;overflow:hidden;}
.pw-strength-fill{height:100%;border-radius:2px;transition:width .3s,background .3s;width:0%;}
.pw-strength-hint{font-size:11px;margin-top:3px;color:var(--muted);}

/* SUBMIT */
.btn-register{width:100%;padding:13px;background:var(--emerald);border:none;border-radius:var(--radius);color:#fff;font-size:15px;font-weight:600;font-family:'DM Sans',sans-serif;cursor:pointer;margin-top:22px;transition:background .2s,transform .15s;box-shadow:0 4px 16px rgba(5,150,105,.38);display:flex;align-items:center;justify-content:center;gap:8px;}
.btn-register:hover{background:var(--emerald-h);transform:translateY(-1px);}

.card-footer{text-align:center;margin-top:20px;font-size:12px;color:var(--muted);border-top:1px solid rgba(255,255,255,.06);padding-top:18px;}
.card-footer a{color:var(--emerald-h);text-decoration:none;font-weight:600;}
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
        <img class="logo-ring" src="../image/pbi_logo	" alt="PBI Logo"/>
        <div class="card-title">School Head Registration</div>
        <div class="card-subtitle">Pandan Bay Institute — Evaluation System</div>
        <div class="role-pill"><i class="fa-solid fa-user-tie"></i> School Head Access</div>
    </div>

    <div class="divider"></div>

    <?php if ($error): ?>
    <div class="alert alert-error">
        <i class="fa-solid fa-circle-exclamation" style="flex-shrink:0;margin-top:1px"></i>
        <span><?= htmlspecialchars($error) ?></span>
    </div>
    <?php endif; ?>

    <form method="POST" action="school_head_register.php"
          enctype="multipart/form-data" autocomplete="off">

        <!-- PROFILE PHOTO -->
        <div class="section-label">
            <i class="fa-solid fa-camera"></i> Profile Photo
        </div>

        <div class="photo-upload-area" onclick="document.getElementById('photoFile').click()">
            <div class="photo-preview-circle" id="photoCircle">
                <i class="fa-solid fa-user-tie ph-icon" id="phIcon"></i>
                <img id="photoPreviewImg" src="" alt="Preview"/>
            </div>
            <div class="photo-upload-info">
                <p>Upload your profile photo</p>
                <span>This will appear on your dashboard sidebar<br>after you log in.</span>
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
        <div class="section-label" style="margin-top:10px;">
            <i class="fa-solid fa-user"></i> Personal Information
        </div>
        <div class="form-grid full" style="margin-bottom:14px;">
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
        <div class="form-grid" style="margin-bottom:14px;">
            <div class="fg">
                <label>School</label>
                <div class="input-wrap">
                    <input class="form-input" type="text" name="school_name"
                           placeholder="e.g. Pandan Bay Institute"
                           value="<?= htmlspecialchars($_POST['school_name'] ?? '') ?>"/>
                    <i class="fa-solid fa-school f-icon"></i>
                </div>
            </div>
            <div class="fg">
                <label>Department / Level</label>
                <div class="input-wrap">
                    <input class="form-input" type="text" name="department"
                           placeholder="e.g. JHS, SHS, College"
                           value="<?= htmlspecialchars($_POST['department'] ?? '') ?>"/>
                    <i class="fa-solid fa-building-columns f-icon"></i>
                </div>
            </div>
        </div>

        <!-- ACCOUNT INFO -->
        <div class="section-label">
            <i class="fa-solid fa-circle-user"></i> Account Credentials
        </div>
        <div class="form-grid" style="margin-bottom:14px;">
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
            <i class="fa-solid fa-user-plus"></i> Create School Head Account
        </button>
    </form>

    <div class="card-footer">
        Already have an account? <a href="school_head_login.php">Sign in here</a><br><br>
        <span class="secure-badge">
            <i class="fa-solid fa-circle-check"></i> Secured &amp; Encrypted Connection
        </span>
    </div>
</div>

<script>
function previewPhoto(input) {
    if (!input.files || !input.files[0]) return;
    const file   = input.files[0];
    const reader = new FileReader();
    reader.onload = e => {
        const img  = document.getElementById('photoPreviewImg');
        const icon = document.getElementById('phIcon');
        const tag  = document.getElementById('photoNameTag');
        const circle = document.getElementById('photoCircle');

        img.src = e.target.result;
        img.style.display = 'block';
        icon.style.display = 'none';
        circle.style.borderColor = 'var(--emerald)';

        tag.textContent = file.name;
        tag.classList.add('visible');
    };
    reader.readAsDataURL(file);
}

function togglePw(id, ic) {
    const e = document.getElementById(id), i = document.getElementById(ic);
    e.type = e.type === 'password' ? 'text' : 'password';
    i.className = e.type === 'password' ? 'fa-solid fa-eye' : 'fa-solid fa-eye-slash';
}

function checkStrength(val) {
    const fill = document.getElementById('strengthFill');
    const hint = document.getElementById('strengthHint');
    let score = 0;
    if (val.length >= 8)           score++;
    if (/[A-Z]/.test(val))         score++;
    if (/[0-9]/.test(val))         score++;
    if (/[^A-Za-z0-9]/.test(val))  score++;
    const pct    = ['0%','25%','50%','75%','100%'];
    const colors = ['','#f87171','#fb923c','#facc15','#4ade80'];
    const labels = ['','Weak','Fair','Good','Strong'];
    fill.style.width      = val ? pct[score] : '0%';
    fill.style.background = colors[score] || '#f87171';
    hint.textContent      = val ? (labels[score] || 'Weak') : '';
    hint.style.color      = colors[score] || '#f87171';
}
</script>
</body>
</html>