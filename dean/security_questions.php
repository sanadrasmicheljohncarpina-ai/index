<?php
// dean/security_questions.php
// Dean: set or update password-recovery security questions.
// Uses the Dean portal's own sidebar, header and theme (see dean_account_settings.php).
session_set_cookie_params([
    'lifetime' => 0, 'path' => '/', 'domain' => '',
    'secure' => false, 'httponly' => true, 'samesite' => 'Lax',
]);
session_start();
require_once 'db.php';

if (empty($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'dean') {
    header('Location: dean_login.php');
    exit;
}
if (empty($_SESSION['dean_settings_csrf'])) $_SESSION['dean_settings_csrf'] = bin2hex(random_bytes(32));
$csrfToken = $_SESSION['dean_settings_csrf'];
$errors = [];
$userId = (int)$_SESSION['user_id'];

// Current Dean profile (sidebar + username)
$stmt = $mysqli->prepare("SELECT full_name, username, email, designation, photo FROM users WHERE id = ? AND role = 'dean' LIMIT 1");
$stmt->bind_param('i', $userId);
$stmt->execute();
$me = $stmt->get_result()->fetch_assoc();
$stmt->close();
if (!$me) { session_unset(); session_destroy(); header('Location: dean_login.php'); exit; }

// ── Security questions: save handler ─────────────────────────
mysqli_report(MYSQLI_REPORT_OFF);
if (!function_exists('sq_load')) { require_once 'security.php'; }   // sq_questions(), sq_validate(), sq_save(), sq_load()
security_ensure_tables($mysqli);
$allQuestions = sq_questions();

$sqUsername = (string)($me['username'] ?? '');
if ($sqUsername === '') {
    if ($st = $mysqli->prepare("SELECT username FROM users WHERE id = ? LIMIT 1")) {
        $st->bind_param('i', $userId); $st->execute();
        $sqUsername = (string)(($st->get_result()->fetch_assoc())['username'] ?? ''); $st->close();
    }
}
$storedPw = null;
foreach (['password_hash', 'password'] as $col) {
    try {
        $st = $mysqli->prepare("SELECT `$col` AS pw FROM users WHERE id = ? LIMIT 1");
        if (!$st) continue;
        $st->bind_param('i', $userId); $st->execute();
        $r = $st->get_result()->fetch_assoc(); $st->close();
        if ($r && isset($r['pw']) && (string)$r['pw'] !== '') { $storedPw = (string)$r['pw']; break; }
    } catch (Throwable $e) { /* column missing: try the next one */ }
}
$passwordOk = function (string $given) use ($storedPw): bool {
    if ($storedPw === null || $given === '') return false;
    return !empty(password_get_info($storedPw)['algo'])
        ? password_verify($given, $storedPw)
        : hash_equals($storedPw, $given);   // legacy plain-text value
};
$alreadySet = false;
try { $alreadySet = count(sq_load($mysqli, $userId)) === 3; } catch (Throwable $e) {}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!hash_equals($csrfToken, (string)($_POST['csrf_token'] ?? ''))) {
        $errors[] = 'Your session expired. Please try again.';
    } elseif (!$passwordOk((string)($_POST['current_password'] ?? ''))) {
        $errors[] = 'Your current password is not correct.';
    } else {
        [$sqErr, $sqRows] = sq_validate($_POST['sq_question'] ?? [], $_POST['sq_answer'] ?? [], $sqUsername);
        if ($sqErr !== null) {
            $errors[] = $sqErr;
        } else {
            try {
                // Replace the old answers and save the new ones together (all or nothing).
                $mysqli->begin_transaction();
                $del = $mysqli->prepare("DELETE FROM student_security_answers WHERE user_id = ?");
                $del->bind_param('i', $userId); $del->execute(); $del->close();
                sq_save($mysqli, $userId, $sqRows);
                $mysqli->commit();
                $_SESSION['sq_flash'] = 'Security questions updated successfully.';
                header('Location: dean_account_settings.php');
                exit;
            } catch (Throwable $e) {
                try { $mysqli->rollback(); } catch (Throwable $ignored) {}
                error_log('security_questions.php failed: ' . $e->getMessage());
                $errors[] = 'The security questions could not be saved. Please try again.';
            }
        }
    }
}

$photoUrl = !empty($me['photo']) ? UPLOAD_URL . $me['photo'] : UPLOAD_URL . 'pbi_logo';
$displayDesignation = trim((string)($me['designation'] ?? '')) !== '' ? $me['designation'] : 'Dean';
$active = 'settings';
$sidebarScope = 'Higher Education Division';
?>
<!DOCTYPE html>
<html lang="en" class="dean-internal-scroll-page">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>PBI — Settings</title>
<link href="https://fonts.googleapis.com/css2?family=Rajdhani:wght@600;700&family=DM+Sans:wght@400;500;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<style>
:root{--dark:#0A192F;--mid:#172A45;--inner:#0F1F3D;--violet:#7C5FD9;--violet-h:#9C85F0;--light:#E0E6F0;--muted:#A0B3C6;--danger:#f05454;--good:#10B981;--shadow:0 8px 32px rgba(0,0,0,.45);--radius:12px}
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
body{min-height:100vh;background:linear-gradient(rgba(5,18,36,.72),rgba(5,18,36,.82)),url('../background.png') center center/cover no-repeat fixed;background-color:var(--dark);font-family:'DM Sans',sans-serif;color:var(--light);display:flex}
.sidebar{width:250px;flex-shrink:0;background:#0F1F33;border-right:1px solid #060E18;min-height:100vh;padding:28px 20px;display:flex;flex-direction:column}
.sb-profile{text-align:center;margin-bottom:26px}.sb-photo{width:72px;height:72px;border-radius:50%;object-fit:cover;border:2.5px solid var(--violet);box-shadow:0 0 18px rgba(124,95,217,.4);margin:0 auto 10px;display:block}.sb-name{font-weight:700;font-size:15px;color:#fff}.sb-role{font-size:11px;color:var(--violet-h);text-transform:uppercase;letter-spacing:.6px;margin-top:2px}.sb-scope{font-size:10px;color:var(--muted);margin-top:4px}.sb-nav{display:flex;flex-direction:column;gap:4px;margin-top:10px}.sb-nav a{display:flex;align-items:center;gap:10px;padding:11px 14px;border-radius:8px;color:var(--muted);text-decoration:none;font-size:14px;font-weight:500;transition:.2s}.sb-nav a:hover,.sb-nav a.active{background:rgba(124,95,217,.15);color:#fff}.sb-nav a i{width:18px;text-align:center;color:var(--violet-h)}.sb-logout{margin-top:auto}.sb-logout a{display:flex;align-items:center;gap:10px;padding:11px 14px;border-radius:8px;color:#fca5a5;text-decoration:none;font-size:14px;font-weight:500}.sb-logout a:hover{background:rgba(240,84,84,.12)}
.main{flex:1;padding:36px 44px;min-width:0}.page-header{margin-bottom:26px}.page-title{font-family:'Rajdhani',sans-serif;font-size:30px;font-weight:700;color:#fff;letter-spacing:1px}.page-sub{font-size:13px;color:var(--muted);margin-top:4px}
.alert{border-radius:10px;padding:12px 15px;font-size:13px;margin-bottom:18px;display:flex;align-items:center;gap:9px}.alert.success{background:rgba(16,185,129,.12);border:1px solid rgba(16,185,129,.3);color:#86efac}.alert.error{background:rgba(240,84,84,.12);border:1px solid rgba(240,84,84,.3);color:#ff9a9a}
.section{background:rgba(23,42,69,.86);border:1px solid rgba(255,255,255,.08);border-radius:14px;padding:24px;box-shadow:var(--shadow);margin-bottom:22px}.section h2{font-family:'Rajdhani',sans-serif;font-size:20px;color:#fff;margin-bottom:18px;display:flex;align-items:center;gap:9px}.section h2 i{color:var(--violet-h);font-size:17px}
.profile-card{display:flex;align-items:center;gap:24px}.profile-photo-lg{width:96px;height:96px;border-radius:50%;object-fit:cover;border:3px solid var(--violet);box-shadow:0 0 22px rgba(124,95,217,.3);background:var(--inner)}.photo-help{font-size:12px;color:var(--muted);margin-top:8px;line-height:1.5}
.form-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:17px 20px;margin-bottom:20px}.form-group{display:flex;flex-direction:column;gap:7px}.form-group label{font-size:11px;font-weight:600;letter-spacing:.8px;text-transform:uppercase;color:var(--muted)}.form-group input{width:100%;padding:12px 13px;background:rgba(10,25,47,.72);border:1px solid rgba(255,255,255,.1);border-radius:9px;color:var(--light);font:14px 'DM Sans',sans-serif;outline:none;transition:.2s}.form-group input:focus{border-color:var(--violet);box-shadow:0 0 0 3px rgba(124,95,217,.16)}.form-group input:disabled{opacity:.65;cursor:not-allowed;background:rgba(10,25,47,.5)}
.btn-primary{padding:11px 17px;border:0;border-radius:9px;background:var(--violet);color:#fff;font:600 14px 'DM Sans',sans-serif;cursor:pointer;box-shadow:0 4px 14px rgba(124,95,217,.3);transition:.2s}.btn-primary:hover{background:var(--violet-h);transform:translateY(-1px)}.btn-secondary{padding:11px 17px;border:1px solid rgba(255,255,255,.12);border-radius:9px;background:rgba(10,25,47,.5);color:var(--light);font:600 14px 'DM Sans',sans-serif;cursor:pointer}
.setting-row{display:flex;justify-content:space-between;align-items:flex-start;gap:20px;flex-wrap:wrap}.setting-row strong{display:block;font-size:14px;color:var(--text-l,#fff);margin-bottom:2px}.setting-row .appearance-hint{font-size:12px;color:var(--muted-l,var(--muted))}.appearance-choice-row{display:flex;gap:10px;flex-wrap:wrap}.appearance-choice{display:flex;align-items:center;gap:8px;padding:10px 16px;border-radius:9px;border:1px solid var(--line-strong-l,rgba(255,255,255,.12));background:var(--card-l,rgba(10,25,47,.5));color:var(--muted-l,var(--muted));font:600 13px 'DM Sans',sans-serif;cursor:pointer;transition:.2s}.appearance-choice:hover{border-color:#7C5FD9;color:#7C5FD9}.appearance-choice.active{background:rgba(124,95,217,.14);border-color:#7C5FD9;color:#7C5FD9}
.password-note{font-size:12px;color:var(--muted);margin:-5px 0 18px}.info-grid{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:14px;margin-bottom:20px}.info-item{padding:14px;border-radius:10px;background:rgba(10,25,47,.42);border:1px solid rgba(255,255,255,.06)}.info-item span{display:block;font-size:10px;text-transform:uppercase;letter-spacing:.7px;color:var(--muted);margin-bottom:5px}.info-item strong{font-size:14px;color:#fff;font-weight:600;word-break:break-word}
@media(max-width:900px){.sidebar{width:220px}.main{padding:28px 24px}.form-grid,.info-grid{grid-template-columns:1fr}}@media(max-width:650px){body{display:block}.sidebar{width:100%;min-height:auto;padding:18px}.sb-nav{display:grid;grid-template-columns:repeat(2,1fr)}.sb-logout{margin-top:12px}.main{padding:24px 16px}.profile-card{align-items:flex-start;flex-direction:column}.form-grid,.info-grid{grid-template-columns:1fr}}
</style>
<link rel="stylesheet" href="includes/dean_light_theme.css?v=dashboard-ui-20261009"/>

<style>
/* Keep the Dean sidebar fixed and scroll this feature workspace internally. */
html.dean-internal-scroll-page,
html.dean-internal-scroll-page body {
  overflow: hidden !important;
  height: 100% !important;
}

main.main.dean-internal-scroll {
  height: calc(100vh - 20px) !important;
  max-height: calc(100vh - 20px) !important;
  min-height: 0 !important;
  overflow-y: scroll !important;
  overflow-x: hidden !important;
  scrollbar-gutter: stable;
  overscroll-behavior: contain;
  scrollbar-width: thin;
  scrollbar-color: #AEBAC8 #EEF2F6;
}

main.main.dean-internal-scroll::-webkit-scrollbar {
  width: 10px;
}

main.main.dean-internal-scroll::-webkit-scrollbar-track {
  background: #EEF2F6;
  border-radius: 10px;
}

main.main.dean-internal-scroll::-webkit-scrollbar-thumb {
  background: #AEBAC8;
  border: 2px solid #EEF2F6;
  border-radius: 10px;
}

main.main.dean-internal-scroll::-webkit-scrollbar-thumb:hover {
  background: #8F9CAB;
}

html.dark-theme main.main.dean-internal-scroll {
  scrollbar-color: #2A4468 #0F1F3D;
}

html.dark-theme main.main.dean-internal-scroll::-webkit-scrollbar-track {
  background: #0F1F3D;
}

html.dark-theme main.main.dean-internal-scroll::-webkit-scrollbar-thumb {
  background: #2A4468;
  border-color: #0F1F3D;
}

html.dark-theme main.main.dean-internal-scroll::-webkit-scrollbar-thumb:hover {
  background: #385A86;
}

@media (max-width: 768px) {
  html.dean-internal-scroll-page,
  html.dean-internal-scroll-page body {
    overflow: auto !important;
    height: auto !important;
  }

  main.main.dean-internal-scroll {
    height: auto !important;
    max-height: none !important;
    min-height: calc(100vh - 12px) !important;
    overflow: visible !important;
    scrollbar-gutter: auto;
  }
}
</style>
<script>
(function(){try{var d=document.documentElement;
if(localStorage.getItem('dean_density')==='compact')d.classList.add('dean-density-compact');
if(localStorage.getItem('dean_reduce_motion')==='1')d.classList.add('dean-reduce-motion');}catch(e){}})();
</script>
</head>
<body>
<?php $photo_src = $photoUrl; include __DIR__ . '/includes/dean_sidebar.php'; if (is_file(__DIR__ . '/dean_header_dark.php')) include __DIR__ . '/dean_header_dark.php'; ?>
<main class="main dean-internal-scroll">
<style id="sq-page">
/* Security questions page. Surfaces follow the portal theme; only the accent is per portal. */
main.main .sqp{--sq-acc:var(--violet,#7C5FD9);--sq-acc-h:var(--violet-h,#9C85F0);--sq-soft:color-mix(in srgb,var(--sq-acc) 13%,transparent);--sq-line:rgba(128,140,160,.30);max-width:680px;margin:0 auto;padding:6px 0 40px}
main.main .sqp-back{display:inline-flex;gap:8px;align-items:center;margin:0 0 14px;color:var(--muted);text-decoration:none;font-weight:600;font-size:12.5px}
main.main .sqp-back:hover{color:var(--sq-acc)}
main.main .sqp-card{background:var(--mid);color:var(--light);border:1px solid var(--sq-line);border-radius:16px;padding:26px;box-shadow:var(--shadow)}
main.main .sqp-card h1{margin:0 0 4px;font-size:20px;color:var(--light);font-family:inherit}
main.main .sqp-sub{margin:0 0 18px;color:var(--muted);font-size:13px}
main.main .sqp-alert{display:flex;gap:10px;align-items:flex-start;padding:11px 14px;margin-bottom:16px;border-radius:10px;background:rgba(240,84,84,.12);border:1px solid rgba(240,84,84,.35);color:#E05252;font-weight:600}
main.main .sqp-confirm{padding:18px;margin-bottom:6px;border:2px solid var(--sq-acc);border-radius:14px;background:var(--sq-soft);box-shadow:0 0 0 4px color-mix(in srgb,var(--sq-acc) 12%,transparent)}
main.main .sqp-confirm-head{display:flex;gap:12px;align-items:center;margin-bottom:14px}
main.main .sqp-confirm-icon{flex:0 0 40px;height:40px;border-radius:50%;display:grid;place-items:center;background:var(--sq-acc);color:#fff;font-size:16px}
main.main .sqp-confirm-title{font-size:15px;font-weight:800;color:var(--light)}
main.main .sqp-confirm-sub{font-size:12px;color:var(--muted);margin-top:1px}
main.main .sqp-label{display:block;margin-bottom:6px;font-size:12px;font-weight:800;letter-spacing:.04em;text-transform:uppercase;color:var(--light)}
main.main .sqp-label .req{color:#E05252}
main.main .sqp-pw{position:relative}
main.main .sqp input[type=text],main.main .sqp input[type=password],main.main .sqp select{width:100%;height:44px;padding:0 13px;border:1px solid var(--sq-line);border-radius:9px;background:var(--inner);color:var(--light);font-size:13.5px;font-weight:500;font-family:inherit}
main.main .sqp .sqp-pw input{height:48px;padding-right:46px;border:1.5px solid var(--sq-acc);font-size:14px}
main.main .sqp select{appearance:none;-webkit-appearance:none;cursor:pointer;padding-right:34px;background-image:url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='8' viewBox='0 0 12 8'%3E%3Cpath d='M1 1l5 5 5-5' fill='none' stroke='%2367819E' stroke-width='2' stroke-linecap='round'/%3E%3C/svg%3E");background-repeat:no-repeat;background-position:right 12px center}
main.main .sqp input:focus,main.main .sqp select:focus{outline:0;border-color:var(--sq-acc);box-shadow:0 0 0 3px color-mix(in srgb,var(--sq-acc) 24%,transparent)}
main.main .sqp select:invalid{color:var(--sq-acc);font-weight:700}
main.main .sqp select option{background:var(--mid);color:var(--light);font-weight:500}
main.main .sqp select option[value=""]{color:var(--sq-acc);font-weight:700}
main.main .sqp select option:checked{color:var(--sq-acc);font-weight:700}
main.main .sqp select option:disabled{color:#8A97A8}
main.main .sqp-eye{position:absolute;right:8px;top:50%;transform:translateY(-50%);border:0;background:none;color:var(--muted);cursor:pointer;padding:8px;line-height:1}
main.main .sqp-eye:hover{color:var(--sq-acc)}
main.main .sqp-sep{height:1px;background:var(--sq-line);margin:18px 0}
main.main .sqp-note{font-size:12px;color:var(--muted);margin:0 0 14px}
main.main .sqp-item{padding:14px;margin-bottom:12px;border:1px solid var(--sq-line);border-radius:12px;background:var(--inner)}
main.main .sqp-item h3{display:flex;align-items:center;gap:10px;margin:0 0 12px}
main.main .sqp-num{flex:0 0 auto;width:26px;height:26px;border-radius:50%;display:grid;place-items:center;background:var(--sq-soft);color:var(--sq-acc);font-weight:800;font-size:12.5px}
main.main .sqp-field{display:flex;flex-direction:column;gap:6px;margin-bottom:12px}
main.main .sqp-item .sqp-field:last-child{margin-bottom:0}
main.main .sqp-chosen{font-size:12.5px;color:var(--light);padding:0 2px}
main.main .sqp-actions{display:flex;gap:10px;justify-content:flex-end;margin-top:18px}
main.main .sqp-btn{display:inline-flex;align-items:center;gap:8px;border:1px solid var(--sq-line);border-radius:9px;padding:11px 18px;font-size:13px;font-weight:700;font-family:inherit;color:var(--light);background:transparent;cursor:pointer;text-decoration:none}
main.main .sqp-btn.primary{background:var(--sq-acc);border-color:var(--sq-acc);color:#fff}
main.main .sqp-btn.primary:hover{background:var(--sq-acc-h);border-color:var(--sq-acc-h)}
</style>
<div class="sqp">
    <a class="sqp-back" href="dean_account_settings.php"><i class="fa-solid fa-arrow-left"></i> Back to Profile &amp; Security</a>
    <div class="sqp-card">
        <h1><?= $alreadySet ? 'Update Security Questions' : 'Set Security Questions' ?></h1>
        <p class="sqp-sub">These questions are used to recover your account if you forget your password. Choose 3 different questions.</p>

        <?php foreach ($errors as $err): ?><div class="sqp-alert" role="alert"><i class="fa-solid fa-circle-exclamation"></i><span><?= htmlspecialchars($err) ?></span></div><?php endforeach; ?>

        <form method="post" action="security_questions.php" autocomplete="off" id="sqForm">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">

            <div class="sqp-confirm">
                <div class="sqp-confirm-head">
                    <span class="sqp-confirm-icon"><i class="fa-solid fa-lock"></i></span>
                    <div>
                        <div class="sqp-confirm-title">Confirm it&rsquo;s you first</div>
                        <div class="sqp-confirm-sub">Enter your current password to continue. It is required to save any changes.</div>
                    </div>
                </div>
                <label for="current_password" class="sqp-label">Current password <span class="req">*</span></label>
                <div class="sqp-pw">
                    <input type="password" id="current_password" name="current_password" required autofocus autocomplete="current-password" placeholder="Enter your current password">
                    <button type="button" class="sqp-eye" id="pwEye" aria-label="Show password" title="Show password"><i class="fa-solid fa-eye-slash"></i></button>
                </div>
            </div>

            <div class="sqp-sep"></div>
            <p class="sqp-note"><?= $alreadySet ? 'Saving replaces your current questions and answers. For your security, your existing answers are never shown.' : 'Pick a question and give an answer only you would know.' ?></p>

            <?php for ($n = 1; $n <= 3; $n++): $selQ = (string)($_POST['sq_question'][$n] ?? ''); ?>
            <div class="sqp-item">
                <h3><span class="sqp-num"><?= $n ?></span></h3>
                <div class="sqp-field">
                    <select name="sq_question[<?= $n ?>]" id="sq_q<?= $n ?>" required>
                        <option value="" <?= $selQ === '' ? 'selected' : '' ?>>Choose a question</option>
                        <?php foreach ($allQuestions as $k => $q): ?>
                        <option value="<?= htmlspecialchars($k) ?>" <?= $selQ === (string)$k ? 'selected' : '' ?>><?= htmlspecialchars($q) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <div class="sqp-chosen" id="sq_chosen<?= $n ?>" aria-live="polite"></div>
                </div>
                <div class="sqp-field">
                    <input type="text" name="sq_answer[<?= $n ?>]" id="sq_a<?= $n ?>" maxlength="100" placeholder="Your answer" autocomplete="off" required>
                </div>
            </div>
            <?php endfor; ?>

            <div class="sqp-actions">
                <a class="sqp-btn" href="dean_account_settings.php">Cancel</a>
                <button class="sqp-btn primary" type="submit"><i class="fa-solid fa-floppy-disk"></i> Save security questions</button>
            </div>
        </form>
    </div>
</div>
<script>
(function(){
  var selects=[1,2,3].map(function(n){return document.getElementById('sq_q'+n);});
  function sync(){
    var chosen=selects.map(function(s){return s.value;}).filter(Boolean);
    selects.forEach(function(s,i){
      Array.prototype.forEach.call(s.options,function(o){ o.disabled = o.value!=='' && chosen.indexOf(o.value)!==-1 && o.value!==s.value; });
      var el=document.getElementById('sq_chosen'+(i+1));
      if(el) el.textContent = s.value ? s.options[s.selectedIndex].text : '';
    });
  }
  selects.forEach(function(s){ s.addEventListener('change',sync); });
  sync();
  var b=document.getElementById('pwEye'), i=document.getElementById('current_password');
  if(b&&i) b.addEventListener('click',function(){
    var show=i.type==='password'; i.type=show?'text':'password';
    b.innerHTML='<i class="fa-solid '+(show?'fa-eye':'fa-eye-slash')+'"></i>';
    b.setAttribute('aria-label',show?'Hide password':'Show password');
  });
})();
</script>
</main>
<script>
/* Dean appearance controls — same options as the Principal settings page. */
(function(){
  var KEY_THEME='dean_theme_pref', KEY_DENSITY='dean_density', KEY_MOTION='dean_reduce_motion';
  var root=document.documentElement, mq=window.matchMedia?window.matchMedia('(prefers-color-scheme: dark)'):null;
  function get(k,d){try{var v=localStorage.getItem(k);return v===null?d:v;}catch(e){return d;}}
  function set(k,v){try{localStorage.setItem(k,v);}catch(e){}}

  function currentPref(){
    var p=get(KEY_THEME,null);
    if(p==='light'||p==='dark'||p==='system')return p;
    return root.classList.contains('dark-theme')?'dark':'light';
  }
  function applyResolved(mode){
    // Prefer the portal's own switcher (defined in the shared sidebar/header) when present.
    if(typeof window.setAppearance==='function'){ try{ window.setAppearance(mode); }catch(e){} }
    root.classList.toggle('dark-theme',mode==='dark');
  }
  function paintTheme(pref){
    document.querySelectorAll('[data-theme-choice]').forEach(function(b){
      var on=b.getAttribute('data-theme-choice')===pref;
      b.setAttribute('aria-pressed',on?'true':'false'); b.classList.toggle('active',on);
    });
    var out=document.getElementById('appearanceCurrentMode');
    if(out){ var eff=root.classList.contains('dark-theme')?'Dark':'Light';
      out.textContent=pref==='system'?'System ('+eff+')':eff; }
  }
  window.setDeanAppearance=function(pref){
    set(KEY_THEME,pref);
    applyResolved(pref==='system'?(mq&&mq.matches?'dark':'light'):pref);
    paintTheme(pref);
  };
  if(mq){ var onChange=function(){ if(currentPref()==='system'){ applyResolved(mq.matches?'dark':'light'); paintTheme('system'); } };
    if(mq.addEventListener)mq.addEventListener('change',onChange); else if(mq.addListener)mq.addListener(onChange); }

  function paintDensity(d){
    document.querySelectorAll('[data-density-choice]').forEach(function(b){
      b.setAttribute('aria-pressed',b.getAttribute('data-density-choice')===d?'true':'false');
    });
  }
  window.setDeanDensity=function(d){
    root.classList.toggle('dean-density-compact',d==='compact');
    set(KEY_DENSITY,d); paintDensity(d);
  };

  function paintMotion(on){
    var b=document.getElementById('appearanceMotionBtn'); if(b)b.setAttribute('aria-pressed',on?'true':'false');
  }
  window.toggleDeanReducedMotion=function(){
    var on=!root.classList.contains('dean-reduce-motion');
    root.classList.toggle('dean-reduce-motion',on);
    set(KEY_MOTION,on?'1':'0'); paintMotion(on);
  };

  document.addEventListener('DOMContentLoaded',function(){
    var pref=currentPref();
    if(pref==='system')applyResolved(mq&&mq.matches?'dark':'light');
    paintTheme(pref);
    paintDensity(get(KEY_DENSITY,'comfortable')==='compact'?'compact':'comfortable');
    paintMotion(get(KEY_MOTION,'0')==='1');
  });
})();
</script>
<style id="dean-password-toggle">
main.main .pw-wrap{position:relative;display:block;}
main.main .pw-wrap input{width:100%;padding-right:46px !important;}
main.main .pw-toggle{position:absolute;top:50%;right:8px;transform:translateY(-50%);width:34px;height:34px;display:flex;align-items:center;justify-content:center;border:0;border-radius:8px;background:transparent;color:#64748B;cursor:pointer;padding:0;}
main.main .pw-toggle .pw-eye{display:none;}
main.main .pw-toggle .pw-eye-off{display:block;}
main.main .pw-toggle[aria-pressed="true"] .pw-eye{display:block;}
main.main .pw-toggle[aria-pressed="true"] .pw-eye-off{display:none;}
main.main .pw-toggle:hover{color:#5F45B8;background:rgba(124,95,217,.12);}
main.main .pw-toggle:focus-visible{outline:2px solid #7C5FD9;outline-offset:1px;}
</style>
<script>
/* Settings tabs: Profile & Security | Appearance */
(function(){
    var tabs = Array.prototype.slice.call(document.querySelectorAll('.ac-tab'));
    var panels = Array.prototype.slice.call(document.querySelectorAll('.ac-tabpanel'));
    function show(name, focus){
        tabs.forEach(function(t){
            var on = t.getAttribute('data-tab') === name;
            t.classList.toggle('active', on);
            t.setAttribute('aria-selected', on ? 'true' : 'false');
            t.tabIndex = on ? 0 : -1;
            if (on && focus) t.focus();
        });
        panels.forEach(function(p){
            var on = p.id === 'tab-' + name;
            p.classList.toggle('active', on);
            p.hidden = !on;
        });
        try { history.replaceState(null, '', '?tab=' + name); } catch(e){}
    }
    tabs.forEach(function(t, i){
        t.addEventListener('click', function(){ show(t.getAttribute('data-tab')); });
        t.addEventListener('keydown', function(e){
            if (e.key !== 'ArrowRight' && e.key !== 'ArrowLeft') return;
            var n = (i + (e.key === 'ArrowRight' ? 1 : -1) + tabs.length) % tabs.length;
            show(tabs[n].getAttribute('data-tab'), true);
        });
    });
    tabs.forEach(function(t){ if (!t.classList.contains('active')) t.tabIndex = -1; });
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
<script id="dean-rename-settings-label">
/* Sidebar/header label: "Account Settings" -> "Settings" (stopgap until includes/dean_sidebar.php is edited). */
(function(){
    function rename(){
        var w=document.createTreeWalker(document.body,NodeFilter.SHOW_TEXT,{acceptNode:function(n){
            var p=n.parentNode&&n.parentNode.nodeName;
            return (p==='SCRIPT'||p==='STYLE'||p==='TEXTAREA')?NodeFilter.FILTER_REJECT:NodeFilter.FILTER_ACCEPT;
        }});
        var n,list=[];
        while((n=w.nextNode())){ if(n.nodeValue.trim()==='Account Settings') list.push(n); }
        list.forEach(function(t){ t.nodeValue=t.nodeValue.replace('Account Settings','Settings'); });
    }
    if(document.readyState==='loading') document.addEventListener('DOMContentLoaded',rename); else rename();
})();
</script>
</body>
<link rel="stylesheet" href="includes/dean_light_theme.css?v=dashboard-ui-20261009" id="dean-light-theme-final"/>

<style id="ac-faculty-style">
/* ── Account Settings — same card language as the Faculty portal ──
   Coloured top bar, icon tile, title + description. Surface/text colours are left to the
   existing theme so light and dark mode keep working; only structure + accents change. */
main.main{--ac:#7C5FD9;}
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
<style id="dean-principal-ui-match">
/* ================================================================
   Dean settings — matched to the Principal settings interface:
   white viewport + inset #F8FAFC workspace, Dean sidebar and violet
   accents, white cards with a coloured top bar, and the three-card
   Appearance panel (Theme / Spacing / Accessibility).
   ================================================================ */
html:not(.dark-theme){background:#FFFFFF !important;color-scheme:light !important;}
html:not(.dark-theme) body{background:#FFFFFF !important;background-image:none !important;color:#172033 !important;}

/* Inset workspace canvas */
html:not(.dark-theme) main.main{
  position:relative !important;flex:1 1 auto !important;width:auto !important;max-width:none !important;
  margin:20px 20px 0 20px !important;padding:24px 34px 34px !important;
  background:#F8FAFC !important;color:#172033 !important;
  border-radius:16px 16px 0 0 !important;box-shadow:none !important;
}
html:not(.dark-theme) main.main::before{display:none !important;content:none !important;}
html:not(.dark-theme) main.main > .page-header{background:transparent !important;border:0 !important;box-shadow:none !important;padding:0 !important;margin:0 0 22px !important;}
html:not(.dark-theme) .page-title{color:#0F172A !important;}

/* White cards */
html:not(.dark-theme) main.main .section{
  background:#FFFFFF !important;color:#172033 !important;border-color:#D9E4EF !important;
  box-shadow:0 1px 5px rgba(15,23,42,.035) !important;
}
html:not(.dark-theme) main.main .ac-head h2{color:#0F172A !important;}
html:not(.dark-theme) main.main .ac-head .ac-sub,
html:not(.dark-theme) .photo-help,
html:not(.dark-theme) .form-group label{color:#64748B !important;}

/* Inputs + buttons */
html:not(.dark-theme) .form-group input{background:#FFFFFF !important;color:#172033 !important;border-color:#CBD5E1 !important;}
html:not(.dark-theme) .form-group input:disabled{background:#F1F5F9 !important;}
.form-group input:focus{border-color:#7C5FD9 !important;box-shadow:0 0 0 3px rgba(124,95,217,.12) !important;}
.btn-primary{background:#7C5FD9 !important;color:#fff !important;box-shadow:0 4px 14px rgba(124,95,217,.25) !important;}
.btn-primary:hover{background:#9C85F0 !important;color:#fff !important;}
.profile-photo-lg{border-color:#7C5FD9 !important;box-shadow:0 0 22px rgba(124,95,217,.22) !important;}
html:not(.dark-theme) .info-item{background:#F8FAFC !important;border-color:#E2E8F0 !important;}
html:not(.dark-theme) .info-item span{color:#64748B !important;}
html:not(.dark-theme) .info-item strong{color:#0F172A !important;}
html:not(.dark-theme) .alert.success{background:rgba(16,185,129,.10);color:#047857;border-color:rgba(16,185,129,.25);}
html:not(.dark-theme) .alert.error{background:rgba(240,84,84,.10);color:#b91c1c;border-color:rgba(240,84,84,.25);}

/* ── Appearance panel ─────────────────────────────────────────── */
main.main{--ap-card:#FFFFFF;--ap-soft:#F8FAFC;--ap-line:#E2E8F0;--ap-text:#0F172A;--ap-muted:#64748B;--ap-accent:#7C5FD9;--ap-accent-bg:rgba(124,95,217,.10);}
html.dark-theme main.main{--ap-card:rgba(10,25,47,.55);--ap-soft:rgba(10,25,47,.45);--ap-line:rgba(255,255,255,.10);--ap-text:#E0E6F0;--ap-muted:#A0B3C6;--ap-accent-bg:rgba(124,95,217,.16);}

.appearance-device-badge{margin-left:auto;display:inline-flex;align-items:center;gap:7px;align-self:flex-start;padding:6px 12px;border-radius:999px;font-size:11.5px;font-weight:600;white-space:nowrap;background:var(--ap-accent-bg);color:#6B4FCB;border:1px solid rgba(124,95,217,.28);}
html.dark-theme .appearance-device-badge{color:#9C85F0;}
.appearance-feature-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:16px;}
.appearance-theme-card{grid-column:1 / -1;}
.appearance-control-card{background:var(--ap-soft);border:1px solid var(--ap-line);border-radius:14px;padding:18px;}
.appearance-control-head{display:flex;align-items:flex-start;gap:12px;margin-bottom:14px;}
.appearance-control-icon{width:34px;height:34px;flex:0 0 34px;border-radius:10px;display:inline-flex;align-items:center;justify-content:center;background:var(--ap-accent-bg);color:var(--ap-accent);font-size:14px;}
.appearance-control-head h3{font-size:15px;font-weight:700;color:var(--ap-text);margin:0 0 2px;}
.appearance-control-head p{font-size:12.5px;color:var(--ap-muted);margin:0;line-height:1.45;}

.appearance-theme-options{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:12px;}
.appearance-choice{position:relative;display:flex;align-items:center;gap:12px;padding:14px;text-align:left;border-radius:12px;border:1.5px solid var(--ap-line);background:var(--ap-card);color:var(--ap-text);cursor:pointer;font-family:inherit;transition:border-color .2s,background .2s,box-shadow .2s;}
.appearance-choice:hover{border-color:var(--ap-accent);}
.appearance-choice-icon{width:36px;height:36px;flex:0 0 36px;border-radius:10px;display:inline-flex;align-items:center;justify-content:center;background:var(--ap-soft);color:var(--ap-muted);font-size:15px;}
.appearance-choice-copy strong,.appearance-density-choice strong,.appearance-motion-copy strong{display:block;font-size:13.5px;font-weight:700;color:var(--ap-text);}
.appearance-choice-copy small,.appearance-density-choice small,.appearance-motion-copy small{display:block;font-size:11.5px;color:var(--ap-muted);margin-top:1px;}
.appearance-choice-check{margin-left:auto;width:20px;height:20px;border-radius:50%;display:inline-flex;align-items:center;justify-content:center;font-size:10px;background:var(--ap-accent);color:#fff;opacity:0;transform:scale(.6);transition:.2s;}
.appearance-choice[aria-pressed="true"]{border-color:var(--ap-accent);background:var(--ap-accent-bg);box-shadow:0 0 0 3px rgba(124,95,217,.12);}
.appearance-choice[aria-pressed="true"] .appearance-choice-icon{background:var(--ap-accent);color:#fff;}
.appearance-choice[aria-pressed="true"] .appearance-choice-check{opacity:1;transform:scale(1);}
.appearance-inline-status{margin-top:14px;font-size:12.5px;color:var(--ap-muted);display:flex;align-items:center;gap:7px;}
.appearance-inline-status i{color:var(--ap-accent);}
.appearance-inline-status strong{color:var(--ap-text);}

.appearance-density-options{display:grid;gap:10px;}
.appearance-density-choice{display:flex;align-items:center;gap:12px;padding:12px 14px;text-align:left;border-radius:12px;border:1.5px solid var(--ap-line);background:var(--ap-card);color:var(--ap-muted);cursor:pointer;font-family:inherit;transition:.2s;}
.appearance-density-choice:hover{border-color:var(--ap-accent);}
.appearance-density-choice > i{font-size:15px;width:20px;text-align:center;}
.appearance-density-choice[aria-pressed="true"]{border-color:var(--ap-accent);background:var(--ap-accent-bg);}
.appearance-density-choice[aria-pressed="true"] > i{color:var(--ap-accent);}

.appearance-motion-toggle{width:100%;display:flex;align-items:center;justify-content:space-between;gap:14px;padding:12px 14px;text-align:left;border-radius:12px;border:1.5px solid var(--ap-line);background:var(--ap-card);cursor:pointer;font-family:inherit;transition:.2s;}
.appearance-motion-toggle:hover{border-color:var(--ap-accent);}
.appearance-switch-track{width:44px;height:24px;flex:0 0 44px;border-radius:999px;background:#CBD5E1;position:relative;transition:background .2s;}
.appearance-switch-thumb{position:absolute;top:3px;left:3px;width:18px;height:18px;border-radius:50%;background:#fff;box-shadow:0 1px 3px rgba(0,0,0,.25);transition:transform .2s;}
.appearance-motion-toggle[aria-pressed="true"]{border-color:var(--ap-accent);background:var(--ap-accent-bg);}
.appearance-motion-toggle[aria-pressed="true"] .appearance-switch-track{background:var(--ap-accent);}
.appearance-motion-toggle[aria-pressed="true"] .appearance-switch-thumb{transform:translateX(20px);}
.appearance-accessibility-note{margin-top:12px;font-size:11.5px;color:var(--ap-muted);display:flex;gap:7px;align-items:flex-start;line-height:1.5;}
.appearance-accessibility-note i{color:var(--ap-accent);margin-top:2px;}

/* Density + reduced motion */
html.dean-density-compact main.main{padding-top:18px !important;padding-bottom:24px !important;}
html.dean-density-compact main.main .ac-card{padding:16px 18px 18px !important;margin-bottom:14px !important;}
html.dean-density-compact main.main .ac-head{margin-bottom:12px;}
html.dean-density-compact .form-grid{gap:12px 16px;margin-bottom:14px;}
html.dean-reduce-motion *,html.dean-reduce-motion *::before,html.dean-reduce-motion *::after{transition:none !important;animation:none !important;scroll-behavior:auto !important;}

@media (max-width:900px){.appearance-theme-options{grid-template-columns:1fr;}}
@media (max-width:768px){
  html:not(.dark-theme) main.main{margin:12px 12px 0 12px !important;padding:20px 18px 28px !important;border-radius:12px 12px 0 0 !important;}
  .appearance-feature-grid{grid-template-columns:1fr;}
  .appearance-device-badge{display:none;}
}
@media print{html:not(.dark-theme) main.main{margin:0 !important;background:#fff !important;}}

/* ── Settings tabs ───────────────────────────────────────────── */
.ac-tabs{display:flex;gap:6px;margin:0 0 22px;padding:5px;width:fit-content;max-width:100%;overflow-x:auto;border-radius:14px;background:var(--ap-card,#fff);border:1px solid var(--ap-line,#E2E8F0);}
.ac-tab{display:inline-flex;align-items:center;gap:8px;padding:10px 18px;border:0;border-radius:10px;background:transparent;color:var(--ap-muted,#64748B);font:600 13.5px 'DM Sans',sans-serif;cursor:pointer;white-space:nowrap;transition:background .2s,color .2s;}
.ac-tab i{font-size:14px;}
.ac-tab:hover{color:var(--ap-accent,#7C5FD9);background:var(--ap-accent-bg,rgba(124,95,217,.10));}
.ac-tab.active{background:#7C5FD9;color:#fff;box-shadow:0 4px 14px rgba(124,95,217,.25);}
.ac-tab:focus-visible{outline:2px solid #7C5FD9;outline-offset:2px;}
.ac-tabpanel[hidden]{display:none !important;}
@media(max-width:600px){.ac-tabs{width:100%;}.ac-tab{flex:1;justify-content:center;padding:10px 12px;}}
</style>
</html>
<?php $mysqli->close(); ?>