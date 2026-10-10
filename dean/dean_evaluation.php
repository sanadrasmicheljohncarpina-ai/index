<?php
// dean/dean_evaluation.php
// Dean "Evaluate Others" — restructured to follow the student evaluation flow:
//   1. Category cards (Faculty / Staff / Executive Assistant) with "x/y done" progress
//   2. Person cards for the selected category (Evaluate / View / Closed)
//   3. Rating modal loaded over AJAX (questions grouped by category, 5..1 scale,
//      live "n / N answered" counter, comment box, Submit Evaluation)
//
// Everything lives in this one file:
//   GET  ?get_questions=1&target_id=ID   -> JSON question set for the modal
//   GET  ?get_submission=1&target_id=ID  -> JSON of the Dean's own submitted answers (View)
//   POST submit_evaluation               -> validated, transactional save, then redirect
//
// Source of truth for questions (unchanged):
//   Faculty -> qn_get_faculty_questions()          (shared Dean/Faculty bank, evaluation_questions)
//   Staff   -> qn_get_person_questions(id,'Staff') (per-person, user_questions)
//   EA      -> qn_get_person_questions(id,'EA')    (per-person, user_questions)
//
// dean_evaluate.php is now only a redirect into this page so old links keep working.

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
require_once dirname(__DIR__) . '/shared/QuestionnaireService.php';
require_once 'school_head_structure_gate.php';
qn_migrate_legacy_once($mysqli);

const HIGHER_ED_LABEL = 'Higher Education';

class DeanEvalException extends RuntimeException {}

$isAjax = isset($_GET['get_questions']) || isset($_GET['get_submission']);

function dean_json(array $payload, int $code = 200): void {
    http_response_code($code);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
    exit;
}

if (empty($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'dean') {
    if ($isAjax) dean_json(['success' => false, 'error' => 'Your session has expired. Please log in again.'], 401);
    header('Location: dean_login.php');
    exit;
}
$deanId = (int)$_SESSION['user_id'];

// One CSRF token per session (the old page rotated it on every load, which broke
// a second open tab). Same session key security.php's csrf_token() uses.
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$stmt = $mysqli->prepare("SELECT full_name, username, email, designation, photo, department FROM users WHERE id=? LIMIT 1");
$stmt->bind_param('i', $deanId);
$stmt->execute();
$me = $stmt->get_result()->fetch_assoc() ?: [];
$stmt->close();

$settings = get_school_head_settings($mysqli, 'dean');
// Academic Structure / Academic Term gate: Dean owns College, Principal
// owns School Year. Narrow-only — existing scheduling, Force Open and
// Force Closed still decide open/closed while College is active.
$settings = sh_gate_apply($settings, 'dean');
$structureActive = !empty($settings['school_head_applicable']);
$period_id_int   = (int)($settings['period_id'] ?? 0);
$evalOpen        = !empty($settings['school_head_is_open']);

$validTabs = ['faculty', 'staff', 'executive_assistant'];
$tab = $_GET['tab'] ?? 'faculty';
if (!in_array($tab, $validTabs, true)) $tab = 'faculty';

$catMeta = [
    'faculty'             => ['label' => 'Faculty',             'icon' => 'fa-users',     'cls' => 'faculty', 'noun' => 'faculty'],
    'staff'               => ['label' => 'Staff',               'icon' => 'fa-briefcase', 'cls' => 'staff',   'noun' => 'staff'],
    'executive_assistant' => ['label' => 'Executive Assistant', 'icon' => 'fa-user-tie',  'cls' => 'ea',      'noun' => 'executive assistant'],
];

/* ───────────────────────── helpers ───────────────────────── */

function dean_safe_rows(mysqli $mysqli, string $sql, string $types = '', array $params = []): array {
    try {
        $stmt = @$mysqli->prepare($sql);
        if (!$stmt) return [];
        if ($types !== '') $stmt->bind_param($types, ...$params);
        if (!@$stmt->execute()) { $stmt->close(); return []; }
        $res = $stmt->get_result();
        $rows = $res ? $res->fetch_all(MYSQLI_ASSOC) : [];
        $stmt->close();
        return $rows;
    } catch (Throwable $e) {
        return [];
    }
}

function dean_roster_levels(mysqli $mysqli, int $userId): array {
    // Level VALUES come from user_year_levels ONLY. teaching_assignments keeps a
    // stale row on every reassignment, so it is only trusted as an existence signal
    // (dean_has_any_teaching_assignment()).
    $rows = dean_safe_rows($mysqli, "SELECT year_level FROM user_year_levels WHERE user_id=?", 'i', [$userId]);
    $levels = [];
    foreach ($rows as $r) {
        $v = trim((string)($r['year_level'] ?? ''));
        if ($v !== '') $levels[] = $v;
    }
    return array_values(array_unique($levels));
}

function dean_is_college_level(string $level): bool {
    $level = trim($level);
    return stripos($level, 'college') !== false
        || (bool)preg_match('/^(1st|2nd|3rd|4th)\s*Year\b/i', $level);
}

function dean_has_any_teaching_assignment(mysqli $mysqli, int $userId): bool {
    $rows = dean_safe_rows(
        $mysqli,
        "SELECT 1 FROM teaching_assignments WHERE user_id=? UNION ALL SELECT 1 FROM user_year_levels WHERE user_id=? LIMIT 1",
        'ii',
        [$userId, $userId]
    );
    return !empty($rows);
}

/**
 * The ONE place that decides which bucket a teacher/staff user belongs to.
 * Used by the roster AND by the AJAX/submit re-checks, so they can never disagree
 * (the old dean_evaluate.php used a slightly different rule than the roster).
 *   - Non-teaching Staff                         -> Staff
 *   - Teacher / Teaching Staff with a College level -> Faculty
 *   - anyone else                                -> not evaluable by the Dean
 */
function dean_resolve_bucket(mysqli $mysqli, array $u): ?array {
    $uid = (int)($u['id'] ?? 0);
    if ($uid <= 0) return null;
    $hasTeaching = dean_has_any_teaching_assignment($mysqli, $uid);

    if (($u['role'] ?? '') === 'staff' && !$hasTeaching) {
        return ['bucket' => 'Staff', 'role_label' => 'Staff', 'route_tab' => 'staff'];
    }
    if ($hasTeaching && count(array_filter(dean_roster_levels($mysqli, $uid), 'dean_is_college_level')) > 0) {
        return [
            'bucket'     => 'Faculty',
            'role_label' => (($u['role'] ?? '') === 'staff') ? 'Teaching Staff' : 'Faculty',
            'route_tab'  => 'faculty',
        ];
    }
    return null;
}

/** The single active Executive Assistant (same query the roster has always used). */
function dean_current_ea(mysqli $mysqli): ?array {
    $rows = dean_safe_rows($mysqli, "SELECT id, full_name, designation, photo, role, department FROM users WHERE role='superadmin' AND is_active=1 AND account_status='approved' ORDER BY updated_at DESC, id DESC LIMIT 1");
    return $rows[0] ?? null;
}

/** Server-side eligibility: returns the target row + bucket info, or null. */
function dean_resolve_target(mysqli $mysqli, int $targetId): ?array {
    $rows = dean_safe_rows(
        $mysqli,
        "SELECT id, full_name, designation, photo, department, role, secondary_role FROM users WHERE id=? AND is_active=1 AND account_status='approved' LIMIT 1",
        'i',
        [$targetId]
    );
    $u = $rows[0] ?? null;
    if (!$u) return null;

    $role = strtolower((string)$u['role']);
    if ($role === 'superadmin') {
        $ea = dean_current_ea($mysqli);
        if (!$ea || (int)$ea['id'] !== $targetId) return null;
        return ['user' => $u, 'bucket' => 'EA', 'role_label' => 'Executive Assistant', 'route_tab' => 'executive_assistant'];
    }
    if ($role !== 'teacher' && $role !== 'staff') return null;

    $info = dean_resolve_bucket($mysqli, $u);
    if ($info === null) return null;
    return ['user' => $u] + $info;
}

function dean_load_questions(mysqli $mysqli, int $targetId, string $bucket): array {
    static $facultyCache = null;
    try {
        if ($bucket === 'Faculty') {
            if ($facultyCache === null) $facultyCache = qn_get_faculty_questions($mysqli) ?: [];
            $rows = $facultyCache;
            $source = 'evaluation';
        } elseif ($bucket === 'Staff') {
            $rows = qn_get_person_questions($mysqli, $targetId, 'Staff') ?: [];
            $source = 'user';
        } else {
            $rows = qn_get_person_questions($mysqli, $targetId, 'EA') ?: [];
            $source = 'user';
        }
    } catch (Throwable $e) {
        return [];
    }

    $out = [];
    foreach ($rows as $q) {
        $cat = trim((string)($q['category'] ?? ''));
        $out[] = [
            'key'      => $source . ':' . (int)$q['id'],
            'id'       => (int)$q['id'],
            'source'   => $source,
            'category' => $cat !== '' ? $cat : 'General',
            'question' => (string)($q['question_text'] ?? ''),
        ];
    }
    return $out;
}

function dean_find_submission(mysqli $mysqli, int $deanId, int $targetId, int $periodId): ?array {
    if ($periodId <= 0) return null;
    $rows = dean_safe_rows($mysqli, "
        SELECT id, score, remarks AS comment, submitted_at
        FROM evaluation_tracker
        WHERE eval_type='school_head'
          AND evaluator_id=? AND target_user_id=? AND period_id=?
          AND status IN ('submitted','approved')
        ORDER BY submitted_at DESC, id DESC
        LIMIT 1
    ", 'iii', [$deanId, $targetId, $periodId]);
    return $rows[0] ?? null;
}

function dean_load_existing_answers(mysqli $mysqli, int $trackerId): array {
    return dean_safe_rows($mysqli, "
        SELECT qa.question_source, qa.question_id, qa.user_question_id,
               qa.answer_score AS score,
               COALESCE(uq.question_text, eq.question_text) AS question,
               COALESCE(uq.category, eq.category, 'General') AS category,
               COALESCE(eq.id, 0) AS sort_key
        FROM questionnaire_answers qa
        LEFT JOIN user_questions uq
          ON qa.question_source='user' AND uq.id=qa.user_question_id
        LEFT JOIN evaluation_questions eq
          ON qa.question_source='evaluation' AND eq.id=qa.question_id
        WHERE qa.tracker_id=?
        ORDER BY category, sort_key, qa.id
    ", 'i', [$trackerId]);
}

/* ───────────────────────── AJAX: question set / submitted answers ───────────────────────── */

if ($isAjax) {
    if (!$structureActive) {
        dean_json(['success' => false, 'error' => HIGHER_ED_LABEL . ' is not the active academic structure right now.'], 403);
    }
    $targetId = (int)($_GET['target_id'] ?? 0);
    $t = $targetId > 0 ? dean_resolve_target($mysqli, $targetId) : null;
    if (!$t) {
        dean_json(['success' => false, 'error' => 'This person is not available for evaluation.'], 403);
    }
    $existing = dean_find_submission($mysqli, $deanId, $targetId, $period_id_int);

    if (isset($_GET['get_submission'])) {
        if (!$existing) {
            dean_json(['success' => false, 'error' => 'You have not evaluated this person in the current period.'], 404);
        }
        $answers = [];
        foreach (dean_load_existing_answers($mysqli, (int)$existing['id']) as $a) {
            $answers[] = [
                'category' => (string)$a['category'],
                'question' => (string)$a['question'],
                'score'    => (float)$a['score'],
            ];
        }
        dean_json([
            'success'      => true,
            'score'        => $existing['score'] !== null ? round((float)$existing['score'], 2) : null,
            'submitted_at' => $existing['submitted_at'] ? date('M j, Y g:i A', strtotime($existing['submitted_at'])) : '',
            'comment'      => (string)($existing['comment'] ?? ''),
            'answers'      => $answers,
        ]);
    }

    // get_questions
    if ($existing) {
        dean_json(['success' => false, 'error' => 'You have already evaluated this person this period.'], 409);
    }
    if ($period_id_int <= 0) {
        dean_json(['success' => false, 'error' => 'No evaluation period is currently open. Please check back later.'], 403);
    }
    if (!$evalOpen) {
        dean_json(['success' => false, 'error' => 'Evaluation is currently closed for this period.'], 403);
    }
    $questions = dean_load_questions($mysqli, $targetId, $t['bucket']);
    if (empty($questions)) {
        dean_json(['success' => false, 'error' => 'No questions have been set up for this person yet. Please contact your administrator.'], 404);
    }
    dean_json(['success' => true, 'questions' => $questions]);
}

/* ───────────────────────── POST: submit evaluation ───────────────────────── */

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_evaluation'])) {
    $postTab = $_POST['tab'] ?? 'faculty';
    if (!in_array($postTab, $validTabs, true)) $postTab = 'faculty';
    $redirectTab = $postTab;
    $submitError = '';
    $submitOk = '';

    if (!hash_equals((string)($_SESSION['csrf_token'] ?? ''), (string)($_POST['csrf_token'] ?? ''))) {
        $submitError = 'Session expired. Please refresh and try again.';
    } elseif (!$structureActive) {
        $submitError = HIGHER_ED_LABEL . ' is not the active academic structure right now.';
    } elseif ($period_id_int <= 0) {
        $submitError = 'No evaluation period is currently open. Please check back later.';
    } elseif (!$evalOpen) {
        $submitError = 'Evaluation is currently closed for this period.';
    } else {
        $targetId = (int)($_POST['target_user_id'] ?? 0);
        // SERVER-SIDE ELIGIBILITY RE-CHECK — the modal only offers eligible people,
        // but nothing else stops a hand-crafted POST with any target_user_id.
        $t = $targetId > 0 ? dean_resolve_target($mysqli, $targetId) : null;
        if (!$t) {
            $submitError = 'This person is not available for evaluation.';
        } else {
            $redirectTab = $t['route_tab'];
            $questions = dean_load_questions($mysqli, $targetId, $t['bucket']);
            $expectedKeys = array_column($questions, 'key');
            $ratings = $_POST['rating'] ?? [];
            if (!is_array($ratings)) $ratings = [];
            $submittedKeys = array_map('strval', array_keys($ratings));
            $missing    = array_values(array_diff($expectedKeys, $submittedKeys));
            $unexpected = array_values(array_diff($submittedKeys, $expectedKeys));

            if (dean_find_submission($mysqli, $deanId, $targetId, $period_id_int)) {
                $submitError = 'You have already evaluated this person this period.';
            } elseif (empty($expectedKeys)) {
                $submitError = 'No evaluation questions are configured for this person yet.';
            } elseif ($unexpected) {
                $submitError = 'Invalid evaluation question data. Please close and try again.';
            } elseif ($missing) {
                $n = count($missing);
                $submitError = 'Please answer all questions before submitting. You have ' . $n . ' unanswered question' . ($n === 1 ? '' : 's') . ' remaining.';
            } else {
                $answers = [];
                $scoreSum = 0;
                foreach ($questions as $q) {
                    $raw = $ratings[$q['key']] ?? '';
                    if (!is_string($raw) || !preg_match('/^[1-5]$/', $raw)) {
                        $submitError = 'Every rating must be a whole number from 1 to 5.';
                        break;
                    }
                    $score = (int)$raw;
                    $answers[] = ['id' => $q['id'], 'source' => $q['source'], 'score' => $score];
                    $scoreSum += $score;
                }

                if ($submitError === '') {
                    $comment  = trim((string)($_POST['comment'] ?? ''));
                    $avgScore = round($scoreSum / count($answers), 2);
                    $level    = 'college';
                    $bucket   = $t['bucket'];
                    $formType = 'school_head_dean_' . strtolower($bucket);

                    try {
                        $mysqli->begin_transaction();

                        // Re-check inside the transaction and lock, so two simultaneous
                        // submits cannot both pass the duplicate check above.
                        $chk = $mysqli->prepare("SELECT id FROM evaluation_tracker WHERE eval_type='school_head' AND evaluator_id=? AND target_user_id=? AND period_id=? AND status IN ('submitted','approved') LIMIT 1 FOR UPDATE");
                        $chk->bind_param('iii', $deanId, $targetId, $period_id_int);
                        $chk->execute();
                        $dup = $chk->get_result()->fetch_row();
                        $chk->close();
                        if ($dup) throw new DeanEvalException('You have already evaluated this person this period.');

                        $ins = $mysqli->prepare("
                            INSERT INTO evaluation_tracker
                                (eval_type, eval_bucket, level, status, score, remarks,
                                 evaluator_id, target_user_id, period_id, form_type, submitted_at)
                            VALUES ('school_head', ?, ?, 'submitted', ?, ?, ?, ?, ?, ?, NOW())
                        ");
                        $ins->bind_param('ssdsiiis', $bucket, $level, $avgScore, $comment, $deanId, $targetId, $period_id_int, $formType);
                        if (!$ins->execute()) throw new DeanEvalException('Failed to save the evaluation.');
                        $trackerId = (int)$mysqli->insert_id;
                        $ins->close();

                        $ains = $mysqli->prepare("
                            INSERT INTO questionnaire_answers
                                (tracker_id, question_id, user_question_id, question_source,
                                 answer_text, answer_score, submitted_at)
                            VALUES (?, ?, ?, ?, ?, ?, NOW())
                        ");
                        foreach ($answers as $a) {
                            $questionId     = $a['source'] === 'evaluation' ? $a['id'] : null;
                            $userQuestionId = $a['source'] === 'user' ? $a['id'] : null;
                            $source         = $a['source'];
                            $answerText     = null;
                            $score          = (float)$a['score'];
                            $ains->bind_param('iiissd', $trackerId, $questionId, $userQuestionId, $source, $answerText, $score);
                            if (!$ains->execute()) throw new DeanEvalException('Failed to save a questionnaire answer.');
                        }
                        $ains->close();

                        $mysqli->commit();
                        $submitOk = 'Evaluation for ' . $t['user']['full_name'] . ' submitted successfully. Thank you!';
                    } catch (DeanEvalException $e) {
                        $mysqli->rollback();
                        $submitError = $e->getMessage();
                    } catch (Throwable $e) {
                        $mysqli->rollback();
                        $submitError = 'Could not save this evaluation. Nothing was recorded — please try again.';
                    }
                }
            }
        }
    }

    $_SESSION['dean_eval_flash'] = $submitError !== ''
        ? ['type' => 'error',   'msg' => $submitError]
        : ['type' => 'success', 'msg' => $submitOk];
    $mysqli->close();
    header('Location: dean_evaluation.php?tab=' . urlencode($redirectTab));
    exit;
}

$flash = $_SESSION['dean_eval_flash'] ?? null;
unset($_SESSION['dean_eval_flash']);

/* ───────────────────────── roster ───────────────────────── */

$rosterByTab = ['faculty' => [], 'staff' => [], 'executive_assistant' => []];
$doneByTarget = [];

if ($structureActive) {
    $ures = $mysqli->query("SELECT id, full_name, designation, photo, role, secondary_role, sector, department FROM users WHERE role IN ('teacher','staff') AND is_active=1 AND account_status='approved' ORDER BY full_name ASC");
    if ($ures) {
        while ($u = $ures->fetch_assoc()) {
            $info = dean_resolve_bucket($mysqli, $u);
            if ($info === null) continue;
            $u['role_label'] = $info['role_label'];
            $u['eval_bucket'] = $info['bucket'];
            $rosterByTab[$info['route_tab']][] = $u;
        }
    }

    if ($ea = dean_current_ea($mysqli)) {
        $ea['role_label'] = 'Executive Assistant';
        $ea['eval_bucket'] = 'EA';
        $rosterByTab['executive_assistant'][] = $ea;
    }

    // Question availability uses the SAME loader as the modal and the submit
    // handler, so the Evaluate button can never promise a form that then fails.
    foreach ($rosterByTab as $k => &$list) {
        foreach ($list as &$p) {
            $p['question_count'] = count(dean_load_questions($mysqli, (int)$p['id'], $p['eval_bucket']));
            $p['has_questions']  = $p['question_count'] > 0;
        }
        unset($p);
    }
    unset($list);

    // Completed status is keyed to this Dean + current active period.
    if ($period_id_int > 0) {
        $stmt = $mysqli->prepare("SELECT target_user_id, submitted_at FROM evaluation_tracker WHERE evaluator_id=? AND eval_type='school_head' AND period_id=? AND status IN ('submitted','approved') ORDER BY submitted_at DESC, id DESC");
        if ($stmt) {
            $stmt->bind_param('ii', $deanId, $period_id_int);
            $stmt->execute();
            $res = $stmt->get_result();
            while ($r = $res->fetch_assoc()) {
                $uid = (int)$r['target_user_id'];
                if (!isset($doneByTarget[$uid])) $doneByTarget[$uid] = $r['submitted_at'];
            }
            $stmt->close();
        }
    }
}

foreach ($rosterByTab as $k => &$list) {
    foreach ($list as &$r) {
        $uid = (int)$r['id'];
        $r['evaluation_status'] = isset($doneByTarget[$uid]) ? 'completed' : 'not_started';
        $r['last_evaluation_date'] = $doneByTarget[$uid] ?? null;
    }
    unset($r);
    usort($list, fn($a, $b) => strcasecmp($a['full_name'], $b['full_name']));
}
unset($list);

$facultyUsers = $rosterByTab['faculty'];
$staffUsers   = $rosterByTab['staff'];
$eaUsers      = $rosterByTab['executive_assistant'];

$allTargets = array_merge($facultyUsers, $staffUsers, $eaUsers);
$totalAssigned = count($allTargets);
$totalCompleted = count(array_filter($allTargets, fn($r) => $r['evaluation_status'] === 'completed'));
$pendingEvaluations = max(0, $totalAssigned - $totalCompleted);
$completionPct = $totalAssigned > 0 ? (int)round($totalCompleted / $totalAssigned * 100) : 0;

if (($_GET['export'] ?? '') === 'csv') {
    $deptFilter = trim($_GET['dept'] ?? 'all');
    $exportRows = $rosterByTab[$tab];
    if ($deptFilter !== 'all' && $deptFilter !== '') {
        $exportRows = array_values(array_filter($exportRows, fn($r) => (string)($r['department'] ?? '') === $deptFilter));
    }
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="dean_' . $tab . '_export_' . date('Ymd_His') . '.csv"');
    $out = fopen('php://output', 'w');
    fputcsv($out, ['Full Name', 'Designation', 'Role', 'Questions', 'Evaluation Status', 'Last Evaluation Date']);
    foreach ($exportRows as $r) {
        fputcsv($out, [$r['full_name'], $r['designation'] ?? '', $r['role_label'], (int)($r['question_count'] ?? 0), $r['evaluation_status'], $r['last_evaluation_date'] ?? '']);
    }
    fclose($out);
    $mysqli->close();
    exit;
}

$openId   = (int)($_GET['open'] ?? 0);
$openView = isset($_GET['view']);
$periodLabel = trim(($settings['academic_year'] ?? '') . ' — ' . ($settings['academic_term'] ?? ''), ' —');

$mysqli->close();
$photo_src = !empty($me['photo']) ? '../image/' . $me['photo'] : '../image/pbi_logo';
function dean_photo_url(?string $photo): string {
    return !empty($photo) ? '../image/' . $photo : '../image/pbi_logo';
}
?>
<!DOCTYPE html>
<html lang="en" class="dean-internal-scroll-page">
<head>
<meta charset="UTF-8"/>
<meta name="viewport" content="width=device-width, initial-scale=1.0"/>
<title>PBI — Evaluate Others</title>
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
.page-header{display:flex;justify-content:space-between;align-items:center;margin-bottom:24px;flex-wrap:wrap;gap:14px;}
.page-title{font-family:'Rajdhani',sans-serif;font-size:28px;font-weight:700;color:#fff;letter-spacing:1px;}
.page-sub{font-size:13px;color:var(--muted);margin-top:4px;}

.period-badge{background:rgba(124,95,217,.14);border:1px solid rgba(124,95,217,.3);color:var(--violet-h);padding:6px 14px;border-radius:20px;font-size:12px;font-weight:600;display:flex;align-items:center;gap:7px;}
.period-badge.closed{background:rgba(240,84,84,.1);border-color:rgba(240,84,84,.3);color:#fca5a5;}
.period-badge.scheduled{background:rgba(217,154,43,.12);border-color:rgba(217,154,43,.28);color:#d49a2a;}
.period-badge.amber{background:rgba(217,119,6,.14);border-color:rgba(217,119,6,.3);color:#fbbf24;}
.period-badge.gray{background:rgba(255,255,255,.08);border-color:rgba(255,255,255,.12);color:var(--muted);}

.structure-note{display:flex;align-items:flex-start;gap:14px;padding:18px 20px;background:rgba(124,95,217,.08);border:1px solid rgba(124,95,217,.25);border-radius:12px;margin-bottom:26px;}
.structure-note i{color:var(--violet-h);font-size:20px;margin-top:2px;}
.structure-note p{font-size:13px;color:var(--light);line-height:1.6;}
.structure-note p b{color:#fff;}

.card-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:16px;margin-bottom:28px;}
.stat-card{background:rgba(23,42,69,.85);border:1px solid rgba(255,255,255,.08);border-radius:14px;padding:18px 20px;box-shadow:var(--shadow);}
.stat-card i{color:var(--violet-h);font-size:18px;margin-bottom:8px;}
.stat-card .num{font-size:24px;font-weight:700;color:#fff;}
.stat-card .label{font-size:11.5px;color:var(--muted);margin-top:4px;}
.filter-hint{font-size:10.5px;color:var(--muted);margin-top:2px;}

/* ── Evaluate Others: category cards → person cards → rating modal ── */
.de-info{display:flex;align-items:center;gap:10px;padding:9px 14px;margin-bottom:14px;border-radius:10px;font-size:12.5px;background:rgba(124,95,217,.08);border:1px solid rgba(124,95,217,.25);color:var(--light);}
.de-info i{color:var(--violet-h);}
.de-flash{display:flex;align-items:center;gap:10px;padding:12px 16px;margin-bottom:18px;border-radius:12px;font-size:13px;font-weight:600;}
.de-flash.success{background:rgba(16,185,129,.12);border:1px solid rgba(16,185,129,.35);color:#0f9d6e;}
.de-flash.error{background:rgba(240,84,84,.1);border:1px solid rgba(240,84,84,.35);color:#d64545;}
.de-section-label{font-size:11px;font-weight:700;letter-spacing:1.2px;text-transform:uppercase;color:var(--muted);margin:6px 0 12px;}

.de-cats{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:16px;margin-bottom:22px;}
.de-cat{position:relative;text-align:center;cursor:pointer;font-family:inherit;color:var(--light);background:var(--mid);border:1px solid var(--border,rgba(255,255,255,.1));border-bottom-width:3px;border-radius:14px;padding:20px 14px 16px;transition:transform .15s,box-shadow .15s,border-color .15s;}
.de-cat:hover{transform:translateY(-2px);box-shadow:0 8px 20px rgba(28,64,92,.12);}
.de-cat .de-cat-ico{width:62px;height:62px;border-radius:50%;display:flex;align-items:center;justify-content:center;margin:0 auto 12px;font-size:24px;}
.de-cat .de-cat-name{font-family:'Rajdhani',sans-serif;font-size:20px;font-weight:700;}
.de-cat .de-cat-count{font-size:14.5px;color:var(--muted);margin-top:2px;}
.de-cat .de-cat-done{display:inline-flex;align-items:center;gap:5px;margin-top:10px;padding:3px 11px;border-radius:20px;font-size:12px;font-weight:700;}
.de-cat.faculty{border-bottom-color:#38bdf8;} .de-cat.faculty .de-cat-ico{background:rgba(56,189,248,.16);color:#0284c7;} .de-cat.faculty .de-cat-done{background:rgba(56,189,248,.14);color:#0284c7;}
.de-cat.staff{border-bottom-color:#10b981;}   .de-cat.staff .de-cat-ico{background:rgba(16,185,129,.16);color:#059669;}   .de-cat.staff .de-cat-done{background:rgba(16,185,129,.14);color:#059669;}
.de-cat.ea{border-bottom-color:#7C5FD9;}      .de-cat.ea .de-cat-ico{background:rgba(124,95,217,.16);color:#7C5FD9;}      .de-cat.ea .de-cat-done{background:rgba(124,95,217,.14);color:#7C5FD9;}
.de-cat.active{box-shadow:0 8px 24px rgba(28,64,92,.16);border-color:var(--violet);border-bottom-width:3px;}
.de-cat.active::after{content:"";position:absolute;left:50%;bottom:-11px;transform:translateX(-50%);border:8px solid transparent;border-top-color:var(--violet);border-bottom:0;}
.de-cat.faculty.active{border-color:#38bdf8;} .de-cat.faculty.active::after{border-top-color:#38bdf8;}
.de-cat.staff.active{border-color:#10b981;}   .de-cat.staff.active::after{border-top-color:#10b981;}

.de-panel{display:none;background:var(--mid);border:1px solid var(--border,rgba(255,255,255,.1));border-radius:16px;box-shadow:0 4px 16px rgba(28,64,92,.09);overflow:hidden;margin-top:6px;}
.de-panel.active{display:block;}
.de-panel-head{display:flex;align-items:center;gap:10px;padding:11px 18px;border-bottom:1px solid var(--border,rgba(255,255,255,.1));flex-wrap:wrap;}
.de-panel-ico{width:30px;height:30px;font-size:13px;border-radius:8px;display:flex;align-items:center;justify-content:center;background:rgba(124,95,217,.14);color:var(--violet);}
.de-panel-title{font-family:'Rajdhani',sans-serif;font-size:17px;font-weight:700;}
.de-panel-tools{margin-left:auto;display:flex;align-items:center;gap:10px;flex-wrap:wrap;}
.de-panel-tools select{background:var(--inner);border:1px solid var(--border,rgba(255,255,255,.12));color:var(--light);padding:6px 9px;border-radius:8px;font-size:12px;font-family:inherit;min-width:160px;}
.de-export{font-size:12px;font-weight:600;color:var(--violet);text-decoration:none;padding:6px 11px;border:1px solid rgba(124,95,217,.35);border-radius:8px;background:rgba(124,95,217,.08);}
.de-export:hover{background:rgba(124,95,217,.16);}

.de-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:10px;padding:14px 18px;}
.de-person{display:flex;align-items:center;gap:11px;padding:9px 13px;border:1px solid var(--border,rgba(255,255,255,.1));border-radius:12px;background:var(--inner);}
.de-person:hover{border-color:rgba(124,95,217,.45);}
.de-avatar{width:38px;height:38px;border-radius:50%;object-fit:cover;flex-shrink:0;background:var(--mid);border:2px solid var(--border,rgba(255,255,255,.1));}
.de-person-info{flex:1;min-width:0;}
.de-person-name{font-size:13.5px;font-weight:700;color:var(--light);}
.de-person-desig{font-size:11.5px;color:var(--muted);margin:0 0 4px;overflow-wrap:anywhere;}
.de-pill{display:inline-flex;align-items:center;gap:5px;padding:1px 8px;border-radius:20px;font-size:10.5px;font-weight:700;}
.de-pill.pending{background:rgba(245,158,11,.14);color:#b7791f;}
.de-pill.done{background:rgba(16,185,129,.14);color:#0f9d6e;}
.de-pill-date{font-size:11px;color:var(--muted);margin-left:6px;}
.de-btn{display:inline-flex;align-items:center;gap:6px;padding:6px 13px;border-radius:7px;font-size:12px;font-weight:600;font-family:inherit;cursor:pointer;white-space:nowrap;border:1px solid transparent;}
.de-btn-eval{background:var(--violet);color:#fff;border-color:var(--violet);}
.de-btn-eval:hover{background:var(--violet-dark);}
.de-btn-view{background:transparent;color:var(--light);border-color:var(--border,rgba(255,255,255,.2));}
.de-btn-view:hover{border-color:var(--violet);color:var(--violet);}
.de-btn[disabled]{opacity:.5;cursor:not-allowed;}
.de-muted{color:var(--muted);font-size:12.5px;}
.de-empty{grid-column:1/-1;text-align:center;padding:44px 20px;color:var(--muted);}
.de-empty i{font-size:34px;display:block;margin-bottom:12px;opacity:.3;}
.de-panel-foot{display:flex;justify-content:space-between;gap:12px;flex-wrap:wrap;padding:9px 18px;border-top:1px solid var(--border,rgba(255,255,255,.1));font-size:12px;color:var(--muted);}
.de-panel-foot b{color:var(--violet);}

/* modal */
.de-overlay{position:fixed;inset:0;z-index:10000;background:rgba(15,23,42,.55);-webkit-backdrop-filter:blur(7px);backdrop-filter:blur(7px);display:none;align-items:center;justify-content:center;padding:24px;overflow-y:auto;}
.de-overlay.open{display:flex;}
.de-modal{margin:auto;width:100%;max-width:960px;background:var(--mid);color:var(--light);border-radius:16px;box-shadow:0 20px 60px rgba(0,0,0,.35);display:flex;flex-direction:column;max-height:calc(100vh - 88px);}
.de-modal-head{display:flex;align-items:center;gap:14px;padding:18px 24px;border-bottom:1px solid var(--border,rgba(255,255,255,.1));}
.de-modal-head img{width:56px;height:56px;border-radius:50%;object-fit:cover;border:2px solid var(--border,rgba(255,255,255,.15));}
.de-modal-name{font-family:'Rajdhani',sans-serif;font-size:22px;font-weight:700;}
.de-modal-sub{font-size:13px;color:var(--muted);}
.de-modal-x{margin-left:auto;background:none;border:none;font-size:20px;color:var(--muted);cursor:pointer;padding:6px 10px;}
.de-modal-x:hover{color:var(--light);}
.de-modal-body{padding:20px 24px;overflow-y:auto;flex:1;min-height:120px;}
.de-modal-foot{display:flex;gap:12px;padding:16px 24px;border-top:1px solid var(--border,rgba(255,255,255,.1));}
.de-modal-foot .de-btn{justify-content:center;padding:13px 20px;font-size:15px;}
.de-modal-foot .de-btn-cancel{background:var(--inner);color:var(--light);border-color:var(--border,rgba(255,255,255,.15));}
.de-modal-foot .de-btn-submit{flex:1;background:var(--violet);color:#fff;border-color:var(--violet);}
.de-modal-foot .de-btn-submit:hover{background:var(--violet-dark);}
.de-loading,.de-error{text-align:center;padding:36px 10px;color:var(--muted);font-size:14px;}
.de-error{color:#d64545;}

.de-progress{display:flex;justify-content:space-between;align-items:center;gap:10px;padding:11px 16px;margin-bottom:14px;border-radius:10px;font-size:13px;font-weight:600;background:rgba(124,95,217,.08);border:1px solid rgba(124,95,217,.25);}
.de-progress .de-progress-status{color:var(--violet);font-weight:700;}
.de-validation{padding:11px 16px;margin-bottom:14px;border-radius:10px;font-size:13px;background:rgba(240,84,84,.1);border:1px solid rgba(240,84,84,.35);color:#c53030;}
.de-validation[hidden]{display:none;}
.de-legend{display:flex;gap:16px;flex-wrap:wrap;padding:10px 14px;margin-bottom:16px;border-radius:10px;background:var(--inner);border:1px solid var(--border,rgba(255,255,255,.1));font-size:12.5px;}
.de-legend-item{display:flex;align-items:center;gap:7px;}
.de-legend-dot{width:22px;height:22px;border-radius:6px;background:var(--violet);color:#fff;font-weight:700;font-size:12px;display:flex;align-items:center;justify-content:center;}
.de-cat-title{display:flex;align-items:center;gap:7px;margin:18px 0 8px;font-size:12px;font-weight:700;letter-spacing:.8px;text-transform:uppercase;color:var(--violet);}
.de-table-wrap{border:1px solid var(--border,rgba(255,255,255,.12));border-radius:12px;overflow-x:auto;}
.de-table{width:100%;border-collapse:collapse;}
.de-table th{background:var(--inner);color:var(--muted);font-size:11px;font-weight:700;letter-spacing:.7px;text-transform:uppercase;text-align:center;padding:11px 8px;border-bottom:1px solid var(--border,rgba(255,255,255,.1));}
.de-table th:first-child{text-align:left;padding-left:16px;}
.de-table th:not(:first-child){width:58px;}
.de-table td{padding:12px 8px;border-bottom:1px solid var(--border,rgba(255,255,255,.08));text-align:center;vertical-align:middle;}
.de-table td:first-child{text-align:left;padding-left:16px;}
.de-table tr:last-child td{border-bottom:none;}
.de-qtext{font-size:14px;line-height:1.45;}
.de-qno{color:var(--violet);font-weight:700;margin-right:6px;}
.de-rate input{position:absolute;opacity:0;pointer-events:none;}
.de-rate{position:relative;display:inline-block;}
.de-rate label{display:inline-flex;align-items:center;justify-content:center;width:36px;height:32px;border-radius:7px;border:1px solid var(--border,rgba(255,255,255,.18));background:var(--inner);color:var(--muted);font-size:13px;font-weight:600;cursor:pointer;transition:all .12s;}
.de-rate label:hover{border-color:var(--violet);color:var(--violet);}
.de-rate input:checked + label{background:var(--violet);border-color:var(--violet);color:#fff;}
.de-rate input:focus-visible + label{outline:2px solid var(--violet-h);outline-offset:2px;}
.de-table tr.unanswered td{background:rgba(240,84,84,.06);}
.de-table tr.unanswered td:first-child{box-shadow:inset 3px 0 0 #ef4444;}
.de-table tr.unanswered .de-qno{color:#ef4444;}
.de-comment{margin-top:20px;}
.de-comment-label{font-size:11px;font-weight:700;letter-spacing:1px;text-transform:uppercase;color:var(--muted);margin-bottom:8px;}
.de-comment textarea{width:100%;min-height:96px;resize:vertical;background:var(--inner);color:var(--light);border:1px solid var(--border,rgba(255,255,255,.15));border-radius:10px;padding:12px;font-family:inherit;font-size:13.5px;}
.de-comment textarea:focus{outline:none;border-color:var(--violet);}
.de-comment-readonly{font-size:13.5px;line-height:1.55;white-space:pre-wrap;}
.de-summary{display:flex;align-items:baseline;gap:8px;margin-bottom:6px;}
.de-summary .num{font-family:'Rajdhani',sans-serif;font-size:34px;font-weight:700;}
.de-summary .of{font-size:13px;color:var(--muted);}
.de-score-chip{display:inline-flex;align-items:center;justify-content:center;min-width:34px;height:28px;padding:0 8px;border-radius:7px;background:var(--violet);color:#fff;font-weight:700;font-size:13px;}

/* modal: sits above the fixed header/sidebar, themed scrollbar, dark-mode colours */
.de-modal{overflow:hidden;border:1px solid var(--border,rgba(255,255,255,.1));}
.de-modal-head{flex:0 0 auto;}
.de-modal-foot{flex:0 0 auto;}
.de-modal-body{scrollbar-width:thin;scrollbar-color:#B9C6D4 transparent;}
.de-modal-body::-webkit-scrollbar{width:10px;}
.de-modal-body::-webkit-scrollbar-track{background:transparent;}
.de-modal-body::-webkit-scrollbar-thumb{background:#B9C6D4;border-radius:10px;border:2px solid transparent;background-clip:padding-box;}
.de-modal-body::-webkit-scrollbar-button{display:none;}
html.dark-theme .de-overlay{background:rgba(3,10,22,.78);}
html.dark-theme .de-modal{background:#172A45;color:#E0E6F0;border-color:rgba(255,255,255,.10);box-shadow:0 24px 70px rgba(0,0,0,.6);}
html.dark-theme .de-modal-head,html.dark-theme .de-modal-foot{border-color:rgba(255,255,255,.08);}
html.dark-theme .de-modal-head img{border-color:rgba(255,255,255,.18);}
html.dark-theme .de-modal-name{color:#F1F5FB;}
html.dark-theme .de-modal-sub,html.dark-theme .de-modal-x{color:#A0B3C6;}
html.dark-theme .de-modal-x:hover{color:#fff;}
html.dark-theme .de-modal-body{scrollbar-color:#2F4B72 transparent;}
html.dark-theme .de-modal-body::-webkit-scrollbar-thumb{background:#2F4B72;background-clip:padding-box;}
html.dark-theme .de-progress{background:rgba(156,133,240,.14);border-color:rgba(156,133,240,.35);color:#E0E6F0;}
html.dark-theme .de-progress .de-progress-status,html.dark-theme .de-cat-title,html.dark-theme .de-qno{color:#B6A5F5;}
html.dark-theme .de-validation{background:rgba(248,113,113,.12);border-color:rgba(248,113,113,.4);color:#FCA5A5;}
html.dark-theme .de-legend{background:#0F1F3D;border-color:rgba(255,255,255,.08);color:#CBD5E1;}
html.dark-theme .de-table-wrap{border-color:rgba(255,255,255,.10);}
html.dark-theme .de-table th{background:#0F1F3D;color:#A0B3C6;border-bottom-color:rgba(255,255,255,.08);}
html.dark-theme .de-table td{border-bottom-color:rgba(255,255,255,.06);color:#E0E6F0;}
html.dark-theme .de-rate label{background:#0F1F3D;border-color:rgba(255,255,255,.14);color:#A0B3C6;}
html.dark-theme .de-rate label:hover{border-color:#9C85F0;color:#C4B5FD;}
html.dark-theme .de-rate input:checked + label{background:#7C5FD9;border-color:#9C85F0;color:#fff;}
html.dark-theme .de-table tr.unanswered td{background:rgba(248,113,113,.07);}
html.dark-theme .de-comment-label{color:#A0B3C6;}
html.dark-theme .de-comment textarea{background:#0F1F3D;color:#E0E6F0;border-color:rgba(255,255,255,.14);}
html.dark-theme .de-modal-foot .de-btn-cancel{background:#0F1F3D;color:#E0E6F0;border-color:rgba(255,255,255,.14);}
html.dark-theme .de-modal-foot .de-btn-cancel:hover{border-color:#9C85F0;}
html.dark-theme .de-error{color:#FCA5A5;}
@media(max-width:768px){.de-overlay{padding:0;align-items:stretch;}.de-modal{max-height:100vh;border-radius:0;}}
/* ===== Evaluation popup: centered card, blurred backdrop, Dean purple accent ===== */
.de-overlay .de-modal{max-width:780px;height:min(940px,calc(100vh - 48px));max-height:calc(100vh - 48px);border-radius:22px;border:1px solid #E3EAF3;background:#FFFFFF;color:#12263A;box-shadow:0 30px 80px rgba(15,23,42,.35);}
.de-overlay .de-modal-head{gap:16px;padding:24px 32px 20px;border-bottom:1px solid #E3EAF3;}
.de-overlay .de-modal-head img{width:66px;height:66px;border:3px solid #7C5FD9;box-shadow:0 0 0 3px rgba(124,95,217,.14);}
.de-overlay .de-modal-name{font-size:24px;color:#12263A;line-height:1.15;}
.de-overlay .de-modal-sub{font-size:14px;color:#64788C;}
.de-overlay .de-modal-x{width:38px;height:38px;padding:0;border-radius:50%;display:flex;align-items:center;justify-content:center;color:#64788C;transition:background .15s,color .15s;}
.de-overlay .de-modal-x:hover{background:rgba(124,95,217,.10);color:#5F45B8;}
.de-overlay .de-modal-body{padding:24px 32px 18px;}
.de-overlay .de-progress{padding:12px 18px;margin-bottom:16px;border-radius:12px;background:#F3F0FC;border:1px solid #D9CFF5;color:#4A5B6E;}
.de-overlay .de-progress .de-progress-status{color:#5F45B8;}
.de-overlay .de-legend{gap:12px 18px;padding:12px 18px;margin-bottom:6px;border-radius:12px;background:#F8FAFC;border:1px solid #E3EAF3;color:#4A5B6E;font-size:13px;}
.de-overlay .de-legend-dot{width:24px;height:24px;border-radius:7px;background:#7C5FD9;color:#fff;}
.de-overlay .de-cat-title{margin:24px 0 10px;color:#5F45B8;}
.de-overlay .de-table-wrap{border:1px solid #E3EAF3;border-radius:14px;background:#fff;}
.de-overlay .de-table th:not(:first-child){width:50px;}
.de-overlay .de-rate label{width:34px;}
.de-overlay .de-table th{background:#F4F8FF;color:#4B6580;border-bottom:1px solid #E3EAF3;padding:13px 8px;}
.de-overlay .de-table td{padding:14px 8px;border-bottom:1px solid #EDF2F8;}
.de-overlay .de-table tbody tr:hover td{background:#FAFBFF;}
.de-overlay .de-qtext{font-size:14.5px;color:#12263A;}
.de-overlay .de-qno{color:#7C5FD9;}
.de-overlay .de-rate label{width:36px;height:36px;border-radius:9px;background:#F4F8FF;border:1px solid #D5E0EC;color:#4B6580;font-size:14px;font-weight:700;}
.de-overlay .de-rate label:hover{background:#F3F0FC;border-color:#7C5FD9;color:#5F45B8;}
.de-overlay .de-rate input:checked + label{background:#7C5FD9;border-color:#7C5FD9;color:#fff;box-shadow:0 4px 10px rgba(124,95,217,.35);}
.de-overlay .de-comment textarea{background:#F8FAFC;border:1px solid #D5E0EC;color:#12263A;border-radius:12px;}
.de-overlay .de-comment textarea:focus{border-color:#7C5FD9;box-shadow:0 0 0 3px rgba(124,95,217,.15);}
.de-overlay .de-modal-foot{gap:14px;padding:18px 32px 22px;border-top:1px solid #E3EAF3;background:#fff;}
.de-overlay .de-modal-foot .de-btn{border-radius:12px;}
.de-overlay .de-modal-foot .de-btn-cancel{background:#fff;color:#12263A;border:1px solid #D5E0EC;font-weight:600;}
.de-overlay .de-modal-foot .de-btn-cancel:hover{background:#F8FAFC;border-color:#7C5FD9;color:#5F45B8;}
.de-overlay .de-modal-foot .de-btn-submit{background:#7C5FD9;border-color:#7C5FD9;color:#fff;font-weight:700;box-shadow:0 6px 16px rgba(124,95,217,.30);}
.de-overlay .de-modal-foot .de-btn-submit:hover{background:#5F45B8;border-color:#5F45B8;}
.de-overlay .de-summary .num{color:#5F45B8;}
.de-overlay .de-score-chip{background:#7C5FD9;}
@media(max-width:768px){.de-overlay .de-modal{border-radius:0;height:100vh;max-height:100vh}.de-overlay .de-modal-head,.de-overlay .de-modal-foot{padding-left:18px;padding-right:18px}.de-overlay .de-modal-body{padding:18px}}
/* dark mode */
html.dark-theme .de-overlay{background:rgba(3,10,22,.62);}
html.dark-theme .de-overlay .de-modal{background:#172A45;color:#E0E6F0;border-color:rgba(255,255,255,.10);box-shadow:0 30px 80px rgba(0,0,0,.6);}
html.dark-theme .de-overlay .de-modal-head,html.dark-theme .de-overlay .de-modal-foot{border-color:rgba(255,255,255,.08);background:transparent;}
html.dark-theme .de-overlay .de-modal-head img{border-color:#9C85F0;box-shadow:0 0 0 3px rgba(156,133,240,.18);}
html.dark-theme .de-overlay .de-modal-name{color:#F1F5FB;}
html.dark-theme .de-overlay .de-modal-sub,html.dark-theme .de-overlay .de-modal-x{color:#A0B3C6;}
html.dark-theme .de-overlay .de-modal-x:hover{background:rgba(156,133,240,.16);color:#fff;}
html.dark-theme .de-overlay .de-progress{background:rgba(156,133,240,.14);border-color:rgba(156,133,240,.35);color:#E0E6F0;}
html.dark-theme .de-overlay .de-progress .de-progress-status,html.dark-theme .de-overlay .de-cat-title{color:#C4B5FD;}
html.dark-theme .de-overlay .de-qno{color:#B6A5F5;}
html.dark-theme .de-overlay .de-legend{background:#0F1F3D;border-color:rgba(255,255,255,.08);color:#CBD5E1;}
html.dark-theme .de-overlay .de-table-wrap{background:transparent;border-color:rgba(255,255,255,.10);}
html.dark-theme .de-overlay .de-table th{background:#0F1F3D;color:#A0B3C6;border-bottom-color:rgba(255,255,255,.08);}
html.dark-theme .de-overlay .de-table td{border-bottom-color:rgba(255,255,255,.06);}
html.dark-theme .de-overlay .de-table tbody tr:hover td{background:rgba(255,255,255,.03);}
html.dark-theme .de-overlay .de-qtext{color:#E0E6F0;}
html.dark-theme .de-overlay .de-rate label{background:#0F1F3D;border-color:rgba(255,255,255,.14);color:#A0B3C6;}
html.dark-theme .de-overlay .de-rate label:hover{background:rgba(156,133,240,.14);border-color:#9C85F0;color:#C4B5FD;}
html.dark-theme .de-overlay .de-rate input:checked + label{background:#7C5FD9;border-color:#9C85F0;color:#fff;}
html.dark-theme .de-overlay .de-comment textarea{background:#0F1F3D;color:#E0E6F0;border-color:rgba(255,255,255,.14);}
html.dark-theme .de-overlay .de-modal-foot .de-btn-cancel{background:#0F1F3D;color:#E0E6F0;border-color:rgba(255,255,255,.14);}
html.dark-theme .de-overlay .de-modal-foot .de-btn-cancel:hover{border-color:#9C85F0;color:#C4B5FD;background:#0F1F3D;}
html.dark-theme .de-overlay .de-summary .num{color:#C4B5FD;}
@media(max-width:900px){.de-cats{grid-template-columns:1fr;}.de-grid{grid-template-columns:1fr;}}
@media(max-width:768px){.de-table th:not(:first-child){width:46px;}.de-rate label{width:30px;height:28px;}}
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
$active = 'evaluation';
$sidebarScope = HIGHER_ED_LABEL . ' Division';
include __DIR__ . '/includes/dean_sidebar.php';
?>

<main class="main dean-internal-scroll">

    <?php if ($flash): ?>
    <div class="de-flash <?= $flash['type'] === 'success' ? 'success' : 'error' ?>" role="status">
        <i class="fa-solid <?= $flash['type'] === 'success' ? 'fa-circle-check' : 'fa-circle-exclamation' ?>"></i>
        <?= htmlspecialchars($flash['msg']) ?>
    </div>
    <?php endif; ?>

    <?php if (!$structureActive): ?>
    <div class="structure-note">
        <i class="fa-solid fa-circle-info"></i>
        <p><b><?= HIGHER_ED_LABEL ?> is not the active academic structure.</b><br>
        The current evaluation period is configured for <b><?= htmlspecialchars($settings['academic_structure_label']) ?></b>.
        Evaluation is unavailable until the Executive Assistant switches it back.</p>
    </div>
    <?php else: ?>

    <div class="de-section-label"></div>
    <div class="de-cats" role="tablist">
        <?php foreach ($validTabs as $t):
            $list = $rosterByTab[$t];
            $n = count($list);
            $done = count(array_filter($list, fn($r) => $r['evaluation_status'] === 'completed'));
        ?>
        <button type="button" role="tab" class="de-cat <?= $catMeta[$t]['cls'] ?> <?= $tab === $t ? 'active' : '' ?>" data-tab="<?= $t ?>" aria-selected="<?= $tab === $t ? 'true' : 'false' ?>">
            <div class="de-cat-ico"><i class="fa-solid <?= $catMeta[$t]['icon'] ?>"></i></div>
            <div class="de-cat-name"><?= htmlspecialchars($catMeta[$t]['label']) ?></div>
            <div class="de-cat-count"><?= $n ?> member<?= $n === 1 ? '' : 's' ?></div>
            <span class="de-cat-done"><i class="fa-solid fa-check" style="font-size:9px;"></i> <?= $done ?>/<?= $n ?> done</span>
        </button>
        <?php endforeach; ?>
    </div>

    <?php foreach ($validTabs as $t):
        $list = $rosterByTab[$t];
        $n = count($list);
        $done = count(array_filter($list, fn($r) => $r['evaluation_status'] === 'completed'));
        $depts = [];
        foreach ($list as $r) { $d = trim((string)($r['department'] ?? '')); if ($d !== '') $depts[$d] = true; }
        $depts = array_keys($depts); sort($depts);
    ?>
    <section class="de-panel <?= $tab === $t ? 'active' : '' ?>" data-tab="<?= $t ?>">
        <div class="de-panel-head">
            <div class="de-panel-ico"><i class="fa-solid <?= $catMeta[$t]['icon'] ?>"></i></div>
            <div class="de-panel-title"><?= htmlspecialchars($catMeta[$t]['label']) ?></div>
            <div class="de-panel-tools">
                <?php if (!empty($depts)): ?>
                <select class="de-dept" aria-label="Filter by department">
                    <option value="all">All Departments</option>
                    <?php foreach ($depts as $d): ?><option value="<?= htmlspecialchars($d) ?>"><?= htmlspecialchars($d) ?></option><?php endforeach; ?>
                </select>
                <?php endif; ?>
                <a class="de-export" data-base="?export=csv&amp;tab=<?= urlencode($t) ?>" href="?export=csv&amp;tab=<?= urlencode($t) ?>"><i class="fa-solid fa-file-csv"></i> Export CSV</a>
            </div>
        </div>

        <div class="de-grid">
        <?php if (empty($list)): ?>
            <div class="de-empty"><i class="fa-solid fa-user-slash"></i><p>No <?= htmlspecialchars($catMeta[$t]['noun']) ?> are available for evaluation.</p></div>
        <?php else: foreach ($list as $p):
            $isDone = $p['evaluation_status'] === 'completed';
            $sub = $p['role_label'] . (!empty($p['department']) ? ' · ' . $p['department'] : '');
        ?>
            <div class="de-person" data-id="<?= (int)$p['id'] ?>" data-dept="<?= htmlspecialchars((string)($p['department'] ?? '')) ?>" data-status="<?= $isDone ? 'completed' : 'pending' ?>">
                <img class="de-avatar" src="<?= htmlspecialchars(dean_photo_url($p['photo'] ?? null)) ?>" alt=""/>
                <div class="de-person-info">
                    <div class="de-person-name"><?= htmlspecialchars($p['full_name']) ?></div>
                    <div class="de-person-desig"><?= htmlspecialchars($p['designation'] ?: '—') ?></div>
                    <?php if ($isDone): ?>
                        <span class="de-pill done"><i class="fa-solid fa-check" style="font-size:9px;"></i> Completed</span>
                        <?php if ($p['last_evaluation_date']): ?><span class="de-pill-date"><?= htmlspecialchars(date('M j, Y', strtotime($p['last_evaluation_date']))) ?></span><?php endif; ?>
                    <?php else: ?>
                        <span class="de-pill pending"><i class="fa-solid fa-hourglass-half" style="font-size:9px;"></i> Pending</span>
                    <?php endif; ?>
                </div>
                <div class="de-person-action">
                <?php if (!$evalOpen || $period_id_int <= 0): ?>
                    <span class="de-muted">Evaluation closed</span>
                <?php elseif ($isDone): ?>
                    <button type="button" class="de-btn de-btn-view" data-mode="view" data-id="<?= (int)$p['id'] ?>" data-name="<?= htmlspecialchars($p['full_name']) ?>" data-sub="<?= htmlspecialchars($sub) ?>" data-photo="<?= htmlspecialchars(dean_photo_url($p['photo'] ?? null)) ?>" data-tab="<?= $t ?>"><i class="fa-regular fa-eye"></i> View</button>
                <?php elseif (empty($p['has_questions'])): ?>
                    <button type="button" class="de-btn de-btn-view" disabled title="No questions have been set up for this person yet."><i class="fa-solid fa-ban"></i> No questions</button>
                <?php else: ?>
                    <button type="button" class="de-btn de-btn-eval" data-mode="evaluate" data-id="<?= (int)$p['id'] ?>" data-name="<?= htmlspecialchars($p['full_name']) ?>" data-sub="<?= htmlspecialchars($sub) ?>" data-photo="<?= htmlspecialchars(dean_photo_url($p['photo'] ?? null)) ?>" data-tab="<?= $t ?>"><i class="fa-solid fa-pen"></i> Evaluate</button>
                <?php endif; ?>
                </div>
            </div>
        <?php endforeach; endif; ?>
        </div>

        <div class="de-panel-foot">
            <div><b class="js-total"><?= $n ?></b> members &nbsp; <b class="js-done"><?= $done ?></b> completed &nbsp; <b class="js-pending"><?= $n - $done ?></b> pending</div>
            <div><i class="fa-regular fa-calendar"></i> <?= htmlspecialchars($periodLabel) ?></div>
        </div>
    </section>
    <?php endforeach; ?>

    <?php endif; ?>
</main>

<?php if ($structureActive): ?>
<div class="de-overlay" id="deOverlay" aria-hidden="true">
    <div class="de-modal" role="dialog" aria-modal="true" aria-labelledby="deName">
        <div class="de-modal-head">
            <img id="dePhoto" src="../image/pbi_logo" alt=""/>
            <div>
                <div class="de-modal-name" id="deName"></div>
                <div class="de-modal-sub" id="deSub"></div>
            </div>
            <button type="button" class="de-modal-x" id="deClose" aria-label="Close"><i class="fa-solid fa-xmark"></i></button>
        </div>
        <form method="POST" action="dean_evaluation.php" id="deForm" novalidate style="display:flex;flex-direction:column;min-height:0;flex:1;">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>"/>
            <input type="hidden" name="submit_evaluation" value="1"/>
            <input type="hidden" name="target_user_id" id="deTarget" value=""/>
            <input type="hidden" name="tab" id="deTab" value="<?= htmlspecialchars($tab) ?>"/>
            <div class="de-modal-body" id="deBody"><div class="de-loading"><i class="fa-solid fa-spinner fa-spin"></i> Loading questions...</div></div>
            <div class="de-modal-foot">
                <button type="button" class="de-btn de-btn-cancel" id="deCancel">Cancel</button>
                <button type="submit" class="de-btn de-btn-submit" id="deSubmit"><i class="fa-solid fa-paper-plane"></i> Submit Evaluation</button>
            </div>
        </form>
    </div>
</div>

<script>
(function(){
    const OPEN_ID   = <?= (int)$openId ?>;
    const OPEN_VIEW = <?= $openView ? 'true' : 'false' ?>;

    const $ = (id) => document.getElementById(id);
    const overlay = $('deOverlay'), form = $('deForm'), body = $('deBody');
    const submitBtn = $('deSubmit'), cancelBtn = $('deCancel');
    let mode = 'evaluate', loaded = false, requestToken = 0;

    function esc(s){
        return String(s == null ? '' : s).replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
    }

    /* ── category cards / panels ── */
    const cats = document.querySelectorAll('.de-cat');
    const panels = document.querySelectorAll('.de-panel');
    function showTab(tab, push){
        cats.forEach(c => { const on = c.dataset.tab === tab; c.classList.toggle('active', on); c.setAttribute('aria-selected', on ? 'true' : 'false'); });
        panels.forEach(p => p.classList.toggle('active', p.dataset.tab === tab));
        if (push && window.history && history.replaceState) {
            const u = new URL(window.location.href);
            u.searchParams.set('tab', tab); u.searchParams.delete('open'); u.searchParams.delete('view');
            history.replaceState(null, '', u.toString());
        }
    }
    cats.forEach(c => c.addEventListener('click', () => showTab(c.dataset.tab, true)));

    /* ── department filter (per panel) ── */
    panels.forEach(panel => {
        const sel = panel.querySelector('.de-dept');
        const exp = panel.querySelector('.de-export');
        function apply(){
            const d = sel ? sel.value : 'all';
            let total = 0, done = 0;
            panel.querySelectorAll('.de-person').forEach(card => {
                const show = d === 'all' || card.dataset.dept === d;
                card.style.display = show ? '' : 'none';
                if (show) { total++; if (card.dataset.status === 'completed') done++; }
            });
            panel.querySelector('.js-total').textContent = total;
            panel.querySelector('.js-done').textContent = done;
            panel.querySelector('.js-pending').textContent = total - done;
            if (exp) exp.href = exp.dataset.base.replace(/&amp;/g,'&') + (d === 'all' ? '' : '&dept=' + encodeURIComponent(d));
        }
        if (sel) sel.addEventListener('change', apply);
    });

    /* ── modal open / close ── */
    function openModal(btn){
        mode = btn.dataset.mode === 'view' ? 'view' : 'evaluate';
        const id = btn.dataset.id;
        $('deName').textContent = btn.dataset.name || '';
        $('deSub').textContent = btn.dataset.sub || '';
        $('dePhoto').src = btn.dataset.photo || '../image/pbi_logo';
        $('deTarget').value = id;
        $('deTab').value = btn.dataset.tab || 'faculty';
        body.innerHTML = '<div class="de-loading"><i class="fa-solid fa-spinner fa-spin"></i> Loading ' + (mode === 'view' ? 'your evaluation' : 'questions') + '...</div>';
        loaded = false;
        submitBtn.style.display = mode === 'view' ? 'none' : '';
        submitBtn.disabled = false;
        submitBtn.innerHTML = '<i class="fa-solid fa-paper-plane"></i> Submit Evaluation';
        cancelBtn.textContent = mode === 'view' ? 'Close' : 'Cancel';
        overlay.classList.add('open'); overlay.setAttribute('aria-hidden', 'false');
        document.body.style.overflow = 'hidden';
        mode === 'view' ? loadSubmission(id) : loadQuestions(id);
    }
    function closeModal(){
        requestToken++;
        overlay.classList.remove('open'); overlay.setAttribute('aria-hidden', 'true');
        document.body.style.overflow = '';
        loaded = false;
    }
    document.querySelectorAll('.de-person-action button[data-id]').forEach(b => b.addEventListener('click', () => openModal(b)));
    $('deClose').addEventListener('click', closeModal);
    cancelBtn.addEventListener('click', closeModal);
    overlay.addEventListener('click', e => { if (e.target === overlay) closeModal(); });
    document.addEventListener('keydown', e => { if (e.key === 'Escape' && overlay.classList.contains('open')) closeModal(); });

    function showError(msg){
        body.innerHTML = '<div class="de-error"><i class="fa-solid fa-triangle-exclamation"></i><p style="margin-top:10px">' + esc(msg) + '</p></div>';
        submitBtn.style.display = 'none';
        cancelBtn.textContent = 'Close';
    }
    function fetchJson(url){
        return fetch(url, {credentials: 'same-origin'}).then(r => r.json().catch(() => ({success:false, error:'Unexpected response from the server.'})));
    }

    /* ── evaluate mode ── */
    function loadQuestions(id){
        const token = ++requestToken;
        fetchJson('dean_evaluation.php?get_questions=1&target_id=' + encodeURIComponent(id)).then(data => {
            if (token !== requestToken) return;
            if (!data.success) return showError(data.error || 'Failed to load questions.');
            const qs = data.questions || [];
            const grouped = {};
            qs.forEach(q => { (grouped[q.category || 'General'] = grouped[q.category || 'General'] || []).push(q); });

            let html = '<div class="de-validation" id="deValidation" role="alert" aria-live="assertive" hidden></div>';
            html += '<div class="de-progress"><span>Please rate every question before submitting.</span><span class="de-progress-status" id="deProgress">0 / ' + qs.length + ' answered</span></div>';
            html += '<div class="de-legend">' + [[5,'Always'],[4,'Often'],[3,'Sometimes'],[2,'Rarely'],[1,'Never']]
                .map(x => '<div class="de-legend-item"><div class="de-legend-dot">' + x[0] + '</div> ' + x[1] + '</div>').join('') + '</div>';

            let n = 1;
            Object.keys(grouped).forEach(cat => {
                html += '<div class="de-cat-title"><i class="fa-solid fa-layer-group"></i>' + esc(cat) + '</div>';
                html += '<div class="de-table-wrap"><table class="de-table"><thead><tr><th>Question</th><th>5</th><th>4</th><th>3</th><th>2</th><th>1</th></tr></thead><tbody>';
                grouped[cat].forEach(q => {
                    const num = n++;
                    html += '<tr data-number="' + num + '"><td><div class="de-qtext"><span class="de-qno">' + num + '.</span>' + esc(q.question) + '</div></td>';
                    [5,4,3,2,1].forEach(v => {
                        const oid = 'r_' + q.source + '_' + q.id + '_' + v;
                        html += '<td><div class="de-rate"><input type="radio" name="rating[' + esc(q.key) + ']" id="' + oid + '" value="' + v + '" aria-label="Question ' + num + ', rating ' + v + '"><label for="' + oid + '">' + v + '</label></div></td>';
                    });
                    html += '</tr>';
                });
                html += '</tbody></table></div>';
            });
            html += '<div class="de-comment"><div class="de-comment-label"><i class="fa-solid fa-comment-dots"></i> Comments, Suggestions &amp; Areas for Improvement</div>' +
                    '<textarea name="comment" rows="4" maxlength="2000" placeholder="Share your thoughts, suggestions, or concerns about this person\'s performance...."></textarea></div>';
            body.innerHTML = html;
            loaded = true;
        }).catch(err => { if (token === requestToken) showError('Failed to load questions. ' + (err && err.message ? err.message : '')); });
    }

    function radioNames(){
        return [...new Set([...form.querySelectorAll('input[type="radio"][name^="rating["]')].map(r => r.name))];
    }
    function isAnswered(name){
        return !!form.querySelector('input[name="' + CSS.escape(name) + '"]:checked');
    }
    function updateProgress(){
        const status = $('deProgress'); if (!status) return;
        const names = radioNames();
        const answered = names.filter(isAnswered).length;
        status.textContent = answered + ' / ' + names.length + ' answered';
        status.style.color = answered === names.length && names.length ? '#10b981' : '';
    }
    form.addEventListener('change', e => {
        if (!e.target.matches('input[type="radio"]')) return;
        const row = e.target.closest('tr'); if (row) row.classList.remove('unanswered');
        updateProgress();
        const box = $('deValidation');
        if (box && radioNames().every(isAnswered)) { box.hidden = true; box.innerHTML = ''; }
    });
    form.addEventListener('submit', e => {
        if (mode !== 'evaluate' || !loaded) { e.preventDefault(); return; }
        const missing = radioNames().filter(n => !isAnswered(n));
        if (missing.length) {
            e.preventDefault();
            form.querySelectorAll('tr.unanswered').forEach(r => r.classList.remove('unanswered'));
            const rows = missing.map(n => form.querySelector('input[name="' + CSS.escape(n) + '"]').closest('tr'));
            rows.forEach(r => r.classList.add('unanswered'));
            const nums = rows.map(r => r.dataset.number);
            const box = $('deValidation');
            if (box) {
                box.hidden = false;
                box.innerHTML = '<strong>Please answer all questions before submitting.</strong> You have ' + missing.length + ' unanswered question' + (missing.length === 1 ? '' : 's') +
                    (nums.length <= 8 ? ' (' + nums.join(', ') + ')' : '') + '.';
                box.scrollIntoView({behavior: 'smooth', block: 'center'});
            }
            return;
        }
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Submitting...';
    });

    /* ── view mode ── */
    function loadSubmission(id){
        const token = ++requestToken;
        fetchJson('dean_evaluation.php?get_submission=1&target_id=' + encodeURIComponent(id)).then(data => {
            if (token !== requestToken) return;
            if (!data.success) return showError(data.error || 'Could not load this evaluation.');
            let html = '<div class="de-summary"><span class="num">' + (data.score != null ? Number(data.score).toFixed(2) : '—') + '</span><span class="of">/ 5.00 overall</span></div>' +
                       '<div class="de-modal-sub" style="margin-bottom:6px">Submitted ' + esc(data.submitted_at) + '</div>';
            const grouped = {};
            (data.answers || []).forEach(a => { (grouped[a.category || 'General'] = grouped[a.category || 'General'] || []).push(a); });
            let n = 1;
            Object.keys(grouped).forEach(cat => {
                html += '<div class="de-cat-title"><i class="fa-solid fa-layer-group"></i>' + esc(cat) + '</div>';
                html += '<div class="de-table-wrap"><table class="de-table"><thead><tr><th>Question</th><th>Your rating</th></tr></thead><tbody>';
                grouped[cat].forEach(a => {
                    html += '<tr><td><div class="de-qtext"><span class="de-qno">' + (n++) + '.</span>' + esc(a.question) + '</div></td><td><span class="de-score-chip">' + esc(Math.round(a.score)) + '</span></td></tr>';
                });
                html += '</tbody></table></div>';
            });
            html += '<div class="de-comment"><div class="de-comment-label"><i class="fa-solid fa-comment-dots"></i> Comment</div><p class="de-comment-readonly">' +
                    (data.comment ? esc(data.comment) : 'No written comment.') + '</p></div>';
            body.innerHTML = html;
            loaded = true;
        }).catch(() => { if (token === requestToken) showError('Could not load this evaluation.'); });
    }

    /* deep link from the old dean_evaluate.php URL: ?tab=…&open=ID[&view=1] */
    if (OPEN_ID > 0) {
        const btn = document.querySelector('.de-person-action button[data-id="' + OPEN_ID + '"]');
        if (btn) {
            const panel = btn.closest('.de-panel'); if (panel) showTab(panel.dataset.tab, false);
            if (!OPEN_VIEW || btn.dataset.mode === 'view') openModal(btn);
        }
    }
})();
</script>
<?php endif; ?>
<script src="../admin/eval_status_poll.js" defer></script>
</body>
<link rel="stylesheet" href="includes/dean_light_theme.css?v=dashboard-ui-20261009" id="dean-light-theme-final"/>
</html>