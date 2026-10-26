<?php
// admin/profile_security.php
// "Profile & Security" tab of the admin Settings center (loaded in an iframe by settings.php).
// Handles: profile photo upload, profile details (full name / username / designation), change password.
require_once 'session_bootstrap.php';
require_once 'db.php';        // provides $mysqli + UPLOAD_DIR + UPLOAD_URL
require_once 'security.php';  // security-question helpers: sq_questions(), sq_validate(), sq_save()

// db.php may name its connection anything ($conn, $mysqli, $db, $con ...). Find the mysqli object.
$conn = null;
foreach (['conn', 'mysqli', 'db', 'con', 'connection', 'link'] as $n) {
    if (isset($$n) && $$n instanceof mysqli) { $conn = $$n; break; }
    if (isset($GLOBALS[$n]) && $GLOBALS[$n] instanceof mysqli) { $conn = $GLOBALS[$n]; break; }
}
if (!$conn) {
    foreach (get_defined_vars() as $v) { if ($v instanceof mysqli) { $conn = $v; break; } }
}
if (!$conn) {
    foreach ($GLOBALS as $v) { if ($v instanceof mysqli) { $conn = $v; break; } }
}
if (!$conn) {
    http_response_code(500);
    exit('Database connection not found. Check that db.php creates a mysqli connection.');
}
if (function_exists('security_ensure_tables')) security_ensure_tables($conn);
$sq_all = function_exists('sq_questions') ? sq_questions() : [];

// Photo folder / URL come from db.php (same constants the registration page uses).
$photoDir = defined('UPLOAD_DIR') ? UPLOAD_DIR : (__DIR__ . '/../image/');
$photoDir = rtrim($photoDir, '/\\') . DIRECTORY_SEPARATOR;
$photoBase = defined('UPLOAD_URL') ? rtrim(UPLOAD_URL, '/') . '/' : '../image/';

// ---------------------------------------------------------------- helpers
function ps_json($ok, $msg, array $extra = []) {
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(array_merge(['ok' => $ok, 'message' => $msg], $extra));
    exit;
}
function ps_h($s) { return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }

// Resolve the logged-in user's id from whichever session key this project uses.
$uid = 0;
foreach (['user_id', 'id', 'uid'] as $k) {
    if (!empty($_SESSION[$k])) { $uid = (int)$_SESSION[$k]; break; }
}
if ($uid <= 0) {
    if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') ps_json(false, 'Your session has expired. Please log in again.');
    http_response_code(403);
    exit('Not signed in.');
}

// Detect which columns exist on `users` so this works with the current schema.
$cols = [];
if ($res = $conn->query("SHOW COLUMNS FROM users")) {
    while ($r = $res->fetch_assoc()) $cols[strtolower($r['Field'])] = true;
}
$nameCol  = isset($cols['full_name']) ? 'full_name' : (isset($cols['name']) ? 'name' : null);
$userCol  = isset($cols['username']) ? 'username' : null;
$desigCol = isset($cols['designation']) ? 'designation' : null;
$passCol  = isset($cols['password']) ? 'password' : (isset($cols['password_hash']) ? 'password_hash' : null);

// Photo column: the registration page stores the filename in `photo`.
$photoCol = isset($cols['photo']) ? 'photo' : (isset($cols['profile_photo']) ? 'profile_photo' : null);
if (!$photoCol) {
    @$conn->query("ALTER TABLE users ADD COLUMN photo VARCHAR(255) NULL DEFAULT NULL");
    $photoCol = 'photo';
}

// CSRF token
if (empty($_SESSION['ps_csrf'])) $_SESSION['ps_csrf'] = bin2hex(random_bytes(16));
$csrf = $_SESSION['ps_csrf'];

// ---------------------------------------------------------------- POST actions
if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    if (!hash_equals($csrf, (string)($_POST['csrf'] ?? ''))) ps_json(false, 'Security token mismatch. Reload the page and try again.');
    $action = $_POST['action'] ?? '';

    // ---- Upload photo
    if ($action === 'photo') {
        if (empty($_FILES['photo']) || $_FILES['photo']['error'] !== UPLOAD_ERR_OK) {
            ps_json(false, 'Please choose a photo to upload.');
        }
        $f = $_FILES['photo'];
        if ($f['size'] > 5 * 1024 * 1024) ps_json(false, 'File is too large. Maximum size is 5MB.');
        $fi = new finfo(FILEINFO_MIME_TYPE);
        $mime = $fi->file($f['tmp_name']);
        $allowed = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
        if (!isset($allowed[$mime])) ps_json(false, 'Only JPG, PNG, or WEBP images are allowed.');
        if (@getimagesize($f['tmp_name']) === false) ps_json(false, 'That file is not a valid image.');

        if (!is_dir($photoDir) && !@mkdir($photoDir, 0755, true)) ps_json(false, 'Could not create the upload folder.');
        $fname = uniqid('adm_', true) . '.' . $allowed[$mime];
        if (!move_uploaded_file($f['tmp_name'], $photoDir . $fname)) ps_json(false, 'Upload failed. Check folder permissions.');

        // Remove the previous photo file (filename only, so we never touch paths outside the folder).
        $old = '';
        if ($st = $conn->prepare("SELECT $photoCol FROM users WHERE id = ?")) {
            $st->bind_param('i', $uid); $st->execute();
            $st->bind_result($old); $st->fetch(); $st->close();
        }
        $old = basename((string)$old);
        if ($old !== '' && is_file($photoDir . $old)) @unlink($photoDir . $old);

        $st = $conn->prepare("UPDATE users SET $photoCol = ? WHERE id = ?");
        $st->bind_param('si', $fname, $uid);
        if (!$st->execute()) ps_json(false, 'Could not save the photo.');
        $_SESSION['photo'] = $fname;
        ps_json(true, 'Profile photo updated.', ['url' => $photoBase . $fname . '?v=' . time()]);
    }

    // ---- Profile details
    if ($action === 'details') {
        $name  = trim((string)($_POST['full_name'] ?? ''));
        $uname = trim((string)($_POST['username'] ?? ''));
        $desig = trim((string)($_POST['designation'] ?? ''));
        if ($nameCol && $name === '')  ps_json(false, 'Full name is required.');
        if ($userCol && $uname === '') ps_json(false, 'Username is required.');
        if (mb_strlen($name) > 120 || mb_strlen($uname) > 60 || mb_strlen($desig) > 120) ps_json(false, 'One of the fields is too long.');
        if ($userCol && !preg_match('/^[A-Za-z0-9._-]{3,60}$/', $uname)) {
            ps_json(false, 'Username must be 3-60 characters: letters, numbers, dot, dash or underscore.');
        }
        if ($userCol) {
            $st = $conn->prepare("SELECT id FROM users WHERE username = ? AND id <> ? LIMIT 1");
            $st->bind_param('si', $uname, $uid); $st->execute(); $st->store_result();
            if ($st->num_rows > 0) ps_json(false, 'That username is already taken.');
            $st->close();
        }
        $sets = []; $types = ''; $vals = [];
        if ($nameCol)  { $sets[] = "$nameCol = ?";  $types .= 's'; $vals[] = $name; }
        if ($userCol)  { $sets[] = "$userCol = ?";  $types .= 's'; $vals[] = $uname; }
        if ($desigCol) { $sets[] = "$desigCol = ?"; $types .= 's'; $vals[] = $desig; }
        if (!$sets) ps_json(false, 'No editable profile columns were found in the users table.');
        $types .= 'i'; $vals[] = $uid;
        $st = $conn->prepare("UPDATE users SET " . implode(', ', $sets) . " WHERE id = ?");
        $st->bind_param($types, ...$vals);
        if (!$st->execute()) ps_json(false, 'Could not save your details.');
        if ($nameCol) { $_SESSION['full_name'] = $name; $_SESSION['name'] = $name; }
        if ($userCol) $_SESSION['username'] = $uname;
        if ($desigCol) $_SESSION['designation'] = $desig;
        ps_json(true, 'Profile details saved.');
    }

    // ---- Change password
    if ($action === 'password') {
        if (!$passCol) ps_json(false, 'No password column was found in the users table.');
        $cur = (string)($_POST['current_password'] ?? '');
        $new = (string)($_POST['new_password'] ?? '');
        $con = (string)($_POST['confirm_password'] ?? '');
        if ($cur === '' || $new === '' || $con === '') ps_json(false, 'Please fill in all password fields.');
        if (strlen($new) < 8) ps_json(false, 'New password must be at least 8 characters.');
        if ($new !== $con)    ps_json(false, 'New password and confirmation do not match.');
        if ($new === $cur)    ps_json(false, 'New password must be different from your current password.');

        $stored = '';
        $st = $conn->prepare("SELECT $passCol FROM users WHERE id = ?");
        $st->bind_param('i', $uid); $st->execute();
        $st->bind_result($stored); $st->fetch(); $st->close();

        // Accept bcrypt/argon hashes; fall back to a plain-text comparison for legacy rows.
        $isHash = (bool)preg_match('/^\$(2[aby]|argon2i|argon2id)\$/', (string)$stored);
        $valid  = $isHash ? password_verify($cur, $stored) : hash_equals((string)$stored, $cur);
        if (!$valid) ps_json(false, 'Your current password is incorrect.');

        $hash = password_hash($new, PASSWORD_DEFAULT);
        $st = $conn->prepare("UPDATE users SET $passCol = ? WHERE id = ?");
        $st->bind_param('si', $hash, $uid);
        if (!$st->execute()) ps_json(false, 'Could not update your password.');
        ps_json(true, 'Password updated successfully.');
    }

    // ---- Security questions (only the account password is required)
    if ($action === 'questions') {
        if (!$passCol) ps_json(false, 'No password column was found in the users table.');
        $pw = (string)($_POST['sq_password'] ?? '');
        if ($pw === '') ps_json(false, 'Enter your password to change your security questions.');

        $stored = '';
        $st = $conn->prepare("SELECT $passCol FROM users WHERE id = ?");
        $st->bind_param('i', $uid); $st->execute();
        $st->bind_result($stored); $st->fetch(); $st->close();
        $isHash = (bool)preg_match('/^\$(2[aby]|argon2i|argon2id)\$/', (string)$stored);
        $valid  = $isHash ? password_verify($pw, $stored) : hash_equals((string)$stored, $pw);
        if (!$valid) ps_json(false, 'Incorrect password.');

        if (!function_exists('sq_validate') || !function_exists('sq_save')) ps_json(false, 'security.php helpers were not found.');
        $uname = '';
        if ($userCol) {
            $st = $conn->prepare("SELECT $userCol FROM users WHERE id = ?");
            $st->bind_param('i', $uid); $st->execute();
            $st->bind_result($uname); $st->fetch(); $st->close();
        }
        // Same validation the registration page uses (3 distinct questions, answers, etc.)
        [$sqErr, $sq_rows] = sq_validate($_POST['sq_question'] ?? [], $_POST['sq_answer'] ?? [], $uname);
        if ($sqErr !== null) ps_json(false, $sqErr);

        try {
            $conn->begin_transaction();
            sq_save($conn, $uid, $sq_rows);
            $conn->commit();
        } catch (Throwable $e) {
            try { $conn->rollback(); } catch (Throwable $ignored) {}
            error_log('profile_security.php sq_save failed: ' . $e->getMessage());
            ps_json(false, 'Could not save your security questions. Please try again.');
        }
        ps_json(true, 'Security questions updated.');
    }

    ps_json(false, 'Unknown action.');
}

// ---------------------------------------------------------------- load current values
$sel = ["$photoCol AS profile_photo"];
if ($nameCol)  $sel[] = "$nameCol AS full_name";
if ($userCol)  $sel[] = "$userCol AS username";
if ($desigCol) $sel[] = "$desigCol AS designation";
$u = ['profile_photo' => '', 'full_name' => '', 'username' => '', 'designation' => ''];
if ($st = $conn->prepare("SELECT " . implode(', ', $sel) . " FROM users WHERE id = ?")) {
    $st->bind_param('i', $uid); $st->execute();
    $row = $st->get_result()->fetch_assoc();
    if ($row) $u = array_merge($u, $row);
    $st->close();
}
$pf = basename((string)$u['profile_photo']);
$photoUrl = ($pf !== '' && is_file($photoDir . $pf)) ? $photoBase . $pf . '?v=' . filemtime($photoDir . $pf) : '';
?>
<!doctype html>
<html lang="en">
<head>
<script>
// Follow the theme of the page that embeds us (Settings / dashboard); fall back to the saved preference.
(function(){
  var h=document.documentElement;
  function lum(c){var m=/rgba?\(\s*(\d+)[ ,]+(\d+)[ ,]+(\d+)(?:[ ,\/]+([\d.]+))?/.exec(c||'');if(!m)return null;if(m[4]!==undefined&&parseFloat(m[4])===0)return null;return (0.299*m[1]+0.587*m[2]+0.114*m[3]);}
  function elDark(w){
    try{
      var els=[w.document.documentElement,w.document.body];
      for(var i=0;i<els.length;i++){
        var e=els[i]; if(!e) continue;
        var t=e.getAttribute('data-theme')||e.getAttribute('data-bs-theme')||e.getAttribute('data-mode')||e.getAttribute('data-color-scheme');
        if(t) return /dark/i.test(t);
        if(/(^|[\s_-])dark([\s_-]|$)/i.test(e.className||'')) return true;
      }
      for(var j=0;j<els.length;j++){
        if(!els[j]) continue;
        var l=lum(w.getComputedStyle(els[j]).backgroundColor);
        if(l!==null) return l<110;
      }
    }catch(e){}
    return null;
  }
  function savedDark(){
    try{
      var t=localStorage.getItem('pbiTheme')||'light';
      return t==='system' ? window.matchMedia('(prefers-color-scheme: dark)').matches : t==='dark';
    }catch(e){return false;}
  }
  function apply(){
    var d=null;
    try{ if(window.parent&&window.parent!==window) d=elDark(window.parent); }catch(e){}
    if(d===null){ try{ if(window.top&&window.top!==window) d=elDark(window.top); }catch(e){} }
    if(d===null) d=savedDark();
    h.setAttribute('data-theme', d?'dark':'light');
    try{
      h.classList.toggle('density-comfortable',(localStorage.getItem('pbiDensity')||'compact')==='comfortable');
      h.style.setProperty('--accent', localStorage.getItem('pbiAccent')||'#2f6ee2');
    }catch(e){}
  }
  apply();
  window.addEventListener('storage',apply);
  window.addEventListener('load',function(){
    apply();
    try{
      var P=window.parent; if(P&&P!==window&&window.MutationObserver){
        var mo=new MutationObserver(apply), o={attributes:true,attributeFilter:['class','data-theme','data-bs-theme','style']};
        mo.observe(P.document.documentElement,o); if(P.document.body) mo.observe(P.document.body,o);
      }
    }catch(e){}
  });
})();
</script>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Profile &amp; Security</title>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<link rel="stylesheet" href="admin_ui_theme.css">
<link rel="stylesheet" href="admin_compact_ui.css">
<style>
/* Same base rules System Archive uses, so the shared admin stylesheets style this page identically */
:root{--bg:#F5F8FC;--card:#fff;--line:#D8E5F4;--text:#102746;--muted:#67819E;--blue:#2563EB;--blue-soft:#EEF4FF;--green:#0F9F6E;--green-soft:#EAF9F2;--amber:#C77A08;--amber-soft:#FFF7E6;--red:#D6455D;--red-soft:#FFF0F2;--shadow:0 3px 14px rgba(30,82,144,.08)}
*{box-sizing:border-box} body{margin:0;background:var(--bg);color:var(--text);font:13px/1.5 Inter,system-ui,-apple-system,BlinkMacSystemFont,"Segoe UI",sans-serif;-webkit-font-smoothing:antialiased}
.wrap{max-width:1240px;margin:0 auto;padding:24px 28px 44px}
.grid{display:grid;grid-template-columns:1.25fr .75fr;gap:14px}
.card{background:var(--card);border:1px solid var(--line);border-radius:12px;box-shadow:var(--shadow);padding:17px}
.card h2{font-size:14px;margin:0 0 4px}
.sub{color:var(--muted);font-size:11.5px}
.label{display:block;font-size:11px;font-weight:700;color:#405A76;margin:14px 0 6px;text-transform:uppercase;letter-spacing:.04em}
.select{width:100%;padding:9px 11px;border:1px solid #B9CDE5;border-radius:8px;background:#fff;color:var(--text);font:600 12px Inter}
.actions{display:flex;gap:8px;align-items:center;margin-top:13px;flex-wrap:wrap}
.btn{border:1px solid #B9CDE5;background:#fff;border-radius:8px;padding:9px 12px;font:700 12px Inter;color:var(--text);cursor:pointer}
.btn.primary{border-color:var(--blue);background:var(--blue);color:#fff}
.btn:disabled{opacity:.45;cursor:not-allowed}
.success{display:flex;gap:8px;align-items:flex-start;padding:9px 10px;background:var(--green-soft);border:1px solid #BDE9D4;border-radius:8px;color:#08734F;font-size:11.5px}
@media(max-width:900px){.grid{grid-template-columns:1fr}}

/* Page-specific bits */
.stack{display:flex;flex-direction:column;gap:14px}
.fg{display:grid;grid-template-columns:1fr 1fr;gap:0 16px}
.fg .span2{grid-column:1/-1}
.half{max-width:calc(50% - 8px)}
.pw{position:relative}.pw .select{padding-right:42px}
.pw .eye{position:absolute;right:6px;top:50%;transform:translateY(-50%);border:0;background:none;color:var(--muted);cursor:pointer;padding:8px 9px;font-size:14px}
.pw .eye:hover{color:var(--green)}
.photo-row{display:flex;gap:18px;align-items:center;flex-wrap:wrap;margin-top:14px}
.avatar{width:84px;height:84px;border-radius:50%;border:3px solid var(--blue);background:#F1F6FC;display:flex;align-items:center;justify-content:center;color:var(--muted);font-size:30px;overflow:hidden;flex:0 0 auto}
.avatar img{width:100%;height:100%;object-fit:cover;display:block}
.photo-ctl{display:flex;flex-direction:column;gap:10px;align-items:flex-start;min-width:0}
.photo-ctl input[type=file]{font:500 12px Inter;color:var(--muted);max-width:100%}
.photo-ctl input[type=file]::file-selector-button{border:1px solid #B9CDE5;background:#fff;color:var(--text);border-radius:8px;padding:6px 11px;font:600 12px Inter;margin-right:9px;cursor:pointer}
.hint{font-size:11.5px;color:var(--muted)}
.btn.in-row{margin-top:0}
/* collapsible security questions */
.sq-card{padding:0;overflow:hidden}
.sq-toggle{width:100%;display:flex;align-items:center;justify-content:space-between;gap:14px;text-align:left;background:none;border:0;padding:17px;cursor:pointer;color:inherit;font-family:Inter}
.sq-toggle:hover{background:rgba(15,159,110,.05)}
.sq-toggle:focus-visible{outline:3px solid rgba(15,159,110,.35);outline-offset:-3px}
.sq-toggle h2{margin:0 0 4px}
.sq-chev{width:32px;height:32px;border-radius:50%;border:1px solid rgba(128,150,180,.35);color:var(--muted);display:flex;align-items:center;justify-content:center;font-size:12px;flex:0 0 auto;transition:transform .2s,color .2s}
.sq-toggle[aria-expanded="true"] .sq-chev{transform:rotate(180deg);color:var(--green)}
.sq-body{padding:2px 17px 18px;border-top:1px solid rgba(128,150,180,.3)}
.sq-body[hidden]{display:none}
.sq-item{display:flex;flex-direction:column;gap:8px}
.sq-item .label{margin-bottom:0}
.toast{position:fixed;right:18px;bottom:18px;max-width:340px;padding:10px 14px;border-radius:10px;font:600 12px Inter;color:#fff;opacity:0;transform:translateY(8px);pointer-events:none;transition:.2s;z-index:50}
.toast.show{opacity:1;transform:none}.toast.ok{background:#0F9F6E}.toast.err{background:#D6455D}
@media(max-width:640px){.fg{grid-template-columns:1fr}.half{max-width:none}}
/* dark mode for the page-specific bits (shared stylesheets handle .card/.select/.btn) */
html[data-theme="dark"] .avatar{background:#16243a}
html[data-theme="dark"] .photo-ctl input[type=file]::file-selector-button{background:#16243a;border-color:#25374f;color:#e7eef8}
</style>
<style id="pbi-feature-scrollbar">

/* PBI FEATURE SCROLLBAR — consistent with the compact page scrollbar */
html, body {
  scrollbar-width: thin !important;
  scrollbar-color: #888 transparent !important;
}
html::-webkit-scrollbar, body::-webkit-scrollbar,
.feature-compact ::-webkit-scrollbar { width: 10px !important; height: 10px !important; }
html::-webkit-scrollbar-track, body::-webkit-scrollbar-track,
.feature-compact ::-webkit-scrollbar-track { background: transparent !important; }
html::-webkit-scrollbar-thumb, body::-webkit-scrollbar-thumb,
.feature-compact ::-webkit-scrollbar-thumb {
  background: #888 !important; border-radius: 999px !important;
  border: 2px solid transparent !important; background-clip: padding-box !important;
}
html::-webkit-scrollbar-thumb:hover, body::-webkit-scrollbar-thumb:hover,
.feature-compact ::-webkit-scrollbar-thumb:hover { background: #777 !important; background-clip: padding-box !important; }
html::-webkit-scrollbar-button, body::-webkit-scrollbar-button,
.feature-compact ::-webkit-scrollbar-button { display: block !important; width: 10px !important; height: 10px !important; background-color: transparent !important; }

</style>

<link rel="stylesheet" href="admin_appearance.css">
<script src="admin_appearance.js"></script>
<link rel="stylesheet" href="admin_unified_ui.css?v=<?= (int)@filemtime(__DIR__ . '/admin_unified_ui.css') ?>">
</head>
<body class="feature-compact ux-nested">
<div class="wrap">
 <div class="stack">

  <div class="grid">
    <!-- Profile details -->
    <section class="card">
      <h2>Profile Details</h2>
      <div class="sub">Update your name, username, and designation.</div>
      <form id="detailsForm" autocomplete="off">
        <div class="fg">
          <div><label class="label" for="full_name">Full Name</label><input class="select" id="full_name" name="full_name" type="text" maxlength="120" value="<?= ps_h($u['full_name']) ?>" <?= $nameCol ? '' : 'disabled' ?>></div>
          <div><label class="label" for="username">Username</label><input class="select" id="username" name="username" type="text" maxlength="60" value="<?= ps_h($u['username']) ?>" <?= $userCol ? '' : 'disabled' ?>></div>
          <div class="span2"><label class="label" for="designation">Designation</label><input class="select" id="designation" name="designation" type="text" maxlength="120" value="<?= ps_h($u['designation']) ?>" <?= $desigCol ? '' : 'disabled' ?>></div>
        </div>
        <div class="actions"><button class="btn primary" type="submit" id="detailsBtn"><i class="fa-solid fa-floppy-disk"></i> Save Changes</button></div>
      </form>
    </section>

    <!-- Profile photo -->
    <section class="card">
      <h2>Profile Photo</h2>
      <div class="sub">Update the photo shown in your sidebar and profile.</div>
      <form id="photoForm" class="photo-row" enctype="multipart/form-data">
        <div class="avatar" id="avatar"><?php if ($photoUrl): ?><img src="<?= ps_h($photoUrl) ?>" alt="Profile photo"><?php else: ?><i class="fa-solid fa-user"></i><?php endif; ?></div>
        <div class="photo-ctl">
          <input type="file" name="photo" id="photoInput" accept="image/jpeg,image/png,image/webp">
          <button class="btn primary in-row" type="submit" id="photoBtn"><i class="fa-solid fa-upload"></i> Upload New Photo</button>
          <span class="hint">JPG, PNG, or WEBP only. Maximum file size: 5MB.</span>
        </div>
      </form>
    </section>
  </div>

  <!-- Change password -->
  <section class="card">
    <h2>Change Password</h2>
    <div class="sub">Your new password must be at least 8 characters and different from your current password.</div>
    <form id="pwForm" autocomplete="off">
      <div class="fg">
        <div class="span2 half"><label class="label" for="current_password">Current Password</label>
          <div class="pw"><input class="select" id="current_password" name="current_password" type="password" autocomplete="current-password"><button type="button" class="eye" aria-label="Show password"><i class="fa-regular fa-eye-slash"></i></button></div></div>
        <div><label class="label" for="new_password">New Password</label>
          <div class="pw"><input class="select" id="new_password" name="new_password" type="password" autocomplete="new-password" minlength="8"><button type="button" class="eye" aria-label="Show password"><i class="fa-regular fa-eye-slash"></i></button></div></div>
        <div><label class="label" for="confirm_password">Confirm New Password</label>
          <div class="pw"><input class="select" id="confirm_password" name="confirm_password" type="password" autocomplete="new-password" minlength="8"><button type="button" class="eye" aria-label="Show password"><i class="fa-regular fa-eye-slash"></i></button></div></div>
      </div>
      <div class="actions"><button class="btn primary" type="submit" id="pwBtn"><i class="fa-solid fa-key"></i> Update Password</button></div>
    </form>
  </section>

  <!-- Security questions (hidden until the dropdown button is clicked) -->
  <section class="card sq-card">
    <button type="button" class="sq-toggle" id="sqToggle" aria-expanded="false" aria-controls="sqBody">
      <span><h2>Security Questions</h2><span class="sub">Used for password recovery. Click to view or change your questions.</span></span>
      <span class="sq-chev"><i class="fa-solid fa-chevron-down"></i></span>
    </button>
    <div class="sq-body" id="sqBody" hidden>
      <div class="success" style="margin-top:14px"><i class="fa-solid fa-shield-halved" style="margin-top:2px"></i><span>Choose 3 different questions. Your password is required to save any change.</span></div>
      <form id="sqForm" autocomplete="off">
        <?php for ($n = 1; $n <= 3; $n++): ?>
        <div class="sq-item">
          <label class="label" for="sq_q<?= $n ?>">Question <?= $n ?></label>
          <select class="select" name="sq_question[<?= $n ?>]" id="sq_q<?= $n ?>">
            <option value="" disabled selected>Choose a question</option>
            <?php foreach ($sq_all as $k => $q): ?>
            <option value="<?= ps_h($k) ?>"><?= ps_h($q) ?></option>
            <?php endforeach; ?>
          </select>
          <input class="select" type="text" name="sq_answer[<?= $n ?>]" id="sq_a<?= $n ?>" maxlength="100" placeholder="Your answer" autocomplete="off">
        </div>
        <?php endfor; ?>
        <div class="half"><label class="label" for="sq_password">Your Password</label>
          <div class="pw"><input class="select" id="sq_password" name="sq_password" type="password" autocomplete="current-password" placeholder="Required to save changes"><button type="button" class="eye" aria-label="Show password"><i class="fa-regular fa-eye-slash"></i></button></div></div>
        <div class="actions"><button class="btn primary" type="submit" id="sqBtn"><i class="fa-solid fa-floppy-disk"></i> Update Security Questions</button></div>
      </form>
    </div>
  </section>

 </div>
</div>
<div class="toast" id="toast"></div>
<script>
const CSRF = <?= json_encode($csrf) ?>;
const toastEl = document.getElementById('toast'); let tt;
function toast(msg, ok){ clearTimeout(tt); toastEl.textContent = msg; toastEl.className = 'toast show ' + (ok ? 'ok' : 'err'); tt = setTimeout(()=>toastEl.classList.remove('show'), 2800); }

async function send(action, form, btn){
  const fd = new FormData(form); fd.append('action', action); fd.append('csrf', CSRF);
  btn.disabled = true;
  try{
    const r = await fetch('profile_security.php', {method:'POST', body:fd, credentials:'same-origin'});
    const j = await r.json();
    toast(j.message || (j.ok ? 'Saved.' : 'Something went wrong.'), !!j.ok);
    return j;
  }catch(e){ toast('Network error. Please try again.', false); return {ok:false}; }
  finally{ btn.disabled = false; }
}

// Photo
document.getElementById('photoForm').addEventListener('submit', async e=>{
  e.preventDefault();
  const inp = document.getElementById('photoInput');
  if(!inp.files.length){ toast('Please choose a photo to upload.', false); return; }
  if(inp.files[0].size > 5*1024*1024){ toast('File is too large. Maximum size is 5MB.', false); return; }
  const j = await send('photo', e.target, document.getElementById('photoBtn'));
  if(j.ok){
    document.getElementById('avatar').innerHTML = '<img alt="Profile photo" src="'+j.url+'">';
    inp.value = '';
    try{ window.parent.postMessage({type:'profile-updated', photo:j.url}, '*'); }catch(_){}
  }
});

// Details
document.getElementById('detailsForm').addEventListener('submit', async e=>{
  e.preventDefault();
  const j = await send('details', e.target, document.getElementById('detailsBtn'));
  if(j.ok){
    try{ window.parent.postMessage({type:'profile-updated', name:document.getElementById('full_name').value, designation:document.getElementById('designation').value}, '*'); }catch(_){}
  }
});

// Password
document.getElementById('pwForm').addEventListener('submit', async e=>{
  e.preventDefault();
  const n = document.getElementById('new_password').value, c = document.getElementById('confirm_password').value;
  if(n.length < 8){ toast('New password must be at least 8 characters.', false); return; }
  if(n !== c){ toast('New password and confirmation do not match.', false); return; }
  const j = await send('password', e.target, document.getElementById('pwBtn'));
  if(j.ok) e.target.reset();
});

// Security questions dropdown (collapsed by default)
(function(){
  const t=document.getElementById('sqToggle'), b=document.getElementById('sqBody');
  t.addEventListener('click',()=>{ const open=t.getAttribute('aria-expanded')==='true'; t.setAttribute('aria-expanded',open?'false':'true'); b.hidden=open; });
})();

// Security questions: keep the three dropdowns from repeating the same question
(function(){
  const sel = [1,2,3].map(n=>document.getElementById('sq_q'+n));
  function sync(){
    const chosen = sel.map(s=>s.value).filter(Boolean);
    sel.forEach(s=>Array.from(s.options).forEach(o=>{ o.disabled = o.value==='' || (chosen.includes(o.value) && o.value!==s.value); }));
  }
  sel.forEach(s=>s.addEventListener('change', sync)); sync();
})();
document.getElementById('sqForm').addEventListener('submit', async e=>{
  e.preventDefault();
  if(!document.getElementById('sq_password').value){ toast('Enter your password to change your security questions.', false); return; }
  const j = await send('questions', e.target, document.getElementById('sqBtn'));
  if(j.ok){ e.target.reset(); [1,2,3].forEach(n=>document.getElementById('sq_q'+n).dispatchEvent(new Event('change'))); }
});

// Show / hide password
document.querySelectorAll('.pw .eye').forEach(b=>b.addEventListener('click',()=>{
  const i = b.parentElement.querySelector('input'), show = i.type === 'password';
  i.type = show ? 'text' : 'password';
  b.innerHTML = '<i class="fa-regular fa-eye' + (show ? '' : '-slash') + '"></i>';
}));
</script>
<script src="admin_ajax.php"></script>
</body>
</html>