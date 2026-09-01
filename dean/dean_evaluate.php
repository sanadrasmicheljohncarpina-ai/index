<?php
// dean/dean_evaluate.php
// NEW FILE — this page did not exist before. The old "Evaluate"/"View"
// links on dean_evaluation.php pointed at ea_questionnaire_route($tab,
// $id), a function in shared/ea_personnel_service.php that built a
// broken URL (localhost//index/dean/dean_evaluate.php?form=...&user_id=)
// pointing at a page that had never been built. dean_evaluation.php now
// links straight here instead: dean_evaluate.php?tab=&user_id=[&view=1].
//
// ── QUESTIONNAIRE CONTENT ────────────────────────────────────────────
// The Faculty tab (Teacher, Teaching/Non-Teaching Staff, and the
// Executive Assistant — all merged under one "Faculty" tab per
// dean_evaluation.php) now fetches its questions directly from the
// questionnaire feature's Student Evaluation tab: evaluation_questions
// WHERE eval_type='student' AND target_type='Teacher'. That is the exact
// pool admin/questionnaire.php's Student Evaluation -> "Faculty" card
// manages, so the Dean rates people on the same rubric students use —
// one shared source of truth instead of a separate per-evaluator
// School Head Evaluation assignment. The evaluation stays labeled and
// bucketed "Faculty" regardless of which of the three groups the target
// belongs to (see $bucket below).
//
// This intentionally replaces the previous mechanism, where questions
// came from evaluator-specific School Head Evaluation assignments
// (school_head_evaluation_assignments, configured per Dean+target by the
// EA via questionnaire.php's "School Head Evaluation Assignments"
// screen). That assignment table/screen still exists and still serves
// the Principal's evaluation flow if it uses the same service — it is
// simply no longer read here.
//
// ── STORAGE ──────────────────────────────────────────────────────────
// Submitting writes ONE row to evaluation_tracker (eval_type='school_head',
// eval_bucket = 'Faculty'|'Staff'|'Executive Assistant', level='college',
// status='submitted', score = average of the question ratings,
// evaluator_id = the Dean, target_user_id = the person being evaluated,
// period_id = current period, comment, submitted_at) plus one row per
// question in questionnaire_answers (tracker_id, question_id, answer_score).
//
// eval_type is 'school_head' (not 'dean') and answers land in
// questionnaire_answers (not evaluation_answers) so that admin/
// admin_analytics.php's School Head Evaluation tab — which reads
// eval_type='school_head' rows joined to questionnaire_answers — actually
// picks these submissions up. A prior version of this file used
// eval_type='dean' and a separate evaluation_answers table that nothing
// downstream ever read, so every Dean submission silently vanished from
// Reports & Analytics.

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
require_once dirname(__DIR__) . '/shared/ea_personnel_service.php'; // for COLLEGE_LEVELS

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

// ── VALIDATE TAB + TARGET ──────────────────────────────────────────
// 'executive_assistant' is still accepted here (and still linked from
// dean_evaluation.php's route_tab for any stale/bookmarked links) purely
// for backward compatibility — it now resolves to the exact same
// evaluation as 'faculty'. There is only one evaluation tab going
// forward: Faculty, which covers Teacher, Teaching/Non-Teaching Staff,
// and the Executive Assistant.
$validTabs = ['faculty', 'executive_assistant'];
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

// Always "Faculty" — this tab is not per-target-group anymore.
$targetRoleLabel = 'Faculty';
$formType = 'faculty_dean';
$bucket = 'Faculty';
$noQuestionsConfigured = false;
$questions = [];

// ── GLOBAL SYSTEM SETTINGS ──────────────────────────────────────────
$settings = get_system_settings($mysqli);
$structureActive = ($settings['academic_structure'] === 'college');
$period_id_int   = $settings['period_id'] ?? 0;
$hasPeriod       = $period_id_int > 0;
$evalOpen        = $settings['is_open_for_submission'];
const HIGHER_ED_LABEL = 'Higher Education';

// ── DEAN PROFILE (for sidebar) ─────────────────────────────
$stmt = $mysqli->prepare("SELECT full_name, designation, photo FROM users WHERE id = ? LIMIT 1");
$stmt->bind_param("i", $deanId);
$stmt->execute();
$me = $stmt->get_result()->fetch_assoc();
$stmt->close();
$photo_src = !empty($me['photo']) ? UPLOAD_URL . $me['photo'] : UPLOAD_URL . 'pbi_logo';

// ── TARGET (ROSTER MEMBERSHIP) ──────────────────────────────────────
// Mirrors dean_evaluation.php's merged Faculty roster exactly: every
// active, approved Teacher/Staff account, plus the single approved
// Executive Assistant (superadmin) account.
if (!$structureActive) {
    http_response_code(403);
    exit('Higher Education is not the active academic structure right now.');
}
$tstmt = $mysqli->prepare("
    SELECT id, full_name, designation, photo, department, role,
           EXISTS(SELECT 1 FROM teaching_assignments ta WHERE ta.user_id = users.id)
           OR EXISTS(SELECT 1 FROM user_year_levels yl WHERE yl.user_id = users.id) AS is_teaching_staff
    FROM users
    WHERE id = ?
      AND is_active = 1
      AND account_status = 'approved'
      AND (role IN ('teacher','staff') OR role = 'superadmin')
    LIMIT 1
");
$tstmt->bind_param('i', $targetId);
$tstmt->execute();
$target = $tstmt->get_result()->fetch_assoc();
$tstmt->close();
if (!$target) {
    http_response_code(403);
    exit('This person is not a valid Faculty evaluation target.');
}
$targetRoleLabel = ($target['role'] === 'superadmin')
    ? 'Executive Assistant'
    : (($target['role'] === 'staff')
        ? ((int)$target['is_teaching_staff'] === 1 ? 'Teaching Staff' : 'Staff')
        : 'Faculty');

// ── QUESTIONS — fetched directly from the questionnaire's Student
// Evaluation -> Faculty (Teacher) pool. This is the exact evaluation_
// questions set students are asked, so the Dean rates people on the
// same rubric. Applies uniformly to Faculty, Teaching/Non-Teaching
// Staff, and the Executive Assistant — there is no separate per-group
// question pool for this tab anymore.
$questions = [];
$qstmt = $mysqli->prepare("SELECT id, category, question_text FROM evaluation_questions WHERE target_type='Teacher' AND eval_type='student' ORDER BY category, id");
$qstmt->execute();
$qrows = $qstmt->get_result()->fetch_all(MYSQLI_ASSOC);
$qstmt->close();
foreach ($qrows as $q) {
    $questions[] = [
        'id' => (int)$q['id'],
        'key' => 'q_' . (int)$q['id'],
        'category' => $q['category'] ?: 'General',
        'question' => $q['question_text'],
    ];
}
$noQuestionsConfigured = empty($questions);

// ── EXISTING SUBMISSION THIS PERIOD? ────────────────────────────────
$existing = null;
if ($hasPeriod) {
    $rows = safe_rows($mysqli, "
        SELECT id, score, remarks AS comment, submitted_at FROM evaluation_tracker
        WHERE eval_type='school_head' AND eval_bucket=? AND level='college'
          AND status IN ('submitted','approved')
          AND evaluator_id=? AND target_user_id=? AND period_id=?
        LIMIT 1
    ", "siii", [$bucket, $deanId, $targetId, $period_id_int]);
    $existing = $rows[0] ?? null;
}
$existingAnswers = [];
if ($existing) {
    $existingAnswers = safe_rows($mysqli, "
        SELECT eq.category AS category, eq.question_text AS question, qa.answer_score AS score
        FROM questionnaire_answers qa
        JOIN evaluation_questions eq ON eq.id = qa.question_id
        WHERE qa.tracker_id=?
        ORDER BY qa.id
    ", "i", [$existing['id']]);
}

$readOnly = $viewOnly || $existing !== null || !$evalOpen || !$hasPeriod || $noQuestionsConfigured;

// ── HANDLE SUBMISSION ────────────────────────────────────────────────
$error = '';
$saved = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$readOnly) {
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== ($_SESSION['csrf_token'] ?? '')) {
        $error = 'Session expired. Please refresh and try again.';
    } else {
        // Re-fetch the live question set on submission — straight from the
        // questionnaire's Student Evaluation -> Faculty pool — so a forged
        // question id/count submitted by the client can't bypass what's
        // actually configured there.
        $liveQstmt = $mysqli->prepare("SELECT id, category, question_text FROM evaluation_questions WHERE target_type='Teacher' AND eval_type='student' ORDER BY category, id");
        $liveQstmt->execute();
        $liveRows = $liveQstmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $liveQstmt->close();
        if (empty($liveRows)) {
            $error = 'No Student Evaluation questions are currently configured for Faculty.';
        } else {
            $questions = [];
            foreach ($liveRows as $q) {
                $questions[] = [
                    'id' => (int)$q['id'],
                    'key' => 'q_' . (int)$q['id'],
                    'category' => $q['category'] ?: 'General',
                    'question' => $q['question_text'],
                ];
            }
        }
        $ratings = [];
        $valid = true;
        if ($error === '') foreach ($questions as $q) {
            $val = (int)($_POST[$q['key']] ?? 0);
            if ($val < 1 || $val > 5) { $valid = false; break; }
            $ratings[$q['key']] = $val;
        }
        $comment = trim($_POST['comment'] ?? '');

        if (!$valid) {
            $error = 'Please rate every question from 1 to 5 before submitting.';
        } else {
            $avgScore = round(array_sum($ratings) / count($ratings), 2);

            try {
                $mysqli->begin_transaction();

                $stmt = $mysqli->prepare("
                    INSERT INTO evaluation_tracker
                        (eval_type, eval_bucket, level, status, score, remarks, evaluator_id, target_user_id, period_id, form_type, submitted_at)
                    VALUES ('school_head', ?, 'college', 'submitted', ?, ?, ?, ?, ?, ?, NOW())
                ");
                $bucket = $bucket;
                $formType = $formType;
                $stmt->bind_param("sdsiiis", $bucket, $avgScore, $comment, $deanId, $targetId, $period_id_int, $formType);
                $stmt->execute();
                $trackerId = $mysqli->insert_id;
                $stmt->close();

                $stmt = $mysqli->prepare("
                    INSERT INTO questionnaire_answers (tracker_id, question_id, answer_score, submitted_at) VALUES (?, ?, ?, NOW())
                ");
                foreach ($questions as $q) {
                    $qid = $q['id']; $score = $ratings[$q['key']];
                    $stmt->bind_param("iii", $trackerId, $qid, $score);
                    $stmt->execute();
                }
                $stmt->close();

                $mysqli->commit();
                $saved = true;
                $readOnly = true;
                $existing = ['id' => $trackerId, 'score' => $avgScore, 'comment' => $comment, 'submitted_at' => date('Y-m-d H:i:s')];
                $existingAnswers = array_map(fn($q) => ['category' => $q['category'], 'question' => $q['question'], 'score' => $ratings[$q['key']]], $questions);
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
    <?php elseif ($noQuestionsConfigured && !$existing): ?>
        <div class="alert alert-info"><i class="fa-solid fa-circle-info"></i> No Student Evaluation questions have been configured for Faculty yet. Contact your administrator.</div>
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
                    <span class="num"><?= htmlspecialchars((string)$existing['score']) ?></span>
                    <span class="of">/ 5.00 overall</span>
                </div>
                <div class="person-meta">Submitted <?= htmlspecialchars(date('M j, Y g:i A', strtotime($existing['submitted_at']))) ?></div>
            </div>
            <?php foreach ($existingAnswers as $a): ?>
            <div class="q-block">
                <div class="q-cat"><?= htmlspecialchars($a['category']) ?></div>
                <div class="q-text"><?= htmlspecialchars($a['question']) ?></div>
                <div class="rating-readonly"><span class="stars"><?= str_repeat('★', (int)$a['score']) . str_repeat('☆', 5 - (int)$a['score']) ?></span> <?= (int)$a['score'] ?>/5</div>
            </div>
            <?php endforeach; ?>
            <div class="comment-block">
                <label>Comment</label>
                <p class="comment-readonly"><?= $existing['comment'] !== '' ? htmlspecialchars($existing['comment']) : 'No written comment.' ?></p>
            </div>
        <?php else: ?>
            <form method="POST" action="dean_evaluate.php?tab=<?= urlencode($tab) ?>&user_id=<?= (int)$target['id'] ?>">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">

                <?php foreach ($questions as $q): ?>
                <div class="q-block">
                    <div class="q-cat"><?= htmlspecialchars($q['category']) ?></div>
                    <div class="q-text"><?= htmlspecialchars($q['question']) ?></div>
                    <div class="rating-row">
                        <?php for ($i = 1; $i <= 5; $i++): ?>
                        <div class="rating-opt">
                            <input type="radio" name="<?= htmlspecialchars($q['key']) ?>" id="<?= htmlspecialchars($q['key']) ?>_<?= $i ?>" value="<?= $i ?>" required>
                            <label for="<?= htmlspecialchars($q['key']) ?>_<?= $i ?>"><?= $i ?></label>
                        </div>
                        <?php endfor; ?>
                    </div>
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