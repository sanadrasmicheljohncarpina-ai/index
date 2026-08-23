<?php
// admin/ea_evaluation.php
// EA Evaluation roster. Rebuilt to match the roster -> evaluate pattern
// used everywhere else evaluations happen in this system (see
// dean/dean_evaluation.php + dean/dean_evaluate.php, and
// principal/principal_evaluations.php + principal/principal_evaluate.php):
// a stat/tab/table roster here, a separate server-rendered form on
// ea_evaluate.php. Eligibility is unchanged from before — the EA can only
// evaluate the active Principal, the active Dean, and Staff members who
// have no year-level/teaching assignment (Non-Teaching Staff).
session_set_cookie_params([
    'lifetime' => 0, 'path' => '/', 'domain' => '',
    'secure' => false, 'httponly' => true, 'samesite' => 'Lax',
]);
session_start();
require_once 'db.php';

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

// EA currently maps to the system's privileged/superadmin account.
// Keep executive_assistant here for forward compatibility.
if (!isset($_SESSION['user_id']) ||
    !in_array($_SESSION['role'] ?? '', ['admin','superadmin','executive_assistant'], true)) {
    header('Location: admin_login.php');
    exit;
}

$ea_id = (int)$_SESSION['user_id'];

// Ensure EA can be stored even on older installations where eval_type was an ENUM.
try {
    $mysqli->query("ALTER TABLE evaluation_tracker MODIFY eval_type VARCHAR(30) NOT NULL DEFAULT 'student'");
} catch (Throwable $ignore) {}

foreach ([
    "ALTER TABLE evaluation_tracker ADD COLUMN evaluator_id INT UNSIGNED NULL",
    "ALTER TABLE evaluation_tracker ADD COLUMN target_user_id INT UNSIGNED NULL",
    "ALTER TABLE evaluation_tracker ADD COLUMN period_id INT UNSIGNED NULL",
    "ALTER TABLE evaluation_tracker ADD COLUMN status VARCHAR(20) NOT NULL DEFAULT 'submitted'",
] as $ddl) {
    // Ignore duplicate-column errors so the page remains safe on existing databases.
    try { $mysqli->query($ddl); } catch (Throwable $ignore) {}
}

$period = $mysqli->query("
    SELECT id, period_label, is_active
    FROM evaluation_periods
    WHERE is_active=1
    ORDER BY id DESC
    LIMIT 1
")->fetch_assoc();
$period_id = (int)($period['id'] ?? 0);
$is_open = $period_id > 0;

// ── ELIGIBLE TARGETS (unchanged) ──────────────────────────────────────
// Principal + Dean are single-user role targets. Non-Teaching Staff =
// primary Staff users with no year-level/teaching assignment. This is
// the exact same eligibility ea_evaluate.php re-checks before accepting
// a submission, so nobody can be evaluated here who isn't allowed.
$heads = ['Principal'=>[], 'Dean'=>[]];
$headStmt = $mysqli->prepare("
    SELECT id, full_name, designation, photo, role
    FROM users
    WHERE role IN ('principal','dean')
      AND is_active=1
      AND account_status='approved'
    ORDER BY full_name
");
$headStmt->execute();
foreach ($headStmt->get_result()->fetch_all(MYSQLI_ASSOC) as $u) {
    $heads[$u['role'] === 'principal' ? 'Principal' : 'Dean'][] = $u;
}
$headStmt->close();

$ntsStmt = $mysqli->prepare("
    SELECT u.id, u.full_name, u.designation, u.photo, u.role, u.secondary_role
    FROM users u
    WHERE u.role='staff'
      AND u.is_active=1
      AND (u.account_status='approved' OR u.source='admin_nologin')
      AND NOT EXISTS (SELECT 1 FROM teaching_assignments ta WHERE ta.user_id=u.id)
      AND NOT EXISTS (SELECT 1 FROM user_year_levels yl WHERE yl.user_id=u.id)
    ORDER BY u.full_name
");
$ntsStmt->execute();
$nonTeaching = $ntsStmt->get_result()->fetch_all(MYSQLI_ASSOC);
$ntsStmt->close();

$categories = [
    'Principal' => $heads['Principal'],
    'Dean' => $heads['Dean'],
    'Non-Teaching Staff' => $nonTeaching,
];
$tabIcons = ['Principal'=>'fa-user-tie','Dean'=>'fa-graduation-cap','Non-Teaching Staff'=>'fa-users-gear'];

$selectedType = $_GET['type'] ?? 'Principal';
if (!array_key_exists($selectedType, $categories)) $selectedType = 'Principal';

// Completion state is scoped by eval_type so EA submissions never collide
// with Student, Peer, or School Head evaluations.
$done = [];
if ($period_id) {
    $doneStmt = $mysqli->prepare("
        SELECT target_user_id
        FROM evaluation_tracker
        WHERE evaluator_id=? AND period_id=? AND eval_type='ea' AND status='submitted'
    ");
    $doneStmt->bind_param('ii', $ea_id, $period_id);
    $doneStmt->execute();
    $done = array_flip(array_map('intval', array_column($doneStmt->get_result()->fetch_all(MYSQLI_ASSOC), 'target_user_id')));
    $doneStmt->close();
}

$total = 0; $completed = 0;
foreach ($categories as $group) foreach ($group as $p) { $total++; if (isset($done[(int)$p['id']])) $completed++; }

$justSubmitted = isset($_GET['submitted']);
$mysqli->close();
function e($v){ return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>EA Evaluation — PBI</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<style>
:root{--bg:#102238;--panel:#18314e;--panel2:#203d5f;--inner:#0F1F3D;--line:#315273;--text:#edf4fb;--muted:#aebfd0;--purple:#8b5cf6;--purple-dark:#6d3fd6;--blue:#60a5fa;--green:#34d399;--amber:#f59e0b;--shadow:0 8px 32px rgba(0,0,0,.35)}
*{box-sizing:border-box} body{margin:0;background:var(--bg);color:var(--text);font-family:Inter,Segoe UI,Arial,sans-serif}
.top{height:74px;background:#203d5f;border-bottom:1px solid var(--line);display:flex;align-items:center;padding:0 34px;gap:26px;position:sticky;top:0;z-index:5}
.brand{font-weight:800;letter-spacing:.4px;flex:1}.brand i{color:var(--purple);margin-right:9px}
.top-back{color:var(--muted);text-decoration:none;padding:10px 16px;border-radius:9px;font-weight:700;font-size:13.5px;display:flex;align-items:center;gap:8px;border:1px solid var(--line)}
.top-back:hover{color:#fff;border-color:#4a6d92}
.account{color:var(--muted);font-size:13px}
.wrap{max-width:1320px;margin:auto;padding:34px}
.page-header{display:flex;justify-content:space-between;align-items:center;margin-bottom:22px;flex-wrap:wrap;gap:14px}
.page-header h1{margin:0;font-size:30px}.page-header p{color:var(--muted);margin:6px 0 0}
.period-badge{background:rgba(139,92,246,.14);border:1px solid rgba(139,92,246,.3);color:#c4b5fd;padding:8px 16px;border-radius:20px;font-size:12.5px;font-weight:700;display:flex;align-items:center;gap:8px;white-space:nowrap}
.period-badge.closed{background:rgba(248,113,113,.1);border-color:rgba(248,113,113,.3);color:#ffb4b4}

.alert{border-radius:10px;padding:13px 16px;font-size:13.5px;margin-bottom:18px;display:flex;align-items:center;gap:8px}
.alert-success{background:rgba(52,211,153,.12);border:1px solid rgba(52,211,153,.25);color:#8df0c8}

.card-grid{display:grid;grid-template-columns:repeat(3,1fr);gap:16px;margin-bottom:24px}
.stat-card{background:var(--panel);border:1px solid var(--line);border-radius:16px;padding:18px 20px;box-shadow:var(--shadow)}
.stat-card i{color:var(--purple);font-size:18px;margin-bottom:8px;display:block}
.stat-card .num{font-size:26px;font-weight:800;color:#fff}
.stat-card .label{font-size:12px;color:var(--muted);margin-top:4px}

.eval-tabs{display:flex;gap:4px;background:var(--panel2);border:1px solid var(--line);border-radius:12px;padding:4px;margin-bottom:22px;width:fit-content;flex-wrap:wrap}
.eval-tab{padding:10px 20px;border-radius:8px;font-size:13.5px;font-weight:700;color:var(--muted);text-decoration:none;display:flex;align-items:center;gap:8px}
.eval-tab.active{background:var(--purple);color:#fff}
.eval-tab:not(.active):hover{background:rgba(255,255,255,.05);color:var(--text)}
.eval-tab .badge{background:rgba(255,255,255,.15);border-radius:20px;padding:1px 8px;font-size:11px;font-weight:700}
.eval-tab.active .badge{background:rgba(255,255,255,.25)}

.table-wrap{background:var(--panel);border:1px solid var(--line);border-radius:16px;overflow:hidden;box-shadow:var(--shadow)}
table{width:100%;border-collapse:collapse}
thead tr{background:var(--inner)}
thead th{padding:13px 18px;font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.6px;color:var(--muted);text-align:left;white-space:nowrap}
tbody tr{border-bottom:1px solid rgba(255,255,255,.05)}
tbody tr:last-child{border-bottom:none}
tbody tr:hover{background:rgba(139,92,246,.06)}
tbody td{padding:14px 18px;font-size:13.5px;vertical-align:middle}
.person-cell{display:flex;align-items:center;gap:11px}
.person-photo{width:38px;height:38px;border-radius:50%;object-fit:cover;background:var(--inner);flex-shrink:0;display:flex;align-items:center;justify-content:center;color:var(--muted)}
.person-name{font-weight:700;color:#fff}
.muted-cell{color:var(--muted);font-size:12.5px}
.status-pill{display:inline-flex;align-items:center;gap:5px;padding:4px 11px;border-radius:20px;font-size:11px;font-weight:800}
.status-pill.done{background:rgba(52,211,153,.14);color:var(--green)}
.status-pill.pending{background:rgba(245,158,11,.14);color:#f8c675}
.btn-eval{background:var(--purple);border:none;color:#fff;padding:8px 15px;border-radius:8px;font-size:12.5px;font-weight:700;cursor:pointer;text-decoration:none;display:inline-flex;align-items:center;gap:6px}
.btn-eval:hover{background:var(--purple-dark)}
.btn-view{background:transparent;border:1px solid var(--line);color:var(--muted);padding:8px 15px;border-radius:8px;font-size:12.5px;font-weight:700;cursor:pointer;text-decoration:none;display:inline-flex;align-items:center;gap:6px;margin-left:6px}
.btn-view:hover{color:var(--text);border-color:#4a6d92}
.empty-state{text-align:center;padding:56px 20px;color:var(--muted)}
.empty-state i{font-size:36px;margin-bottom:14px;display:block;opacity:.3}
@media(max-width:900px){.card-grid{grid-template-columns:1fr}.top-back span{display:none}.top{padding:0 18px}.wrap{padding:20px}}
</style>
</head>
<body>
<header class="top">
  <div class="brand"><i class="fa-solid fa-user-check"></i>EA Evaluation</div>
  <a class="top-back" href="admin_dashboard.php"><i class="fa-solid fa-arrow-left"></i> Back to Dashboard</a>
  <div class="account"><?= e($_SESSION['full_name'] ?? 'Executive Assistant') ?></div>
</header>
<main class="wrap">

<div class="page-header">
  <div>
    <h1>EA Evaluation</h1>
    <p>Evaluate the active Principal, Dean, and staff members who are not assigned to teach any year level.</p>
  </div>
  <span class="period-badge <?= $is_open ? '' : 'closed' ?>">
    <i class="fa-solid fa-calendar-check"></i>
    <?= e($period['period_label'] ?? 'No active evaluation period') ?> — <?= $is_open ? 'OPEN' : 'CLOSED' ?>
  </span>
</div>

<?php if ($justSubmitted): ?>
<div class="alert alert-success"><i class="fa-solid fa-circle-check"></i> EA evaluation submitted successfully.</div>
<?php endif; ?>

<div class="card-grid">
  <div class="stat-card"><i class="fa-solid fa-list-check"></i><div class="num"><?= $total ?></div><div class="label">Required EA evaluations</div></div>
  <div class="stat-card"><i class="fa-solid fa-circle-check"></i><div class="num"><?= $completed ?></div><div class="label">Completed</div></div>
  <div class="stat-card"><i class="fa-solid fa-hourglass-half"></i><div class="num"><?= max(0,$total-$completed) ?></div><div class="label">Pending</div></div>
</div>

<div class="eval-tabs">
<?php foreach ($categories as $type => $people): ?>
  <a class="eval-tab <?= $selectedType===$type?'active':'' ?>" href="?type=<?= urlencode($type) ?>">
    <i class="fa-solid <?= $tabIcons[$type] ?>"></i> <?= e($type) ?> <span class="badge"><?= count($people) ?></span>
  </a>
<?php endforeach; ?>
</div>

<div class="table-wrap">
<table>
<thead><tr><th>Profile</th><th>Full Name</th><th>Designation</th><th>Evaluation Status</th><th>Actions</th></tr></thead>
<tbody>
<?php if (!$categories[$selectedType]): ?>
<tr><td colspan="5"><div class="empty-state"><i class="fa-solid fa-user-slash"></i><p>No eligible <?= e($selectedType) ?> personnel found.</p></div></td></tr>
<?php else: foreach ($categories[$selectedType] as $p): $pid = (int)$p['id']; $isDone = isset($done[$pid]); ?>
<tr>
  <td><?php if (!empty($p['photo'])): ?><img class="person-photo" src="../image/<?= e($p['photo']) ?>" alt=""><?php else: ?><div class="person-photo"><i class="fa-solid fa-user"></i></div><?php endif; ?></td>
  <td><span class="person-name"><?= e($p['full_name']) ?></span></td>
  <td class="muted-cell"><?= e($p['designation'] ?: $selectedType) ?></td>
  <td>
    <span class="status-pill <?= $isDone ? 'done' : 'pending' ?>">
      <?php if ($isDone): ?><i class="fa-solid fa-check" style="font-size:9px;"></i> Completed
      <?php else: ?><i class="fa-solid fa-hourglass-half" style="font-size:9px;"></i> Pending
      <?php endif; ?>
    </span>
  </td>
  <td>
    <?php if (!$is_open && !$isDone): ?>
      <span class="muted-cell">Evaluation closed</span>
    <?php elseif ($isDone): ?>
      <a class="btn-view" href="ea_evaluate.php?type=<?= urlencode($selectedType) ?>&user_id=<?= $pid ?>"><i class="fa-solid fa-eye"></i> View</a>
    <?php else: ?>
      <a class="btn-eval" href="ea_evaluate.php?type=<?= urlencode($selectedType) ?>&user_id=<?= $pid ?>"><i class="fa-solid fa-pen"></i> Evaluate</a>
    <?php endif; ?>
  </td>
</tr>
<?php endforeach; endif; ?>
</tbody>
</table>
</div>

</main>
</body>
</html>
