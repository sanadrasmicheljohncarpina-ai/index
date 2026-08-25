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

// Portal scope: Reports & Analytics is a pixel/function clone of the EA page,
// but its dataset is restricted to the current executive's education division.
$reportScope = 'PRINCIPAL';
$reportScopeSql = ($reportScope === 'DEAN')
    ? "(u.role='staff' OR (u.role IN ('teacher','faculty') AND EXISTS (SELECT 1 FROM user_year_levels scope_uyl WHERE scope_uyl.user_id=u.id AND scope_uyl.year_level IN ('1st Year College','2nd Year College','3rd Year College','4th Year College'))))"
    : "(u.role='staff' OR (u.role IN ('teacher','faculty') AND EXISTS (SELECT 1 FROM user_year_levels scope_uyl WHERE scope_uyl.user_id=u.id AND scope_uyl.year_level IN ('Grade 7','Grade 8','Grade 9','Grade 10','Grade 11','Grade 12','Grade 7 - JHS','Grade 8 - JHS','Grade 9 - JHS','Grade 10 - JHS','Grade 11 - SHS','Grade 12 - SHS'))) )";
$reportStudentScopeSql = ($reportScope === 'DEAN')
    ? "education_level IN ('college','higher education','college / university','college/university')"
    : "education_level IN ('junior_high','senior_high','basic_education','jhs','shs')";

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

// ── DASHBOARD QUICK-REPORT LINKS ────────────────────────────────
// principal_dashboard.php's "Reports & Analytics" buttons link here with a
// ?type= value (teacher_performance, grade_comparison, school_summary,
// student_participation, staff_performance, completion) instead of
// ?eval_type=/&group=. Map each to the closest matching tab/group here so
// the button actually lands somewhere relevant instead of always opening
// the default Student tab. student_participation/completion don't have a
// dedicated tab on this page (that data lives on the Evaluation Tracker),
// so they fall back to the default All/Student view like the others.
$typeDefaults = [
    'teacher_performance'   => ['eval_type' => 'student', 'group' => 'Teacher'],
    'staff_performance'     => ['eval_type' => 'peer',    'group' => 'Staff'],
    'grade_comparison'      => ['eval_type' => 'student', 'group' => 'All'],
    'school_summary'        => ['eval_type' => 'student', 'group' => 'All'],
    'student_participation' => ['eval_type' => 'student', 'group' => 'All'],
    'completion'            => ['eval_type' => 'student', 'group' => 'All'],
];
$typeDefault = $typeDefaults[$_GET['type'] ?? ''] ?? null;

// ── ACTIVE EVAL TYPE ──────────────────────────────────────────
$activeEval = $_GET['eval_type'] ?? ($typeDefault['eval_type'] ?? 'student');
if (!in_array($activeEval, ['student','multi_role','peer'])) $activeEval = 'student';

$evalTypeSql = match ($activeEval) {
    // Multi-Role is primarily identified by the explicit evaluation_context.
    // The EXISTS fallback also recognizes older submissions that were saved
    // before evaluation_context was introduced, provided their answers point
    // to a per-person Multi-Role question. This keeps legacy submissions from
    // disappearing from the dedicated Multi-Role analytics tab.
    'multi_role' => "et.eval_type='student' AND (
        et.evaluation_context='multi_role'
        OR EXISTS (
            SELECT 1
            FROM questionnaire_answers qam
            JOIN user_questions uqm ON uqm.id = qam.user_question_id
            WHERE qam.tracker_id = et.id
              AND uqm.target_type = 'Multi-Role'
              AND uqm.eval_type = 'student'
        )
    )",
    // Peer-to-Peer submissions are written under eval_type='faculty_peer'
    // (faculty_dashboard.php) or 'staff_peer' (staff_dashboard.php), plus
    // the legacy 'peer' value from older submissions. All three must be
    // matched or the Peer-to-Peer tab shows no data — mirrors the EA page.
    'peer' => "et.eval_type IN ('peer','faculty_peer','staff_peer')",
    default => "et.eval_type='" . $mysqli->real_escape_string($activeEval) . "'"
};
$evalTypePlainSql = match ($activeEval) {
    'multi_role' => "eval_type='student' AND (
        evaluation_context='multi_role'
        OR EXISTS (
            SELECT 1
            FROM questionnaire_answers qam
            JOIN user_questions uqm ON uqm.id = qam.user_question_id
            WHERE qam.tracker_id = evaluation_tracker.id
              AND uqm.target_type = 'Multi-Role'
              AND uqm.eval_type = 'student'
        )
    )",
    'peer' => "eval_type IN ('peer','faculty_peer','staff_peer')",
    default => "eval_type='" . $mysqli->real_escape_string($activeEval) . "'"
};

// ── VIEWS ─────────────────────────────────────────────────────
$view       = $_GET['view']       ?? 'list';
$target_id  = intval($_GET['target_id']  ?? 0);
$student_id = intval($_GET['student_id'] ?? 0);  // evaluator for peer
$tracker_id = intval($_GET['tracker_id'] ?? 0);
$groupFilter = $_GET['group'] ?? ($typeDefault['group'] ?? 'All');
if (!in_array($groupFilter, ['All','Teacher','Staff'])) $groupFilter = 'All';

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
    if ($s >= 4.5)  return '#4ade80';
    if ($s >= 3.5)  return '#86efac';
    if ($s >= 2.5)  return '#facc15';
    if ($s >= 1.5)  return '#fb923c';
    return '#f87171';
}

// Eval type UI config
$evalLabel      = $activeEval === 'peer' ? 'Peer-to-Peer Evaluation' : ($activeEval === 'multi_role' ? 'Multi-Role Evaluation' : 'Student Evaluation');
$evalColor      = $activeEval === 'peer' ? '#7C3AED' : ($activeEval === 'multi_role' ? '#F59E0B' : '#3B82F6');
$evalColorBg    = $activeEval === 'peer' ? 'rgba(124,58,237,.08)' : ($activeEval === 'multi_role' ? 'rgba(245,158,11,.08)' : 'rgba(59,130,246,.08)');
$evalColorBorder= $activeEval === 'peer' ? 'rgba(124,58,237,.25)' : ($activeEval === 'multi_role' ? 'rgba(245,158,11,.25)' : 'rgba(59,130,246,.25)');
$evalIcon       = $activeEval === 'peer' ? 'fa-people-arrows' : ($activeEval === 'multi_role' ? 'fa-people-group' : 'fa-graduation-cap');
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
        'settings' => ['principal_account_settings.php','fa-gear','Account Settings'],
    ];
    ?>
    <aside class="sidebar">
        <div class="sb-profile">
            <img class="sb-photo" src="<?= htmlspecialchars($photo_src) ?>" alt="Profile"/>
            <div class="sb-name"><?= htmlspecialchars($me['full_name'] ?? 'Principal') ?></div>
            <div class="sb-role"><?= htmlspecialchars($me['designation'] ?? 'Principal') ?></div>
            <div class="sb-scope"><?= htmlspecialchars($scope) ?></div>
        </div>
        <nav class="sb-nav">
            <?php foreach ($links as $key => [$href,$icon,$label]): ?>
            <a href="<?= htmlspecialchars($href) ?>" class="<?= $key === $active ? 'active' : '' ?>"><i class="fa-solid <?= htmlspecialchars($icon) ?>"></i> <?= htmlspecialchars($label) ?></a>
            <?php endforeach; ?>
        </nav>
        <div class="sb-logout"><a href="principal_logout.php"><i class="fa-solid fa-right-from-bracket"></i> Log Out</a></div>
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
<style>
:root{--portal-accent:#d99a2b;--portal-accent-h:#f0b84d;--portal-accent-glow:rgba(217,154,43,.4);--portal-accent-bg:rgba(217,154,43,.15);}
html{background:#0A192F !important;color-scheme:dark;}
body{min-height:100vh;display:flex !important;padding:0 !important;background:linear-gradient(rgba(5,18,36,.72),rgba(5,18,36,.82)),url('../background.png') center center / cover no-repeat fixed !important;background-color:#0A192F !important;color:#E0E6F0 !important;font-family:'DM Sans',sans-serif !important;}
.sidebar{width:250px;flex:0 0 250px;min-height:100vh;background:rgba(23,42,69,.9);border-right:1px solid rgba(255,255,255,.08);padding:28px 20px;display:flex;flex-direction:column;position:sticky;top:0;height:100vh;z-index:20;}
.sb-profile{text-align:center;margin-bottom:26px}.sb-photo{width:72px;height:72px;border-radius:50%;object-fit:cover;border:2.5px solid var(--portal-accent);box-shadow:0 0 18px var(--portal-accent-glow);margin:0 auto 10px;display:block}.sb-name{font-weight:700;font-size:15px;color:#fff}.sb-role{font-size:11px;color:var(--portal-accent-h);text-transform:uppercase;letter-spacing:.6px;margin-top:2px}.sb-scope{font-size:10px;color:#A0B3C6;margin-top:4px}.sb-nav{display:flex;flex-direction:column;gap:4px;margin-top:10px}.sb-nav a{display:flex;align-items:center;gap:10px;padding:11px 14px;border-radius:8px;color:#A0B3C6;text-decoration:none;font-size:14px;font-weight:500}.sb-nav a:hover,.sb-nav a.active{background:var(--portal-accent-bg);color:#fff}.sb-nav a i{width:18px;text-align:center;color:var(--portal-accent-h)}.sb-logout{margin-top:auto}.sb-logout a{display:flex;align-items:center;gap:10px;padding:11px 14px;border-radius:8px;color:#fca5a5;text-decoration:none;font-size:14px}
.main{flex:1;min-width:0;padding:36px 44px !important;}
.page-header{background:rgba(23,42,69,.85) !important;border:1px solid rgba(255,255,255,.08) !important;color:#E0E6F0 !important;box-shadow:0 8px 32px rgba(0,0,0,.45) !important}.page-header h1,.page-title,.section-title,.sheet-name,.target-name,.person-name,.standing-name{color:#fff !important}.page-header p,.page-sub,.standing-desig,.person-meta,.sum-label,.sum-sub,.pstat-lbl,.muted,.no-data{color:#A0B3C6 !important}
.eval-switcher,.eval-banner,.group-tab,.desig-subtabs,.sum-card,.standing-panel,.person-row,.no-evaluated,.target-card,.eval-card,.comment-section,.avg-summary,.top-bar,.history-card,.section,.content-panel,.no-archived{background:rgba(23,42,69,.85) !important;border-color:rgba(255,255,255,.08) !important;box-shadow:0 8px 32px rgba(0,0,0,.24) !important;color:#E0E6F0 !important}
.eval-tab{color:#A0B3C6 !important}.eval-tab:hover{color:#fff !important;background:rgba(255,255,255,.04) !important}.eval-tab.student.active,.eval-tab.multi-role.active,.eval-tab.peer.active{background:var(--portal-accent-bg) !important;color:var(--portal-accent-h) !important}.eval-tab.student.active::after,.eval-tab.multi-role.active::after,.eval-tab.peer.active::after{background:var(--portal-accent) !important}.tab-badge,.tab-count{background:rgba(255,255,255,.10) !important;color:#E0E6F0 !important}
.group-tab.active-all,.group-tab.active-teacher,.group-tab.active-staff,.desig-subtab.active{background:var(--portal-accent-bg) !important;border-color:rgba(255,255,255,.16) !important;color:var(--portal-accent-h) !important}
.sum-value,.person-name,.section-title,.standing-name,.sheet-name,.target-name{color:#fff !important}.person-link,.back-btn,.btn-archived-link{color:#E0E6F0 !important}.person-row:hover{border-color:rgba(255,255,255,.16) !important}.person-photo-ph,.standing-photo-ph{background:#0F1F3D !important;border-color:rgba(255,255,255,.10) !important;color:#A0B3C6 !important}
.score-bar-bg,.avg-bar-bg,.eval-bar-bg{background:rgba(255,255,255,.08) !important}.btn-print{background:var(--portal-accent) !important}.btn-archived-link{background:rgba(23,42,69,.85) !important;border-color:rgba(255,255,255,.10) !important}.btn-archived-link:hover{color:#fff !important}.back-btn{background:rgba(23,42,69,.85) !important;border-color:rgba(255,255,255,.10) !important}.back-btn:hover{background:var(--portal-accent-bg) !important;color:#fff !important}
input,select,textarea{background:#0F1F3D !important;color:#E0E6F0 !important;border-color:rgba(255,255,255,.12) !important}.comment-text{background:#0F1F3D !important;color:#E0E6F0 !important}.eval-banner-title{color:var(--portal-accent-h) !important}.eval-banner-desc{color:#A0B3C6 !important}
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

<style id="executive-original-theme">
:root{
 --dark:#0A192F;--mid:#172A45;--inner:#0F1F3D;
 --amber:#d99a2b;--amber-h:#f0b84d;--amber-dark:#b8801f;
 --light:#E0E6F0;--muted:#A0B3C6;--border:rgba(255,255,255,.08);
 --accent:#d99a2b;--accent-h:#f0b84d;--good:#10B981;--danger:#f05454;
 --page-bg:#0A192F;--card-bg:rgba(23,42,69,.85);--card-border:rgba(255,255,255,.08);
}
html{background:var(--dark)!important;color-scheme:dark!important;}
body{background:linear-gradient(rgba(5,18,36,.72),rgba(5,18,36,.82)),url('../background.png') center center/cover no-repeat fixed!important;background-color:var(--dark)!important;color:var(--light)!important;}
.main,.content,.page-content{color:var(--light)!important;}
.page-title,.page-header h1,.section-title,.sheet-name,.target-name{color:#fff!important;}
.page-sub,.sheet-desig,.target-desig,.muted,.hint,.helper,.description,p{color:var(--muted)!important;}
.card,.panel,.section,.table-card,.content-card,.stat-card,.sum-card,.standing-panel,.eval-card,.eval-banner,.info-banner,.history-card,.gl-card,.amber-card,.green-card,.red-card,.person-row,.target-card,.no-eval,.no-data,.no-evaluated,.comment-section,.avg-summary,.cat-section,.people-list,.evaluator-grid{
 background:rgba(23,42,69,.85)!important;border-color:rgba(255,255,255,.08)!important;box-shadow:0 8px 32px rgba(0,0,0,.45)!important;color:var(--light)!important;
}
.table-wrap{background:transparent!important;color:var(--light)!important;}
table.data th,table.data td,th,td{color:var(--light)!important;border-color:rgba(255,255,255,.08)!important;}
.q-table{color:var(--light)!important;}
.q-table th{color:var(--muted)!important;background:rgba(15,31,61,.65)!important;border-color:rgba(255,255,255,.08)!important;}
.q-table td{color:var(--light)!important;border-color:rgba(255,255,255,.06)!important;}
.eval-switcher,.tabs,.level-tabs,.status-tabs{background:rgba(23,42,69,.85)!important;border-color:rgba(255,255,255,.08)!important;box-shadow:none!important;}
.eval-tab,.tab,.level-tab,.status-tab,.group-tab,.desig-subtab{color:var(--muted)!important;background:transparent!important;}
.eval-tab:hover,.tab:hover,.level-tab:hover,.status-tab:hover,.group-tab:hover,.desig-subtab:hover{color:#fff!important;background:rgba(217,154,43,.08)!important;}
.eval-tab.student.active,.group-tab.active,.desig-subtab.active{background:rgba(217,154,43,.14)!important;color:var(--amber-h)!important;border-color:rgba(217,154,43,.35)!important;}
.eval-tab.student.active::after{background:var(--amber)!important;}
.eval-tab.peer.active{background:rgba(217,154,43,.14)!important;color:var(--amber-h)!important;}
.eval-tab.peer.active::after{background:var(--amber)!important;}
.eval-tab.multi-role.active{background:rgba(217,154,43,.14)!important;color:var(--amber-h)!important;}
.eval-tab.multi-role.active::after{background:var(--amber)!important;}
.tab-badge{background:rgba(255,255,255,.08)!important;color:var(--muted)!important;}
.eval-tab.student.active .tab-badge,.eval-tab.peer.active .tab-badge,.eval-tab.multi-role.active .tab-badge{background:rgba(217,154,43,.18)!important;color:var(--amber-h)!important;}
.btn-print,.btn-print.no-print,.back-btn,.btn,.action-btn,.btn-archive,.btn-restore,.btn-solid,.btn-archived-link{background:rgba(217,154,43,.12)!important;border:1px solid rgba(217,154,43,.35)!important;color:var(--amber-h)!important;}
.btn-print:hover,.back-btn:hover,.btn:hover,.action-btn:hover,.btn-archive:hover,.btn-restore:hover,.btn-solid:hover,.btn-archived-link:hover{background:rgba(217,154,43,.22)!important;color:#fff!important;}
.btn-solid{background:var(--amber)!important;color:#fff!important;}
.eval-banner{background:rgba(217,154,43,.08)!important;border-color:rgba(217,154,43,.25)!important;}
.eval-banner-title,.eval-banner-icon,.cat-title,.comment-title,.section h2 i,.section h2{color:var(--amber-h)!important;}
.period-badge{background:rgba(217,154,43,.14)!important;border-color:rgba(217,154,43,.3)!important;color:var(--amber-h)!important;}
.bar-fill,.avg-bar-fill{background:linear-gradient(90deg,var(--amber-dark),var(--amber-h))!important;}
.score-bar-bg,.eval-bar-bg,.avg-bar-bg,.bar-wrap{background:rgba(255,255,255,.08)!important;}
.standing-title.top,.standing-score,.pstat-val,.avg-score-big,.avg-score-label{color:#4ade80!important;}
.standing-title.low{color:#f87171!important;}
.person-row:hover,.standing-item:hover{border-color:rgba(217,154,43,.4)!important;background:rgba(217,154,43,.05)!important;}
.comment-text{background:rgba(15,31,61,.7)!important;color:var(--light)!important;}
.empty-note,.no-comment,.no-eval{color:var(--muted)!important;}
label,th{color:var(--muted)!important;}
input,select,textarea{background:#0F1F3D!important;color:var(--light)!important;border-color:rgba(255,255,255,.12)!important;}
input::placeholder,textarea::placeholder{color:#7890a8!important;}
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
               u.id as student_id, u.full_name, u.photo,
               (SELECT AVG(qa.answer_score) FROM questionnaire_answers qa WHERE qa.tracker_id=et.id) as avg_score
        FROM evaluation_tracker et
        JOIN users u ON u.id = et.evaluator_id
        WHERE et.target_user_id = $target_id AND $evalTypeSql
        ORDER BY et.submitted_at DESC
    ");
    if ($eq) $evaluators = $eq->fetch_all(MYSQLI_ASSOC);

    $allScores  = array_filter(array_column($evaluators,'avg_score'), fn($s) => $s !== null);
    $overallAvg = count($allScores) ? round(array_sum($allScores)/count($allScores),2) : null;

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

/* ── PRINT: hide evaluator cards entirely, show summary only ── */
@media print{
  .no-print{display:none!important;}
  body{background:#fff!important;color:#000!important;padding:0;}
  body *{color:#000!important;}
  /* Hide every evaluator card — names, photos, dates, remarks */
  .evaluator-grid{display:none!important;}
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
</div>

<div class="section-title">
    <i class="fa-solid <?= $activeEval === 'peer' ? 'fa-people-arrows' : 'fa-user-graduate' ?>" style="color:var(--accent)"></i>
    <?= ucfirst($evaluatorNounP) ?> Who Evaluated
    <span style="font-size:13px;font-weight:400;color:var(--muted)">(<?= count($evaluators) ?>)</span>
</div>

<?php if (empty($evaluators)): ?>
<div class="no-eval">
    <i class="fa-solid fa-users-slash"></i>
    <p>No <?= $evaluatorNounP ?> have evaluated this person yet.</p>
</div>
<?php else: ?>

<!-- Screen: full evaluator cards with names/photos -->
<div class="evaluator-grid">
    <?php foreach ($evaluators as $ev):
        $sc = $ev['avg_score'] !== null ? round($ev['avg_score'],2) : null;
        $scolor = scoreColor($sc);
        $pct    = $sc !== null ? ($sc/5)*100 : 0;
        // For peer, look up evaluator's designation
        $evalDesig = '';
        if ($activeEval === 'peer') {
            $dr = $mysqli->query("SELECT designation FROM users WHERE id={$ev['student_id']} LIMIT 1");
            if ($dr) $evalDesig = $dr->fetch_assoc()['designation'] ?? '';
        }
    ?>
    <a class="eval-card"
       href="?view=sheet&target_id=<?= $target_id ?>&student_id=<?= $ev['student_id'] ?>&tracker_id=<?= $ev['tracker_id'] ?>&group=<?= urlencode($groupFilter) ?>&eval_type=<?= $activeEval ?>">
        <div class="eval-card-top">
            <?php if ($ev['photo']): ?><img class="eval-avatar" src="../image/<?= htmlspecialchars($ev['photo']) ?>" alt=""/>
            <?php else: ?><div class="eval-avatar-ph"><i class="fa-solid <?= $activeEval==='peer'?'fa-user-tie':'fa-user-graduate' ?>"></i></div><?php endif; ?>
            <div style="flex:1;min-width:0;">
                <div class="eval-name"><?= htmlspecialchars($ev['full_name']) ?></div>
                <div class="eval-date"><i class="fa-solid fa-clock" style="margin-right:4px"></i><?= date('M d, Y g:i A', strtotime($ev['submitted_at'])) ?></div>
            </div>
            <?php if ($activeEval === 'peer' && $evalDesig): ?>
            <span class="evaluator-role-badge"><?= htmlspecialchars($evalDesig) ?></span>
            <?php endif; ?>
        </div>
        <div class="eval-score-row">
            <div>
                <div class="eval-score" style="color:<?= $scolor ?>"><?= $sc !== null ? number_format($sc,2) : '—' ?></div>
                <div class="eval-score-lbl" style="color:<?= $scolor ?>"><?= scoreLabel($sc) ?></div>
            </div>
            <div class="eval-bar-bg"><div class="eval-bar-fill" style="width:<?= $pct ?>%;background:<?= $scolor ?>"></div></div>
        </div>
        <?php if (!empty($ev['remarks'])): ?>
        <div class="eval-remark"><i class="fa-solid fa-comment-dots" style="margin-right:4px"></i>"<?= htmlspecialchars($ev['remarks']) ?>"</div>
        <?php endif; ?>
        <div class="view-eval-btn"><i class="fa-solid fa-eye" style="margin-right:6px"></i>View Full Evaluation Sheet</div>
    </a>
    <?php endforeach; ?>
</div>

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

<style id="executive-original-theme">
:root{
 --dark:#0A192F;--mid:#172A45;--inner:#0F1F3D;
 --amber:#d99a2b;--amber-h:#f0b84d;--amber-dark:#b8801f;
 --light:#E0E6F0;--muted:#A0B3C6;--border:rgba(255,255,255,.08);
 --accent:#d99a2b;--accent-h:#f0b84d;--good:#10B981;--danger:#f05454;
 --page-bg:#0A192F;--card-bg:rgba(23,42,69,.85);--card-border:rgba(255,255,255,.08);
}
html{background:var(--dark)!important;color-scheme:dark!important;}
body{background:linear-gradient(rgba(5,18,36,.72),rgba(5,18,36,.82)),url('../background.png') center center/cover no-repeat fixed!important;background-color:var(--dark)!important;color:var(--light)!important;}
.main,.content,.page-content{color:var(--light)!important;}
.page-title,.page-header h1,.section-title,.sheet-name,.target-name{color:#fff!important;}
.page-sub,.sheet-desig,.target-desig,.muted,.hint,.helper,.description,p{color:var(--muted)!important;}
.card,.panel,.section,.table-card,.content-card,.stat-card,.sum-card,.standing-panel,.eval-card,.eval-banner,.info-banner,.history-card,.gl-card,.amber-card,.green-card,.red-card,.person-row,.target-card,.no-eval,.no-data,.no-evaluated,.comment-section,.avg-summary,.cat-section,.people-list,.evaluator-grid{
 background:rgba(23,42,69,.85)!important;border-color:rgba(255,255,255,.08)!important;box-shadow:0 8px 32px rgba(0,0,0,.45)!important;color:var(--light)!important;
}
.table-wrap{background:transparent!important;color:var(--light)!important;}
table.data th,table.data td,th,td{color:var(--light)!important;border-color:rgba(255,255,255,.08)!important;}
.q-table{color:var(--light)!important;}
.q-table th{color:var(--muted)!important;background:rgba(15,31,61,.65)!important;border-color:rgba(255,255,255,.08)!important;}
.q-table td{color:var(--light)!important;border-color:rgba(255,255,255,.06)!important;}
.eval-switcher,.tabs,.level-tabs,.status-tabs{background:rgba(23,42,69,.85)!important;border-color:rgba(255,255,255,.08)!important;box-shadow:none!important;}
.eval-tab,.tab,.level-tab,.status-tab,.group-tab,.desig-subtab{color:var(--muted)!important;background:transparent!important;}
.eval-tab:hover,.tab:hover,.level-tab:hover,.status-tab:hover,.group-tab:hover,.desig-subtab:hover{color:#fff!important;background:rgba(217,154,43,.08)!important;}
.eval-tab.student.active,.group-tab.active,.desig-subtab.active{background:rgba(217,154,43,.14)!important;color:var(--amber-h)!important;border-color:rgba(217,154,43,.35)!important;}
.eval-tab.student.active::after{background:var(--amber)!important;}
.eval-tab.peer.active{background:rgba(217,154,43,.14)!important;color:var(--amber-h)!important;}
.eval-tab.peer.active::after{background:var(--amber)!important;}
.eval-tab.multi-role.active{background:rgba(217,154,43,.14)!important;color:var(--amber-h)!important;}
.eval-tab.multi-role.active::after{background:var(--amber)!important;}
.tab-badge{background:rgba(255,255,255,.08)!important;color:var(--muted)!important;}
.eval-tab.student.active .tab-badge,.eval-tab.peer.active .tab-badge,.eval-tab.multi-role.active .tab-badge{background:rgba(217,154,43,.18)!important;color:var(--amber-h)!important;}
.btn-print,.btn-print.no-print,.back-btn,.btn,.action-btn,.btn-archive,.btn-restore,.btn-solid,.btn-archived-link{background:rgba(217,154,43,.12)!important;border:1px solid rgba(217,154,43,.35)!important;color:var(--amber-h)!important;}
.btn-print:hover,.back-btn:hover,.btn:hover,.action-btn:hover,.btn-archive:hover,.btn-restore:hover,.btn-solid:hover,.btn-archived-link:hover{background:rgba(217,154,43,.22)!important;color:#fff!important;}
.btn-solid{background:var(--amber)!important;color:#fff!important;}
.eval-banner{background:rgba(217,154,43,.08)!important;border-color:rgba(217,154,43,.25)!important;}
.eval-banner-title,.eval-banner-icon,.cat-title,.comment-title,.section h2 i,.section h2{color:var(--amber-h)!important;}
.period-badge{background:rgba(217,154,43,.14)!important;border-color:rgba(217,154,43,.3)!important;color:var(--amber-h)!important;}
.bar-fill,.avg-bar-fill{background:linear-gradient(90deg,var(--amber-dark),var(--amber-h))!important;}
.score-bar-bg,.eval-bar-bg,.avg-bar-bg,.bar-wrap{background:rgba(255,255,255,.08)!important;}
.standing-title.top,.standing-score,.pstat-val,.avg-score-big,.avg-score-label{color:#4ade80!important;}
.standing-title.low{color:#f87171!important;}
.person-row:hover,.standing-item:hover{border-color:rgba(217,154,43,.4)!important;background:rgba(217,154,43,.05)!important;}
.comment-text{background:rgba(15,31,61,.7)!important;color:var(--light)!important;}
.empty-note,.no-comment,.no-eval{color:var(--muted)!important;}
label,th{color:var(--muted)!important;}
input,select,textarea{background:#0F1F3D!important;color:var(--light)!important;border-color:rgba(255,255,255,.12)!important;}
input::placeholder,textarea::placeholder{color:#7890a8!important;}
</style>

</body></html>
<?php $mysqli->close(); exit; }

// ══════════════════════════════════════════════════════════════
// VIEW: ARCHIVED PERSONNEL
// ══════════════════════════════════════════════════════════════
if ($view === 'archived') {
    $whereRoleArc = $groupFilter==='Teacher' ? "u.role='teacher'" : ($groupFilter==='Staff' ? "u.role='staff'" : "u.role IN ('teacher','staff')");
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
 --amber:#d99a2b;--amber-h:#f0b84d;--amber-dark:#b8801f;
 --light:#E0E6F0;--muted:#A0B3C6;--border:rgba(255,255,255,.08);
 --accent:#d99a2b;--accent-h:#f0b84d;--good:#10B981;--danger:#f05454;
 --page-bg:#0A192F;--card-bg:rgba(23,42,69,.85);--card-border:rgba(255,255,255,.08);
}
html{background:var(--dark)!important;color-scheme:dark!important;}
body{background:linear-gradient(rgba(5,18,36,.72),rgba(5,18,36,.82)),url('../background.png') center center/cover no-repeat fixed!important;background-color:var(--dark)!important;color:var(--light)!important;}
.main,.content,.page-content{color:var(--light)!important;}
.page-title,.page-header h1,.section-title,.sheet-name,.target-name{color:#fff!important;}
.page-sub,.sheet-desig,.target-desig,.muted,.hint,.helper,.description,p{color:var(--muted)!important;}
.card,.panel,.section,.table-card,.content-card,.stat-card,.sum-card,.standing-panel,.eval-card,.eval-banner,.info-banner,.history-card,.gl-card,.amber-card,.green-card,.red-card,.person-row,.target-card,.no-eval,.no-data,.no-evaluated,.comment-section,.avg-summary,.cat-section,.people-list,.evaluator-grid{
 background:rgba(23,42,69,.85)!important;border-color:rgba(255,255,255,.08)!important;box-shadow:0 8px 32px rgba(0,0,0,.45)!important;color:var(--light)!important;
}
.table-wrap{background:transparent!important;color:var(--light)!important;}
table.data th,table.data td,th,td{color:var(--light)!important;border-color:rgba(255,255,255,.08)!important;}
.q-table{color:var(--light)!important;}
.q-table th{color:var(--muted)!important;background:rgba(15,31,61,.65)!important;border-color:rgba(255,255,255,.08)!important;}
.q-table td{color:var(--light)!important;border-color:rgba(255,255,255,.06)!important;}
.eval-switcher,.tabs,.level-tabs,.status-tabs{background:rgba(23,42,69,.85)!important;border-color:rgba(255,255,255,.08)!important;box-shadow:none!important;}
.eval-tab,.tab,.level-tab,.status-tab,.group-tab,.desig-subtab{color:var(--muted)!important;background:transparent!important;}
.eval-tab:hover,.tab:hover,.level-tab:hover,.status-tab:hover,.group-tab:hover,.desig-subtab:hover{color:#fff!important;background:rgba(217,154,43,.08)!important;}
.eval-tab.student.active,.group-tab.active,.desig-subtab.active{background:rgba(217,154,43,.14)!important;color:var(--amber-h)!important;border-color:rgba(217,154,43,.35)!important;}
.eval-tab.student.active::after{background:var(--amber)!important;}
.eval-tab.peer.active{background:rgba(217,154,43,.14)!important;color:var(--amber-h)!important;}
.eval-tab.peer.active::after{background:var(--amber)!important;}
.eval-tab.multi-role.active{background:rgba(217,154,43,.14)!important;color:var(--amber-h)!important;}
.eval-tab.multi-role.active::after{background:var(--amber)!important;}
.tab-badge{background:rgba(255,255,255,.08)!important;color:var(--muted)!important;}
.eval-tab.student.active .tab-badge,.eval-tab.peer.active .tab-badge,.eval-tab.multi-role.active .tab-badge{background:rgba(217,154,43,.18)!important;color:var(--amber-h)!important;}
.btn-print,.btn-print.no-print,.back-btn,.btn,.action-btn,.btn-archive,.btn-restore,.btn-solid,.btn-archived-link{background:rgba(217,154,43,.12)!important;border:1px solid rgba(217,154,43,.35)!important;color:var(--amber-h)!important;}
.btn-print:hover,.back-btn:hover,.btn:hover,.action-btn:hover,.btn-archive:hover,.btn-restore:hover,.btn-solid:hover,.btn-archived-link:hover{background:rgba(217,154,43,.22)!important;color:#fff!important;}
.btn-solid{background:var(--amber)!important;color:#fff!important;}
.eval-banner{background:rgba(217,154,43,.08)!important;border-color:rgba(217,154,43,.25)!important;}
.eval-banner-title,.eval-banner-icon,.cat-title,.comment-title,.section h2 i,.section h2{color:var(--amber-h)!important;}
.period-badge{background:rgba(217,154,43,.14)!important;border-color:rgba(217,154,43,.3)!important;color:var(--amber-h)!important;}
.bar-fill,.avg-bar-fill{background:linear-gradient(90deg,var(--amber-dark),var(--amber-h))!important;}
.score-bar-bg,.eval-bar-bg,.avg-bar-bg,.bar-wrap{background:rgba(255,255,255,.08)!important;}
.standing-title.top,.standing-score,.pstat-val,.avg-score-big,.avg-score-label{color:#4ade80!important;}
.standing-title.low{color:#f87171!important;}
.person-row:hover,.standing-item:hover{border-color:rgba(217,154,43,.4)!important;background:rgba(217,154,43,.05)!important;}
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
$whereRole = $groupFilter==='Teacher' ? "u.role='teacher'" : ($groupFilter==='Staff' ? "u.role='staff'" : "u.role IN ('teacher','staff')");

$people = [];
$res = $mysqli->query("
    SELECT u.id, u.full_name, u.designation, u.photo, u.role,
           aa.archived_at,
           COUNT(DISTINCT et.id) AS total_responses,
           AVG(qa.answer_score)  AS avg_score
    FROM users u
    JOIN evaluation_tracker et ON et.target_user_id=u.id AND $evalTypeSql
    LEFT JOIN questionnaire_answers qa ON qa.tracker_id=et.id
    LEFT JOIN analytics_archive aa ON aa.target_user_id=u.id
    WHERE $whereRole AND u.is_active=1 AND $reportScopeSql
      AND (aa.id IS NULL OR " . ($activeEval === 'multi_role' ? '1=1' : '0=1') . ")
    GROUP BY u.id
    ORDER BY avg_score DESC, u.full_name ASC
");
if ($res) $people = $res->fetch_all(MYSQLI_ASSOC);

$top4 = array_slice($people, 0, 4);
$low4 = array_slice(array_reverse($people), 0, 4);

$totalResponses = array_sum(array_column($people,'total_responses'));
$scores         = array_filter(array_column($people,'avg_score'), fn($s) => $s !== null);
$overallAvg     = count($scores) ? round(array_sum($scores)/count($scores),2) : null;

$totalStudents = $mysqli->query("SELECT COUNT(*) as c FROM users us WHERE us.role='student' AND us.is_active=1 AND $reportStudentScopeSql")->fetch_assoc()['c'] ?? 0;
$totalFacStaff = $mysqli->query("SELECT COUNT(*) as c FROM users u WHERE u.role IN ('teacher','staff','faculty') AND u.is_active=1 AND $reportScopeSql")->fetch_assoc()['c'] ?? 0;
$facCount      = $mysqli->query("SELECT COUNT(*) as c FROM users u WHERE u.role IN ('teacher','faculty') AND u.is_active=1 AND $reportScopeSql")->fetch_assoc()['c'] ?? 0;
$staffCount    = $mysqli->query("SELECT COUNT(*) as c FROM users u WHERE u.role='staff' AND u.is_active=1 AND $reportScopeSql")->fetch_assoc()['c'] ?? 0;
if ($activeEval === 'multi_role') {
    // Multi-Role tab counts must represent the CURRENT Multi-Role personnel,
    // not only people who already have a submitted evaluation. Keep the
    // definition identical to Questionnaire / Student Evaluation by using the
    // shared additional-role rule.
    $facCount = 0;
    $staffCount = 0;
    $mrUsers = $mysqli->query("SELECT id, role, secondary_role, designation, source, account_status, is_active
        FROM users u
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
$archivedCount = $mysqli->query("SELECT COUNT(*) as c FROM analytics_archive aa JOIN users u ON u.id=aa.target_user_id WHERE $reportScopeSql")->fetch_assoc()['c'] ?? 0;

// Count evaluations per eval_type for tab badges
$studentEvalCount = $mysqli->query("SELECT COUNT(DISTINCT et.id) as c FROM evaluation_tracker et JOIN users u ON u.id=et.target_user_id WHERE et.eval_type='student' AND COALESCE(et.evaluation_context,'teacher') IN ('teacher','staff') AND $reportScopeSql")->fetch_assoc()['c'] ?? 0;
$multiRoleEvalCount = $mysqli->query("SELECT COUNT(DISTINCT et.id) AS c
    FROM evaluation_tracker et
    JOIN users u ON u.id=et.target_user_id
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
    ) AND $reportScopeSql")->fetch_assoc()['c'] ?? 0;
$peerEvalCount    = $mysqli->query("SELECT COUNT(DISTINCT et.id) as c FROM evaluation_tracker et JOIN users u ON u.id=et.target_user_id WHERE et.eval_type IN ('peer','faculty_peer','staff_peer') AND $reportScopeSql")->fetch_assoc()['c'] ?? 0;

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
</style>
</head><body>
<?php render_exec_sidebar('reports', $me, $photo_src, $principalScopeLabel); ?>
<main class="main">


<?php if ($toast): ?><div class="toast"><i class="fa-solid fa-circle-check"></i><?= htmlspecialchars($toast) ?></div><?php endif; ?>

<!-- ── EVAL TYPE SWITCHER ── -->
<div class="eval-switcher">
    <a href="?group=<?= urlencode($groupFilter) ?>&eval_type=student"
       class="eval-tab student <?= $activeEval==='student'?'active':'' ?>">
        <i class="fa-solid fa-graduation-cap"></i> Student Evaluation
        <span class="tab-badge"><?= $studentEvalCount ?></span>
    </a>
    <div class="eval-divider"></div>
    <a href="?group=<?= urlencode($groupFilter) ?>&eval_type=multi_role"
       class="eval-tab multi-role <?= $activeEval==='multi_role'?'active':'' ?>">
        <i class="fa-solid fa-people-group"></i> Multi-Role
        <span class="tab-badge"><?= $multiRoleEvalCount ?></span>
    </a>
    <div class="eval-divider"></div>
    <a href="?group=<?= urlencode($groupFilter) ?>&eval_type=peer"
       class="eval-tab peer <?= $activeEval==='peer'?'active':'' ?>">
        <i class="fa-solid fa-people-arrows"></i> Peer-to-Peer
        <span class="tab-badge"><?= $peerEvalCount ?></span>
    </a>
</div>

<div class="page-header">
    <div>
        <h1>Reports &amp; Analytics <span style="font-size:18px;color:var(--ec);font-family:'Inter',sans-serif;font-weight:400;margin-left:6px;">— <?= $evalLabel ?></span></h1>
        <p>Only showing personnel who have been evaluated<?= $activeEval==='peer'?' by colleagues':' by students' ?><?= $activeEval==='multi_role'?' in their Multi-Role capacity':'' ?></p>
    </div>
    <div class="header-actions">
        <a href="?view=archived&group=<?= urlencode($groupFilter) ?>&eval_type=<?= $activeEval ?>" class="btn-archived-link"> 
            <i class="fa-solid fa-box-archive"></i> Archived
            <?php if ($archivedCount > 0): ?><span class="archived-count-badge"><?= $archivedCount ?></span><?php endif; ?>
        </a>
        <button class="btn-print" onclick="window.print()"><i class="fa-solid fa-print"></i> Print / Save PDF</button>
    </div>
</div>

<?php if ($activeEval === 'multi_role'): ?>
<div class="peer-info-note" style="background:rgba(245,158,11,.06);border-color:rgba(245,158,11,.18);">
    <i class="fa-solid fa-people-group" style="color:#F59E0B;"></i>
    <div><strong style="color:#F59E0B;display:block;margin-bottom:2px;">Multi-Role Evaluations</strong>
    These results are only for evaluations where the person was evaluated in their Multi-Role capacity. They are kept completely separate from the same person's Teacher or Staff evaluations.</div>
</div>
<?php elseif ($activeEval === 'peer'): ?>
<div class="peer-info-note">
    <i class="fa-solid fa-circle-info"></i>
    <div><strong style="color:#7C3AED;display:block;margin-bottom:2px;">Peer-to-Peer Evaluations</strong>
    These results show how teacher and staff rated their colleagues. Evaluations are submitted by fellow personnel, not students. Questions used are from the Peer-to-Peer question bank.</div>
</div>
<?php endif; ?>

<!-- GROUP TABS -->
<div class="group-tabs">
    <a href="?group=All&eval_type=<?= $activeEval ?>"     class="group-tab <?= $groupFilter==='All'?'active-all':'' ?>"><i class="fa-solid fa-users"></i> All <span class="tab-count"><?= $facCount+$staffCount ?></span></a>
    <a href="?group=Teacher&eval_type=<?= $activeEval ?>" class="group-tab <?= $groupFilter==='Teacher'?'active-teacher':'' ?>"><i class="fa-solid fa-chalkboard-user"></i> Teacher <span class="tab-count"><?= $facCount ?></span></a>
    <a href="?group=Staff&eval_type=<?= $activeEval ?>"   class="group-tab <?= $groupFilter==='Staff'?'active-staff':'' ?>"><i class="fa-solid fa-briefcase"></i> Staff <span class="tab-count"><?= $staffCount ?></span></a>
</div>

<?php if ($activeEval !== 'multi_role' && $groupFilter === 'Staff' && !empty($staffDesigCounts)): ?>
<div class="desig-subtabs">
    <span style="font-size:11px;color:var(--muted);font-weight:700;text-transform:uppercase;letter-spacing:.8px;margin-right:4px;">By Role:</span>
    <?php foreach ($staffDesigCounts as $desig => $cnt): ?>
    <a href="#" class="desig-subtab" onclick="filterByDesig('<?= htmlspecialchars(addslashes($desig)) ?>');return false;"><?= htmlspecialchars($desig) ?> <span style="opacity:.6">(<?= $cnt ?>)</span></a>
    <?php endforeach; ?>
    <a href="#" class="desig-subtab active" onclick="filterByDesig('all');return false;">Show All</a>
</div>
<?php endif; ?>

<!-- SUMMARY CARDS -->
<div class="summary-row">
    <div class="sum-card">
        <div class="sum-label">Total <?= $groupFilter==='All'?'Teacher & Staff':$groupFilter ?></div>
        <div class="sum-value"><?= $totalFacStaff ?></div>
        <div class="sum-sub"><?= count($people) ?> have been evaluated</div>
    </div>
    <div class="sum-card">
        <div class="sum-label">Total <?= $evalLabel ?> Submissions</div>
        <div class="sum-value"><?= number_format($totalResponses) ?></div>
        <div class="sum-sub">by <?= $evaluatorNounP ?></div>
    </div>
    <div class="sum-card highlight">
        <div class="sum-label">Overall Avg. Score</div>
        <div class="sum-value"><?= $overallAvg !== null ? number_format($overallAvg,2) : '—' ?></div>
        <div class="sum-sub"><?= scoreLabel($overallAvg) ?> · out of 5.00</div>
    </div>
    <div class="sum-card">
        <div class="sum-label"><?= $activeEval==='peer'?'Total Personnel':'Students' ?></div>
        <div class="sum-value"><?= $activeEval==='peer' ? $totalFacStaff : $totalStudents ?></div>
        <div class="sum-sub"><?= $activeEval==='peer'?'eligible evaluators':'registered' ?></div>
    </div>
</div>

<!-- STANDINGS -->
<?php if (!empty($top4) || !empty($low4)): ?>
<div class="standings-row">
    <div class="standing-panel">
        <div class="standing-title top"><i class="fa-solid fa-trophy"></i> Top Performers</div>
        <?php if (empty($top4)): ?>
        <div class="no-data"><i class="fa-solid fa-chart-simple" style="font-size:28px;opacity:.3;display:block;margin-bottom:10px"></i>No data yet.</div>
        <?php else: foreach ($top4 as $i => $p): ?>
        <div class="standing-item">
            <div class="standing-rank"><?= $i+1 ?></div>
            <?php if($p['photo']): ?><img class="standing-photo" src="../image/<?= htmlspecialchars($p['photo']) ?>" alt=""/>
            <?php else: ?><div class="standing-photo-ph"><i class="fa-solid fa-user"></i></div><?php endif; ?>
            <div class="standing-info">
                <div class="standing-name"><?= htmlspecialchars($p['full_name']) ?></div>
                <div class="standing-desig"><?= $p['role']==='teacher'?'teacher':'Staff' ?> · <?= htmlspecialchars($p['designation']) ?></div>
            </div>
            <div class="standing-score score-top"><?= number_format($p['avg_score'],2) ?></div>
        </div>
        <?php endforeach; endif; ?>
    </div>
    <div class="standing-panel">
        <div class="standing-title low"><i class="fa-solid fa-arrow-trend-up"></i> Areas for Improvement</div>
        <?php if (empty($low4)): ?>
        <div class="no-data"><i class="fa-solid fa-chart-simple" style="font-size:28px;opacity:.3;display:block;margin-bottom:10px"></i>No data yet.</div>
        <?php else: foreach ($low4 as $i => $p): ?>
        <div class="standing-item">
            <div class="standing-rank"><?= $i+1 ?></div>
            <?php if($p['photo']): ?><img class="standing-photo" src="../image/<?= htmlspecialchars($p['photo']) ?>" alt=""/>
            <?php else: ?><div class="standing-photo-ph"><i class="fa-solid fa-user"></i></div><?php endif; ?>
            <div class="standing-info">
                <div class="standing-name"><?= htmlspecialchars($p['full_name']) ?></div>
                <div class="standing-desig"><?= $p['role']==='teacher'?'Teacher':'Staff' ?> · <?= htmlspecialchars($p['designation']) ?></div>
            </div>
            <div class="standing-score score-low"><?= number_format($p['avg_score'],2) ?></div>
        </div>
        <?php endforeach; endif; ?>
    </div>
</div>
<?php endif; ?>

<!-- PEOPLE LIST -->
<div class="section-title">
    <i class="fa-solid fa-list-check" style="color:var(--accent)"></i>
    Evaluated Personnel
    <span style="font-size:13px;font-weight:400;color:var(--muted)">(<?= count($people) ?> evaluated)</span>
</div>

<?php if (empty($people)): ?>
<div class="no-evaluated">
    <i class="fa-solid fa-hourglass-half"></i>
    <p>No <?= $activeEval==='peer'?'peer-to-peer':($activeEval==='multi_role'?'Multi-Role':'student') ?> evaluations have been submitted yet.<br>
    <small style="font-size:12px;opacity:.6">Personnel will appear here once <?= $evaluatorNounP ?> have evaluated them.</small></p>
</div>
<?php else: ?>
<div class="people-list" id="peopleList">
<?php foreach ($people as $p):
    $avg   = $p['avg_score'] !== null ? round($p['avg_score'],2) : null;
    $pct   = $avg !== null ? ($avg/5)*100 : 0;
    $color = scoreColor($avg);
    $cls   = $avg === null ? '' : ($avg >= 4 ? 'good' : ($avg >= 3 ? 'mid' : 'poor'));
    $isFac = $p['role'] === 'teacher';
    $isArchived = !empty($p['archived_at']);
?>
<div class="person-row" data-desig="<?= htmlspecialchars($p['designation']) ?>">
    <div class="person-header">
        <a class="person-link" href="?view=students&target_id=<?= $p['id'] ?>&group=<?= urlencode($groupFilter) ?>&eval_type=<?= $activeEval ?>">
            <?php if($p['photo']): ?><img class="person-photo" src="../image/<?= htmlspecialchars($p['photo']) ?>" alt=""/>
            <?php else: ?><div class="person-photo-ph"><i class="fa-solid fa-user"></i></div><?php endif; ?>
            <div class="person-info">
                <div class="person-name"><?= htmlspecialchars($p['full_name']) ?></div>
                <div class="person-meta">
                    <span class="group-badge <?= $activeEval==='multi_role' ? 'staff' : ($isFac?'teacher':'staff') ?>" style="<?= $activeEval==='multi_role' ? 'background:rgba(245,158,11,.15);color:#F59E0B;border-color:rgba(245,158,11,.3);' : '' ?>">
                        <i class="fa-solid <?= $activeEval==='multi_role' ? 'fa-people-group' : ($isFac?'fa-chalkboard-user':'fa-briefcase') ?>"></i>
                        <?= $activeEval==='multi_role' ? 'Multi-Role' : ($isFac?'Teacher':'Staff') ?>
                    </span>
                    <span class="desig-pill"><?= htmlspecialchars($p['designation']) ?></span>
                    <?php if ($isArchived && $activeEval === 'multi_role'): ?>
                    <span class="desig-pill" style="background:rgba(100,116,139,.10);color:#64748B;border-color:rgba(100,116,139,.25);"><i class="fa-solid fa-box-archive"></i> Archived</span>
                    <?php endif; ?>
                    <span style="font-size:12px;color:var(--muted)"><?= $p['total_responses'] ?> <?= $evaluatorNoun ?><?= $p['total_responses']!=1?'s':'' ?> evaluated</span>
                </div>
            </div>
            <div class="person-stats">
                <div class="pstat">
                    <div class="pstat-val <?= $cls ?>"><?= $avg !== null ? number_format($avg,2) : '—' ?></div>
                    <div class="pstat-lbl">Avg Score</div>
                </div>
                <div class="pstat" style="min-width:70px">
                    <div class="pstat-val" style="font-size:13px;color:<?= $color ?>"><?= scoreLabel($avg) ?></div>
                    <div class="pstat-lbl">Rating</div>
                </div>
                <div class="score-bar-wrap">
                    <div class="score-bar-bg"><div class="score-bar-fill" style="width:<?= $pct ?>%;background:<?= $color ?>"></div></div>
                    <span style="font-size:11px;color:var(--muted);width:30px"><?= $avg!==null?number_format($pct,0).'%':'—' ?></span>
                </div>
            </div>
            <i class="fa-solid fa-chevron-right arrow-icon"></i>
        </a>
        <div class="person-actions">
            <?php if ($isArchived && $activeEval === 'multi_role'): ?>
            <a class="btn-archive" href="?restore_id=<?= $p['id'] ?>&group=<?= urlencode($groupFilter) ?>&eval_type=<?= $activeEval ?>&view=archived" style="text-decoration:none;">
                <i class="fa-solid fa-rotate-left"></i> <span>Restore</span>
            </a>
            <?php else: ?>
            <button class="btn-archive" onclick="archivePerson(<?= $p['id'] ?>,'<?= htmlspecialchars(addslashes($p['full_name'])) ?>')">
                <i class="fa-solid fa-box-archive"></i> <span>Archive</span>
            </button>
            <?php endif; ?>
        </div>
    </div>
</div>
<?php endforeach; ?>
</div>
<?php endif; ?>

<script>
function filterByDesig(desig) {
    document.querySelectorAll('.desig-subtabs .desig-subtab').forEach(t => t.classList.remove('active'));
    event.target.closest('.desig-subtab').classList.add('active');
    document.querySelectorAll('#peopleList .person-row').forEach(row => {
        row.style.display = (desig==='all' || row.dataset.desig===desig) ? '' : 'none';
    });
}
function archivePerson(id, name) {
    if (confirm(`Archive "${name}"? They'll be hidden from this list but their evaluation data is kept and can be restored anytime.`)) {
        window.location.href = `?archive_id=${id}&group=<?= urlencode($groupFilter) ?>&eval_type=<?= $activeEval ?>`;
    }
}
</script>
<?php $mysqli->close(); ?>

<style id="executive-original-theme">
:root{
 --dark:#0A192F;--mid:#172A45;--inner:#0F1F3D;
 --amber:#d99a2b;--amber-h:#f0b84d;--amber-dark:#b8801f;
 --light:#E0E6F0;--muted:#A0B3C6;--border:rgba(255,255,255,.08);
 --accent:#d99a2b;--accent-h:#f0b84d;--good:#10B981;--danger:#f05454;
 --page-bg:#0A192F;--card-bg:rgba(23,42,69,.85);--card-border:rgba(255,255,255,.08);
}
html{background:var(--dark)!important;color-scheme:dark!important;}
body{background:linear-gradient(rgba(5,18,36,.72),rgba(5,18,36,.82)),url('../background.png') center center/cover no-repeat fixed!important;background-color:var(--dark)!important;color:var(--light)!important;}
.main,.content,.page-content{color:var(--light)!important;}
.page-title,.page-header h1,.section-title,.sheet-name,.target-name{color:#fff!important;}
.page-sub,.sheet-desig,.target-desig,.muted,.hint,.helper,.description,p{color:var(--muted)!important;}
.card,.panel,.section,.table-card,.content-card,.stat-card,.sum-card,.standing-panel,.eval-card,.eval-banner,.info-banner,.history-card,.gl-card,.amber-card,.green-card,.red-card,.person-row,.target-card,.no-eval,.no-data,.no-evaluated,.comment-section,.avg-summary,.cat-section,.people-list,.evaluator-grid{
 background:rgba(23,42,69,.85)!important;border-color:rgba(255,255,255,.08)!important;box-shadow:0 8px 32px rgba(0,0,0,.45)!important;color:var(--light)!important;
}
.table-wrap{background:transparent!important;color:var(--light)!important;}
table.data th,table.data td,th,td{color:var(--light)!important;border-color:rgba(255,255,255,.08)!important;}
.q-table{color:var(--light)!important;}
.q-table th{color:var(--muted)!important;background:rgba(15,31,61,.65)!important;border-color:rgba(255,255,255,.08)!important;}
.q-table td{color:var(--light)!important;border-color:rgba(255,255,255,.06)!important;}
.eval-switcher,.tabs,.level-tabs,.status-tabs{background:rgba(23,42,69,.85)!important;border-color:rgba(255,255,255,.08)!important;box-shadow:none!important;}
.eval-tab,.tab,.level-tab,.status-tab,.group-tab,.desig-subtab{color:var(--muted)!important;background:transparent!important;}
.eval-tab:hover,.tab:hover,.level-tab:hover,.status-tab:hover,.group-tab:hover,.desig-subtab:hover{color:#fff!important;background:rgba(217,154,43,.08)!important;}
.eval-tab.student.active,.group-tab.active,.desig-subtab.active{background:rgba(217,154,43,.14)!important;color:var(--amber-h)!important;border-color:rgba(217,154,43,.35)!important;}
.eval-tab.student.active::after{background:var(--amber)!important;}
.eval-tab.peer.active{background:rgba(217,154,43,.14)!important;color:var(--amber-h)!important;}
.eval-tab.peer.active::after{background:var(--amber)!important;}
.eval-tab.multi-role.active{background:rgba(217,154,43,.14)!important;color:var(--amber-h)!important;}
.eval-tab.multi-role.active::after{background:var(--amber)!important;}
.tab-badge{background:rgba(255,255,255,.08)!important;color:var(--muted)!important;}
.eval-tab.student.active .tab-badge,.eval-tab.peer.active .tab-badge,.eval-tab.multi-role.active .tab-badge{background:rgba(217,154,43,.18)!important;color:var(--amber-h)!important;}
.btn-print,.btn-print.no-print,.back-btn,.btn,.action-btn,.btn-archive,.btn-restore,.btn-solid,.btn-archived-link{background:rgba(217,154,43,.12)!important;border:1px solid rgba(217,154,43,.35)!important;color:var(--amber-h)!important;}
.btn-print:hover,.back-btn:hover,.btn:hover,.action-btn:hover,.btn-archive:hover,.btn-restore:hover,.btn-solid:hover,.btn-archived-link:hover{background:rgba(217,154,43,.22)!important;color:#fff!important;}
.btn-solid{background:var(--amber)!important;color:#fff!important;}
.eval-banner{background:rgba(217,154,43,.08)!important;border-color:rgba(217,154,43,.25)!important;}
.eval-banner-title,.eval-banner-icon,.cat-title,.comment-title,.section h2 i,.section h2{color:var(--amber-h)!important;}
.period-badge{background:rgba(217,154,43,.14)!important;border-color:rgba(217,154,43,.3)!important;color:var(--amber-h)!important;}
.bar-fill,.avg-bar-fill{background:linear-gradient(90deg,var(--amber-dark),var(--amber-h))!important;}
.score-bar-bg,.eval-bar-bg,.avg-bar-bg,.bar-wrap{background:rgba(255,255,255,.08)!important;}
.standing-title.top,.standing-score,.pstat-val,.avg-score-big,.avg-score-label{color:#4ade80!important;}
.standing-title.low{color:#f87171!important;}
.person-row:hover,.standing-item:hover{border-color:rgba(217,154,43,.4)!important;background:rgba(217,154,43,.05)!important;}
.comment-text{background:rgba(15,31,61,.7)!important;color:var(--light)!important;}
.empty-note,.no-comment,.no-eval{color:var(--muted)!important;}
label,th{color:var(--muted)!important;}
input,select,textarea{background:#0F1F3D!important;color:var(--light)!important;border-color:rgba(255,255,255,.12)!important;}
input::placeholder,textarea::placeholder{color:#7890a8!important;}
</style>

</body>
</html>