<?php
// session_bootstrap.php — include this BEFORE session_start() everywhere
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
require_once dirname(__DIR__) . '/shared/system_settings_service.php';
require_once dirname(__DIR__) . '/shared/ea_personnel_service.php';

// ── AUTH GUARD ────────────────────────────────────────────
if (empty($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'dean') {
    header("Location: dean_login.php");
    exit;
}
$deanId = (int)$_SESSION['user_id'];

// ── PULL DEAN PROFILE ─────────────────────────────────────
$stmt = $mysqli->prepare("SELECT full_name, username, email, designation, photo FROM users WHERE id = ? LIMIT 1");
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$me = $stmt->get_result()->fetch_assoc();
$stmt->close();

// ── GLOBAL SYSTEM SETTINGS (Step 14) ───────────────────────────────────
$settings = get_system_settings($mysqli);
$structureActive = ($settings['academic_structure'] === 'college');
$period_id_int    = $settings['period_id'] ?? 0;
$evalOpen         = $settings['is_open_for_submission'];
$hasPeriod        = $period_id_int > 0;

const HIGHER_ED_LABEL = 'Higher Education';

// ── TAB (single "Faculty" tab: Teacher + Staff + Executive Assistant) ──
// REFACTOR NOTE: Teacher and Staff used to be two separate tabs/queries,
// then were merged into one "Faculty" roster with a Department/Office
// filter and an Include filter (All Faculty / Teachers Only / Staff
// Only) doing the narrowing that separate tabs used to do. Executive
// Assistant was its own tab after that; it is now folded into this same
// Faculty tab/roster too, so Faculty, Teaching/Non-Teaching Staff, and
// the EA all appear together in one place. Only one tab remains, so
// $_GET['tab'] is no longer read here — any old bookmarked
// ?tab=executive_assistant link still resolves, it just shows the same
// merged Faculty roster instead of a separate EA-only one.
$validTabs = ['faculty'];
$tab = 'faculty';

$tabLabels = [
    'faculty' => 'Faculty',
];
$tabIcons = [
    'faculty' => 'fa-users',
];

// ── FACULTY ROSTER (Teacher + Staff + Executive Assistant) ─────────────
// Who is EVALUABLE (appears in this roster) and what QUESTIONS they get
// evaluated on are two different questions with two different sources:
//   - Roster membership = the real personnel pool: every approved Teacher
//     and Staff account, plus the single approved EA (superadmin)
//     account, all merged into one Faculty list. This mirrors
//     questionnaire.php's own $all_users / $faculty_users query, so the
//     same person set shows up in both places.
//   - Per-person QUESTIONS come directly from the questionnaire's Student
//     Evaluation -> Faculty (Teacher) pool (evaluation_questions WHERE
//     target_type='Teacher' AND eval_type='student') — see
//     dean_evaluate.php. That pool is shared/global, not per-evaluator,
//     so there is no more "not assigned to you yet" per-Dean state; the
//     only "no questions" case is that pool being empty entirely.
// Assigning Year Levels (Manage Privileged Accounts) is what turns a Staff
// account into a "Teaching Staff" one — that's a classification/label only,
// not an eligibility gate. Both plain Staff and Teaching Staff belong in
// this Faculty roster.
$facultyMerged = [];

$fres = $mysqli->query("
    SELECT id, full_name, designation AS position, photo, department, role,
           EXISTS(
               SELECT 1 FROM teaching_assignments ta WHERE ta.user_id = users.id
           ) OR EXISTS(
               SELECT 1 FROM user_year_levels yl WHERE yl.user_id = users.id
           ) AS is_teaching_staff
    FROM users
    WHERE role IN ('teacher','staff')
      AND is_active = 1
      AND account_status = 'approved'
    ORDER BY full_name ASC
");
if ($fres) while ($u = $fres->fetch_assoc()) {
    $u['evaluation_status'] = 'not_started';
    $u['last_evaluation_date'] = null;
    if ($hasPeriod) {
        $statusStmt = $mysqli->prepare("SELECT status, submitted_at
            FROM evaluation_tracker
            WHERE eval_type='school_head' AND evaluator_id=? AND target_user_id=? AND period_id=?
              AND status IN ('submitted','approved')
            ORDER BY submitted_at DESC, id DESC LIMIT 1");
        if ($statusStmt) {
            $statusStmt->bind_param('iii', $deanId, $u['id'], $period_id_int);
            $statusStmt->execute();
            $statusRow = $statusStmt->get_result()->fetch_assoc();
            $statusStmt->close();
            if ($statusRow) {
                $u['evaluation_status'] = 'completed';
                $u['last_evaluation_date'] = $statusRow['submitted_at'];
            }
        }
    }
    $u['role_label'] = ($u['role'] === 'staff')
        ? ((int)$u['is_teaching_staff'] === 1 ? 'Teaching Staff' : 'Staff')
        : 'Faculty';
    $u['route_tab'] = 'faculty';
    $facultyMerged[] = $u;
}

// Single active Executive Assistant (superadmin) account, folded into the
// same Faculty roster/tab as Teacher and Staff. The viewer here is always
// the Dean (not the EA), so — unlike questionnaire.php, which prefers the
// viewing EA's own session — this always takes the most recently active
// approved EA account.
$eaRes = $mysqli->query("SELECT id, full_name, designation AS position, photo, department, role
    FROM users WHERE role='superadmin' AND is_active=1 AND account_status='approved'
    ORDER BY updated_at DESC LIMIT 1");
$current_ea = $eaRes ? $eaRes->fetch_assoc() : null;
if ($current_ea) {
    $current_ea['evaluation_status'] = 'not_started';
    $current_ea['last_evaluation_date'] = null;
    if ($hasPeriod) {
        $statusStmt = $mysqli->prepare("SELECT status, submitted_at
            FROM evaluation_tracker
            WHERE eval_type='school_head' AND evaluator_id=? AND target_user_id=? AND period_id=?
              AND status IN ('submitted','approved')
            ORDER BY submitted_at DESC, id DESC LIMIT 1");
        if ($statusStmt) {
            $statusStmt->bind_param('iii', $deanId, $current_ea['id'], $period_id_int);
            $statusStmt->execute();
            $statusRow = $statusStmt->get_result()->fetch_assoc();
            $statusStmt->close();
            if ($statusRow) {
                $current_ea['evaluation_status'] = 'completed';
                $current_ea['last_evaluation_date'] = $statusRow['submitted_at'];
            }
        }
    }
    $current_ea['role_label'] = 'Executive Assistant';
    $current_ea['route_tab'] = 'faculty';
    $current_ea['department'] = null;
    $facultyMerged[] = $current_ea;
}

usort($facultyMerged, fn($a, $b) => strcmp($a['full_name'], $b['full_name']));

$rosterByTab = ['faculty' => $facultyMerged];
$activeRoster = $rosterByTab[$tab];

$countCompleted = fn(array $rows) => count(array_filter($rows, fn($r) => $r['evaluation_status'] === 'completed'));
$facultyCompleted = $countCompleted($facultyMerged);

$facultyToEvaluate = count($facultyMerged);
$totalAssigned = $facultyToEvaluate;
$totalCompleted = $facultyCompleted;
$completedEvaluations = $totalCompleted;
$pendingEvaluations = max(0, $totalAssigned - $totalCompleted);
$completionPct = $totalAssigned > 0 ? (int)round($totalCompleted / $totalAssigned * 100) : 0;

// ── FILTER OPTIONS (built from real roster data, not hardcoded) ────────
// Department/Office dropdown only ever lists departments that actually
// exist among current Faculty rows.
$departmentOptions = [];
foreach ($facultyMerged as $r) {
    $d = trim((string)($r['department'] ?? ''));
    if ($d !== '') $departmentOptions[$d] = true;
}
$departmentOptions = array_keys($departmentOptions);
sort($departmentOptions);

// "Include" options ARE a fixed 4-way set (All / Teachers / Staff / EA) —
// this isn't personnel data, it mirrors the three source roles the merge
// itself is built from, so a fixed list here is legitimate, unlike
// department.
$includeOptions = ['all' => 'All Faculty', 'teacher' => 'Teachers Only', 'staff' => 'Staff Only', 'ea' => 'Executive Assistant'];

// ── APPLY FILTERS + SEARCH (server-side, against the fetched roster) ───
$deptFilter    = trim($_GET['dept'] ?? 'all');
$includeFilter = $_GET['include'] ?? 'all';
if (!in_array($includeFilter, array_keys($includeOptions), true)) $includeFilter = 'all';
$search        = trim($_GET['q'] ?? '');

$filteredRoster = $activeRoster;
if ($tab === 'faculty') {
    if ($includeFilter !== 'all') {
        // Filter on the underlying users.role, not role_label — role_label
        // is a display string ('Faculty', 'Teaching Staff', 'Staff',
        // 'Executive Assistant') and never literally equals 'Teacher' or
        // 'Staff', so comparing against it here would silently match
        // nothing.
        $wantRole = ['teacher' => 'teacher', 'staff' => 'staff', 'ea' => 'superadmin'][$includeFilter];
        $filteredRoster = array_values(array_filter($filteredRoster, fn($r) => $r['role'] === $wantRole));
    }
    if ($deptFilter !== 'all' && $deptFilter !== '') {
        $filteredRoster = array_values(array_filter($filteredRoster, fn($r) => ($r['department'] ?? '') === $deptFilter));
    }
}
if ($search !== '') {
    $needle = mb_strtolower($search);
    $filteredRoster = array_values(array_filter($filteredRoster, function ($r) use ($needle) {
        $haystack = mb_strtolower(($r['full_name'] ?? '') . ' ' . ($r['department'] ?? '') . ' ' . ($r['position'] ?? ''));
        return str_contains($haystack, $needle);
    }));
}

// ── EXPORT (CSV of the currently filtered roster) — must run before any
// HTML output ────────────────────────────────────────────────────────
if (($_GET['export'] ?? '') === 'csv') {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="dean_' . $tab . '_export_' . date('Ymd_His') . '.csv"');
    $out = fopen('php://output', 'w');
    fputcsv($out, ['Full Name', 'Department/Office', 'Position', 'Role', 'Evaluation Status', 'Last Evaluation Date']);
    foreach ($filteredRoster as $r) {
        fputcsv($out, [
            $r['full_name'], $r['department'] ?: '', $r['position'] ?: '', $r['role_label'],
            $r['evaluation_status'], $r['last_evaluation_date'] ?: '',
        ]);
    }
    fclose($out);
    $mysqli->close();
    exit;
}

// ── PAGINATION ───────────────────────────────────────────────────────
$perPage      = 5;
$totalFiltered = count($filteredRoster);
$totalPages    = max(1, (int)ceil($totalFiltered / $perPage));
$page          = max(1, min($totalPages, (int)($_GET['page'] ?? 1)));
$pageRoster    = array_slice($filteredRoster, ($page - 1) * $perPage, $perPage);
$showingFrom   = $totalFiltered === 0 ? 0 : (($page - 1) * $perPage) + 1;
$showingTo     = min($totalFiltered, $page * $perPage);

// Helper to rebuild the current query string with one param overridden —
// used by filter controls, search, and pagination links.
function dean_eval_qs(array $overrides = []): string {
    $params = array_merge($_GET, $overrides);
    // Changing a filter/search always resets back to page 1.
    if (!isset($overrides['page'])) $params['page'] = 1;
    return htmlspecialchars('?' . http_build_query($params));
}

$mysqli->close();
$photo_src = !empty($me['photo']) ? '../image/' . $me['photo'] : '../image/pbi_logo';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"/>
<meta name="viewport" content="width=device-width, initial-scale=1.0"/>
<title>PBI — Dean Evaluation</title>
<link href="https://fonts.googleapis.com/css2?family=Rajdhani:wght@600;700&family=DM+Sans:wght@400;500;600&display=swap" rel="stylesheet"/>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css"/>
<style>
:root{--dark:#0A192F;--mid:#172A45;--inner:#0F1F3D;--violet:#7C5FD9;--violet-h:#9C85F0;--violet-dark:#5F45B8;--light:#E0E6F0;--muted:#A0B3C6;--radius:10px;--shadow:0 8px 32px rgba(0,0,0,0.45);--danger:#f05454;--good:#10B981;}
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0;}
body{min-height:100vh;background:linear-gradient(rgba(5,18,36,.72),rgba(5,18,36,.82)),url('../background.png') center center / cover no-repeat fixed;background-color:var(--dark);font-family:'DM Sans',sans-serif;color:var(--light);display:flex;}

.sidebar{width:250px;flex-shrink:0;background:rgba(23,42,69,.9);border-right:1px solid rgba(255,255,255,.08);min-height:100vh;padding:28px 20px;display:flex;flex-direction:column;}
.sb-profile{text-align:center;margin-bottom:26px;}
.sb-photo{width:72px;height:72px;border-radius:50%;object-fit:cover;border:2.5px solid var(--violet);box-shadow:0 0 18px rgba(124,95,217,.4);margin:0 auto 10px;display:block;}
.sb-name{font-weight:700;font-size:15px;color:#fff;}
.sb-role{font-size:11px;color:var(--violet-h);text-transform:uppercase;letter-spacing:.6px;margin-top:2px;}
.sb-scope{font-size:10px;color:var(--muted);margin-top:4px;}
.sb-nav{display:flex;flex-direction:column;gap:4px;margin-top:10px;}
.sb-nav a{display:flex;align-items:center;gap:10px;padding:11px 14px;border-radius:8px;color:var(--muted);text-decoration:none;font-size:14px;font-weight:500;transition:background .2s,color .2s;}
.sb-nav a:hover,.sb-nav a.active{background:rgba(124,95,217,.15);color:#fff;}
.sb-nav a i{width:18px;text-align:center;color:var(--violet-h);}
.sb-logout{margin-top:auto;}
.sb-logout a{display:flex;align-items:center;gap:10px;padding:11px 14px;border-radius:8px;color:#fca5a5;text-decoration:none;font-size:14px;font-weight:500;transition:background .2s;}
.sb-logout a:hover{background:rgba(240,84,84,.12);}

.main{flex:1;padding:36px 44px;}
.page-header{display:flex;justify-content:space-between;align-items:center;margin-bottom:24px;flex-wrap:wrap;gap:14px;}
.page-title{font-family:'Rajdhani',sans-serif;font-size:28px;font-weight:700;color:#fff;letter-spacing:1px;}
.page-sub{font-size:13px;color:var(--muted);margin-top:4px;}

.period-badge{background:rgba(124,95,217,.14);border:1px solid rgba(124,95,217,.3);color:var(--violet-h);padding:6px 14px;border-radius:20px;font-size:12px;font-weight:600;display:flex;align-items:center;gap:7px;}
.period-badge.closed{background:rgba(240,84,84,.1);border-color:rgba(240,84,84,.3);color:#fca5a5;}
.period-badge.amber{background:rgba(217,119,6,.14);border-color:rgba(217,119,6,.3);color:#fbbf24;}
.period-badge.gray{background:rgba(255,255,255,.08);border-color:rgba(255,255,255,.12);color:var(--muted);}

.structure-note{display:flex;align-items:flex-start;gap:14px;padding:18px 20px;background:rgba(124,95,217,.08);border:1px solid rgba(124,95,217,.25);border-radius:12px;margin-bottom:26px;}
.structure-note i{color:var(--violet-h);font-size:20px;margin-top:2px;}
.structure-note p{font-size:13px;color:var(--light);line-height:1.6;}
.structure-note p b{color:#fff;}

.card-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:16px;margin-bottom:28px;}
.stat-card{background:rgba(23,42,69,.85);border:1px solid rgba(255,255,255,.08);border-radius:14px;padding:18px 20px;box-shadow:var(--shadow);}
.stat-card i{color:var(--violet-h);font-size:18px;margin-bottom:8px;}
.stat-card .num{font-size:24px;font-weight:700;color:#fff;}
.stat-card .label{font-size:11.5px;color:var(--muted);margin-top:4px;}

.eval-tabs{display:flex;gap:4px;background:var(--mid);border:1px solid rgba(255,255,255,.08);border-radius:12px;padding:4px;margin-bottom:22px;width:fit-content;flex-wrap:wrap;}
.eval-tab{padding:10px 22px;border-radius:8px;font-size:13px;font-weight:600;color:var(--muted);text-decoration:none;display:flex;align-items:center;gap:8px;transition:all .2s;}
.eval-tab.active{background:var(--violet);color:#fff;}
.eval-tab:not(.active):hover{background:rgba(255,255,255,.05);color:var(--light);}
.eval-tab .badge{background:rgba(255,255,255,.15);border-radius:20px;padding:1px 8px;font-size:11px;font-weight:700;}
.eval-tab.active .badge{background:rgba(255,255,255,.25);}

.filter-bar{display:flex;align-items:flex-end;gap:20px;flex-wrap:wrap;background:rgba(23,42,69,.85);border:1px solid rgba(255,255,255,.08);border-radius:14px;padding:18px 20px;margin-bottom:18px;box-shadow:var(--shadow);}
.filter-field{display:flex;flex-direction:column;gap:6px;}
.filter-field label{font-size:10.5px;font-weight:700;color:var(--muted);text-transform:uppercase;letter-spacing:.5px;}
.filter-field select{background:var(--inner);border:1px solid rgba(255,255,255,.12);color:var(--light);padding:9px 12px;border-radius:8px;font-size:13px;min-width:190px;}
.filter-hint{font-size:10.5px;color:var(--muted);margin-top:2px;}
.search-wrap{flex:1;min-width:220px;position:relative;}
.search-wrap input{width:100%;background:var(--inner);border:1px solid rgba(255,255,255,.12);color:var(--light);padding:9px 36px 9px 12px;border-radius:8px;font-size:13px;}
.search-wrap i{position:absolute;right:12px;top:50%;transform:translateY(-50%);color:var(--muted);font-size:13px;}
.export-btn{background:rgba(124,95,217,.14);border:1px solid rgba(124,95,217,.35);color:var(--violet-h);padding:10px 16px;border-radius:8px;font-size:13px;font-weight:600;cursor:pointer;text-decoration:none;display:inline-flex;align-items:center;gap:7px;white-space:nowrap;transition:background .2s;align-self:flex-end;}
.export-btn:hover{background:rgba(124,95,217,.22);}

.table-wrap{background:rgba(23,42,69,.85);border:1px solid rgba(255,255,255,.08);border-radius:14px;overflow:hidden;box-shadow:var(--shadow);}
table{width:100%;border-collapse:collapse;}
thead tr{background:var(--inner);}
thead th{padding:12px 16px;font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.7px;color:var(--muted);text-align:left;white-space:nowrap;}
tbody tr{border-bottom:1px solid rgba(255,255,255,.05);}
tbody tr:last-child{border-bottom:none;}
tbody tr:hover{background:rgba(124,95,217,.06);}
tbody td{padding:13px 16px;font-size:13.5px;vertical-align:middle;}
.person-cell{display:flex;align-items:center;gap:10px;}
.person-photo{width:36px;height:36px;border-radius:50%;object-fit:cover;background:var(--inner);flex-shrink:0;}
.person-name{font-weight:600;color:#fff;}
.muted-cell{color:var(--muted);font-size:12.5px;}
.role-pill{display:inline-block;padding:2px 9px;border-radius:20px;font-size:11px;font-weight:700;background:rgba(255,255,255,.08);color:var(--light);}

.status-pill{display:inline-flex;align-items:center;gap:5px;padding:3px 10px;border-radius:20px;font-size:11px;font-weight:700;}
.status-pill.completed{background:rgba(16,185,129,.14);color:var(--good);}
.status-pill.in_progress{background:rgba(124,95,217,.14);color:var(--violet-h);}
.status-pill.not_started{background:rgba(160,179,198,.14);color:var(--muted);}

.btn-eval{background:var(--violet);border:none;color:#fff;padding:7px 14px;border-radius:7px;font-size:12.5px;font-weight:600;cursor:pointer;text-decoration:none;display:inline-flex;align-items:center;gap:6px;transition:background .2s;}
.btn-eval:hover{background:var(--violet-dark);}
.btn-view{background:transparent;border:1px solid rgba(255,255,255,.14);color:var(--muted);padding:7px 14px;border-radius:7px;font-size:12.5px;font-weight:600;cursor:pointer;text-decoration:none;display:inline-flex;align-items:center;gap:6px;transition:all .2s;margin-left:6px;}
.btn-view:hover{color:var(--light);border-color:rgba(255,255,255,.3);}

.empty-state{text-align:center;padding:56px 20px;color:var(--muted);}
.empty-state i{font-size:38px;margin-bottom:14px;display:block;opacity:.25;}

.table-footer{display:flex;justify-content:space-between;align-items:center;padding:14px 20px;font-size:12.5px;color:var(--muted);flex-wrap:wrap;gap:10px;}
.pagination{display:flex;align-items:center;gap:6px;}
.page-btn{width:30px;height:30px;display:flex;align-items:center;justify-content:center;border-radius:7px;background:var(--inner);border:1px solid rgba(255,255,255,.1);color:var(--muted);text-decoration:none;font-size:12.5px;font-weight:600;}
.page-btn.active{background:var(--violet);color:#fff;border-color:var(--violet);}
.page-btn.disabled{opacity:.35;pointer-events:none;}

.stub-note{font-size:11.5px;color:var(--violet-h);background:rgba(124,95,217,.08);border:1px dashed rgba(124,95,217,.35);border-radius:8px;padding:10px 14px;margin-bottom:20px;}

@media(max-width:768px){body{flex-direction:column;}.sidebar{width:100%;min-height:auto;}}
</style>
</head>
<body>

<?php
$active = 'evaluation';
$sidebarScope = HIGHER_ED_LABEL . ' Division';
include __DIR__ . '/includes/dean_sidebar.php';
?>

<main class="main">
    <div class="page-header">
        <div>
            <div class="page-title">Evaluation</div>
            <div class="page-sub">Pandan Bay Institute — <?= HIGHER_ED_LABEL ?> Division</div>
        </div>
        <div class="period-badge <?= htmlspecialchars($settings['status']['cls']) ?>">
            <i class="fa-solid fa-calendar-check"></i>
            <?= htmlspecialchars($settings['academic_year']) ?> · <?= HIGHER_ED_LABEL ?> · <?= htmlspecialchars($settings['academic_term']) ?>
            — <?= htmlspecialchars($settings['status']['label']) ?>
        </div>
    </div>

    <?php if (!$structureActive): ?>
    <div class="structure-note">
        <i class="fa-solid fa-circle-info"></i>
        <p>
            <b><?= HIGHER_ED_LABEL ?> is not the active academic structure.</b><br>
            The current evaluation period is configured for <b><?= htmlspecialchars($settings['academic_structure_label']) ?></b>.
            Evaluation is unavailable until the Executive Assistant switches it back.
        </p>
    </div>
    <?php else: ?>

    <!-- SUMMARY CARDS -->
    <div class="card-grid">
        <div class="stat-card"><i class="fa-solid fa-users"></i><div class="num"><?= $facultyToEvaluate ?></div><div class="label">Faculty to Evaluate (Teachers, Staff &amp; EA)</div></div>
        <div class="stat-card"><i class="fa-solid fa-circle-check"></i><div class="num"><?= $completedEvaluations ?></div><div class="label">Completed Evaluations</div></div>
        <div class="stat-card"><i class="fa-solid fa-hourglass-half"></i><div class="num"><?= $pendingEvaluations ?></div><div class="label">Pending Evaluations</div></div>
        <div class="stat-card"><i class="fa-solid fa-chart-simple"></i><div class="num"><?= $completionPct ?>%</div><div class="label">Completion Percentage</div></div>
    </div>

    <?php if ($totalAssigned > 0 && $totalCompleted === 0): ?>
    <div class="stub-note">
        <i class="fa-solid fa-clock-rotate-left"></i>
        Faculty rosters are live, but Dean-evaluation status tracking isn't wired up yet — everyone shows "Not Started" until the evaluation tracker's eval_type for Dean-initiated evaluations is confirmed. "Evaluate" links route to a placeholder questionnaire form until that's confirmed too.
    </div>
    <?php endif; ?>

    <!-- ROSTER TABLE -->
    <div class="table-wrap">
    <table>
        <thead>
            <tr>
                <th>Profile</th>
                <th>Full Name</th>
                <th>Department / Office</th><th>Position</th><th>Role</th>
                <th>Evaluation Status</th>
                <th>Last Evaluation Date</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
        <?php $colspan = 8; ?>
        <?php if (empty($pageRoster)): ?>
        <tr><td colspan="<?= $colspan ?>">
            <div class="empty-state">
                <i class="fa-solid fa-user-slash"></i>
                <p>No <?= strtolower($tabLabels[$tab]) ?> match the current filters.</p>
            </div>
        </td></tr>
        <?php else: foreach ($pageRoster as $p): ?>
        <tr>
            <td><img class="person-photo" src="<?= !empty($p['photo']) ? htmlspecialchars('../image/' . $p['photo']) : '../image/pbi_logo' ?>" alt=""/></td>
            <td><span class="person-name"><?= htmlspecialchars($p['full_name']) ?></span></td>
            <td class="muted-cell"><?= htmlspecialchars($p['department'] ?: '—') ?></td>
            <td class="muted-cell"><?= htmlspecialchars($p['position'] ?: '—') ?></td>
            <!-- Confidentiality rule (Phase 2, §3): the EA's raw role
                 (role='superadmin') is never rendered directly — role_label
                 already carries the public-facing "Executive Assistant"
                 string for that row, same as every other role_label here. -->
            <td><span class="role-pill"><?= htmlspecialchars($p['role_label']) ?></span></td>
            <td>
                <span class="status-pill <?= htmlspecialchars($p['evaluation_status']) ?>">
                    <?php if ($p['evaluation_status'] === 'completed'): ?><i class="fa-solid fa-check" style="font-size:9px;"></i> Completed
                    <?php elseif ($p['evaluation_status'] === 'in_progress'): ?><i class="fa-solid fa-spinner" style="font-size:9px;"></i> In Progress
                    <?php else: ?><i class="fa-solid fa-hourglass-half" style="font-size:9px;"></i> Not Started
                    <?php endif; ?>
                </span>
            </td>
            <td class="muted-cell"><?= $p['last_evaluation_date'] ? htmlspecialchars(date('M j, Y', strtotime($p['last_evaluation_date']))) : '—' ?></td>
            <td>
                <?php if (!$evalOpen): ?>
                    <span class="muted-cell">Evaluation closed</span>
                <?php else: ?>
                    <?php
                    // Link straight to dean_evaluate.php — NOT ea_questionnaire_route()
                    // (that helper is a stub that builds a broken placeholder URL, see
                    // shared/ea_personnel_service.php). dean_evaluate.php pulls its
                    // questions directly from the questionnaire's Student Evaluation ->
                    // Faculty (Teacher) pool (evaluation_questions, eval_type='student',
                    // target_type='Teacher') — the same shared pool for everyone in this
                    // roster, Faculty/Staff/EA alike — so the roster and the evaluate
                    // form stay in sync off one source of truth.
                    $evalUrl  = 'dean_evaluate.php?tab=' . urlencode($p['route_tab']) . '&user_id=' . (int)$p['id'];
                    ?>
                    <a class="btn-eval" href="<?= htmlspecialchars($evalUrl) ?>">
                        <i class="fa-solid fa-pen"></i> Evaluate
                    </a>
                    <?php if ($p['evaluation_status'] === 'completed'): ?>
                    <a class="btn-view" href="<?= htmlspecialchars($evalUrl) ?>&view=1">
                        <i class="fa-solid fa-eye"></i> View
                    </a>
                    <?php endif; ?>
                <?php endif; ?>
            </td>
        </tr>
        <?php endforeach; endif; ?>
        </tbody>
    </table>
    <?php if ($totalFiltered > 0): ?>
    <div class="table-footer">
        <div>Showing <?= $showingFrom ?> to <?= $showingTo ?> of <?= $totalFiltered ?> <?= strtolower($tabLabels[$tab]) ?> member<?= $totalFiltered === 1 ? '' : 's' ?></div>
        <?php if ($totalPages > 1): ?>
        <div class="pagination">
            <a class="page-btn <?= $page <= 1 ? 'disabled' : '' ?>" href="<?= dean_eval_qs(['page' => max(1, $page - 1)]) ?>"><i class="fa-solid fa-chevron-left"></i></a>
            <?php for ($i = 1; $i <= $totalPages; $i++): ?>
            <a class="page-btn <?= $i === $page ? 'active' : '' ?>" href="<?= dean_eval_qs(['page' => $i]) ?>"><?= $i ?></a>
            <?php endfor; ?>
            <a class="page-btn <?= $page >= $totalPages ? 'disabled' : '' ?>" href="<?= dean_eval_qs(['page' => min($totalPages, $page + 1)]) ?>"><i class="fa-solid fa-chevron-right"></i></a>
        </div>
        <?php endif; ?>
    </div>
    <?php endif; ?>
    </div>
    <?php if ($tab === 'faculty'): ?>
    <p class="filter-hint" style="margin-top:10px;">Faculty includes all teaching and non-teaching personnel (Teachers and Staff) plus the Executive Assistant, under Higher Education Division.</p>
    <?php endif; ?>

    <?php endif; ?>
</main>
</body>
</html>