<?php
// admin/security_questions.php
// Lets the signed-in admin set or update their password-recovery security questions.
// Linked from Settings > Profile & Security > "Set / Update Security Questions".
require_once 'session_bootstrap.php';
require_once 'db.php';
require_once 'security.php';   // sq_questions(), sq_validate(), sq_save(), security_ensure_tables()

if (session_status() !== PHP_SESSION_ACTIVE) session_start();
$meId = (int)($_SESSION['user_id'] ?? 0);
$role = strtolower(trim((string)($_SESSION['role'] ?? '')));
if (!$meId || !in_array($role, ['admin', 'superadmin', 'super admin'], true)) {
    header('Location: admin_login.php');
    exit;
}

mysqli_report(MYSQLI_REPORT_OFF);
security_ensure_tables($mysqli);

// Same CSRF token the Settings page uses.
if (empty($_SESSION['settings_csrf'])) $_SESSION['settings_csrf'] = bin2hex(random_bytes(32));
$csrf = $_SESSION['settings_csrf'];

function sqh($v): string { return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }

$allQuestions = sq_questions();
$error = '';

// Current account
$me = ['username' => '', 'password_hash' => ''];
if ($st = $mysqli->prepare("SELECT username, password_hash FROM users WHERE id = ? LIMIT 1")) {
    $st->bind_param('i', $meId);
    $st->execute();
    $me = array_merge($me, $st->get_result()->fetch_assoc() ?: []);
    $st->close();
}

// Are questions already set?
$alreadySet = false;
if ($st = $mysqli->prepare("SELECT COUNT(*) AS c FROM student_security_answers WHERE user_id = ?")) {
    $st->bind_param('i', $meId);
    $st->execute();
    $row = $st->get_result()->fetch_assoc();
    $st->close();
    $alreadySet = (int)($row['c'] ?? 0) >= 3;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!hash_equals($csrf, (string)($_POST['csrf_token'] ?? ''))) {
        $error = 'Your session expired. Please try again.';
    } elseif (!password_verify((string)($_POST['current_password'] ?? ''), (string)$me['password_hash'])) {
        $error = 'Your current password is not correct.';
    } else {
        [$sqErr, $sqRows] = sq_validate($_POST['sq_question'] ?? [], $_POST['sq_answer'] ?? [], (string)$me['username']);
        if ($sqErr !== null) {
            $error = $sqErr;
        } else {
            try {
                // Replace the old answers and save the new ones together (all or nothing).
                $mysqli->begin_transaction();
                $del = $mysqli->prepare("DELETE FROM student_security_answers WHERE user_id = ?");
                $del->bind_param('i', $meId);
                $del->execute();
                $del->close();
                sq_save($mysqli, $meId, $sqRows);
                $mysqli->commit();
                $_SESSION['settings_flash'] = ['type' => 'success', 'msg' => 'Security questions updated.'];
                header('Location: settings.php?tab=profile');
                exit;
            } catch (Throwable $e) {
                try { $mysqli->rollback(); } catch (Throwable $ignored) {}
                error_log('security_questions.php failed: ' . $e->getMessage());
                $error = 'The security questions could not be saved. Please try again.';
            }
        }
    }
}
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Security Questions</title>
<script src="admin_appearance.js"></script>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<style>
:root{--bg:#F5F8FC;--card:#fff;--line:#D8E5F4;--text:#102746;--muted:#67819E;--red:#D6455D}
html{--acc:var(--accent,#E8731A);--acc-soft:var(--accent-soft,#FFF3E8)}
*{box-sizing:border-box}
body{margin:0;background:var(--bg);color:var(--text);font:13px/1.5 Inter,system-ui,-apple-system,"Segoe UI",sans-serif}
.wrap{max-width:640px;margin:0 auto;padding:28px 20px 44px}
.back{display:inline-flex;gap:8px;align-items:center;color:var(--muted);text-decoration:none;font-weight:600;font-size:12px;margin-bottom:14px}
.back:hover{color:var(--acc)}
.card{background:var(--card);border:1px solid var(--line);border-radius:16px;padding:24px;box-shadow:0 3px 14px rgba(30,82,144,.08)}
h1{margin:0 0 4px;font-size:18px}
.sub{margin:0 0 18px;color:var(--muted);font-size:12.5px}
.alert{display:flex;gap:10px;align-items:flex-start;padding:11px 14px;margin-bottom:16px;border-radius:10px;background:#FFF0F2;border:1px solid #F1B7C0;color:#A62A42;font-weight:600}
.field{display:flex;flex-direction:column;gap:6px;margin-bottom:14px}
label{font-size:10.5px;font-weight:700;letter-spacing:.06em;text-transform:uppercase;color:var(--muted)}
input[type=text],input[type=password],select{width:100%;height:42px;padding:0 13px;border:1px solid #B9CDE5;border-radius:9px;background:#fff;color:var(--text);font:500 13px Inter,system-ui,sans-serif}
select{appearance:none;-webkit-appearance:none;cursor:pointer;padding-right:34px;background:#fff url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='8' viewBox='0 0 12 8'%3E%3Cpath d='M1 1l5 5 5-5' fill='none' stroke='%2367819E' stroke-width='2' stroke-linecap='round'/%3E%3C/svg%3E") no-repeat right 12px center}
input:focus,select:focus{outline:0;border-color:var(--acc);box-shadow:0 0 0 3px color-mix(in srgb,var(--acc) 22%,transparent)}
select:invalid{color:var(--acc);font-weight:700}
select option{color:var(--text);font-weight:500}
select option[value=""]{color:var(--acc);font-weight:700;background:var(--acc-soft)}
select option:checked{background:var(--acc-soft) linear-gradient(var(--acc-soft),var(--acc-soft));color:var(--acc);font-weight:700}
select option:disabled{color:#A9B8C9}

.chosen{font-size:12.5px;color:var(--text);padding:0 2px;min-height:0}
.item{padding:14px;margin-bottom:12px;border:1px solid var(--line);border-radius:12px;background:#FAFCFF}
.item .field:last-child{margin-bottom:0}
.item h3{display:flex;align-items:center;gap:10px;margin:0 0 12px;font-size:13px}
.num{flex:0 0 auto;width:26px;height:26px;border-radius:50%;display:grid;place-items:center;background:var(--acc-soft);color:var(--acc);font-weight:800;font-size:12.5px}
.confirm-box{padding:18px;margin-bottom:6px;border:2px solid var(--acc);border-radius:14px;background:var(--acc-soft);box-shadow:0 0 0 4px color-mix(in srgb,var(--acc) 12%,transparent)}
.confirm-head{display:flex;gap:12px;align-items:center;margin-bottom:14px}
.confirm-icon{flex:0 0 40px;height:40px;border-radius:50%;display:grid;place-items:center;background:var(--acc);color:#fff;font-size:16px}
.confirm-title{font-size:15px;font-weight:800;color:var(--text)}
.confirm-sub{font-size:12px;color:#5C6B8A;margin-top:1px}
.confirm-label{display:block;margin-bottom:6px;font-size:12px;font-weight:800;letter-spacing:.04em;text-transform:uppercase;color:var(--text)}
.confirm-label .req{color:var(--red)}
.pw-wrap{position:relative}
.pw-wrap input{height:48px;padding-right:46px;background:#fff;border:1.5px solid var(--acc);font-size:14px}
.pw-eye{position:absolute;right:8px;top:50%;transform:translateY(-50%);border:0;background:none;color:var(--muted);cursor:pointer;padding:8px;line-height:1}
.pw-eye:hover{color:var(--acc)}
.sep{height:1px;background:var(--line);margin:18px 0}
.note{font-size:11.5px;color:var(--muted);margin:0 0 14px}
.actions{display:flex;gap:10px;justify-content:flex-end;margin-top:18px}
.btn{display:inline-flex;align-items:center;gap:8px;border:1px solid var(--line);border-radius:9px;padding:10px 18px;font:700 12.5px Inter,system-ui,sans-serif;color:#49647F;background:#fff;cursor:pointer;text-decoration:none}
.btn.primary{background:var(--acc);border-color:var(--acc);color:#fff}
.btn:hover{filter:brightness(.97)}
.btn.primary:hover{filter:brightness(1.08)}
</style>
</head>
<body>
<div class="wrap">
  <a class="back" href="settings.php?tab=profile"><i class="fa-solid fa-arrow-left"></i> Back to Profile &amp; Security</a>
  <div class="card">
    <h1><?= $alreadySet ? 'Update Security Questions' : 'Set Security Questions' ?></h1>
    <p class="sub">These questions are used to recover your account if you forget your password. Choose 3 different questions.</p>

    <?php if ($error !== ''): ?><div class="alert" role="alert"><i class="fa-solid fa-circle-exclamation"></i><span><?= sqh($error) ?></span></div><?php endif; ?>

    <form method="post" action="security_questions.php" autocomplete="off" id="sqForm">
      <input type="hidden" name="csrf_token" value="<?= sqh($csrf) ?>">

      <div class="confirm-box">
        <div class="confirm-head">
          <span class="confirm-icon"><i class="fa-solid fa-lock"></i></span>
          <div>
            <div class="confirm-title">Confirm it&rsquo;s you first</div>
            <div class="confirm-sub">Enter your current password to continue. It is required to save any changes.</div>
          </div>
        </div>
        <label for="current_password" class="confirm-label">Current password <span class="req">*</span></label>
        <div class="pw-wrap">
          <input type="password" id="current_password" name="current_password" required autofocus autocomplete="current-password" placeholder="Enter your current password">
          <button type="button" class="pw-eye" id="pwEye" aria-label="Show password" title="Show password"><i class="fa-solid fa-eye-slash"></i></button>
        </div>
      </div>

      <div class="sep"></div>
      <p class="note"><?= $alreadySet ? 'Saving replaces your current questions and answers. For your security, your existing answers are never shown.' : 'Pick a question and give an answer only you would know.' ?></p>

      <?php for ($n = 1; $n <= 3; $n++): $selQ = (string)($_POST['sq_question'][$n] ?? ''); ?>
      <div class="item">
        <h3><span class="num"><?= $n ?></span></h3>
        <div class="field">
          <select name="sq_question[<?= $n ?>]" id="sq_q<?= $n ?>" required>
            <option value="" <?= $selQ === '' ? 'selected' : '' ?>>Choose a question</option>
            <?php foreach ($allQuestions as $k => $q): ?>
            <option value="<?= sqh($k) ?>" <?= $selQ === (string)$k ? 'selected' : '' ?>><?= sqh($q) ?></option>
            <?php endforeach; ?>
          </select>
          <div class="chosen" id="sq_chosen<?= $n ?>" aria-live="polite"></div>
        </div>
        <div class="field">
          <input type="text" name="sq_answer[<?= $n ?>]" id="sq_a<?= $n ?>" maxlength="100" placeholder="Your answer" autocomplete="off" required>
        </div>
      </div>
      <?php endfor; ?>

      <div class="actions">
        <a class="btn" href="settings.php?tab=profile">Cancel</a>
        <button class="btn primary" type="submit"><i class="fa-solid fa-floppy-disk"></i> Save security questions</button>
      </div>
    </form>
  </div>
</div>
<script>
// Keep the three dropdowns from repeating and show the full chosen question.
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
})();
(function(){
  var b=document.getElementById('pwEye'), i=document.getElementById('current_password');
  if(!b||!i) return;
  b.addEventListener('click',function(){
    var show=i.type==='password'; i.type=show?'text':'password';
    b.innerHTML='<i class="fa-solid '+(show?'fa-eye':'fa-eye-slash')+'"></i>';
    b.setAttribute('aria-label',show?'Hide password':'Show password');
  });
})();
</script>
</body>
</html>
