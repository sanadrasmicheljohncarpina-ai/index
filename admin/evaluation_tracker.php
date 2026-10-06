<?php
// Student Evaluation Tracker
session_start();
require_once 'db.php';
require_once dirname(__DIR__) . '/shared/system_settings_service.php';

require_once '../shared/EvaluationContextService.php';

if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'] ?? '', ['admin','superadmin'], true)) {
    header('Location: admin_login.php');
    exit;
}

if ($_SESSION['role'] === 'admin') {
    require_once 'permissions.php';
    if (!admin_can_edit($mysqli, 'reports_analytics')) {
        die("You don't have access to this feature. Ask a Super Admin to enable it.");
    }
}

// Apply the schedule before reading evaluation_periods.is_active.
ss_sync_from_database($mysqli);


/* ───────── Student "required evaluations" ─────────
   This mirrors the student portal (student/student_dashboard.php) rule for rule, so the
   tracker's REQUIRED number always equals what the student sees under Assigned Evaluations:
     • Faculty = teachers (+ staff who carry a teaching/year-level assignment) whose assignment
                 matches the student's level + year level; College students additionally need the
                 teacher's assigned_period to equal the active period's semester.
     • Staff   = staff accounts with NO teaching/year-level assignment (institution-wide).
     • School head = Dean for College students, Principal for Junior/Senior High students (one each).
   Each required item is keyed "userId|context" (context: teacher | staff | school_head).        */
function levelVariants(string $level): array {
    $k = strtolower(trim($level));
    $college = ['college','higher education','college / university','college/university'];
    $map = [
        'elementary' => ['basic education'], 'junior_high' => ['basic education'],
        'senior_high' => ['basic education'], 'basic education' => ['basic education'],
        'college' => $college, 'higher education' => $college,
        'college / university' => $college, 'college/university' => $college,
    ];
    return array_values(array_unique(array_map('strtolower', $map[$k] ?? [$k])));
}

function trackerIsCollegeLevel(string $level): bool {
    return in_array(strtolower(trim($level)), ['college','higher education','college / university','college/university'], true);
}

/* Loaded once per request: every possible evaluatee plus their assignments. */
function trackerRoster(mysqli $db): array {
    static $roster = null;
    if ($roster !== null) return $roster;

    $assign = []; // uid => ['ta' => [[edu, year], ...], 'uyl' => [year, ...]]
    $q = $db->query("SELECT user_id, LOWER(TRIM(education_level)) AS e, LOWER(TRIM(year_level)) AS y FROM teaching_assignments");
    if ($q) { while ($r = $q->fetch_assoc()) { $assign[(int)$r['user_id']]['ta'][] = [(string)$r['e'], (string)$r['y']]; } $q->free(); }
    $q = $db->query("SELECT user_id, LOWER(TRIM(year_level)) AS y FROM user_year_levels");
    if ($q) { while ($r = $q->fetch_assoc()) { $assign[(int)$r['user_id']]['uyl'][] = (string)$r['y']; } $q->free(); }

    $staff = [];
    $q = $db->query("SELECT id, role, secondary_role, assigned_period FROM users
                     WHERE role IN ('teacher','staff','faculty') AND is_active=1
                       AND (account_status='approved' OR source='admin_nologin')");
    if ($q) { while ($u = $q->fetch_assoc()) { $u['id'] = (int)$u['id']; $staff[] = $u; } $q->free(); }

    $heads = ['principal' => null, 'dean' => null];
    foreach (array_keys($heads) as $role) {
        $st = $db->prepare("SELECT id FROM users WHERE role=? AND is_active=1 LIMIT 1");
        if ($st) { $st->bind_param('s', $role); $st->execute(); $row = $st->get_result()->fetch_assoc(); $st->close(); if ($row) $heads[$role] = (int)$row['id']; }
    }
    return $roster = ['assign' => $assign, 'staff' => $staff, 'heads' => $heads];
}

function trackerRequiredKeys(mysqli $db, array $student, ?string $periodSemester): array {
    $roster = trackerRoster($db);
    $level = trim((string)($student['education_level'] ?? ''));
    $year  = trim((string)($student['year_level'] ?? ''));
    $yearKey = strtolower($year);
    $variants = levelVariants($level);
    $isCollege = trackerIsCollegeLevel($level);
    $isJhsShs  = in_array(strtolower($level), ['junior_high','senior_high'], true);
    $keys = [];

    foreach ($roster['staff'] as $u) {
        $uid = $u['id'];
        $a = $roster['assign'][$uid] ?? null;
        $hasAssign = $a !== null;
        $isTeacher = ec_has_teacher_function($u);
        $isStaff   = ec_has_staff_function($u);

        $match = false;
        if ($hasAssign && $level !== '' && $year !== '') {
            foreach (($a['ta'] ?? []) as [$e, $y]) { if ($y === $yearKey && in_array($e, $variants, true)) { $match = true; break; } }
            if (!$match) foreach (($a['uyl'] ?? []) as $y) { if ($y === $yearKey) { $match = true; break; } }
        }

        $semesterOk = true;
        if ($isCollege) {
            $semesterOk = $periodSemester !== null && trim($periodSemester) !== ''
                && trim((string)($u['assigned_period'] ?? '')) === trim($periodSemester);
        }

        if (($isTeacher || ($isStaff && $hasAssign)) && $match && $semesterOk) $keys[$uid . '|teacher'] = true;
        if ($isStaff && !$hasAssign) $keys[$uid . '|staff'] = true;
    }

    if ($isJhsShs && $roster['heads']['principal']) $keys[$roster['heads']['principal'] . '|school_head'] = true;
    if ($isCollege && $roster['heads']['dean'])     $keys[$roster['heads']['dean'] . '|school_head'] = true;
    return $keys;
}

/* [required, done, lastSubmission] for one student, counting only submissions that match a required item. */
function trackerStudentProgress(mysqli $db, array $student, int $periodId, ?string $periodSemester): array {
    $required = trackerRequiredKeys($db, $student, $periodSemester);
    $done = 0; $last = null;
    if ($periodId && $required) {
        $st = $db->prepare("SELECT target_user_id, evaluation_context, MAX(submitted_at)
                            FROM evaluation_tracker
                            WHERE evaluator_id=? AND period_id=? AND eval_type='student' AND status='submitted'
                            GROUP BY target_user_id, evaluation_context");
        if ($st) {
            $sid = (int)$student['id'];
            $st->bind_param('ii', $sid, $periodId); $st->execute(); $st->bind_result($tid, $ctx, $at);
            while ($st->fetch()) {
                $key = (int)$tid . '|' . ($ctx ?: 'teacher');
                if (isset($required[$key])) { $done++; if ($last === null || $at > $last) $last = $at; }
            }
            $st->close();
        }
    }
    return [count($required), $done, $last];
}

/* ───────── Evaluator tabs (Faculty / Dean / Principal) ─────────
   Who each evaluator is REQUIRED to evaluate lives in trackerRequiredTargets().
   Change the rules there if your evaluation roster differs.               */
function trackerYearScope(string $year, string $edu = ''): string {
    $y = strtolower($year); $e = strtolower($edu);
    if (strpos($y, 'college') !== false || strpos($e, 'college') !== false || strpos($e, 'higher') !== false) return 'college';
    if (strpos($y, 'grade') !== false || strpos($e, 'basic') !== false) return 'hs';
    return '';
}

function trackerLoadPeople(mysqli $db): array {
    $assign = [];
    $q = $db->query("SELECT user_id, year_level, '' AS edu FROM user_year_levels UNION ALL SELECT user_id, year_level, education_level FROM teaching_assignments");
    if ($q) {
        while ($r = $q->fetch_assoc()) {
            $uid = (int)$r['user_id'];
            if (!isset($assign[$uid])) $assign[$uid] = ['hs' => false, 'college' => false];
            $sc = trackerYearScope((string)$r['year_level'], (string)$r['edu']);
            if ($sc !== '') $assign[$uid][$sc] = true;
        }
        $q->free();
    }
    $people = ['faculty' => [], 'staff' => [], 'dean' => [], 'principal' => [], 'ea' => []];
    $q = $db->query("SELECT id, full_name, photo, designation, role FROM users
                     WHERE is_active=1 AND role IN ('teacher','faculty','staff','dean','principal','superadmin')
                       AND (role='superadmin' OR account_status='approved' OR source='admin_nologin')
                     ORDER BY full_name");
    if ($q) {
        while ($u = $q->fetch_assoc()) {
            $uid = (int)$u['id']; $role = (string)$u['role'];
            $u['id'] = $uid;
            $u['hs'] = $assign[$uid]['hs'] ?? false;
            $u['college'] = $assign[$uid]['college'] ?? false;
            $hasAssign = isset($assign[$uid]);
            if ($role === 'dean')            $people['dean'][$uid] = $u;
            elseif ($role === 'principal')   $people['principal'][$uid] = $u;
            elseif ($role === 'superadmin')  $people['ea'][$uid] = $u;
            elseif ($role === 'staff' && !$hasAssign) $people['staff'][$uid] = $u;   // non-teaching staff
            else                             $people['faculty'][$uid] = $u;          // teachers + teaching staff
        }
        $q->free();
    }
    return $people;
}

/* Returns [target ids the evaluator must evaluate, eval_type list that counts as a submission]. */
function trackerRequiredTargets(string $tab, array $me, array $people): array {
    $ids = [];
    if ($tab === 'dean' || $tab === 'principal') {
        // Dean = College faculty, Principal = High School faculty; both evaluate non-teaching Staff and the EA.
        $scope = $tab === 'dean' ? 'college' : 'hs';
        foreach ($people['faculty'] as $f) if (!empty($f[$scope])) $ids[$f['id']] = true;
        foreach ($people['staff'] as $f) $ids[$f['id']] = true;
        foreach ($people['ea'] as $f) $ids[$f['id']] = true;
        $types = ['school_head', 'supervisor_to_teacher', 'supervisor_to_staff', 'upward_to_ea'];
    } else {
        // Faculty / teaching staff: fellow faculty, non-teaching staff, and the Dean/Principal.
        foreach ($people['faculty'] as $f) $ids[$f['id']] = true;
        foreach ($people['staff'] as $f) $ids[$f['id']] = true;
        foreach ($people['dean'] as $f) $ids[$f['id']] = true;
        foreach ($people['principal'] as $f) $ids[$f['id']] = true;
        $types = ['peer', 'faculty_peer', 'staff_peer'];
    }
    unset($ids[$me['id']]);
    return [$ids, $types];
}

function trackerEvaluatorRows(mysqli $db, string $tab, int $periodId, array $people): array {
    $rows = [];
    foreach ($people[$tab] as $me) {
        [$targets, $types] = trackerRequiredTargets($tab, $me, $people);
        $required = count($targets);
        $done = 0; $last = null;
        if ($periodId && $required) {
            $in = "'" . implode("','", array_map([$db, 'real_escape_string'], $types)) . "'";
            $st = $db->prepare("SELECT target_user_id, MAX(submitted_at) FROM evaluation_tracker
                                WHERE evaluator_id=? AND period_id=? AND status='submitted' AND eval_type IN ($in)
                                GROUP BY target_user_id");
            if ($st) {
                $st->bind_param('ii', $me['id'], $periodId); $st->execute(); $st->bind_result($tid, $at);
                while ($st->fetch()) {
                    if (isset($targets[(int)$tid])) { $done++; if ($last === null || $at > $last) $last = $at; }
                }
                $st->close();
            }
        }
        if ($tab === 'dean')           { $scopeKey = 'college'; $scopeLabel = 'College'; }
        elseif ($tab === 'principal')  { $scopeKey = 'hs';      $scopeLabel = 'High School'; }
        elseif ($me['hs'] && $me['college']) { $scopeKey = 'both';    $scopeLabel = 'High School + College'; }
        elseif ($me['college'])        { $scopeKey = 'college'; $scopeLabel = 'College'; }
        elseif ($me['hs'])             { $scopeKey = 'hs';      $scopeLabel = 'High School'; }
        else                           { $scopeKey = 'none';    $scopeLabel = 'No assignment'; }
        $state = statusFor($done, $required);
        $rows[] = [
            'id' => $me['id'], 'full_name' => $me['full_name'], 'photo' => $me['photo'],
            'sub' => trim((string)($me['designation'] ?? '')) ?: ucfirst($tab === 'faculty' ? 'Faculty' : $tab),
            'scope_key' => $scopeKey, 'scope_label' => $scopeLabel,
            'required' => $required, 'done' => min($done, $required), 'state' => $state,
            'pct' => $required > 0 ? min(100, (int)round($done / $required * 100)) : 0, 'last' => $last,
        ];
    }
    return $rows;
}

function statusFor(int $done, int $required): string {
    if ($required <= 0 || $done <= 0) return 'not_started';
    if ($done >= $required) return 'completed';
    return 'in_progress';
}

$ctxCol=$mysqli->query("SHOW COLUMNS FROM evaluation_tracker LIKE 'evaluation_context'");
if($ctxCol&&$ctxCol->num_rows===0){
    $mysqli->query("ALTER TABLE evaluation_tracker ADD COLUMN evaluation_context VARCHAR(30) NOT NULL DEFAULT 'teacher'");
    $mysqli->query("UPDATE evaluation_tracker et JOIN users u ON u.id=et.target_user_id SET et.evaluation_context=CASE WHEN u.role IN ('principal','dean') THEN 'school_head' WHEN u.role='staff' THEN 'staff' WHEN u.role='teacher' THEN 'teacher' ELSE et.evaluation_context END");
}
$period=$mysqli->query("SELECT * FROM evaluation_periods WHERE is_active=1 LIMIT 1")->fetch_assoc();
$periodId = $period ? (int)$period['id'] : 0;
$periodSemester = isset($period['semester']) ? (string)$period['semester'] : null;

// Year levels per student education level (values match users.year_level).
$yearLevelsByLevel = [
    'junior_high' => ['Grade 7','Grade 8','Grade 9','Grade 10'],
    'senior_high' => ['Grade 11','Grade 12'],
    'college'     => ['1st Year College','2nd Year College','3rd Year College','4th Year College'],
];
$tab = $_GET['tab'] ?? 'students';
if (!in_array($tab, ['students','faculty','dean','principal'], true)) $tab = 'students';
$scopeFilter = $_GET['scope'] ?? 'all';
if (!in_array($scopeFilter, ['all','hs','college'], true)) $scopeFilter = 'all';
$people = trackerLoadPeople($mysqli);
$studentCount = 0;
$sc = $mysqli->query("SELECT COUNT(*) FROM users WHERE role='student' AND is_active=1");
if ($sc) { $studentCount = (int)$sc->fetch_row()[0]; $sc->free(); }
$tabCounts = ['students' => $studentCount, 'faculty' => count($people['faculty']), 'dean' => count($people['dean']), 'principal' => count($people['principal'])];

$level = $_GET['level'] ?? 'all';
if (!in_array($level, ['all','junior_high','senior_high','college'], true)) $level = 'all';

$status = $_GET['status'] ?? 'all';
if (!in_array($status, ['all','not_started','in_progress','completed'], true)) $status = 'all';

$search = trim($_GET['search'] ?? '');

$yearAllowed = ($level === 'all') ? array_merge(...array_values($yearLevelsByLevel)) : $yearLevelsByLevel[$level];
$year = $_GET['year'] ?? 'all';
if ($year !== 'all' && !in_array($year, $yearAllowed, true)) $year = 'all';

if ($tab === 'students') {
$levels = [
    'junior_high' => 'Junior High School',
    'senior_high' => 'Senior High School',
    'college' => 'College'
];

$where = "role='student' AND is_active=1";
if ($level !== 'all') {
    $where .= " AND education_level='" . $mysqli->real_escape_string($level) . "'";
}
if ($year !== 'all') {
    $where .= " AND TRIM(year_level)='" . $mysqli->real_escape_string($year) . "'";
}
if ($search !== '') {
    $where .= " AND full_name LIKE '%" . $mysqli->real_escape_string($search) . "%'";
}

$res = $mysqli->query("SELECT id, full_name, photo, education_level, year_level FROM users WHERE $where ORDER BY education_level, full_name");
$students = $res ? $res->fetch_all(MYSQLI_ASSOC) : [];
if ($res) $res->free();

$rows = [];
foreach ($students as $student) {
    [$required, $done, $last] = trackerStudentProgress($mysqli, $student, $periodId, $periodSemester);

    $state = statusFor($done, $required);
    if ($status !== 'all' && $state !== $status) continue;

    $percentage = $required > 0 ? min(100, round(($done / $required) * 100)) : 0;
    $rows[] = [
        'id' => (int)$student['id'],
        'full_name' => $student['full_name'],
        'photo' => $student['photo'],
        'education_level' => $student['education_level'],
        'year_level' => $student['year_level'],
        'required' => $required,
        'done' => min($done, $required),
        'state' => $state,
        'pct' => $percentage,
        'last' => $last
    ];
}

$summary = [
    'junior_high' => ['total'=>0,'completed'=>0,'pending'=>0],
    'senior_high' => ['total'=>0,'completed'=>0,'pending'=>0],
    'college' => ['total'=>0,'completed'=>0,'pending'=>0]
];

$summaryRes = $mysqli->query("SELECT id, education_level, year_level FROM users WHERE role='student' AND is_active=1");
if ($summaryRes) {
    while ($student = $summaryRes->fetch_assoc()) {
        $lvl = $student['education_level'];
        if (!isset($summary[$lvl])) continue;
        $summary[$lvl]['total']++;

        [$required, $done] = trackerStudentProgress($mysqli, $student, $periodId, $periodSemester);

        if (statusFor($done, $required) === 'completed') $summary[$lvl]['completed']++;
        else $summary[$lvl]['pending']++;
    }
    $summaryRes->free();
}
} else {
    $levels = [];
    $rows = []; $summary = [];
    $allRows = trackerEvaluatorRows($mysqli, $tab, $periodId, $people);
    $tot = ['total' => count($allRows), 'completed' => 0, 'in_progress' => 0, 'not_started' => 0];
    foreach ($allRows as $r) {
        $tot[$r['state']]++;
        if ($status !== 'all' && $r['state'] !== $status) continue;
        if ($tab === 'faculty' && $scopeFilter !== 'all') {
            $k = $r['scope_key'];
            if ($scopeFilter === 'hs' && !in_array($k, ['hs','both'], true)) continue;
            if ($scopeFilter === 'college' && !in_array($k, ['college','both'], true)) continue;
        }
        if ($search !== '' && stripos($r['full_name'], $search) === false) continue;
        $rows[] = $r;
    }
    $summary = $tot;
}
?>

<style>
@import url('https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css');
.et-wrap{width:100%;box-sizing:border-box;color:#f5f8ff;font-family:Arial,Helvetica,sans-serif;padding:18px 22px 30px;background:#F8FAFC;border-radius:0;min-height:100%;}
.et-head{display:flex;justify-content:space-between;align-items:flex-start;gap:20px;margin-bottom:18px}.et-title{display:flex;align-items:center;gap:10px;font-size:18px;font-weight:700}.et-title-icon{width:34px;height:34px;border:1px solid #3478d1;border-radius:8px;display:grid;place-items:center;color:#3d91ff}.et-sub{font-size:12px;color:#aebfd6;margin-top:6px}.et-updated{font-size:12px;color:#c2d0e2;white-space:nowrap;padding-top:8px}.et-updated span{color:#4e9cff;margin-right:5px}.et-cards{display:grid;grid-template-columns:repeat(3,1fr);gap:14px;margin-bottom:20px}.et-card{background:#21466f;border:1px solid #41698F;border-radius:12px;padding:18px 20px;box-sizing:border-box}.et-card-top{display:flex;justify-content:space-between;align-items:center}.et-card-label{font-size:11px;text-transform:uppercase;color:#b7c6d8;font-weight:700;letter-spacing:.5px}.et-card-number{font-size:30px;font-weight:700;margin-top:8px}.et-card-icon{width:36px;height:36px;border:1px solid #3175cc;border-radius:8px;display:grid;place-items:center;color:#4c98ff}.et-card-line{height:1px;background:#41698F;margin:15px 0 11px}.et-card-foot{font-size:12px;color:#c0cee0}.et-card-foot b{font-weight:400}.et-card-foot .done{color:#8dc0ff}.et-card-foot .sep{margin:0 8px;color:#667f9c}.et-controls{display:flex;justify-content:space-between;align-items:center;gap:12px;margin-bottom:14px}.et-levels{display:flex;gap:7px;flex-wrap:wrap}.et-btn{border:1px solid #41698F;background:#21466f;color:#cbd8e8;border-radius:7px;padding:9px 13px;font-size:11px;text-decoration:none;display:inline-block}.et-btn.active{background:#3283ef;border-color:#3283ef;color:white}.et-right{display:flex;gap:8px;align-items:center}.et-select,.et-search{border:1px solid #41698F;background:#21466f;color:#dce6f2;border-radius:8px;padding:10px 12px;font-size:12px;outline:none}.et-search{width:230px}.et-table{border:1px solid #41698F;background:#21466f;border-radius:12px;overflow:hidden}.et-table table{width:100%;border-collapse:collapse;table-layout:fixed}.et-table th{height:48px;text-align:left;padding:0 20px;font-size:10px;text-transform:uppercase;letter-spacing:.6px;color:#9fb2c9;background:#20456e;border-bottom:1px solid #41698F}.et-table td{padding:15px 20px;border-bottom:1px solid #41698F;font-size:13px;color:#edf3fb;vertical-align:middle}.et-table tr:last-child td{border-bottom:0}.et-student{display:flex;align-items:center;gap:12px}.et-avatar{width:38px;height:38px;border-radius:50%;background:#183b62;border:1px solid #41698F;display:grid;place-items:center;overflow:hidden;flex:none}.et-avatar img{width:100%;height:100%;object-fit:cover}.et-avatar svg{width:17px;height:17px;fill:#9eb5ce}.et-name{font-weight:700}.et-mini{font-size:10px;color:#8fa7c0;margin-top:4px}.et-level-pill{display:inline-block;padding:5px 11px;border-radius:14px;font-size:11px;font-weight:600;border:1px solid}.lvl-col{color:#31d6c2;border-color:#198f8c;background:rgba(25,143,140,.13)}.lvl-jhs{color:#4b91ff;border-color:#2869c4;background:rgba(40,105,196,.13)}.lvl-shs{color:#ff3fa5;border-color:#a62975;background:rgba(166,41,117,.12)}.et-status{display:inline-block;padding:7px 12px;border-radius:15px;font-size:10px;font-weight:700}.st-completed{color:#52e49a;background:#123d2b}.st-progress{color:#f4ad21;background:#4b3509}.st-start{color:#b4c2d3;background:#18314d}.et-progress{display:flex;align-items:center;gap:10px}.et-progress-text{min-width:28px;font-size:11px;color:#dce6f2}.et-bar{height:6px;background:#142b45;border-radius:8px;overflow:hidden;flex:1;min-width:90px}.et-fill{height:100%;border-radius:8px;background:#ffab1f}.et-arrow{font-size:22px;color:#a5b9d0;text-align:right}.et-empty{padding:40px;text-align:center;color:#9fb2c9}.et-note{font-size:10px;color:#8098b3;margin-top:8px}@media(max-width:900px){.et-cards{grid-template-columns:1fr}.et-controls{align-items:stretch;flex-direction:column}.et-right{width:100%}.et-search{width:100%}.et-table{overflow-x:auto}.et-table table{min-width:900px}}

/* Admin Module light design system — matches the dashboard */
:root{
  --page-bg:#FFFFFF; --card-bg:#FFFFFF; --card-border:#D8E5F4;
  --inner:#F7FAFF; --text-dark:#0B1F3A; --text-dim:#67819E;
  --light:#0B1F3A; --muted:#67819E; --dark:#FFFFFF; --mid:#FFFFFF;
  --border:#D8E5F4; --accent:#0F9F6E; --blue:#0F9F6E;
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

.et-wrap{background:#fff !important;color:#0B1F3A !important;padding:28px !important;}
.et-card{background:#fff !important;border-color:#D8E5F4 !important;box-shadow:0 2px 4px rgba(30,82,144,.06),0 6px 16px rgba(30,82,144,.08) !important;}
.et-card-label,.et-sub,.et-updated,.et-mini,.et-progress-text{color:#67819E !important;}
.et-card-number,.et-title,.et-name{color:#0B1F3A !important;}
.et-table{background:#fff !important;border-color:#D8E5F4 !important;color:#0B1F3A !important;}
.et-table th{background:#F8FAFC !important;color:#67819E !important;}
.et-table td{border-color:#D8E5F4 !important;color:#294765 !important;}
.et-search,.et-select{background:#fff !important;color:#0B1F3A !important;border-color:#B9CDE5 !important;}
.et-empty{background:#F8FAFC !important;color:#67819E !important;border-color:#D8E5F4 !important;}
.et-progress{background:transparent !important;}

/* Student Tracker — readable light surfaces with color-coded visual cues. */
.et-title-icon{background:#E6F7F1;border-color:#8DD4B7 !important;color:#0F9F6E !important;}
.et-card{border-top:3px solid #0F9F6E !important;}
.et-card-junior_high{border-top-color:#2563EB !important;}
.et-card-senior_high{border-top-color:#4968C8 !important;}
.et-card-college{border-top-color:#0E7490 !important;}
.et-card-icon{background:#E6F0FF;border-color:#B8D4F8 !important;color:#2563EB !important;}
.et-card-senior_high .et-card-icon{background:#F5F3FF;border-color:#DDD6FE !important;color:#4968C8 !important;}
.et-card-college .et-card-icon{background:#F0FDFA;border-color:#99F6E4 !important;color:#0E7490 !important;}
.et-card-line{background:#D8E5F4 !important;}
.et-card-foot{color:#67819E !important;}
.et-card-foot .done{color:#0F9F6E !important;font-weight:700;}
.et-card-foot .sep{color:#91A6BE !important;}
.et-btn{background:#FFFFFF !important;border-color:#B9CDE5 !important;color:#294765 !important;font-weight:700;}
.et-btn:hover{background:#E6F7F1 !important;border-color:#8DD4B7 !important;color:#0F9F6E !important;}
.et-btn.active{background:#0F9F6E !important;border-color:#0F9F6E !important;color:#FFFFFF !important;}
.et-avatar{background:#E6F7F1 !important;border-color:#8DD4B7 !important;}
.et-avatar svg{fill:#0F9F6E !important;}
.et-level-pill{font-weight:700;}
.st-start{color:#67819E !important;background:#F4F8FF !important;}
.st-progress{color:#B45309 !important;background:#FFFBEB !important;}
.st-completed{color:#047857 !important;background:#ECFDF5 !important;}
.et-bar{background:#D8E5F4 !important;}
.et-fill{background:#0F9F6E !important;}
.et-arrow{color:#0F9F6E !important;font-weight:700;}


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

/* Tracker tabs */
.et-tabs{display:flex;gap:6px;flex-wrap:wrap;width:fit-content;max-width:100%;margin:0 0 22px;padding:6px;background:#FFFFFF;border:1px solid #D8E5F4;border-radius:12px;box-shadow:0 2px 4px rgba(30,82,144,.05)}
.et-tab{display:inline-flex;align-items:center;gap:9px;padding:10px 16px;border-radius:9px;font-size:13px;font-weight:700;color:#67819E;text-decoration:none;white-space:nowrap}
.et-tab:hover{background:#F0FAF6;color:#0F9F6E}
.et-tab .et-tab-n{font-size:11px;font-weight:700;padding:2px 8px;border-radius:999px;background:#E6F7F1;color:#0F9F6E}
.et-tab.active{background:#0F9F6E;color:#FFFFFF}
.et-tab.active .et-tab-n{background:rgba(255,255,255,.24);color:#FFFFFF}
html[data-theme="dark"] .et-tabs{background:var(--panel-bg,#132238);border-color:var(--panel-border,#284260)}
html[data-theme="dark"] .et-tab{color:var(--muted,#9FB2C9)}
html[data-theme="dark"] .et-tab.active{color:#fff}
.et-refresh{border:0;background:none;color:#0F9F6E;cursor:pointer;font-size:15px;line-height:1;padding:0 6px 0 0;vertical-align:middle}
.et-refresh:hover{color:#0C7F59}
.et-refresh.spin{animation:et-spin .8s linear infinite}
@keyframes et-spin{to{transform:rotate(360deg)}}
</style>
<link rel="stylesheet" href="admin_appearance.css">
<script src="admin_appearance.js"></script>
<script src="eval_status_poll.js" data-watch="submissions" defer></script>

<div class="et-wrap">
    <div class="et-head">
        <div>
            <div class="et-title"><span class="et-title-icon"><?= $tab==='students' ? '🎓' : ($tab==='faculty' ? '👩‍🏫' : '🏛') ?></span>Evaluation Tracker</div>
            <div class="et-sub"><?php
                if ($tab==='students') echo 'Progress of students evaluating their required faculty/staff this period.';
                elseif ($tab==='faculty') echo 'Progress of faculty and teaching staff evaluating their required peers, staff and Dean/Principal this period.';
                elseif ($tab==='dean') echo 'Progress of the Dean evaluating College faculty, non-teaching staff and the Executive Assistant this period.';
                else echo 'Progress of the Principal evaluating High School faculty, non-teaching staff and the Executive Assistant this period.';
            ?></div>
        </div>
        <div class="et-updated"><button type="button" class="et-refresh" id="etRefresh" title="Refresh now" aria-label="Refresh now">⟳</button> Last updated: <time id="etUpdatedAt"><?=htmlspecialchars(date('M j, Y g:i A'))?></time></div>
    </div>

    <nav class="et-tabs" aria-label="Evaluation tracker tabs">
        <?php foreach (['students'=>'Students','faculty'=>'Faculty','dean'=>'Dean','principal'=>'Principal'] as $tk=>$tl): ?>
            <a class="et-tab <?=$tab===$tk?'active':''?>" href="?tab=<?=$tk?>"><?=$tl?> <span class="et-tab-n"><?=$tabCounts[$tk]?></span></a>
        <?php endforeach; ?>
    </nav>

    <div class="et-cards">
        <?php if ($tab==='students'): foreach (['junior_high'=>'JUNIOR HIGH','senior_high'=>'SENIOR HIGH','college'=>'COLLEGE'] as $key=>$label): ?>
            <div class="et-card">
                <div class="et-card-top">
                    <div>
                        <div class="et-card-label"><?=$label?></div>
                        <div class="et-card-number"><?=$summary[$key]['total']?></div>
                    </div>
                    <div class="et-card-icon">▣</div>
                </div>
                <div class="et-card-line"></div>
                <div class="et-card-foot"><span class="done"><?=$summary[$key]['completed']?> completed</span><span class="sep">|</span><span><?=$summary[$key]['pending']?> pending</span></div>
            </div>
        <?php endforeach; else:
            $tabWord = ['faculty'=>'FACULTY','dean'=>'DEAN','principal'=>'PRINCIPAL'][$tab];
            $cardDefs = [
                ['et-card-junior_high', 'TOTAL '.$tabWord, $summary['total'], '<span class="done">'.$summary['completed'].' completed</span><span class="sep">|</span><span>'.($summary['total']-$summary['completed']).' pending</span>'],
                ['et-card-senior_high', 'IN PROGRESS', $summary['in_progress'], '<span>Started, not yet finished</span>'],
                ['et-card-college', 'NOT STARTED', $summary['not_started'], '<span>No submissions yet</span>'],
            ];
            foreach ($cardDefs as $cd): ?>
            <div class="et-card <?=$cd[0]?>">
                <div class="et-card-top">
                    <div>
                        <div class="et-card-label"><?=$cd[1]?></div>
                        <div class="et-card-number"><?=$cd[2]?></div>
                    </div>
                    <div class="et-card-icon">▣</div>
                </div>
                <div class="et-card-line"></div>
                <div class="et-card-foot"><?=$cd[3]?></div>
            </div>
        <?php endforeach; endif; ?>
    </div>

    <form class="et-controls" method="get">
        <input type="hidden" name="tab" value="<?=htmlspecialchars($tab)?>">
        <div class="et-levels">
            <?php if ($tab==='students'): ?>
                <a class="et-btn <?=$level==='all'?'active':''?>" href="?tab=students&level=all&status=<?=urlencode($status)?>&search=<?=urlencode($search)?>">All Levels</a>
                <a class="et-btn <?=$level==='junior_high'?'active':''?>" href="?tab=students&level=junior_high&status=<?=urlencode($status)?>&search=<?=urlencode($search)?>">Junior High School</a>
                <a class="et-btn <?=$level==='senior_high'?'active':''?>" href="?tab=students&level=senior_high&status=<?=urlencode($status)?>&search=<?=urlencode($search)?>">Senior High School</a>
                <a class="et-btn <?=$level==='college'?'active':''?>" href="?tab=students&level=college&status=<?=urlencode($status)?>&search=<?=urlencode($search)?>">College</a>
            <?php elseif ($tab==='faculty'): ?>
                <?php foreach (['all'=>'All Faculty','hs'=>'High School','college'=>'College'] as $sk=>$sl): ?>
                    <a class="et-btn <?=$scopeFilter===$sk?'active':''?>" href="?tab=faculty&scope=<?=$sk?>&status=<?=urlencode($status)?>&search=<?=urlencode($search)?>"><?=$sl?></a>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
        <div class="et-right">
            <select class="et-select" name="status" onchange="this.form.submit()">
                <option value="all" <?=$status==='all'?'selected':''?>>All Status</option>
                <option value="not_started" <?=$status==='not_started'?'selected':''?>>Not Started</option>
                <option value="in_progress" <?=$status==='in_progress'?'selected':''?>>In Progress</option>
                <option value="completed" <?=$status==='completed'?'selected':''?>>Completed</option>
            </select>
            <?php if ($tab==='students'): ?>
            <select class="et-select" name="year" onchange="this.form.submit()">
                <option value="all" <?=$year==='all'?'selected':''?>>All Year Levels</option>
                <?php if ($level==='all'): foreach ($yearLevelsByLevel as $lk=>$ys): ?>
                    <optgroup label="<?=htmlspecialchars($levels[$lk])?>">
                        <?php foreach ($ys as $y): ?><option value="<?=htmlspecialchars($y)?>" <?=$year===$y?'selected':''?>><?=htmlspecialchars($y)?></option><?php endforeach; ?>
                    </optgroup>
                <?php endforeach; else: foreach ($yearLevelsByLevel[$level] as $y): ?>
                    <option value="<?=htmlspecialchars($y)?>" <?=$year===$y?'selected':''?>><?=htmlspecialchars($y)?></option>
                <?php endforeach; endif; ?>
            </select>
            <?php endif; ?>
            <input class="et-search" type="text" name="search" value="<?=htmlspecialchars($search)?>" placeholder="🔍  Search <?= $tab==='students' ? 'student' : ($tab==='faculty' ? 'faculty' : $tab) ?> by name...">
            <?php if ($tab==='students'): ?><input type="hidden" name="level" value="<?=htmlspecialchars($level)?>"><?php endif; ?>
            <?php if ($tab==='faculty'): ?><input type="hidden" name="scope" value="<?=htmlspecialchars($scopeFilter)?>"><?php endif; ?>
        </div>
    </form>

    <div class="et-table">
<?php if ($tab !== 'students'): ?>
        <?php if (!$rows): ?>
            <div class="et-empty">No <?= $tab==='faculty' ? 'faculty' : htmlspecialchars($tab) ?> accounts match the selected filters.</div>
        <?php else: ?>
            <table>
                <thead><tr><th style="width:38%">EVALUATOR</th><th style="width:12%">REQUIRED</th><th style="width:14%">COMPLETED</th><th style="width:16%">STATUS</th><th style="width:17%">PROGRESS</th><th style="width:3%"></th></tr></thead>
                <tbody>
                <?php foreach ($rows as $r):
                    $photo = trim((string)($r['photo'] ?? ''));
                    $stateClass = $r['state']==='completed' ? 'st-completed' : ($r['state']==='in_progress' ? 'st-progress' : 'st-start');
                    $stateLabel = $r['state']==='completed' ? 'Completed' : ($r['state']==='in_progress' ? 'In Progress' : 'Not Started');
                ?>
                    <tr>
                        <td>
                            <div class="et-student">
                                <div class="et-avatar">
                                    <?php if ($photo): ?><img src="<?=htmlspecialchars($photo)?>" alt=""><?php else: ?><svg viewBox="0 0 24 24"><path d="M12 12a4 4 0 1 0 0-8 4 4 0 0 0 0 8Zm0 2c-4.42 0-8 2.24-8 5v1h16v-1c0-2.76-3.58-5-8-5Z"/></svg><?php endif; ?>
                                </div>
                                <div><div class="et-name"><?=htmlspecialchars($r['full_name'])?></div><div class="et-mini"><?=htmlspecialchars($r['sub'])?></div></div>
                            </div>
                        </td>
                        <td><?=$r['required']?></td>
                        <td><?=$r['done']?> / <?=$r['required']?></td>
                        <td><span class="et-status <?=$stateClass?>"><?=$stateLabel?></span></td>
                        <td>
                            <div class="et-progress"><span class="et-progress-text"><?=$r['pct']?>%</span><div class="et-bar"><div class="et-fill" style="width:<?=$r['pct']?>%"></div></div></div>
                            <?php if ($r['last']): ?><div class="et-note">Last: <?=htmlspecialchars(date('M j, Y g:i A', strtotime($r['last'])))?></div><?php endif; ?>
                        </td>
                        <td class="et-arrow">›</td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
<?php else: ?>
        <?php if (!$rows): ?>
            <div class="et-empty">No students match the selected level, year level, status or search.</div>
        <?php else: ?>
            <table>
                <thead><tr><th style="width:22%">STUDENT</th><th style="width:20%">LEVEL</th><th style="width:10%">REQUIRED</th><th style="width:11%">COMPLETED</th><th style="width:14%">STATUS</th><th style="width:18%">PROGRESS</th><th style="width:5%"></th></tr></thead>
                <tbody>
                <?php foreach ($rows as $r):
                    $photo = trim((string)($r['photo'] ?? ''));
                    $stateClass = $r['state']==='completed' ? 'st-completed' : ($r['state']==='in_progress' ? 'st-progress' : 'st-start');
                    $stateLabel = $r['state']==='completed' ? 'Completed' : ($r['state']==='in_progress' ? 'In Progress' : 'Not Started');
                    $levelClass = $r['education_level']==='college' ? 'lvl-col' : ($r['education_level']==='senior_high' ? 'lvl-shs' : 'lvl-jhs');
                ?>
                    <tr>
                        <td>
                            <div class="et-student">
                                <div class="et-avatar">
                                    <?php if ($photo): ?><img src="<?=htmlspecialchars($photo)?>" alt=""><?php else: ?><svg viewBox="0 0 24 24"><path d="M12 12a4 4 0 1 0 0-8 4 4 0 0 0 0 8Zm0 2c-4.42 0-8 2.24-8 5v1h16v-1c0-2.76-3.58-5-8-5Z"/></svg><?php endif; ?>
                                </div>
                                <div><div class="et-name"><?=htmlspecialchars($r['full_name'])?></div><div class="et-mini"><?=htmlspecialchars(trim((string)($r['year_level'] ?? '')) ?: ($levels[$r['education_level']] ?? $r['education_level']))?></div></div>
                            </div>
                        </td>
                        <td><span class="et-level-pill <?=$levelClass?>"><?=htmlspecialchars($levels[$r['education_level']] ?? $r['education_level'])?></span></td>
                        <td><?=$r['required']?></td>
                        <td><?=$r['done']?> / <?=$r['required']?></td>
                        <td><span class="et-status <?=$stateClass?>"><?=$stateLabel?></span></td>
                        <td>
                            <div class="et-progress"><span class="et-progress-text"><?=$r['pct']?>%</span><div class="et-bar"><div class="et-fill" style="width:<?=$r['pct']?>%"></div></div></div>
                            <?php if ($r['last']): ?><div class="et-note">Last: <?=htmlspecialchars(date('M j, Y g:i A', strtotime($r['last'])))?></div><?php endif; ?>
                        </td>
                        <td class="et-arrow">›</td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
<?php endif; ?>
    </div>
</div>

<script>
/* Auto-refresh: re-fetches this same page (filters/tab/search preserved) and swaps
   only the tabs, summary cards and table in place. No full reload, no flicker.
   Change ET_REFRESH_MS to adjust how often (milliseconds). */
(function () {
  var ET_REFRESH_MS = 20000;
  var busy = false, timer = null, fails = 0, lastRun = Date.now();
  var btn = document.getElementById('etRefresh');
  var stamp = document.getElementById('etUpdatedAt');

  function userIsTyping() {
    var a = document.activeElement;
    return !!(a && a.classList && a.classList.contains('et-search'));
  }
  function swap(sel, doc) {
    var cur = document.querySelector(sel), nxt = doc.querySelector(sel);
    if (cur && nxt && cur.innerHTML !== nxt.innerHTML) cur.innerHTML = nxt.innerHTML;
  }
  function schedule() {
    clearTimeout(timer);
    // back off a little if requests keep failing (max 4x)
    timer = setTimeout(function () { refresh(false); }, ET_REFRESH_MS * Math.min(1 + fails, 4));
  }
  function refresh(manual) {
    if (busy) return;
    if (!manual && (document.hidden || userIsTyping())) { schedule(); return; }
    busy = true; lastRun = Date.now();
    if (btn) btn.classList.add('spin');
    fetch(location.href, { credentials: 'same-origin', cache: 'no-store', headers: { 'X-Requested-With': 'fetch' } })
      .then(function (r) { if (!r.ok) throw new Error('HTTP ' + r.status); return r.text(); })
      .then(function (html) {
        var doc = new DOMParser().parseFromString(html, 'text/html');
        if (!doc.querySelector('.et-wrap')) throw new Error('unexpected response (session expired?)');
        swap('.et-tabs', doc); swap('.et-cards', doc); swap('.et-table', doc);
        var t = doc.getElementById('etUpdatedAt');
        if (t && stamp) stamp.textContent = t.textContent;
        fails = 0;
      })
      .catch(function () { fails++; })
      .finally(function () { busy = false; if (btn) btn.classList.remove('spin'); schedule(); });
  }

  if (btn) btn.addEventListener('click', function () { refresh(true); });
  // Refresh right away when the person comes back to this tab after a while
  document.addEventListener('visibilitychange', function () {
    if (!document.hidden && Date.now() - lastRun >= ET_REFRESH_MS) refresh(false);
  });
  schedule();
})();
</script>
<?php $mysqli->close(); ?>