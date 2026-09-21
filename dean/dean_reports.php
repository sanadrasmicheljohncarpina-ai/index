<?php
// Dean Reports & Analytics — based on the EA Reports & Analytics workflow, with Dean scope/theme
session_start();
require_once 'db.php';
require_once '../shared/EvaluationContextService.php';
require_once '../shared/system_settings_service.php';

// ── AUTH GUARD ───────────────────────────────────────────────
// Evaluation scores and archive/restore actions are sensitive —
// require an authenticated admin-level session before anything else runs.
if (empty($_SESSION['user_id']) || !in_array($_SESSION['role'] ?? '', ['dean'])) {
    header("Location: dean_login.php");
    exit;
}

// ── EXECUTIVE PROFILE (shared portal layout) ─────────────────
$stmt = $mysqli->prepare("SELECT full_name, username, email, designation, photo, department FROM users WHERE id = ? LIMIT 1");
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$me = $stmt->get_result()->fetch_assoc() ?: [];
$stmt->close();
$photo_src = !empty($me['photo']) ? '../image/' . $me['photo'] : '../image/pbi_logo';

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

// Student evaluator school-level metadata — kept in sync with the EA analytics
// model so the Dean can apply the same Student Evaluation logic, but only to
// College evaluators for the Higher Education report.
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

// Portal scope: Reports & Analytics is a pixel/function clone of the EA page,
// but its dataset is restricted to the current executive's education division.
$reportScope = 'DEAN';
$reportScopeSql = ($reportScope === 'DEAN')
    ? "(u.role IN ('dean','principal') OR u.role='staff' OR (u.role IN ('teacher','faculty') AND EXISTS (SELECT 1 FROM user_year_levels scope_uyl WHERE scope_uyl.user_id=u.id AND scope_uyl.year_level IN ('1st Year College','2nd Year College','3rd Year College','4th Year College'))))"
    : "(u.role IN ('dean','principal') OR u.role='staff' OR (u.role IN ('teacher','faculty') AND EXISTS (SELECT 1 FROM user_year_levels scope_uyl WHERE scope_uyl.user_id=u.id AND scope_uyl.year_level IN ('Grade 7','Grade 8','Grade 9','Grade 10','Grade 11','Grade 12','Grade 7 - JHS','Grade 8 - JHS','Grade 9 - JHS','Grade 10 - JHS','Grade 11 - SHS','Grade 12 - SHS'))) )";
// Student evaluators are independently restricted below to College. Keep the
// portal's Higher Education scope explicit here as well for any summary queries.
$reportStudentScopeSql = "(education_level='higher_ed' OR year_level IN ('1st Year College','2nd Year College','3rd Year College','4th Year College'))";

// ── ARCHIVE / RESTORE ─────────────────────────────────────────
if (isset($_GET['archive_id'])) {
    $aid  = intval($_GET['archive_id']);
    $scopeCheck = $mysqli->query("SELECT u.id FROM users u WHERE u.id=$aid AND $reportScopeSql LIMIT 1");
    if (!$scopeCheck || !$scopeCheck->num_rows) { header("Location: ?group=".urlencode($_GET['group']??'All')."&eval_type=".urlencode($_GET['eval_type']??'student')); exit; }
    $stmt = $mysqli->prepare("INSERT IGNORE INTO analytics_archive (target_user_id) VALUES (?)");
    $stmt->bind_param("i", $aid); $stmt->execute(); $stmt->close();
    $_SESSION['toast'] = "Personnel archived. Their data is kept and can be restored anytime.";
    header("Location: dean_reports.php?group=".urlencode($_GET['group']??'All')."&eval_type=".urlencode($_GET['eval_type']??'student')); exit;
}
if (isset($_GET['restore_id'])) {
    $rid  = intval($_GET['restore_id']);
    $scopeCheck = $mysqli->query("SELECT u.id FROM users u WHERE u.id=$rid AND $reportScopeSql LIMIT 1");
    if (!$scopeCheck || !$scopeCheck->num_rows) { header("Location: ?view=archived&group=".urlencode($_GET['group']??'All')."&eval_type=".urlencode($_GET['eval_type']??'student')); exit; }
    $stmt = $mysqli->prepare("DELETE FROM analytics_archive WHERE target_user_id=?");
    $stmt->bind_param("i", $rid); $stmt->execute(); $stmt->close();
    $_SESSION['toast'] = "Personnel restored to the main list.";
    header("Location: dean_reports.php?group=".urlencode($_GET['group']??'All')."&eval_type=".urlencode($_GET['eval_type']??'student')."&view=archived"); exit;
}

$toast = $_SESSION['toast'] ?? ''; unset($_SESSION['toast']);

// ── ACTIVE EVAL TYPE ──────────────────────────────────────────
// Multi-Role is a filter inside Student Evaluation, not a separate
// top-level evaluation type. Legacy multi_role links are redirected into
// Student Evaluation with the Multi-Role filter selected.
$requestedEvalType = $_GET['eval_type'] ?? 'student';
$legacyMultiRoleLink = ($requestedEvalType === 'multi_role');
// School Head Evaluation belongs to the EA analytics view, not the Dean portal.
// Redirect any legacy/deep link to the normal Student Evaluation view instead of
// allowing an unsupported school-head mode to be activated.
$legacySchoolHeadLink = ($requestedEvalType === 'schoolhead' || $requestedEvalType === 'school_head');
$activeEval = ($legacyMultiRoleLink || $legacySchoolHeadLink) ? 'student' : $requestedEvalType;
if (!in_array($activeEval, ['student','peer'], true)) $activeEval = 'student';

$groupFilter = $_GET['group'] ?? 'All';
$allowedGroups = $activeEval === 'student'
    ? ['All','Faculty','Staff','Dean']
    : ['All','Faculty','Teacher','Staff'];
if (!in_array($groupFilter, $allowedGroups, true)) $groupFilter = 'All';
$isMultiRole = false;
$multiRoleSubFilter = 'All';
$groupForOtherTabs = in_array($groupFilter, ['Faculty','Teacher','Staff'], true) ? $groupFilter : 'All';

$settings = get_system_settings($mysqli);
$period_id_int = (int)($settings['period_id'] ?? 0);
$periodSql = $period_id_int > 0 ? "et.period_id=" . $period_id_int : "1=0";
$periodPlainSql = $period_id_int > 0 ? "period_id=" . $period_id_int : "1=0";

$sqlQuote = static function (string $value) use ($mysqli): string {
    return "'" . $mysqli->real_escape_string($value) . "'";
};
$studentTypeSql = $sqlQuote('student');
$peerTypesSql = implode(',', array_map($sqlQuote, ['peer','faculty_peer','staff_peer']));
$multiRoleContextSql = $sqlQuote('multi_role');
$teacherContextSql = $sqlQuote('teacher');
$staffContextSql = $sqlQuote('staff');
$multiRoleQuestionTypeSql = $sqlQuote('Multi-Role');

// IMPORTANT: Dean Student Evaluation is Higher Education only.
// Match the EA Student Evaluation evaluator model (evaluation_tracker.evaluator_id)
// while excluding JHS/SHS student submissions. A student is treated as College
// when the canonical education_level says higher_ed OR their assigned year_level
// is one of the four College levels. The year_level fallback also keeps legacy
// rows visible when education_level was not populated.
$collegeLevelsSql = "'1st Year College','2nd Year College','3rd Year College','4th Year College'";
$collegeStudentEvaluatorSql = "et.evaluator_id IN (
    SELECT id FROM users
    WHERE role='student'
      AND (education_level='higher_ed' OR year_level IN ($collegeLevelsSql))
)";
$collegeStudentEvaluatorPlainSql = "evaluator_id IN (
    SELECT id FROM users
    WHERE role='student'
      AND (education_level='higher_ed' OR year_level IN ($collegeLevelsSql))
)";

$multiRoleClauseSql = "$periodSql AND et.eval_type=$studentTypeSql AND $collegeStudentEvaluatorSql AND (
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
$multiRoleClausePlainSql = "$periodPlainSql AND eval_type=$studentTypeSql AND $collegeStudentEvaluatorPlainSql AND (
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
        'peer' => "$periodSql AND et.eval_type IN ($peerTypesSql)",
        default => "$periodSql AND et.eval_type=$studentTypeSql AND $collegeStudentEvaluatorSql AND (COALESCE(et.evaluation_context,'teacher') IN ($teacherContextSql,$staffContextSql) OR et.target_user_id IN (SELECT id FROM users WHERE role='dean' AND is_active=1 AND account_status='approved'))"
    };
    $evalTypePlainSql = match ($activeEval) {
        'peer' => "$periodPlainSql AND eval_type IN ($peerTypesSql)",
        default => "$periodPlainSql AND eval_type=$studentTypeSql AND $collegeStudentEvaluatorPlainSql AND (COALESCE(evaluation_context,'teacher') IN ($teacherContextSql,$staffContextSql) OR target_user_id IN (SELECT id FROM users WHERE role='dean' AND is_active=1 AND account_status='approved'))"
    };
}

// ── VIEWS ─────────────────────────────────────────────────────
$view       = $_GET['view']       ?? 'list';
$target_id  = intval($_GET['target_id']  ?? 0);
$student_id = intval($_GET['student_id'] ?? 0);  // evaluator for peer
$tracker_id = intval($_GET['tracker_id'] ?? 0);

// ── HELPERS ───────────────────────────────────────────────────
// Resolve a target into the same Student Evaluation groups used by the
// Executive Assistant report. This helper is local to the Dean report so the
// roster/count/table labels stay consistent without depending on admin_analytics.php.
function ec_resolve_student_group_full($p, $mysqli) {
    $role = strtolower(trim((string)($p['role'] ?? '')));
    if ($role === 'principal' || $role === 'dean') return 'school_head';

    $uid = (int)($p['id'] ?? 0);
    if ($uid <= 0) return null;

    $stmt = $mysqli->prepare(
        "SELECT id, full_name, designation, photo, source, role, sector,
                EXISTS(SELECT 1 FROM teaching_assignments ta WHERE ta.user_id=u.id) AS has_teaching_assignment,
                (u.role='teacher' OR u.sector='Teacher'
                 OR EXISTS(SELECT 1 FROM teaching_assignments ta2 WHERE ta2.user_id=u.id)
                 OR EXISTS(SELECT 1 FROM user_year_levels yl2 WHERE yl2.user_id=u.id)) AS is_teaching_staff,
                EXISTS(SELECT 1 FROM user_year_levels yl WHERE yl.user_id=u.id) AS has_year_level
         FROM users u WHERE u.id=? LIMIT 1"
    );
    if (!$stmt) return null;
    $stmt->bind_param('i', $uid);
    $stmt->execute();
    $u = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    if (!$u) return null;

    // Mirror the shared Evaluation Context / Questionnaire teaching rule.
    $has_teacher = ((int)($u['is_teaching_staff'] ?? 0) === 1)
        || (function_exists('ec_has_teacher_function') && ec_has_teacher_function($u));
    if ($has_teacher) return 'teacher';

    $has_staff = function_exists('ec_has_staff_function') && ec_has_staff_function($u);
    if ($role === 'staff' || $has_staff) {
        return (
            (int)($u['has_teaching_assignment'] ?? 0) === 0
            && (int)($u['has_year_level'] ?? 0) === 0
        ) ? 'staff' : null;
    }
    return null;
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
function render_exec_sidebar(string $active, array $me, string $photo_src): void {
    $links = [
        'dashboard' => ['dean_dashboard.php','fa-gauge','Dashboard'],
        'evaluation' => ['dean_evaluation.php','fa-clipboard-check','My Evaluation'],
        'tracker' => ['dean_evaluation_tracker.php','fa-satellite-dish','Evaluation Tracker'],
        'results' => ['dean_results.php','fa-star-half-stroke','View Results'],
        'reports' => ['dean_reports.php','fa-chart-line','Evaluation Reports'],
        'settings' => ['dean_account_settings.php','fa-gear','Account Settings'],
    ];
    ?>
    <aside class="sidebar">
        <div class="sb-profile">
            <img class="sb-photo" src="<?= htmlspecialchars($photo_src) ?>" alt="Profile"/>
            <div class="sb-name"><?= htmlspecialchars($me['full_name'] ?? 'Dean') ?></div>
            <div class="sb-role"><?= htmlspecialchars($me['designation'] ?? 'Dean') ?></div>
            <div class="sb-scope">Higher Education Division</div>
        </div>
        <nav class="sb-nav">
            <div class="sb-section-title">MAIN</div>
            <?php foreach ($links as $key => [$href,$icon,$label]): ?>
                <?php if ($key === 'settings'): ?>
                    <div class="sb-section-title sb-section-title-account">ACCOUNT</div>
                <?php endif; ?>
                <a href="<?= htmlspecialchars($href) ?>" class="<?= $key === $active ? 'active' : '' ?>"><i class="fa-solid <?= htmlspecialchars($icon) ?>"></i> <?= htmlspecialchars($label) ?></a>
            <?php endforeach; ?>
        </nav>
        <div class="sb-logout"><a href="dean_logout.php"><i class="fa-solid fa-right-from-bracket"></i> Log Out</a></div>
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
<title><?= htmlspecialchars($title) ?> — PBI Evaluation Reports</title>
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
<style>
:root{--portal-accent:#7C5FD9;--portal-accent-h:#9C85F0;--portal-accent-glow:rgba(124,95,217,.4);--portal-accent-bg:rgba(124,95,217,.15);}
html{background:#0A192F !important;color-scheme:dark;}
body{min-height:100vh;display:flex !important;padding:0 !important;background:linear-gradient(rgba(5,18,36,.72),rgba(5,18,36,.82)),url('../background.png') center center / cover no-repeat fixed !important;background-color:#0A192F !important;color:#E0E6F0 !important;font-family:'DM Sans',sans-serif !important;}
.sidebar{width:250px;flex:0 0 250px;min-height:100vh;background:#0F1F33;border-right:1px solid #060E18;padding:28px 20px;display:flex;flex-direction:column;position:sticky;top:0;height:100vh;z-index:20;}
.sb-nav .sb-section-title{width:100%;padding:0 6px;margin:8px 0 3px;color:#C4B5FD;font-size:11px;font-weight:900;letter-spacing:1.5px;line-height:1.25;text-align:center;text-transform:uppercase;text-shadow:0 1px 8px rgba(167,139,250,.16)}.sb-nav .sb-section-title:first-child{margin-top:0}.sb-profile{text-align:center;margin-bottom:26px}.sb-photo{width:72px;height:72px;border-radius:50%;object-fit:cover;border:2.5px solid var(--portal-accent);box-shadow:0 0 18px var(--portal-accent-glow);margin:0 auto 10px;display:block}.sb-name{font-weight:700;font-size:15px;color:#fff}.sb-role{font-size:11px;color:var(--portal-accent-h);text-transform:uppercase;letter-spacing:.6px;margin-top:2px}.sb-scope{font-size:10px;color:#A0B3C6;margin-top:4px}.sb-nav{display:flex;flex-direction:column;gap:4px;margin-top:10px}.sb-nav a{display:flex;align-items:center;gap:10px;padding:11px 14px;border-radius:8px;color:#A0B3C6;text-decoration:none;font-size:14px;font-weight:500}.sb-nav a:hover,.sb-nav a.active{background:var(--portal-accent-bg);color:#fff}.sb-nav a i{width:18px;text-align:center;color:var(--portal-accent-h)}.sb-logout{margin-top:auto}.sb-logout a{display:flex;align-items:center;gap:10px;padding:11px 14px;border-radius:8px;color:#fca5a5;text-decoration:none;font-size:14px}
.main{flex:1;min-width:0;padding:36px 44px !important;}
.page-header{background:rgba(23,42,69,.85) !important;border:1px solid rgba(255,255,255,.08) !important;color:#E0E6F0 !important;box-shadow:0 8px 32px rgba(0,0,0,.45) !important}.page-header h1,.page-title,.section-title,.sheet-name,.target-name,.person-name,.standing-name{color:#fff !important}.page-header p,.page-sub,.standing-desig,.person-meta,.sum-label,.sum-sub,.pstat-lbl,.muted,.no-data{color:#A0B3C6 !important}
.eval-switcher,.eval-banner,.group-tab,.desig-subtabs,.sum-card,.standing-panel,.person-row,.no-evaluated,.target-card,.eval-card,.comment-section,.avg-summary,.top-bar,.history-card,.section,.content-panel,.no-archived{background:rgba(23,42,69,.85) !important;border-color:rgba(255,255,255,.08) !important;box-shadow:0 8px 32px rgba(0,0,0,.24) !important;color:#E0E6F0 !important}
.eval-tab{color:#A0B3C6 !important}.eval-tab:hover{color:#fff !important;background:rgba(255,255,255,.04) !important}.eval-tab.student.active,.eval-tab.multi-role.active,.eval-tab.peer.active{background:var(--portal-accent-bg) !important;color:var(--portal-accent-h) !important}.eval-tab.student.active::after,.eval-tab.multi-role.active::after,.eval-tab.peer.active::after{background:var(--portal-accent) !important}.tab-badge,.tab-count{background:rgba(255,255,255,.10) !important;color:#E0E6F0 !important}
.group-tab.active-all,.group-tab.active-teacher,.group-tab.active-staff,.desig-subtab.active{background:var(--portal-accent-bg) !important;border-color:rgba(255,255,255,.16) !important;color:var(--portal-accent-h) !important}
.sum-value,.person-name,.section-title,.standing-name,.sheet-name,.target-name{color:#fff !important}.person-link,.back-btn,.btn-archived-link{color:#E0E6F0 !important}.person-row:hover{border-color:rgba(255,255,255,.16) !important}.person-photo-ph,.standing-photo-ph{background:#0F1F3D !important;border-color:rgba(255,255,255,.10) !important;color:#A0B3C6 !important}
.score-bar-bg,.avg-bar-bg,.eval-bar-bg{background:rgba(255,255,255,.08) !important}.btn-print{background:var(--portal-accent) !important}.btn-archived-link{background:rgba(23,42,69,.85) !important;border-color:rgba(255,255,255,.10) !important}.btn-archived-link:hover{color:#fff !important}.back-btn{background:rgba(23,42,69,.85) !important;border-color:rgba(255,255,255,.10) !important}.back-btn:hover{background:var(--portal-accent-bg) !important;color:#fff !important}
input,select,textarea{background:#0F1F3D !important;color:#E0E6F0 !important;border-color:rgba(255,255,255,.12) !important}.comment-text{background:#0F1F3D !important;color:#E0E6F0 !important}.eval-banner-title{color:var(--portal-accent-h) !important}.eval-banner-desc{color:#A0B3C6 !important}
/* FINAL LIGHT THEME OVERRIDE — reports matches Dean dashboard */
html{background:#F8FAFC !important;color-scheme:light !important;}
body{background:#F8FAFC !important;color:#172033 !important;font-family:'Inter',sans-serif !important;}
.main{background:#F8FAFC !important;color:#172033 !important;}
.page-header{background:#FFFFFF !important;border:1px solid #E2E8F0 !important;color:#172033 !important;box-shadow:0 2px 4px rgba(15,23,42,.04),0 6px 16px rgba(15,23,42,.05) !important;}
.page-header h1,.page-title,.section-title,.sheet-name,.target-name,.person-name,.standing-name,.sum-value{color:#172033 !important;}
.page-header p,.page-sub,.standing-desig,.person-meta,.sum-label,.sum-sub,.pstat-lbl,.muted,.no-data,.eval-banner-desc{color:#475569 !important;}
.eval-switcher,.eval-banner,.group-tab,.desig-subtabs,.sum-card,.standing-panel,.person-row,.no-evaluated,.target-card,.eval-card,.comment-section,.avg-summary,.top-bar,.history-card,.section,.content-panel,.no-archived,.table-wrap,.create-panel,.period-card,.stat-card,.sector-card,.gl-card,.amber-card,.green-card,.red-card{
  background:#FFFFFF !important;border-color:#E2E8F0 !important;box-shadow:0 2px 4px rgba(15,23,42,.04),0 6px 16px rgba(15,23,42,.05) !important;color:#172033 !important;
}
.eval-tab{color:#475569 !important;}
.eval-tab:hover{color:#172033 !important;background:#F4F7FB !important;}
.eval-tab.student.active,.eval-tab.multi-role.active,.eval-tab.peer.active{background:#F5F3FF !important;color:#7C5FD9 !important;}
.eval-tab.student.active::after,.eval-tab.multi-role.active::after,.eval-tab.peer.active::after{background:#7C5FD9 !important;}
.tab-badge,.tab-count{background:#F1F5F9 !important;color:#475569 !important;}
.group-tab.active-all,.group-tab.active-teacher,.group-tab.active-staff,.desig-subtab.active{background:#F5F3FF !important;border-color:#E2E8F0 !important;color:#7C5FD9 !important;}
.sum-value,.person-name,.section-title,.standing-name,.sheet-name,.target-name{color:#172033 !important;}
.person-link,.back-btn,.btn-archived-link{color:#334155 !important;}
.person-row:hover{border-color:#CBD5E1 !important;}
.person-photo-ph,.standing-photo-ph{background:#F1F5F9 !important;border-color:#E2E8F0 !important;color:#64748B !important;}
.score-bar-bg,.avg-bar-bg,.eval-bar-bg{background:#E2E8F0 !important;}
.btn-print{background:#7C5FD9 !important;color:#fff !important;}
.btn-archived-link,.back-btn{background:#FFFFFF !important;border-color:#CBD5E1 !important;}
.btn-archived-link:hover,.back-btn:hover{background:#F5F3FF !important;color:#7C5FD9 !important;}
input,select,textarea{background:#FFFFFF !important;color:#172033 !important;border-color:#CBD5E1 !important;}
.comment-text{background:#F8FAFC !important;color:#334155 !important;}
.eval-banner-title{color:#7C5FD9 !important;}
:::-webkit-scrollbar-track{background:#F8FAFC !important;}
:::-webkit-scrollbar-thumb{background:#CBD5E1 !important;border:2px solid #F8FAFC !important;}

@media(max-width:900px){body{display:block !important}.sidebar{position:relative;width:100%;height:auto;min-height:0}.main{padding:24px !important}}
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
<link rel="stylesheet" href="includes/dean_light_theme.css"/>
</head><body>
</div>
<?php render_exec_sidebar('reports', $me, $photo_src); ?>
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

<style id="executive-original-theme">
:root{
 --dark:#0A192F;--mid:#172A45;--inner:#0F1F3D;
 --violet:#7C5FD9;--violet-h:#9C85F0;--violet-dark:#5F45B8;
 --light:#E0E6F0;--muted:#A0B3C6;--border:rgba(255,255,255,.08);
 --accent:#7C5FD9;--accent-h:#9C85F0;--good:#10B981;--danger:#f05454;
 --page-bg:#0A192F;--card-bg:rgba(23,42,69,.85);--card-border:rgba(255,255,255,.08);
}
html{background:var(--dark)!important;color-scheme:dark!important;}
body{background:linear-gradient(rgba(5,18,36,.72),rgba(5,18,36,.82)),url('../background.png') center center/cover no-repeat fixed!important;background-color:var(--dark)!important;color:var(--light)!important;}
.main,.content,.page-content{color:var(--light)!important;}
.page-title,.page-header h1,.section-title,.sheet-name,.target-name{color:#fff!important;}
.page-sub,.sheet-desig,.target-desig,.muted,.hint,.helper,.description,p{color:var(--muted)!important;}
/* Cards/panels */
.card,.panel,.section,.table-card,.content-card,.stat-card,.sum-card,.standing-panel,.eval-card,.eval-banner,.info-banner,.history-card,.gl-card,.amber-card,.green-card,.red-card,.person-row,.target-card,.no-eval,.no-data,.no-evaluated,.comment-section,.avg-summary,.cat-section,.people-list,.evaluator-grid{
 background:rgba(23,42,69,.85)!important;border-color:rgba(255,255,255,.08)!important;box-shadow:0 8px 32px rgba(0,0,0,.45)!important;color:var(--light)!important;
}
.table-wrap{background:transparent!important;color:var(--light)!important;}
table.data th,table.data td,th,td{color:var(--light)!important;border-color:rgba(255,255,255,.08)!important;}
.q-table{color:var(--light)!important;}
.q-table th{color:var(--muted)!important;background:rgba(15,31,61,.65)!important;border-color:rgba(255,255,255,.08)!important;}
.q-table td{color:var(--light)!important;border-color:rgba(255,255,255,.06)!important;}
/* Tabs and filters */
.eval-switcher,.tabs,.level-tabs,.status-tabs{background:rgba(23,42,69,.85)!important;border-color:rgba(255,255,255,.08)!important;box-shadow:none!important;}
.eval-tab,.tab,.level-tab,.status-tab,.group-tab,.desig-subtab{color:var(--muted)!important;background:transparent!important;}
.eval-tab:hover,.tab:hover,.level-tab:hover,.status-tab:hover,.group-tab:hover,.desig-subtab:hover{color:#fff!important;background:rgba(124,95,217,.08)!important;}
.eval-tab.student.active,.group-tab.active,.desig-subtab.active{background:rgba(124,95,217,.14)!important;color:var(--violet-h)!important;border-color:rgba(124,95,217,.35)!important;}
.eval-tab.student.active::after{background:var(--violet)!important;}
.eval-tab.peer.active{background:rgba(124,95,217,.14)!important;color:var(--violet-h)!important;}
.eval-tab.peer.active::after{background:var(--violet)!important;}
.tab-badge{background:rgba(255,255,255,.08)!important;color:var(--muted)!important;}
.eval-tab.student.active .tab-badge,.eval-tab.peer.active .tab-badge{background:rgba(124,95,217,.18)!important;color:var(--violet-h)!important;}
/* Buttons/links */
.btn-print,.btn-print.no-print,.back-btn,.btn,.action-btn,.btn-archive,.btn-restore,.btn-solid,.btn-archived-link{background:rgba(124,95,217,.12)!important;border:1px solid rgba(124,95,217,.35)!important;color:var(--violet-h)!important;}
.btn-print:hover,.back-btn:hover,.btn:hover,.action-btn:hover,.btn-archive:hover,.btn-restore:hover,.btn-solid:hover,.btn-archived-link:hover{background:rgba(124,95,217,.22)!important;color:#fff!important;}
.btn-solid{background:var(--violet)!important;color:#fff!important;}
/* accents */
.eval-banner{background:rgba(124,95,217,.08)!important;border-color:rgba(124,95,217,.25)!important;}
.eval-banner-title,.eval-banner-icon,.cat-title,.comment-title,.section h2 i,.section h2{color:var(--violet-h)!important;}
.period-badge{background:rgba(124,95,217,.14)!important;border-color:rgba(124,95,217,.3)!important;color:var(--violet-h)!important;}
.bar-fill,.avg-bar-fill{background:linear-gradient(90deg,var(--violet-dark),var(--violet-h))!important;}
.score-bar-bg,.eval-bar-bg,.avg-bar-bg,.bar-wrap{background:rgba(255,255,255,.08)!important;}
.standing-title.top,.standing-score,.pstat-val,.avg-score-big,.avg-score-label{color:#4ade80!important;}
.standing-title.low{color:#f87171!important;}
.person-row:hover,.standing-item:hover{border-color:rgba(124,95,217,.4)!important;background:rgba(124,95,217,.05)!important;}
.comment-text{background:rgba(15,31,61,.7)!important;color:var(--light)!important;}
.empty-note,.no-comment,.no-eval{color:var(--muted)!important;}
label,th{color:var(--muted)!important;}
input,select,textarea{background:#0F1F3D!important;color:var(--light)!important;border-color:rgba(255,255,255,.12)!important;}
input::placeholder,textarea::placeholder{color:#7890a8!important;}
</style>
<style id="dean-sheet-light-final">
/* Final light-theme correction for the individual evaluation sheet. */
html{background:#F8FAFC!important;color-scheme:light!important;}
body{background:#F8FAFC!important;color:#0F172A!important;}
.main{background:#F8FAFC!important;color:#0F172A!important;}
.detail-page{background:transparent!important;}
.sheet-header,.scale-bar,.cat-section,.comment-section,.avg-summary{background:#FFFFFF!important;border-color:#CFE0F0!important;box-shadow:0 5px 18px rgba(28,64,92,.05)!important;color:#173956!important;}
.sheet-name,.avg-score-big,.avg-score-label{color:#0F2944!important;}
.sheet-desig,.eval-by-date,.avg-out-of,.no-comment{color:#647B8E!important;}
.eval-by-label{color:#60778D!important;}
.eval-by-name{color:#2B67DE!important;}
.sheet-avatar-ph{background:#F1F5F9!important;border-color:#CFE0F0!important;color:#6C8195!important;}
.eval-type-chip{background:#EFF6FF!important;border-color:#BFD7FF!important;color:#2B67DE!important;}
.scale-bar{align-items:center;}
.scale-bar > span{color:#647B8E!important;}
.scale-item{color:#506A82!important;}
.cat-title,.comment-title{background:#EFF6FF!important;color:#2B67DE!important;border-color:#CFE0F0!important;}
.q-table{background:#FFFFFF!important;border-color:#CFE0F0!important;color:#173956!important;}
.q-table thead tr{background:#F7FAFD!important;}
.q-table th{background:#F7FAFD!important;color:#60778D!important;border-color:#D8E4EE!important;}
.q-table td{background:#FFFFFF!important;color:#173956!important;border-color:#E0E9F1!important;}
.q-table tr:hover td{background:#F7FAFD!important;}
.q-table td.q-num{color:#70869A!important;}
.rating-badge{background:#F8FBFE!important;border-color:#D8E4EE!important;}
.comment-text{background:#F8FAFC!important;color:#334155!important;border-color:#DCE8F2!important;}
.avg-bar-bg{background:#E2E8F0!important;}
.avg-score-big,.avg-score-label{color:#10B981!important;}
.avg-bar-label{color:#526B82!important;}
.detail-actions .back-btn{background:#FFFFFF!important;color:#173956!important;border-color:#BCD0E5!important;}
.detail-actions .btn-print{background:#2D66E1!important;color:#FFFFFF!important;}
@media print{
  body,.main{background:#fff!important;color:#000!important;}
  .sheet-header,.scale-bar,.cat-section,.comment-section,.avg-summary,.q-table{box-shadow:none!important;}
}
</style>

</body></html>
<?php $mysqli->close(); exit; }

// ══════════════════════════════════════════════════════════════
// VIEW: TARGET EVALUATION DETAILS
// Shows the selected person's evaluation summary and each received evaluation.
// ══════════════════════════════════════════════════════════════
if ($view === 'students' && $target_id) {
    $tgt = $mysqli->query("SELECT id,full_name,designation,photo,role FROM users u WHERE u.id=$target_id AND $reportScopeSql LIMIT 1")->fetch_assoc();
    if (!$tgt) { http_response_code(404); exit('Personnel not found in this report scope.'); }

    // Keep the selected year level only for Student Evaluation.
    $selectedYearLevel = trim((string)($_GET['year_level'] ?? ''));
    $availableYearLevels = [];
    if ($activeEval === 'student') {
        $ylq = $mysqli->query("
            SELECT DISTINCT TRIM(COALESCE(s.year_level,'')) AS year_level
            FROM evaluation_tracker et
            JOIN users s ON s.id = et.evaluator_id
            WHERE et.target_user_id = $target_id
              AND $evalTypePlainSql
              AND TRIM(COALESCE(s.year_level,'')) <> ''
            ORDER BY
              CASE TRIM(s.year_level)
                WHEN '1st Year College' THEN 1
                WHEN '2nd Year College' THEN 2
                WHEN '3rd Year College' THEN 3
                WHEN '4th Year College' THEN 4
                ELSE 99
              END,
              TRIM(s.year_level)
        ");
        if ($ylq) {
            while ($yr = $ylq->fetch_assoc()) $availableYearLevels[] = $yr['year_level'];
        }
        if ($selectedYearLevel !== '' && !in_array($selectedYearLevel, $availableYearLevels, true)) {
            $selectedYearLevel = '';
        }
    } else {
        $selectedYearLevel = '';
    }

    $studentYearWhere = '';
    if ($activeEval === 'student' && $selectedYearLevel !== '') {
        $studentYearWhere = " AND TRIM(COALESCE(u.year_level,'')) = '" . $mysqli->real_escape_string($selectedYearLevel) . "'";
    }

    $evaluators = [];
    $eq = $mysqli->query("
        SELECT et.id AS tracker_id,
               et.submitted_at,
               et.remarks,
               u.id AS student_id,
               u.full_name,
               u.photo,
               u.role AS evaluator_role,
               " . ($activeEval === 'student' ? "u.year_level" : "NULL AS year_level") . ",
               (SELECT AVG(qa.answer_score)
                  FROM questionnaire_answers qa
                 WHERE qa.tracker_id=et.id) AS avg_score
          FROM evaluation_tracker et
          JOIN users u ON u.id = et.evaluator_id
         WHERE et.target_user_id = $target_id
           AND $evalTypeSql
           $studentYearWhere
         ORDER BY et.submitted_at DESC
    ");
    if ($eq) $evaluators = $eq->fetch_all(MYSQLI_ASSOC);

    $allScores  = array_filter(array_column($evaluators,'avg_score'), fn($s) => $s !== null);
    $overallAvg = count($allScores) ? round(array_sum($allScores)/count($allScores),2) : null;

    $scaleItems  = [5=>'Always',4=>'Often',3=>'Sometimes',2=>'Rarely',1=>'Never'];
    pageHead('Evaluation Details', $evalColor, $evalColorBg, $evalColorBorder);
    ?>
<style>
.detail-page{max-width:none;}
.detail-actions{display:flex;align-items:center;justify-content:space-between;gap:12px;margin-bottom:16px;}
.detail-back{display:inline-flex;align-items:center;gap:8px;padding:9px 15px;border:1px solid #BCD0E5;border-radius:10px;background:#fff;color:#173956;text-decoration:none;font-size:12px;font-weight:800;}
.detail-back:hover{background:#F4F8FC;border-color:#8FAFCB;}
.detail-print{border:0;border-radius:12px;padding:12px 20px;background:#2D66E1;color:#fff;font:800 12px 'Inter',sans-serif;display:inline-flex;align-items:center;gap:8px;cursor:pointer;box-shadow:0 6px 16px rgba(45,102,225,.18);}
.detail-print:hover{background:#2459C9;}
.detail-eval-banner{display:flex;align-items:center;gap:14px;padding:14px 18px;border:1px solid #CFE0F0;border-radius:14px;background:#EFF6FF;margin-bottom:22px;}
.detail-eval-icon{width:42px;height:42px;border-radius:11px;display:flex;align-items:center;justify-content:center;background:#E0ECFF;border:1px solid #BFD7FF;color:#2B67DE;font-size:18px;flex:0 0 auto;}
.detail-eval-title{font-size:17px;font-weight:800;color:#1558CF;line-height:1.15;}
.detail-eval-sub{margin-top:3px;font-size:12px;color:#5C7590;}
.detail-person-card{display:flex;align-items:center;gap:20px;padding:28px 30px;border:1px solid #CFE0F0;border-radius:16px;background:#fff;box-shadow:0 5px 18px rgba(28,64,92,.05);margin-bottom:30px;flex-wrap:wrap;}
.detail-person-avatar{width:74px;height:74px;border-radius:50%;object-fit:cover;border:2px solid #2B67DE;flex:0 0 auto;}
.detail-person-avatar.ph{display:flex;align-items:center;justify-content:center;background:#F1F5F9;color:#6C8195;font-size:26px;}
.detail-person-meta{min-width:280px;flex:1;}
.detail-person-name{font-family:'Rajdhani',sans-serif;font-size:28px;font-weight:800;line-height:1.05;color:#0F2944;}
.detail-person-desig{margin-top:5px;font-size:13px;color:#5F7890;line-height:1.35;}
.detail-stats{display:flex;align-items:flex-start;gap:42px;margin-left:auto;flex-wrap:wrap;}
.detail-stat{text-align:center;min-width:104px;}
.detail-stat-value{font-family:'Rajdhani',sans-serif;font-size:31px;font-weight:800;line-height:1;color:#2B67DE;}
.detail-stat-value.score{color:#10B981;}
.detail-stat-label{margin-top:5px;font-size:10px;color:#72879A;text-transform:uppercase;letter-spacing:.06em;}
.detail-legend{width:250px;border:1px solid #CFE0F0;border-radius:13px;background:#F8FBFE;padding:15px 17px;}
.detail-legend-title{font-size:13px;font-weight:800;color:#122E4A;margin-bottom:8px;}
.detail-legend-row{display:flex;align-items:center;justify-content:space-between;font-size:11px;line-height:1.45;}
.detail-legend-score{font-weight:800;min-width:82px;}
.detail-legend-text{color:#647B90;}
.detail-results-head{display:flex;align-items:center;justify-content:space-between;gap:18px;margin-bottom:12px;}
.detail-results-title{display:flex;align-items:center;gap:10px;}
.detail-results-title i{color:#2B67DE;font-size:19px;}
.detail-results-title h2{font-family:'Rajdhani',sans-serif;font-size:23px;font-weight:800;color:#122E4A;margin:0;}
.detail-results-title span{font-size:11px;color:#70869A;}
.detail-year-filter{display:flex;align-items:center;gap:10px;}
.detail-year-filter label{font-size:10px;font-weight:800;color:#70869A;text-transform:uppercase;letter-spacing:.07em;}
.detail-year-filter select{min-width:210px;height:42px;padding:0 14px;border:1px solid #BFD2E5;border-radius:10px;background:#fff;color:#102C47;font:700 12px 'Inter',sans-serif;outline:none;}
.detail-results-panel{border:1px solid #CFE0F0;border-radius:16px;background:#fff;box-shadow:0 5px 18px rgba(28,64,92,.04);overflow:hidden;}
.detail-results-table{width:100%;border-collapse:collapse;}
.detail-results-table th{padding:12px 16px;background:#F7FAFD;color:#60778D!important;border-bottom:1px solid #D8E4EE;font-size:10px!important;font-weight:800!important;letter-spacing:.07em;text-align:left;white-space:nowrap;}
.detail-results-table td{padding:17px 16px;border-bottom:1px solid #E0E9F1;color:#173956!important;font-size:13px!important;vertical-align:middle;}
.detail-results-table tbody tr:hover td{background:#FAFCFE!important;}
.detail-results-table tbody tr:last-child td{border-bottom:0;}
.detail-no{width:56px;text-align:center;}
.detail-evaluator{font-weight:700;color:#102C47!important;}
.detail-role{color:#526D84!important;}
.detail-avg{font-size:16px;font-weight:800;color:#10B981;}
.detail-rating{display:inline-flex;align-items:center;padding:5px 11px;border-radius:999px;background:#E8F6F1;color:#10B981;font-size:11px;font-weight:800;}
.detail-date{white-space:nowrap;color:#31516C!important;}
.detail-view-btn{display:inline-flex;align-items:center;justify-content:center;gap:7px;padding:8px 16px;border:1px solid #AFCBFF;border-radius:10px;background:#F1F6FF;color:#2363D4;text-decoration:none;font-size:11px;font-weight:800;}
.detail-view-btn:hover{background:#E7F0FF;}
.detail-empty{text-align:center;padding:54px 22px;color:#70869A;}
.detail-empty i{display:block;font-size:32px;margin-bottom:10px;color:#A4B5C5;}
.detail-empty h3{font-family:'Rajdhani',sans-serif;font-size:19px;color:#163450;margin:0 0 4px;}
.detail-empty p{font-size:12px;color:#71869A;margin:0;}
@media(max-width:1000px){
  .detail-stats{width:100%;margin-left:0;justify-content:flex-start;gap:26px;}
  .detail-legend{margin-left:auto;}
}
@media(max-width:760px){
  .detail-actions{align-items:stretch;flex-direction:column;}
  .detail-print,.detail-back{justify-content:center;}
  .detail-person-card{padding:22px;}
  .detail-person-meta{min-width:220px;}
  .detail-legend{width:100%;margin-left:0;}
  .detail-results-head{align-items:flex-start;flex-direction:column;}
  .detail-year-filter{width:100%;}
  .detail-year-filter select{flex:1;min-width:0;}
  .detail-results-panel{overflow-x:auto;}
  .detail-results-table{min-width:820px;}
}
@media print{
  .no-print,.detail-actions,.detail-year-filter{display:none!important;}
  body{background:#fff!important;color:#000!important;padding:0;}
  .detail-eval-banner,.detail-person-card,.detail-results-panel{box-shadow:none!important;}
  .detail-results-table th,.detail-results-table td{color:#000!important;}
}
</style>
<link rel="stylesheet" href="includes/dean_light_theme.css"/>
</head><body>
<?php render_exec_sidebar('reports', $me, $photo_src); ?>
<main class="main detail-page">

<div class="detail-actions no-print">
    <a href="?group=<?= urlencode($groupFilter) ?>&eval_type=<?= $activeEval ?>" class="detail-back"><i class="fa-solid fa-arrow-left"></i> Back to Analytics</a>
    <button class="detail-print" onclick="window.print()"><i class="fa-solid fa-print"></i> Print / Save PDF</button>
</div>

<div class="detail-eval-banner">
    <div class="detail-eval-icon"><i class="fa-solid <?= $evalIcon ?>"></i></div>
    <div>
        <div class="detail-eval-title"><?= htmlspecialchars($evalLabel) ?></div>
        <div class="detail-eval-sub"><?= $activeEval === 'peer' ? 'Evaluated by fellow teacher and staff personnel.' : 'Evaluated by students using the Student Evaluation questionnaire.' ?></div>
    </div>
</div>

<div class="detail-person-card">
    <?php if ($tgt['photo']): ?><img class="detail-person-avatar" src="../image/<?= htmlspecialchars($tgt['photo']) ?>" alt=""/>
    <?php else: ?><div class="detail-person-avatar ph"><i class="fa-solid fa-user"></i></div><?php endif; ?>
    <div class="detail-person-meta">
        <div class="detail-person-name"><?= htmlspecialchars($tgt['full_name']) ?></div>
        <div class="detail-person-desig">
            · <?= $tgt['role']==='teacher' || $tgt['role']==='faculty' ? 'Faculty' : 'Staff' ?><br>
            <?= $activeEval === 'peer' ? 'Peer-to-Peer Evaluation' : ($selectedYearLevel !== '' ? htmlspecialchars($selectedYearLevel) : 'College Students Only') ?>
        </div>
    </div>
    <div class="detail-stats">
        <div class="detail-stat">
            <div class="detail-stat-value"><?= count($evaluators) ?></div>
            <div class="detail-stat-label">Total Evaluations</div>
        </div>
        <div class="detail-stat">
            <div class="detail-stat-value score"><?= $overallAvg !== null ? number_format($overallAvg,2) . ' / 5.00' : '—' ?></div>
            <div class="detail-stat-label">Overall Average Score</div>
        </div>
    </div>
    <div class="detail-legend">
        <div class="detail-legend-title">Score Legend</div>
        <div class="detail-legend-row"><span class="detail-legend-score" style="color:#10B981">4.50 - 5.00</span><span class="detail-legend-text">Always</span></div>
        <div class="detail-legend-row"><span class="detail-legend-score" style="color:#34C38F">3.50 - 4.49</span><span class="detail-legend-text">Often</span></div>
        <div class="detail-legend-row"><span class="detail-legend-score" style="color:#EAB308">2.50 - 3.49</span><span class="detail-legend-text">Sometimes</span></div>
        <div class="detail-legend-row"><span class="detail-legend-score" style="color:#F97316">1.50 - 2.49</span><span class="detail-legend-text">Rarely</span></div>
        <div class="detail-legend-row"><span class="detail-legend-score" style="color:#EF4444">1.00 - 1.49</span><span class="detail-legend-text">Never</span></div>
    </div>
</div>

<div class="detail-results-head">
    <div class="detail-results-title">
        <i class="fa-solid fa-list-check"></i>
        <h2>Evaluation Results</h2>
        <span>(<?= count($evaluators) ?>)</span>
    </div>
    <?php if ($activeEval === 'student' && !empty($availableYearLevels)): ?>
    <form method="get" class="detail-year-filter no-print">
        <input type="hidden" name="view" value="students"/>
        <input type="hidden" name="target_id" value="<?= $target_id ?>"/>
        <input type="hidden" name="group" value="<?= htmlspecialchars($groupFilter) ?>"/>
        <input type="hidden" name="eval_type" value="<?= htmlspecialchars($activeEval) ?>"/>
        <label for="detailYearLevel">College Year Level</label>
        <select id="detailYearLevel" name="year_level" onchange="this.form.submit()">
            <option value="">All College Year Levels</option>
            <?php foreach ($availableYearLevels as $yl): ?>
                <option value="<?= htmlspecialchars($yl) ?>" <?= $selectedYearLevel===$yl?'selected':'' ?>><?= htmlspecialchars($yl) ?></option>
            <?php endforeach; ?>
        </select>
    </form>
    <?php endif; ?>
</div>

<div class="detail-results-panel">
<?php if (empty($evaluators)): ?>
    <div class="detail-empty">
        <i class="fa-solid fa-clipboard-question"></i>
        <h3>No Evaluation Results</h3>
        <p>No <?= $evaluatorNounP ?> have evaluated this person for the selected report scope.</p>
    </div>
<?php else: ?>
    <div style="overflow-x:auto;">
    <table class="detail-results-table">
        <thead><tr>
            <th class="detail-no">NO.</th>
            <th>EVALUATOR</th>
            <th>ROLE</th>
            <th>AVERAGE SCORE</th>
            <th>RATING</th>
            <th>DATE EVALUATED</th>
            <th>ACTIONS</th>
        </tr></thead>
        <tbody>
        <?php foreach ($evaluators as $i => $ev):
            $sc = $ev['avg_score'] !== null ? round((float)$ev['avg_score'],2) : null;
            $roleLabel = $activeEval === 'peer'
                ? (($ev['evaluator_role'] === 'teacher' || $ev['evaluator_role'] === 'faculty') ? 'Faculty / Peer' : 'Staff / Peer')
                : 'Student';
            $dateEval = !empty($ev['submitted_at']) ? date('M d, Y', strtotime($ev['submitted_at'])) : '—';
        ?>
        <tr>
            <td class="detail-no"><?= $i + 1 ?></td>
            <td class="detail-evaluator"><?= htmlspecialchars($ev['full_name']) ?></td>
            <td class="detail-role"><?= htmlspecialchars($roleLabel) ?></td>
            <td><span class="detail-avg" style="color:<?= scoreColor($sc) ?>"><?= $sc !== null ? number_format($sc,2) : '—' ?></span></td>
            <td><span class="detail-rating" style="color:<?= scoreColor($sc) ?>;background:<?= scoreColor($sc) ?>18;"><?= htmlspecialchars(scoreLabel($sc)) ?></span></td>
            <td class="detail-date"><?= htmlspecialchars($dateEval) ?></td>
            <td>
                <a class="detail-view-btn" href="?view=sheet&target_id=<?= $target_id ?>&student_id=<?= $ev['student_id'] ?>&tracker_id=<?= $ev['tracker_id'] ?>&group=<?= urlencode($groupFilter) ?>&eval_type=<?= $activeEval ?>">
                    <i class="fa-solid fa-eye"></i> View
                </a>
            </td>
        </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    </div>
<?php endif; ?>
</div>

</main>
</body></html>
<?php $mysqli->close(); exit; }

// ══════════════════════════════════════════════════════════════
// VIEW: ARCHIVED PERSONNEL
// ══════════════════════════════════════════════════════════════
if ($view === 'archived') {
    $whereRoleArc = match ($groupFilter) {
        'Teacher' => "u.role='teacher'",
        'Staff' => "u.role='staff'",
        'MultiRoleTeacher','MultiRoleStaff','MultiRole' => "u.role IN ('teacher','staff','faculty')",
        default => "u.role IN ('teacher','staff','faculty')"
    };
    $archived = [];
    $res = $mysqli->query("
        SELECT u.id,u.full_name,u.designation,u.photo,u.role,u.secondary_role,u.source,u.account_status,aa.archived_at,
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
    if ($isMultiRole) {
        $archived = array_values(array_filter($archived, function(array $person) use ($groupFilter) {
            if (!ec_has_additional_role($person)) return false;
            if ($groupFilter === 'MultiRoleTeacher') {
                $base = strtolower(trim((string)$person['role']));
                return $base === 'teacher' || $base === 'faculty' || strtolower(trim((string)$person['secondary_role'])) === 'teacher';
            }
            if ($groupFilter === 'MultiRoleStaff') {
                $base = strtolower(trim((string)$person['role']));
                return !($base === 'teacher' || $base === 'faculty' || strtolower(trim((string)$person['secondary_role'])) === 'teacher');
            }
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
<?php render_exec_sidebar('reports', $me, $photo_src); ?>
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
                <span><?= $p['role']==='teacher'?'Teacher':'Staff' ?> · <?= htmlspecialchars($p['designation']) ?></span>
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

<style id="executive-original-theme">
:root{
 --dark:#0A192F;--mid:#172A45;--inner:#0F1F3D;
 --violet:#7C5FD9;--violet-h:#9C85F0;--violet-dark:#5F45B8;
 --light:#E0E6F0;--muted:#A0B3C6;--border:rgba(255,255,255,.08);
 --accent:#7C5FD9;--accent-h:#9C85F0;--good:#10B981;--danger:#f05454;
 --page-bg:#0A192F;--card-bg:rgba(23,42,69,.85);--card-border:rgba(255,255,255,.08);
}
html{background:var(--dark)!important;color-scheme:dark!important;}
body{background:linear-gradient(rgba(5,18,36,.72),rgba(5,18,36,.82)),url('../background.png') center center/cover no-repeat fixed!important;background-color:var(--dark)!important;color:var(--light)!important;}
.main,.content,.page-content{color:var(--light)!important;}
.page-title,.page-header h1,.section-title,.sheet-name,.target-name{color:#fff!important;}
.page-sub,.sheet-desig,.target-desig,.muted,.hint,.helper,.description,p{color:var(--muted)!important;}
/* Cards/panels */
.card,.panel,.section,.table-card,.content-card,.stat-card,.sum-card,.standing-panel,.eval-card,.eval-banner,.info-banner,.history-card,.gl-card,.amber-card,.green-card,.red-card,.person-row,.target-card,.no-eval,.no-data,.no-evaluated,.comment-section,.avg-summary,.cat-section,.people-list,.evaluator-grid{
 background:rgba(23,42,69,.85)!important;border-color:rgba(255,255,255,.08)!important;box-shadow:0 8px 32px rgba(0,0,0,.45)!important;color:var(--light)!important;
}
.table-wrap{background:transparent!important;color:var(--light)!important;}
table.data th,table.data td,th,td{color:var(--light)!important;border-color:rgba(255,255,255,.08)!important;}
.q-table{color:var(--light)!important;}
.q-table th{color:var(--muted)!important;background:rgba(15,31,61,.65)!important;border-color:rgba(255,255,255,.08)!important;}
.q-table td{color:var(--light)!important;border-color:rgba(255,255,255,.06)!important;}
/* Tabs and filters */
.eval-switcher,.tabs,.level-tabs,.status-tabs{background:rgba(23,42,69,.85)!important;border-color:rgba(255,255,255,.08)!important;box-shadow:none!important;}
.eval-tab,.tab,.level-tab,.status-tab,.group-tab,.desig-subtab{color:var(--muted)!important;background:transparent!important;}
.eval-tab:hover,.tab:hover,.level-tab:hover,.status-tab:hover,.group-tab:hover,.desig-subtab:hover{color:#fff!important;background:rgba(124,95,217,.08)!important;}
.eval-tab.student.active,.group-tab.active,.desig-subtab.active{background:rgba(124,95,217,.14)!important;color:var(--violet-h)!important;border-color:rgba(124,95,217,.35)!important;}
.eval-tab.student.active::after{background:var(--violet)!important;}
.eval-tab.peer.active{background:rgba(124,95,217,.14)!important;color:var(--violet-h)!important;}
.eval-tab.peer.active::after{background:var(--violet)!important;}
.tab-badge{background:rgba(255,255,255,.08)!important;color:var(--muted)!important;}
.eval-tab.student.active .tab-badge,.eval-tab.peer.active .tab-badge{background:rgba(124,95,217,.18)!important;color:var(--violet-h)!important;}
/* Buttons/links */
.btn-print,.btn-print.no-print,.back-btn,.btn,.action-btn,.btn-archive,.btn-restore,.btn-solid,.btn-archived-link{background:rgba(124,95,217,.12)!important;border:1px solid rgba(124,95,217,.35)!important;color:var(--violet-h)!important;}
.btn-print:hover,.back-btn:hover,.btn:hover,.action-btn:hover,.btn-archive:hover,.btn-restore:hover,.btn-solid:hover,.btn-archived-link:hover{background:rgba(124,95,217,.22)!important;color:#fff!important;}
.btn-solid{background:var(--violet)!important;color:#fff!important;}
/* accents */
.eval-banner{background:rgba(124,95,217,.08)!important;border-color:rgba(124,95,217,.25)!important;}
.eval-banner-title,.eval-banner-icon,.cat-title,.comment-title,.section h2 i,.section h2{color:var(--violet-h)!important;}
.period-badge{background:rgba(124,95,217,.14)!important;border-color:rgba(124,95,217,.3)!important;color:var(--violet-h)!important;}
.bar-fill,.avg-bar-fill{background:linear-gradient(90deg,var(--violet-dark),var(--violet-h))!important;}
.score-bar-bg,.eval-bar-bg,.avg-bar-bg,.bar-wrap{background:rgba(255,255,255,.08)!important;}
.standing-title.top,.standing-score,.pstat-val,.avg-score-big,.avg-score-label{color:#4ade80!important;}
.standing-title.low{color:#f87171!important;}
.person-row:hover,.standing-item:hover{border-color:rgba(124,95,217,.4)!important;background:rgba(124,95,217,.05)!important;}
.comment-text{background:rgba(15,31,61,.7)!important;color:var(--light)!important;}
.empty-note,.no-comment,.no-eval{color:var(--muted)!important;}
label,th{color:var(--muted)!important;}
input,select,textarea{background:#0F1F3D!important;color:var(--light)!important;border-color:rgba(255,255,255,.12)!important;}
input::placeholder,textarea::placeholder{color:#7890a8!important;}
</style>

</body></html>
<?php exit; }

// ══════════════════════════════════════════════════════════════
// VIEW: MAIN LIST
// ══════════════════════════════════════════════════════════════
$whereRole = match ($activeEval) {
    // The Student Evaluation report must use the person's actual personnel
    // classification, not the tracker row's default evaluation_context.
    // A Staff account can be a Teaching Staff member and therefore belongs
    // under Faculty; a true Non-Teaching Staff account belongs under Staff.
    'student' => "u.role IN ('teacher','staff','faculty','dean')",
    'peer'    => "u.role IN ('teacher','staff','faculty','dean','principal')",
    default   => "u.role IN ('teacher','staff')"
};

$people = [];
$res = $mysqli->query(" 
    SELECT u.id, u.full_name, u.designation, u.photo, u.role, u.secondary_role, u.source, u.account_status,
           aa.archived_at,
           COUNT(DISTINCT et.id) AS total_responses,
           MAX(et.submitted_at) AS last_evaluated,
           AVG(qa.answer_score)  AS avg_score
    FROM users u
    JOIN evaluation_tracker et ON et.target_user_id=u.id AND $evalTypeSql
    LEFT JOIN questionnaire_answers qa ON qa.tracker_id=et.id
    LEFT JOIN analytics_archive aa ON aa.target_user_id=u.id
    WHERE $whereRole AND u.is_active=1 AND $reportScopeSql
      AND (aa.id IS NULL OR " . ($isMultiRole ? '1=1' : '0=1') . ")
    GROUP BY u.id
    ORDER BY avg_score DESC, u.full_name ASC
");
if ($res) $people = $res->fetch_all(MYSQLI_ASSOC);

// ── Canonical Student Evaluation grouping ────────────────────────────────
// IMPORTANT: do NOT infer the Faculty/Staff tab from evaluation_tracker's
// eval_bucket/evaluation_context. Legacy Student Evaluation tracker rows can
// carry the default "teacher" context even when the target is a true
// Non-Teaching Staff member. The report tabs instead use the same personnel
// classification rule as the questionnaire roster:
//   • Faculty  = Teacher/Faculty personnel OR Teaching Staff (staff account
//                with teaching function/assignment/year-level scope)
//   • Staff    = Non-Teaching Staff only (staff account with no teaching
//                function/assignment/year-level scope)
// This also means a teaching Staff account such as Amelia appears only under
// Faculty, while genuine Non-Teaching Staff accounts appear only under Staff.
if ($activeEval === 'student') {
    foreach ($people as &$person) {
        $person['student_resolved_group'] = ec_resolve_student_group_full($person, $mysqli) ?? '';
    }
    unset($person);

    if (!$isMultiRole) {
        $people = array_values(array_filter($people, function (array $p) use ($groupFilter) {
            $resolved = strtolower(trim((string)($p['student_resolved_group'] ?? '')));
            $rawRole  = strtolower(trim((string)($p['role'] ?? '')));
            if ($groupFilter === 'Faculty') return $resolved === 'teacher';
            if ($groupFilter === 'Staff') return $resolved === 'staff';
            if ($groupFilter === 'Dean') return $rawRole === 'dean' && $resolved === 'school_head';
            return true;
        }));
    }
}

$top4 = array_slice($people, 0, 4);
$low4 = array_slice(array_reverse($people), 0, 4);

$totalResponses = array_sum(array_column($people,'total_responses'));
$scores         = array_filter(array_column($people,'avg_score'), fn($s) => $s !== null);
$overallAvg     = count($scores) ? round(array_sum($scores)/count($scores),2) : null;

$totalStudents = $mysqli->query("SELECT COUNT(*) AS c
    FROM users us
    WHERE us.role='student'
      AND us.is_active=1
      AND (us.education_level='higher_ed' OR us.year_level IN ('1st Year College','2nd Year College','3rd Year College','4th Year College'))")->fetch_assoc()['c'] ?? 0;
$facCount = 0;
$staffCount = 0;
$studentDeanCount = 0;
$totalFacStaff = 0;

// Match the EA Student Evaluation roster counts for the Higher Education Dean:
// Faculty, non-teaching Staff, and Dean. College Student Evaluation does not
// include Principal targets in the Dean portal.
$studentRosterQ = $mysqli->query("SELECT id, full_name, designation, photo, role, secondary_role, sector, source
    FROM users u
    WHERE u.is_active=1
      AND u.account_status='approved'
      AND u.role IN ('teacher','faculty','staff','dean')
      AND $reportScopeSql");
if ($studentRosterQ) {
    while ($rosterUser = $studentRosterQ->fetch_assoc()) {
        $g = ec_resolve_student_group_full($rosterUser, $mysqli);
        if ($g === 'teacher') {
            $facCount++;
        } elseif ($g === 'staff') {
            $staffCount++;
        } elseif ($g === 'school_head') {
            $role = strtolower(trim((string)($rosterUser['role'] ?? '')));
            if ($role === 'dean') $studentDeanCount++;
        }
    }
    $studentRosterQ->free();
}
$totalFacStaff = $facCount + $staffCount + $studentDeanCount;

$multiRoleFacCount = 0;
$multiRoleStaffCount = 0;
$mrUsers = $mysqli->query("SELECT id, role, secondary_role, designation, source, account_status, is_active
    FROM users u
    WHERE role IN ('teacher','staff','faculty')
      AND is_active=1
      AND (account_status='approved' OR source='admin_nologin')
      AND $reportScopeSql");
if ($mrUsers) {
    while ($u = $mrUsers->fetch_assoc()) {
        if (!ec_has_additional_role($u)) continue;
        $baseRole = strtolower(trim((string)$u['role']));
        if ($baseRole === 'teacher' || $baseRole === 'faculty' || strtolower(trim((string)$u['secondary_role'])) === 'teacher') {
            $multiRoleFacCount++;
        } else {
            $multiRoleStaffCount++;
        }
    }
    $mrUsers->free();
}
$multiRoleTotal = $multiRoleFacCount + $multiRoleStaffCount;
if ($isMultiRole) {
    $facCount = $multiRoleFacCount;
    $staffCount = $multiRoleStaffCount;
    $totalFacStaff = $multiRoleTotal;
} elseif ($groupFilter === 'Teacher' || $groupFilter === 'Staff') {
    $totalFacStaff = $groupFilter === 'Teacher' ? $facCount : $staffCount;
}

$archivedCount = $mysqli->query("SELECT COUNT(*) as c FROM analytics_archive aa JOIN users u ON u.id=aa.target_user_id WHERE $reportScopeSql")->fetch_assoc()['c'] ?? 0;
$studentEvalCount = $mysqli->query("SELECT COUNT(DISTINCT et.id) as c
    FROM evaluation_tracker et
    JOIN users u ON u.id=et.target_user_id
    WHERE et.eval_type='student'
      AND $collegeStudentEvaluatorSql
      AND COALESCE(et.evaluation_context,'teacher') IN ('teacher','staff')
      AND $reportScopeSql")->fetch_assoc()['c'] ?? 0;
$multiRoleEvalCount = $mysqli->query("SELECT COUNT(DISTINCT et.id) AS c
    FROM evaluation_tracker et JOIN users u ON u.id=et.target_user_id
    WHERE et.period_id=$period_id_int
      AND et.eval_type='student'
      AND $collegeStudentEvaluatorSql
      AND (et.evaluation_context='multi_role' OR EXISTS (
        SELECT 1 FROM questionnaire_answers qam JOIN user_questions uqm ON uqm.id=qam.user_question_id
        WHERE qam.tracker_id=et.id AND uqm.target_type='Multi-Role' AND uqm.eval_type='student'
    )) AND $reportScopeSql")->fetch_assoc()['c'] ?? 0;
$peerEvalCount = $mysqli->query("SELECT COUNT(DISTINCT et.id) as c FROM evaluation_tracker et JOIN users u ON u.id=et.target_user_id WHERE et.period_id=$period_id_int
      AND et.eval_type IN ('peer','faculty_peer','staff_peer') AND $reportScopeSql")->fetch_assoc()['c'] ?? 0;

$staffDesigCounts = [];
$sdq = $mysqli->query("SELECT designation, COUNT(*) as c FROM users u WHERE u.role='staff' AND u.is_active=1 AND $reportScopeSql GROUP BY designation ORDER BY designation");
if ($sdq) while ($r = $sdq->fetch_assoc()) $staffDesigCounts[$r['designation']] = $r['c'];

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
.group-tab.active-staff{background:rgba(217,119,6,.2);border-color:rgba(217,119,6,.5);color:#F59E0B;}.group-tab.active-faculty,.group-tab.active-multi-role{background:rgba(124,95,217,.14);border-color:rgba(124,95,217,.35);color:var(--violet-h);}
.group-tab.faculty-tab > i,.group-tab.multi-role-tab > i{color:var(--violet-h);}
.faculty-subtabs{display:flex;align-items:center;gap:6px;flex-wrap:wrap;margin:-6px 0 20px;padding:8px 10px;background:rgba(23,42,69,.55);border:1px solid var(--border);border-radius:12px;width:max-content;max-width:100%;}
.subtabs-label{font-size:11px;color:var(--muted);font-weight:700;text-transform:uppercase;letter-spacing:.7px;margin:0 4px 0 2px;}
.faculty-subtab{display:flex;align-items:center;gap:7px;padding:8px 16px;border-radius:10px;border:1px solid transparent;color:var(--muted);text-decoration:none;font-size:13px;font-weight:700;transition:all .2s;}
.faculty-subtab:hover{color:#fff;background:rgba(124,95,217,.08);}
.faculty-subtab.active{background:rgba(124,95,217,.14);border-color:rgba(124,95,217,.35);color:var(--violet-h);}
.faculty-subtab i{color:var(--violet-h);}

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
</style>
</head><body>
<?php render_exec_sidebar('reports', $me, $photo_src); ?>
<main class="main">


<?php if ($toast): ?><div class="toast"><i class="fa-solid fa-circle-check"></i><?= htmlspecialchars($toast) ?></div><?php endif; ?>

<!-- ── REPORTS REDESIGN ── -->
<div class="reports-redesign">

    <!-- Top-level report types: keep the current two data sets only. -->
    <div class="reports-top-switcher">
        <a href="?group=<?= urlencode($groupFilter) ?>&eval_type=student" class="reports-top-tab student <?= $activeEval==='student'?'active':'' ?>">
            <i class="fa-solid fa-graduation-cap"></i>
            <span>Student Evaluation</span>
            <span class="reports-top-badge"><?= $studentEvalCount + $multiRoleEvalCount ?></span>
        </a>
        <div class="reports-top-divider"></div>
        <a href="?group=<?= urlencode($groupForOtherTabs) ?>&eval_type=peer" class="reports-top-tab peer <?= $activeEval==='peer'?'active':'' ?>">
            <i class="fa-solid fa-people-arrows"></i>
            <span>Peer-to-Peer</span>
            <span class="reports-top-badge"><?= $peerEvalCount ?></span>
        </a>
    </div>

    <div class="reports-toolbar">
        <div class="reports-toolbar-spacer" aria-hidden="true"></div>
        <div class="reports-toolbar-actions">
            <a href="?view=archived&group=<?= urlencode($groupFilter) ?>&eval_type=<?= $activeEval ?>" class="reports-archive-link">
                <i class="fa-solid fa-box-archive"></i> Archived<?php if ($archivedCount > 0): ?><span class="reports-archive-count"><?= $archivedCount ?></span><?php endif; ?>
            </a>
            <button class="reports-print-btn" onclick="window.print()"><i class="fa-solid fa-print"></i> Print / Save PDF</button>
        </div>
    </div>

    <?php if ($activeEval === 'student'): ?>
    <div class="reports-filter-tabs">
        <a href="?group=All&eval_type=student" class="reports-filter-tab <?= $groupFilter==='All'?'active':'' ?>">
            <i class="fa-solid fa-users"></i><span>All</span><b><?= $totalFacStaff ?></b>
        </a>
        <a href="?group=Faculty&eval_type=student" class="reports-filter-tab <?= $groupFilter==='Faculty'?'active':'' ?>">
            <i class="fa-solid fa-chalkboard-user"></i><span>Faculty</span><b><?= $facCount ?></b>
        </a>
        <a href="?group=Staff&eval_type=student" class="reports-filter-tab <?= $groupFilter==='Staff'?'active':'' ?>">
            <i class="fa-solid fa-briefcase"></i><span>Staff</span><b><?= $staffCount ?></b>
        </a>
    </div>
    <?php else: ?>
    <div class="reports-filter-tabs">
        <a href="?group=All&eval_type=peer" class="reports-filter-tab <?= $groupFilter==='All'?'active':'' ?>">
            <i class="fa-solid fa-users"></i><span>All</span><b><?= $facCount + $staffCount ?></b>
        </a>
        <a href="?group=Teacher&eval_type=peer" class="reports-filter-tab <?= $groupFilter==='Teacher'?'active':'' ?>">
            <i class="fa-solid fa-chalkboard-user"></i><span>Teacher</span><b><?= $facCount ?></b>
        </a>
        <a href="?group=Staff&eval_type=peer" class="reports-filter-tab <?= $groupFilter==='Staff'?'active':'' ?>">
            <i class="fa-solid fa-briefcase"></i><span>Staff</span><b><?= $staffCount ?></b>
        </a>
    </div>
    <div class="reports-peer-note"><i class="fa-solid fa-circle-info"></i><span><strong>Peer-to-Peer Evaluations</strong> — results submitted by fellow teacher and staff personnel.</span></div>
    <?php endif; ?>

    <!-- Main data panel styled after the supplied reference layout. -->
    <section class="reports-data-panel">
        <div class="reports-data-head">
            <div class="reports-data-title">
                <i class="fa-solid fa-list-check"></i>
                <h2>Users Being Evaluated</h2>
                <span>(<?= count($people) ?> evaluated)</span>
                <span class="reports-live-status" id="reportsLiveStatus"><span class="reports-live-dot"></span> Live</span>
            </div>
            <div class="reports-data-actions">
                <button type="button" class="reports-action-btn reports-export-btn" onclick="exportReportsTable()"><i class="fa-solid fa-download"></i> Export</button>
                <div class="reports-search-box">
                    <i class="fa-solid fa-magnifying-glass"></i>
                    <input id="reportsSearch" type="search" placeholder="Search name, designation..." autocomplete="off" aria-label="Search evaluated users">
                </div>
                <button type="button" class="reports-action-btn reports-filters-btn" onclick="toggleReportsFilters()"><i class="fa-solid fa-filter"></i> Filters</button>
            </div>
        </div>

        <div class="reports-active-filter-note" id="reportsFilterNote" hidden>
            <span>Use the tabs above to narrow the current report.</span>
            <button type="button" onclick="clearReportsSearch()"><i class="fa-solid fa-xmark"></i> Clear search</button>
        </div>

        <?php if (empty($people)): ?>
        <div class="reports-empty-state">
            <i class="fa-solid fa-hourglass-half"></i>
            <h3>No <?= $activeEval==='peer'?'peer-to-peer':'student' ?> evaluations yet</h3>
            <p>Personnel will appear here once <?= $evaluatorNounP ?> have evaluated them.</p>
        </div>
        <?php else: ?>
        <div class="reports-table-wrap">
            <table class="reports-table" id="reportsTable">
                <thead>
                    <tr>
                        <th class="col-no">NO.</th>
                        <th>NAME</th>
                        <th>DESIGNATION</th>
                        <th>ROLE</th>
                        <th>OVERALL AVG. SCORE</th>
                        <th>LAST EVALUATED</th>
                        <th class="col-actions">ACTIONS</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($people as $i => $p):
                    $avg = $p['avg_score'] !== null ? round((float)$p['avg_score'], 2) : null;
                    $rawRole = strtolower(trim((string)($p['role'] ?? '')));
                    $isArchived = !empty($p['archived_at']);
                    if ($activeEval === 'student') {
                        $resolvedGroup = strtolower(trim((string)($p['student_resolved_group'] ?? '')));
                        if ($resolvedGroup === 'teacher') {
                            $roleLabel = 'Faculty';
                        } elseif ($resolvedGroup === 'staff') {
                            $roleLabel = 'Staff';
                        } elseif ($resolvedGroup === 'school_head' && $rawRole === 'principal') {
                            $roleLabel = 'Principal';
                        } elseif ($resolvedGroup === 'school_head' && $rawRole === 'dean') {
                            $roleLabel = 'Dean';
                        } else {
                            $roleLabel = ucfirst($rawRole);
                        }
                    } else {
                        $roleLabel = $isMultiRole ? 'Multi-Role' : (in_array($rawRole, ['teacher','faculty'], true) ? 'Faculty' : 'Staff');
                    }
                    $isFac = $roleLabel === 'Faculty';
                    $lastEval = !empty($p['last_evaluated']) ? date('M d, Y', strtotime($p['last_evaluated'])) : '—';
                    $searchText = trim(($p['full_name'] ?? '') . ' ' . ($p['designation'] ?? '') . ' ' . $roleLabel);
                ?>
                    <tr class="reports-person-row" data-search="<?= htmlspecialchars(strtolower($searchText)) ?>" data-desig="<?= htmlspecialchars($p['designation']) ?>">
                        <td class="col-no"><?= $i + 1 ?></td>
                        <td>
                            <a class="reports-person-link" href="?view=students&target_id=<?= $p['id'] ?>&group=<?= urlencode($groupFilter) ?>&eval_type=<?= $activeEval ?>">
                                <?php if($p['photo']): ?><img class="reports-person-photo" src="../image/<?= htmlspecialchars($p['photo']) ?>" alt=""/><?php else: ?><span class="reports-person-photo placeholder"><i class="fa-solid fa-user"></i></span><?php endif; ?>
                                <span class="reports-person-name"><?= htmlspecialchars($p['full_name']) ?></span>
                            </a>
                        </td>
                        <td class="reports-designation-cell"><?= !empty($p['designation']) ? htmlspecialchars($p['designation']) : '—' ?></td>
                        <td><span class="reports-role-badge <?= $isFac ? 'faculty' : (in_array($roleLabel, ['Dean','Principal'], true) ? strtolower($roleLabel) : 'staff') ?>"><i class="fa-solid <?= $roleLabel === 'Dean' ? 'fa-graduation-cap' : ($roleLabel === 'Principal' ? 'fa-user-tie' : ($isFac ? 'fa-chalkboard-user' : 'fa-briefcase')) ?>"></i><?= htmlspecialchars($roleLabel) ?></span></td>
                        <td><span class="reports-score <?= $avg !== null && $avg >= 4.5 ? 'high' : ($avg !== null && $avg >= 3.5 ? 'mid' : 'low') ?>"><?= $avg !== null ? number_format($avg,2) : '—' ?></span></td>
                        <td class="reports-date"><?= htmlspecialchars($lastEval) ?></td>
                        <td class="col-actions">
                            <div class="reports-row-actions">
                                <a class="reports-view-btn" href="?view=students&target_id=<?= $p['id'] ?>&group=<?= urlencode($groupFilter) ?>&eval_type=<?= $activeEval ?>"><i class="fa-solid fa-eye"></i> View</a>
                                <?php if ($isArchived && $isMultiRole): ?>
                                    <a class="reports-archive-btn" href="?restore_id=<?= $p['id'] ?>&group=<?= urlencode($groupFilter) ?>&eval_type=<?= $activeEval ?>&view=archived"><i class="fa-solid fa-rotate-left"></i> Restore</a>
                                <?php else: ?>
                                    <button type="button" class="reports-archive-btn" onclick="archivePerson(<?= $p['id'] ?>,'<?= htmlspecialchars(addslashes($p['full_name'])) ?>')"><i class="fa-solid fa-box-archive"></i> Archive</button>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <div class="reports-no-search" id="reportsNoSearch" hidden>
            <i class="fa-solid fa-magnifying-glass"></i> No evaluated users match your search.
        </div>
        <?php endif; ?>
    </section>
</div>

<style>
/* ── Reference-style Reports layout: visual-only redesign, current data preserved ── */
.reports-redesign{display:flex;flex-direction:column;gap:0;margin-top:-2px;}
.reports-top-switcher{display:flex;align-items:center;justify-content:flex-start;gap:9px;width:max-content;max-width:100%;margin:0 0 0 0;border:0;border-radius:0;background:transparent;overflow:visible;box-shadow:none;}
.reports-top-tab{display:inline-flex;flex:0 0 auto;min-width:0;align-items:center;justify-content:center;gap:9px;min-height:47px;padding:0 22px;border:1px solid #CFE0F0;border-radius:13px;color:#56708A;text-decoration:none;font-size:14px;font-weight:700;letter-spacing:.01em;transition:.18s;background:#fff;position:relative;white-space:nowrap;box-shadow:0 1px 3px rgba(27,67,106,.03);}
.reports-top-tab.student{color:#2764D4;}
.reports-top-tab i{font-size:14px;}
.reports-top-tab.active{background:#E9F2FF;color:#2161CF;border-color:#7CB0FF;box-shadow:0 2px 7px rgba(45,102,225,.08);}
.reports-top-tab.peer.active{background:#EEE9FF;color:#6547BE;border-color:#D4C7FF;box-shadow:0 2px 7px rgba(101,71,190,.08);}
.reports-top-tab:hover{background:#F7FAFD;color:#274B6C;}
.reports-top-badge{min-width:23px;padding:4px 7px;border-radius:999px;background:#ECF2F7;color:#58718B;font-size:10px;text-align:center;line-height:1;}
.reports-top-tab.student.active .reports-top-badge{background:#D4E5FF;color:#2161CF;}
.reports-top-tab.peer.active .reports-top-badge{background:#E6DFFF;color:#6547BE;}
.reports-top-divider{display:none;}
.reports-toolbar{display:flex;justify-content:space-between;align-items:flex-end;gap:20px;padding:34px 0 22px;min-height:102px;}
.reports-toolbar-spacer{flex:1;min-height:1px;}
.reports-toolbar-actions{display:flex;align-items:center;gap:10px;}
.reports-print-btn{border:0;border-radius:13px;padding:12px 20px;background:#2D66E1;color:#fff;font:700 13px 'Inter',sans-serif;display:inline-flex;align-items:center;gap:8px;cursor:pointer;box-shadow:0 6px 16px rgba(45,102,225,.18);}
.reports-print-btn:hover{background:#2459C9;}
.reports-archive-link{display:inline-flex;align-items:center;gap:7px;padding:11px 15px;border:1px solid #BCD0E5;border-radius:11px;background:#fff;color:#38546E;text-decoration:none;font-size:12px;font-weight:700;}
.reports-archive-link:hover{background:#F6F9FC;border-color:#8FAFCB;color:#173957;}
.reports-archive-count{padding:2px 7px;border-radius:999px;background:#EDF2F7;color:#64748B;font-size:10px;}
.reports-filter-tabs{display:flex;gap:9px;flex-wrap:wrap;margin-bottom:12px;}
.reports-filter-tab{display:inline-flex;align-items:center;gap:9px;min-height:47px;padding:0 22px;border:1px solid #CFE0F0;border-radius:13px;background:#fff;color:#56708A;text-decoration:none;font-size:14px;font-weight:700;box-shadow:0 1px 3px rgba(27,67,106,.03);}
.reports-filter-tab:hover{background:#F7FAFD;color:#274B6C;}
.reports-filter-tab.active{background:#E9F2FF;border-color:#7CB0FF;color:#2161CF;box-shadow:0 2px 7px rgba(45,102,225,.08);}
.reports-filter-tab i{color:inherit;}
.reports-filter-tab b{font-size:10px;min-width:23px;text-align:center;padding:4px 6px;border-radius:999px;background:#ECF2F7;color:#58718B;}
.reports-filter-tab.active b{background:#D4E5FF;color:#2161CF;}
.reports-sub-filters,.reports-designation-filters{display:flex;align-items:center;gap:7px;flex-wrap:wrap;margin-bottom:14px;padding:10px 12px;border:1px solid #D6E4F0;border-radius:12px;background:#F8FBFE;}
.reports-sub-label{font-size:10px;font-weight:800;letter-spacing:.08em;color:#71859A;margin-right:2px;}
.reports-sub-tab,.reports-designation-pill{display:inline-flex;align-items:center;gap:6px;padding:7px 12px;border:1px solid transparent;border-radius:9px;color:#617A91;text-decoration:none;font-size:12px;font-weight:700;}
.reports-sub-tab:hover,.reports-designation-pill:hover{background:#EEF5FB;color:#244969;}
.reports-sub-tab.active,.reports-designation-pill.active{background:#EEE9FF;border-color:#D4C7FF;color:#6547BE;}
.reports-sub-tab b,.reports-designation-pill b{font-size:10px;padding:2px 6px;border-radius:999px;background:#EDF2F7;color:#64748B;}
.reports-peer-note{display:flex;align-items:center;gap:9px;margin:1px 0 14px;padding:10px 13px;border:1px solid #E5DAFF;border-radius:10px;background:#FAF8FF;color:#6C6380;font-size:11px;}
.reports-peer-note i{color:#7C5FD9;}
.reports-peer-note strong{color:#6641BB;}
.reports-data-panel{border:1px solid #CFE0F0;border-radius:15px;background:#fff;box-shadow:0 6px 18px rgba(28,64,92,.06);overflow:hidden;}
.reports-data-head{display:flex;justify-content:space-between;align-items:center;gap:16px;padding:22px 28px 18px;}
.reports-data-title{display:flex;align-items:center;gap:11px;min-width:0;}
.reports-data-title i{color:#2B67DE;font-size:18px;}
.reports-data-title h2{font-family:'Rajdhani',sans-serif;font-size:23px;font-weight:700;color:#122E4A;margin:0;}
.reports-data-title span{color:#637B92;font-size:11px;white-space:nowrap;}
.reports-data-actions{display:flex;align-items:center;gap:10px;}
.reports-action-btn{height:41px;padding:0 15px;border:1px solid #C6D8E8;border-radius:10px;background:#fff;color:#27445F;font:700 12px 'Inter',sans-serif;display:inline-flex;align-items:center;gap:7px;cursor:pointer;}
.reports-action-btn:hover{background:#F5F9FD;border-color:#9DB9D2;}
.reports-filters-btn{padding:0 16px;}
.reports-search-box{display:flex;align-items:center;gap:8px;width:275px;height:41px;padding:0 12px;border:1px solid #B9CFE3;border-radius:10px;background:#fff;}
.reports-search-box i{color:#6A8299;font-size:13px;}
.reports-search-box input{width:100%;border:0!important;outline:0!important;background:transparent!important;padding:0!important;box-shadow:none!important;color:#173956!important;font-size:12px!important;}
.reports-search-box input::placeholder{color:#91A4B6!important;}
.reports-active-filter-note{margin:0 28px 12px;padding:9px 12px;border-radius:9px;background:#F8FBFE;border:1px solid #DCE8F2;color:#617B92;font-size:11px;}
.reports-active-filter-note button{float:right;border:0;background:none;color:#5C45B5;font-size:11px;font-weight:700;cursor:pointer;}
.reports-table-wrap{overflow-x:auto;}
.reports-table{width:100%;border-collapse:collapse;min-width:1020px;}
.reports-table th{padding:12px 16px;background:#F7FAFD;color:#60778D!important;border-top:1px solid #DDE8F1;border-bottom:1px solid #D8E4EE;font-size:10px!important;font-weight:800!important;letter-spacing:.07em;text-align:left;white-space:nowrap;}
.reports-table td{padding:16px;border-bottom:1px solid #E0E9F1;color:#173956!important;font-size:13px!important;vertical-align:middle;}
.reports-table tbody tr:hover td{background:#F9FBFD!important;}
.reports-table .col-no{width:58px;text-align:center;}
.reports-table .col-actions{width:225px;}
.reports-person-link{display:flex;align-items:center;gap:12px;text-decoration:none;color:inherit;min-width:240px;}
.reports-person-photo{width:42px;height:42px;border-radius:50%;object-fit:cover;border:1px solid #C9D8E6;flex:0 0 auto;}
.reports-person-photo.placeholder{display:inline-flex;align-items:center;justify-content:center;background:#F2F6F9;color:#6E8294;font-size:15px;}
.reports-person-name{font-size:13px;font-weight:800;color:#0F2944;line-height:1.2;}
.reports-designation-cell{max-width:310px;line-height:1.35;color:#21435F!important;}
.reports-role-badge{display:inline-flex;align-items:center;gap:6px;padding:5px 10px;border-radius:999px;font-size:11px;font-weight:800;white-space:nowrap;border:1px solid;}
.reports-role-badge.faculty{background:#EFF5FF;border-color:#A9C9FF;color:#2563D8;}
.reports-role-badge.staff{background:#F3ECFF;border-color:#D0B8FF;color:#733DD0;}
.reports-role-badge.dean{background:#F0EBFF;border-color:#D8C8FF;color:#7450C9;}
.reports-role-badge.principal{background:#FFF4E3;border-color:#F1C98B;color:#B96A00;}
.reports-score{font-size:15px;font-weight:800;}
.reports-score.high{color:#08A06A;}
.reports-score.mid{color:#B06B00;}
.reports-score.low{color:#CF3D3D;}
.reports-date{color:#2E4B64!important;white-space:nowrap;}
.reports-row-actions{display:flex;align-items:center;gap:7px;}
.reports-view-btn,.reports-archive-btn{display:inline-flex;align-items:center;justify-content:center;gap:6px;min-width:72px;padding:8px 11px;border-radius:9px;text-decoration:none;font-size:11px;font-weight:800;cursor:pointer;font-family:'Inter',sans-serif;}
.reports-view-btn{background:#F1F6FF;border:1px solid #B5D0FF;color:#2363D4;}
.reports-view-btn:hover{background:#E6F0FF;}
.reports-archive-btn{background:#fff;border:1px solid #C7D7E5;color:#687E92;}
.reports-archive-btn:hover{background:#F7FAFC;color:#314E68;border-color:#AABFD2;}
.reports-empty-state{text-align:center;padding:64px 24px;color:#6B8195;}
.reports-empty-state i{display:block;font-size:34px;margin-bottom:13px;color:#9CB0C1;}
.reports-empty-state h3{font-family:'Rajdhani',sans-serif;font-size:20px;color:#163450;margin-bottom:5px;}
.reports-empty-state p{font-size:12px;color:#71869A;}
.reports-no-search{text-align:center;padding:26px;color:#71869A;font-size:12px;}
.reports-redesign [hidden]{display:none!important;}
.reports-live-status{display:inline-flex;align-items:center;gap:5px;margin-left:3px;padding:4px 8px;border-radius:999px;background:#ECFDF5;border:1px solid #BBE7D2;color:#0F8A61;font-size:10px;font-weight:800;white-space:nowrap;}
.reports-live-dot{width:6px;height:6px;border-radius:50%;background:#10B981;box-shadow:0 0 0 0 rgba(16,185,129,.45);animation:reportsLivePulse 2s infinite;}
.reports-live-status.stale{background:#F8FAFC;border-color:#D6E0EA;color:#71869A;}
.reports-live-status.stale .reports-live-dot{background:#94A3B8;box-shadow:none;animation:none;}
@keyframes reportsLivePulse{0%{box-shadow:0 0 0 0 rgba(16,185,129,.4);}70%{box-shadow:0 0 0 6px rgba(16,185,129,0);}100%{box-shadow:0 0 0 0 rgba(16,185,129,0);}}
@media (max-width:900px){
  .reports-toolbar{align-items:flex-start;flex-direction:column;padding-bottom:16px;}
  .reports-toolbar-actions{width:100%;justify-content:flex-end;}
  .reports-data-head{align-items:flex-start;flex-direction:column;}
  .reports-data-actions{width:100%;flex-wrap:wrap;}
  .reports-search-box{flex:1;min-width:210px;}
}
@media (max-width:640px){
  .reports-top-switcher{width:100%;max-width:none;flex-wrap:wrap;}
  .reports-top-tab{flex:0 0 auto;min-height:44px;padding:0 14px;font-size:12px;gap:7px;}
  .reports-toolbar-actions{justify-content:stretch;}
  .reports-archive-link,.reports-print-btn{flex:1;justify-content:center;}
  .reports-data-head{padding:18px 16px;}
  .reports-search-box{order:3;width:100%;flex-basis:100%;}
  .reports-action-btn{flex:1;justify-content:center;}
}
@media print{
  .reports-toolbar-actions,.reports-data-actions,.reports-row-actions,.reports-sub-filters,.reports-designation-filters{display:none!important;}
  .reports-redesign{margin:0;}
  .reports-top-switcher{box-shadow:none;}
  .reports-data-panel{box-shadow:none;border-color:#CBD5E1;}
}
</style>

<script>
function filterByDesig(desig, evt) {
    if (evt) evt.preventDefault();
    document.querySelectorAll('.reports-designation-pill').forEach(t => t.classList.remove('active'));
    if (evt && evt.target) {
        const pill = evt.target.closest('.reports-designation-pill');
        if (pill) pill.classList.add('active');
    }
    document.querySelectorAll('#reportsTable tbody .reports-person-row').forEach(row => {
        row.style.display = (desig === 'all' || row.dataset.desig === desig) ? '' : 'none';
    });
}
function toggleReportsFilters() {
    const designation = document.getElementById('designationFilters');
    const note = document.getElementById('reportsFilterNote');
    if (designation) {
        designation.hidden = !designation.hidden;
        if (!designation.hidden) designation.scrollIntoView({behavior:'smooth', block:'nearest'});
        if (note) note.hidden = true;
        return;
    }
    if (note) note.hidden = !note.hidden;
}
function clearReportsSearch() {
    const input = document.getElementById('reportsSearch');
    if (input) { input.value = ''; input.dispatchEvent(new Event('input')); input.focus(); }
}
function exportReportsTable() {
    const rows = Array.from(document.querySelectorAll('#reportsTable tbody .reports-person-row')).filter(r => r.style.display !== 'none');
    if (!rows.length) return;
    const csv = [['No.','Name','Designation','Role','Overall Avg. Score','Last Evaluated']];
    rows.forEach(row => {
        const cells = row.querySelectorAll('td');
        const name = row.querySelector('.reports-person-name')?.textContent.trim() || '';
        const designation = row.querySelector('.reports-designation-cell')?.textContent.trim() || '';
        const role = row.querySelector('.reports-role-badge')?.textContent.trim() || '';
        const score = row.querySelector('.reports-score')?.textContent.trim() || '';
        const last = row.querySelector('.reports-date')?.textContent.trim() || '';
        csv.push([cells[0].textContent.trim(), name, designation, role, score, last]);
    });
    const blob = new Blob([csv.map(r => r.map(v => '"' + String(v).replace(/"/g,'""') + '"').join(',')).join('\n')], {type:'text/csv;charset=utf-8;'});
    const url = URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = 'dean-report-<?= $activeEval ?>.csv';
    document.body.appendChild(a);
    a.click();
    a.remove();
    URL.revokeObjectURL(url);
}
document.addEventListener('DOMContentLoaded', () => {
    const input = document.getElementById('reportsSearch');
    const noSearch = document.getElementById('reportsNoSearch');
    if (!input) return;
    input.addEventListener('input', () => {
        const q = input.value.trim().toLowerCase();
        let visible = 0;
        document.querySelectorAll('#reportsTable tbody .reports-person-row').forEach(row => {
            const match = !q || row.dataset.search.includes(q);
            row.style.display = match ? '' : 'none';
            if (match) visible++;
        });
        if (noSearch) noSearch.hidden = visible !== 0 || !q;
        const note = document.getElementById('reportsFilterNote');
        if (note) note.hidden = !q;
    });
});
function archivePerson(id, name) {
    if (confirm(`Archive "${name}"? They'll be hidden from this list but their evaluation data is kept and can be restored anytime.`)) {
        window.location.href = `?archive_id=${id}&group=<?= urlencode($groupFilter) ?>&eval_type=<?= $activeEval ?>`;
    }
}

// ── LIVE REPORT UPDATES ────────────────────────────────────────────────
// Reports reads the same evaluation_tracker records used by the tracker/results
// pages. Polling the lightweight endpoint makes newly submitted evaluations
// appear without requiring the Dean to manually reload the report.
(function(){
    const status = document.getElementById('reportsLiveStatus');
    const search = document.getElementById('reportsSearch');
    const storageKey = 'pbiDeanReportsSearch';
    if (search) {
        const saved = sessionStorage.getItem(storageKey);
        if (saved && !search.value) {
            search.value = saved;
            search.dispatchEvent(new Event('input'));
        }
        search.addEventListener('input', function(){ sessionStorage.setItem(storageKey, search.value); });
    }
    if (status) status.title = 'Live — checks for new evaluations every 10 seconds';
    const base = 'dean_reports_live_api.php?eval_type=<?= urlencode($activeEval) ?>&group=<?= urlencode($groupFilter) ?>';
    let lastSignature = null;
    let busy = false;
    function pollReports(){
        if (busy) return;
        busy = true;
        fetch(base, {credentials:'same-origin', cache:'no-store'})
            .then(function(r){ if(!r.ok) throw new Error('status'); return r.json(); })
            .then(function(data){
                if (status) status.classList.remove('stale');
                const sig = data && data.signature ? data.signature : '';
                if (lastSignature === null) {
                    lastSignature = sig;
                    return;
                }
                if (sig && sig !== lastSignature) {
                    if (status) {
                        status.classList.add('updating');
                        status.title = 'New evaluation/report data detected — refreshing…';
                        status.innerHTML = '<span class=\"reports-live-dot\"></span> Updating…';
                    }
                    setTimeout(function(){ window.location.reload(); }, 450);
                }
            })
            .catch(function(){ if (status) status.classList.add('stale'); })
            .finally(function(){ busy = false; });
    }
    pollReports();
    setInterval(pollReports, 10000);
})();
</script>
<?php $mysqli->close(); ?>

<style id="executive-original-theme">
:root{
 --dark:#0A192F;--mid:#172A45;--inner:#0F1F3D;
 --violet:#7C5FD9;--violet-h:#9C85F0;--violet-dark:#5F45B8;
 --light:#E0E6F0;--muted:#A0B3C6;--border:rgba(255,255,255,.08);
 --accent:#7C5FD9;--accent-h:#9C85F0;--good:#10B981;--danger:#f05454;
 --page-bg:#0A192F;--card-bg:rgba(23,42,69,.85);--card-border:rgba(255,255,255,.08);
}
html{background:var(--dark)!important;color-scheme:dark!important;}
body{background:linear-gradient(rgba(5,18,36,.72),rgba(5,18,36,.82)),url('../background.png') center center/cover no-repeat fixed!important;background-color:var(--dark)!important;color:var(--light)!important;}
.main,.content,.page-content{color:var(--light)!important;}
.page-title,.page-header h1,.section-title,.sheet-name,.target-name{color:#fff!important;}
.page-sub,.sheet-desig,.target-desig,.muted,.hint,.helper,.description,p{color:var(--muted)!important;}
/* Cards/panels */
.card,.panel,.section,.table-card,.content-card,.stat-card,.sum-card,.standing-panel,.eval-card,.eval-banner,.info-banner,.history-card,.gl-card,.amber-card,.green-card,.red-card,.person-row,.target-card,.no-eval,.no-data,.no-evaluated,.comment-section,.avg-summary,.cat-section,.people-list,.evaluator-grid{
 background:rgba(23,42,69,.85)!important;border-color:rgba(255,255,255,.08)!important;box-shadow:0 8px 32px rgba(0,0,0,.45)!important;color:var(--light)!important;
}
.table-wrap{background:transparent!important;color:var(--light)!important;}
table.data th,table.data td,th,td{color:var(--light)!important;border-color:rgba(255,255,255,.08)!important;}
.q-table{color:var(--light)!important;}
.q-table th{color:var(--muted)!important;background:rgba(15,31,61,.65)!important;border-color:rgba(255,255,255,.08)!important;}
.q-table td{color:var(--light)!important;border-color:rgba(255,255,255,.06)!important;}
/* Tabs and filters */
.eval-switcher,.tabs,.level-tabs,.status-tabs{background:rgba(23,42,69,.85)!important;border-color:rgba(255,255,255,.08)!important;box-shadow:none!important;}
.eval-tab,.tab,.level-tab,.status-tab,.group-tab,.desig-subtab{color:var(--muted)!important;background:transparent!important;}
.eval-tab:hover,.tab:hover,.level-tab:hover,.status-tab:hover,.group-tab:hover,.desig-subtab:hover{color:#fff!important;background:rgba(124,95,217,.08)!important;}
.eval-tab.student.active,.group-tab.active,.desig-subtab.active{background:rgba(124,95,217,.14)!important;color:var(--violet-h)!important;border-color:rgba(124,95,217,.35)!important;}
.eval-tab.student.active::after{background:var(--violet)!important;}
.eval-tab.peer.active{background:rgba(124,95,217,.14)!important;color:var(--violet-h)!important;}
.eval-tab.peer.active::after{background:var(--violet)!important;}
.tab-badge{background:rgba(255,255,255,.08)!important;color:var(--muted)!important;}
.eval-tab.student.active .tab-badge,.eval-tab.peer.active .tab-badge{background:rgba(124,95,217,.18)!important;color:var(--violet-h)!important;}
/* Buttons/links */
.btn-print,.btn-print.no-print,.back-btn,.btn,.action-btn,.btn-archive,.btn-restore,.btn-solid,.btn-archived-link{background:rgba(124,95,217,.12)!important;border:1px solid rgba(124,95,217,.35)!important;color:var(--violet-h)!important;}
.btn-print:hover,.back-btn:hover,.btn:hover,.action-btn:hover,.btn-archive:hover,.btn-restore:hover,.btn-solid:hover,.btn-archived-link:hover{background:rgba(124,95,217,.22)!important;color:#fff!important;}
.btn-solid{background:var(--violet)!important;color:#fff!important;}
/* accents */
.eval-banner{background:rgba(124,95,217,.08)!important;border-color:rgba(124,95,217,.25)!important;}
.eval-banner-title,.eval-banner-icon,.cat-title,.comment-title,.section h2 i,.section h2{color:var(--violet-h)!important;}
.period-badge{background:rgba(124,95,217,.14)!important;border-color:rgba(124,95,217,.3)!important;color:var(--violet-h)!important;}
.bar-fill,.avg-bar-fill{background:linear-gradient(90deg,var(--violet-dark),var(--violet-h))!important;}
.score-bar-bg,.eval-bar-bg,.avg-bar-bg,.bar-wrap{background:rgba(255,255,255,.08)!important;}
.standing-title.top,.standing-score,.pstat-val,.avg-score-big,.avg-score-label{color:#4ade80!important;}
.standing-title.low{color:#f87171!important;}
.person-row:hover,.standing-item:hover{border-color:rgba(124,95,217,.4)!important;background:rgba(124,95,217,.05)!important;}
.comment-text{background:rgba(15,31,61,.7)!important;color:var(--light)!important;}
.empty-note,.no-comment,.no-eval{color:var(--muted)!important;}
label,th{color:var(--muted)!important;}
input,select,textarea{background:#0F1F3D!important;color:var(--light)!important;border-color:rgba(255,255,255,.12)!important;}
input::placeholder,textarea::placeholder{color:#7890a8!important;}
</style>

</body>
<link rel="stylesheet" href="includes/dean_light_theme.css" id="dean-light-theme-final"/>
</html>