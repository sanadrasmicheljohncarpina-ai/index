<?php
// admin/ea_evaluation.php
// Executive Assistant "My Evaluations" landing page.
// The page follows the same group-selection structure used by the faculty
// evaluation view: choose an evaluation group first, then select a person.
// Eligibility and completion logic remain server-side and unchanged.
session_set_cookie_params([
    'lifetime' => 0, 'path' => '/', 'domain' => '',
    'secure' => false, 'httponly' => true, 'samesite' => 'Lax',
]);
session_start();
require_once 'db.php';
require_once dirname(__DIR__) . '/shared/system_settings_service.php';

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

if (!isset($_SESSION['user_id']) ||
    !in_array($_SESSION['role'] ?? '', ['admin', 'superadmin', 'executive_assistant'], true)) {
    header('Location: admin_login.php');
    exit;
}

ss_sync_from_database($mysqli);
$ea_id = (int) $_SESSION['user_id'];

// Keep this page safe on older installations that may still use a legacy ENUM.
try {
    $mysqli->query("ALTER TABLE evaluation_tracker MODIFY eval_type VARCHAR(30) NOT NULL DEFAULT 'student'");
} catch (Throwable $ignore) {}

foreach ([
    "ALTER TABLE evaluation_tracker ADD COLUMN evaluator_id INT UNSIGNED NULL",
    "ALTER TABLE evaluation_tracker ADD COLUMN target_user_id INT UNSIGNED NULL",
    "ALTER TABLE evaluation_tracker ADD COLUMN period_id INT UNSIGNED NULL",
    "ALTER TABLE evaluation_tracker ADD COLUMN status VARCHAR(20) NOT NULL DEFAULT 'submitted'",
] as $ddl) {
    try { $mysqli->query($ddl); } catch (Throwable $ignore) {}
}

$period = $mysqli->query("
    SELECT id, period_label, is_active
    FROM evaluation_periods
    WHERE is_active=1
    ORDER BY id DESC
    LIMIT 1
")->fetch_assoc();
$period_id = (int) ($period['id'] ?? 0);
$is_open = $period_id > 0;
$periodName = trim((string) ($period['period_label'] ?? ''));

// ── ELIGIBLE TARGETS ──────────────────────────────────────────────────
// EA may evaluate the active Principal, active Dean, and Staff members
// without teaching/year-level assignments.
$heads = ['Principal' => [], 'Dean' => []];
$headStmt = $mysqli->prepare("
    SELECT id, full_name, designation, photo, role
    FROM users
    WHERE role IN ('principal', 'dean')
      AND is_active=1
      AND account_status='approved'
    ORDER BY full_name
");
$headStmt->execute();
foreach ($headStmt->get_result()->fetch_all(MYSQLI_ASSOC) as $user) {
    $bucket = $user['role'] === 'principal' ? 'Principal' : 'Dean';
    $heads[$bucket][] = $user;
}
$headStmt->close();

$staffStmt = $mysqli->prepare("
    SELECT u.id, u.full_name, u.designation, u.photo, u.role
    FROM users u
    WHERE u.role='staff'
      AND u.is_active=1
      AND (u.account_status='approved' OR u.source='admin_nologin')
      AND NOT EXISTS (SELECT 1 FROM teaching_assignments ta WHERE ta.user_id=u.id)
      AND NOT EXISTS (SELECT 1 FROM user_year_levels yl WHERE yl.user_id=u.id)
    ORDER BY u.full_name
");
$staffStmt->execute();
$staff = $staffStmt->get_result()->fetch_all(MYSQLI_ASSOC);
$staffStmt->close();

$categories = [
    'Principal' => $heads['Principal'],
    'Dean'      => $heads['Dean'],
    'Staff'     => $staff,
];

$groupMeta = [
    'Principal' => [
        'icon' => 'fa-user-tie',
        'class' => 'group-principal',
        'description' => 'Evaluate the Principal assigned to your evaluation scope.',
    ],
    'Dean' => [
        'icon' => 'fa-graduation-cap',
        'class' => 'group-dean',
        'description' => 'Evaluate the Dean responsible for your evaluation scope.',
    ],
    'Staff' => [
        'icon' => 'fa-briefcase',
        'class' => 'group-staff',
        'description' => 'Evaluate eligible staff members assigned to your scope.',
    ],
];

$selectedType = $_GET['type'] ?? '';
if (!array_key_exists($selectedType, $categories)) {
    $selectedType = '';
}

// Completion state is scoped to EA evaluations for the active period.
$done = [];
if ($period_id) {
    $doneStmt = $mysqli->prepare("
        SELECT target_user_id
        FROM evaluation_tracker
        WHERE evaluator_id=?
          AND period_id=?
          AND eval_type='ea'
          AND status='submitted'
    ");
    $doneStmt->bind_param('ii', $ea_id, $period_id);
    $doneStmt->execute();
    $rows = $doneStmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $done = array_flip(array_map('intval', array_column($rows, 'target_user_id')));
    $doneStmt->close();
}

$total = 0;
$completed = 0;
foreach ($categories as $group) {
    foreach ($group as $person) {
        $total++;
        if (isset($done[(int) $person['id']])) {
            $completed++;
        }
    }
}
$pending = max(0, $total - $completed);
$justSubmitted = isset($_GET['submitted']);
$mysqli->close();

function e($value) {
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function personPhoto(?string $photo): string {
    return !empty($photo) ? '../image/' . e($photo) : '';
}
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Evaluate Others — PBI</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<link href="https://fonts.googleapis.com/css2?family=Rajdhani:wght@600;700&family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="admin_appearance.css">
<script src="admin_appearance.js"></script>
<style>
:root{
  /* Light mode defaults. These tokens intentionally follow the shared
     PBI Admin appearance engine instead of hard-locking this page to dark. */
  --ea-page-bg:var(--bg,#f4f8ff);
  --ea-panel:var(--panel-bg,#ffffff);
  --ea-panel-soft:#f8fbff;
  --ea-panel-deep:#eef4fb;
  --ea-line:var(--panel-border,#dce8f5);
  --ea-text:var(--text,#10243f);
  --ea-muted:var(--muted,#66809c);
  --ea-muted-2:#7e93ab;
  --ea-card:#ffffff;
  --ea-card-border:#d7e3ef;
  --ea-card-hover:#f4f8ff;
  --ea-card-selected:#edf5ff;
  --ea-icon-neutral:#edf4fd;
  --ea-photo-bg:#f2f6fb;
  --ea-photo-border:#c9d9ea;
  --ea-shadow:0 8px 24px rgba(30,82,144,.08);
  --ea-shadow-hover:0 10px 24px rgba(30,82,144,.12);
  --ea-info:#58718c;
  --blue:#2F6EE2;
  --blue-soft:rgba(59,130,246,.10);
  --blue-border:rgba(59,130,246,.35);
  --green:#22C55E;
  --green-soft:rgba(34,197,94,.10);
  --green-border:rgba(34,197,94,.26);
  --gold:#F59E0B;
  --gold-soft:rgba(245,158,11,.10);
  --gold-border:rgba(245,158,11,.28);
}

/* Dark mode — preserve the established dark visual palette. */
html[data-theme="dark"]{
  --ea-page-bg:#07192D;
  --ea-panel:#132844;
  --ea-panel-soft:#142A47;
  --ea-panel-deep:#10233D;
  --ea-line:#213A5C;
  --ea-text:#F5F7FB;
  --ea-muted:#9CB0CA;
  --ea-muted-2:#8298B4;
  --ea-card:#10233E;
  --ea-card-border:#223A5B;
  --ea-card-hover:#142C4B;
  --ea-card-selected:#142C4B;
  --ea-icon-neutral:#18304E;
  --ea-photo-bg:#18304E;
  --ea-photo-border:#2B476A;
  --ea-shadow:0 12px 32px rgba(0,0,0,.15);
  --ea-shadow-hover:0 10px 24px rgba(0,0,0,.18);
  --ea-info:#8FA7C6;
}

*{box-sizing:border-box}
html,body{
  margin:0;
  min-height:100%;
  background:var(--ea-page-bg);
  color:var(--ea-text);
  font-family:'Inter',Segoe UI,Arial,sans-serif;
}
body{overflow-x:hidden}
a{color:inherit}
.wrap{max-width:1390px;margin:0 auto;padding:28px 30px 34px}

/* Main evaluation panel — intentionally mirrors the faculty evaluation card. */
.evaluation-panel{
  background:var(--ea-panel);
  border:1px solid var(--ea-line);
  border-radius:14px;
  padding:24px 28px 22px;
  box-shadow:var(--ea-shadow);
}
.panel-title{display:flex;align-items:center;gap:11px;margin:0 0 14px;font-family:'Rajdhani',sans-serif;font-size:26px;font-weight:700;letter-spacing:.1px;color:var(--ea-text)}
.panel-title i{color:var(--blue);font-size:18px}
.panel-copy{margin:0 0 20px;color:var(--ea-info);font-size:13.5px;line-height:1.6}
.step-title{display:flex;align-items:center;gap:8px;margin:0 0 10px;color:var(--ea-muted);font-size:11.5px;font-weight:800;letter-spacing:.7px;text-transform:uppercase}
.step-title i{color:var(--ea-muted);font-size:12px}

.group-grid{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:18px}
.group-card{
  position:relative;
  display:flex;
  flex-direction:column;
  align-items:center;
  justify-content:center;
  min-height:188px;
  padding:22px 18px;
  text-decoration:none;
  background:var(--ea-card);
  border:1px solid var(--ea-card-border);
  border-radius:14px;
  transition:transform .16s ease,border-color .16s ease,background .16s ease,box-shadow .16s ease;
  overflow:hidden;
}
.group-card::after{content:'';position:absolute;inset:auto 0 0;height:3px;background:transparent;transition:background .16s ease}
.group-card:hover{transform:translateY(-2px);background:var(--ea-card-hover);border-color:#8fb5e2;box-shadow:var(--ea-shadow-hover)}
.group-card.selected{background:var(--ea-card-selected);border-color:#9fc5f5;box-shadow:0 8px 20px rgba(30,82,144,.10)}
.group-icon{width:64px;height:64px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:25px;margin-bottom:15px}
.group-card h3{margin:0;color:var(--ea-text);font-family:'Rajdhani',sans-serif;font-size:20px;font-weight:700;letter-spacing:.1px}
.group-count{margin-top:3px;color:var(--ea-muted);font-size:13.5px}
.group-description{max-width:310px;margin:10px 0 0;text-align:center;color:var(--ea-muted-2);font-size:11.5px;line-height:1.45}
.group-principal .group-icon{background:var(--blue-soft);color:#3D8BFF}
.group-principal::after{background:#3B82F6}
.group-dean .group-icon{background:var(--gold-soft);color:#F59E0B}
.group-dean::after{background:#F59E0B}
.group-staff .group-icon{background:var(--green-soft);color:#22C55E}
.group-staff::after{background:#22C55E}
.group-card.selected.group-principal{border-color:var(--blue-border)}
.group-card.selected.group-dean{border-color:var(--gold-border)}
.group-card.selected.group-staff{border-color:var(--green-border)}

.info-note{
  display:flex;
  align-items:flex-start;
  gap:8px;
  margin-top:18px;
  color:var(--ea-info);
  font-size:12px;
  line-height:1.5;
}
.info-note i{color:var(--ea-info);margin-top:1px;flex-shrink:0}

.alert{display:flex;align-items:center;gap:9px;margin:14px 0 0;padding:11px 13px;border-radius:9px;font-size:12.5px}
.alert-success{color:#087f5b;background:rgba(16,185,129,.10);border:1px solid rgba(52,211,153,.24)}
.alert-info{color:#245b9d;background:rgba(59,130,246,.08);border:1px solid rgba(59,130,246,.20)}
html[data-theme="dark"] .alert-success{color:#A7F3D0;background:rgba(16,185,129,.11);border-color:rgba(52,211,153,.22)}
html[data-theme="dark"] .alert-info{color:#A8C7EF;background:rgba(59,130,246,.10);border-color:rgba(59,130,246,.22)}

/* Step 2 appears only after a group is selected. */
.roster-panel{
  margin-top:20px;
  background:var(--ea-panel);
  border:1px solid var(--ea-line);
  border-radius:14px;
  overflow:hidden;
  box-shadow:var(--ea-shadow);
}
.roster-head{display:flex;align-items:center;justify-content:space-between;gap:16px;padding:18px 22px;border-bottom:1px solid var(--ea-line);background:var(--ea-panel-soft)}
.roster-heading{display:flex;align-items:center;gap:11px}
.roster-heading .roster-icon{width:38px;height:38px;border-radius:10px;display:flex;align-items:center;justify-content:center;background:var(--ea-icon-neutral);color:#2F6EE2;font-size:15px}
.roster-heading h2{margin:0;font-family:'Rajdhani',sans-serif;font-size:20px;font-weight:700;color:var(--ea-text)}
.roster-heading p{margin:2px 0 0;color:var(--ea-muted-2);font-size:11.5px}
.roster-step{color:var(--ea-muted);font-size:11px;font-weight:800;text-transform:uppercase;letter-spacing:.6px;white-space:nowrap}
.person-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:12px;padding:18px;background:var(--ea-panel)}
.person-card{
  display:flex;align-items:center;gap:13px;
  background:var(--ea-card);
  border:1px solid var(--ea-card-border);
  border-radius:11px;
  padding:13px 14px;
}
.person-photo{width:44px;height:44px;border-radius:50%;object-fit:cover;background:var(--ea-photo-bg);border:1px solid var(--ea-photo-border);display:flex;align-items:center;justify-content:center;color:var(--ea-muted);flex-shrink:0;overflow:hidden}
.person-photo img{width:100%;height:100%;object-fit:cover;display:block}
.person-info{min-width:0;flex:1}
.person-name{font-size:13.5px;font-weight:800;color:var(--ea-text);white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
.person-meta{margin-top:3px;color:var(--ea-muted);font-size:11.5px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
.status-pill{display:inline-flex;align-items:center;gap:5px;margin-top:7px;padding:4px 9px;border-radius:999px;font-size:10px;font-weight:800}
.status-pill.done{background:rgba(52,211,153,.12);color:#11805f;border:1px solid rgba(52,211,153,.18)}
.status-pill.pending{background:rgba(245,158,11,.12);color:#a66000;border:1px solid rgba(245,158,11,.18)}
html[data-theme="dark"] .status-pill.done{color:#6EE7B7}
html[data-theme="dark"] .status-pill.pending{color:#F8C465}
.person-action{flex-shrink:0}
.btn-action{display:inline-flex;align-items:center;justify-content:center;gap:6px;padding:9px 13px;border-radius:8px;text-decoration:none;font-size:11.5px;font-weight:800;border:1px solid transparent}
.btn-action.evaluate{background:#2563EB;color:#fff;border-color:#3B82F6}
.btn-action.evaluate:hover{background:#1D4ED8}
.btn-action.view{background:transparent;color:var(--ea-muted);border-color:#9fb4ca}
.btn-action.view:hover{background:var(--ea-card-hover);color:var(--ea-text)}
html[data-theme="dark"] .btn-action.view{color:#B1C1D4;border-color:#34506F}
html[data-theme="dark"] .btn-action.view:hover{background:#172E4C;color:#fff}
.roster-empty{text-align:center;padding:44px 20px;color:var(--ea-muted-2);font-size:12.5px}
.roster-empty i{display:block;margin-bottom:10px;font-size:26px;opacity:.45}

.compact-summary{display:flex;align-items:center;gap:18px;padding:13px 18px;border-top:1px solid var(--ea-line);background:var(--ea-panel-soft);color:var(--ea-muted);font-size:11.5px}
.compact-summary strong{color:var(--ea-text)}

/* Keep the light palette readable even though the shared dark-surface observer
   adds classes only while dark mode is active. */
html[data-theme="dark"] .group-card:hover,
html[data-theme="dark"] .group-card.selected{border-color:#31557D}
html[data-theme="dark"] .roster-heading .roster-icon{background:#1D3A60;color:#78AFFF}
html[data-theme="dark"] .person-photo{background:#18304E;border-color:#2B476A;color:#91A7C3}
html[data-theme="dark"] .btn-action.view{background:transparent}

@media(max-width:900px){
  .wrap{padding:18px}
  .evaluation-panel{padding:20px}
  .group-grid{grid-template-columns:1fr}
  .group-card{min-height:160px}
  .person-grid{grid-template-columns:1fr}
}
@media(max-width:560px){
  .wrap{padding:12px}
  .evaluation-panel{padding:16px}
  .panel-title{font-size:23px}
  .group-card{min-height:150px}
  .roster-head{align-items:flex-start;flex-direction:column}
  .roster-step{margin-left:49px}
  .person-card{align-items:flex-start;flex-wrap:wrap}
  .person-action{width:100%}
  .person-action .btn-action{width:100%}
}
</style>
</head>
<body class="feature-compact">
<main class="wrap">
  <section class="evaluation-panel">
    <h1 class="panel-title"><i class="fa-solid fa-clipboard-check"></i> Evaluation</h1>
    <p class="panel-copy">Choose who you want to evaluate. The Executive Assistant can evaluate the Principal, Dean, or eligible Staff members.</p>

    <div class="step-title"><i class="fa-solid fa-bolt"></i> Step 1: Select Evaluation Group</div>

    <div class="group-grid">
      <?php foreach ($categories as $type => $people):
          $meta = $groupMeta[$type];
          $isSelected = $selectedType === $type;
          $count = count($people);
      ?>
        <a class="group-card <?= e($meta['class']) ?> <?= $isSelected ? 'selected' : '' ?>" href="?type=<?= urlencode($type) ?>" aria-label="Select <?= e($type) ?>">
          <div class="group-icon"><i class="fa-solid <?= e($meta['icon']) ?>"></i></div>
          <h3><?= e($type) ?></h3>
          <div class="group-count"><?= $count ?> <?= $count === 1 ? 'member' : 'members' ?></div>
          <p class="group-description"><?= e($meta['description']) ?></p>
        </a>
      <?php endforeach; ?>
    </div>

    <div class="info-note">
      <i class="fa-solid fa-circle-info"></i>
      <span>Only active and eligible personnel are shown. Staff listed here are non-teaching staff without teaching or year-level assignments.</span>
    </div>

    <?php if ($justSubmitted): ?>
      <div class="alert alert-success"><i class="fa-solid fa-circle-check"></i> Evaluation submitted successfully.</div>
    <?php endif; ?>
  </section>

  <?php if ($selectedType !== ''):
      $people = $categories[$selectedType];
      $selectedMeta = $groupMeta[$selectedType];
      $groupCompleted = 0;
      foreach ($people as $person) {
          if (isset($done[(int) $person['id']])) $groupCompleted++;
      }
  ?>
    <section class="roster-panel">
      <div class="roster-head">
        <div class="roster-heading">
          <div class="roster-icon"><i class="fa-solid <?= e($selectedMeta['icon']) ?>"></i></div>
          <div>
            <h2><?= e($selectedType) ?></h2>
            <p>Select a person to start or review your evaluation.</p>
          </div>
        </div>
        <div class="roster-step">Step 2: Select Personnel</div>
      </div>

      <?php if (!$people): ?>
        <div class="roster-empty">
          <i class="fa-solid fa-user-slash"></i>
          No eligible <?= e($selectedType) ?> personnel are currently available.
        </div>
      <?php else: ?>
        <div class="person-grid">
          <?php foreach ($people as $person):
              $pid = (int) $person['id'];
              $isDone = isset($done[$pid]);
          ?>
            <article class="person-card">
              <?php if (!empty($person['photo'])): ?>
                <div class="person-photo"><img src="<?= personPhoto($person['photo']) ?>" alt=""></div>
              <?php else: ?>
                <div class="person-photo"><i class="fa-solid fa-user"></i></div>
              <?php endif; ?>

              <div class="person-info">
                <div class="person-name" title="<?= e($person['full_name']) ?>"><?= e($person['full_name']) ?></div>
                <div class="person-meta"><?= e($person['designation'] ?: $selectedType) ?></div>
                <span class="status-pill <?= $isDone ? 'done' : 'pending' ?>">
                  <i class="fa-solid <?= $isDone ? 'fa-check' : 'fa-hourglass-half' ?>"></i>
                  <?= $isDone ? 'Completed' : 'Pending' ?>
                </span>
              </div>

              <div class="person-action">
                <a class="btn-action <?= $isDone ? 'view' : 'evaluate' ?>" href="ea_evaluate.php?type=<?= urlencode($selectedType) ?>&user_id=<?= $pid ?>">
                  <i class="fa-solid <?= $isDone ? 'fa-eye' : 'fa-pen' ?>"></i>
                  <?= $isDone ? 'View' : 'Evaluate' ?>
                </a>
              </div>
            </article>
          <?php endforeach; ?>
        </div>
        <div class="compact-summary">
          <span><strong><?= count($people) ?></strong> <?= count($people) === 1 ? 'member' : 'members' ?></span>
          <span><strong><?= $groupCompleted ?></strong> completed</span>
          <span><strong><?= max(0, count($people) - $groupCompleted) ?></strong> pending</span>
          <?php if ($periodName !== ''): ?><span style="margin-left:auto"><i class="fa-regular fa-calendar"></i> <?= e($periodName) ?></span><?php endif; ?>
        </div>
      <?php endif; ?>
    </section>
  <?php endif; ?>
</main>
</body>
</html>
