<?php
require_once 'principal_common.php';

// principal_account_settings.php
// Principal self-service account settings: profile details, profile photo,
// and password. Structured the same way as dean_account_settings.php.

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

if (empty($_SESSION['principal_settings_csrf'])) {
    $_SESSION['principal_settings_csrf'] = bin2hex(random_bytes(32));
}
$csrfToken = $_SESSION['principal_settings_csrf'];

$errors = [];
$success = null;
$userId = (int)$_SESSION['user_id'];

// ── SECURITY QUESTIONS STATUS (for the Settings card) ────────
$sq_status = 'none'; // none | ok | retired
try {
    if (!function_exists('sq_load')) { require_once 'security.php'; }
    security_ensure_tables($mysqli);
    $sq_existing = sq_load($mysqli, $userId);
    if (count($sq_existing) === 3) { $sq_status = sq_needs_update($sq_existing) ? 'retired' : 'ok'; }
} catch (Throwable $e) { error_log('settings sq_load failed: ' . $e->getMessage()); }

$validateCsrf = function () use ($csrfToken): bool {
    return isset($_POST['csrf_token']) && hash_equals($csrfToken, (string)$_POST['csrf_token']);
};

// ═══════════════════════════════════════════════════════════
// HANDLE FORM SUBMISSIONS
// ═══════════════════════════════════════════════════════════
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!$validateCsrf()) {
        $errors[] = 'Your session expired. Please refresh the page and try again.';
    } else {
        $action = $_POST['action'] ?? '';

        // ── PROFILE DETAILS ───────────────────────────────────────────
        if ($action === 'update_profile') {
            $newName        = trim((string)($_POST['full_name'] ?? ''));
            $newUsername    = trim((string)($_POST['username'] ?? ''));
            $newDesignation = trim((string)($_POST['designation'] ?? ''));

            if ($newName === '') {
                $errors[] = "Full name can't be empty.";
            } elseif (mb_strlen($newName) > 150) {
                $errors[] = 'Full name must be 150 characters or fewer.';
            }

            if ($newDesignation === '') {
                $errors[] = "Designation can't be empty.";
            } elseif (mb_strlen($newDesignation) > 100) {
                $errors[] = 'Designation must be 100 characters or fewer.';
            }

            if ($newUsername === '') {
                $errors[] = "Username can't be empty.";
            } elseif (mb_strlen($newUsername) > 50) {
                $errors[] = 'Username must be 50 characters or fewer.';
            } elseif (preg_match('/\s/', $newUsername)) {
                $errors[] = 'Username cannot contain spaces.';
            }

            if (!$errors) {
                try {
                    // Keep the username unique while excluding this Principal.
                    $check = $mysqli->prepare('SELECT id FROM users WHERE username = ? AND id <> ? LIMIT 1');
                    $check->bind_param('si', $newUsername, $userId);
                    $check->execute();
                    $duplicate = $check->get_result()->fetch_assoc();
                    $check->close();

                    if ($duplicate) {
                        $errors[] = 'That username is already in use.';
                    } else {
                        $stmt = $mysqli->prepare('UPDATE users SET full_name = ?, username = ?, designation = ? WHERE id = ?');
                        $stmt->bind_param('sssi', $newName, $newUsername, $newDesignation, $userId);
                        $stmt->execute();
                        $stmt->close();

                        $me['full_name']   = $newName;
                        $me['username']    = $newUsername;
                        $me['designation'] = $newDesignation;
                        $_SESSION['full_name'] = $newName;
                        $_SESSION['username']  = $newUsername;
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
                    $destDir = defined('UPLOAD_DIR') ? UPLOAD_DIR : __DIR__ . '/../image/';
                    if (!is_dir($destDir) && !@mkdir($destDir, 0755, true)) {
                        $errors[] = 'The profile photo folder could not be created.';
                    } elseif (!is_writable($destDir)) {
                        $errors[] = 'The profile photo folder is not writable.';
                    } else {
                        $filename = 'principal_' . $userId . '_' . bin2hex(random_bytes(6)) . '.' . $allowed[$mime];
                        $destination = rtrim($destDir, '/\\') . DIRECTORY_SEPARATOR . $filename;

                        if (move_uploaded_file($file['tmp_name'], $destination)) {
                            $stmt = $mysqli->prepare('UPDATE users SET photo = ? WHERE id = ?');
                            $stmt->bind_param('si', $filename, $userId);
                            $stmt->execute();
                            $stmt->close();

                            // Remove the previous Principal photo only after the new
                            // database value has been saved. Only files this page created
                            // (principal_*) are deleted, never a shared/default image.
                            $oldPhoto = $me['photo'] ?? '';
                            if ($oldPhoto && basename($oldPhoto) === $oldPhoto
                                && strpos($oldPhoto, 'principal_') === 0 && $oldPhoto !== $filename) {
                                $oldPath = rtrim($destDir, '/\\') . DIRECTORY_SEPARATOR . $oldPhoto;
                                if (is_file($oldPath)) {
                                    @unlink($oldPath);
                                }
                            }

                            $me['photo'] = $filename;
                            $photo_src = '../image/' . $filename;
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

            // Read the stored password directly (no helper) and tolerate the
            // common column names, so a lookup problem can't masquerade as a
            // "could not verify" error.
            $pwCol = null;
            $row   = null;
            foreach (['password', 'password_hash'] as $col) {
                try {
                    $stmt = $mysqli->prepare("SELECT `$col` AS pw FROM users WHERE id = ? LIMIT 1");
                    $stmt->bind_param('i', $userId);
                    $stmt->execute();
                    $res = $stmt->get_result()->fetch_assoc();
                    $stmt->close();
                    if ($res && isset($res['pw']) && (string)$res['pw'] !== '') {
                        $pwCol = $col;
                        $row   = (string)$res['pw'];
                        break;
                    }
                } catch (Throwable $e) {
                    // Column doesn't exist; try the next candidate.
                }
            }

            // A stored value that isn't a password hash is a legacy plain-text
            // password: compare it directly (it is re-saved as a hash below).
            $currentOk = false;
            if ($row !== null) {
                $currentOk = !empty(password_get_info($row)['algo'])
                    ? password_verify($current, $row)
                    : hash_equals($row, $current);
            }

            if ($row === null) {
                $errors[] = 'Could not verify your current password. Please contact the administrator.';
            } elseif (!$currentOk) {
                $errors[] = 'Your current password is incorrect.';
            } elseif (strlen($new) < 8) {
                $errors[] = 'New password must be at least 8 characters.';
            } elseif ($new !== $confirm) {
                $errors[] = 'New password and confirmation do not match.';
            } elseif ($current === $new) {
                $errors[] = 'Your new password must be different from your current password.';
            } else {
                $hashed = password_hash($new, PASSWORD_DEFAULT);
                $stmt = $mysqli->prepare("UPDATE users SET `$pwCol` = ? WHERE id = ?");
                $stmt->bind_param('si', $hashed, $userId);
                $stmt->execute();
                $stmt->close();
                $success = 'Password changed successfully.';
            }
        }
    }
}

html_head_open('PBI — Account Settings');
render_principal_sidebar('settings', $me, $scopeLabel, $photo_src);
?>

<style id="principal-ea-logs-canvas-final">
/* EA System Logs-like visual treatment:
   white viewport + a subtle #F8FAFC feature canvas inset inside it. */
html, body {
  background: #FFFFFF !important;
  background-image: none !important;
  color: #172033 !important;
}

.main,
main.main,
.main-content,
.content,
.page-content {
  position: relative !important;
  isolation: isolate !important;
  background: #FFFFFF !important;
  color: #172033 !important;
  min-height: 100vh;
}

/* The feature canvas does NOT cover the whole white page. */
.main::before,
main.main::before {
  content: "";
  position: absolute;
  left: 16px;
  right: 0;
  top: 56px;
  bottom: 0;
  background: #F8FAFC !important;
  border-radius: 16px 0 0 0;
  pointer-events: none;
  z-index: -1;
}

/* Preserve the existing navy Principal sidebar. */
.sidebar {
  background: #0A192F !important;
}

/* Keep feature surfaces white, with only a very light edge/shadow. */
.main .page-header,
.main .section,
.main .card,
.main .panel,
.main .table-card,
.main .content-card,
.main .stat-card,
.main .period-strip,
.main .filter-bar,
.main .table-wrap,
.main .table-card-wrap,
.main .content-panel,
.main .eval-banner,
.main .info-banner,
.main .history-card,
.main .gl-card,
.main .amber-card,
.main .green-card,
.main .red-card,
.main .ra-table-card,
.main .eval-card,
.main .comment-section,
.main .avg-summary,
.main .standing-panel,
.main .person-row,
.main .target-card,
.main .no-eval,
.main .no-data,
.main .no-evaluated,
.main .no-archived,
.main .evaluator-grid,
.main .people-list,
.main .cat-section,
.main .results-card,
.main .result-card,
.main .feature-card {
  background: #FFFFFF !important;
  color: #172033 !important;
  border-color: #D9E4EF !important;
  box-shadow: 0 1px 5px rgba(15,23,42,.035) !important;
}

.main table thead th,
.main .table-head,
.main .table-header,
.main .thead,
.main .subtle-head {
  background: #F4F8FF !important;
  color: #4B6580 !important;
  border-color: #D9E4EF !important;
}

.main table tbody td {
  background: #FFFFFF !important;
  color: #172033 !important;
  border-color: #D9E4EF !important;
}

.main table tbody tr:hover td,
.main .person-row:hover,
.main .standing-item:hover {
  background: #F8FAFC !important;
}

.main input,
.main select,
.main textarea,
.main .search-box input[type=text],
.main .search-box select,
.main .form-group input,
.main .filter-field select,
.main .filter-field input[type=text],
.main .ra-filter-row select,
.main .ra-search,
.main .eval-comment-box {
  background: #FFFFFF !important;
  color: #172033 !important;
  border-color: #CBD5E1 !important;
}

.main .modal,
.main .modal-content,
.main .dropdown,
.main .menu,
.main .popover,
.main .panel {
  background: #FFFFFF !important;
  color: #172033 !important;
  border-color: #D9E4EF !important;
}

@media (max-width: 768px) {
  .main::before,
  main.main::before {
    left: 0;
    top: 52px;
    border-radius: 12px 0 0 0;
  }
}

@media print {
  html, body, .main, main.main, .main-content, .content, .page-content {
    background: #FFFFFF !important;
  }
  .main::before,
  main.main::before {
    display: none !important;
  }
}
</style>

<main class="main principal-internal-scroll">
    <div class="page-header">
        <div class="page-title"></div>
    </div>

    <?php if ($success): ?><div class="alert success"><i class="fa-solid fa-circle-check"></i> <?= htmlspecialchars($success) ?></div><?php endif; ?>
    <?php foreach ($errors as $err): ?><div class="alert error"><i class="fa-solid fa-circle-exclamation"></i> <?= htmlspecialchars($err) ?></div><?php endforeach; ?>

    <div class="settings-tabs" role="tablist" aria-label="Account settings sections">
        <button type="button" class="settings-tab" role="tab" id="tabBtnProfile" data-tab="profile" aria-controls="tabPanelProfile" aria-selected="true"><i class="fa-solid fa-user"></i> Profile &amp; Security</button>
        <button type="button" class="settings-tab" role="tab" id="tabBtnAppearance" data-tab="appearance" aria-controls="tabPanelAppearance" aria-selected="false" tabindex="-1"><i class="fa-solid fa-palette"></i> Appearance</button>
    </div>

    <div class="settings-tabpanel" role="tabpanel" id="tabPanelProfile" aria-labelledby="tabBtnProfile" data-panel="profile">
    <div class="section ac-card ac-c2">
        <div class="ac-head">
            <span class="ac-icon"><i class="fa-solid fa-id-badge"></i></span>
            <div>
                <h2>Profile Photo</h2>
                <p class="ac-sub">Update the photo shown in your sidebar and profile.</p>
            </div>
        </div>
        <div class="profile-card">
            <img class="profile-photo-lg" src="<?= htmlspecialchars($photo_src) ?>" alt="Principal profile photo">
            <div class="photo-form-wrap">
                <form method="post" enctype="multipart/form-data" style="display:flex;flex-direction:column;gap:10px;">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
                    <input type="hidden" name="action" value="change_photo">
                    <input type="file" name="photo" accept="image/jpeg,image/png,image/webp" required>
                    <button class="btn-primary" type="submit" style="width:fit-content;"><i class="fa-solid fa-upload"></i> Upload New Photo</button>
                </form>
                <div class="photo-help">JPG, PNG, or WEBP only. Maximum file size: 5MB.</div>
            </div>
        </div>
    </div>

    <div class="section ac-card ac-c3">
        <div class="ac-head">
            <span class="ac-icon"><i class="fa-solid fa-user-pen"></i></span>
            <div>
                <h2>Profile Details</h2>
                <p class="ac-sub">Update your name, username, and designation.</p>
            </div>
        </div>
        <form method="post">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
            <input type="hidden" name="action" value="update_profile">
            <div class="form-grid">
                <div class="form-group"><label>Full Name</label><input type="text" name="full_name" maxlength="150" value="<?= htmlspecialchars($me['full_name'] ?? '') ?>" required></div>
                <div class="form-group"><label>Username</label><input type="text" name="username" maxlength="50" value="<?= htmlspecialchars($me['username'] ?? '') ?>" required autocomplete="username"></div>
                <div class="form-group"><label>Designation</label><input type="text" name="designation" maxlength="100" value="<?= htmlspecialchars(($me['designation'] ?? '') !== '' ? $me['designation'] : 'Principal') ?>" placeholder="e.g. Principal" required></div>
            </div>
            <button class="btn-primary" type="submit"><i class="fa-solid fa-floppy-disk"></i> Save Changes</button>
        </form>
    </div>

    <div class="section ac-card ac-c4">
        <div class="ac-head">
            <span class="ac-icon"><i class="fa-solid fa-lock"></i></span>
            <div>
                <h2>Change Password</h2>
                <p class="ac-sub">Your new password must be at least 8 characters and different from your current password.</p>
            </div>
        </div>
        <form method="post" autocomplete="off">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
            <input type="hidden" name="action" value="change_password">
            <div class="form-grid">
                <div class="form-group"><label>Current Password</label><div class="pw-wrap"><input type="password" name="current_password" minlength="1" required autocomplete="current-password"><button type="button" class="pw-toggle" aria-label="Show password" aria-pressed="false" tabindex="0"><svg class="pw-eye" viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M1 12s4-7 11-7 11 7 11 7-4 7-11 7S1 12 1 12z"/><circle cx="12" cy="12" r="3"/></svg><svg class="pw-eye-off" viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M17.94 17.94A10.94 10.94 0 0 1 12 19C5 19 1 12 1 12a18.5 18.5 0 0 1 5.06-5.94"/><path d="M9.9 4.24A9.1 9.1 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19"/><path d="M14.12 14.12A3 3 0 1 1 9.88 9.88"/><line x1="1" y1="1" x2="23" y2="23"/></svg></button></div></div>
                <div class="form-group"></div>
                <div class="form-group"><label>New Password</label><div class="pw-wrap"><input type="password" name="new_password" minlength="8" required autocomplete="new-password"><button type="button" class="pw-toggle" aria-label="Show password" aria-pressed="false" tabindex="0"><svg class="pw-eye" viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M1 12s4-7 11-7 11 7 11 7-4 7-11 7S1 12 1 12z"/><circle cx="12" cy="12" r="3"/></svg><svg class="pw-eye-off" viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M17.94 17.94A10.94 10.94 0 0 1 12 19C5 19 1 12 1 12a18.5 18.5 0 0 1 5.06-5.94"/><path d="M9.9 4.24A9.1 9.1 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19"/><path d="M14.12 14.12A3 3 0 1 1 9.88 9.88"/><line x1="1" y1="1" x2="23" y2="23"/></svg></button></div></div>
                <div class="form-group"><label>Confirm New Password</label><div class="pw-wrap"><input type="password" name="confirm_password" minlength="8" required autocomplete="new-password"><button type="button" class="pw-toggle" aria-label="Show password" aria-pressed="false" tabindex="0"><svg class="pw-eye" viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M1 12s4-7 11-7 11 7 11 7-4 7-11 7S1 12 1 12z"/><circle cx="12" cy="12" r="3"/></svg><svg class="pw-eye-off" viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M17.94 17.94A10.94 10.94 0 0 1 12 19C5 19 1 12 1 12a18.5 18.5 0 0 1 5.06-5.94"/><path d="M9.9 4.24A9.1 9.1 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19"/><path d="M14.12 14.12A3 3 0 1 1 9.88 9.88"/><line x1="1" y1="1" x2="23" y2="23"/></svg></button></div></div>
            </div>
            <button class="btn-primary" type="submit"><i class="fa-solid fa-key"></i> Update Password</button>
        </form>
    </div>

    <div class="section ac-card ac-c6" id="security-questions-settings">
        <div class="ac-head">
            <span class="ac-icon"><i class="fa-solid fa-shield-halved"></i></span>
            <div>
                <h2>Security Questions</h2>
                <p class="ac-sub">These questions are used to recover your account if you forget your password.</p>
            </div>
        </div>
        <?php if ($sq_status === 'ok'): ?>
        <div class="sq-status ok"><i class="fa-solid fa-circle-check"></i> Security questions are set</div>
        <?php elseif ($sq_status === 'retired'): ?>
        <div class="sq-status warn"><i class="fa-solid fa-triangle-exclamation"></i> One of your questions is no longer offered &mdash; please update them</div>
        <?php else: ?>
        <div class="sq-status warn"><i class="fa-solid fa-triangle-exclamation"></i> Not set up yet</div>
        <?php endif; ?>
        <div>
            <a class="btn-primary" href="security_questions.php"><i class="fa-solid fa-pen-to-square"></i> <?= $sq_status === 'none' ? 'Set Security Questions' : 'Update Security Questions' ?></a>
        </div>
    </div>
    </div>

    <div class="settings-tabpanel" role="tabpanel" id="tabPanelAppearance" aria-labelledby="tabBtnAppearance" data-panel="appearance" hidden>
    <div class="section appearance-settings-section ac-card ac-c1" id="principal-appearance-settings">
        <div class="appearance-heading-row ac-head">
            <span class="ac-icon"><i class="fa-solid fa-palette"></i></span>
            <div>
                <h2>Appearance</h2>
                <p class="appearance-subtitle ac-sub">Choose your theme, content spacing, and motion preferences.</p>
            </div>
            <span class="appearance-device-badge"><i class="fa-solid fa-laptop"></i> Saved on this device</span>
        </div>

        <div class="appearance-feature-grid">
            <section class="appearance-control-card appearance-theme-card" aria-labelledby="appearanceThemeHeading">
                <div class="appearance-control-head">
                    <span class="appearance-control-icon"><i class="fa-solid fa-circle-half-stroke"></i></span>
                    <div>
                        <h3 id="appearanceThemeHeading">Theme mode</h3>
                        <p>Choose a look that suits your environment.</p>
                    </div>
                </div>
                <div class="appearance-theme-options" role="group" aria-label="Theme mode">
                    <button type="button" class="appearance-choice" id="appearanceLightBtn" data-theme-choice="light" aria-pressed="false" onclick="setPrincipalAppearance('light')">
                        <span class="appearance-choice-icon"><i class="fa-solid fa-sun"></i></span>
                        <span class="appearance-choice-copy"><strong>Light</strong><small>Bright and clean</small></span>
                        <span class="appearance-choice-check"><i class="fa-solid fa-check"></i></span>
                    </button>
                    <button type="button" class="appearance-choice" id="appearanceDarkBtn" data-theme-choice="dark" aria-pressed="false" onclick="setPrincipalAppearance('dark')">
                        <span class="appearance-choice-icon"><i class="fa-solid fa-moon"></i></span>
                        <span class="appearance-choice-copy"><strong>Dark</strong><small>Low-light friendly</small></span>
                        <span class="appearance-choice-check"><i class="fa-solid fa-check"></i></span>
                    </button>
                    <button type="button" class="appearance-choice" id="appearanceSystemBtn" data-theme-choice="system" aria-pressed="false" onclick="setPrincipalAppearance('system')">
                        <span class="appearance-choice-icon"><i class="fa-solid fa-desktop"></i></span>
                        <span class="appearance-choice-copy"><strong>System</strong><small>Follow device setting</small></span>
                        <span class="appearance-choice-check"><i class="fa-solid fa-check"></i></span>
                    </button>
                </div>
                <div class="appearance-inline-status"><i class="fa-solid fa-circle-info"></i> Current mode: <strong id="appearanceCurrentMode">Light</strong></div>
            </section>



            <section class="appearance-control-card" aria-labelledby="appearanceLayoutHeading">
                <div class="appearance-control-head">
                    <span class="appearance-control-icon"><i class="fa-solid fa-sliders"></i></span>
                    <div>
                        <h3 id="appearanceLayoutHeading">Interface spacing</h3>
                        <p>Choose how much space appears around tables and content.</p>
                    </div>
                </div>
                <div class="appearance-density-options" role="group" aria-label="Interface spacing">
                    <button type="button" class="appearance-density-choice" data-density-choice="comfortable" aria-pressed="false" onclick="setPrincipalDensity('comfortable')"><i class="fa-solid fa-expand"></i><span><strong>Comfortable</strong><small>More breathing room</small></span></button>
                    <button type="button" class="appearance-density-choice" data-density-choice="compact" aria-pressed="false" onclick="setPrincipalDensity('compact')"><i class="fa-solid fa-compress"></i><span><strong>Compact</strong><small>More content on screen</small></span></button>
                </div>
            </section>

            <section class="appearance-control-card appearance-accessibility-card" aria-labelledby="appearanceAccessibilityHeading">
                <div class="appearance-control-head">
                    <span class="appearance-control-icon"><i class="fa-solid fa-universal-access"></i></span>
                    <div>
                        <h3 id="appearanceAccessibilityHeading">Accessibility</h3>
                        <p>Reduce interface movement for a calmer experience.</p>
                    </div>
                </div>
                <button type="button" class="appearance-motion-toggle" id="appearanceMotionBtn" aria-pressed="false" onclick="togglePrincipalReducedMotion()">
                    <span class="appearance-motion-copy"><strong>Reduce animations</strong><small>Minimize transitions and motion effects</small></span>
                    <span class="appearance-switch-track" aria-hidden="true"><span class="appearance-switch-thumb"></span></span>
                </button>
                <div class="appearance-accessibility-note"><i class="fa-solid fa-shield-heart"></i> Your settings stay in this browser and do not change other users' views.</div>
            </section>
        </div>

    </div>
    </div>
</main>
<style id="principal-white-theme-final">
:root{
  --dark:#ffffff!important;
  --mid:#ffffff!important;
  --inner:#f5f7fb!important;
  --light:#172033!important;
  --muted:#64748b!important;
  --border:#e2e8f0!important;
  --shadow:0 4px 18px rgba(15,23,42,.08)!important;
  --page-bg:#ffffff!important;
  --card-bg:#ffffff!important;
  --card-border:#e2e8f0!important;
}
html{background:#F8FAFC!important;color-scheme:light!important;}
body{background:#F8FAFC!important;background-image:none!important;color:#172033!important;}

/* Keep the existing navy sidebar; the content area is the white-theme area. */
.sidebar{background:#0A192F!important;color:#E0E6F0!important;border-right:1px solid #172A45!important;}
.sidebar *{color:inherit;}
.sidebar .sb-name{color:#fff!important;}
.sidebar .sb-role,.sidebar .sb-nav a i{color:#f0b84d!important;}
.sidebar .sb-scope,.sidebar .sb-nav a{color:#A0B3C6!important;}
.sidebar .sb-nav a:hover,.sidebar .sb-nav a.active{background:rgba(217,154,43,.15)!important;color:#fff!important;}
.sidebar .sb-logout a{color:#fca5a5!important;}

/* Main content surfaces */
main,.main,.main-content,.content,.page-content{background:#F8FAFC!important;color:#172033!important;}
.stat-card,.section,.period-strip,.filter-bar,.card,.panel,.table-wrap,
.content-card,.table-card,.summary-card,.sum-card,.standing-panel,.eval-card,
.eval-banner,.info-banner,.history-card,.gl-card,.amber-card,.green-card,.red-card,
.person-row,.target-card,.no-eval,.no-data,.no-evaluated,.comment-section,
.avg-summary,.cat-section,.people-list,.evaluator-grid,.eval-q-card,.eval-table-wrap,
.received-item,.q-result,.info-grid>div,.comment-modal,.ra-table-card,
.eval-switcher,.tabs,.level-tabs,.status-tabs,.faculty-subtabs{
  background:#fff!important;
  color:#172033!important;
  border-color:#e2e8f0!important;
  box-shadow:var(--shadow)!important;
}

/* Headings and readable data text */
.page-title,.page-header h1,.section-title,.sheet-name,.target-name,
h1,h2,h3,h4,h5,h6,.section h2,.eval-modal-title,.modal-section-title,
.stat-card .num,.tracker-item .big,.eval-q-text,.eval-qtext,.q-text,.info-value,
.received-anon,.cat-name-modal,.cat-score-modal{color:#0f172a!important;}
.page-sub,.sheet-desig,.target-desig,.muted,.hint,.helper,.filter-hint,.empty-note,
.main p,.main label,.main td,.main li,.main small,.q-score,.q-no,.received-meta,
.info-label,.loading-eval,.eval-rating-scale-note,.eval-read-score{color:#64748b!important;}
table{color:#172033!important;}
table thead th,table.data th,.q-table th{background:#f8fafc!important;color:#334155!important;border-color:#e2e8f0!important;}
table tbody td,table.data td,.q-table td{color:#334155!important;border-color:#e2e8f0!important;}
table tbody tr:hover,.person-row:hover,.standing-item:hover{background:#f8fafc!important;}

/* Accent elements stay amber/semantic rather than reverting to dark-mode text. */
.stat-card i,.section h2 i,.eval-q-category,.eval-category-heading,
.eval-modal-title i,.period-item .v,.period-badge,.back-link,
.stat-card .label,.period-item .k,.received-score,.score-big,.cat-score-modal,
.bell-btn,.bell-head button,.bell-list li i,.eval-banner-title,.eval-banner-icon,
.cat-title,.comment-title,.search-box button,.section h2 i{color:#d99a2b!important;}
.period-badge{background:rgba(217,154,43,.12)!important;border-color:rgba(217,154,43,.28)!important;}
.period-badge.closed{background:rgba(240,84,84,.10)!important;border-color:rgba(240,84,84,.28)!important;color:#dc2626!important;}
.period-badge.gray{background:#f1f5f9!important;border-color:#cbd5e1!important;color:#64748b!important;}

/* Forms */
input,select,textarea,
.search-box input[type=text],.search-box select,.form-group input,
.filter-field select,.filter-field input[type=text],.ra-filter-row select,.ra-search,
.eval-comment-box{
  background:#fff!important;color:#172033!important;border-color:#cbd5e1!important;
}
input::placeholder,textarea::placeholder{color:#94a3b8!important;}
input:focus,select:focus,textarea:focus{border-color:#d99a2b!important;box-shadow:0 0 0 3px rgba(217,154,43,.10)!important;outline:none!important;}

/* Buttons / tabs */
.report-btns button,.qa-btns a,.filter-btns a,a.btn,
.btn,.action-btn,.back-btn,.btn-print,.btn-archive,.btn-restore,.btn-solid,
.btn-archived-link,.ra-tool-btn,.page-btn{
  background:rgba(217,154,43,.10)!important;
  border-color:rgba(217,154,43,.30)!important;
  color:#a16207!important;
}
.report-btns button:hover,.qa-btns a:hover,.filter-btns a:hover,a.btn:hover,
.btn:hover,.action-btn:hover,.back-btn:hover,.btn-print:hover,.btn-archive:hover,
.btn-restore:hover,.btn-archived-link:hover,.ra-tool-btn:hover,.page-btn:hover{
  background:rgba(217,154,43,.16)!important;color:#92400e!important;
}
.btn-primary,.btn-solid{background:#d99a2b!important;color:#0A192F!important;border-color:#d99a2b!important;}
.btn-primary:hover,.btn-solid:hover{background:#f0b84d!important;color:#0A192F!important;}
.report-btns a.active,.filter-btns a.active,.group-tab.active,
.eval-tab.student.active,.eval-tab.peer.active,.eval-tab.multi-role.active,
.tab.active,.level-tab.active,.status-tab.active,.desig-subtab.active,
.page-btn.active{background:rgba(217,154,43,.14)!important;color:#a16207!important;border-color:rgba(217,154,43,.35)!important;}
.eval-tab,.tab,.level-tab,.status-tab,.group-tab,.desig-subtab{color:#64748b!important;background:transparent!important;}
.eval-tab:hover,.tab:hover,.level-tab:hover,.status-tab:hover,.group-tab:hover,.desig-subtab:hover{color:#334155!important;background:#f8fafc!important;}

/* Evaluation questionnaire */
.eval-q-card,.eval-table-wrap{background:#fff!important;}
.eval-rating-opt{background:#f8fafc!important;color:#334155!important;border-color:#cbd5e1!important;}
.eval-rating-opt:has(input:checked){background:rgba(217,154,43,.10)!important;color:#92400e!important;border-color:#d99a2b!important;}
.eval-table th{background:#f8fafc!important;color:#64748b!important;border-color:#e2e8f0!important;}
.eval-table td{border-color:#e2e8f0!important;color:#334155!important;}
.eval-rating-cell label{background:#fff!important;color:#64748b!important;border-color:#cbd5e1!important;}
.eval-rating-cell label:hover{background:rgba(217,154,43,.08)!important;border-color:#d99a2b!important;}
.eval-rating-cell input:checked + label{background:#d99a2b!important;color:#fff!important;border-color:#d99a2b!important;}
.eval-qno{color:#b8801f!important;}
.eval-comment-box{color:#334155!important;}

/* Status pills */
.pill.good{background:rgba(16,185,129,.12)!important;color:#047857!important;}
.pill.warn{background:rgba(217,154,43,.12)!important;color:#a16207!important;}
.pill.bad{background:rgba(240,84,84,.10)!important;color:#b91c1c!important;}
.alert.success{background:rgba(16,185,129,.10)!important;color:#047857!important;border-color:rgba(16,185,129,.25)!important;}
.alert.error{background:rgba(240,84,84,.10)!important;color:#b91c1c!important;border-color:rgba(240,84,84,.25)!important;}

/* Progress bars */
.bar-wrap,.score-bar-bg,.eval-bar-bg,.avg-bar-bg,.cat-bar{background:#e2e8f0!important;}
.bar-fill,.avg-bar-fill,.cat-bar>div{background:linear-gradient(90deg,#b8801f,#f0b84d)!important;}

/* Notifications */
.notif-list li,.bell-list li{background:#f8fafc!important;color:#334155!important;border-color:#e2e8f0!important;}
.notif-list li.unseen,.bell-list li.unseen{background:rgba(217,154,43,.08)!important;}
.bell-btn{background:#fff!important;border-color:#cbd5e1!important;color:#d99a2b!important;}
.bell-btn:hover,.bell-btn[aria-expanded="true"]{background:rgba(217,154,43,.10)!important;border-color:rgba(217,154,43,.35)!important;}
.bell-panel{background:#fff!important;border-color:#e2e8f0!important;box-shadow:0 18px 44px rgba(15,23,42,.16)!important;color:#172033!important;}
.bell-head{border-color:#e2e8f0!important;}
.bell-head h3{color:#0f172a!important;}
.bell-list li{color:#334155!important;}
.bell-count{border-color:#fff!important;}

/* Reports / analytics data surfaces and modal */
.standing-score,.pstat-val,.avg-score-big,.avg-score-label{color:#047857!important;}
.standing-title.top{color:#047857!important;}
.standing-title.low{color:#dc2626!important;}
.comment-text{background:#f8fafc!important;color:#475569!important;border-color:#e2e8f0!important;}
.info-grid>div,.q-result,.received-item{border-color:#e2e8f0!important;}
.eval-modal-overlay{background:rgba(15,23,42,.55)!important;}
.eval-modal{background:#fff!important;color:#172033!important;border-color:#e2e8f0!important;}
.eval-modal-header{border-color:#e2e8f0!important;}
.eval-modal-close{color:#64748b!important;}
.star{color:#cbd5e1!important;}
.star.filled{color:#facc15!important;}

/* Account / settings / roster utilities */
.profile-photo-lg{border-color:#d99a2b!important;}
.avatar-sm{border-color:rgba(217,154,43,.45)!important;}
.btn-reset{color:#475569!important;border-color:#cbd5e1!important;background:#fff!important;}
.structure-note{background:rgba(217,154,43,.06)!important;border-color:rgba(217,154,43,.24)!important;}
.structure-note p{color:#475569!important;}
.structure-note p b{color:#0f172a!important;}

@media(max-width:768px){
  body{background:#F8FAFC!important;}
}
</style>

<style id="principal-integrated-light-view">
/* ================================================================
   PRINCIPAL INTEGRATED LIGHT VIEW
   Visual direction: same overall canvas treatment as the EA dashboard.
   The content area is one continuous light surface; feature sections
   remain white, but are flatter and more naturally integrated instead
   of looking like isolated floating cards.
   ================================================================ */
html, body {
  background: #F8FAFC !important;
  color: #172033 !important;
}
body {
  background-image: none !important;
}

/* Continuous page canvas */
.main,
main.main,
.main-content,
.content,
.page-content {
  background: #F8FAFC !important;
  color: #172033 !important;
  min-height: 100vh;
}

/* Keep the existing navy Principal sidebar exactly as-is */
.sidebar {
  background: #0A192F !important;
}

/* Page heading sits directly on the canvas — not in a floating card. */
.main > .page-header,
main.main > .page-header {
  background: transparent !important;
  border: 0 !important;
  box-shadow: none !important;
  border-radius: 0 !important;
  padding: 0 !important;
  margin: 0 0 22px !important;
}

.page-title,
.page-header h1 {
  color: #0F172A !important;
}
.page-sub,
.page-header p {
  color: #64748B !important;
}

/* Major feature sections: white, but visually grounded on the light canvas. */
.period-strip,
.structure-note,
.stub-note,
.section,
.card-grid,
.eval-switcher,
.eval-banner,
.group-tab-wrap,
.group-tabs,
.desig-subtabs,
.faculty-subtabs,
.filter-bar,
.ra-table-card,
.ra-filters-panel,
.table-wrap,
.table-card,
.content-card,
.summary-card,
.standing-panel,
.history-card,
.people-list,
.evaluator-grid,
.no-archived,
.info-grid,
.sheet-header,
.scale-bar,
.q-table,
.comment-section,
.avg-summary {
  border-color: #E2E8F0 !important;
}

.period-strip,
.structure-note,
.section,
.sheet-header,
.scale-bar,
.comment-section,
.avg-summary,
.ra-table-card,
.ra-filters-panel,
.table-wrap,
.table-card,
.content-card,
.summary-card,
.standing-panel,
.history-card,
.people-list,
.evaluator-grid,
.no-archived,
.info-grid {
  background: #FFFFFF !important;
  color: #172033 !important;
  box-shadow: 0 2px 10px rgba(15,23,42,.055) !important;
}

/* Dashboard KPI/detail cards stay visible, but lose the heavy "floating" effect. */
.card-grid {
  background: transparent !important;
  box-shadow: none !important;
  border: 0 !important;
  padding: 0 !important;
}
.stat-card,
.stat-link .stat-card {
  background: #FFFFFF !important;
  border-color: #E2E8F0 !important;
  box-shadow: 0 3px 12px rgba(15,23,42,.055) !important;
}
.stat-card:hover,
.stat-link:hover .stat-card {
  box-shadow: 0 5px 14px rgba(15,23,42,.075) !important;
}

/* Common tables/inner surfaces stay clean and readable. */
table,
.eval-table-wrap,
.eval-q-card,
.q-result,
.received-item {
  background: #FFFFFF !important;
  color: #172033 !important;
  border-color: #E2E8F0 !important;
}

table thead th,
.eval-table th,
.q-table thead tr,
table.data th,
.q-table th {
  background: #F8FAFC !important;
  color: #475569 !important;
  border-color: #E2E8F0 !important;
}

table tbody td,
.eval-table td,
.q-table td,
table.data td {
  background: #FFFFFF !important;
  color: #334155 !important;
  border-color: #E2E8F0 !important;
}

table tbody tr:hover td,
.eval-table tr:hover td,
.q-table tr:hover td {
  background: #F8FAFC !important;
}

/* Report feature area follows the same integrated page treatment. */
.eval-tab,
.tab,
.level-tab,
.status-tab,
.group-tab,
.desig-subtab,
.faculty-subtab {
  background: transparent !important;
}

/* Inner highlighted controls can still use soft tint, never dark navy. */
.main .subtle-head,
.main .table-head,
.main .table-header,
.main .thead {
  background: #F4F8FF !important;
}

input,
select,
textarea,
.search-box input[type=text],
.search-box select,
.form-group input,
.filter-field select,
.filter-field input[type=text],
.ra-filter-row select,
.ra-search,
.eval-comment-box {
  background: #FFFFFF !important;
  color: #172033 !important;
  border-color: #CBD5E1 !important;
}

/* Avoid accidental dark-mode remnants in common feature containers. */
.main .modal,
.main .modal-content,
.main .dropdown,
.main .menu,
.main .popover,
.main .panel {
  background: #FFFFFF !important;
  color: #172033 !important;
  border-color: #E2E8F0 !important;
}

@media (max-width: 768px) {
  .main,
  main.main,
  .main-content,
  .content,
  .page-content {
    padding: 24px 18px !important;
  }
}

@media print {
  html, body, .main, main.main, .main-content, .content, .page-content {
    background: #FFFFFF !important;
  }
  .main > .page-header,
  main.main > .page-header {
    box-shadow: none !important;
  }
}
</style>

</body></html>
<?php $mysqli->close(); ?>
<style id="white-theme-override">
:root{--dark:#ffffff;--mid:#ffffff;--inner:#f5f7fb;--light:#172033;--muted:#64748b;--shadow:0 4px 18px rgba(15,23,42,.08);}
html,body{background:#F8FAFC!important;color:#172033!important;}
body{background-image:none!important;}
main,.main-content,.content,.page-content{background:#F8FAFC!important;color:#172033!important;}
.sidebar{background:#0A192F!important;color:#E0E6F0!important;border-right:1px solid #172A45!important;}
.sidebar *,.sidebar a{color:inherit;}
.stat-card,.section,.period-strip,.filter-bar,.card,.panel,.table-wrap,.modal-content{background:#fff!important;color:#172033!important;border-color:#e2e8f0!important;box-shadow:var(--shadow)!important;}
h1,h2,h3,h4,h5,h6,.section-title,.page-title{color:#0f172a!important;}
p,span,label,td,th,small{color:inherit;}
table{color:#172033!important;}
table thead th{background:#f8fafc!important;color:#334155!important;border-color:#e2e8f0!important;}
table tbody td{border-color:#e2e8f0!important;}
input,select,textarea{background:#fff!important;color:#172033!important;border-color:#cbd5e1!important;}
.search-box input[type=text],.search-box select,.form-group input,.filter-field select,.filter-field input[type=text]{background:#fff!important;color:#172033!important;}
.btn-reset{color:#475569!important;border-color:#cbd5e1!important;}
.notif-list li{background:#f8fafc!important;}
</style>

<style id="principal-ea-exact-workspace-canvas">
/*
  Principal workspace shell — matches the EA feature/iframe composition:
  white outer viewport + an inset #F8FAFC workspace canvas.
  The canvas is the feature surface; white cards remain white.
*/
html{
  background:#FFFFFF !important;
  color-scheme:light !important;
}
body{
  background:#FFFFFF !important;
  background-image:none !important;
  color:#172033 !important;
}

/* Leave the navy Principal sidebar unchanged. */
.sidebar{
  background:#0A192F !important;
  color:#E0E6F0 !important;
}

/* The feature workspace is inset, like the EA iframe inside its white page. */
.main,
main.main{
  position:relative !important;
  flex:1 1 auto !important;
  width:auto !important;
  max-width:none !important;
  min-height:calc(100vh - 20px) !important;
  margin:20px 20px 0 20px !important;
  padding:24px 34px 34px !important;
  background:#F8FAFC !important;
  color:#172033 !important;
  border-radius:16px 16px 0 0 !important;
  box-shadow:none !important;
  isolation:auto !important;
  overflow:visible !important;
}

/* Disable the earlier pseudo-canvas implementation; the main itself is now
   the correctly inset workspace surface. */
.main::before,
main.main::before{
  display:none !important;
  content:none !important;
}

/* Page headings live on the same light workspace surface, as on the EA
   feature pages rendered inside their iframe. */
.main > .page-header,
main.main > .page-header{
  background:transparent !important;
  border:0 !important;
  box-shadow:none !important;
}

/* White feature surfaces remain clearly distinct from the #F8FAFC canvas. */
.main .page-header:has(.page-title),
.main .section,
.main .card,
.main .panel,
.main .table-card,
.main .content-card,
.main .stat-card,
.main .period-strip,
.main .filter-bar,
.main .table-wrap,
.main .table-card-wrap,
.main .content-panel,
.main .eval-banner,
.main .info-banner,
.main .history-card,
.main .gl-card,
.main .amber-card,
.main .green-card,
.main .red-card,
.main .ra-table-card,
.main .eval-card,
.main .comment-section,
.main .avg-summary,
.main .standing-panel,
.main .person-row,
.main .target-card,
.main .no-eval,
.main .no-data,
.main .no-evaluated,
.main .no-archived,
.main .evaluator-grid,
.main .people-list,
.main .cat-section,
.main .results-card,
.main .result-card,
.main .feature-card{
  background:#FFFFFF !important;
  color:#172033 !important;
  border-color:#D9E4EF !important;
  box-shadow:0 1px 5px rgba(15,23,42,.035) !important;
}

/* Soft inner tint, matching the EA logs table header / page surfaces. */
.main table thead th,
.main .table-head,
.main .table-header,
.main .thead,
.main .subtle-head{
  background:#F4F8FF !important;
  color:#4B6580 !important;
  border-color:#D9E4EF !important;
}

/* Responsive: retain the inset composition without squeezing narrow screens. */
@media (max-width:768px){
  .main,
  main.main{
    margin:12px 12px 0 12px !important;
    padding:20px 18px 28px !important;
    min-height:calc(100vh - 12px) !important;
    border-radius:12px 12px 0 0 !important;
  }
}

@media print{
  html,body{
    background:#FFFFFF !important;
  }
  .main,
  main.main{
    margin:0 !important;
    padding:20px !important;
    min-height:0 !important;
    background:#FFFFFF !important;
    border-radius:0 !important;
  }
}
</style>

<link rel="stylesheet" href="includes/principal_dark_repairs.css?v=20261011.4" id="principal-dark-repairs"/>
<style id="ac-faculty-style">
/* ── Account Settings — same card language as the Faculty portal ──
   Coloured top bar, icon tile, title + description. Surface/text colours are left to the
   existing theme so light and dark mode keep working; only structure + accents change. */
main.main{--ac:#D97706;}
main.main .ac-c1{--ac-c:var(--ac);}
main.main .ac-c2,main.main .ac-c5{--ac-c:#10B981;}
main.main .ac-c3{--ac-c:#D88900;}
main.main .ac-c4{--ac-c:#3B82F6;}
main.main .ac-c6{--ac-c:#8B5CF6;}
main.main a.btn-primary{display:inline-flex;align-items:center;gap:8px;text-decoration:none;}
.sq-status{display:inline-flex;align-items:center;gap:7px;padding:5px 11px;margin-bottom:16px;border-radius:999px;font-size:12px;font-weight:600;background:rgba(148,163,184,.12);border:1px solid rgba(148,163,184,.3);}
.sq-status.ok{color:#10B981;border-color:rgba(16,185,129,.4);}
.sq-status.warn{color:#D97706;border-color:rgba(217,119,6,.45);}
main.main .ac-card{position:relative !important;overflow:hidden !important;border-radius:15px !important;padding:22px 24px 24px !important;margin-bottom:20px !important;}
main.main .ac-card::before{content:'';position:absolute;left:0;right:0;top:0;height:3px;background:var(--ac-c);}
main.main .ac-head{display:flex;align-items:flex-start;gap:14px;margin-bottom:18px;}
main.main .ac-icon{width:42px;height:42px;flex:0 0 42px;border-radius:12px;display:inline-flex;align-items:center;justify-content:center;font-size:17px;background:color-mix(in srgb,var(--ac-c) 14%,transparent) !important;color:var(--ac-c) !important;}
main.main .ac-icon i{font-size:17px;color:var(--ac-c) !important;}
main.main .ac-head h2{display:block !important;margin:0 0 4px !important;font-family:'Rajdhani',sans-serif !important;font-size:21px !important;font-weight:800 !important;line-height:1.15 !important;letter-spacing:0 !important;}
main.main .ac-head .ac-sub{margin:0 !important;font-size:13px !important;line-height:1.5 !important;color:inherit !important;opacity:.72;}
main.main .ac-head.appearance-heading-row{justify-content:flex-start !important;}
main.main .ac-head.appearance-heading-row .appearance-device-badge{margin-left:auto;}
@media(max-width:600px){main.main .ac-card{padding:18px 18px 20px !important;}}
</style>
<style id="principal-settings-structure">
/* Same structure as the Dean settings page: photo help text + a fixed sidebar
   with the settings workspace scrolling internally. */
.photo-help{font-size:12px;color:#64748B;margin-top:8px;line-height:1.5;}
.profile-card{display:flex;align-items:center;gap:24px;}
.form-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:17px 20px;margin-bottom:20px;}
.form-group{display:flex;flex-direction:column;gap:7px;}
.form-group label{font-size:11px;font-weight:600;letter-spacing:.8px;text-transform:uppercase;color:#64748B !important;}
.alert{border-radius:10px;padding:12px 15px;font-size:13px;margin-bottom:18px;display:flex;align-items:center;gap:9px;}

html:has(main.main.principal-internal-scroll),
html:has(main.main.principal-internal-scroll) body{overflow:hidden !important;height:100% !important;}
main.main.principal-internal-scroll{
  height:calc(100vh - 20px) !important;max-height:calc(100vh - 20px) !important;min-height:0 !important;
  overflow-y:scroll !important;overflow-x:hidden !important;scrollbar-gutter:stable;
  overscroll-behavior:contain;scrollbar-width:thin;scrollbar-color:#AEBAC8 #EEF2F6;
}
main.main.principal-internal-scroll::-webkit-scrollbar{width:10px;}
main.main.principal-internal-scroll::-webkit-scrollbar-track{background:#EEF2F6;border-radius:10px;}
main.main.principal-internal-scroll::-webkit-scrollbar-thumb{background:#AEBAC8;border:2px solid #EEF2F6;border-radius:10px;}
main.main.principal-internal-scroll::-webkit-scrollbar-thumb:hover{background:#8F9CAB;}
html.dark-theme main.main.principal-internal-scroll{scrollbar-color:#2A4468 #0F1F3D;}
html.dark-theme main.main.principal-internal-scroll::-webkit-scrollbar-track{background:#0F1F3D;}
html.dark-theme main.main.principal-internal-scroll::-webkit-scrollbar-thumb{background:#2A4468;border-color:#0F1F3D;}

@media(max-width:900px){.form-grid{grid-template-columns:1fr;}}
@media(max-width:768px){
  html:has(main.main.principal-internal-scroll),
  html:has(main.main.principal-internal-scroll) body{overflow:auto !important;height:auto !important;}
  main.main.principal-internal-scroll{height:auto !important;max-height:none !important;min-height:calc(100vh - 12px) !important;overflow:visible !important;scrollbar-gutter:auto;}
}
@media(max-width:650px){.profile-card{align-items:flex-start;flex-direction:column;}}
</style>

<style id="principal-dean-ui-match">
/* ================================================================
   Principal settings — interface matched to the Dean settings page:
   full-width Theme card (3 horizontal options), Interface spacing and
   Accessibility side by side, dark headings, check badge at the right,
   same photo/button sizing. Principal keeps its amber accent.
   ================================================================ */
main.main{--ap-card:#FFFFFF;--ap-soft:#F8FAFC;--ap-line:#E2E8F0;--ap-text:#0F172A;--ap-muted:#64748B;--ap-accent:#D97706;--ap-accent-bg:rgba(217,119,6,.10);--ap-ring:rgba(217,119,6,.14);}
html.dark-theme main.main{--ap-card:rgba(10,25,47,.55);--ap-soft:rgba(10,25,47,.45);--ap-line:rgba(255,255,255,.10);--ap-text:#E0E6F0;--ap-muted:#A0B3C6;--ap-accent-bg:rgba(217,119,6,.16);}

/* Headings: dark, not accent-coloured */
html:not(.dark-theme) main.main .ac-head h2{color:#0F172A !important;}
html:not(.dark-theme) main.main .ac-head .ac-sub{color:#64748B !important;opacity:1;}

/* Appearance grid: Theme spans the full row, the other two share the next row */
main.main .appearance-feature-grid{display:grid !important;grid-template-columns:repeat(2,minmax(0,1fr)) !important;gap:16px !important;}
main.main .appearance-theme-card{grid-column:1 / -1 !important;}
main.main .appearance-control-card{display:block !important;background:var(--ap-soft) !important;border:1px solid var(--ap-line) !important;border-radius:14px !important;padding:18px !important;box-shadow:none !important;}
main.main .appearance-control-head{display:flex !important;align-items:flex-start !important;gap:12px !important;margin-bottom:14px !important;}
main.main .appearance-control-icon{width:34px !important;height:34px !important;flex:0 0 34px !important;border-radius:10px !important;display:inline-flex !important;align-items:center !important;justify-content:center !important;background:var(--ap-accent-bg) !important;color:var(--ap-accent) !important;font-size:14px !important;}
main.main .appearance-control-head h3{font-size:15px !important;font-weight:700 !important;color:var(--ap-text) !important;margin:0 0 2px !important;}
main.main .appearance-control-head p{font-size:12.5px !important;color:var(--ap-muted) !important;margin:0 !important;line-height:1.45 !important;}

/* Theme options: three wide horizontal cards */
main.main .appearance-theme-options{display:grid !important;grid-template-columns:repeat(3,minmax(0,1fr)) !important;gap:12px !important;}
main.main .appearance-choice{position:relative !important;display:flex !important;flex-direction:row !important;align-items:center !important;justify-content:flex-start !important;gap:12px !important;width:auto !important;height:auto !important;min-height:0 !important;padding:14px !important;text-align:left !important;border-radius:12px !important;border:1.5px solid var(--ap-line) !important;background:var(--ap-card) !important;color:var(--ap-text) !important;cursor:pointer;box-shadow:none !important;}
main.main .appearance-choice::before,main.main .appearance-choice::after{display:none !important;content:none !important;}
main.main .appearance-choice:hover{border-color:var(--ap-accent) !important;}
main.main .appearance-choice-icon{width:36px !important;height:36px !important;flex:0 0 36px !important;border-radius:10px !important;display:inline-flex !important;align-items:center !important;justify-content:center !important;background:var(--ap-soft) !important;color:var(--ap-muted) !important;font-size:15px !important;}
main.main .appearance-choice-copy{display:block !important;min-width:0;}
main.main .appearance-choice-copy strong,main.main .appearance-density-choice strong,main.main .appearance-motion-copy strong{display:block !important;font-size:13.5px !important;font-weight:700 !important;color:var(--ap-text) !important;}
main.main .appearance-choice-copy small,main.main .appearance-density-choice small,main.main .appearance-motion-copy small{display:block !important;font-size:11.5px !important;color:var(--ap-muted) !important;margin-top:1px !important;}
main.main .appearance-choice-check{position:static !important;margin-left:auto !important;width:20px !important;height:20px !important;flex:0 0 20px !important;border:0 !important;border-radius:50% !important;display:inline-flex !important;align-items:center !important;justify-content:center !important;font-size:10px !important;background:var(--ap-accent) !important;color:#fff !important;opacity:0 !important;transform:scale(.6) !important;transition:.2s;}
main.main .appearance-choice[aria-pressed="true"]{border-color:var(--ap-accent) !important;background:var(--ap-accent-bg) !important;box-shadow:0 0 0 3px var(--ap-ring) !important;}
main.main .appearance-choice[aria-pressed="true"] .appearance-choice-icon{background:var(--ap-accent) !important;color:#fff !important;}
main.main .appearance-choice[aria-pressed="true"] .appearance-choice-check{opacity:1 !important;transform:scale(1) !important;}
main.main .appearance-inline-status{margin-top:14px !important;font-size:12.5px !important;color:var(--ap-muted) !important;display:flex !important;align-items:center !important;gap:7px !important;}
main.main .appearance-inline-status i{color:var(--ap-accent) !important;}
main.main .appearance-inline-status strong{color:var(--ap-text) !important;}

/* Interface spacing: stacked rows, plain icon (no filled tile) */
main.main .appearance-density-options{display:grid !important;grid-template-columns:1fr !important;gap:10px !important;}
main.main .appearance-density-choice{display:flex !important;align-items:center !important;gap:12px !important;width:auto !important;padding:12px 14px !important;text-align:left !important;border-radius:12px !important;border:1.5px solid var(--ap-line) !important;background:var(--ap-card) !important;color:var(--ap-muted) !important;cursor:pointer;box-shadow:none !important;}
main.main .appearance-density-choice:hover{border-color:var(--ap-accent) !important;}
main.main .appearance-density-choice > i{width:20px !important;height:auto !important;flex:0 0 20px !important;background:transparent !important;border-radius:0 !important;font-size:15px !important;text-align:center !important;color:var(--ap-muted) !important;}
main.main .appearance-density-choice[aria-pressed="true"]{border-color:var(--ap-accent) !important;background:var(--ap-accent-bg) !important;box-shadow:none !important;}
main.main .appearance-density-choice[aria-pressed="true"] > i{color:var(--ap-accent) !important;background:transparent !important;}

/* Accessibility toggle */
main.main .appearance-motion-toggle{width:100% !important;display:flex !important;align-items:center !important;justify-content:space-between !important;gap:14px !important;padding:12px 14px !important;text-align:left !important;border-radius:12px !important;border:1.5px solid var(--ap-line) !important;background:var(--ap-card) !important;cursor:pointer;box-shadow:none !important;}
main.main .appearance-motion-toggle:hover{border-color:var(--ap-accent) !important;}
main.main .appearance-motion-toggle[aria-pressed="true"]{border-color:var(--ap-accent) !important;background:var(--ap-accent-bg) !important;}
main.main .appearance-switch-track{width:44px !important;height:24px !important;flex:0 0 44px !important;border-radius:999px !important;background:#CBD5E1 !important;position:relative !important;transition:background .2s;}
main.main .appearance-switch-thumb{position:absolute !important;top:3px !important;left:3px !important;width:18px !important;height:18px !important;border-radius:50% !important;background:#fff !important;box-shadow:0 1px 3px rgba(0,0,0,.25);transition:transform .2s;}
main.main .appearance-motion-toggle[aria-pressed="true"] .appearance-switch-track{background:var(--ap-accent) !important;}
main.main .appearance-motion-toggle[aria-pressed="true"] .appearance-switch-thumb{transform:translateX(20px) !important;}
main.main .appearance-accessibility-note{margin-top:12px !important;font-size:11.5px !important;color:var(--ap-muted) !important;display:flex !important;gap:7px;align-items:flex-start;line-height:1.5;}
main.main .appearance-accessibility-note i{color:var(--ap-accent) !important;margin-top:2px;}

/* Profile photo row + buttons, same sizing as Dean */
main.main .profile-photo-lg{width:96px !important;height:96px !important;border-radius:50% !important;object-fit:cover;border:3px solid var(--ap-accent) !important;}
main.main .photo-form-wrap input[type="file"]{font:13px 'DM Sans',sans-serif !important;padding:2px 4px !important;width:auto !important;max-width:100%;border:1px solid #CBD5E1 !important;border-radius:6px !important;background:#fff !important;}
main.main .btn-primary{padding:11px 17px !important;border:0 !important;border-radius:9px !important;font:600 14px 'DM Sans',sans-serif !important;color:#fff !important;background:var(--ap-accent) !important;box-shadow:0 4px 14px rgba(217,119,6,.25) !important;cursor:pointer;}
main.main .btn-primary:hover{background:#F59E0B !important;color:#fff !important;transform:translateY(-1px);}
main.main .form-group input{padding:12px 13px !important;font:14px 'DM Sans',sans-serif !important;border-radius:9px !important;}

@media (max-width:900px){main.main .appearance-theme-options{grid-template-columns:1fr !important;}}
@media (max-width:768px){main.main .appearance-feature-grid{grid-template-columns:1fr !important;}main.main .appearance-device-badge{display:none !important;}}
</style>

<style id="principal-password-toggle">
main.main .pw-wrap{position:relative;display:block;}
main.main .pw-wrap input{width:100%;padding-right:46px !important;}
main.main .pw-toggle{position:absolute;top:50%;right:8px;transform:translateY(-50%);width:34px;height:34px;display:flex;align-items:center;justify-content:center;border:0;border-radius:8px;background:transparent;color:#64748B;font-size:15px;cursor:pointer;padding:0;}
main.main .pw-toggle .pw-eye{display:none;}
main.main .pw-toggle .pw-eye-off{display:block;}
main.main .pw-toggle[aria-pressed="true"] .pw-eye{display:block;}
main.main .pw-toggle[aria-pressed="true"] .pw-eye-off{display:none;}
main.main .pw-toggle:hover{color:#B45309;background:rgba(217,154,43,.12);}
main.main .pw-toggle:focus-visible{outline:2px solid #D99A2B;outline-offset:1px;}
</style>
<style id="principal-settings-tabs">
.settings-tabs{display:flex;gap:6px;margin:0 0 18px;padding:5px;border:1px solid #D9E4EF;border-radius:12px;background:#F4F8FF;width:fit-content;max-width:100%;overflow-x:auto;}
.settings-tab{appearance:none;border:0;background:transparent;color:#64748B;font:inherit;font-size:13.5px;font-weight:600;padding:9px 18px;border-radius:9px;cursor:pointer;display:inline-flex;align-items:center;gap:8px;white-space:nowrap;transition:background .2s,color .2s,box-shadow .2s;}
.settings-tab i{color:#D99A2B;}
.settings-tab:hover{color:#172033;background:rgba(217,154,43,.12);}
.settings-tab[aria-selected="true"]{background:#FFFFFF;color:#172033;box-shadow:0 1px 5px rgba(15,23,42,.10);}
.settings-tab:focus-visible{outline:2px solid #D99A2B;outline-offset:2px;}
.settings-tabpanel[hidden]{display:none !important;}
html.dark-theme .settings-tabs{background:rgba(10,25,47,.55);border-color:rgba(255,255,255,.10);}
html.dark-theme .settings-tab{color:#A0B3C6;}
html.dark-theme .settings-tab:hover{color:#fff;background:rgba(217,154,43,.15);}
html.dark-theme .settings-tab[aria-selected="true"]{background:rgba(217,154,43,.18);color:#fff;box-shadow:none;}
/* Pull the settings up: the page heading is empty here, so drop its reserved space and trim the top padding. */
main.main > .page-header,
.main > .page-header{display:none !important;}
main.main,
.main{padding-top:14px !important;}
@media (max-width:768px){main.main,.main{padding-top:12px !important;}}
</style>

<script>
(function(){
    var tabs=[].slice.call(document.querySelectorAll('.settings-tab'));
    var panels=[].slice.call(document.querySelectorAll('.settings-tabpanel'));
    if(!tabs.length)return;
    function show(name,focus){
        if(!panels.some(function(p){return p.dataset.panel===name;}))name='profile';
        tabs.forEach(function(t){
            var on=t.dataset.tab===name;
            t.setAttribute('aria-selected',on?'true':'false');
            t.tabIndex=on?0:-1;
            if(on&&focus)t.focus();
        });
        panels.forEach(function(p){p.hidden=p.dataset.panel!==name;});
        try{history.replaceState(null,'','#'+name);}catch(e){}
    }
    tabs.forEach(function(t,i){
        t.addEventListener('click',function(){show(t.dataset.tab);});
        t.addEventListener('keydown',function(e){
            var n=null;
            if(e.key==='ArrowRight')n=tabs[(i+1)%tabs.length];
            if(e.key==='ArrowLeft')n=tabs[(i-1+tabs.length)%tabs.length];
            if(n){e.preventDefault();show(n.dataset.tab,true);}
        });
    });
    show((location.hash||'').replace('#','')||'profile');
})();
</script>

<script>
document.querySelectorAll('.pw-toggle').forEach(function(btn){
    btn.addEventListener('click', function(){
        var input = btn.parentElement.querySelector('input');
        var show = input.type === 'password';
        input.type = show ? 'text' : 'password';
        btn.setAttribute('aria-pressed', show ? 'true' : 'false');
        btn.setAttribute('aria-label', show ? 'Hide password' : 'Show password');
    });
});
</script>