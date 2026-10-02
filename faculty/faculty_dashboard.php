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
require_once '../shared/eligibility.php';
require_once '../shared/EvaluationContextService.php';
require_once '../shared/system_settings_service.php';
require_once '../shared/QuestionnaireService.php';
require_once '../shared/notification_message.php';
qn_migrate_legacy_once($mysqli);

if (empty($_SESSION['user_id']) || $_SESSION['role'] !== 'teacher') {
    header("Location: faculty_login.php"); exit;
}

$user_id     = $_SESSION['user_id'];
$full_name   = $_SESSION['full_name'];
$designation = $_SESSION['designation'] ?? 'Teacher';
$page        = $_GET['page'] ?? 'dashboard';

// ── CSRF TOKEN ────────────────────────────────────────────────
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrf_token = $_SESSION['csrf_token'];

function csrf_check(): bool {
    return isset($_POST['csrf_token']) && hash_equals($_SESSION['csrf_token'] ?? '', $_POST['csrf_token']);
}

// Centralized live schedule gate for Faculty/Staff evaluation access.
// Follow Schedule uses Asia/Manila and the configured [start, end) window.
// Force Open / Force Closed remain explicit overrides.
function fs_schedule_settings(mysqli $mysqli): array {
    $out = [];
    $rs = $mysqli->query("SELECT setting_key, setting_value FROM system_settings");
    if ($rs) {
        while ($row = $rs->fetch_assoc()) {
            $out[(string)$row['setting_key']] = (string)$row['setting_value'];
        }
        $rs->free();
    }
    return $out;
}

function fs_parse_manila_datetime(?string $raw): ?DateTimeImmutable {
    $raw = trim((string)$raw);
    if ($raw === '') return null;
    $tz = new DateTimeZone('Asia/Manila');
    $formats = ['Y-m-d\\TH:i:s', 'Y-m-d\\TH:i', 'Y-m-d H:i:s', 'Y-m-d H:i'];
    foreach ($formats as $format) {
        $dt = DateTimeImmutable::createFromFormat($format, $raw, $tz);
        if ($dt !== false) {
            $errors = DateTimeImmutable::getLastErrors();
            if ($errors === false || ($errors['warning_count'] ?? 0) === 0 && ($errors['error_count'] ?? 0) === 0) {
                return $dt;
            }
        }
    }
    try {
        return new DateTimeImmutable($raw, $tz);
    } catch (Throwable $e) {
        return null;
    }
}

function fs_schedule_is_open(mysqli $mysqli): bool {
    $s = fs_schedule_settings($mysqli);
    $mode = strtolower(trim((string)($s['control_mode'] ?? 'schedule')));

    if ($mode === 'open') return true;
    if ($mode === 'closed') return false;

    $start = fs_parse_manila_datetime($s['eval_start'] ?? '');
    $end   = fs_parse_manila_datetime($s['eval_end'] ?? '');
    if (!$start || !$end || $end <= $start) return false;

    $now = new DateTimeImmutable('now', new DateTimeZone('Asia/Manila'));
    return $now >= $start && $now < $end;
}


// ── FETCH PROFILE PHOTO ───────────────────────────────────────
$phRes = $mysqli->prepare("SELECT photo FROM users WHERE id=? LIMIT 1");
$phRes->bind_param("i", $user_id);
$phRes->execute();
$faculty_photo = $phRes->get_result()->fetch_assoc()['photo'] ?? '';
$phRes->close();

// ── VALID SPECIFIC YEAR LEVELS ───────────────────────────────────
// Same exact list & string values as admin/manage_privileged_accounts.php
// uses for students and for teacher/staff assignment — keeping these
// identical is what lets a faculty member's picks match real students.
$year_levels = [
    'Grade 7','Grade 8','Grade 9','Grade 10',
    'Grade 11','Grade 12',
    '1st Year College','2nd Year College','3rd Year College','4th Year College',
];

// ── ENSURE user_year_levels TABLE EXISTS ────────────────────────
// Shared junction table with the admin page — a person can have more
// than one specific year level checked (e.g. Grade 8 AND Grade 10).
$mysqli->query("
    CREATE TABLE IF NOT EXISTS user_year_levels (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT UNSIGNED NOT NULL,
        year_level VARCHAR(30) NOT NULL,
        UNIQUE KEY uniq_user_year (user_id, year_level),
        CONSTRAINT fk_uyl_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    )
");

// ── FETCH MY ASSIGNED TEACHING LEVEL(S) ─────────────────────────
// A faculty member can be assigned to more than one SPECIFIC year
// level at once (e.g. Grade 8 AND Grade 10), so this is a list, not
// a single value.
$my_levels = [];
$lvlQ = $mysqli->prepare("SELECT year_level FROM user_year_levels WHERE user_id=?");
$lvlQ->bind_param("i", $user_id);
$lvlQ->execute();
$lvlRes = $lvlQ->get_result();
while ($lr = $lvlRes->fetch_assoc()) $my_levels[] = $lr['year_level'];
$lvlQ->close();

// ── DEAN/PRINCIPAL EVALUATOR ELIGIBILITY (by own teaching assignment) ──
// Mirrors the admin-side Dean/Principal Evaluation targeting rule
// (Principal <- Faculty/Teaching Staff assigned to Grade 7-12, Dean <-
// assigned to College): a Faculty/Teaching Staff member's teaching
// assignment(s) determine which of the two they're allowed to evaluate
// here. JHS/SHS assignment -> can evaluate the Principal only. College
// assignment -> can evaluate the Dean only. Assigned to both at once ->
// can evaluate both. No assignment at all -> can evaluate neither yet.
$jhs_shs_levels          = ['Grade 7','Grade 8','Grade 9','Grade 10','Grade 11','Grade 12'];
$dean_principal_college_levels = ['1st Year College','2nd Year College','3rd Year College','4th Year College'];
$can_evaluate_principal  = (bool) array_intersect($my_levels, $jhs_shs_levels);
$can_evaluate_dean       = (bool) array_intersect($my_levels, $dean_principal_college_levels);

// ── ACTIVE PERIOD ─────────────────────────────────────────────
$period = null;
$pr = $mysqli->query("SELECT * FROM evaluation_periods WHERE is_active=1 LIMIT 1");
if ($pr) $period = $pr->fetch_assoc();

// The active-period flag is not the schedule clock. Always evaluate the
// live Asia/Manila schedule so a future period cannot appear open early.
$evaluation_open = fs_schedule_is_open($mysqli);

// ── DASHBOARD SCHEDULE SUMMARY ─────────────────────────────────────
// Read the same database-backed settings used by the admin Settings page so
// Faculty/Staff see the exact opening/closing date and time on their dashboard.
$dashboard_sys = ss_raw($mysqli);
$dashboard_structure_labels = ss_structure_labels();
$dashboard_structure = $dashboard_structure_labels[$dashboard_sys['acad_structure'] ?? ''] ?? ucfirst((string)($dashboard_sys['acad_structure'] ?? ''));
$dashboard_acad_year = trim((string)($dashboard_sys['acad_year'] ?? ($period['school_year'] ?? '')));
$dashboard_term = trim((string)($dashboard_sys['acad_term'] ?? ($period['semester'] ?? '')));
$dashboard_start_raw = trim((string)($dashboard_sys['eval_start'] ?? ''));
$dashboard_end_raw = trim((string)($dashboard_sys['eval_end'] ?? ''));
$dashboard_start = $dashboard_start_raw !== '' ? ss_parse_datetime($dashboard_start_raw) : null;
$dashboard_end = $dashboard_end_raw !== '' ? ss_parse_datetime($dashboard_end_raw) : null;
$dashboard_tz = new DateTimeZone((string)($dashboard_sys['schedule_timezone'] ?? 'Asia/Manila'));

$dashboard_status = 'Scheduled';
if (($dashboard_sys['control_mode'] ?? 'schedule') === 'open') {
    $dashboard_status = 'Open';
} elseif (($dashboard_sys['control_mode'] ?? 'schedule') === 'closed') {
    $dashboard_status = 'Closed';
} elseif ($dashboard_start && $dashboard_end) {
    $dashboard_now = new DateTimeImmutable('now', $dashboard_tz);
    if ($dashboard_now < $dashboard_start) {
        $dashboard_status = 'Scheduled';
    } elseif ($dashboard_now >= $dashboard_end) {
        $dashboard_status = 'Closed';
    } else {
        $dashboard_status = 'Open';
    }
}

$dashboard_window = 'Not configured';
if ($dashboard_start && $dashboard_end) {
    $dashboard_window = $dashboard_start->format('M j, Y') . ' – ' . $dashboard_end->format('M j, Y');
}
$dashboard_open_display = $dashboard_start ? $dashboard_start->format('M j, Y g:i A') : 'Not configured';
$dashboard_close_display = $dashboard_end ? $dashboard_end->format('M j, Y g:i A') : 'Not configured';
$dashboard_status_class = strtolower($dashboard_status);

// ── MY EVALUATION RESULTS ─────────────────────────────────────
// Mirrors staff_dashboard.php: all-time totals (not period-scoped) so the
// "Performance by Category" card behaves identically for Faculty accounts.
$my_avg   = null;
$my_total = 0;
$my_scores = [];

$res_stmt = $mysqli->prepare("
    SELECT AVG(qa.answer_score) as avg_score, COUNT(DISTINCT et.id) as total
    FROM evaluation_tracker et
    JOIN questionnaire_answers qa ON qa.tracker_id = et.id
    WHERE et.target_user_id = ?
");
$res_stmt->bind_param("i", $user_id);
$res_stmt->execute();
$res = $res_stmt->get_result();
if ($res) {
    $row      = $res->fetch_assoc();
    $my_avg   = $row['avg_score'] !== null ? round($row['avg_score'], 2) : null;
    $my_total = $row['total'] ?? 0;
}
$res_stmt->close();

$cat_stmt = $mysqli->prepare("
    SELECT COALESCE(uq.category, eq.category, 'General') AS category,
           AVG(qa.answer_score) AS avg_cat
    FROM questionnaire_answers qa
    JOIN evaluation_tracker et ON et.id = qa.tracker_id
    LEFT JOIN user_questions uq
      ON qa.question_source='user'
     AND uq.id = COALESCE(qa.user_question_id, qa.question_id)
    LEFT JOIN evaluation_questions eq
      ON qa.question_source='evaluation'
     AND eq.id = qa.question_id
    WHERE et.target_user_id = ?
    GROUP BY COALESCE(uq.category, eq.category, 'General')
");
$cat_stmt->bind_param("i", $user_id);
$cat_stmt->execute();
$cat_res = $cat_stmt->get_result();
if ($cat_res) $my_scores = $cat_res->fetch_all(MYSQLI_ASSOC);
$cat_stmt->close();

// ── PEER EVALUATION GROUPING (canonical, matches admin/questionnaire.php) ──
// Faculty/Staff grouping here is NOT derived from designation text. It uses
// the same shared predicates (ec_has_teacher_function / ec_has_staff_function)
// admin/questionnaire.php uses for its own Teacher/Staff/Multi-Role buckets,
// plus the identical "Non-Teaching Staff" DB check admin/questionnaire.php
// and ea_evaluation.php already use: a Staff account with NO
// teaching_assignments row and NO user_year_levels row. A designation like
// "Coordinator" or "Department Head" creates an *additional* Multi-Role
// context elsewhere in the system — it does not reclassify someone's base
// Teacher/Staff function or move them out of Non-Teaching Staff here.
function isNonTeachingStaff(mysqli $mysqli, int $user_id): bool {
    $stmt = $mysqli->prepare(
        "SELECT
            NOT EXISTS(SELECT 1 FROM teaching_assignments ta WHERE ta.user_id=?)
            AND NOT EXISTS(SELECT 1 FROM user_year_levels yl WHERE yl.user_id=?)
         AS is_non_teaching"
    );
    $stmt->bind_param('ii', $user_id, $user_id);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    return (bool)($row['is_non_teaching'] ?? false);
}

// Resolves a peer-eval bucket for a user row ('teacher', 'staff', or null
// if the account belongs to neither). A Staff account with an active
// year-level assignment (i.e. NOT isNonTeachingStaff — the exact same
// Manage Registrations logic used above) is Teaching Staff, and Teaching
// Staff are evaluated as part of the Faculty ('teacher') group, not Staff.
// Only a Staff account with no active year-level/teaching assignment
// (Non-Teaching Staff) resolves to 'staff'. Actual Faculty accounts are
// still identified the same way they always were, via
// ec_has_teacher_function(), so no valid Faculty record is affected.
function resolve_peer_group(mysqli $mysqli, array $u): ?string {
    if (ec_has_teacher_function($u)) return 'teacher';
    if (ec_has_staff_function($u)) {
        return isNonTeachingStaff($mysqli, (int)$u['id']) ? 'staff' : 'teacher';
    }
    return null;
}

// Human-readable role label for a Peer/Faculty evaluation target card.
// Actual Faculty keep showing their existing stored designation. A Staff
// account dynamically placed in the Faculty ('teacher') group because of
// an active year-level assignment is labeled "Teaching Staff" (plus their
// assigned level(s), from the same user_year_levels table Manage
// Registrations uses) so it's clear at a glance they're Teaching Staff and
// not an ordinary/Non-Teaching Staff member. Does not change any stored
// designation/role — display only.
function peer_display_label(mysqli $mysqli, array $p, string $peer_group, array $peer_group_labels): string {
    $role = strtolower(trim($p['role'] ?? ''));
    if ($peer_group === 'teacher' && $role === 'staff') {
        $lvlQ = $mysqli->prepare("SELECT year_level FROM user_year_levels WHERE user_id=?");
        $pid = (int)$p['id'];
        $lvlQ->bind_param("i", $pid);
        $lvlQ->execute();
        $lvlRes = $lvlQ->get_result();
        $levels = [];
        while ($lr = $lvlRes->fetch_assoc()) $levels[] = $lr['year_level'];
        $lvlQ->close();
        return $levels ? 'Teaching Staff · ' . implode(', ', $levels) : 'Teaching Staff';
    }
    return $p['designation'] ?: ($peer_group_labels[$peer_group] ?? '');
}
function eval_type_label($eval_type, $peer_group = null) {
    switch ($eval_type) {
        case 'student':               return 'Student Evaluation';
        case 'peer':
        case 'faculty_peer':          return 'Peer-to-Peer Evaluation' . ($peer_group ? ' (' . $peer_group . ')' : '');
        case 'school_head':           return 'Dean / Principal Peer Evaluation';
        case 'supervisor_to_teacher':
        case 'supervisor_to_staff':                              return 'Supervisor Evaluation';
        case 'upward_to_ea':                                     return 'Executive Assistant Review';
        default:                      return ucwords(str_replace('_', ' ', $eval_type ?: 'Evaluation'));
    }
}
$peer_group_labels = ['teacher' => 'Faculty', 'staff' => 'Staff', 'school_head' => 'Dean / Principal', 'principal' => 'Principal', 'dean' => 'Dean'];
$peer_school_head_display = 'Dean / Principal';
// Group values that route through the same Dean/Principal targeting +
// question-source pipeline as the old combined 'school_head' group.
$school_head_groups = ['principal', 'dean'];

// ── ADD peer_group COLUMN TO evaluation_tracker (idempotent) ──
// Shared table with the staff dashboard — the column may already exist
// if staff_dashboard.php has run first.
$colChk = $mysqli->query("SHOW COLUMNS FROM evaluation_tracker LIKE 'peer_group'");
if ($colChk && $colChk->num_rows === 0) {
    $mysqli->query("ALTER TABLE evaluation_tracker ADD COLUMN peer_group VARCHAR(20) NULL AFTER eval_type");
}

// ── EVALUATIONUATION ───────────────────────────────────────────
// Step 1: pick a designation group (Teacher or Staff) — a teacher may
// need to peer-evaluate either a fellow teacher or a staff member they
// worked with. Step 2: pick a specific person from that filtered list.
$peers_all       = [];   // eligible faculty/staff peers for the Teacher/Staff tabs
$peers            = [];   // targets shown for the selected evaluation group
$school_heads     = [];   // Dean + Principal targets for the Dean / Principal tab
$principal_targets = [];  // subset of $school_heads with role='principal'
$dean_targets       = []; // subset of $school_heads with role='dean'
$done_peers       = [];
$done_school_heads = [];
$peer_group       = null; // 'teacher' | 'staff' | 'school_head' | null

if ($page === 'peer') {
    $pr2 = $mysqli->prepare("SELECT id, full_name, designation, photo, role, secondary_role FROM users WHERE role IN ('teacher','staff','faculty') AND is_active=1 AND account_status='approved' AND id != ? ORDER BY full_name ASC");
    $pr2->bind_param("i", $user_id);
    $pr2->execute();
    $pr2res = $pr2->get_result();
    if ($pr2res) $peers_all = $pr2res->fetch_all(MYSQLI_ASSOC);
    $pr2->close();

    // Dean / Principal targets come from the same users source used by the
    // Questionnaire / privileged-account feature.  Do not infer school-head
    // status from the free-text designation: the account role is the source
    // of truth. Only active, approved Principal/Dean accounts are eligible —
    // and only for the roles this evaluator's own teaching assignment
    // qualifies them for (see $can_evaluate_principal / $can_evaluate_dean
    // above).
    $eligible_sh_roles = [];
    if ($can_evaluate_principal) $eligible_sh_roles[] = 'principal';
    if ($can_evaluate_dean)      $eligible_sh_roles[] = 'dean';

    if (!empty($eligible_sh_roles)) {
        $roleSlots = implode(',', array_fill(0, count($eligible_sh_roles), '?'));
        $sh = $mysqli->prepare("
            SELECT id, full_name, designation, photo, role
            FROM users
            WHERE role IN ($roleSlots)
              AND is_active=1
              AND account_status='approved'
              AND id != ?
            ORDER BY
              CASE WHEN role='dean' THEN 1 ELSE 2 END,
              full_name ASC
        ");
        $shTypes = str_repeat('s', count($eligible_sh_roles)) . 'i';
        $shArgs  = $eligible_sh_roles;
        $shArgs[] = $user_id;
        $sh->bind_param($shTypes, ...$shArgs);
        $sh->execute();
        $shRes = $sh->get_result();
        if ($shRes) $school_heads = $shRes->fetch_all(MYSQLI_ASSOC);
        $sh->close();
    }

    $principal_targets = array_values(array_filter($school_heads, fn($t) => strtolower($t['role'] ?? '') === 'principal'));
    $dean_targets       = array_values(array_filter($school_heads, fn($t) => strtolower($t['role'] ?? '') === 'dean'));

    if ($period) {
        $period_id_int = (int)$period['id'];

        $dpStmt = $mysqli->prepare("SELECT target_user_id FROM evaluation_tracker WHERE evaluator_id=? AND period_id=? AND eval_type='faculty_peer'");
        $dpStmt->bind_param("ii", $user_id, $period_id_int);
        $dpStmt->execute();
        $dp = $dpStmt->get_result();
        if ($dp) while ($r = $dp->fetch_assoc()) $done_peers[] = (int)$r['target_user_id'];
        $dpStmt->close();

        $shDone = $mysqli->prepare("SELECT target_user_id FROM evaluation_tracker WHERE evaluator_id=? AND period_id=? AND eval_type IN ('faculty_peer','school_head') AND (peer_group IN ('Dean / Principal','School Head','Principal','Dean') OR peer_group IS NULL)");
        $shDone->bind_param("ii", $user_id, $period_id_int);
        $shDone->execute();
        $shDoneRes = $shDone->get_result();
        if ($shDoneRes) while ($r = $shDoneRes->fetch_assoc()) $done_school_heads[] = (int)$r['target_user_id'];
        $shDone->close();
    }

    if (isset($_GET['group']) && in_array($_GET['group'], ['teacher', 'staff', 'principal', 'dean'], true)) {
        $peer_group = $_GET['group'];

        if (in_array($peer_group, $school_head_groups, true)) {
            $peers = array_values(array_filter($school_heads, function ($p) use ($peer_group) {
                return strtolower($p['role'] ?? '') === $peer_group;
            }));
        } else {
            // Classify with the same shared predicates + DB-backed
            // "Non-Teaching Staff" check admin/questionnaire.php's own
            // Peer-to-Peer tab uses (see resolve_peer_group() above), not
            // designation text. Keeps both portals' Peer-to-Peer rosters
            // in agreement.
            $peers = array_values(array_filter($peers_all, function ($p) use ($mysqli, $peer_group) {
                return resolve_peer_group($mysqli, $p) === $peer_group;
            }));
        }
    }
}

// ── EVALUATION FORM ─────────────────────────────────────────────
$peer_target       = null;
$peer_questions    = [];
$peer_form_id      = 0;
$peer_eval_group   = null;
$peer_group_error  = '';

if ($page === 'peer_eval' && isset($_GET['tid'])) {
    $tid = intval($_GET['tid']);
    $req_group = $_GET['group'] ?? null;

    $tu = $mysqli->prepare("SELECT * FROM users WHERE id=? AND is_active=1 AND account_status='approved' LIMIT 1");
    $tu->bind_param("i", $tid);
    $tu->execute();
    $peer_target = $tu->get_result()->fetch_assoc();
    $tu->close();

    if ($peer_target && !in_array($req_group, ['teacher', 'staff', 'principal', 'dean'], true)) {
        $peer_group_error = "Please select an evaluation group.";
        $peer_target = null;
    } elseif ($peer_target) {
        if (in_array($req_group, $school_head_groups, true)) {
            $targetRole = strtolower(trim($peer_target['role'] ?? ''));
            $roleEligible = $req_group === 'principal' ? $can_evaluate_principal : $can_evaluate_dean;
            if ($targetRole !== $req_group) {
                $peer_group_error = "The selected user is not the " . ($req_group === 'principal' ? 'Principal' : 'Dean') . ".";
                $peer_target = null;
            } elseif (!$roleEligible) {
                $peer_group_error = $req_group === 'principal'
                    ? "Evaluating the Principal is only available to Faculty/Teaching Staff with a Grade 7-12 teaching assignment."
                    : "Evaluating the Dean is only available to Faculty/Teaching Staff with a College teaching assignment.";
                $peer_target = null;
            } else {
                $peer_eval_group = $req_group;
            }
        } else {
            $actual_group = resolve_peer_group($mysqli, $peer_target);
            if (!in_array($peer_target['role'] ?? '', ['teacher', 'staff'], true) || $actual_group !== $req_group) {
                $peer_group_error = "The selected user does not belong to the selected evaluation group.";
                $peer_target = null;
            } else {
                $peer_eval_group = $req_group;
            }
        }
    }

    if ($peer_target && !$evaluation_open) {
        $peer_group_error = 'Evaluation is currently closed. It will open at the scheduled time set by the administrator.';
        $peer_target = null;
    }

    if ($peer_target) {
        $form_type = 'faculty_peer';
        $fu = $mysqli->prepare("SELECT id FROM questionnaire_forms WHERE eval_type=? AND is_active=1 ORDER BY id DESC LIMIT 1");
        $fu->bind_param("s", $form_type);
        $fu->execute();
        $fuRes = $fu->get_result();
        if ($fuRes) {
            $fuRow = $fuRes->fetch_assoc();
            $peer_form_id = (int)($fuRow['id'] ?? 0);
        }
        $fu->close();

        // Questionnaire is the single source of truth for Peer-to-Peer questions.
        // Mirror admin/questionnaire.php exactly:
        //   Faculty -> shared evaluation_questions (Teacher / peer)
        //   Staff -> per-user user_questions (Staff / peer)
        //   Dean/Principal -> per-user user_questions (School / peer)
        if (in_array($peer_eval_group, $school_head_groups, true)) {
            $qs = $mysqli->prepare("
                SELECT * FROM user_questions
                WHERE user_id=? AND target_type=? AND eval_type='general'
                ORDER BY category ASC, id ASC
            ");
            $schoolHeadTargetType = $peer_eval_group === 'principal' ? 'Principal' : 'Dean';
            $qs->bind_param("is", $tid, $schoolHeadTargetType);
            $qs->execute();
            $peer_questions = $qs->get_result()->fetch_all(MYSQLI_ASSOC);
            $qs->close();
        } elseif ($peer_eval_group === 'staff') {
            $qs = $mysqli->prepare("
                SELECT * FROM user_questions
                WHERE user_id=? AND target_type='Staff' AND eval_type='general'
                ORDER BY category ASC, id ASC
            ");
            $qs->bind_param("i", $tid);
            $qs->execute();
            $peer_questions = $qs->get_result()->fetch_all(MYSQLI_ASSOC);
            $qs->close();
        } else {
            // Faculty is the shared Peer-to-Peer questionnaire bank.
            // Do not fall back to per-user questions: that would bypass the
            // questionnaire configured by the admin.
            $qs = $mysqli->prepare("
                SELECT * FROM evaluation_questions
                WHERE target_type='Faculty' AND eval_type='general' AND evaluator_role='shared' AND is_active=1
                ORDER BY category ASC, id ASC
            ");
            $qs->execute();
            $peer_questions = $qs->get_result()->fetch_all(MYSQLI_ASSOC);
            $qs->close();
        }

    }
}

// ── NOTIFICATIONS TABLE ───────────────────────────────────────
$mysqli->query("CREATE TABLE IF NOT EXISTS notifications (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    type VARCHAR(50) NOT NULL DEFAULT 'designation_update',
    user_id INT UNSIGNED NOT NULL,
    message TEXT NOT NULL,
    extra_data TEXT NULL,
    is_read TINYINT(1) NOT NULL DEFAULT 0,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_unread (is_read, created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

// ── ROLE CHANGE LOG TABLE ────────────────────────────────────
// This is what powers the admin dashboard's "System Audits" box and bell.
$mysqli->query("CREATE TABLE IF NOT EXISTS role_change_log (
    id               INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id          INT NOT NULL,
    old_role         VARCHAR(60)  NOT NULL DEFAULT '',
    new_role         VARCHAR(60)  NOT NULL DEFAULT '',
    old_designation  VARCHAR(120) NULL,
    new_designation  VARCHAR(120) NULL,
    changed_at       DATETIME    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_changed (changed_at),
    INDEX idx_user    (user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

// ── AUTO-CREATE user_preferences table ───────────────────────
// Shared with the staff dashboard — idempotent, so safe to run here too.
$mysqli->query("CREATE TABLE IF NOT EXISTS user_preferences (
    user_id INT UNSIGNED PRIMARY KEY,
    email_on_designation_update TINYINT(1) NOT NULL DEFAULT 1,
    email_on_new_evaluation     TINYINT(1) NOT NULL DEFAULT 1,
    show_result_details         TINYINT(1) NOT NULL DEFAULT 1,
    compact_dashboard           TINYINT(1) NOT NULL DEFAULT 0,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

// Keep preferences compatible with older installations.
$mysqli->query("ALTER TABLE user_preferences ADD COLUMN IF NOT EXISTS show_result_details TINYINT(1) NOT NULL DEFAULT 1");
$mysqli->query("ALTER TABLE user_preferences ADD COLUMN IF NOT EXISTS compact_dashboard TINYINT(1) NOT NULL DEFAULT 0");

$prefStmt = $mysqli->prepare("SELECT email_on_designation_update, email_on_new_evaluation, show_result_details, compact_dashboard FROM user_preferences WHERE user_id=? LIMIT 1");
$prefStmt->bind_param('i', $user_id);
$prefStmt->execute();
$user_prefs = $prefStmt->get_result()->fetch_assoc() ?: ['email_on_designation_update'=>1,'email_on_new_evaluation'=>1,'show_result_details'=>1,'compact_dashboard'=>0];
$prefStmt->close();

// Save personal dashboard preferences.
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_preferences'])) {
    if (!csrf_check()) { $_SESSION['toast_error'] = 'Your session expired. Please try again.'; header('Location: ' . __FILE__ . '?page=settings'); exit; }
    $p1 = isset($_POST['email_on_designation_update']) ? 1 : 0;
    $p2 = isset($_POST['email_on_new_evaluation']) ? 1 : 0;
    $p3 = isset($_POST['show_result_details']) ? 1 : 0;
    $p4 = isset($_POST['compact_dashboard']) ? 1 : 0;
    $up = $mysqli->prepare("INSERT INTO user_preferences (user_id,email_on_designation_update,email_on_new_evaluation,show_result_details,compact_dashboard) VALUES (?,?,?,?,?) ON DUPLICATE KEY UPDATE email_on_designation_update=VALUES(email_on_designation_update),email_on_new_evaluation=VALUES(email_on_new_evaluation),show_result_details=VALUES(show_result_details),compact_dashboard=VALUES(compact_dashboard)");
    $up->bind_param('iiiii', $user_id, $p1, $p2, $p3, $p4); $up->execute(); $up->close();
    $_SESSION['toast'] = 'Settings saved successfully.';
    header('Location: ' . __FILE__ . '?page=settings'); exit;
}

// ── ENSURE A UNIQUE CONSTRAINT BACKS THE DUPLICATE-EVAL CHECK ──
// The application-level duplicate check below (SELECT ... then INSERT) has
// a race window between two near-simultaneous submissions. This unique
// index makes the DB itself the source of truth: a second insert for the
// same (evaluator, target, eval_type, period) fails with a duplicate-key
// error instead of silently creating a second row. Shared with the staff
// dashboard's evaluation_tracker usage — idempotent, so safe to run here too.
$idxChk = $mysqli->query("SHOW INDEX FROM evaluation_tracker WHERE Key_name = 'uniq_eval_submission'");
if ($idxChk && $idxChk->num_rows === 0) {
    $mysqli->query("ALTER TABLE evaluation_tracker ADD UNIQUE INDEX uniq_eval_submission (evaluator_id, target_user_id, eval_type, period_id)");
}

// ── SUBMIT EVALUATION ──────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_peer'])) {
    if (!csrf_check()) {
        $_SESSION['toast_error'] = "Your session expired or the request could not be verified. Please try again.";
        header("Location: faculty_dashboard.php?page=peer"); exit;
    }

    $submitted_group = $_POST['group'] ?? '';
    $tid     = intval($_POST['target_id'] ?? 0);
    $pid     = intval($_POST['period_id'] ?? 0);
    $ratings = $_POST['ratings'] ?? [];
    $comment = trim($_POST['comment'] ?? '');

    if (!in_array($submitted_group, ['teacher', 'staff', 'principal', 'dean'], true)) {
        $_SESSION['toast_error'] = "Please select an evaluation group.";
        header("Location: faculty_dashboard.php?page=peer"); exit;
    }

    $eval_type = 'faculty_peer';
    $form_type = 'faculty_peer';
    $fid = 0;

    $formStmt = $mysqli->prepare("SELECT id FROM questionnaire_forms WHERE eval_type=? AND is_active=1 ORDER BY id DESC LIMIT 1");
    $formStmt->bind_param("s", $form_type);
    $formStmt->execute();
    $formRow = $formStmt->get_result()->fetch_assoc();
    $fid = (int)($formRow['id'] ?? 0);
    $formStmt->close();

    if ($fid <= 0) {
        $_SESSION['toast_error'] = "The Evaluation form is not available or is inactive.";
        header("Location: faculty_dashboard.php?page=peer&group=" . urlencode($submitted_group)); exit;
    }

    // Re-check the live schedule on the server. This blocks stale pages and
    // direct POSTs before the configured opening time and at/after closing.
    if (!fs_schedule_is_open($mysqli)) {
        $_SESSION['toast_error'] = "Evaluation is currently closed. Please return at the scheduled opening time.";
        header("Location: faculty_dashboard.php?page=peer&group=" . urlencode($submitted_group)); exit;
    }

    // Never trust the period id carried by the browser. Use the currently
    // active period after the live schedule check.
    $currentPeriod = null;
    $currentPeriodResult = $mysqli->query("SELECT * FROM evaluation_periods WHERE is_active=1 LIMIT 1");
    if ($currentPeriodResult) $currentPeriod = $currentPeriodResult->fetch_assoc();
    if (!$currentPeriod) {
        $_SESSION['toast_error'] = "No evaluation period is currently open.";
        header("Location: faculty_dashboard.php?page=peer&group=" . urlencode($submitted_group)); exit;
    }
    $pid = (int)$currentPeriod['id'];

    // Re-validate the target server-side. Dean/Principal targets are strictly
    // limited to the specific role requested, and the evaluator must
    // currently be eligible for that role by their own teaching assignment —
    // re-derived here (not trusted from the submitted form), since an
    // assignment can change between page load and submit.
    if (in_array($submitted_group, $school_head_groups, true)) {
        $roleEligible = $submitted_group === 'principal' ? $can_evaluate_principal : $can_evaluate_dean;
        if (!$roleEligible) {
            $_SESSION['toast_error'] = $submitted_group === 'principal'
                ? "Evaluating the Principal is only available to Faculty/Teaching Staff with a Grade 7-12 teaching assignment."
                : "Evaluating the Dean is only available to Faculty/Teaching Staff with a College teaching assignment.";
            header("Location: faculty_dashboard.php?page=peer"); exit;
        }

        $tchk = $mysqli->prepare("SELECT id, full_name, designation, role FROM users WHERE id=? AND is_active=1 LIMIT 1");
        $tchk->bind_param("i", $tid);
        $tchk->execute();
        $tchkRow = $tchk->get_result()->fetch_assoc();
        $tchk->close();

        $targetRole = strtolower(trim($tchkRow['role'] ?? ''));

        if (!$tchkRow || $targetRole !== $submitted_group || $tid === (int)$user_id) {
            $_SESSION['toast_error'] = "The selected " . ($submitted_group === 'principal' ? 'Principal' : 'Dean') . " is no longer available.";
            header("Location: faculty_dashboard.php?page=peer&group=" . urlencode($submitted_group)); exit;
        }

        // Peer-to-Peer Questionnaire -> Dean / Principal uses the per-user
        // School target under eval_type='peer'. This is deliberately separate
        // from the Dean/Principal Evaluation (school_head) questionnaire.
        $validQStmt = $mysqli->prepare("
            SELECT id
            FROM user_questions
            WHERE user_id=? AND target_type=? AND eval_type='general'
        ");
        $targetType = $submitted_group === 'principal' ? 'Principal' : 'Dean';
        $validQStmt->bind_param("is", $tid, $targetType);
        $validQStmt->execute();
        $validQRes = $validQStmt->get_result();
        $valid_question_ids = [];
        if ($validQRes) while ($vq = $validQRes->fetch_assoc()) $valid_question_ids[] = (int)$vq['id'];
        $validQStmt->close();
        $question_source = 'user';

        if (empty($valid_question_ids)) {
            $_SESSION['toast_error'] = "No " . ($submitted_group === 'principal' ? 'Principal' : 'Dean') . " questions have been assigned to this target in the Peer-to-Peer Questionnaire.";
            header("Location: faculty_dashboard.php?page=peer&group=" . urlencode($submitted_group)); exit;
        }
    } else {
        [$eligible, $eligMsg] = canPeerEvaluate($mysqli, $user_id, $tid);
        if (!$eligible) {
            $_SESSION['toast_error'] = $eligMsg;
            header("Location: faculty_dashboard.php?page=peer&group=" . urlencode($submitted_group)); exit;
        }

        $tchk = $mysqli->prepare("SELECT id, designation, role, secondary_role FROM users WHERE id=? AND role IN ('teacher','staff','faculty') AND is_active=1 LIMIT 1");
        $tchk->bind_param("i", $tid);
        $tchk->execute();
        $tchkRow = $tchk->get_result()->fetch_assoc();
        $tchk->close();

        if (!$tchkRow || resolve_peer_group($mysqli, $tchkRow) !== $submitted_group) {
            $_SESSION['toast_error'] = "The selected faculty member is no longer available in this evaluation group.";
            header("Location: faculty_dashboard.php?page=peer&group=" . urlencode($submitted_group)); exit;
        }

        // Match the exact Peer-to-Peer Questionnaire source used to build
        // the form. Faculty is the shared Teacher pool; Staff is the selected
        // person's Staff pool.
        $valid_question_ids = [];
        if ($submitted_group === 'teacher') {
            $validQStmt = $mysqli->prepare("SELECT id FROM evaluation_questions WHERE target_type='Faculty' AND eval_type='general' AND evaluator_role='shared' AND is_active=1");
            $validQStmt->execute();
            $validQRes = $validQStmt->get_result();
            if ($validQRes) while ($vq = $validQRes->fetch_assoc()) $valid_question_ids[] = (int)$vq['id'];
            $validQStmt->close();
            $question_source = 'evaluation';
        } else {
            $validQStmt = $mysqli->prepare("SELECT id FROM user_questions WHERE user_id=? AND target_type='Staff' AND eval_type='general'");
            $validQStmt->bind_param("i", $tid);
            $validQStmt->execute();
            $validQRes = $validQStmt->get_result();
            if ($validQRes) while ($vq = $validQRes->fetch_assoc()) $valid_question_ids[] = (int)$vq['id'];
            $validQStmt->close();
            $question_source = 'user';
        }
    }

    $ratings = array_filter($ratings, function ($val, $qid) use ($valid_question_ids) {
        return in_array((int)$qid, $valid_question_ids, true);
    }, ARRAY_FILTER_USE_BOTH);

    if (empty($valid_question_ids) || count($ratings) !== count($valid_question_ids)) {
        $_SESSION['toast_error'] = "Please rate all questions before submitting.";
        header("Location: faculty_dashboard.php?page=peer_eval&tid=" . $tid . "&group=" . urlencode($submitted_group)); exit;
    }

    $dup = $mysqli->prepare("SELECT id FROM evaluation_tracker WHERE evaluator_id=? AND target_user_id=? AND period_id=? AND eval_type=? LIMIT 1");
    $dup->bind_param("iiis", $user_id, $tid, $pid, $eval_type);
    $dup->execute();
    $dup->store_result();

    if ($dup->num_rows === 0) {
        $dup->close();
        try {
            $mysqli->begin_transaction();

            $overall = round(array_sum($ratings) / count($ratings), 2);
            $group_label = $peer_group_labels[$submitted_group] ?? ucfirst($submitted_group);

            $ins = $mysqli->prepare("
                INSERT INTO evaluation_tracker
                (evaluator_id,target_user_id,form_id,period_id,eval_type,peer_group,score,remarks,status,submitted_at)
                VALUES (?,?,?,?,?,?,?,?,'submitted',NOW())
            ");
            $ins->bind_param("iiiissds", $user_id, $tid, $fid, $pid, $eval_type, $group_label, $overall, $comment);
            $ins->execute();
            $tracker_id = $mysqli->insert_id;
            $ins->close();

            if ($question_source === 'user') {
                $ri = $mysqli->prepare("INSERT INTO questionnaire_answers (tracker_id,question_id,question_source,user_question_id,answer_score,submitted_at) VALUES (?,NULL,'user',?,?,NOW())");
            } else {
                $ri = $mysqli->prepare("INSERT INTO questionnaire_answers (tracker_id,question_id,question_source,user_question_id,answer_score,submitted_at) VALUES (?,?,'evaluation',NULL,?,NOW())");
            }
            foreach ($ratings as $qid => $rating) {
                $qid = intval($qid);
                $rating = min(5, max(1, intval($rating)));
                $ri->bind_param("iii", $tracker_id, $qid, $rating);
                $ri->execute();
            }
            $ri->close();

            $mysqli->commit();

            $notif_msg = in_array($submitted_group, $school_head_groups, true)
                ? "You have received a new " . ($submitted_group === 'principal' ? 'Principal' : 'Dean') . " evaluation."
                : "You have received a new evaluation.";
            $nins = $mysqli->prepare("INSERT INTO notifications (type, user_id, message) VALUES ('evaluation_received', ?, ?)");
            $nins->bind_param("is", $tid, $notif_msg);
            $nins->execute();
            $nins->close();

            $_SESSION['toast'] = "Evaluation submitted!";
        } catch (Exception $e) {
            $mysqli->rollback();
            error_log('[faculty_dashboard] submit evaluation failed for evaluator=' . $user_id . ' target=' . $tid . ': ' . $e->getMessage());
            $_SESSION['toast_error'] = (($mysqli->errno ?? 0) === 1062)
                ? "You already evaluated this person this period."
                : "Submission failed. Please try again.";
        }
    } else {
        $dup->close();
        $_SESSION['toast_error'] = "You already evaluated this person this period.";
    }

    header("Location: faculty_dashboard.php?page=peer&group=" . urlencode($submitted_group)); exit;
}

// ── UPDATE DESIGNATION ────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_designation'])) {
    if (!csrf_check()) {
        $_SESSION['toast_error'] = "Your session expired or the request could not be verified. Please try again.";
        header("Location: faculty_dashboard.php?page=profile"); exit;
    }
    $new_desig = trim($_POST['new_designation'] ?? '');
    $old_desig = $designation;

    if (empty($new_desig)) {
        $_SESSION['toast_error'] = "Designation cannot be empty.";
    } elseif (preg_replace('/\s+/', ' ', mb_strtolower($new_desig)) === preg_replace('/\s+/', ' ', mb_strtolower(trim((string)$old_desig)))) {
        $_SESSION['toast_error'] = "That's already your current designation.";
    } else {
        $stmt = $mysqli->prepare("UPDATE users SET designation=? WHERE id=?");
        $stmt->bind_param("si", $new_desig, $user_id);
        $stmt->execute(); $stmt->close();

        $_SESSION['designation'] = $new_desig;
        $designation = $new_desig;

        // No self-notification here: the person just made this change themselves.
        // The admin is still informed through role_change_log below.

        // Also log to role_change_log so it shows up in the admin dashboard's
        // System Audits box and notification bell.
        $rstmt = $mysqli->prepare("INSERT INTO role_change_log (user_id, old_role, new_role, old_designation, new_designation) VALUES (?, 'faculty', 'faculty', ?, ?)");
        $rstmt->bind_param("iss", $user_id, $old_desig, $new_desig);
        $rstmt->execute(); $rstmt->close();

        $_SESSION['toast'] = "Your designation has been updated to \"$new_desig\".";
    }
    header("Location: faculty_dashboard.php?page=profile"); exit;
}

// ── UPDATE TEACHING LEVEL(S) ──────────────────────────────────
// Multi-select — a faculty member can be assigned to more than one
// specific year level at once (e.g. teaches both Grade 8 and Grade 10).
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_levels'])) {
    if (!csrf_check()) {
        $_SESSION['toast_error'] = "Your session expired or the request could not be verified. Please try again.";
        header("Location: faculty_dashboard.php?page=profile"); exit;
    }
    $selected = array_values(array_intersect($_POST['levels'] ?? [], $year_levels));

    $mysqli->begin_transaction();
    try {
        $del = $mysqli->prepare("DELETE FROM user_year_levels WHERE user_id=?");
        $del->bind_param("i", $user_id);
        $del->execute();
        $del->close();

        if (!empty($selected)) {
            $ins = $mysqli->prepare("INSERT INTO user_year_levels (user_id, year_level) VALUES (?, ?)");
            foreach ($selected as $lvl) {
                $ins->bind_param("is", $user_id, $lvl);
                $ins->execute();
            }
            $ins->close();
        }
        $mysqli->commit();

        $_SESSION['toast'] = !empty($selected)
            ? "Your teaching level(s) updated to: " . implode(', ', $selected) . "."
            : "Your teaching level assignment has been cleared. Students won't be able to evaluate you until a level is set.";
    } catch (Exception $e) {
        $mysqli->rollback();
        error_log('[faculty_dashboard] update_levels failed for user_id=' . $user_id . ': ' . $e->getMessage());
        $_SESSION['toast_error'] = "Failed to update teaching levels. Please try again.";
    }
    header("Location: faculty_dashboard.php?page=profile"); exit;
}


// ── RECENT SUBMISSIONS ────────────────────────────────────────
// faculty_dashboard.php — recent_subs
$rsStmt = $mysqli->prepare("
    SELECT et.id AS tracker_id,
           (SELECT AVG(qa.answer_score) FROM questionnaire_answers qa WHERE qa.tracker_id = et.id) AS overall_score,
           et.eval_type, et.peer_group, et.submitted_at, ep.period_label, ep.semester
    FROM evaluation_tracker et
    LEFT JOIN evaluation_periods ep ON ep.id = et.period_id
    WHERE et.target_user_id = ?
    ORDER BY et.submitted_at DESC LIMIT 5
");
$rsStmt->bind_param("i", $user_id);
$rsStmt->execute();
$rs = $rsStmt->get_result();
if ($rs) $recent_subs = $rs->fetch_all(MYSQLI_ASSOC);
$rsStmt->close();

// ── MARK NOTIFICATIONS READ ──────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['mark_notifications_read'])) {
    if (!csrf_check()) {
        header("Location: faculty_dashboard.php?page=" . urlencode($page)); exit;
    }
    $mr = $mysqli->prepare("UPDATE notifications SET is_read=1 WHERE user_id=? AND is_read=0");
    $mr->bind_param("i", $user_id);
    $mr->execute(); $mr->close();
    header("Location: faculty_dashboard.php?page=" . urlencode($page)); exit;
}

// ── MY NOTIFICATIONS (bell) ──────────────────────────────────
$my_notifications = [];
$unread_count     = 0;
$nq = $mysqli->prepare("SELECT id, type, message, extra_data, is_read, created_at FROM notifications WHERE user_id=? ORDER BY created_at DESC, id DESC LIMIT 15");
$nq->bind_param("i", $user_id);
$nq->execute();
$nres = $nq->get_result();
if ($nres) $my_notifications = $nres->fetch_all(MYSQLI_ASSOC);
$nq->close();
foreach ($my_notifications as &$notification) {
    $notification['message'] = notification_message_for_view($notification, (int)$user_id);
}
unset($notification);
foreach ($my_notifications as $n) if (empty($n['is_read'])) $unread_count++;

$desig_suggestions = ['Teacher','Registrar','Cashier','Bookkeeper','Librarian','Guidance','Nurse','Personnel','Adviser','Coordinator','Department Head'];

$toast       = $_SESSION['toast']       ?? ''; unset($_SESSION['toast']);
$toast_error = $_SESSION['toast_error'] ?? ''; unset($_SESSION['toast_error']);

// Helpers
$first_name = explode(',', $full_name)[0] ?? $full_name;
$parts      = explode(' ', trim($full_name));
$initials   = strtoupper(substr($parts[0], 0, 1) . (isset($parts[1]) ? substr($parts[1], 0, 1) : ''));
$perf_label = $my_avg === null ? '—' : ($my_avg >= 4 ? 'Excellent' : ($my_avg >= 3 ? 'Good' : 'Needs Improvement'));
$perf_color = $my_avg === null ? '#6b7280' : ($my_avg >= 4 ? '#4ade80' : ($my_avg >= 3 ? '#facc15' : '#f87171'));
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"/>
<meta name="viewport" content="width=device-width, initial-scale=1.0"/>
<title>Faculty Dashboard — PBI</title>
<script>
// Prevent a flash of the wrong theme: if the saved preference is "dark" but the
// page markup below defaults to class="light-theme", hide the page until the
// real theme JS (further down) applies the correct class and reveals it.
(function(){
    try{
        if (localStorage.getItem('pbi_theme') === 'dark') {
            document.write('<style id="theme-fouc-guard">body{visibility:hidden}</style>');
        }
    }catch(e){}
})();
</script>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css"/>
<link href="https://fonts.googleapis.com/css2?family=Rajdhani:wght@600;700&family=DM+Sans:wght@400;500;600&display=swap" rel="stylesheet"/>
<style>
:root{
    --dark:#0A192F; --mid:#172A45; --inner:#0F1F3D;
    --accent:#2563EB; --hover:#1D4ED8;
    --teal:#2563EB; --teal-light:rgba(37,99,235,.14); --teal-hover:#1D4ED8;
    --light:#E0E6F0; --muted:#A0B3C6;
    --danger:#F05454; --success:#22C55E;
    --border:rgba(255,255,255,0.08);
    --radius:12px; --shadow:0 4px 24px rgba(0,0,0,.35);
    --sidebar-w:240px;
}
body.light-theme{
    --dark:#F3F6FB; --mid:#FFFFFF; --inner:#EEF2F8;
    --accent:#2563EB; --hover:#1D4ED8;
    --teal:#2563EB; --teal-light:rgba(37,99,235,.10); --teal-hover:#1D4ED8;
    --light:#16263B; --muted:#5B7186;
    --danger:#DC2626; --success:#16A34A;
    --border:rgba(15,31,61,0.10);
    --shadow:0 4px 24px rgba(15,31,61,.08);
}
body.light-theme .sidebar-brand:hover{background:rgba(15,31,61,.04);}
body.light-theme .nav-link:hover{background:rgba(15,31,61,.05);}
body.light-theme .cat-bar-bg{background:rgba(15,31,61,.08);}
body.light-theme .scale-legend-bar{background:rgba(15,31,61,.03);}
body.light-theme .btn-cancel-new:hover{background:rgba(15,31,61,.06);}
body.light-theme .suggestion-chip{background:rgba(15,31,61,.04);}
body.light-theme .notif-btn{background:rgba(15,31,61,.06);}
body.light-theme .notif-item{border-bottom:1px solid rgba(15,31,61,.07);}
body.light-theme .profile-dd-btn:hover{background:rgba(15,31,61,.06);}
body.light-theme .profile-dd-icon{background:rgba(15,31,61,.06);}
body.light-theme .dd-appearance-val{background:rgba(15,31,61,.06);}
body.light-theme .sidebar-title,
body.light-theme .nav-link.active,
body.light-theme .nav-page-title,
body.light-theme .welcome-text h2,
body.light-theme .stat-card-val,
body.light-theme .section-title,
body.light-theme .eval-modal-title,
body.light-theme .eval-info-value,
body.light-theme .peer-name,
body.light-theme .eval-name,
body.light-theme .q-text-new,
body.light-theme .profile-name,
body.light-theme .notif-header-title,
body.light-theme .eval-group-title,
body.light-theme .photo-modal-title{color:var(--light);}
body.light-theme .level-view-empty,
body.light-theme .peer-select-hint.warn,
body.light-theme .no-period-warn{color:#B45309;}
body.light-theme .notif-btn:hover,
body.light-theme .notif-btn.has-unread{color:#B45309;}
body.light-theme .info-note i{color:#2563EB;}
body.light-theme .btn-view-all-evals{color:#2563EB;}
body.light-theme .toast-success{color:#15803D;}
body.light-theme .toast-error{color:#B91C1C;}
body.light-theme .level-view-pill{color:#0F766E;}
.nav-icon-blue{color:#3B82F6;}
.nav-icon-purple{color:#8B5CF6;}
.nav-icon-green{color:#22C55E;}
.nav-icon-orange{color:#F97316;}
.profile-dd-icon.dd-icon-blue{background:rgba(59,130,246,.14);color:#3B82F6;}
.profile-dd-icon.dd-icon-amber{background:rgba(217,119,6,.14);color:#D97706;}
.profile-dd-icon.dd-icon-purple{background:rgba(139,92,246,.14);color:#8B5CF6;}
.profile-dd-btn:hover .profile-dd-icon.dd-icon-blue{background:rgba(59,130,246,.24);}
.profile-dd-btn:hover .profile-dd-icon.dd-icon-amber{background:rgba(217,119,6,.24);}
.profile-dd-btn:hover .profile-dd-icon.dd-icon-purple{background:rgba(139,92,246,.24);}
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0;}
body{font-family:'DM Sans',sans-serif;background:var(--dark);color:var(--light);min-height:100vh;display:flex;}
.sidebar{width:var(--sidebar-w);flex-shrink:0;background:var(--mid);border-right:1px solid var(--border);display:flex;flex-direction:column;position:fixed;top:0;left:0;height:100vh;z-index:40;transition:transform .3s;}
.sidebar-brand{padding:16px 18px;border-bottom:1px solid var(--border);display:flex;align-items:center;gap:12px;cursor:pointer;transition:background .2s;position:relative;}
.sidebar-brand:hover{background:rgba(255,255,255,.04);}
.brand-avatar{width:42px;height:42px;border-radius:50%;flex-shrink:0;border:2px solid var(--teal);box-shadow:0 0 10px rgba(13,148,136,.3);overflow:hidden;background:var(--inner);display:flex;align-items:center;justify-content:center;}
.brand-avatar img{width:100%;height:100%;object-fit:cover;display:block;}
.brand-avatar .brand-initials{font-family:'Rajdhani',sans-serif;font-size:15px;font-weight:700;color:var(--teal-hover);line-height:1;}
.sidebar-title{font-family:'Rajdhani',sans-serif;font-size:14px;font-weight:700;letter-spacing:.4px;color:#fff;line-height:1.3;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:118px;}
.sidebar-sub{font-size:11px;color:var(--teal-hover);letter-spacing:.2px;margin-top:2px;font-weight:600;}
.sidebar-caret{font-size:11px;color:var(--muted);flex-shrink:0;transition:transform .2s;}
.sidebar-profile-dropdown{margin:0 12px;max-height:0;opacity:0;overflow:hidden;background:var(--inner);border-radius:10px;transition:max-height .2s ease,opacity .2s ease,margin .2s ease;}
.sidebar-profile-dropdown.open{max-height:320px;opacity:1;margin:8px 12px 10px;border:1px solid var(--border);}
.sidebar-nav{flex:1;padding:14px 12px;overflow-y:auto;}
.nav-section-label{font-size:11px;font-weight:900;text-transform:uppercase;letter-spacing:1.5px;color:#99F6E4;padding:0 8px;margin-bottom:7px;margin-top:18px;text-align:center;text-shadow:0 1px 8px rgba(45,212,191,.14);}
.nav-section-label:first-child{margin-top:0;}
.nav-link{display:flex;align-items:center;gap:10px;padding:10px 12px;border-radius:9px;color:var(--muted);text-decoration:none;font-size:13.5px;font-weight:600;transition:all .2s;margin-bottom:2px;}
.nav-link:hover{background:rgba(255,255,255,.05);color:var(--light);}
.nav-link.active{background:rgba(13,148,136,.16);color:#fff;}
.nav-link.active i{color:var(--teal-hover);}
.nav-link i{font-size:14px;width:17px;text-align:center;flex-shrink:0;}
.nav-badge{margin-left:auto;background:var(--teal);color:#fff;font-size:10px;font-weight:700;padding:1px 7px;border-radius:20px;}
.sidebar-footer{padding:14px 16px;border-top:1px solid var(--border);}
.btn-logout-side{display:flex;align-items:center;gap:8px;width:100%;padding:9px 13px;border:1px solid rgba(240,84,84,.3);background:rgba(240,84,84,.07);border-radius:9px;color:#f87171;font-size:13px;font-weight:600;cursor:pointer;text-decoration:none;transition:all .2s;font-family:'DM Sans',sans-serif;}
.btn-logout-side:hover{background:rgba(240,84,84,.16);}
.top-nav{position:fixed;top:0;left:var(--sidebar-w);right:0;height:58px;z-index:30;background:var(--mid);border-bottom:1px solid var(--border);display:flex;align-items:center;justify-content:space-between;padding:0 26px;box-shadow:var(--shadow);}
.nav-page-title{font-family:'Rajdhani',sans-serif;font-size:19px;font-weight:700;letter-spacing:.4px;color:#fff;}
.period-badge{background:rgba(13,148,136,.14);border:1px solid rgba(13,148,136,.28);color:var(--teal-hover);padding:5px 13px;border-radius:20px;font-size:12px;font-weight:600;display:flex;align-items:center;gap:6px;}
.hamburger{display:none;background:none;border:none;color:var(--light);font-size:20px;cursor:pointer;padding:4px;}
.main{margin-left:var(--sidebar-w);margin-top:58px;padding:26px 28px;flex:1;min-height:calc(100vh - 58px);}
.toast{border-radius:9px;padding:11px 18px;font-size:13px;margin-bottom:20px;display:flex;align-items:center;gap:9px;animation:fadeUp .3s ease;}
.toast-success{background:rgba(34,197,94,.1);border:1px solid rgba(34,197,94,.28);color:#86efac;}
.toast-error{background:rgba(240,84,84,.1);border:1px solid rgba(240,84,84,.28);color:#fca5a5;}
@keyframes fadeUp{from{opacity:0;transform:translateY(-6px)}to{opacity:1;transform:none}}
.evaluation-schedule-card{background:#fff;border:1px solid rgba(30,82,144,.14);border-radius:16px;padding:22px 26px;margin:0 0 22px;box-shadow:0 5px 18px rgba(15,35,60,.035);}
.schedule-summary-grid{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:20px;align-items:start;}
.schedule-summary-item{min-width:0;}
.schedule-label{display:block;font-size:11px;font-weight:800;letter-spacing:.08em;text-transform:uppercase;color:#E19A1D;margin-bottom:7px;}
.schedule-summary-item strong{display:block;font-size:15px;line-height:1.25;color:#10243E;font-weight:700;white-space:normal;}
.schedule-status{display:inline-flex;align-items:center;padding:6px 14px;border-radius:999px;font-size:12px;font-weight:700;border:1px solid #F3B0B0;background:#FFF1F1;color:#B42318;}
.schedule-status.open{border-color:#91E7B2;background:#E8FFF1;color:#168447;}
.schedule-status.closed{border-color:#F3B0B0;background:#FFF1F1;color:#B42318;}
.schedule-times{margin-top:19px;padding-top:16px;border-top:1px solid rgba(30,82,144,.10);}
.schedule-message{display:flex;align-items:center;gap:7px;flex-wrap:wrap;font-size:13px;color:#5C7187;}
.schedule-message i{color:#E19A1D;font-size:15px;margin-right:2px;}
.schedule-message strong{color:#10243E;}
@media(max-width:900px){.schedule-summary-grid{grid-template-columns:repeat(2,minmax(0,1fr));}}
@media(max-width:600px){.evaluation-schedule-card{padding:18px 20px;}.schedule-summary-grid{grid-template-columns:1fr 1fr;gap:16px;}.schedule-message{display:block;line-height:1.7;}.schedule-message i{margin-right:5px;}}
.welcome-bar{background:linear-gradient(135deg, var(--mid) 0%, rgba(13,148,136,.12) 100%);border:1px solid rgba(13,148,136,.18);border-radius:var(--radius);padding:24px 28px;margin-bottom:22px;display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:16px;}
.welcome-text h2{font-family:'Rajdhani',sans-serif;font-size:22px;font-weight:700;color:#fff;margin-bottom:5px;letter-spacing:.3px;}
.welcome-text p{font-size:13px;color:var(--muted);line-height:1.5;}
.welcome-text p strong{color:var(--teal-hover);}
.score-chip{background:rgba(13,148,136,.13);border:1px solid rgba(13,148,136,.25);border-radius:12px;padding:16px 26px;text-align:center;min-width:120px;flex:1 1 auto;max-width:340px;flex-shrink:0;align-self:stretch;display:flex;flex-direction:column;align-items:center;justify-content:center;}
.score-chip .sc-val{font-family:'Rajdhani',sans-serif;font-size:44px;font-weight:700;color:var(--teal-hover);line-height:1;}
.score-chip .sc-lbl{font-size:11px;color:var(--muted);text-transform:uppercase;letter-spacing:1px;margin-top:8px;}
.stats-grid{display:grid;grid-template-columns:repeat(4,1fr);gap:14px;margin-bottom:22px;}
.stat-card{background:var(--mid);border:1px solid var(--border);border-radius:var(--radius);padding:20px 22px;}
.stat-card-lbl{font-size:11px;text-transform:uppercase;letter-spacing:.9px;color:var(--muted);margin-bottom:12px;font-weight:700;}
.stat-card-val{font-size:28px;font-weight:700;color:#fff;line-height:1;}
.stat-card-val.teal{color:var(--teal-hover);}
.stat-card-val.gold{color:#F59E0B;}
.stat-card-val.sm{font-size:18px;margin-top:2px;}
.section-card{background:var(--mid);border:1px solid var(--border);border-radius:var(--radius);padding:22px 24px;margin-bottom:20px;}
.section-title{font-family:'Rajdhani',sans-serif;font-size:17px;font-weight:700;color:#fff;margin-bottom:16px;display:flex;align-items:center;gap:8px;}
.section-title i{font-size:16px;}
.cat-row{display:flex;align-items:center;gap:12px;margin-bottom:11px;}
.cat-name{font-size:13px;color:var(--light);width:200px;flex-shrink:0;}
.cat-bar-bg{flex:1;height:6px;background:rgba(255,255,255,.06);border-radius:4px;overflow:hidden;}
.cat-bar-fill{height:100%;border-radius:4px;transition:width .5s ease;}
.cat-score{font-size:13px;font-weight:700;width:36px;text-align:right;flex-shrink:0;}
.eval-item{
    background:var(--inner);
    border:1px solid var(--border);
    border-radius:10px;
    padding:14px 18px;
    margin-bottom:8px;
    display:flex;
    justify-content:space-between;
    align-items:center;
    flex-wrap:wrap;
    gap:10px;
}
.eval-item-left .anon{font-size:13px;color:var(--light);font-weight:600;display:flex;align-items:center;gap:5px;}
.eval-item-left .meta{font-size:12px;color:var(--muted);margin-top:2px;}
.btn-view-details{background:rgba(13,148,136,.13);border:1px solid rgba(13,148,136,.3);color:var(--teal-hover);font-size:11px;font-weight:700;padding:5px 12px;border-radius:20px;cursor:pointer;white-space:nowrap;font-family:'DM Sans',sans-serif;}
.btn-view-all-evals{
    width:100%;
    display:flex;
    align-items:center;
    gap:10px;
    background:rgba(43,108,176,.12);
    border:1px solid rgba(43,108,176,.3);
    color:#5b9bd8;
    font-size:13.5px;
    font-weight:700;
    padding:12px 16px;
    border-radius:10px;
    cursor:pointer;
    font-family:'DM Sans',sans-serif;
    transition:background .2s;
}
.btn-view-all-evals:hover{background:rgba(43,108,176,.2);}

.eval-modal-overlay{position:fixed;inset:0;background:rgba(0,0,0,.75);z-index:300;display:none;align-items:center;justify-content:center;padding:20px;}
.eval-modal-overlay.open{display:flex;}
.eval-modal{background:var(--mid);border:1px solid var(--border);border-radius:18px;width:100%;max-width:640px;max-height:88vh;display:flex;flex-direction:column;box-shadow:0 20px 60px rgba(0,0,0,.6);}
.eval-modal-header{padding:20px 24px;border-bottom:1px solid var(--border);display:flex;align-items:center;justify-content:space-between;flex-shrink:0;}
.eval-modal-title{font-family:'Rajdhani',sans-serif;font-size:19px;font-weight:700;color:#fff;}
.eval-modal-close{background:none;border:none;color:var(--muted);font-size:18px;cursor:pointer;}
.eval-modal-body{padding:22px 24px;overflow-y:auto;}
.eval-info-grid{display:grid;grid-template-columns:1fr 1fr;gap:14px;margin-bottom:20px;}
.eval-info-item{background:var(--inner);border:1px solid var(--border);border-radius:10px;padding:12px 14px;}
.eval-info-label{font-size:10px;text-transform:uppercase;letter-spacing:.8px;color:var(--muted);font-weight:700;margin-bottom:5px;}
.eval-info-value{font-size:13.5px;color:#fff;font-weight:600;}
.eval-modal-loading{text-align:center;padding:50px 20px;color:var(--muted);}
.eval-modal-loading i{font-size:26px;margin-bottom:10px;display:block;}
.eval-q-star{color:#374151;font-size:14px;}
.eval-q-star.filled{color:#facc15;}
.eval-comment-box{background:var(--inner);border:1px solid var(--border);border-radius:10px;padding:15px 17px;font-size:13.5px;color:var(--light);line-height:1.6;font-style:italic;}
.eval-comment-box.empty{color:var(--muted);font-style:normal;}
.score-pill{padding:4px 13px;border-radius:20px;font-size:13px;font-weight:700;}
.empty-state{text-align:center;padding:44px 20px;color:var(--muted);}
.empty-state i{font-size:36px;opacity:.25;display:block;margin-bottom:12px;}
.empty-state p{font-size:13px;}
.peer-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(150px,1fr));gap:16px;align-items:stretch;}
.peer-card{background:var(--mid);border:2px solid var(--border);border-radius:var(--radius);padding:18px 12px;display:flex;flex-direction:column;align-items:center;justify-content:flex-start;gap:8px;text-align:center;transition:all .2s;height:100%;box-sizing:border-box;}
.peer-card:hover:not(.done){border-color:var(--teal);transform:translateY(-3px);box-shadow:0 6px 20px rgba(0,0,0,.3);}
.peer-card.done{opacity:.6;cursor:not-allowed;border-color:rgba(34,197,94,.3);}
.peer-photo{width:68px;height:68px;border-radius:50%;object-fit:cover;border:2px solid var(--border);}
.peer-photo-ph{width:68px;height:68px;border-radius:50%;background:var(--inner);border:2px solid var(--border);display:flex;align-items:center;justify-content:center;color:var(--muted);font-size:22px;}
.peer-photo,.peer-photo-ph{flex-shrink:0;}
.peer-name{font-size:13px;font-weight:600;color:#fff;line-height:1.3;min-height:2.6em;display:flex;align-items:center;justify-content:center;width:100%;}
.peer-desig{font-size:11px;color:var(--muted);line-height:1.35;width:100%;flex:1 1 auto;display:flex;align-items:flex-start;justify-content:center;}
.done-badge{background:rgba(34,197,94,.14);color:#4ade80;border-radius:20px;padding:2px 10px;font-size:11px;font-weight:700;margin-top:auto;display:inline-flex;align-items:center;gap:4px;min-height:29px;box-sizing:border-box;}
.btn-eval-peer{margin-top:auto;width:100%;padding:7px 0;border:none;border-radius:7px;background:var(--teal);color:#fff;font-size:12px;font-weight:700;cursor:pointer;transition:background .2s;font-family:'DM Sans',sans-serif;}
.btn-eval-peer:hover{background:var(--teal-hover);}
.btn-eval-peer:disabled{opacity:.45;cursor:not-allowed;}
.back-link{display:inline-flex;align-items:center;gap:7px;background:var(--inner);border:1px solid var(--border);color:var(--light);padding:8px 16px;border-radius:8px;font-size:13px;font-weight:600;text-decoration:none;margin-bottom:18px;transition:background .2s;}
.back-link:hover{background:var(--accent);}
.eval-header-bar{background:var(--mid);border:1px solid var(--border);border-radius:var(--radius);padding:20px 24px;margin-bottom:20px;display:flex;align-items:center;gap:16px;}
.eval-photo-lg{width:58px;height:58px;border-radius:50%;object-fit:cover;border:2px solid var(--teal);}
.eval-photo-ph{width:58px;height:58px;border-radius:50%;background:var(--inner);border:2px solid var(--teal);display:flex;align-items:center;justify-content:center;color:var(--muted);font-size:20px;}
.eval-name{font-family:'Rajdhani',sans-serif;font-size:18px;font-weight:700;color:#fff;}
.eval-desig{font-size:12px;color:var(--muted);margin-top:2px;}
.scale-legend-bar{display:flex;gap:8px;flex-wrap:wrap;margin-bottom:22px;background:rgba(255,255,255,.03);border:1px solid var(--border);border-radius:10px;padding:12px 16px;}
.legend-pill{display:flex;align-items:center;gap:7px;padding:5px 13px;border-radius:8px;background:rgba(13,148,136,.12);border:1px solid rgba(13,148,136,.22);font-size:13px;font-weight:700;color:var(--teal-hover);}
.legend-pill .l-num{font-size:15px;font-weight:800;}
.legend-pill .l-lbl{font-size:11px;font-weight:600;color:var(--muted);}
.q-category-header{display:flex;align-items:center;gap:8px;font-size:11px;font-weight:800;text-transform:uppercase;letter-spacing:1.5px;color:var(--teal-hover);margin:22px 0 10px;padding-bottom:8px;border-bottom:1px solid rgba(13,148,136,.18);}
.q-card-new{background:var(--mid);border:1px solid var(--border);border-radius:12px;padding:18px 20px;margin-bottom:10px;}
.q-no-new{font-size:11px;color:var(--muted);text-transform:uppercase;letter-spacing:.8px;margin-bottom:5px;font-weight:700;}
.q-text-new{font-size:14px;font-weight:600;color:#fff;margin-bottom:14px;line-height:1.5;}
.eval-form-wrap{background:var(--mid);border:1px solid var(--border);border-radius:12px;overflow:hidden;margin:0 0 10px}
.eval-form-table{width:100%;border-collapse:collapse;table-layout:fixed}
.eval-form-table th{background:rgba(255,255,255,.04);color:var(--muted);font-size:10px;font-weight:800;letter-spacing:.7px;text-transform:uppercase;text-align:center;padding:10px 6px;border-bottom:1px solid var(--border)}
.eval-form-table th:first-child{text-align:left;width:auto;padding-left:16px}
.eval-form-table th:not(:first-child){width:56px}
.eval-form-table td{padding:12px 6px;border-bottom:1px solid var(--border);vertical-align:middle;text-align:center}
.eval-form-table tr:last-child td{border-bottom:none}
.eval-form-table td:first-child{text-align:left;padding-left:16px;padding-right:12px}
.eval-form-qtext{font-size:13px;font-weight:600;line-height:1.5;color:var(--light)}
.eval-form-qno{color:var(--teal-hover);font-weight:800;margin-right:6px}
.eval-form-rating{display:flex;justify-content:center}
.eval-form-rating input{position:absolute;opacity:0;pointer-events:none}
.eval-form-rating label{width:38px;height:34px;display:flex;align-items:center;justify-content:center;border-radius:8px;border:1px solid var(--border);background:var(--inner);color:var(--muted);font-size:14px;font-weight:800;cursor:pointer;transition:all .18s ease;font-family:'DM Sans',sans-serif}
.eval-form-rating label:hover{border-color:var(--teal);color:var(--teal-hover);background:rgba(13,148,136,.1)}
.eval-form-rating input:checked + label{background:var(--teal);border-color:var(--teal);color:#fff}
@media(max-width:700px){.eval-form-table th:not(:first-child){width:42px}.eval-form-rating label{width:30px;height:30px;font-size:12px}.eval-form-qtext{font-size:12px}}
.comment-box-new{background:var(--mid);border:1px solid var(--border);border-radius:12px;padding:18px 20px;margin-bottom:10px;display:flex;gap:14px;align-items:flex-start;}
.comment-box-icon{color:var(--teal-hover);font-size:17px;margin-top:2px;flex-shrink:0;}
.comment-box-inner{flex:1;}
.comment-box-label{font-size:13px;font-weight:700;color:var(--teal-hover);margin-bottom:9px;}
.comment-textarea-new{width:100%;background:var(--inner);border:1px solid var(--border);border-radius:8px;color:var(--light);padding:11px 13px;font-size:13px;font-family:'DM Sans',sans-serif;resize:vertical;outline:none;transition:border-color .2s;line-height:1.5;min-height:88px;}
.comment-textarea-new:focus{border-color:var(--teal);}
.comment-textarea-new::placeholder{color:rgba(160,179,198,.38);}
.submit-row-new{background:var(--mid);border:1px solid var(--border);border-radius:12px;padding:15px 20px;display:flex;justify-content:space-between;align-items:center;gap:14px;flex-wrap:wrap;}
.btn-cancel-new{padding:10px 24px;background:var(--inner);border:1px solid var(--border);border-radius:var(--radius);color:var(--light);font-size:14px;font-weight:600;cursor:pointer;transition:background .2s;font-family:'DM Sans',sans-serif;}
.btn-cancel-new:hover{background:rgba(255,255,255,.06);}
.btn-submit-new{background:var(--teal);color:#fff;border:none;padding:11px 28px;border-radius:var(--radius);font-size:14px;font-weight:700;cursor:pointer;transition:all .2s;display:flex;align-items:center;gap:8px;font-family:'DM Sans',sans-serif;}
.btn-submit-new:hover{background:var(--teal-hover);transform:translateY(-1px);}
.profile-card{background:var(--mid);border:1px solid var(--border);border-radius:var(--radius);padding:26px;margin-bottom:20px;}
.profile-header{display:flex;align-items:center;gap:18px;margin-bottom:22px;padding-bottom:20px;border-bottom:1px solid var(--border);}
.profile-avatar-wrap{width:62px;height:62px;border-radius:50%;overflow:hidden;border:2.5px solid var(--teal);flex-shrink:0;background:var(--inner);display:flex;align-items:center;justify-content:center;color:var(--teal-hover);font-size:24px;}
.profile-avatar-wrap img{width:100%;height:100%;object-fit:cover;}
.profile-name{font-family:'Rajdhani',sans-serif;font-size:21px;font-weight:700;color:#fff;}
.profile-desig-badge{display:inline-flex;align-items:center;gap:5px;background:rgba(13,148,136,.14);border:1px solid rgba(13,148,136,.28);color:var(--teal-hover);font-size:12px;font-weight:700;padding:3px 12px;border-radius:20px;margin-top:6px;}
.fg-label{font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:1px;color:var(--muted);margin-bottom:7px;display:block;}
.suggestion-chips{display:flex;flex-wrap:wrap;gap:7px;margin-bottom:16px;}
.suggestion-chip{background:rgba(255,255,255,.04);border:1px solid var(--border);color:var(--muted);font-size:12px;font-weight:600;padding:5px 13px;border-radius:20px;cursor:pointer;transition:all .2s;}
.suggestion-chip:hover{background:rgba(13,148,136,.13);border-color:rgba(13,148,136,.35);color:var(--teal-hover);}
.suggestion-chip.is-current{background:rgba(13,148,136,.18);border-color:var(--teal);color:var(--teal-hover);cursor:default;}
.desig-input-row{display:flex;gap:10px;flex-wrap:wrap;margin-bottom:14px;}
.desig-input{flex:1;min-width:200px;background:var(--inner);border:1px solid var(--border);color:var(--light);padding:11px 15px;border-radius:10px;font-size:14px;font-family:'DM Sans',sans-serif;outline:none;transition:border-color .2s;}
.desig-input:focus{border-color:var(--teal);}
.btn-update-desig{background:var(--teal);color:#fff;border:none;padding:11px 22px;border-radius:10px;font-size:14px;font-weight:700;cursor:pointer;display:flex;align-items:center;gap:8px;transition:background .2s;font-family:'DM Sans',sans-serif;white-space:nowrap;}
.btn-update-desig:hover{background:var(--teal-hover);}
.info-note{background:rgba(43,108,176,.07);border:1px solid rgba(43,108,176,.18);border-radius:10px;padding:13px 17px;font-size:13px;color:var(--muted);display:flex;gap:10px;align-items:flex-start;margin-top:16px;}
.info-note i{color:#60a5fa;flex-shrink:0;margin-top:1px;}
.level-view-wrap{flex-shrink:0;background:var(--inner);border:1px solid var(--border);border-radius:12px;padding:14px 18px;min-width:220px;max-width:320px;transition:box-shadow .3s ease,border-color .3s ease;}
.level-view-wrap.flash{border-color:var(--teal);box-shadow:0 0 0 3px rgba(13,148,136,.25);}
.level-view-label{font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.8px;color:var(--muted);display:flex;align-items:center;gap:7px;margin-bottom:9px;}
.level-view-pills{display:flex;flex-wrap:wrap;gap:6px;}
.level-view-pill{background:rgba(13,148,136,.15);color:#5eead4;border:1px solid rgba(13,148,136,.35);font-size:12px;font-weight:700;padding:4px 12px;border-radius:20px;}
.level-view-empty{font-size:11.5px;color:#fcd34d;display:flex;align-items:center;gap:6px;line-height:1.5;}
.level-view-hint{font-size:11px;color:var(--muted);margin-top:9px;}
.toggle-switch{position:relative;display:inline-block;width:42px;height:24px;flex-shrink:0;cursor:pointer;}
.toggle-switch input{display:none;}
.toggle-slider{position:absolute;inset:0;background:var(--border);border-radius:20px;transition:background .2s;}
.toggle-slider::before{content:"";position:absolute;width:18px;height:18px;left:3px;top:3px;background:#fff;border-radius:50%;transition:transform .2s;}
.toggle-switch input:checked + .toggle-slider{background:var(--teal);}
.toggle-switch input:checked + .toggle-slider::before{transform:translateX(18px);}
.photo-upload-area{display:flex;align-items:center;gap:16px;flex-wrap:wrap;margin-top:20px;padding-top:18px;border-top:1px solid var(--border);}
.photo-preview-circle{width:62px;height:62px;border-radius:50%;overflow:hidden;border:2px solid var(--teal);flex-shrink:0;background:var(--inner);display:flex;align-items:center;justify-content:center;color:var(--muted);font-size:22px;}
.photo-preview-circle img{width:100%;height:100%;object-fit:cover;}
.photo-upload-btn{display:inline-flex;align-items:center;gap:7px;background:rgba(13,148,136,.13);border:1px solid rgba(13,148,136,.35);color:var(--teal-hover);padding:8px 16px;border-radius:8px;font-size:13px;font-weight:600;cursor:pointer;}
.photo-upload-hint{font-size:11px;color:var(--muted);margin-top:5px;}
.no-period-warn{background:rgba(251,191,36,.07);border:1px solid rgba(251,191,36,.18);border-radius:10px;padding:16px 20px;margin-bottom:18px;display:flex;gap:10px;align-items:center;font-size:13px;color:#fcd34d;}

/* ── EVALUATION — STEP 1: DESIGNATION SELECT ── */
.eval-group-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:18px;margin-bottom:4px;}
.eval-group-card{background:var(--inner);border:1px solid var(--border);border-radius:14px;padding:30px 20px;display:flex;flex-direction:column;align-items:center;text-align:center;text-decoration:none;transition:all .2s;}
.eval-group-card:hover{border-color:var(--teal);transform:translateY(-3px);box-shadow:var(--shadow);}
.eval-group-icon{width:58px;height:58px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:22px;margin-bottom:16px;}
.eval-group-icon.blue{background:rgba(59,130,246,.14);color:#3B82F6;}
.eval-group-icon.green{background:rgba(34,197,94,.14);color:#22C55E;}
.eval-group-icon.purple{background:rgba(139,92,246,.14);color:#8B5CF6;}
.eval-group-icon.gold{background:rgba(245,158,11,.14);color:#F59E0B;}
.eval-group-title{font-family:'Rajdhani',sans-serif;font-size:17px;font-weight:700;color:#fff;margin-bottom:4px;}
.eval-group-count{font-size:12.5px;color:var(--muted);}
.peer-select-hint{font-size:12px;color:var(--muted);display:flex;align-items:center;gap:6px;margin-top:14px;}
.peer-select-hint.warn{color:#fcd34d;}

/* ── NOTIFICATION BELL ── */
.notif-wrap{position:relative;display:flex;align-items:center;}
.notif-btn{width:36px;height:36px;border-radius:50%;background:rgba(255,255,255,.06);border:1px solid var(--border);display:flex;align-items:center;justify-content:center;color:var(--muted);font-size:15px;cursor:pointer;transition:all .2s;position:relative;}
.notif-btn:hover,.notif-btn.has-unread{color:#facc15;border-color:rgba(250,204,21,.4);background:rgba(250,204,21,.08);}
.notif-badge{position:absolute;top:-4px;right:-4px;min-width:18px;height:18px;border-radius:9px;background:#ef4444;color:#fff;font-size:10px;font-weight:700;display:flex;align-items:center;justify-content:center;padding:0 4px;border:2px solid var(--mid);}
.notif-dropdown{position:absolute;top:calc(100% + 10px);right:0;width:min(320px,calc(100vw - 24px));max-height:min(70vh,560px);display:flex;flex-direction:column;background:var(--mid);border:1px solid var(--border);border-radius:14px;box-shadow:0 16px 48px rgba(0,0,0,.55);opacity:0;visibility:hidden;transform:translateY(-8px);transition:all .2s ease;z-index:120;overflow:hidden;}
.notif-dropdown.show{opacity:1;visibility:visible;transform:translateY(0);}
.notif-header{display:flex;align-items:center;justify-content:space-between;padding:14px 16px;border-bottom:1px solid var(--border);}
.notif-header-title{font-size:13px;font-weight:700;color:#fff;display:flex;align-items:center;gap:7px;}
.notif-mark-read{font-size:11px;color:var(--teal-hover);cursor:pointer;font-weight:600;background:none;border:none;font-family:'DM Sans',sans-serif;padding:0;}
.notif-list{flex:1 1 auto;min-height:0;max-height:100%;overflow-y:auto;overflow-x:hidden;overscroll-behavior:contain;scrollbar-width:thin;scrollbar-color:rgba(148,163,184,.55) transparent;}
.notif-list::-webkit-scrollbar{width:7px;}
.notif-list::-webkit-scrollbar-track{background:transparent;}
.notif-list::-webkit-scrollbar-thumb{background:rgba(148,163,184,.55);border-radius:8px;}
.notif-item{display:flex;align-items:flex-start;gap:10px;padding:11px 14px;border-bottom:1px solid rgba(255,255,255,.05);position:relative;}
.notif-item:last-child{border-bottom:none;}
.notif-item.unread{background:rgba(13,148,136,.07);}
.notif-item.unread::before{content:'';position:absolute;left:0;top:0;bottom:0;width:3px;background:var(--teal);border-radius:0 2px 2px 0;}
.notif-icon{width:30px;height:30px;border-radius:8px;flex-shrink:0;display:flex;align-items:center;justify-content:center;font-size:12px;margin-top:1px;}
.notif-text{font-size:12px;color:var(--light);line-height:1.45;margin-bottom:3px;word-break:break-word;}
.notif-meta{font-size:11px;color:var(--muted);}
.notif-empty{text-align:center;padding:32px 16px;color:var(--muted);font-size:13px;}
.notif-empty i{font-size:28px;display:block;margin-bottom:8px;opacity:.2;}
.live-notif-toast{position:fixed;top:74px;right:28px;z-index:250;max-width:380px;display:flex;align-items:flex-start;gap:10px;padding:12px 14px;border:1px solid rgba(16,185,129,.35);border-radius:12px;background:var(--mid);box-shadow:0 16px 40px rgba(0,0,0,.25);color:var(--light);opacity:0;transform:translateY(-10px);pointer-events:none;transition:opacity .22s ease,transform .22s ease;}
.live-notif-toast.show{opacity:1;transform:translateY(0);}
.live-notif-toast-icon{width:30px;height:30px;flex:0 0 30px;border-radius:8px;display:flex;align-items:center;justify-content:center;background:rgba(16,185,129,.12);color:#10b981;}
.live-notif-toast strong{display:block;font-size:12px;color:#fff;margin-bottom:2px;}
.live-notif-toast span{display:block;font-size:12px;line-height:1.4;color:var(--light);}
@media (max-width:700px){.live-notif-toast{left:16px;right:16px;top:70px;max-width:none;}}


/* ── SIDEBAR PROFILE DROPDOWN ── */
.profile-dd-btn{width:100%;padding:9px 10px;border-radius:10px;border:none;background:none;color:var(--light);font-size:13.5px;font-weight:600;font-family:'DM Sans',sans-serif;cursor:pointer;display:flex;align-items:center;gap:12px;transition:background .18s;text-align:left;text-decoration:none;}
.profile-dd-btn:hover{background:rgba(255,255,255,.06);}
.profile-dd-icon{width:34px;height:34px;border-radius:9px;background:rgba(255,255,255,.06);display:flex;align-items:center;justify-content:center;color:var(--muted);font-size:14px;flex-shrink:0;transition:all .18s;}
.profile-dd-btn:hover .profile-dd-icon{background:rgba(13,148,136,.16);color:var(--teal-hover);}
.profile-dd-divider{height:1px;background:var(--border);margin:6px 4px;}
.profile-dd-btn.logout{color:#f87171;}
.profile-dd-btn.logout .profile-dd-icon{background:rgba(240,84,84,.1);color:#f87171;}
.profile-dd-btn.logout:hover{background:rgba(240,84,84,.08);}
.profile-dd-btn.logout:hover .profile-dd-icon{background:rgba(240,84,84,.18);color:#f87171;}
.dd-appearance-val{margin-left:auto;font-size:11px;color:var(--muted);font-weight:700;background:rgba(255,255,255,.06);padding:3px 9px;border-radius:20px;flex-shrink:0;}
.profile-dd-btn:hover .dd-appearance-val{color:var(--teal-hover);}

/* ── PROFILE PHOTO MODAL ── */
.photo-modal-overlay{position:fixed;inset:0;background:rgba(0,0,0,.75);z-index:300;display:none;align-items:center;justify-content:center;padding:20px;}
.photo-modal-overlay.open{display:flex;}
.photo-modal{background:var(--mid);border:1px solid var(--border);border-radius:18px;width:100%;max-width:400px;box-shadow:0 20px 60px rgba(0,0,0,.6);overflow:hidden;}
.photo-modal-header{padding:20px 24px 16px;border-bottom:1px solid var(--border);display:flex;align-items:center;justify-content:space-between;}
.photo-modal-title{font-family:'Rajdhani',sans-serif;font-size:20px;font-weight:700;color:#fff;}
.photo-modal-body{padding:24px;}
.photo-upload-circle{width:110px;height:110px;border-radius:50%;border:3px dashed rgba(13,148,136,.5);display:flex;align-items:center;justify-content:center;margin:0 auto 20px;cursor:pointer;transition:border-color .2s;overflow:hidden;position:relative;background:var(--inner);}
.photo-upload-circle:hover{border-color:var(--teal);}
.photo-upload-circle img{width:100%;height:100%;object-fit:cover;display:none;border-radius:50%;}
.photo-upload-circle .upload-icon-modal{color:var(--muted);font-size:32px;transition:color .2s;}
.photo-upload-circle:hover .upload-icon-modal{color:var(--teal);}
.photo-upload-hint-modal{text-align:center;font-size:12px;color:var(--muted);margin-bottom:20px;}
.photo-upload-hint-modal span{color:var(--teal-hover);font-weight:600;cursor:pointer;}
.photo-modal-footer{padding:0 24px 24px;display:flex;gap:10px;}
.btn-save-photo{flex:1;padding:11px;background:var(--teal);border:none;border-radius:var(--radius);color:#fff;font-size:14px;font-weight:700;cursor:pointer;font-family:'DM Sans',sans-serif;}
.btn-save-photo:hover{background:var(--teal-hover);}
.btn-skip-photo{flex:1;padding:11px;background:var(--inner);border:1px solid var(--border);border-radius:var(--radius);color:var(--muted);font-size:14px;font-weight:600;cursor:pointer;font-family:'DM Sans',sans-serif;}

@media(max-width:1024px){.stats-grid{grid-template-columns:repeat(2,1fr);}}
@media(max-width:900px){.sidebar{transform:translateX(-100%);}.sidebar.open{transform:translateX(0);}.top-nav{left:0;}.main{margin-left:0;}.hamburger{display:block;}}
@media(max-width:600px){.main{padding:16px;}.stats-grid{grid-template-columns:1fr 1fr;}.peer-grid{grid-template-columns:repeat(auto-fill,minmax(130px,1fr));}.desig-input-row{flex-direction:column;}.welcome-bar{padding:18px 20px;}.welcome-text h2{font-size:19px;}.eval-group-grid{grid-template-columns:1fr;}}

/* STAFF-STYLE PROFILE / ROLE LAYOUT — Faculty theme */
.profile-hero{background:linear-gradient(135deg,var(--mid) 0%,rgba(13,148,136,.18) 100%);border:1px solid rgba(13,148,136,.25);border-radius:16px;padding:28px;margin-bottom:22px;display:flex;align-items:center;justify-content:space-between;gap:22px;flex-wrap:wrap;}
.profile-hero-left{display:flex;align-items:center;gap:22px;flex-wrap:wrap;}
.profile-hero-avatar{width:80px;height:80px;border-radius:50%;object-fit:cover;border:3px solid var(--teal);box-shadow:0 0 18px rgba(13,148,136,.4);flex-shrink:0;display:flex;align-items:center;justify-content:center;color:var(--teal-hover);font-size:30px;background:var(--inner);}
.profile-hero-name{font-family:'Rajdhani',sans-serif;font-size:26px;font-weight:700;color:#fff;margin-bottom:6px;}
.profile-hero-desig{display:inline-flex;align-items:center;gap:7px;background:rgba(13,148,136,.2);border:1px solid rgba(13,148,136,.35);color:var(--teal-hover);font-size:13px;font-weight:700;padding:5px 14px;border-radius:20px;}
.role-card{background:var(--mid);border:2px solid rgba(13,148,136,.3);border-radius:16px;padding:26px;margin-bottom:22px;position:relative;overflow:hidden;}
.role-card::before{content:'';position:absolute;top:0;left:0;right:0;height:3px;background:linear-gradient(90deg,var(--teal),var(--teal-hover));}
.role-card-title{font-family:'Rajdhani',sans-serif;font-size:20px;font-weight:700;color:#fff;margin-bottom:4px;display:flex;align-items:center;gap:9px;}
.role-card-sub{font-size:13px;color:var(--muted);margin-bottom:22px;}
.role-chips-label{font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:1px;color:var(--muted);margin-bottom:10px;display:flex;align-items:center;gap:6px;}
.role-chips{display:flex;flex-wrap:wrap;gap:8px;margin-bottom:20px;}
.role-chip{padding:8px 16px;border-radius:8px;font-size:13px;font-weight:600;cursor:pointer;border:2px solid var(--border);background:var(--inner);color:var(--muted);transition:all .22s;display:flex;align-items:center;gap:6px;}
.role-chip:hover{border-color:var(--teal);color:var(--teal-hover);background:rgba(13,148,136,.1);}
.role-chip.is-current{border-color:var(--teal);background:rgba(13,148,136,.18);color:var(--teal-hover);pointer-events:none;}
.role-chip.is-current::after{content:'✓';margin-left:2px;font-size:12px;}
.custom-role-label{font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:1px;color:var(--muted);margin-bottom:8px;display:flex;align-items:center;gap:6px;}
.custom-role-row{display:flex;gap:10px;flex-wrap:wrap;}
.custom-role-input{flex:1;min-width:220px;background:var(--inner);border:2px solid var(--border);color:var(--light);padding:13px 16px;border-radius:10px;font-size:14px;font-family:'DM Sans',sans-serif;outline:none;transition:border-color .2s,box-shadow .2s;}
.custom-role-input:focus{border-color:var(--teal);box-shadow:0 0 0 3px rgba(13,148,136,.2);}
.custom-role-input::placeholder{color:var(--muted);}
.btn-save-role{background:var(--teal);color:#fff;border:none;padding:13px 28px;border-radius:10px;font-size:14px;font-weight:700;cursor:pointer;display:flex;align-items:center;gap:8px;transition:all .2s;font-family:'DM Sans',sans-serif;white-space:nowrap;}
.btn-save-role:hover{background:var(--teal-hover);transform:translateY(-1px);}

.settings-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:16px;margin-top:18px;}
.settings-card{background:var(--inner);border:1px solid var(--border);border-radius:14px;padding:18px;}
.settings-card.full{grid-column:1/-1;}
.settings-card-head{display:flex;align-items:center;gap:11px;margin-bottom:14px;}
.settings-card-head .sicon{width:38px;height:38px;border-radius:10px;display:flex;align-items:center;justify-content:center;background:rgba(13,148,136,.12);color:var(--teal-hover);}
.settings-card-head h3{margin:0;color:var(--light);font-size:15px;}
.settings-card-head p{margin:3px 0 0;color:var(--muted);font-size:11.5px;}
.setting-row{display:flex;align-items:center;justify-content:space-between;gap:18px;padding:12px 0;border-top:1px solid var(--border);}
.setting-row:first-of-type{border-top:0;}
.setting-row strong{display:block;color:var(--light);font-size:13px;}
.setting-row span{display:block;color:var(--muted);font-size:11px;margin-top:3px;line-height:1.45;}
.setting-toggle{position:relative;width:44px;height:24px;flex:0 0 44px;}
.setting-toggle input{display:none;}
.setting-toggle .slider{position:absolute;inset:0;border-radius:20px;background:var(--border);cursor:pointer;transition:.2s;}
.setting-toggle .slider:before{content:'';position:absolute;width:18px;height:18px;left:3px;top:3px;background:#fff;border-radius:50%;transition:.2s;}
.setting-toggle input:checked+.slider{background:var(--teal);}
.setting-toggle input:checked+.slider:before{transform:translateX(20px);}
.settings-actions{display:flex;justify-content:flex-end;gap:10px;margin-top:16px;}
.settings-save{border:0;border-radius:9px;background:var(--teal);color:#fff;font-weight:700;padding:10px 16px;cursor:pointer;}
.settings-save:hover{background:var(--teal-hover);}
.account-facts{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:10px;}
.account-fact{background:var(--mid);border:1px solid var(--border);border-radius:10px;padding:12px;}
.account-fact label{display:block;font-size:10px;text-transform:uppercase;letter-spacing:.7px;color:var(--muted);margin-bottom:4px;}
.account-fact b{color:var(--light);font-size:12.5px;}
@media(max-width:760px){.settings-grid{grid-template-columns:1fr}.settings-card.full{grid-column:auto}.account-facts{grid-template-columns:1fr}}


/* ── Light theme refinement: white surface + coordinated text/tab/icon states ── */
body.light-theme .nav-section-label{
    color:#0F766E;
    text-shadow:none;
}
body.light-theme .nav-link{
    color:#52677A;
}
body.light-theme .nav-link:hover{
    background:rgba(15,31,61,.055);
    color:#16263B;
}
body.light-theme .nav-link.active{
    background:rgba(13,148,136,.11);
    color:#0F766E;
    font-weight:700;
}
body.light-theme .nav-link.active i{
    color:#0F766E;
}
body.light-theme .nav-badge{
    background:#0D9488;
    color:#FFFFFF;
}
body.light-theme .period-badge{
    background:rgba(13,148,136,.09);
    border-color:rgba(13,148,136,.22);
    color:#0F766E;
}
body.light-theme .sidebar-sub{
    color:#0F766E;
}
body.light-theme .brand-avatar{
    border-color:#0D9488;
    box-shadow:0 0 10px rgba(13,148,136,.16);
    background:#EEF2F8;
}
body.light-theme .brand-avatar .brand-initials{
    color:#0F766E;
}
body.light-theme .profile-dd-btn{
    color:#16263B;
}
body.light-theme .profile-dd-btn:hover{
    background:rgba(15,31,61,.055);
}
body.light-theme .profile-dd-icon{
    box-shadow:none;
}
body.light-theme .dd-appearance-val{
    color:#52677A;
    background:#EEF2F8;
}
/* Required-rating feedback for peer evaluations */
.eval-validation-alert{display:none;margin:0 0 12px;padding:11px 14px;border-radius:9px;border:1px solid rgba(240,84,84,.34);background:rgba(240,84,84,.10);color:#fca5a5;font-size:13px;line-height:1.5;}
.eval-validation-alert.show{display:block;}
.eval-validation-alert strong{color:#fff;}
.eval-question-row.eval-unanswered{background:rgba(240,84,84,.06);}
.eval-question-row.eval-unanswered td:first-child{border-left:3px solid #f87171;}
.eval-progress{display:flex;align-items:center;justify-content:center;width:max-content;padding:6px 10px;margin:0 0 12px;border-radius:7px;background:rgba(255,255,255,.05);color:var(--muted);font-size:12px;font-weight:600;}
.eval-progress.is-complete{color:#86efac;background:rgba(34,197,94,.10);}

body.light-theme .eval-modal,
body.light-theme .eval-item{
    box-shadow:0 6px 22px rgba(15,31,61,.055);
}
body.light-theme .peer-card:hover{
    border-color:rgba(13,148,136,.24);
}
body.light-theme .peer-card.done{
    background:#F8FAFD;
}
body.light-theme .peer-select-input{
    background-color:#FFFFFF;
    color:#16263B;
}
body.light-theme .peer-select-input::placeholder{
    color:#7A8B9B;
}
body.light-theme .eval-form-table th,
body.light-theme .compact-eval-table th{
    background:#EEF2F8;
    color:#5B7186;
}
body.light-theme .btn-eval-peer,
body.light-theme .btn-proceed-peer,
body.light-theme .btn-save-photo{
    color:#FFFFFF;
}

    
/* Core light surfaces — Faculty */
body.light-theme,
body.light-theme .main{background:#F3F6FB;color:#16263B;}
body.light-theme .sidebar,
body.light-theme .top-nav{background:#FFFFFF;}
body.light-theme .nav-page-title,
body.light-theme .sidebar-title,
body.light-theme .welcome-text h2,
body.light-theme .section-title,
body.light-theme .stat-card-val,
body.light-theme .peer-name,
body.light-theme .eval-name{color:#16263B;}

/* ── Final Faculty white + blue role theme ───────────────────────────── */
body.light-theme{
    --accent:#2563EB;
    --hover:#1D4ED8;
    --teal:#2563EB;
    --teal-light:rgba(37,99,235,.10);
    --teal-hover:#1D4ED8;
    --dark:#F3F6FB;
    --mid:#FFFFFF;
    --inner:#F8FAFD;
    --light:#16263B;
    --muted:#52677A;
    --border:rgba(15,31,61,.10);
    --shadow:0 6px 22px rgba(15,31,61,.055);
}
body.light-theme .nav-section-label{color:#1D4ED8;text-shadow:none;}
body.light-theme .nav-link{color:#52677A;}
body.light-theme .nav-link:hover{background:rgba(37,99,235,.055);color:#16263B;}
body.light-theme .nav-link.active{background:rgba(37,99,235,.10);color:#1D4ED8;border-left:3px solid #2563EB;padding-left:9px;}
body.light-theme .nav-link.active i{color:#2563EB;}
body.light-theme .nav-badge{background:#2563EB;color:#fff;}
body.light-theme .period-badge{background:rgba(37,99,235,.08);border-color:rgba(37,99,235,.20);color:#1D4ED8;}
body.light-theme .sidebar-sub{color:#1D4ED8;}
body.light-theme .brand-avatar{border-color:#2563EB;box-shadow:0 0 10px rgba(37,99,235,.16);background:#EEF2F8;}
body.light-theme .brand-avatar .brand-initials{color:#1D4ED8;}
body.light-theme .welcome-bar{background:linear-gradient(135deg,#FFFFFF 0%,rgba(37,99,235,.055) 100%);border-color:rgba(37,99,235,.16);}
body.light-theme .welcome-text p strong{color:#2563EB;}
body.light-theme .score-chip{background:rgba(37,99,235,.07);border-color:rgba(37,99,235,.18);}
body.light-theme .score-chip .sc-val{color:#1D4ED8;}
body.light-theme .stat-card-val.teal{color:#1D4ED8;}
body.light-theme .section-title i{color:#2563EB !important;}
body.light-theme .btn-view-details{background:rgba(37,99,235,.07);border-color:rgba(37,99,235,.20);color:#1D4ED8;}
body.light-theme .btn-view-all-evals{background:rgba(37,99,235,.07);border-color:rgba(37,99,235,.18);color:#1D4ED8;}
body.light-theme .btn-view-all-evals:hover{background:rgba(37,99,235,.12);}
body.light-theme .profile-hero{background:linear-gradient(135deg,#FFFFFF 0%,rgba(37,99,235,.06) 100%);border-color:rgba(37,99,235,.18);}
body.light-theme .profile-hero-avatar{border-color:#2563EB;box-shadow:0 0 18px rgba(37,99,235,.20);color:#1D4ED8;}
body.light-theme .profile-hero-desig,
body.light-theme .profile-desig-badge{color:#1D4ED8;}
body.light-theme .profile-desig-badge{background:rgba(37,99,235,.08);border-color:rgba(37,99,235,.20);}
body.light-theme .suggestion-chip:hover{background:rgba(37,99,235,.08);border-color:rgba(37,99,235,.24);color:#1D4ED8;}
body.light-theme .suggestion-chip.is-current{background:rgba(37,99,235,.11);border-color:#2563EB;color:#1D4ED8;}
body.light-theme .peer-card:hover{border-color:rgba(37,99,235,.24);box-shadow:0 8px 24px rgba(37,99,235,.08);}
body.light-theme .eval-group-card:hover{border-color:#2563EB;box-shadow:0 8px 24px rgba(37,99,235,.08);}
body.light-theme .level-view-wrap.flash{border-color:#2563EB;box-shadow:0 0 0 3px rgba(37,99,235,.14);}
body.light-theme .level-view-pill{background:rgba(37,99,235,.08);color:#1D4ED8;border-color:rgba(37,99,235,.20);}
body.light-theme .photo-upload-circle{border-color:rgba(37,99,235,.35);}
body.light-theme .photo-upload-btn{background:rgba(37,99,235,.07);border-color:rgba(37,99,235,.22);color:#1D4ED8;}
body.light-theme .btn-save-photo,
body.light-theme .btn-submit-new,
body.light-theme .btn-update-desig,
body.light-theme .btn-eval-peer,
body.light-theme .btn-proceed-peer{background:#2563EB;}
body.light-theme .eval-form-rating label:hover{border-color:#2563EB;color:#1D4ED8;background:rgba(37,99,235,.07);}
body.light-theme .eval-form-rating input:checked + label{background:#2563EB;border-color:#2563EB;color:#fff;}
body.light-theme .q-category-header{color:#1D4ED8;border-bottom-color:rgba(37,99,235,.18);}
body.light-theme .legend-pill{background:rgba(37,99,235,.07);border-color:rgba(37,99,235,.18);color:#1D4ED8;}
body.light-theme .scale-legend-num{background:#2563EB;}
body.light-theme .notif-item.unread{background:rgba(37,99,235,.05);}
body.light-theme .profile-dd-btn:hover .profile-dd-icon{background:rgba(37,99,235,.12);color:#1D4ED8;}
body.light-theme .settings-card-head .sicon{background:rgba(37,99,235,.09);color:#1D4ED8;}
body.light-theme .compact-eval-qno{color:#1D4ED8;}
body.light-theme .compact-eval-rating button:hover{border-color:#2563EB;background:rgba(37,99,235,.07);}
body.light-theme .compact-eval-rating button.selected{background:#2563EB;border-color:#2563EB;}


/* LIGHT CONTENT + DARK ROLE SIDEBAR */
body.light-theme .sidebar{
    background:#172A45 !important;
    border-right-color:rgba(255,255,255,.08) !important;
}
body.light-theme .sidebar-brand{
    background:#172A45 !important;
    border-bottom-color:rgba(255,255,255,.08) !important;
}
body.light-theme .sidebar-brand:hover{
    background:#1B3455 !important;
}
body.light-theme .sidebar-title{
    color:#FFFFFF !important;
}
body.light-theme .sidebar-sub{
    color:#60A5FA !important;
}
body.light-theme .sidebar-caret{
    color:#A0B3C6 !important;
}
body.light-theme .brand-avatar{
    border-color:#2563EB !important;
    background:#0F1F3D !important;
    box-shadow:0 0 12px rgba(37,99,235,.28) !important;
}
body.light-theme .brand-avatar .brand-initials{
    color:#60A5FA !important;
}
body.light-theme .sidebar-profile-dropdown{
    background:#0F1F3D !important;
    border-color:rgba(255,255,255,.08) !important;
}
body.light-theme .profile-dd-btn{
    color:#E0E6F0 !important;
}
body.light-theme .profile-dd-btn:hover{
    background:rgba(255,255,255,.06) !important;
}
body.light-theme .profile-dd-btn i{
    color:#A0B3C6 !important;
}
body.light-theme .dd-appearance-val{
    background:rgba(255,255,255,.08) !important;
    color:#A0B3C6 !important;
}
body.light-theme .nav-section-label{
    color:#93C5FD !important;
    text-shadow:0 1px 8px rgba(37,99,235,.12) !important;
}
body.light-theme .nav-link{
    color:#A0B3C6 !important;
}
body.light-theme .nav-link:hover{
    background:rgba(255,255,255,.06) !important;
    color:#FFFFFF !important;
}
body.light-theme .nav-link.active{
    background:rgba(37,99,235,.18) !important;
    color:#FFFFFF !important;
    border-left:3px solid #2563EB !important;
}
body.light-theme .nav-link.active i{
    color:#3B82F6 !important;
}
body.light-theme .nav-link .badge{
    background:#2563EB !important;
    color:#FFFFFF !important;
}
body.light-theme .nav-icon-blue{color:#3B82F6 !important;}
body.light-theme .nav-icon-purple{color:#8B5CF6 !important;}
body.light-theme .nav-icon-green{color:#22C55E !important;}
body.light-theme .nav-icon-orange{color:#F97316 !important;}
body.light-theme .profile-dd-icon.dd-icon-blue{color:#60A5FA !important;}
body.light-theme .profile-dd-icon.dd-icon-amber{color:#FBBF24 !important;}
body.light-theme .profile-dd-icon.dd-icon-purple{color:#A78BFA !important;}
body.light-theme .sidebar-footer{
    border-top-color:rgba(255,255,255,.08) !important;
    background:#172A45 !important;
}
body.light-theme .btn-logout-side{
    background:rgba(240,84,84,.08) !important;
    border-color:rgba(240,84,84,.30) !important;
    color:#FCA5A5 !important;
}
body.light-theme .btn-logout-side:hover{
    background:rgba(240,84,84,.16) !important;
}

/* ================================================================
   REFERENCE-STYLE LIGHT DASHBOARD
   Inspired by the Student Portal visual language: dark sidebar,
   airy light workspace, crisp white cards, soft borders/shadows,
   role accent used consistently throughout.
   ================================================================ */
body.light-theme{
    --light:#13263F;
    --muted:#536A82;
    --dark:#F4F7FB;
    --mid:#FFFFFF;
    --inner:#F7F9FC;
    --border:#DFE6EE;
    --accent:#2563EB;
    --hover:#1D4ED8;
    --teal:#2563EB;
    --teal-hover:#1D4ED8;
    --teal-light:rgba(37,99,235,.10);
    --shadow:0 8px 26px rgba(18,43,73,.06);
}
body.light-theme .sidebar{
    background:#081B2E !important;
    border-right:1px solid rgba(255,255,255,.08) !important;
}
body.light-theme .sidebar-brand,
body.light-theme .sidebar-footer{background:#081B2E !important;}
body.light-theme .sidebar-brand{border-bottom-color:rgba(255,255,255,.08) !important;}
body.light-theme .sidebar-title{color:#fff !important;}
body.light-theme .sidebar-sub{color:#60A5FA !important;}
body.light-theme .nav-section-label{color:#93C5FD !important;}
body.light-theme .nav-link{color:#B6C5D6 !important;}
body.light-theme .nav-link:hover{background:rgba(255,255,255,.06) !important;color:#fff !important;}
body.light-theme .nav-link.active{background:rgba(37,99,235,.16) !important;color:#fff !important;border-left:3px solid #2563EB !important;}
body.light-theme .nav-link.active i{color:#60A5FA !important;}
body.light-theme .sidebar-caret{color:#AFC0D2 !important;}
body.light-theme .brand-avatar{border-color:#2563EB !important;box-shadow:0 0 14px rgba(37,99,235,.25) !important;}
body.light-theme .sidebar-profile-dropdown{background:#0E2944 !important;border-color:rgba(255,255,255,.08) !important;}
body.light-theme .profile-dd-btn{color:#E6EDF5 !important;}
body.light-theme .profile-dd-btn:hover{background:rgba(255,255,255,.06) !important;}
body.light-theme .dd-appearance-val{background:rgba(255,255,255,.08) !important;color:#B6C5D6 !important;}
body.light-theme .sidebar-footer{border-top-color:rgba(255,255,255,.08) !important;}
body.light-theme .btn-logout-side{background:rgba(248,113,113,.08) !important;color:#FCA5A5 !important;border-color:rgba(248,113,113,.28) !important;}
body.light-theme .btn-logout-side:hover{background:rgba(248,113,113,.15) !important;}
body.light-theme .top-nav{background:#FFFFFF !important;border-bottom-color:#DEE6EF !important;box-shadow:0 2px 14px rgba(15,34,55,.04) !important;}
body.light-theme .nav-page-title{color:#13263F !important;}
body.light-theme .period-badge{background:rgba(37,99,235,.07) !important;border-color:rgba(37,99,235,.20) !important;color:#1D4ED8 !important;}
body.light-theme .main{background:#F4F7FB !important;color:#13263F !important;padding:24px 28px 42px !important;}

/* Dashboard shell */
body.light-theme .dashboard-shell{
    background:linear-gradient(180deg,#FFFFFF 0%,#F8FAFD 100%);
    border:1px solid #E1E8F0;
    border-radius:22px;
    padding:22px 28px 180px;
    box-shadow:0 10px 28px rgba(19,38,63,.045);
    min-height:calc(100vh - 112px);
}
body.light-theme .evaluation-schedule-card{background:#FFFFFF;border-color:rgba(30,82,144,.13);box-shadow:0 5px 18px rgba(30,82,144,.04);}
body.light-theme .schedule-summary-item strong{color:#10243E;}
body.light-theme .dashboard-intro{margin:2px 2px 18px;}
body.light-theme .dashboard-kicker{
    color:#D97706;font-size:11px;font-weight:800;letter-spacing:1.2px;text-transform:uppercase;
    display:flex;align-items:center;gap:7px;margin-bottom:5px;
}
body.light-theme .dashboard-intro h1{
    margin:0;font-family:'Rajdhani',sans-serif;font-size:28px;line-height:1.05;font-weight:800;
    letter-spacing:.2px;color:#10243E;
}
body.light-theme .dashboard-intro p{margin-top:7px;font-size:13px;color:#5C7187;}
body.light-theme .dashboard-intro .status-pill{
    display:inline-flex;align-items:center;gap:7px;margin-top:14px;padding:6px 12px;border-radius:20px;
    background:#E8FFF1;border:1px solid #91E7B2;color:#168447;font-size:11.5px;font-weight:700;
}

body.light-theme .dashboard-intro .status-pill.closed{background:#FFF1F1;border-color:#F3B0B0;color:#B42318;}

/* Welcome card */
body.light-theme .welcome-bar{
    background:linear-gradient(135deg,#FFFFFF 0%,#F5F9FF 100%) !important;
    border:1px solid rgba(37,99,235,.20) !important;
    border-radius:17px !important;padding:24px 28px !important;margin-bottom:20px !important;
    box-shadow:0 5px 18px rgba(37,99,235,.05);
}
body.light-theme .welcome-text h2{color:#11263F !important;font-size:23px !important;}
body.light-theme .welcome-text p{color:#5E7388 !important;}
body.light-theme .welcome-text p strong{color:#2563EB !important;}
body.light-theme .score-chip{
    background:#F0F5FF !important;border:1px solid #CADBFF !important;border-radius:15px !important;
    max-width:340px;min-width:230px;padding:16px 26px !important;
}
body.light-theme .score-chip .sc-val{color:#2563EB !important;font-size:39px !important;}
body.light-theme .score-chip .sc-lbl{color:#66798D !important;}

/* Statistic cards */
body.light-theme .stats-grid{gap:14px !important;margin-bottom:20px !important;}
body.light-theme .stat-card{
    position:relative;background:#FFFFFF !important;border:1px solid #E0E7EF !important;
    border-radius:15px !important;padding:18px 20px 20px !important;box-shadow:0 6px 18px rgba(20,42,67,.045) !important;
    overflow:hidden;
}
body.light-theme .stat-card::before{content:'';position:absolute;left:0;right:0;top:0;height:3px;background:#2563EB;}
body.light-theme .stat-card:nth-child(2)::before{background:#10B981;}
body.light-theme .stat-card:nth-child(3)::before{background:#F59E0B;}
body.light-theme .stat-card:nth-child(4)::before{background:#3B82F6;}
body.light-theme .stat-card-icon{
    width:34px;height:34px;border-radius:10px;display:flex;align-items:center;justify-content:center;
    margin-bottom:12px;font-size:15px;
}
body.light-theme .stat-card-icon.blue{background:#E6EEFF;color:#2563EB;}
body.light-theme .stat-card-icon.green{background:#E5F9ED;color:#10B981;}
body.light-theme .stat-card-icon.gold{background:#FFF4D8;color:#D88900;}
body.light-theme .stat-card-icon.sky{background:#E5F0FF;color:#3478E5;}
body.light-theme .stat-card-lbl{color:#5E7388 !important;font-size:10.5px !important;letter-spacing:.9px !important;}
body.light-theme .stat-card-val{color:#12263F !important;font-size:28px !important;}
body.light-theme .stat-card-val.teal{color:#2563EB !important;}
body.light-theme .stat-card-val.gold{color:#D88900 !important;}

/* Section cards and expandable evaluation row */
body.light-theme .section-card{
    background:#FFFFFF !important;border:1px solid #E1E8F0 !important;border-radius:16px !important;
    padding:20px 22px !important;margin-bottom:18px !important;box-shadow:0 5px 18px rgba(20,42,67,.04) !important;
}
body.light-theme .section-title{color:#13263F !important;font-size:17px !important;}
body.light-theme .section-title i{color:#2563EB !important;}
body.light-theme .btn-view-all-evals{
    background:#F0F5FF !important;border-color:#CFDDFF !important;color:#2563EB !important;
    border-radius:10px !important;padding:12px 15px !important;
}
body.light-theme .btn-view-all-evals:hover{background:#E7EEFF !important;}
body.light-theme .eval-item{background:#F8FAFD !important;border:1px solid #E1E8F0 !important;}
body.light-theme .eval-item-left .anon{color:#20364F !important;}
body.light-theme .eval-item-left .meta{color:#6E8093 !important;}
body.light-theme .btn-view-details{background:#EFF4FF !important;border-color:#CEDBFF !important;color:#2563EB !important;}
body.light-theme .btn-view-details:hover{background:#E4ECFF !important;}
body.light-theme .empty-state{color:#66798D !important;}

/* Profile / role page visual language */
body.light-theme .profile-hero,
body.light-theme .role-card,
body.light-theme .students-grid > *,
body.light-theme .peer-card,
body.light-theme .ea-table-card,
body.light-theme .section-card{
    box-shadow:0 5px 18px rgba(20,42,67,.035);
}
body.light-theme .profile-hero{background:linear-gradient(135deg,#FFFFFF 0%,#F1F7FF 100%) !important;border-color:#CFE0FF !important;}
body.light-theme .profile-hero-name{color:#11263F !important;}
body.light-theme .profile-hero-desig{background:#EEF4FF !important;border-color:#CBDCFF !important;color:#2563EB !important;}
body.light-theme .role-card{background:#FFFFFF !important;border-color:#CFDAFF !important;}
body.light-theme .role-card-title{color:#13263F !important;}
body.light-theme .role-card::before{background:linear-gradient(90deg,#2563EB,#60A5FA) !important;}
body.light-theme .role-chip{background:#F7F9FC !important;border-color:#DDE5EE !important;color:#51677F !important;}
body.light-theme .role-chip:hover,.role-chip.is-current{background:#EEF4FF !important;border-color:#AFC8FF !important;color:#2563EB !important;}
body.light-theme .level-view-wrap{background:#F8FAFD !important;border-color:#DDE6EF !important;}
body.light-theme .level-view-pill{background:#E9F1FF !important;color:#2563EB !important;border-color:#C5D8FF !important;}

@media(max-width:900px){
    body.light-theme .dashboard-shell{padding:18px 16px 130px;border-radius:16px;}
    body.light-theme .main{padding:18px 14px 28px !important;}
}

/* Settings is now a first-class sidebar destination. */
.settings-page-head{
    display:flex;align-items:flex-start;justify-content:space-between;gap:20px;
    margin:0 0 18px;padding:0 2px;
}
.settings-page-kicker{
    display:flex;align-items:center;gap:7px;color:var(--teal);
    font-size:11px;font-weight:800;letter-spacing:1px;text-transform:uppercase;
    margin-bottom:5px;
}
.settings-page-title{
    margin:0;color:var(--light);font-family:'Rajdhani',sans-serif;
    font-size:28px;font-weight:700;
}
.settings-page-subtitle{margin:3px 0 0;color:var(--muted);font-size:12.5px;line-height:1.5;}
.settings-appearance-current{
    display:inline-flex;align-items:center;gap:7px;padding:8px 11px;border-radius:999px;
    background:rgba(37,99,235,.08);border:1px solid rgba(37,99,235,.18);
    color:#1D4ED8;font-size:11px;font-weight:700;white-space:nowrap;
}
.appearance-choice-row{display:flex;gap:10px;flex-wrap:wrap;margin-top:2px;}
.appearance-choice{
    border:1px solid var(--border);background:var(--mid);color:var(--light);
    border-radius:10px;padding:10px 14px;font:700 12px 'DM Sans',sans-serif;cursor:pointer;
    display:inline-flex;align-items:center;gap:8px;transition:.18s ease;
}
.appearance-choice:hover{border-color:var(--teal);color:var(--teal-hover);}
.appearance-choice.active{background:rgba(37,99,235,.10);border-color:rgba(37,99,235,.28);color:#1D4ED8;}
body.light-theme .settings-card{background:#FFFFFF;border-color:rgba(15,31,61,.10);box-shadow:0 5px 18px rgba(30,82,144,.06);}
body.light-theme .settings-card-head h3{color:#16263B;}
body.light-theme .settings-card-head p{color:#5B7186;}
body.light-theme .setting-row{border-top-color:rgba(15,31,61,.08);}
body.light-theme .setting-row strong{color:#16263B;}
body.light-theme .setting-row span{color:#5B7186;}
body.light-theme .account-fact{background:#F8FAFC;border-color:rgba(15,31,61,.08);}
body.light-theme .account-fact label{color:#5B7186;}
body.light-theme .account-fact b{color:#16263B;}
body.light-theme .settings-page-title{color:#16263B;}
body.light-theme .settings-page-subtitle{color:#5B7186;}
body.light-theme .appearance-choice{background:#FFFFFF;color:#294765;border-color:#D8E3EF;}

/* Unified Student-style sidebar structure for Faculty; existing role accent retained. */
:root { --sidebar-w:248px; --role-accent:#2563EB; --role-accent-light:#60A5FA; }
.sidebar { width:var(--sidebar-w) !important; background:#0A192F !important; border-right:1px solid #172A45 !important; box-shadow:6px 0 20px rgba(0,0,0,.16); }
.sidebar-brand.portal-brand { min-height:68px; padding:14px 17px !important; border-bottom:1px solid rgba(255,255,255,.08) !important; display:flex; align-items:center; gap:11px; cursor:default; }
.portal-brand-logo { width:38px; height:38px; flex:0 0 38px; border-radius:11px; display:flex; align-items:center; justify-content:center; overflow:hidden; background:#0F1F3D; border:1px solid #2563EB; box-shadow:0 0 14px rgba(37,99,235,.18); }
.portal-brand-logo img { width:100%; height:100%; object-fit:cover; display:block; }
.portal-brand-copy { min-width:0; display:flex; flex-direction:column; line-height:1.2; }
.portal-brand-copy strong { font-family:'Rajdhani',sans-serif; font-size:15px; font-weight:700; color:#F8FAFC; letter-spacing:.25px; }
.portal-brand-copy span { margin-top:3px; font-size:10px; color:#8FA6BF; font-weight:600; letter-spacing:.15px; white-space:nowrap; }
.portal-sidebar-profile { padding:18px 16px 17px; text-align:center; border-bottom:1px solid rgba(255,255,255,.08); }
.portal-profile-avatar-wrap { margin:0 auto 11px; display:flex; justify-content:center; }
.portal-profile-avatar { width:64px; height:64px; border-radius:50%; object-fit:cover; border:2px solid #2563EB; background:#0F1F3D; box-shadow:0 0 15px rgba(37,99,235,.18); display:flex; align-items:center; justify-content:center; }
.portal-profile-fallback { color:#60A5FA; font-family:'Rajdhani',sans-serif; font-size:17px; font-weight:700; }
.portal-profile-name { color:#F8FAFC; font-size:13px; line-height:1.35; font-weight:700; word-break:break-word; }
.portal-profile-role { margin-top:3px; color:#8FA6BF; font-size:10px; text-transform:uppercase; letter-spacing:.7px; font-weight:700; line-height:1.35; }
.portal-sidebar-nav { flex:1; padding:16px 10px !important; overflow-y:auto; }
.portal-sidebar-nav .nav-section-label { padding:0 8px !important; margin:0 0 7px !important; font-size:9.5px !important; font-weight:800 !important; letter-spacing:1.25px !important; text-align:left !important; color:#8FA6BF !important; text-shadow:none !important; }
.portal-sidebar-nav .nav-section-label.sidebar-section-secondary { margin-top:17px !important; }
.portal-sidebar-nav .nav-link { min-height:40px; margin:2px 2px !important; padding:9px 11px !important; border-left:0 !important; border-radius:8px !important; gap:10px; color:#CBD8E8 !important; font-size:13px !important; font-weight:500 !important; line-height:1.2; }
.portal-sidebar-nav .nav-link i { width:18px; font-size:14px; color:#8FA6BF !important; text-align:center; flex:0 0 18px; }
.portal-sidebar-nav .nav-link:hover { background:rgba(255,255,255,.07) !important; color:#FFFFFF !important; }
.portal-sidebar-nav .nav-link:hover i { color:#60A5FA !important; }
.portal-sidebar-nav .nav-link.active { background:linear-gradient(90deg,rgba(37,99,235,.18),rgba(255,255,255,.025)) !important; color:#FFFFFF !important; font-weight:700 !important; box-shadow:inset 3px 0 0 #60A5FA; }
.portal-sidebar-nav .nav-link.active i { color:#60A5FA !important; }
.portal-sidebar-nav .side-nav-badge, .portal-sidebar-nav .nav-badge { margin-left:auto; background:rgba(37,99,235,.22) !important; color:#BFDBFE !important; border-radius:20px; padding:2px 7px; font-size:9px; font-weight:800; }
.sidebar-footer { padding:13px 14px 15px !important; border-top:1px solid rgba(255,255,255,.08) !important; }
.btn-logout-side { padding:9px 11px !important; border-radius:8px !important; }
@media(max-width:900px) { .sidebar { width:248px !important; transform:translateX(-100%); transition:transform .22s ease; } .sidebar.open { transform:translateX(0); box-shadow:12px 0 34px rgba(0,0,0,.45); } .top-nav { left:0 !important; } .main { margin-left:0 !important; } }
@media(max-width:520px) { .portal-sidebar-nav { padding-left:9px !important; padding-right:9px !important; } }

</style>
</head>
<body class="light-theme">

<aside class="sidebar" id="sidebar">
    <div class="sidebar-brand portal-brand">
        <div class="portal-brand-logo"><img src="../image/pbi_logo" alt="PBI" onerror="this.style.display='none'"/></div>
        <div class="portal-brand-copy">
            <strong>Faculty Workspace</strong>
            <span>Evaluation Workspace</span>
        </div>
    </div>

    <div class="portal-sidebar-profile">
        <div class="portal-profile-avatar-wrap">
            <?php if ($faculty_photo): ?>
            <img class="portal-profile-avatar" src="<?= UPLOAD_URL . htmlspecialchars($faculty_photo) ?>" alt=""/>
            <?php else: ?>
            <span class="portal-profile-avatar portal-profile-fallback"><?= htmlspecialchars($initials) ?></span>
            <?php endif; ?>
        </div>
        <div class="portal-profile-name"><?= htmlspecialchars($full_name) ?></div>
        <div class="portal-profile-role">FACULTY<?= $designation ? ' · ' . htmlspecialchars($designation) : '' ?></div>
    </div>

    <nav class="sidebar-nav portal-sidebar-nav">
        <div class="nav-section-label">Main</div>
        <a href="faculty_dashboard.php?page=dashboard" class="nav-link <?= $page==='dashboard'?'active':'' ?>">
            <i class="fa-solid fa-house"></i><span>Dashboard</span>
        </a>
        <a href="faculty_dashboard.php?page=profile" class="nav-link <?= $page==='profile'?'active':'' ?>">
            <i class="fa-solid fa-id-badge"></i><span>Role &amp; Designation</span>
        </a>

        <div class="nav-section-label sidebar-section-secondary">Evaluation</div>
        <a href="faculty_dashboard.php?page=my_results" class="nav-link <?= $page==='my_results'?'active':'' ?>">
            <i class="fa-solid fa-chart-bar"></i><span>Feedback's Received</span>
        </a>
        <a href="faculty_dashboard.php?page=peer" class="nav-link <?= in_array($page,['peer','peer_eval'])?'active':'' ?>">
            <i class="fa-solid fa-users-viewfinder"></i><span>Evaluate Others</span>
            <?php if ($page==='peer' && (!empty($peers_all) || !empty($school_heads))): ?>
            <span class="side-nav-badge"><?= (count($peers_all) - count($done_peers)) + (count($school_heads) - count($done_school_heads)) ?></span>
            <?php endif; ?>
        </a>

        <div class="nav-section-label sidebar-section-secondary">Support</div>
        <a href="faculty_dashboard.php?page=settings" class="nav-link <?= $page==='settings'?'active':'' ?>">
            <i class="fa-solid fa-gear"></i><span>Settings</span>
        </a>
    </nav>

    <div class="sidebar-footer">
        <a href="../logout.php" class="btn-logout-side" onclick="return confirm('Log out of your faculty session?')">
            <i class="fa-solid fa-power-off"></i><span>Log Out</span>
        </a>
    </div>
</aside>
    
<!-- PROFILE PHOTO MODAL -->
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
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>"/>
                <div class="photo-upload-circle" id="photoCircle" onclick="document.getElementById('photoFileInput').click()">
                    <img id="photoPreviewImg" src="<?= $faculty_photo ? UPLOAD_URL.htmlspecialchars($faculty_photo) : '' ?>"
                         style="<?= $faculty_photo ? 'display:block' : '' ?>"/>
                    <i class="fa-solid fa-camera upload-icon-modal" id="uploadIconEl" style="<?= $faculty_photo ? 'display:none' : '' ?>"></i>
                </div>
                <div class="photo-upload-hint-modal">
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

<!-- EVALUATION DETAILS MODAL -->
<div class="eval-modal-overlay" id="evalDetailsModal">
    <div class="eval-modal">
        <div class="eval-modal-header">
            <div class="eval-modal-title"><i class="fa-solid fa-star" style="color:var(--teal-hover);margin-right:8px;"></i>Evaluation Details</div>
            <button class="eval-modal-close" onclick="closeEvalDetails()"><i class="fa-solid fa-xmark"></i></button>
        </div>
        <div class="eval-modal-body" id="evalDetailsBody">
            <div class="eval-modal-loading"><i class="fa-solid fa-spinner fa-spin"></i>Loading evaluation…</div>
        </div>
    </div>
</div>

<nav class="top-nav">
    <div style="display:flex;align-items:center;gap:13px;">
        <button class="hamburger" onclick="document.getElementById('sidebar').classList.toggle('open')">
            <i class="fa-solid fa-bars"></i>
        </button>
        <div class="nav-page-title">
            <?php $titles=['dashboard'=>'Dashboard','profile'=>'Role & Designation','my_results'=>"Feedback's Received",'peer'=>'Evaluate Others','peer_eval'=>'Evaluate','settings'=>'Settings'];
            echo $titles[$page] ?? 'Dashboard'; ?>
        </div>
    </div>
    <div style="display:flex;align-items:center;gap:12px;">
        <?php if ($period): ?>
        <div class="period-badge">
            <i class="fa-solid fa-calendar-check"></i>
            <?= htmlspecialchars($period['period_label'] ?? ($period['semester'] ?? '')) ?>
        </div>
        <?php endif; ?>

        <div class="notif-wrap" id="notifWrap">
            <button class="notif-btn <?= $unread_count>0?'has-unread':'' ?>" id="notifBtn" onclick="toggleNotifDropdown(event)" title="Notifications">
                <i class="fa-regular fa-bell"></i>
                <?php if ($unread_count > 0): ?>
                <span class="notif-badge show" id="notifBadge"><?= $unread_count > 99 ? '99+' : $unread_count ?></span>
                <?php endif; ?>
            </button>
            <div class="notif-dropdown" id="notifDropdown">
                <div class="notif-header">
                    <span class="notif-header-title"><i class="fa-solid fa-bell" style="color:var(--teal-hover);"></i> Notifications</span>
                    <form method="POST" style="margin:0;">
                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>"/>
                        <input type="hidden" name="mark_notifications_read" value="1"/>
                        <button type="submit" class="notif-mark-read">Mark all read</button>
                    </form>
                </div>
                <div class="notif-list">
                    <?php if (empty($my_notifications)): ?>
                    <div class="notif-empty"><i class="fa-regular fa-bell-slash"></i>No notifications yet.</div>
                    <?php else: foreach ($my_notifications as $n):
                        $is_eval = $n['type'] === 'evaluation_received';
                        $n_icon  = $is_eval ? 'fa-star' : 'fa-id-badge';
                        $n_color = $is_eval ? '#facc15' : '#2B6CB0';
                    ?>
                    <div class="notif-item <?= empty($n['is_read'])?'unread':'' ?>" data-notification-id="<?= (int)$n['id'] ?>">
                        <div class="notif-icon" style="color:<?= $n_color ?>;background:<?= $n_color ?>22;"><i class="fa-solid <?= $n_icon ?>"></i></div>
                        <div style="flex:1;min-width:0;">
                            <div class="notif-text"><?= htmlspecialchars($n['message']) ?></div>
                            <div class="notif-meta"><?= date('M d, Y g:i A', strtotime($n['created_at'])) ?></div>
                        </div>
                    </div>
                    <?php endforeach; endif; ?>
                </div>
            </div>
        </div>
    </div>
</nav>

<main class="main">

<?php if ($toast): ?>
<div class="toast toast-success"><i class="fa-solid fa-circle-check"></i> <?= htmlspecialchars($toast) ?></div>
<?php endif; ?>
<?php if ($toast_error): ?>
<div class="toast toast-error"><i class="fa-solid fa-circle-exclamation"></i> <?= htmlspecialchars($toast_error) ?></div>
<?php endif; ?>

<?php if ($page === 'dashboard'): ?>

<div class="dashboard-shell">
    <div class="dashboard-intro">
        <div class="dashboard-kicker"><i class="fa-solid fa-chart-line"></i> Dashboard Overview</div>
        <h1>Faculty Evaluation Workspace</h1>
        <p>Review your evaluation results and manage your professional evaluation activities in one place.</p>
        <?php if ($period): ?>
        <span class="status-pill <?= $evaluation_open ? '' : 'closed' ?>">
                <i class="fa-solid <?= $evaluation_open ? 'fa-lock-open' : 'fa-lock' ?>"></i>
                <?= $evaluation_open ? 'Evaluation period is currently open' : 'Evaluation is currently closed — waiting for the scheduled opening' ?>
            </span>
        <?php endif; ?>
    </div>

    <div class="evaluation-schedule-card">
        <div class="schedule-summary-grid">
            <div class="schedule-summary-item">
                <span class="schedule-label">Academic Year</span>
                <strong><?= htmlspecialchars($dashboard_acad_year ?: '—') ?></strong>
            </div>
            <div class="schedule-summary-item">
                <span class="schedule-label">Evaluation Opens</span>
                <strong><?= htmlspecialchars($dashboard_open_display) ?></strong>
            </div>
            <div class="schedule-summary-item">
                <span class="schedule-label">Evaluation Closes</span>
                <strong><?= htmlspecialchars($dashboard_close_display) ?></strong>
            </div>
            <div class="schedule-summary-item schedule-status-item">
                <span class="schedule-label">Status</span>
                <span class="schedule-status <?= htmlspecialchars($dashboard_status_class) ?>">
                    <?= htmlspecialchars($dashboard_status) ?>
                </span>
            </div>
        </div>
    </div>

<div class="welcome-bar">
    <div class="welcome-text">
        <h2>Welcome, <?= htmlspecialchars($first_name) ?>!</h2>
        <p>
            <strong><?= htmlspecialchars($designation) ?></strong>
            &nbsp;·&nbsp;
            <?= $period ? htmlspecialchars($period['period_label'] ?? $period['semester']) : 'No active evaluation period' ?>
        </p>
    </div>
    <div class="score-chip">
        <div class="sc-val"><?= $my_avg !== null ? number_format($my_avg, 2) : '—' ?></div>
        <div class="sc-lbl">Your Avg Score</div>
    </div>
</div>

<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-card-icon blue"><i class="fa-solid fa-users"></i></div>
        <div class="stat-card-lbl">Evaluations Received</div>
        <div class="stat-card-val teal"><?= $my_total ?></div>
    </div>
    <div class="stat-card">
        <div class="stat-card-icon green"><i class="fa-solid fa-circle-check"></i></div>
        <div class="stat-card-lbl">Overall Average</div>
        <div class="stat-card-val gold"><?= $my_avg !== null ? number_format($my_avg, 2) . ' / 5' : '—' ?></div>
    </div>
    <div class="stat-card">
        <div class="stat-card-icon gold"><i class="fa-solid fa-award"></i></div>
        <div class="stat-card-lbl">Performance Level</div>
        <div class="stat-card-val sm" style="color:<?= $perf_color ?>"><?= $perf_label ?></div>
    </div>
    <div class="stat-card">
        <div class="stat-card-icon sky"><i class="fa-solid fa-calendar"></i></div>
        <div class="stat-card-lbl">Current Period</div>
        <div class="stat-card-val sm" style="color:var(--teal-hover)">
            <?= $period ? htmlspecialchars($period['semester'] ?? '—') : 'None' ?>
        </div>
    </div>
</div>

<?php if (!empty($my_scores)): ?>
<div class="section-card">
    <div class="section-title"><i class="fa-solid fa-layer-group" style="color:var(--teal)"></i> Performance by Category</div>
    <?php foreach ($my_scores as $cs):
        $pct = round(($cs['avg_cat']/5)*100);
        $col = $cs['avg_cat']>=4?'#4ade80':($cs['avg_cat']>=3?'#facc15':'#f87171');
    ?>
    <div class="cat-row">
        <div class="cat-name"><?= htmlspecialchars($cs['category']) ?></div>
        <div class="cat-bar-bg"><div class="cat-bar-fill" style="width:<?= $pct ?>%;background:<?= $col ?>"></div></div>
        <div class="cat-score" style="color:<?= $col ?>"><?= number_format($cs['avg_cat'],2) ?></div>
    </div>
    <?php endforeach; ?>
</div>
<?php endif; ?>

<div class="section-card">
    <div class="section-title">
        <i class="fa-solid fa-clock-rotate-left" style="color:var(--accent)"></i> Evaluations Received
    </div>

    <button type="button" class="btn-view-all-evals" id="viewEvalsBtn" onclick="toggleRecentEvals()">
        <i class="fa-solid fa-eye"></i> View Evaluations Received
        <i class="fa-solid fa-chevron-down" id="recentEvalsCaret" style="transition:transform .2s;margin-left:auto;"></i>
    </button>

    <div id="recentEvalsList" style="display:none;margin-top:14px;">
    <?php if (empty($recent_subs)): ?>
        <div class="empty-state">
            <i class="fa-solid fa-inbox"></i>
            <p>No evaluations received yet this period.</p>
        </div>
    <?php else: foreach ($recent_subs as $s):
            $col = $s['overall_score']>=4?'#4ade80':($s['overall_score']>=3?'#facc15':'#f87171');
            $period_lbl = $s['period_label'] ?? $s['semester'] ?? '';
        ?>
        <div class="eval-item eval-item-clickable" onclick="openEvalDetails(<?= (int)$s['tracker_id'] ?>)">
            <div class="eval-item-left">
                <div class="anon"><i class="fa-solid fa-eye-slash" style="color:var(--muted)"></i> Anonymous Evaluator</div>
                <div class="meta">
                    <?= date('M d, Y', strtotime($s['submitted_at'])) ?><?= $period_lbl ? ' · ' . htmlspecialchars($period_lbl) : '' ?>
                </div>
            </div>
            <div style="display:flex;flex-direction:column;align-items:flex-end;gap:8px;">
                <div class="score-pill" style="background:<?= $col ?>1a;color:<?= $col ?>;border:1px solid <?= $col ?>44">
                    <?= number_format($s['overall_score'],2) ?> / 5
                </div>
                <button type="button" class="btn-view-details" onclick="event.stopPropagation(); openEvalDetails(<?= (int)$s['tracker_id'] ?>)">
                    View Details <i class="fa-solid fa-chevron-right"></i>
                </button>
            </div>
        </div>
    <?php endforeach; endif; ?>
    </div>
</div>

</div>

<?php elseif ($page === 'profile'): ?>
    <!-- STAFF-STYLE PROFILE / ROLE PAGE, preserving Faculty functionality and theme -->
    <div class="profile-hero">
        <div class="profile-hero-left">
            <div style="position:relative;">
                <?php if ($faculty_photo): ?>
                <img class="profile-hero-avatar" src="<?= UPLOAD_URL . htmlspecialchars($faculty_photo) ?>" alt="<?= htmlspecialchars($full_name) ?>" style="display:block;"/>
                <?php else: ?>
                <div class="profile-hero-avatar"><i class="fa-solid fa-chalkboard-user"></i></div>
                <?php endif; ?>
                <button type="button" onclick="openPhotoModal()" title="Update Profile Photo"
                        style="position:absolute;bottom:-2px;right:-2px;width:30px;height:30px;border-radius:50%;background:var(--teal);border:2px solid var(--mid);color:#fff;cursor:pointer;display:flex;align-items:center;justify-content:center;font-size:13px;">
                    <i class="fa-solid fa-camera"></i>
                </button>
            </div>
            <div>
                <div class="profile-hero-name"><?= htmlspecialchars($full_name) ?></div>
                <div class="profile-hero-desig"><i class="fa-solid fa-id-badge"></i> <?= htmlspecialchars($designation) ?></div>
                <button type="button" onclick="openPhotoModal()" style="margin-top:6px;background:none;border:none;color:var(--teal);font-size:12px;font-weight:600;cursor:pointer;padding:0;">
                    <i class="fa-solid fa-camera"></i> Update Profile Photo
                </button>
            </div>
        </div>

        <div class="level-view-wrap" id="levelDdWrap">
            <div class="level-view-label"><i class="fa-solid fa-layer-group"></i> My Teaching Level(s)</div>
            <div class="level-view-pills">
                <?php if (empty($my_levels)): ?>
                <span class="level-view-empty"><i class="fa-solid fa-triangle-exclamation"></i> No level assigned yet — contact the admin.</span>
                <?php else: foreach ($my_levels as $lvl): ?>
                <span class="level-view-pill"><?= htmlspecialchars($lvl) ?></span>
                <?php endforeach; endif; ?>
            </div>
            <div class="level-view-hint">Set by the admin — reach out to them to change this.</div>
        </div>
    </div>

    <div class="role-card">
        <div class="role-card-title">
            <i class="fa-solid fa-tags" style="color:var(--teal)"></i>
            Assign / Update My Role
        </div>
        <div class="role-card-sub">
            Your role determines which evaluation questions apply to you and how you appear in the questionnaire.
            Changes take effect immediately and the admin is notified.
        </div>

        <div class="role-chips-label"><i class="fa-solid fa-bolt" style="color:var(--teal)"></i> Quick Pick</div>
        <div class="role-chips">
            <?php foreach ($desig_suggestions as $d): ?>
            <div class="role-chip <?= ($d === $designation) ? 'is-current' : '' ?>"
                 onclick="document.getElementById('roleInput').value='<?= htmlspecialchars(addslashes($d)) ?>'">
                <?= htmlspecialchars($d) ?>
            </div>
            <?php endforeach; ?>
        </div>

        <div class="custom-role-label"><i class="fa-solid fa-pen-to-square" style="color:var(--teal)"></i> Or type a custom role</div>
        <form method="POST" id="roleForm">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>"/>
            <input type="hidden" name="update_designation" value="1"/>
            <div class="custom-role-row">
                <input type="text" name="new_designation" id="roleInput" class="custom-role-input"
                       placeholder="e.g. Department Head, Bookkeeper…"
                       value="<?= htmlspecialchars($designation) ?>" required/>
                <button type="submit" class="btn-save-role">
                    <i class="fa-solid fa-floppy-disk"></i> Save &amp; Notify Admin
                </button>
            </div>
        </form>

        <div class="info-note">
            <i class="fa-solid fa-circle-info"></i>
            <span>Your role change is logged and sent to the admin automatically so the admin can confirm or reassign you if needed.</span>
        </div>
    </div>

    <?php elseif ($page === 'settings'): ?>

<div class="settings-page-head">
    <div>
        <div class="settings-page-kicker"><i class="fa-solid fa-gear"></i> Personal Settings</div>
        <h2 class="settings-page-title">Settings</h2>
        <p class="settings-page-subtitle">Manage your account preferences, appearance, notifications, security, and evaluation access.</p>
    </div>
    <div class="settings-appearance-current"><i class="fa-solid fa-circle-half-stroke"></i> Current: <span id="appearanceVal">Light</span></div>
</div>

<div class="settings-grid">
<div class="settings-card">
            <div class="settings-card-head">
                <div class="sicon"><i class="fa-solid fa-palette"></i></div>
                <div><h3>Appearance</h3><p>Choose how the portal is displayed on this device.</p></div>
            </div>
            <div class="setting-row" style="border-top:0;padding-top:0;align-items:flex-start;">
                <div style="padding-top:2px;">
                    <strong>Theme</strong>
                    <span>Light is the default portal appearance.</span>
                </div>
                <div class="appearance-choice-row">
                    <button type="button" class="appearance-choice" id="appearanceLightBtn" onclick="setAppearance('light')"><i class="fa-solid fa-sun"></i> Light</button>
                    <button type="button" class="appearance-choice" id="appearanceDarkBtn" onclick="setAppearance('dark')"><i class="fa-solid fa-moon"></i> Dark</button>
                </div>
            </div>
        </div>
<div class="settings-card full">
            <div class="settings-card-head"><div class="sicon"><i class="fa-solid fa-user-shield"></i></div><div><h3>Account Overview</h3><p>Your access is controlled by the system administrator.</p></div></div>
            <div class="account-facts">
                <div class="account-fact"><label>Account Name</label><b><?= htmlspecialchars($full_name) ?></b></div>
                <div class="account-fact"><label>System Role</label><b>Faculty</b></div>
                <div class="account-fact"><label>Evaluation Access</label><b>Peer Evaluation</b></div>
            </div>
        </div>

        <div class="settings-card">
            <div class="settings-card-head"><div class="sicon"><i class="fa-solid fa-bell"></i></div><div><h3>Notifications</h3><p>Choose which updates you want to receive.</p></div></div>
            <form method="POST">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
                <input type="hidden" name="save_preferences" value="1">
                <div class="setting-row"><div><strong>Designation updates</strong><span>Notify me when designation changes are recorded.</span></div><label class="setting-toggle"><input type="checkbox" name="email_on_designation_update" <?= !empty($user_prefs['email_on_designation_update'])?'checked':'' ?>><span class="slider"></span></label></div>
                <div class="setting-row"><div><strong>New evaluation results</strong><span>Notify me when a new evaluation is received.</span></div><label class="setting-toggle"><input type="checkbox" name="email_on_new_evaluation" <?= !empty($user_prefs['email_on_new_evaluation'])?'checked':'' ?>><span class="slider"></span></label></div>
                <div class="setting-row"><div><strong>Evaluation details</strong><span>Allow detailed evaluation entries to appear in My Results.</span></div><label class="setting-toggle"><input type="checkbox" name="show_result_details" <?= !empty($user_prefs['show_result_details'])?'checked':'' ?>><span class="slider"></span></label></div>
                <div class="settings-actions"><button class="settings-save" type="submit"><i class="fa-solid fa-check"></i> Save Preferences</button></div>
            </form>
        </div>

        <div class="settings-card">
            <div class="settings-card-head"><div class="sicon"><i class="fa-solid fa-lock"></i></div><div><h3>Security</h3><p>Keep your account protected.</p></div></div>
            <div class="setting-row"><div><strong>Password</strong><span>Update your password without leaving the dashboard.</span></div><a href="change_password.php" class="settings-save" style="text-decoration:none;">Change</a></div>
            <div class="setting-row"><div><strong>Role protection</strong><span>Your system role is administrator-controlled.</span></div><i class="fa-solid fa-shield-halved" style="color:var(--success)"></i></div>
        </div>

        <div class="settings-card full">
            <div class="settings-card-head"><div class="sicon"><i class="fa-solid fa-circle-info"></i></div><div><h3>Faculty Evaluation Access</h3><p>What you can do in the Employee Performance Management System.</p></div></div>
            <div class="account-facts">
                <?php
                $canEvaluateParts = ['Faculty', 'Staff'];
                if ($can_evaluate_principal) $canEvaluateParts[] = 'Principal';
                if ($can_evaluate_dean)      $canEvaluateParts[] = 'Dean';
                ?>
                <div class="account-fact"><label>Can Evaluate</label><b><?= htmlspecialchars(implode(', ', $canEvaluateParts)) ?></b></div>
                <div class="account-fact"><label>Can View</label><b>Own Evaluation Results</b></div>
                <div class="account-fact"><label>Privacy</label><b>Evaluator identity remains protected</b></div>
            </div>
        </div>
    </div>

<?php elseif ($page === 'my_results'): ?>

<div class="section-card">
    <div class="section-title">
        <i class="fa-solid fa-list" style="color:var(--accent)"></i> Evaluations Received (Anonymous)
    </div>

    <button type="button" class="btn-view-all-evals" id="viewAllEvalsBtn" onclick="toggleAllEvals()">
        <i class="fa-solid fa-eye"></i> View Evaluations Received
        <i class="fa-solid fa-chevron-down" id="allEvalsCaret" style="transition:transform .2s;margin-left:auto;"></i>
    </button>

    <div id="allEvalsList" style="display:none;margin-top:14px;">
    <?php
    $allStmt = $mysqli->prepare("
        SELECT et.id AS tracker_id,
               (SELECT AVG(qa.answer_score) FROM questionnaire_answers qa WHERE qa.tracker_id = et.id) AS overall_score,
               et.submitted_at, ep.period_label, ep.semester
        FROM evaluation_tracker et
        LEFT JOIN evaluation_periods ep ON ep.id = et.period_id
        WHERE et.target_user_id=?
        ORDER BY et.submitted_at DESC
    ");
    $allStmt->bind_param("i", $user_id);
    $allStmt->execute();
    $all_subs = $allStmt->get_result();
    if (!$all_subs || $all_subs->num_rows === 0):
    ?>
    <div class="empty-state"><i class="fa-solid fa-inbox"></i><p>No evaluations recorded yet.</p></div>
    <?php else: while ($s = $all_subs->fetch_assoc()):
        $col = $s['overall_score']>=4?'#4ade80':($s['overall_score']>=3?'#facc15':'#f87171');
        $period_lbl = $s['period_label'] ?? $s['semester'] ?? '';
    ?>
    <div class="eval-item eval-item-clickable" onclick="openEvalDetails(<?= (int)$s['tracker_id'] ?>)">
        <div class="eval-item-left">
            <div class="anon"><i class="fa-solid fa-eye-slash" style="color:var(--muted)"></i> Anonymous Evaluator</div>
            <div class="meta">
                <?= date('M d, Y g:i A', strtotime($s['submitted_at'])) ?><?= $period_lbl ? ' · ' . htmlspecialchars($period_lbl) : '' ?>
            </div>
        </div>
        <div style="display:flex;flex-direction:column;align-items:flex-end;gap:8px;">
            <div class="score-pill" style="background:<?= $col ?>1a;color:<?= $col ?>;border:1px solid <?= $col ?>44">
                <?= number_format($s['overall_score'],2) ?> / 5
            </div>
            <?php if (!empty($user_prefs['show_result_details'])): ?>
            <button type="button" class="btn-view-details" onclick="event.stopPropagation(); openEvalDetails(<?= (int)$s['tracker_id'] ?>)">
                View Details <i class="fa-solid fa-chevron-right"></i>
            </button>
            <?php endif; ?>
        </div>
    </div>
    <?php endwhile; $allStmt->close(); endif; ?>
    </div>
</div>

<!-- ══════════ EVALUATION — STEP 1: CHOOSE GROUP ══════════ -->
<?php elseif ($page === 'peer' && $peer_group === null): ?>

<?php
// Counts mirror the same shared-predicate + Non-Teaching-Staff grouping
// used to build the Step 2 lists below and admin/questionnaire.php's own
// Peer-to-Peer cards. The currently logged-in faculty account has already
// been excluded from $peers_all above.
$teacher_count = count(array_filter($peers_all, fn($p) => resolve_peer_group($mysqli, $p) === 'teacher'));
$staff_count = count(array_filter($peers_all, fn($p) => resolve_peer_group($mysqli, $p) === 'staff'));
?>

<?php if (!$evaluation_open): ?>
<div class="no-period-warn"><i class="fa-solid fa-clock"></i> Evaluation is currently closed until the scheduled opening time.</div>
<?php endif; ?>

<div class="section-card">
    <div class="section-title"><i class="fa-solid fa-clipboard-check" style="color:var(--teal)"></i> Evaluation</div>
    <p style="font-size:13px;color:var(--muted);margin-bottom:20px;">Choose who you want to evaluate. Faculty can evaluate fellow faculty, staff members, or the Dean / Principal.</p>

    <div class="fg-label" style="margin-bottom:10px;"><i class="fa-solid fa-bolt" style="margin-right:5px"></i>Step 1: Select Evaluation Group</div>
    <div class="eval-group-grid">
        <a href="faculty_dashboard.php?page=peer&group=teacher" class="eval-group-card">
            <div class="eval-group-icon blue"><i class="fa-solid fa-chalkboard-user"></i></div>
            <div class="eval-group-title">Faculty</div>
            <div class="eval-group-count"><?= $teacher_count ?> member<?= $teacher_count == 1 ? '' : 's' ?></div>
        </a>
        <a href="faculty_dashboard.php?page=peer&group=staff" class="eval-group-card">
            <div class="eval-group-icon green"><i class="fa-solid fa-briefcase"></i></div>
            <div class="eval-group-title">Staff</div>
            <div class="eval-group-count"><?= $staff_count ?> member<?= $staff_count == 1 ? '' : 's' ?></div>
        </a>
        <?php if ($can_evaluate_principal): ?>
        <a href="faculty_dashboard.php?page=peer&group=principal" class="eval-group-card">
            <div class="eval-group-icon purple"><i class="fa-solid fa-user-tie"></i></div>
            <div class="eval-group-title">Principal</div>
            <div class="eval-group-count"><?= count($principal_targets) ?> member<?= count($principal_targets) == 1 ? '' : 's' ?></div>
        </a>
        <?php endif; ?>
        <?php if ($can_evaluate_dean): ?>
        <a href="faculty_dashboard.php?page=peer&group=dean" class="eval-group-card">
            <div class="eval-group-icon gold"><i class="fa-solid fa-graduation-cap"></i></div>
            <div class="eval-group-title">Dean</div>
            <div class="eval-group-count"><?= count($dean_targets) ?> member<?= count($dean_targets) == 1 ? '' : 's' ?></div>
        </a>
        <?php endif; ?>
    </div>
    <?php if ($can_evaluate_principal && $can_evaluate_dean): ?>
    <div class="peer-select-hint"><i class="fa-solid fa-circle-info"></i> You teach both JHS/SHS and College, so you can evaluate both the Principal and the Dean.</div>
    <?php elseif ($can_evaluate_principal): ?>
    <div class="peer-select-hint"><i class="fa-solid fa-circle-info"></i> Your JHS/SHS teaching assignment makes you eligible to evaluate the Principal. Dean evaluation is only for those with a College teaching assignment.</div>
    <?php elseif ($can_evaluate_dean): ?>
    <div class="peer-select-hint"><i class="fa-solid fa-circle-info"></i> Your College teaching assignment makes you eligible to evaluate the Dean. Principal evaluation is only for those with a JHS/SHS teaching assignment.</div>
    <?php else: ?>
    <div class="peer-select-hint"><i class="fa-solid fa-circle-info"></i> Dean/Principal evaluation isn't available yet — it opens once your teaching assignment (Role &amp; Designation) is set.</div>
    <?php endif; ?>
</div>

<!-- ══════════ EVALUATION — STEP 2: TARGET LIST ══════════ -->
<?php elseif ($page === 'peer' && $peer_group !== null): ?>

<a href="faculty_dashboard.php?page=peer" class="back-link"><i class="fa-solid fa-arrow-left"></i> Change Evaluation Group</a>

<?php if (!$evaluation_open): ?>
<div class="no-period-warn"><i class="fa-solid fa-clock"></i> Evaluation is currently closed until the scheduled opening time.</div>
<?php endif; ?>

<div class="section-card">
    <div class="section-title"><i class="fa-solid <?= in_array($peer_group, $school_head_groups, true) ? 'fa-user-tie' : 'fa-users' ?>" style="color:var(--teal)"></i>
        <?= in_array($peer_group, $school_head_groups, true) ? htmlspecialchars($peer_group_labels[$peer_group]) : 'Fellow ' . htmlspecialchars($peer_group_labels[$peer_group]) . ' Members' ?>
    </div>
    <p style="font-size:13px;color:var(--muted);margin-bottom:18px;">
        <?= in_array($peer_group, $school_head_groups, true)
            ? 'Select the ' . htmlspecialchars($peer_group_labels[$peer_group]) . ' to evaluate. Your identity will be kept confidential.'
            : 'Select a colleague to evaluate. Your identity will be kept confidential.' ?>
    </p>

    <?php $sh_role_eligible = $peer_group === 'principal' ? $can_evaluate_principal : ($peer_group === 'dean' ? $can_evaluate_dean : true); ?>
    <?php if (in_array($peer_group, $school_head_groups, true) && !$sh_role_eligible): ?>
    <div class="empty-state"><i class="fa-solid fa-lock"></i><p>
        Evaluating the <?= htmlspecialchars($peer_group_labels[$peer_group]) ?> is only available to Faculty/Teaching Staff with a <?= $peer_group === 'principal' ? 'Grade 7-12' : 'College' ?> teaching assignment.
    </p></div>
    <?php elseif (empty($peers)): ?>
    <div class="empty-state"><i class="fa-solid fa-users"></i><p>
        <?= in_array($peer_group, $school_head_groups, true) ? 'No ' . htmlspecialchars($peer_group_labels[$peer_group]) . ' account was found.' : 'No registered ' . htmlspecialchars(strtolower($peer_group_labels[$peer_group])) . ' members found.' ?>
    </p></div>
    <?php else: ?>
    <div class="peer-grid">
        <?php foreach ($peers as $p):
            $done = in_array($peer_group, $school_head_groups, true)
                ? in_array((int)$p['id'], $done_school_heads, true)
                : in_array((int)$p['id'], $done_peers, true);
        ?>
        <div class="peer-card <?= $done?'done':'' ?>">
            <?php if ($p['photo']): ?>
            <img class="peer-photo" src="<?= UPLOAD_URL . htmlspecialchars($p['photo']) ?>" alt=""/>
            <?php else: ?>
            <div class="peer-photo-ph"><i class="fa-solid fa-user"></i></div>
            <?php endif; ?>
            <div class="peer-name"><?= htmlspecialchars($p['full_name']) ?></div>
            <div class="peer-desig"><?= htmlspecialchars(peer_display_label($mysqli, $p, $peer_group, $peer_group_labels)) ?></div>
            <?php if ($done): ?>
            <div class="done-badge"><i class="fa-solid fa-circle-check"></i> Done</div>
            <?php else: ?>
            <button class="btn-eval-peer" <?= !$evaluation_open?'disabled':'' ?>
                    onclick="window.location='faculty_dashboard.php?page=peer_eval&tid=<?= $p['id'] ?>&group=<?= urlencode($peer_group) ?>'">
                Evaluate
            </button>
            <?php endif; ?>
        </div>
        <?php endforeach; ?>
    </div>
    <?php
        $done_in_group = in_array($peer_group, $school_head_groups, true)
            ? count(array_intersect(array_map('intval', array_column($peers, 'id')), $done_school_heads))
            : count(array_intersect(array_map('intval', array_column($peers, 'id')), $done_peers));
    ?>
    <?php if ($done_in_group > 0): ?>
    <div class="peer-select-hint warn"><i class="fa-solid fa-circle-check"></i>
        You've already evaluated <?= $done_in_group ?> of <?= count($peers) ?> <?= htmlspecialchars(strtolower($peer_group_labels[$peer_group])) ?> members this period.
    </div>
    <?php endif; ?>
    <?php endif; ?>
</div>

<?php elseif ($page === 'peer_eval' && $peer_target): ?>

<a href="faculty_dashboard.php?page=peer&group=<?= urlencode($peer_eval_group) ?>" class="back-link">
    <i class="fa-solid fa-arrow-left"></i> Back to <?= htmlspecialchars($peer_group_labels[$peer_eval_group]) ?> List
</a>

<div class="eval-header-bar">
    <?php if ($peer_target['photo']): ?>
    <img class="eval-photo-lg" src="<?= UPLOAD_URL . htmlspecialchars($peer_target['photo']) ?>" alt=""/>
    <?php else: ?>
    <div class="eval-photo-ph"><i class="fa-solid fa-user"></i></div>
    <?php endif; ?>
    <div>
        <div class="eval-name"><?= htmlspecialchars($peer_target['full_name']) ?></div>
        <div class="eval-desig"><?= htmlspecialchars(peer_display_label($mysqli, $peer_target, $peer_eval_group ?? 'teacher', $peer_group_labels)) ?></div>
    </div>
</div>

<?php if (empty($peer_questions)): ?>
<div style="background:rgba(251,191,36,.07);border:1px solid rgba(251,191,36,.18);border-radius:10px;padding:18px;color:#fcd34d;font-size:13px;display:flex;gap:10px;">
    <i class="fa-solid fa-triangle-exclamation"></i> No evaluation questions have been set up yet. Please contact the admin.
</div>
<?php else: ?>

<div class="scale-legend-bar">
    <?php foreach ([5=>'Always',4=>'Often',3=>'Sometimes',2=>'Rarely',1=>'Never'] as $num=>$lbl): ?>
    <div class="legend-pill"><span class="l-num"><?= $num ?></span><span class="l-lbl"><?= $lbl ?></span></div>
    <?php endforeach; ?>
</div>

<form method="POST" action="faculty_dashboard.php?page=peer_eval&tid=<?= $peer_target['id'] ?>&group=<?= urlencode($peer_eval_group) ?>" id="evalForm" novalidate>
    <div class="eval-validation-alert" id="facultyEvalValidation" role="alert" aria-live="assertive"></div>
    <div class="eval-progress" id="facultyEvalProgress" aria-live="polite">0 of 0 questions answered</div>
    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>"/>
    <input type="hidden" name="submit_peer" value="1"/>
    <input type="hidden" name="target_id"   value="<?= $peer_target['id'] ?>"/>
    <input type="hidden" name="form_id"     value="<?= $peer_form_id ?>"/>
    <input type="hidden" name="period_id"   value="<?= $period['id'] ?? 0 ?>"/>
    <input type="hidden" name="group"       value="<?= htmlspecialchars($peer_eval_group) ?>"/>

    <?php
    $grouped_qs = [];
    foreach ($peer_questions as $q) $grouped_qs[$q['category'] ?? 'General'][] = $q;
    $cat_icons  = ['Work Performance'=>'fa-briefcase','Professional Ethics'=>'fa-scale-balanced','Interpersonal Skills'=>'fa-handshake','Teaching'=>'fa-chalkboard-user','Communication'=>'fa-comments','General'=>'fa-layer-group'];
    $qno        = 1;
    foreach ($grouped_qs as $cat => $qs):
        $icon = $cat_icons[$cat] ?? 'fa-layer-group';
    ?>
    <div class="q-category-header"><i class="fa-solid <?= $icon ?>"></i><?= htmlspecialchars($cat) ?></div>
    <div class="eval-form-wrap">
    <table class="eval-form-table">
        <thead><tr><th>Question</th><th>5</th><th>4</th><th>3</th><th>2</th><th>1</th></tr></thead>
        <tbody>
        <?php foreach ($qs as $q): ?>
        <tr class="eval-question-row">
            <td><div class="eval-form-qtext"><span class="eval-form-qno">Q<?= $qno++ ?>.</span><?= htmlspecialchars($q['question_text']) ?></div></td>
            <?php foreach ([5,4,3,2,1] as $v): $optId = 'r_' . $q['id'] . '_' . $v; ?>
            <td><div class="eval-form-rating"><input type="radio" name="ratings[<?= $q['id'] ?>]" id="<?= $optId ?>" value="<?= $v ?>"><label for="<?= $optId ?>"><?= $v ?></label></div></td>
            <?php endforeach; ?>
        </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    </div>
    <?php endforeach; ?>

    <div class="comment-box-new">
        <i class="fa-solid fa-comment-dots comment-box-icon"></i>
        <div class="comment-box-inner">
            <div class="comment-box-label">Comments &amp; Suggestions</div>
            <textarea name="comment" class="comment-textarea-new" placeholder="Share your thoughts or suggestions…"></textarea>
        </div>
    </div>

    <div class="submit-row-new">
        <span style="font-size:13px;color:var(--muted);"><i class="fa-solid fa-circle-info" style="color:#60a5fa;margin-right:5px"></i>Rate 1 (Never) – 5 (Always). All questions required.</span>
        <div style="display:flex;gap:10px;">
            <button type="button" class="btn-cancel-new" onclick="window.location='faculty_dashboard.php?page=peer&group=<?= urlencode($peer_eval_group) ?>'">Cancel</button>
            <button type="submit" class="btn-submit-new"><i class="fa-solid fa-paper-plane"></i> Submit Evaluation</button>
        </div>
    </div>
</form>
<?php endif; ?>

<!-- ══════════ EVALUATION — INVALID / MISSING TARGET ══════════ -->
<?php elseif ($page === 'peer_eval' && !$peer_target): ?>

<a href="faculty_dashboard.php?page=peer" class="back-link"><i class="fa-solid fa-arrow-left"></i> Back to Evaluation Selection</a>

<div style="background:rgba(240,84,84,.08);border:1px solid rgba(240,84,84,.25);border-radius:10px;padding:18px;color:#fca5a5;font-size:13px;display:flex;gap:10px;">
    <i class="fa-solid fa-circle-exclamation"></i>
    <?= htmlspecialchars($peer_group_error ?: "Selected user does not exist, is inactive, or is not a valid Faculty/Staff account. Please choose someone from the list.") ?>
</div>

<?php endif; ?>

</main>

<script>
function toggleRecentEvals() {
    const list  = document.getElementById('recentEvalsList');
    const caret = document.getElementById('recentEvalsCaret');
    const isOpen = list.style.display !== 'none';
    list.style.display = isOpen ? 'none' : 'block';
    caret.style.transform = isOpen ? '' : 'rotate(180deg)';
}

function toggleAllEvals() {
    const list  = document.getElementById('allEvalsList');
    const caret = document.getElementById('allEvalsCaret');
    const isOpen = list.style.display !== 'none';
    list.style.display = isOpen ? 'none' : 'block';
    caret.style.transform = isOpen ? '' : 'rotate(180deg)';
}

function checkAll() {
    const form = document.getElementById('evalForm');
    if (!form) return false;

    const radios = [...form.querySelectorAll('input[type="radio"][name^="ratings["]')];
    const groups = [...new Set(radios.map(r => r.name))];
    const missing = groups.filter(name => !form.querySelector('input[name="' + CSS.escape(name) + '"]:checked'));
    const alertBox = document.getElementById('facultyEvalValidation');

    form.querySelectorAll('.eval-question-row.eval-unanswered').forEach(row => row.classList.remove('eval-unanswered'));

    if (!groups.length) {
        if (alertBox) {
            alertBox.innerHTML = '<strong>No questions found.</strong> Please close and try again.';
            alertBox.classList.add('show');
            alertBox.scrollIntoView({behavior:'smooth', block:'center'});
        }
        return false;
    }

    if (missing.length) {
        const labels = [];
        missing.forEach(name => {
            const input = form.querySelector('input[name="' + CSS.escape(name) + '"]');
            const row = input ? input.closest('.eval-question-row') : null;
            if (row) {
                row.classList.add('eval-unanswered');
                const q = row.querySelector('.eval-form-qno');
                if (q) labels.push(q.textContent.replace(/[^0-9]/g, ''));
            }
        });

        const plural = missing.length === 1 ? 'question is' : 'questions are';
        const detail = labels.length ? ' Missing: <strong>Q' + labels.join(', Q') + '.</strong>' : '';
        if (alertBox) {
            alertBox.innerHTML = '<strong>Please complete all required questions.</strong> ' + missing.length + ' ' + plural + ' unanswered.' + detail;
            alertBox.classList.add('show');
            alertBox.scrollIntoView({behavior:'smooth', block:'center'});
        }

        const firstRow = form.querySelector('.eval-question-row.eval-unanswered');
        if (firstRow) setTimeout(() => firstRow.scrollIntoView({behavior:'smooth', block:'center'}), 120);
        return false;
    }

    if (alertBox) alertBox.classList.remove('show');
    return true;
}

function updateFacultyEvalProgress() {
    const form = document.getElementById('evalForm');
    const counter = document.getElementById('facultyEvalProgress');
    if (!form || !counter) return;

    const groups = [...new Set([...form.querySelectorAll('input[type="radio"][name^="ratings["]')].map(r => r.name))];
    const answered = groups.filter(name => form.querySelector('input[name="' + CSS.escape(name) + '"]:checked')).length;
    counter.textContent = answered + ' of ' + groups.length + ' questions answered';
    counter.classList.toggle('is-complete', groups.length > 0 && answered === groups.length);

    if (groups.length > 0 && answered === groups.length) {
        document.getElementById('facultyEvalValidation')?.classList.remove('show');
        form.querySelectorAll('.eval-question-row.eval-unanswered').forEach(row => row.classList.remove('eval-unanswered'));
    }
}

const facultyEvalForm = document.getElementById('evalForm');
if (facultyEvalForm) {
    facultyEvalForm.addEventListener('change', function(e) {
        if (!e.target.matches('input[type="radio"][name^="ratings["]')) return;
        const row = e.target.closest('.eval-question-row');
        if (row) row.classList.remove('eval-unanswered');
        updateFacultyEvalProgress();
    });
    facultyEvalForm.addEventListener('submit', function(e) {
        if (!checkAll()) e.preventDefault();
    });
    updateFacultyEvalProgress();
}
function openEvalDetails(trackerId) {
    const modal = document.getElementById('evalDetailsModal');
    const body  = document.getElementById('evalDetailsBody');
    body.innerHTML = '<div class="eval-modal-loading"><i class="fa-solid fa-spinner fa-spin"></i>Loading evaluation…</div>';
    modal.classList.add('open');
    document.body.style.overflow = 'hidden';

    fetch('get_evaluation_details.php?tracker_id=' + encodeURIComponent(trackerId))
        .then(r => r.json())
        .then(data => {
            body.innerHTML = data.ok
                ? renderEvalDetails(data)
                : '<div class="eval-modal-loading"><i class="fa-solid fa-triangle-exclamation"></i>' + (data.error || 'Unable to load this evaluation.') + '</div>';
        })
        .catch(() => {
            body.innerHTML = '<div class="eval-modal-loading"><i class="fa-solid fa-triangle-exclamation"></i>Something went wrong loading this evaluation.</div>';
        });
}
function closeEvalDetails() {
    document.getElementById('evalDetailsModal').classList.remove('open');
    document.body.style.overflow = '';
}
document.getElementById('evalDetailsModal').addEventListener('click', function(e) {
    if (e.target === this) closeEvalDetails();
});

function starsHtml(score) {
    let h = '';
    for (let i = 1; i <= 5; i++) h += `<i class="fa-solid fa-star eval-q-star ${i <= score ? 'filled' : ''}"></i>`;
    return h;
}

function renderEvalDetails(data) {
    let html = `<div class="eval-info-grid">
        <div class="eval-info-item"><div class="eval-info-label">Evaluator</div><div class="eval-info-value"><i class="fa-solid fa-eye-slash" style="color:var(--muted);margin-right:5px"></i>Anonymous Evaluator</div></div>
        <div class="eval-info-item"><div class="eval-info-label">Period</div><div class="eval-info-value">${escapeHtml(data.period_label || '—')}</div></div>
        <div class="eval-info-item"><div class="eval-info-label">Submitted</div><div class="eval-info-value">${escapeHtml(data.submitted_at)}</div></div>
        <div class="eval-info-item"><div class="eval-info-label">Overall Score</div><div class="eval-info-value" style="color:var(--teal-hover)">${data.overall_score.toFixed(2)} / 5</div></div>
    </div>`;

    if (data.categories && data.categories.length) {
        html += `<div class="section-title" style="font-size:14px;margin-bottom:12px;"><i class="fa-solid fa-layer-group" style="color:var(--teal)"></i> Performance by Category</div>`;
        data.categories.forEach(c => {
            const pct = Math.round((c.avg / 5) * 100);
            const col = c.avg >= 4 ? '#4ade80' : (c.avg >= 3 ? '#facc15' : '#f87171');
            html += `<div class="cat-row">
                <div class="cat-name">${escapeHtml(c.category)}</div>
                <div class="cat-bar-bg"><div class="cat-bar-fill" style="width:${pct}%;background:${col}"></div></div>
                <div class="cat-score" style="color:${col}">${c.avg.toFixed(2)}</div>
            </div>`;
        });
    }

    if (data.questions && data.questions.length) {
        html += `<div class="section-title" style="font-size:14px;margin:20px 0 12px;"><i class="fa-solid fa-list-check" style="color:var(--accent)"></i> Question-by-Question Results</div>`;
        let lastCat = null;
        data.questions.forEach((q, idx) => {
            if (q.category !== lastCat) {
                html += `<div class="q-category-header" style="margin-top:${lastCat===null?'0':'18px'}">${escapeHtml(q.category)}</div>`;
                lastCat = q.category;
            }
            html += `<div class="q-card-new" style="padding:14px 16px;">
                <div class="q-no-new">Question ${idx + 1}</div>
                <div class="q-text-new" style="margin-bottom:8px;">${escapeHtml(q.question_text)}</div>
                <div style="display:flex;align-items:center;gap:10px;">
                    <div>${starsHtml(q.score)}</div>
                    <div style="font-size:12px;color:var(--muted);font-weight:700;">Score: ${q.score} / 5</div>
                </div>
            </div>`;
        });
    }

    html += `<div class="section-title" style="font-size:14px;margin:20px 0 10px;"><i class="fa-solid fa-comment-dots" style="color:var(--teal-hover)"></i> Comments / Feedback</div>`;
    html += data.comment
        ? `<div class="eval-comment-box">"${escapeHtml(data.comment)}"</div>`
        : `<div class="eval-comment-box empty">No written feedback was provided.</div>`;

    return html;
}

function escapeHtml(str) {
    if (str === null || str === undefined) return '';
    const d = document.createElement('div');
    d.textContent = str;
    return d.innerHTML;
}
document.addEventListener('click', function(e) {
    const sb = document.getElementById('sidebar');
    if (sb && sb.classList.contains('open') && !sb.contains(e.target) && !e.target.closest('.hamburger')) {
        sb.classList.remove('open');
    }
});

// ── Real-time notification bell ──
let notificationKnownIds = new Set();
let notificationFirstRefresh = true;
let notificationRefreshBusy = false;

function toggleNotifDropdown(e) {
    e.stopPropagation();
    document.getElementById('notifDropdown')?.classList.toggle('show');
    refreshNotifications();
}

document.addEventListener('click', function(e) {
    const wrap = document.getElementById('notifWrap');
    if (wrap && !wrap.contains(e.target)) {
        document.getElementById('notifDropdown')?.classList.remove('show');
    }
});

function escapeNotificationHtml(value) {
    return String(value ?? '').replace(/[&<>\"']/g, ch => ({
        '&':'&amp;', '<':'&lt;', '>':'&gt;', '\"':'&quot;', "'":'&#039;'
    }[ch] || ch));
}

function notificationVisual(type) {
    if (type === 'evaluation_received') {
        return { icon: 'fa-star', color: '#facc15' };
    }
    if (type === 'designation_update') {
        return { icon: 'fa-id-badge', color: '#2B6CB0' };
    }
    if (type === 'teaching_assignment') {
        return { icon: 'fa-chalkboard-user', color: '#2B6CB0' };
    }
    if (type === 'academic_period') {
        return { icon: 'fa-calendar-days', color: '#2B6CB0' };
    }
    if (type === 'evaluation_schedule') {
        return { icon: 'fa-clock', color: '#2B6CB0' };
    }
    return { icon: 'fa-bell', color: 'var(--teal-hover)' };
}

function renderLiveNotifications(items) {
    const list = document.querySelector('#notifDropdown .notif-list');
    if (!list) return;

    if (!items.length) {
        list.innerHTML = '<div class="notif-empty"><i class="fa-regular fa-bell-slash"></i>No notifications yet.</div>';
        return;
    }

    list.innerHTML = items.map(n => {
        const v = notificationVisual(n.type);
        const unread = Number(n.is_read) === 0;
        return `<div class="notif-item ${unread ? 'unread' : ''}" data-notification-id="${Number(n.id) || 0}">
` +
            `  <div class="notif-icon" style="color:${v.color};background:${v.color}22;"><i class="fa-solid ${v.icon}"></i></div>
` +
            `  <div style="flex:1;min-width:0;">
` +
            `    <div class="notif-text">${escapeNotificationHtml(n.message)}</div>
` +
            `    <div class="notif-meta">${escapeNotificationHtml(n.created_at_label)}</div>
` +
            `  </div>
` +
            `</div>`;
    }).join('');
}

function updateNotificationBadge(count) {
    const button = document.getElementById('notifBtn');
    const existing = document.getElementById('notifBadge');
    const safeCount = Math.max(0, Number(count) || 0);

    button?.classList.toggle('has-unread', safeCount > 0);

    if (safeCount > 0) {
        const label = safeCount > 99 ? '99+' : String(safeCount);
        if (existing) {
            existing.textContent = label;
        } else if (button) {
            const badge = document.createElement('span');
            badge.className = 'notif-badge show';
            badge.id = 'notifBadge';
            badge.textContent = label;
            button.appendChild(badge);
        }
    } else if (existing) {
        existing.remove();
    }
}

function showLiveNotificationToast(notification) {
    const existing = document.getElementById('liveNotifToast');
    if (existing) existing.remove();

    const toast = document.createElement('div');
    toast.id = 'liveNotifToast';
    toast.className = 'live-notif-toast';
    toast.innerHTML = `<div class="live-notif-toast-icon"><i class="fa-solid fa-bell"></i></div>` +
        `<div><strong>New notification</strong><span>${escapeNotificationHtml(notification.message)}</span></div>`;
    document.body.appendChild(toast);
    requestAnimationFrame(() => toast.classList.add('show'));
    setTimeout(() => {
        toast.classList.remove('show');
        setTimeout(() => toast.remove(), 250);
    }, 5000);
}

async function refreshNotifications(showNewToast = true) {
    if (notificationRefreshBusy || document.hidden) return;
    notificationRefreshBusy = true;
    try {
        const response = await fetch('notifications_api.php?_=' + Date.now(), {
            method: 'GET',
            credentials: 'same-origin',
            cache: 'no-store',
            headers: { 'Accept': 'application/json' }
        });
        if (!response.ok) throw new Error('Notification request failed');
        const data = await response.json();
        if (!data.success) throw new Error(data.message || 'Notification request failed');

        const incoming = Array.isArray(data.notifications) ? data.notifications : [];
        if (!notificationFirstRefresh && showNewToast) {
            const newUnread = incoming.filter(n => Number(n.is_read) === 0 && !notificationKnownIds.has(Number(n.id)));
            if (newUnread.length) showLiveNotificationToast(newUnread[0]);
        }

        notificationKnownIds = new Set(incoming.map(n => Number(n.id)));
        notificationFirstRefresh = false;
        renderLiveNotifications(incoming);
        updateNotificationBadge(data.unread_count);
    } catch (err) {
        // Keep the last rendered notification state when polling temporarily fails.
        console.debug('Notification polling:', err.message);
    } finally {
        notificationRefreshBusy = false;
    }
}

function startNotificationPolling() {
    refreshNotifications(false);
    setInterval(() => refreshNotifications(true), 5000);
    document.addEventListener('visibilitychange', () => {
        if (!document.hidden) refreshNotifications(false);
    });

    const markReadForm = document.querySelector('#notifDropdown form');
    if (markReadForm) {
        markReadForm.addEventListener('submit', async function(e) {
            e.preventDefault();
            try {
                const response = await fetch('notifications_api.php', {
                    method: 'POST',
                    credentials: 'same-origin',
                    cache: 'no-store',
                    headers: { 'Accept': 'application/json' },
                    body: new FormData(markReadForm)
                });
                if (!response.ok) throw new Error('Mark-read request failed');
                const data = await response.json();
                if (!data.success) throw new Error(data.message || 'Mark-read request failed');
                renderLiveNotifications(Array.isArray(data.notifications) ? data.notifications : []);
                updateNotificationBadge(data.unread_count);
            } catch (err) {
                console.debug('Mark notifications read:', err.message);
            }
        });
    }
}

document.addEventListener('DOMContentLoaded', startNotificationPolling);

// ── Appearance toggle (Dark / Light) ──
function applyAppearance(mode) {
    const normalized = mode === 'dark' ? 'dark' : 'light';
    document.body.classList.toggle('light-theme', normalized === 'light');
    const value = document.getElementById('appearanceVal');
    if (value) value.textContent = normalized === 'light' ? 'Light' : 'Dark';
    const lightBtn = document.getElementById('appearanceLightBtn');
    const darkBtn  = document.getElementById('appearanceDarkBtn');
    if (lightBtn) lightBtn.classList.toggle('active', normalized === 'light');
    if (darkBtn) darkBtn.classList.toggle('active', normalized === 'dark');
}
function setAppearance(mode) {
    const normalized = mode === 'dark' ? 'dark' : 'light';
    localStorage.setItem('pbi_theme', normalized);
    applyAppearance(normalized);
}
document.addEventListener('DOMContentLoaded', function() {
    const savedTheme = localStorage.getItem('pbi_theme');
    const theme = savedTheme === 'dark' ? 'dark' : 'light';
    localStorage.setItem('pbi_theme', theme);
    applyAppearance(theme);
    // Reveal the page now that the correct theme class is in place
    // (only needed when the FOUC guard above hid it for the dark case).
    document.body.style.visibility = 'visible';
});

// ── Profile photo modal ──
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
</script>

<?php $mysqli->close(); ?>
<script src="../admin/eval_status_poll.js" defer></script>
</body>
</html>
