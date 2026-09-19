<?php
// dean_account_settings.php
// Dean self-service account settings: profile details, profile photo,
// and password. Uses the same authentication/session path as the Dean portal.

session_set_cookie_params([
    'lifetime' => 0,
    'path'     => '/',
    'domain'   => '',
    'secure'   => false,
    'httponly' => true,
    'samesite' => 'Lax',
]);
session_start();
require_once 'db.php';

if (empty($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'dean') {
    header('Location: dean_login.php');
    exit;
}

if (empty($_SESSION['dean_settings_csrf'])) {
    $_SESSION['dean_settings_csrf'] = bin2hex(random_bytes(32));
}
$csrfToken = $_SESSION['dean_settings_csrf'];

$errors = [];
$success = null;
$userId = (int)$_SESSION['user_id'];

// Pull the current Dean profile before processing a request so all forms
// and the sidebar always reflect the latest saved data.
$stmt = $mysqli->prepare(
    "SELECT full_name, username, email, designation, photo, department, employee_id
     FROM users WHERE id = ? AND role = 'dean' LIMIT 1"
);
$stmt->bind_param('i', $userId);
$stmt->execute();
$me = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$me) {
    session_unset();
    session_destroy();
    header('Location: dean_login.php');
    exit;
}

$validateCsrf = function () use ($csrfToken): bool {
    return isset($_POST['csrf_token']) && hash_equals($csrfToken, (string)$_POST['csrf_token']);
};

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!$validateCsrf()) {
        $errors[] = 'Your session expired. Please refresh the page and try again.';
    } else {
        $action = $_POST['action'] ?? '';

        // ── PROFILE DETAILS ───────────────────────────────────────────
        if ($action === 'update_profile') {
            $newName  = trim((string)($_POST['full_name'] ?? ''));
            $newEmail = trim((string)($_POST['email'] ?? ''));

            if ($newName === '') {
                $errors[] = "Full name can't be empty.";
            } elseif (mb_strlen($newName) > 150) {
                $errors[] = 'Full name must be 150 characters or fewer.';
            }

            if ($newEmail === '' || !filter_var($newEmail, FILTER_VALIDATE_EMAIL)) {
                $errors[] = 'Please enter a valid email address.';
            } elseif (mb_strlen($newEmail) > 150) {
                $errors[] = 'Email must be 150 characters or fewer.';
            }

            if (!$errors) {
                try {
                    // Prevent changing the email to another account's email.
                    $check = $mysqli->prepare('SELECT id FROM users WHERE email = ? AND id <> ? LIMIT 1');
                    $check->bind_param('si', $newEmail, $userId);
                    $check->execute();
                    $duplicate = $check->get_result()->fetch_assoc();
                    $check->close();

                    if ($duplicate) {
                        $errors[] = 'That email address is already in use.';
                    } else {
                        $stmt = $mysqli->prepare('UPDATE users SET full_name = ?, email = ? WHERE id = ? AND role = \'dean\'');
                        $stmt->bind_param('ssi', $newName, $newEmail, $userId);
                        $stmt->execute();
                        $stmt->close();

                        $me['full_name'] = $newName;
                        $me['email'] = $newEmail;
                        $_SESSION['full_name'] = $newName;
                        $success = 'Profile details updated successfully.';
                    }
                } catch (mysqli_sql_exception $e) {
                    $errors[] = 'Unable to update your profile right now. Please try again.';
                }
            }
        }

        // ── PROFILE PHOTO ─────────────────────────────────────────────
        if ($action === 'change_photo') {
            if (empty($_FILES['photo']['name'])) {
                $errors[] = 'Please choose a photo to upload.';
            } else {
                $file = $_FILES['photo'];
                $allowed = [
                    'image/jpeg' => 'jpg',
                    'image/png'  => 'png',
                    'image/webp' => 'webp',
                ];

                $mime = '';
                if ($file['error'] === UPLOAD_ERR_OK && is_uploaded_file($file['tmp_name'])) {
                    $finfo = new finfo(FILEINFO_MIME_TYPE);
                    $mime = $finfo->file($file['tmp_name']) ?: '';
                }

                if ($file['error'] !== UPLOAD_ERR_OK) {
                    $errors[] = 'Photo upload failed. Please try again.';
                } elseif (!isset($allowed[$mime])) {
                    $errors[] = 'Please upload a JPG, PNG, or WEBP image.';
                } elseif ($file['size'] > 5 * 1024 * 1024) {
                    $errors[] = 'Photo must be smaller than 5MB.';
                } else {
                    $destDir = defined('UPLOAD_DIR') ? UPLOAD_DIR : dirname(__DIR__) . '/image/';
                    if (!is_dir($destDir) && !@mkdir($destDir, 0755, true)) {
                        $errors[] = 'The profile photo folder could not be created.';
                    } elseif (!is_writable($destDir)) {
                        $errors[] = 'The profile photo folder is not writable.';
                    } else {
                        $filename = 'dean_' . $userId . '_' . bin2hex(random_bytes(6)) . '.' . $allowed[$mime];
                        $destination = rtrim($destDir, '/\\') . DIRECTORY_SEPARATOR . $filename;

                        if (move_uploaded_file($file['tmp_name'], $destination)) {
                            $stmt = $mysqli->prepare("UPDATE users SET photo = ? WHERE id = ? AND role = 'dean'");
                            $stmt->bind_param('si', $filename, $userId);
                            $stmt->execute();
                            $stmt->close();

                            // Remove the previous Dean photo only after the new
                            // database value has been saved.
                            $oldPhoto = $me['photo'] ?? '';
                            if ($oldPhoto && basename($oldPhoto) === $oldPhoto) {
                                $oldPath = rtrim($destDir, '/\\') . DIRECTORY_SEPARATOR . $oldPhoto;
                                if (is_file($oldPath) && $oldPhoto !== $filename) {
                                    @unlink($oldPath);
                                }
                            }

                            $me['photo'] = $filename;
                            $success = 'Profile photo updated successfully.';
                        } else {
                            $errors[] = 'Could not save the uploaded photo. Please try again.';
                        }
                    }
                }
            }
        }

        // ── PASSWORD ──────────────────────────────────────────────────
        if ($action === 'change_password') {
            $current = (string)($_POST['current_password'] ?? '');
            $new     = (string)($_POST['new_password'] ?? '');
            $confirm = (string)($_POST['confirm_password'] ?? '');

            $stmt = $mysqli->prepare("SELECT password_hash FROM users WHERE id = ? AND role = 'dean' LIMIT 1");
            $stmt->bind_param('i', $userId);
            $stmt->execute();
            $row = $stmt->get_result()->fetch_assoc();
            $stmt->close();

            if (!$row || empty($row['password_hash']) || $row['password_hash'] === 'NOT NULL') {
                $errors[] = 'Could not verify your current password. Please contact the administrator.';
            } elseif (!password_verify($current, $row['password_hash'])) {
                $errors[] = 'Your current password is incorrect.';
            } elseif (strlen($new) < 8) {
                $errors[] = 'New password must be at least 8 characters.';
            } elseif ($new !== $confirm) {
                $errors[] = 'New password and confirmation do not match.';
            } elseif ($current === $new) {
                $errors[] = 'Your new password must be different from your current password.';
            } else {
                $hashed = password_hash($new, PASSWORD_DEFAULT);
                $stmt = $mysqli->prepare("UPDATE users SET password_hash = ? WHERE id = ? AND role = 'dean'");
                $stmt->bind_param('si', $hashed, $userId);
                $stmt->execute();
                $stmt->close();
                $success = 'Password changed successfully.';
            }
        }
    }
}

$photoUrl = !empty($me['photo']) ? UPLOAD_URL . $me['photo'] : '../background.png';
$displayDesignation = trim((string)($me['designation'] ?? '')) ?: 'Dean';
$displayDepartment = trim((string)($me['department'] ?? '')) ?: 'Higher Education Division';
$displayEmployeeId = trim((string)($me['employee_id'] ?? '')) ?: 'Not assigned';

$active = 'settings';
$sidebarScope = 'Higher Education Division';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>PBI — Dean Account Settings</title>
<link href="https://fonts.googleapis.com/css2?family=Rajdhani:wght@600;700&family=DM+Sans:wght@400;500;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<style>
:root{--dark:#0A192F;--mid:#172A45;--inner:#0F1F3D;--violet:#7C5FD9;--violet-h:#9C85F0;--light:#E0E6F0;--muted:#A0B3C6;--danger:#f05454;--good:#10B981;--shadow:0 8px 32px rgba(0,0,0,.45);--radius:12px}
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
body{min-height:100vh;background:linear-gradient(rgba(5,18,36,.72),rgba(5,18,36,.82)),url('../background.png') center center/cover no-repeat fixed;background-color:var(--dark);font-family:'DM Sans',sans-serif;color:var(--light);display:flex}
.sidebar{width:250px;flex-shrink:0;background:rgba(23,42,69,.9);border-right:1px solid rgba(255,255,255,.08);min-height:100vh;padding:28px 20px;display:flex;flex-direction:column}
.sb-profile{text-align:center;margin-bottom:26px}.sb-photo{width:72px;height:72px;border-radius:50%;object-fit:cover;border:2.5px solid var(--violet);box-shadow:0 0 18px rgba(124,95,217,.4);margin:0 auto 10px;display:block}.sb-name{font-weight:700;font-size:15px;color:#fff}.sb-role{font-size:11px;color:var(--violet-h);text-transform:uppercase;letter-spacing:.6px;margin-top:2px}.sb-scope{font-size:10px;color:var(--muted);margin-top:4px}.sb-nav{display:flex;flex-direction:column;gap:4px;margin-top:10px}.sb-nav a{display:flex;align-items:center;gap:10px;padding:11px 14px;border-radius:8px;color:var(--muted);text-decoration:none;font-size:14px;font-weight:500;transition:.2s}.sb-nav a:hover,.sb-nav a.active{background:rgba(124,95,217,.15);color:#fff}.sb-nav a i{width:18px;text-align:center;color:var(--violet-h)}.sb-logout{margin-top:auto}.sb-logout a{display:flex;align-items:center;gap:10px;padding:11px 14px;border-radius:8px;color:#fca5a5;text-decoration:none;font-size:14px;font-weight:500}.sb-logout a:hover{background:rgba(240,84,84,.12)}
.main{flex:1;padding:36px 44px;min-width:0}.page-header{margin-bottom:26px}.page-title{font-family:'Rajdhani',sans-serif;font-size:30px;font-weight:700;color:#fff;letter-spacing:1px}.page-sub{font-size:13px;color:var(--muted);margin-top:4px}
.alert{border-radius:10px;padding:12px 15px;font-size:13px;margin-bottom:18px;display:flex;align-items:center;gap:9px}.alert.success{background:rgba(16,185,129,.12);border:1px solid rgba(16,185,129,.3);color:#86efac}.alert.error{background:rgba(240,84,84,.12);border:1px solid rgba(240,84,84,.3);color:#ff9a9a}
.section{background:rgba(23,42,69,.86);border:1px solid rgba(255,255,255,.08);border-radius:14px;padding:24px;box-shadow:var(--shadow);margin-bottom:22px}.section h2{font-family:'Rajdhani',sans-serif;font-size:20px;color:#fff;margin-bottom:18px;display:flex;align-items:center;gap:9px}.section h2 i{color:var(--violet-h);font-size:17px}
.profile-card{display:flex;align-items:center;gap:24px}.profile-photo-lg{width:96px;height:96px;border-radius:50%;object-fit:cover;border:3px solid var(--violet);box-shadow:0 0 22px rgba(124,95,217,.3);background:var(--inner)}.photo-help{font-size:12px;color:var(--muted);margin-top:8px;line-height:1.5}
.form-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:17px 20px;margin-bottom:20px}.form-group{display:flex;flex-direction:column;gap:7px}.form-group label{font-size:11px;font-weight:600;letter-spacing:.8px;text-transform:uppercase;color:var(--muted)}.form-group input{width:100%;padding:12px 13px;background:rgba(10,25,47,.72);border:1px solid rgba(255,255,255,.1);border-radius:9px;color:var(--light);font:14px 'DM Sans',sans-serif;outline:none;transition:.2s}.form-group input:focus{border-color:var(--violet);box-shadow:0 0 0 3px rgba(124,95,217,.16)}.form-group input:disabled{opacity:.65;cursor:not-allowed;background:rgba(10,25,47,.5)}
.btn-primary{padding:11px 17px;border:0;border-radius:9px;background:var(--violet);color:#fff;font:600 14px 'DM Sans',sans-serif;cursor:pointer;box-shadow:0 4px 14px rgba(124,95,217,.3);transition:.2s}.btn-primary:hover{background:var(--violet-h);transform:translateY(-1px)}.btn-secondary{padding:11px 17px;border:1px solid rgba(255,255,255,.12);border-radius:9px;background:rgba(10,25,47,.5);color:var(--light);font:600 14px 'DM Sans',sans-serif;cursor:pointer}
.password-note{font-size:12px;color:var(--muted);margin:-5px 0 18px}.info-grid{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:14px;margin-bottom:20px}.info-item{padding:14px;border-radius:10px;background:rgba(10,25,47,.42);border:1px solid rgba(255,255,255,.06)}.info-item span{display:block;font-size:10px;text-transform:uppercase;letter-spacing:.7px;color:var(--muted);margin-bottom:5px}.info-item strong{font-size:14px;color:#fff;font-weight:600;word-break:break-word}
@media(max-width:900px){.sidebar{width:220px}.main{padding:28px 24px}.form-grid,.info-grid{grid-template-columns:1fr}}@media(max-width:650px){body{display:block}.sidebar{width:100%;min-height:auto;padding:18px}.sb-nav{display:grid;grid-template-columns:repeat(2,1fr)}.sb-logout{margin-top:12px}.main{padding:24px 16px}.profile-card{align-items:flex-start;flex-direction:column}.form-grid,.info-grid{grid-template-columns:1fr}}
</style>
<link rel="stylesheet" href="includes/dean_light_theme.css"/>
</head>
<body>
<?php $photo_src = $photoUrl; include __DIR__ . '/includes/dean_sidebar.php'; ?>
<main class="main">
    <div class="page-header">
        <div class="page-title">Account Settings</div>
        <div class="page-sub">Manage your Dean profile, photo, and login credentials.</div>
    </div>

    <?php if ($success): ?><div class="alert success"><i class="fa-solid fa-circle-check"></i><?= htmlspecialchars($success) ?></div><?php endif; ?>
    <?php foreach ($errors as $err): ?><div class="alert error"><i class="fa-solid fa-circle-exclamation"></i><?= htmlspecialchars($err) ?></div><?php endforeach; ?>

    <div class="section">
        <h2><i class="fa-solid fa-id-badge"></i> Profile Photo</h2>
        <div class="profile-card">
            <img class="profile-photo-lg" src="<?= htmlspecialchars($photoUrl) ?>" alt="Dean profile photo">
            <div>
                <form method="post" enctype="multipart/form-data">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
                    <input type="hidden" name="action" value="change_photo">
                    <input type="file" name="photo" accept="image/jpeg,image/png,image/webp" required>
                    <div style="margin-top:12px"><button class="btn-primary" type="submit"><i class="fa-solid fa-upload"></i> Upload New Photo</button></div>
                </form>
                <div class="photo-help">JPG, PNG, or WEBP only. Maximum file size: 5MB.</div>
            </div>
        </div>
    </div>

    <div class="section">
        <h2><i class="fa-solid fa-user-pen"></i> Profile Details</h2>
        <form method="post">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
            <input type="hidden" name="action" value="update_profile">
            <div class="form-grid">
                <div class="form-group"><label>Full Name</label><input type="text" name="full_name" maxlength="150" value="<?= htmlspecialchars($me['full_name'] ?? '') ?>" required></div>
                <div class="form-group"><label>Email</label><input type="email" name="email" maxlength="150" value="<?= htmlspecialchars($me['email'] ?? '') ?>" required></div>
                <div class="form-group"><label>Username</label><input type="text" value="<?= htmlspecialchars($me['username'] ?? '') ?>" disabled></div>
                <div class="form-group"><label>Employee ID</label><input type="text" value="<?= htmlspecialchars($displayEmployeeId) ?>" disabled></div>
                <div class="form-group"><label>Designation</label><input type="text" value="<?= htmlspecialchars($displayDesignation) ?>" disabled></div>
                <div class="form-group"><label>Department / Office</label><input type="text" value="<?= htmlspecialchars($displayDepartment) ?>" disabled></div>
            </div>
            <button class="btn-primary" type="submit"><i class="fa-solid fa-floppy-disk"></i> Save Changes</button>
        </form>
    </div>

    <div class="section">
        <h2><i class="fa-solid fa-lock"></i> Change Password</h2>
        <div class="password-note">Your new password must be at least 8 characters and different from your current password.</div>
        <form method="post" autocomplete="off">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
            <input type="hidden" name="action" value="change_password">
            <div class="form-grid">
                <div class="form-group"><label>Current Password</label><input type="password" name="current_password" minlength="1" required autocomplete="current-password"></div>
                <div class="form-group"></div>
                <div class="form-group"><label>New Password</label><input type="password" name="new_password" minlength="8" required autocomplete="new-password"></div>
                <div class="form-group"><label>Confirm New Password</label><input type="password" name="confirm_password" minlength="8" required autocomplete="new-password"></div>
            </div>
            <button class="btn-primary" type="submit"><i class="fa-solid fa-key"></i> Update Password</button>
        </form>
    </div>

    <div class="section">
        <h2><i class="fa-solid fa-circle-info"></i> Account Information</h2>
        <div class="info-grid">
            <div class="info-item"><span>Role</span><strong>Dean</strong></div>
            <div class="info-item"><span>Academic Scope</span><strong>Higher Education</strong></div>
            <div class="info-item"><span>Account Status</span><strong>Approved</strong></div>
        </div>
    </div>
</main>
</body>
<link rel="stylesheet" href="includes/dean_light_theme.css" id="dean-light-theme-final"/>
</html>
<?php $mysqli->close(); ?>
