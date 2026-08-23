<?php
// dean/dean_evaluate.php
// The per-person evaluation form for the Dean portal. Reached from
// dean_evaluation.php via ?tab=<faculty|staff|executive_assistant>&user_id=.
//
// ── QUESTIONS COME FROM THE REAL QUESTIONNAIRE DB ────────────────────
// Previously this page had its own hardcoded $QUESTION_SETS array (five
// generic questions per role, baked into this file, not editable from
// any admin screen). That's been removed. Questions now come from the
// same questionnaire_forms/questionnaire_questions tables
// principal_evaluate.php reads, keyed by the same eval_type values
// (supervisor_to_teacher / supervisor_to_staff / supervisor_to_ea) — a
// "supervisor evaluates a Teacher/Staff/EA" form is the same form
// whether the supervisor is a Principal (Basic Ed) or a Dean (College),
// so both portals now show identical, centrally-authored questions.
//
// ── STORAGE ────────────────────────────────────────────────────────
// Submitting now writes into the same tables/columns
// principal_evaluate.php uses — one evaluation_tracker row (evaluator_id
// = the Dean, target_user_id, eval_bucket, level='college', form_id,
// form_type = form title, period_id, score = average of rating answers,
// remarks, eval_type, status='submitted') plus one questionnaire_answers
// row per question (tracker_id, question_id, answer_text, answer_score).
// The old Dean-only eval_type='dean' + evaluation_answers(category,
// question, score) pairing is gone — nothing else in the codebase read
// from it, so this was safe to retire.

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
require_once dirname(__DIR__) . '/shared/ea_personnel_service.php';

// ── AUTH GUARD ────────────────────────────────────────────
if (empty($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'dean') {
    header("Location: dean_login.php");
    exit;
}
$deanId = (int)$_SESSION['user_id'];

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

// ── VALIDATE tab + user_id ──────────────────────────────────────────
$validTabs = ['faculty', 'staff', 'executive_assistant'];
$tab = $_GET['tab'] ?? '';
if (!in_array($tab, $validTabs, true)) {
    http_response_code(404);
    exit('Unknown evaluation type.');
}
$targetId = (int)($_GET['user_id'] ?? 0);
if ($targetId <= 0) {
    http_response_code(404);
    exit('Missing user_id.');
}
$viewOnly = isset($_GET['view']);

// eval_type: SAME real questionnaire pipeline principal_evaluate.php uses
// (questionnaire_forms + questionnaire_questions), not a Dean-only
// hardcoded set. A "supervisor evaluates a Teacher/Staff/EA" form is the
// same form regardless of whether the supervisor is a Principal (Basic
// Ed) or a Dean (College) — reusing it here means both portals show the
// same authored questions, and an admin only has to maintain the
// question bank in one place.
$tabConfig = [
    'faculty'              => ['role' => 'teacher',     'bucket' => 'Faculty',              'label' => 'Teacher',              'eval_type' => 'supervisor_to_teacher'],
    'staff'                => ['role' => 'staff',        'bucket' => 'Staff',                'label' => 'Staff',                'eval_type' => 'supervisor_to_staff'],
    'executive_assistant'  => ['role' => 'superadmin', 'bucket' => 'Executive Assistant',  'label' => 'Executive Assistant',  'eval_type' => 'supervisor_to_ea'],
];
$cfg = $tabConfig[$tab];
$evalType = $cfg['eval_type'];

// ── LOAD FORM + QUESTIONS FROM THE DB (not hardcoded) ────────────────
$formStmt = $mysqli->prepare("SELECT id, title FROM questionnaire_forms WHERE eval_type=? AND is_active=1 LIMIT 1");
$formStmt->bind_param("s", $evalType);
$formStmt->execute();
$form = $formStmt->get_result()->fetch_assoc();
$formStmt->close();

$questions = [];
if ($form) {
    $qstmt = $mysqli->prepare("SELECT id, question_no, question, type, max_score, is_required FROM questionnaire_questions WHERE form_id=? ORDER BY question_no ASC, id ASC");
    $qstmt->bind_param("i", $form['id']);
    $qstmt->execute();
    $questions = $qstmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $qstmt->close();
}

// ── GLOBAL SYSTEM SETTINGS ──────────────────────────────────────────
$settings = get_system_settings($mysqli);
$structureActive = ($settings['academic_structure'] === 'college');
$period_id_int   = $settings['period_id'] ?? 0;
$hasPeriod       = $period_id_int > 0;
$evalOpen        = $settings['is_open_for_submission'];
const HIGHER_ED_LABEL = 'Higher Education';

// No active form configured for this eval_type at all — same message
// principal_evaluate.php shows, rather than pretending questions exist.
if (!$form) {
    $mysqli->close();
    ?>
    <!DOCTYPE html><html lang="en"><head><meta charset="UTF-8"/>
    <title>PBI — Form Not Configured</title>
    <link href="https://fonts.googleapis.com/css2?family=Rajdhani:wght@600;700&family=DM+Sans:wght@400;500;600&display=swap" rel="stylesheet"/>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css"/>
    <style>body{min-height:100vh;background:#0A192F;font-family:'DM Sans',sans-serif;color:#E0E6F0;display:flex;align-items:center;justify-content:center;} .box{max-width:480px;text-align:center;padding:30px;} a{color:#9C85F0;}</style>
    </head><body>
    <div class="box">
        <p>No active <?= htmlspecialchars($cfg['label']) ?> evaluation form is configured yet. Contact your administrator.</p>
        <p><a href="dean_evaluation.php?tab=<?= urlencode($tab) ?>">Back to Evaluation</a></p>
    </div>
    </body></html>
    <?php
    exit;
}

// ── DEAN PROFILE (for sidebar) ─────────────────────────────
$stmt = $mysqli->prepare("SELECT full_name, designation, photo FROM users WHERE id = ? LIMIT 1");
$stmt->bind_param("i", $deanId);
$stmt->execute();
$me = $stmt->get_result()->fetch_assoc();
$stmt->close();
$photo_src = !empty($me['photo']) ? UPLOAD_URL . $me['photo'] : UPLOAD_URL . 'pbi_logo';

// ── TARGET PERSON (must be approved/active — same criteria as the EA's
//    Manage Registrations roster: role + account_status='approved' +
//    is_active=1. No College/user_year_levels gate, so anyone who shows
//    up in ea_get_faculty()/ea_get_staff() can actually be opened here
//    too, instead of 404ing on people missing a year-level assignment.) ─
$target = safe_rows($mysqli, "
    SELECT id, full_name, photo, department, designation FROM users
    WHERE id=? AND role=? AND is_active=1 AND account_status='approved'
    LIMIT 1
", "is", [$targetId, $cfg['role']]);
$target = $target[0] ?? null;

if (!$structureActive || !$target) {
    http_response_code(404);
    exit('Person not found or not in the current Higher Education scope.');
}
// Executive Assistant is a designation of the Super Admin account;
// the database role remains 'superadmin'.
$targetRoleLabel = $cfg['label'];

// ── EXISTING SUBMISSION THIS PERIOD? ────────────────────────────────
// Keyed by eval_type now (supervisor_to_teacher/staff/ea) instead of the
// old eval_type='dean' silo, so this lives in the same pool of
// submissions principal_evaluate.php writes to — one evaluation_tracker
// row per (evaluator, target, eval_type, period), same as everywhere else.
$existing = null;
if ($hasPeriod) {
    $rows = safe_rows($mysqli, "
        SELECT id, score, remarks AS comment, submitted_at FROM evaluation_tracker
        WHERE eval_type=? AND status IN ('submitted','approved')
          AND evaluator_id=? AND target_user_id=? AND period_id=?
        LIMIT 1
    ", "siii", [$evalType, $deanId, $targetId, $period_id_int]);
    $existing = $rows[0] ?? null;
}
$existingAnswers = [];
if ($existing) {
    $existingAnswers = safe_rows($mysqli, "
        SELECT qq.question AS question, qa.answer_text, qa.answer_score AS score
        FROM questionnaire_answers qa
        INNER JOIN questionnaire_questions qq ON qq.id = qa.question_id
        WHERE qa.tracker_id=?
        ORDER BY qq.question_no ASC, qq.id ASC
    ", "i", [$existing['id']]);
}

$readOnly = $viewOnly || $existing !== null || !$evalOpen || !$hasPeriod;

// ── HANDLE SUBMISSION ────────────────────────────────────────────────
// Mirrors principal_evaluate.php's submission logic: q_<question_id>
// fields, rating/yes_no/text types, average computed from rating answers
// only, written into the same evaluation_tracker + questionnaire_answers
// tables (not the old Dean-only evaluation_tracker/evaluation_answers
// pairing).
$error = '';
$saved = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$readOnly) {
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== ($_SESSION['csrf_token'] ?? '')) {
        $error = 'Session expired. Please refresh and try again.';
    } else {
        $answers = [];
        $scoreSum = 0; $scoreCount = 0;
        $valid = true;

        foreach ($questions as $q) {
            $field = 'q_' . $q['id'];
            $val = trim($_POST[$field] ?? '');

            if ($q['is_required'] && $val === '') { $valid = false; break; }

            if ($q['type'] === 'rating') {
                $score = (int)$val;
                $max = (int)round((float)($q['max_score'] ?? 5));
                if ($val === '' && !$q['is_required']) {
                    $answers[] = ['question_id' => $q['id'], 'answer_text' => null, 'answer_score' => null];
                    continue;
                }
                if ($score < 1 || $score > $max) { $valid = false; break; }
                $answers[] = ['question_id' => $q['id'], 'answer_text' => null, 'answer_score' => $score];
                $scoreSum += $score;
                $scoreCount++;
            } else {
                $answers[] = ['question_id' => $q['id'], 'answer_text' => $val, 'answer_score' => null];
            }
        }

        $comment = trim($_POST['comment'] ?? '');

        if (!$valid) {
            $error = 'Please answer every required question before submitting.';
        } else {
            $avgScore = $scoreCount > 0 ? round($scoreSum / $scoreCount, 2) : null;

            try {
                $mysqli->begin_transaction();

                $stmt = $mysqli->prepare("
                    INSERT INTO evaluation_tracker
                        (evaluator_id, target_user_id, eval_bucket, level, form_type, form_id, period_id, score, remarks, eval_type, status, submitted_at)
                    VALUES (?,?,?,'college',?,?,?,?,?,?, 'submitted', NOW())
                ");
                $bucket = $cfg['bucket'];
                $formTitle = $form['title'];
                $formId = (int)$form['id'];
                $stmt->bind_param(
                    "iissiidss",
                    $deanId, $targetId, $bucket, $formTitle, $formId, $period_id_int, $avgScore, $comment, $evalType
                );
                $stmt->execute();
                $trackerId = $mysqli->insert_id;
                $stmt->close();

                if (!empty($answers)) {
                    $ains = $mysqli->prepare("INSERT INTO questionnaire_answers (tracker_id, question_id, answer_text, answer_score) VALUES (?,?,?,?)");
                    foreach ($answers as $a) {
                        $ains->bind_param("iisd", $trackerId, $a['question_id'], $a['answer_text'], $a['answer_score']);
                        $ains->execute();
                    }
                    $ains->close();
                }

                $mysqli->commit();
                $saved = true;
                $readOnly = true;
                $existing = ['id' => $trackerId, 'score' => $avgScore, 'comment' => $comment, 'submitted_at' => date('Y-m-d H:i:s')];
                $answersByQid = [];
                foreach ($answers as $a) { $answersByQid[$a['question_id']] = $a; }
                $existingAnswers = [];
                foreach ($questions as $q) {
                    $a = $answersByQid[$q['id']] ?? null;
                    $existingAnswers[] = [
                        'question'     => $q['question'],
                        'answer_text'  => $a['answer_text'] ?? null,
                        'score'        => $a['answer_score'] ?? null,
                    ];
                }
            } catch (mysqli_sql_exception $e) {
                $mysqli->rollback();
                $error = 'Could not save this evaluation. Please try again.';
            }
        }
    }
}

$_SESSION['csrf_token'] = bin2hex(random_bytes(32));
$mysqli->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"/>
<meta name="viewport" content="width=device-width, initial-scale=1.0"/>
<title>PBI — Evaluate <?= htmlspecialchars($targetRoleLabel) ?></title>
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

.main{flex:1;padding:36px 44px;max-width:760px;}
.back-link{color:var(--violet-h);font-size:13px;text-decoration:none;display:inline-flex;align-items:center;gap:6px;margin-bottom:18px;}
.back-link:hover{text-decoration:underline;}

.person-card{display:flex;align-items:center;gap:16px;background:rgba(23,42,69,.85);border:1px solid rgba(255,255,255,.08);border-radius:14px;padding:20px 22px;box-shadow:var(--shadow);margin-bottom:24px;}
.person-photo{width:60px;height:60px;border-radius:50%;object-fit:cover;border:2px solid var(--violet);}
.person-name{font-family:'Rajdhani',sans-serif;font-size:20px;font-weight:700;color:#fff;}
.person-meta{font-size:12.5px;color:var(--muted);margin-top:2px;}

.alert{border-radius:8px;padding:11px 14px;font-size:13px;margin-bottom:18px;display:flex;align-items:center;gap:8px;}
.alert-error{background:rgba(240,84,84,.12);border:1px solid rgba(240,84,84,.35);color:#ff8a8a;}
.alert-success{background:rgba(74,222,128,.12);border:1px solid rgba(74,222,128,.35);color:#a9f0c4;}
.alert-info{background:rgba(124,95,217,.1);border:1px solid rgba(124,95,217,.3);color:var(--violet-h);}

.q-block{background:rgba(23,42,69,.85);border:1px solid rgba(255,255,255,.08);border-radius:14px;padding:20px 22px;box-shadow:var(--shadow);margin-bottom:16px;}
.q-cat{font-size:11px;font-weight:700;letter-spacing:1px;text-transform:uppercase;color:var(--violet-h);margin-bottom:6px;}
.q-text{font-size:14.5px;color:var(--light);margin-bottom:14px;}
.rating-row{display:flex;gap:10px;}
.rating-opt{flex:1;text-align:center;}
.rating-opt input{display:none;}
.rating-opt label{display:block;padding:10px 0;border-radius:8px;border:1px solid rgba(255,255,255,.12);background:rgba(10,25,47,.5);color:var(--muted);font-size:13px;font-weight:600;cursor:pointer;transition:all .15s;}
.rating-opt input:checked + label{background:var(--violet);border-color:var(--violet);color:#fff;}
.rating-opt label:hover{border-color:var(--violet-h);}
.rating-readonly{display:flex;align-items:center;gap:6px;}
.rating-readonly .stars{color:var(--violet-h);}

.comment-block{background:rgba(23,42,69,.85);border:1px solid rgba(255,255,255,.08);border-radius:14px;padding:20px 22px;box-shadow:var(--shadow);margin-bottom:22px;}
.comment-block label{display:block;font-size:11px;font-weight:700;letter-spacing:1px;text-transform:uppercase;color:var(--muted);margin-bottom:10px;}
.comment-block textarea{width:100%;min-height:100px;background:rgba(10,25,47,.7);border:1px solid rgba(255,255,255,.1);border-radius:8px;color:var(--light);font-size:13.5px;font-family:'DM Sans',sans-serif;padding:12px;outline:none;resize:vertical;}
.comment-block textarea:focus{border-color:var(--violet);}
.comment-readonly{font-size:13.5px;color:var(--light);font-style:italic;line-height:1.6;}

.btn-submit{padding:13px 26px;background:var(--violet);border:none;border-radius:var(--radius);color:#fff;font-size:14.5px;font-weight:600;font-family:'DM Sans',sans-serif;cursor:pointer;box-shadow:0 4px 16px rgba(124,95,217,.4);display:inline-flex;align-items:center;gap:8px;}
.btn-submit:hover{background:var(--violet-h);}

.summary-score{display:flex;align-items:baseline;gap:8px;margin-bottom:4px;}
.summary-score .num{font-family:'Rajdhani',sans-serif;font-size:34px;font-weight:700;color:#fff;}
.summary-score .of{font-size:13px;color:var(--muted);}

@media(max-width:768px){body{flex-direction:column;}.sidebar{width:100%;min-height:auto;}.rating-row{flex-wrap:wrap;}.rating-opt{min-width:50px;}}
</style>
</head>
<body>

<?php
$active = 'evaluation';
$sidebarScope = HIGHER_ED_LABEL . ' Division';
include __DIR__ . '/includes/dean_sidebar.php';
?>

<main class="main">
    <a href="dean_evaluation.php?tab=<?= urlencode($tab) ?>" class="back-link"><i class="fa-solid fa-arrow-left"></i> Back to Evaluation</a>

    <div class="person-card">
        <img class="person-photo" src="<?= !empty($target['photo']) ? htmlspecialchars(UPLOAD_URL . $target['photo']) : htmlspecialchars(UPLOAD_URL . 'pbi_logo') ?>" alt=""/>
        <div>
            <div class="person-name"><?= htmlspecialchars($target['full_name']) ?></div>
            <div class="person-meta">
                <?= htmlspecialchars($targetRoleLabel) ?><?php if (!empty($target['department'])): ?> · <?= htmlspecialchars($target['department']) ?><?php endif; ?>
            </div>
        </div>
    </div>

    <?php if (!$hasPeriod): ?>
        <div class="alert alert-info"><i class="fa-solid fa-circle-info"></i> No active evaluation period right now.</div>
    <?php elseif (!$evalOpen && !$existing): ?>
        <div class="alert alert-info"><i class="fa-solid fa-lock"></i> Evaluation is currently closed for this period.</div>
    <?php else: ?>

        <?php if ($error): ?>
        <div class="alert alert-error"><i class="fa-solid fa-circle-exclamation"></i> <?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <?php if ($saved): ?>
        <div class="alert alert-success"><i class="fa-solid fa-circle-check"></i> Evaluation submitted. Thank you.</div>
        <?php elseif ($readOnly && $existing): ?>
        <div class="alert alert-info"><i class="fa-solid fa-eye"></i> You already evaluated <?= htmlspecialchars($target['full_name']) ?> this period — showing what was submitted.</div>
        <?php endif; ?>

        <?php if ($readOnly && $existing): ?>
            <div class="q-block">
                <div class="summary-score">
                    <span class="num"><?= $existing['score'] !== null ? htmlspecialchars((string)$existing['score']) : '—' ?></span>
                    <span class="of">overall</span>
                </div>
                <div class="person-meta">Submitted <?= htmlspecialchars(date('M j, Y g:i A', strtotime($existing['submitted_at']))) ?></div>
            </div>
            <?php foreach ($existingAnswers as $a): ?>
            <div class="q-block">
                <div class="q-text"><?= htmlspecialchars($a['question']) ?></div>
                <?php if ($a['score'] !== null): ?>
                <div class="rating-readonly"><span class="stars"><?= str_repeat('★', (int)$a['score']) . str_repeat('☆', max(0, 5 - (int)$a['score'])) ?></span> <?= (int)$a['score'] ?></div>
                <?php elseif ($a['answer_text'] !== null && $a['answer_text'] !== ''): ?>
                <div class="comment-readonly"><?= htmlspecialchars($a['answer_text']) ?></div>
                <?php else: ?>
                <div class="comment-readonly">No answer provided.</div>
                <?php endif; ?>
            </div>
            <?php endforeach; ?>
            <div class="comment-block">
                <label>Comment</label>
                <p class="comment-readonly"><?= $existing['comment'] !== '' ? htmlspecialchars($existing['comment']) : 'No written comment.' ?></p>
            </div>
        <?php elseif (empty($questions)): ?>
            <div class="q-block">
                <p class="comment-readonly">No questions have been added to this form yet.</p>
            </div>
        <?php else: ?>
            <form method="POST" action="dean_evaluate.php?tab=<?= urlencode($tab) ?>&user_id=<?= (int)$target['id'] ?>">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">

                <?php foreach ($questions as $i => $q): $field = 'q_' . $q['id']; ?>
                <div class="q-block">
                    <div class="q-text"><?= $i + 1 ?>. <?= htmlspecialchars($q['question']) ?><?= $q['is_required'] ? ' *' : '' ?></div>

                    <?php if ($q['type'] === 'rating'):
                        $max = (int)round((float)($q['max_score'] ?? 5)); ?>
                    <div class="rating-row">
                        <?php for ($n = 1; $n <= $max; $n++): ?>
                        <div class="rating-opt">
                            <input type="radio" name="<?= htmlspecialchars($field) ?>" id="<?= htmlspecialchars($field) ?>_<?= $n ?>" value="<?= $n ?>" <?= $q['is_required'] ? 'required' : '' ?>>
                            <label for="<?= htmlspecialchars($field) ?>_<?= $n ?>"><?= $n ?></label>
                        </div>
                        <?php endfor; ?>
                    </div>

                    <?php elseif ($q['type'] === 'yes_no'): ?>
                    <div class="rating-row">
                        <div class="rating-opt">
                            <input type="radio" name="<?= htmlspecialchars($field) ?>" id="<?= htmlspecialchars($field) ?>_yes" value="Yes" <?= $q['is_required'] ? 'required' : '' ?>>
                            <label for="<?= htmlspecialchars($field) ?>_yes">Yes</label>
                        </div>
                        <div class="rating-opt">
                            <input type="radio" name="<?= htmlspecialchars($field) ?>" id="<?= htmlspecialchars($field) ?>_no" value="No" <?= $q['is_required'] ? 'required' : '' ?>>
                            <label for="<?= htmlspecialchars($field) ?>_no">No</label>
                        </div>
                    </div>

                    <?php else: /* text or fallback */ ?>
                    <textarea name="<?= htmlspecialchars($field) ?>" class="comment-block-textarea" style="width:100%;min-height:80px;background:rgba(10,25,47,.7);border:1px solid rgba(255,255,255,.1);border-radius:8px;color:var(--light);font-size:13.5px;font-family:'DM Sans',sans-serif;padding:12px;" <?= $q['is_required'] ? 'required' : '' ?>></textarea>
                    <?php endif; ?>
                </div>
                <?php endforeach; ?>

                <div class="comment-block">
                    <label for="comment">Comment (optional)</label>
                    <textarea id="comment" name="comment" placeholder="Any additional feedback..."></textarea>
                </div>

                <button type="submit" class="btn-submit"><i class="fa-solid fa-paper-plane"></i> Submit Evaluation</button>
            </form>
        <?php endif; ?>

    <?php endif; ?>
</main>
</body>
</html>