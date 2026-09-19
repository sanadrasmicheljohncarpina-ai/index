<?php
session_start();
require_once 'db.php';
require_once dirname(__DIR__) . '/shared/system_settings_service.php';

if (empty($_SESSION['user_id']) || !in_array($_SESSION['role'] ?? '', ['admin','superadmin'], true)) {
    http_response_code(403);
    exit('Unauthorized.');
}

// Keep the database-backed evaluation period in sync with the configured schedule.
ss_sync_from_database($mysqli);

$sys = ss_raw($mysqli);
$saveMsg = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'save_system_settings') {
    $acad_year = trim($_POST['acad_year'] ?? '');
    $acad_structure = $_POST['acad_structure'] ?? 'college';
    $terms = ss_structure_terms();
    if (!isset($terms[$acad_structure])) $acad_structure = 'college';
    $acad_term = $_POST['acad_term'] ?? $terms[$acad_structure][0];
    if (!in_array($acad_term, $terms[$acad_structure], true)) $acad_term = $terms[$acad_structure][0];

    $control_mode = $_POST['control_mode'] ?? 'schedule';
    if (!in_array($control_mode, ['schedule','open','closed'], true)) $control_mode = 'schedule';
    $auto_schedule = isset($_POST['auto_schedule']) ? 1 : 0;
    $maintenance = isset($_POST['maintenance']) ? 1 : 0;
    $eval_start = trim($_POST['eval_start'] ?? '');
    $eval_end = trim($_POST['eval_end'] ?? '');

    $parsedStart = ss_parse_datetime($eval_start);
    $parsedEnd   = ss_parse_datetime($eval_end);
    if (($eval_start !== '' || $eval_end !== '') && (!$parsedStart || !$parsedEnd || $parsedEnd <= $parsedStart)) {
        $saveMsg = 'Invalid evaluation schedule. The closing time must be after the opening time.';
    }

    $ruleKeys = ['rule_only_during_period','rule_edit_after_submit','rule_one_submission','rule_require_all','rule_auto_lock','rule_countdown','rule_prevent_late'];
    if ($saveMsg === '') {
    $new = [
        'acad_year'=>$acad_year,
        'acad_structure'=>$acad_structure,
        'acad_term'=>$acad_term,
        'auto_schedule'=>$auto_schedule,
        'control_mode'=>$control_mode,
        'eval_start'=>$eval_start,
        'eval_end'=>$eval_end,
        'maintenance'=>$maintenance,
    ];
    foreach ($ruleKeys as $k) $new[$k] = isset($_POST[$k]) ? 1 : 0;
    foreach ($new as $k=>$v) {
        $stmt=$mysqli->prepare("INSERT INTO system_settings(setting_key,setting_value) VALUES(?,?) ON DUPLICATE KEY UPDATE setting_value=VALUES(setting_value)");
        $sv=(string)$v; $stmt->bind_param('ss',$k,$sv); $stmt->execute(); $stmt->close();
    }
    $sys = ss_raw($mysqli);
    ss_sync_evaluation_period($mysqli, $sys);
    $saveMsg = 'System & Period Settings saved.';
    }
}

function ss_h($v){ return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1.0" />
<title>System & Period Settings</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=IBM+Plex+Mono:wght@400;500&display=swap" rel="stylesheet">
<style>
  :root{
    --bg:          #0A1628;
    --surface:     #FFFFFF;
    --surface-2:   #F8FAFC;
    --surface-3:   #F4F8FF;
    --border:      rgba(30,82,144,.13);
    --border-strong: rgba(15,23,42,.18);
    --text:        #0B1F3A;
    --text-muted:  #8291B3;
    --text-faint:  #5C6B8A;
    --blue:        #3B6FE0;
    --blue-hover:  #4C7CEA;
    --blue-soft:   rgba(59,111,224,0.16);
    --green:       #34D399;
    --green-soft:  rgba(52,211,153,0.14);
    --red:         #E6788A;
    --red-soft:    rgba(248,113,113,0.14);
    --amber:       #FBBF24;
    --amber-soft:  rgba(251,191,36,0.14);
    --radius:      14px;
    font-size: 16px;
  }

  *{ box-sizing: border-box; }
  html,body{ margin:0; padding:0; }

  body{
    background: var(--bg);
    color: var(--text);
    font-family: 'Inter', sans-serif;
    -webkit-font-smoothing: antialiased;
    min-height: 100vh;
  }

  .shell{
    max-width: 1000px;
    margin: 0 auto;
    padding: 40px 28px 90px;
  }

  /* ---------- header ---------- */
  .top{
    display:flex;
    align-items:flex-start;
    justify-content:space-between;
    gap: 20px;
    margin-bottom: 22px;
  }
  .page-title{
    font-size: 28px;
    font-weight: 800;
    letter-spacing: -0.01em;
    margin: 0 0 6px;
  }
  .page-sub{
    color: var(--text-muted);
    font-size: 14px;
    margin: 0;
  }
  .btn{
    font-family:'Inter', sans-serif;
    font-size: 14px;
    font-weight: 600;
    padding: 11px 18px;
    border-radius: 10px;
    border: 1px solid var(--border);
    background: var(--surface-2);
    color: var(--text);
    cursor: pointer;
    transition: background .15s ease, border-color .15s ease, transform .1s ease;
    display:inline-flex; align-items:center; gap:8px;
    white-space: nowrap;
  }
  .btn:hover{ border-color: var(--border-strong); }
  .btn:active{ transform: translateY(1px); }
  .btn-primary{
    background: var(--blue);
    border-color: var(--blue);
    color: #fff;
  }
  .btn-primary:hover{ background: var(--blue-hover); }

  /* ---------- tabs ---------- */
  .tabs{
    display: inline-flex;
    gap: 4px;
    background: var(--surface-2);
    border: 1px solid var(--border);
    border-radius: 12px;
    padding: 4px;
    margin-bottom: 26px;
  }
  .tab{
    font-size: 13.5px;
    font-weight: 600;
    color: var(--text-muted);
    padding: 9px 16px;
    border-radius: 8px;
    cursor: default;
    user-select: none;
    transition: background .15s ease, color .15s ease;
  }
  .tab.active{
    color: #fff;
    background: var(--blue);
  }

  /* ---------- stat row (snapshot) ---------- */
  .stat-row{
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 16px;
    margin-bottom: 26px;
  }
  @media (max-width: 720px){ .stat-row{ grid-template-columns: 1fr; } }
  .stat-card{
    background: var(--surface);
    border: 1px solid var(--border);
    border-radius: var(--radius);
    padding: 20px 22px;
  }
  .stat-label{
    font-size: 11.5px;
    font-weight: 600;
    letter-spacing: 0.07em;
    text-transform: uppercase;
    color: var(--text-muted);
    margin-bottom: 10px;
  }
  .stat-value{
    font-size: 26px;
    font-weight: 700;
    color: var(--text);
    line-height: 1.15;
  }
  .stat-value.small{ font-size: 18px; }
  .stat-value.green{ color: var(--green); }
  .stat-value.red{ color: var(--red); }
  .stat-value.amber{ color: var(--amber); }
  .stat-note{
    font-family: 'IBM Plex Mono', monospace;
    font-size: 11.5px;
    color: var(--text-faint);
    margin-top: 8px;
  }

  /* ---------- sections ---------- */
  .section{
    background: var(--surface);
    border: 1px solid var(--border);
    border-radius: var(--radius);
    margin-bottom: 18px;
    overflow: hidden;
  }
  .section-head{
    display:flex; align-items:baseline; justify-content:space-between;
    gap: 12px;
    padding: 18px 22px;
    background: var(--surface-2);
    border-bottom: 1px solid var(--border);
  }
  .section-eyebrow{
    font-size: 11.5px;
    font-weight: 700;
    letter-spacing: 0.08em;
    color: #9DB4E8;
    text-transform: uppercase;
  }
  .section-hint{
    font-size: 12.5px;
    color: var(--text-faint);
  }
  .section-body{
    padding: 20px 22px 22px;
    display: grid;
    gap: 16px;
  }

  .field-row{
    display:grid;
    grid-template-columns: 230px 1fr;
    align-items: center;
    gap: 14px;
  }
  @media (max-width: 620px){
    .field-row{ grid-template-columns: 1fr; }
  }
  .field-label{
    font-size: 14px;
    font-weight: 600;
    color: var(--text);
  }
  .field-help{
    display:block;
    font-size: 12px;
    font-weight: 400;
    color: var(--text-faint);
    margin-top: 3px;
  }

  input[type="text"], input[type="datetime-local"], select{
    width: 100%;
    font-family: 'Inter', sans-serif;
    font-size: 14px;
    color: var(--text);
    background: var(--surface-2);
    border: 1px solid var(--border);
    border-radius: 9px;
    padding: 10px 12px;
    transition: border-color .15s ease, box-shadow .15s ease;
  }
  input::placeholder{ color: var(--text-faint); }
  input[type="datetime-local"]{ color-scheme: light; }
  input[type="datetime-local"]::-webkit-calendar-picker-indicator{
    opacity: 1;
    cursor: pointer;
    padding: 4px;
    margin-left: 6px;
    border-radius: 6px;
    filter: invert(46%) sepia(56%) saturate(1934%) hue-rotate(196deg) brightness(94%) contrast(93%);
  }
  input[type="datetime-local"]::-webkit-calendar-picker-indicator:hover{
    background: var(--blue-soft);
  }
  input:focus, select:focus{
    outline: none;
    border-color: var(--blue);
    box-shadow: 0 0 0 3px var(--blue-soft);
  }
  select:disabled, input:disabled{
    opacity: 0.5;
    cursor: not-allowed;
  }
  select{ appearance: none; -webkit-appearance:none;
    background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='10' height='6' viewBox='0 0 10 6'%3E%3Cpath d='M1 1l4 4 4-4' stroke='%238291B3' stroke-width='1.5' fill='none' stroke-linecap='round' stroke-linejoin='round'/%3E%3C/svg%3E");
    background-repeat: no-repeat;
    background-position: right 12px center;
    padding-right: 32px;
  }

  /* Visible dropdown arrows for all select fields */
  .select-wrap{
    position:relative;
    width:100%;
  }
  .select-wrap select{
    width:100%;
    appearance:none !important;
    -webkit-appearance:none !important;
    -moz-appearance:none !important;
    padding-right:46px !important;
    background-image:none !important;
  }
  .select-wrap::after{
    content:'';
    position:absolute;
    right:16px;
    top:50%;
    width:9px;
    height:9px;
    border-right:2px solid #294765;
    border-bottom:2px solid #294765;
    transform:translateY(-65%) rotate(45deg);
    pointer-events:none;
  }
  .select-wrap:hover::after{
    border-color:#2563EB;
  }
  .select-wrap select:focus + * + &{}



  /* toggle switch */
  .switch{
    position: relative;
    width: 42px; height: 24px;
    flex: none;
    display:inline-block;
  }
  .switch input{ display:none; }
  .switch .track{
    position:absolute; inset:0;
    background: var(--surface-3);
    border: 1px solid var(--border);
    border-radius: 999px;
    transition: background .15s ease;
    cursor:pointer;
  }
  .switch .thumb{
    position:absolute; top:2px; left:2px;
    width:18px; height:18px;
    background:#fff;
    border-radius:50%;
    box-shadow: 0 1px 3px rgba(0,0,0,0.4);
    transition: transform .15s ease;
  }
  .switch input:checked + .track{ background: var(--blue); border-color: var(--blue); }
  .switch input:checked + .track .thumb{ transform: translateX(18px); }

  .toggle-row{
    display:flex; align-items:center; justify-content:space-between;
    gap: 14px;
    padding: 12px 0;
    border-bottom: 1px solid var(--border);
  }
  .toggle-row:last-child{ border-bottom:none; padding-bottom:0; }
  .toggle-copy .field-label{ margin:0; }

  /* radio control mode */
  .control-modes{
    display:grid;
    gap: 8px;
  }
  .control-option{
    display:flex; align-items:flex-start; gap: 10px;
    border: 1px solid var(--border);
    background: var(--surface-2);
    border-radius: 10px;
    padding: 12px 14px;
    cursor: pointer;
    transition: border-color .15s ease, background .15s ease;
  }
  .control-option:hover{ border-color: var(--border-strong); }
  .control-option input{ margin-top: 3px; accent-color: var(--blue); }
  .control-option.selected{ border-color: var(--blue); background: var(--blue-soft); }
  .control-option .co-title{ font-size: 14px; font-weight: 600; color: var(--text); }
  .control-option .co-desc{ font-size: 12px; color: var(--text-faint); margin-top:1px; }

  .maint-banner{
    display:none;
    align-items:center; gap:10px;
    background: var(--amber-soft);
    color: var(--amber);
    border: 1px solid rgba(251,191,36,0.35);
    border-radius: 10px;
    padding: 12px 16px;
    font-size: 13px;
    margin-bottom: 18px;
  }
  .maint-banner.show{ display:flex; }

  /* footer actions */
  .actions{
    display:flex; justify-content:flex-end; gap: 10px;
    padding-top: 8px;
  }

  .toast{
    position: fixed;
    bottom: 24px; left: 50%;
    transform: translateX(-50%) translateY(20px);
    background: var(--surface-3);
    border: 1px solid var(--border-strong);
    color: var(--text);
    font-size: 13px;
    padding: 12px 20px;
    border-radius: 10px;
    box-shadow: 0 8px 24px rgba(0,0,0,0.4);
    opacity: 0;
    pointer-events: none;
    transition: opacity .2s ease, transform .2s ease;
    display:flex; align-items:center; gap:8px;
  }
  .toast.show{ opacity: 1; transform: translateX(-50%) translateY(0); }
  .toast .dot{ width:7px; height:7px; border-radius:50%; background: var(--green); }

  ::-webkit-scrollbar{ width: 10px; }
  ::-webkit-scrollbar-thumb{ background: var(--surface-3); border-radius: 999px; }
  ::-webkit-scrollbar-track{ background: transparent; }

/* Admin Module light design system — matches the dashboard */
:root{
  --page-bg:#FFFFFF; --card-bg:#FFFFFF; --card-border:#D8E5F4;
  --inner:#F7FAFF; --text-dark:#0B1F3A; --text-dim:#67819E;
  --light:#0B1F3A; --muted:#67819E; --dark:#FFFFFF; --mid:#FFFFFF;
  --border:#D8E5F4; --accent:#2563EB; --blue:#2563EB;
  --gold:#C77A08; --gold-h:#D69612; --teal:#0E7490; --violet:#4968C8;
  --danger:#D6455D; --success:#0F9F6E; --radius:12px;
  --card-shadow:0 2px 4px rgba(30,82,144,.06),0 6px 16px rgba(30,82,144,.08);
}
html{background:#FFFFFF;color-scheme:light;}
body{background:#FFFFFF !important;color:#0B1F3A !important;}
a{color:inherit;}
.page-header h1,.page-title,.et-title,.section-title{color:#0B1F3A !important;}
.page-header p,.page-sub,.et-sub,.et-updated,.muted,.hint{color:#67819E !important;}
input,select,textarea{background:#fff !important;color:#0B1F3A !important;border-color:#B9CDE5 !important;}
button{font-family:inherit;}
.table-wrap,.content-panel,.create-panel,.period-card,.stat-card,.sector-card,.person-row,
.sum-card,.standing-panel,.eval-card,.eval-banner,.info-banner,.section,.shell .section,
.history-card,.gl-card,.amber-card,.green-card,.red-card{
  background:#fff !important;border-color:#D8E5F4 !important;box-shadow:0 2px 4px rgba(30,82,144,.05),0 6px 16px rgba(30,82,144,.06) !important;
}
.sector-tabs,.eval-switcher,.tabs,.level-tabs,.status-tabs{
  background:#fff !important;border-color:#D8E5F4 !important;box-shadow:0 2px 4px rgba(30,82,144,.05) !important;
}
.sector-tab,.eval-tab,.tab,.level-tab,.status-tab{color:#67819E !important;}
.sector-tab:hover,.eval-tab:hover,.tab:hover,.level-tab:hover,.status-tab:hover{color:#0B1F3A !important;background:#F7FAFF !important;}
thead tr{background:#F8FAFC !important;}
tbody tr:hover{background:#F8FAFC !important;}
.btn-cancel,.btn-icon,.btn-back{background:#fff !important;color:#0B1F3A !important;border-color:#B9CDE5 !important;}
.empty-state,.empty-cta{color:#67819E !important;}
::-webkit-scrollbar-track{background:#fff;}
::-webkit-scrollbar-thumb{background:#B9CDE5;border:2px solid #fff;}

body{background:#fff !important;}
.shell{max-width:none !important;margin:0 !important;padding:28px !important;}
.section{background:#fff !important;}
.maint-banner{background:#FFFBEB !important;color:#92400E !important;border-color:#FDE68A !important;}
.control-option,.toggle-row{background:#F8FAFC !important;border-color:#D8E5F4 !important;}


/* ── SHARP LIGHT ADMIN UI ── */
html { background:#F8FAFC; }
body {
  color:#0B1F3A !important;
  background:#F8FAFC !important;
  -webkit-font-smoothing:antialiased;
  text-rendering:optimizeLegibility;
}
h1,h2,h3,h4,h5,h6 { color:#0B1F3A; letter-spacing:-.01em; }
p, .subtitle, .description, .helper, .muted, small { color:#67819E; }
label, th { color:#294765; font-weight:600; }
td { color:#0B1F3A; }
input, select, textarea {
  color:#0B1F3A;
  background:#FFFFFF;
  border-color:#B9CDE5;
}
input::placeholder, textarea::placeholder { color:#91A6BE; }
.card, .panel, .section, .table-card, .content-card {
  border-color:#B9CDE5;
  box-shadow:0 4px 14px rgba(30,82,144,.09);
}
button, .btn { font-weight:700; }
a { color:inherit; }
</style>
    <link rel="stylesheet" href="admin_ui_theme.css">
    <link rel="stylesheet" href="admin_compact_ui.css">
<style id="pbi-feature-scrollbar">

/* PBI FEATURE SCROLLBAR — consistent with the compact page scrollbar */
html, body {
  scrollbar-width: thin !important;
  scrollbar-color: #888 transparent !important;
}
html::-webkit-scrollbar, body::-webkit-scrollbar,
.feature-compact ::-webkit-scrollbar {
  width: 10px !important;
  height: 10px !important;
}
html::-webkit-scrollbar-track, body::-webkit-scrollbar-track,
.feature-compact ::-webkit-scrollbar-track {
  background: transparent !important;
}
html::-webkit-scrollbar-thumb, body::-webkit-scrollbar-thumb,
.feature-compact ::-webkit-scrollbar-thumb {
  background: #888 !important;
  border-radius: 999px !important;
  border: 2px solid transparent !important;
  background-clip: padding-box !important;
}
html::-webkit-scrollbar-thumb:hover, body::-webkit-scrollbar-thumb:hover,
.feature-compact ::-webkit-scrollbar-thumb:hover {
  background: #777 !important;
  background-clip: padding-box !important;
}
html::-webkit-scrollbar-button, body::-webkit-scrollbar-button,
.feature-compact ::-webkit-scrollbar-button {
  display: block !important;
  width: 10px !important;
  height: 10px !important;
  background-color: transparent !important;
}
/* Small native-looking arrow hints on classic scrollbars */
html::-webkit-scrollbar-button:single-button:vertical:decrement,
body::-webkit-scrollbar-button:single-button:vertical:decrement,
.feature-compact ::-webkit-scrollbar-button:single-button:vertical:decrement {
  background:
    linear-gradient(135deg, transparent 50%, #777 50%) 3px 5px/5px 5px no-repeat !important;
}
html::-webkit-scrollbar-button:single-button:vertical:increment,
body::-webkit-scrollbar-button:single-button:vertical:increment,
.feature-compact ::-webkit-scrollbar-button:single-button:vertical:increment {
  background:
    linear-gradient(315deg, transparent 50%, #777 50%) 3px 0/5px 5px no-repeat !important;
}
html::-webkit-scrollbar-button:single-button:horizontal:decrement,
body::-webkit-scrollbar-button:single-button:horizontal:decrement,
.feature-compact ::-webkit-scrollbar-button:single-button:horizontal:decrement {
  background:
    linear-gradient(45deg, transparent 50%, #777 50%) 5px 3px/5px 5px no-repeat !important;
}
html::-webkit-scrollbar-button:single-button:horizontal:increment,
body::-webkit-scrollbar-button:single-button:horizontal:increment,
.feature-compact ::-webkit-scrollbar-button:single-button:horizontal:increment {
  background:
    linear-gradient(225deg, transparent 50%, #777 50%) 0 3px/5px 5px no-repeat !important;
}

</style>
<link rel="stylesheet" href="admin_appearance.css">
<script src="admin_appearance.js"></script>
</head>
<body class="feature-compact">
<div class="shell">

  <?php if ($saveMsg): ?><div class="maint-banner show" style="margin-bottom:14px"><strong><?=ss_h($saveMsg)?></strong></div><?php endif; ?>

  <div class="top">
    <div>
      <p class="page-title">System &amp; Period Settings</p>
      <p class="page-sub">Configure academic terms and evaluation windows for College/University, JHS, and SHS. Evaluation scheduling timezone: <b>Asia/Manila</b>.</p>
    </div>
    <button class="btn btn-primary" id="saveBtnTop" style="display:none">Save System Settings</button>
  </div>

  <!-- Live snapshot, styled like the stat cards -->
  <div class="stat-row">
    <div class="stat-card">
      <div class="stat-label">Academic Period</div>
      <div class="stat-value small" id="statAcademic"><?=ss_h($sys['acad_year'] ?: '')?> · <?=ss_h(ss_structure_labels()[$sys['acad_structure']] ?? 'College')?></div>
      <div class="stat-note" id="statTerm"><?=ss_h($sys['acad_term'] ?? '1st Semester')?></div>
    </div>
    <div class="stat-card">
      <div class="stat-label">Evaluation Status</div>
      <div class="stat-value" id="statStatus">—</div>
      <div class="stat-note" id="statWindow">—</div>
    </div>
    <div class="stat-card">
      <div class="stat-label">Control Mode</div>
      <div class="stat-value small" id="statMode">Follow Schedule</div>
    </div>
  </div>

  <div class="maint-banner" id="maintBanner">
    <strong>Maintenance mode is on.</strong>&nbsp;Only administrators can access the system right now.
  </div>

  <!-- ACADEMIC CONFIGURATION -->
  <div class="section">
    <div class="section-head">
      <span class="section-eyebrow">Academic Configuration</span>
      <span class="section-hint">Defines the current period, not the evaluation window</span>
    </div>
    <div class="section-body">
      <div class="field-row">
        <label class="field-label" for="academicYear">Academic Year</label>
        <input type="text" id="academicYear" value="<?=ss_h($sys['acad_year'] ?? '')?>" />
      </div>
      <div class="field-row">
        <label class="field-label" for="academicStructure">Academic Structure</label>
        <div class="select-wrap"><select id="academicStructure">
          <option value="college" <?=($sys['acad_structure']==='college'?'selected':'')?>>College / University</option>
          <option value="jhs" <?=($sys['acad_structure']==='jhs'?'selected':'')?>>Junior High School</option>
          <option value="shs" <?=($sys['acad_structure']==='shs'?'selected':'')?>>Senior High School</option>
        </select></div>
      </div>
      <div class="field-row">
        <label class="field-label" for="academicTerm">
          Academic Term
          <span class="field-help">Choices update automatically with Academic Structure</span>
        </label>
        <div class="select-wrap"><select id="academicTerm"></select></div>
      </div>
      <div class="field-row">
        <div class="field-label">Period meaning</div>
        <div class="field-help">The selected Academic Term controls the <b>Dean evaluation</b>. The same Academic Year is used for the <b>Principal evaluation</b>, whose period is always <b>School Year</b>.</div>
      </div>
    </div>
  </div>

  <!-- EVALUATION SCHEDULE -->
  <div class="section">
    <div class="section-head">
      <span class="section-eyebrow">Evaluation Schedule</span>
      <span class="section-hint">Independent from the academic period above</span>
    </div>
    <div class="section-body">
      <div class="field-row">
        <label class="field-label" for="evalOpens">Evaluation Opens</label>
        <input type="datetime-local" id="evalOpens" value="<?=ss_h($sys['eval_start'] ?? '')?>" />
      </div>
      <div class="field-row">
        <label class="field-label" for="evalCloses">Evaluation Closes</label>
        <input type="datetime-local" id="evalCloses" value="<?=ss_h($sys['eval_end'] ?? '')?>" />
      </div>
      <div class="field-row">
        <div class="field-label">Scheduling Timezone</div>
        <div class="field-help"><b>Asia/Manila</b> — all scheduled opening, closing, and submission-boundary checks use this timezone.</div>
      </div>
      <div class="toggle-row">
        <div class="toggle-copy">
          <div class="field-label">Automatic Schedule</div>
          <span class="field-help">Open and close submissions automatically at the times above</span>
        </div>
        <label class="switch">
          <input type="checkbox" id="autoSchedule" <?=!empty($sys['auto_schedule'])?'checked':''?> >
          <span class="track"><span class="thumb"></span></span>
        </label>
      </div>
    </div>
  </div>

  <!-- EVALUATION ACCESS -->
  <div class="section">
    <div class="section-head">
      <span class="section-eyebrow">Evaluation Access</span>
      <span class="section-hint">Current status and manual override</span>
    </div>
    <div class="section-body">
      <div class="control-modes" id="controlModes">
        <label class="control-option" data-mode="schedule">
          <input type="radio" name="controlMode" value="schedule" <?=($sys['control_mode']??'schedule')==='schedule'?'checked':''?> >
          <div>
            <div class="co-title">Follow Schedule</div>
            <div class="co-desc">Status is determined automatically from the evaluation window</div>
          </div>
        </label>
        <label class="control-option" data-mode="open">
          <input type="radio" name="controlMode" value="open" <?=($sys['control_mode']??'schedule')==='open'?'checked':''?> >
          <div>
            <div class="co-title">Force Open</div>
            <div class="co-desc">Allow submissions regardless of the scheduled period</div>
          </div>
        </label>
        <label class="control-option" data-mode="closed">
          <input type="radio" name="controlMode" value="closed" <?=($sys['control_mode']??'schedule')==='closed'?'checked':''?> >
          <div>
            <div class="co-title">Force Closed</div>
            <div class="co-desc">Block submissions regardless of the scheduled period</div>
          </div>
        </label>
      </div>
    </div>
  </div>

  <!-- EVALUATION RULES -->
  <div class="section">
    <div class="section-head">
      <span class="section-eyebrow">Evaluation Rules</span>
      <span class="section-hint">Submission and locking behavior</span>
    </div>
    <div class="section-body" id="rulesBody" style="gap:0;">
      <!-- populated by JS -->
    </div>
  </div>

  <!-- MAINTENANCE -->
  <div class="section">
    <div class="section-head">
      <span class="section-eyebrow">Maintenance</span>
      <span class="section-hint">System-wide, separate from evaluation access</span>
    </div>
    <div class="section-body">
      <div class="toggle-row" style="border-bottom:none;">
        <div class="toggle-copy">
          <div class="field-label">Maintenance Mode</div>
          <span class="field-help">Locks access for non-administrators; administrators keep access</span>
        </div>
        <label class="switch">
          <input type="checkbox" id="maintenanceMode" <?=!empty($sys['maintenance'])?'checked':''?> >
          <span class="track"><span class="thumb"></span></span>
        </label>
      </div>
    </div>
  </div>

  <div class="actions">
    <button class="btn" id="cancelBtn">Cancel</button>
    <button class="btn btn-primary" id="saveBtn">Save System Settings</button>
  </div>

</div>

<div class="toast" id="toast"><span class="dot"></span><span id="toastText">Saved</span></div>

<script>
  const TERM_OPTIONS = {
    college: ['1st Semester', '2nd Semester', 'Summer'],
    jhs: ['School Year'],
    shs: ['School Year']
  };
  const STRUCTURE_LABEL = {
    college: 'College / University',
    jhs: 'Junior High School',
    shs: 'Senior High School'
  };

  const RULES = [
    { id: 'onlyDuringPeriod', label: 'Allow submissions only during the evaluation period', help: 'Blocks submissions outside the configured window', def: true },
    { id: 'editAfterSubmit',  label: 'Allow students to edit after submission', help: 'Off by default to protect evaluation integrity', def: false },
    { id: 'oneSubmission',    label: 'Allow only one submission', help: 'Prevents duplicate or repeated submissions', def: true },
    { id: 'requireAll',       label: 'Require all required evaluations before submission', help: 'Blocks partial submissions', def: true },
    { id: 'autoLock',         label: 'Automatically lock an evaluation after submission', help: 'Prevents further changes once submitted', def: true },
    { id: 'countdown',        label: 'Show a countdown before the evaluation closes', help: 'Warns students the window is ending', def: true },
    { id: 'preventLate',      label: 'Prevent submissions after the closing time', help: 'Hard stop once the window closes', def: true },
  ];

  const els = {
    structure: document.getElementById('academicStructure'),
    term: document.getElementById('academicTerm'),
    year: document.getElementById('academicYear'),
    opens: document.getElementById('evalOpens'),
    closes: document.getElementById('evalCloses'),
    auto: document.getElementById('autoSchedule'),
    maintenance: document.getElementById('maintenanceMode'),
    maintBanner: document.getElementById('maintBanner'),
    statAcademic: document.getElementById('statAcademic'),
    statTerm: document.getElementById('statTerm'),
    statStatus: document.getElementById('statStatus'),
    statWindow: document.getElementById('statWindow'),
    statMode: document.getElementById('statMode'),
    rulesBody: document.getElementById('rulesBody'),
    controlModes: document.getElementById('controlModes'),
    toast: document.getElementById('toast'),
    toastText: document.getElementById('toastText'),
  };

  const MODE_LABEL = { schedule: 'Follow Schedule', open: 'Force Open', closed: 'Force Closed' };

  function populateTerms(selected) {
    const structure = els.structure.value;
    const options = TERM_OPTIONS[structure];
    els.term.innerHTML = '';
    options.forEach(opt => {
      const o = document.createElement('option');
      o.value = opt; o.textContent = opt;
      els.term.appendChild(o);
    });
    if (selected && options.includes(selected)) {
      els.term.value = selected;
    }
    els.term.disabled = options.length === 1;
  }

  const SCHEDULE_TIMEZONE = 'Asia/Manila';
  // datetime-local has no timezone information. Interpret every configured value
  // explicitly as Manila time so an administrator in another timezone sees the
  // same schedule status as the server.
  function parseManilaLocal(value) {
    if (!value) return NaN;
    const m = String(value).match(/^(\d{4})-(\d{2})-(\d{2})T(\d{2}):(\d{2})$/);
    if (!m) return NaN;
    const y = Number(m[1]), mo = Number(m[2]), d = Number(m[3]);
    const h = Number(m[4]), mi = Number(m[5]);
    // Asia/Manila is UTC+08:00 and has no DST transition.
    return Date.UTC(y, mo - 1, d, h - 8, mi);
  }

  function formatDateTime(value) {
    const ts = parseManilaLocal(value);
    if (!Number.isFinite(ts)) return '—';
    return new Intl.DateTimeFormat('en-US', {
      timeZone: SCHEDULE_TIMEZONE,
      month: 'short',
      day: 'numeric',
      year: 'numeric',
      hour: 'numeric',
      minute: '2-digit',
      hour12: true
    }).format(new Date(ts));
  }

  function getControlMode() {
    const checked = els.controlModes.querySelector('input[name="controlMode"]:checked');
    return checked ? checked.value : 'schedule';
  }

  function refreshControlOptionStyles() {
    els.controlModes.querySelectorAll('.control-option').forEach(opt => {
      const input = opt.querySelector('input');
      opt.classList.toggle('selected', input.checked);
    });
  }

  function computeStatus() {
    if (els.maintenance.checked) return { cls: '', color: 'text-muted', label: 'MAINTENANCE' };
    const mode = getControlMode();
    if (mode === 'open') return { color: 'amber', label: 'FORCED OPEN' };
    if (mode === 'closed') return { color: 'amber', label: 'FORCED CLOSED' };
    if (!els.auto.checked) return { color: '', label: 'CLOSED · MANUAL' };
    const now = Date.now();
    const opens = parseManilaLocal(els.opens.value);
    const closes = parseManilaLocal(els.closes.value);
    if (!Number.isFinite(opens) || !Number.isFinite(closes)) return { color: '', label: 'NOT CONFIGURED' };
    if (closes <= opens) return { color: 'red', label: 'INVALID SCHEDULE' };
    if (now < opens) return { color: '', label: 'UPCOMING' };
    if (now >= closes) return { color: 'red', label: 'CLOSED · ENDED' };
    return { color: 'green', label: 'OPEN' };
  }

  function render() {
    const structureLabel = STRUCTURE_LABEL[els.structure.value];
    els.statAcademic.textContent = `${els.year.value} · ${structureLabel}`;
    els.statTerm.textContent = els.term.value;
    els.statWindow.textContent = `${formatDateTime(els.opens.value)} – ${formatDateTime(els.closes.value)}`;
    els.statMode.textContent = MODE_LABEL[getControlMode()];

    els.maintBanner.classList.toggle('show', els.maintenance.checked);
    refreshControlOptionStyles();

    const status = computeStatus();
    els.statStatus.textContent = status.label;
    els.statStatus.className = 'stat-value' + (status.color ? ' ' + status.color : '');
  }

  els.structure.addEventListener('change', () => { populateTerms(); render(); });
  els.term.addEventListener('change', render);
  els.year.addEventListener('input', render);
  els.opens.addEventListener('input', render);
  els.closes.addEventListener('input', render);
  els.auto.addEventListener('change', render);
  els.maintenance.addEventListener('change', render);
  els.controlModes.addEventListener('change', render);

  // ── UNSAVED CHANGES TRACKING ───────────────────────────────
  // Anything the user actually edits after the form has loaded flips
  // this flag. Reset it once a save succeeds. Exposed on window so the
  // parent Settings shell can ask "is it safe to switch tabs?" before
  // it hides this iframe, and beforeunload below covers a refresh,
  // browser-back, or closing the tab outright.
  let isDirty = false;
  function markDirty(){
    if (isDirty) return;
    isDirty = true;
    try { parent.postMessage({type:'system-settings-dirty', dirty:true}, '*'); } catch(e){}
  }
  function clearDirty(){
    isDirty = false;
    try { parent.postMessage({type:'system-settings-dirty', dirty:false}, '*'); } catch(e){}
  }
  [els.structure, els.term, els.opens, els.closes, els.auto, els.maintenance, els.controlModes]
    .forEach(el => el.addEventListener('change', markDirty));
  [els.year, els.opens, els.closes].forEach(el => el.addEventListener('input', markDirty));
  window.isSystemSettingsDirty = () => isDirty;
  window.addEventListener('beforeunload', (e) => {
    if (!isDirty) return;
    e.preventDefault();
    e.returnValue = '';
  });

  RULES.forEach(rule => {
    const row = document.createElement('div');
    row.className = 'toggle-row';
    row.innerHTML = `
      <div class="toggle-copy">
        <div class="field-label">${rule.label}</div>
        <span class="field-help">${rule.help}</span>
      </div>
      <label class="switch">
        <input type="checkbox" id="rule_${rule.id}" ${rule.def ? 'checked' : ''}>
        <span class="track"><span class="thumb"></span></span>
      </label>
    `;
    els.rulesBody.appendChild(row);
    row.querySelector('input[type="checkbox"]').addEventListener('change', markDirty);
  });

  populateTerms(<?=json_encode($sys['acad_term'] ?? '1st Semester')?>);
  render();
  try { parent.postMessage({type:'system-settings-dirty', dirty:false}, '*'); } catch(e){}

  function showToast(text) {
    els.toastText.textContent = text;
    els.toast.classList.add('show');
    setTimeout(() => els.toast.classList.remove('show'), 2200);
  }
  function saveSystemSettings(){
    const fd = new FormData();
    fd.append('action','save_system_settings');
    fd.append('acad_year', els.year.value.trim());
    fd.append('acad_structure', els.structure.value);
    fd.append('acad_term', els.term.value);
    const openValue = els.opens.value.trim();
    const closeValue = els.closes.value.trim();
    if ((openValue && !closeValue) || (!openValue && closeValue)) {
      showToast('Please set both opening and closing times.');
      return;
    }
    if (openValue && closeValue && parseManilaLocal(closeValue) <= parseManilaLocal(openValue)) {
      showToast('Closing time must be after opening time (Asia/Manila).');
      return;
    }
    fd.append('eval_start', openValue);
    fd.append('eval_end', closeValue);
    fd.append('control_mode', getControlMode());
    if (els.auto.checked) fd.append('auto_schedule','1');
    if (els.maintenance.checked) fd.append('maintenance','1');
    RULES.forEach(rule => { if (document.getElementById('rule_'+rule.id).checked) fd.append(rule.id,'1'); });
    fetch('system_settings.php',{method:'POST',body:fd,credentials:'same-origin'})
      .then(r=>r.ok?r.text():Promise.reject(new Error('save failed')))
      .then(()=>{ clearDirty(); showToast('System settings saved'); })
      .catch(()=>showToast('Unable to save system settings'));
  }
  document.getElementById('saveBtn').addEventListener('click', saveSystemSettings);
  document.getElementById('saveBtnTop').addEventListener('click', saveSystemSettings);
  document.getElementById('cancelBtn').addEventListener('click', () => location.reload());

  setInterval(render, 60000);
</script>
</body>
</html>