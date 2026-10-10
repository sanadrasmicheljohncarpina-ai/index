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
// embed=1: rendered inside the popup on ea_evaluation.php.
$embed = !empty($_GET['embed']);

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
                $doneUrl = 'ea_evaluation.php?type=' . urlencode($type) . '&submitted=1';
                if ($embed) {
                    echo '<!doctype html><meta charset="utf-8"><script>var u=' . json_encode($doneUrl) . ';var p=window.parent;if(p.pbiEvalDone){p.pbiEvalDone(u);}else{p.location.href=u;}</script>';
                } else {
                    header('Location: ' . $doneUrl);
                }
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
<link href="https://fonts.googleapis.com/css2?family=Rajdhani:wght@600;700&family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="admin_appearance.css">
<script src="admin_appearance.js"></script>
<style>
/* EA evaluation form — same look and behaviour as the student evaluation form. */
:root{
    --mid:#ffffff;--inner:#f8fbff;--soft:#eef4fb;
    /* This evaluation form uses the admin's gold accent regardless of a saved site-wide accent. */
    --accent:#B8860B !important;--accent-h:#946A08;--accent-soft:rgba(184,134,11,.10);--accent-line:rgba(184,134,11,.30);
    --light:#10243f;--muted:#66809c;--border:#dce8f5;--radius:10px;
    --name:#10243f;--legend-bg:#f8fbff;--th-bg:#eef4fb;--td-line:#e6eef7;
    --overlay:rgba(9,17,30,.64);--modal-border:#d7e3ef;--modal-shadow:0 30px 80px rgba(15,23,42,.24);
    --footer-bg:rgba(255,255,255,.96);--footer-line:#e3ecf6;--comment-border:#dce8f5;--ta-bg:#fff;--ta-line:#cfdceb;--ta-color:#10243f;
}
html[data-theme="dark"]{
    --mid:#132844;--inner:#10233D;--soft:#142A47;
    --light:#F5F7FB;--muted:#9CB0CA;--border:#213A5C;
    --name:#F5F7FB;--legend-bg:#142A47;--th-bg:#142A47;--td-line:#213A5C;
    --overlay:rgba(0,0,0,.75);--modal-border:#223A5B;--modal-shadow:0 20px 60px rgba(0,0,0,.6);
    --footer-bg:#132844;--footer-line:#213A5C;--comment-border:#213A5C;--ta-bg:#07192D;--ta-line:#213A5C;--ta-color:#F5F7FB;
}
*{box-sizing:border-box}
html,body{margin:0;min-height:100%;}
body{font-family:'Inter',Segoe UI,Arial,sans-serif;background:#f4f8ff!important;color:var(--light);}
html[data-theme="dark"] body{background:#07192D!important;}
body.embed,html[data-theme="dark"] body.embed{background:transparent!important;}
html:has(body.embed){background:transparent!important;}

.modal-overlay{position:fixed;inset:0;background:var(--overlay);backdrop-filter:blur(4px);z-index:200;display:flex;align-items:center;justify-content:center;padding:20px;}
.modal{background:var(--mid);border:1px solid var(--modal-border);border-radius:18px;width:100%;max-width:780px;max-height:92vh;overflow-y:auto;box-shadow:var(--modal-shadow);display:flex;flex-direction:column;}
.modal form{display:flex;flex-direction:column;flex:1;min-height:0;}
.modal-header{padding:24px 28px 16px;border-bottom:1px solid var(--border);display:flex;align-items:center;gap:14px;position:sticky;top:0;background:var(--mid);z-index:3;box-shadow:0 1px 0 var(--footer-line);flex-shrink:0;}
.modal-avatar{width:52px;height:52px;border-radius:50%;object-fit:cover;border:2px solid var(--accent);flex-shrink:0;}
.modal-avatar-ph{width:52px;height:52px;border-radius:50%;background:var(--inner);border:2px solid var(--border);display:flex;align-items:center;justify-content:center;color:var(--muted);font-size:20px;flex-shrink:0;}
.modal-name{font-family:'Rajdhani',sans-serif;font-size:20px;font-weight:700;color:var(--name);}
.modal-desig{font-size:12px;color:var(--muted);}
.modal-close{margin-left:auto;background:none;border:none;color:var(--muted);font-size:18px;cursor:pointer;padding:4px 8px;border-radius:6px;transition:color .2s;text-decoration:none;}
.modal-close:hover{color:var(--name);}
.modal-body{padding:24px 28px;}

.scale-legend{display:flex;gap:16px;flex-wrap:wrap;margin-bottom:18px;background:var(--legend-bg);border:1px solid var(--border);border-radius:8px;padding:10px 14px;}
.legend-item{font-size:11px;color:var(--muted);display:flex;align-items:center;gap:5px;}
.legend-dot{width:18px;height:18px;border-radius:4px;background:var(--accent);display:flex;align-items:center;justify-content:center;font-size:10px;font-weight:700;color:#fff;flex-shrink:0;}

.eval-validation{display:flex;align-items:flex-start;gap:9px;background:rgba(240,84,84,.11);border:1px solid rgba(240,84,84,.42);color:#b91c1c;border-radius:9px;padding:11px 13px;margin:0 0 16px;font-size:12.5px;line-height:1.5;}
.eval-validation[hidden]{display:none!important;}
.eval-validation i{color:#ef4444;flex-shrink:0;margin-top:2px;}
.eval-validation strong{color:#991b1b;}
html[data-theme="dark"] .eval-validation{color:#ff9b9b;}
html[data-theme="dark"] .eval-validation i{color:#ff7f7f;}
html[data-theme="dark"] .eval-validation strong{color:#ffd0d0;}
.eval-info{display:flex;align-items:flex-start;gap:9px;background:var(--accent-soft);border:1px solid var(--accent-line);color:var(--accent-h);border-radius:9px;padding:11px 13px;margin:0 0 16px;font-size:12.5px;line-height:1.5;}
.eval-info i{margin-top:2px;flex-shrink:0;}
.eval-info a{color:var(--accent-h);font-weight:700;}
.eval-progress{display:flex;align-items:center;justify-content:space-between;gap:10px;background:var(--accent-soft);border:1px solid var(--accent-line);border-radius:8px;padding:9px 12px;margin:0 0 14px;color:var(--muted);font-size:11.5px;font-weight:600;}
.eval-progress .progress-status{color:var(--accent);font-weight:800;white-space:nowrap;}

.eval-form-cat{font-size:10px;text-transform:uppercase;letter-spacing:.9px;font-weight:800;color:var(--accent);margin:18px 0 8px}
.eval-form-cat:first-of-type{margin-top:0}
.eval-form-wrap{background:var(--mid);border:1px solid var(--border);border-radius:12px;overflow:hidden;overflow-x:auto;margin:0 0 16px;-webkit-overflow-scrolling:touch;}
.eval-form-table{width:100%;border-collapse:collapse;table-layout:fixed;min-width:650px}
.eval-form-table th{background:var(--th-bg);color:var(--muted);font-size:10px;font-weight:800;letter-spacing:.7px;text-transform:uppercase;text-align:center;padding:10px 6px;border-bottom:1px solid var(--border)}
.eval-form-table th:first-child{text-align:left;width:auto;padding-left:14px;min-width:360px}
.eval-form-table th:not(:first-child){width:52px}
.eval-form-table td{padding:10px 6px;border-bottom:1px solid var(--td-line);vertical-align:middle;text-align:center}
.eval-form-table tr:last-child td{border-bottom:none}
.eval-form-table td:first-child{text-align:left;padding-left:14px;padding-right:10px;min-width:360px}
.eval-form-qtext{font-size:12.5px;line-height:1.45;color:var(--light)}
.eval-form-qno{color:var(--accent);font-weight:800;margin-right:6px}
.eval-form-rating{display:flex;justify-content:center}
.eval-form-rating input{position:absolute;opacity:0;pointer-events:none}
.eval-form-rating label,.eval-form-static span{width:34px;height:30px;display:flex;align-items:center;justify-content:center;border-radius:7px;border:1px solid var(--border);background:var(--inner);color:var(--muted);font-size:12px;font-weight:800;box-shadow:0 2px 5px rgba(15,23,42,.03)}
.eval-form-rating label{cursor:pointer;transition:all .15s ease}
.eval-form-rating label:hover{border-color:var(--accent);background:var(--accent-soft)}
.eval-form-rating input:checked + label{background:var(--accent);border-color:var(--accent);color:#fff}
.eval-form-rating input:focus-visible + label{outline:3px solid rgba(184,134,11,.28);outline-offset:2px}
.eval-form-static{display:flex;justify-content:center}
.eval-form-static span{opacity:.55}
.eval-form-static span.on{background:var(--accent);border-color:var(--accent);color:#fff;opacity:1}
.eval-form-table tr.unanswered-question td{background:rgba(240,84,84,.06);}
.eval-form-table tr.unanswered-question td:first-child{box-shadow:inset 3px 0 0 #ef4444;}
.eval-form-table tr.unanswered-question .eval-form-qtext{color:#b91c1c;}
.eval-form-table tr.unanswered-question .eval-form-qno{color:#ef4444;}

.comment-box{margin-top:24px;background:var(--inner);border:1px solid var(--comment-border);border-radius:10px;padding:16px;}
.comment-label{font-size:12px;font-weight:700;color:var(--accent);margin-bottom:10px;display:flex;align-items:center;gap:7px;}
.comment-textarea{width:100%;background:var(--ta-bg);border:1px solid var(--ta-line);border-radius:8px;color:var(--ta-color);padding:12px 14px;font-size:13px;font-family:'Inter',sans-serif;resize:vertical;outline:none;transition:border-color .2s;line-height:1.5;min-height:100px;}
.comment-textarea:focus{border-color:var(--accent);box-shadow:0 0 0 3px rgba(184,134,11,.14);}
.comment-textarea::placeholder{color:#64748b;opacity:1;}
.comment-readonly{font-size:13px;color:var(--light);font-style:italic;line-height:1.6;margin:0;}

.summary-line{display:flex;align-items:baseline;gap:8px;flex-wrap:wrap;margin:0 0 16px;color:var(--muted);font-size:12px;}
.summary-line .num{font-size:26px;font-weight:800;color:var(--name);font-family:'Rajdhani',sans-serif;}

.modal-footer{padding:16px 28px 24px;display:flex;gap:12px;position:sticky;bottom:0;z-index:2;background:var(--footer-bg);border-top:1px solid var(--footer-line);backdrop-filter:blur(8px);flex-shrink:0;margin-top:auto;}
.btn-submit{flex:1;padding:13px;background:var(--accent);border:none;border-radius:var(--radius);color:#fff;font-size:15px;font-weight:700;cursor:pointer;transition:background .2s;font-family:inherit;}
.btn-submit:hover{background:var(--accent-h);}
.btn-submit:disabled{opacity:.7;cursor:not-allowed;}
.btn-cancel-modal{padding:13px 22px;background:var(--inner);border:1px solid var(--border);border-radius:var(--radius);color:var(--light);font-size:14px;font-weight:600;cursor:pointer;transition:background .2s;text-decoration:none;display:inline-flex;align-items:center;justify-content:center;font-family:inherit;}
.btn-cancel-modal:hover{background:var(--soft);}
.btn-cancel-modal.full{flex:1;}

html:has(body.embed),html:has(body.embed) body{overflow:hidden!important;height:100%!important;min-height:0!important;scrollbar-width:none!important;}
html:has(body.embed)::-webkit-scrollbar,body.embed::-webkit-scrollbar{display:none!important;width:0!important;height:0!important;}
.embed .modal-overlay{position:static;background:transparent;backdrop-filter:none;padding:0;height:100%;}
.embed .modal{max-width:none;height:100%;max-height:none;overflow:hidden!important;}
.embed .modal form{min-height:0;}
.embed .modal-body{flex:1;min-height:0;overflow-y:auto;}
@media(max-width:700px){
    .eval-progress{font-size:10.5px}.eval-validation{font-size:12px}
    .modal-body,.modal-header,.modal-footer{padding-left:18px;padding-right:18px}
    .modal-footer{display:grid;grid-template-columns:1fr 1.3fr}
    .modal-overlay{padding:10px}
}
</style>
</head>
<body class="<?= $embed ? 'embed' : '' ?>">
<?php
$backUrl = 'ea_evaluation.php?type=' . urlencode($type);
$desig   = $target['designation'] ?: $type;
$closeAttr = $embed ? ' onclick="parent.closeEvalModal();return false;"' : '';
?>
<div class="modal-overlay">
  <div class="modal" role="dialog" aria-modal="true" aria-label="Evaluate <?= e($target['full_name']) ?>">
    <div class="modal-header">
      <?php if ($tPhoto): ?>
        <img class="modal-avatar" src="<?= e($tPhoto) ?>" alt="<?= e($target['full_name']) ?>" onerror="this.outerHTML='<div class=&quot;modal-avatar-ph&quot;><i class=&quot;fa-solid fa-user&quot;></i></div>'">
      <?php else: ?>
        <div class="modal-avatar-ph"><i class="fa-solid fa-user"></i></div>
      <?php endif; ?>
      <div>
        <div class="modal-name"><?= e($target['full_name']) ?></div>
        <div class="modal-desig"><?= e($desig) ?></div>
      </div>
      <a class="modal-close" href="<?= e($backUrl) ?>"<?= $closeAttr ?> title="Close" aria-label="Close"><i class="fa-solid fa-xmark"></i></a>
    </div>

<?php if ($existingTracker): ?>
    <div class="modal-body">
      <div class="eval-info"><i class="fa-solid fa-eye"></i><div>You already evaluated <?= e($target['full_name']) ?> this period — showing what was submitted.</div></div>
      <div class="summary-line">
        <?php if ($existingAvg !== null): ?><span class="num"><?= e($existingAvg) ?></span><span>/ 5.00 overall</span><span>·</span><?php endif; ?>
        <span>Submitted <?= e(date('M j, Y g:i A', strtotime($existingTracker['submitted_at']))) ?></span>
      </div>
      <?php $existingGroups = []; foreach ($existingAnswers as $a) { $existingGroups[$a['category'] ?: 'General'][] = $a; } ?>
      <?php $qn = 0; foreach ($existingGroups as $cat => $answers): ?>
      <div class="eval-form-cat"><i class="fa-solid fa-layer-group" style="margin-right:5px"></i><?= e($cat) ?></div>
      <div class="eval-form-wrap">
        <table class="eval-form-table">
          <thead><tr><th>Question</th><th>5</th><th>4</th><th>3</th><th>2</th><th>1</th></tr></thead>
          <tbody>
          <?php foreach ($answers as $a): $qn++; $score = (int)$a['answer_score']; ?>
            <tr>
              <td><div class="eval-form-qtext"><span class="eval-form-qno"><?= $qn ?>.</span><?= e($a['question_text']) ?></div></td>
              <?php for ($n = 5; $n >= 1; $n--): ?>
              <td><div class="eval-form-static"><span class="<?= $n === $score ? 'on' : '' ?>"><?= $n ?></span></div></td>
              <?php endfor; ?>
            </tr>
          <?php endforeach; ?>
          </tbody>
        </table>
      </div>
      <?php endforeach; ?>
      <div class="comment-box">
        <div class="comment-label"><i class="fa-solid fa-comment-dots"></i> Comments, Suggestions &amp; Areas for Improvement</div>
        <p class="comment-readonly"><?= $existingTracker['remarks'] !== '' ? e($existingTracker['remarks']) : 'No written comment.' ?></p>
      </div>
    </div>
    <div class="modal-footer">
      <a class="btn-cancel-modal full" href="<?= e($backUrl) ?>"<?= $closeAttr ?>>Close</a>
    </div>

<?php elseif (!$questions): ?>
    <div class="modal-body">
      <?php foreach ($errors as $err): ?>
      <div class="eval-validation"><i class="fa-solid fa-circle-exclamation"></i><div><?= e($err) ?></div></div>
      <?php endforeach; ?>
      <?php if (!$period_id): ?>
      <div class="eval-info"><i class="fa-solid fa-circle-info"></i><div>No active evaluation period right now.</div></div>
      <?php endif; ?>
      <?php $manageLabel = 'Questionnaire → Executive Assistant Evaluation → ' . $type; ?>
      <div class="eval-info"><i class="fa-solid fa-circle-info"></i><div>No questions have been assigned to <?= e($target['full_name']) ?> yet. Go to <a href="questionnaire.php?view=manage&eval_type=<?= urlencode($qEvalType) ?>&target=<?= urlencode($qTargetType) ?>&user_id=<?= $targetId ?>"><?= e($manageLabel) ?></a> and select this person to assign their questions.</div></div>
    </div>
    <div class="modal-footer">
      <a class="btn-cancel-modal full" href="<?= e($backUrl) ?>"<?= $closeAttr ?>>Close</a>
    </div>

<?php else: ?>
    <form method="post" id="evalForm" novalidate>
      <div class="modal-body" id="modalBody">
        <div class="eval-validation" id="evalValidation" role="alert" aria-live="assertive" <?= $errors ? '' : 'hidden' ?>>
          <?php if ($errors): ?><i class="fa-solid fa-circle-exclamation"></i><div><?php foreach ($errors as $err): ?><div><?= e($err) ?></div><?php endforeach; ?></div><?php endif; ?>
        </div>
        <div class="eval-progress" id="evalProgress"><span>Please rate every question before submitting.</span><span class="progress-status" id="evalProgressStatus">0 / <?= count($questions) ?> answered</span></div>
        <div class="scale-legend">
          <div class="legend-item"><div class="legend-dot">5</div> Always</div>
          <div class="legend-item"><div class="legend-dot">4</div> Often</div>
          <div class="legend-item"><div class="legend-dot">3</div> Sometimes</div>
          <div class="legend-item"><div class="legend-dot">2</div> Rarely</div>
          <div class="legend-item"><div class="legend-dot">1</div> Never</div>
        </div>
        <?php $qn = 0; foreach ($questionGroups as $cat => $qs): ?>
        <div class="eval-form-cat"><i class="fa-solid fa-layer-group" style="margin-right:5px"></i><?= e($cat) ?></div>
        <div class="eval-form-wrap">
          <table class="eval-form-table">
            <thead><tr><th>Question</th><th>5</th><th>4</th><th>3</th><th>2</th><th>1</th></tr></thead>
            <tbody>
            <?php foreach ($qs as $q): $qn++; $qid = (int)$q['id']; ?>
              <tr data-question-number="<?= $qn ?>">
                <td><div class="eval-form-qtext"><span class="eval-form-qno"><?= $qn ?>.</span><?= e($q['question_text']) ?></div></td>
                <?php for ($n = 5; $n >= 1; $n--): ?>
                <td><div class="eval-form-rating">
                  <input type="radio" name="rating[<?= $qid ?>]" id="r_<?= $qid ?>_<?= $n ?>" value="<?= $n ?>" required aria-label="Question <?= $qn ?>, rating <?= $n ?>">
                  <label for="r_<?= $qid ?>_<?= $n ?>"><?= $n ?></label>
                </div></td>
                <?php endfor; ?>
              </tr>
            <?php endforeach; ?>
            </tbody>
          </table>
        </div>
        <?php endforeach; ?>
        <div class="comment-box">
          <div class="comment-label"><i class="fa-solid fa-comment-dots"></i> Comments, Suggestions &amp; Areas for Improvement</div>
          <textarea name="comment" class="comment-textarea" rows="4"
            placeholder="Share your thoughts, suggestions, or concerns about this person's performance...."><?= e($_POST['comment'] ?? '') ?></textarea>
        </div>
      </div>
      <div class="modal-footer">
        <a class="btn-cancel-modal" href="<?= e($backUrl) ?>"<?= $closeAttr ?>>Cancel</a>
        <button type="submit" class="btn-submit"><i class="fa-solid fa-paper-plane"></i> Submit Evaluation</button>
      </div>
    </form>
<?php endif; ?>
  </div>
</div>

<script>
(function(){
    const form = document.getElementById('evalForm');
    if (!form) return;

    function names(){
        return [...new Set([...form.querySelectorAll('input[type="radio"][name^="rating["]')].map(r => r.name))];
    }
    function isAnswered(name){ return !!form.querySelector('input[name="' + CSS.escape(name) + '"]:checked'); }
    function updateProgress(){
        const status = form.querySelector('#evalProgressStatus');
        const all = names();
        const answered = all.filter(isAnswered).length;
        if (status) {
            status.textContent = answered + ' / ' + all.length + ' answered';
            status.style.color = answered === all.length ? 'var(--accent)' : '';
        }
    }
    function clearValidation(){
        const box = form.querySelector('#evalValidation');
        if (box) { box.hidden = true; box.innerHTML = ''; }
        form.querySelectorAll('.unanswered-question').forEach(r => r.classList.remove('unanswered-question'));
    }
    function showValidation(unanswered){
        const box = form.querySelector('#evalValidation');
        const rows = unanswered.map(n => form.querySelector('input[name="' + CSS.escape(n) + '"]')?.closest('tr')).filter(Boolean);
        form.querySelectorAll('.unanswered-question').forEach(r => r.classList.remove('unanswered-question'));
        rows.forEach(r => r.classList.add('unanswered-question'));
        const nums = rows.map(r => r.dataset.questionNumber).filter(Boolean);
        const detail = (nums.length && nums.length <= 6) ? ' Missing: ' + nums.map(n => 'Question ' + n).join(', ') + '.' : '';
        if (box) {
            box.innerHTML = '<i class="fa-solid fa-circle-exclamation"></i><div><strong>Please complete all required questions.</strong> You have '
                + unanswered.length + ' unanswered question' + (unanswered.length === 1 ? '' : 's') + '.' + detail + '</div>';
            box.hidden = false;
        }
        const first = rows[0];
        if (first) {
            first.scrollIntoView({behavior:'smooth', block:'center'});
            const radio = first.querySelector('input[type="radio"]');
            if (radio) setTimeout(() => radio.focus({preventScroll:true}), 120);
        }
    }

    form.addEventListener('change', function(e){
        const radio = e.target.closest('input[type="radio"][name^="rating["]');
        if (!radio) return;
        radio.closest('tr')?.classList.remove('unanswered-question');
        updateProgress();
        if (!names().filter(n => !isAnswered(n)).length) clearValidation();
    });

    form.addEventListener('submit', function(e){
        const unanswered = names().filter(n => !isAnswered(n));
        updateProgress();
        if (unanswered.length) { e.preventDefault(); showValidation(unanswered); return; }
        clearValidation();
        const btn = form.querySelector('.btn-submit');
        if (btn) { btn.disabled = true; btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Submitting Evaluation...'; }
    });

    updateProgress();
})();
</script>
</body>
</html>