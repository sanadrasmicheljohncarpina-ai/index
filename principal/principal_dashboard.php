<?php
// session_bootstrap.php — include this BEFORE session_start() everywhere
session_set_cookie_params([
    'lifetime' => 0,        // session cookie, dies when browser closes
    'path'     => '/',      // available across the whole site, not just /admin/
    'domain'   => '',       // let the browser infer it — avoids localhost vs IP mismatches
    'secure'   => false,    // set true only if you're on https
    'httponly' => true,
    'samesite' => 'Lax',
]);
session_start();
require_once 'db.php';
require_once dirname(__DIR__) . '/shared/system_settings_service.php';
require_once 'principal_notifications_feed.php';   // safe_scalar(), esc_list(), principal_build_notifications()

// ── AUTH GUARD ────────────────────────────────────────────
if (empty($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'principal') {
    header("Location: principal_login.php");
    exit;
}

/* =========================================================================
   OVERVIEW-ONLY DASHBOARD
   -------------------------------------------------------------------------
   This page is deliberately a landing/summary page. Detail lives on the
   dedicated pages and is NOT duplicated here:

     Grade-level analytics ............ principal_reports.php
     Teacher lists / rankings ......... principal_teachers.php, principal_reports.php
     Staff breakdown .................. principal_staff.php
     Live monitoring / countdown ...... principal_evaluation_tracker.php
     Report generation ................ principal_reports.php
     My own results ................... principal_results.php

   Anything added here should answer "what's the state of my division right
   now?" in one glance. If it needs a table, it belongs on another page.
   ========================================================================= */

// ── SELF-HEALING SCHEMA (matches your ALTER-TABLE-on-load pattern) ─────
@$mysqli->query("ALTER TABLE users ADD COLUMN IF NOT EXISTS academic_level VARCHAR(20) NULL");
@$mysqli->query("ALTER TABLE users ADD COLUMN IF NOT EXISTS grade_level VARCHAR(10) NULL");

// ── PULL PRINCIPAL PROFILE ────────────────────────────────
$stmt = $mysqli->prepare("SELECT full_name, username, email, designation, photo, education_level FROM users WHERE id = ? LIMIT 1");
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$me = $stmt->get_result()->fetch_assoc();
$stmt->close();

// ── SCOPE (Principal must never see College) ────────────────
$myLevel = $me['education_level'] ?? 'both';
if ($myLevel === 'junior_high') {
    $scopeAcademicLevels = ['junior_high'];
    $scopeGrades = ['7', '8', '9', '10'];
} elseif ($myLevel === 'senior_high') {
    $scopeAcademicLevels = ['senior_high'];
    $scopeGrades = ['11', '12'];
} else {
    $scopeAcademicLevels = ['junior_high', 'senior_high'];
    $scopeGrades = ['7', '8', '9', '10', '11', '12'];
}
$scopeAcademicIn = esc_list($mysqli, $scopeAcademicLevels);
$scopeGradesIn   = esc_list($mysqli, $scopeGrades);

// ── GLOBAL SYSTEM SETTINGS (single source of truth) ─────────────────────
// Everything about "what period is it, what's the status, what's the
// schedule" comes from here. The Principal dashboard never derives or
// stores any of this itself, and never hardcodes an academic year/term —
// same convention as dean_dashboard.php (Step 14: Synchronization with
// System Settings).
$settings = get_system_settings($mysqli);

// A Principal oversees the Basic Education (Junior High / Senior High)
// division. If the Executive Assistant/Admin has the active Academic
// Structure set to College, the Principal must not show Basic Ed
// analytics as current.
$structureActive = ($settings['academic_structure'] !== 'college');
$period_id_int   = $settings['period_id'] ?? 0;
$evalOpen        = $settings['is_open_for_submission'];
$hasPeriod       = $period_id_int > 0;

// Step 8 (mirrored from Dean): internal value stays whatever admin uses;
// this is the only display label used in markup below.
const BASIC_ED_LABEL = 'Basic Education';

$daysRemaining = null;
if ($settings['eval_end']) {
    $diff = (strtotime($settings['eval_end']) - strtotime(date('Y-m-d')));
    $daysRemaining = (int)ceil($diff / 86400);
}

// ── HEADLINE STATS ONLY ────────────────────────────────────
// Six numbers, nothing per-person and nothing per-grade. The old page ran an
// N+1 query per teacher and per grade here; all of that moved to the pages
// that actually display it.
$teacherCount = $staffCount = $studentCount = 0;
$studentsSubmitted = $targetsEvaluated = 0;
$totalTargets = $evaluationCompletion = $pendingEvaluations = 0;
$studentParticipation = 0;

if ($structureActive) {
    $teacherCount = (int)(safe_scalar($mysqli, "
        SELECT COUNT(*) c FROM users
        WHERE role='teacher' AND is_active=1 AND account_status='approved'
          AND academic_level IN ($scopeAcademicIn)
    ") ?? 0);

    $staffCount = (int)(safe_scalar($mysqli, "
        SELECT COUNT(*) c FROM users
        WHERE role='staff' AND is_active=1 AND account_status='approved'
          AND academic_level IN ($scopeAcademicIn)
    ") ?? 0);

    $studentCount = (int)(safe_scalar($mysqli, "
        SELECT COUNT(*) c FROM users
        WHERE role='student' AND is_active=1 AND account_status='approved'
          AND grade_level IN ($scopeGradesIn)
    ") ?? 0);

    if ($hasPeriod) {
        $studentsSubmitted = (int)(safe_scalar($mysqli, "
            SELECT COUNT(DISTINCT et.evaluator_id) c
            FROM evaluation_tracker et
            INNER JOIN users u ON u.id = et.evaluator_id
            WHERE et.eval_type='student' AND et.period_id=?
              AND u.role='student' AND u.grade_level IN ($scopeGradesIn)
        ", "i", [$period_id_int]) ?? 0);

        $targetsEvaluated = (int)(safe_scalar($mysqli, "
            SELECT COUNT(DISTINCT et.target_user_id) c
            FROM evaluation_tracker et
            INNER JOIN users u ON u.id = et.target_user_id
            WHERE et.eval_type='student' AND et.period_id=?
              AND u.role IN ('teacher','staff') AND u.academic_level IN ($scopeAcademicIn)
        ", "i", [$period_id_int]) ?? 0);
    }

    $totalTargets          = $teacherCount + $staffCount;
    $evaluationCompletion  = $totalTargets > 0 ? round($targetsEvaluated / $totalTargets * 100) : 0;
    $pendingEvaluations    = max(0, $totalTargets - $targetsEvaluated);
    $studentParticipation  = $studentCount > 0 ? round($studentsSubmitted / $studentCount * 100) : 0;
}

$photo_src = !empty($me['photo']) ? '../image/' . $me['photo'] : '../image/pbi_logo';

// ── NOTIFICATIONS ─────────────────────────────────────────────────────
// Built by the shared feed so the initial render and the polled JSON can
// never disagree. Must run BEFORE $mysqli->close().
$notifications = principal_build_notifications($mysqli, (int)$_SESSION['user_id'], $settings);

$mysqli->close();

$scopeLabel = $myLevel === 'both' ? 'Junior High & Senior High' : ($myLevel === 'junior_high' ? 'Junior High School' : 'Senior High School');
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"/>
<meta name="viewport" content="width=device-width, initial-scale=1.0"/>
<title>PBI — Principal Dashboard</title>
<link href="https://fonts.googleapis.com/css2?family=Rajdhani:wght@600;700&family=DM+Sans:wght@400;500;600&display=swap" rel="stylesheet"/>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css"/>
<style>
:root{--dark:#0A192F;--mid:#172A45;--inner:#0F1F3D;--amber:#d99a2b;--amber-h:#f0b84d;--amber-dark:#b8801f;--light:#E0E6F0;--muted:#A0B3C6;--radius:10px;--shadow:0 8px 32px rgba(0,0,0,0.45);--danger:#f05454;--good:#10B981;}
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0;}
body{min-height:100vh;background:linear-gradient(rgba(5,18,36,.72),rgba(5,18,36,.82)),url('../background.png') center center / cover no-repeat fixed;background-color:var(--dark);font-family:'DM Sans',sans-serif;color:var(--light);display:flex;}

/* SIDEBAR */
.sidebar{width:250px;flex-shrink:0;background:rgba(23,42,69,.9);border-right:1px solid rgba(255,255,255,.08);min-height:100vh;padding:28px 20px;display:flex;flex-direction:column;}
.sb-profile{text-align:center;margin-bottom:26px;}
.sb-photo{width:72px;height:72px;border-radius:50%;object-fit:cover;border:2.5px solid var(--amber);box-shadow:0 0 18px rgba(217,154,43,.4);margin:0 auto 10px;display:block;}
.sb-name{font-weight:700;font-size:15px;color:#fff;}
.sb-role{font-size:11px;color:var(--amber-h);text-transform:uppercase;letter-spacing:.6px;margin-top:2px;}
.sb-scope{font-size:10px;color:var(--muted);margin-top:4px;}
.sb-nav{display:flex;flex-direction:column;gap:5px;margin-top:10px;width:100%;}
.sb-nav-section-label{width:auto;margin:5px 12px 1px;padding:0 2px;color:var(--muted);font-size:10px;font-weight:800;letter-spacing:1.35px;line-height:1.2;text-transform:uppercase;}
.sb-nav-section-label:first-child{margin-top:0;}
.sb-nav a{box-sizing:border-box;width:100%;min-height:42px;margin:0;padding:5px 14px;display:flex;align-items:center;gap:10px;border-radius:8px;color:var(--muted);text-decoration:none;font-size:14px;font-weight:500;transition:background .2s,color .2s;}
.sb-nav a:hover,.sb-nav a.active{background:rgba(217,154,43,.15);color:#fff;}
.sb-nav a i{width:18px;flex:0 0 18px;text-align:center;color:var(--amber-h);}
.sb-nav .sb-logout-link{color:#fca5a5;}
.sb-nav .sb-logout-link:hover{background:rgba(240,84,84,.12);color:#fecaca;}
.sb-logout{margin-top:6px;}
.sb-logout a{display:flex;align-items:center;gap:10px;padding:11px 14px;border-radius:8px;color:#fca5a5;text-decoration:none;font-size:14px;font-weight:500;transition:background .2s;}
.sb-logout a:hover{background:rgba(240,84,84,.12);}

/* MAIN */
.main{flex:1;padding:36px 44px;max-width:1280px;}
.page-header{position:relative;}
.page-header{display:flex;justify-content:space-between;align-items:center;margin-bottom:26px;flex-wrap:wrap;gap:14px;}
.page-title{font-family:'Rajdhani',sans-serif;font-size:28px;font-weight:700;color:#fff;letter-spacing:1px;}
.page-sub{font-size:13px;color:var(--muted);margin-top:4px;}

/* PERIOD STRIP — one compact line instead of a full panel */
.period-strip{display:flex;flex-wrap:wrap;align-items:center;gap:26px;background:rgba(23,42,69,.85);border:1px solid rgba(255,255,255,.08);border-radius:14px;padding:16px 22px;box-shadow:var(--shadow);margin-bottom:22px;}
.period-item{display:flex;flex-direction:column;gap:3px;}
.period-item .k{font-size:10px;font-weight:700;color:var(--muted);text-transform:uppercase;letter-spacing:.6px;}
.period-item .v{font-size:14px;font-weight:700;color:#fff;}
.period-note{flex:1 1 100%;font-size:12px;color:var(--muted);}
.period-note strong{color:var(--light);}

.card-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(190px,1fr));gap:16px;margin-bottom:24px;}
.stat-card{background:rgba(23,42,69,.85);border:1px solid rgba(255,255,255,.08);border-radius:14px;padding:20px;box-shadow:var(--shadow);height:100%;transition:border-color .2s,transform .2s;}
a.stat-link{text-decoration:none;color:inherit;display:block;}
a.stat-link:hover .stat-card{border-color:rgba(217,154,43,.45);transform:translateY(-2px);}
.stat-card i{color:var(--amber-h);font-size:20px;margin-bottom:10px;}
.stat-card .num{font-size:26px;font-weight:700;color:#fff;}
.stat-card .label{font-size:12px;color:var(--muted);margin-top:4px;}

.section{background:rgba(23,42,69,.85);border:1px solid rgba(255,255,255,.08);border-radius:14px;padding:24px;box-shadow:var(--shadow);margin-bottom:22px;}
.section h2{font-family:'Rajdhani',sans-serif;font-size:19px;color:#fff;margin-bottom:16px;display:flex;align-items:center;gap:8px;}
.section h2 i{color:var(--amber-h);font-size:16px;}

/* NOTIFICATION BELL — header-right, flush to the page edge */
.header-right{display:flex;align-items:center;gap:12px;margin-left:auto;}
.bell{position:relative;}
.bell-btn{position:relative;width:42px;height:42px;border-radius:12px;background:rgba(23,42,69,.9);border:1px solid rgba(255,255,255,.1);color:var(--amber-h);font-size:16px;cursor:pointer;display:flex;align-items:center;justify-content:center;transition:background .2s,border-color .2s;}
.bell-btn:hover,.bell-btn[aria-expanded="true"]{background:rgba(217,154,43,.18);border-color:rgba(217,154,43,.45);}
.bell-count{position:absolute;top:-6px;right:-6px;min-width:19px;height:19px;padding:0 5px;border-radius:10px;background:var(--danger);color:#fff;font-size:10px;font-weight:700;display:none;align-items:center;justify-content:center;border:2px solid var(--dark);}
.bell-count.show{display:flex;}
.bell-btn.pulse{animation:bellPulse 1.1s ease-in-out 2;}
@keyframes bellPulse{0%,100%{transform:scale(1);}35%{transform:scale(1.14);}}

.bell-panel{position:absolute;top:calc(100% + 10px);right:0;width:340px;max-height:60vh;overflow-y:auto;background:rgba(15,31,61,.98);border:1px solid rgba(255,255,255,.12);border-radius:14px;box-shadow:0 18px 44px rgba(0,0,0,.55);padding:8px;z-index:60;display:none;}
.bell-panel.open{display:block;}
.bell-head{display:flex;align-items:center;justify-content:space-between;padding:8px 10px 10px;border-bottom:1px solid rgba(255,255,255,.08);margin-bottom:6px;}
.bell-head h3{font-family:'Rajdhani',sans-serif;font-size:16px;color:#fff;font-weight:700;}
.bell-head button{background:none;border:none;color:var(--amber-h);font-size:11px;font-weight:600;cursor:pointer;text-transform:uppercase;letter-spacing:.4px;}
.bell-head button:hover{text-decoration:underline;}
.bell-list{list-style:none;font-size:13px;}
.bell-list li{padding:10px 11px;border-radius:9px;display:flex;align-items:flex-start;gap:10px;line-height:1.45;color:var(--light);}
.bell-list li+li{margin-top:4px;}
.bell-list li i{font-size:12px;margin-top:3px;color:var(--amber-h);flex-shrink:0;}
.bell-list li.unseen{background:rgba(217,154,43,.1);}
.bell-list li.lv-warn i{color:#fca5a5;}
.bell-list li.lv-good i{color:var(--good);}
.bell-foot{padding:8px 11px 4px;font-size:10px;color:var(--muted);text-transform:uppercase;letter-spacing:.5px;}

.period-badge{background:rgba(217,154,43,.14);border:1px solid rgba(217,154,43,.3);color:var(--amber-h);padding:6px 14px;border-radius:20px;font-size:12px;font-weight:600;display:inline-flex;align-items:center;gap:7px;}
.period-badge.closed{background:rgba(240,84,84,.1);border-color:rgba(240,84,84,.3);color:#fca5a5;}
.period-badge.amber{background:rgba(217,154,43,.14);border-color:rgba(217,154,43,.3);color:var(--amber-h);}
.period-badge.gray{background:rgba(255,255,255,.08);border-color:rgba(255,255,255,.12);color:var(--muted);}
.empty-note{color:var(--muted);font-size:13px;font-style:italic;}

.structure-note{display:flex;align-items:flex-start;gap:14px;padding:18px 20px;background:rgba(217,154,43,.08);border:1px solid rgba(217,154,43,.25);border-radius:12px;margin-bottom:22px;}
.structure-note i{color:var(--amber-h);font-size:20px;margin-top:2px;}
.structure-note p{font-size:13px;color:var(--light);line-height:1.6;}
.structure-note p b{color:#fff;}

.stub-note{font-size:11px;color:var(--amber-h);background:rgba(217,154,43,.08);border:1px dashed rgba(217,154,43,.35);border-radius:8px;padding:8px 12px;margin-bottom:22px;}

@media(max-width:768px){body{flex-direction:column;}.sidebar{width:100%;min-height:auto;}.main{padding:24px 18px;}}
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
</head>
<body>

<aside class="sidebar">
    <div class="sb-profile">
        <img class="sb-photo" src="<?= htmlspecialchars($photo_src) ?>" alt="Profile"/>
        <div class="sb-name"><?= htmlspecialchars($me['full_name'] ?? 'Principal') ?></div>
        <div class="sb-role"><?= htmlspecialchars($me['designation'] ?? 'Principal') ?></div>
        <div class="sb-scope"><?= htmlspecialchars($scopeLabel) ?></div>
    </div>
    <nav class="sb-nav" aria-label="Principal navigation">
        <div class="sb-nav-section-label">MAIN</div>
        <a href="principal_dashboard.php" class="active"><i class="fa-solid fa-gauge"></i> Dashboard</a>
        <a href="principal_evaluations.php"><i class="fa-solid fa-clipboard-list"></i> Evaluation</a>
        <a href="principal_evaluation_tracker.php"><i class="fa-solid fa-satellite-dish"></i> Evaluation Tracker</a>
        <a href="principal_results.php"><i class="fa-solid fa-star-half-stroke"></i> View Results</a>
        <a href="principal_reports.php"><i class="fa-solid fa-chart-line"></i> Reports</a>

        <div class="sb-nav-section-label">ADMINISTRATION</div>
        <a href="principal_account_settings.php"><i class="fa-solid fa-gear"></i> Settings</a>

        <div class="sb-nav-section-label">ACCOUNT</div>
        <a href="../logout.php" class="sb-logout-link"><i class="fa-solid fa-right-from-bracket"></i> Log Out</a>
    </nav>
</aside>

<main class="main">
    <div class="page-header">
        <div>
            <div class="page-title">Welcome, <?= htmlspecialchars(explode(',', $me['full_name'] ?? 'Principal')[0]) ?></div>
            <div class="page-sub">Pandan Bay Institute — <?= htmlspecialchars($scopeLabel) ?> Oversight</div>
        </div>
        <div class="header-right">
            <div class="period-badge <?= htmlspecialchars($settings['status']['cls']) ?>">
                <i class="fa-solid fa-calendar-check"></i>
                <?= htmlspecialchars($settings['academic_year']) ?> · <?= htmlspecialchars($settings['academic_structure_label']) ?> · <?= htmlspecialchars($settings['academic_term']) ?>
                — <?= htmlspecialchars($settings['status']['label']) ?>
            </div>

            <!-- NOTIFICATION BELL — replaces the old full-width Notifications panel -->
            <div class="bell" id="bell">
                <button class="bell-btn" id="bellBtn" type="button"
                        aria-haspopup="true" aria-expanded="false" aria-label="Notifications">
                    <i class="fa-solid fa-bell"></i>
                    <span class="bell-count" id="bellCount">0</span>
                </button>
                <div class="bell-panel" id="bellPanel" role="dialog" aria-label="Notifications">
                    <div class="bell-head">
                        <h3>Notifications</h3>
                        <button type="button" id="bellMarkRead">Mark all read</button>
                    </div>
                    <ul class="bell-list" id="bellList">
                        <?php foreach ($notifications as $n): ?>
                        <li class="lv-<?= htmlspecialchars($n['level']) ?>" data-id="<?= htmlspecialchars($n['id']) ?>">
                            <i class="fa-solid fa-circle-exclamation"></i>
                            <span><?= htmlspecialchars($n['text']) ?></span>
                        </li>
                        <?php endforeach; ?>
                    </ul>
                    <div class="bell-foot" id="bellFoot">Live</div>
                </div>
            </div>
        </div>
    </div>

    <?php if (!$structureActive): ?>
    <div class="structure-note">
        <i class="fa-solid fa-circle-info"></i>
        <p>
            <b><?= BASIC_ED_LABEL ?> evaluations are currently inactive.</b><br>
            The current evaluation period is configured for <b><?= htmlspecialchars($settings['academic_structure_label']) ?></b>.
            Principal evaluation tracking will become available when the evaluation period is switched to <?= BASIC_ED_LABEL ?>.
        </p>
    </div>
    <?php endif; ?>

    <!-- ── PERIOD STRIP (condensed) ── -->
    <div class="period-strip">
        <div class="period-item">
            <div class="k">Academic Year</div>
            <div class="v"><?= htmlspecialchars($settings['academic_year']) ?></div>
        </div>
        <div class="period-item">
            <div class="k">Structure</div>
            <div class="v"><?= BASIC_ED_LABEL ?></div>
        </div>
        <div class="period-item">
            <div class="k">Term</div>
            <div class="v"><?= htmlspecialchars($settings['academic_term']) ?></div>
        </div>
        <div class="period-item">
            <div class="k">Evaluation Opens</div>
            <div class="v"><?= $settings['eval_start_display'] !== '' ? htmlspecialchars($settings['eval_start_display']) : '—' ?></div>
        </div>
        <div class="period-item">
            <div class="k">Evaluation Closes</div>
            <div class="v"><?= $settings['eval_end_display'] !== '' ? htmlspecialchars($settings['eval_end_display']) : '—' ?></div>
        </div>
        <div class="period-item">
            <div class="k">Status</div>
            <div class="v"><span class="period-badge <?= htmlspecialchars($settings['status']['cls']) ?>" style="font-size:11px;"><?= htmlspecialchars($settings['status']['label']) ?></span></div>
        </div>
        <?php if ($hasPeriod && $evalOpen && $daysRemaining !== null && $daysRemaining >= 0): ?>
        <div class="period-item">
            <div class="k">Days Left</div>
            <div class="v"><?= $daysRemaining ?></div>
        </div>
        <?php endif; ?>
        <div class="period-note">
            <strong><?= htmlspecialchars($settings['message']['headline']) ?></strong>
            <?= htmlspecialchars($settings['message']['sub']) ?>
        </div>
    </div>

    <?php if ($structureActive): ?>

    <!-- HEADLINE STATS — each card is the doorway to its detail page -->
    <div class="card-grid">
        <a class="stat-link" href="principal_teachers.php">
            <div class="stat-card"><i class="fa-solid fa-chalkboard-user"></i><div class="num"><?= $teacherCount ?></div><div class="label">Teachers</div></div>
        </a>
        <a class="stat-link" href="principal_staff.php">
            <div class="stat-card"><i class="fa-solid fa-users"></i><div class="num"><?= $staffCount ?></div><div class="label">School Staff</div></div>
        </a>
        <a class="stat-link" href="principal_evaluation_tracker.php">
            <div class="stat-card"><i class="fa-solid fa-user-graduate"></i><div class="num"><?= $studentParticipation ?>%</div><div class="label">Student Participation</div></div>
        </a>
        <a class="stat-link" href="principal_evaluation_tracker.php">
            <div class="stat-card"><i class="fa-solid fa-clipboard-check"></i><div class="num"><?= $evaluationCompletion ?>%</div><div class="label">Evaluation Completion</div></div>
        </a>
        <a class="stat-link" href="principal_evaluations.php">
            <div class="stat-card"><i class="fa-solid fa-hourglass-half"></i><div class="num"><?= $pendingEvaluations ?></div><div class="label">Pending Evaluations</div></div>
        </a>
        <a class="stat-link" href="principal_reports.php">
            <div class="stat-card"><i class="fa-solid fa-chart-line"></i><div class="num">Open</div><div class="label">Reports &amp; Analytics</div></div>
        </a>
        <a class="stat-link" href="principal_results.php">
            <div class="stat-card"><i class="fa-solid fa-star"></i><div class="num">View</div><div class="label">Your Evaluation Results</div></div>
        </a>
    </div>

    <?php /* The empty-roster warning was removed from the page body by
             request — it still reaches the Principal through the bell
             ('no-roster' item in principal_notifications_feed.php). */ ?>
    <?php if (!$hasPeriod): ?>
    <div class="stub-note">
        <i class="fa-solid fa-clock-rotate-left"></i>
        Rosters are live, but there's no active evaluation period from System &amp; Period settings yet — completion and participation figures stay at 0% until one is opened.
    </div>
    <?php endif; ?>

    <?php else: ?>
    <div class="section">
        <h2><i class="fa-solid fa-circle-info"></i> <?= BASIC_ED_LABEL ?> Analytics</h2>
        <p class="empty-note">
            <?= BASIC_ED_LABEL ?> analytics will resume automatically once the Executive Assistant sets the active
            Academic Structure back to Basic Education.
        </p>
    </div>
    <?php endif; ?>

</main>

<script>
/* =========================================================================
   Notification bell — live polling
   -------------------------------------------------------------------------
   Refreshes from principal_notifications.php every 30s and while the tab is
   visible only, so a dashboard left open on a spare monitor overnight is not
   hammering the DB. Read-state is per-browser (localStorage) — there is no
   notifications table to persist it server-side yet, so "read" does not
   follow the Principal to another device.
   ========================================================================= */
(function(){
    const POLL_MS   = 30000;
    const ENDPOINT  = 'principal_notifications.php';
    const SEEN_KEY  = 'pbi_principal_seen_notifs';

    const bell   = document.getElementById('bell');
    const btn    = document.getElementById('bellBtn');
    const panel  = document.getElementById('bellPanel');
    const list   = document.getElementById('bellList');
    const count  = document.getElementById('bellCount');
    const foot   = document.getElementById('bellFoot');
    const markBtn= document.getElementById('bellMarkRead');
    if (!bell || !btn || !panel || !list) return;

    let timer = null;

    function readSeen(){
        try { return new Set(JSON.parse(localStorage.getItem(SEEN_KEY) || '[]')); }
        catch (e) { return new Set(); }
    }
    function writeSeen(set){
        try { localStorage.setItem(SEEN_KEY, JSON.stringify([...set])); } catch (e) {}
    }
    function currentIds(){
        return [...list.querySelectorAll('li[data-id]')].map(li => li.dataset.id);
    }

    function refreshBadge(){
        const seen = readSeen();
        let unread = 0;
        list.querySelectorAll('li[data-id]').forEach(li => {
            const isNew = !seen.has(li.dataset.id);
            li.classList.toggle('unseen', isNew);
            if (isNew) unread++;
        });
        count.textContent = unread > 9 ? '9+' : unread;
        count.classList.toggle('show', unread > 0);
        return unread;
    }

    function render(items){
        const before = new Set(currentIds());
        list.innerHTML = items.map(n => {
            const div = document.createElement('div');
            div.textContent = n.text;
            return '<li class="lv-' + n.level + '" data-id="' + n.id + '">' +
                   '<i class="fa-solid fa-circle-exclamation"></i><span>' +
                   div.innerHTML + '</span></li>';
        }).join('');

        // Prune read-state for items that no longer exist, so the key does
        // not grow forever across a school year.
        const live = new Set(items.map(n => n.id));
        const seen = readSeen();
        let changed = false;
        seen.forEach(id => { if (!live.has(id)) { seen.delete(id); changed = true; } });
        if (changed) writeSeen(seen);

        const arrived = items.some(n => !before.has(n.id));
        refreshBadge();
        if (arrived && before.size) {
            btn.classList.remove('pulse');
            void btn.offsetWidth;          // restart the animation
            btn.classList.add('pulse');
        }
    }

    async function poll(){
        try {
            const res = await fetch(ENDPOINT, { headers: { 'Accept': 'application/json' }, credentials: 'same-origin' });
            if (res.status === 401) { foot.textContent = 'Session expired — reload'; stop(); return; }
            const data = await res.json();
            if (!data.ok) throw new Error('feed');
            render(data.items || []);
            foot.textContent = 'Updated ' + new Date(data.ts * 1000).toLocaleTimeString();
        } catch (e) {
            foot.textContent = 'Offline — retrying';
        }
    }

    function start(){ if (!timer) { timer = setInterval(poll, POLL_MS); } }
    function stop(){ clearInterval(timer); timer = null; }

    btn.addEventListener('click', e => {
        e.stopPropagation();
        const open = panel.classList.toggle('open');
        btn.setAttribute('aria-expanded', open ? 'true' : 'false');
        btn.classList.remove('pulse');
        if (open) poll();
    });
    markBtn.addEventListener('click', e => {
        e.stopPropagation();
        writeSeen(new Set(currentIds()));
        refreshBadge();
    });
    document.addEventListener('click', e => {
        if (!bell.contains(e.target)) {
            panel.classList.remove('open');
            btn.setAttribute('aria-expanded', 'false');
        }
    });
    document.addEventListener('keydown', e => {
        if (e.key === 'Escape') {
            panel.classList.remove('open');
            btn.setAttribute('aria-expanded', 'false');
        }
    });
    document.addEventListener('visibilitychange', () => {
        if (document.hidden) { stop(); } else { poll(); start(); }
    });

    refreshBadge();
    start();
})();
</script>
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

/* Keep the existing navy sidebar; the content area is the white-theme area. */
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

/* Accent elements stay amber/semantic rather than reverting to dark-mode text. */
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
