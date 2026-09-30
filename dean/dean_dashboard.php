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
require_once dirname(__DIR__) . '/shared/ea_personnel_service.php';
require_once 'school_head_structure_gate.php';

// ── AUTH GUARD ────────────────────────────────────────────
if (empty($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'dean') {
    header("Location: dean_login.php");
    exit;
}

// Faculty/Staff/EA/Student roster reads (ea_get_faculty(), ea_get_staff(),
// ea_get_executive_assistants(), ea_get_students()) live in
// shared/ea_personnel_service.php — see that file's header comment for
// the confirmed schema decisions and still-open TODOs (evaluation_status
// tracking, Faculty "program" field, questionnaire routing).

// ── PULL DEAN PROFILE ─────────────────────────────────────
$stmt = $mysqli->prepare("SELECT full_name, username, email, designation, photo, department FROM users WHERE id = ? LIMIT 1");
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$me = $stmt->get_result()->fetch_assoc();
$stmt->close();

// ── GLOBAL SYSTEM SETTINGS (single source of truth) ─────────────────────
// Everything about "what period is it, what's the status, what's the
// schedule" comes from here. The Dean dashboard never derives or stores
// any of this itself, and never hardcodes an academic year/term.
// (Step 14: Synchronization with System Settings)
$settings = get_school_head_settings($mysqli, 'dean');

// Academic Structure / Academic Term decide which school head's evaluation
// is applicable. Dean owns College; Principal owns School Year. This only
// ever narrows — schedule / Force Open / Force Closed are untouched when
// College is the active structure. See school_head_structure_gate.php.
$settings = sh_gate_apply($settings, 'dean');

// A Dean oversees the Higher Education (internally "college") division.
// If the Executive Assistant has the active Academic Structure set to
// something else, the Dean must not show Higher Ed analytics as current.
$structureActive  = !empty($settings['school_head_applicable']);
$period_id_int    = $settings['period_id'] ?? 0;
$evalOpen         = !empty($settings['school_head_is_open']);

// Step 8: internal value stays "college" everywhere; this is the only
// display label used in markup below.
const HIGHER_ED_LABEL = 'Higher Education';

// ── PERSONNEL ROSTERS (Step 9 / Step 11 — Dashboard cards) ─────────────
// Rosters reflect the Dean's Higher Education headcount and are fetched
// unconditionally — they're informational even when the college period
// isn't the currently active academic structure, so the dashboard never
// has to sit empty (see the personnel-overview branch below). Only the
// evaluation-status math (completed/pending, ratings) depends on the
// active period actually being college and is gated behind $structureActive.
$facultyList = ea_get_faculty($mysqli, $period_id_int);
$staffList   = ea_get_staff($mysqli, $period_id_int);
$eaList      = ea_get_executive_assistants($mysqli, $period_id_int);
$studentList = ea_get_students($mysqli, $period_id_int);

$countByStatus = function (array $rows, string $status): int {
    return count(array_filter($rows, fn($r) => ($r['evaluation_status'] ?? '') === $status));
};

$facultyPending = $staffPending = $eaPending = 0;
$facultyCompleted = $staffCompleted = $eaCompleted = 0;
$overallCompletionPct = 0;
$studentParticipationPct = 0;
$totalAssigned = 0;

if ($structureActive) {
    $facultyCompleted = $countByStatus($facultyList, 'completed');
    $staffCompleted   = $countByStatus($staffList, 'completed');
    $eaCompleted      = $countByStatus($eaList, 'completed');

    $facultyPending = max(0, count($facultyList) - $facultyCompleted);
    $staffPending   = max(0, count($staffList) - $staffCompleted);
    $eaPending      = max(0, count($eaList) - $eaCompleted);

    $totalAssigned  = count($facultyList) + count($staffList) + count($eaList);
    $totalCompleted = $facultyCompleted + $staffCompleted + $eaCompleted;
    $overallCompletionPct = $totalAssigned > 0 ? (int) round($totalCompleted / $totalAssigned * 100) : 0;

    // Student participation stays informational only — Dean never manages
    // student accounts (Step 7), this is read-only context for the card.
    $studentSubmitted = $countByStatus($studentList, 'submitted');
    $studentParticipationPct = count($studentList) > 0
        ? (int) round($studentSubmitted / count($studentList) * 100)
        : 0;
}

// ── NOTIFICATIONS ─────────────────────────────────────────────────────
// Generic schedule-driven notices come straight from the shared service.
$notifications = $settings['notifications'];

// Seed the bell with the latest submitted evaluation events as well as the
// current status notices. The browser poll keeps these events visible.
if (($settings['period_id'] ?? 0) > 0) {
    $currentPeriodId = (int)$settings['period_id'];
    $collegeLevels = "'1st Year College','2nd Year College','3rd Year College','4th Year College'";
    $recentEvalQ = $mysqli->query("SELECT et.id, et.eval_type, et.submitted_at, target.full_name AS target_name
        FROM evaluation_tracker et
        JOIN users target ON target.id=et.target_user_id
        WHERE et.period_id=$currentPeriodId
          AND et.submitted_at IS NOT NULL
          AND target.is_active=1
          AND target.account_status='approved'
          AND (target.role IN ('dean','principal','staff') OR (target.role IN ('teacher','faculty') AND EXISTS (
              SELECT 1 FROM user_year_levels uyl
              WHERE uyl.user_id=target.id AND uyl.year_level IN ($collegeLevels)
          )))
          AND et.eval_type IN ('student','peer','faculty_peer','staff_peer')
        ORDER BY et.submitted_at DESC, et.id DESC
        LIMIT 12");
    if ($recentEvalQ) {
        while ($ev = $recentEvalQ->fetch_assoc()) {
            $kind = in_array($ev['eval_type'] ?? '', ['peer','faculty_peer','staff_peer'], true) ? 'Peer-to-Peer' : 'Student';
            $notifications[] = "New {$kind} evaluation received for " . ($ev['target_name'] ?? 'personnel') . ".";
        }
        $recentEvalQ->free();
    }
}

if ($structureActive) {
    if ($evalOpen && ($facultyPending + $staffPending + $eaPending) > 0) {
        $remaining = $facultyPending + $staffPending + $eaPending;
        $notifications[] = "{$remaining} evaluation" . ($remaining === 1 ? '' : 's') . " still pending.";
    }
    if ($totalAssigned > 0 && $overallCompletionPct === 100) {
        $notifications[] = "All Higher Education evaluations are complete.";
    }
}
// (No "structure not active" notice added here — the Personnel Overview
// cards above already stand in for the old empty-state messaging, so this
// doesn't need to also eat a slot in the notification bell.)
if (empty($notifications)) {
    $notifications[] = "No urgent items right now.";
}

// ── YOUR EVALUATION RATING (Phase 2, §7) ────────────────────────────
// The Dean's own average rating this period, from Teacher-submitted
// results. Same eval_type/eval_bucket placeholder convention used in
// dean_results.php — confirm the actual values your evaluation_tracker
// uses for Teacher → Dean submissions and adjust both files together.
const DEAN_RESULT_EVAL_TYPE   = 'teacher';
const DEAN_RESULT_EVAL_BUCKET = 'Dean';
$myRating = null;
if ($structureActive && $period_id_int > 0) {
    try {
        // bind_param() requires actual variables passed by reference — a
        // class constant or an array element (like $_SESSION['user_id'])
        // can't be passed directly, so copy them into plain local
        // variables first.
        $evalType   = DEAN_RESULT_EVAL_TYPE;
        $evalBucket = DEAN_RESULT_EVAL_BUCKET;
        $deanIdForRating = (int)$_SESSION['user_id'];

        $stmt = $mysqli->prepare("
            SELECT AVG(score) v FROM evaluation_tracker
            WHERE eval_type=? AND eval_bucket=? AND status IN ('submitted','approved')
              AND target_user_id=? AND period_id=?
        ");
        $stmt->bind_param("ssii", $evalType, $evalBucket, $deanIdForRating, $period_id_int);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        $myRating = $row && $row['v'] !== null ? round((float)$row['v'], 2) : null;
    } catch (mysqli_sql_exception $e) {
        $myRating = null;
    }
}
$pendingEvaluationsTotal = $facultyPending + $staffPending + $eaPending;

$mysqli->close();
$photo_src = !empty($me['photo']) ? '../image/' . $me['photo'] : '../image/pbi_logo';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"/>
<meta name="viewport" content="width=device-width, initial-scale=1.0"/>
<title>PBI — Dean Dashboard</title>
<link href="https://fonts.googleapis.com/css2?family=Rajdhani:wght@600;700&family=DM+Sans:wght@400;500;600&display=swap" rel="stylesheet"/>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css"/>
<style>
:root{--page-l:#F5F8FC;--card-l:#FFFFFF;--line-l:#DCE7F1;--line-strong-l:#9FB2C3;--text-l:#12263A;--muted-l:#6D8194;--shadow-l:0 4px 16px rgba(28,64,92,.09);--input-l:#F7FAFC;--dark:#0A192F;--mid:#172A45;--inner:#0F1F3D;--violet:#7C5FD9;--violet-h:#9C85F0;--violet-dark:#5F45B8;--light:#E0E6F0;--muted:#A0B3C6;--radius:10px;--shadow:0 8px 32px rgba(0,0,0,0.45);--danger:#f05454;--good:#10B981;}
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0;}
body{min-height:100vh;background:var(--page-l);font-family:'DM Sans',sans-serif;color:var(--text-l);display:flex;}

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
.page-header{display:flex;justify-content:space-between;align-items:center;margin-bottom:28px;flex-wrap:wrap;gap:14px;}
.page-title{font-family:'Rajdhani',sans-serif;font-size:28px;font-weight:700;color:var(--text-l);letter-spacing:1px;}
.page-sub{font-size:13px;color:var(--muted-l);margin-top:4px;}
.card-grid{display:grid;grid-template-columns:repeat(6,minmax(0,1fr));gap:16px;margin-bottom:30px;}
.stat-card-link{display:block;text-decoration:none;color:inherit;min-width:0;}
.stat-card{background:var(--card-l);border:1px solid var(--line-l);border-radius:14px;padding:20px;box-shadow:var(--shadow-l);}
.stat-card i{color:var(--violet-dark);font-size:20px;margin-bottom:10px;}
.stat-card .num{font-size:26px;font-weight:700;color:var(--text-l);}
.stat-card .label{font-size:12px;color:var(--muted-l);margin-top:4px;}

.section{background:var(--card-l);border:1px solid var(--line-l);border-radius:14px;padding:24px;box-shadow:var(--shadow-l);margin-bottom:26px;}
.section h2{font-family:'Rajdhani',sans-serif;font-size:19px;color:var(--text-l);margin-bottom:16px;display:flex;align-items:center;gap:8px;}
.section h2 i{color:var(--violet-dark);font-size:16px;}

.bar-wrap{background:var(--input-l);border-radius:6px;height:8px;width:100%;overflow:hidden;}
.bar-fill{height:100%;background:linear-gradient(90deg,var(--violet-dark),var(--violet-h));border-radius:6px;}
.pill{display:inline-block;padding:3px 10px;border-radius:20px;font-size:11px;font-weight:600;}
.pill.good{background:rgba(16,185,129,.14);color:var(--good);}
.pill.warn{background:rgba(124,95,217,.14);color:var(--violet-dark);}
.pill.bad{background:rgba(240,84,84,.12);color:#fca5a5;}

.two-col{display:grid;grid-template-columns:1fr 1fr;gap:20px;}


.notif-list{list-style:none;font-size:13px;}
.notif-list li{padding:10px 12px;border-radius:8px;background:var(--input-l);margin-bottom:8px;display:flex;align-items:center;gap:10px;}
.notif-list li i{color:var(--violet-dark);}
.notif-list li.notif-evaluation i{color:var(--good);}
.notif-list li:last-child{margin-bottom:0;}

.qa-btns{display:flex;flex-wrap:wrap;gap:10px;}

.period-badge{background:rgba(124,95,217,.14);border:1px solid rgba(124,95,217,.3);color:var(--violet-dark);padding:6px 14px;border-radius:20px;font-size:12px;font-weight:600;display:flex;align-items:center;gap:7px;}
.period-badge.closed{background:rgba(240,84,84,.1);border-color:rgba(240,84,84,.3);color:#fca5a5;}
.period-badge.scheduled{background:rgba(217,154,43,.12);border-color:rgba(217,154,43,.28);color:#d49a2a;}
.period-badge.amber{background:rgba(217,119,6,.14);border-color:rgba(217,119,6,.3);color:#fbbf24;}
.period-badge.gray{background:var(--input-l);border-color:var(--line-l);color:var(--muted-l);}
.empty-note{color:var(--muted-l);font-size:13px;font-style:italic;}

.period-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(150px,1fr));gap:16px;margin-bottom:16px;}
.period-field .period-field-label{font-size:11px;font-weight:700;color:var(--muted-l);text-transform:uppercase;letter-spacing:.6px;margin-bottom:5px;}
.period-field .period-field-value{font-size:16px;font-weight:700;color:var(--text-l);}
.period-message{font-size:13px;color:var(--muted-l);padding-top:14px;border-top:1px solid var(--line-l);}
.period-message strong{color:var(--text-l);}

.section-header-row{display:flex;align-items:center;justify-content:space-between;gap:14px;margin-bottom:16px;}
.section-header-row h2{margin-bottom:0;}

.notif-bell-wrap{position:relative;}
.notif-bell{background:rgba(124,95,217,.12);border:1px solid rgba(124,95,217,.35);color:var(--violet-dark);width:38px;height:38px;border-radius:10px;display:flex;align-items:center;justify-content:center;cursor:pointer;position:relative;transition:background .2s;}
.notif-bell:hover{background:rgba(124,95,217,.22);}
.notif-bell i{font-size:15px;}
.notif-badge{position:absolute;top:-6px;right:-6px;background:var(--danger);color:#fff;font-size:10px;font-weight:700;min-width:18px;height:18px;border-radius:9px;display:none;align-items:center;justify-content:center;padding:0 4px;border:2px solid var(--card-l);}

.notif-dropdown{position:absolute;top:calc(100% + 10px);right:0;width:320px;max-height:360px;overflow-y:auto;background:var(--input-l);border:1px solid var(--line-l);border-radius:12px;box-shadow:var(--shadow-l);padding:14px;z-index:50;opacity:0;visibility:hidden;transform:translateY(-6px);transition:opacity .15s,transform .15s,visibility .15s;}
.notif-dropdown.open{opacity:1;visibility:visible;transform:translateY(0);}
.notif-dropdown-header{display:flex;align-items:center;justify-content:space-between;font-size:11px;font-weight:700;color:var(--muted-l);text-transform:uppercase;letter-spacing:.5px;margin-bottom:10px;padding-bottom:10px;border-bottom:1px solid var(--line-l);}
.notif-live-dot{width:7px;height:7px;border-radius:50%;background:var(--good);box-shadow:0 0 0 0 rgba(16,185,129,.6);animation:notifPulse 2s infinite;}
.notif-live-dot.stale{background:var(--muted-l);animation:none;box-shadow:none;}
@keyframes notifPulse{0%{box-shadow:0 0 0 0 rgba(16,185,129,.55);}70%{box-shadow:0 0 0 7px rgba(16,185,129,0);}100%{box-shadow:0 0 0 0 rgba(16,185,129,0);}}

.countdown-row{display:flex;gap:14px;margin-top:14px;}
.countdown-box{flex:1;background:var(--input-l);border:1px solid var(--line-l);border-radius:10px;padding:12px;text-align:center;}
.countdown-box .num{font-size:22px;font-weight:700;color:var(--text-l);}
.countdown-box .lbl{font-size:10px;color:var(--muted-l);text-transform:uppercase;letter-spacing:.5px;margin-top:2px;}

.stub-note{font-size:11px;color:var(--violet-dark);background:rgba(124,95,217,.08);border:1px dashed rgba(124,95,217,.35);border-radius:8px;padding:8px 12px;margin-top:10px;}

@media(max-width:1200px){.card-grid{grid-template-columns:repeat(3,minmax(0,1fr));}}
@media(max-width:900px){.two-col{grid-template-columns:1fr;}.card-grid{grid-template-columns:repeat(2,minmax(0,1fr));}}
@media(max-width:560px){.card-grid{grid-template-columns:1fr;}}
@media(max-width:768px){body{flex-direction:column;}.sidebar{width:100%;min-height:auto;}}

/* ================================================================
   DEAN SIDEBAR — FINAL FACULTY-MATCHED SHELL
   Kept inline on the dashboard so the shared sidebar cannot be
   overridden by the dashboard's legacy .sidebar rules.
   ================================================================ */
:root { --sidebar-w:248px; --role-accent:#2563EB; --role-accent-light:#60A5FA; }
.sidebar {
  width:var(--sidebar-w) !important;
  flex:0 0 var(--sidebar-w) !important;
  min-height:100vh !important;
  padding:0 !important;
  background:#0A192F !important;
  background-image:none !important;
  border-right:1px solid #172A45 !important;
  box-shadow:6px 0 20px rgba(0,0,0,.16) !important;
  overflow:hidden !important;
  display:flex !important;
  flex-direction:column !important;
}
.sidebar-brand.portal-brand {
  min-height:68px !important;
  padding:14px 17px !important;
  margin:0 !important;
  border-bottom:1px solid rgba(255,255,255,.08) !important;
  display:flex !important;
  align-items:center !important;
  gap:11px !important;
  background:transparent !important;
}
.portal-brand-logo {
  width:38px !important; height:38px !important; flex:0 0 38px !important;
  border-radius:11px !important; display:flex !important; align-items:center !important;
  justify-content:center !important; overflow:hidden !important;
  background:#0F1F3D !important; border:1px solid #2563EB !important;
  box-shadow:0 0 14px rgba(37,99,235,.18) !important;
}
.portal-brand-logo img { width:100% !important; height:100% !important; object-fit:cover !important; display:block !important; }
.portal-brand-copy { min-width:0 !important; display:flex !important; flex-direction:column !important; line-height:1.2 !important; }
.portal-brand-copy strong { font-family:'Rajdhani','DM Sans',sans-serif !important; font-size:15px !important; font-weight:700 !important; color:#F8FAFC !important; letter-spacing:.25px !important; white-space:nowrap !important; }
.portal-brand-copy span { margin-top:3px !important; font-size:10px !important; color:#8FA6BF !important; font-weight:600 !important; letter-spacing:.15px !important; white-space:nowrap !important; }
.portal-sidebar-profile {
  padding:18px 16px 17px !important; margin:0 !important; text-align:center !important;
  border-bottom:1px solid rgba(255,255,255,.08) !important; background:transparent !important;
}
.portal-profile-avatar-wrap { margin:0 auto 11px !important; display:flex !important; justify-content:center !important; }
.portal-profile-avatar {
  width:64px !important; height:64px !important; max-width:64px !important; max-height:64px !important;
  border-radius:50% !important; object-fit:cover !important; border:2px solid #2563EB !important;
  background:#0F1F3D !important; box-shadow:0 0 15px rgba(37,99,235,.18) !important; display:block !important; flex:none !important;
}
.portal-profile-fallback {
  color:#60A5FA !important; font-family:'Rajdhani','DM Sans',sans-serif !important;
  font-size:17px !important; font-weight:700 !important; align-items:center !important; justify-content:center !important;
}
.portal-profile-name { color:#F8FAFC !important; font-size:13px !important; line-height:1.35 !important; font-weight:700 !important; word-break:break-word !important; }
.portal-profile-role { margin-top:3px !important; color:#8FA6BF !important; font-size:10px !important; text-transform:uppercase !important; letter-spacing:.7px !important; font-weight:700 !important; line-height:1.35 !important; }
.portal-profile-scope { margin-top:4px !important; color:#8FA6BF !important; font-size:10px !important; line-height:1.35 !important; font-weight:600 !important; }
.portal-sidebar-nav { flex:1 1 auto !important; min-height:0 !important; padding:16px 10px !important; margin:0 !important; overflow-y:auto !important; display:flex !important; flex-direction:column !important; gap:0 !important; }
.portal-sidebar-nav .nav-section-label { padding:0 8px !important; margin:0 0 7px !important; font-size:9.5px !important; font-weight:800 !important; letter-spacing:1.25px !important; text-align:left !important; color:#8FA6BF !important; line-height:1.3 !important; }
.portal-sidebar-nav .nav-section-label.sidebar-section-secondary { margin-top:17px !important; }
.portal-sidebar-nav .nav-link {
  min-height:40px !important; width:auto !important; margin:2px 2px !important; padding:9px 11px !important;
  border:0 !important; border-left:0 !important; border-radius:8px !important; gap:10px !important;
  color:#CBD8E8 !important; background:transparent !important; font-size:13px !important; font-weight:500 !important;
  line-height:1.2 !important; text-decoration:none !important; display:flex !important; align-items:center !important; box-shadow:none !important; transform:none !important;
}
.portal-sidebar-nav .nav-link i { width:18px !important; min-width:18px !important; font-size:14px !important; color:#8FA6BF !important; text-align:center !important; flex:0 0 18px !important; }
.portal-sidebar-nav .nav-link:hover { background:rgba(255,255,255,.07) !important; color:#FFFFFF !important; }
.portal-sidebar-nav .nav-link:hover i { color:#60A5FA !important; }
.portal-sidebar-nav .nav-link.active {
  background:linear-gradient(90deg,rgba(37,99,235,.18),rgba(255,255,255,.025)) !important;
  color:#FFFFFF !important; font-weight:700 !important; border:0 !important;
  box-shadow:inset 3px 0 0 #60A5FA !important;
}
.portal-sidebar-nav .nav-link.active i { color:#60A5FA !important; }
.sidebar-footer { flex:0 0 auto !important; padding:13px 14px 15px !important; margin:0 !important; border-top:1px solid rgba(255,255,255,.08) !important; background:transparent !important; }
.btn-logout-side {
  width:100% !important; min-height:40px !important; display:flex !important; align-items:center !important; gap:10px !important;
  padding:9px 11px !important; border-radius:8px !important; color:#FCA5A5 !important; text-decoration:none !important;
  font-size:13px !important; font-weight:600 !important; border:1px solid rgba(248,113,113,.28) !important; background:rgba(248,113,113,.08) !important; box-sizing:border-box !important;
}
.btn-logout-side i { width:18px !important; min-width:18px !important; text-align:center !important; flex:0 0 18px !important; color:#FF8E8E !important; }
@media(max-width:768px){ .sidebar{width:248px !important; flex:0 0 248px !important;} }

/* ================================================================
   DEAN DASHBOARD — DARK MODE CONTENT
   Uses the same localStorage preference as the Dean settings page.
   The shared sidebar toggles html.dark-theme; these rules extend that
   theme across the dashboard workspace itself.
   ================================================================ */
html.dark-theme {
  --page-l:#0A192F !important;
  --card-l:#172A45 !important;
  --line-l:rgba(255,255,255,.08) !important;
  --line-strong-l:rgba(255,255,255,.14) !important;
  --text-l:#E0E6F0 !important;
  --muted-l:#A0B3C6 !important;
  --shadow-l:0 8px 26px rgba(0,0,0,.28) !important;
  --input-l:#0F1F3D !important;
  --violet-dark:#9C85F0 !important;
}
html.dark-theme body {
  background:#0A192F !important;
  color:#E0E6F0 !important;
  color-scheme:dark !important;
}
html.dark-theme .main {
  background:#0A192F !important;
  color:#E0E6F0 !important;
}
html.dark-theme .page-title { color:#F8FAFC !important; }
html.dark-theme .page-sub { color:#A0B3C6 !important; }
html.dark-theme .section,
html.dark-theme .stat-card {
  background:#172A45 !important;
  color:#E0E6F0 !important;
  border-color:rgba(255,255,255,.08) !important;
  box-shadow:0 8px 26px rgba(0,0,0,.28) !important;
}
html.dark-theme .section h2 { color:#F8FAFC !important; }
html.dark-theme .section h2 i,
html.dark-theme .stat-card i { color:#9C85F0 !important; }
html.dark-theme .period-field .period-field-label,
html.dark-theme .stat-card .label,
html.dark-theme .empty-note,
html.dark-theme .period-message,
html.dark-theme .countdown-box .lbl {
  color:#A0B3C6 !important;
}
html.dark-theme .period-field .period-field-value,
html.dark-theme .stat-card .num,
html.dark-theme .period-message strong {
  color:#F8FAFC !important;
}
html.dark-theme .period-message { border-top-color:rgba(255,255,255,.08) !important; }
html.dark-theme .bar-wrap,
html.dark-theme .countdown-box,
html.dark-theme .notif-list li,
html.dark-theme .notif-dropdown {
  background:#0F1F3D !important;
  border-color:rgba(255,255,255,.08) !important;
  color:#E0E6F0 !important;
}
html.dark-theme .countdown-box .num { color:#F8FAFC !important; }
html.dark-theme .period-badge {
  background:rgba(156,133,240,.14) !important;
  border-color:rgba(156,133,240,.30) !important;
  color:#C4B5FD !important;
}
html.dark-theme .period-badge.closed {
  background:rgba(240,84,84,.10) !important;
  border-color:rgba(240,84,84,.30) !important;
  color:#FCA5A5 !important;
}
html.dark-theme .period-badge.scheduled,
html.dark-theme .period-badge.amber { color:#FBBF24 !important; }
html.dark-theme .period-badge.gray {
  background:#0F1F3D !important;
  border-color:rgba(255,255,255,.08) !important;
  color:#A0B3C6 !important;
}
html.dark-theme .notif-bell {
  background:rgba(156,133,240,.14) !important;
  border-color:rgba(156,133,240,.34) !important;
  color:#C4B5FD !important;
}
html.dark-theme .notif-bell:hover { background:rgba(156,133,240,.22) !important; }
html.dark-theme .notif-dropdown-header {
  color:#A0B3C6 !important;
  border-bottom-color:rgba(255,255,255,.08) !important;
}
html.dark-theme .notif-list li i { color:#C4B5FD !important; }
html.dark-theme .notif-badge { border-color:#172A45 !important; }
html.dark-theme .stub-note {
  background:rgba(156,133,240,.10) !important;
  border-color:rgba(156,133,240,.30) !important;
  color:#C4B5FD !important;
}
html.dark-theme .stub-note code { color:#E0E6F0 !important; }
html.dark-theme a.stat-card-link:hover .stat-card {
  border-color:rgba(156,133,240,.32) !important;
  transform:translateY(-1px);
}

</style>
</head>
<body>

<?php
$active = 'dashboard';
$sidebarScope = HIGHER_ED_LABEL . ' Division';
include __DIR__ . '/includes/dean_sidebar.php';
?>

<main class="main">
    <div class="page-header">
        <div>
            <div class="page-title">Welcome, <?= htmlspecialchars(explode(',', $me['full_name'] ?? 'Dean')[0]) ?></div>
            <div class="page-sub">Pandan Bay Institute — <?= HIGHER_ED_LABEL ?> Division Oversight</div>
        </div>
        <div class="period-badge <?= htmlspecialchars($settings['status']['cls']) ?>">
            <i class="fa-solid fa-calendar-check"></i>
            <?= htmlspecialchars($settings['academic_year']) ?> · <?= HIGHER_ED_LABEL ?> · <?= htmlspecialchars($settings['academic_term']) ?>
            — <?= htmlspecialchars($settings['status']['label']) ?>
        </div>
    </div>

    <!-- ── CURRENT EVALUATION PERIOD ── -->
    <div class="section">
        <div class="section-header-row">
            <h2><i class="fa-solid fa-calendar-days"></i> Current Evaluation Period</h2>

            <!-- NOTIFICATION BELL — replaces the old static Notifications
                 list/section. One button, badge-counted, opposite the
                 period icon/title on this same header row. Its dropdown
                 is seeded from $notifications (server render) and then
                 kept current by polling dean_notifications_api.php on an
                 interval (see the script block near the end of <body>) so
                 the Dean sees new notices without reloading the page. -->
            <div class="notif-bell-wrap">
                <button type="button" class="notif-bell" id="notifBellBtn" aria-haspopup="true" aria-expanded="false" aria-label="Notifications">
                    <i class="fa-solid fa-bell"></i>
                    <span class="notif-badge" id="notifBadge"></span>
                </button>
                <div class="notif-dropdown" id="notifDropdown" role="menu" aria-hidden="true">
                    <div class="notif-dropdown-header">
                        <span>Notifications</span>
                        <span class="notif-live-dot" id="notifLiveDot" title="Live — updates automatically"></span>
                    </div>
                    <ul class="notif-list" id="notifList">
                        <?php foreach ($notifications as $n): ?>
                            <li><i class="fa-solid fa-circle-exclamation"></i> <?= htmlspecialchars($n) ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            </div>
        </div>
        <div class="period-grid">
            <div class="period-field">
                <div class="period-field-label">Academic Year</div>
                <div class="period-field-value"><?= htmlspecialchars($settings['academic_year']) ?></div>
            </div>
            <div class="period-field">
                <div class="period-field-label">Academic Structure</div>
                <div class="period-field-value"><?= HIGHER_ED_LABEL ?></div>
            </div>
            <div class="period-field">
                <div class="period-field-label">Academic Term</div>
                <div class="period-field-value"><?= htmlspecialchars($settings['academic_term']) ?></div>
            </div>
            <div class="period-field">
                <div class="period-field-label">Status</div>
                <div class="period-field-value">
                    <span class="period-badge <?= htmlspecialchars($settings['status']['cls']) ?>" style="font-size:11px;">
                        <?= htmlspecialchars($settings['status']['label']) ?>
                    </span>
                </div>
            </div>
            <div class="period-field">
                <div class="period-field-label">Evaluation Opens</div>
                <div class="period-field-value"><?= $settings['eval_start_display'] !== '' ? htmlspecialchars($settings['eval_start_display']) : '—' ?></div>
            </div>
            <div class="period-field">
                <div class="period-field-label">Evaluation Closes</div>
                <div class="period-field-value"><?= $settings['eval_end_display'] !== '' ? htmlspecialchars($settings['eval_end_display']) : '—' ?></div>
            </div>
        </div>
        <?php
        // The shared settings service normally returns message as ['headline', 'sub'].
        // Keep this rendering defensive so older deployments that still return a
        // plain string do not trigger a PHP 8 TypeError.
        $periodMessage = $settings['message'] ?? '';
        $periodHeadline = is_array($periodMessage) ? ($periodMessage['headline'] ?? '') : (string)$periodMessage;
        $periodSub = is_array($periodMessage) ? ($periodMessage['sub'] ?? '') : '';
        ?>
        <div class="period-message">
            <strong><?= htmlspecialchars($periodHeadline) ?></strong>
            <?= htmlspecialchars($periodSub) ?>
        </div>

        <?php if ($settings['countdown_enabled'] && $evalOpen && $settings['eval_end']): ?>
        <div class="countdown-row" id="countdownRow" data-end="<?= $settings['eval_end'] ? htmlspecialchars(ss_parse_datetime($settings['eval_end'])->format('c')) : '' ?>">
            <div class="countdown-box"><div class="num" id="cd-days">—</div><div class="lbl">Days</div></div>
            <div class="countdown-box"><div class="num" id="cd-hours">—</div><div class="lbl">Hours</div></div>
            <div class="countdown-box"><div class="num" id="cd-mins">—</div><div class="lbl">Minutes</div></div>
        </div>
        <?php endif; ?>
    </div>

    <?php if ($structureActive): ?>

    <!-- OVERVIEW STATS — concise dashboard summary for the Dean. -->
    <div class="card-grid">
        <a href="dean_evaluation.php?tab=faculty" class="stat-card-link">
        <div class="stat-card"><i class="fa-solid fa-chalkboard-user"></i><div class="num"><?= $facultyPending ?></div><div class="label">Teachers Awaiting Evaluation</div></div>
        </a>
        <a href="dean_evaluation.php?tab=staff" class="stat-card-link">
        <div class="stat-card"><i class="fa-solid fa-id-badge"></i><div class="num"><?= $staffPending ?></div><div class="label">Staff Awaiting Evaluation</div></div>
        </a>
        <a href="dean_evaluation.php?tab=executive_assistant" class="stat-card-link">
        <div class="stat-card"><i class="fa-solid fa-user-tie"></i><div class="num"><?= $eaPending ?></div><div class="label">Executive Assistant Awaiting Evaluation</div></div>
        </a>
        <a href="dean_evaluation_tracker.php" class="stat-card-link">
        <div class="stat-card"><i class="fa-solid fa-user-graduate"></i><div class="num"><?= $studentParticipationPct ?>%</div><div class="label">Student Submission Progress</div></div>
        </a>
        <a href="dean_results.php" class="stat-card-link">
        <div class="stat-card"><i class="fa-solid fa-star"></i><div class="num"><?= $myRating !== null ? $myRating : '—' ?></div><div class="label">Your Evaluation Rating</div></div>
        </a>
        <div class="stat-card"><i class="fa-solid fa-hourglass-half"></i><div class="num"><?= $pendingEvaluationsTotal ?></div><div class="label">Pending Evaluations</div></div>
    </div>

    <?php if (empty($facultyList) && empty($staffList) && empty($eaList)): ?>
    <div class="stub-note">
        <i class="fa-solid fa-plug-circle-exclamation"></i>
        No approved &amp; active Teacher, Staff, or Executive Assistant accounts found for the current structure — check <code>account_status</code>/<code>is_active</code> and College year-level assignment in Manage Privileged Accounts.
    </div>
    <?php endif; ?>

    <?php else: ?>

    <!-- PERSONNEL OVERVIEW — simple headcount summary when the College
         evaluation period is not currently active. -->
    <div class="card-grid">
        <a href="dean_evaluation.php?tab=faculty" class="stat-card-link">
        <div class="stat-card"><i class="fa-solid fa-chalkboard-user"></i><div class="num"><?= count($facultyList) ?></div><div class="label">Teachers on Record</div></div>
        </a>
        <a href="dean_evaluation.php?tab=staff" class="stat-card-link">
        <div class="stat-card"><i class="fa-solid fa-id-badge"></i><div class="num"><?= count($staffList) ?></div><div class="label">Staff on Record</div></div>
        </a>
        <a href="dean_evaluation.php?tab=executive_assistant" class="stat-card-link">
        <div class="stat-card"><i class="fa-solid fa-user-tie"></i><div class="num"><?= count($eaList) ?></div><div class="label">Executive Assistants on Record</div></div>
        </a>
        <a href="dean_evaluation_tracker.php" class="stat-card-link">
        <div class="stat-card"><i class="fa-solid fa-user-graduate"></i><div class="num"><?= count($studentList) ?></div><div class="label">Students on Record</div></div>
        </a>
    </div>

    <?php endif; ?>

</main>

<script>
(function(){
    const row = document.getElementById('countdownRow');
    if (!row) return;
    const end = new Date(row.dataset.end).getTime();
    function tick(){
        const now = Date.now();
        let diff = Math.max(0, end - now);
        const days = Math.floor(diff / 86400000); diff -= days * 86400000;
        const hours = Math.floor(diff / 3600000); diff -= hours * 3600000;
        const mins = Math.floor(diff / 60000);
        document.getElementById('cd-days').textContent = days;
        document.getElementById('cd-hours').textContent = hours;
        document.getElementById('cd-mins').textContent = mins;
    }
    tick();
    setInterval(tick, 30000);
})();
</script>

<script>
(function(){
    const bellBtn  = document.getElementById('notifBellBtn');
    const dropdown = document.getElementById('notifDropdown');
    const badge    = document.getElementById('notifBadge');
    const list     = document.getElementById('notifList');
    const liveDot  = document.getElementById('notifLiveDot');
    if (!bellBtn || !dropdown) return;

    const STORE_KEY = 'pbiDeanPersistentNotifications';
    const MAX_ITEMS = 40;
    let stored = loadStored();

    function loadStored(){
        try {
            const raw = localStorage.getItem(STORE_KEY);
            const parsed = raw ? JSON.parse(raw) : [];
            return Array.isArray(parsed) ? parsed : [];
        } catch(e){ return []; }
    }
    function saveStored(){
        try { localStorage.setItem(STORE_KEY, JSON.stringify(stored.slice(0, MAX_ITEMS))); } catch(e){}
    }
    function stableKey(text){
        return 'notice:' + String(text || '').trim();
    }
    function normalize(n){
        if (typeof n === 'string') {
            return {key: stableKey(n), text:n, type:'notice', created_at:null};
        }
        n = n || {};
        return {
            key: String(n.key || stableKey(n.text || '')),
            text: String(n.text || ''),
            type: String(n.type || 'notice'),
            created_at: n.created_at || null
        };
    }
    function isPlaceholder(n){ return n && n.text === 'No urgent items right now.'; }

    function mergeNotifications(incoming){
        const map = new Map();
        stored.forEach(function(item){ if(item && item.key && item.text) map.set(item.key, item); });
        const fresh = [];
        (Array.isArray(incoming) ? incoming : []).map(normalize).forEach(function(item){
            if (!item.text || isPlaceholder(item)) return;
            const previous = map.get(item.key);
            if (previous) {
                map.set(item.key, Object.assign({}, previous, item));
            } else {
                item.read = false;
                map.set(item.key, item);
                fresh.push(item.key);
            }
        });
        stored = Array.from(map.values()).sort(function(a,b){
            const da = a.created_at ? new Date(a.created_at).getTime() : 0;
            const db = b.created_at ? new Date(b.created_at).getTime() : 0;
            return db - da;
        }).slice(0, MAX_ITEMS);
        saveStored();
        return fresh;
    }

    function renderNotifications(incoming){
        mergeNotifications(incoming);
        list.innerHTML = '';
        const visible = stored.length ? stored : (Array.isArray(incoming) ? incoming.map(normalize).filter(n => !isPlaceholder(n)) : []);
        visible.forEach(function(item){
            const li = document.createElement('li');
            if (item.type === 'evaluation') li.classList.add('notif-evaluation');
            const icon = document.createElement('i');
            icon.className = item.type === 'evaluation' ? 'fa-solid fa-clipboard-check' : 'fa-solid fa-circle-exclamation';
            li.appendChild(icon);
            li.appendChild(document.createTextNode(' ' + item.text));
            list.appendChild(li);
        });
        const unread = stored.filter(function(n){ return n && !n.read; }).length;
        if (unread > 0) {
            badge.textContent = unread > 9 ? '9+' : String(unread);
            badge.style.display = 'flex';
        } else {
            badge.style.display = 'none';
        }
    }

    function closeDropdown(){
        dropdown.classList.remove('open');
        bellBtn.setAttribute('aria-expanded', 'false');
        dropdown.setAttribute('aria-hidden', 'true');
    }

    renderNotifications(<?= json_encode($notifications) ?>);

    bellBtn.addEventListener('click', function(e){
        e.stopPropagation();
        const isOpen = dropdown.classList.toggle('open');
        bellBtn.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
        dropdown.setAttribute('aria-hidden', isOpen ? 'false' : 'true');
        if (isOpen) {
            stored = stored.map(function(n){ return Object.assign({}, n, {read:true}); });
            saveStored();
            renderNotifications([]);
        }
    });
    document.addEventListener('click', function(e){
        if (!dropdown.contains(e.target) && e.target !== bellBtn) closeDropdown();
    });
    document.addEventListener('keydown', function(e){
        if (e.key === 'Escape') closeDropdown();
    });

    // REAL-TIME: the API returns recent submitted evaluation events on every
    // poll, and the local history keeps previous events from disappearing.
    const POLL_MS = 10000;
    function poll(){
        fetch('dean_notifications_api.php', { credentials: 'same-origin', cache: 'no-store' })
            .then(function(res){ if (!res.ok) throw new Error('bad status'); return res.json(); })
            .then(function(data){
                mergeNotifications(data.notifications);
                renderNotifications([]);
                if (liveDot) liveDot.classList.remove('stale');
            })
            .catch(function(){
                if (liveDot) liveDot.classList.add('stale');
            });
    }
    setInterval(poll, POLL_MS);
})();
</script>
<script src="../admin/eval_status_poll.js" defer></script>
</body>
</html>