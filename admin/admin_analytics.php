<?php
// admin/admin_analytics.php
session_start();
require_once 'db.php';
require_once '../shared/EvaluationContextService.php';

// ── AUTH GUARD ───────────────────────────────────────────────
// Evaluation scores and archive/restore actions are sensitive —
// require an authenticated admin-level session before anything else runs.
if (empty($_SESSION['user_id']) || !in_array($_SESSION['role'] ?? '', ['superadmin','admin'])) {
    header("Location: admin_login.php");
    exit;
}

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

// education_level / year_level — lets the Evaluators List be filtered to a
// specific grade (Basic Ed) or year (Higher Ed) of student evaluators.
$elCol = $mysqli->query("SHOW COLUMNS FROM users LIKE 'education_level'");
if ($elCol && $elCol->num_rows === 0) {
    $mysqli->query("ALTER TABLE users ADD COLUMN education_level ENUM('basic_ed','higher_ed') NULL DEFAULT NULL AFTER role");
    $mysqli->query("ALTER TABLE users ADD INDEX idx_education_level (education_level)");
}
$ylCol = $mysqli->query("SHOW COLUMNS FROM users LIKE 'year_level'");
if ($ylCol && $ylCol->num_rows === 0) {
    $mysqli->query("ALTER TABLE users ADD COLUMN year_level VARCHAR(30) NULL DEFAULT NULL AFTER education_level");
    $mysqli->query("ALTER TABLE users ADD INDEX idx_year_level (year_level)");
}

// Evaluation context keeps separate questionnaires/analytics for the same
// person when they can be evaluated as Teacher, Staff, or Multi-Role.
$ctxCol = $mysqli->query("SHOW COLUMNS FROM evaluation_tracker LIKE 'evaluation_context'");
if ($ctxCol && $ctxCol->num_rows === 0) {
    $mysqli->query("ALTER TABLE evaluation_tracker ADD COLUMN evaluation_context VARCHAR(30) NOT NULL DEFAULT 'teacher' AFTER period_id");
    $mysqli->query("ALTER TABLE evaluation_tracker ADD INDEX idx_eval_context (evaluation_context)");
    $mysqli->query("UPDATE evaluation_tracker et JOIN users u ON u.id=et.target_user_id SET et.evaluation_context=CASE WHEN u.role IN ('principal','dean') THEN 'school_head' WHEN u.role='staff' THEN 'staff' WHEN u.role='teacher' THEN 'teacher' ELSE et.evaluation_context END WHERE et.evaluation_context='teacher'");
}

// ── ARCHIVE / RESTORE ─────────────────────────────────────────
if (isset($_GET['archive_id'])) {
    $aid  = intval($_GET['archive_id']);
    $stmt = $mysqli->prepare("INSERT IGNORE INTO analytics_archive (target_user_id) VALUES (?)");
    $stmt->bind_param("i", $aid); $stmt->execute(); $stmt->close();
    $_SESSION['toast'] = "Personnel archived. Their data is kept and can be restored anytime.";
    header("Location: admin_analytics.php?group=".urlencode($_GET['group']??'All')."&eval_type=".urlencode($_GET['eval_type']??'student')."&evaluator=".urlencode($_GET['evaluator']??'All')); exit;
}
if (isset($_GET['restore_id'])) {
    $rid  = intval($_GET['restore_id']);
    $stmt = $mysqli->prepare("DELETE FROM analytics_archive WHERE target_user_id=?");
    $stmt->bind_param("i", $rid); $stmt->execute(); $stmt->close();
    $_SESSION['toast'] = "Personnel restored to the main list.";
    header("Location: admin_analytics.php?group=".urlencode($_GET['group']??'All')."&eval_type=".urlencode($_GET['eval_type']??'student')."&evaluator=".urlencode($_GET['evaluator']??'All')."&view=archived"); exit;
}

$toast = $_SESSION['toast'] ?? ''; unset($_SESSION['toast']);

// ── ACTIVE EVAL TYPE ──────────────────────────────────────────
// "Multi-Role" used to be its own top-level tab (eval_type=multi_role). It now
// lives inside the Student Evaluation tab as a group filter pill instead, so
// old links/bookmarks are redirected there with that pill pre-selected.
$requestedEvalType = $_GET['eval_type'] ?? 'student';
$legacyMultiRoleLink = ($requestedEvalType === 'multi_role');
$activeEval = $legacyMultiRoleLink ? 'student' : $requestedEvalType;
if (!in_array($activeEval, ['student','peer','schoolhead'], true)) $activeEval = 'student';

// ── GROUP FILTER (All / Teacher / Staff / Multi-Role) ──────────
// Resolved before the eval-type SQL below because the Multi-Role filter now
// changes which rows the "student" eval type pulls in.
$groupFilter = $_GET['group'] ?? ($legacyMultiRoleLink ? 'MultiRole' : 'All');
// School Head Evaluation's targets are Faculty and EA (Principal/Dean are
// the evaluators, filtered separately below via $evaluatorFilter) —
// 'Faculty'/'EA' are new group values, distinct from every other tab's
// Teacher/Staff values, so they can't collide with student/peer filtering.
$allowedGroups = $activeEval === 'student'
    ? ['All','Teacher','Staff','MultiRole','SchoolHead']
    : ($activeEval === 'schoolhead' ? ['All','Faculty','EA'] : ($activeEval === 'peer' ? ['All','Teacher','Staff','SchoolHead'] : ['All','Teacher','Staff']));
if (!in_array($groupFilter, $allowedGroups, true)) $groupFilter = 'All';

// School Head Evaluation's evaluator filter (Principal / Dean / All) — a
// second, independent axis from the target group filter above. Only
// meaningful for the schoolhead tab; forced to 'All' everywhere else so a
// stray query param can't silently filter other tabs' SQL paths.
$evaluatorFilter = $_GET['evaluator'] ?? 'All';
if (!in_array($evaluatorFilter, ['All','Principal','Dean'], true)) $evaluatorFilter = 'All';
if ($activeEval !== 'schoolhead') $evaluatorFilter = 'All';

// Faculty and Staff are independent top-level group tabs (no more merged
// "Faculty" tab with a nested Teacher/Staff sub-toggle). The underlying
// group values remain Teacher/Staff so all existing filtering, scoring,
// archive, and report logic continues to work unchanged.

// True only when we're on the Student Evaluation tab with the Multi-Role pill
// selected. Keeps the original Multi-Role query logic completely intact —
// it's just triggered by the group pill instead of a separate tab now.
$isMultiRole = ($activeEval === 'student' && $groupFilter === 'MultiRole');

// When Multi-Role is selected, it can be narrowed by the person's BASE
// function without changing the Multi-Role evaluation context itself.
// `all` shows every current Multi-Role user, while `teacher` and `staff`
// show only Multi-Role users whose primary account role is Teacher/Staff.
$multiRoleFilter = strtolower(trim((string)($_GET['mr_filter'] ?? 'all')));
if (!$isMultiRole && !in_array($multiRoleFilter, ['all','teacher','staff'], true)) {
    $multiRoleFilter = 'all';
}
if (!in_array($multiRoleFilter, ['all','teacher','staff'], true)) $multiRoleFilter = 'all';
$mrFilterRoleSql = $multiRoleFilter === 'teacher' ? "u.role='teacher'" : ($multiRoleFilter === 'staff' ? "u.role='staff'" : "u.role IN ('teacher','staff')");

// "School Head Evaluation" reports evaluations SUBMITTED BY the active
// Principal/Dean — they are the EVALUATORS, not the evaluated personnel.
// The people they evaluate (the targets) are Faculty (Faculty Members +
// Teaching Staff, same has_teacher rule questionnaire.php/Peer above use)
// and the current EA. This mirrors admin/questionnaire.php's School Head
// Evaluation tab, which now assigns questions to target_type IN
// ('Faculty','EA') under eval_type='school_head' rather than to Principal/
// Dean directly.
//
// Two evaluator portals write genuine School Head Evaluation rows, each
// under its own eval_type: dean_evaluate.php uses a single 'school_head'
// for every target, while principal_evaluate.php splits into three —
// 'supervisor_to_teacher', 'supervisor_to_staff', 'upward_to_ea' — because
// those exact strings are also the lookup key into questionnaire_forms for
// which form to render, so they can't be collapsed to 'school_head'
// without breaking Principal's own evaluate flow. All four are recognized
// here as the same report. Older installations may have recorded the
// previous (reversed) EA/student-evaluates-Principal/Dean relationship
// under eval_type IN ('ea','student','principal') with target_user_id =
// the Principal/Dean — those rows are NOT deleted (see analytics_archive/
// history preservation), but they no longer fit this corrected report and
// are intentionally excluded from it: showing them here would silently
// reintroduce the exact "Dean/Principal treated as targets" bug this fixes.
$schoolheadTypes = ['school_head', 'supervisor_to_teacher', 'supervisor_to_staff', 'upward_to_ea'];

// Build SQL literals only from a closed set of server-defined values.
// This avoids unquoted identifiers such as `student` ever reaching MySQL
// when the active analytics tab is changed.
$sqlQuote = static function (string $value) use ($mysqli): string {
    return "'" . $mysqli->real_escape_string($value) . "'";
};
$schoolheadTypeSql = '(' . implode(',', array_map($sqlQuote, $schoolheadTypes)) . ')';
$studentTypeSql = $sqlQuote('student');
$peerTypesSql = implode(',', array_map($sqlQuote, ['peer','faculty_peer','staff_peer']));
$schoolheadContextSql = $sqlQuote('school_head');
$multiRoleContextSql = $sqlQuote('multi_role');
$teacherContextSql = $sqlQuote('teacher');
$staffContextSql = $sqlQuote('staff');
$multiRoleQuestionTypeSql = $sqlQuote('Multi-Role');

// Evaluation targets under School Head Evaluation: every Teacher and Staff
// account (teaching or non-teaching — dean_evaluate.php rates both under
// its single merged "Faculty" tab, it doesn't distinguish) plus the current
// EA (role='superadmin'). Principal/Dean are never targets here. Matches
// dean_evaluate.php's own target-roster query exactly (role IN
// ('teacher','staff') OR role='superadmin').
$schoolheadTargetPoolSql = "role IN ('teacher','staff','superadmin') AND is_active=1";
$schoolheadTargetSql = "et.target_user_id IN (SELECT id FROM users WHERE $schoolheadTargetPoolSql)";
$schoolheadTargetPlainSql = "target_user_id IN (SELECT id FROM users WHERE $schoolheadTargetPoolSql)";

// Evaluators under School Head Evaluation: the active Principal and/or
// Dean, per $evaluatorFilter. This is the reverse direction from the old
// model and is what actually distinguishes a real School Head Evaluation
// submission from a stale/legacy row.
$schoolheadEvaluatorRoles = $evaluatorFilter === 'Principal' ? "'principal'" : ($evaluatorFilter === 'Dean' ? "'dean'" : "'principal','dean'");
$schoolheadEvaluatorSql = "et.evaluator_id IN (SELECT id FROM users WHERE role IN ($schoolheadEvaluatorRoles) AND is_active=1)";
$schoolheadEvaluatorPlainSql = "evaluator_id IN (SELECT id FROM users WHERE role IN ($schoolheadEvaluatorRoles) AND is_active=1)";

$studentSchoolHeadTargetSql = "et.target_user_id IN (SELECT id FROM users WHERE role IN ('principal','dean') AND is_active=1 AND account_status='approved')";
$studentSchoolHeadTargetPlainSql = "target_user_id IN (SELECT id FROM users WHERE role IN ('principal','dean') AND is_active=1 AND account_status='approved')";
$studentTargetSql = "et.eval_type=$studentTypeSql AND (
        (COALESCE(et.evaluation_context,'teacher') IN ($teacherContextSql,$staffContextSql)
         AND et.target_user_id IN (SELECT id FROM users WHERE role IN ('teacher','staff') AND is_active=1 AND account_status='approved'))
        OR
        (et.evaluation_context=$schoolheadContextSql AND $studentSchoolHeadTargetSql)
    )";
$studentTargetPlainSql = "eval_type=$studentTypeSql AND (
        (COALESCE(evaluation_context,'teacher') IN ($teacherContextSql,$staffContextSql)
         AND target_user_id IN (SELECT id FROM users WHERE role IN ('teacher','staff') AND is_active=1 AND account_status='approved'))
        OR
        (evaluation_context=$schoolheadContextSql AND $studentSchoolHeadTargetPlainSql)
    )";


// Multi-Role is primarily identified by the explicit evaluation_context. The
// EXISTS fallback also recognizes older submissions saved before the context
// column existed, provided their answers point to a Multi-Role question
// assigned to that personnel. This clause is unchanged from before — only
// how it's triggered (via $isMultiRole) has moved.
$multiRoleClauseSql      = "et.eval_type=$studentTypeSql AND (
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
    $evalTypeSql      = $multiRoleClauseSql;
    $evalTypePlainSql = $multiRoleClausePlainSql;
} else {
    $evalTypeSql = match ($activeEval) {
        'schoolhead' => "et.eval_type IN $schoolheadTypeSql AND $schoolheadEvaluatorSql AND $schoolheadTargetSql",
        'peer' => "et.eval_type IN ($peerTypesSql)",
        // Plain Student Evaluation includes the normal Faculty/Staff contexts
        // plus the dedicated Student Evaluation -> School Head context.
        // Multi-Role remains isolated in its own group filter.
        default => $studentTargetSql
    };
    $evalTypePlainSql = match ($activeEval) {
        'schoolhead' => "eval_type IN $schoolheadTypeSql AND $schoolheadEvaluatorPlainSql AND $schoolheadTargetPlainSql",
        'peer' => "eval_type IN ($peerTypesSql)",
        default => $studentTargetPlainSql
    };
}

// ── YEAR LEVEL FILTER (Evaluators List, Student Evaluation only) ──────
// One dropdown, backed by two columns: education_level ('basic_ed'/'higher_ed')
// is derived from whichever specific year_level is picked, so the UI only
// needs to carry $yearLevel around. Meaningless outside eval_type=student
// (Peer/School Head evaluators aren't students), so it's forced to 'All' there
// to keep a stray query param from silently filtering those SQL paths.
$basicEdYears  = ['Grade 7','Grade 8','Grade 9','Grade 10','Grade 11','Grade 12'];
$higherEdYears = ['1st Year College','2nd Year College','3rd Year College','4th Year College'];
$allYearLevels = array_merge($basicEdYears, $higherEdYears);
$yearLevel = $_GET['year_level'] ?? 'All';
if ($yearLevel !== 'All' && !in_array($yearLevel, $allYearLevels, true)) $yearLevel = 'All';
if ($activeEval !== 'student') $yearLevel = 'All';
$educationLevel = $yearLevel === 'All' ? 'All' : (in_array($yearLevel, $basicEdYears, true) ? 'basic_ed' : 'higher_ed');
$yearLevelSql = ($activeEval === 'student' && $yearLevel !== 'All') ? " AND u.year_level=" . $sqlQuote($yearLevel) : "";

// ── VIEWS ─────────────────────────────────────────────────────
$view       = $_GET['view']       ?? 'list';
$target_id  = intval($_GET['target_id']  ?? 0);
$student_id = intval($_GET['student_id'] ?? 0);  // evaluator for peer
$tracker_id = intval($_GET['tracker_id'] ?? 0);

// ── HELPERS ───────────────────────────────────────────────────
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
    if ($s >= 4.5)  return '#32B98A';
    if ($s >= 3.5)  return '#6BCFA9';
    if ($s >= 2.5)  return '#facc15';
    if ($s >= 1.5)  return '#fb923c';
    return '#E6788A';
}
// Display label for a School Head Evaluation TARGET (never Principal/Dean —
// those are evaluators). role='superadmin' is the current EA; everything
// else reaching this report is already known to be a Faculty/Teaching
// Staff account via $schoolheadTargetSql / ec_resolve_schoolhead_target_group().
function schoolheadTargetLabel($role) {
    return $role === 'superadmin' ? 'EA' : 'Faculty';
}

// Peer-to-Peer canonical grouping, mirrored directly from admin/questionnaire.php's
// own Faculty/Staff/School Head bucketing so the two features can never disagree:
//   - School Head: role is Principal or Dean, peer-reviewed by colleagues
//     (target_type='School', eval_type='peer' in the question bank) --
//     entirely separate from the top-level School Head Evaluation tab, where
//     the EA/students evaluate the Dean/Principal instead.
//   - Faculty: exactly questionnaire.php's own has_teacher rule --
//     is_teaching_staff (Teacher role, an explicit secondary Teacher role, a
//     Teacher sector, or an actual teaching_assignments row) OR
//     ec_has_teacher_function($u). That second half matters: it's an
//     independent OR in questionnaire.php, so some teaching Staff are only
//     caught by ec_has_teacher_function() and not by the SQL predicate alone
//     -- skipping it is what left Faculty undercounted here before. It's a
//     pure array function (no $mysqli), so $u below carries the same fields
//     questionnaire.php's own user query builds, for identical results.
//   - Staff (Non-Teaching Staff): a Staff account with no teaching
//     assignment AND no year-level scope at all -- questionnaire.php's
//     isNonTeachingStaff() predicate.
//   - Anything else (e.g. a Staff account scoped to a year level but with no
//     actual teaching assignment) has no Peer-to-Peer context and is
//     dropped, same as before.
function ec_resolve_peer_group_full($p, $mysqli) {
    $role = strtolower(trim((string)($p['role'] ?? '')));
    if ($role === 'principal' || $role === 'dean') return 'school_head';

    // Faculty accounts always belong to the Faculty reporting category.
    // Staff accounts are promoted to Faculty only when the existing
    // teaching/year-level assignment architecture identifies them as teaching.
    if ($role === 'faculty') return 'teacher';

    $uid = (int)($p['id'] ?? 0);
    if ($uid <= 0) return null;

    $stmt = $mysqli->prepare(
        "SELECT id, full_name, designation, photo, source, role, secondary_role, sector,
                EXISTS(SELECT 1 FROM teaching_assignments ta WHERE ta.user_id=u.id) AS has_teaching_assignment,
                (u.role='teacher' OR u.secondary_role='teacher' OR u.sector='Teacher'
                 OR EXISTS(SELECT 1 FROM teaching_assignments ta2 WHERE ta2.user_id=u.id)) AS is_teaching_staff,
                EXISTS(SELECT 1 FROM user_year_levels yl WHERE yl.user_id=u.id) AS has_year_level
         FROM users u WHERE u.id=? LIMIT 1"
    );
    $stmt->bind_param('i', $uid);
    $stmt->execute();
    $u = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    if (!$u) return null;

    // Exactly questionnaire.php's own has_teacher rule: the SQL predicate
    // OR'd with the same external ec_has_teacher_function() call, given the
    // same field set that function expects.
    $has_teacher = ((int)($u['is_teaching_staff'] ?? 0) === 1) || ec_has_teacher_function($u);
    if ($has_teacher) return 'teacher';

    $secondary = strtolower(trim((string)($p['secondary_role'] ?? '')));
    if ($role === 'staff' || $secondary === 'staff') {
        return (
            (int)($u['has_teaching_assignment'] ?? 0) === 0
            && (int)($u['has_year_level'] ?? 0) === 0
        ) ? 'staff' : null;
    }
    return null;
}

// Student Evaluation reporting grouping:
//   - School Head: active Principal/Dean targets
//   - Faculty: Faculty/Teacher accounts + Staff accounts with the same active
//     teaching/year-level logic used by Account Management/questionnaire.php
//   - Staff: only Non-Teaching Staff (no teaching_assignments and no user_year_levels)
//   - null: not a Student Evaluation target
function ec_resolve_student_group_full($p, $mysqli) {
    $role = strtolower(trim((string)($p['role'] ?? '')));
    if ($role === 'principal' || $role === 'dean') return 'school_head';

    $uid = (int)($p['id'] ?? 0);
    if ($uid <= 0) return null;

    $stmt = $mysqli->prepare(
        "SELECT id, full_name, designation, photo, source, role, secondary_role, sector,
                EXISTS(SELECT 1 FROM teaching_assignments ta WHERE ta.user_id=u.id) AS has_teaching_assignment,
                (u.role='teacher' OR u.secondary_role='teacher' OR u.sector='Teacher'
                 OR EXISTS(SELECT 1 FROM teaching_assignments ta2 WHERE ta2.user_id=u.id)
                 OR EXISTS(SELECT 1 FROM user_year_levels yl2 WHERE yl2.user_id=u.id)) AS is_teaching_staff,
                EXISTS(SELECT 1 FROM user_year_levels yl WHERE yl.user_id=u.id) AS has_year_level
         FROM users u WHERE u.id=? LIMIT 1"
    );
    $stmt->bind_param('i', $uid);
    $stmt->execute();
    $u = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    if (!$u) return null;

    // Exactly mirror the existing questionnaire.php teaching rule.
    $has_teacher = ((int)($u['is_teaching_staff'] ?? 0) === 1) || ec_has_teacher_function($u);
    if ($has_teacher) return 'teacher';

    $has_staff = ec_has_staff_function($u);
    if ($role === 'staff' || strtolower(trim((string)($u['secondary_role'] ?? ''))) === 'staff' || $has_staff) {
        return (
            (int)($u['has_teaching_assignment'] ?? 0) === 0
            && (int)($u['has_year_level'] ?? 0) === 0
        ) ? 'staff' : null;
    }
    return null;
}

// School Head Evaluation's target grouping: 'faculty' (every Teacher AND
// Staff account — dean_evaluate.php rates both under one merged "Faculty"
// tab, it does not split out a separate Staff bucket) or 'ea'
// (role='superadmin'). Principal/Dean never resolve to a target group
// here — they're the evaluators, filtered separately. Mirrors
// schoolheadTargetLabel() below, which is the display-label counterpart
// of this same rule.
function ec_resolve_schoolhead_target_group($p, $mysqli) {
    $role = strtolower(trim((string)($p['role'] ?? '')));
    if ($role === 'superadmin') return 'ea';
    if ($role === 'teacher' || $role === 'staff') return 'faculty';
    return null;
}

// Eval type UI config
$evalLabel      = $activeEval === 'peer' ? 'Peer-to-Peer Evaluation' : ($activeEval === 'schoolhead' ? 'School Head Evaluation' : 'Student Evaluation');
// Multi-Role is a Student Evaluation view, so it keeps the exact same
// Student Evaluation header color and presentation.
$evalColor      = $activeEval === 'peer' ? '#4968C8' : ($activeEval === 'schoolhead' ? '#C77A08' : '#2563EB');
$evalColorBg    = $activeEval === 'peer' ? 'rgba(73,104,200,.08)' : ($activeEval === 'schoolhead' ? 'rgba(217,119,6,.08)' : 'rgba(37,99,235,.08)');
$evalColorBorder= $activeEval === 'peer' ? 'rgba(124,58,237,.25)' : ($activeEval === 'schoolhead' ? 'rgba(217,119,6,.25)' : 'rgba(59,130,246,.25)');
$evalIcon       = $activeEval === 'peer' ? 'fa-people-arrows' : ($activeEval === 'schoolhead' ? 'fa-user-tie' : 'fa-graduation-cap');
// Label for "who evaluated"
$evaluatorNoun  = $activeEval === 'peer' ? 'colleague' : ($activeEval === 'schoolhead' ? 'evaluator' : 'student');
$evaluatorNounP = $activeEval === 'peer' ? 'colleagues' : ($activeEval === 'schoolhead' ? 'evaluators' : 'students');
// In evaluation_tracker: student_id = the evaluator (student or peer teacher)
// eval_type filters which set we show; peer reports include legacy `peer` plus current `faculty_peer` and `staff_peer` tracker values

// ══════════════════════════════════════════════════════════════
// SHARED CSS HEAD (used across all sub-views)
// ══════════════════════════════════════════════════════════════
function pageHead($title, $evalColor, $evalColorBg, $evalColorBorder) { ?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"/>
<meta name="viewport" content="width=device-width, initial-scale=1"/>
<title><?= htmlspecialchars($title) ?> — PBI Admin</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css"/>
<link href="https://fonts.googleapis.com/css2?family=Rajdhani:wght@600;700&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet"/>
<style>
:root{
  --dark:#F8FAFC;--mid:#FFFFFF;--inner:#F4F8FF;--accent:#2563EB;
  --gold:#C77A08;--gold-h:#D69612;--teal:#0E7490;--violet:#4968C8;
  --light:#0B1F3A;--muted:#67819E;--danger:#E6788A;
  --border:#B9CDE5;--radius:10px;
  --card-shadow:0 1px 2px rgba(30,82,144,.08),0 4px 12px rgba(30,82,144,.08);
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
.toast{position:fixed;top:20px;right:20px;z-index:999;background:rgba(34,197,94,.15);border:1px solid rgba(34,197,94,.35);color:#6BCFA9;padding:12px 20px;border-radius:8px;font-size:13px;display:flex;align-items:center;gap:8px;animation:slideIn .3s ease,fadeOut .4s ease 3s forwards;}
@keyframes slideIn{from{opacity:0;transform:translateX(20px)}to{opacity:1;transform:none}}
@keyframes fadeOut{to{opacity:0;pointer-events:none}}
.back-btn{display:inline-flex;align-items:center;gap:8px;padding:9px 18px;background:var(--mid);border:1px solid var(--border);border-radius:var(--radius);color:var(--light);font-size:13px;font-weight:600;text-decoration:none;margin-bottom:22px;transition:background .2s;}
.back-btn:hover{background:var(--accent);}

/* ── EVAL SWITCHER ── */
.eval-switcher{display:flex;gap:0;background:var(--mid);border:1px solid var(--border);border-radius:12px;overflow:hidden;margin-bottom:26px;width:fit-content;}
.eval-tab{display:flex;align-items:center;gap:9px;padding:12px 24px;font-size:13px;font-weight:600;cursor:pointer;text-decoration:none;color:var(--muted);border:none;background:none;font-family:'Inter',sans-serif;transition:all .2s;position:relative;}
.eval-tab:hover{color:var(--light);background:rgba(37,99,235,.05);}
.eval-tab.student.active{color:var(--accent);background:rgba(37,99,235,.08);}
.eval-tab.student.active::after{content:'';position:absolute;bottom:0;left:0;right:0;height:2px;background:var(--accent);border-radius:2px 2px 0 0;}
.eval-tab.peer.active{color:var(--violet);background:rgba(73,104,200,.08);}
.eval-tab.peer.active::after{content:'';position:absolute;bottom:0;left:0;right:0;height:2px;background:var(--violet);border-radius:2px 2px 0 0;}
.eval-divider{width:1px;background:var(--border);margin:8px 0;}
.tab-badge{font-size:10px;font-weight:700;padding:2px 8px;border-radius:20px;background:rgba(30,82,144,.13);color:var(--muted);}
.eval-tab.student.active .tab-badge{background:rgba(37,99,235,.10);color:var(--accent);}
.eval-tab.peer.active .tab-badge{background:rgba(73,104,200,.10);color:var(--violet);}
.eval-tab.multi-role.active{color:#D69612;background:rgba(245,158,11,.07);}
.eval-tab.multi-role.active::after{content:'';position:absolute;bottom:0;left:0;right:0;height:2px;background:#D69612;border-radius:2px 2px 0 0;}
.eval-tab.multi-role.active .tab-badge{background:rgba(245,158,11,.15);color:#D69612;}
.eval-tab.schoolhead.active{color:#C77A08;background:rgba(217,119,6,.07);}
.eval-tab.schoolhead.active::after{content:'';position:absolute;bottom:0;left:0;right:0;height:2px;background:#C77A08;border-radius:2px 2px 0 0;}
.eval-tab.schoolhead.active .tab-badge{background:rgba(217,119,6,.15);color:#C77A08;}

/* ── EVAL TYPE BANNER ── */
.eval-banner{display:flex;align-items:center;gap:14px;padding:13px 18px;border-radius:10px;margin-bottom:20px;border:1px solid var(--ec-bd);background:var(--ec-bg);}
.eval-banner-icon{width:40px;height:40px;border-radius:9px;display:flex;align-items:center;justify-content:center;font-size:18px;color:var(--ec);background:rgba(37,99,235,.05);border:1px solid var(--ec-bd);flex-shrink:0;}
.eval-banner-title{font-size:14px;font-weight:700;color:var(--ec);}
.eval-banner-desc{font-size:12px;color:var(--muted);margin-top:1px;}

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
html{background:#fff;color-scheme:light;}
body{background:#fff !important;color:#0B1F3A !important;}
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

body{padding:28px !important;}
.eval-tab.student.active{background:#E6F0FF !important;color:#2563EB !important;}
.eval-tab.peer.active{background:#F5F3FF !important;color:#4968C8 !important;}
.eval-tab.multi-role.active{background:#FFF7E8 !important;color:#C77A08 !important;}
.eval-banner{background:var(--ec-bg) !important;}
.avg-bar-bg,.eval-bar-bg,.score-bar-bg{background:#D8E5F4 !important;}
.comment-text,.comment-section{background:#F8FAFC !important;color:#294765 !important;}


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

/* Persistent evaluation-tab icon coding */
.eval-tab.student > i{color:#2563EB !important;}
.eval-tab.peer > i{color:#4968C8 !important;}
.eval-tab.multi-role > i{color:#C77A08 !important;}

</style>
<?php } // end pageHead

// ══════════════════════════════════════════════════════════════
// VIEW: EVALUATION SHEET
// ══════════════════════════════════════════════════════════════
if ($view === 'sheet' && $target_id && $tracker_id) {
    $tgt = $mysqli->query("SELECT id,full_name,designation,photo,role FROM users WHERE id=$target_id LIMIT 1")->fetch_assoc();
    $stu = $mysqli->query("SELECT id,full_name,photo FROM users WHERE id=$student_id LIMIT 1")->fetch_assoc();
    $trk = $mysqli->query("SELECT * FROM evaluation_tracker WHERE id=$tracker_id LIMIT 1")->fetch_assoc();

    $answers = [];
    // Resolve submitted answers without dropping rows when a legacy answer
    // has a missing question_source mapping, a NULL user_question_id, or the
    // original question was later removed from its question bank. The answer
    // row itself is authoritative for the score.
    $aq = $mysqli->query("
        SELECT
            qa.id AS answer_id,
            COALESCE(qa.question_id, qa.user_question_id) AS q_id,
            COALESCE(eq.question_text, uq.question_text, CONCAT('Question #', COALESCE(qa.question_id, qa.user_question_id, qa.id))) AS question_text,
            COALESCE(eq.category, uq.category, 'General') AS category,
            qa.answer_score
        FROM questionnaire_answers qa
        LEFT JOIN evaluation_questions eq
            ON qa.question_source = 'evaluation'
           AND eq.id = qa.question_id
        LEFT JOIN user_questions uq
            ON qa.question_source = 'user'
           AND uq.id = COALESCE(qa.user_question_id, qa.question_id)
        WHERE qa.tracker_id = $tracker_id
        ORDER BY category, q_id, qa.id
    ");
    if ($aq) $answers = $aq->fetch_all(MYSQLI_ASSOC);

    // Some older Student → Faculty submissions reference question IDs from a
    // previous question bank. If those rows were later replaced, the direct
    // ID join above cannot recover their text and would show "Question #...".
    // For that case, use the faculty member's current Student Evaluation
    // questions in their configured order as a display fallback. This affects
    // only answers whose question text could not be resolved by either ID join.
    $missingTextIndexes = [];
    foreach ($answers as $i => $answer) {
        if (strpos((string)($answer['question_text'] ?? ''), 'Question #') === 0) {
            $missingTextIndexes[] = $i;
        }
    }

    if (!empty($missingTextIndexes)) {
        $fallbackQuestions = [];
        $fq = $mysqli->prepare("
            SELECT question_text, category
            FROM user_questions
            WHERE user_id=? AND eval_type='student'
            ORDER BY category ASC, sort_order ASC, id ASC
        ");
        if ($fq) {
            $fq->bind_param('i', $target_id);
            $fq->execute();
            $fqRes = $fq->get_result();
            if ($fqRes) $fallbackQuestions = $fqRes->fetch_all(MYSQLI_ASSOC);
            $fq->close();
        }

        // Map the unresolved submitted answers to the corresponding Student
        // Evaluation questions by their display order, without changing scores
        // or the stored answer records.
        foreach ($missingTextIndexes as $pos => $answerIndex) {
            if (!empty($fallbackQuestions[$pos]['question_text'])) {
                $answers[$answerIndex]['question_text'] = $fallbackQuestions[$pos]['question_text'];
                $answers[$answerIndex]['category'] = $fallbackQuestions[$pos]['category'] ?? 'General';
            }
        }
    }

    $grouped_ans = [];
    foreach ($answers as $a) $grouped_ans[$a['category']][] = $a;

    $scores = array_filter(array_column($answers,'answer_score'), fn($s) => $s !== null);
    $avg    = count($scores) ? round(array_sum($scores)/count($scores),2) : null;
    $remark = $trk['remarks'] ?? '';

    $scaleItems  = [5=>'Always',4=>'Often',3=>'Sometimes',2=>'Rarely',1=>'Never'];
    $scaleColors = [5=>'#32B98A',4=>'#6BCFA9',3=>'#facc15',2=>'#fb923c',1=>'#E6788A'];

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
.avg-bar-bg{flex:1;height:7px;background:rgba(30,82,144,.13);border-radius:4px;overflow:hidden;}
.avg-bar-fill{height:100%;border-radius:4px;}
.avg-bar-val{font-size:12px;font-weight:700;width:28px;}
.avg-out-of{font-size:13px;color:var(--muted);margin-top:6px;}
.btn-print{background:var(--accent);color:#fff;border:none;padding:10px 20px;border-radius:var(--radius);font-size:13px;font-weight:600;cursor:pointer;display:inline-flex;align-items:center;gap:7px;transition:opacity .2s;font-family:'Inter',sans-serif;}
.btn-print:hover{opacity:.85;}
.top-bar{display:flex;justify-content:space-between;align-items:center;margin-bottom:16px;flex-wrap:wrap;gap:10px;}

/* ── PRINT: hide evaluator identity entirely ── */
@media print{
  @page{margin:10mm;}
  html,body{width:100%!important;height:auto!important;}
  .no-print{display:none!important;}
  body{background:#fff!important;color:#000!important;padding:0!important;margin:0!important;min-height:0!important;}
  body *{color:#000!important;}

  /* Compact the evaluation sheet for printing */
  .sheet-header{padding:12px 16px!important;margin-bottom:10px!important;gap:12px!important;border-radius:0!important;}
  .sheet-avatar,.sheet-avatar-ph{width:46px!important;height:46px!important;}
  .sheet-name{font-size:18px!important;}
  .sheet-desig{font-size:11px!important;}
  .cat-section{margin-bottom:12px!important;break-inside:avoid;page-break-inside:avoid;}
  .cat-title{margin-bottom:6px!important;padding-bottom:3px!important;}
  .q-table th{padding:6px 10px!important;}
  .q-table td{padding:6px 10px!important;line-height:1.25!important;vertical-align:middle!important;}
  .q-table td.q-num{padding-top:6px!important;}
  .q-table td.rating-cell{padding-top:5px!important;}
  .rating-badge{padding:3px 8px!important;gap:0!important;}
  .rating-num{font-size:14px!important;}
  .rating-lbl{font-size:8px!important;}
  .comment-section{padding:10px 14px!important;margin-bottom:12px!important;border-radius:0!important;}
  .comment-title{margin-bottom:5px!important;}
  .comment-text{line-height:1.35!important;}
  .avg-summary{padding:12px 14px!important;gap:14px!important;border-radius:0!important;break-inside:avoid;page-break-inside:avoid;}
  .avg-score-big{font-size:42px!important;}
  .avg-bar-row{margin-bottom:4px!important;}

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
</head>
<body class="feature-compact">

<div class="person-actions no-print" style="display:flex;justify-content:space-between;align-items:center;margin-bottom:16px;">
    <a href="?view=students&target_id=<?= $target_id ?>&group=<?= urlencode($groupFilter) ?>&eval_type=<?= $activeEval ?>&mr_filter=<?= urlencode($multiRoleFilter) ?>&evaluator=<?= urlencode($evaluatorFilter) ?>&year_level=<?= urlencode($yearLevel) ?>" class="back-btn">
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
        <div class="sheet-desig"><?= htmlspecialchars($tgt['designation']) ?> · <?= in_array($tgt['role'], ['principal','dean'], true) ? ($tgt['role']==='principal'?'Principal':'Dean') : ($tgt['role']==='teacher'?'Faculty':'Staff') ?></div>
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

</body></html>
<?php $mysqli->close(); exit; }

// ══════════════════════════════════════════════════════════════
// VIEW: EVALUATORS LIST (students who evaluated / peers who evaluated)
// ══════════════════════════════════════════════════════════════
if ($view === 'students' && $target_id) {
    $tgt = $mysqli->query("SELECT id,full_name,designation,photo,role FROM users WHERE id=$target_id LIMIT 1")->fetch_assoc();

    // For peer eval, evaluator_id in tracker = the peer who did the evaluating
    $evaluators = [];
    $eq = $mysqli->query("
        SELECT et.id as tracker_id, et.submitted_at, et.remarks,
               u.id as student_id, u.full_name, u.photo, u.role, u.education_level, u.year_level,
               (SELECT AVG(qa.answer_score) FROM questionnaire_answers qa WHERE qa.tracker_id=et.id) as avg_score
        FROM evaluation_tracker et
        JOIN users u ON u.id = et.evaluator_id
        WHERE et.target_user_id = $target_id AND $evalTypeSql $yearLevelSql
        ORDER BY et.submitted_at DESC
    ");
    if ($eq) $evaluators = $eq->fetch_all(MYSQLI_ASSOC);

    $allScores  = array_filter(array_column($evaluators,'avg_score'), fn($s) => $s !== null);
    $overallAvg = count($allScores) ? round(array_sum($allScores)/count($allScores),2) : null;

    // "Evaluation Results" rows — one row per evaluator (their name, role,
    // score, and the date they submitted), so the EA can drill into each
    // individual's full answer sheet via the View action.
    $resultRows = [];
    foreach ($evaluators as $ev) {
        $resultRows[] = [
            'id'         => 'ev-'.$ev['tracker_id'],
            'label'      => $ev['full_name'],
            'role'       => ucfirst($ev['role'] ?? $evaluatorNoun),
            'avg'        => $ev['avg_score'] !== null ? round($ev['avg_score'],2) : null,
            'date'       => $ev['submitted_at'],
            'student_id' => $ev['student_id'],
            'tracker_id' => $ev['tracker_id'],
        ];
    }

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
.score-legend{background:var(--inner);border:1px solid var(--border);border-radius:10px;padding:12px 16px;font-size:12px;min-width:180px;}
.score-legend-title{font-weight:700;color:var(--light);margin-bottom:6px;}
.score-legend-row{display:flex;justify-content:space-between;gap:10px;color:var(--muted);padding:1px 0;}
.score-legend-row span{font-weight:700;}
.results-table-wrap{overflow-x:auto;}
table.results-table{width:100%;border-collapse:collapse;font-size:13px;}
table.results-table th{text-align:left;padding:10px 12px;font-size:11px;text-transform:uppercase;letter-spacing:.5px;color:var(--muted);border-bottom:1px solid var(--border);white-space:nowrap;}
table.results-table td{padding:12px;border-bottom:1px solid var(--border);vertical-align:middle;}
table.results-table tbody tr:hover{background:var(--inner);}
table.results-table tfoot td{font-weight:700;border-top:2px solid var(--border);border-bottom:none;}
.rt-rating-pill{font-size:11px;font-weight:700;padding:3px 10px;border-radius:20px;display:inline-block;}
.rt-print-btn{padding:6px 14px;border-radius:7px;border:1px solid var(--ec-bd,var(--border));background:var(--ec-bg,transparent);color:var(--ec,inherit);font-size:12px;font-weight:700;cursor:pointer;display:inline-flex;align-items:center;gap:6px;}
.rt-print-btn:hover{opacity:.85;}
@media print{
  .score-legend{display:none!important;}
}
.section-title{font-family:'Rajdhani',sans-serif;font-size:20px;font-weight:700;color:var(--light);margin-bottom:16px;display:flex;align-items:center;gap:10px;}
.evaluator-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(260px,1fr));gap:16px;}
.eval-card{background:var(--mid);border:1px solid var(--border);border-radius:14px;padding:20px;cursor:pointer;transition:all .22s;text-decoration:none;display:block;}
.eval-card:hover{border-color:var(--ec);transform:translateY(-2px);box-shadow:0 10px 24px rgba(30,82,144,.13);}
.eval-card-top{display:flex;align-items:center;gap:12px;margin-bottom:14px;}
.eval-avatar{width:46px;height:46px;border-radius:50%;object-fit:cover;border:2px solid var(--border);}
.eval-avatar-ph{width:46px;height:46px;border-radius:50%;background:var(--inner);border:2px solid var(--border);display:flex;align-items:center;justify-content:center;color:var(--muted);font-size:18px;}
.eval-name{font-size:14px;font-weight:700;color:var(--light);}
.eval-date{font-size:11px;color:var(--muted);margin-top:2px;}
.eval-score-row{display:flex;align-items:center;justify-content:space-between;}
.eval-score{font-family:'Rajdhani',sans-serif;font-size:26px;font-weight:700;}
.eval-score-lbl{font-size:12px;font-weight:600;margin-top:1px;}
.eval-bar-bg{flex:1;height:6px;background:rgba(30,82,144,.13);border-radius:3px;overflow:hidden;margin:0 12px;}
.eval-bar-fill{height:100%;border-radius:3px;}
.view-eval-btn{margin-top:12px;width:100%;padding:9px;background:var(--ec-bg);border:1px solid var(--ec-bd);border-radius:8px;color:var(--ec);font-size:12px;font-weight:700;text-align:center;}
.eval-remark{margin-top:10px;font-size:12px;color:var(--muted);font-style:italic;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;}
.no-eval{text-align:center;padding:48px;background:var(--mid);border-radius:14px;border:1px solid var(--border);color:var(--muted);}
.no-eval i{font-size:36px;opacity:.3;display:block;margin-bottom:12px;}
/* Peer evaluator role badge */
.evaluator-role-badge{font-size:10px;font-weight:700;padding:2px 8px;border-radius:20px;background:rgba(73,104,200,.09);color:#4968C8;border:1px solid rgba(124,58,237,.25);margin-left:auto;}

/* ── PRINT: hide evaluator cards entirely, show summary only ── */
@media print{
  .no-print{display:none!important;}
  body{background:#fff!important;color:#000!important;padding:0;}
  body *{color:#000!important;}
  /* Hide the on-screen results table — names, dates, per-row actions */
  .evaluator-grid,.results-table-wrap{display:none!important;}
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
</head><body>
<div class="top-bar no-print">
    <a href="?group=<?= urlencode($groupFilter) ?>&eval_type=<?= $activeEval ?>&mr_filter=<?= urlencode($multiRoleFilter) ?>&evaluator=<?= urlencode($evaluatorFilter) ?>" class="back-btn" style="margin-bottom:0;">
        <i class="fa-solid fa-arrow-left"></i> Back to Analytics
    </a>
    <button class="btn-print" onclick="window.print()"><i class="fa-solid fa-print"></i> Print / Save PDF</button>
</div>

<!-- eval type banner -->
<div class="eval-banner" style="margin-bottom:18px;">
    <div class="eval-banner-icon"><i class="fa-solid <?= $evalIcon ?>"></i></div>
    <div>
        <div class="eval-banner-title"><?= $evalLabel ?></div>
        <div class="eval-banner-desc"><?= $activeEval === 'peer' ? 'Evaluated by fellow faculty and staff' : ($activeEval === 'schoolhead' ? 'Evaluated by the Dean or Principal' : 'Evaluated by students') ?></div>
    </div>
</div>

<div class="target-card">
    <?php if ($tgt['photo']): ?><img class="target-avatar" src="../image/<?= htmlspecialchars($tgt['photo']) ?>" alt=""/>
    <?php else: ?><div class="target-avatar-ph"><i class="fa-solid fa-user"></i></div><?php endif; ?>
    <div>
        <div class="target-name"><?= htmlspecialchars($tgt['full_name']) ?></div>
        <div class="target-desig"><?= htmlspecialchars($tgt['designation']) ?> · <?= $tgt['role']==='teacher'?'teacher':'Staff' ?></div>
        <div class="target-desig">All Year Levels</div>
    </div>
    <div class="target-stats">
        <div class="tstat">
            <div class="tstat-val" style="color:var(--ec)"><?= count($evaluators) ?></div>
            <div class="tstat-lbl">Total Evaluations</div>
        </div>
        <div class="tstat">
            <div class="tstat-val" style="color:<?= scoreColor($overallAvg) ?>"><?= $overallAvg !== null ? number_format($overallAvg,2).' / 5.00' : '—' ?></div>
            <div class="tstat-lbl">Overall Average Score</div>
        </div>
    </div>
    <div class="score-legend no-print">
        <div class="score-legend-title">Score Legend</div>
        <div class="score-legend-row"><span style="color:#32B98A">4.50 - 5.00</span> Always</div>
        <div class="score-legend-row"><span style="color:#6BCFA9">3.50 - 4.49</span> Often</div>
        <div class="score-legend-row"><span style="color:#facc15">2.50 - 3.49</span> Sometimes</div>
        <div class="score-legend-row"><span style="color:#fb923c">1.50 - 2.49</span> Rarely</div>
        <div class="score-legend-row"><span style="color:#E6788A">1.00 - 1.49</span> Never</div>
    </div>
</div>

<div class="section-title" style="flex-wrap:wrap;gap:14px;justify-content:space-between;">
    <span style="display:flex;align-items:center;gap:10px;">
        <i class="fa-solid fa-list-check" style="color:var(--accent)"></i>
        Evaluation Results
        <span style="font-size:13px;font-weight:400;color:var(--muted)">(<?= count($resultRows) ?>)</span>
    </span>
    <?php if ($activeEval === 'student'): ?>
    <span class="no-print" style="display:flex;align-items:center;gap:8px;">
        <label for="yearLevelSelect" style="font-size:12px;font-weight:700;color:var(--muted);text-transform:uppercase;letter-spacing:.5px;">Year Level</label>
        <select id="yearLevelSelect" onchange="applyYearLevelFilter(this.value)" style="padding:8px 14px;border-radius:8px;border:1px solid var(--border);font-size:13px;font-weight:600;background:var(--mid);color:var(--light);">
            <option value="All" <?= $yearLevel==='All' ? 'selected' : '' ?>>All Year Levels</option>
            <optgroup label="Basic Education">
                <?php foreach ($basicEdYears as $g): ?>
                <option value="<?= htmlspecialchars($g) ?>" <?= $yearLevel===$g ? 'selected' : '' ?>><?= htmlspecialchars($g) ?></option>
                <?php endforeach; ?>
            </optgroup>
            <optgroup label="Higher Education">
                <?php foreach ($higherEdYears as $y): ?>
                <option value="<?= htmlspecialchars($y) ?>" <?= $yearLevel===$y ? 'selected' : '' ?>><?= htmlspecialchars($y) ?></option>
                <?php endforeach; ?>
            </optgroup>
        </select>
    </span>
    <script>
    function applyYearLevelFilter(val) {
        const url = new URL(window.location.href);
        if (val === 'All') { url.searchParams.delete('year_level'); }
        else { url.searchParams.set('year_level', val); }
        window.location.href = url.toString();
    }
    </script>
    <?php endif; ?>
</div>

<?php if (empty($resultRows)): ?>
<div class="no-eval">
    <i class="fa-solid fa-users-slash"></i>
    <p>No <?= $evaluatorNounP ?> have evaluated this person yet.</p>
</div>
<?php else: ?>

<div class="results-table-wrap">
<table class="results-table" id="resultsTable">
    <thead>
        <tr>
            <th>No.</th>
            <th>Name</th>
            <th>Role</th>
            <th>Average Score</th>
            <th>Rating</th>
            <th>Date Evaluated</th>
            <th class="no-print">Actions</th>
        </tr>
    </thead>
    <tbody>
    <?php foreach ($resultRows as $i => $row): $rc = scoreColor($row['avg']); ?>
        <tr data-row-id="<?= htmlspecialchars($row['id']) ?>">
            <td><?= $i+1 ?></td>
            <td><?= htmlspecialchars($row['label']) ?></td>
            <td><?= htmlspecialchars($row['role']) ?></td>
            <td style="font-weight:700;color:<?= $rc ?>"><?= $row['avg'] !== null ? number_format($row['avg'],2) : '—' ?></td>
            <td><span class="rt-rating-pill" style="background:<?= $rc ?>1A;color:<?= $rc ?>"><?= scoreLabel($row['avg']) ?></span></td>
            <td><?= !empty($row['date']) ? date('M d, Y', strtotime($row['date'])) : '—' ?></td>
            <td class="no-print">
                <a class="rt-print-btn" href="?view=sheet&target_id=<?= $target_id ?>&student_id=<?= $row['student_id'] ?>&tracker_id=<?= $row['tracker_id'] ?>&group=<?= urlencode($groupFilter) ?>&eval_type=<?= $activeEval ?>&mr_filter=<?= urlencode($multiRoleFilter) ?>&evaluator=<?= urlencode($evaluatorFilter) ?>"><i class="fa-solid fa-eye"></i> View</a>
            </td>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table>
</div>

<!-- Print-only: anonymised summary, no names or photos (Peer/School Head
     rows show real evaluator names on screen above, per the master spec's
     "EA retains full access to evaluator identity" rule — this summary is
     the printed hand-out version, kept anonymous like before). -->
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
</body></html>
<?php $mysqli->close(); exit; }

// ══════════════════════════════════════════════════════════════
// VIEW: ARCHIVED PERSONNEL
// ══════════════════════════════════════════════════════════════
if ($view === 'archived') {
    // Peer tab: don't filter Teacher/Staff by raw role in SQL — the canonical
    // grouping (ec_resolve_peer_group, same predicate as faculty_dashboard.php
    // / admin/questionnaire.php) needs a per-person teaching_assignments /
    // user_year_levels lookup that isn't a static column, so pull every
    // faculty/staff/teacher candidate here and apply the Teacher/Staff pill
    // in PHP below instead.
    $whereRoleArc = $activeEval === 'schoolhead'
        ? "(u.role IN ('teacher','staff','superadmin'))"
        : ($isMultiRole ? $mrFilterRoleSql : ($activeEval === 'peer'
            ? "u.role IN ('teacher','staff','faculty','principal','dean')"
            : ($activeEval === 'student'
                ? "u.role IN ('teacher','staff','principal','dean')"
                : ($groupFilter==='Teacher' ? "u.role='teacher'" : ($groupFilter==='Staff' ? "u.role='staff'" : "u.role IN ('teacher','staff')")))));
    $archived = [];
    $res = $mysqli->query("
        SELECT u.id,u.full_name,u.designation,u.photo,u.role,u.secondary_role,aa.archived_at,
               COUNT(DISTINCT et.id) AS total_responses,
               AVG(qa.answer_score)  AS avg_score
        FROM analytics_archive aa
        JOIN users u ON u.id=aa.target_user_id
        LEFT JOIN evaluation_tracker et ON et.target_user_id=u.id AND $evalTypeSql
        LEFT JOIN questionnaire_answers qa ON qa.tracker_id=et.id
        WHERE $whereRoleArc
        GROUP BY u.id ORDER BY aa.archived_at DESC
    ");
    if ($res) $archived = $res->fetch_all(MYSQLI_ASSOC);

    if ($activeEval === 'peer') {
        // Same canonical rule as the dashboard roster: null (not a valid
        // Teacher, Staff, or School Head peer target) is dropped entirely;
        // the pill then narrows to just 'teacher', 'staff', or 'school_head'.
        $archived = array_values(array_filter($archived, function ($p) use ($groupFilter, $mysqli) {
            $g = ec_resolve_peer_group_full($p, $mysqli);
            if ($g === null) return false;
            if ($groupFilter === 'Teacher') return $g === 'teacher';
            if ($groupFilter === 'Staff') return $g === 'staff';
            if ($groupFilter === 'SchoolHead') return $g === 'school_head';
            return true;
        }));
    }

    if ($activeEval === 'student' && !$isMultiRole) {
        // Student Evaluation uses the same canonical Faculty/Teaching Staff vs
        // Non-Teaching Staff vs School Head classification as questionnaire.php.
        $archived = array_values(array_filter($archived, function ($p) use ($groupFilter, $mysqli) {
            $g = ec_resolve_student_group_full($p, $mysqli);
            if ($g === null) return false;
            if ($groupFilter === 'Teacher') return $g === 'teacher';
            if ($groupFilter === 'Staff') return $g === 'staff';
            if ($groupFilter === 'SchoolHead') return $g === 'school_head';
            return true;
        }));
    }

    if ($activeEval === 'schoolhead') {
        // Same has_teacher/EA rule as the main roster below: null (not a
        // valid Faculty or EA target) is dropped entirely; the pill then
        // narrows to just 'faculty' or 'ea'.
        $archived = array_values(array_filter($archived, function ($p) use ($groupFilter, $mysqli) {
            $g = ec_resolve_schoolhead_target_group($p, $mysqli);
            if ($g === null) return false;
            if ($groupFilter === 'Faculty') return $g === 'faculty';
            if ($groupFilter === 'EA') return $g === 'ea';
            return true;
        }));
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
</head><body>
<?php if ($toast): ?><div class="toast"><i class="fa-solid fa-circle-check"></i><?= htmlspecialchars($toast) ?></div><?php endif; ?>
<a href="?group=<?= urlencode($groupFilter) ?>&eval_type=<?= $activeEval ?>&mr_filter=<?= urlencode($multiRoleFilter) ?>&evaluator=<?= urlencode($evaluatorFilter) ?>" class="back-btn"><i class="fa-solid fa-arrow-left"></i> Back to Analytics</a>
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
                <span><?= ($activeEval==='schoolhead' ? schoolheadTargetLabel($p['role']) : ($activeEval==='student' && $groupFilter==='SchoolHead' ? 'School Head · '.($p['role']==='principal'?'Principal':'Dean') : ($activeEval==='student' ? ($p['role']==='teacher'?'Faculty':'Staff') : ($p['role']==='teacher'?'Faculty':'Staff')))) ?> · <?= htmlspecialchars($p['designation']) ?></span>
                <span><?= $p['total_responses'] ?> evaluation<?= $p['total_responses']!=1?'s':'' ?></span>
                <?php if ($avg !== null): ?><span style="color:<?= scoreColor($avg) ?>;font-weight:700;"><?= number_format($avg,2) ?> avg</span><?php endif; ?>
            </div>
        </div>
        <a class="btn-restore" href="?restore_id=<?= $p['id'] ?>&group=<?= urlencode($groupFilter) ?>&eval_type=<?= $activeEval ?>&mr_filter=<?= urlencode($multiRoleFilter) ?>&evaluator=<?= urlencode($evaluatorFilter) ?>"
           onclick="return confirm('Restore <?= htmlspecialchars(addslashes($p['full_name'])) ?> to the analytics list?')">
            <i class="fa-solid fa-rotate-left"></i> Restore
        </a>
    </div>
</div>
<?php endforeach; ?>
</div>
<?php endif; ?>
<?php $mysqli->close(); ?>
</body></html>
<?php exit; }

// ══════════════════════════════════════════════════════════════
// VIEW: MAIN LIST
// ══════════════════════════════════════════════════════════════
// Peer tab: same reasoning as the archived view above — pull every
// faculty/staff/teacher candidate and let ec_resolve_peer_group() (below)
// decide Teacher vs Staff vs excluded, instead of filtering by raw role.
$whereRole = $activeEval === 'schoolhead'
    ? "u.role IN ('teacher','staff','superadmin')"
    : ($isMultiRole ? $mrFilterRoleSql : ($activeEval === 'peer'
        ? "u.role IN ('teacher','staff','faculty','principal','dean')"
        : ($activeEval === 'student'
            ? "u.role IN ('teacher','staff','principal','dean')"
            : ($groupFilter==='Teacher' ? "u.role='teacher'" : ($groupFilter==='Staff' ? "u.role='staff'" : "u.role IN ('teacher','staff')")))));

$people = [];
$res = $mysqli->query("
    SELECT u.id, u.full_name, u.designation, u.photo, u.role, u.secondary_role,
           aa.archived_at,
           COUNT(DISTINCT et.id) AS total_responses,
           AVG(qa.answer_score)  AS avg_score,
           MAX(et.submitted_at)  AS last_evaluated
    FROM users u
    JOIN evaluation_tracker et ON et.target_user_id=u.id AND $evalTypeSql
    LEFT JOIN questionnaire_answers qa ON qa.tracker_id=et.id
    LEFT JOIN analytics_archive aa ON aa.target_user_id=u.id
    WHERE $whereRole AND u.is_active=1
      AND (aa.id IS NULL OR " . ($isMultiRole ? '1=1' : '0=1') . ")
    GROUP BY u.id
    ORDER BY avg_score DESC, u.full_name ASC
");
if ($res) $people = $res->fetch_all(MYSQLI_ASSOC);

if ($activeEval === 'student' && !$isMultiRole) {
    $people = array_values(array_filter($people, function ($p) use ($groupFilter, $mysqli) {
        $g = ec_resolve_student_group_full($p, $mysqli);
        if ($g === null) return false;
        if ($groupFilter === 'Teacher') return $g === 'teacher';
        if ($groupFilter === 'Staff') return $g === 'staff';
        if ($groupFilter === 'SchoolHead') return $g === 'school_head';
        return true;
    }));
}

if ($activeEval === 'peer') {
    $people = array_values(array_filter($people, function ($p) use ($groupFilter, $mysqli) {
        $g = ec_resolve_peer_group_full($p, $mysqli);
        if ($g === null) return false;
        if ($groupFilter === 'Teacher') return $g === 'teacher';
        if ($groupFilter === 'Staff') return $g === 'staff';
        if ($groupFilter === 'SchoolHead') return $g === 'school_head';
        return true;
    }));
}

if ($activeEval === 'schoolhead') {
    $people = array_values(array_filter($people, function ($p) use ($groupFilter, $mysqli) {
        $g = ec_resolve_schoolhead_target_group($p, $mysqli);
        if ($g === null) return false;
        if ($groupFilter === 'Faculty') return $g === 'faculty';
        if ($groupFilter === 'EA') return $g === 'ea';
        return true;
    }));
}

$top4 = array_slice($people, 0, 4);
$low4 = array_slice(array_reverse($people), 0, 4);

$totalResponses = array_sum(array_column($people,'total_responses'));
$scores         = array_filter(array_column($people,'avg_score'), fn($s) => $s !== null);
$overallAvg     = count($scores) ? round(array_sum($scores)/count($scores),2) : null;

$totalStudents = $mysqli->query("SELECT COUNT(*) as c FROM users WHERE role='student'")->fetch_assoc()['c'] ?? 0;
$facCount = 0;
$staffCount = 0;
$schoolHeadCount = 0;
$allTargetIds = [];
$studentCandidates = $mysqli->query("SELECT id, full_name, designation, photo, role, secondary_role, sector, source
    FROM users
    WHERE role IN ('teacher','staff','principal','dean')
      AND is_active=1
      AND (role IN ('teacher','staff') OR account_status='approved')");
if ($studentCandidates) {
    while ($u = $studentCandidates->fetch_assoc()) {
        $g = ec_resolve_student_group_full($u, $mysqli);
        if ($g === 'teacher') { $facCount++; $allTargetIds[(int)$u['id']] = true; }
        elseif ($g === 'staff') { $staffCount++; $allTargetIds[(int)$u['id']] = true; }
        elseif ($g === 'school_head') { $schoolHeadCount++; $allTargetIds[(int)$u['id']] = true; }
    }
    $studentCandidates->free();
}
$totalFacStaff = count($allTargetIds);
if ($activeEval === 'schoolhead') {
    // The separate School Head Evaluation tab reports Faculty/EA targets --
    // NOT $schoolHeadCount above, which counts active Principal+Dean (that
    // meaning is only correct for the Student Evaluation tab's "School
    // Head" pill at the bottom of this file). Reusing it here was the old
    // reversed-model bug: it silently showed the Principal/Dean count
    // (0-2) instead of the actual number of Faculty/Staff/EA being rated.
    $schoolHeadTargetCount = $mysqli->query("SELECT COUNT(*) AS c FROM users WHERE $schoolheadTargetPoolSql")->fetch_assoc()['c'] ?? 0;
    $totalFacStaff = $schoolHeadTargetCount;
    $facCount = $schoolHeadTargetCount;
    $staffCount = 0;
}
if ($activeEval === 'peer') {
    // Canonical counts — use the exact same resolved grouping as the
    // Peer-to-Peer roster filter. This is deliberately NOT a raw role count:
    // Staff with an existing year-level/teaching assignment are Faculty
    // (Teaching Staff), while non-teaching Staff remain Staff.
    // Faculty-role accounts are also resolved directly to Faculty.
    // listed. A Staff account that actually teaches (has a
    // teaching_assignments row, a Teacher sector, or a secondary Teacher
    // role) is counted under Faculty, not Staff. Principal/Dean are counted
    // separately as "School Head" — Peer-to-Peer's third context, distinct
    // from the $schoolHeadCount used by the top-level School Head
    // Evaluation tab below.
    $facCount = 0;
    $staffCount = 0;
    $peerSchoolHeadCount = 0;
    $peerCandidates = $mysqli->query("SELECT id, role, secondary_role, sector, source
        FROM users
        WHERE role IN ('teacher','staff','faculty','principal','dean')
          AND is_active=1
          AND (role NOT IN ('principal','dean') OR account_status='approved')");
    if ($peerCandidates) {
        while ($u = $peerCandidates->fetch_assoc()) {
            $g = ec_resolve_peer_group_full($u, $mysqli);
            if ($g === 'teacher') $facCount++;
            elseif ($g === 'staff') $staffCount++;
            elseif ($g === 'school_head') $peerSchoolHeadCount++;
        }
        $peerCandidates->free();
    }
    $totalFacStaff = $facCount + $staffCount + $peerSchoolHeadCount;
}
if ($isMultiRole) {
    // Multi-Role counts must represent the CURRENT Multi-Role personnel,
    // not only people who already have a submitted evaluation. Keep the
    // definition identical to Questionnaire / Student Evaluation by using the
    // shared additional-role rule.
    $facCount = 0;
    $staffCount = 0;
    $mrUsers = $mysqli->query("SELECT id, role, secondary_role, designation, source, account_status, is_active
        FROM users
        WHERE role IN ('teacher','staff','faculty')
          AND is_active=1
          AND (account_status='approved' OR source='admin_nologin')");
    if ($mrUsers) {
        while ($u = $mrUsers->fetch_assoc()) {
            if (!ec_has_additional_role($u)) continue;
            $baseRole = strtolower(trim((string)$u['role']));
            if ($baseRole === 'teacher' || $baseRole === 'faculty' || strtolower(trim((string)$u['secondary_role'])) === 'teacher') {
                $facCount++;
            } else {
                $staffCount++;
            }
        }
        $mrUsers->free();
    }
    $totalFacStaff = $facCount + $staffCount;
}
$archivedCount = $mysqli->query("SELECT COUNT(*) as c FROM analytics_archive")->fetch_assoc()['c'] ?? 0;

// Count evaluations per eval_type for tab badges
$studentEvalCount = $mysqli->query("SELECT COUNT(DISTINCT et.id) as c
    FROM evaluation_tracker et
    JOIN users target ON target.id=et.target_user_id
    WHERE $studentTargetPlainSql")->fetch_assoc()['c'] ?? 0;
$multiRoleEvalCount = $mysqli->query("SELECT COUNT(DISTINCT et.id) AS c
    FROM evaluation_tracker et
    WHERE et.eval_type='student' AND (
        et.evaluation_context='multi_role'
        OR EXISTS (
            SELECT 1
            FROM questionnaire_answers qam
            JOIN user_questions uqm ON uqm.id = qam.user_question_id
            WHERE qam.tracker_id = et.id
              AND uqm.target_type = 'Multi-Role'
              AND uqm.eval_type = 'student'
        )
    )")->fetch_assoc()['c'] ?? 0;
$peerEvalCount    = $mysqli->query("SELECT COUNT(DISTINCT id) as c FROM evaluation_tracker WHERE eval_type IN ($peerTypesSql)")->fetch_assoc()['c'] ?? 0;
// Same evaluator/target direction as $evalTypeSql above (Principal/Dean are
// the EVALUATORS, Faculty/EA are the targets) — this badge previously
// filtered by target.role IN ('principal','dean'), the old reversed model,
// which meant it could never match a genuine post-fix submission and would
// silently show 0 even when School Head Evaluation had real data.
//
// Built from both roles directly (not $schoolheadEvaluatorSql) so this stays
// an unfiltered grand total, like $studentEvalCount/$peerEvalCount/
// $multiRoleEvalCount above — it must not shrink just because the
// Principal/Dean evaluator pill happens to be narrowed while on that tab.
$schoolheadEvaluatorAnySql = "et.evaluator_id IN (SELECT id FROM users WHERE role IN ('principal','dean') AND is_active=1)";
$schoolHeadEvalCount = $mysqli->query("SELECT COUNT(DISTINCT et.id) AS c
    FROM evaluation_tracker et
    WHERE et.eval_type IN $schoolheadTypeSql
      AND $schoolheadEvaluatorAnySql
      AND $schoolheadTargetSql
")->fetch_assoc()['c'] ?? 0;

// Per-evaluator counts, used to give Principal and Dean their own tabs
// (query the target rule directly rather than $schoolheadTargetSql/
// $schoolheadEvaluatorSql, which are built off the live $evaluatorFilter and
// would otherwise collapse to whichever evaluator is currently selected).
$principalEvalCount = $mysqli->query("SELECT COUNT(DISTINCT et.id) AS c
    FROM evaluation_tracker et
    WHERE et.eval_type IN $schoolheadTypeSql
      AND et.evaluator_id IN (SELECT id FROM users WHERE role='principal' AND is_active=1)
      AND $schoolheadTargetSql
")->fetch_assoc()['c'] ?? 0;
$deanEvalCount = $mysqli->query("SELECT COUNT(DISTINCT et.id) AS c
    FROM evaluation_tracker et
    WHERE et.eval_type IN $schoolheadTypeSql
      AND et.evaluator_id IN (SELECT id FROM users WHERE role='dean' AND is_active=1)
      AND $schoolheadTargetSql
")->fetch_assoc()['c'] ?? 0;
// How many School Head accounts are actually active/available to evaluate
// right now (0, 1, or 2) — distinct from $schoolHeadCount, which counts the
// Faculty/EA being evaluated, not the evaluators doing the evaluating.
$activeSchoolHeadEvaluators = $mysqli->query("SELECT COUNT(*) AS c FROM users WHERE role IN ('principal','dean') AND is_active=1")->fetch_assoc()['c'] ?? 0;

$staffDesigCounts = [];
if ($activeEval === 'peer') {
    // Peer tab's Staff pill uses the canonical non-teaching-staff predicate,
    // so this breakdown must match it rather than every role='staff' row.
    $sdq = $mysqli->query("SELECT id, designation, role, secondary_role FROM users WHERE role IN ('teacher','staff','faculty') AND is_active=1");
    if ($sdq) {
        while ($r = $sdq->fetch_assoc()) {
            if (ec_resolve_peer_group($r, $mysqli) !== 'staff') continue;
            $d = $r['designation'];
            $staffDesigCounts[$d] = ($staffDesigCounts[$d] ?? 0) + 1;
        }
        ksort($staffDesigCounts);
    }
} elseif ($activeEval === 'student') {
    $sdq = $mysqli->query("SELECT id, designation, role, secondary_role, sector, source
        FROM users
        WHERE role IN ('teacher','staff') AND is_active=1 AND account_status='approved'");
    if ($sdq) {
        while ($r = $sdq->fetch_assoc()) {
            if (ec_resolve_student_group_full($r, $mysqli) !== 'staff') continue;
            $d = $r['designation'];
            $staffDesigCounts[$d] = ($staffDesigCounts[$d] ?? 0) + 1;
        }
        ksort($staffDesigCounts);
    }
} else {
    $sdq = $mysqli->query("SELECT designation, COUNT(*) as c FROM users WHERE role='staff' AND is_active=1 GROUP BY designation ORDER BY designation");
    if ($sdq) while ($r = $sdq->fetch_assoc()) $staffDesigCounts[$r['designation']] = $r['c'];
}

pageHead('Evaluation Report', $evalColor, $evalColorBg, $evalColorBorder);
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
.group-tab.active-all{background:rgba(59,130,246,.2);border-color:rgba(59,130,246,.5);color:#2563EB;}
.group-tab.active-faculty{background:rgba(14,116,144,.09);border-color:rgba(14,116,144,.30);color:#0E7490;}
.group-tab.active-faculty i{color:#0E7490;}
.group-tab.active-multirole{background:rgba(14,116,144,.09);border-color:rgba(14,116,144,.30);color:#0E7490;}
.group-tab.active-multirole i{color:#4968C8;}
.group-tab .all-icon{color:#2563EB;}
.group-tab .teacher-icon{color:#0E7490;}
.group-tab .staff-icon{color:#4968C8;}
.group-tab .multirole-icon{color:#4968C8;}
.group-tab.active-all i{color:#2563EB;}
.faculty-subtabs{display:flex;align-items:center;gap:8px;margin:-8px 0 20px 0;padding:8px 10px;border:1px solid var(--border);border-radius:12px;background:#F8FAFC;width:fit-content;box-shadow:0 2px 8px rgba(30,82,144,.05);}
.faculty-subtabs-label{font-size:11px;color:var(--muted);font-weight:800;text-transform:uppercase;letter-spacing:.7px;margin-right:2px;}
.faculty-subtab{display:flex;align-items:center;gap:7px;padding:8px 15px;border-radius:9px;border:1px solid transparent;color:var(--muted);text-decoration:none;font-size:13px;font-weight:700;transition:all .2s;}
.faculty-subtab:hover{background:#EEF2F7;color:#0B1F3A;}
/* Faculty and Multi-Role sub-tabs use one consistent tab color.
   Icons remain individually color-coded for quick visual recognition. */
.faculty-subtab.active-teacher,
.faculty-subtab.active-staff{background:#E8F8FB;border-color:rgba(14,116,144,.24);color:#0E7490;}
.faculty-subtab.active-mr-all{background:#E8F8FB;border-color:rgba(14,116,144,.24);color:#0E7490;}
.faculty-subtab i{color:#67819E;transition:color .2s;}
.faculty-subtab .teacher-icon{color:#2563EB;}
.faculty-subtab .staff-icon{color:#4968C8;}
.faculty-subtab .mr-all-icon{color:#0E7490;}
.faculty-subtab.active-teacher i{color:#2563EB;}
.faculty-subtab.active-staff i{color:#4968C8;}
.faculty-subtab.active-mr-all i{color:#0E7490;}
.tab-count{background:rgba(255,255,255,.12);border-radius:20px;padding:1px 8px;font-size:11px;font-weight:700;}
.group-tab.active-schoolhead{background:rgba(199,122,8,.09);border-color:rgba(199,122,8,.30);color:#C77A08;}
.group-tab.active-schoolhead i{color:#C77A08;}
.group-tab .schoolhead-icon{color:#C77A08;}
.desig-subtabs{display:flex;gap:6px;flex-wrap:wrap;margin-bottom:20px;padding:12px 16px;background:rgba(217,119,6,.06);border:1px solid rgba(217,119,6,.15);border-radius:10px;}
.desig-subtab{padding:5px 14px;border-radius:20px;font-size:12px;font-weight:600;background:rgba(37,99,235,.06);border:1px solid var(--border);color:var(--muted);cursor:pointer;text-decoration:none;transition:all .2s;}
.desig-subtab:hover{color:var(--light);}
.desig-subtab.active{background:rgba(217,119,6,.2);border-color:rgba(217,119,6,.4);color:#D69612;}
.summary-row{display:grid;grid-template-columns:repeat(auto-fill,minmax(180px,1fr));gap:16px;margin-bottom:24px;}
.sum-card{background:var(--mid);border:1px solid var(--border);border-radius:12px;padding:18px 20px;}
.sum-label{font-size:11px;text-transform:uppercase;letter-spacing:.8px;color:var(--muted);margin-bottom:8px;}
.sum-value{font-size:28px;font-weight:700;color:var(--light);}
.sum-sub{font-size:12px;color:var(--muted);margin-top:4px;}
.sum-card.highlight .sum-value{color:var(--ec);}
.standings-row{display:grid;grid-template-columns:1fr 1fr;gap:20px;margin-bottom:28px;}
.standing-panel{background:var(--mid);border:1px solid var(--border);border-radius:14px;padding:22px;}
.standing-title{font-size:15px;font-weight:700;margin-bottom:16px;display:flex;align-items:center;gap:8px;}
.standing-title.top{color:#32B98A;}
.standing-title.low{color:#E6788A;}
.standing-item{display:flex;align-items:center;gap:12px;padding:10px 0;border-bottom:1px solid var(--border);}
.standing-item:last-child{border-bottom:none;}
.standing-rank{font-size:13px;font-weight:700;color:var(--muted);width:22px;text-align:center;flex-shrink:0;}
.standing-photo{width:38px;height:38px;border-radius:50%;object-fit:cover;border:2px solid var(--border);}
.standing-photo-ph{width:38px;height:38px;border-radius:50%;background:var(--inner);border:2px solid var(--border);display:flex;align-items:center;justify-content:center;color:var(--muted);font-size:14px;}
.standing-info{flex:1;}
.standing-name{font-size:13px;font-weight:600;color:var(--light);}
.standing-desig{font-size:11px;color:var(--muted);}
.standing-score{font-size:15px;font-weight:700;}
.score-top{color:#32B98A;}
.score-low{color:#E6788A;}
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
.group-badge.teacher{background:rgba(14,116,144,.12);color:#0E7490;border:1px solid rgba(13,148,136,.3);}
.group-badge.staff{background:rgba(217,119,6,.15);color:#D69612;border:1px solid rgba(217,119,6,.3);}
.group-badge.schoolhead{background:rgba(217,119,6,.10);color:#C77A08;border:1px solid rgba(217,119,6,.28);}
.desig-pill{display:inline-flex;align-items:center;gap:4px;padding:2px 9px;border-radius:20px;font-size:11px;font-weight:600;background:rgba(255,255,255,.07);color:var(--muted);border:1px solid var(--border);}
.person-stats{display:flex;gap:20px;align-items:center;flex-shrink:0;flex-wrap:wrap;}
.pstat{display:flex;flex-direction:column;align-items:center;gap:2px;}
.pstat-val{font-size:18px;font-weight:700;color:var(--light);}
.pstat-lbl{font-size:10px;color:var(--muted);text-transform:uppercase;letter-spacing:.5px;}
.pstat-val.good{color:#32B98A;}
.pstat-val.mid{color:var(--gold-h);}
.pstat-val.poor{color:#E6788A;}
.score-bar-wrap{display:flex;align-items:center;gap:10px;min-width:140px;}
.score-bar-bg{flex:1;height:6px;background:rgba(255,255,255,.07);border-radius:3px;overflow:hidden;}
.score-bar-fill{height:100%;border-radius:3px;}
.arrow-icon{color:var(--muted);font-size:14px;flex-shrink:0;margin-left:4px;}
.person-actions{display:flex;align-items:center;gap:8px;flex-shrink:0;margin-left:12px;}
.btn-archive{background:none;border:1px solid var(--border);color:var(--muted);padding:9px 11px;border-radius:8px;font-size:13px;cursor:pointer;transition:all .2s;display:flex;align-items:center;gap:6px;font-family:'Inter',sans-serif;}
.btn-archive:hover{background:rgba(240,84,84,.12);border-color:rgba(240,84,84,.4);color:#E6788A;}
.btn-archive span{font-size:12px;font-weight:600;}
.no-evaluated{text-align:center;padding:48px;background:var(--mid);border-radius:14px;border:1px solid var(--border);color:var(--muted);}
.no-evaluated i{font-size:36px;opacity:.3;display:block;margin-bottom:14px;}
/* peer note */
.peer-info-note{display:flex;align-items:flex-start;gap:10px;padding:12px 16px;border-radius:10px;margin-bottom:20px;font-size:12px;color:var(--muted);line-height:1.6;background:rgba(124,58,237,.06);border:1px solid rgba(73,104,200,.12);}
.peer-info-note i{color:#4968C8;margin-top:1px;flex-shrink:0;}
@media(max-width:800px){.standings-row{grid-template-columns:1fr;}body{padding:16px;}}
@media(max-width:560px){.summary-row{grid-template-columns:1fr 1fr;}.person-header{flex-wrap:wrap;}.btn-archive span{display:none;}.eval-switcher{width:100%;}.eval-tab{flex:1;justify-content:center;}}

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
</head>
<body>

<?php if ($toast): ?><div class="toast"><i class="fa-solid fa-circle-check"></i><?= htmlspecialchars($toast) ?></div><?php endif; ?>

<!-- ── EVAL TYPE SWITCHER ── -->
<?php
// Peer-to-Peer and School Head don't support the Multi-Role pill, so switching
// to them resets the group filter to All rather than carrying over a filter
// that wouldn't apply.
$groupForOtherTabs = $groupFilter === 'MultiRole' ? 'All' : $groupFilter;
?>
<div class="eval-switcher">
    <a href="?group=<?= urlencode($groupFilter) ?>&eval_type=student"
       class="eval-tab student <?= $activeEval==='student'?'active':'' ?>">
        <i class="fa-solid fa-graduation-cap"></i> Student Evaluation
        <span class="tab-badge"><?= $studentEvalCount + $multiRoleEvalCount ?></span>
    </a>
    <div class="eval-divider"></div>
    <a href="?group=<?= urlencode($groupForOtherTabs) ?>&eval_type=peer"
       class="eval-tab peer <?= $activeEval==='peer'?'active':'' ?>">
        <i class="fa-solid fa-people-arrows"></i> Peer-to-Peer
        <span class="tab-badge"><?= $peerEvalCount ?></span>
    </a>
    <div class="eval-divider"></div>
    <a href="?group=<?= urlencode($groupForOtherTabs) ?>&eval_type=schoolhead"
       class="eval-tab schoolhead <?= $activeEval==='schoolhead'?'active':'' ?>">
        <i class="fa-solid fa-user-tie"></i> School Head Evaluation
        <span class="tab-badge"><?= $schoolHeadEvalCount ?></span>
    </a>
</div>

<div class="header-actions" style="display:flex;justify-content:flex-end;margin-bottom:24px;">
    <button class="btn-print" onclick="window.print()"><i class="fa-solid fa-print"></i> Print / Save PDF</button>
</div>

<?php if ($activeEval === 'peer'): ?>
<div class="peer-info-note">
    <i class="fa-solid fa-circle-info"></i>
    <div><strong style="color:#4968C8;display:block;margin-bottom:2px;">Peer-to-Peer Evaluations</strong>
    These results show how faculty, staff, and school heads rated their colleagues under the Faculty, Staff, and School Head contexts. Evaluations are submitted by fellow personnel, not students. Questions used are from the Peer-to-Peer question bank.</div>
</div>
<?php elseif ($activeEval === 'schoolhead'): ?>
<div class="peer-info-note" style="background:rgba(217,119,6,.06);border-color:rgba(217,119,6,.18);">
    <i class="fa-solid fa-user-tie" style="color:#C77A08;"></i>
    <div><strong style="color:#C77A08;display:block;margin-bottom:2px;">School Head Evaluation Submissions</strong>
    These are ratings submitted by the active Dean and/or Principal, evaluating Faculty and the EA. Switch between the Principal and Dean tabs below to review each evaluator's submissions on their own, or stay on All to see both combined.</div>
</div>
<?php endif; ?>

<!-- GROUP TABS -->
<?php if ($activeEval === 'schoolhead'): ?>
<div class="group-tabs">
    <a href="?group=<?= urlencode($groupFilter) ?>&eval_type=schoolhead&evaluator=All"
       class="group-tab <?= $evaluatorFilter==='All'?'active-all':'' ?>">
        <i class="fa-solid fa-users all-icon"></i> All
        <span class="tab-count"><?= $schoolHeadEvalCount ?></span>
    </a>
    <a href="?group=<?= urlencode($groupFilter) ?>&eval_type=schoolhead&evaluator=Principal"
       class="group-tab <?= $evaluatorFilter==='Principal'?'active-faculty':'' ?>">
        <i class="fa-solid fa-user-tie teacher-icon"></i> Principal
        <span class="tab-count"><?= $principalEvalCount ?></span>
    </a>
    <a href="?group=<?= urlencode($groupFilter) ?>&eval_type=schoolhead&evaluator=Dean"
       class="group-tab <?= $evaluatorFilter==='Dean'?'active-staff':'' ?>">
        <i class="fa-solid fa-user-tie staff-icon"></i> Dean
        <span class="tab-count"><?= $deanEvalCount ?></span>
    </a>
</div>
<?php else: ?>
<div class="group-tabs">
    <a href="?group=All&eval_type=<?= $activeEval ?>"
       class="group-tab <?= $groupFilter==='All'?'active-all':'' ?>">
        <i class="fa-solid fa-users all-icon"></i> All
        <span class="tab-count"><?= $totalFacStaff ?></span>
    </a>

    <!-- Faculty and Staff are independent top-level tabs -- matching the
         same Faculty/Staff/Multi-Role/School Head structure used in
         admin/questionnaire.php and the student dashboard. No more nested
         Faculty/Staff sub-toggle underneath a merged "Faculty" tab. -->
    <a href="?group=Teacher&eval_type=<?= $activeEval ?>"
       class="group-tab <?= $groupFilter==='Teacher'?'active-faculty':'' ?>">
        <i class="fa-solid fa-chalkboard-user teacher-icon"></i> Faculty
        <span class="tab-count"><?= $facCount ?></span>
    </a>

    <a href="?group=Staff&eval_type=<?= $activeEval ?>"
       class="group-tab <?= $groupFilter==='Staff'?'active-staff':'' ?>">
        <i class="fa-solid fa-briefcase staff-icon"></i> Staff
        <span class="tab-count"><?= $staffCount ?></span>
    </a>

    <?php if ($activeEval === 'peer'): ?>
    <!-- Peer-to-Peer's third context: Principal + Dean, peer-reviewed by
         fellow personnel. This remains separate from Student Evaluation. -->
    <a href="?group=SchoolHead&eval_type=peer"
       class="group-tab <?= $groupFilter==='SchoolHead'?'active-schoolhead':'' ?>">
        <i class="fa-solid fa-user-tie schoolhead-icon"></i> School Head
        <span class="tab-count"><?= $peerSchoolHeadCount ?></span>
    </a>
    <?php endif; ?>

    <?php if ($activeEval === 'student'): ?>
    <a href="?group=MultiRole&eval_type=student"
       class="group-tab <?= $groupFilter==='MultiRole'?'active-multirole':'' ?>">
        <i class="fa-solid fa-people-group multirole-icon"></i> Multi-Role
        <span class="tab-count"><?= $multiRoleEvalCount ?></span>
    </a>
    <a href="?group=SchoolHead&eval_type=student"
       class="group-tab <?= $groupFilter==='SchoolHead'?'active-schoolhead':'' ?>">
        <i class="fa-solid fa-user-tie schoolhead-icon"></i> School Head
        <span class="tab-count"><?= $schoolHeadCount ?></span>
    </a>
    <?php endif; ?>
</div>
<?php endif; ?>

<?php if ($isMultiRole): ?>
<div class="faculty-subtabs multi-role-subtabs">
    <span class="faculty-subtabs-label">Multi-Role:</span>
    <a href="?group=MultiRole&eval_type=student&mr_filter=all"
       class="faculty-subtab <?= $multiRoleFilter==='all'?'active-mr-all':'' ?>">
        <i class="fa-solid fa-layer-group mr-all-icon"></i> All
        <span class="tab-count"><?= $facCount+$staffCount ?></span>
    </a>
    <a href="?group=MultiRole&eval_type=student&mr_filter=teacher"
       class="faculty-subtab <?= $multiRoleFilter==='teacher'?'active-teacher':'' ?>">
        <i class="fa-solid fa-chalkboard-user teacher-icon"></i> Faculty
        <span class="tab-count"><?= $facCount ?></span>
    </a>
    <a href="?group=MultiRole&eval_type=student&mr_filter=staff"
       class="faculty-subtab <?= $multiRoleFilter==='staff'?'active-staff':'' ?>">
        <i class="fa-solid fa-briefcase staff-icon"></i> Staff
        <span class="tab-count"><?= $staffCount ?></span>
    </a>
</div>
<?php endif; ?>

<?php if (!$isMultiRole && $groupFilter === 'Staff' && !empty($staffDesigCounts)): ?>
<div class="desig-subtabs">
    <span style="font-size:11px;color:var(--muted);font-weight:700;text-transform:uppercase;letter-spacing:.8px;margin-right:4px;">By Role:</span>
    <?php foreach ($staffDesigCounts as $desig => $cnt): ?>
    <a href="#" class="desig-subtab" onclick="filterByDesig('<?= htmlspecialchars(addslashes($desig)) ?>');return false;"><?= htmlspecialchars($desig) ?> <span style="opacity:.6">(<?= $cnt ?>)</span></a>
    <?php endforeach; ?>
    <a href="#" class="desig-subtab active" onclick="filterByDesig('all');return false;">Show All</a>
</div>
<?php endif; ?>

<!-- PEOPLE TABLE -->
<style>
.ra-table-card{background:var(--mid);border:1px solid var(--border);border-radius:14px;padding:22px 24px;}
.ra-table-head{display:flex;align-items:center;justify-content:space-between;gap:16px;flex-wrap:wrap;margin-bottom:16px;}
.ra-table-head .section-title{margin-bottom:0;}
.ra-table-tools{display:flex;align-items:center;gap:10px;}
.ra-tool-btn{padding:9px 14px;border-radius:8px;border:1px solid var(--border);background:var(--inner);color:var(--light);font-size:13px;font-weight:600;cursor:pointer;display:inline-flex;align-items:center;gap:7px;white-space:nowrap;}
.ra-tool-btn:hover{border-color:var(--ec);color:var(--ec);}
.ra-filters-wrap{position:relative;}
.ra-filters-panel{display:none;position:absolute;right:0;top:calc(100% + 6px);background:var(--mid);border:1px solid var(--border);border-radius:10px;padding:14px;min-width:220px;box-shadow:0 8px 24px rgba(0,0,0,.18);z-index:20;}
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
table.ra-table tbody tr:hover{background:var(--inner);}
.ra-name-cell{display:flex;align-items:center;gap:10px;}
.ra-photo{width:34px;height:34px;border-radius:50%;object-fit:cover;border:1px solid var(--border);flex-shrink:0;}
.ra-photo-ph{width:34px;height:34px;border-radius:50%;background:var(--inner);border:1px solid var(--border);display:flex;align-items:center;justify-content:center;color:var(--muted);font-size:13px;flex-shrink:0;}
.ra-name-text a{font-weight:700;color:var(--light);text-decoration:none;}
.ra-name-text a:hover{color:var(--ec);}
.ra-role-badge{font-size:11px;font-weight:700;padding:3px 9px;border-radius:20px;white-space:nowrap;display:inline-flex;align-items:center;gap:5px;}
.ra-score{font-weight:700;}
.ra-view-btn{padding:6px 14px;border-radius:7px;border:1px solid var(--ec-bd,var(--border));background:var(--ec-bg,transparent);color:var(--ec,inherit);font-size:12px;font-weight:700;text-decoration:none;display:inline-flex;align-items:center;gap:6px;}
.ra-view-btn:hover{opacity:.85;}
.ra-icon-btn{padding:6px 10px;border-radius:7px;border:1px solid var(--border);background:transparent;color:var(--muted);font-size:12px;font-weight:700;cursor:pointer;display:inline-flex;align-items:center;gap:6px;}
.ra-icon-btn:hover{border-color:var(--ec);color:var(--ec);}
.ra-actions-cell{display:flex;gap:8px;white-space:nowrap;}
.ra-pager{display:flex;align-items:center;justify-content:space-between;margin-top:16px;flex-wrap:wrap;gap:10px;}
.ra-pager-info{font-size:12px;color:var(--muted);}
.ra-pager-btns{display:flex;gap:6px;}
.ra-pager-btns button{width:30px;height:30px;border-radius:7px;border:1px solid var(--border);background:var(--inner);color:var(--light);font-size:12px;font-weight:700;cursor:pointer;}
.ra-pager-btns button.active{background:var(--ec,#1E5290);border-color:var(--ec,#1E5290);color:#fff;}
.ra-pager-btns button:disabled{opacity:.4;cursor:not-allowed;}
</style>

<div class="ra-table-card">
    <div class="ra-table-head">
        <div class="section-title">
            <i class="fa-solid fa-list-check" style="color:var(--accent)"></i>
            <?= $activeEval==='student' ? 'Users Being Evaluated' : 'Evaluated Personnel' ?>
            <span style="font-size:13px;font-weight:400;color:var(--muted)">(<?= count($people) ?> evaluated)</span>
        </div>
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
                            <?php foreach (array_unique(array_column($people, 'role')) as $r): ?>
                            <option value="<?= htmlspecialchars($r) ?>"><?= htmlspecialchars(ucfirst($r)) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <button class="ra-filter-clear" onclick="raClearFilters()">Clear filters</button>
                </div>
            </div>
        </div>
    </div>

    <?php if (empty($people)): ?>
    <div class="no-evaluated">
        <i class="fa-solid fa-hourglass-half"></i>
        <p>No <?= $activeEval==='schoolhead'?'School Head':($activeEval==='peer'?'peer-to-peer':($isMultiRole?'Multi-Role':'student')) ?> evaluations have been submitted yet.<br>
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
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
        <?php $rowNum = 0; foreach ($people as $p):
            $rowNum++;
            $avg   = $p['avg_score'] !== null ? round($p['avg_score'],2) : null;
            $color = scoreColor($avg);
            // Two different meanings hide behind "school head" here. Student
            // Evaluation's School Head pill really does list Principal/Dean as
            // the evaluated people. The School Head Evaluation tab is the
            // opposite direction -- Principal/Dean are the evaluators there,
            // and $p is one of the Faculty/EA people they evaluated -- so it
            // must NOT be labeled Principal/Dean here (the old reversed-model
            // bug).
            $isSchoolHeadTargetPill = $activeEval === 'student' && $groupFilter === 'SchoolHead';
            $isSchoolHeadEvalTab = $activeEval === 'schoolhead';
            $isFac = $activeEval === 'student' && !$isSchoolHeadTargetPill ? ec_resolve_student_group_full($p, $mysqli) === 'teacher' : $p['role'] === 'teacher';
            $isEA = $isSchoolHeadEvalTab && $p['role'] === 'superadmin';
            $isArchived = !empty($p['archived_at']);
            $roleLabel = $isSchoolHeadTargetPill ? ($p['role']==='principal'?'Principal':'Dean') : ($isSchoolHeadEvalTab ? schoolheadTargetLabel($p['role']) : ($isMultiRole ? 'Multi-Role' : ($isFac?'Faculty':'Staff')));
            $roleIcon  = $isSchoolHeadTargetPill ? 'fa-user-tie' : ($isEA ? 'fa-user-tie' : ($isMultiRole ? 'fa-people-group' : ($isFac?'fa-chalkboard-user':'fa-briefcase')));
            $roleBadgeStyle = $isSchoolHeadTargetPill || $isEA
                ? 'background:rgba(30,82,144,.10);color:var(--ec,#1E5290);border:1px solid rgba(30,82,144,.25);'
                : ($isMultiRole
                    ? 'background:rgba(245,158,11,.15);color:#D69612;border:1px solid rgba(245,158,11,.3);'
                    : ($isFac
                        ? 'background:rgba(30,82,144,.10);color:#1E5290;border:1px solid rgba(30,82,144,.25);'
                        : 'background:rgba(214,150,18,.10);color:#B5790E;border:1px solid rgba(214,150,18,.25);'));
        ?>
            <tr data-search="<?= htmlspecialchars(strtolower($p['full_name'].' '.$p['designation'])) ?>" data-desig="<?= htmlspecialchars($p['designation']) ?>" data-score="<?= $avg !== null ? $avg : '' ?>" data-role="<?= htmlspecialchars($p['role']) ?>">
                <td><?= $rowNum ?></td>
                <td>
                    <div class="ra-name-cell">
                        <?php if($p['photo']): ?><img class="ra-photo" src="../image/<?= htmlspecialchars($p['photo']) ?>" alt=""/>
                        <?php else: ?><div class="ra-photo-ph"><i class="fa-solid fa-user"></i></div><?php endif; ?>
                        <div class="ra-name-text">
                            <a href="?view=students&target_id=<?= $p['id'] ?>&group=<?= urlencode($groupFilter) ?>&eval_type=<?= $activeEval ?>&mr_filter=<?= urlencode($multiRoleFilter) ?>&evaluator=<?= urlencode($evaluatorFilter) ?>"><?= htmlspecialchars($p['full_name']) ?></a>
                            <?php if ($isArchived && $isMultiRole): ?>
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
                        <a class="ra-view-btn" href="?view=students&target_id=<?= $p['id'] ?>&group=<?= urlencode($groupFilter) ?>&eval_type=<?= $activeEval ?>&mr_filter=<?= urlencode($multiRoleFilter) ?>&evaluator=<?= urlencode($evaluatorFilter) ?>"><i class="fa-solid fa-eye"></i> View</a>
                        <?php if ($isArchived && $isMultiRole): ?>
                        <a class="ra-icon-btn" href="?restore_id=<?= $p['id'] ?>&group=<?= urlencode($groupFilter) ?>&eval_type=<?= $activeEval ?>&mr_filter=<?= urlencode($multiRoleFilter) ?>&evaluator=<?= urlencode($evaluatorFilter) ?>&view=archived"><i class="fa-solid fa-rotate-left"></i> Restore</a>
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

    let activeDesig = 'all';

    window.raFilterTable = function() {
        const q = document.getElementById('raSearch').value.trim().toLowerCase();
        const minScore = parseFloat(document.getElementById('raMinScore').value) || 0;
        const roleVal = document.getElementById('raRoleFilter').value;
        allRows.forEach(r => {
            const matchesSearch = !q || r.dataset.search.includes(q);
            const matchesDesig = activeDesig === 'all' || r.dataset.desig === activeDesig;
            const rowScore = parseFloat(r.dataset.score);
            const matchesScore = minScore === 0 || (!isNaN(rowScore) && rowScore >= minScore);
            const matchesRole = roleVal === 'all' || r.dataset.role === roleVal;
            r.dataset.matched = (matchesSearch && matchesDesig && matchesScore && matchesRole) ? 'true' : 'false';
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
            document.getElementById('raFiltersPanel').classList.remove('open');
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

    window.filterByDesig = function(desig) {
        activeDesig = desig;
        document.querySelectorAll('.desig-subtabs .desig-subtab').forEach(t => t.classList.remove('active'));
        if (window.event && window.event.target) {
            window.event.target.closest('.desig-subtab').classList.add('active');
        }
        raFilterTable();
    };

    allRows.forEach(r => r.dataset.matched = 'true');
    render();
})();

function archivePerson(id, name) {
    if (confirm(`Archive "${name}"? They'll be hidden from this list but their evaluation data is kept and can be restored anytime.`)) {
        window.location.href = `?archive_id=${id}&group=<?= urlencode($groupFilter) ?>&eval_type=<?= $activeEval ?>&mr_filter=<?= urlencode($multiRoleFilter) ?>&evaluator=<?= urlencode($evaluatorFilter) ?>`;
    }
}
</script>
<?php $mysqli->close(); ?>
</body>
</html>