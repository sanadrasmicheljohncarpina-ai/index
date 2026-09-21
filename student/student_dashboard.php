<?php
// session_bootstrap.php — include this BEFORE session_start() everywhere
session_set_cookie_params([
    'lifetime' => 0,        // session cookie, dies when browser closes
    'path'     => '/',      // available across the whole site, not just /admin/
    'domain'   => '',       // let the browser infer it — avoids localhost vs IP mismatches
    'secure'   => false,    // set true only if you're on https
    'httponly' => true,
    'samesite' => 'Lax',
]);
session_start();
require_once 'db.php';
require_once 'security.php';
require_once '../shared/eligibility.php';
require_once '../shared/EvaluationContextService.php';
require_once '../shared/QuestionnaireService.php';
qn_migrate_legacy_once($mysqli);

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
    header("Location: student_login.php"); exit;
}

$student_id   = $_SESSION['user_id'];
$student_name = $_SESSION['full_name'];

// ── FETCH STUDENT'S OWN PHOTO + EDUCATION LEVEL ───────────────
// Moved up here (was previously fetched further down, AFTER the
// submission handler) because the server-side eligibility re-check in
// the submission handler now needs $student_level/$student_year_level
// too -- it can no longer rely purely on shared/eligibility.php.
$phRes = $mysqli->prepare("SELECT photo, education_level, year_level, username, email FROM users WHERE id=? LIMIT 1");
$phRes->bind_param("i", $student_id);
$phRes->execute();
$phRow = $phRes->get_result()->fetch_assoc();
$phRes->close();
$student_photo      = $phRow['photo'] ?? '';
$student_level      = $phRow['education_level'] ?? null;
$student_year_level = $phRow['year_level'] ?? null;
$student_username   = $phRow['username'] ?? '';
$student_email       = $phRow['email'] ?? '';

// Same JHS/SHS/College -> year/grade level lists used at registration
// (student_register.php), reused here so Settings offers a self-edit
// dropdown that stays consistent with the values eligibility checks
// (teaching_assignments / user_year_levels) actually match against.
//
// NOTE: College values carry a " College" suffix (e.g. "3rd Year College")
// because that's the exact string the EA's Account Management assigns in
// teaching_assignments/user_year_levels for College-level targets -- a bare
// "3rd Year" (which is what student_register.php's own dropdown currently
// stores) does NOT match it and silently breaks the student's eligibility
// to evaluate Faculty/Staff assigned to that year level. JHS/SHS use plain
// "Grade N" on both sides, so no suffix is needed there.
$reg_year_levels = [
    'junior_high' => ['Grade 7','Grade 8','Grade 9','Grade 10'],
    'senior_high' => ['Grade 11','Grade 12'],
    'college'     => ['1st Year College','2nd Year College','3rd Year College','4th Year College'],
];
$year_level_options = $reg_year_levels[$student_level] ?? [];

// ── ROLE / ASSIGNMENT-BASED ELIGIBILITY ────────────────────────
// Only two personnel contexts are exposed to students:
//   • Faculty / Teacher / Teaching Staff -> matching teaching assignment.
//   • Staff / Non-teaching Staff          -> institution-wide Staff context.
// Principal and Dean are each handled as their own top-level category.
function normalizeLevelVariants($education_level) {
    $level_key = strtolower(trim($education_level ?? ''));
    $edu_bucket_map = [
        'elementary'             => ['basic education'],
        'junior_high'            => ['basic education'],
        'senior_high'            => ['basic education'],
        'basic education'        => ['basic education'],
        'college'                => ['college', 'higher education', 'college / university', 'college/university'],
        'higher education'       => ['college', 'higher education', 'college / university', 'college/university'],
        'college / university'   => ['college', 'higher education', 'college / university', 'college/university'],
        'college/university'     => ['college', 'higher education', 'college / university', 'college/university'],
    ];
    return array_values(array_unique(array_map('strtolower', $edu_bucket_map[$level_key] ?? [$level_key])));
}

function isMatchedViaAssignment($mysqli, $target_id, $level_variants, $student_year_level) {
    if (empty($level_variants) || $student_year_level === null || $student_year_level === '') return false;
    $placeholders = implode(',', array_fill(0, count($level_variants), '?'));
    $stmt = $mysqli->prepare(
        "SELECT 1 FROM (
            SELECT ta.user_id FROM teaching_assignments ta
            WHERE ta.user_id = ?
              AND LOWER(TRIM(ta.education_level)) IN ($placeholders)
              AND LOWER(TRIM(ta.year_level)) = LOWER(TRIM(?))
            UNION
            SELECT uyl.user_id FROM user_year_levels uyl
            WHERE uyl.user_id = ?
              AND LOWER(TRIM(uyl.year_level)) = LOWER(TRIM(?))
        ) AS matched"
    );
    $types  = 'i' . str_repeat('s', count($level_variants)) . 's' . 'is';
    $params = array_merge([$target_id], $level_variants, [$student_year_level, $target_id, $student_year_level]);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $matches = (bool)$stmt->get_result()->fetch_row();
    $stmt->close();
    return $matches;
}

function hasAnyYearLevelAssignment($mysqli, $target_id) {
    $stmt = $mysqli->prepare(
        "SELECT 1 FROM (
            SELECT user_id FROM user_year_levels WHERE user_id=?
            UNION ALL
            SELECT user_id FROM teaching_assignments WHERE user_id=?
        ) x LIMIT 1"
    );
    $stmt->bind_param('ii', $target_id, $target_id);
    $stmt->execute();
    $has_any = (bool)$stmt->get_result()->fetch_row();
    $stmt->close();
    return $has_any;
}

// "Teaching person" for evaluation_context='teacher' purposes: a Teacher
// (role/secondary_role literally 'teacher'), OR a Staff member who carries
// an actual teaching/year-level assignment ("teaching staff"). Mirrors
// admin/questionnaire.php's is_teaching_staff rule, where such a Staff
// account is placed under the Teacher bucket, not Staff. Every check that
// gates a 'teacher' evaluation_context must use this instead of the plain
// ec_has_teacher_function() role check, or a teaching-staff member shown
// under the Faculty tab will fail eligibility when they try to evaluate.
function isTeachingPerson($mysqli, array $u): bool {
    return ec_has_teacher_function($u)
        || (ec_has_staff_function($u) && hasAnyYearLevelAssignment($mysqli, (int)($u['id'] ?? 0)));
}

function isCollegeEducationLevel($education_level) {
    $v = strtolower(trim((string)$education_level));
    return in_array($v, [
        'college',
        'higher education',
        'college / university',
        'college/university'
    ], true);
}

/**
 * College Teacher evaluations are semester-specific.  When the current
 * student is a College student, a Teacher is eligible only when the
 * Teacher's assigned_period matches the active evaluation period's
 * semester.  Non-college student flows keep the existing year-level rule.
 */
function teacherMatchesActiveSemester($mysqli, $target_id, $student_level, $active_period_semester) {
    if (!isCollegeEducationLevel($student_level)) return true;
    if ($active_period_semester === null || trim((string)$active_period_semester) === '') return false;

    $stmt = $mysqli->prepare("SELECT assigned_period FROM users WHERE id=? LIMIT 1");
    $stmt->bind_param('i', $target_id);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    return $row
        && trim((string)($row['assigned_period'] ?? '')) === trim((string)$active_period_semester);
}

function canStudentEvaluateTarget(
    $mysqli,
    $student_level,
    $student_year_level,
    $target_id,
    $evaluation_context = '',
    $active_period_semester = null
) {
    $stmt = $mysqli->prepare(
        "SELECT role, secondary_role, designation, assigned_period, is_active
         FROM users
         WHERE id=?
         LIMIT 1"
    );

    $stmt->bind_param('i', $target_id);
    $stmt->execute();

    $target = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$target || !(int)$target['is_active']) {
        return [
            false,
            'This person is not available for evaluation.'
        ];
    }
    $target['id'] = $target_id;

    /*
     * SCHOOL HEAD
     *
     * A student only evaluates the school head of their own department:
     * College students evaluate the Dean only; Junior High / Senior High
     * students evaluate the Principal only. A student whose education
     * level is neither may evaluate no school head at all. This mirrors
     * the Dashboard's tab visibility, but is re-checked here so a manually
     * crafted POST (e.g. a College student's browser posting the
     * Principal's id) can never slip through.
     */
    if (in_array($target['role'], ['principal', 'dean'], true)) {
        if ($evaluation_context !== 'school_head' && $evaluation_context !== '') {
            return [false, null];
        }
        $is_college_student = isCollegeEducationLevel($student_level);
        $is_jhs_shs_student = in_array(strtolower(trim((string)$student_level)), ['junior_high', 'senior_high'], true);

        if ($target['role'] === 'dean' && !$is_college_student) {
            return [
                false,
                'This person is not available for evaluation.'
            ];
        }
        if ($target['role'] === 'principal' && !$is_jhs_shs_student) {
            return [
                false,
                'This person is not available for evaluation.'
            ];
        }

        return [true, null];
    }

    /*
     * DETERMINE AVAILABLE CONTEXTS
     *
     * $has_teacher covers both actual Teachers and "teaching staff" --
     * Staff members with a real teaching/year-level assignment -- so they
     * pass evaluation_context='teacher' eligibility, matching where they
     * now appear on the Dashboard (Faculty tab, not Staff).
     */
    $has_teacher = isTeachingPerson($mysqli, $target);
    $has_staff   = ec_has_staff_function($target);

    /*
     * TEACHER / STAFF CONTEXT VALIDATION
     */
    if ($evaluation_context === 'teacher' && !$has_teacher) {
        return [
            false,
            'This person is not configured for a Teacher evaluation.'
        ];
    }

    // College Teachers are tied to the active evaluation semester.  This
    // prevents a Teacher assigned to 2nd Semester from being evaluated by
    // a College student while the active period is 1st Semester (and vice
    // versa).  Staff context is intentionally unaffected.
    if ($evaluation_context === 'teacher'
        && $has_teacher
        && !teacherMatchesActiveSemester($mysqli, $target_id, $student_level, $active_period_semester)) {
        return [
            false,
            'This teacher is not assigned to the current evaluation semester.'
        ];
    }

    if ($evaluation_context === 'staff' && !$has_staff) {
        return [
            false,
            'This person is not configured for a Staff evaluation.'
        ];
    }

    // Staff context is non-teaching staff ONLY (mirrors the Dashboard's
    // Staff tab / admin/questionnaire.php's isNonTeachingStaff rule). A
    // Staff member with an actual teaching/year-level assignment is
    // teaching staff -- evaluable only under 'teacher', never 'staff',
    // even when their assignment matches this student's year level.
    if ($evaluation_context === 'staff' && $has_staff && hasAnyYearLevelAssignment($mysqli, $target_id)) {
        return [
            false,
            'This person is not configured for a Staff evaluation.'
        ];
    }

    /*
     * If no context was explicitly supplied, determine the primary
     * context from the person's available functions.
     */
    if ($evaluation_context === '') {
        $evaluation_context = $has_teacher
            ? 'teacher'
            : 'staff';
    }

    /*
     * Student account must have education level and year level.
     */
    if (!$student_level || !$student_year_level) {
        return [
            false,
            'Your education level / year level is not set on your account. Please contact the registrar.'
        ];
    }

    /*
     * Check whether the target has ANY teaching/year-level assignment.
     */
    $has_assignment = hasAnyYearLevelAssignment(
        $mysqli,
        $target_id
    );

    /*
     * STAFF WITH NO ASSIGNMENT
     *
     * Non-teaching staff are institution-wide.
     *
     * Teachers without an assignment are NOT automatically
     * available to everybody.
     */
    if (!$has_assignment) {

        if ($evaluation_context === 'staff' && $has_staff) {
            return [true, null];
        }

        return [
            false,
            'This teacher is not assigned to a year level.'
        ];
    }

    /*
     * TEACHER / TEACHING STAFF
     *
     * Must match student's education level + year level.
     */
    $matched = isMatchedViaAssignment(
        $mysqli,
        $target_id,
        normalizeLevelVariants($student_level),
        $student_year_level
    );

    return [
        $matched,
        $matched
            ? null
            : 'This person is not assigned to your education level / year level.'
    ];
}

// ── ACTIVE EVALUATION PERIOD ──────────────────────────────────
// Fetched once up front so the Dashboard, Guidelines, and the submit
// handler below all agree on whether evaluations are currently open.
$activePeriodRow = $mysqli->query("SELECT id, semester FROM evaluation_periods WHERE is_active=1 LIMIT 1")->fetch_assoc();
$period_is_open  = (bool)$activePeriodRow;
$active_period_semester = $activePeriodRow['semester'] ?? null;

// ── LEVEL-SCOPED PERIOD GATING ────────────────────────────────
// JH/SHS only evaluate once, at the end of the school year (a period
// whose semester = 'School Year'). College evaluates per-term (1st
// Semester / 2nd Semester / Summer). evaluation_periods.is_active is a
// single global flag with no level of its own, so activating a College
// term was also opening the JH/SHS window (and activating the School
// Year period was opening the College window). Close it again here
// whenever the active period's type doesn't match the student's own
// level -- every other use of $period_is_open below (dashboard status,
// card rendering, and the submit handler) reads this same variable, so
// gating it once here is enough.
if ($period_is_open) {
    $is_college_student    = isCollegeEducationLevel($student_level);
    $is_school_year_period = trim((string)$active_period_semester) === 'School Year';
    if ($is_college_student === $is_school_year_period) {
        $period_is_open = false;
    }
}
$ctxCol=$mysqli->query("SHOW COLUMNS FROM evaluation_tracker LIKE 'evaluation_context'");
if ($ctxCol && $ctxCol->num_rows===0) {
    $mysqli->query("ALTER TABLE evaluation_tracker ADD COLUMN evaluation_context VARCHAR(30) NOT NULL DEFAULT 'teacher'");
    $mysqli->query("UPDATE evaluation_tracker et JOIN users u ON u.id=et.target_user_id SET et.evaluation_context=CASE WHEN u.role IN ('principal','dean') THEN 'school_head' WHEN u.role='staff' THEN 'staff' WHEN u.role='teacher' THEN 'teacher' ELSE et.evaluation_context END");
}

// ── HANDLE EVALUATION SUBMISSION ─────────────────────────────
$submit_error   = '';
$submit_success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_evaluation'])) {
    $target_id = intval($_POST['target_user_id']);
    $evaluation_context = strtolower(trim($_POST['evaluation_context'] ?? 'teacher'));
    if (!in_array($evaluation_context,['teacher','staff','school_head'],true)) $evaluation_context='teacher';

    // SERVER-SIDE ELIGIBILITY RE-CHECK. The Evaluate view only ever renders
    // buttons for people already filtered into $all_users below, but
    // nothing previously stopped a POST with an arbitrary target_user_id
    // from being submitted directly, bypassing that filter entirely.
    // canStudentEvaluateTarget() (defined above) re-runs the EXACT same
    // rule the display query uses -- Teacher/Teaching-Staff must match
    // the student's education level + year level via teaching_assignments
    // or user_year_levels; non-teaching staff (zero user_year_levels
    // rows, ever) is always eligible; Principal/Dean is a singleton
    // bypass -- so a manually changed target_id can never slip through
    // with a person the student isn't actually authorized to evaluate.
    [$eligible, $eligibility_result] = ($target_id > 0) ? canStudentEvaluateTarget($mysqli,$student_level,$student_year_level,$target_id,$evaluation_context,$active_period_semester) : [false,null];
    $ctxStmt=$mysqli->prepare("SELECT role,secondary_role,designation FROM users WHERE id=? AND is_active=1 LIMIT 1");
    $ctxStmt->bind_param('i',$target_id); $ctxStmt->execute(); $ctxRow=$ctxStmt->get_result()->fetch_assoc(); $ctxStmt->close();
    if ($ctxRow) $ctxRow['id'] = $target_id;
    $has_teacher_context=$ctxRow && isTeachingPerson($mysqli,$ctxRow);
    $has_staff_context=$ctxRow && ec_has_staff_function($ctxRow);
    $context_allowed=($evaluation_context==='teacher'&&$has_teacher_context)||($evaluation_context==='staff'&&$has_staff_context)||($evaluation_context==='school_head'&&$ctxRow&&in_array($ctxRow['role'],['principal','dean'],true));

    if ($target_id <= 0) {
        $submit_error = "Invalid submission data. Please try again.";
    } elseif (!$eligible) {
        $submit_error = $eligibility_result;
    } elseif (!$context_allowed) {
        $submit_error = 'This evaluation context is not available for this person.';
    } elseif (!$period_is_open) {
        $submit_error = "No evaluation period is currently open. Please check back later.";
    } else {
        $period_id = (int)$activePeriodRow['id'];

        $chk = $mysqli->prepare(
            "SELECT id FROM evaluation_tracker WHERE evaluator_id=? AND target_user_id=? AND period_id=? AND evaluation_context=?"
        );
        $chk->bind_param("iiis",$student_id,$target_id,$period_id,$evaluation_context);
        $chk->execute();
        $chk->store_result();
        $already_done = $chk->num_rows > 0;
        $chk->close();

        if ($already_done) {
            $submit_error = "You have already evaluated this person this period.";
        } else {
            $ratings = $_POST['rating'] ?? [];
            $comment = trim($_POST['comment'] ?? '');
            if (empty($ratings)) {
                $submit_error = "Please answer all questions before submitting.";
            } else {
                try {
                    $mysqli->begin_transaction();

                    $trk = $mysqli->prepare(
                        "INSERT INTO evaluation_tracker (evaluator_id,target_user_id,remarks,eval_type,period_id,evaluation_context,status,submitted_at) VALUES (?, ?, ?, 'student', ?, ?, 'submitted', NOW())"
                    );
                    $trk->bind_param("iisis",$student_id,$target_id,$comment,$period_id,$evaluation_context);
                    $trk->execute();
                    $tracker_id = $mysqli->insert_id;
                    $trk->close();

                    // NOTE: `rating[q_id]` keys come straight from whichever
                    // table loadQuestions() pulled them from (evaluation_questions
                    // Faculty/Teacher questions come from evaluation_questions;
                    // Staff questions come from user_questions. The answer stores
                    // the source so the question id spaces remain unambiguous.
$ins = $mysqli->prepare(
    "INSERT INTO questionnaire_answers
     (
        tracker_id,
        question_id,
        question_source,
        user_question_id,
        answer_score,
        submitted_at
     )
     VALUES (?, ?, ?, ?, ?, NOW())"
);

foreach ($ratings as $question_key => $rating) {

    /*
     * Question keys now look like:
     *   evaluation:15
     *   user:7
     */

    $parts = explode(':', $question_key, 2);

    if (count($parts) !== 2) {
        throw new Exception("Invalid question reference.");
    }

    $source = $parts[0];
    $q_id   = intval($parts[1]);

    if (!in_array($source, ['evaluation', 'user'], true) || $q_id <= 0) {
        throw new Exception("Invalid question reference.");
    }

    $score = max(1, min(5, floatval($rating)));

    $question_id      = null;
    $user_question_id = null;

    if ($source === 'evaluation') {
        $question_id = $q_id;
    } else {
        $user_question_id = $q_id;
    }

    $ins->bind_param(
        "iisid",
        $tracker_id,
        $question_id,
        $source,
        $user_question_id,
        $score
    );

    if (!$ins->execute()) {
        throw new Exception(
            "Failed to save questionnaire answer: " . $ins->error
        );
    }
}

$ins->close();

                    $mysqli->commit();
                    $submit_success = "Evaluation submitted successfully. Thank you!";

                } catch (Exception $e) {
                    $mysqli->rollback();
                    $submit_error = "Submission failed: " . $e->getMessage();
                }
            }
        }
    }
}

// ── HANDLE SETTINGS: PROFILE DETAILS ─────────────────────────
$profile_error   = '';
$profile_success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    if (!csrf_valid($_POST['csrf_token'] ?? '')) {
        $profile_error = "Your session expired. Please refresh the page and try again.";
    } else {
        $new_full_name  = trim($_POST['full_name'] ?? '');
        $new_username   = trim($_POST['username'] ?? '');
        $new_year_level = trim($_POST['year_level'] ?? '');
        $new_email      = trim($_POST['email'] ?? '');

        if ($new_full_name === '') {
            $profile_error = "Full name is required.";
        } elseif (mb_strlen($new_full_name) > 100) {
            $profile_error = "Full name is too long.";
        } elseif ($new_username === '') {
            $profile_error = "Username is required.";
        } elseif (mb_strlen($new_username) > 50) {
            $profile_error = "Username is too long.";
        } elseif (preg_match('/\s/', $new_username)) {
            $profile_error = "Username cannot contain spaces.";
        } elseif (!empty($year_level_options) && !in_array($new_year_level, $year_level_options, true)) {
            $profile_error = "Please select a valid year/grade level.";
        } elseif ($new_email !== '' && !filter_var($new_email, FILTER_VALIDATE_EMAIL)) {
            $profile_error = "Please enter a valid email address.";
        } elseif (mb_strlen($new_email) > 150) {
            $profile_error = "Email is too long.";
        } else {
            // Username must stay unique across all accounts, not just students.
            $dupChk = $mysqli->prepare("SELECT id FROM users WHERE username = ? AND id <> ? LIMIT 1");
            $dupChk->bind_param("si", $new_username, $student_id);
            $dupChk->execute();
            $dupChk->store_result();
            if ($dupChk->num_rows > 0) {
                $profile_error = "That username is already taken. Please choose another.";
                $dupChk->close();
            } else {
                $dupChk->close();
                $emailToSave = $new_email !== '' ? $new_email : null;
                $upd = $mysqli->prepare("UPDATE users SET full_name = ?, username = ?, year_level = ?, email = ? WHERE id = ? AND role = 'student' LIMIT 1");
                $upd->bind_param("ssssi", $new_full_name, $new_username, $new_year_level, $emailToSave, $student_id);
                $upd->execute();
                $upd->close();

                // Keep everything the rest of this page reads from in sync
                // for the current render, and refresh the session so the
                // sidebar/header (which read $_SESSION['full_name']) and the
                // next login (which reads username) reflect the change too.
                $student_name              = $new_full_name;
                $student_username          = $new_username;
                $student_year_level        = $new_year_level;
                $student_email             = $emailToSave ?? '';
                $_SESSION['full_name']     = $new_full_name;
                $profile_success           = "Profile updated.";
            }
        }
    }
}

// ── HANDLE SETTINGS: CHANGE PASSWORD ──────────────────────────
$password_error   = '';
$password_success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_password'])) {
    $current_password = $_POST['current_password'] ?? '';
    $new_password      = $_POST['new_password']      ?? '';
    $confirm_password  = $_POST['confirm_password']  ?? '';

    if (!csrf_valid($_POST['csrf_token'] ?? '')) {
        $password_error = "Your session expired. Please refresh the page and try again.";
    } elseif (auth_is_locked($mysqli, 'pwchange', (string)$student_id)) {
        $password_error = AUTH_LOCK_MESSAGE;
    } else {
        $pwStmt = $mysqli->prepare("SELECT username, password_hash FROM users WHERE id = ? AND role = 'student' LIMIT 1");
        $pwStmt->bind_param("i", $student_id);
        $pwStmt->execute();
        $pwRow = $pwStmt->get_result()->fetch_assoc();
        $pwStmt->close();

        if (!$pwRow || !password_verify($current_password, $pwRow['password_hash'] ?? '')) {
            auth_record($mysqli, 'pwchange', (string)$student_id, false);
            $password_error = "Current password is incorrect.";
        } elseif (strlen($new_password) < 8) {
            $password_error = "New password must be at least 8 characters.";
        } elseif (strlen($new_password) > 72) {
            $password_error = "New password must be 72 characters or fewer.";
        } elseif (mb_strtolower($new_password) === mb_strtolower($pwRow['username'])) {
            $password_error = "Your password cannot be the same as your username.";
        } elseif ($new_password !== $confirm_password) {
            $password_error = "New passwords do not match.";
        } elseif (password_verify($new_password, $pwRow['password_hash'] ?? '')) {
            $password_error = "New password must be different from your current password.";
        } else {
            auth_clear($mysqli, 'pwchange', (string)$student_id);
            $newHash = password_hash($new_password, PASSWORD_DEFAULT);
            $upd = $mysqli->prepare("UPDATE users SET password_hash = ? WHERE id = ? AND role = 'student' LIMIT 1");
            $upd->bind_param("si", $newHash, $student_id);
            $upd->execute();
            $upd->close();
            $password_success = "Password updated successfully.";
        }
    }
}

$land_on_settings = ($profile_error || $profile_success || $password_error || $password_success);

// ── FETCH QUESTIONS FOR A TARGET (AJAX) ──────────────────────
// Reads from the same question source the admin assigns for this
// person's student-evaluation context:
//   - Staff   -> user_questions (per-person, target_type='Staff')
//   - Teacher -> evaluation_questions (shared pool, target_type='Teacher')
if (isset($_GET['get_questions'])) {
    header('Content-Type: application/json');
    try {
        $target_id = intval($_GET['target_id']);

        $userRow = $mysqli->query(
            "SELECT designation, role, secondary_role FROM users WHERE id=$target_id AND is_active=1 LIMIT 1"
        )->fetch_assoc();

        if (!$userRow) throw new Exception("User not found.");
        $userRow['id'] = $target_id;

        $designation = $userRow['designation'] ?? '';
        $role        = $userRow['role'] ?? 'teacher';

        // ── PRINCIPAL / DEAN → SCHOOL HEAD ───────────────────────
        // Questionnaire stores Principal and Dean as per-user question sets.
        // IMPORTANT: this must read the STUDENT-EVALUATION pool
        // (eval_type='student'), which is what the EA assigns under
        // Questionnaire → Student Evaluation → School Head → Principal/Dean.
        // eval_type='school_head' is a DIFFERENT, unrelated pool used only
        // when the EA evaluates the Principal/Dean directly (Questionnaire
        // → School Head Evaluation). Reading that pool here was the bug:
        // it made students see questions the EA never assigned for student
        // evaluation, just because the EA's-own-evaluation pool happened
        // to have rows in it.
        if (in_array($role, ['principal', 'dean'], true)) {
            // Same department rule as canStudentEvaluateTarget(): College
            // students may only fetch Dean questions, JHS/SHS students may
            // only fetch Principal questions. Without this check, a College
            // student could still pull up the Principal's question set (or
            // vice versa) by requesting this endpoint directly with the
            // right target_id, even though that tab is hidden from them.
            $is_college_student = isCollegeEducationLevel($student_level);
            $is_jhs_shs_student = in_array(strtolower(trim((string)$student_level)), ['junior_high', 'senior_high'], true);
            if ($role === 'dean' && !$is_college_student) {
                throw new Exception('This person is not available for evaluation.');
            }
            if ($role === 'principal' && !$is_jhs_shs_student) {
                throw new Exception('This person is not available for evaluation.');
            }

            $pd_target_type = ucfirst($role);
            $pdq = $mysqli->prepare(
                "SELECT id, question_text, category,
                        'user' AS question_source
                 FROM user_questions
                 WHERE user_id = ? AND target_type = ? AND eval_type = 'general'
                 ORDER BY category, id"
            );
            $pdq->bind_param("is", $target_id, $pd_target_type);
            $pdq->execute();
            $pd_questions = $pdq->get_result()->fetch_all(MYSQLI_ASSOC);
            $pdq->close();

            if (empty($pd_questions)) {
                throw new Exception(
                    "No questions have been set up for this person yet. " .
                    "Please ask the admin to add questions under Questionnaire → Dean / Principal → $pd_target_type."
                );
            }

            echo json_encode(['success' => true, 'questions' => $pd_questions]);
            exit;
        }

        $requested_context = $_GET['context'] ?? '';
        $has_teacher_context = isTeachingPerson($mysqli, $userRow);
        $has_staff_context   = ec_has_staff_function($userRow);
        $has_assignment      = hasAnyYearLevelAssignment($mysqli, $target_id);

        if ($requested_context === 'staff') {
            // Staff context is non-teaching staff only.
            if (!$has_staff_context || $has_assignment) {
                throw new Exception('This person is not configured for a Staff evaluation.');
            }
            $q = $mysqli->prepare(
                "SELECT id,question_text,category,'user' AS question_source
                 FROM user_questions
                 WHERE user_id=? AND target_type='Staff' AND eval_type='general'
                 ORDER BY category,id"
            );
            $q->bind_param('i',$target_id);
            $q->execute();
            $questions=$q->get_result()->fetch_all(MYSQLI_ASSOC);
            $q->close();
            if (empty($questions)) throw new Exception('No Staff questions have been set up for this person yet.');
            echo json_encode(['success'=>true,'questions'=>$questions]); exit;
        }

        // Default personnel context is Teacher / Faculty.
        if ($requested_context !== 'teacher') {
            throw new Exception('Invalid evaluation context.');
        }
        if (!$has_teacher_context) {
            throw new Exception('This person is not configured for a Teacher evaluation.');
        }
        $q = $mysqli->prepare(
            "SELECT id,question_text,category,'evaluation' AS question_source
             FROM evaluation_questions
             WHERE target_type='Faculty' AND eval_type='general'
             ORDER BY category,id"
        );
        $q->execute();
        $questions=$q->get_result()->fetch_all(MYSQLI_ASSOC);
        $q->close();
        if (empty($questions)) throw new Exception('No Teacher questions have been set up yet.');

        echo json_encode(['success'=>true,'questions'=>$questions]);
        exit;
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['success'=>false,'error'=>$e->getMessage()]);
    }
    exit;
}


// ── FETCH EVALUATEES ──────────────────────────────────────────────
// Build contexts per person instead of first filtering people and then
// guessing their category.  This is the authoritative student visibility rule.
// Faculty, Staff, Principal, and Dean are all independent top-level
// categories, each shown as its own tab.
$grouped = ['Faculty'=>[], 'Staff'=>[]];

$ures = $mysqli->query("SELECT id, full_name, designation, photo, role, secondary_role, assigned_period
                        FROM users
                        WHERE role IN ('teacher','staff','faculty')
                          AND is_active=1
                          AND (account_status='approved' OR source='admin_nologin')
                        ORDER BY full_name ASC");
if (!$ures) throw new Exception('Failed to load evaluation personnel: '.$mysqli->error);

$level_variants = normalizeLevelVariants($student_level);
foreach ($ures->fetch_all(MYSQLI_ASSOC) as $u) {
    $has_teacher = ec_has_teacher_function($u);
    $has_staff   = ec_has_staff_function($u);
    $has_assign  = hasAnyYearLevelAssignment($mysqli, (int)$u['id']);
    $base_match  = ($student_level && $student_year_level && $has_assign)
        ? isMatchedViaAssignment($mysqli, (int)$u['id'], $level_variants, $student_year_level)
        : false;

    // Faculty includes actual Teachers and Staff members who carry a real
    // teaching/year-level assignment. College faculty also respects the
    // active semester.
    $semester_match = teacherMatchesActiveSemester($mysqli, (int)$u['id'], $student_level, $active_period_semester);
    $is_teaching_person = $has_teacher || ($has_staff && $has_assign);
    if ($is_teaching_person && $base_match && $semester_match) {
        $grouped['Faculty'][] = $u;
    }

    // Staff means non-teaching staff only. A person with a teaching
    // assignment belongs in Faculty, not Staff.
    if ($has_staff && !$has_assign) {
        $grouped['Staff'][] = $u;
    }
}

// ── SINGLETON PRINCIPAL / DEAN CATEGORIES ─────────────────────
// Principal and Dean aren't tied to teaching_assignments/user_year_levels
// like Teacher/Staff -- there's exactly ONE active user per role. Unlike
// Teacher/Staff though, they are NOT both open to every student: a
// student only ever evaluates the school head of their own department.
//   • College students          -> Dean only. Principal must not appear.
//   • Junior High / Senior High -> Principal only. Dean must not appear.
// A student whose education_level is neither (e.g. unset) sees neither
// tab -- there's no school head defined for them to evaluate.
// Each eligible role gets its own top-level category (Principal, Dean),
// shown as its own tab alongside Faculty and Staff, with the person's
// `designation` stamped as their role ("Principal"/"Dean") so they're
// distinguishable on the card -- but only when designation is blank, so
// an admin-set title (e.g. "Principal, Senior High") isn't clobbered.
$is_college_student = isCollegeEducationLevel($student_level);
$is_jhs_shs_student  = in_array(strtolower(trim((string)$student_level)), ['junior_high', 'senior_high'], true);
$eligible_school_head_roles = [];
if ($is_jhs_shs_student)  $eligible_school_head_roles['principal'] = 'Principal';
if ($is_college_student)  $eligible_school_head_roles['dean']      = 'Dean';

foreach ($eligible_school_head_roles as $role_value => $role_label) {
    $singleton = $mysqli->prepare(
        "SELECT id, full_name, designation, photo, role
         FROM users
         WHERE role = ? AND is_active = 1
         LIMIT 1"
    );
    $singleton->bind_param("s", $role_value);
    $singleton->execute();
    $singleton_row = $singleton->get_result()->fetch_assoc();
    $singleton->close();

    if ($singleton_row) {
        if (trim($singleton_row['designation'] ?? '') === '') {
            $singleton_row['designation'] = $role_label;
        }
        $all_users[]            = $singleton_row;
        $grouped[$role_label]   = [$singleton_row];
    }
}

// ── QUESTION-SET AVAILABILITY PRECHECK ─────────────────────────
// A person with zero configured questions for their evaluation context
// used to still show an "Evaluate" button -- the student would open the
// modal, hit a dead-end "No questions have been set up..." error, and
// nothing would ever get submitted, with no signal to the student (or
// the admin) that anything was wrong. This stamps `_has_questions` onto
// every person in $grouped so the button can be disabled up front
// instead of failing after the fact.
//
// Teacher/Faculty draws from the single shared evaluation_questions pool
// (target_type='Teacher', eval_type='student'), so one count covers
// everyone in that group. Staff, Principal, and Dean are per-person sets
// in user_questions, so they're looked up individually.
$teacherQCount = $mysqli->query(
    "SELECT COUNT(*) AS c FROM evaluation_questions WHERE target_type='Faculty' AND eval_type='general'"
)->fetch_assoc()['c'] ?? 0;
$facultyHasQuestions = $teacherQCount > 0;

$perUserQCounts = [];
$puq = $mysqli->query(
    "SELECT user_id, target_type, COUNT(*) AS c
     FROM user_questions
     WHERE eval_type='general' AND target_type IN ('Staff','Principal','Dean')
     GROUP BY user_id, target_type"
);
if ($puq) {
    while ($row = $puq->fetch_assoc()) {
        $perUserQCounts[(int)$row['user_id']][$row['target_type']] = (int)$row['c'];
    }
}

if (!empty($grouped['Faculty'])) {
    foreach ($grouped['Faculty'] as &$fp) { $fp['_has_questions'] = $facultyHasQuestions; }
    unset($fp);
}
if (!empty($grouped['Staff'])) {
    foreach ($grouped['Staff'] as &$sp) {
        $sp['_has_questions'] = ($perUserQCounts[(int)$sp['id']]['Staff'] ?? 0) > 0;
    }
    unset($sp);
}
foreach (['Principal', 'Dean'] as $pd_label) {
    if (!empty($grouped[$pd_label])) {
        foreach ($grouped[$pd_label] as &$hp) {
            $hp['_has_questions'] = ($perUserQCounts[(int)$hp['id']][$pd_label] ?? 0) > 0;
        }
        unset($hp);
    }
}

// Drop any category that ended up with nobody in it, so students only
// see categories with people in them. Faculty, Staff, Principal, and
// Dean are all independent -- any one can be empty while the others
// still show.
if (empty($grouped['Faculty']))   unset($grouped['Faculty']);
if (empty($grouped['Staff']))     unset($grouped['Staff']);
if (empty($grouped['Principal'])) unset($grouped['Principal']);
if (empty($grouped['Dean']))      unset($grouped['Dean']);

// ── FETCH ALREADY EVALUATED IDs (CURRENT PERIOD ONLY) ─────────
// evaluation_tracker.period_id scopes a submission to one evaluation
// period, and the submit handler's dedupe check above is already
// period-scoped (evaluator_id + target_user_id + period_id). But this
// lookup previously had NO period_id filter -- it pulled every person
// this student has EVER evaluated, in any past period. That meant once
// a period ended and a new one opened, every teacher/staff member would
// still show as "Done" (Evaluate button hidden, Completed/Pending/
// Progress stuck) forever, even though a fresh evaluation for the new
// period was both expected and allowed by the backend. Scoping this to
// $activePeriodRow's id makes "Done" reset for each new period.
$done_ids=[];
if($period_is_open){
    $current_period_id=(int)$activePeriodRow['id'];
    $dres=$mysqli->prepare("SELECT target_user_id,evaluation_context FROM evaluation_tracker WHERE evaluator_id=? AND period_id=?");
    $dres->bind_param('ii',$student_id,$current_period_id); $dres->execute(); $dres->bind_result($done_target_id,$done_context);
    while($dres->fetch()) $done_ids[$done_target_id.'|'.($done_context?:'teacher')]=true;
    $dres->close();
}

// ── FETCH EVALUATION HISTORY (for the History view) ──────────
// One row per past submission by this student, with the target's
// name/photo/designation and the average score they gave, so the
// History view doesn't need another round trip.
$history = [];
$hres = $mysqli->prepare(
    "SELECT et.id, et.target_user_id, u.full_name, u.designation, u.photo,
            et.remarks, et.status, et.submitted_at,
            AVG(qa.answer_score) AS avg_score, COUNT(qa.id) AS answer_count
     FROM evaluation_tracker et
     JOIN users u ON u.id = et.target_user_id
     LEFT JOIN questionnaire_answers qa ON qa.tracker_id = et.id
     WHERE et.evaluator_id = ?
     GROUP BY et.id
     ORDER BY et.submitted_at DESC"
);
$hres->bind_param("i", $student_id);
$hres->execute();
$history = $hres->get_result()->fetch_all(MYSQLI_ASSOC);
$hres->close();

// ── GROUP ICONS / COLORS ──
$group_icons = [
    'Faculty'            => 'fa-people-group',
    'Staff'              => 'fa-briefcase',
    'Principal'          => 'fa-user-tie',
    'Dean'               => 'fa-user-graduate',
];
$group_colors = [
    'Faculty'            => '#00E5FF',
    'Teacher'            => '#00E5FF',
    'Staff'              => '#10b981',
    'Principal'          => '#8B5CF6',
    'Dean'               => '#F59E0B',
];

// Progress counts evaluation contexts, not unique accounts.
$total_evaluatees=count($grouped['Faculty']??[])+count($grouped['Staff']??[])+count($grouped['Principal']??[])+count($grouped['Dean']??[]);
$total_done=count($done_ids);
$total_pending     = max(0, $total_evaluatees - $total_done);
$pct              = $total_evaluatees > 0 ? round(($total_done / $total_evaluatees) * 100) : 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"/>
<meta name="viewport" content="width=device-width, initial-scale=1.0"/>
<title>PBI — Student Evaluation</title>
<link href="https://fonts.googleapis.com/css2?family=Rajdhani:wght@600;700&family=DM+Sans:wght@400;500;600&display=swap" rel="stylesheet"/>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css"/>
<link rel="stylesheet" href="student_ui.css"/>
<style>
:root{
    --dark:#0A192F;--mid:#172A45;--inner:#0F1F3D;
    --gold:#D97706;--gold-h:#F59E0B;
    --light:#E0E6F0;--muted:#A0B3C6;
    --border:rgba(255,255,255,0.08);--radius:10px;
    --sidebar-w:230px;
}
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0;}
body{font-family:'DM Sans',sans-serif;background:var(--dark);color:var(--light);min-height:100vh;}

/* ── TOPNAV ── */
.topnav{background:var(--mid);border-bottom:1px solid var(--border);padding:14px 32px;display:flex;align-items:center;justify-content:space-between;position:sticky;top:0;z-index:50;}
.nav-brand{display:flex;align-items:center;gap:12px;}
.hamburger-btn{display:none;background:none;border:1px solid var(--border);color:var(--light);font-size:16px;width:38px;height:38px;border-radius:8px;cursor:pointer;align-items:center;justify-content:center;}
.hamburger-btn:hover{border-color:var(--gold);color:var(--gold-h);}
.nav-logo{width:56px;height:56px;border-radius:50%;object-fit:cover;border:2px solid var(--gold);}
.nav-right{display:flex;align-items:center;gap:16px;}

/* Profile dropdown */
.nav-profile{position:relative;}
.profile-trigger{display:flex;align-items:center;gap:10px;cursor:pointer;padding:6px 10px;border-radius:var(--radius);transition:background .2s;}
.profile-trigger:hover{background:rgba(255,255,255,.06);}
.profile-avatar{width:36px;height:36px;border-radius:50%;object-fit:cover;border:2px solid var(--gold);flex-shrink:0;}
.profile-avatar-ph{width:36px;height:36px;border-radius:50%;background:var(--inner);border:2px solid var(--gold);display:flex;align-items:center;justify-content:center;color:var(--gold);font-size:15px;flex-shrink:0;}
.profile-name{font-size:13px;font-weight:600;color:var(--light);max-width:120px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;}
.profile-caret{font-size:11px;color:var(--muted);transition:transform .2s;}
.profile-dropdown{position:absolute;top:calc(100% + 8px);right:0;background:var(--mid);border:1px solid var(--border);border-radius:12px;width:240px;box-shadow:0 12px 40px rgba(0,0,0,.5);z-index:100;display:none;overflow:hidden;}
.profile-dropdown.open{display:block;animation:fadeDown .18s ease;}
@keyframes fadeDown{from{opacity:0;transform:translateY(-6px)}to{opacity:1;transform:none}}
.profile-dd-header{padding:16px;border-bottom:1px solid var(--border);display:flex;align-items:center;gap:12px;}
.profile-dd-avatar{width:44px;height:44px;border-radius:50%;object-fit:cover;border:2px solid var(--gold);flex-shrink:0;}
.profile-dd-avatar-ph{width:44px;height:44px;border-radius:50%;background:var(--inner);border:2px solid var(--gold);display:flex;align-items:center;justify-content:center;color:var(--gold);font-size:18px;flex-shrink:0;}
.profile-dd-name{font-size:13px;font-weight:700;color:#fff;line-height:1.3;}
.profile-dd-role{font-size:11px;color:var(--muted);text-transform:capitalize;}
.profile-dd-body{padding:10px;}
.profile-dd-btn{width:100%;padding:10px 12px;border-radius:8px;border:none;background:none;color:var(--light);font-size:13px;font-family:'DM Sans',sans-serif;cursor:pointer;display:flex;align-items:center;gap:10px;transition:background .18s;text-align:left;}
.profile-dd-btn:hover{background:rgba(255,255,255,.06);}
.profile-dd-btn i{width:16px;text-align:center;color:var(--muted);}
.profile-dd-divider{height:1px;background:var(--border);margin:6px 0;}
.profile-dd-btn.logout{color:#f87171;}
.profile-dd-btn.logout i{color:#f87171;}

/* ── PHOTO MODAL ── */
.photo-modal-overlay{position:fixed;inset:0;background:rgba(0,0,0,.75);z-index:300;display:none;align-items:center;justify-content:center;padding:20px;}
.photo-modal-overlay.open{display:flex;}
.photo-modal{background:var(--mid);border:1px solid var(--border);border-radius:18px;width:100%;max-width:400px;box-shadow:0 20px 60px rgba(0,0,0,.6);overflow:hidden;}
.photo-modal-header{padding:20px 24px 16px;border-bottom:1px solid var(--border);display:flex;align-items:center;justify-content:space-between;}
.photo-modal-title{font-family:'Rajdhani',sans-serif;font-size:20px;font-weight:700;color:#fff;}
.photo-modal-body{padding:24px;}
.photo-upload-circle{width:110px;height:110px;border-radius:50%;border:3px dashed rgba(217,119,6,.5);display:flex;align-items:center;justify-content:center;margin:0 auto 20px;cursor:pointer;transition:border-color .2s;overflow:hidden;position:relative;background:var(--inner);}
.photo-upload-circle:hover{border-color:var(--gold);}
.photo-upload-circle img{width:100%;height:100%;object-fit:cover;display:none;border-radius:50%;}
.photo-upload-circle .upload-icon{color:var(--muted);font-size:32px;transition:color .2s;}
.photo-upload-circle:hover .upload-icon{color:var(--gold);}
.photo-upload-hint{text-align:center;font-size:12px;color:var(--muted);margin-bottom:20px;}
.photo-upload-hint span{color:var(--gold-h);font-weight:600;cursor:pointer;}
.photo-modal-footer{padding:0 24px 24px;display:flex;gap:10px;}
.btn-save-photo{flex:1;padding:11px;background:var(--gold);border:none;border-radius:var(--radius);color:#fff;font-size:14px;font-weight:700;cursor:pointer;font-family:'DM Sans',sans-serif;}
.btn-save-photo:hover{background:var(--gold-h);}
.btn-skip-photo{flex:1;padding:11px;background:var(--inner);border:1px solid var(--border);border-radius:var(--radius);color:var(--muted);font-size:14px;font-weight:600;cursor:pointer;font-family:'DM Sans',sans-serif;}

/* ── APP SHELL: SIDEBAR + MAIN ── */
.app-shell{display:flex;align-items:flex-start;}

.sidebar{width:var(--sidebar-w);flex-shrink:0;background:var(--mid);border-right:1px solid var(--border);min-height:100vh;position:sticky;top:0;padding:20px 0;}
.sb-logo-wrap{display:flex;justify-content:center;padding:4px 0 14px;}
.sb-logo{width:60px;height:60px;border-radius:50%;object-fit:cover;border:2px solid var(--gold);box-shadow:0 0 18px rgba(217,119,6,.3);}
.sb-profile{text-align:center;padding:0 18px 18px;margin-bottom:12px;border-bottom:1px solid rgba(255,255,255,.08);}
.sb-profile-name{font-size:14px;font-weight:700;color:#fff;line-height:1.35;word-break:break-word;}
.sb-profile-role{font-size:11px;color:#8ea3bd;margin-top:3px;text-transform:capitalize;}
.side-section-label{font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:1.3px;color:var(--muted);padding:0 20px;margin-bottom:8px;}
.side-nav-item{display:flex;align-items:center;gap:12px;padding:11px 20px;color:var(--light);font-size:13.5px;font-weight:600;cursor:pointer;border-left:3px solid transparent;transition:all .18s;}
.side-nav-item i{width:18px;text-align:center;color:var(--muted);font-size:15px;transition:color .18s;}
.side-nav-item:hover{background:rgba(255,255,255,.05);}
.side-nav-item.active{background:rgba(217,119,6,.12);border-left-color:var(--gold);color:#fff;}
.side-nav-item.active i{color:var(--gold-h);}
.side-nav-badge{margin-left:auto;background:rgba(217,119,6,.2);color:var(--gold-h);font-size:10px;font-weight:700;border-radius:20px;padding:2px 8px;}

.sidebar-overlay{display:none;position:fixed;inset:0;background:rgba(0,0,0,.6);z-index:79;}
.sidebar-overlay.open{display:block;}

.main{flex:1;min-width:0;max-width:1000px;margin:0 auto;padding:36px 28px;}
.content-topbar{display:flex;align-items:center;margin-bottom:22px;}
.content-topbar .nav-profile{margin-left:auto;}
.page-title{font-family:'Rajdhani',sans-serif;font-size:28px;font-weight:700;color:#fff;margin-bottom:4px;}
.page-sub{font-size:13px;color:var(--muted);margin-bottom:32px;}

.view-content{display:none;}
.view-content.active{display:block;animation:fadeIn .18s ease;}
@keyframes fadeIn{from{opacity:0}to{opacity:1}}

/* ── DASHBOARD VIEW ── */
.stat-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(160px,1fr));gap:16px;margin-bottom:28px;}
.stat-card{background:var(--mid);border:1px solid var(--border);border-radius:var(--radius);padding:18px 20px;}
.stat-card i{font-size:18px;color:var(--gold-h);margin-bottom:10px;display:block;}
.stat-num{font-family:'Rajdhani',sans-serif;font-size:26px;font-weight:700;color:#fff;line-height:1;}
.stat-lbl{font-size:12px;color:var(--muted);margin-top:6px;}
.period-pill{display:inline-flex;align-items:center;gap:6px;font-size:11px;font-weight:700;border-radius:20px;padding:4px 12px;margin-bottom:20px;}
.period-pill.open{background:rgba(34,197,94,.15);border:1px solid rgba(34,197,94,.3);color:#4ade80;}
.period-pill.closed{background:rgba(240,84,84,.1);border:1px solid rgba(240,84,84,.3);color:#fca5a5;}
.dash-cta{background:var(--mid);border:1px solid var(--border);border-radius:14px;padding:26px;display:flex;align-items:center;gap:20px;flex-wrap:wrap;}
.dash-cta-icon{width:52px;height:52px;border-radius:12px;background:rgba(217,119,6,.15);display:flex;align-items:center;justify-content:center;font-size:22px;color:var(--gold-h);flex-shrink:0;}
.dash-cta-text{flex:1;min-width:180px;}
.dash-cta-text h3{font-family:'Rajdhani',sans-serif;font-size:17px;color:#fff;margin-bottom:3px;}
.dash-cta-text p{font-size:12.5px;color:var(--muted);}
.btn-primary-cta{padding:11px 22px;background:var(--gold);border:none;border-radius:var(--radius);color:#fff;font-size:13.5px;font-weight:700;cursor:pointer;white-space:nowrap;}
.btn-primary-cta:hover{background:var(--gold-h);}

.progress-wrap{background:var(--mid);border:1px solid var(--border);border-radius:var(--radius);padding:16px 20px;margin-bottom:28px;display:flex;align-items:center;gap:16px;flex-wrap:wrap;}
.progress-label{font-size:13px;color:var(--muted);white-space:nowrap;}
.progress-label span{color:var(--gold-h);font-weight:700;}
.progress-bar-bg{flex:1;min-width:120px;height:8px;background:rgba(255,255,255,.08);border-radius:20px;overflow:hidden;}
.progress-bar-fill{height:100%;background:linear-gradient(90deg,var(--gold),var(--gold-h));border-radius:20px;transition:width .4s ease;}
.progress-pct{font-size:12px;font-weight:700;color:var(--gold-h);white-space:nowrap;}
.alert{border-radius:8px;padding:12px 16px;font-size:13px;margin-bottom:20px;display:flex;align-items:center;gap:8px;}
.alert-success{background:rgba(34,197,94,.1);border:1px solid rgba(34,197,94,.3);color:#86efac;}
.alert-error{background:rgba(240,84,84,.1);border:1px solid rgba(240,84,84,.3);color:#fca5a5;}
.section-label{font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:1.5px;color:var(--muted);margin-bottom:14px;}

/* ── CATEGORY CARDS ── */
.category-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(190px,1fr));gap:16px;margin-bottom:10px;}
.cat-btn{background:var(--mid);border:1px solid var(--border);border-radius:14px;padding:24px 18px 20px;text-align:center;cursor:pointer;transition:all .22s;position:relative;user-select:none;}
.cat-btn:hover{transform:translateY(-3px);box-shadow:0 6px 20px rgba(0,0,0,.35);}
.cat-btn.active{transform:translateY(-3px);box-shadow:0 6px 20px rgba(0,0,0,.35);}
.cat-btn.all-done{opacity:.55;}
.cat-icon{font-size:32px;margin-bottom:11px;}
.cat-name{font-family:'Rajdhani',sans-serif;font-size:18px;font-weight:700;color:#fff;margin-bottom:3px;}
.cat-meta{font-size:12px;color:var(--muted);}
.cat-done-pill{margin-top:9px;display:inline-block;background:rgba(34,197,94,.15);border:1px solid rgba(34,197,94,.3);color:#4ade80;border-radius:20px;font-size:10px;font-weight:700;padding:2px 9px;}
.cat-active-arrow{position:absolute;bottom:-10px;left:50%;transform:translateX(-50%);width:0;height:0;border-left:9px solid transparent;border-right:9px solid transparent;border-top:10px solid var(--gold);display:none;filter:drop-shadow(0 2px 4px rgba(0,0,0,.4));}
.cat-btn.active .cat-active-arrow{display:block;}

/* ── MEMBERS PANEL ── */
.members-panel{display:none;border-radius:14px;margin-bottom:32px;overflow:hidden;animation:slideDown .22s ease;background:var(--mid);border:1px solid var(--border);}
.members-panel.open{display:block;}
@keyframes slideDown{from{opacity:0;transform:translateY(-8px)}to{opacity:1;transform:translateY(0)}}
.panel-header{padding:14px 20px;border-bottom:1px solid var(--border);display:flex;align-items:center;gap:10px;}
.panel-header-icon{font-size:16px;}
.panel-header-title{font-family:'Rajdhani',sans-serif;font-size:17px;font-weight:700;color:#fff;}
.panel-header-count{font-size:12px;color:var(--muted);margin-left:2px;}
.panel-close-btn{margin-left:auto;background:none;border:none;color:var(--muted);font-size:18px;cursor:pointer;padding:4px 8px;border-radius:6px;transition:color .2s;}
.panel-close-btn:hover{color:#fff;}

/* ── SUBGROUP (Teacher / Staff inside the Faculty panel) ── */
.subgroup-header{display:flex;align-items:center;gap:8px;padding:14px 18px 6px;font-family:'Rajdhani',sans-serif;font-size:14px;font-weight:700;color:#fff;}
.subgroup-header i{font-size:13px;}
.subgroup-header .subgroup-count{font-size:11px;font-weight:500;color:var(--muted);margin-left:2px;}
.subgroup-divider{height:1px;background:var(--border);margin:4px 18px 0;}

.members-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(160px,1fr));gap:14px;padding:10px 18px 18px;}
.person-card{background:var(--inner);border:1px solid var(--border);border-radius:12px;padding:18px 14px;text-align:center;transition:all .22s;position:relative;}
.person-card:hover:not(.done){border-color:var(--gold);transform:translateY(-2px);box-shadow:0 6px 20px rgba(0,0,0,.4);}
.person-card.done{opacity:.55;}
.person-avatar{width:64px;height:64px;border-radius:50%;object-fit:cover;border:2px solid var(--border);margin:0 auto 10px;display:block;background:var(--mid);}
.person-avatar-ph{width:64px;height:64px;border-radius:50%;background:var(--mid);border:2px solid var(--border);margin:0 auto 10px;display:flex;align-items:center;justify-content:center;color:var(--muted);font-size:22px;}
.person-name{font-size:13px;font-weight:600;color:#fff;margin-bottom:3px;line-height:1.3;}
.person-desig{font-size:11px;color:var(--muted);margin-bottom:2px;}
.person-teaching-line{font-size:10.5px;color:var(--gold-h);font-weight:600;margin-bottom:2px;}
.done-badge{position:absolute;top:9px;right:9px;background:rgba(34,197,94,.2);border:1px solid rgba(34,197,94,.4);color:#4ade80;border-radius:20px;font-size:10px;font-weight:700;padding:2px 8px;}
.eval-btn{margin-top:11px;width:100%;padding:8px;background:var(--gold);border:none;border-radius:7px;color:#fff;font-size:12px;font-weight:700;cursor:pointer;transition:background .2s;}
.eval-btn:hover{background:var(--gold-h);}
.eval-btn:disabled{background:var(--inner);color:var(--muted);cursor:not-allowed;}

/* ── REMINDER BANNER (Dean/EA → college students only) ── */
.reminder-banner{background:rgba(139,92,246,.1);border:1px solid rgba(139,92,246,.3);border-radius:12px;padding:14px 18px;margin-bottom:20px;display:flex;align-items:flex-start;gap:12px;}
.reminder-banner i.bell-ic{color:#a78bfa;font-size:16px;margin-top:2px;}
.reminder-banner .rb-body{flex:1;}
.reminder-banner .rb-title{font-size:13px;font-weight:700;color:#fff;margin-bottom:2px;}
.reminder-banner .rb-msg{font-size:12.5px;color:var(--muted);line-height:1.5;}
.reminder-banner .rb-meta{font-size:11px;color:#a78bfa;margin-top:4px;}
.reminder-banner .rb-dismiss{background:none;border:none;color:var(--muted);cursor:pointer;font-size:14px;padding:4px 6px;flex-shrink:0;}
.reminder-banner .rb-dismiss:hover{color:#fff;}

/* ── HISTORY VIEW ── */
.history-list{display:flex;flex-direction:column;gap:12px;}
.history-card{background:var(--mid);border:1px solid var(--border);border-radius:12px;padding:16px 18px;display:flex;align-items:center;gap:14px;flex-wrap:wrap;}
.history-avatar{width:48px;height:48px;border-radius:50%;object-fit:cover;border:2px solid var(--border);flex-shrink:0;}
.history-avatar-ph{width:48px;height:48px;border-radius:50%;background:var(--inner);border:2px solid var(--border);display:flex;align-items:center;justify-content:center;color:var(--muted);font-size:18px;flex-shrink:0;}
.history-info{flex:1;min-width:160px;}
.history-name{font-size:14px;font-weight:700;color:#fff;}
.history-desig{font-size:11.5px;color:var(--muted);}
.history-comment{font-size:12px;color:var(--muted);margin-top:4px;font-style:italic;max-width:420px;}
.history-meta{display:flex;flex-direction:column;align-items:flex-end;gap:4px;}
.history-score{display:flex;align-items:center;gap:6px;font-family:'Rajdhani',sans-serif;font-weight:700;color:var(--gold-h);font-size:15px;}
.history-date{font-size:11px;color:var(--muted);}

/* ── GUIDELINES VIEW ── */
.gl-card{background:var(--mid);border:1px solid var(--border);border-radius:12px;padding:20px 22px;margin-bottom:16px;}
.gl-card h3{font-family:'Rajdhani',sans-serif;font-size:16px;color:#fff;margin-bottom:10px;display:flex;align-items:center;gap:8px;}
.gl-card h3 i{color:var(--gold-h);}
.gl-card p, .gl-card li{font-size:13px;color:var(--muted);line-height:1.7;}
.gl-card ul{padding-left:18px;}
.gl-scale-row{display:flex;align-items:center;gap:10px;padding:6px 0;}
.gl-scale-num{width:26px;height:26px;border-radius:6px;background:var(--gold);color:#fff;font-weight:700;font-size:12px;display:flex;align-items:center;justify-content:center;flex-shrink:0;}

/* ── SETTINGS VIEW ── */
.settings-row{display:flex;align-items:center;gap:16px;flex-wrap:wrap;}
.settings-row .profile-dd-avatar,.settings-row .profile-dd-avatar-ph{width:64px;height:64px;font-size:24px;}
.settings-row .profile-dd-name{font-size:16px;}
.settings-info{flex:1;min-width:160px;}
.settings-grid{display:grid;grid-template-columns:1fr 1fr;gap:16px;margin-bottom:16px;}
.settings-field label{display:block;font-size:11px;font-weight:700;letter-spacing:.5px;text-transform:uppercase;color:var(--muted);margin-bottom:6px;}
.settings-field .pw-wrap{position:relative;}
.settings-field input,.settings-field select{width:100%;padding:11px 14px;background:var(--inner);border:1px solid var(--border);border-radius:9px;color:#fff;font-size:13.5px;}
.settings-field input:focus,.settings-field select:focus{outline:none;border-color:var(--gold-h);}
.settings-field input[readonly]{color:var(--muted);cursor:not-allowed;}
.settings-field .pw-wrap input{padding-right:40px;}
.settings-field .pw-toggle{position:absolute;right:12px;top:50%;transform:translateY(-50%);background:none;border:none;color:var(--muted);cursor:pointer;font-size:14px;}
.settings-field .pw-toggle:hover{color:#fff;}
.settings-hint{font-size:11.5px;color:var(--muted);margin:-8px 0 16px;}
@media(max-width:600px){.settings-grid{grid-template-columns:1fr;}}
.btn-logout{padding:11px 22px;background:rgba(220,38,38,.08);border:1px solid rgba(220,38,38,.25);border-radius:var(--radius);color:#dc2626;font-weight:700;font-size:13.5px;cursor:pointer;display:inline-flex;align-items:center;gap:8px;text-decoration:none;transition:background .2s;}
.btn-logout:hover{background:rgba(220,38,38,.15);}

/* ── EVAL MODAL ── */
.modal-overlay{position:fixed;inset:0;background:rgba(0,0,0,.75);z-index:200;display:none;align-items:center;justify-content:center;padding:20px;}
.modal-overlay.open{display:flex;}
.modal{background:var(--mid);border:1px solid var(--border);border-radius:18px;width:100%;max-width:780px;max-height:92vh;overflow-y:auto;box-shadow:0 20px 60px rgba(0,0,0,.6);}
.modal-header{padding:24px 28px 16px;border-bottom:1px solid var(--border);display:flex;align-items:center;gap:14px;position:sticky;top:0;background:var(--mid);z-index:1;}
.modal-avatar{width:52px;height:52px;border-radius:50%;object-fit:cover;border:2px solid var(--gold);}
.modal-avatar-ph{width:52px;height:52px;border-radius:50%;background:var(--inner);border:2px solid var(--border);display:flex;align-items:center;justify-content:center;color:var(--muted);font-size:20px;}
.modal-name{font-family:'Rajdhani',sans-serif;font-size:20px;font-weight:700;color:#fff;}
.modal-desig{font-size:12px;color:var(--muted);}
.modal-close{margin-left:auto;background:none;border:none;color:var(--muted);font-size:18px;cursor:pointer;padding:4px 8px;border-radius:6px;transition:color .2s;}
.modal-close:hover{color:#fff;}
.modal-body{padding:24px 28px;}
.q-category{font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:1.2px;color:var(--gold-h);margin:20px 0 10px;padding-bottom:6px;border-bottom:1px solid rgba(217,119,6,.18);}
.q-item{margin-bottom:18px;background:var(--inner);border-radius:10px;padding:14px 16px;}
.q-text{font-size:14px;color:var(--light);margin-bottom:12px;line-height:1.5;display:flex;gap:6px;align-items:flex-start;}
.q-num-badge{color:var(--gold-h);font-weight:700;font-size:14px;flex-shrink:0;min-width:22px;}
.rating-row{display:flex;gap:6px;align-items:center;flex-wrap:wrap;}
.r-btn{flex:1;min-width:66px;padding:8px 4px;background:rgba(255,255,255,.05);border:1px solid var(--border);border-radius:7px;color:var(--muted);font-size:13px;font-weight:700;cursor:pointer;transition:all .18s;text-align:center;}
.r-btn:hover{border-color:var(--gold);color:var(--gold-h);}
.r-btn.selected{background:var(--gold);border-color:var(--gold);color:#fff;}
.r-val{font-size:10px;display:block;margin-top:2px;font-weight:400;}
.comment-box{margin-top:24px;background:var(--inner);border-radius:10px;padding:16px;}
.comment-label{font-size:12px;font-weight:700;color:var(--gold-h);margin-bottom:10px;display:flex;align-items:center;gap:7px;}
.comment-optional{font-size:11px;color:var(--muted);font-weight:400;}
.comment-textarea{width:100%;background:var(--dark);border:1px solid var(--border);border-radius:8px;color:var(--light);padding:12px 14px;font-size:13px;font-family:'DM Sans',sans-serif;resize:vertical;outline:none;transition:border-color .2s;line-height:1.5;}
.comment-textarea:focus{border-color:var(--gold);}
.comment-textarea::placeholder{color:rgba(160,179,198,.4);}
.scale-legend{display:flex;gap:16px;flex-wrap:wrap;margin-bottom:18px;background:rgba(255,255,255,.03);border:1px solid var(--border);border-radius:8px;padding:10px 14px;}
.legend-item{font-size:11px;color:var(--muted);display:flex;align-items:center;gap:5px;}
.legend-dot{width:18px;height:18px;border-radius:4px;background:var(--gold);display:flex;align-items:center;justify-content:center;font-size:10px;font-weight:700;color:#fff;flex-shrink:0;}
.modal-footer{padding:16px 28px 24px;display:flex;gap:12px;}
.btn-submit{flex:1;padding:13px;background:var(--gold);border:none;border-radius:var(--radius);color:#fff;font-size:15px;font-weight:700;cursor:pointer;transition:background .2s;}
.btn-submit:hover{background:var(--gold-h);}
.btn-cancel-modal{padding:13px 22px;background:var(--inner);border:1px solid var(--border);border-radius:var(--radius);color:var(--light);font-size:14px;font-weight:600;cursor:pointer;transition:background .2s;}
.btn-cancel-modal:hover{background:rgba(255,255,255,.06);}
.loading-qs{text-align:center;padding:40px;color:var(--muted);}
.loading-qs i{font-size:28px;animation:spin 1s linear infinite;display:block;margin-bottom:10px;}
@keyframes spin{to{transform:rotate(360deg)}}
.empty{text-align:center;padding:48px 20px;color:var(--muted);}
.empty i{font-size:36px;margin-bottom:12px;display:block;opacity:.3;}

@media(max-width:900px){
    .sidebar{position:fixed;top:0;left:0;height:100vh;z-index:80;transform:translateX(-100%);transition:transform .22s ease;padding-top:20px;}
    .sidebar.open{transform:translateX(0);box-shadow:0 0 40px rgba(0,0,0,.5);}
    .hamburger-btn{display:flex;}
    .main{padding:24px 16px;max-width:100%;}
}
@media(max-width:600px){
    .category-grid{grid-template-columns:1fr;}
    .members-grid{grid-template-columns:repeat(auto-fill,minmax(130px,1fr));}
    .modal-body,.modal-header,.modal-footer{padding-left:18px;padding-right:18px;}
    .content-topbar{margin-bottom:16px;}
    .progress-wrap{flex-direction:column;align-items:flex-start;gap:8px;}
    .history-meta{align-items:flex-start;width:100%;}
}

/* Compact evaluation questionnaire table — rating cells are native radio+label
   inputs (same markup pattern as the EA evaluation table), not JS-toggled
   buttons. Colors/theme are unchanged from before. */
.eval-form-table{width:100%;border-collapse:collapse;table-layout:fixed}.eval-form-table th{background:rgba(255,255,255,.04);color:var(--muted);font-size:10px;font-weight:800;letter-spacing:.7px;text-transform:uppercase;text-align:center;padding:10px 6px;border-bottom:1px solid var(--border)}.eval-form-table th:first-child{text-align:left;width:auto;padding-left:14px}.eval-form-table th:not(:first-child){width:52px}.eval-form-table td{padding:10px 6px;border-bottom:1px solid rgba(255,255,255,.06);vertical-align:middle;text-align:center}.eval-form-table tr:last-child td{border-bottom:none}.eval-form-table td:first-child{text-align:left;padding-left:14px;padding-right:10px}.eval-form-wrap{background:var(--mid);border:1px solid var(--border);border-radius:12px;overflow:hidden;margin:0 0 16px}.eval-form-qtext{font-size:12.5px;line-height:1.45;color:var(--light)}.eval-form-qno{color:#00E5FF;font-weight:800;margin-right:6px}.eval-form-rating{display:flex;justify-content:center}.eval-form-rating input{position:absolute;opacity:0;pointer-events:none}.eval-form-rating label{width:34px;height:30px;display:flex;align-items:center;justify-content:center;border-radius:7px;border:1px solid var(--border);background:var(--inner);color:var(--muted);font-size:12px;font-weight:800;cursor:pointer;transition:all .15s ease}.eval-form-rating label:hover{border-color:#00E5FF;background:rgba(0,229,255,.08)}.eval-form-rating input:checked + label{background:#00E5FF;border-color:#00E5FF;color:#07131f}.eval-form-cat{font-size:10px;text-transform:uppercase;letter-spacing:.9px;font-weight:800;color:#00E5FF;margin:18px 0 8px}.eval-form-cat:first-child{margin-top:0}@media(max-width:700px){.eval-form-table th:not(:first-child){width:44px}.eval-form-rating label{width:28px;height:28px}.eval-form-qtext{font-size:11.5px}}

/* ══════════════════════════════════════════════════════════════
   LIGHT THEME OVERRIDE — matches the Principal/EA dashboards'
   white workspace + dark-navy amber sidebar look. Nothing above
   this block was removed -- every class, tab, icon and piece of
   text still renders exactly as before; only the colors change.
   ══════════════════════════════════════════════════════════════ */
:root{
    --dark:#ffffff;--mid:#ffffff;--inner:#f5f7fb;
    --light:#172033;--muted:#64748b;--border:#e2e8f0;
}
html{background:#FFFFFF;color-scheme:light;}
body{background:#FFFFFF!important;color:#172033!important;}

/* The sidebar stays the dark-navy "chrome" (matching the reference
   screenshot); the rest of the page -- including the logo, which now
   lives in the sidebar -- is part of the light workspace. The .main
   column itself is a pale inset panel (#F3F6FA) so it reads as a
   distinct workspace sitting on the pure-white outer page, with the
   white cards (--mid) floating a shade lighter on top of it. */
.sidebar{background:#0A192F!important;border-right:1px solid #172A45!important;}
.main{background:#F3F6FA;border-radius:22px;margin:20px auto;min-height:calc(100vh - 40px);}
@media(max-width:900px){.main{margin:16px;border-radius:18px;min-height:calc(100vh - 32px);}}
.hamburger-btn{color:#475569!important;border-color:#e2e8f0!important;}
.profile-name{color:#172033!important;}
.profile-caret{color:#64748b!important;}
.profile-trigger:hover{background:rgba(15,23,42,.05)!important;}
.side-section-label{color:#A0B3C6!important;}
.side-nav-item{color:#E0E6F0!important;}
.side-nav-item i{color:#A0B3C6!important;}
.side-nav-item.active i{color:var(--gold-h)!important;}

/* Headings/labels that were hardcoded to white text for the old dark
   cards now need to read dark-on-white on the new light cards. */
.page-title,.stat-num,.dash-cta-text h3,.cat-name,.panel-header-title,
.subgroup-header,.person-name,.history-name,.gl-card h3,.modal-name,
.photo-modal-title,.profile-dd-name,.reminder-banner .rb-title{color:#0f172a!important;}
.panel-close-btn:hover,.modal-close:hover,.reminder-banner .rb-dismiss:hover{color:#0f172a!important;}

/* Settings form fields (Profile Details / Change Password) were styled
   for the old dark card too -- white text on the new near-white input
   background is unreadable, so force dark text + a light-but-visible
   input background here as well. */
.settings-field label{color:#64748b!important;}
.settings-field input,.settings-field select{background:#ffffff!important;border:1px solid #cbd5e1!important;color:#0f172a!important;}
.settings-field input::placeholder{color:#94a3b8!important;}
.settings-field input:focus,.settings-field select:focus{border-color:#D97706!important;box-shadow:0 0 0 3px rgba(217,119,6,.12)!important;}
.settings-field input[readonly]{background:#eef2f7!important;border-color:#e2e8f0!important;color:#64748b!important;box-shadow:none!important;}
.settings-field .pw-toggle{color:#94a3b8!important;}
.settings-field .pw-toggle:hover{color:#0f172a!important;}
.settings-hint{color:#64748b!important;}

/* Status pills/badges used pale, low-opacity text meant for a dark
   backdrop -- darken them so they stay legible on white/near-white. */
.period-pill.open,.cat-done-pill,.done-badge,.alert-success{color:#16a34a!important;}
.period-pill.closed,.alert-error{color:#dc2626!important;}
.reminder-banner .rb-meta{color:#7c3aed!important;}

/* Fill in tracks/rows that were a faint white-on-dark wash and would
   otherwise vanish (white-on-white) now that their card is white. */
.progress-bar-bg{background:#e2e8f0!important;}
.scale-legend{background:#f8fafc!important;}
.r-btn{background:#f8fafc!important;}
.profile-dd-btn:hover{background:rgba(15,23,42,.05)!important;}
.eval-form-table th{background:#f8fafc!important;}
.eval-form-table td{border-bottom:1px solid #eef2f7!important;}

/* ══════════════════════════════════════════════════════════════
   ALIGNED STUDENT WORKSPACE
   Keeps the Student Dashboard's original dark navy + gold theme,
   while adopting the shared EA dashboard layout, spacing, hierarchy,
   and card structure.
   ══════════════════════════════════════════════════════════════ */
.app-shell{align-items:stretch;min-height:100vh;background:var(--dark);}
.sidebar{width:248px;min-height:100vh;padding:20px 0 18px;position:sticky;top:0;background:#0A192F!important;border-right:1px solid #172A45!important;box-shadow:6px 0 20px rgba(0,0,0,.16);z-index:20;}
.sb-profile-wrap{padding:0 18px 18px;margin-bottom:18px;border-bottom:1px solid rgba(255,255,255,.08);text-align:center;}
.sb-avatar-wrap{width:76px;height:76px;margin:0 auto 11px;border-radius:50%;padding:3px;background:linear-gradient(135deg,var(--gold-h),var(--gold));box-shadow:0 6px 18px rgba(217,119,6,.16);}
.sb-avatar{width:70px;height:70px;border-radius:50%;object-fit:cover;border:2px solid #0A192F;display:block;background:var(--inner);}
.sb-avatar-fallback{object-fit:contain;padding:5px;}
.sb-logo-wrap{display:none;}
.sb-profile-name{font-size:15px;font-weight:700;color:#fff;line-height:1.3;word-break:break-word;}
.sb-profile-role{font-size:10px;color:var(--gold-h);margin-top:4px;font-weight:800;letter-spacing:.6px;text-transform:uppercase;}
.side-section-label{font-size:9.5px;font-weight:800;letter-spacing:1.2px;color:#9FB2C7!important;padding:0 18px;margin:0 0 7px;text-transform:uppercase;}
.side-section-secondary{margin-top:19px;}
.side-nav-item{margin:2px 12px;padding:9px 12px;border-left:0;border-radius:8px;gap:10px;color:#DCE7F3!important;font-size:13px;font-weight:600;line-height:1.2;transition:all .18s ease;min-height:40px;}
.side-nav-item span:not(.side-nav-badge){color:inherit!important;}
.side-nav-item i{width:18px;color:#9FB2C7!important;font-size:14px;}
.side-nav-item:hover{background:rgba(255,255,255,.06)!important;color:#F7FAFC!important;}
.side-nav-item:hover i{color:#C7D6E6!important;}
.side-nav-item.active{background:rgba(217,119,6,.14)!important;color:#FFFFFF!important;border-left:0;box-shadow:inset 3px 0 0 var(--gold);}
.side-nav-item.active i{color:var(--gold-h)!important;}
.side-nav-badge{background:rgba(217,119,6,.20)!important;color:#FFD08A!important;padding:2px 7px;font-size:9px;}
.sb-profile-role{color:#C5D4E4!important;}
.main{flex:1;max-width:none;margin:18px 22px 18px 20px;padding:26px 26px 34px;background:var(--inner);border:1px solid var(--border);border-radius:22px;min-height:calc(100vh - 36px);box-shadow:0 0 0 1px rgba(255,255,255,.02);overflow:hidden;}
.content-topbar{display:block;margin-bottom:25px;}
.hamburger-btn{position:absolute;left:16px;top:18px;display:none;}
.workspace-heading{width:100%;}
.workspace-kicker{font-size:11px;font-weight:800;color:var(--muted);letter-spacing:.85px;text-transform:uppercase;margin-bottom:3px;display:flex;align-items:center;gap:6px;}
.workspace-kicker i{font-size:12px;color:var(--gold-h);}
.workspace-title-row{display:flex;align-items:flex-start;justify-content:space-between;gap:18px;}
.workspace-title{font-family:'Rajdhani',sans-serif;font-size:29px;font-weight:700;color:#fff;line-height:1.1;}
.workspace-subtitle{font-size:13px;color:var(--muted);margin-top:6px;}
.workspace-meta{display:flex;align-items:center;gap:10px;flex-wrap:wrap;justify-content:flex-end;}
.workspace-pill{display:inline-flex;align-items:center;gap:7px;padding:9px 13px;border:1px solid var(--border);border-radius:18px;background:var(--mid);color:var(--light);font-size:12px;font-weight:700;box-shadow:0 2px 6px rgba(0,0,0,.12);white-space:nowrap;}
.workspace-pill i{color:var(--gold-h);}
.workspace-pill.muted{background:rgba(255,255,255,.04);color:var(--muted);font-weight:600;}
.view-content.active{animation:fadeIn .16s ease;}
.dashboard-welcome{display:flex;align-items:flex-end;justify-content:space-between;gap:18px;margin-bottom:15px;}
.page-title{font-family:'Rajdhani',sans-serif;font-size:25px;line-height:1.2;font-weight:700;color:#fff!important;margin-bottom:4px;}
.page-sub{font-size:13px;color:var(--muted);margin-bottom:0;}
.year-level-badge{font-size:13px;font-weight:600;color:var(--muted);}
.period-pill{margin-bottom:0;padding:7px 13px;font-size:11px;border-radius:18px;white-space:nowrap;}
.period-pill.open{background:rgba(34,197,94,.13);border:1px solid rgba(34,197,94,.28);color:#4ade80!important;}
.period-pill.closed{background:rgba(240,84,84,.10);border:1px solid rgba(240,84,84,.25);color:#fca5a5!important;}
.stat-grid{grid-template-columns:repeat(4,minmax(0,1fr));gap:12px;margin-bottom:14px;}
.stat-card{background:var(--mid);border:1px solid var(--border);border-radius:12px;padding:16px 18px;box-shadow:0 5px 14px rgba(0,0,0,.16);min-height:102px;position:relative;overflow:hidden;}
.stat-card:after{content:"";position:absolute;inset:auto 0 0 0;height:2px;background:linear-gradient(90deg,rgba(217,119,6,.28),rgba(245,158,11,.04));}
.stat-card i{display:flex;align-items:center;justify-content:center;width:31px;height:31px;border-radius:9px;background:rgba(217,119,6,.14);color:var(--gold-h);margin-bottom:11px;font-size:14px;}
.stat-card:nth-child(1) i{background:rgba(217,119,6,.14);color:var(--gold-h);}
.stat-card:nth-child(2) i{background:rgba(74,222,128,.10);color:#4ade80;}
.stat-card:nth-child(3) i{background:rgba(250,204,21,.10);color:#facc15;}
.stat-card:nth-child(4) i{background:rgba(96,165,250,.10);color:#60a5fa;}
.stat-num{font-family:'Rajdhani',sans-serif;font-size:27px;font-weight:700;color:#fff!important;line-height:1;}
.stat-lbl{font-size:11px;color:var(--muted);margin-top:6px;}
.progress-wrap{background:var(--mid);border:1px solid var(--border);border-radius:12px;padding:14px 17px;margin-bottom:14px;gap:14px;box-shadow:0 5px 14px rgba(0,0,0,.14);}
.progress-label{font-size:12px;color:var(--muted);}
.progress-label span{color:var(--gold-h);}
.progress-bar-bg{height:9px;background:rgba(255,255,255,.08)!important;}
.progress-bar-fill{background:linear-gradient(90deg,var(--gold),var(--gold-h))!important;}
.progress-pct{font-size:11px;color:var(--gold-h);}
.dash-cta{background:var(--mid);border:1px solid var(--border);border-radius:14px;padding:19px 20px;gap:15px;box-shadow:0 5px 14px rgba(0,0,0,.15);}
.dash-cta-icon{width:46px;height:46px;border-radius:11px;background:rgba(217,119,6,.14);color:var(--gold-h);font-size:20px;}
.dash-cta-text h3{font-family:'Rajdhani',sans-serif;font-size:17px;font-weight:700;color:#fff!important;margin-bottom:4px;}
.dash-cta-text p{font-size:12px;color:var(--muted);}
.btn-primary-cta{padding:10px 18px;background:var(--gold);border-radius:9px;font-size:12.5px;box-shadow:0 4px 10px rgba(217,119,6,.18);}
.btn-primary-cta:hover{background:var(--gold-h);}
.alert{border:1px solid var(--border);background:var(--mid);box-shadow:0 5px 14px rgba(0,0,0,.12);}
.section-label{font-size:11px;font-weight:800;letter-spacing:1.1px;color:var(--muted);text-transform:uppercase;}
.category-grid{gap:12px;}
.cat-btn,.person-card,.members-panel,.history-card,.gl-card{background:var(--mid)!important;border-color:var(--border)!important;box-shadow:0 5px 14px rgba(0,0,0,.12);}
.cat-btn:hover,.person-card:hover{box-shadow:0 9px 20px rgba(0,0,0,.2);transform:translateY(-1px);}
.panel-header{background:var(--mid)!important;border-color:var(--border)!important;}
.modal,.photo-modal{background:var(--mid);border-color:var(--border);box-shadow:0 24px 60px rgba(0,0,0,.55);}
.modal-header,.photo-modal-header{background:var(--mid);border-color:var(--border);}
.modal-body,.photo-modal-body{background:var(--mid);}
.q-item,.comment-box{background:var(--inner)!important;}
.comment-textarea{background:var(--inner);color:var(--light);border-color:var(--border);}
.eval-form-wrap{background:var(--mid);border-color:var(--border);}
.eval-form-table th{background:var(--inner)!important;}
.eval-form-table td{border-bottom-color:var(--border)!important;}
@media(max-width:1100px){.workspace-title-row{flex-direction:column;}.workspace-meta{justify-content:flex-start;}.stat-grid{grid-template-columns:repeat(2,minmax(0,1fr));}}
@media(max-width:900px){
    .sidebar{position:fixed;left:0;top:0;height:100vh;transform:translateX(-100%);transition:transform .22s ease;padding-top:20px;}
    .sidebar.open{transform:translateX(0);box-shadow:12px 0 34px rgba(0,0,0,.45);}
    .hamburger-btn{display:flex;position:fixed;left:16px;top:16px;z-index:81;background:var(--mid)!important;border-color:var(--border)!important;color:var(--light)!important;}
    .main{margin:12px;border-radius:18px;min-height:calc(100vh - 24px);padding:72px 16px 28px;}
    .content-topbar{margin-bottom:18px;}
}
@media(max-width:600px){
    .stat-grid{grid-template-columns:1fr 1fr;gap:10px;}
    .workspace-title{font-size:24px;}
    .workspace-subtitle{font-size:12.5px;}
    .dashboard-welcome{align-items:flex-start;flex-direction:column;}
    .workspace-pill{padding:8px 10px;font-size:11px;}
    .dash-cta{align-items:flex-start;}
    .btn-primary-cta{width:100%;}
}
@media(max-width:430px){.stat-grid{grid-template-columns:1fr;}.workspace-meta{width:100%;}.workspace-pill{flex:1;justify-content:center;}}

/* ══════════════════════════════════════════════════════════════
   TEXT CONTRAST & CONSISTENCY FIX
   1) Sidebar: readable light-on-navy text with a clear hierarchy
      (section label < tab < hovered tab < active tab) and a cleaner
      active-tab highlight (the old amber wash turned muddy grey).
   2) Workspace: the "aligned workspace" block above re-applied white
      text to headings that now sit on the white/pale workspace, and
      used pale green/red/amber that wash out on white. Those are set
      back to dark, high-contrast tones here.
   ══════════════════════════════════════════════════════════════ */

/* ── Sidebar ── */
.sb-profile-name{color:#FFFFFF!important;letter-spacing:.1px;}
.sb-profile-role{color:#F5B54A!important;letter-spacing:.9px;}
.side-section-label{color:#8FA6BF!important;font-size:10px;letter-spacing:1.4px;}
.side-nav-item{color:#CBD8E8!important;font-weight:500;letter-spacing:.1px;}
.side-nav-item span:not(.side-nav-badge){color:inherit!important;}
.side-nav-item i{color:#8FA6BF!important;}
.side-nav-item:hover{background:rgba(255,255,255,.07)!important;color:#FFFFFF!important;}
.side-nav-item:hover i{color:#F5B54A!important;}
.side-nav-item.active{background:linear-gradient(90deg,rgba(245,158,11,.20),rgba(245,158,11,.05))!important;color:#FFFFFF!important;font-weight:700;box-shadow:inset 3px 0 0 var(--gold-h);}
.side-nav-item.active i{color:#FBBF24!important;}
.side-nav-badge{background:rgba(245,158,11,.22)!important;color:#FFD9A0!important;font-weight:800;font-size:10px;}
.side-nav-item:focus-visible{outline:2px solid #FBBF24;outline-offset:2px;}

/* ── Workspace (white / pale) ── */
.workspace-title,.page-title,.stat-num,.dash-cta-text h3{color:#0F172A!important;}
.workspace-subtitle,.page-sub,.stat-lbl,.dash-cta-text p,.year-level-badge,.progress-label{color:#475569!important;}
.workspace-kicker{color:#64748B!important;}
.workspace-kicker i,.workspace-pill i{color:#D97706!important;}
.workspace-pill{color:#1E293B!important;}
.workspace-pill.muted{color:#475569!important;background:#FFFFFF!important;}
.progress-label span,.progress-pct{color:#B45309!important;}
.period-pill.open{background:#DCFCE7!important;border-color:#86EFAC!important;color:#15803D!important;}
.period-pill.closed{background:#FEE2E2!important;border-color:#FCA5A5!important;color:#B91C1C!important;}
.stat-card:nth-child(1) i{background:#FEF3C7!important;color:#B45309!important;}
.stat-card:nth-child(2) i{background:#DCFCE7!important;color:#15803D!important;}
.stat-card:nth-child(3) i{background:#FEF9C3!important;color:#A16207!important;}
.stat-card:nth-child(4) i{background:#DBEAFE!important;color:#1D4ED8!important;}
.dash-cta-icon{background:#FEF3C7!important;color:#B45309!important;}

@media print{html,body{background:#fff!important;}}
</style>
</head>
<body>

<!-- PHOTO UPLOAD MODAL -->
<div class="photo-modal-overlay" id="photoModal">
    <div class="photo-modal">
        <div class="photo-modal-header">
            <div class="photo-modal-title">Profile Photo</div>
            <button style="background:none;border:none;color:var(--muted);font-size:18px;cursor:pointer;" onclick="closePhotoModal()">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>
        <div class="photo-modal-body">
            <form method="POST" action="update_photo.php" enctype="multipart/form-data" id="photoForm">
                <div class="photo-upload-circle" id="photoCircle" onclick="document.getElementById('photoFileInput').click()">
                    <img id="photoPreviewImg" src="<?= $student_photo ? '../image/'.htmlspecialchars($student_photo) : '' ?>"
                         style="<?= $student_photo ? 'display:block' : '' ?>"/>
                    <i class="fa-solid fa-camera upload-icon" id="uploadIconEl" style="<?= $student_photo ? 'display:none' : '' ?>"></i>
                </div>
                <div class="photo-upload-hint">
                    Click to choose a photo<br>
                    <span onclick="document.getElementById('photoFileInput').click()">Browse files</span>
                    &nbsp;·&nbsp; JPG, PNG, WebP · Max 10MB
                </div>
                <input type="file" id="photoFileInput" name="photo" accept="image/jpeg,image/png,image/webp,image/gif"
                       onchange="previewPhoto(this)" style="display:none"/>
            </form>
        </div>
        <div class="photo-modal-footer">
            <button class="btn-skip-photo" onclick="closePhotoModal()">Skip / Cancel</button>
            <button class="btn-save-photo" onclick="submitPhoto()"><i class="fa-solid fa-check"></i> Save Photo</button>
        </div>
    </div>
</div>

<div class="sidebar-overlay" id="sidebarOverlay" onclick="toggleSidebar()"></div>

<div class="app-shell">
    <aside class="sidebar" id="sidebar">
        <div class="student-sidebar-brand">
            <img src="../image/pbi_logo" alt="PBI"/>
            <div>
                <strong>Student Portal</strong>
                <span>Evaluation Workspace</span>
            </div>
        </div>
        <div class="sb-profile-wrap">
            <div class="sb-avatar-wrap">
                <?php if ($student_photo): ?>
                <img class="sb-avatar" src="../image/<?= htmlspecialchars($student_photo) ?>" alt=""/>
                <?php else: ?>
                <img class="sb-avatar sb-avatar-fallback" src="../image/pbi_logo" alt="PBI" onerror="this.style.display='none'"/>
                <?php endif; ?>
            </div>
            <div class="sb-profile-name"><?= htmlspecialchars($student_name) ?></div>
            <div class="sb-profile-role">STUDENT<?= $student_year_level ? ' · ' . htmlspecialchars($student_year_level) : '' ?></div>
        </div>

        <div class="side-section-label">Main</div>
        <div class="side-nav-item<?= $land_on_settings ? '' : ' active' ?>" id="nav-dashboard" onclick="switchView('dashboard')">
            <i class="fa-solid fa-house"></i><span>Dashboard</span>
        </div>
        <div class="side-nav-item" id="nav-evaluate" onclick="switchView('evaluate')">
            <i class="fa-solid fa-star-half-stroke"></i><span>Evaluation</span>
            <?php if ($total_pending > 0): ?><span class="side-nav-badge"><?= $total_pending ?></span><?php endif; ?>
        </div>
        <div class="side-nav-item" id="nav-history" onclick="switchView('history')">
            <i class="fa-solid fa-clock-rotate-left"></i><span>Evaluation History</span>
        </div>

        <div class="side-section-label side-section-secondary">Support</div>
        <div class="side-nav-item" id="nav-guidelines" onclick="switchView('guidelines')">
            <i class="fa-solid fa-circle-info"></i><span>Guidelines</span>
        </div>
        <div class="side-nav-item<?= $land_on_settings ? ' active' : '' ?>" id="nav-settings" onclick="switchView('settings')">
            <i class="fa-solid fa-gear"></i><span>Settings</span>
        </div>
    </aside>

    <div class="main">

        <div class="content-topbar">
            <button class="hamburger-btn" onclick="toggleSidebar()" aria-label="Open navigation"><i class="fa-solid fa-bars"></i></button>
            <div class="workspace-heading">
                <div class="workspace-kicker"><i class="fa-solid fa-chart-line"></i> Dashboard Overview</div>
                <div class="workspace-title-row">
                    <div>
                        <div class="workspace-title">Student Evaluation Workspace</div>
                        <div class="workspace-subtitle">Complete your assigned faculty and staff evaluations in one place.</div>
                    </div>
                    <div class="workspace-meta">
                        <div class="workspace-pill">
                            <i class="fa-regular fa-calendar"></i>
                            <?= htmlspecialchars($active_period_semester ?: 'No active period') ?>
                        </div>
                        <div class="workspace-pill muted">
                            <i class="fa-solid fa-rotate"></i>
                            Updated <?= date('M j, Y') ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <?php if ($submit_success): ?>
        <div class="alert alert-success"><i class="fa-solid fa-circle-check"></i> <?= htmlspecialchars($submit_success) ?></div>
        <?php endif; ?>
        <?php if ($submit_error): ?>
        <div class="alert alert-error"><i class="fa-solid fa-circle-exclamation"></i> <?= htmlspecialchars($submit_error) ?></div>
        <?php endif; ?>
        <?php if (!$student_year_level): ?>
        <div class="alert alert-error"><i class="fa-solid fa-circle-exclamation"></i> Your account has no year level set. Please contact the admin so faculty/staff can be assigned to you correctly.</div>
        <?php endif; ?>

        <!-- ══════════════ DASHBOARD VIEW ══════════════ -->
        <div class="view-content<?= $land_on_settings ? '' : ' active' ?>" id="view-dashboard">
            <div class="dashboard-welcome">
                <div>
                    <div class="page-title">
                        Welcome, <?= htmlspecialchars(explode(' ', $student_name)[0]) ?>
                        <?php if ($student_year_level): ?>
                        <span class="year-level-badge"> · <?= htmlspecialchars($student_year_level) ?></span>
                        <?php endif; ?>
                    </div>
                    <div class="page-sub">Here's an overview of your faculty &amp; staff evaluations.</div>
                </div>
                <div class="period-pill <?= $period_is_open ? 'open' : 'closed' ?>">
                    <i class="fa-solid <?= $period_is_open ? 'fa-lock-open' : 'fa-lock' ?>"></i>
                    Evaluation period is currently <?= $period_is_open ? 'OPEN' : 'CLOSED' ?>
                </div>
            </div>

            <div class="stat-grid">
                <div class="stat-card"><i class="fa-solid fa-users"></i><div class="stat-num"><?= $total_evaluatees ?></div><div class="stat-lbl">To evaluate</div></div>
                <div class="stat-card"><i class="fa-solid fa-circle-check"></i><div class="stat-num"><?= $total_done ?></div><div class="stat-lbl">Completed</div></div>
                <div class="stat-card"><i class="fa-solid fa-hourglass-half"></i><div class="stat-num"><?= $total_pending ?></div><div class="stat-lbl">Pending</div></div>
                <div class="stat-card"><i class="fa-solid fa-percent"></i><div class="stat-num"><?= $pct ?>%</div><div class="stat-lbl">Progress</div></div>
            </div>

            <?php if ($total_evaluatees > 0): ?>
            <div class="progress-wrap">
                <div class="progress-label">Progress: <span><?= $total_done ?> of <?= $total_evaluatees ?></span> evaluated</div>
                <div class="progress-bar-bg"><div class="progress-bar-fill" style="width:<?= $pct ?>%"></div></div>
                <div class="progress-pct"><?= $pct ?>%</div>
            </div>
            <?php endif; ?>

            <div class="dash-cta">
                <div class="dash-cta-icon"><i class="fa-solid fa-star-half-stroke"></i></div>
                <div class="dash-cta-text">
                    <h3><?= $total_pending > 0 ? "You have $total_pending evaluation" . ($total_pending !== 1 ? 's' : '') . " left" : "All evaluations complete" ?></h3>
                    <p><?= $total_pending > 0 ? 'Head over to the Evaluate section to keep going.' : 'Thank you for completing all your evaluations!' ?></p>
                </div>
                <button class="btn-primary-cta" onclick="switchView('<?= $total_pending > 0 ? 'evaluate' : 'history' ?>')">
                    <i class="fa-solid <?= $total_pending > 0 ? 'fa-arrow-right' : 'fa-clock-rotate-left' ?>"></i>
                    <?= $total_pending > 0 ? 'Continue Evaluations' : 'View History' ?>
                </button>
            </div>
        </div>

        <!-- ══════════════ EVALUATE VIEW ══════════════ -->
        <div class="view-content" id="view-evaluate">
            <div class="page-title">Faculty &amp; Staff Evaluation</div>
            <div class="page-sub">Select a category to see who is available for evaluation. Faculty and Staff are evaluated separately based on their current assignment.</div>
            <div class="student-eval-note"><i class="fa-solid fa-circle-info"></i><span>Your responses are recorded for the active evaluation period. Review each rating before you submit.</span></div>

            <?php
            // Build the top-level display list: Faculty, Staff, Principal, and Dean.
            // Principal and Dean each get their own tab now, though both still
            // submit under evaluation_context='school_head' -- the backend tells
            // them apart by the target's actual role, not by which tab it came from.
            $top_level = [];
            if (!empty($grouped['Faculty'])) {
                $top_level['Faculty']=array_map(fn($p)=>array_merge($p,['_evaluation_context'=>'teacher']),$grouped['Faculty']);
            }
            if (!empty($grouped['Staff'])) {
                $top_level['Staff']=array_map(fn($p)=>array_merge($p,['_evaluation_context'=>'staff']),$grouped['Staff']);
            }
            if (!empty($grouped['Principal'])) {
                $top_level['Principal']=array_map(fn($p)=>array_merge($p,['_evaluation_context'=>'school_head']),$grouped['Principal']);
            }
            if (!empty($grouped['Dean'])) {
                $top_level['Dean']=array_map(fn($p)=>array_merge($p,['_evaluation_context'=>'school_head']),$grouped['Dean']);
            }
            ?>

            <?php if (empty($top_level)): ?>
            <div class="empty"><i class="fa-solid fa-users-slash"></i><p>No evaluations are available right now.</p></div>
            <?php else: ?>

            <div class="section-label">Choose a category</div>
            <div class="category-grid">
                <?php foreach ($top_level as $group_name => $persons):
                    $slug     = strtolower(str_replace([' ','-'], '_', $group_name));
                    $total    = count($persons);
                    $done_ct=count(array_filter($persons,fn($p)=>isset($done_ids[$p['id'].'|'.($p['_evaluation_context']??'teacher')])));
                    $all_done = ($total > 0 && $done_ct === $total);
                    $icon     = $group_icons[$group_name] ?? 'fa-user';
                    $color    = $group_colors[$group_name] ?? '#D97706';
                ?>
                <div class="cat-btn <?= $all_done ? 'all-done' : '' ?>"
                     id="catbtn_<?= $slug ?>" onclick="togglePanel('<?= $slug ?>')"
                     style="border-color:<?= $color ?>33;">
                    <div class="cat-icon" style="color:<?= $color ?>;"><i class="fa-solid <?= $icon ?>"></i></div>
                    <div class="cat-name"><?= htmlspecialchars($group_name) ?></div>
                    <div class="cat-meta"><?= $total ?> member<?= $total !== 1 ? 's' : '' ?></div>
                    <?php if ($done_ct > 0): ?>
                    <div class="cat-done-pill"><i class="fa-solid fa-check"></i> <?= $done_ct ?>/<?= $total ?> done</div>
                    <?php endif; ?>
                    <div class="cat-active-arrow" style="border-top-color:<?= $color ?>;"></div>
                </div>
                <?php endforeach; ?>
            </div>

            <?php
            // Reusable person-card renderer for Faculty, Staff, Principal, and Dean.
            function render_person_card($p, $done_ids, $period_is_open) {
                $context=$p['_evaluation_context']??'teacher';
                $is_done=isset($done_ids[$p['id'].'|'.$context]);
                // Defaults to true for any group that doesn't stamp this flag,
                // so nothing new gets hidden unless we've actually confirmed
                // there are zero questions configured for this person.
                $has_questions = $p['_has_questions'] ?? true;
                $eval_args = json_encode([
                    'id'    => $p['id'],
                    'name'  => $p['full_name'],
                    'desig' => $p['designation'] ?: ($p['role'] === 'teacher' ? 'Teacher' : 'Personnel'),
                    'photo'=>$p['photo']?'../image/'.$p['photo']:'',
                    'context'=>$p['_evaluation_context']??'teacher',
                ]);
                ob_start();
                ?>
                <div class="person-card <?= $is_done ? 'done' : '' ?>">
                    <?php if ($is_done): ?>
                    <span class="done-badge"><i class="fa-solid fa-check"></i> Done</span>
                    <?php endif; ?>
                    <?php if ($p['photo']): ?>
                    <img class="person-avatar" src="../image/<?= htmlspecialchars($p['photo']) ?>"
                         alt="<?= htmlspecialchars($p['full_name']) ?>"/>
                    <?php else: ?>
                    <div class="person-avatar-ph"><i class="fa-solid fa-user"></i></div>
                    <?php endif; ?>
                    <div class="person-name"><?= htmlspecialchars($p['full_name']) ?></div>
                    <div class="person-desig"><?= htmlspecialchars($p['designation'] ?: '—') ?></div>
                    <?php if (!$is_done && $has_questions): ?>
                    <button type="button" class="eval-btn" onclick="openEvalFromData(this)"
                        <?= !$period_is_open ? 'disabled title="No evaluation period is currently open"' : '' ?>
                        data-eval='<?= htmlspecialchars($eval_args, ENT_QUOTES) ?>'>
                        <i class="fa-solid fa-star-half-stroke"></i> Evaluate
                    </button>
                    <?php elseif (!$is_done && !$has_questions): ?>
                    <button type="button" class="eval-btn" disabled title="The admin hasn't set up evaluation questions for this person yet">
                        <i class="fa-solid fa-hourglass-half"></i> Not available yet
                    </button>
                    <?php endif; ?>
                </div>
                <?php
                return ob_get_clean();
            }
            ?>

            <?php foreach ($top_level as $group_name => $persons):
                $slug  = strtolower(str_replace([' ','-'], '_', $group_name));
                $icon  = $group_icons[$group_name] ?? 'fa-user';
                $color = $group_colors[$group_name] ?? '#D97706';
            ?>
            <div class="members-panel" id="panel_<?= $slug ?>" style="border-color:<?= $color ?>44;">
                <div class="panel-header">
                    <i class="fa-solid <?= $icon ?> panel-header-icon" style="color:<?= $color ?>;"></i>
                    <span class="panel-header-title"><?= htmlspecialchars($group_name) ?></span>
                    <span class="panel-header-count">&mdash; <?= count($persons) ?> member<?= count($persons) !== 1 ? 's' : '' ?></span>
                    <button class="panel-close-btn" onclick="closePanel('<?= $slug ?>')">
                        <i class="fa-solid fa-xmark"></i>
                    </button>
                </div>

                <div class="members-grid">
                    <?php foreach ($persons as $p) echo render_person_card($p, $done_ids, $period_is_open); ?>
                </div>
            </div>
            <?php endforeach; ?>

            <?php endif; ?>
        </div>

        <!-- ══════════════ HISTORY VIEW ══════════════ -->
        <div class="view-content" id="view-history">
            <div class="page-title">Evaluation History</div>
            <div class="page-sub">Faculty, staff, and school heads you've already evaluated.</div>

            <?php if (empty($history)): ?>
            <div class="empty"><i class="fa-solid fa-clock-rotate-left"></i><p>You haven't submitted any evaluations yet.</p></div>
            <?php else: ?>
            <div class="history-list">
                <?php foreach ($history as $h):
                    $avg = $h['avg_score'] !== null ? round($h['avg_score'], 1) : null;
                ?>
                <div class="history-card">
                    <?php if ($h['photo']): ?>
                    <img class="history-avatar" src="../image/<?= htmlspecialchars($h['photo']) ?>" alt=""/>
                    <?php else: ?>
                    <div class="history-avatar-ph"><i class="fa-solid fa-user"></i></div>
                    <?php endif; ?>
                    <div class="history-info">
                        <div class="history-name"><?= htmlspecialchars($h['full_name']) ?></div>
                        <div class="history-desig"><?= htmlspecialchars($h['designation'] ?: '—') ?></div>
                        <?php if (!empty($h['remarks'])): ?>
                        <div class="history-comment">"<?= htmlspecialchars(mb_strimwidth($h['remarks'], 0, 120, '…')) ?>"</div>
                        <?php endif; ?>
                    </div>
                    <div class="history-meta">
                        <?php if ($avg !== null): ?>
                        <div class="history-score"><i class="fa-solid fa-star"></i> <?= $avg ?> / 5</div>
                        <?php endif; ?>
                        <div class="history-date"><?= htmlspecialchars(date('M j, Y', strtotime($h['submitted_at']))) ?></div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>

        <!-- ══════════════ GUIDELINES VIEW ══════════════ -->
        <div class="view-content" id="view-guidelines">
            <div class="page-title">Evaluation Guidelines</div>
            <div class="page-sub">How to evaluate faculty and staff fairly and accurately.</div>

            <div class="gl-card">
                <h3><i class="fa-solid fa-scale-balanced"></i> Rating Scale</h3>
                <div class="gl-scale-row"><div class="gl-scale-num">5</div><p><strong>Always</strong> — consistently demonstrates this</p></div>
                <div class="gl-scale-row"><div class="gl-scale-num">4</div><p><strong>Often</strong> — usually demonstrates this</p></div>
                <div class="gl-scale-row"><div class="gl-scale-num">3</div><p><strong>Sometimes</strong> — demonstrates this about half the time</p></div>
                <div class="gl-scale-row"><div class="gl-scale-num">2</div><p><strong>Rarely</strong> — seldom demonstrates this</p></div>
                <div class="gl-scale-row"><div class="gl-scale-num">1</div><p><strong>Never</strong> — does not demonstrate this</p></div>
            </div>

            <div class="gl-card">
                <h3><i class="fa-solid fa-circle-check"></i> Rules</h3>
                <ul>
                    <li>You can only evaluate faculty and staff assigned to your education level.</li>
                    <li>Each person can only be evaluated once per evaluation period.</li>
                    <li>Submissions cannot be edited once submitted, so review your ratings before sending.</li>
                    <li>Evaluations can only be submitted while an evaluation period is open.</li>
                </ul>
            </div>

            <div class="gl-card">
                <h3><i class="fa-solid fa-shield-halved"></i> Confidentiality</h3>
                <p>Your individual ratings and comments are used to help faculty and staff improve. Please answer honestly and constructively.</p>
            </div>
        </div>

        <!-- ══════════════ SETTINGS VIEW ══════════════ -->
        <div class="view-content<?= $land_on_settings ? ' active' : '' ?>" id="view-settings">
            <div class="page-title">Settings</div>
            <div class="page-sub">Manage your profile and account.</div>

            <div class="gl-card">
                <h3><i class="fa-solid fa-user"></i> Profile</h3>
                <div class="settings-row">
                    <?php if ($student_photo): ?>
                    <img class="profile-dd-avatar" src="../image/<?= htmlspecialchars($student_photo) ?>" alt=""/>
                    <?php else: ?>
                    <div class="profile-dd-avatar-ph"><i class="fa-solid fa-user"></i></div>
                    <?php endif; ?>
                    <div class="settings-info">
                        <div class="profile-dd-name"><?= htmlspecialchars($student_name) ?></div>
                        <div class="profile-dd-role">Student<?= $student_year_level ? ' · ' . htmlspecialchars($student_year_level) : '' ?></div>
                    </div>
                    <button class="btn-primary-cta" onclick="openPhotoModal()">
                        <i class="fa-solid fa-camera"></i> Update Profile Photo
                    </button>
                </div>
            </div>

            <div class="gl-card">
                <h3><i class="fa-solid fa-id-card"></i> Profile Details</h3>
                <?php if ($profile_success): ?>
                <div class="alert alert-success"><i class="fa-solid fa-circle-check"></i> <?= htmlspecialchars($profile_success) ?></div>
                <?php endif; ?>
                <?php if ($profile_error): ?>
                <div class="alert alert-error"><i class="fa-solid fa-circle-exclamation"></i> <?= htmlspecialchars($profile_error) ?></div>
                <?php endif; ?>
                <form method="POST" action="student_dashboard.php">
                    <?= csrf_field() ?>
                    <input type="hidden" name="update_profile" value="1"/>
                    <div class="settings-grid">
                        <div class="settings-field">
                            <label for="set_fullname">Full Name</label>
                            <input type="text" id="set_fullname" name="full_name" maxlength="100" required value="<?= htmlspecialchars($student_name) ?>"/>
                        </div>
                        <div class="settings-field">
                            <label for="set_username">Username</label>
                            <input type="text" id="set_username" name="username" maxlength="50" required autocomplete="username" value="<?= htmlspecialchars($student_username) ?>"/>
                        </div>
                        <div class="settings-field">
                            <label for="set_email">Email</label>
                            <input type="email" id="set_email" name="email" maxlength="150" placeholder="you@example.com" value="<?= htmlspecialchars($student_email) ?>"/>
                        </div>
                        <div class="settings-field">
                            <label for="set_yearlevel">Year Level</label>
                            <?php if (!empty($year_level_options)): ?>
                            <select id="set_yearlevel" name="year_level" required>
                                <?php
                                $current_yl_matched = false;
                                foreach ($year_level_options as $opt):
                                    $isSel = (strcasecmp($opt, (string)$student_year_level) === 0);
                                    if ($isSel) $current_yl_matched = true;
                                ?>
                                <option value="<?= htmlspecialchars($opt) ?>"<?= $isSel ? ' selected' : '' ?>><?= htmlspecialchars($opt) ?></option>
                                <?php endforeach; ?>
                                <?php if (!$current_yl_matched && $student_year_level): ?>
                                <option value="<?= htmlspecialchars($student_year_level) ?>" selected><?= htmlspecialchars($student_year_level) ?></option>
                                <?php endif; ?>
                            </select>
                            <?php else: ?>
                            <input type="text" id="set_yearlevel" name="year_level" maxlength="30" value="<?= htmlspecialchars($student_year_level ?? '') ?>"/>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="settings-hint">Changing your Username changes what you sign in with. Changing your Year Level changes which faculty &amp; staff you're eligible to evaluate.</div>
                    <button type="submit" class="btn-primary-cta"><i class="fa-solid fa-floppy-disk"></i> Save Changes</button>
                </form>
            </div>

            <div class="gl-card">
                <h3><i class="fa-solid fa-lock"></i> Change Password</h3>
                <?php if ($password_success): ?>
                <div class="alert alert-success"><i class="fa-solid fa-circle-check"></i> <?= htmlspecialchars($password_success) ?></div>
                <?php endif; ?>
                <?php if ($password_error): ?>
                <div class="alert alert-error"><i class="fa-solid fa-circle-exclamation"></i> <?= htmlspecialchars($password_error) ?></div>
                <?php endif; ?>
                <form method="POST" action="student_dashboard.php" id="changePwForm" autocomplete="off">
                    <?= csrf_field() ?>
                    <input type="hidden" name="change_password" value="1"/>
                    <div class="settings-grid">
                        <div class="settings-field">
                            <label for="cur_pw">Current Password</label>
                            <div class="pw-wrap">
                                <input type="password" id="cur_pw" name="current_password" required autocomplete="current-password"/>
                                <button type="button" class="pw-toggle" onclick="togglePw('cur_pw','ic1')"><i class="fa-solid fa-eye" id="ic1"></i></button>
                            </div>
                        </div>
                        <div></div>
                        <div class="settings-field">
                            <label for="new_pw">New Password</label>
                            <div class="pw-wrap">
                                <input type="password" id="new_pw" name="new_password" required minlength="8" maxlength="72" autocomplete="new-password"/>
                                <button type="button" class="pw-toggle" onclick="togglePw('new_pw','ic2')"><i class="fa-solid fa-eye" id="ic2"></i></button>
                            </div>
                        </div>
                        <div class="settings-field">
                            <label for="confirm_pw">Confirm New Password</label>
                            <div class="pw-wrap">
                                <input type="password" id="confirm_pw" name="confirm_password" required minlength="8" maxlength="72" autocomplete="new-password"/>
                                <button type="button" class="pw-toggle" onclick="togglePw('confirm_pw','ic3')"><i class="fa-solid fa-eye" id="ic3"></i></button>
                            </div>
                        </div>
                    </div>
                    <div class="settings-hint">Minimum 8 characters. Use something you don't use anywhere else.</div>
                    <div class="alert alert-error" id="pwMismatch" style="display:none;"><i class="fa-solid fa-circle-exclamation"></i> New passwords do not match.</div>
                    <button type="submit" class="btn-primary-cta" id="updatePwBtn"><i class="fa-solid fa-key"></i> Update Password</button>
                </form>
            </div>

            <div class="gl-card">
                <h3><i class="fa-solid fa-right-from-bracket"></i> Account</h3>
                <p style="margin-bottom:14px;">Sign out of your student evaluation account on this device.</p>
                <a href="../logout.php" class="btn-logout">
                    <i class="fa-solid fa-right-from-bracket"></i> Log out
                </a>
            </div>
        </div>

    </div>
</div>

<!-- EVALUATION MODAL -->
<div class="modal-overlay" id="evalModal">
    <div class="modal">
        <div class="modal-header">
            <div id="modalAvatarWrap"></div>
            <div>
                <div class="modal-name" id="modalName"></div>
                <div class="modal-desig" id="modalDesig"></div>
            </div>
            <button class="modal-close" onclick="closeEval()"><i class="fa-solid fa-xmark"></i></button>
        </div>
        <form method="POST" action="student_dashboard.php" id="evalForm">
            <input type="hidden" name="submit_evaluation" value="1"/>
            <input type="hidden" name="target_user_id" id="targetUserId"/>
            <input type="hidden" name="evaluation_context" id="evaluationContext" value="teacher"/>
            <div class="modal-body" id="modalBody">
                <div class="loading-qs"><i class="fa-solid fa-spinner"></i> Loading questions...</div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn-cancel-modal" onclick="closeEval()">Cancel</button>
                <button type="submit" class="btn-submit">
                    <i class="fa-solid fa-paper-plane"></i> Submit Evaluation
                </button>
            </div>
        </form>
    </div>
</div>

<script>
let activePanel     = null;
let questionsLoaded = false;

// ── Sidebar view switching ──
function switchView(view) {
    document.querySelectorAll('.view-content').forEach(v => v.classList.remove('active'));
    document.getElementById('view-' + view)?.classList.add('active');
    document.querySelectorAll('.side-nav-item').forEach(i => i.classList.remove('active'));
    document.getElementById('nav-' + view)?.classList.add('active');
    closeSidebarMobile();
    window.scrollTo({top: 0, behavior: 'smooth'});
}

// ── Settings: show/hide password ──
function togglePw(inputId, iconId) {
    const input = document.getElementById(inputId), icon = document.getElementById(iconId);
    input.type = input.type === 'password' ? 'text' : 'password';
    icon.className = input.type === 'password' ? 'fa-solid fa-eye' : 'fa-solid fa-eye-slash';
}

// ── Settings: change password form guard ──
const changePwForm = document.getElementById('changePwForm');
if (changePwForm) {
    changePwForm.addEventListener('submit', (ev) => {
        const np = document.getElementById('new_pw').value, cp = document.getElementById('confirm_pw').value;
        if (np !== cp) {
            ev.preventDefault();
            document.getElementById('pwMismatch').style.display = 'flex';
            return;
        }
        document.getElementById('pwMismatch').style.display = 'none';
        document.getElementById('updatePwBtn').disabled = true;
    });
}

// ── Mobile sidebar drawer ──
function toggleSidebar() {
    document.getElementById('sidebar').classList.toggle('open');
    document.getElementById('sidebarOverlay').classList.toggle('open');
}
function closeSidebarMobile() {
    document.getElementById('sidebar').classList.remove('open');
    document.getElementById('sidebarOverlay').classList.remove('open');
}

// ── Photo modal ──
function openPhotoModal() {
    document.getElementById('photoModal').classList.add('open');
    document.body.style.overflow = 'hidden';
}
function closePhotoModal() {
    document.getElementById('photoModal').classList.remove('open');
    document.body.style.overflow = '';
}
function previewPhoto(input) {
    if (input.files && input.files[0]) {
        const r = new FileReader();
        r.onload = e => {
            const img = document.getElementById('photoPreviewImg');
            const ic  = document.getElementById('uploadIconEl');
            img.src = e.target.result;
            img.style.display = 'block';
            ic.style.display  = 'none';
        };
        r.readAsDataURL(input.files[0]);
    }
}
function submitPhoto() {
    const fileInput = document.getElementById('photoFileInput');
    if (!fileInput.files || !fileInput.files[0]) { closePhotoModal(); return; }
    document.getElementById('photoForm').submit();
}
document.getElementById('photoModal').addEventListener('click', function(e) {
    if (e.target === this) closePhotoModal();
});

// ── Category panel toggle (Evaluate view) ──
function togglePanel(slug) {
    if (activePanel && activePanel !== slug) _closePanel(activePanel);
    if (activePanel === slug) { _closePanel(slug); activePanel = null; }
    else { _openPanel(slug); activePanel = slug; }
}
function _openPanel(slug) {
    document.getElementById('panel_' + slug)?.classList.add('open');
    document.getElementById('catbtn_' + slug)?.classList.add('active');
    setTimeout(() => document.getElementById('panel_' + slug)?.scrollIntoView({behavior:'smooth',block:'nearest'}), 50);
}
function _closePanel(slug) {
    document.getElementById('panel_' + slug)?.classList.remove('open');
    document.getElementById('catbtn_' + slug)?.classList.remove('active');
}
function closePanel(slug) { _closePanel(slug); if (activePanel === slug) activePanel = null; }

// Escape text inserted into HTML generated by the evaluation modal.
// The table-style renderer calls this helper for category/question text.
function escapeHtml(value) {
    return String(value ?? '')
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/\"/g, '&quot;')
        .replace(/'/g, '&#039;');
}

// ── Eval modal ──
function openEvalFromData(btn) {
    const d = JSON.parse(btn.getAttribute('data-eval'));
    openEval(d.id,d.name,d.desig,d.photo,d.context||'teacher');
}
function openEval(id,name,desig,photo,context){
    questionsLoaded = false;
    document.getElementById('targetUserId').value=id;
    const ctxInput=document.getElementById('evaluationContext'); if(ctxInput)ctxInput.value=context||'teacher';
    document.getElementById('modalName').textContent  = name;
    document.getElementById('modalDesig').textContent = desig || 'Faculty / Staff';
    const wrap = document.getElementById('modalAvatarWrap');
    if (photo) {
        const img = document.createElement('img');
        img.className = 'modal-avatar'; img.src = photo; img.alt = name;
        img.onerror = () => img.outerHTML = '<div class="modal-avatar-ph"><i class="fa-solid fa-user"></i></div>';
        wrap.innerHTML = ''; wrap.appendChild(img);
    } else {
        wrap.innerHTML = '<div class="modal-avatar-ph"><i class="fa-solid fa-user"></i></div>';
    }
    document.getElementById('modalBody').innerHTML =
        '<div class="loading-qs"><i class="fa-solid fa-spinner"></i> Loading questions...</div>';
    document.getElementById('evalModal').classList.add('open');
    document.body.style.overflow = 'hidden';
    loadQuestions(id,context||'teacher');
}
function closeEval() {
    document.getElementById('evalModal').classList.remove('open');
    document.body.style.overflow = '';
    questionsLoaded = false;
}

function loadQuestions(id,context){
    fetch(`student_dashboard.php?get_questions=1&target_id=${id}&context=${encodeURIComponent(context||'teacher')}`)
        .then(r => r.json())
        .then(data => {
            if (!data.success) {
                document.getElementById('modalBody').innerHTML =
                    `<div class="empty"><i class="fa-solid fa-triangle-exclamation"></i>
                     <p style="color:#dc2626;margin-top:10px;font-size:13px">${data.error}</p></div>`;
                return;
            }
            const questions = data.questions;
            const legend = `<div class="scale-legend">
                <div class="legend-item"><div class="legend-dot">5</div> Always</div>
                <div class="legend-item"><div class="legend-dot">4</div> Often</div>
                <div class="legend-item"><div class="legend-dot">3</div> Sometimes</div>
                <div class="legend-item"><div class="legend-dot">2</div> Rarely</div>
                <div class="legend-item"><div class="legend-dot">1</div> Never</div>
            </div>`;
            const labels  = {5:'Always',4:'Often',3:'Sometimes',2:'Rarely',1:'Never'};
            const grouped = {};
            questions.forEach(q => {
                const cat = q.category || 'General';
                if (!grouped[cat]) grouped[cat] = [];
                grouped[cat].push(q);
            });
            let html = legend;
            let qNum = 1;
            for (const [cat, qs] of Object.entries(grouped)) {
                html += `<div class="eval-form-cat"><i class="fa-solid fa-layer-group" style="margin-right:5px"></i>${escapeHtml(cat)}</div>`;
                html += `<div class="eval-form-wrap"><table class="eval-form-table"><thead><tr><th>Question</th><th>5</th><th>4</th><th>3</th><th>2</th><th>1</th></tr></thead><tbody>`;
                qs.forEach(q => {
                    const qkey = `${q.question_source}:${q.id}`;
                    html += `<tr><td><div class="eval-form-qtext"><span class="eval-form-qno">${qNum++}.</span>${escapeHtml(q.question_text)}</div></td>`;
                    [5,4,3,2,1].forEach(v => {
                        const optId = `r_${q.question_source}_${q.id}_${v}`;
                        html += `<td><div class="eval-form-rating"><input type="radio" name="rating[${qkey}]" id="${optId}" value="${v}" required><label for="${optId}">${v}</label></div></td>`;
                    });
                    html += `</tr>`;
                });
                html += `</tbody></table></div>`;
            }
            html += `<div class="comment-box">
                <div class="comment-label">
                    <i class="fa-solid fa-comment-dots"></i>
                    Comments, Suggestions &amp; Areas for Improvement
                    <span class="comment-optional">(Optional)</span>
                </div>
                <textarea name="comment" class="comment-textarea"
                    placeholder="Share your thoughts, suggestions, or concerns about this person's performance..."
                    rows="4"></textarea>
            </div>`;
            document.getElementById('modalBody').innerHTML = html;
            questionsLoaded = true;
        })
        .catch(err => {
            document.getElementById('modalBody').innerHTML =
                `<div class="empty"><i class="fa-solid fa-triangle-exclamation"></i>
                 <p style="color:#dc2626;margin-top:10px;font-size:13px">
                    Failed to load questions.<br><small>${err.message}</small></p></div>`;
        });
}

document.getElementById('evalModal').addEventListener('click', function(e) {
    if (e.target === this) closeEval();
});

document.getElementById('evalForm').addEventListener('submit', function(e) {
    if (!questionsLoaded) { e.preventDefault(); alert('Questions are still loading. Please wait.'); return; }
    const radios = this.querySelectorAll('input[type="radio"][name^="rating["]');
    if (!radios.length) { e.preventDefault(); alert('No questions found. Please close and try again.'); return; }
    const names      = [...new Set([...radios].map(r => r.name))];
    const unanswered = names.filter(n => !this.querySelector(`input[name="${CSS.escape(n)}"]:checked`));
    if (unanswered.length) { e.preventDefault(); alert(`Please answer all questions. (${unanswered.length} remaining)`); return; }
});

// If the page reloaded after a submit-evaluation POST (e.g. validation
// error/success), keep the user on the Evaluate view instead of bouncing
// them back to Dashboard, since that's where the alert is relevant.
<?php if ($submit_success || $submit_error): ?>
switchView('evaluate');
<?php endif; ?>
</script>
</body>
</html>