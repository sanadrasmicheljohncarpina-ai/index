<?php
// admin/ea_results.php
// Anonymous evaluation results received by the Executive Assistant (EA).
// All submitted evaluations of the EA (Principal, Dean, Staff, ...). Evaluator
// identity is deliberately never exposed on this page, including in the
// detail view, to preserve anonymous feedback.

session_set_cookie_params([
    'lifetime' => 0,
    'path' => '/',
    'domain' => '',
    'secure' => false,
    'httponly' => true,
    'samesite' => 'Lax',
]);
session_start();
require_once 'db.php';
require_once dirname(__DIR__) . '/shared/system_settings_service.php';

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

$role = $_SESSION['role'] ?? '';
if (empty($_SESSION['user_id']) || !in_array($role, ['superadmin', 'executive_assistant'], true)) {
    http_response_code(403);
    exit('Unauthorized');
}

$ea_id = (int)$_SESSION['user_id'];
$escape = static fn($v) => htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');

function safe_rows(mysqli $mysqli, string $sql, string $types = '', array $params = []): array {
    try {
        $stmt = $mysqli->prepare($sql);
        if (!$stmt) return [];
        if ($types !== '') { $stmt->bind_param($types, ...$params); }
        $stmt->execute();
        $res = $stmt->get_result();
        $rows = $res ? $res->fetch_all(MYSQLI_ASSOC) : [];
        $stmt->close();
        return $rows;
    } catch (mysqli_sql_exception $e) {
        return [];
    }
}

// Resolve the currently signed-in EA. The role check above makes this the EA
// whose own results should be displayed, rather than another person's records.
$ea = $mysqli->prepare("SELECT id, full_name, designation, photo FROM users WHERE id=? LIMIT 1");
$ea->bind_param('i', $ea_id);
$ea->execute();
$eaRow = $ea->get_result()->fetch_assoc();
$ea->close();

if (!$eaRow) {
    http_response_code(404);
    exit('Executive Assistant account not found.');
}

// Received evaluations = every submitted evaluation whose target is this EA
// account, whoever the authorized evaluator group is (Principal, Dean, Staff,
// Faculty ...) and whichever eval_type their portal writes. The EA's own
// evaluations of others are excluded (evaluator_id<>?), and drafts are ignored.
// NOTE: evaluator_id is used ONLY as a filter — no evaluator column is ever
// selected, returned or rendered. Keep it that way.
const EA_RECEIVED_FROM = "
    FROM evaluation_tracker et
    LEFT JOIN evaluation_periods ep ON ep.id = et.period_id";
const EA_RECEIVED_WHERE = "et.target_user_id=? AND et.evaluator_id<>? AND et.status IN ('submitted','approved')";

// Detail endpoint used by the modal. It returns no evaluator identity at all.
// ?details=<id>            -> live evaluation_tracker row
// ?details=<id>&src=kept   -> identity-free copy kept by System Archive
if (isset($_GET['details'])) {
    header('Content-Type: application/json; charset=utf-8');
    $detailId = (int)$_GET['details'];
    $isKept   = (($_GET['src'] ?? '') === 'kept');

    if ($isKept) {
        $rows = safe_rows($mysqli, "SELECT remarks, submitted_at, period_label, school_year, semester, answers_json
            FROM feedback_received_keep WHERE id=? AND target_user_id=? LIMIT 1", 'ii', [$detailId, $ea_id]);
        $kept = $rows[0] ?? null;
        if (!$kept) {
            http_response_code(404);
            echo json_encode(['error' => 'Evaluation not found.']);
            exit;
        }
        $answers = [];
        foreach ((json_decode((string)$kept['answers_json'], true) ?: []) as $a) {
            $answers[] = [
                'category'      => $a['category'] ?? 'General',
                'question_text' => $a['question_text'] ?? 'Question',
                'answer_score'  => isset($a['score']) ? (float)$a['score'] : null,
            ];
        }
        $remarks     = (string)($kept['remarks'] ?? '');
        $submittedAt = $kept['submitted_at'];
        $periodLabel = (!empty($kept['school_year']) && !empty($kept['semester']))
            ? $kept['school_year'] . ' · ' . $kept['semester']
            : ((string)($kept['period_label'] ?? '') ?: 'Evaluation Period');
    } else {
        $rows = safe_rows($mysqli, "
            SELECT et.id, et.submitted_at, et.remarks,
                   COALESCE(ep.period_label, et.period, 'Evaluation Period') AS period_label
            " . EA_RECEIVED_FROM . "
            WHERE et.id=? AND " . EA_RECEIVED_WHERE . "
            LIMIT 1", 'iii', [$detailId, $ea_id, $ea_id]);
        $tracker = $rows[0] ?? null;
        if (!$tracker) {
            http_response_code(404);
            echo json_encode(['error' => 'Evaluation not found.']);
            exit;
        }
        $answers = safe_rows($mysqli, "
            SELECT qa.answer_score,
                   COALESCE(eq.category, uq.category, 'General') AS category,
                   COALESCE(eq.question_text, uq.question_text,
                            CONCAT('Question #', COALESCE(qa.question_id, qa.user_question_id, qa.id))) AS question_text
            FROM questionnaire_answers qa
            LEFT JOIN evaluation_questions eq ON qa.question_source='evaluation' AND eq.id=qa.question_id
            LEFT JOIN user_questions uq ON qa.question_source='user' AND uq.id=COALESCE(qa.user_question_id, qa.question_id)
            WHERE qa.tracker_id=?
            ORDER BY category, qa.id", 'i', [$detailId]);
        $remarks     = (string)($tracker['remarks'] ?? '');
        $submittedAt = $tracker['submitted_at'];
        $periodLabel = $tracker['period_label'];
    }

    $categories = [];
    $sumAll = 0.0; $cntAll = 0;
    foreach ($answers as $answer) {
        $cat = (string)($answer['category'] ?? 'General');
        if (!isset($categories[$cat])) { $categories[$cat] = ['sum' => 0.0, 'count' => 0]; }
        if ($answer['answer_score'] !== null) {
            $categories[$cat]['sum'] += (float)$answer['answer_score'];
            $categories[$cat]['count']++;
            $sumAll += (float)$answer['answer_score'];
            $cntAll++;
        }
    }
    $categoryRows = [];
    foreach ($categories as $cat => $stats) {
        $categoryRows[] = [
            'category' => $cat,
            'score'    => $stats['count'] ? round($stats['sum'] / $stats['count'], 2) : null,
        ];
    }

    echo json_encode([
        'evaluator'     => 'Anonymous Evaluator',
        'period'        => $periodLabel,
        'submitted'     => $submittedAt ? date('F j, Y g:i A', strtotime($submittedAt)) : '—',
        'overall_score' => $cntAll ? round($sumAll / $cntAll, 2) : null,
        'categories'    => $categoryRows,
        'answers'       => array_map(static function ($row) {
            return [
                'category' => $row['category'] ?? 'General',
                'question' => $row['question_text'] ?? 'Question',
                'score'    => $row['answer_score'] !== null ? (float)$row['answer_score'] : null,
            ];
        }, $answers),
        'remarks'       => $remarks,
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

// ── GLOBAL SYSTEM SETTINGS (current evaluation term) ────────────────
$settings      = get_system_settings($mysqli);
$period_id_int = (int)($settings['period_id'] ?? 0);
$hasPeriod     = $period_id_int > 0;

// ── EVALUATION TERMS (dropdown) ─────────────────────────────────────
// A term is listed when it is the active period, still has live feedback for
// this EA, or has feedback kept by System Archive (feedback_received_keep).
$termIds = [];
if ($hasPeriod) { $termIds[$period_id_int] = true; }

foreach (safe_rows($mysqli, "SELECT DISTINCT et.period_id AS pid " . EA_RECEIVED_FROM . " WHERE " . EA_RECEIVED_WHERE,
        'ii', [$ea_id, $ea_id]) as $r) {
    if ((int)$r['pid'] > 0) $termIds[(int)$r['pid']] = true;
}
$keptTerms = [];
foreach (safe_rows($mysqli, "SELECT period_id AS pid, MAX(school_year) AS school_year, MAX(semester) AS semester, MAX(period_label) AS period_label
        FROM feedback_received_keep WHERE target_user_id=? GROUP BY period_id", 'i', [$ea_id]) as $r) {
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
        $t['current']  = ($hasPeriod && $pid === $period_id_int);
        $t['archived'] = isset($keptTerms[$pid]);
        $t['label']    = ($t['school_year'] !== '' && $t['period_label'] !== '')
            ? $t['school_year'] . ' — ' . $t['period_label']
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

$requestedPeriod    = $_GET['period'] ?? 'all';
$allPeriodsSelected = !is_string($requestedPeriod) || strtolower(trim($requestedPeriod)) === 'all' || $requestedPeriod === '';
$selPeriod          = $allPeriodsSelected ? 0 : (int)$requestedPeriod;
if (!$allPeriodsSelected && !isset($terms[$selPeriod])) { $allPeriodsSelected = true; $selPeriod = 0; }

// ── RESPONSES (live + kept), identity-free ──────────────────────────
// Selects ONLY tracker id, comment, score totals, period labels and submission
// time. No evaluator_id / name / photo / department column — do not add one.
$liveSql = "
    SELECT et.id, et.remarks AS comment, et.submitted_at,
           COALESCE(ep.period_label, et.period) AS period_label, ep.semester, ep.school_year,
           (SELECT SUM(qa2.answer_score)   FROM questionnaire_answers qa2 WHERE qa2.tracker_id = et.id) AS score_sum,
           (SELECT COUNT(qa2.answer_score) FROM questionnaire_answers qa2 WHERE qa2.tracker_id = et.id) AS score_count
    " . EA_RECEIVED_FROM . "
    WHERE " . EA_RECEIVED_WHERE . ($allPeriodsSelected ? '' : ' AND et.period_id=?');
$liveRows = $allPeriodsSelected
    ? safe_rows($mysqli, $liveSql, 'ii',  [$ea_id, $ea_id])
    : safe_rows($mysqli, $liveSql, 'iii', [$ea_id, $ea_id, $selPeriod]);

$keptSql = "SELECT id, remarks, submitted_at, school_year, semester, period_label, score_sum, score_count
    FROM feedback_received_keep WHERE target_user_id=?" . ($allPeriodsSelected ? '' : ' AND period_id=?');
$keptRows = $allPeriodsSelected
    ? safe_rows($mysqli, $keptSql, 'i',  [$ea_id])
    : safe_rows($mysqli, $keptSql, 'ii', [$ea_id, $selPeriod]);

$items = [];
foreach ($liveRows as $r) {
    $items[] = ['source' => 'live', 'key' => (int)$r['id'], 'comment' => $r['comment'] ?: '', 'submitted_at' => $r['submitted_at'],
        'score_sum' => (float)($r['score_sum'] ?? 0), 'score_count' => (int)($r['score_count'] ?? 0),
        'school_year' => $r['school_year'] ?? '', 'semester' => $r['semester'] ?? '', 'period_label' => $r['period_label'] ?? ''];
}
foreach ($keptRows as $r) {
    $items[] = ['source' => 'kept', 'key' => (int)$r['id'], 'comment' => $r['remarks'] ?: '', 'submitted_at' => $r['submitted_at'],
        'score_sum' => (float)$r['score_sum'], 'score_count' => (int)$r['score_count'],
        'school_year' => $r['school_year'] ?? '', 'semester' => $r['semester'] ?? '', 'period_label' => $r['period_label'] ?? ''];
}

// Submission order only — never evaluator identity.
usort($items, function ($a, $b) {
    $ta = $a['submitted_at'] ? strtotime($a['submitted_at']) : 0;
    $tb = $b['submitted_at'] ? strtotime($b['submitted_at']) : 0;
    return $ta <=> $tb ?: ($a['key'] <=> $b['key']);
});

$history = []; $totalSum = 0.0; $totalCount = 0; $n = 1;
foreach ($items as $it) {
    $totalSum   += $it['score_sum'];
    $totalCount += $it['score_count'];
    $history[] = [
        'id'                 => $it['key'],
        'source'             => $it['source'],
        'label'              => 'Evaluation #' . $n,
        'score'              => $it['score_count'] > 0 ? round($it['score_sum'] / $it['score_count'], 2) : null,
        'comment'            => $it['comment'],
        'submitted_at_label' => $it['submitted_at'] ? date('M j, Y g:i A', strtotime($it['submitted_at'])) : 'Unknown date',
        'period_label'       => (!empty($it['school_year']) && !empty($it['semester'])) ? ($it['school_year'] . ' · ' . $it['semester']) : ($it['period_label'] ?: $it['semester']),
    ];
    $n++;
}
$responseCount = count($history);
$overallAvg    = $totalCount > 0 ? round($totalSum / $totalCount, 2) : null;
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Evaluation Received — PBI</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<style>
:root{
    --page:#F8FAFC;--card:#FFFFFF;--border:#D7E3EF;--border-soft:#E8EEF5;
    --text:#0B1F3A;--muted:#647B96;--accent:#D99121;--accent-bg:#FFF7E9;--accent-border:#F0D39D;
    --blue:#2563EB;--shadow:0 7px 22px rgba(30,82,144,.08);--radius:14px;
}
*{box-sizing:border-box}
html,body{margin:0;background:var(--page);color:var(--text);font-family:Inter,Segoe UI,Arial,sans-serif}
body{min-height:100vh}
.wrap{max-width:1450px;margin:0 auto;padding:34px 38px 46px}
.page-head{display:flex;align-items:flex-end;justify-content:space-between;gap:18px;margin-bottom:18px;flex-wrap:wrap}
.page-head h1{font-size:27px;line-height:1.15;margin:0;font-weight:800;letter-spacing:-.02em}
.page-head p{margin:7px 0 0;font-size:12.5px;color:var(--muted)}
.stats{display:flex;gap:10px;flex-wrap:wrap}
.stat{min-width:150px;background:var(--card);border:1px solid var(--border);border-radius:12px;padding:12px 15px;box-shadow:0 3px 12px rgba(30,82,144,.05)}
.stat-label{font-size:10px;text-transform:uppercase;letter-spacing:.75px;font-weight:800;color:#7389A1}
.stat-value{margin-top:4px;font-size:18px;font-weight:800;color:var(--text)}
.banner{background:var(--accent-bg);border:1px solid var(--accent-border);color:var(--accent);padding:14px 18px;border-radius:12px;font-size:13.5px;font-weight:800;margin:0 0 17px}
.banner i{margin-right:8px}
.results{display:flex;flex-direction:column;gap:12px}
.result-card{background:var(--card);border:1px solid var(--border);border-radius:15px;min-height:108px;padding:16px 21px;display:flex;align-items:center;justify-content:space-between;gap:22px;box-shadow:var(--shadow)}
.result-main{min-width:0}
.anonymous{font-size:15px;font-weight:800;color:#101E35}
.meta{margin-top:6px;color:#58718C;font-size:12px;display:flex;align-items:center;gap:7px;flex-wrap:wrap}
.dot{color:#A7B7C7}
.right{display:flex;align-items:center;gap:12px;flex-shrink:0}
.score-pill{min-width:88px;text-align:center;border:1px solid var(--accent-border);background:#FFF9EF;color:var(--accent);border-radius:21px;padding:9px 13px;font-size:13px;font-weight:800}
.view-btn{border:1px solid var(--accent-border);background:#FFFCF5;color:var(--accent);border-radius:20px;padding:8px 16px;font-size:12px;font-weight:800;cursor:pointer}
.view-btn:hover{background:#FFF6E6}
.empty{background:var(--card);border:1px dashed #C7D5E4;border-radius:15px;padding:62px 24px;text-align:center;color:var(--muted)}
.empty i{font-size:31px;opacity:.4;margin-bottom:12px}
.empty strong{display:block;color:var(--text);font-size:15px;margin-bottom:5px}
.modal-backdrop{position:fixed;inset:0;background:rgba(15,23,42,.55);display:none;align-items:center;justify-content:center;padding:24px;z-index:1000}
.modal-backdrop.open{display:flex}
.modal{width:min(840px,100%);max-height:min(88vh,900px);overflow:auto;background:#fff;border-radius:18px;box-shadow:0 24px 70px rgba(15,23,42,.35)}
.modal-head{padding:22px 26px 18px;border-bottom:1px solid var(--border-soft);display:flex;align-items:center;justify-content:space-between;gap:16px}
.modal-title{font-size:24px;font-weight:800;letter-spacing:-.02em}
.close{width:34px;height:34px;border-radius:50%;border:1px solid var(--border);background:#fff;color:#60758C;cursor:pointer;display:flex;align-items:center;justify-content:center}
.detail-grid{display:grid;grid-template-columns:1fr 1fr;gap:14px;padding:24px 26px 4px}
.detail-box{border:1px solid var(--border);border-radius:12px;padding:13px 16px;background:#fff;box-shadow:0 4px 12px rgba(30,82,144,.05)}
.detail-label{font-size:10px;text-transform:uppercase;letter-spacing:.65px;color:#70849C;margin-bottom:5px}
.detail-value{font-size:14px;font-weight:800;color:var(--text)}
.detail-value.score{color:var(--accent)}
.section{padding:18px 26px 0}
.section-title{font-size:15px;font-weight:800;margin:0 0 12px}
.category-row{display:grid;grid-template-columns:170px 1fr 50px;align-items:center;gap:14px;margin:9px 0}
.category-name{font-size:12.5px;font-weight:700;color:#344B63}
.bar{height:9px;background:#E8EEF5;border-radius:999px;overflow:hidden}.bar > span{display:block;height:100%;border-radius:999px;background:linear-gradient(90deg,#C17D19,#F3BA54)}
.category-score{font-size:12px;font-weight:800;color:var(--accent);text-align:right}
.answers{display:flex;flex-direction:column;gap:10px}
.answer{border:1px solid var(--border);border-radius:12px;padding:14px 16px;background:#fff}
.q-label{font-size:10px;text-transform:uppercase;letter-spacing:.65px;color:#70849C;margin-bottom:6px}
.q-text{font-size:13.5px;font-weight:700;line-height:1.35;color:#162A43}
.q-score{margin-top:9px;font-size:12px;font-weight:800;color:#526A82}
.comments{margin:18px 26px 26px;border:1px solid var(--border);border-radius:12px;padding:15px 16px;background:#fff}
.comments .label{font-size:15px;font-weight:800;margin-bottom:10px}
.comments .text{font-size:13px;color:#354C63;line-height:1.55;white-space:pre-wrap}
.loading{text-align:center;padding:50px 20px;color:var(--muted)}
@media(max-width:760px){
  .wrap{padding:22px 16px 32px}.page-head{align-items:flex-start}.stats{width:100%}.stat{flex:1;min-width:130px}
  .result-card{align-items:flex-start;flex-direction:column}.right{width:100%;justify-content:space-between}
  .detail-grid{grid-template-columns:1fr}.category-row{grid-template-columns:1fr 1fr 48px;gap:10px}.category-name{grid-column:1 / -1}
}

/* ── Dean-style layout ── */
.term-picker{display:flex;flex-direction:column;gap:5px;min-width:260px}
.term-picker label{font-size:11px;font-weight:700;letter-spacing:.06em;text-transform:uppercase;color:var(--muted);display:flex;align-items:center;gap:6px}
.term-picker select{appearance:none;-webkit-appearance:none;width:100%;padding:10px 38px 10px 14px;border-radius:10px;border:1px solid var(--border);background:var(--card) url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='8' viewBox='0 0 12 8'%3E%3Cpath d='M1 1l5 5 5-5' fill='none' stroke='%2367819E' stroke-width='2' stroke-linecap='round'/%3E%3C/svg%3E") no-repeat right 14px center;color:var(--text);font:600 13px Inter,Segoe UI,Arial,sans-serif;cursor:pointer}
.term-picker select:hover{border-color:var(--blue)}
.term-picker select:focus{outline:3px solid rgba(37,99,235,.22);outline-offset:1px;border-color:var(--blue)}
.confidentiality-note{display:flex;align-items:flex-start;gap:14px;padding:16px 20px;background:rgba(16,185,129,.09);border:1px solid rgba(16,185,129,.32);border-radius:12px;margin-bottom:22px}
.confidentiality-note i{color:#10B981;font-size:18px;margin-top:2px}
.confidentiality-note p{margin:0;font-size:12.5px;color:var(--text);line-height:1.6}
.card-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:16px;margin-bottom:22px}
.stat-card{background:var(--card);border:1px solid var(--border);border-radius:14px;padding:22px 24px;box-shadow:var(--shadow)}
.stat-card i{color:var(--blue);font-size:22px;margin-bottom:12px;display:block}
.stat-card .num{font-size:30px;font-weight:700;color:var(--text)}
.stat-card .label{font-size:12.5px;color:var(--muted);margin-top:4px}
.panel{background:var(--card);border:1px solid var(--border);border-radius:14px;padding:24px;box-shadow:var(--shadow);margin-bottom:22px}
.panel h2{font-size:18px;font-weight:700;margin:0 0 16px;display:flex;align-items:center;gap:9px;color:var(--text)}
.panel h2 i{color:var(--blue);font-size:16px}
.response-card{background:var(--card);border:1px solid var(--border);border-radius:14px;padding:16px 20px;margin-bottom:12px}
.response-card:last-child{margin-bottom:0}
.response-head{display:flex;justify-content:space-between;align-items:center;margin-bottom:8px;gap:12px}
.response-label{font-size:12px;font-weight:700;color:var(--muted);text-transform:uppercase;letter-spacing:.6px;display:flex;align-items:center;gap:7px}
.response-score{font-size:13px;font-weight:700;color:#0F8F66;background:rgba(16,185,129,.13);padding:3px 12px;border-radius:12px}
.response-comment{margin:0;font-size:13.5px;color:var(--text);line-height:1.6;font-style:italic;white-space:pre-wrap}
.empty-note{margin:0;color:var(--muted);font-size:13px;font-style:italic}
.view-evals-btn{width:100%;display:flex;align-items:center;gap:10px;background:var(--accent-bg);border:1px solid var(--accent-border);color:var(--accent);padding:12px 14px;border-radius:10px;font:700 13px Inter,Segoe UI,Arial,sans-serif;cursor:pointer}
.view-evals-btn .caret{margin-left:auto;transition:transform .2s}
.view-evals-btn.open .caret{transform:rotate(180deg)}
.result-card{cursor:pointer}
.result-card:hover{border-color:var(--accent-border)}

/* ── DARK THEME ──
   This page had no dark support at all (unlike admin_analytics.php,
   which already has this). Re-declares the page's own local tokens
   under html[data-theme="dark"] plus the handful of colors that were
   hardcoded instead of using a variable. */
html[data-theme="dark"]{
    --page:#0A192F;--card:#172A45;--border:#24395A;--border-soft:#1E314F;
    --text:#E7ECF3;--muted:#93A5BE;--accent-bg:#2A2013;--accent-border:#4A3A1E;
    color-scheme:dark;
}
html[data-theme="dark"] .stat-value,html[data-theme="dark"] .detail-value{color:var(--text) !important;}
html[data-theme="dark"] .anonymous{color:#F2F5F9 !important;}
html[data-theme="dark"] .meta,html[data-theme="dark"] .q-score{color:var(--muted) !important;}
html[data-theme="dark"] .dot{color:#54688A !important;}
html[data-theme="dark"] .score-pill,html[data-theme="dark"] .view-btn{background:var(--accent-bg) !important;}
html[data-theme="dark"] .view-btn:hover{background:#332612 !important;}
html[data-theme="dark"] .empty{border-color:#2C4269 !important;}
html[data-theme="dark"] .term-picker select{background-color:var(--card) !important;color:var(--text) !important;}
html[data-theme="dark"] .response-score{color:#6EE7B7;}
html[data-theme="dark"] .stat-card .num,html[data-theme="dark"] .panel h2,html[data-theme="dark"] .response-comment{color:var(--text) !important;}
html[data-theme="dark"] .modal,html[data-theme="dark"] .detail-box,html[data-theme="dark"] .answer,
html[data-theme="dark"] .comments,html[data-theme="dark"] .close{
  background:var(--card) !important;
}
html[data-theme="dark"] .close{color:var(--muted) !important;}
html[data-theme="dark"] .detail-label,html[data-theme="dark"] .q-label{color:var(--muted) !important;}
html[data-theme="dark"] .category-name{color:#B9C6DA !important;}
html[data-theme="dark"] .bar{background:#233A5B !important;}
html[data-theme="dark"] .q-text{color:#EAEFF5 !important;}
html[data-theme="dark"] .comments .text{color:#C7D2E3 !important;}
</style>
<link rel="stylesheet" href="admin_appearance.css">
<script src="admin_appearance.js"></script>
<script>
/* This report is often rendered inside the dark admin shell as an iframe.
   Keep this document synchronized with the parent theme before and after
   first paint, including theme changes made while this iframe remains open. */
(function () {
  function getParentTheme() {
    try {
      if (window.parent && window.parent !== window) {
        var t = window.parent.document.documentElement.getAttribute('data-theme');
        if (t === 'dark' || t === 'light') return t;
      }
    } catch (e) {}
    var saved = null;
    try { saved = localStorage.getItem('pbiTheme'); } catch (e) {}
    return saved === 'dark' ? 'dark' : 'light';
  }

  function syncTheme() {
    var theme = getParentTheme();
    document.documentElement.setAttribute('data-theme', theme);
    document.documentElement.style.colorScheme = theme;
  }

  syncTheme();

  try {
    var parentRoot = window.parent && window.parent !== window
      ? window.parent.document.documentElement
      : null;
    if (parentRoot && window.MutationObserver) {
      new MutationObserver(syncTheme).observe(parentRoot, {
        attributes: true,
        attributeFilter: ['data-theme']
      });
    }
  } catch (e) {}

  window.addEventListener('storage', function (e) {
    if (e.key === 'pbiTheme') syncTheme();
  });
})();
</script>
</head>
<body>
<div class="wrap">
    <div class="page-head">
        <div>
            <h1>Evaluation Received</h1>
        </div>
        <?php if ($terms): ?>
        <form method="get" class="term-picker">
            <label for="termSelect"><i class="fa-solid fa-calendar-days"></i> Evaluation Term</label>
            <select id="termSelect" name="period" onchange="this.form.submit()">
                <option value="all"<?= $allPeriodsSelected ? ' selected' : '' ?>>All Evaluation Terms — Past &amp; Current</option>
                <?php foreach ($terms as $t): ?>
                <option value="<?= (int)$t['id'] ?>"<?= !$allPeriodsSelected && (int)$t['id'] === $selPeriod ? ' selected' : '' ?>><?= $escape($t['label']) ?><?= $t['current'] ? ' (Current)' : ($t['archived'] ? ' (Archived)' : '') ?></option>
                <?php endforeach; ?>
            </select>
        </form>
        <?php endif; ?>
    </div>


    <div class="card-grid">
        <div class="stat-card"><i class="fa-solid fa-star"></i><div class="num"><?= $overallAvg !== null ? $escape(rtrim(rtrim(number_format($overallAvg, 2), '0'), '.')) : '—' ?></div><div class="label">Overall Average</div></div>
        <div class="stat-card"><i class="fa-solid fa-comments"></i><div class="num"><?= $responseCount ?></div><div class="label">Responses Received</div></div>
    </div>

    <div class="panel">
        <h2><i class="fa-solid fa-comment-dots"></i> Evaluation History — Anonymous Responses</h2>
        <?php if (!$history): ?>
            <p class="empty-note"><?= $allPeriodsSelected ? 'No responses were submitted across any evaluation term.' : 'No responses were submitted for this term.' ?></p>
        <?php else: ?>
            <?php foreach ($history as $h): ?>
            <div class="response-card">
                <div class="response-head">
                    <span class="response-label"><i class="fa-solid fa-user-secret"></i> Anonymous — <?= $escape($h['label']) ?></span>
                    <?php if ($h['score'] !== null): ?><span class="response-score"><?= $escape(rtrim(rtrim(number_format($h['score'], 2), '0'), '.')) ?></span><?php endif; ?>
                </div>
                <?php if ($h['comment'] !== ''): ?>
                    <p class="response-comment">&ldquo;<?= $escape($h['comment']) ?>&rdquo;</p>
                <?php else: ?>
                    <p class="empty-note">No written comment.</p>
                <?php endif; ?>
            </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <div class="panel">
        <h2><i class="fa-solid fa-clock-rotate-left"></i> Evaluations Received</h2>
        <button type="button" class="view-evals-btn" id="viewEvalsBtn" onclick="toggleEvals()">
            <i class="fa-solid fa-eye"></i> View Evaluations Received
            <i class="fa-solid fa-chevron-down caret"></i>
        </button>
        <div id="evalsList" style="display:none;margin-top:14px">
            <?php if (!$history): ?>
                <p class="empty-note">No evaluations have been received yet.</p>
            <?php else: ?>
                <div class="results">
                <?php foreach ($history as $h): ?>
                    <div class="result-card" onclick="openDetails(<?= (int)$h['id'] ?>,'<?= $h['source'] === 'kept' ? 'kept' : 'live' ?>')">
                        <div class="result-main">
                            <div class="anonymous"><i class="fa-solid fa-eye-slash" style="color:var(--muted);margin-right:6px"></i>Anonymous Evaluator</div>
                            <div class="meta">
                                <span><?= $escape($h['submitted_at_label']) ?></span>
                                <?php if (!empty($h['period_label'])): ?><span class="dot">·</span><span><?= $escape($h['period_label']) ?></span><?php endif; ?>
                            </div>
                        </div>
                        <div class="right">
                            <div class="score-pill"><?= $h['score'] !== null ? $escape(number_format($h['score'], 2)) . ' / 5' : '—' ?></div>
                            <button type="button" class="view-btn" onclick="event.stopPropagation();openDetails(<?= (int)$h['id'] ?>,'<?= $h['source'] === 'kept' ? 'kept' : 'live' ?>')">View Details</button>
                        </div>
                    </div>
                <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<div class="modal-backdrop" id="detailBackdrop" onclick="backdropClose(event)">
    <div class="modal" role="dialog" aria-modal="true" aria-labelledby="detailTitle">
        <div class="modal-head">
            <div class="modal-title" id="detailTitle">Evaluation Details</div>
            <button type="button" class="close" onclick="closeDetails()" aria-label="Close"><i class="fa-solid fa-xmark"></i></button>
        </div>
        <div id="detailContent" class="loading">Loading evaluation details…</div>
    </div>
</div>

<script>
const backdrop = document.getElementById('detailBackdrop');
const detailContent = document.getElementById('detailContent');

function esc(value){
    return String(value ?? '').replace(/[&<>'"]/g, ch => ({'&':'&amp;','<':'&lt;','>':'&gt;',"'":'&#39;','"':'&quot;'}[ch]));
}
function scoreWidth(score){
    if(score === null || score === undefined || Number.isNaN(Number(score))) return 0;
    return Math.max(0, Math.min(100, (Number(score) / 5) * 100));
}
function scoreText(score){
    return score === null || score === undefined ? '—' : `${Number(score).toFixed(2)}`;
}
function toggleEvals(){
    const l=document.getElementById('evalsList'),b=document.getElementById('viewEvalsBtn');
    const open=l.style.display!=='none';
    l.style.display=open?'none':'block';
    b.classList.toggle('open',!open);
}
function openDetails(id,src){
    backdrop.classList.add('open');
    document.body.style.overflow='hidden';
    detailContent.className='loading';
    detailContent.textContent='Loading evaluation details…';

    fetch(`ea_results.php?details=${encodeURIComponent(id)}${src==='kept'?'&src=kept':''}`, {credentials:'same-origin'})
        .then(r => r.ok ? r.json() : Promise.reject(new Error('Unable to load this evaluation.')))
        .then(data => {
            if(data.error) throw new Error(data.error);
            const overall = data.overall_score !== null ? `${Number(data.overall_score).toFixed(2)} / 5` : '—';
            const categories = (data.categories || []).map(row => `
                <div class="category-row">
                    <div class="category-name">${esc(row.category)}</div>
                    <div class="bar"><span style="width:${scoreWidth(row.score)}%"></span></div>
                    <div class="category-score">${row.score !== null ? Number(row.score).toFixed(2) : '—'}</div>
                </div>`).join('');
            const answers = (data.answers || []).map((row, index) => `
                <div class="answer">
                    <div class="q-label">Question ${index + 1}</div>
                    <div class="q-text">${esc(row.question)}</div>
                    <div class="q-score">Score: ${row.score !== null ? Number(row.score).toFixed(0) : '—'} / 5</div>
                </div>`).join('');

            detailContent.className='';
            detailContent.innerHTML = `
                <div class="detail-grid">
                    <div class="detail-box"><div class="detail-label">Evaluator</div><div class="detail-value">Anonymous Evaluator</div></div>
                    <div class="detail-box"><div class="detail-label">Period</div><div class="detail-value">${esc(data.period)}</div></div>
                    <div class="detail-box"><div class="detail-label">Submitted</div><div class="detail-value">${esc(data.submitted)}</div></div>
                    <div class="detail-box"><div class="detail-label">Overall Score</div><div class="detail-value score">${overall}</div></div>
                </div>
                <div class="section">
                    <div class="section-title">Performance by Category</div>
                    ${categories || '<div style="font-size:13px;color:#647B96">No category data available.</div>'}
                </div>
                <div class="section">
                    <div class="section-title">Question-by-Question Results</div>
                    <div class="answers">${answers || '<div style="font-size:13px;color:#647B96">No question responses available.</div>'}</div>
                </div>
                <div class="comments">
                    <div class="label">Comments / Feedback</div>
                    <div class="text">${esc(data.remarks || '“N/A”')}</div>
                </div>`;
        })
        .catch(err => {
            detailContent.className='loading';
            detailContent.innerHTML = `<div style="color:#B42318">${esc(err.message || 'Unable to load this evaluation.')}</div>`;
        });
}
function closeDetails(){
    backdrop.classList.remove('open');
    document.body.style.overflow='';
}
function backdropClose(event){
    if(event.target === backdrop) closeDetails();
}
document.addEventListener('keydown', e => { if(e.key === 'Escape') closeDetails(); });
</script>
</body>
</html>
<?php $mysqli->close(); ?>