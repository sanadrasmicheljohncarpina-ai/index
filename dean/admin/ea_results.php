<?php
// admin/ea_results.php
// Anonymous evaluation results received by the Executive Assistant (EA).
// Only authorized EA evaluators (Principal / Dean) are included. Evaluator
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

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

$role = $_SESSION['role'] ?? '';
if (empty($_SESSION['user_id']) || !in_array($role, ['superadmin', 'executive_assistant'], true)) {
    http_response_code(403);
    exit('Unauthorized');
}

$ea_id = (int)$_SESSION['user_id'];
$escape = static fn($v) => htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');

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

// Detail endpoint used by the modal. It returns no evaluator identity at all.
if (isset($_GET['details'])) {
    header('Content-Type: application/json; charset=utf-8');
    $trackerId = (int)$_GET['details'];

    $stmt = $mysqli->prepare("
        SELECT
            et.id,
            et.period_id,
            et.submitted_at,
            et.remarks,
            COALESCE(ep.period_label, et.period, 'Evaluation Period') AS period_label,
            ROUND(AVG(qa.answer_score), 2) AS overall_score
        FROM evaluation_tracker et
        LEFT JOIN evaluation_periods ep ON ep.id = et.period_id
        LEFT JOIN questionnaire_answers qa ON qa.tracker_id = et.id
        INNER JOIN users evaluator
            ON evaluator.id = et.evaluator_id
           AND evaluator.role IN ('principal','dean')
        WHERE et.id=?
          AND et.target_user_id=?
          AND et.evaluator_id<>?
          AND et.eval_type IN ('school_head','upward_to_ea')
          AND et.status='submitted'
        GROUP BY et.id
        LIMIT 1
    ");
    $stmt->bind_param('iii', $trackerId, $ea_id, $ea_id);
    $stmt->execute();
    $tracker = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$tracker) {
        http_response_code(404);
        echo json_encode(['error' => 'Evaluation not found.']);
        exit;
    }

    $answersStmt = $mysqli->prepare("
        SELECT
            qa.id AS answer_id,
            qa.answer_score,
            COALESCE(eq.category, uq.category, 'General') AS category,
            COALESCE(
                eq.question_text,
                uq.question_text,
                CONCAT('Question #', COALESCE(qa.question_id, qa.user_question_id, qa.id))
            ) AS question_text
        FROM questionnaire_answers qa
        LEFT JOIN evaluation_questions eq
            ON qa.question_source='evaluation' AND eq.id=qa.question_id
        LEFT JOIN user_questions uq
            ON qa.question_source='user'
           AND uq.id=COALESCE(qa.user_question_id, qa.question_id)
        WHERE qa.tracker_id=?
        ORDER BY category, qa.id
    ");
    $answersStmt->bind_param('i', $trackerId);
    $answersStmt->execute();
    $answers = $answersStmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $answersStmt->close();

    $categories = [];
    foreach ($answers as $answer) {
        $cat = (string)($answer['category'] ?? 'General');
        if (!isset($categories[$cat])) {
            $categories[$cat] = ['sum' => 0.0, 'count' => 0];
        }
        if ($answer['answer_score'] !== null) {
            $categories[$cat]['sum'] += (float)$answer['answer_score'];
            $categories[$cat]['count']++;
        }
    }

    $categoryRows = [];
    foreach ($categories as $cat => $stats) {
        $categoryRows[] = [
            'category' => $cat,
            'score' => $stats['count'] ? round($stats['sum'] / $stats['count'], 2) : null,
        ];
    }

    echo json_encode([
        'evaluator' => 'Anonymous Evaluator',
        'period' => $tracker['period_label'],
        'submitted' => date('F j, Y g:i A', strtotime($tracker['submitted_at'])),
        'overall_score' => $tracker['overall_score'] !== null ? (float)$tracker['overall_score'] : null,
        'categories' => $categoryRows,
        'answers' => array_map(static function ($row) {
            return [
                'category' => $row['category'] ?? 'General',
                'question' => $row['question_text'] ?? 'Question',
                'score' => $row['answer_score'] !== null ? (float)$row['answer_score'] : null,
            ];
        }, $answers),
        'remarks' => (string)($tracker['remarks'] ?? ''),
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

// Received evaluations are scoped to this EA and to the two roles the current
// evaluation design authorizes to evaluate the EA: Principal and Dean.
$stmt = $mysqli->prepare("
    SELECT
        et.id,
        et.submitted_at,
        COALESCE(ep.period_label, et.period, 'Evaluation Period') AS period_label,
        ROUND(AVG(qa.answer_score), 2) AS overall_score
    FROM evaluation_tracker et
    LEFT JOIN evaluation_periods ep ON ep.id=et.period_id
    LEFT JOIN questionnaire_answers qa ON qa.tracker_id=et.id
    INNER JOIN users evaluator
        ON evaluator.id=et.evaluator_id
       AND evaluator.role IN ('principal','dean')
    WHERE et.target_user_id=?
      AND et.evaluator_id<>?
      AND et.eval_type IN ('school_head','upward_to_ea')
      AND et.status='submitted'
    GROUP BY et.id
    ORDER BY et.submitted_at DESC
");
$stmt->bind_param('ii', $ea_id, $ea_id);
$stmt->execute();
$evaluations = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

$totalReceived = count($evaluations);
$overallValues = array_values(array_filter(array_map(
    static fn($row) => $row['overall_score'] !== null ? (float)$row['overall_score'] : null,
    $evaluations
), static fn($v) => $v !== null));
$overallReceivedScore = $overallValues ? round(array_sum($overallValues) / count($overallValues), 2) : null;

function score_class(?float $score): string {
    if ($score === null) return 'score-neutral';
    if ($score >= 4.5) return 'score-high';
    if ($score >= 3.5) return 'score-good';
    if ($score >= 2.5) return 'score-mid';
    return 'score-low';
}
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>View Results — PBI</title>
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
</style>
</head>
<body>
<div class="wrap">
    <div class="page-head">
        <div>
            <h1>Evaluations Received</h1>
            <p>Anonymous evaluations submitted by authorized evaluators of your EA account.</p>
        </div>
        <div class="stats">
            <div class="stat">
                <div class="stat-label">Total Received</div>
                <div class="stat-value"><?= $totalReceived ?></div>
            </div>
            <div class="stat">
                <div class="stat-label">Average Score</div>
                <div class="stat-value"><?= $overallReceivedScore !== null ? number_format($overallReceivedScore, 2) . ' / 5' : '—' ?></div>
            </div>
        </div>
    </div>

    <div class="banner"><i class="fa-solid fa-eye-slash"></i> View Evaluations Received</div>

    <div class="results">
        <?php if (!$evaluations): ?>
            <div class="empty">
                <i class="fa-regular fa-comment-dots"></i>
                <strong>No evaluations received yet.</strong>
                <span>Authorized evaluators' anonymous submissions will appear here.</span>
            </div>
        <?php else: ?>
            <?php foreach ($evaluations as $row): ?>
                <?php $score = $row['overall_score'] !== null ? (float)$row['overall_score'] : null; ?>
                <div class="result-card">
                    <div class="result-main">
                        <div class="anonymous">Anonymous Evaluator</div>
                        <div class="meta">
                            <span><?= $escape(date('M j, Y g:i A', strtotime($row['submitted_at']))) ?></span>
                            <span class="dot">·</span>
                            <span><?= $escape($row['period_label']) ?></span>
                        </div>
                    </div>
                    <div class="right">
                        <div class="score-pill <?= score_class($score) ?>"><?= $score !== null ? number_format($score, 2) . ' / 5' : '—' ?></div>
                        <button type="button" class="view-btn" onclick="openDetails(<?= (int)$row['id'] ?>)">View Details</button>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
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
function openDetails(id){
    backdrop.classList.add('open');
    document.body.style.overflow='hidden';
    detailContent.className='loading';
    detailContent.textContent='Loading evaluation details…';

    fetch(`ea_results.php?details=${encodeURIComponent(id)}`, {credentials:'same-origin'})
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
