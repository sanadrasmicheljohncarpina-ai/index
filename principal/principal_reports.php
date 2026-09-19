<?php
// dean/principal Reports & Analytics — EA clone
session_start();
require_once 'db.php';
require_once '../shared/EvaluationContextService.php';

// ── AUTH GUARD ───────────────────────────────────────────────
// Evaluation scores and archive/restore actions are sensitive —
// require an authenticated admin-level session before anything else runs.
if (empty($_SESSION['user_id']) || !in_array($_SESSION['role'] ?? '', ['principal'])) {
    header("Location: principal_login.php");
    exit;
}

// ── EXECUTIVE PROFILE (shared portal layout) ─────────────────
$stmt = $mysqli->prepare("SELECT full_name, username, email, designation, photo, education_level FROM users WHERE id = ? LIMIT 1");
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$me = $stmt->get_result()->fetch_assoc() ?: [];
$stmt->close();
$photo_src = !empty($me['photo']) ? '../image/' . $me['photo'] : '../image/pbi_logo';
$principalScopeLabel = ($me['education_level'] ?? 'both') === 'junior_high' ? 'Junior High School' : (($me['education_level'] ?? 'both') === 'senior_high' ? 'Senior High School' : 'Junior High & Senior High');

// ── ENSURE TABLES EXIST ───────────────────────────────────────
$mysqli->query("CREATE TABLE IF NOT EXISTS analytics_archive (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    target_user_id INT UNSIGNED NOT NULL,
    archived_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uniq_target (target_user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

// evaluation_tracker already has student evals; add eval_type column if missing
$col = $mysqli->query("SHOW COLUMNS FROM evaluation_tracker LIKE 'eval_type'");
if ($col && $col->num_rows === 0) {
    $mysqli->query("ALTER TABLE evaluation_tracker ADD COLUMN eval_type ENUM('student','peer') NOT NULL DEFAULT 'student' AFTER remarks");
    $mysqli->query("ALTER TABLE evaluation_tracker ADD INDEX idx_eval_type (eval_type)");
}

// evaluator_id column — for peer evals this is the teacher/staff doing the rating
// (for student evals this mirrors student_id; we add it only if missing)
$col2 = $mysqli->query("SHOW COLUMNS FROM evaluation_tracker LIKE 'evaluator_id'");
if ($col2 && $col2->num_rows === 0) {
    $mysqli->query("ALTER TABLE evaluation_tracker ADD COLUMN evaluator_id INT UNSIGNED NULL DEFAULT NULL AFTER eval_type");
    $mysqli->query("ALTER TABLE evaluation_tracker ADD INDEX id_evaluator (evaluator_id)");
}

// Evaluation context keeps separate questionnaires/analytics for the same
// person when they can be evaluated as Teacher, Staff, or Multi-Role.
$ctxCol = $mysqli->query("SHOW COLUMNS FROM evaluation_tracker LIKE 'evaluation_context'");
if ($ctxCol && $ctxCol->num_rows === 0) {
    $mysqli->query("ALTER TABLE evaluation_tracker ADD COLUMN evaluation_context VARCHAR(30) NOT NULL DEFAULT 'teacher' AFTER period_id");
    $mysqli->query("ALTER TABLE evaluation_tracker ADD INDEX idx_eval_context (evaluation_context)");
    $mysqli->query("UPDATE evaluation_tracker et JOIN users u ON u.id=et.target_user_id SET et.evaluation_context=CASE WHEN u.role IN ('principal','dean') THEN 'school_head' WHEN u.role='staff' THEN 'staff' WHEN u.role='teacher' THEN 'teacher' ELSE et.evaluation_context END WHERE et.evaluation_context='teacher'");
}

// Portal scope: Reports & Analytics matches the Admin/EA page's Student
// Evaluation + Peer-to-Peer feature set (tabs, group pills, stat cards,
// Top Performers / Areas for Improvement, Evaluators List drill-down,
// archive/restore, print), but its dataset is restricted to the current
// principal's own education division.
//
// ── CONFIDENTIALITY BOUNDARY (do not remove) ─────────────────────────
// Admin's Reports & Analytics also covers a "School Head Evaluation"
// direction (eval_type IN school_head/supervisor_to_teacher/
// supervisor_to_staff/upward_to_ea) and lets Peer-to-Peer's target group
// include Principal/Dean — i.e. it lets the Executive Assistant see WHO
// (which teacher, staff, student, or EA) submitted a given evaluation OF
// a Principal or Dean, via the "Evaluators List" drill-down.
//
// That drill-down is exactly what must never be reachable from a
// Principal's own Reports & Analytics page: a Principal must never be
// able to identify which of their own teachers/staff/EA rated them, or
// see the individual submission behind that score. That is what keeps
// upward evaluations honest. Those results belong solely in
// principal_results.php, which the Principal can already view in
// AGGREGATE (no per-evaluator identity), never here.
//
// Consequently this page intentionally:
//   (a) only ever supports eval_type IN ('student','peer') — see the
//       $activeEval whitelist below, which excludes 'schoolhead'/'ea'
//       outright, even via a hand-crafted URL;
//   (b) restricts $reportScopeSql's target roster to role IN
//       ('teacher','staff','faculty') within the Principal's own grade
//       scope — Principal/Dean/EA/superadmin can never appear as a
//       target here, whether as a Peer-to-Peer target group (as Admin's
//       page allows) or anywhere else;
//   (c) explicitly re-asserts (a)+(b) as a hard, redundant filter in
//       every roster query below (see EXCLUDE_LEADERSHIP_SQL) so a
//       future edit to the scope logic can't silently reopen this;
//   (d) re-checks the resolved target on every drill-down view
//       (view=students, view=sheet) and refuses to render if it ever
//       resolves to a Principal/Dean/EA account or to the logged-in
//       user themselves — see the guard right after $target_id below.
$reportScope = 'PRINCIPAL';
// Principal Reports is limited to Basic Education. Faculty/Teaching Staff are
// included only when they have a JHS/SHS teaching scope; genuine Non-Teaching
// Staff remain eligible even when they have no grade assignment. College-only
// teaching personnel are excluded from this portal.
$principalHighSchoolLevelsSql = "'Grade 7','Grade 8','Grade 9','Grade 10','Grade 11','Grade 12','Grade 7 - JHS','Grade 8 - JHS','Grade 9 - JHS','Grade 10 - JHS','Grade 11 - SHS','Grade 12 - SHS'";
$principalHighSchoolScopeSql = "(
    EXISTS (SELECT 1 FROM user_year_levels scope_uyl WHERE scope_uyl.user_id=u.id AND scope_uyl.year_level IN ($principalHighSchoolLevelsSql))
    OR EXISTS (SELECT 1 FROM teaching_assignments scope_ta WHERE scope_ta.user_id=u.id AND scope_ta.year_level IN ($principalHighSchoolLevelsSql))
)";
$principalAnyTeachingScopeSql = "(
    EXISTS (SELECT 1 FROM user_year_levels any_uyl WHERE any_uyl.user_id=u.id)
    OR EXISTS (SELECT 1 FROM teaching_assignments any_ta WHERE any_ta.user_id=u.id)
)";
$reportScopeSql = "(
    (u.role IN ('teacher','faculty') AND $principalHighSchoolScopeSql)
    OR
    (u.role='staff' AND (
        $principalHighSchoolScopeSql
        OR NOT $principalAnyTeachingScopeSql
    ))
)";
// Hard, redundant exclusion — appended to $reportScopeSql everywhere it's
// used below. Even if $reportScopeSql's own role list were ever loosened
// by a future edit, this clause independently guarantees no Principal,
// Dean, EA, or superadmin account — and never the logged-in Principal's
// own account — can appear as a report target.
$myUserId = (int)$_SESSION['user_id'];
$reportScopeSql = "($reportScopeSql) AND u.role NOT IN ('principal','dean','superadmin') AND u.id <> $myUserId";
$reportStudentScopeSql = "education_level IN ('junior_high','senior_high','basic_education','jhs','shs')";

// Principal Student Evaluation reports only use JHS/SHS student evaluators.
$principalStudentLevelsSql = $principalHighSchoolLevelsSql;
$principalStudentEvaluatorSql = "et.evaluator_id IN (
    SELECT id FROM users
    WHERE role='student'
      AND is_active=1
      AND (
          education_level IN ('junior_high','senior_high','basic_education','jhs','shs')
          OR year_level IN ($principalStudentLevelsSql)
      )
)";
$principalStudentEvaluatorPlainSql = "evaluator_id IN (
    SELECT id FROM users
    WHERE role='student'
      AND is_active=1
      AND (
          education_level IN ('junior_high','senior_high','basic_education','jhs','shs')
          OR year_level IN ($principalStudentLevelsSql)
      )
)";

// ── ARCHIVE / RESTORE ─────────────────────────────────────────
if (isset($_GET['archive_id'])) {
    $aid  = intval($_GET['archive_id']);
    $scopeCheck = $mysqli->query("SELECT u.id FROM users u WHERE u.id=$aid AND $reportScopeSql LIMIT 1");
    if (!$scopeCheck || !$scopeCheck->num_rows) { header("Location: ?group=".urlencode($_GET['group']??'All')."&eval_type=".urlencode($_GET['eval_type']??'student')); exit; }
    $stmt = $mysqli->prepare("INSERT IGNORE INTO analytics_archive (target_user_id) VALUES (?)");
    $stmt->bind_param("i", $aid); $stmt->execute(); $stmt->close();
    $_SESSION['toast'] = "Personnel archived. Their data is kept and can be restored anytime.";
    header("Location: principal_reports.php?group=".urlencode($_GET['group']??'All')."&eval_type=".urlencode($_GET['eval_type']??'student')); exit;
}
if (isset($_GET['restore_id'])) {
    $rid  = intval($_GET['restore_id']);
    $scopeCheck = $mysqli->query("SELECT u.id FROM users u WHERE u.id=$rid AND $reportScopeSql LIMIT 1");
    if (!$scopeCheck || !$scopeCheck->num_rows) { header("Location: ?view=archived&group=".urlencode($_GET['group']??'All')."&eval_type=".urlencode($_GET['eval_type']??'student')); exit; }
    $stmt = $mysqli->prepare("DELETE FROM analytics_archive WHERE target_user_id=?");
    $stmt->bind_param("i", $rid); $stmt->execute(); $stmt->close();
    $_SESSION['toast'] = "Personnel restored to the main list.";
    header("Location: principal_reports.php?group=".urlencode($_GET['group']??'All')."&eval_type=".urlencode($_GET['eval_type']??'student')."&view=archived"); exit;
}

$toast = $_SESSION['toast'] ?? ''; unset($_SESSION['toast']);

// ── ACTIVE EVAL TYPE ──────────────────────────────────────────
// Multi-Role is a filter inside Student Evaluation, not a separate
// top-level evaluation type. Legacy multi_role links are redirected into
// Student Evaluation with the Multi-Role filter selected.
$requestedEvalType = $_GET['eval_type'] ?? 'student';
$legacyMultiRoleLink = ($requestedEvalType === 'multi_role');
$activeEval = $legacyMultiRoleLink ? 'student' : $requestedEvalType;
// Deliberately narrower than admin_analytics.php's whitelist
// (['student','peer','schoolhead','ea','staff']). 'schoolhead' and 'ea'
// are evaluations OF school leadership / the EA — never reportable here.
// See the confidentiality boundary note above $reportScopeSql.
if (!in_array($activeEval, ['student','peer'], true)) $activeEval = 'student';

$groupFilter = $_GET['group'] ?? 'All';
// Match the current Dean Reports structure: Student Evaluation uses only
// All / Faculty / Staff. Teaching Staff is part of Faculty; Non-Teaching Staff
// is part of Staff. Peer-to-Peer keeps its own All / Teacher / Staff grouping.
$allowedGroups = $activeEval === 'student'
    ? ['All','Faculty','Staff']
    : ['All','Faculty','Teacher','Staff'];
if (!in_array($groupFilter, $allowedGroups, true)) $groupFilter = 'All';
$isMultiRole = false;
$multiRoleSubFilter = 'All';

$sqlQuote = static function (string $value) use ($mysqli): string {
    return "'" . $mysqli->real_escape_string($value) . "'";
};
$studentTypeSql = $sqlQuote('student');
$peerTypesSql = implode(',', array_map($sqlQuote, ['peer','faculty_peer','staff_peer']));
$multiRoleContextSql = $sqlQuote('multi_role');
$teacherContextSql = $sqlQuote('teacher');
$staffContextSql = $sqlQuote('staff');
$multiRoleQuestionTypeSql = $sqlQuote('Multi-Role');

$multiRoleClauseSql = "et.eval_type=$studentTypeSql AND (
        et.evaluation_context=$multiRoleContextSql
        OR EXISTS (
            SELECT 1
            FROM questionnaire_answers qam
            JOIN user_questions uqm ON uqm.id = qam.user_question_id
            WHERE qam.tracker_id = et.id
              AND uqm.target_type = $multiRoleQuestionTypeSql
              AND uqm.eval_type = $studentTypeSql
        )
    )";
$multiRoleClausePlainSql = "eval_type=$studentTypeSql AND (
        evaluation_context=$multiRoleContextSql
        OR EXISTS (
            SELECT 1
            FROM questionnaire_answers qam
            JOIN user_questions uqm ON uqm.id = qam.user_question_id
            WHERE qam.tracker_id = evaluation_tracker.id
              AND uqm.target_type = $multiRoleQuestionTypeSql
              AND uqm.eval_type = $studentTypeSql
        )
    )";

if ($isMultiRole) {
    $evalTypeSql = $multiRoleClauseSql;
    $evalTypePlainSql = $multiRoleClausePlainSql;
} else {
    $evalTypeSql = match ($activeEval) {
        'peer' => "et.eval_type IN ($peerTypesSql)",
        default => "et.eval_type=$studentTypeSql AND $principalStudentEvaluatorSql AND COALESCE(et.evaluation_context,'teacher') IN ($teacherContextSql,$staffContextSql)"
    };
    $evalTypePlainSql = match ($activeEval) {
        'peer' => "eval_type IN ($peerTypesSql)",
        default => "eval_type=$studentTypeSql AND $principalStudentEvaluatorPlainSql AND COALESCE(evaluation_context,'teacher') IN ($teacherContextSql,$staffContextSql)"
    };
}

// ── VIEWS ─────────────────────────────────────────────────────
$view       = $_GET['view']       ?? 'list';
$target_id  = intval($_GET['target_id']  ?? 0);
$student_id = intval($_GET['student_id'] ?? 0);  // evaluator for peer
$tracker_id = intval($_GET['tracker_id'] ?? 0);

// ── CONFIDENTIALITY GUARD (drill-down views) ───────────────────
// view=students (the "Evaluators List") and view=sheet (a single
// evaluator's individual submission) are exactly where an evaluator's
// identity becomes visible. $reportScopeSql already keeps Principal/
// Dean/EA/superadmin and the logged-in Principal themselves out of every
// roster query, but a $target_id can still arrive as a raw query-string
// value typed straight into the URL. Re-check it here, independently of
// $reportScopeSql, before either drill-down view is allowed to render.
if ($target_id > 0 && in_array($view, ['students', 'sheet'], true)) {
    $targetRoleCheck = $mysqli->query("SELECT role FROM users WHERE id=$target_id LIMIT 1")->fetch_assoc();
    $targetRole = $targetRoleCheck['role'] ?? null;
    if ($target_id === $myUserId || in_array($targetRole, ['principal', 'dean', 'superadmin'], true)) {
        // Never reveal *why* — just land back on the roster. Their own
        // evaluation results (in aggregate, with no evaluator identity)
        // live at principal_results.php, not here.
        header("Location: principal_reports.php?group=" . urlencode($_GET['group'] ?? 'All') . "&eval_type=" . urlencode($activeEval));
        exit;
    }
}

// ── HELPERS ───────────────────────────────────────────────────
// ── PRINCIPAL STUDENT REPORT GROUPING ─────────────────────────
// This mirrors the current Dean Reports rule:
//   Faculty = Teacher/Faculty accounts + Teaching Staff with a teaching scope.
//   Staff   = genuine Non-Teaching Staff with no teaching/year-level scope.
// The helper intentionally checks the same assignment sources used elsewhere
// in the portal, rather than trusting a stale role/evaluation_context value.
if (!function_exists('principal_resolve_student_group')) {
function principal_resolve_student_group(array $p, mysqli $mysqli): ?string {
    global $principalHighSchoolLevelsSql;
    $rawRole = strtolower(trim((string)($p['role'] ?? '')));
    if ($rawRole === 'principal' || $rawRole === 'dean') return 'school_head';
    $uid = (int)($p['id'] ?? 0);
    if ($uid <= 0) return null;

    $stmt = $mysqli->prepare("SELECT u.id, u.role, u.sector,
            EXISTS(SELECT 1 FROM teaching_assignments ta WHERE ta.user_id=u.id) AS has_teaching_assignment,
            EXISTS(SELECT 1 FROM user_year_levels yl WHERE yl.user_id=u.id) AS has_year_level,
            EXISTS(SELECT 1 FROM teaching_assignments tha WHERE tha.user_id=u.id AND tha.year_level IN ($principalHighSchoolLevelsSql)) AS has_hs_assignment,
            EXISTS(SELECT 1 FROM user_year_levels hyl WHERE hyl.user_id=u.id AND hyl.year_level IN ($principalHighSchoolLevelsSql)) AS has_hs_year_level
        FROM users u WHERE u.id=? LIMIT 1");
    if (!$stmt) return null;
    $stmt->bind_param('i', $uid);
    $stmt->execute();
    $u = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    if (!$u) return null;

    $isTeacherRole = in_array(strtolower((string)$u['role']), ['teacher','faculty'], true)
        || strtolower((string)$u['sector']) === 'teacher';
    $hasAnyTeaching = (int)$u['has_teaching_assignment'] === 1 || (int)$u['has_year_level'] === 1;
    $hasHighSchoolTeaching = (int)$u['has_hs_assignment'] === 1 || (int)$u['has_hs_year_level'] === 1;

    if (($isTeacherRole || $hasAnyTeaching) && $hasHighSchoolTeaching) return 'teacher';

    // Non-teaching staff: staff function with no teaching/year-level scope.
    if (strtolower((string)$u['role']) === 'staff' && !$hasAnyTeaching) return 'staff';
    return null;
}
}

function scoreLabel($s) {
    if ($s === null) return '—';
    if ($s >= 4.5)  return 'Always';
    if ($s >= 3.5)  return 'Often';
    if ($s >= 2.5)  return 'Sometimes';
    if ($s >= 1.5)  return 'Rarely';
    return 'Never';
}
function scoreColor($s) {
    if ($s === null) return '#6b7280';
    if ($s >= 4.5)  return '#4ade80';
    if ($s >= 3.5)  return '#86efac';
    if ($s >= 2.5)  return '#facc15';
    if ($s >= 1.5)  return '#fb923c';
    return '#f87171';
}

// Eval type UI config
$evalLabel      = $activeEval === 'peer' ? 'Peer-to-Peer Evaluation' : 'Student Evaluation';
$evalColor      = $activeEval === 'peer' ? '#7C3AED' : '#3B82F6';
$evalColorBg    = $activeEval === 'peer' ? 'rgba(124,58,237,.08)' : 'rgba(59,130,246,.08)';
$evalColorBorder= $activeEval === 'peer' ? 'rgba(124,58,237,.25)' : 'rgba(59,130,246,.25)';
$evalIcon       = $activeEval === 'peer' ? 'fa-people-arrows' : 'fa-graduation-cap';
// Label for "who evaluated"
$evaluatorNoun  = $activeEval === 'peer' ? 'colleague' : 'student';
$evaluatorNounP = $activeEval === 'peer' ? 'colleagues' : 'students';
// In evaluation_tracker: student_id = the evaluator (student or peer teacher)
// eval_type filters which set we show

// ══════════════════════════════════════════════════════════════
function render_exec_sidebar(string $active, array $me, string $photo_src, string $scope): void {
    $links = [
        'dashboard' => ['principal_dashboard.php','fa-gauge','Dashboard'],
        'evaluation' => ['principal_evaluations.php','fa-clipboard-list','Evaluation'],
        'tracker' => ['principal_evaluation_tracker.php','fa-satellite-dish','Evaluation Tracker'],
        'results' => ['principal_results.php','fa-star-half-stroke','View Results'],
        'reports' => ['principal_reports.php','fa-chart-line','Reports'],
        'settings' => ['principal_account_settings.php','fa-gear','Settings'],
    ];
    ?>
    <aside class="sidebar">
        <div class="sb-profile">
            <img class="sb-photo" src="<?= htmlspecialchars($photo_src) ?>" alt="Profile"/>
            <div class="sb-name"><?= htmlspecialchars($me['full_name'] ?? 'Principal') ?></div>
            <div class="sb-role"><?= htmlspecialchars($me['designation'] ?? 'Principal') ?></div>
            <div class="sb-scope"><?= htmlspecialchars($scope) ?></div>
        </div>
        <nav class="sb-nav" aria-label="Principal navigation">
            <div class="sb-nav-section-label">MAIN</div>
            <?php foreach (['dashboard', 'evaluation', 'tracker', 'results', 'reports'] as $key): ?>
                <?php [$href,$icon,$label] = $links[$key]; ?>
                <a href="<?= htmlspecialchars($href) ?>" class="<?= $key === $active ? 'active' : '' ?>"><i class="fa-solid <?= htmlspecialchars($icon) ?>"></i> <?= htmlspecialchars($label) ?></a>
            <?php endforeach; ?>

            <div class="sb-nav-section-label">ADMINISTRATION</div>
            <?php [$href,$icon,$label] = $links['settings']; ?>
            <a href="<?= htmlspecialchars($href) ?>" class="<?= $active === 'settings' ? 'active' : '' ?>"><i class="fa-solid <?= htmlspecialchars($icon) ?>"></i> <?= htmlspecialchars($label) ?></a>

            <div class="sb-nav-section-label">ACCOUNT</div>
            <a href="../logout.php" class="sb-logout-link"><i class="fa-solid fa-right-from-bracket"></i> Log Out</a>
        </nav>
    </aside>
    <?php
}

// SHARED CSS HEAD (used across all sub-views)
// ══════════════════════════════════════════════════════════════
function pageHead($title, $evalColor, $evalColorBg, $evalColorBorder) { ?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"/>
<meta name="viewport" content="width=device-width, initial-scale=1"/>
<title><?= htmlspecialchars($title) ?> — PBI Reports &amp; Analytics</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css"/>
<link href="https://fonts.googleapis.com/css2?family=Rajdhani:wght@600;700&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet"/>
<style>
:root{
  --dark:#F8FAFC;--mid:#FFFFFF;--inner:#F1F5F9;--accent:#3B82F6;
  --gold:#D97706;--gold-h:#F59E0B;--teal:#0D9488;--violet:#7C3AED;
  --light:#0F172A;--muted:#475569;--danger:#F87171;
  --border:#CBD5E1;--radius:10px;
  --card-shadow:0 1px 2px rgba(15,23,42,.06),0 4px 12px rgba(15,23,42,.06);
  --ec:<?= $evalColor ?>;
  --ec-bg:<?= $evalColorBg ?>;
  --ec-bd:<?= $evalColorBorder ?>;
}
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0;}
body{font-family:'Inter',sans-serif;background:var(--dark);color:var(--light);min-height:100vh;padding:28px;}

/* ── CUSTOM SCROLLBAR ── */
html,body{scrollbar-width:thin;scrollbar-color:var(--light) transparent;}
::-webkit-scrollbar{width:10px;height:10px;}
::-webkit-scrollbar-track{background:transparent;}
::-webkit-scrollbar-thumb{background:var(--light);border-radius:20px;border:2px solid var(--dark);background-clip:padding-box;}
::-webkit-scrollbar-thumb:hover{background:#fff;background-clip:padding-box;}
.toast{position:fixed;top:20px;right:20px;z-index:999;background:rgba(34,197,94,.15);border:1px solid rgba(34,197,94,.35);color:#86efac;padding:12px 20px;border-radius:8px;font-size:13px;display:flex;align-items:center;gap:8px;animation:slideIn .3s ease,fadeOut .4s ease 3s forwards;}
@keyframes slideIn{from{opacity:0;transform:translateX(20px)}to{opacity:1;transform:none}}
@keyframes fadeOut{to{opacity:0;pointer-events:none}}
.back-btn{display:inline-flex;align-items:center;gap:8px;padding:9px 18px;background:var(--mid);border:1px solid var(--border);border-radius:var(--radius);color:var(--light);font-size:13px;font-weight:600;text-decoration:none;margin-bottom:22px;transition:background .2s;}
.back-btn:hover{background:var(--accent);}

/* ── EVAL SWITCHER ── */
.eval-switcher{display:flex;gap:0;background:var(--mid);border:1px solid var(--border);border-radius:12px;overflow:hidden;margin-bottom:26px;width:fit-content;}
.eval-tab{display:flex;align-items:center;gap:9px;padding:12px 24px;font-size:13px;font-weight:600;cursor:pointer;text-decoration:none;color:var(--muted);border:none;background:none;font-family:'Inter',sans-serif;transition:all .2s;position:relative;}
.eval-tab:hover{color:var(--light);background:rgba(255,255,255,.04);}
.eval-tab.student.active{color:var(--accent);background:rgba(59,130,246,.07);}
.eval-tab.student.active::after{content:'';position:absolute;bottom:0;left:0;right:0;height:2px;background:var(--accent);border-radius:2px 2px 0 0;}
.eval-tab.peer.active{color:var(--violet);background:rgba(124,58,237,.07);}
.eval-tab.peer.active::after{content:'';position:absolute;bottom:0;left:0;right:0;height:2px;background:var(--violet);border-radius:2px 2px 0 0;}
.eval-divider{width:1px;background:var(--border);margin:8px 0;}
.tab-badge{font-size:10px;font-weight:700;padding:2px 8px;border-radius:20px;background:rgba(15,23,42,.12);color:var(--muted);}
.eval-tab.student.active .tab-badge{background:rgba(59,130,246,.15);color:var(--accent);}
.eval-tab.peer.active .tab-badge{background:rgba(124,58,237,.15);color:var(--violet);}
.eval-tab.multi-role.active{color:#F59E0B;background:rgba(245,158,11,.07);}
.eval-tab.multi-role.active::after{content:'';position:absolute;bottom:0;left:0;right:0;height:2px;background:#F59E0B;border-radius:2px 2px 0 0;}
.eval-tab.multi-role.active .tab-badge{background:rgba(245,158,11,.15);color:#F59E0B;}

/* ── EVAL TYPE BANNER ── */
.eval-banner{display:flex;align-items:center;gap:14px;padding:13px 18px;border-radius:10px;margin-bottom:20px;border:1px solid var(--ec-bd);background:var(--ec-bg);}
.eval-banner-icon{width:40px;height:40px;border-radius:9px;display:flex;align-items:center;justify-content:center;font-size:18px;color:var(--ec);background:rgba(255,255,255,.05);border:1px solid var(--ec-bd);flex-shrink:0;}
.eval-banner-title{font-size:14px;font-weight:700;color:var(--ec);}
.eval-banner-desc{font-size:12px;color:var(--muted);margin-top:1px;}

/* Admin Module light design system — matches the dashboard */
:root{
  --page-bg:#FFFFFF; --card-bg:#FFFFFF; --card-border:#E2E8F0;
  --inner:#F4F7FB; --text-dark:#172033; --text-dim:#475569;
  --light:#172033; --muted:#475569; --dark:#FFFFFF; --mid:#FFFFFF;
  --border:#E2E8F0; --accent:#3B82F6; --blue:#3B82F6;
  --gold:#D97706; --gold-h:#F59E0B; --teal:#0D9488; --violet:#7C3AED;
  --danger:#DC2626; --success:#059669; --radius:12px;
  --card-shadow:0 2px 4px rgba(15,23,42,.05),0 6px 16px rgba(15,23,42,.06);
}
html{background:#fff;color-scheme:light;}
body{background:#fff !important;color:#172033 !important;}
a{color:inherit;}
.page-header h1,.page-title,.et-title,.section-title{color:#172033 !important;}
.page-header p,.page-sub,.et-sub,.et-updated,.muted,.hint{color:#475569 !important;}
input,select,textarea{background:#fff !important;color:#172033 !important;border-color:#CBD5E1 !important;}
button{font-family:inherit;}
.table-wrap,.content-panel,.create-panel,.period-card,.stat-card,.sector-card,.person-row,
.sum-card,.standing-panel,.eval-card,.eval-banner,.info-banner,.section,.shell .section,
.history-card,.gl-card,.amber-card,.green-card,.red-card{
  background:#fff !important;border-color:#E2E8F0 !important;box-shadow:0 2px 4px rgba(15,23,42,.04),0 6px 16px rgba(15,23,42,.05) !important;
}
.sector-tabs,.eval-switcher,.tabs,.level-tabs,.status-tabs{
  background:#fff !important;border-color:#E2E8F0 !important;box-shadow:0 2px 4px rgba(15,23,42,.04) !important;
}
.sector-tab,.eval-tab,.tab,.level-tab,.status-tab{color:#475569 !important;}
.sector-tab:hover,.eval-tab:hover,.tab:hover,.level-tab:hover,.status-tab:hover{color:#172033 !important;background:#F4F7FB !important;}
thead tr{background:#F8FAFC !important;}
tbody tr:hover{background:#F8FAFC !important;}
.btn-cancel,.btn-icon,.btn-back{background:#fff !important;color:#172033 !important;border-color:#CBD5E1 !important;}
.empty-state,.empty-cta{color:#475569 !important;}
::-webkit-scrollbar-track{background:#fff;}
::-webkit-scrollbar-thumb{background:#CBD5E1;border:2px solid #fff;}

body{padding:28px !important;}
.eval-tab.student.active{background:#EFF6FF !important;color:#2563EB !important;}
.eval-tab.peer.active{background:#F5F3FF !important;color:#7C3AED !important;}
.eval-tab.multi-role.active{background:#FFF7ED !important;color:#D97706 !important;}
.eval-banner{background:var(--ec-bg) !important;}
.avg-bar-bg,.eval-bar-bg,.score-bar-bg{background:#E2E8F0 !important;}
.comment-text,.comment-section{background:#F8FAFC !important;color:#334155 !important;}


/* ── SHARP LIGHT ADMIN UI ── */
html { background:#F8FAFC; }
body {
  color:#0F172A !important;
  background:#F8FAFC !important;
  -webkit-font-smoothing:antialiased;
  text-rendering:optimizeLegibility;
}
h1,h2,h3,h4,h5,h6 { color:#0F172A; letter-spacing:-.01em; }
p, .subtitle, .description, .helper, .muted, small { color:#475569; }
label, th { color:#334155; font-weight:600; }
td { color:#0F172A; }
input, select, textarea {
  color:#0F172A;
  background:#FFFFFF;
  border-color:#CBD5E1;
}
input::placeholder, textarea::placeholder { color:#94A3B8; }
.card, .panel, .section, .table-card, .content-card {
  border-color:#CBD5E1;
  box-shadow:0 4px 14px rgba(15,23,42,.07);
}
button, .btn { font-weight:700; }
a { color:inherit; }

/* Persistent evaluation-tab icon coding */
.eval-tab.student > i{color:#2563EB !important;}
.eval-tab.peer > i{color:#7C3AED !important;}
.eval-tab.multi-role > i{color:#D97706 !important;}

</style>
<style id="principal-white-portal-theme">
:root{
  --portal-accent:#d99a2b;
  --portal-accent-h:#f0b84d;
  --portal-accent-glow:rgba(217,154,43,.20);
  --portal-accent-bg:rgba(217,154,43,.10);
}
html{background:#fff !important;color-scheme:light !important;}
body{
  min-height:100vh;
  display:flex !important;
  padding:0 !important;
  background:#fff !important;
  background-image:none !important;
  color:#172033 !important;
  font-family:'DM Sans',sans-serif !important;
}
.sidebar{
  width:250px;flex:0 0 250px;min-height:100vh;
  background:#0A192F !important;
  border-right:1px solid #172A45 !important;
  padding:28px 20px;display:flex;flex-direction:column;
  position:sticky;top:0;height:100vh;z-index:20;
}
.sb-profile{text-align:center;margin-bottom:26px}
.sb-photo{width:72px;height:72px;border-radius:50%;object-fit:cover;border:2.5px solid var(--portal-accent);box-shadow:0 0 18px var(--portal-accent-glow);margin:0 auto 10px;display:block}
.sb-name{font-weight:700;font-size:15px;color:#fff}
.sb-role{font-size:11px;color:var(--portal-accent-h);text-transform:uppercase;letter-spacing:.6px;margin-top:2px}
.sb-scope{font-size:10px;color:#A0B3C6;margin-top:4px}
.sb-nav{display:flex;flex-direction:column;gap:4px;margin-top:10px}
.sb-nav a{display:flex;align-items:center;gap:10px;padding:11px 14px;border-radius:8px;color:#A0B3C6;text-decoration:none;font-size:14px;font-weight:500}
.sb-nav a:hover,.sb-nav a.active{background:var(--portal-accent-bg);color:#fff}
.sb-nav a i{width:18px;text-align:center;color:var(--portal-accent-h)}
.sb-logout{margin-top:6px}
.sb-logout a{display:flex;align-items:center;gap:10px;padding:11px 14px;border-radius:8px;color:#fca5a5;text-decoration:none;font-size:14px}
.main{flex:1;min-width:0;padding:36px 44px !important;background:#fff !important;color:#172033 !important}
.page-header,
.eval-switcher,.eval-banner,.group-tab,.group-tab-wrap,.group-tabs,.desig-subtabs,
.sum-card,.standing-panel,.person-row,.no-evaluated,.target-card,.eval-card,
.comment-section,.avg-summary,.top-bar,.history-card,.section,.content-panel,.no-archived,
.card,.panel,.table-card,.content-card,.stat-card,.info-banner,.gl-card,.amber-card,
.green-card,.red-card,.cat-section,.people-list,.evaluator-grid,.ra-table-card,
.eval-q-card,.eval-table-wrap,.table-wrap{
  background:#fff !important;
  border-color:#E2E8F0 !important;
  box-shadow:0 4px 18px rgba(15,23,42,.08) !important;
  color:#172033 !important;
}
.page-header h1,.page-title,.section-title,.sheet-name,.target-name,.person-name,.standing-name,
.sum-value,h1,h2,h3,h4,h5,h6{color:#0F172A !important}
.page-header p,.page-sub,.sheet-desig,.target-desig,.standing-desig,.person-meta,
.sum-label,.sum-sub,.pstat-lbl,.muted,.no-data,.hint,.helper,.description,.subtitle,
.main p,.main small,.subtabs-label,.ra-pager-info{color:#64748B !important}
.eval-switcher,.tabs,.level-tabs,.status-tabs,.group-tab-wrap,.group-tabs,.desig-subtabs,.faculty-subtabs{
  background:#fff !important;border-color:#E2E8F0 !important;box-shadow:none !important;
}
.eval-tab,.tab,.level-tab,.status-tab,.group-tab,.desig-subtab,.faculty-subtab{color:#475569 !important;background:transparent !important}
.eval-tab:hover,.tab:hover,.level-tab:hover,.status-tab:hover,.group-tab:hover,.desig-subtab:hover,.faculty-subtab:hover{color:#172033 !important;background:#F8FAFC !important}
.eval-tab.student.active{background:#EFF6FF !important;color:#2563EB !important}
.eval-tab.peer.active{background:#F5F3FF !important;color:#7C3AED !important}
.eval-tab.multi-role.active{background:#FFF7ED !important;color:#D97706 !important}
.group-tab.active,.desig-subtab.active,.faculty-subtab.active{background:#FFF7ED !important;color:#B45309 !important;border-color:#FED7AA !important}
.tab-badge,.tab-count{background:#F1F5F9 !important;color:#475569 !important}
.eval-tab.student.active .tab-badge{background:#DBEAFE !important;color:#2563EB !important}
.eval-tab.peer.active .tab-badge{background:#EDE9FE !important;color:#7C3AED !important}
.eval-tab.multi-role.active .tab-badge{background:#FFEDD5 !important;color:#D97706 !important}
.student-report-groups .faculty-subtabs{background:#F8FAFC !important;border:1px solid #E2E8F0 !important}
.student-report-groups .faculty-subtab:hover{background:#FFF7ED !important;color:#92400E !important}
.student-report-groups .faculty-subtab.active{background:#FFF7ED !important;border-color:#FED7AA !important;color:#B45309 !important}
.student-report-groups .tab-count{background:#F1F5F9 !important}

/* Report table and controls: no dark surfaces anywhere in the content area. */
.ra-table-card{background:#fff !important;color:#172033 !important;border-color:#E2E8F0 !important}
.ra-table-tools .ra-tool-btn,.ra-filter-clear,.ra-search,.ra-filter-row select,.ra-pager-btns button{
  background:#fff !important;color:#334155 !important;border-color:#CBD5E1 !important;
}
.ra-filters-panel{background:#fff !important;color:#172033 !important;border-color:#E2E8F0 !important;box-shadow:0 10px 28px rgba(15,23,42,.12) !important}
.ra-table-wrap{background:#fff !important}
table.ra-table{background:#fff !important;color:#172033 !important}
table.ra-table th{color:#64748B !important;border-color:#E2E8F0 !important;background:#F8FAFC !important}
table.ra-table td{color:#172033 !important;border-color:#E2E8F0 !important;background:#fff !important}
table.ra-table tbody tr:hover{background:#F8FAFC !important}
.ra-name-text a{color:#172033 !important}
.ra-tool-btn:hover,.ra-filter-clear:hover,.ra-pager-btns button:hover{background:#F8FAFC !important;color:#172033 !important}
.ra-pager-btns button.active{background:#d99a2b !important;border-color:#d99a2b !important;color:#fff !important}
.score-bar-bg,.eval-bar-bg,.avg-bar-bg,.bar-wrap{background:#E2E8F0 !important}
.comment-text{background:#F8FAFC !important;color:#334155 !important;border-color:#E2E8F0 !important}

/* Buttons and semantic accents remain light-theme compatible. */
.btn-print,.btn-archive,.btn-restore,.btn-solid,.btn,.action-btn,.back-btn,.btn-archived-link{
  background:rgba(217,154,43,.10) !important;
  border-color:rgba(217,154,43,.30) !important;
  color:#A16207 !important;
}
.btn-solid,.btn-primary{background:#d99a2b !important;color:#fff !important;border-color:#d99a2b !important}
.btn-print:hover,.btn-archive:hover,.btn-restore:hover,.btn:hover,.action-btn:hover,.back-btn:hover,.btn-archived-link:hover{background:rgba(217,154,43,.16) !important;color:#92400E !important}
input,select,textarea{background:#fff !important;color:#172033 !important;border-color:#CBD5E1 !important}
input::placeholder,textarea::placeholder{color:#94A3B8 !important}
.period-badge{background:rgba(217,154,43,.12) !important;border-color:rgba(217,154,43,.28) !important;color:#B45309 !important}
.print-eval-summary{background:#fff !important;color:#172033 !important}

@media(max-width:900px){body{display:block !important}.sidebar{position:relative;width:100%;height:auto;min-height:0}.main{padding:24px !important}}
@media print{html,body,.main{background:#fff !important;color:#000 !important}}
</style>
<?php } // end pageHead

// ══════════════════════════════════════════════════════════════
// VIEW: EVALUATION SHEET
// ══════════════════════════════════════════════════════════════
if ($view === 'sheet' && $target_id && $tracker_id) {
    $tgt = $mysqli->query("SELECT id,full_name,designation,photo,role FROM users u WHERE u.id=$target_id AND $reportScopeSql LIMIT 1")->fetch_assoc();
    if (!$tgt) { http_response_code(404); exit('Personnel not found in this report scope.'); }
    $stu = $mysqli->query("SELECT id,full_name,photo FROM users WHERE id=$student_id LIMIT 1")->fetch_assoc();
    $trk = $mysqli->query("SELECT * FROM evaluation_tracker et WHERE et.id=$tracker_id AND et.target_user_id=$target_id AND $evalTypeSql LIMIT 1")->fetch_assoc();
    if (!$trk) { http_response_code(404); exit('Evaluation not found in this report scope.'); }

    $answers = [];
    // Student Teacher/Multi-Role questionnaires come from evaluation_questions;
    // Staff questionnaires are stored in user_questions. Keep both sources
    // separate so a multi-role evaluation can never be mixed with the person's
    // Staff evaluation even when the same question IDs exist in both tables.
    $aq = $mysqli->query("
        SELECT qa.question_id AS q_id, eq.question_text, eq.category, qa.answer_score
        FROM questionnaire_answers qa
        JOIN evaluation_questions eq ON eq.id = qa.question_id
        WHERE qa.tracker_id = $tracker_id AND qa.question_source='evaluation'
        UNION ALL
        SELECT qa.user_question_id AS q_id, uq.question_text, uq.category, qa.answer_score
        FROM questionnaire_answers qa
        JOIN user_questions uq ON uq.id = qa.user_question_id
        WHERE qa.tracker_id = $tracker_id AND qa.question_source='user'
        ORDER BY category, q_id
    ");
    if ($aq) $answers = $aq->fetch_all(MYSQLI_ASSOC);

    $grouped_ans = [];
    foreach ($answers as $a) $grouped_ans[$a['category']][] = $a;

    $scores = array_filter(array_column($answers,'answer_score'), fn($s) => $s !== null);
    $avg    = count($scores) ? round(array_sum($scores)/count($scores),2) : null;
    $remark = $trk['remarks'] ?? '';

    $scaleItems  = [5=>'Always',4=>'Often',3=>'Sometimes',2=>'Rarely',1=>'Never'];
    $scaleColors = [5=>'#4ade80',4=>'#86efac',3=>'#facc15',2=>'#fb923c',1=>'#f87171'];

    pageHead('Evaluation Sheet', $evalColor, $evalColorBg, $evalColorBorder);
    ?>
<style>
.sheet-header{background:var(--mid);border:1px solid var(--border);border-radius:14px;padding:22px 26px;margin-bottom:20px;display:flex;align-items:center;gap:20px;flex-wrap:wrap;}
.sheet-avatar{width:64px;height:64px;border-radius:50%;object-fit:cover;border:2px solid var(--ec);}
.sheet-avatar-ph{width:64px;height:64px;border-radius:50%;background:var(--inner);border:2px solid var(--border);display:flex;align-items:center;justify-content:center;color:var(--muted);font-size:26px;}
.sheet-name{font-family:'Rajdhani',sans-serif;font-size:22px;font-weight:700;color:var(--light);}
.sheet-desig{font-size:13px;color:var(--muted);margin-top:2px;}
.sheet-eval-by{margin-left:auto;text-align:right;}
.eval-by-label{font-size:11px;color:var(--muted);text-transform:uppercase;letter-spacing:.8px;margin-bottom:3px;}
.eval-by-name{font-size:14px;font-weight:600;color:var(--ec);}
.eval-by-date{font-size:11px;color:var(--muted);margin-top:2px;}
.scale-bar{display:flex;gap:6px;margin-bottom:20px;background:var(--mid);border:1px solid var(--border);border-radius:10px;padding:12px 18px;flex-wrap:wrap;}
.scale-item{display:flex;align-items:center;gap:6px;font-size:12px;color:var(--muted);}
.scale-dot{width:22px;height:22px;border-radius:5px;display:flex;align-items:center;justify-content:center;font-size:11px;font-weight:700;color:#fff;flex-shrink:0;}
.eval-type-chip{display:inline-flex;align-items:center;gap:6px;padding:4px 12px;border-radius:20px;font-size:11px;font-weight:700;border:1px solid var(--ec-bd);background:var(--ec-bg);color:var(--ec);margin-bottom:18px;}
.cat-section{margin-bottom:28px;}
.cat-title{font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:1.2px;color:var(--ec);margin-bottom:12px;padding-bottom:6px;border-bottom:1px solid var(--ec-bd);display:flex;align-items:center;gap:7px;}
.q-table{width:100%;border-collapse:collapse;background:var(--mid);border-radius:12px;overflow:hidden;border:1px solid var(--border);}
.q-table thead tr{background:var(--inner);}
.q-table th{padding:11px 16px;font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.8px;color:var(--muted);text-align:left;}
.q-table th.rating-col{text-align:center;width:90px;}
.q-table td{padding:13px 16px;border-top:1px solid var(--border);font-size:13px;color:var(--light);line-height:1.5;vertical-align:top;}
.q-table td.q-num{width:40px;color:var(--muted);font-weight:700;font-size:13px;padding-top:14px;}
.q-table td.rating-cell{text-align:center;padding-top:12px;}
.q-table tr:hover td{background:rgba(59,130,246,.06);}
.rating-badge{display:inline-flex;flex-direction:column;align-items:center;gap:2px;background:var(--inner);border-radius:8px;padding:6px 12px;}
.rating-num{font-size:16px;font-weight:700;}
.rating-lbl{font-size:9px;font-weight:600;text-transform:uppercase;letter-spacing:.5px;}
.comment-section{background:var(--mid);border:1px solid var(--ec-bd);border-radius:12px;padding:20px 24px;margin-bottom:24px;}
.comment-title{font-size:13px;font-weight:700;color:var(--ec);margin-bottom:10px;display:flex;align-items:center;gap:8px;}
.comment-text{font-size:14px;color:var(--light);line-height:1.6;font-style:italic;}
.no-comment{font-size:13px;color:var(--muted);font-style:italic;}
.avg-summary{background:var(--mid);border:1px solid var(--border);border-radius:14px;padding:24px;display:flex;align-items:center;gap:24px;flex-wrap:wrap;}
.avg-score-big{font-family:'Rajdhani',sans-serif;font-size:56px;font-weight:700;line-height:1;}
.avg-score-label{font-size:14px;font-weight:700;margin-top:4px;}
.avg-breakdown{flex:1;min-width:200px;}
.avg-bar-row{display:flex;align-items:center;gap:10px;margin-bottom:7px;}
.avg-bar-label{font-size:12px;color:var(--muted);width:100px;text-align:right;}
.avg-bar-bg{flex:1;height:7px;background:rgba(15,23,42,.12);border-radius:4px;overflow:hidden;}
.avg-bar-fill{height:100%;border-radius:4px;}
.avg-bar-val{font-size:12px;font-weight:700;width:28px;}
.avg-out-of{font-size:13px;color:var(--muted);margin-top:6px;}
.btn-print{background:var(--accent);color:#fff;border:none;padding:10px 20px;border-radius:var(--radius);font-size:13px;font-weight:600;cursor:pointer;display:inline-flex;align-items:center;gap:7px;transition:opacity .2s;font-family:'Inter',sans-serif;}
.btn-print:hover{opacity:.85;}
.top-bar{display:flex;justify-content:space-between;align-items:center;margin-bottom:16px;flex-wrap:wrap;gap:10px;}

/* ── PRINT: hide evaluator identity entirely ── */
@media print{
  .no-print{display:none!important;}
  body{background:#fff!important;color:#000!important;padding:0;}
  body *{color:#000!important;}
  /* Hide the "Evaluated by" block in the sheet header */
  .sheet-eval-by{display:none!important;}
  /* Anonymity notice shown only in print */
  .print-anon-notice{display:block!important;}
}
/* Hidden on screen, visible only when printing */
.print-anon-notice{
  display:none;
  margin-top:10px;
  font-size:11px;
  color:#555;
  font-style:italic;
  border-top:1px solid #ddd;
  padding-top:8px;
}

/* ── SHARP LIGHT ADMIN UI ── */
html { background:#F8FAFC; }
body {
  color:#0F172A !important;
  background:#F8FAFC !important;
  -webkit-font-smoothing:antialiased;
  text-rendering:optimizeLegibility;
}
h1,h2,h3,h4,h5,h6 { color:#0F172A; letter-spacing:-.01em; }
p, .subtitle, .description, .helper, .muted, small { color:#475569; }
label, th { color:#334155; font-weight:600; }
td { color:#0F172A; }
input, select, textarea {
  color:#0F172A;
  background:#FFFFFF;
  border-color:#CBD5E1;
}
input::placeholder, textarea::placeholder { color:#94A3B8; }
.card, .panel, .section, .table-card, .content-card {
  border-color:#CBD5E1;
  box-shadow:0 4px 14px rgba(15,23,42,.07);
}
button, .btn { font-weight:700; }
a { color:inherit; }
</style>

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

<style id="principal-subtle-feature-background">
/* Subtle light backdrop for every Principal feature area. Feature cards remain white; sidebar stays navy. */
html{background:#F8FAFC !important;}
body{background:#F8FAFC !important;}
.main, main.main, .main-content, .content, .page-content{background:#F8FAFC !important;}
.page-header,.eval-switcher,.eval-banner,.group-tab-wrap,.group-tabs,.desig-subtabs,.faculty-subtabs,
.card,.panel,.section,.table-card,.content-card,.stat-card,.info-banner,.gl-card,.amber-card,
.green-card,.red-card,.ra-table-card,.table-wrap,.table-card-wrap,.content-panel,.history-card,
target-card,.eval-card,.comment-section,.avg-summary,.standing-panel,.person-row,.no-evaluated,
.no-archived,.evaluator-grid,.people-list,.cat-section,.period-strip,.filter-bar{background:#FFFFFF !important;}
@media print{html,body,.main,main.main,.main-content,.content,.page-content{background:#fff !important;}}
</style>

<style id="principal-ea-style-feature-canvas-standalone">
/* Match the EA System Logs feature: subtle light canvas with white content surfaces. */
html{background:#F8FAFC !important;}
body{background:#F8FAFC !important;background-image:none !important;}
.main, main.main, .main-content, .content, .page-content{background:#F8FAFC !important;}
/* Preserve clean white feature cards/panels. */
.page-header,.card,.panel,.section,.table-card,.content-card,.stat-card,
.period-strip,.filter-bar,.table-wrap,.table-card-wrap,.content-panel,
.eval-banner,.info-banner,.history-card,.gl-card,.amber-card,.green-card,.red-card,
.ra-table-card,.eval-card,.comment-section,.avg-summary,.standing-panel,
.person-row,.target-card,.no-eval,.no-data,.no-evaluated,.no-archived,
.evaluator-grid,.people-list,.cat-section,.eval-switcher,.tabs,.level-tabs,
.status-tabs,.faculty-subtabs,.group-tabs,.group-tab-wrap,
.desig-subtabs,.desig-subtab-wrap,.results-card,.result-card,.feature-card{
    background:#FFFFFF !important;
}
/* Light inner surfaces, equivalent to the EA log table header treatment. */
.main table thead th,.main .table-head,.main .table-header,.main .thead,
.main .subtle-head{background:#F4F8FF !important;}
@media print{
  html,body,.main,main.main,.main-content,.content,.page-content{background:#fff !important;}
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


<style id="principal-workspace-sharper-final">
/* Final Principal workspace composition: white outer page + sharper light workspace. */
html{background:#FFFFFF!important;color-scheme:light!important;}
body{background:#FFFFFF!important;background-image:none!important;color:#172033!important;}
.sidebar{background:#0A192F!important;}
.main,main.main{
  position:relative!important;
  background:#F3F6FA!important;
  color:#172033!important;
  border:1px solid #E5EAF0!important;
  border-bottom:0!important;
  border-radius:16px 16px 0 0!important;
  box-shadow:none!important;
}
.main > .page-header,main.main > .page-header{
  background:transparent!important;
  border:0!important;
  box-shadow:none!important;
}
.main .page-header,.main .section,.main .card,.main .panel,.main .table-card,.main .content-card,.main .stat-card,
.main .period-strip,.main .filter-bar,.main .table-wrap,.main .table-card-wrap,.main .content-panel,
.main .eval-banner,.main .info-banner,.main .history-card,.main .gl-card,.main .amber-card,.main .green-card,
.main .red-card,.main .ra-table-card,.main .eval-card,.main .comment-section,.main .avg-summary,.main .standing-panel,
.main .person-row,.main .target-card,.main .no-eval,.main .no-data,.main .no-evaluated,.main .no-archived,
.main .evaluator-grid,.main .people-list,.main .cat-section,.main .results-card,.main .result-card,.main .feature-card{
  background:#FFFFFF!important;
  color:#172033!important;
  border-color:#D9E4EF!important;
  box-shadow:0 1px 5px rgba(15,23,42,.035)!important;
}
.main table thead th,.main .table-head,.main .table-header,.main .thead,.main .subtle-head{
  background:#F4F8FF!important;color:#4B6580!important;border-color:#D9E4EF!important;
}
@media(max-width:768px){
  .main,main.main{margin:12px 12px 0!important;padding:20px 18px 28px!important;border-radius:12px 12px 0 0!important;}
}
@media print{
  html,body,.main,main.main{background:#FFFFFF!important;border-color:transparent!important;}
}
</style>
</head><body>
<?php render_exec_sidebar('reports', $me, $photo_src, $principalScopeLabel); ?>
<main class="main">


<div class="person-actions no-print" style="display:flex;justify-content:space-between;align-items:center;margin-bottom:16px;">
    <a href="?view=students&target_id=<?= $target_id ?>&group=<?= urlencode($groupFilter) ?>&eval_type=<?= $activeEval ?>" class="back-btn">
        <i class="fa-solid fa-arrow-left"></i> Back to <?= ucfirst($evaluatorNounP) ?> List
    </a>
    <button class="btn-print no-print" onclick="window.print()"><i class="fa-solid fa-print"></i> Print / Save PDF</button>
</div>

<!-- Eval type chip -->
<div class="eval-type-chip no-print">
    <i class="fa-solid <?= $evalIcon ?>"></i> <?= $evalLabel ?>
</div>

<!-- SHEET HEADER -->
<div class="sheet-header">
    <?php if ($tgt['photo']): ?><img class="sheet-avatar" src="../image/<?= htmlspecialchars($tgt['photo']) ?>" alt=""/>
    <?php else: ?><div class="sheet-avatar-ph"><i class="fa-solid fa-user"></i></div><?php endif; ?>
    <div>
        <div class="sheet-name"><?= htmlspecialchars($tgt['full_name']) ?></div>
        <div class="sheet-desig"><?= htmlspecialchars($tgt['designation']) ?> · <?= $tgt['role']==='teacher'?'Teacher':'Staff' ?></div>
    </div>

    <!-- Visible on screen: shows evaluator identity -->
    <div class="sheet-eval-by no-print">
        <div class="eval-by-label">Evaluated by <?= $evaluatorNoun ?></div>
        <div class="eval-by-name"><?= htmlspecialchars($stu['full_name'] ?? 'Unknown') ?></div>
        <div class="eval-by-date"><i class="fa-solid fa-clock" style="margin-right:4px"></i><?= $trk ? date('F d, Y g:i A', strtotime($trk['submitted_at'])) : '—' ?></div>
    </div>

    <!-- Visible only when printing: no name, just date -->
    <div class="sheet-eval-by" style="margin-left:auto;text-align:right;">
        <p class="print-anon-notice">
            Evaluator identity is kept confidential to protect <?= $evaluatorNoun ?> privacy.<br>
            Date submitted: <?= $trk ? date('F d, Y', strtotime($trk['submitted_at'])) : '—' ?>
        </p>
    </div>
</div>

<!-- RATING SCALE -->
<div class="scale-bar no-print">
    <span style="font-size:11px;font-weight:700;color:var(--muted);text-transform:uppercase;letter-spacing:.8px;margin-right:6px;">Rating Scale:</span>
    <?php foreach ($scaleItems as $n => $lbl): ?>
    <div class="scale-item">
        <div class="scale-dot" style="background:<?= $scaleColors[$n] ?>"><?= $n ?></div> <?= $lbl ?>
    </div>
    <?php endforeach; ?>
</div>

<!-- QUESTIONS + RATINGS -->
<?php $qNum = 1; foreach ($grouped_ans as $cat => $qs): ?>
<div class="cat-section">
    <div class="cat-title"><i class="fa-solid fa-layer-group" style="font-size:10px"></i> <?= htmlspecialchars($cat) ?></div>
    <table class="q-table">
        <thead><tr>
            <th style="width:40px">No.</th>
            <th>Statement / Question</th>
            <th class="rating-col">Rating</th>
        </tr></thead>
        <tbody>
        <?php foreach ($qs as $q):
            $s = $q['answer_score']; $sc = scoreColor($s); $sl = scoreLabel($s); ?>
        <tr>
            <td class="q-num"><?= $qNum++ ?></td>
            <td><?= htmlspecialchars($q['question_text']) ?></td>
            <td class="rating-cell">
                <div class="rating-badge" style="border:1px solid <?= $sc ?>33">
                    <span class="rating-num" style="color:<?= $sc ?>"><?= $s !== null ? intval($s) : '—' ?></span>
                    <span class="rating-lbl" style="color:<?= $sc ?>"><?= $sl ?></span>
                </div>
            </td>
        </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?php endforeach; ?>

<?php if (empty($answers)): ?>
<div style="text-align:center;padding:40px;color:var(--muted);background:var(--mid);border-radius:12px;border:1px solid var(--border);">
    <i class="fa-solid fa-clipboard-question" style="font-size:32px;opacity:.3;display:block;margin-bottom:12px;"></i>
    No answers found for this evaluation.
</div>
<?php endif; ?>

<!-- COMMENTS & SUGGESTIONS -->
<div class="comment-section">
    <div class="comment-title"><i class="fa-solid fa-comment-dots"></i> Comments, Suggestions &amp; Areas for Improvement</div>
    <?php if (!empty($remark)): ?>
    <div class="comment-text">"<?= nl2br(htmlspecialchars($remark)) ?>"</div>
    <?php else: ?>
    <div class="no-comment">No comments or suggestions were provided.</div>
    <?php endif; ?>
</div>

<!-- AVERAGE SUMMARY -->
<?php if ($avg !== null): ?>
<div class="avg-summary">
    <div>
        <div class="avg-score-big" style="color:<?= scoreColor($avg) ?>"><?= number_format($avg,2) ?></div>
        <div class="avg-score-label" style="color:<?= scoreColor($avg) ?>"><?= scoreLabel($avg) ?></div>
        <div class="avg-out-of">out of 5.00</div>
    </div>
    <div class="avg-breakdown">
        <?php foreach ($scaleItems as $n => $lbl):
            $cnt = count(array_filter($scores, fn($s) => intval(round($s)) === $n));
            $pct = count($scores) ? round(($cnt/count($scores))*100) : 0;
        ?>
        <div class="avg-bar-row">
            <div class="avg-bar-label"><?= $n ?> — <?= $lbl ?></div>
            <div class="avg-bar-bg"><div class="avg-bar-fill" style="width:<?= $pct ?>%;background:<?= $scaleColors[$n] ?>"></div></div>
            <div class="avg-bar-val" style="color:<?= $scaleColors[$n] ?>"><?= $cnt ?></div>
        </div>
        <?php endforeach; ?>
    </div>
    <div style="text-align:center;">
        <div style="font-size:12px;color:var(--muted);margin-bottom:4px;">Based on</div>
        <div style="font-family:'Rajdhani',sans-serif;font-size:28px;font-weight:700;color:var(--light);"><?= count($scores) ?></div>
        <div style="font-size:12px;color:var(--muted);">question<?= count($scores)!==1?'s':'' ?></div>
    </div>
</div>
<?php endif; ?>

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

/* Keep the sidebar navy; all report content remains in the white theme. */
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

/* Accent elements stay amber/semantic within the light theme. */
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
  body{background:#fff!important;}
}
</style>
</body></html>
<?php $mysqli->close(); exit; }

// ══════════════════════════════════════════════════════════════
// VIEW: EVALUATORS LIST (students who evaluated / peers who evaluated)
// ══════════════════════════════════════════════════════════════
if ($view === 'students' && $target_id) {
    $tgt = $mysqli->query("SELECT id,full_name,designation,photo,role FROM users u WHERE u.id=$target_id AND $reportScopeSql LIMIT 1")->fetch_assoc();
    if (!$tgt) { http_response_code(404); exit('Personnel not found in this report scope.'); }

    // For peer eval, evaluator_id in tracker = the peer who did the evaluating
    $evaluators = [];
    $eq = $mysqli->query("
        SELECT et.id as tracker_id, et.submitted_at, et.remarks,
               u.id as student_id, u.full_name, u.photo, u.role as evaluator_role, u.designation as evaluator_designation,
               (SELECT yl.year_level FROM user_year_levels yl WHERE yl.user_id=u.id ORDER BY yl.id LIMIT 1) as year_level,
               (SELECT AVG(qa.answer_score) FROM questionnaire_answers qa WHERE qa.tracker_id=et.id) as avg_score
        FROM evaluation_tracker et
        JOIN users u ON u.id = et.evaluator_id
        WHERE et.target_user_id = $target_id AND $evalTypeSql
        ORDER BY et.submitted_at DESC
    ");
    if ($eq) $evaluators = $eq->fetch_all(MYSQLI_ASSOC);

    $allScores  = array_filter(array_column($evaluators,'avg_score'), fn($s) => $s !== null);
    $overallAvg = count($allScores) ? round(array_sum($allScores)/count($allScores),2) : null;
    $yearLevels = array_values(array_unique(array_filter(array_column($evaluators,'year_level'))));
    sort($yearLevels);

    pageHead('Evaluators List', $evalColor, $evalColorBg, $evalColorBorder);
    ?>
<style>
.target-card{background:var(--mid);border:1px solid var(--border);border-radius:14px;padding:22px 26px;margin-bottom:24px;display:flex;align-items:center;gap:18px;flex-wrap:wrap;}
.target-avatar{width:60px;height:60px;border-radius:50%;object-fit:cover;border:2px solid var(--ec);}
.target-avatar-ph{width:60px;height:60px;border-radius:50%;background:var(--inner);border:2px solid var(--border);display:flex;align-items:center;justify-content:center;color:var(--muted);font-size:24px;}
.target-name{font-family:'Rajdhani',sans-serif;font-size:22px;font-weight:700;color:var(--light);}
.target-desig{font-size:13px;color:var(--muted);}
.target-stats{margin-left:auto;display:flex;gap:24px;flex-wrap:wrap;}
.tstat{text-align:center;}
.tstat-val{font-family:'Rajdhani',sans-serif;font-size:28px;font-weight:700;}
.tstat-lbl{font-size:11px;color:var(--muted);text-transform:uppercase;letter-spacing:.5px;}
.section-title{font-family:'Rajdhani',sans-serif;font-size:20px;font-weight:700;color:var(--light);margin-bottom:16px;display:flex;align-items:center;gap:10px;}
.evaluator-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(260px,1fr));gap:16px;}
.eval-card{background:var(--mid);border:1px solid var(--border);border-radius:14px;padding:20px;cursor:pointer;transition:all .22s;text-decoration:none;display:block;}
.eval-card:hover{border-color:var(--ec);transform:translateY(-2px);box-shadow:0 10px 24px rgba(15,23,42,.12);}
.eval-card-top{display:flex;align-items:center;gap:12px;margin-bottom:14px;}
.eval-avatar{width:46px;height:46px;border-radius:50%;object-fit:cover;border:2px solid var(--border);}
.eval-avatar-ph{width:46px;height:46px;border-radius:50%;background:var(--inner);border:2px solid var(--border);display:flex;align-items:center;justify-content:center;color:var(--muted);font-size:18px;}
.eval-name{font-size:14px;font-weight:700;color:var(--light);}
.eval-date{font-size:11px;color:var(--muted);margin-top:2px;}
.eval-score-row{display:flex;align-items:center;justify-content:space-between;}
.eval-score{font-family:'Rajdhani',sans-serif;font-size:26px;font-weight:700;}
.eval-score-lbl{font-size:12px;font-weight:600;margin-top:1px;}
.eval-bar-bg{flex:1;height:6px;background:rgba(15,23,42,.12);border-radius:3px;overflow:hidden;margin:0 12px;}
.eval-bar-fill{height:100%;border-radius:3px;}
.view-eval-btn{margin-top:12px;width:100%;padding:9px;background:var(--ec-bg);border:1px solid var(--ec-bd);border-radius:8px;color:var(--ec);font-size:12px;font-weight:700;text-align:center;}
.eval-remark{margin-top:10px;font-size:12px;color:var(--muted);font-style:italic;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;}
.no-eval{text-align:center;padding:48px;background:var(--mid);border-radius:14px;border:1px solid var(--border);color:var(--muted);}
.no-eval i{font-size:36px;opacity:.3;display:block;margin-bottom:12px;}
/* Peer evaluator role badge */
.evaluator-role-badge{font-size:10px;font-weight:700;padding:2px 8px;border-radius:20px;background:rgba(124,58,237,.12);color:#7C3AED;border:1px solid rgba(124,58,237,.25);margin-left:auto;}

/* Score legend box (inside target-card) */
.legend-card{min-width:210px;padding:14px 20px;border-radius:10px;}
.legend-title{font-family:'Rajdhani',sans-serif;font-size:14px;font-weight:700;color:var(--light);margin-bottom:8px;}
.legend-item{display:flex;align-items:center;justify-content:space-between;gap:18px;font-size:12px;padding:2px 0;}
.legend-range{font-weight:700;}
.legend-label{color:var(--muted);}

/* Evaluation results table */
.results-toolbar{display:flex;align-items:center;justify-content:space-between;gap:16px;flex-wrap:wrap;margin-bottom:16px;}
.year-filter{display:flex;align-items:center;gap:10px;}
.year-filter label{font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.6px;color:var(--muted);}
.year-filter select{padding:9px 14px;border-radius:8px;border:1px solid var(--border);font-size:13px;font-weight:600;min-width:170px;}
.results-table{width:100%;border-collapse:collapse;}
.results-table th{padding:11px 16px;font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.6px;text-align:left;border-bottom:1px solid var(--border);}
.results-table td{padding:14px 16px;font-size:13px;border-bottom:1px solid var(--border);vertical-align:middle;}
.results-table tbody tr:last-child td{border-bottom:none;}
.results-table tbody tr:hover td{background:var(--ec-bg);}
.rating-pill{display:inline-block;padding:4px 12px;border-radius:20px;font-size:11px;font-weight:700;border:1px solid;}
.role-cell{color:var(--muted);}
.results-table .action-btn{display:inline-flex;align-items:center;gap:6px;padding:7px 14px;border-radius:8px;font-size:12px;font-weight:700;text-decoration:none;white-space:nowrap;}

/* ── PRINT: hide evaluator cards entirely, show summary only ── */
@media print{
  .no-print{display:none!important;}
  body{background:#fff!important;color:#000!important;padding:0;}
  body *{color:#000!important;}
  /* Hide every evaluator card/row — names, photos, dates, remarks */
  .evaluator-grid,.table-card,.results-toolbar{display:none!important;}
  /* Show the print-only anonymised summary block */
  .print-eval-summary{display:block!important;}
  .target-card{border:1px solid #ccc!important;border-radius:0!important;}
  .section-title{color:#000!important;}
}
/* Hidden on screen, shown only when printing */
.print-eval-summary{
  display:none;
  border:1px solid #ccc;
  border-radius:8px;
  padding:20px 24px;
  margin-top:16px;
  font-size:13px;
  color:#333;
  line-height:1.8;
}
.print-eval-summary h3{font-size:15px;font-weight:700;margin-bottom:10px;color:#000;}
.print-eval-summary p{margin:0;}

/* ── SHARP LIGHT ADMIN UI ── */
html { background:#F8FAFC; }
body {
  color:#0F172A !important;
  background:#F8FAFC !important;
  -webkit-font-smoothing:antialiased;
  text-rendering:optimizeLegibility;
}
h1,h2,h3,h4,h5,h6 { color:#0F172A; letter-spacing:-.01em; }
p, .subtitle, .description, .helper, .muted, small { color:#475569; }
label, th { color:#334155; font-weight:600; }
td { color:#0F172A; }
input, select, textarea {
  color:#0F172A;
  background:#FFFFFF;
  border-color:#CBD5E1;
}
input::placeholder, textarea::placeholder { color:#94A3B8; }
.card, .panel, .section, .table-card, .content-card {
  border-color:#CBD5E1;
  box-shadow:0 4px 14px rgba(15,23,42,.07);
}
button, .btn { font-weight:700; }
a { color:inherit; }
</style>
</head><body>
<?php render_exec_sidebar('reports', $me, $photo_src, $principalScopeLabel); ?>
<main class="main">

<div class="top-bar no-print">
    <a href="?group=<?= urlencode($groupFilter) ?>&eval_type=<?= $activeEval ?>" class="back-btn" style="margin-bottom:0;">
        <i class="fa-solid fa-arrow-left"></i> Back to Analytics
    </a>
    <button class="btn-print" onclick="window.print()"><i class="fa-solid fa-print"></i> Print / Save PDF</button>
</div>

<!-- eval type banner -->
<div class="eval-banner" style="margin-bottom:18px;">
    <div class="eval-banner-icon"><i class="fa-solid <?= $evalIcon ?>"></i></div>
    <div>
        <div class="eval-banner-title"><?= $evalLabel ?></div>
        <div class="eval-banner-desc"><?= $activeEval === 'peer' ? 'Evaluated by fellow teacher and staff' : 'Evaluated by students' ?></div>
    </div>
</div>

<div class="target-card">
    <?php if ($tgt['photo']): ?><img class="target-avatar" src="../image/<?= htmlspecialchars($tgt['photo']) ?>" alt=""/>
    <?php else: ?><div class="target-avatar-ph"><i class="fa-solid fa-user"></i></div><?php endif; ?>
    <div>
        <div class="target-name"><?= htmlspecialchars($tgt['full_name']) ?></div>
        <div class="target-desig"><?= htmlspecialchars($tgt['designation']) ?> · <?= $tgt['role']==='teacher'?'teacher':'Staff' ?></div>
    </div>
    <div class="target-stats">
        <div class="tstat">
            <div class="tstat-val" style="color:var(--ec)"><?= count($evaluators) ?></div>
            <div class="tstat-lbl">Evaluations</div>
        </div>
        <div class="tstat">
            <div class="tstat-val" style="color:<?= scoreColor($overallAvg) ?>"><?= $overallAvg !== null ? number_format($overallAvg,2) : '—' ?></div>
            <div class="tstat-lbl">Avg Score</div>
        </div>
        <div class="tstat">
            <div class="tstat-val" style="color:<?= scoreColor($overallAvg) ?>;font-size:18px;padding-top:6px"><?= scoreLabel($overallAvg) ?></div>
            <div class="tstat-lbl">Rating</div>
        </div>
    </div>
    <div class="legend-card gl-card no-print">
        <div class="legend-title">Score Legend</div>
        <div class="legend-item"><span class="legend-range" style="color:<?= scoreColor(5) ?>">4.50 - 5.00</span><span class="legend-label">Always</span></div>
        <div class="legend-item"><span class="legend-range" style="color:<?= scoreColor(4) ?>">3.50 - 4.49</span><span class="legend-label">Often</span></div>
        <div class="legend-item"><span class="legend-range" style="color:<?= scoreColor(3) ?>">2.50 - 3.49</span><span class="legend-label">Sometimes</span></div>
        <div class="legend-item"><span class="legend-range" style="color:<?= scoreColor(2) ?>">1.50 - 2.49</span><span class="legend-label">Rarely</span></div>
        <div class="legend-item"><span class="legend-range" style="color:<?= scoreColor(1) ?>">1.00 - 1.49</span><span class="legend-label">Never</span></div>
    </div>
</div>

<div class="results-toolbar no-print">
    <div class="section-title" style="margin-bottom:0;">
        <i class="fa-solid fa-list-check" style="color:var(--accent)"></i>
        Evaluation Results
        <span style="font-size:13px;font-weight:400;color:var(--muted)">(<?= count($evaluators) ?>)</span>
    </div>
    <?php if ($activeEval === 'student' && $yearLevels): ?>
    <div class="year-filter">
        <label>Year Level</label>
        <select id="yearLevelFilter" onchange="filterYearLevel(this.value)">
            <option value="all">All Year Levels</option>
            <?php foreach ($yearLevels as $yl): ?>
            <option value="<?= htmlspecialchars($yl) ?>"><?= htmlspecialchars($yl) ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <?php endif; ?>
</div>

<?php if (empty($evaluators)): ?>
<div class="no-eval">
    <i class="fa-solid fa-users-slash"></i>
    <p>No <?= $evaluatorNounP ?> have evaluated this person yet.</p>
</div>
<?php else: ?>

<!-- Screen: evaluation results table -->
<div class="table-card" style="overflow-x:auto;">
<table class="data results-table">
    <thead><tr>
        <th>No.</th>
        <th>Evaluator</th>
        <th>Role</th>
        <th>Average Score</th>
        <th>Rating</th>
        <th>Date Evaluated</th>
        <th class="no-print">Actions</th>
    </tr></thead>
    <tbody id="resultsBody">
    <?php foreach ($evaluators as $i => $ev):
        $sc = $ev['avg_score'] !== null ? round($ev['avg_score'],2) : null;
        $scolor = scoreColor($sc);
        $roleLabel = $activeEval === 'peer'
            ? ($ev['evaluator_designation'] ?: ucfirst($ev['evaluator_role'] ?? 'Staff'))
            : 'Student';
    ?>
    <tr data-year="<?= htmlspecialchars($ev['year_level'] ?? '') ?>">
        <td><?= $i + 1 ?></td>
        <td><?= htmlspecialchars($ev['full_name']) ?></td>
        <td class="role-cell"><?= htmlspecialchars($roleLabel) ?></td>
        <td style="color:<?= $scolor ?>;font-weight:700;"><?= $sc !== null ? number_format($sc,2) : '—' ?></td>
        <td><span class="rating-pill" style="color:<?= $scolor ?>;background:<?= $scolor ?>1A;border-color:<?= $scolor ?>4D;"><?= scoreLabel($sc) ?></span></td>
        <td><?= date('M d, Y', strtotime($ev['submitted_at'])) ?></td>
        <td class="no-print">
            <a class="action-btn" href="?view=sheet&target_id=<?= $target_id ?>&student_id=<?= $ev['student_id'] ?>&tracker_id=<?= $ev['tracker_id'] ?>&group=<?= urlencode($groupFilter) ?>&eval_type=<?= $activeEval ?>">
                <i class="fa-solid fa-eye"></i> View
            </a>
        </td>
    </tr>
    <?php endforeach; ?>
    </tbody>
</table>
</div>

<script>
function filterYearLevel(val) {
    document.querySelectorAll('#resultsBody tr').forEach(row => {
        row.style.display = (val === 'all' || row.dataset.year === val) ? '' : 'none';
    });
}
</script>

<!-- Print-only: anonymised summary, no names or photos -->
<div class="print-eval-summary">
    <h3>Evaluation Summary — <?= htmlspecialchars($tgt['full_name']) ?></h3>
    <p><strong>Total evaluations received:</strong> <?= count($evaluators) ?></p>
    <p><strong>Overall average score:</strong> <?= $overallAvg !== null ? number_format($overallAvg,2).' / 5.00 ('.scoreLabel($overallAvg).')' : '—' ?></p>
    <p><strong>Evaluation type:</strong> <?= $evalLabel ?></p>
    <p style="margin-top:12px;font-size:11px;color:#888;font-style:italic;">
        Individual <?= $evaluatorNoun ?> identities are not shown in this report to protect their privacy and encourage honest feedback.
    </p>
</div>

<?php endif; ?>
</main>

</body></html>
<?php $mysqli->close(); exit; }

// ══════════════════════════════════════════════════════════════
// VIEW: ARCHIVED PERSONNEL
// ══════════════════════════════════════════════════════════════
if ($view === 'archived') {
    $whereRoleArc = "u.role IN ('teacher','staff','faculty')";
    $archived = [];
    $res = $mysqli->query("
        SELECT u.id,u.full_name,u.designation,u.photo,u.role,aa.archived_at,
               COUNT(DISTINCT et.id) AS total_responses,
               AVG(qa.answer_score)  AS avg_score
        FROM analytics_archive aa
        JOIN users u ON u.id=aa.target_user_id
        LEFT JOIN evaluation_tracker et ON et.target_user_id=u.id AND $evalTypeSql
        LEFT JOIN questionnaire_answers qa ON qa.tracker_id=et.id
        WHERE $whereRoleArc AND $reportScopeSql
        GROUP BY u.id ORDER BY aa.archived_at DESC
    ");
    if ($res) $archived = $res->fetch_all(MYSQLI_ASSOC);
    if ($activeEval === 'student') {
        foreach ($archived as &$archivedPerson) {
            $archivedPerson['student_resolved_group'] = principal_resolve_student_group($archivedPerson, $mysqli) ?? '';
        }
        unset($archivedPerson);
        if ($groupFilter === 'Faculty') {
            $archived = array_values(array_filter($archived, fn(array $p) => ($p['student_resolved_group'] ?? '') === 'teacher'));
        } elseif ($groupFilter === 'Staff') {
            $archived = array_values(array_filter($archived, fn(array $p) => ($p['student_resolved_group'] ?? '') === 'staff'));
        }
    }

    pageHead('Archived Personnel', $evalColor, $evalColorBg, $evalColorBorder);
    ?>
<style>
.page-title{font-family:'Rajdhani',sans-serif;font-size:28px;font-weight:700;color:var(--light);margin-bottom:3px;}
.page-sub{font-size:13px;color:var(--muted);margin-bottom:24px;}
.people-list{display:flex;flex-direction:column;gap:14px;}
.person-row{background:var(--mid);border:1px solid var(--border);border-radius:14px;overflow:hidden;opacity:.85;}
.person-header{display:flex;align-items:center;gap:16px;padding:18px 22px;}
.person-photo{width:52px;height:52px;border-radius:50%;object-fit:cover;border:2px solid var(--border);filter:grayscale(.4);}
.person-photo-ph{width:52px;height:52px;border-radius:50%;background:var(--inner);border:2px solid var(--border);display:flex;align-items:center;justify-content:center;color:var(--muted);font-size:20px;}
.person-name{font-size:15px;font-weight:700;color:var(--light);margin-bottom:4px;}
.person-meta{display:flex;align-items:center;gap:8px;flex-wrap:wrap;font-size:12px;color:var(--muted);}
.archived-badge{display:inline-flex;align-items:center;gap:5px;padding:3px 11px;border-radius:20px;font-size:11px;font-weight:700;background:rgba(160,179,198,.15);color:var(--muted);border:1px solid var(--border);}
.btn-restore{background:var(--teal);color:#fff;border:none;padding:9px 18px;border-radius:8px;font-size:12px;font-weight:700;cursor:pointer;text-decoration:none;display:inline-flex;align-items:center;gap:6px;transition:background .2s;white-space:nowrap;}
.btn-restore:hover{background:#14b89f;}
.no-archived{text-align:center;padding:48px;background:var(--mid);border-radius:14px;border:1px solid var(--border);color:var(--muted);}
.no-archived i{font-size:36px;opacity:.3;display:block;margin-bottom:14px;}

/* ── SHARP LIGHT ADMIN UI ── */
html { background:#F8FAFC; }
body {
  color:#0F172A !important;
  background:#F8FAFC !important;
  -webkit-font-smoothing:antialiased;
  text-rendering:optimizeLegibility;
}
h1,h2,h3,h4,h5,h6 { color:#0F172A; letter-spacing:-.01em; }
p, .subtitle, .description, .helper, .muted, small { color:#475569; }
label, th { color:#334155; font-weight:600; }
td { color:#0F172A; }
input, select, textarea {
  color:#0F172A;
  background:#FFFFFF;
  border-color:#CBD5E1;
}
input::placeholder, textarea::placeholder { color:#94A3B8; }
.card, .panel, .section, .table-card, .content-card {
  border-color:#CBD5E1;
  box-shadow:0 4px 14px rgba(15,23,42,.07);
}
button, .btn { font-weight:700; }
a { color:inherit; }
</style>
</head><body>
<?php render_exec_sidebar('reports', $me, $photo_src, $principalScopeLabel); ?>
<main class="main">

<?php if ($toast): ?><div class="toast"><i class="fa-solid fa-circle-check"></i><?= htmlspecialchars($toast) ?></div><?php endif; ?>
<a href="?group=<?= urlencode($groupFilter) ?>&eval_type=<?= $activeEval ?>" class="back-btn"><i class="fa-solid fa-arrow-left"></i> Back to Analytics</a>
<div class="page-title">Archived Personnel</div>
<div class="page-sub">Hidden from the main list. Evaluation data is preserved and can be restored anytime.</div>
<?php if (empty($archived)): ?>
<div class="no-archived"><i class="fa-solid fa-box-archive"></i><p>No archived personnel.</p></div>
<?php else: ?>
<div class="people-list">
<?php foreach ($archived as $p):
    $avg = $p['avg_score'] !== null ? round($p['avg_score'],2) : null;
?>
<div class="person-row">
    <div class="person-header">
        <?php if($p['photo']): ?><img class="person-photo" src="../image/<?= htmlspecialchars($p['photo']) ?>" alt=""/>
        <?php else: ?><div class="person-photo-ph"><i class="fa-solid fa-user"></i></div><?php endif; ?>
        <div style="flex:1;">
            <div class="person-name"><?= htmlspecialchars($p['full_name']) ?></div>
            <div class="person-meta">
                <span class="archived-badge"><i class="fa-solid fa-box-archive"></i> Archived <?= date('M d, Y', strtotime($p['archived_at'])) ?></span>
                <span><?= $activeEval==='student' ? (($p['student_resolved_group'] ?? '')==='teacher' ? 'Faculty' : 'Staff') : ($p['role']==='teacher'?'Faculty':'Staff') ?> · <?= htmlspecialchars($p['designation']) ?></span>
                <span><?= $p['total_responses'] ?> evaluation<?= $p['total_responses']!=1?'s':'' ?></span>
                <?php if ($avg !== null): ?><span style="color:<?= scoreColor($avg) ?>;font-weight:700;"><?= number_format($avg,2) ?> avg</span><?php endif; ?>
            </div>
        </div>
        <a class="btn-restore" href="?restore_id=<?= $p['id'] ?>&group=<?= urlencode($groupFilter) ?>&eval_type=<?= $activeEval ?>"
           onclick="return confirm('Restore <?= htmlspecialchars(addslashes($p['full_name'])) ?> to the analytics list?')">
            <i class="fa-solid fa-rotate-left"></i> Restore
        </a>
    </div>
</div>
<?php endforeach; ?>
</div>
<?php endif; ?>
<?php $mysqli->close(); ?>
</main>

</body></html>
<?php exit; }

// ══════════════════════════════════════════════════════════════
// VIEW: MAIN LIST
// ══════════════════════════════════════════════════════════════
$whereRole = "u.role IN ('teacher','staff','faculty')";

$people = [];
$res = $mysqli->query("
    SELECT u.id, u.full_name, u.designation, u.photo, u.role, u.secondary_role, u.source, u.account_status,
           aa.archived_at,
           COUNT(DISTINCT et.id) AS total_responses,
           AVG(qa.answer_score)  AS avg_score,
           MAX(et.submitted_at)  AS last_evaluated
    FROM users u
    JOIN evaluation_tracker et ON et.target_user_id=u.id AND $evalTypeSql
    LEFT JOIN questionnaire_answers qa ON qa.tracker_id=et.id
    LEFT JOIN analytics_archive aa ON aa.target_user_id=u.id
    WHERE $whereRole AND u.is_active=1 AND $reportScopeSql
      AND (aa.id IS NULL)
    GROUP BY u.id
    ORDER BY avg_score DESC, u.full_name ASC
");
if ($res) $people = $res->fetch_all(MYSQLI_ASSOC);

// Match the Dean Reports classification: teaching Staff is Faculty, while
// genuine Non-Teaching Staff is Staff. Filter the Student Evaluation tabs by
// this resolved function instead of users.role or tracker context alone.
foreach ($people as &$person) {
    $person['student_resolved_group'] = principal_resolve_student_group($person, $mysqli) ?? '';
}
unset($person);
if ($activeEval === 'student') {
    if ($groupFilter === 'Faculty') {
        $people = array_values(array_filter($people, fn(array $p) => strtolower(trim((string)$p['student_resolved_group'])) === 'teacher'));
    } elseif ($groupFilter === 'Staff') {
        $people = array_values(array_filter($people, fn(array $p) => strtolower(trim((string)$p['student_resolved_group'])) === 'staff'));
    }
}

$top4 = array_slice($people, 0, 4);
$low4 = array_slice(array_reverse($people), 0, 4);

$totalResponses = array_sum(array_column($people,'total_responses'));
$scores         = array_filter(array_column($people,'avg_score'), fn($s) => $s !== null);
$overallAvg     = count($scores) ? round(array_sum($scores)/count($scores),2) : null;

$totalStudents = $mysqli->query("SELECT COUNT(*) as c FROM users us WHERE us.role='student' AND us.is_active=1 AND $reportStudentScopeSql")->fetch_assoc()['c'] ?? 0;
$facCount = 0;
$staffCount = 0;
$totalFacStaff = 0;

// Use the same resolved grouping as the main report list so the tab badges
// exactly match the personnel shown in each tab.
$studentRosterQ = $mysqli->query("SELECT id, role, sector, secondary_role, designation, source, account_status, is_active
    FROM users u
    WHERE u.is_active=1
      AND u.account_status='approved'
      AND u.role IN ('teacher','faculty','staff')
      AND $reportScopeSql");
if ($studentRosterQ) {
    while ($rosterUser = $studentRosterQ->fetch_assoc()) {
        $g = principal_resolve_student_group($rosterUser, $mysqli);
        if ($g === 'teacher') $facCount++;
        elseif ($g === 'staff') $staffCount++;
    }
    $studentRosterQ->free();
}
$totalFacStaff = $facCount + $staffCount;

$archivedCount = $mysqli->query("SELECT COUNT(*) as c FROM analytics_archive aa JOIN users u ON u.id=aa.target_user_id WHERE $reportScopeSql")->fetch_assoc()['c'] ?? 0;
$studentEvalCount = $mysqli->query("SELECT COUNT(DISTINCT et.id) as c
    FROM evaluation_tracker et
    JOIN users u ON u.id=et.target_user_id
    WHERE et.eval_type='student'
      AND $principalStudentEvaluatorSql
      AND COALESCE(et.evaluation_context,'teacher') IN ('teacher','staff')
      AND $reportScopeSql")->fetch_assoc()['c'] ?? 0;
$multiRoleEvalCount = 0;
$peerEvalCount = $mysqli->query("SELECT COUNT(DISTINCT et.id) as c FROM evaluation_tracker et JOIN users u ON u.id=et.target_user_id WHERE et.eval_type IN ('peer','faculty_peer','staff_peer') AND $reportScopeSql")->fetch_assoc()['c'] ?? 0;

pageHead('Reports & Analytics', $evalColor, $evalColorBg, $evalColorBorder);
?>
<style>
.page-header{display:flex;justify-content:space-between;align-items:flex-start;margin-bottom:24px;background:var(--mid);border:1px solid var(--border);border-radius:14px;padding:22px 26px;flex-wrap:wrap;gap:14px;}
.page-header h1{font-family:'Rajdhani',sans-serif;font-size:28px;font-weight:700;color:var(--light);margin-bottom:3px;}
.page-header p{font-size:13px;color:var(--muted);}
.header-actions{display:flex;gap:10px;flex-wrap:wrap;}
.btn-print{background:var(--accent);color:#fff;border:none;padding:10px 20px;border-radius:var(--radius);font-size:13px;font-weight:600;cursor:pointer;display:flex;align-items:center;gap:7px;transition:opacity .2s;font-family:'Inter',sans-serif;}
.btn-archived-link{background:var(--mid);color:var(--muted);border:1px solid var(--border);padding:10px 20px;border-radius:var(--radius);font-size:13px;font-weight:600;display:flex;align-items:center;gap:7px;text-decoration:none;transition:all .2s;}
.btn-print:hover{opacity:.85;}
.btn-archived-link{background:var(--mid);color:var(--muted);border:1px solid var(--border);padding:10px 20px;border-radius:var(--radius);font-size:13px;font-weight:600;display:flex;align-items:center;gap:7px;text-decoration:none;transition:all .2s;}
.btn-archived-link:hover{color:var(--light);}
.archived-count-badge{background:rgba(160,179,198,.2);border-radius:20px;padding:1px 8px;font-size:11px;font-weight:700;}
.group-tabs{display:flex;gap:8px;margin-bottom:20px;flex-wrap:wrap;}
.group-tab{display:flex;align-items:center;gap:8px;padding:10px 22px;border-radius:var(--radius);font-size:14px;font-weight:600;cursor:pointer;border:1px solid var(--border);background:var(--mid);color:var(--muted);text-decoration:none;transition:all .22s;}
.group-tab:hover{color:var(--light);}
.group-tab.active-all{background:rgba(59,130,246,.2);border-color:rgba(59,130,246,.5);color:#3B82F6;}
.group-tab.active-teacher{background:rgba(13,148,136,.2);border-color:rgba(13,148,136,.5);color:#0D9488;}
.group-tab.active-staff{background:rgba(217,119,6,.2);border-color:rgba(217,119,6,.5);color:#F59E0B;}
.tab-count{background:rgba(255,255,255,.12);border-radius:20px;padding:1px 8px;font-size:11px;font-weight:700;}
.desig-subtabs{display:flex;gap:6px;flex-wrap:wrap;margin-bottom:20px;padding:12px 16px;background:rgba(217,119,6,.06);border:1px solid rgba(217,119,6,.15);border-radius:10px;}
.desig-subtab{padding:5px 14px;border-radius:20px;font-size:12px;font-weight:600;background:rgba(255,255,255,.06);border:1px solid var(--border);color:var(--muted);cursor:pointer;text-decoration:none;transition:all .2s;}
.desig-subtab:hover{color:var(--light);}
.desig-subtab.active{background:rgba(217,119,6,.2);border-color:rgba(217,119,6,.4);color:#F59E0B;}
.summary-row{display:grid;grid-template-columns:repeat(auto-fill,minmax(180px,1fr));gap:16px;margin-bottom:24px;}
.sum-card{background:var(--mid);border:1px solid var(--border);border-radius:12px;padding:18px 20px;}
.sum-label{font-size:11px;text-transform:uppercase;letter-spacing:.8px;color:var(--muted);margin-bottom:8px;}
.sum-value{font-size:28px;font-weight:700;color:var(--light);}
.sum-sub{font-size:12px;color:var(--muted);margin-top:4px;}
.sum-card.highlight .sum-value{color:var(--ec);}
.standings-row{display:grid;grid-template-columns:1fr 1fr;gap:20px;margin-bottom:28px;}
.standing-panel{background:var(--mid);border:1px solid var(--border);border-radius:14px;padding:22px;}
.standing-title{font-size:15px;font-weight:700;margin-bottom:16px;display:flex;align-items:center;gap:8px;}
.standing-title.top{color:#4ade80;}
.standing-title.low{color:#f87171;}
.standing-item{display:flex;align-items:center;gap:12px;padding:10px 0;border-bottom:1px solid var(--border);}
.standing-item:last-child{border-bottom:none;}
.standing-rank{font-size:13px;font-weight:700;color:var(--muted);width:22px;text-align:center;flex-shrink:0;}
.standing-photo{width:38px;height:38px;border-radius:50%;object-fit:cover;border:2px solid var(--border);}
.standing-photo-ph{width:38px;height:38px;border-radius:50%;background:var(--inner);border:2px solid var(--border);display:flex;align-items:center;justify-content:center;color:var(--muted);font-size:14px;}
.standing-info{flex:1;}
.standing-name{font-size:13px;font-weight:600;color:var(--light);}
.standing-desig{font-size:11px;color:var(--muted);}
.standing-score{font-size:15px;font-weight:700;}
.score-top{color:#4ade80;}
.score-low{color:#f87171;}
.no-data{text-align:center;padding:24px;color:var(--muted);font-size:13px;}
.section-title{font-family:'Rajdhani',sans-serif;font-size:20px;font-weight:700;color:var(--light);margin-bottom:16px;display:flex;align-items:center;gap:10px;}
.people-list{display:flex;flex-direction:column;gap:14px;}
.person-row{background:var(--mid);border:1px solid var(--border);border-radius:14px;overflow:hidden;transition:all .2s;}
.person-row:hover{border-color:rgba(255,255,255,.2);box-shadow:0 4px 16px rgba(15,23,42,.1);}
.person-header{display:flex;align-items:center;gap:16px;padding:18px 22px;}
.person-link{display:flex;align-items:center;gap:16px;flex:1;text-decoration:none;color:inherit;min-width:0;}
.person-photo{width:52px;height:52px;border-radius:50%;object-fit:cover;border:2px solid var(--border);flex-shrink:0;}
.person-photo-ph{width:52px;height:52px;border-radius:50%;background:var(--inner);border:2px solid var(--border);display:flex;align-items:center;justify-content:center;color:var(--muted);font-size:20px;flex-shrink:0;}
.person-info{flex:1;min-width:0;}
.person-name{font-size:15px;font-weight:700;color:var(--light);margin-bottom:4px;}
.person-meta{display:flex;align-items:center;gap:8px;flex-wrap:wrap;}
.group-badge{display:inline-flex;align-items:center;gap:5px;padding:3px 11px;border-radius:20px;font-size:12px;font-weight:700;}
.group-badge.teacher{background:rgba(13,148,136,.18);color:#0D9488;border:1px solid rgba(13,148,136,.3);}
.group-badge.staff{background:rgba(217,119,6,.15);color:#F59E0B;border:1px solid rgba(217,119,6,.3);}
.desig-pill{display:inline-flex;align-items:center;gap:4px;padding:2px 9px;border-radius:20px;font-size:11px;font-weight:600;background:rgba(255,255,255,.07);color:var(--muted);border:1px solid var(--border);}
.person-stats{display:flex;gap:20px;align-items:center;flex-shrink:0;flex-wrap:wrap;}
.pstat{display:flex;flex-direction:column;align-items:center;gap:2px;}
.pstat-val{font-size:18px;font-weight:700;color:var(--light);}
.pstat-lbl{font-size:10px;color:var(--muted);text-transform:uppercase;letter-spacing:.5px;}
.pstat-val.good{color:#4ade80;}
.pstat-val.mid{color:var(--gold-h);}
.pstat-val.poor{color:#f87171;}
.score-bar-wrap{display:flex;align-items:center;gap:10px;min-width:140px;}
.score-bar-bg{flex:1;height:6px;background:rgba(255,255,255,.07);border-radius:3px;overflow:hidden;}
.score-bar-fill{height:100%;border-radius:3px;}
.arrow-icon{color:var(--muted);font-size:14px;flex-shrink:0;margin-left:4px;}
.person-actions{display:flex;align-items:center;gap:8px;flex-shrink:0;margin-left:12px;}
.btn-archive{background:none;border:1px solid var(--border);color:var(--muted);padding:9px 11px;border-radius:8px;font-size:13px;cursor:pointer;transition:all .2s;display:flex;align-items:center;gap:6px;font-family:'Inter',sans-serif;}
.btn-archive:hover{background:rgba(240,84,84,.12);border-color:rgba(240,84,84,.4);color:#f87171;}
.btn-archive span{font-size:12px;font-weight:600;}
.no-evaluated{text-align:center;padding:48px;background:var(--mid);border-radius:14px;border:1px solid var(--border);color:var(--muted);}
.no-evaluated i{font-size:36px;opacity:.3;display:block;margin-bottom:14px;}
/* peer note */
.peer-info-note{display:flex;align-items:flex-start;gap:10px;padding:12px 16px;border-radius:10px;margin-bottom:20px;font-size:12px;color:var(--muted);line-height:1.6;background:rgba(124,58,237,.06);border:1px solid rgba(124,58,237,.18);}
.peer-info-note i{color:#7C3AED;margin-top:1px;flex-shrink:0;}
@media(max-width:800px){.standings-row{grid-template-columns:1fr;}body{padding:16px;}}
@media(max-width:560px){.summary-row{grid-template-columns:1fr 1fr;}.person-header{flex-wrap:wrap;}.btn-archive span{display:none;}.eval-switcher{width:100%;}.eval-tab{flex:1;justify-content:center;}}

/* ── SHARP LIGHT ADMIN UI ── */
html { background:#F8FAFC; }
body {
  color:#0F172A !important;
  background:#F8FAFC !important;
  -webkit-font-smoothing:antialiased;
  text-rendering:optimizeLegibility;
}
h1,h2,h3,h4,h5,h6 { color:#0F172A; letter-spacing:-.01em; }
p, .subtitle, .description, .helper, .muted, small { color:#475569; }
label, th { color:#334155; font-weight:600; }
td { color:#0F172A; }
input, select, textarea {
  color:#0F172A;
  background:#FFFFFF;
  border-color:#CBD5E1;
}
input::placeholder, textarea::placeholder { color:#94A3B8; }
.card, .panel, .section, .table-card, .content-card {
  border-color:#CBD5E1;
  box-shadow:0 4px 14px rgba(15,23,42,.07);
}
button, .btn { font-weight:700; }
a { color:inherit; }

/* ── Student Evaluation nested Faculty / Multi-Role filters ──
   Principal theme preserved: gold/amber portal accent. */
.group-tabs{display:flex;align-items:center;gap:8px;flex-wrap:wrap;margin-bottom:12px;}
.group-tab{display:flex;align-items:center;gap:9px;padding:11px 20px;border-radius:10px;border:1px solid var(--border);background:var(--mid);color:var(--muted);text-decoration:none;font-size:13px;font-weight:700;transition:all .2s;}
.group-tab:hover{color:#fff;background:rgba(255,255,255,.05);}
.group-tab.active-all,.group-tab.active-faculty,.group-tab.active-staff{background:var(--portal-accent-bg);border-color:rgba(217,154,43,.45);color:var(--portal-accent-h);}
.group-tab > i{font-size:14px;}
.group-tab:not(.active-all):not(.active-faculty):not(.active-multi-role) > i{color:var(--portal-accent-h);}

/* Each top icon keeps its own accent color while the active tab uses the Principal theme. */
.group-tab.active-all > i,.group-tab.all-tab > i{color:#2563EB !important;}
.group-tab.faculty-tab > i{color:#0D9488 !important;}
.tab-count{background:rgba(255,255,255,.12);border-radius:20px;padding:1px 8px;font-size:11px;font-weight:700;color:inherit;}

.faculty-subtabs{display:none;}
.subtabs-label{font-size:11px;color:var(--muted);font-weight:700;text-transform:uppercase;letter-spacing:.7px;margin:0 4px 0 2px;}
.faculty-subtab{display:flex;align-items:center;gap:7px;padding:8px 16px;border-radius:10px;border:1px solid transparent;color:var(--muted);text-decoration:none;font-size:13px;font-weight:700;transition:all .2s;}
.faculty-subtab:hover{color:#fff;background:var(--portal-accent-bg);}
.faculty-subtab.active{background:var(--portal-accent-bg);border-color:rgba(217,154,43,.4);color:var(--portal-accent-h);}
.faculty-subtab i{color:var(--portal-accent-h);}
.faculty-subtab:nth-of-type(1) i{color:#2563EB !important;}
.faculty-subtab:nth-of-type(2) i{color:#7C3AED !important;}
.multi-role-subtabs .faculty-subtab:nth-of-type(1) i{color:#0D9488 !important;}
.multi-role-subtabs .faculty-subtab:nth-of-type(2) i{color:#2563EB !important;}
.multi-role-subtabs .faculty-subtab:nth-of-type(3) i{color:#7C3AED !important;}
</style>
</head><body>
<?php render_exec_sidebar('reports', $me, $photo_src, $principalScopeLabel); ?>
<main class="main">


<?php if ($toast): ?><div class="toast"><i class="fa-solid fa-circle-check"></i><?= htmlspecialchars($toast) ?></div><?php endif; ?>

<!-- ── EVAL TYPE SWITCHER ── -->
<?php $groupForOtherTabs = in_array($groupFilter, ['Faculty','Teacher','Staff'], true) ? $groupFilter : 'All'; ?>
<div class="eval-switcher">
    <a href="?group=<?= urlencode($groupFilter) ?>&eval_type=student" class="eval-tab student <?= $activeEval==='student'?'active':'' ?>">
        <i class="fa-solid fa-graduation-cap"></i> Student Evaluation
        <span class="tab-badge"><?= $studentEvalCount ?></span>
    </a>
    <div class="eval-divider"></div>
    <a href="?group=<?= urlencode($groupForOtherTabs) ?>&eval_type=peer" class="eval-tab peer <?= $activeEval==='peer'?'active':'' ?>">
        <i class="fa-solid fa-people-arrows"></i> Peer-to-Peer
        <span class="tab-badge"><?= $peerEvalCount ?></span>
    </a>
</div>

<div class="page-header">
    <div>
        <h1>Reports &amp; Analytics <span style="font-size:18px;color:var(--ec);font-family:'Inter',sans-serif;font-weight:400;margin-left:6px;">— <?= $evalLabel ?></span></h1>
        <p>Only showing personnel who have been evaluated<?= $activeEval==='peer'?' by colleagues':' by students' ?></p>
    </div>
    <div class="header-actions">
        <a href="?view=archived&group=<?= urlencode($groupFilter) ?>&eval_type=<?= $activeEval ?>" class="btn-archived-link"><i class="fa-solid fa-box-archive"></i> Archived<?php if ($archivedCount > 0): ?><span class="archived-count-badge"><?= $archivedCount ?></span><?php endif; ?></a>
        <button class="btn-print" onclick="window.print()"><i class="fa-solid fa-print"></i> Print / Save PDF</button>
    </div>
</div>


<?php if ($activeEval === 'student'): ?>
<div class="student-report-groups">
<div class="group-tabs">
    <a href="?group=All&eval_type=student" class="group-tab <?= $groupFilter==='All'?'active-all':'' ?>"><i class="fa-solid fa-users"></i> All <span class="tab-count"><?= $totalFacStaff ?></span></a>
    <a href="?group=Faculty&eval_type=student" class="group-tab faculty-tab <?= $groupFilter==='Faculty'?'active-faculty':'' ?>"><i class="fa-solid fa-building-columns"></i> Faculty <span class="tab-count"><?= $facCount ?></span></a>
    <a href="?group=Staff&eval_type=student" class="group-tab staff-tab <?= $groupFilter==='Staff'?'active-staff':'' ?>"><i class="fa-solid fa-briefcase"></i> Staff <span class="tab-count"><?= $staffCount ?></span></a>
</div>
</div>
<?php else: ?>
<div class="peer-report-groups">
<div class="group-tabs">
    <a href="?group=All&eval_type=peer" class="group-tab <?= $groupFilter==='All'?'active-all':'' ?>"><i class="fa-solid fa-users"></i> All <span class="tab-count"><?= $facCount+$staffCount ?></span></a>
    <a href="?group=Teacher&eval_type=peer" class="group-tab <?= $groupFilter==='Teacher'?'active-teacher':'' ?>"><i class="fa-solid fa-chalkboard-user"></i> Teacher <span class="tab-count"><?= $facCount ?></span></a>
    <a href="?group=Staff&eval_type=peer" class="group-tab <?= $groupFilter==='Staff'?'active-staff':'' ?>"><i class="fa-solid fa-briefcase"></i> Staff <span class="tab-count"><?= $staffCount ?></span></a>
</div>
</div>
<?php endif; ?>

<!-- PEOPLE TABLE ── layout ported from the Admin/EA Evaluation Report
     (Export/Search/Filters toolbar + sortable table + pagination), restyled
     in the Principal portal's white / amber palette rather than
     the Principal's white theme applied by the page-level report styling above. Confidentiality:
     $people is still built exclusively from the hardened $reportScopeSql, so
     this table can never list a Principal/Dean/EA/superadmin row or the
     logged-in Principal themselves. -->
<style>
.ra-table-card{background:var(--mid);border:1px solid var(--border);border-radius:14px;padding:22px 24px;}
.ra-table-head{display:flex;align-items:center;justify-content:space-between;gap:16px;flex-wrap:wrap;margin-bottom:16px;}
.ra-table-head .section-title{margin-bottom:0;}
.ra-table-tools{display:flex;align-items:center;gap:10px;}
.ra-tool-btn{padding:9px 14px;border-radius:8px;border:1px solid var(--border);background:var(--inner);color:var(--light);font-size:13px;font-weight:600;cursor:pointer;display:inline-flex;align-items:center;gap:7px;white-space:nowrap;}
.ra-tool-btn:hover{border-color:var(--ec);color:var(--ec);}
.ra-filters-wrap{position:relative;}
.ra-filters-panel{display:none;position:absolute;right:0;top:calc(100% + 6px);background:var(--mid);border:1px solid var(--border);border-radius:10px;padding:14px;min-width:220px;box-shadow:0 8px 24px rgba(0,0,0,.4);z-index:20;}
.ra-filters-panel.open{display:block;}
.ra-filter-row{display:flex;flex-direction:column;gap:4px;margin-bottom:12px;}
.ra-filter-row label{font-size:11px;text-transform:uppercase;letter-spacing:.5px;color:var(--muted);}
.ra-filter-row select{padding:7px 10px;border-radius:7px;border:1px solid var(--border);background:var(--inner);color:var(--light);font-size:13px;}
.ra-filter-clear{width:100%;padding:7px;border-radius:7px;border:1px solid var(--border);background:transparent;color:var(--muted);font-size:12px;font-weight:600;cursor:pointer;}
.ra-filter-clear:hover{color:var(--ec);border-color:var(--ec);}
.ra-search-wrap{position:relative;}
.ra-search-wrap i{position:absolute;left:12px;top:50%;transform:translateY(-50%);color:var(--muted);font-size:13px;}
.ra-search{padding:9px 12px 9px 32px;border-radius:8px;border:1px solid var(--border);font-size:13px;background:var(--inner);color:var(--light);min-width:220px;}
.ra-table-wrap{overflow-x:auto;}
table.ra-table{width:100%;border-collapse:collapse;font-size:13px;}
table.ra-table th{text-align:left;padding:10px 12px;font-size:11px;text-transform:uppercase;letter-spacing:.5px;color:var(--muted);border-bottom:1px solid var(--border);white-space:nowrap;}
table.ra-table td{padding:12px;border-bottom:1px solid var(--border);vertical-align:middle;}
table.ra-table thead tr{background:transparent !important;}
table.ra-table tbody tr:hover{background:var(--inner) !important;}
.ra-name-cell{display:flex;align-items:center;gap:10px;}
.ra-photo{width:34px;height:34px;border-radius:50%;object-fit:cover;border:1px solid var(--border);flex-shrink:0;}
.ra-photo-ph{width:34px;height:34px;border-radius:50%;background:var(--inner);border:1px solid var(--border);display:flex;align-items:center;justify-content:center;color:var(--muted);font-size:13px;flex-shrink:0;}
.ra-name-text a{font-weight:700;color:var(--light);text-decoration:none;}
.ra-name-text a:hover{color:var(--ec);}
.ra-role-badge{font-size:11px;font-weight:700;padding:3px 9px;border-radius:20px;white-space:nowrap;display:inline-flex;align-items:center;gap:5px;}
.ra-score{font-weight:700;}
.ra-view-btn{padding:6px 14px;border-radius:7px;border:1px solid var(--portal-accent,var(--border));background:var(--portal-accent-bg,transparent);color:var(--portal-accent-h,inherit);font-size:12px;font-weight:700;text-decoration:none;display:inline-flex;align-items:center;gap:6px;}
.ra-view-btn:hover{opacity:.85;}
.ra-icon-btn{padding:6px 10px;border-radius:7px;border:1px solid var(--border);background:transparent;color:var(--muted);font-size:12px;font-weight:700;cursor:pointer;display:inline-flex;align-items:center;gap:6px;}
.ra-icon-btn:hover{border-color:#f87171;color:#f87171;}
.ra-actions-cell{display:flex;gap:8px;white-space:nowrap;}
.ra-pager{display:flex;align-items:center;justify-content:space-between;margin-top:16px;flex-wrap:wrap;gap:10px;}
.ra-pager-info{font-size:12px;color:var(--muted);}
.ra-pager-btns{display:flex;gap:6px;}
.ra-pager-btns button{width:30px;height:30px;border-radius:7px;border:1px solid var(--border);background:var(--inner);color:var(--light);font-size:12px;font-weight:700;cursor:pointer;}
.ra-pager-btns button.active{background:var(--portal-accent,var(--ec));border-color:var(--portal-accent,var(--ec));color:#fff;}
.ra-pager-btns button:disabled{opacity:.4;cursor:not-allowed;}
@media print{.no-print{display:none!important;}}
</style>

<div class="ra-table-card">
    <div class="ra-table-head">
        <div class="section-title">
            <i class="fa-solid fa-list-check" style="color:var(--accent)"></i>
            <?= $activeEval==='student' ? 'Users Being Evaluated' : 'Evaluated Personnel' ?>
            <span style="font-size:13px;font-weight:400;color:var(--muted)">(<?= count($people) ?> evaluated)</span>
        </div>
        <?php if (!empty($people)): ?>
        <div class="ra-table-tools no-print">
            <button class="ra-tool-btn" onclick="raExportCsv()"><i class="fa-solid fa-download"></i> Export</button>
            <div class="ra-search-wrap">
                <i class="fa-solid fa-magnifying-glass"></i>
                <input type="text" id="raSearch" class="ra-search" placeholder="Search name, designation..." oninput="raFilterTable()">
            </div>
            <div class="ra-filters-wrap">
                <button class="ra-tool-btn" id="raFiltersToggle" onclick="raToggleFilters()"><i class="fa-solid fa-filter"></i> Filters</button>
                <div class="ra-filters-panel" id="raFiltersPanel">
                    <div class="ra-filter-row">
                        <label>Min score</label>
                        <select id="raMinScore" onchange="raFilterTable()">
                            <option value="0">Any</option>
                            <option value="4.5">4.50 (Excellent)</option>
                            <option value="3.5">3.50 (Very Good)</option>
                            <option value="2.5">2.50 (Good)</option>
                        </select>
                    </div>
                    <div class="ra-filter-row">
                        <label>Role</label>
                        <select id="raRoleFilter" onchange="raFilterTable()">
                            <option value="all">All</option>
                            <?php if ($activeEval === 'student'): ?>
                                <option value="faculty">Faculty</option>
                                <option value="staff">Staff</option>
                            <?php else: ?>
                                <option value="teacher">Teacher</option>
                                <option value="staff">Staff</option>
                            <?php endif; ?>
                        </select>
                    </div>
                    <button class="ra-filter-clear" onclick="raClearFilters()">Clear filters</button>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <?php if (empty($people)): ?>
    <div class="no-evaluated">
        <i class="fa-solid fa-hourglass-half"></i>
        <p>No <?= $activeEval==='peer'?'peer-to-peer':'student' ?> evaluations have been submitted yet.<br>
        <small style="font-size:12px;opacity:.6">Personnel will appear here once <?= $evaluatorNounP ?> have evaluated them.</small></p>
    </div>
    <?php else: ?>
    <div class="ra-table-wrap">
    <table class="ra-table" id="raTable">
        <thead>
            <tr>
                <th>No.</th>
                <th>Name</th>
                <th>Designation</th>
                <th>Role</th>
                <th>Overall Avg. Score</th>
                <th>Last Evaluated</th>
                <th class="no-print">Actions</th>
            </tr>
        </thead>
        <tbody>
        <?php $rowNum = 0; foreach ($people as $p):
            $rowNum++;
            $avg        = $p['avg_score'] !== null ? round($p['avg_score'],2) : null;
            $color      = scoreColor($avg);
            $isArchived = !empty($p['archived_at']);
            if ($activeEval === 'student') {
                $resolvedGroup = strtolower(trim((string)($p['student_resolved_group'] ?? '')));
                $roleKey = $resolvedGroup === 'teacher' ? 'faculty' : 'staff';
                $roleLabel = $resolvedGroup === 'teacher' ? 'Faculty' : 'Staff';
                $roleIcon = $resolvedGroup === 'teacher' ? 'fa-chalkboard-user' : 'fa-briefcase';
                $roleBadgeStyle = $resolvedGroup === 'teacher'
                    ? 'background:rgba(13,148,136,.18);color:#0D9488;border:1px solid rgba(13,148,136,.3);'
                    : 'background:rgba(124,58,237,.15);color:#A78BFA;border:1px solid rgba(124,58,237,.3);';
            } else {
                $rawRole = strtolower(trim((string)($p['role'] ?? '')));
                $isFac = in_array($rawRole, ['teacher','faculty'], true);
                $roleKey = $isFac ? 'teacher' : 'staff';
                $roleLabel = $isFac ? 'Faculty' : 'Staff';
                $roleIcon = $isFac ? 'fa-chalkboard-user' : 'fa-briefcase';
                $roleBadgeStyle = $isFac
                    ? 'background:rgba(13,148,136,.18);color:#0D9488;border:1px solid rgba(13,148,136,.3);'
                    : 'background:rgba(124,58,237,.15);color:#A78BFA;border:1px solid rgba(124,58,237,.3);';
            }
        ?>
            <tr data-search="<?= htmlspecialchars(strtolower($p['full_name'].' '.$p['designation'])) ?>" data-desig="<?= htmlspecialchars($p['designation']) ?>" data-score="<?= $avg !== null ? $avg : '' ?>" data-role="<?= $roleKey ?>">
                <td><?= $rowNum ?></td>
                <td>
                    <div class="ra-name-cell">
                        <?php if($p['photo']): ?><img class="ra-photo" src="../image/<?= htmlspecialchars($p['photo']) ?>" alt=""/>
                        <?php else: ?><div class="ra-photo-ph"><i class="fa-solid fa-user"></i></div><?php endif; ?>
                        <div class="ra-name-text">
                            <a href="?view=students&target_id=<?= $p['id'] ?>&group=<?= urlencode($groupFilter) ?>&eval_type=<?= $activeEval ?>"><?= htmlspecialchars($p['full_name']) ?></a>
                            <?php if ($isArchived): ?>
                            <div><span style="font-size:11px;color:var(--muted);"><i class="fa-solid fa-box-archive"></i> Archived</span></div>
                            <?php endif; ?>
                        </div>
                    </div>
                </td>
                <td><?= htmlspecialchars($p['designation']) ?></td>
                <td><span class="ra-role-badge" style="<?= $roleBadgeStyle ?>"><i class="fa-solid <?= $roleIcon ?>"></i> <?= $roleLabel ?></span></td>
                <td class="ra-score" style="color:<?= $color ?>"><?= $avg !== null ? number_format($avg,2) : '—' ?></td>
                <td><?= !empty($p['last_evaluated']) ? date('M d, Y', strtotime($p['last_evaluated'])) : '—' ?></td>
                <td>
                    <div class="ra-actions-cell no-print">
                        <a class="ra-view-btn" href="?view=students&target_id=<?= $p['id'] ?>&group=<?= urlencode($groupFilter) ?>&eval_type=<?= $activeEval ?>"><i class="fa-solid fa-eye"></i> View</a>
                        <?php if ($isArchived): ?>
                        <a class="ra-icon-btn" href="?restore_id=<?= $p['id'] ?>&group=<?= urlencode($groupFilter) ?>&eval_type=<?= $activeEval ?>&view=archived"><i class="fa-solid fa-rotate-left"></i> Restore</a>
                        <?php else: ?>
                        <button class="ra-icon-btn" onclick="archivePerson(<?= $p['id'] ?>,'<?= htmlspecialchars(addslashes($p['full_name'])) ?>')"><i class="fa-solid fa-box-archive"></i> Archive</button>
                        <?php endif; ?>
                    </div>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    </div>
    <div class="ra-pager no-print">
        <div class="ra-pager-info" id="raPagerInfo"></div>
        <div class="ra-pager-btns" id="raPagerBtns"></div>
    </div>
    <?php endif; ?>
</div>

<script>
(function(){
    const pageSize = 10;
    let currentPage = 1;
    const table = document.getElementById('raTable');
    if (!table) return;
    const allRows = Array.from(table.querySelectorAll('tbody tr'));

    function visibleRows() {
        return allRows.filter(r => r.dataset.matched !== 'false');
    }

    function render() {
        const vis = visibleRows();
        const totalPages = Math.max(1, Math.ceil(vis.length / pageSize));
        if (currentPage > totalPages) currentPage = totalPages;
        allRows.forEach(r => r.style.display = 'none');
        const start = (currentPage - 1) * pageSize;
        vis.slice(start, start + pageSize).forEach(r => r.style.display = '');

        const info = document.getElementById('raPagerInfo');
        if (vis.length === 0) {
            info.textContent = 'No matching entries';
        } else {
            info.textContent = `Showing ${start + 1} to ${Math.min(start + pageSize, vis.length)} of ${vis.length} entries`;
        }

        const btnsWrap = document.getElementById('raPagerBtns');
        btnsWrap.innerHTML = '';
        const prev = document.createElement('button');
        prev.innerHTML = '<i class="fa-solid fa-chevron-left"></i>';
        prev.disabled = currentPage === 1;
        prev.onclick = () => { currentPage--; render(); };
        btnsWrap.appendChild(prev);
        for (let i = 1; i <= totalPages; i++) {
            const b = document.createElement('button');
            b.textContent = i;
            if (i === currentPage) b.classList.add('active');
            b.onclick = () => { currentPage = i; render(); };
            btnsWrap.appendChild(b);
        }
        const next = document.createElement('button');
        next.innerHTML = '<i class="fa-solid fa-chevron-right"></i>';
        next.disabled = currentPage === totalPages;
        next.onclick = () => { currentPage++; render(); };
        btnsWrap.appendChild(next);
    }

    window.raFilterTable = function() {
        const q = document.getElementById('raSearch').value.trim().toLowerCase();
        const minScore = parseFloat(document.getElementById('raMinScore').value) || 0;
        const roleVal = document.getElementById('raRoleFilter').value;
        allRows.forEach(r => {
            const matchesSearch = !q || r.dataset.search.includes(q);
            const rowScore = parseFloat(r.dataset.score);
            const matchesScore = minScore === 0 || (!isNaN(rowScore) && rowScore >= minScore);
            const matchesRole = roleVal === 'all' || r.dataset.role === roleVal;
            r.dataset.matched = (matchesSearch && matchesScore && matchesRole) ? 'true' : 'false';
        });
        currentPage = 1;
        render();
    };

    window.raToggleFilters = function() {
        document.getElementById('raFiltersPanel').classList.toggle('open');
    };

    window.raClearFilters = function() {
        document.getElementById('raMinScore').value = '0';
        document.getElementById('raRoleFilter').value = 'all';
        raFilterTable();
    };

    document.addEventListener('click', function(e) {
        const wrap = document.querySelector('.ra-filters-wrap');
        if (wrap && !wrap.contains(e.target)) {
            const panel = document.getElementById('raFiltersPanel');
            if (panel) panel.classList.remove('open');
        }
    });

    window.raExportCsv = function() {
        const rows = [['No.','Name','Designation','Role','Overall Avg. Score','Last Evaluated']];
        visibleRows().forEach((r, i) => {
            const cells = r.querySelectorAll('td');
            rows.push([i+1, cells[1].innerText.trim().split('\n')[0], cells[2].innerText.trim(), cells[3].innerText.trim(), cells[4].innerText.trim(), cells[5].innerText.trim()]);
        });
        const csv = rows.map(row => row.map(v => `"${String(v).replace(/"/g,'""')}"`).join(',')).join('\n');
        const blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' });
        const link = document.createElement('a');
        link.href = URL.createObjectURL(blob);
        link.download = 'evaluated_personnel.csv';
        link.click();
    };

    allRows.forEach(r => r.dataset.matched = 'true');
    render();
})();

function archivePerson(id, name) {
    if (confirm(`Archive "${name}"? They'll be hidden from this list but their evaluation data is kept and can be restored anytime.`)) {
        window.location.href = `?archive_id=${id}&group=<?= urlencode($groupFilter) ?>&eval_type=<?= $activeEval ?>`;
    }
}
</script>
<?php $mysqli->close(); ?>

<style id="principal-reports-final-white-theme">
/* Final white theme for Principal Reports content. Keep the sidebar navy. */
html{background:#F8FAFC!important;color-scheme:light!important;}
body{background:#fff!important;color:#172033!important;}
.main{background:#fff!important;color:#172033!important;}
.main > *{color:#172033;}

/* Page headers and report containers */
.page-header,
.eval-switcher,
.eval-banner,
.group-tab-wrap,
.group-tabs,
.desig-subtabs,
.sum-card,
.standing-panel,
.person-row,
.no-evaluated,
.target-card,
.eval-card,
.comment-section,
.avg-summary,
.top-bar,
.history-card,
.section,
.content-panel,
.no-archived,
.table-wrap,
.card,
.panel,
.stat-card,
.table-card,
.content-card,
.info-banner,
.gl-card,
.amber-card,
.green-card,
.red-card,
.cat-section,
.people-list,
.evaluator-grid{
    background:#fff!important;
    color:#172033!important;
    border-color:#e2e8f0!important;
    box-shadow:0 4px 18px rgba(15,23,42,.08)!important;
}

.page-header h1,
.page-title,
.section-title,
.sheet-name,
.target-name,
.person-name,
.standing-name,
.sum-value,
.main h1,.main h2,.main h3,.main h4,.main h5,.main h6{
    color:#0f172a!important;
}
.page-header p,
.page-sub,
.sheet-desig,
.target-desig,
.standing-desig,
.person-meta,
.sum-label,
.sum-sub,
.pstat-lbl,
.muted,
.no-data,
.hint,
.helper,
.description,
.subtitle,
.main p,
.main small{
    color:#475569!important;
}

/* Tabs / filters */
.eval-switcher,.tabs,.level-tabs,.status-tabs{background:#fff!important;border-color:#e2e8f0!important;box-shadow:none!important;}
.eval-tab,.tab,.level-tab,.status-tab,.group-tab,.desig-subtab{color:#475569!important;background:transparent!important;}
.eval-tab:hover,.tab:hover,.level-tab:hover,.status-tab:hover,.group-tab:hover,.desig-subtab:hover{color:#172033!important;background:#f8fafc!important;}
.eval-tab.student.active{background:#eff6ff!important;color:#2563eb!important;}
.eval-tab.peer.active{background:#f5f3ff!important;color:#7c3aed!important;}
.eval-tab.multi-role.active{background:#fff7ed!important;color:#d97706!important;}
.group-tab.active,.desig-subtab.active{background:#fff7ed!important;color:#b45309!important;border-color:#fed7aa!important;}
.tab-badge,.tab-count{background:#f1f5f9!important;color:#475569!important;}
.eval-tab.student.active .tab-badge{background:#dbeafe!important;color:#2563eb!important;}
.eval-tab.peer.active .tab-badge{background:#ede9fe!important;color:#7c3aed!important;}
.eval-tab.multi-role.active .tab-badge{background:#ffedd5!important;color:#d97706!important;}

/* Tables and report data */
table,.q-table{background:#fff!important;color:#172033!important;}
table thead th,.q-table th,th{background:#f8fafc!important;color:#334155!important;border-color:#e2e8f0!important;}
table tbody td,.q-table td,td{background:#fff!important;color:#172033!important;border-color:#e2e8f0!important;}
tbody tr:hover td,.q-table tr:hover td{background:#f8fafc!important;}

/* Inputs */
input,select,textarea,
.search-box input[type=text],.search-box select,
.form-group input,.filter-field select,.filter-field input[type=text]{
    background:#fff!important;
    color:#172033!important;
    border-color:#cbd5e1!important;
}
input::placeholder,textarea::placeholder{color:#94a3b8!important;}

/* Buttons / links */
.btn-archived-link,.back-btn,.btn-cancel,.btn-icon,.btn-back{background:#fff!important;color:#475569!important;border-color:#cbd5e1!important;}
.btn-archived-link:hover,.back-btn:hover,.btn-cancel:hover,.btn-icon:hover,.btn-back:hover{background:#f8fafc!important;color:#172033!important;}

/* Bars, comments, empty states */
.score-bar-bg,.eval-bar-bg,.avg-bar-bg,.bar-wrap{background:#e2e8f0!important;}
.comment-text{background:#f8fafc!important;color:#334155!important;border-color:#e2e8f0!important;}
.empty-note,.no-comment,.no-eval,.empty-state,.empty-cta{color:#64748b!important;}

/* Keep sidebar in the established navy theme */
.sidebar{background:#0a192f!important;color:#e0e6f0!important;border-right:1px solid #172a45!important;box-shadow:none!important;}
.sb-name{color:#fff!important;}
.sb-role{color:#f0b84d!important;}
.sb-scope,.sb-nav a{color:#a0b3c6!important;}
.sb-nav a:hover,.sb-nav a.active{background:#172a45!important;color:#fff!important;}
.sb-nav a i{color:#f0b84d!important;}

/* Print remains clean and white */
@media print{
    html,body,.main{background:#fff!important;color:#000!important;}
}
</style>

<style id="principal-f8fafc-workspace-final">
/* Principal workspace canvas: integrated light background matching the EA view. */
html, body {
  background: #F8FAFC !important;
  background-image: none !important;
  color: #172033 !important;
}
main, main.main, .main, .main-content, .content, .page-content {
  background: #F8FAFC !important;
  color: #172033 !important;
}
.sidebar { background: #0A192F !important; }
@media print {
  html, body, main, main.main, .main, .main-content, .content, .page-content {
    background: #fff !important;
  }
}
</style>

<style id="principal-sidebar-placement-and-spacing-final">
/* Corrected principal navigation placement: main pages first, settings in administration, logout in account. */
.sidebar .sb-nav{display:flex!important;flex-direction:column!important;gap:5px!important;margin-top:10px!important;width:100%!important;}
.sidebar .sb-nav-section-label{width:auto!important;margin:5px 12px 1px!important;padding:0 2px!important;color:#A0B3C6!important;font-size:10px!important;font-weight:800!important;letter-spacing:1.35px!important;line-height:1.2!important;text-transform:uppercase!important;}
.sidebar .sb-nav-section-label:first-child{margin-top:0!important;}
.sidebar .sb-nav a{box-sizing:border-box!important;width:100%!important;min-height:42px!important;margin:0!important;padding:5px 14px!important;display:flex!important;align-items:center!important;gap:10px!important;border-radius:8px!important;font-size:14px!important;font-weight:500!important;}
.sidebar .sb-nav a i{width:18px!important;flex:0 0 18px!important;text-align:center!important;}
.sidebar .sb-nav .sb-logout-link{color:#fca5a5!important;}
.sidebar .sb-nav .sb-logout-link:hover{background:rgba(240,84,84,.12)!important;color:#fecaca!important;}
</style>
</body>
</html>

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
