<?php
// dean/dean_evaluation.php
// Dean evaluation roster — mirrors Questionnaire -> Dean / Principal Evaluation -> Dean.
// Source of truth:
//   Faculty -> evaluation_questions (school_head / dean / Faculty) shared bank
//   Staff   -> user_questions (school_head / Staff) per-person assignments
//   EA      -> evaluation_questions (school_head / dean / EA) shared bank

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
require_once dirname(__DIR__) . '/shared/QuestionnaireService.php';
require_once 'school_head_structure_gate.php';
qn_migrate_legacy_once($mysqli);

if (empty($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'dean') {
    header('Location: dean_login.php');
    exit;
}
$deanId = (int)$_SESSION['user_id'];

$stmt = $mysqli->prepare("SELECT full_name, username, email, designation, photo, department FROM users WHERE id=? LIMIT 1");
$stmt->bind_param('i', $deanId);
$stmt->execute();
$me = $stmt->get_result()->fetch_assoc() ?: [];
$stmt->close();

$settings = get_school_head_settings($mysqli, 'dean');
// Academic Structure / Academic Term gate: Dean owns College, Principal
// owns School Year. Narrow-only — existing scheduling, Force Open and
// Force Closed still decide open/closed while College is active.
$settings = sh_gate_apply($settings, 'dean');
$structureActive = !empty($settings['school_head_applicable']);
$period_id_int   = (int)($settings['period_id'] ?? 0);
$evalOpen        = !empty($settings['school_head_is_open']);
const HIGHER_ED_LABEL = 'Higher Education';

$validTabs = ['faculty', 'staff', 'executive_assistant'];
$tab = $_GET['tab'] ?? 'faculty';
if (!in_array($tab, $validTabs, true)) $tab = 'faculty';

$tabLabels = [
    'faculty' => 'Faculty',
    'staff' => 'Staff',
    'executive_assistant' => 'Executive Assistant',
];
$tabIcons = [
    'faculty' => 'fa-users',
    'staff' => 'fa-briefcase',
    'executive_assistant' => 'fa-user-tie',
];

$facultyUsers = [];
$staffUsers = [];
$eaUsers = [];
$rosterByTab = ['faculty' => [], 'staff' => [], 'executive_assistant' => []];
$questionCounts = ['faculty' => 0, 'staff' => 0, 'executive_assistant' => 0];
$doneByTarget = [];

function dean_roster_levels(mysqli $mysqli, int $userId): array {
    // IMPORTANT: level VALUES come from user_year_levels ONLY — see the
    // matching note in principal_evaluations.php's principal_roster_levels().
    // teaching_assignments accumulates a new row on every reassignment
    // without clearing the previous one (confirmed in prod: a
    // since-reassigned-to-College user still carried a stale Grade 7 row
    // from before the change), so it cannot be trusted for "which level
    // is this person currently assigned to." It's still fine as a coarse
    // existence signal (dean_has_any_teaching_assignment() below).
    $levels = [];
    $stmt = $mysqli->prepare("SELECT year_level FROM user_year_levels WHERE user_id=?");
    if ($stmt) {
        $stmt->bind_param('i', $userId);
        $stmt->execute();
        $res = $stmt->get_result();
        while ($r = $res->fetch_assoc()) {
            $v = trim((string)($r['year_level'] ?? ''));
            if ($v !== '') $levels[] = $v;
        }
        $stmt->close();
    }
    return array_values(array_unique($levels));
}

function dean_is_college_level(string $level): bool {
    $level = trim($level);
    return stripos($level, 'college') !== false
        || (bool)preg_match('/^(1st|2nd|3rd|4th)\s*Year\b/i', $level);
}

// IMPORTANT: a Teacher role by itself is NOT enough for Dean Faculty eligibility.
// The person must have a real teaching/year-level assignment, and that assignment
// must include a College level. This prevents users such as Amelia ("Not assigned yet")
// from appearing in the Dean Faculty roster.
function dean_has_any_teaching_assignment(mysqli $mysqli, int $userId): bool {
    $stmt = $mysqli->prepare("SELECT 1 FROM teaching_assignments WHERE user_id=? UNION ALL SELECT 1 FROM user_year_levels WHERE user_id=? LIMIT 1");
    if (!$stmt) return false;
    $stmt->bind_param('ii', $userId, $userId);
    $stmt->execute();
    $ok = (bool)$stmt->get_result()->fetch_row();
    $stmt->close();
    return $ok;
}

function dean_is_non_teaching_staff(mysqli $mysqli, array $u): bool {
    if (($u['role'] ?? '') !== 'staff') return false;
    return !dean_has_any_teaching_assignment($mysqli, (int)$u['id']);
}

if ($structureActive) {
    $ures = $mysqli->query("SELECT id, full_name, designation, photo, role, secondary_role, sector, department FROM users WHERE role IN ('teacher','staff') AND is_active=1 AND account_status='approved' ORDER BY full_name ASC");
    if ($ures) {
        while ($u = $ures->fetch_assoc()) {
            $uid = (int)$u['id'];
            $levels = dean_roster_levels($mysqli, $uid);
            $hasTeaching = dean_has_any_teaching_assignment($mysqli, $uid);

            // Non-teaching Staff belong only in the Staff tab.
            if (dean_is_non_teaching_staff($mysqli, $u)) {
                $u['role_label'] = 'Staff';
                $u['eval_bucket'] = 'Staff';
                $u['question_count'] = 0;
                $staffUsers[] = $u;
                continue;
            }

            // Dean Faculty = actual College-assigned teaching personnel only.
            if ($hasTeaching && count(array_filter($levels, 'dean_is_college_level')) > 0) {
                $u['role_label'] = ($u['role'] === 'staff') ? 'Teaching Staff' : 'Faculty';
                $u['eval_bucket'] = 'Faculty';
                $u['question_count'] = 0;
                $facultyUsers[] = $u;
            }
        }
    }

    $eaRes = $mysqli->query("SELECT id, full_name, designation, photo, role, department FROM users WHERE role='superadmin' AND is_active=1 AND account_status='approved' ORDER BY updated_at DESC, id DESC LIMIT 1");
    if ($eaRes && ($ea = $eaRes->fetch_assoc())) {
        $ea['role_label'] = 'Executive Assistant';
        $ea['eval_bucket'] = 'EA';
        $ea['question_count'] = 0;
        $eaUsers[] = $ea;
    }

    $questionCounts['faculty'] = (int)($mysqli->query("SELECT COUNT(*) c FROM evaluation_questions WHERE eval_type='general' AND evaluator_role='shared' AND target_type='Faculty'")->fetch_assoc()['c'] ?? 0);
    $questionCounts['executive_assistant'] = (int)($mysqli->query("SELECT COUNT(*) c FROM user_questions WHERE eval_type='general' AND target_type='EA'")->fetch_assoc()['c'] ?? 0);

    if (!empty($staffUsers)) {
        $ids = array_map(fn($u) => (int)$u['id'], $staffUsers);
        $ph = implode(',', array_fill(0, count($ids), '?'));
        $types = str_repeat('i', count($ids));
        $stmt = $mysqli->prepare("SELECT user_id, COUNT(*) AS total FROM user_questions WHERE eval_type='general' AND target_type='Staff' AND user_id IN ($ph) GROUP BY user_id");
        if ($stmt) {
            $stmt->bind_param($types, ...$ids);
            $stmt->execute();
            $res = $stmt->get_result();
            while ($r = $res->fetch_assoc()) {
                foreach ($staffUsers as &$u) {
                    if ((int)$u['id'] === (int)$r['user_id']) $u['question_count'] = (int)$r['total'];
                }
                unset($u);
            }
            $stmt->close();
        }
        $questionCounts['staff'] = array_sum(array_map(fn($u) => (int)$u['question_count'], $staffUsers));
    }

    foreach ($facultyUsers as &$u) $u['question_count'] = $questionCounts['faculty'];
    unset($u);
    foreach ($eaUsers as &$u) $u['question_count'] = $questionCounts['executive_assistant'];
    unset($u);

    // Completed status is keyed to this Dean + current active period.
    if ($period_id_int > 0) {
        $stmt = $mysqli->prepare("SELECT target_user_id, submitted_at FROM evaluation_tracker WHERE evaluator_id=? AND eval_type='school_head' AND period_id=? AND status IN ('submitted','approved') ORDER BY submitted_at DESC, id DESC");
        if ($stmt) {
            $stmt->bind_param('ii', $deanId, $period_id_int);
            $stmt->execute();
            $res = $stmt->get_result();
            while ($r = $res->fetch_assoc()) {
                $uid = (int)$r['target_user_id'];
                if (!isset($doneByTarget[$uid])) $doneByTarget[$uid] = $r['submitted_at'];
            }
            $stmt->close();
        }
    }
}

foreach ([$facultyUsers, $staffUsers, $eaUsers] as $list) {
    // no-op; arrays are assembled above for clarity
}

function dean_decorate_status(array $rows, array $doneByTarget): array {
    foreach ($rows as &$r) {
        $uid = (int)$r['id'];
        $r['evaluation_status'] = isset($doneByTarget[$uid]) ? 'completed' : 'not_started';
        $r['last_evaluation_date'] = $doneByTarget[$uid] ?? null;
        $r['route_tab'] = $r['eval_bucket'] === 'Staff' ? 'staff' : ($r['eval_bucket'] === 'EA' ? 'executive_assistant' : 'faculty');
    }
    unset($r);
    return $rows;
}

$facultyUsers = dean_decorate_status($facultyUsers, $doneByTarget);
$staffUsers = dean_decorate_status($staffUsers, $doneByTarget);
$eaUsers = dean_decorate_status($eaUsers, $doneByTarget);

usort($facultyUsers, fn($a,$b) => strcasecmp($a['full_name'], $b['full_name']));
usort($staffUsers, fn($a,$b) => strcasecmp($a['full_name'], $b['full_name']));

$rosterByTab = [
    'faculty' => $facultyUsers,
    'staff' => $staffUsers,
    'executive_assistant' => $eaUsers,
];
$activeRoster = $rosterByTab[$tab];

$deptFilter = trim($_GET['dept'] ?? 'all');
$search = trim($_GET['q'] ?? '');
$filteredRoster = $activeRoster;

$departmentOptions = [];
foreach ($activeRoster as $r) {
    $d = trim((string)($r['department'] ?? ''));
    if ($d !== '') $departmentOptions[$d] = true;
}
$departmentOptions = array_keys($departmentOptions);
sort($departmentOptions);

if ($deptFilter !== 'all' && $deptFilter !== '') {
    $filteredRoster = array_values(array_filter($filteredRoster, fn($r) => (string)($r['department'] ?? '') === $deptFilter));
}
if ($search !== '') {
    $needle = mb_strtolower($search);
    $filteredRoster = array_values(array_filter($filteredRoster, function($r) use ($needle) {
        $hay = mb_strtolower(($r['full_name'] ?? '') . ' ' . ($r['department'] ?? '') . ' ' . ($r['designation'] ?? ''));
        return str_contains($hay, $needle);
    }));
}

if (($_GET['export'] ?? '') === 'csv') {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="dean_' . $tab . '_export_' . date('Ymd_His') . '.csv"');
    $out = fopen('php://output', 'w');
    fputcsv($out, ['Full Name','Department/Office','Position','Role','Questions','Evaluation Status','Last Evaluation Date']);
    foreach ($filteredRoster as $r) {
        fputcsv($out, [$r['full_name'], $r['department'] ?? '', $r['designation'] ?? '', $r['role_label'], (int)($r['question_count'] ?? 0), $r['evaluation_status'], $r['last_evaluation_date'] ?? '']);
    }
    fclose($out);
    $mysqli->close();
    exit;
}

$perPage = 5;
$totalFiltered = count($filteredRoster);
$totalPages = max(1, (int)ceil($totalFiltered / $perPage));
$page = max(1, min($totalPages, (int)($_GET['page'] ?? 1)));
$pageRoster = array_slice($filteredRoster, ($page - 1) * $perPage, $perPage);
$showingFrom = $totalFiltered === 0 ? 0 : (($page - 1) * $perPage) + 1;
$showingTo = min($totalFiltered, $page * $perPage);

$allTargets = array_merge($facultyUsers, $staffUsers, $eaUsers);
$totalAssigned = count($allTargets);
$totalCompleted = count(array_filter($allTargets, fn($r) => $r['evaluation_status'] === 'completed'));
$pendingEvaluations = max(0, $totalAssigned - $totalCompleted);
$completionPct = $totalAssigned > 0 ? (int)round($totalCompleted / $totalAssigned * 100) : 0;

function dean_eval_qs(array $overrides = []): string {
    $params = array_merge($_GET, $overrides);
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

.sidebar{width:250px;flex-shrink:0;background:#0F1F33;border-right:1px solid #060E18;min-height:100vh;padding:28px 20px;display:flex;flex-direction:column;}
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
.period-badge.scheduled{background:rgba(217,154,43,.12);border-color:rgba(217,154,43,.28);color:#d49a2a;}
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

.table-wrap{background:rgba(23,42,69,.85);border:1px solid rgba(255,255,255,.08);border-radius:14px;overflow-x:auto;overflow-y:hidden;box-shadow:var(--shadow);}
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

/* Deliberately NOT named .btn-eval/.btn-view: those class names are also
   claimed by includes/dean_light_theme.css as part of the portal-wide
   primary/secondary button system (solid violet vs. white-outline), and
   that stylesheet's rules carry !important, so it would silently win over
   anything defined here and put us back to the mismatched solid/outline
   look. This page wants its own soft-tint treatment instead, so it gets
   its own class names the shared theme never touches. */
.eval-action-primary{background:rgba(124,95,217,.14);border:1px solid rgba(124,95,217,.35);color:var(--violet-h);padding:9px 16px;border-radius:8px;font-size:13px;font-weight:600;cursor:pointer;text-decoration:none;display:inline-flex;align-items:center;gap:7px;transition:background .2s;}
.eval-action-primary:hover{background:rgba(124,95,217,.24);}
.eval-action-secondary{background:rgba(124,95,217,.14);border:1px solid rgba(124,95,217,.35);color:var(--violet-h);padding:9px 16px;border-radius:8px;font-size:13px;font-weight:600;cursor:pointer;text-decoration:none;display:inline-flex;align-items:center;gap:7px;margin-left:6px;opacity:.75;transition:background .2s,opacity .2s;}
.eval-action-secondary:hover{opacity:1;background:rgba(124,95,217,.24);}
/* Keep Evaluate + View on one line, matching the Principal roster. Without
   this, a long wrapped Department/Office value in an earlier column (e.g.
   a multi-line office title) steals width in the table's auto layout and
   pushes the last column narrow enough that the two buttons break onto
   separate rows. */
.actions-cell{white-space:nowrap;}
thead th:last-child{min-width:210px;}

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
<link rel="stylesheet" href="includes/dean_light_theme.css"/>
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
            <div class="page-title">My Evaluation</div>
            <div class="page-sub">Pandan Bay Institute — <?= HIGHER_ED_LABEL ?> Division</div>
        </div>
        <div class="period-badge <?= htmlspecialchars($settings['status']['cls']) ?>">
            <i class="fa-solid fa-calendar-check"></i>
            <?= htmlspecialchars($settings['academic_year']) ?> · <?= HIGHER_ED_LABEL ?> · <?= htmlspecialchars($settings['academic_term']) ?>
            — <?= htmlspecialchars($settings['status']['label']) ?>
        </div>
    </div>

    <div class="schedule-strip" style="display:grid;grid-template-columns:1fr 1fr 160px;gap:12px;margin:14px 0 18px;padding:14px 16px;border:1px solid rgba(255,255,255,.08);border-radius:12px;background:rgba(255,255,255,.035);">
        <div><div style="font-size:10px;text-transform:uppercase;letter-spacing:.08em;opacity:.65;font-weight:700;">Evaluation Opens</div><div style="margin-top:4px;font-size:15px;font-weight:700;"><?= $settings['eval_start_display'] !== '' ? htmlspecialchars($settings['eval_start_display']) : '—' ?></div></div>
        <div><div style="font-size:10px;text-transform:uppercase;letter-spacing:.08em;opacity:.65;font-weight:700;">Evaluation Closes</div><div style="margin-top:4px;font-size:15px;font-weight:700;"><?= $settings['eval_end_display'] !== '' ? htmlspecialchars($settings['eval_end_display']) : '—' ?></div></div>
        <div><div style="font-size:10px;text-transform:uppercase;letter-spacing:.08em;opacity:.65;font-weight:700;">Current State</div><div style="margin-top:4px;font-size:15px;font-weight:800;"><?= htmlspecialchars($settings['status']['label']) ?></div></div>
    </div>

    <?php if (!$structureActive): ?>
    <div class="structure-note">
        <i class="fa-solid fa-circle-info"></i>
        <p><b><?= HIGHER_ED_LABEL ?> is not the active academic structure.</b><br>
        The current evaluation period is configured for <b><?= htmlspecialchars($settings['academic_structure_label']) ?></b>.
        Evaluation is unavailable until the Executive Assistant switches it back.</p>
    </div>
    <?php else: ?>

    <div class="card-grid">
        <div class="stat-card"><i class="fa-solid fa-users"></i><div class="num"><?= count($facultyUsers) ?></div><div class="label">Faculty Targets</div></div>
        <div class="stat-card"><i class="fa-solid fa-briefcase"></i><div class="num"><?= count($staffUsers) ?></div><div class="label">Staff Targets</div></div>
        <div class="stat-card"><i class="fa-solid fa-user-tie"></i><div class="num"><?= count($eaUsers) ?></div><div class="label">Executive Assistant</div></div>
        <div class="stat-card"><i class="fa-solid fa-circle-check"></i><div class="num"><?= $totalCompleted ?></div><div class="label">Completed Evaluations</div></div>
        <div class="stat-card"><i class="fa-solid fa-hourglass-half"></i><div class="num"><?= $pendingEvaluations ?></div><div class="label">Pending Evaluations</div></div>
        <div class="stat-card"><i class="fa-solid fa-chart-simple"></i><div class="num"><?= $completionPct ?>%</div><div class="label">Completion Percentage</div></div>
    </div>

    <div class="eval-tabs">
        <?php foreach ($validTabs as $t): ?>
        <a class="eval-tab <?= $tab === $t ? 'active' : '' ?>" href="?tab=<?= urlencode($t) ?>">
            <i class="fa-solid <?= $tabIcons[$t] ?>"></i> <?= htmlspecialchars($tabLabels[$t]) ?>
            <span class="badge"><?= count($rosterByTab[$t]) ?></span>
        </a>
        <?php endforeach; ?>
    </div>

    <form class="filter-bar" method="get">
        <input type="hidden" name="tab" value="<?= htmlspecialchars($tab) ?>"/>
        <?php if (!empty($departmentOptions)): ?>
        <div class="filter-field">
            <label for="deptSelect">Department</label>
            <select id="deptSelect" name="dept" onchange="this.form.submit()">
                <option value="all" <?= $deptFilter === 'all' ? 'selected' : '' ?>>All Departments</option>
                <?php foreach ($departmentOptions as $d): ?>
                <option value="<?= htmlspecialchars($d) ?>" <?= $deptFilter === $d ? 'selected' : '' ?>><?= htmlspecialchars($d) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <?php endif; ?>
        <div class="search-wrap">
            <label style="font-size:10.5px;font-weight:700;color:var(--muted);text-transform:uppercase;letter-spacing:.5px;display:block;margin-bottom:6px;">Search</label>
            <input type="text" name="q" value="<?= htmlspecialchars($search) ?>" placeholder="Search by name, department, position..."/>
            <i class="fa-solid fa-magnifying-glass"></i>
        </div>
    </form>

    <div class="table-wrap">
    <table>
        <thead><tr>
            <th>Profile</th><th>Full Name</th><th>Department / Office</th><th>Position</th><th>Role</th><th>Questions</th><th>Evaluation Status</th><th>Last Evaluation Date</th><th>Actions</th>
        </tr></thead>
        <tbody>
        <?php $colspan = 9; ?>
        <?php if (empty($pageRoster)): ?>
        <tr><td colspan="<?= $colspan ?>"><div class="empty-state"><i class="fa-solid fa-user-slash"></i><p>No <?= strtolower($tabLabels[$tab]) ?> match the current filters.</p></div></td></tr>
        <?php else: foreach ($pageRoster as $p): ?>
        <tr>
            <td><img class="person-photo" src="<?= !empty($p['photo']) ? htmlspecialchars('../image/' . $p['photo']) : '../image/pbi_logo' ?>" alt=""/></td>
            <td><span class="person-name"><?= htmlspecialchars($p['full_name']) ?></span></td>
            <td class="muted-cell"><?= htmlspecialchars($p['department'] ?: '—') ?></td>
            <td class="muted-cell"><?= htmlspecialchars($p['designation'] ?: $p['role_label']) ?></td>
            <td><span class="role-pill"><?= htmlspecialchars($p['role_label']) ?></span></td>
            <td><span class="role-pill"><?= (int)($p['question_count'] ?? 0) ?></span></td>
            <td>
                <span class="status-pill <?= htmlspecialchars($p['evaluation_status']) ?>">
                <?php if ($p['evaluation_status'] === 'completed'): ?><i class="fa-solid fa-check" style="font-size:9px;"></i> Completed
                <?php else: ?><i class="fa-solid fa-hourglass-half" style="font-size:9px;"></i> Not Started<?php endif; ?>
                </span>
            </td>
            <td class="muted-cell"><?= $p['last_evaluation_date'] ? htmlspecialchars(date('M j, Y', strtotime($p['last_evaluation_date']))) : '—' ?></td>
            <td class="actions-cell">
                <?php if (!$evalOpen || $period_id_int <= 0): ?>
                    <span class="muted-cell">Evaluation closed</span>
                <?php else: ?>
                    <?php $evalUrl = 'dean_evaluate.php?tab=' . urlencode($p['route_tab']) . '&user_id=' . (int)$p['id']; ?>
                    <a class="eval-action-primary" href="<?= htmlspecialchars($evalUrl) ?>"><i class="fa-solid fa-pen-to-square"></i> Evaluate</a>
                    <?php if ($p['evaluation_status'] === 'completed'): ?>
                    <a class="eval-action-secondary" href="<?= htmlspecialchars($evalUrl) ?>&view=1"><i class="fa-solid fa-eye"></i> View</a>
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
        <?php if ($totalPages > 1): ?><div class="pagination">
            <a class="page-btn <?= $page <= 1 ? 'disabled' : '' ?>" href="<?= dean_eval_qs(['page' => max(1,$page-1)]) ?>"><i class="fa-solid fa-chevron-left"></i></a>
            <?php for ($i=1; $i<=$totalPages; $i++): ?><a class="page-btn <?= $i === $page ? 'active' : '' ?>" href="<?= dean_eval_qs(['page'=>$i]) ?>"><?= $i ?></a><?php endfor; ?>
            <a class="page-btn <?= $page >= $totalPages ? 'disabled' : '' ?>" href="<?= dean_eval_qs(['page' => min($totalPages,$page+1)]) ?>"><i class="fa-solid fa-chevron-right"></i></a>
        </div><?php endif; ?>
    </div>
    <?php endif; ?>
    </div>

    <?php if ($tab === 'faculty'): ?>
    <p class="filter-hint" style="margin-top:10px;">Matches Questionnaire → Dean / Principal Evaluation → Dean → Faculty: only College-assigned teaching personnel. All Faculty use the Dean shared Faculty question bank.</p>
    <?php elseif ($tab === 'staff'): ?>
    <p class="filter-hint" style="margin-top:10px;">Matches Questionnaire → Dean / Principal Evaluation → Dean → Staff: non-teaching Staff only. Each Staff member uses that person's individually assigned questions.</p>
    <?php else: ?>
    <p class="filter-hint" style="margin-top:10px;">Matches Questionnaire → Dean / Principal Evaluation → Dean → EA: the active Executive Assistant uses the Dean EA shared question bank.</p>
    <?php endif; ?>

    <?php endif; ?>
</main>
</body>
<link rel="stylesheet" href="includes/dean_light_theme.css" id="dean-light-theme-final"/>
</html>
