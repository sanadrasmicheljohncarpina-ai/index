<?php
// admin/ea_evaluate.php
// The per-person EA evaluation form. Reached from ea_evaluation.php via
// ?type=<Principal|Dean|Staff>&user_id=<id>. Matches the same
// evaluate-page pattern used by principal_evaluate.php and
// dean/dean_evaluate.php: a server-rendered form with a person card,
// one card per question with a 1-5 rating, a comment box, and a
// read-only view once already submitted this period.
//
// Questions are not hardcoded here. They come from the centralized
// Executive Assistant (EA) target questionnaire. Each target person has
// one target-centric question set shared by all applicable evaluators.
//
// Eligibility and storage are otherwise unchanged: the target must
// currently be the active Principal/Dean or qualifying Staff
// (re-checked here, independent of ea_evaluation.php), and a submission
// writes one evaluation_tracker row (eval_type='ea') plus one
// questionnaire_answers row per question.
session_set_cookie_params([
    'lifetime' => 0, 'path' => '/', 'domain' => '',
    'secure' => false, 'httponly' => true, 'samesite' => 'Lax',
]);
session_start();
require_once 'db.php';
require_once dirname(__DIR__) . '/shared/QuestionnaireService.php';
qn_migrate_legacy_once($mysqli);
require_once dirname(__DIR__) . '/shared/system_settings_service.php';


mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

if (!isset($_SESSION['user_id']) ||
    !in_array($_SESSION['role'] ?? '', ['admin','superadmin','executive_assistant'], true)) {
    header('Location: admin_login.php');
    exit;
}

// Apply the schedule before reading evaluation_periods.is_active.
ss_sync_from_database($mysqli);

$ea_id = (int)$_SESSION['user_id'];

try {
    $mysqli->query("ALTER TABLE evaluation_tracker MODIFY eval_type VARCHAR(30) NOT NULL DEFAULT 'student'");
} catch (Throwable $ignore) {}
foreach ([
    "ALTER TABLE evaluation_tracker ADD COLUMN evaluator_id INT UNSIGNED NULL",
    "ALTER TABLE evaluation_tracker ADD COLUMN target_user_id INT UNSIGNED NULL",
    "ALTER TABLE evaluation_tracker ADD COLUMN period_id INT UNSIGNED NULL",
    "ALTER TABLE evaluation_tracker ADD COLUMN status VARCHAR(20) NOT NULL DEFAULT 'submitted'",
] as $ddl) {
    try { $mysqli->query($ddl); } catch (Throwable $ignore) {}
}

function e($v){ return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }

$type = $_GET['type'] ?? '';
$targetId = isset($_GET['user_id']) ? (int)$_GET['user_id'] : 0;
$validTypes = ['Principal', 'Dean', 'Staff'];
if (!in_array($type, $validTypes, true) || $targetId <= 0) {
    header('Location: ea_evaluation.php');
    exit;
}

// Executive Assistant evaluation is intentionally available at all times.
// The global schedule continues to govern other evaluation flows, but it does
// not restrict the EA evaluator's access here.

// ── RE-DERIVE ELIGIBILITY FOR THIS TYPE (must match ea_evaluation.php) ──
if ($type === 'Principal' || $type === 'Dean') {
    $role = $type === 'Principal' ? 'principal' : 'dean';
    $stmt = $mysqli->prepare("
        SELECT id, full_name, designation, photo, role
        FROM users
        WHERE id=? AND role=? AND is_active=1 AND account_status='approved'
        LIMIT 1
    ");
    $stmt->bind_param('is', $targetId, $role);
    $stmt->execute();
    $target = $stmt->get_result()->fetch_assoc();
    $stmt->close();
} else {
    // Staff target = Staff members without teaching/year-level assignments.
    $stmt = $mysqli->prepare("
        SELECT u.id, u.full_name, u.designation, u.photo, u.role
        FROM users u
        WHERE u.id=? AND u.role='staff'
          AND u.is_active=1
          AND (u.account_status='approved' OR u.source='admin_nologin')
          AND NOT EXISTS (SELECT 1 FROM teaching_assignments ta WHERE ta.user_id=u.id)
          AND NOT EXISTS (SELECT 1 FROM user_year_levels yl WHERE yl.user_id=u.id)
        LIMIT 1
    ");
    $stmt->bind_param('i', $targetId);
    $stmt->execute();
    $target = $stmt->get_result()->fetch_assoc();
    $stmt->close();
}

if (!$target) {
    header('Location: ea_evaluation.php?type=' . urlencode($type));
    exit;
}

$period = $mysqli->query("SELECT id, period_label FROM evaluation_periods WHERE is_active=1 ORDER BY id DESC LIMIT 1")->fetch_assoc();
$period_id = (int)($period['id'] ?? 0);
$is_open = true; // EA Evaluation is always available.

// ── QUESTIONS: centralized EA target bank ───────────────────────────
$qTargetType = $type;
$qEvalType = 'general';
$qStmt = $mysqli->prepare("
    SELECT id, category, question_text, 0 AS sort_order
    FROM user_questions
    WHERE user_id=? AND target_type=? AND eval_type='general'
    ORDER BY category, id
");
$qStmt->bind_param('is', $targetId, $qTargetType);
$qStmt->execute();
$questions = $qStmt->get_result()->fetch_all(MYSQLI_ASSOC);
$qStmt->close();

// ── ALREADY SUBMITTED THIS PERIOD? ────────────────────────────────────
$existingTracker = null;
if ($period_id) {
    $chk = $mysqli->prepare("
        SELECT id, remarks, submitted_at
        FROM evaluation_tracker
        WHERE evaluator_id=? AND target_user_id=? AND period_id=? AND eval_type='ea' AND status='submitted'
        LIMIT 1
    ");
    $chk->bind_param('iii', $ea_id, $targetId, $period_id);
    $chk->execute();
    $existingTracker = $chk->get_result()->fetch_assoc();
    $chk->close();
}

$existingAnswers = [];
if ($existingTracker) {
    $ansStmt = $mysqli->prepare("
        SELECT qa.answer_score, uq.category, uq.question_text
        FROM questionnaire_answers qa
        LEFT JOIN user_questions uq
          ON uq.id = COALESCE(qa.user_question_id, qa.question_id)
        WHERE qa.tracker_id=? AND qa.question_source='user'
        ORDER BY uq.category, uq.id
    ");
    $ansStmt->bind_param('i', $existingTracker['id']);
    $ansStmt->execute();
    $existingAnswers = $ansStmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $ansStmt->close();
}
$existingAvg = null;
if ($existingAnswers) {
    $sum = array_sum(array_column($existingAnswers, 'answer_score'));
    $existingAvg = round($sum / count($existingAnswers), 2);
}

// ── CONFIDENTIALITY ────────────────────────────────────────────────────
// evaluator_id is stored on evaluation_tracker purely for audit/dedupe
// (so the same EA can't submit twice for the same person/period) — it is
// never joined to a name or shown anywhere the evaluated person could see
// it. There is currently no "my EA evaluation results" view for
// Principal/Dean/Staff at all; if one is ever built, follow
// dean/dean_results.php's convention and select score/comment/date only,
// never evaluator identity.

// ── HANDLE SUBMIT ──────────────────────────────────────────────────────
$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$existingTracker) {
    // EA Evaluation is intentionally not gated by the global schedule.
    // Re-check question availability and submission rules only.
    $is_open = true;

    if (!$questions) {
        $errors[] = 'No EA questions have been assigned to this person yet.';
    } else {
        $ratings = $_POST['rating'] ?? [];
        $valid = true;
        foreach ($questions as $q) {
            $v = isset($ratings[$q['id']]) ? (int)$ratings[$q['id']] : 0;
            if ($v < 1 || $v > 5) { $valid = false; break; }
        }
        if (!$valid) {
            $errors[] = 'Please answer every question before submitting.';
        } else {
            $comment = trim($_POST['comment'] ?? '');
            $submittedAt = ss_now()->format('Y-m-d H:i:s');
            $mysqli->begin_transaction();
            try {
                $ins = $mysqli->prepare("
                    INSERT INTO evaluation_tracker
                    (evaluator_id, target_user_id, remarks, eval_type, period_id, status, submitted_at)
                    VALUES (?, ?, ?, 'ea', ?, 'submitted', ?)
                ");
                $ins->bind_param('iisis', $ea_id, $targetId, $comment, $period_id, $submittedAt);
                $ins->execute();
                $trackerId = $mysqli->insert_id;
                $ins->close();

                $ans = $mysqli->prepare("
                    INSERT INTO questionnaire_answers
                    (tracker_id, question_id, question_source, user_question_id, answer_score, submitted_at)
                    VALUES (?, NULL, 'user', ?, ?, ?)
                ");
                foreach ($questions as $q) {
                    $score = (int)$ratings[$q['id']];
                    $ans->bind_param('iiis', $trackerId, $q['id'], $score, $submittedAt);
                    $ans->execute();
                }
                $ans->close();
                $mysqli->commit();
                $mysqli->close();
                header('Location: ea_evaluation.php?type=' . urlencode($type) . '&submitted=1');
                exit;
            } catch (Throwable $ex) {
                $mysqli->rollback();
                $errors[] = 'Unable to submit the EA evaluation. Please try again.';
            }
        }
    }
}

$mysqli->close();

$tPhoto = !empty($target['photo']) ? '../image/' . $target['photo'] : '';
$questionGroups = [];
foreach ($questions as $q) { $questionGroups[$q['category'] ?: 'General'][] = $q; }
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Evaluate <?= e($target['full_name']) ?> — PBI</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<style>
:root{--bg:#F8FAFC;--panel:#FFFFFF;--panel2:#E6F0FF;--inner:#F8FAFC;--line:#B9CDE5;--text:#0B1F3A;--muted:#67819E;--purple:#2563EB;--purple-dark:#2563EB;--green:#0F9F6E;--shadow:0 2px 4px rgba(30,82,144,.05),0 6px 16px rgba(30,82,144,.08)}
*{box-sizing:border-box} body{margin:0;background:var(--bg);color:var(--text);font-family:Inter,Segoe UI,Arial,sans-serif}
.top{height:74px;background:#FFFFFF;border-bottom:1px solid var(--line);display:flex;align-items:center;padding:0 34px;gap:26px;position:sticky;top:0;z-index:5}
.brand{font-weight:800;letter-spacing:.4px;flex:1;color:var(--text)}.brand i{color:var(--purple);margin-right:9px}
.account{color:var(--muted);font-size:13px}
.wrap{max-width:820px;margin:auto;padding:34px}
.back-link{color:var(--text);font-size:13.5px;font-weight:700;text-decoration:none;display:inline-flex;align-items:center;gap:8px;margin-bottom:20px;background:var(--panel);border:1px solid var(--line);padding:10px 16px;border-radius:9px}
.back-link:hover{border-color:#93C5FD;background:var(--panel2)}
.person-card{display:flex;align-items:center;gap:16px;background:var(--panel);border:1px solid var(--line);border-radius:14px;padding:20px 22px;box-shadow:var(--shadow);margin-bottom:22px}
.person-photo{width:60px;height:60px;border-radius:50%;object-fit:cover;background:var(--inner);flex-shrink:0;display:flex;align-items:center;justify-content:center;color:var(--muted);font-size:22px}
.person-name{font-size:20px;font-weight:800;color:var(--text)}
.person-meta{font-size:12.5px;color:var(--muted);margin-top:2px}
.badge{display:inline-block;margin-top:6px;color:var(--purple);font-size:11.5px;background:#E6F0FF;padding:4px 10px;border-radius:99px;font-weight:700}
.alert{border-radius:10px;padding:13px 16px;font-size:13.5px;margin-bottom:18px;display:flex;align-items:center;gap:8px}
.alert-error{background:rgba(248,113,113,.1);border:1px solid rgba(248,113,113,.25);color:#ffb4b4}
.alert-info{background:#E6F0FF;border:1px solid #B8D4F8;color:#2563EB}
.q-block{background:var(--panel);border:1px solid var(--line);border-radius:12px;overflow:hidden;box-shadow:var(--shadow);margin-bottom:12px}
.q-cat{font-size:11px;font-weight:700;letter-spacing:1px;text-transform:uppercase;color:var(--purple);margin-bottom:6px}
.cat-heading{display:flex;align-items:center;gap:8px;font-size:12.5px;font-weight:800;letter-spacing:1px;text-transform:uppercase;color:var(--purple);margin:26px 0 10px;padding-bottom:8px;border-bottom:1px solid var(--line)}
.cat-heading:first-child{margin-top:0}
.cat-heading i{font-size:11px}
.q-num{color:var(--purple);font-weight:800;margin-right:8px}
.q-text{font-size:14px;color:var(--text);line-height:1.45;margin:0}
.eval-table{width:100%;border-collapse:collapse;table-layout:fixed}
.eval-table th{background:var(--panel2);color:var(--muted);font-size:11px;font-weight:800;letter-spacing:.5px;text-align:center;padding:11px 8px;border-bottom:1px solid var(--line)}
.eval-table th:first-child{text-align:left;width:auto}
.eval-table th:not(:first-child){width:58px}
.eval-table td{padding:12px 8px;border-bottom:1px solid #E6EEF7;vertical-align:middle;text-align:center}
.eval-table tr:last-child td{border-bottom:none}
.eval-table td:first-child{text-align:left;padding-left:16px;padding-right:14px}
.rating-opt{display:flex;justify-content:center;align-items:center}
.rating-opt input{position:absolute;opacity:0;pointer-events:none}
.rating-opt label{width:38px;height:34px;display:flex;align-items:center;justify-content:center;border-radius:7px;border:1px solid var(--line);background:var(--inner);color:var(--muted);font-size:13px;font-weight:800;cursor:pointer;transition:.15s ease}
.rating-opt label:hover{border-color:#93C5FD;background:#F1F7FF}
.rating-opt input:checked + label{background:var(--purple);border-color:var(--purple);color:#fff}
.rating-readonly{display:flex;align-items:center;justify-content:center;gap:6px;font-weight:800;color:var(--text)}
.rating-readonly .stars{color:#c4b5fd;letter-spacing:1px}
.readonly-table .score-cell{font-size:12px;color:var(--muted);font-weight:800}
.comment-block{background:var(--panel);border:1px solid var(--line);border-radius:14px;padding:20px 22px;box-shadow:var(--shadow);margin-bottom:22px}
.comment-block label{display:block;font-size:11px;font-weight:700;letter-spacing:1px;text-transform:uppercase;color:var(--muted);margin-bottom:10px}
.comment-block textarea{width:100%;min-height:100px;background:var(--inner);border:1px solid var(--line);border-radius:8px;color:var(--text);font-size:13.5px;font-family:inherit;padding:12px;outline:none;resize:vertical}
.comment-block textarea:focus{border-color:var(--purple)}
.comment-readonly{font-size:13.5px;color:var(--text);font-style:italic;line-height:1.6}
.btn-submit{padding:13px 26px;background:var(--purple);border:none;border-radius:10px;color:#fff;font-size:14.5px;font-weight:700;cursor:pointer;display:inline-flex;align-items:center;gap:8px}
.btn-submit:hover{background:var(--purple-dark)}
.summary-score{display:flex;align-items:baseline;gap:8px;margin-bottom:4px}
.summary-score .num{font-size:32px;font-weight:800;color:var(--text)}
.summary-score .of{font-size:13px;color:var(--muted)}
@media(max-width:900px){.top{padding:0 18px}.wrap{padding:20px}.eval-table th:not(:first-child){width:50px}.rating-opt label{width:34px;height:32px}}

/* Required-question guidance: replace the browser's generic radio validation popup with a clear reminder. */
.question-progress{margin:0 0 14px;padding:11px 14px;border:1px solid var(--line);border-radius:10px;background:var(--panel);color:var(--muted);font-size:13px;font-weight:700;display:flex;align-items:center;gap:8px;}
.question-progress.complete{border-color:rgba(34,197,94,.35);color:#15803D;background:rgba(34,197,94,.07)}
.eval-client-error{margin-bottom:16px;}
.eval-client-error[hidden]{display:none;}
.eval-table tr.question-missing td{background:rgba(248,113,113,.08);border-bottom-color:rgba(248,113,113,.28)}
.eval-table tr.question-missing td:first-child{box-shadow:inset 4px 0 0 #EF4444;}
.eval-table tr.question-missing .q-num{color:#DC2626;}
.rating-opt input:focus-visible + label{outline:3px solid rgba(37,99,235,.3);outline-offset:2px;}
</style>
    <link rel="stylesheet" href="admin_ui_theme.css">
    <link rel="stylesheet" href="admin_compact_ui.css">
<style id="pbi-feature-scrollbar">

/* PBI FEATURE SCROLLBAR — consistent with the compact page scrollbar */
html, body {
  scrollbar-width: thin !important;
  scrollbar-color: #888 transparent !important;
}
html::-webkit-scrollbar, body::-webkit-scrollbar,
.feature-compact ::-webkit-scrollbar { width: 10px !important; height: 10px !important; }
html::-webkit-scrollbar-track, body::-webkit-scrollbar-track,
.feature-compact ::-webkit-scrollbar-track { background: transparent !important; }
html::-webkit-scrollbar-thumb, body::-webkit-scrollbar-thumb,
.feature-compact ::-webkit-scrollbar-thumb {
  background: #888 !important; border-radius: 999px !important;
  border: 2px solid transparent !important; background-clip: padding-box !important;
}
html::-webkit-scrollbar-thumb:hover, body::-webkit-scrollbar-thumb:hover,
.feature-compact ::-webkit-scrollbar-thumb:hover { background: #777 !important; background-clip: padding-box !important; }
html::-webkit-scrollbar-button, body::-webkit-scrollbar-button,
.feature-compact ::-webkit-scrollbar-button { display: block !important; width: 10px !important; height: 10px !important; background-color: transparent !important; }

</style>

<link rel="stylesheet" href="admin_appearance.css">
<script src="admin_appearance.js"></script>
<style id="dean-structure">
/* Dean evaluate layout applied to the EA form */
.wrap{max-width:1200px}
.back-link{background:none;border:0;padding:0;border-radius:0;color:var(--purple);font-weight:600;margin-bottom:26px}
.back-link:hover{background:none;text-decoration:underline}
.eval-meta{display:flex;justify-content:space-between;gap:24px;flex-wrap:wrap;margin:0 4px 26px}
.eval-meta span{display:block;font-size:11px;font-weight:600;letter-spacing:1px;text-transform:uppercase;color:var(--muted);margin-bottom:6px}
.eval-meta strong{font-size:17px;font-weight:700;color:var(--text)}
.person-card{padding:22px 26px;gap:20px;margin-bottom:30px}
.person-photo{width:76px;height:76px}
.person-name{font-size:22px}
.person-meta{font-size:13.5px;margin-top:4px}
.cat-heading{border-bottom:0;padding-bottom:0;margin:30px 0 12px;font-size:12px}
.cat-heading i{display:none}
.q-block{border-radius:16px}
.eval-table th:first-child{padding-left:20px}
.eval-table td:first-child{padding-left:20px}
.q-num{color:var(--purple)}
.req{color:#DC2626;margin-left:4px;font-weight:700}
.rating-static{display:flex;justify-content:center}
.rating-static span{width:38px;height:34px;display:flex;align-items:center;justify-content:center;border-radius:7px;border:1px solid var(--line);background:var(--inner);color:var(--muted);font-size:13px;font-weight:800;opacity:.55}
.rating-static span.on{background:var(--purple);border-color:var(--purple);color:#fff;opacity:1}
/* Scrollbar: dark rounded thumb with up/down arrow buttons, like the Dean page */
html, body{scrollbar-width:auto !important;scrollbar-color:#4B5563 transparent !important}
html::-webkit-scrollbar, body::-webkit-scrollbar{width:14px !important;height:14px !important}
html::-webkit-scrollbar-track, body::-webkit-scrollbar-track{background:transparent !important}
html::-webkit-scrollbar-thumb, body::-webkit-scrollbar-thumb{background:#4B5563 !important;border-radius:999px !important;border:3px solid transparent !important;background-clip:padding-box !important}
html::-webkit-scrollbar-thumb:hover, body::-webkit-scrollbar-thumb:hover{background:#1F2937 !important;background-clip:padding-box !important}
html::-webkit-scrollbar-button, body::-webkit-scrollbar-button{display:block !important;height:14px !important;width:14px !important;background-color:transparent !important}
</style>
</head>
<body class="feature-compact">
<header class="top">
  <div class="brand"><i class="fa-solid fa-user-check"></i>EA Evaluation</div>
  <div class="account"><?= e($_SESSION['full_name'] ?? 'Executive Assistant') ?></div>
</header>
<main class="wrap">
<a class="back-link" href="ea_evaluation.php?type=<?= urlencode($type) ?>"><i class="fa-solid fa-arrow-left"></i> Back to EA Evaluation</a>

<div class="eval-meta">
  <div><span>Evaluation period</span><strong><?= e($period['period_label'] ?? 'No active period') ?></strong></div>
  <div><span>Current state</span><strong><?= $existingTracker ? 'Submitted' : ($period_id ? 'Open' : 'No active period') ?></strong></div>
</div>

<div class="person-card">
  <?php if ($tPhoto): ?><img class="person-photo" src="<?= e($tPhoto) ?>" alt=""><?php else: ?><div class="person-photo"><i class="fa-solid fa-user"></i></div><?php endif; ?>
  <div>
    <div class="person-name"><?= e($target['full_name']) ?></div>
    <div class="person-meta"><?= e($target['designation'] ?: $type) ?></div>
  </div>
</div>

<?php foreach ($errors as $err): ?>
<div class="alert alert-error"><i class="fa-solid fa-circle-exclamation"></i> <?= e($err) ?></div>
<?php endforeach; ?>

<?php if (!$period_id): ?>
<div class="alert alert-info"><i class="fa-solid fa-circle-info"></i> No active evaluation period right now.</div>
<?php elseif (!$is_open && !$existingTracker): ?>
<div class="alert alert-info"><i class="fa-solid fa-lock"></i> Evaluation is currently closed for this period.</div>
<?php endif; ?>

<?php if ($existingTracker): ?>
    <div class="alert alert-info"><i class="fa-solid fa-eye"></i> You already evaluated <?= e($target['full_name']) ?> this period — showing what was submitted.</div>
    <div class="q-block">
        <?php if ($existingAvg !== null): ?>
        <div class="summary-score"><span class="num"><?= e($existingAvg) ?></span><span class="of">/ 5.00 overall</span></div>
        <?php endif; ?>
        <div class="person-meta">Submitted <?= e(date('M j, Y g:i A', strtotime($existingTracker['submitted_at']))) ?></div>
    </div>
    <?php $existingGroups = []; foreach ($existingAnswers as $a) { $existingGroups[$a['category'] ?: 'General'][] = $a; } ?>
    <?php $qn = 0; foreach ($existingGroups as $cat => $answers): ?>
    <div class="cat-heading"><i class="fa-solid fa-layer-group"></i> <?= e($cat) ?></div>
    <div class="q-block readonly-table">
        <table class="eval-table">
            <thead>
                <tr><th>Question</th><th>5</th><th>4</th><th>3</th><th>2</th><th>1</th></tr>
            </thead>
            <tbody>
            <?php foreach ($answers as $a): $qn++; $score=(int)$a['answer_score']; ?>
                <tr>
                    <td><div class="q-text"><span class="q-num"><?= $qn ?>.</span><?= e($a['question_text']) ?></div></td>
                    <?php for ($n=5; $n>=1; $n--): ?>
                    <td><div class="rating-static"><span class="<?= $n === $score ? 'on' : '' ?>"><?= $n ?></span></div></td>
                    <?php endfor; ?>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php endforeach; ?>
    <div class="comment-block">
        <label>Comment</label>
        <p class="comment-readonly"><?= $existingTracker['remarks'] !== '' ? e($existingTracker['remarks']) : 'No written comment.' ?></p>
    </div>
<?php elseif (!$questions): ?>
    <?php
        $manageLabel = 'Questionnaire → Executive Assistant Evaluation → ' . $type;
    ?>
    <div class="alert alert-info"><i class="fa-solid fa-circle-info"></i> No questions have been assigned to <?= e($target['full_name']) ?> yet. Go to <a href="questionnaire.php?view=manage&eval_type=<?= urlencode($qEvalType) ?>&target=<?= urlencode($qTargetType) ?>&user_id=<?= $targetId ?>" style="color:#2563EB;font-weight:700;"><?= e($manageLabel) ?></a> and select this person to assign their questions.</div>
<?php else: ?>
    <form method="post">
        <?php $qn = 0; foreach ($questionGroups as $cat => $qs): ?>
        <div class="cat-heading"><i class="fa-solid fa-layer-group"></i> <?= e($cat) ?></div>
        <div class="q-block">
            <table class="eval-table">
                <thead>
                    <tr><th>Question</th><th>5</th><th>4</th><th>3</th><th>2</th><th>1</th></tr>
                </thead>
                <tbody>
                <?php foreach ($qs as $q): $qn++; ?>
                    <tr>
                        <td><div class="q-text"><span class="q-num"><?= $qn ?>.</span><?= e($q['question_text']) ?><span class="req" title="Required">*</span></div></td>
                        <?php for ($n = 5; $n >= 1; $n--): ?>
                        <td>
                            <div class="rating-opt">
                                <input type="radio" name="rating[<?= (int)$q['id'] ?>]" id="q<?= (int)$q['id'] ?>_<?= $n ?>" value="<?= $n ?>">
                                <label for="q<?= (int)$q['id'] ?>_<?= $n ?>"><?= $n ?></label>
                            </div>
                        </td>
                        <?php endfor; ?>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endforeach; ?>

        <div class="question-progress" id="questionProgress" role="status" aria-live="polite">
            <i class="fa-solid fa-circle-check"></i> <span id="questionProgressText">0 of <?= count($questions) ?> questions answered</span>
        </div>

        <div class="alert alert-error eval-client-error" id="evalClientError" role="alert" aria-live="assertive" hidden>
            <i class="fa-solid fa-circle-exclamation"></i>
            <div><strong>Please complete all required questions.</strong><div id="evalClientErrorText"></div></div>
        </div>

        <div class="comment-block">
            <label for="comment">Comments/ Suggestions</label>
            <textarea id="comment" name="comment" placeholder="Comments, suggestions, or areas for improvement..."></textarea>
        </div>

        <button type="submit" class="btn-submit">
            <i class="fa-solid fa-paper-plane"></i> Submit EA Evaluation
        </button>
    </form>
<?php endif; ?>
</main>

<script>
(function(){
    const form = document.querySelector('main.wrap form');
    if (!form) return;

    const rows = Array.from(form.querySelectorAll('.eval-table tbody tr'));
    const progress = document.getElementById('questionProgress');
    const progressText = document.getElementById('questionProgressText');
    const errorBox = document.getElementById('evalClientError');
    const errorText = document.getElementById('evalClientErrorText');
    const submitBtn = form.querySelector('.btn-submit');

    function getQuestionRows(){
        return rows.filter(row => row.querySelector('input[type="radio"]'));
    }

    function updateProgress(mark){
        const qRows = getQuestionRows();
        let answered = 0;
        const missing = [];
        qRows.forEach((row, idx) => {
            const checked = row.querySelector('input[type="radio"]:checked');
            row.classList.toggle('question-missing', !!mark && !checked);
            if (checked) answered++;
            else missing.push(idx + 1);
        });
        if (progressText) progressText.textContent = answered + ' of ' + qRows.length + ' questions answered';
        if (progress) progress.classList.toggle('complete', qRows.length > 0 && answered === qRows.length);
        return {answered, total:qRows.length, missing};
    }

    form.querySelectorAll('input[type="radio"]').forEach(input => {
        input.addEventListener('change', () => {
            const state = updateProgress(errorBox && !errorBox.hidden);
            if (state.missing.length === 0 && errorBox) errorBox.hidden = true;
        });
    });

    form.addEventListener('submit', function(e){
        const state = updateProgress(true);
        if (state.missing.length === 0) {
            if (errorBox) errorBox.hidden = true;
            if (submitBtn) {
                submitBtn.disabled = true;
                submitBtn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Submitting...';
            }
            return;
        }

        e.preventDefault();
        const labels = state.missing.map(n => 'Question ' + n);
        if (errorText) errorText.textContent = state.missing.length === 1
            ? labels[0] + ' is unanswered. Please select a rating from 1 to 5.'
            : labels.join(', ') + ' are unanswered. Please select a rating from 1 to 5 for each.';
        if (errorBox) { errorBox.hidden = false; errorBox.scrollIntoView({behavior:'smooth', block:'center'}); }

        const firstMissing = getQuestionRows()[state.missing[0]-1];
        if (firstMissing) {
            firstMissing.scrollIntoView({behavior:'smooth', block:'center'});
            const firstRadio = firstMissing.querySelector('input[type="radio"]');
            if (firstRadio) setTimeout(() => firstRadio.focus({preventScroll:true}), 300);
        }
    });

    updateProgress(false);
})();
</script>
</body>
</html>