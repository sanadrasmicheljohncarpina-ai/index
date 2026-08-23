<?php
// admin/ea_evaluate.php
// The per-person EA evaluation form. Reached from ea_evaluation.php via
// ?type=<Principal|Dean|Non-Teaching Staff>&user_id=<id>. Rebuilt to match
// the same evaluate-page pattern used by principal_evaluate.php and
// dean/dean_evaluate.php: a server-rendered form with a person card,
// one card per question with a 1-5 rating, a comment box, and a
// read-only view once already submitted this period — instead of the
// old AJAX-loaded question list.
//
// Eligibility, storage, and question content are unchanged from before:
// the target must currently be the active Principal/Dean or qualifying
// Non-Teaching Staff (re-checked here, independent of ea_evaluation.php),
// questions come from user_questions (eval_type='ea'), and a submission
// writes one evaluation_tracker row (eval_type='ea') plus one
// questionnaire_answers row per question.
session_set_cookie_params([
    'lifetime' => 0, 'path' => '/', 'domain' => '',
    'secure' => false, 'httponly' => true, 'samesite' => 'Lax',
]);
session_start();
require_once 'db.php';

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

if (!isset($_SESSION['user_id']) ||
    !in_array($_SESSION['role'] ?? '', ['admin','superadmin','executive_assistant'], true)) {
    header('Location: admin_login.php');
    exit;
}

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
$validTypes = ['Principal', 'Dean', 'Non-Teaching Staff'];
if (!in_array($type, $validTypes, true) || $targetId <= 0) {
    header('Location: ea_evaluation.php');
    exit;
}

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
    $stmt = $mysqli->prepare("
        SELECT u.id, u.full_name, u.designation, u.photo, u.role, u.secondary_role
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
$is_open = $period_id > 0;

// ── EA per-role default question sets (seeded the first time each
// target is evaluated so the feature is self-contained). ─────────────
$eaQuestionSets = [
    'Principal' => [
        ['Leadership & Direction','Provides clear direction and leadership for the school.'],
        ['Leadership & Direction','Makes decisions that support the institution’s goals and policies.'],
        ['Professionalism','Demonstrates professionalism, fairness, and accountability.'],
        ['Communication','Communicates policies, expectations, and important information clearly.'],
        ['People Management','Promotes a respectful, supportive, and productive workplace.'],
        ['Performance','Responds appropriately to concerns and opportunities for improvement.'],
    ],
    'Dean' => [
        ['Academic Leadership','Provides effective academic leadership for the college.'],
        ['Academic Leadership','Supports quality instruction and continuous academic improvement.'],
        ['Professionalism','Demonstrates fairness, integrity, and accountability in decisions.'],
        ['Communication','Communicates academic plans, policies, and expectations clearly.'],
        ['People Management','Supports faculty and staff and addresses concerns constructively.'],
        ['Performance','Uses resources and decisions responsibly to achieve institutional goals.'],
    ],
    'Non-Teaching Staff' => [
        ['Work Performance','Performs assigned duties accurately and consistently.'],
        ['Work Performance','Completes responsibilities efficiently and on time.'],
        ['Service Quality','Provides courteous, helpful, and responsive service.'],
        ['Professionalism','Demonstrates professionalism, reliability, and accountability.'],
        ['Communication','Communicates clearly and respectfully with clients and coworkers.'],
        ['Teamwork','Works cooperatively with other members of the institution.'],
    ],
];

function ensureEAQuestions(mysqli $db, int $userId, string $targetType, array $questions): void {
    $check = $db->prepare("SELECT COUNT(*) AS c FROM user_questions WHERE user_id=? AND target_type=? AND eval_type='ea'");
    $check->bind_param('is', $userId, $targetType);
    $check->execute();
    $count = (int)$check->get_result()->fetch_assoc()['c'];
    $check->close();
    if ($count > 0) return;

    $ins = $db->prepare("INSERT INTO user_questions (user_id,target_type,eval_type,category,question_text,sort_order) VALUES (?,?,'ea',?,?,?)");
    $sort = 1;
    foreach ($questions as [$category,$question]) {
        $ins->bind_param('isssi', $userId, $targetType, $category, $question, $sort);
        $ins->execute();
        $sort++;
    }
    $ins->close();
}
ensureEAQuestions($mysqli, $targetId, $type, $eaQuestionSets[$type]);

$qStmt = $mysqli->prepare("
    SELECT id, category, question_text, sort_order
    FROM user_questions
    WHERE user_id=? AND target_type=? AND eval_type='ea'
    ORDER BY category, sort_order, id
");
$qStmt->bind_param('is', $targetId, $type);
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
        JOIN user_questions uq ON uq.id = qa.question_id
        WHERE qa.tracker_id=?
        ORDER BY uq.category, uq.sort_order, uq.id
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
    if (!$is_open) {
        $errors[] = 'EA Evaluation is unavailable because no evaluation period is currently open.';
    } elseif (!$questions) {
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
            $mysqli->begin_transaction();
            try {
                $ins = $mysqli->prepare("
                    INSERT INTO evaluation_tracker
                    (evaluator_id, target_user_id, remarks, eval_type, period_id, status, submitted_at)
                    VALUES (?, ?, ?, 'ea', ?, 'submitted', NOW())
                ");
                $ins->bind_param('iisi', $ea_id, $targetId, $comment, $period_id);
                $ins->execute();
                $trackerId = $mysqli->insert_id;
                $ins->close();

                $ans = $mysqli->prepare("
                    INSERT INTO questionnaire_answers (tracker_id, question_id, answer_score, submitted_at)
                    VALUES (?, ?, ?, NOW())
                ");
                foreach ($questions as $q) {
                    $score = (int)$ratings[$q['id']];
                    $ans->bind_param('iii', $trackerId, $q['id'], $score);
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
:root{--bg:#102238;--panel:#18314e;--panel2:#203d5f;--inner:#0F1F3D;--line:#315273;--text:#edf4fb;--muted:#aebfd0;--purple:#8b5cf6;--purple-dark:#6d3fd6;--green:#34d399;--shadow:0 8px 32px rgba(0,0,0,.35)}
*{box-sizing:border-box} body{margin:0;background:var(--bg);color:var(--text);font-family:Inter,Segoe UI,Arial,sans-serif}
.top{height:74px;background:#203d5f;border-bottom:1px solid var(--line);display:flex;align-items:center;padding:0 34px;gap:26px;position:sticky;top:0;z-index:5}
.brand{font-weight:800;letter-spacing:.4px;flex:1}.brand i{color:var(--purple);margin-right:9px}
.account{color:var(--muted);font-size:13px}
.wrap{max-width:820px;margin:auto;padding:34px}
.back-link{color:var(--text);font-size:13.5px;font-weight:700;text-decoration:none;display:inline-flex;align-items:center;gap:8px;margin-bottom:20px;background:var(--panel2);border:1px solid var(--line);padding:10px 16px;border-radius:9px}
.back-link:hover{border-color:#4a6d92;background:var(--panel)}
.person-card{display:flex;align-items:center;gap:16px;background:var(--panel);border:1px solid var(--line);border-radius:14px;padding:20px 22px;box-shadow:var(--shadow);margin-bottom:22px}
.person-photo{width:60px;height:60px;border-radius:50%;object-fit:cover;background:var(--inner);flex-shrink:0;display:flex;align-items:center;justify-content:center;color:var(--muted);font-size:22px}
.person-name{font-size:20px;font-weight:800;color:#fff}
.person-meta{font-size:12.5px;color:var(--muted);margin-top:2px}
.badge{display:inline-block;margin-top:6px;color:#c4b5fd;font-size:11.5px;background:rgba(139,92,246,.14);padding:4px 10px;border-radius:99px;font-weight:700}
.alert{border-radius:10px;padding:13px 16px;font-size:13.5px;margin-bottom:18px;display:flex;align-items:center;gap:8px}
.alert-error{background:rgba(248,113,113,.1);border:1px solid rgba(248,113,113,.25);color:#ffb4b4}
.alert-info{background:rgba(139,92,246,.1);border:1px solid rgba(139,92,246,.3);color:#c4b5fd}
.q-block{background:var(--panel);border:1px solid var(--line);border-radius:14px;padding:20px 22px;box-shadow:var(--shadow);margin-bottom:16px}
.q-cat{font-size:11px;font-weight:700;letter-spacing:1px;text-transform:uppercase;color:#c4b5fd;margin-bottom:6px}
.q-text{font-size:14.5px;color:var(--text);margin-bottom:14px}
.rating-row{display:flex;gap:10px}
.rating-opt{flex:1;text-align:center}
.rating-opt input{display:none}
.rating-opt label{display:block;padding:10px 0;border-radius:8px;border:1px solid var(--line);background:var(--inner);color:var(--muted);font-size:13px;font-weight:700;cursor:pointer}
.rating-opt input:checked + label{background:var(--purple);border-color:var(--purple);color:#fff}
.rating-opt label:hover{border-color:#c4b5fd}
.rating-readonly{display:flex;align-items:center;gap:6px}
.rating-readonly .stars{color:#c4b5fd}
.comment-block{background:var(--panel);border:1px solid var(--line);border-radius:14px;padding:20px 22px;box-shadow:var(--shadow);margin-bottom:22px}
.comment-block label{display:block;font-size:11px;font-weight:700;letter-spacing:1px;text-transform:uppercase;color:var(--muted);margin-bottom:10px}
.comment-block textarea{width:100%;min-height:100px;background:var(--inner);border:1px solid var(--line);border-radius:8px;color:var(--text);font-size:13.5px;font-family:inherit;padding:12px;outline:none;resize:vertical}
.comment-block textarea:focus{border-color:var(--purple)}
.comment-readonly{font-size:13.5px;color:var(--text);font-style:italic;line-height:1.6}
.btn-submit{padding:13px 26px;background:var(--purple);border:none;border-radius:10px;color:#fff;font-size:14.5px;font-weight:700;cursor:pointer;display:inline-flex;align-items:center;gap:8px}
.btn-submit:hover{background:var(--purple-dark)}
.summary-score{display:flex;align-items:baseline;gap:8px;margin-bottom:4px}
.summary-score .num{font-size:32px;font-weight:800;color:#fff}
.summary-score .of{font-size:13px;color:var(--muted)}
@media(max-width:900px){.top{padding:0 18px}.wrap{padding:20px}.rating-row{flex-wrap:wrap}.rating-opt{min-width:50px}}
</style>
</head>
<body>
<header class="top">
  <div class="brand"><i class="fa-solid fa-user-check"></i>EA Evaluation</div>
  <div class="account"><?= e($_SESSION['full_name'] ?? 'Executive Assistant') ?></div>
</header>
<main class="wrap">
<a class="back-link" href="ea_evaluation.php?type=<?= urlencode($type) ?>"><i class="fa-solid fa-arrow-left"></i> Back to EA Evaluation</a>

<div class="person-card">
  <?php if ($tPhoto): ?><img class="person-photo" src="<?= e($tPhoto) ?>" alt=""><?php else: ?><div class="person-photo"><i class="fa-solid fa-user"></i></div><?php endif; ?>
  <div>
    <div class="person-name"><?= e($target['full_name']) ?></div>
    <div class="person-meta"><?= e($target['designation'] ?: $type) ?></div>
    <span class="badge"><?= e($type) ?></span>
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
    <?php foreach ($existingAnswers as $a): ?>
    <div class="q-block">
        <div class="q-cat"><?= e($a['category']) ?></div>
        <div class="q-text"><?= e($a['question_text']) ?></div>
        <div class="rating-readonly"><span class="stars"><?= str_repeat('★', (int)$a['answer_score']) . str_repeat('☆', 5 - (int)$a['answer_score']) ?></span> <?= (int)$a['answer_score'] ?>/5</div>
    </div>
    <?php endforeach; ?>
    <div class="comment-block">
        <label>Comment</label>
        <p class="comment-readonly"><?= $existingTracker['remarks'] !== '' ? e($existingTracker['remarks']) : 'No written comment.' ?></p>
    </div>
<?php elseif (!$questions): ?>
    <div class="alert alert-info"><i class="fa-solid fa-circle-info"></i> No EA questions have been assigned yet. Configure them in <a style="color:#c4b5fd" href="questionnaire.php?eval_type=ea">Manage Questions</a>.</div>
<?php else: ?>
    <form method="post">
        <?php foreach ($questionGroups as $cat => $qs): foreach ($qs as $i => $q): ?>
        <div class="q-block">
            <div class="q-cat"><?= e($cat) ?></div>
            <div class="q-text"><?= e($q['question_text']) ?></div>
            <div class="rating-row">
                <?php for ($n = 5; $n >= 1; $n--): ?>
                <div class="rating-opt">
                    <input type="radio" name="rating[<?= (int)$q['id'] ?>]" id="q<?= (int)$q['id'] ?>_<?= $n ?>" value="<?= $n ?>" required>
                    <label for="q<?= (int)$q['id'] ?>_<?= $n ?>"><?= $n ?></label>
                </div>
                <?php endfor; ?>
            </div>
        </div>
        <?php endforeach; endforeach; ?>

        <div class="comment-block">
            <label for="comment">Comment (optional)</label>
            <textarea id="comment" name="comment" placeholder="Comments, suggestions, or areas for improvement..."></textarea>
        </div>

        <button type="submit" class="btn-submit" <?= !$is_open ? 'disabled style="opacity:.5;cursor:not-allowed;"' : '' ?>>
            <i class="fa-solid fa-paper-plane"></i> Submit EA Evaluation
        </button>
    </form>
<?php endif; ?>
</main>
</body>
</html>
