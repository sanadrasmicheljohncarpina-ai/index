<?php
// school_head/school_head_login.php
session_start();
require_once 'db.php';

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
            "SELECT id, full_name, password_hash, role, designation, account_status
             FROM users WHERE username = ? AND role = 'school_head' AND is_active = 1 LIMIT 1"
        );
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $user = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if ($user && password_verify($password, $user['password_hash']) && $user['account_status'] !== 'approved') {
            $error = $user['account_status'] === 'pending'
                ? "Your account is awaiting admin approval. You'll be able to log in once it's approved."
                : "Your account has been blocked. Please contact the administrator.";
        } elseif ($user && password_verify($password, $user['password_hash'])) {
            session_regenerate_id(true);
            $_SESSION['user_id']              = $user['id'];
            $_SESSION['full_name']            = $user['full_name'];
            $_SESSION['role']                 = $user['role'];
            $_SESSION['designation']          = $user['designation'];
            $_SESSION['must_change_password'] = $user['must_change_password'];
            $mysqli->close();
            if ($user['must_change_password']) {
                header("Location: ../admin/force_password_change.php");
            } else {
                header("Location: school_head_dashboard.php");
            }
            exit;
        } else {
            $error = "Incorrect username or password.";
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
<title>PBI — School Head Portal</title>
<link href="https://fonts.googleapis.com/css2?family=Rajdhani:wght@600;700&family=DM+Sans:wght@400;500;600&display=swap" rel="stylesheet"/>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css"/>
<style>
:root{--dark:#0A192F;--mid:#172A45;--emerald:#059669;--emerald-h:#10B981;--light:#E0E6F0;--muted:#A0B3C6;--radius:10px;--shadow:0 8px 32px rgba(0,0,0,0.45);}
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0;}
body{min-height:100vh;background:var(--dark);font-family:'DM Sans',sans-serif;color:var(--light);display:flex;align-items:center;justify-content:center;overflow:hidden;position:relative;}
.bg-grid{position:fixed;inset:0;z-index:0;background-image:linear-gradient(rgba(5,150,105,.06) 1px,transparent 1px),linear-gradient(90deg,rgba(5,150,105,.06) 1px,transparent 1px);background-size:48px 48px;animation:g 22s linear infinite;}
@keyframes g{0%{background-position:0 0}100%{background-position:48px 48px}}
.orb{position:fixed;border-radius:50%;filter:blur(90px);z-index:0;pointer-events:none;}
.orb-1{width:380px;height:380px;background:radial-gradient(circle,rgba(5,150,105,.2) 0%,transparent 70%);top:-80px;right:-80px;animation:o1 14s ease-in-out infinite;}
.orb-2{width:300px;height:300px;background:radial-gradient(circle,rgba(43,108,176,.15) 0%,transparent 70%);bottom:-60px;left:-60px;animation:o2 18s ease-in-out infinite;}
@keyframes o1{0%,100%{transform:translate(0,0)}50%{transform:translate(-30px,25px)}}
@keyframes o2{0%,100%{transform:translate(0,0)}50%{transform:translate(25px,-20px)}}
.login-card{position:relative;z-index:10;background:rgba(23,42,69,.85);backdrop-filter:blur(18px);border:1px solid rgba(255,255,255,.09);border-radius:18px;padding:48px 44px 40px;width:100%;max-width:430px;box-shadow:var(--shadow),0 0 0 1px rgba(5,150,105,.15);animation:cardIn .7s cubic-bezier(.22,1,.36,1) both;}
@keyframes cardIn{from{opacity:0;transform:translateY(32px) scale(.97)}to{opacity:1;transform:none}}
.card-header{text-align:center;margin-bottom:28px;}
.logo-ring{width:76px;height:76px;border-radius:50%;display:block;object-fit:cover;border:2.5px solid var(--emerald);box-shadow:0 0 22px rgba(5,150,105,.45);margin:0 auto 16px;}
.card-title{font-family:'Rajdhani',sans-serif;font-size:26px;font-weight:700;letter-spacing:2px;color:#fff;text-transform:uppercase;}
.card-subtitle{font-size:12px;color:var(--muted);letter-spacing:1.2px;text-transform:uppercase;margin-top:4px;}
.role-pill{display:inline-flex;align-items:center;gap:6px;background:rgba(5,150,105,.15);border:1px solid rgba(5,150,105,.35);color:var(--emerald-h);font-size:11px;font-weight:700;padding:4px 14px;border-radius:20px;text-transform:uppercase;letter-spacing:.8px;margin-top:10px;}
.divider{height:1px;background:linear-gradient(90deg,transparent,rgba(5,150,105,.45),transparent);margin-bottom:24px;}
.form-group{margin-bottom:18px;}
.form-label{display:block;font-size:11px;font-weight:600;letter-spacing:1.2px;text-transform:uppercase;color:var(--muted);margin-bottom:7px;}
.input-wrap{position:relative;}
.f-icon{position:absolute;left:14px;top:50%;transform:translateY(-50%);color:var(--muted);font-size:14px;pointer-events:none;}
.form-input{width:100%;padding:12px 14px 12px 40px;background:rgba(10,25,47,.7);border:1px solid rgba(255,255,255,.1);border-radius:var(--radius);color:var(--light);font-size:14px;font-family:'DM Sans',sans-serif;outline:none;transition:border-color .25s,box-shadow .25s;}
.form-input::placeholder{color:rgba(160,179,198,.45);}
.form-input:focus{border-color:var(--emerald);box-shadow:0 0 0 3px rgba(5,150,105,.2);}
.toggle-pw{position:absolute;right:13px;top:50%;transform:translateY(-50%);background:none;border:none;color:var(--muted);cursor:pointer;font-size:14px;padding:0;}
.alert{border-radius:8px;padding:11px 14px;font-size:13px;margin-bottom:18px;display:flex;align-items:center;gap:8px;}
.alert-success{background:rgba(34,197,94,.1);border:1px solid rgba(34,197,94,.28);border-radius:8px;padding:11px 14px;font-size:13px;margin-bottom:18px;display:flex;align-items:center;gap:8px;color:#86efac;}
.alert-error{background:rgba(240,84,84,.12);border:1px solid rgba(240,84,84,.35);color:#ff8a8a;}
.btn-login{width:100%;padding:13px;background:var(--emerald);border:none;border-radius:var(--radius);color:#fff;font-size:15px;font-weight:600;font-family:'DM Sans',sans-serif;cursor:pointer;transition:background .2s,transform .15s;box-shadow:0 4px 16px rgba(5,150,105,.4);display:flex;align-items:center;justify-content:center;gap:8px;}
.btn-login:hover{background:var(--emerald-h);transform:translateY(-1px);}
.card-footer{text-align:center;margin-top:24px;font-size:12px;color:var(--muted);border-top:1px solid rgba(255,255,255,.06);padding-top:20px;}
.card-footer a{color:var(--emerald-h);text-decoration:none;font-weight:600;}
.card-footer a:hover{text-decoration:underline;}
.register-row{text-align:center;margin-top:14px;font-size:13px;color:var(--muted);}
.register-row a{color:var(--emerald-h);font-weight:600;text-decoration:none;}
.register-row a:hover{text-decoration:underline;}
.card-footer{text-align:center;margin-top:14px;font-size:12px;color:var(--muted);border-top:1px solid rgba(255,255,255,.06);padding-top:14px;}
.secure-badge{display:inline-flex;align-items:center;gap:5px;font-size:11px;color:var(--muted);margin-top:0;}
@media(max-width:480px){.login-card{padding:36px 20px 32px;margin:16px;}}
</style>
</head>
<body>
<div class="bg-grid"></div>
<div class="orb orb-1"></div>
<div class="orb orb-2"></div>

<div class="login-card">
    <div class="card-header">
        <img class="logo-ring" src="../image/pbi_logo" alt="PBI Logo"/>
        <div class="card-title">School Head Portal</div>
        <div class="card-subtitle">Pandan Bay Institute — Evaluation System</div>
        <div class="role-pill"><i class="fa-solid fa-user-tie"></i> School Head Access</div>
    </div>
    <div class="divider"></div>

<?php if ($success): ?>
<div class="alert alert-success">
    <i class="fa-solid fa-circle-check"></i><?= htmlspecialchars($success) ?>
</div>
<?php endif; ?>
    <?php if ($error): ?>
    <div class="alert alert-error">
        <i class="fa-solid fa-circle-exclamation"></i><?= htmlspecialchars($error) ?>
    </div>
    <?php endif; ?>

    <form method="POST" action="school_head_login.php">
        <div class="form-group">
            <label class="form-label">Username</label>
            <div class="input-wrap">
                <input class="form-input" type="text" name="username"
                       placeholder="Enter your username" required autocomplete="username"
                       value="<?= htmlspecialchars($_POST['username'] ?? '') ?>"/>
                <i class="fa-solid fa-user f-icon"></i>
            </div>
        </div>
        <div class="form-group">
            <label class="form-label">Password</label>
            <div class="input-wrap">
                <input class="form-input" type="password" id="password" name="password"
                       placeholder="Enter your password" required autocomplete="current-password"/>
                <i class="fa-solid fa-lock f-icon"></i>
                <button type="button" class="toggle-pw" onclick="togglePw()">
                    <i class="fa-solid fa-eye" id="eyeIcon"></i>
                </button>
            </div>
        </div>
        <button type="submit" class="btn-login">
            <i class="fa-solid fa-right-to-bracket"></i> Sign In as School Head
        </button>
    </form>

    <div class="register-row">
        New to the system? <a href="school_head_register.php">Register here</a>
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
</script>
</body>
</html>