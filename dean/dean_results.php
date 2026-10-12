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

// ── AUTH GUARD ────────────────────────────────────────────
if (empty($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'dean') {
    header("Location: dean_login.php");
    exit;
}

// ── dean_results.php (Phase 2, new page) ────────────────────────────
// The Dean's OWN evaluation results — feedback received from the
// evaluator groups currently authorized to evaluate the Dean (including
// the Executive Assistant's EA evaluation flow). This is a read-only
// "how am I doing" view, not an evaluation tool.
//
// ── CONFIDENTIALITY — NON-NEGOTIABLE ────────────────────────────────
// The Dean must NEVER be able to identify which Teacher submitted which
// response. Every query below intentionally does NOT join to the
// evaluator's identity (name/id/photo/department/account), and nothing
// derived from evaluator identity (timestamp-vs-other-students ordering,
// IP, etc.) is exposed. Responses are labeled only "Evaluation #1",
// "Evaluation #2", ... in submission order, or "Anonymous Response".

function safe_scalar(mysqli $mysqli, string $sql, string $types = '', array $params = []) {
    try {
        $stmt = @$mysqli->prepare($sql);
        if (!$stmt) return null;
        if ($types !== '') { $stmt->bind_param($types, ...$params); }
        if (!@$stmt->execute()) { $stmt->close(); return null; }
        $res = $stmt->get_result();
        $row = $res ? $res->fetch_assoc() : null;
        $stmt->close();
        return $row ? reset($row) : null;
    } catch (mysqli_sql_exception $e) {
        return null;
    }
}
function safe_rows(mysqli $mysqli, string $sql, string $types = '', array $params = []): array {
    try {
        $stmt = @$mysqli->prepare($sql);
        if (!$stmt) return [];
        if ($types !== '') { $stmt->bind_param($types, ...$params); }
        if (!@$stmt->execute()) { $stmt->close(); return []; }
        $res = $stmt->get_result();
        $rows = $res ? $res->fetch_all(MYSQLI_ASSOC) : [];
        $stmt->close();
        return $rows;
    } catch (mysqli_sql_exception $e) {
        return [];
    }
}

$deanId = (int)$_SESSION['user_id'];

// ── DEAN PROFILE (for sidebar) ─────────────────────────────
$stmt = $mysqli->prepare("SELECT full_name, designation, photo FROM users WHERE id = ? LIMIT 1");
$stmt->bind_param("i", $deanId);
$stmt->execute();
$me = $stmt->get_result()->fetch_assoc();
$stmt->close();
$photo_src = !empty($me['photo']) ? UPLOAD_URL . $me['photo'] : UPLOAD_URL . 'pbi_logo';

// ── GLOBAL SYSTEM SETTINGS ──────────────────────────────────────────
$settings = get_system_settings($mysqli);
$period_id_int = $settings['period_id'] ?? 0;
$hasPeriod     = $period_id_int > 0;

const HIGHER_ED_LABEL = 'Higher Education';

// NOTE ON eval_type/evaluation_context (confirmed against student_dashboard.php
// and admin/questionnaire.php, not assumed):
// Students evaluate the active Dean via the normal Student Evaluation flow —
// eval_type='student', evaluation_context='school_head'. The Executive
// Assistant evaluation flow writes eval_type='ea'. Both tracker shapes use
// target_user_id to point at the Dean's own account, so this page accepts
// either authorized source without exposing evaluator identity. The question set is
// per-person (Principal/Dean are "per_user_targets" in questionnaire.php), so
// answers join through user_questions via questionnaire_answers.user_question_id,
// with question_source='user' — the same source admin_analytics.php's sheet
// view already reads for Staff/Principal/Dean questions.
const SCHOOL_HEAD_EVAL_TYPE = 'student';
const SCHOOL_HEAD_CONTEXT   = 'school_head';

$overallAvg = null;
$responseCount = 0;
$history = [];

// ── EVALUATION TERMS (dropdown) ─────────────────────────────────────
// Feedback is separated per evaluation period/term. A term is listed when it is the active
// period, still has live feedback for this Dean, or has feedback kept by System Archive
// (feedback_received_keep — an identity-free copy saved when the EA archives a period).
const EA_EVAL_TYPE_ELIGIBLE = "((et.eval_type=? AND et.evaluation_context=?) OR et.eval_type='ea') AND et.status IN ('submitted','approved') AND et.target_user_id=?";

$termIds = [];
if ($hasPeriod) { $termIds[(int)$period_id_int] = true; }

$liveTermRows = safe_rows($mysqli, "SELECT DISTINCT et.period_id AS pid FROM evaluation_tracker et WHERE " . EA_EVAL_TYPE_ELIGIBLE,
    "ssi", [SCHOOL_HEAD_EVAL_TYPE, SCHOOL_HEAD_CONTEXT, $deanId]);
foreach ($liveTermRows as $r) { if ((int)$r['pid'] > 0) $termIds[(int)$r['pid']] = true; }

$keptTermRows = safe_rows($mysqli, "SELECT period_id AS pid, MAX(school_year) AS school_year, MAX(semester) AS semester, MAX(period_label) AS period_label
    FROM feedback_received_keep WHERE target_user_id=? GROUP BY period_id", "i", [$deanId]);
$keptTerms = [];
foreach ($keptTermRows as $r) {
    $keptTerms[(int)$r['pid']] = $r;
    if ((int)$r['pid'] > 0) $termIds[(int)$r['pid']] = true;
}

$terms = [];
if ($termIds) {
    $in = implode(',', array_map('intval', array_keys($termIds)));
    foreach (safe_rows($mysqli, "SELECT * FROM evaluation_periods WHERE id IN ($in)") as $ep) {
        $terms[(int)$ep['id']] = [
            'id'          => (int)$ep['id'],
            'school_year' => $ep['school_year'] ?? '',
            'period_label'=> $ep['period_label'] ?? ($ep['semester'] ?? ''),
            'date_start'  => $ep['date_start'] ?? '',
        ];
    }
    // A period row that no longer exists can still have kept feedback — fall back to its snapshot labels.
    foreach (array_keys($termIds) as $pid) {
        if (!isset($terms[$pid])) {
            $k = $keptTerms[$pid] ?? [];
            $terms[$pid] = ['id' => $pid, 'school_year' => $k['school_year'] ?? '', 'period_label' => $k['period_label'] ?? ($k['semester'] ?? ''), 'date_start' => ''];
        }
    }
    foreach ($terms as $pid => &$t) {
        $t['current']  = ($hasPeriod && $pid === (int)$period_id_int);
        $t['archived'] = isset($keptTerms[$pid]);
        // period_label may already contain the school year — don't prepend it twice.
        $t['label']    = ($t['school_year'] !== '' && $t['period_label'] !== '')
            ? (stripos($t['period_label'], $t['school_year']) !== false ? $t['period_label'] : $t['school_year'] . ' — ' . $t['period_label'])
            : ($t['period_label'] !== '' ? $t['period_label'] : ($t['school_year'] !== '' ? $t['school_year'] : 'Period #' . $pid));
    }
    unset($t);
    // Current term first, then newest to oldest.
    uasort($terms, function ($a, $b) {
        if ($a['current'] !== $b['current']) return $a['current'] ? -1 : 1;
        $c = strcmp((string)$b['date_start'], (string)$a['date_start']);
        return $c ?: ($b['id'] <=> $a['id']);
    });
}

$requestedPeriod = $_GET['period'] ?? '';
$allPeriodsSelected = is_string($requestedPeriod) && strtolower(trim($requestedPeriod)) === 'all';
$selPeriod = $allPeriodsSelected ? 0 : (int)$requestedPeriod;
if (!$allPeriodsSelected && !isset($terms[$selPeriod])) {
    $selPeriod = $terms ? (int)array_key_first($terms) : 0;   // current term, else newest archived term
}
$showData = $allPeriodsSelected || $selPeriod > 0;

$items = [];

if ($showData) {
    // ── LIVE RESPONSES (selected term or all terms) ─────────────────
    // Deliberately selects ONLY tracker id (for per-tracker avg + ordering),
    // comment, score totals, period labels, and submission order. No evaluator_id,
    // name, or any evaluator-identifying column is selected — do not add one.
    $livePeriodFilter = $allPeriodsSelected ? '' : ' AND et.period_id=?';
    $liveTypes = $allPeriodsSelected ? 'ssi' : 'ssii';
    $liveParams = $allPeriodsSelected
        ? [SCHOOL_HEAD_EVAL_TYPE, SCHOOL_HEAD_CONTEXT, $deanId]
        : [SCHOOL_HEAD_EVAL_TYPE, SCHOOL_HEAD_CONTEXT, $deanId, $selPeriod];
    $rawHistory = safe_rows($mysqli, "
        SELECT et.id, et.remarks AS comment, et.submitted_at, ep.period_label, ep.semester, ep.school_year,
               (SELECT SUM(qa2.answer_score)   FROM questionnaire_answers qa2 WHERE qa2.tracker_id = et.id) AS score_sum,
               (SELECT COUNT(qa2.answer_score) FROM questionnaire_answers qa2 WHERE qa2.tracker_id = et.id) AS score_count
        FROM evaluation_tracker et
        LEFT JOIN evaluation_periods ep ON ep.id = et.period_id
        WHERE " . EA_EVAL_TYPE_ELIGIBLE . $livePeriodFilter . "
    ", $liveTypes, $liveParams);

    foreach ($rawHistory as $r) {
        $items[] = [
            'source'       => 'live',
            'key'          => (int)$r['id'],
            'comment'      => $r['comment'] ?: '',
            'submitted_at' => $r['submitted_at'],
            'score_sum'    => (float)($r['score_sum'] ?? 0),
            'score_count'  => (int)($r['score_count'] ?? 0),
            'school_year'  => $r['school_year'] ?? '',
            'semester'     => $r['semester'] ?? '',
            'period_label' => $r['period_label'] ?? '',
        ];
    }

    // ── KEPT BY SYSTEM ARCHIVE (selected term or all terms) ─────────
    $keptPeriodFilter = $allPeriodsSelected ? '' : ' AND period_id=?';
    $keptTypes = $allPeriodsSelected ? 'i' : 'ii';
    $keptParams = $allPeriodsSelected ? [$deanId] : [$deanId, $selPeriod];
    $keptRows = safe_rows($mysqli, "
        SELECT id, remarks, submitted_at, school_year, semester, period_label, score_sum, score_count
        FROM feedback_received_keep
        WHERE target_user_id=?" . $keptPeriodFilter . "
    ", $keptTypes, $keptParams);
    foreach ($keptRows as $r) {
        $items[] = [
            'source'       => 'kept',
            'key'          => (int)$r['id'],          // feedback_received_keep.id (not the old tracker id)
            'comment'      => $r['remarks'] ?: '',
            'submitted_at' => $r['submitted_at'],
            'score_sum'    => (float)$r['score_sum'],
            'score_count'  => (int)$r['score_count'],
            'school_year'  => $r['school_year'] ?? '',
            'semester'     => $r['semester'] ?? '',
            'period_label' => $r['period_label'] ?? '',
        ];
    }
}

// Submission order only — never evaluator identity.
usort($items, function ($a, $b) {
    $ta = $a['submitted_at'] ? strtotime($a['submitted_at']) : 0;
    $tb = $b['submitted_at'] ? strtotime($b['submitted_at']) : 0;
    return $ta <=> $tb ?: ($a['key'] <=> $b['key']);
});

$totalSum = 0.0; $totalCount = 0; $n = 1;
foreach ($items as $it) {
    $totalSum   += $it['score_sum'];
    $totalCount += $it['score_count'];
    $cleanComment = trim((string)$it['comment']);
    // Treat placeholders such as "N/A", "none", "-" as no written comment.
    $commentOk = $cleanComment !== '' && !preg_match('/^(n\/?a|none|nil|no comments?|no feedback|not applicable|[\s.\-–—]+)$/iu', $cleanComment);
    $history[] = [
        '_tracker_id'        => $it['key'],
        'ts'                 => $it['submitted_at'] ? (int)strtotime($it['submitted_at']) : 0,
        'idx'                => $n,
        'comment_ok'         => $commentOk,
        '_source'            => $it['source'],
        'label'              => 'Evaluation #' . $n,
        'score'              => $it['score_count'] > 0 ? round($it['score_sum'] / $it['score_count'], 2) : null,
        'comment'            => $commentOk ? $cleanComment : '',
        'submitted_at_label' => $it['submitted_at'] ? date('M d, Y g:i A', strtotime($it['submitted_at'])) : 'Unknown date',
        'period_label'       => (!empty($it['school_year']) && !empty($it['semester'])) ? ($it['school_year'].' · '.$it['semester']) : ($it['period_label'] ?: $it['semester']),
    ];
    $n++;
}
$responseCount = count($history);
$overallAvg    = $totalCount > 0 ? round($totalSum / $totalCount, 2) : null;

// ── SUMMARY STATS FOR THE OVERVIEW CARDS ────────────────────────────
// All aggregates only — nothing here touches evaluator identity.
$scoredCount = 0; $commentCount = 0;
foreach ($history as $h) {
    if ($h['score'] !== null) $scoredCount++;
    if ($h['comment_ok']) $commentCount++;
}

// Category averages across every response shown.
$catAgg = []; $liveIds = []; $keptIds = [];
foreach ($history as $h) {
    if ($h['_source'] === 'kept') { $keptIds[] = (int)$h['_tracker_id']; } else { $liveIds[] = (int)$h['_tracker_id']; }
}
if ($liveIds) {
    $inLive = implode(',', array_map('intval', $liveIds));
    $catRows = safe_rows($mysqli, "
        SELECT COALESCE(uq.category, eq.category, 'General') AS category, SUM(qa.answer_score) AS s, COUNT(qa.answer_score) AS c
        FROM questionnaire_answers qa
        LEFT JOIN user_questions uq ON qa.question_source='user' AND uq.id=qa.user_question_id
        LEFT JOIN evaluation_questions eq ON qa.question_source='evaluation' AND eq.id=qa.question_id
        WHERE qa.tracker_id IN ($inLive)
        GROUP BY COALESCE(uq.category, eq.category, 'General')
    ");
    foreach ($catRows as $r) {
        $cat = trim((string)$r['category']) !== '' ? trim((string)$r['category']) : 'General';
        if (!isset($catAgg[$cat])) $catAgg[$cat] = [0.0, 0];
        $catAgg[$cat][0] += (float)$r['s'];
        $catAgg[$cat][1] += (int)$r['c'];
    }
}
if ($keptIds) {
    $inKept = implode(',', array_map('intval', $keptIds));
    $keptAns = safe_rows($mysqli, "SELECT answers_json FROM feedback_received_keep WHERE target_user_id=? AND id IN ($inKept)", 'i', [$deanId]);
    foreach ($keptAns as $r) {
        $list = json_decode((string)$r['answers_json'], true);
        if (!is_array($list)) continue;
        foreach ($list as $row) {
            if (!isset($row['score'])) continue;
            $cat = !empty($row['category']) ? (string)$row['category'] : 'General';
            if (!isset($catAgg[$cat])) $catAgg[$cat] = [0.0, 0];
            $catAgg[$cat][0] += (float)$row['score'];
            $catAgg[$cat][1] += 1;
        }
    }
}
$catStats = [];
foreach ($catAgg as $name => $v) {
    if ($v[1] > 0) $catStats[] = ['name' => (string)$name, 'avg' => round($v[0] / $v[1], 2)];
}
usort($catStats, function ($a, $b) { return $b['avg'] <=> $a['avg']; });
$topCat = $catStats[0] ?? null;
$lowCat = count($catStats) > 1 ? $catStats[count($catStats) - 1] : null;

// Change versus the previous term (only when a single term is selected).
$trendText = ''; $trendClass = ''; $prevTermLabel = '';
if (!$allPeriodsSelected && $selPeriod > 0 && $overallAvg !== null) {
    $termKeys = array_keys($terms);
    $pos = array_search($selPeriod, $termKeys, true);
    if ($pos !== false && isset($termKeys[$pos + 1])) {
        $prevId = (int)$termKeys[$pos + 1];
        $pSum = 0.0; $pCnt = 0;
        $pl = safe_rows($mysqli, "SELECT SUM(qa.answer_score) AS s, COUNT(qa.answer_score) AS c
            FROM questionnaire_answers qa INNER JOIN evaluation_tracker et ON et.id = qa.tracker_id
            WHERE " . EA_EVAL_TYPE_ELIGIBLE . " AND et.period_id=?", 'ssii', [SCHOOL_HEAD_EVAL_TYPE, SCHOOL_HEAD_CONTEXT, $deanId, $prevId]);
        if ($pl) { $pSum += (float)$pl[0]['s']; $pCnt += (int)$pl[0]['c']; }
        $pk = safe_rows($mysqli, "SELECT SUM(score_sum) AS s, SUM(score_count) AS c FROM feedback_received_keep WHERE target_user_id=? AND period_id=?", 'ii', [$deanId, $prevId]);
        if ($pk) { $pSum += (float)$pk[0]['s']; $pCnt += (int)$pk[0]['c']; }
        if ($pCnt > 0) {
            $d = round($overallAvg - ($pSum / $pCnt), 2);
            $prevTermLabel = $terms[$prevId]['label'];
            if ($d > 0)      { $trendClass = 'up';   $trendText = '▲ +' . number_format($d, 2); }
            elseif ($d < 0)  { $trendClass = 'down'; $trendText = '▼ -' . number_format(abs($d), 2); }
            else             { $trendClass = 'flat'; $trendText = 'No change'; }
        }
    }
}

function er_score_class($score) {
    if ($score === null) return 'none';
    if ($score >= 4) return 'high';
    if ($score >= 3) return 'mid';
    return 'low';
}
$displayList = array_reverse($history);   // newest first by default
$starsOn = $overallAvg !== null ? (int)round($overallAvg) : 0;

$mysqli->close();
?>
<!DOCTYPE html>
<html lang="en" class="dean-internal-scroll-page">
<head>
<meta charset="UTF-8"/>
<meta name="viewport" content="width=device-width, initial-scale=1.0"/>
<title>PBI — Evaluation Received</title>
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
.page-header{margin-bottom:26px;}
.page-title{font-family:'Rajdhani',sans-serif;font-size:30px;font-weight:700;color:#fff;letter-spacing:1px;}
.page-sub{font-size:13px;color:var(--muted);margin-top:4px;}
.page-header{display:flex;justify-content:space-between;align-items:flex-end;gap:16px;flex-wrap:wrap;}
.term-picker{display:flex;flex-direction:column;gap:5px;min-width:260px;}
.term-picker label{font-size:11px;font-weight:700;letter-spacing:.06em;text-transform:uppercase;color:var(--muted);display:flex;align-items:center;gap:6px;}
.term-picker select{appearance:none;-webkit-appearance:none;width:100%;padding:10px 38px 10px 14px;border-radius:10px;border:1px solid #B9CDE5;background:#fff url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='8' viewBox='0 0 12 8'%3E%3Cpath d='M1 1l5 5 5-5' fill='none' stroke='%2367819E' stroke-width='2' stroke-linecap='round'/%3E%3C/svg%3E") no-repeat right 14px center;color:#102746;font:600 13px 'DM Sans',sans-serif;cursor:pointer;}
.term-picker select:hover{border-color:var(--violet);}
.term-picker select:focus{outline:3px solid rgba(124,95,217,.28);outline-offset:1px;border-color:var(--violet);}

.confidentiality-note{display:flex;align-items:flex-start;gap:14px;padding:16px 20px;background:rgba(16,185,129,.08);border:1px solid rgba(16,185,129,.3);border-radius:12px;margin-bottom:26px;}
.confidentiality-note i{color:var(--good);font-size:18px;margin-top:2px;}
.confidentiality-note p{font-size:12.5px;color:var(--light);line-height:1.6;}

.card-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:16px;margin-bottom:26px;}
.stat-card{background:rgba(23,42,69,.85);border:1px solid rgba(255,255,255,.08);border-radius:14px;padding:20px;box-shadow:var(--shadow);}
.stat-card i{color:var(--violet-h);font-size:20px;margin-bottom:10px;}
.stat-card .num{font-size:28px;font-weight:700;color:#fff;}
.stat-card .label{font-size:12px;color:var(--muted);margin-top:4px;}

.section{background:rgba(23,42,69,.85);border:1px solid rgba(255,255,255,.08);border-radius:14px;padding:24px;box-shadow:var(--shadow);margin-bottom:26px;}
.section h2{font-family:'Rajdhani',sans-serif;font-size:19px;color:#fff;margin-bottom:16px;display:flex;align-items:center;gap:8px;}
.section h2 i{color:var(--violet-h);font-size:16px;}

.cat-row{display:flex;align-items:center;gap:14px;padding:10px 0;border-bottom:1px solid rgba(255,255,255,.05);}
.cat-row:last-child{border-bottom:none;}
.cat-name{width:180px;font-size:13px;color:var(--light);flex-shrink:0;}
.bar-wrap{flex:1;background:rgba(255,255,255,.08);border-radius:6px;height:8px;overflow:hidden;}
.bar-fill{height:100%;background:linear-gradient(90deg,var(--violet-dark),var(--violet-h));border-radius:6px;}
.cat-score{width:48px;text-align:right;font-size:13px;font-weight:700;color:#fff;flex-shrink:0;}

.trend-row{display:flex;justify-content:space-between;padding:9px 0;border-bottom:1px solid rgba(255,255,255,.05);font-size:13px;}
.trend-row:last-child{border-bottom:none;}
.trend-row .val{color:var(--violet-h);font-weight:700;}

.response-card{background:var(--inner);border:1px solid rgba(255,255,255,.06);border-radius:10px;padding:16px 18px;margin-bottom:12px;}
.response-card:last-child{margin-bottom:0;}
.response-head{display:flex;justify-content:space-between;align-items:center;margin-bottom:8px;}
.response-label{font-size:12px;font-weight:700;color:var(--violet-h);text-transform:uppercase;letter-spacing:.6px;display:flex;align-items:center;gap:6px;}
.response-score{font-size:13px;font-weight:700;color:#fff;background:rgba(124,95,217,.15);padding:2px 10px;border-radius:12px;}
.response-comment{font-size:13.5px;color:var(--light);line-height:1.6;font-style:italic;}

.empty-note{color:var(--muted);font-size:13px;font-style:italic;}


.view-evals-btn{width:100%;display:flex;align-items:center;gap:10px;background:rgba(124,95,217,.12);border:1px solid rgba(124,95,217,.28);color:#d8cffd;padding:12px 14px;border-radius:10px;font:600 13px ''DM Sans'',sans-serif;cursor:pointer;}
.view-evals-btn i:last-child{margin-left:auto;transition:transform .2s}.received-item{display:flex;justify-content:space-between;gap:14px;align-items:center;background:var(--inner);border:1px solid rgba(255,255,255,.06);border-radius:10px;padding:14px 16px;margin-bottom:10px;cursor:pointer}.received-item:hover{border-color:rgba(124,95,217,.35)}.received-anon{font-size:13px;font-weight:700;color:#fff}.received-anon i{color:var(--muted);margin-right:5px}.received-meta{font-size:11px;color:var(--muted);margin-top:4px}.received-right{display:flex;flex-direction:column;align-items:flex-end;gap:8px}.received-score{font-size:12px;font-weight:800;color:#bdebd9;background:rgba(16,185,129,.12);border:1px solid rgba(16,185,129,.25);padding:4px 10px;border-radius:18px}.details-btn{background:rgba(13,148,136,.12);border:1px solid rgba(13,148,136,.28);color:#5eead4;font-size:11px;font-weight:700;padding:6px 12px;border-radius:18px;cursor:pointer}.eval-modal-overlay{position:fixed;inset:0;background:rgba(0,0,0,.78);z-index:500;display:none;align-items:center;justify-content:center;padding:20px}.eval-modal-overlay.open{display:flex}.eval-modal{background:var(--mid);border:1px solid rgba(255,255,255,.08);border-radius:16px;width:100%;max-width:720px;max-height:88vh;display:flex;flex-direction:column;box-shadow:0 24px 80px rgba(0,0,0,.6)}.eval-modal-header{display:flex;align-items:center;justify-content:space-between;padding:18px 22px;border-bottom:1px solid rgba(255,255,255,.08)}.eval-modal-title{font-family:'Rajdhani',sans-serif;font-size:21px;font-weight:700;color:#fff}.eval-modal-title i{color:#9C85F0;margin-right:8px}.eval-modal-close{background:none;border:none;color:var(--muted);font-size:19px;cursor:pointer}.eval-modal-body{padding:22px;overflow:auto}.info-grid{display:grid;grid-template-columns:1fr 1fr;gap:14px;margin-bottom:20px}.info-grid>div{background:var(--inner);border:1px solid rgba(255,255,255,.05);border-radius:10px;padding:12px 14px}.info-label{font-size:10px;color:var(--muted);text-transform:uppercase;letter-spacing:.7px;margin-bottom:5px}.info-value{font-size:13px;color:#fff;font-weight:600}.info-value i{color:var(--muted);margin-right:4px}.score-big{color:#5eead4}.modal-section-title{font-size:14px;color:#fff;margin:18px 0 10px}.cat-row-modal{display:flex;align-items:center;gap:10px;margin:9px 0}.cat-name-modal{width:170px;font-size:12px;color:var(--light);flex-shrink:0}.cat-bar{flex:1;height:7px;background:rgba(255,255,255,.08);border-radius:6px;overflow:hidden}.cat-bar>div{height:100%;background:linear-gradient(90deg,#5f45b8,#9c85f0);border-radius:6px}.cat-score-modal{width:42px;text-align:right;font-size:12px;font-weight:700;color:#fff}.q-result{background:var(--inner);border:1px solid rgba(255,255,255,.06);border-radius:10px;padding:13px 15px;margin-bottom:8px}.q-no{font-size:10px;color:var(--muted);text-transform:uppercase;letter-spacing:.7px;margin-bottom:4px}.q-text{font-size:13px;color:#fff;font-weight:600;line-height:1.5}.q-score{margin-top:7px;font-size:12px;color:var(--muted)}.q-score span{margin-left:7px;font-weight:700}.dean-star{color:rgba(255,255,255,.16);margin-right:2px}.dean-star.filled{color:#facc15}.comment-modal{background:var(--inner);border:1px solid rgba(255,255,255,.06);border-radius:10px;padding:14px;color:var(--light);font-size:13px;line-height:1.6;font-style:italic}.comment-modal.empty{color:var(--muted);font-style:normal}.loading-eval{padding:50px 10px;text-align:center;color:var(--muted);font-size:13px}.loading-eval i{margin-right:8px}
@media(max-width:768px){body{flex-direction:column;}.sidebar{width:100%;min-height:auto;}.cat-name{width:120px;}}
</style>
<link rel="stylesheet" href="includes/dean_light_theme.css?v=dashboard-ui-20261009"/>

<style>
/* Keep the Dean sidebar fixed and scroll this feature workspace internally. */
html.dean-internal-scroll-page,
html.dean-internal-scroll-page body {
  overflow: hidden !important;
  height: 100% !important;
}

main.main.dean-internal-scroll {
  height: calc(100vh - 20px) !important;
  max-height: calc(100vh - 20px) !important;
  min-height: 0 !important;
  overflow-y: scroll !important;
  overflow-x: hidden !important;
  scrollbar-gutter: stable;
  overscroll-behavior: contain;
  scrollbar-width: thin;
  scrollbar-color: #AEBAC8 #EEF2F6;
}

main.main.dean-internal-scroll::-webkit-scrollbar {
  width: 10px;
}

main.main.dean-internal-scroll::-webkit-scrollbar-track {
  background: #EEF2F6;
  border-radius: 10px;
}

main.main.dean-internal-scroll::-webkit-scrollbar-thumb {
  background: #AEBAC8;
  border: 2px solid #EEF2F6;
  border-radius: 10px;
}

main.main.dean-internal-scroll::-webkit-scrollbar-thumb:hover {
  background: #8F9CAB;
}

html.dark-theme main.main.dean-internal-scroll {
  scrollbar-color: #2A4468 #0F1F3D;
}

html.dark-theme main.main.dean-internal-scroll::-webkit-scrollbar-track {
  background: #0F1F3D;
}

html.dark-theme main.main.dean-internal-scroll::-webkit-scrollbar-thumb {
  background: #2A4468;
  border-color: #0F1F3D;
}

html.dark-theme main.main.dean-internal-scroll::-webkit-scrollbar-thumb:hover {
  background: #385A86;
}

@media (max-width: 768px) {
  html.dean-internal-scroll-page,
  html.dean-internal-scroll-page body {
    overflow: auto !important;
    height: auto !important;
  }

  main.main.dean-internal-scroll {
    height: auto !important;
    max-height: none !important;
    min-height: calc(100vh - 12px) !important;
    overflow: visible !important;
    scrollbar-gutter: auto;
  }
}
</style>
</head>
<body>

<?php
$active = 'results';
$sidebarScope = HIGHER_ED_LABEL . ' Division';
include __DIR__ . '/includes/dean_sidebar.php'; if (is_file(__DIR__ . '/dean_header_dark.php')) include __DIR__ . '/dean_header_dark.php';
?>

<main class="main dean-internal-scroll">
    <?php if (!$showData): ?>
        <p class="empty-note">No evaluation term is available yet.</p>
    <?php else: ?>

    <!-- OVERVIEW -->
    <div class="card-grid er-stats">
        <div class="stat-card">
            <div class="er-stat-icon"><i class="fa-solid fa-star"></i></div>
            <div class="er-stat-body">
                <div class="num"><?= $overallAvg !== null ? $overallAvg : '—' ?><?php if ($overallAvg !== null): ?><span class="er-num-unit">/ 5</span><?php endif; ?></div>
                <div class="label">Overall Average</div>
                <?php if ($overallAvg !== null): ?>
                <div class="er-stat-sub">
                    <span class="er-stars" aria-hidden="true"><?php for ($i = 1; $i <= 5; $i++): ?><span class="<?= $i <= $starsOn ? 'on' : '' ?>">★</span><?php endfor; ?></span>
                    <?php if ($trendText !== ''): ?>
                        <span class="er-trend <?= $trendClass ?>"><?= htmlspecialchars($trendText) ?></span> vs <?= htmlspecialchars($prevTermLabel) ?>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
            </div>
        </div>
        <div class="stat-card">
            <div class="er-stat-icon"><i class="fa-solid fa-comments"></i></div>
            <div class="er-stat-body">
                <div class="num"><?= (int)$responseCount ?></div>
                <div class="label">Responses Received</div>
                <?php if ($responseCount > 0): ?><div class="er-stat-sub"><?= (int)$scoredCount ?> scored · <?= (int)$commentCount ?> with comments</div><?php endif; ?>
            </div>
        </div>
        <?php if ($topCat): ?>
        <div class="stat-card">
            <div class="er-stat-icon"><i class="fa-solid fa-arrow-trend-up"></i></div>
            <div class="er-stat-body">
                <div class="num"><?= htmlspecialchars((string)$topCat['avg']) ?><span class="er-num-unit">/ 5</span></div>
                <div class="label">Highest-Rated Area</div>
                <div class="er-stat-sub"><?= htmlspecialchars($topCat['name']) ?></div>
            </div>
        </div>
        <?php endif; ?>
        <?php if ($lowCat): ?>
        <div class="stat-card">
            <div class="er-stat-icon"><i class="fa-solid fa-arrow-trend-down"></i></div>
            <div class="er-stat-body">
                <div class="num"><?= htmlspecialchars((string)$lowCat['avg']) ?><span class="er-num-unit">/ 5</span></div>
                <div class="label">Lowest-Rated Area</div>
                <div class="er-stat-sub"><?= htmlspecialchars($lowCat['name']) ?></div>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <?php if ($catStats): ?>
    <!-- PERFORMANCE BY CATEGORY -->
    <div class="section">
        <div class="er-head">
            <h2><i class="fa-solid fa-chart-simple"></i> Performance by Category</h2>
            <span class="er-shown">Average score out of 5</span>
        </div>
        <?php foreach ($catStats as $ci => $c):
            $pct = max(0, min(100, round(($c['avg'] / 5) * 100)));
            $tag = ($ci === 0 && count($catStats) > 1) ? 'Highest' : (($ci === count($catStats) - 1 && count($catStats) > 1) ? 'Lowest' : '');
        ?>
        <div class="er-cat">
            <div class="er-cat-name"><?= htmlspecialchars($c['name']) ?><?php if ($tag): ?> <span class="er-cat-tag <?= strtolower($tag) ?>"><?= $tag ?></span><?php endif; ?></div>
            <div class="er-cat-track"><div class="er-cat-fill <?= er_score_class($c['avg']) ?>" style="width:<?= $pct ?>%"></div></div>
            <div class="er-cat-score"><?= number_format($c['avg'], 2) ?><small> / 5</small></div>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <!-- EVALUATIONS RECEIVED (anonymous) -->
    <div class="section">
        <div class="er-head">
            <h2><i class="fa-solid fa-clock-rotate-left"></i> Evaluations Received <span class="er-count"><?= (int)$responseCount ?></span></h2>
            <?php if ($terms): ?>
            <form method="get" class="term-picker">
                <label for="termSelect"><i class="fa-solid fa-calendar-days"></i> Evaluation Term</label>
                <select id="termSelect" name="period" onchange="this.form.submit()">
                    <option value="all"<?= $allPeriodsSelected ? ' selected' : '' ?>>All Evaluation Terms — Past &amp; Current</option>
                    <?php foreach ($terms as $t): ?>
                    <option value="<?= (int)$t['id'] ?>"<?= !$allPeriodsSelected && (int)$t['id'] === $selPeriod ? ' selected' : '' ?>><?= htmlspecialchars($t['label']) ?><?= $t['current'] ? ' (Current)' : ($t['archived'] ? ' (Archived)' : '') ?></option>
                    <?php endforeach; ?>
                </select>
            </form>
            <?php endif; ?>
        </div>

        <?php if (empty($history)): ?>
            <p class="empty-note"><?= $allPeriodsSelected ? 'No responses were submitted across any evaluation term.' : 'No responses were submitted for this term.' ?></p>
        <?php else: ?>
            <div class="er-toolbar">
                <div class="er-controls">
                    <select id="erSort" class="er-select" onchange="erApply()" aria-label="Sort evaluations">
                        <option value="newest">Newest first</option>
                        <option value="oldest">Oldest first</option>
                        <option value="high">Highest score</option>
                        <option value="low">Lowest score</option>
                    </select>
                    <label class="er-chip"><input type="checkbox" id="erOnlyComments" onchange="erApply()"> With comments only</label>
                </div>
                <span class="er-shown" id="erShown">Showing <?= (int)$responseCount ?> of <?= (int)$responseCount ?></span>
            </div>

            <div id="erList">
            <?php foreach ($displayList as $h):
                $src = $h['_source'] === 'kept' ? 'kept' : 'live';
                $cls = er_score_class($h['score']);
                $open = 'openDeanEvalDetails(' . (int)$h['_tracker_id'] . ",'" . $src . "')";
            ?>
                <div class="er-item" role="button" tabindex="0"
                     data-ts="<?= (int)$h['ts'] ?>" data-idx="<?= (int)$h['idx'] ?>"
                     data-score="<?= $h['score'] !== null ? htmlspecialchars((string)$h['score']) : '-1' ?>"
                     data-comment="<?= $h['comment_ok'] ? '1' : '0' ?>"
                     onclick="<?= $open ?>"
                     onkeydown="if(event.target===this&&(event.key==='Enter'||event.key===' ')){event.preventDefault();<?= $open ?>}">
                    <div class="er-main">
                        <div class="er-eyebrow"><i class="fa-solid fa-user-secret"></i> Anonymous — <?= htmlspecialchars($h['label']) ?></div>
                        <div class="er-meta"><?= htmlspecialchars($h['submitted_at_label']) ?><?= !empty($h['period_label']) ? ' · ' . htmlspecialchars($h['period_label']) : '' ?></div>
                        <?php if ($h['comment_ok']): ?>
                            <p class="er-comment">&ldquo;<?= htmlspecialchars($h['comment']) ?>&rdquo;</p>
                        <?php else: ?>
                            <p class="er-comment none">No written comment</p>
                        <?php endif; ?>
                    </div>
                    <div class="er-side">
                        <?php if ($h['score'] !== null): ?>
                            <span class="er-badge <?= $cls ?>"><?= htmlspecialchars((string)$h['score']) ?><small> / 5</small></span>
                        <?php else: ?>
                            <span class="er-badge none">No score</span>
                        <?php endif; ?>
                        <button type="button" class="details-btn" onclick="event.stopPropagation();<?= $open ?>">View Details <i class="fa-solid fa-chevron-right"></i></button>
                    </div>
                </div>
            <?php endforeach; ?>
            </div>
            <p class="empty-note" id="erNoMatch" style="display:none;">No evaluations match this filter.</p>
        <?php endif; ?>
    </div>

    <?php endif; ?>
</main>

<div class="eval-modal-overlay" id="deanEvalModal">
  <div class="eval-modal">
    <div class="eval-modal-header"><div class="eval-modal-title"><i class="fa-solid fa-star"></i> Evaluation Details</div><button class="eval-modal-close" onclick="closeDeanEvalDetails()"><i class="fa-solid fa-xmark"></i></button></div>
    <div class="eval-modal-body" id="deanEvalBody"><div class="empty-note">Loading evaluation…</div></div>
  </div>
</div>

<script>
function erApply(){const list=document.getElementById('erList');if(!list)return;const items=[].slice.call(list.querySelectorAll('.er-item'));const mode=document.getElementById('erSort').value;const only=document.getElementById('erOnlyComments').checked;const n=(el,k)=>parseFloat(el.dataset[k])||0;const lowKey=el=>{const v=parseFloat(el.dataset.score);return v<0?Infinity:v;};
items.sort((a,b)=>{if(mode==='oldest')return n(a,'ts')-n(b,'ts')||n(a,'idx')-n(b,'idx');if(mode==='high')return parseFloat(b.dataset.score)-parseFloat(a.dataset.score)||n(b,'ts')-n(a,'ts');if(mode==='low'){const x=lowKey(a),y=lowKey(b);return x===y?n(b,'ts')-n(a,'ts'):(x<y?-1:1);}return n(b,'ts')-n(a,'ts')||n(b,'idx')-n(a,'idx');});
let shown=0;items.forEach(el=>{const ok=!only||el.dataset.comment==='1';el.style.display=ok?'':'none';if(ok)shown++;list.appendChild(el);});
document.getElementById('erShown').textContent='Showing '+shown+' of '+items.length;document.getElementById('erNoMatch').style.display=shown?'none':'block';}
function toggleDeanEvals(){const l=document.getElementById('deanEvalsList'),c=document.getElementById('deanEvalsCaret');const open=l.style.display!=='none';l.style.display=open?'none':'block';c.style.transform=open?'':'rotate(180deg)';}
function escDean(v){if(v===null||v===undefined)return '';const d=document.createElement('div');d.textContent=v;return d.innerHTML;}
function starsDean(score){let h='';for(let i=1;i<=5;i++)h+=`<i class="fa-solid fa-star dean-star ${i<=score?'filled':''}"></i>`;return h;}
function openDeanEvalDetails(id,src){const m=document.getElementById('deanEvalModal'),b=document.getElementById('deanEvalBody');b.innerHTML='<div class="loading-eval"><i class="fa-solid fa-spinner fa-spin"></i> Loading evaluation…</div>';m.classList.add('open');document.body.style.overflow='hidden';fetch('get_my_evaluation_details.php?'+(src==='kept'?'kept_id=':'tracker_id=')+encodeURIComponent(id)).then(r=>r.json()).then(d=>{if(!d.ok){b.innerHTML='<div class="loading-eval"><i class="fa-solid fa-triangle-exclamation"></i>'+escDean(d.error||'Unable to load this evaluation.')+'</div>';return;}let h=`<div class="info-grid"><div><div class="info-label">Evaluator</div><div class="info-value"><i class="fa-solid fa-eye-slash"></i> Anonymous Evaluator</div></div><div><div class="info-label">Period</div><div class="info-value">${escDean(d.period_label||'—')}</div></div><div><div class="info-label">Submitted</div><div class="info-value">${escDean(d.submitted_at||'—')}</div></div><div><div class="info-label">Overall Score</div><div class="info-value score-big">${Number(d.overall_score||0).toFixed(2)} / 5</div></div></div>`;if(d.categories?.length){h+='<h3 class="modal-section-title">Performance by Category</h3>';d.categories.forEach(c=>{const pct=Math.round((c.avg/5)*100);h+=`<div class="cat-row-modal"><div class="cat-name-modal">${escDean(c.category)}</div><div class="cat-bar"><div style="width:${pct}%"></div></div><div class="cat-score-modal">${Number(c.avg).toFixed(2)}</div></div>`})}if(d.questions?.length){h+='<h3 class="modal-section-title">Question-by-Question Results</h3>';d.questions.forEach((q,i)=>{h+=`<div class="q-result"><div class="q-no">Question ${i+1}</div><div class="q-text">${escDean(q.question_text)}</div><div class="q-score">${starsDean(q.score)} <span>Score: ${q.score} / 5</span></div></div>`})}h+='<h3 class="modal-section-title">Comments / Feedback</h3>';h+=d.comment?`<div class="comment-modal">“${escDean(d.comment)}”</div>`:'<div class="comment-modal empty">No written feedback was provided.</div>';b.innerHTML=h;}).catch(()=>{b.innerHTML='<div class="loading-eval"><i class="fa-solid fa-triangle-exclamation"></i>Something went wrong loading this evaluation.</div>';});}
function closeDeanEvalDetails(){document.getElementById('deanEvalModal').classList.remove('open');document.body.style.overflow='';}
document.getElementById('deanEvalModal').addEventListener('click',function(e){if(e.target===this)closeDeanEvalDetails();});
document.addEventListener('keydown',function(e){if(e.key==='Escape')closeDeanEvalDetails();});
</script>
</body>
</html>
</body>
<link rel="stylesheet" href="includes/dean_light_theme.css?v=dashboard-ui-20261009" id="dean-light-theme-final"/>
</html>

<style id="dean-results-violet">
/* Dean theme (violet) for this page's content. The shared light theme forces
   blue (#2563EB) on icons and stat-card top borders; override it here. */
html:not(.dark-theme) main.main .section h2 i,
html:not(.dark-theme) main.main .section-title i,
html:not(.dark-theme) main.main .page-header i,
html:not(.dark-theme) main.main .stat-card i,
html:not(.dark-theme) main.main .response-label i,
html:not(.dark-theme) main.main .view-evals-btn i { color:#7C5FD9 !important; }
html:not(.dark-theme) main.main .stat-card,
html:not(.dark-theme) main.main .card-grid > .stat-card:nth-child(n),
html:not(.dark-theme) main.main .card-grid > a:nth-child(n) .stat-card {
  border-top:3px solid #7C5FD9 !important;
  border-top-color:#7C5FD9 !important;
}
html:not(.dark-theme) main.main .term-picker select { border-color:#D9CFF7 !important; }
html:not(.dark-theme) main.main .term-picker select:hover,
html:not(.dark-theme) main.main .term-picker select:focus { border-color:#7C5FD9 !important; }
</style>

<style id="er-view">
/* ── Evaluation Received — refreshed layout ─────────────────────── */
main.main .er-stats{grid-template-columns:repeat(auto-fit,minmax(230px,1fr));gap:14px;margin-bottom:20px;}
main.main .er-stats .stat-card{display:flex;align-items:flex-start;gap:14px;padding:16px 18px;}
.er-stat-icon{flex:0 0 40px;width:40px;height:40px;border-radius:12px;background:rgba(124,95,217,.12);display:flex;align-items:center;justify-content:center;}
main.main .er-stats .stat-card .er-stat-icon i{margin:0;font-size:17px;}
.er-stat-body{min-width:0;}
.er-stats .stat-card .num{font-size:28px;line-height:1.1;}
.er-num-unit{font-size:14px;font-weight:600;color:var(--muted);margin-left:4px;}
.er-stat-sub{font-size:12px;color:var(--muted);margin-top:6px;line-height:1.5;overflow-wrap:anywhere;}
.er-stars{letter-spacing:2px;font-size:14px;margin-right:6px;}
.er-stars span{color:#D5DDE6;}
.er-stars span.on{color:#F5B301;}
.er-trend{font-weight:700;}
.er-trend.up{color:#059669;}
.er-trend.down{color:#DC2626;}
.er-trend.flat{color:var(--muted);}

.er-head{display:flex;justify-content:space-between;align-items:center;gap:14px;flex-wrap:wrap;margin-bottom:14px;}
.er-head h2{margin-bottom:0 !important;}
.er-count{font:700 12px 'DM Sans',sans-serif;background:rgba(124,95,217,.12);color:#5F45B8;padding:2px 10px;border-radius:12px;margin-left:4px;}
.er-head .term-picker{flex-direction:row;align-items:center;gap:10px;min-width:0;}
.er-head .term-picker select{width:auto;min-width:270px;}

.er-toolbar{display:flex;justify-content:space-between;align-items:center;gap:12px;flex-wrap:wrap;padding:12px 0 14px;border-top:1px solid var(--border);}
.er-controls{display:flex;align-items:center;gap:10px;flex-wrap:wrap;}
.er-select{padding:7px 12px;border-radius:9px;border:1px solid #D9CFF7;background:var(--inner);color:var(--light);font:500 13px 'DM Sans',sans-serif;cursor:pointer;}
.er-select:focus{outline:3px solid rgba(124,95,217,.28);outline-offset:1px;border-color:#7C5FD9;}
.er-chip{display:inline-flex;align-items:center;gap:7px;padding:7px 12px;border-radius:9px;border:1px solid #D9CFF7;font:500 13px 'DM Sans',sans-serif;color:var(--light);cursor:pointer;user-select:none;}
.er-chip input{accent-color:#7C5FD9;}
.er-shown{font-size:12px;color:var(--muted);}

.er-item{display:flex;justify-content:space-between;align-items:center;gap:18px;background:var(--inner);border:1px solid var(--border);border-radius:12px;padding:14px 18px;margin-bottom:10px;cursor:pointer;transition:border-color .15s,box-shadow .15s;}
.er-item:last-child{margin-bottom:0;}
.er-item:hover,.er-item:focus-visible{border-color:#7C5FD9;box-shadow:0 2px 12px rgba(124,95,217,.16);outline:none;}
.er-main{min-width:0;flex:1;}
.er-eyebrow{font-size:12px;font-weight:700;letter-spacing:.06em;text-transform:uppercase;color:var(--muted);display:flex;align-items:center;gap:6px;}
.er-eyebrow i{color:#7C5FD9;}
.er-meta{font-size:12px;color:var(--muted);margin-top:3px;}
.er-comment{margin-top:8px;font-size:13.5px;line-height:1.55;font-style:italic;color:var(--light);display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden;}
.er-comment.none{font-style:normal;color:var(--muted);}
.er-side{display:flex;flex-direction:column;align-items:flex-end;gap:8px;flex-shrink:0;}
.er-badge{font-size:13px;font-weight:800;padding:4px 12px;border-radius:18px;border:1px solid transparent;white-space:nowrap;}
.er-badge small{font-size:11px;font-weight:600;opacity:.75;}
.er-badge.high{background:rgba(16,185,129,.12);color:#047857;border-color:rgba(16,185,129,.3);}
.er-badge.mid{background:rgba(245,158,11,.14);color:#B45309;border-color:rgba(245,158,11,.35);}
.er-badge.low{background:rgba(239,68,68,.12);color:#B91C1C;border-color:rgba(239,68,68,.32);}
.er-badge.none{background:rgba(109,129,148,.12);color:var(--muted);border-color:rgba(109,129,148,.25);font-weight:700;font-size:12px;}
.details-btn:hover{filter:brightness(.95);}

@media(max-width:640px){
  .er-item{flex-direction:column;align-items:flex-start;gap:12px;}
  .er-side{flex-direction:row;align-items:center;justify-content:space-between;width:100%;}
  .er-head .term-picker{flex-direction:column;align-items:stretch;width:100%;}
  .er-head .term-picker select{width:100%;min-width:0;}
}

/* Performance by Category */
.er-cat{display:grid;grid-template-columns:minmax(150px,230px) 1fr 84px;align-items:center;gap:16px;padding:10px 0;}
.er-cat-name{font-size:13.5px;font-weight:600;color:var(--light);line-height:1.35;}
.er-cat-tag{display:inline-block;margin-left:6px;font:700 10px 'DM Sans',sans-serif;letter-spacing:.05em;text-transform:uppercase;padding:2px 7px;border-radius:10px;vertical-align:middle;}
.er-cat-tag.highest{background:rgba(16,185,129,.12);color:#047857;}
.er-cat-tag.lowest{background:rgba(245,158,11,.14);color:#B45309;}
.er-cat-track{height:10px;background:#E8EDF4;border-radius:8px;overflow:hidden;}
.er-cat-fill{height:100%;border-radius:8px;}
.er-cat-fill.high{background:linear-gradient(90deg,#059669,#34D399);}
.er-cat-fill.mid{background:linear-gradient(90deg,#D97706,#FBBF24);}
.er-cat-fill.low{background:linear-gradient(90deg,#DC2626,#F87171);}
.er-cat-score{text-align:right;font-size:15px;font-weight:800;color:var(--light);white-space:nowrap;}
.er-cat-score small{font-size:11px;font-weight:600;color:var(--muted);}
@media(max-width:640px){.er-cat{grid-template-columns:1fr 70px;}.er-cat-name{grid-column:1/-1;}.er-cat-track{grid-column:1;}}

/* ── Evaluation Details popup — cover the whole page (sidebar + header) ── */
html .eval-modal-overlay{z-index:100000 !important;background:rgba(3,10,22,.55) !important;-webkit-backdrop-filter:blur(4px);backdrop-filter:blur(4px);}
html.dark-theme .eval-modal-overlay{background:rgba(0,0,0,.65) !important;}
</style>