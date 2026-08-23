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
// The Dean's OWN evaluation results — feedback the Dean received from
// Teachers (or whichever evaluator groups the evaluation policy defines
// as evaluating the Dean). This is a read-only "how am I doing" view,
// not an evaluation tool. Staff do not evaluate the Dean, so there is
// no Staff-results section here (per spec §6).
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
// eval_type='student', evaluation_context='school_head' — same tracker row
// shape as Student→Teacher, just a different evaluation_context and a
// target_user_id that points at the Dean's own account. The question set is
// per-person (Principal/Dean are "per_user_targets" in questionnaire.php), so
// answers join through user_questions via questionnaire_answers.user_question_id,
// with question_source='user' — the same source admin_analytics.php's sheet
// view already reads for Staff/Principal/Dean questions.
const SCHOOL_HEAD_EVAL_TYPE = 'student';
const SCHOOL_HEAD_CONTEXT   = 'school_head';

$overallAvg = null;
$responseCount = 0;
$categoryBreakdown = [];
$trend = [];
$history = [];

if ($hasPeriod) {
    // ── OVERALL AVERAGE (this period) ───────────────────────────────
    $overallAvgRaw = safe_scalar($mysqli, "
        SELECT AVG(qa.answer_score) v
        FROM evaluation_tracker et
        INNER JOIN questionnaire_answers qa ON qa.tracker_id = et.id
        WHERE et.eval_type=? AND et.evaluation_context=? AND et.status IN ('submitted','approved')
          AND et.target_user_id=? AND et.period_id=?
    ", "ssii", [SCHOOL_HEAD_EVAL_TYPE, SCHOOL_HEAD_CONTEXT, $deanId, $period_id_int]);
    $overallAvg = $overallAvgRaw !== null ? round((float)$overallAvgRaw, 2) : null;

    $responseCount = (int)(safe_scalar($mysqli, "
        SELECT COUNT(DISTINCT et.id) c
        FROM evaluation_tracker et
        WHERE et.eval_type=? AND et.evaluation_context=? AND et.status IN ('submitted','approved')
          AND et.target_user_id=? AND et.period_id=?
    ", "ssii", [SCHOOL_HEAD_EVAL_TYPE, SCHOOL_HEAD_CONTEXT, $deanId, $period_id_int]) ?? 0);

    // ── CATEGORY BREAKDOWN ───────────────────────────────────────────
    $categoryBreakdown = safe_rows($mysqli, "
        SELECT uq.category, AVG(qa.answer_score) avg_score, COUNT(*) n
        FROM questionnaire_answers qa
        INNER JOIN user_questions uq ON uq.id = qa.user_question_id
        INNER JOIN evaluation_tracker et ON et.id = qa.tracker_id
        WHERE et.eval_type=? AND et.evaluation_context=? AND et.status IN ('submitted','approved')
          AND et.target_user_id=? AND et.period_id=? AND qa.question_source='user'
        GROUP BY uq.category
        ORDER BY uq.category
    ", "ssii", [SCHOOL_HEAD_EVAL_TYPE, SCHOOL_HEAD_CONTEXT, $deanId, $period_id_int]);
    foreach ($categoryBreakdown as &$c) { $c['avg_score'] = round((float)$c['avg_score'], 2); }
    unset($c);

    // ── TREND (average rating per period, most recent periods) ──────
    // Scoped to this dean across all periods, not just the active one.
    // evaluation_periods' real columns are period_label/school_year/semester
    // (confirmed via shared/system_settings_service.php) — not
    // academic_term/academic_year, which don't exist on that table.
    $trend = safe_rows($mysqli, "
        SELECT ep.period_label, AVG(qa.answer_score) avg_score
        FROM evaluation_tracker et
        INNER JOIN evaluation_periods ep ON ep.id = et.period_id
        INNER JOIN questionnaire_answers qa ON qa.tracker_id = et.id
        WHERE et.eval_type=? AND et.evaluation_context=? AND et.status IN ('submitted','approved')
          AND et.target_user_id=?
        GROUP BY et.period_id, ep.period_label
        ORDER BY ep.id ASC
        LIMIT 12
    ", "ssi", [SCHOOL_HEAD_EVAL_TYPE, SCHOOL_HEAD_CONTEXT, $deanId]);
    foreach ($trend as &$t) { $t['avg_score'] = round((float)$t['avg_score'], 2); }
    unset($t);

    // ── ANONYMOUS RESPONSE HISTORY ────────────────────────────────
    // Deliberately selects ONLY tracker id (for per-tracker avg + ordering),
    // comment, and submission order. No evaluator_id, name, or any
    // evaluator-identifying column is selected — do not add one.
    $rawHistory = safe_rows($mysqli, "
        SELECT et.id, et.remarks AS comment, et.submitted_at, ep.period_label, ep.semester, ep.school_year,
               (SELECT AVG(qa2.answer_score) FROM questionnaire_answers qa2 WHERE qa2.tracker_id = et.id) AS score
        FROM evaluation_tracker et
        LEFT JOIN evaluation_periods ep ON ep.id = et.period_id
        WHERE et.eval_type=? AND et.evaluation_context=? AND et.status IN ('submitted','approved')
          AND et.target_user_id=? AND et.period_id=?
        ORDER BY et.submitted_at ASC
    ", "ssii", [SCHOOL_HEAD_EVAL_TYPE, SCHOOL_HEAD_CONTEXT, $deanId, $period_id_int]);

    $n = 1;
    foreach ($rawHistory as $r) {
        $history[] = [
            '_tracker_id'      => (int)$r['id'],
            'label'            => 'Evaluation #' . $n,
            'score'            => $r['score'] !== null ? round((float)$r['score'], 2) : null,
            'comment'          => $r['comment'] ?: '',
            'submitted_at_label' => $r['submitted_at'] ? date('M d, Y g:i A', strtotime($r['submitted_at'])) : 'Unknown date',
            'period_label'     => (!empty($r['school_year']) && !empty($r['semester'])) ? ($r['school_year'].' · '.$r['semester']) : ($r['period_label'] ?? $r['semester'] ?? ''),
        ];
        $n++;
    }
}

$mysqli->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"/>
<meta name="viewport" content="width=device-width, initial-scale=1.0"/>
<title>PBI — View Results</title>
<link href="https://fonts.googleapis.com/css2?family=Rajdhani:wght@600;700&family=DM+Sans:wght@400;500;600&display=swap" rel="stylesheet"/>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css"/>
<style>
:root{--dark:#0A192F;--mid:#172A45;--inner:#0F1F3D;--violet:#7C5FD9;--violet-h:#9C85F0;--violet-dark:#5F45B8;--light:#E0E6F0;--muted:#A0B3C6;--radius:10px;--shadow:0 8px 32px rgba(0,0,0,0.45);--danger:#f05454;--good:#10B981;}
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0;}
body{min-height:100vh;background:linear-gradient(rgba(5,18,36,.72),rgba(5,18,36,.82)),url('../background.png') center center / cover no-repeat fixed;background-color:var(--dark);font-family:'DM Sans',sans-serif;color:var(--light);display:flex;}

.sidebar{width:250px;flex-shrink:0;background:rgba(23,42,69,.9);border-right:1px solid rgba(255,255,255,.08);min-height:100vh;padding:28px 20px;display:flex;flex-direction:column;}
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
</head>
<body>

<?php
$active = 'results';
$sidebarScope = HIGHER_ED_LABEL . ' Division';
include __DIR__ . '/includes/dean_sidebar.php';
?>

<main class="main">
    <div class="page-header">
        <div class="page-title">View Results</div>
        <div class="page-sub">Your evaluation results, as submitted by Teachers this period.</div>
    </div>

    <div class="confidentiality-note">
        <i class="fa-solid fa-user-shield"></i>
        <p>
            Evaluator identities are never shown here. Responses below are numbered in submission
            order only — names, IDs, photos, departments, and timestamps that could identify who
            submitted a response are intentionally excluded.
        </p>
    </div>

    <?php if (!$hasPeriod): ?>
        <p class="empty-note">No active evaluation period right now.</p>
    <?php else: ?>

    <!-- OVERVIEW -->
    <div class="card-grid">
        <div class="stat-card"><i class="fa-solid fa-star"></i><div class="num"><?= $overallAvg !== null ? $overallAvg : '—' ?></div><div class="label">Overall Average</div></div>
        <div class="stat-card"><i class="fa-solid fa-comments"></i><div class="num"><?= $responseCount ?></div><div class="label">Responses Received</div></div>
    </div>

    <!-- CATEGORY BREAKDOWN -->
    <div class="section">
        <h2><i class="fa-solid fa-list-check"></i> Category Breakdown</h2>
        <?php if (empty($categoryBreakdown)): ?>
            <p class="empty-note">No category-level results yet this period.</p>
        <?php else: ?>
            <?php foreach ($categoryBreakdown as $c): ?>
            <div class="cat-row">
                <div class="cat-name"><?= htmlspecialchars($c['category']) ?></div>
                <div class="bar-wrap"><div class="bar-fill" style="width:<?= min(100, ($c['avg_score'] / 5) * 100) ?>%"></div></div>
                <div class="cat-score"><?= htmlspecialchars((string)$c['avg_score']) ?></div>
            </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <!-- EVALUATION TREND -->
    <div class="section">
        <h2><i class="fa-solid fa-chart-line"></i> Evaluation Trend</h2>
        <?php if (empty($trend)): ?>
            <p class="empty-note">Not enough history yet to show a trend.</p>
        <?php else: ?>
            <?php foreach ($trend as $t): ?>
            <div class="trend-row">
                <span><?= htmlspecialchars(trim($t['academic_term'] . ' ' . $t['academic_year'])) ?></span>
                <span class="val"><?= htmlspecialchars((string)$t['avg_score']) ?></span>
            </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <!-- ANONYMOUS RESPONSES -->
    <div class="section">
        <h2><i class="fa-solid fa-comment-dots"></i> Evaluation History — Anonymous Responses</h2>
        <?php if (empty($history)): ?>
            <p class="empty-note">No responses submitted yet this period.</p>
        <?php else: ?>
            <?php foreach ($history as $h): ?>
            <div class="response-card">
                <div class="response-head">
                    <span class="response-label"><i class="fa-solid fa-user-secret"></i> Anonymous — <?= htmlspecialchars($h['label']) ?></span>
                    <?php if ($h['score'] !== null): ?><span class="response-score"><?= htmlspecialchars((string)$h['score']) ?></span><?php endif; ?>
                </div>
                <?php if ($h['comment'] !== ''): ?>
                    <p class="response-comment">&ldquo;<?= htmlspecialchars($h['comment']) ?>&rdquo;</p>
                <?php else: ?>
                    <p class="empty-note">No written comment.</p>
                <?php endif; ?>
            </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <!-- FACULTY-STYLE EVALUATIONS RECEIVED -->
    <div class="section">
        <h2><i class="fa-solid fa-clock-rotate-left"></i> Evaluations Received</h2>
        <button type="button" class="view-evals-btn" id="deanViewEvalsBtn" onclick="toggleDeanEvals()">
            <i class="fa-solid fa-eye"></i> View Evaluations Received
            <i class="fa-solid fa-chevron-down" id="deanEvalsCaret"></i>
        </button>
        <div id="deanEvalsList" style="display:none;margin-top:14px;">
            <?php if (empty($history)): ?>
                <div class="empty-note">No evaluations have been received yet.</div>
            <?php else: ?>
                <?php foreach ($history as $idx => $h): ?>
                    <div class="received-item" onclick="openDeanEvalDetails(<?= (int)$h['_tracker_id'] ?>)">
                        <div>
                            <div class="received-anon"><i class="fa-solid fa-eye-slash"></i> Anonymous Evaluator</div>
                            <div class="received-meta"><?= htmlspecialchars($h['submitted_at_label']) ?><?= !empty($h['period_label']) ? ' · '.htmlspecialchars($h['period_label']) : '' ?></div>
                        </div>
                        <div class="received-right">
                            <span class="received-score"><?= $h['score'] !== null ? htmlspecialchars((string)$h['score']).' / 5' : '—' ?></span>
                            <button type="button" class="details-btn" onclick="event.stopPropagation();openDeanEvalDetails(<?= (int)$h['_tracker_id'] ?>)">View Details <i class="fa-solid fa-chevron-right"></i></button>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
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
function toggleDeanEvals(){const l=document.getElementById('deanEvalsList'),c=document.getElementById('deanEvalsCaret');const open=l.style.display!=='none';l.style.display=open?'none':'block';c.style.transform=open?'':'rotate(180deg)';}
function escDean(v){if(v===null||v===undefined)return '';const d=document.createElement('div');d.textContent=v;return d.innerHTML;}
function starsDean(score){let h='';for(let i=1;i<=5;i++)h+=`<i class="fa-solid fa-star dean-star ${i<=score?'filled':''}"></i>`;return h;}
function openDeanEvalDetails(id){const m=document.getElementById('deanEvalModal'),b=document.getElementById('deanEvalBody');b.innerHTML='<div class="loading-eval"><i class="fa-solid fa-spinner fa-spin"></i> Loading evaluation…</div>';m.classList.add('open');document.body.style.overflow='hidden';fetch('get_my_evaluation_details.php?tracker_id='+encodeURIComponent(id)).then(r=>r.json()).then(d=>{if(!d.ok){b.innerHTML='<div class="loading-eval"><i class="fa-solid fa-triangle-exclamation"></i>'+escDean(d.error||'Unable to load this evaluation.')+'</div>';return;}let h=`<div class="info-grid"><div><div class="info-label">Evaluator</div><div class="info-value"><i class="fa-solid fa-eye-slash"></i> Anonymous Evaluator</div></div><div><div class="info-label">Period</div><div class="info-value">${escDean(d.period_label||'—')}</div></div><div><div class="info-label">Submitted</div><div class="info-value">${escDean(d.submitted_at||'—')}</div></div><div><div class="info-label">Overall Score</div><div class="info-value score-big">${Number(d.overall_score||0).toFixed(2)} / 5</div></div></div>`;if(d.categories?.length){h+='<h3 class="modal-section-title">Performance by Category</h3>';d.categories.forEach(c=>{const pct=Math.round((c.avg/5)*100);h+=`<div class="cat-row-modal"><div class="cat-name-modal">${escDean(c.category)}</div><div class="cat-bar"><div style="width:${pct}%"></div></div><div class="cat-score-modal">${Number(c.avg).toFixed(2)}</div></div>`})}if(d.questions?.length){h+='<h3 class="modal-section-title">Question-by-Question Results</h3>';d.questions.forEach((q,i)=>{h+=`<div class="q-result"><div class="q-no">Question ${i+1}</div><div class="q-text">${escDean(q.question_text)}</div><div class="q-score">${starsDean(q.score)} <span>Score: ${q.score} / 5</span></div></div>`})}h+='<h3 class="modal-section-title">Comments / Feedback</h3>';h+=d.comment?`<div class="comment-modal">“${escDean(d.comment)}”</div>`:'<div class="comment-modal empty">No written feedback was provided.</div>';b.innerHTML=h;}).catch(()=>{b.innerHTML='<div class="loading-eval"><i class="fa-solid fa-triangle-exclamation"></i>Something went wrong loading this evaluation.</div>';});}
function closeDeanEvalDetails(){document.getElementById('deanEvalModal').classList.remove('open');document.body.style.overflow='';}
document.getElementById('deanEvalModal').addEventListener('click',function(e){if(e.target===this)closeDeanEvalDetails();});
</script>
</body>
</html>
</body>
</html>