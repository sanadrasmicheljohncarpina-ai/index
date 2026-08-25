<?php
// admin/force_password_change.php
// Any dashboard's login script should redirect here if
// $_SESSION['must_change_password'] == 1 after login succeeds.
session_start();
require_once 'db.php';

if (empty($_SESSION['user_id'])) {
    header("Location: login.php"); exit;
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $password = $_POST['password'] ?? '';
    $confirm  = $_POST['confirm_password'] ?? '';

    if (strlen($password) < 8) {
        $error = "Password must be at least 8 characters.";
    } elseif ($password !== $confirm) {
        $error = "Passwords do not match.";
    } else {
        $hash = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $mysqli->prepare("UPDATE users SET password_hash = ?, must_change_password = 0 WHERE id = ?");
        $stmt->bind_param("si", $hash, $_SESSION['user_id']);
        $stmt->execute();
        $stmt->close();

        $_SESSION['must_change_password'] = 0;

        // Send them to their role's dashboard
        $dest = match($_SESSION['role'] ?? '') {
            'superadmin'           => 'admin_dashboard.php',
            'admin'                => 'admin_dashboard.php',
            'school_head'          => 'school_head_dashboard.php',
            'faculty', 'staff'     => 'staff_dashboard.php',
            'student'              => 'student_dashboard.php',
            default                => 'login.php',
        };
        header("Location: $dest"); exit;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"/>
<meta name="viewport" content="width=device-width, initial-scale=1.0"/>
<title>Set New Password — PBI</title>
<link href="https://fonts.googleapis.com/css2?family=Rajdhani:wght@600;700&family=DM+Sans:wght@400;500;600&display=swap" rel="stylesheet"/>
<style>
:root{--dark:#0A192F;--mid:#172A45;--inner:#0F1F3D;--accent:#2B6CB0;--hover:#4C78B8;--light:#E0E6F0;--muted:#A0B3C6;--danger:#F05454;--border:rgba(255,255,255,0.08);--radius:10px;}
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0;}
body{font-family:'DM Sans',sans-serif;background:var(--dark);color:var(--light);min-height:100vh;display:flex;align-items:center;justify-content:center;padding:20px;}
.card{background:var(--mid);border:1px solid var(--border);border-radius:16px;padding:34px;width:100%;max-width:400px;}
.card h1{font-family:'Rajdhani',sans-serif;font-size:22px;color:#fff;margin-bottom:6px;}
.card p{font-size:13px;color:var(--muted);margin-bottom:22px;line-height:1.6;}
.fg{display:flex;flex-direction:column;gap:5px;margin-bottom:14px;}
.fg label{font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.7px;color:var(--muted);}
.fg input{background:var(--inner);border:1px solid var(--border);border-radius:8px;padding:10px 12px;color:var(--light);font-size:13px;outline:none;width:100%;}
.fg input:focus{border-color:var(--accent);}
.error{background:rgba(240,84,84,.12);border:1px solid rgba(240,84,84,.3);color:#fca5a5;padding:10px 14px;border-radius:8px;font-size:12px;margin-bottom:16px;}
.btn{width:100%;padding:11px;background:var(--accent);border:none;border-radius:var(--radius);color:#fff;font-size:14px;font-weight:600;cursor:pointer;margin-top:6px;}
.btn:hover{background:var(--hover);}
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
</head>
<body>
<div class="card">
    <h1>Set Your Password</h1>
    <p>Your account was created by an administrator. For security, you must set your own password before continuing.</p>
    <?php if ($error): ?><div class="error"><?= htmlspecialchars($error) ?></div><?php endif; ?>
    <form method="POST">
        <div class="fg"><label>New Password</label><input type="password" name="password" required minlength="8"/></div>
        <div class="fg"><label>Confirm New Password</label><input type="password" name="confirm_password" required minlength="8"/></div>
        <button type="submit" class="btn"><i class="fa-solid fa-key"></i> Set Password &amp; Continue</button>
    </form>
</div>
</body>
</html>