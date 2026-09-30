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
        require_once '../shared/ea_personnel_service.php';
require_once '../shared/system_settings_service.php';
require_once '../shared/QuestionnaireService.php';
        // ── AUTH GUARD ────────────────────────────────────────────────
        if (empty($_SESSION['user_id']) || $_SESSION['role'] !== 'staff') {
            header("Location: staff_login.php"); exit;
        }

        $user_id     = $_SESSION['user_id'];
        $full_name   = $_SESSION['full_name']   ?? 'Staff';
        $designation = $_SESSION['designation'] ?? 'Staff';

qn_migrate_legacy_once($mysqli);
        $page        = $_GET['page'] ?? 'dashboard';
        // NOTE: 'peer' / 'peer_eval' are live pages in their own right (the
        // Peer Evaluation feature below) and must NOT be aliased away — only
        // the old EA-only URLs collapse into the unified Staff Evaluation page.
        if ($page === 'ea_eval') $page = 'staff_eval';
        if ($page === 'ea_eval_form') $page = 'staff_eval_form';

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
function staff_schedule_settings(mysqli $mysqli): array {
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

function staff_parse_manila_datetime(?string $raw): ?DateTimeImmutable {
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

function staff_schedule_is_open(mysqli $mysqli): bool {
    $s = staff_schedule_settings($mysqli);
    $mode = strtolower(trim((string)($s['control_mode'] ?? 'schedule')));

    if ($mode === 'open') return true;
    if ($mode === 'closed') return false;

    $start = staff_parse_manila_datetime($s['eval_start'] ?? '');
    $end   = staff_parse_manila_datetime($s['eval_end'] ?? '');
    if (!$start || !$end || $end <= $start) return false;

    $now = new DateTimeImmutable('now', new DateTimeZone('Asia/Manila'));
    return $now >= $start && $now < $end;
}


        // ── FETCH MY PHOTO ────────────────────────────────────────────
        $photo_url   = null;
        $staff_photo = '';
        $pq = $mysqli->prepare("SELECT photo FROM users WHERE id=? LIMIT 1");
        $pq->bind_param("i", $user_id); $pq->execute(); $pq->bind_result($photo_db); $pq->fetch(); $pq->close();
        if (!empty($photo_db)) { $photo_url = '../image/' . $photo_db; $staff_photo = $photo_db; }

        // ── VALID SPECIFIC YEAR LEVELS ───────────────────────────────────
        // Same exact list & string values as admin/manage_privileged_accounts.php
        // uses for students and for faculty/staff assignment — keeping these
        // identical is what lets a staff member's picks match real students.
        $year_levels = [
            'Grade 7','Grade 8','Grade 9','Grade 10',
            'Grade 11','Grade 12',
            '1st Year College','2nd Year College','3rd Year College','4th Year College',
        ];

        // ── FETCH MY ASSIGNED TEACHING LEVEL(S) ─────────────────────────
        // A staff member can be assigned to more than one SPECIFIC year
        // level at once (e.g. Grade 8 AND Grade 10), so this is a list, not
        // a single value.
        $my_levels = [];
        $lvlQ = $mysqli->prepare("SELECT year_level FROM user_year_levels WHERE user_id=?");
        $lvlQ->bind_param("i", $user_id);
        $lvlQ->execute();
        $lvlRes = $lvlQ->get_result();
        while ($lr = $lvlRes->fetch_assoc()) $my_levels[] = $lr['year_level'];
        $lvlQ->close();

        // ── DERIVE TEACHING SCOPE (College vs JHS/SHS) ──────────────────
        // A Staff account that has been assigned to teach one or more year
        // levels (user_year_levels — set via admin/manage_privileged_accounts.php's
        // "Year Level(s) Responsible For / Teaching") is Teaching Staff. Which
        // supervisor(s) that Staff account evaluates depends on WHICH levels:
        // any College level ('1st Year College'..'4th Year College') means
        // they evaluate the Dean; any Grade level ('Grade 7'..'Grade 12', i.e.
        // JHS/SHS) means they evaluate the Principal; teaching both scopes at
        // once means they evaluate both the Dean and the Principal. Staff with
        // zero teaching assignments are non-teaching Staff and instead
        // evaluate the Executive Assistant. Teaching Staff (of any scope)
        // additionally get Peer Evaluation (evaluating fellow Teacher/Staff),
        // which non-teaching Staff do not. Enforced both in the render
        // (below) and again in the submit handlers, since those must never
        // trust page state alone.
        $staff_has_teaching_assignment = !empty($my_levels);
        $staff_teaches_college  = false;
        $staff_teaches_basic_ed = false; // JHS/SHS (Grade 7–12)
        foreach ($my_levels as $lvl) {
            if (stripos($lvl, 'College') !== false) $staff_teaches_college  = true;
            if (stripos($lvl, 'Grade') !== false)   $staff_teaches_basic_ed = true;
        }

        // Human-readable summary of who this Staff account can currently
        // evaluate, used on the Role & Designation page.
        $staff_can_evaluate_parts = [];
        if ($staff_teaches_college)  $staff_can_evaluate_parts[] = 'Dean';
        if ($staff_teaches_basic_ed) $staff_can_evaluate_parts[] = 'Principal';
        if ($staff_has_teaching_assignment) $staff_can_evaluate_parts[] = 'Fellow Teacher/Staff (Peer Evaluation)';
        if (empty($staff_can_evaluate_parts)) $staff_can_evaluate_parts[] = 'Executive Assistant';
        $staff_can_evaluate_label = implode(', ', $staff_can_evaluate_parts);

        // ── STAFF EVALUATION "HOME" PAGE ─────────────────────────────
        // Non-teaching Staff evaluate the EA under the dedicated Staff
        // Evaluation page/nav item. Teaching Staff no longer get a separate
        // Staff Evaluation nav item — their Dean/Principal evaluation is
        // folded into the Peer Evaluation page instead (rendered above the
        // Teacher/Staff designation picker), so every link/redirect that
        // used to point at 'staff_eval' for them now points at 'peer'.
        $staff_eval_home_page = $staff_has_teaching_assignment ? 'peer' : 'staff_eval';
        $staff_eval_feature_label = $staff_has_teaching_assignment ? 'Evaluation' : 'Staff Evaluation';

        // Teaching Staff no longer have a standalone Staff Evaluation landing
        // page — Dean/Principal evaluation now lives inside Peer Evaluation's
        // Step 1 designation picker. Send old bookmarks/links straight there
        // instead of showing an interstitial "this moved" screen.
        if ($staff_has_teaching_assignment && $page === 'staff_eval') {
            header('Location: staff_dashboard.php?page=peer'); exit;
        }

        // ── AUTO-CREATE notifications table ──────────────────────────
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

        // ── AUTO-CREATE role_change_log table ────────────────────────
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
        $prefStmt = $mysqli->prepare("SELECT email_on_designation_update,email_on_new_evaluation,show_result_details,compact_dashboard FROM user_preferences WHERE user_id=? LIMIT 1");
        $prefStmt->bind_param('i',$user_id); $prefStmt->execute();
        $user_prefs = $prefStmt->get_result()->fetch_assoc() ?: ['email_on_designation_update'=>1,'email_on_new_evaluation'=>1,'show_result_details'=>1,'compact_dashboard'=>0];
        $prefStmt->close();

        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_preferences'])) {
            if (!csrf_check()) { $_SESSION['toast_error']='Your session expired. Please try again.'; header('Location: staff_dashboard.php?page=settings'); exit; }
            $p1=isset($_POST['email_on_designation_update'])?1:0; $p2=isset($_POST['email_on_new_evaluation'])?1:0;
            $p3=isset($_POST['show_result_details'])?1:0; $p4=isset($_POST['compact_dashboard'])?1:0;
            $up=$mysqli->prepare("INSERT INTO user_preferences (user_id,email_on_designation_update,email_on_new_evaluation,show_result_details,compact_dashboard) VALUES (?,?,?,?,?) ON DUPLICATE KEY UPDATE email_on_designation_update=VALUES(email_on_designation_update),email_on_new_evaluation=VALUES(email_on_new_evaluation),show_result_details=VALUES(show_result_details),compact_dashboard=VALUES(compact_dashboard)");
            $up->bind_param('iiiii',$user_id,$p1,$p2,$p3,$p4); $up->execute(); $up->close();
            $_SESSION['toast']='Settings saved successfully.'; header('Location: staff_dashboard.php?page=settings'); exit;
        }

        // ── UPDATE MY DESIGNATION ─────────────────────────────────────
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_designation'])) {
            if (!csrf_check()) {
                $_SESSION['toast_error'] = "Your session expired or the request could not be verified. Please try again.";
                header("Location: staff_dashboard.php?page=profile"); exit;
            }
            $new_desig = trim($_POST['new_designation'] ?? '');
            $old_desig = $designation;

            if (empty($new_desig)) {
                $_SESSION['toast_error'] = "Designation cannot be empty.";
            } elseif ($new_desig === $old_desig) {
                $_SESSION['toast_error'] = "That is already your current designation.";
            } else {
                $stmt = $mysqli->prepare("UPDATE users SET designation=? WHERE id=?");
                $stmt->bind_param("si", $new_desig, $user_id);
                $stmt->execute(); $stmt->close();
                $designation = $new_desig;
                $_SESSION['designation'] = $new_desig;

                // Notify admin
                $message = $full_name . ' updated their designation from "' . $old_desig . '" to "' . $new_desig . '".';
                $extra   = json_encode(['user_id'=>$user_id,'full_name'=>$full_name,'role'=>'staff','old_desig'=>$old_desig,'new_desig'=>$new_desig]);
                $nstmt   = $mysqli->prepare("INSERT INTO notifications (type, user_id, message, extra_data) VALUES ('designation_update', ?, ?, ?)");
                $nstmt->bind_param("iss", $user_id, $message, $extra);
                $nstmt->execute(); $nstmt->close();

                // Also log to role_change_log so it shows up in the admin dashboard's
                // System Audits box and notification bell.
                $rstmt = $mysqli->prepare("INSERT INTO role_change_log (user_id, old_role, new_role, old_designation, new_designation) VALUES (?, 'staff', 'staff', ?, ?)");
                $rstmt->bind_param("iss", $user_id, $old_desig, $new_desig);
                $rstmt->execute(); $rstmt->close();

                $_SESSION['toast'] = "Your designation has been updated to \"$new_desig\" and the admin has been notified.";
            }
            header("Location: staff_dashboard.php?page=profile"); exit;
        }

        // ── UPDATE TEACHING LEVEL(S) ──────────────────────────────────
        // Multi-select — a staff member can be assigned to more than one
        // specific year level at once (e.g. teaches both Grade 8 and Grade 10).
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_levels'])) {
            if (!csrf_check()) {
                $_SESSION['toast_error'] = "Your session expired or the request could not be verified. Please try again.";
                header("Location: staff_dashboard.php?page=profile"); exit;
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
                error_log('[staff_dashboard] update_levels failed for user_id=' . $user_id . ': ' . $e->getMessage());
                $_SESSION['toast_error'] = "Failed to update teaching levels. Please try again.";
            }
            header("Location: staff_dashboard.php?page=profile"); exit;
        }

        // ── CHANGE PASSWORD ─────────────────────────────────────────────
        // ── MARK NOTIFICATIONS READ ──────────────────────────────────
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['mark_notifications_read'])) {
            if (!csrf_check()) {
                header("Location: staff_dashboard.php?page=" . urlencode($page)); exit;
            }
            $mr = $mysqli->prepare("UPDATE notifications SET is_read=1 WHERE user_id=? AND is_read=0");
            $mr->bind_param("i", $user_id);
            $mr->execute(); $mr->close();
            header("Location: staff_dashboard.php?page=" . urlencode($page)); exit;
        }

        // ── ACTIVE PERIOD ─────────────────────────────────────────────
        $period = null;
        $pr = $mysqli->query("SELECT * FROM evaluation_periods WHERE is_active=1 LIMIT 1");
        if ($pr) $period = $pr->fetch_assoc();

        // Do not use evaluation_periods.is_active as the schedule clock.
        // Follow Schedule must remain closed until the exact Asia/Manila
        // opening instant, and must close immediately at the exact end.
        $evaluation_open = staff_schedule_is_open($mysqli);

        // ── DASHBOARD SCHEDULE SUMMARY ─────────────────────────────────
        // Use the same database-backed settings as the admin Settings page.
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
        if (($dashboard_sys['control_mode'] ?? 'schedule') === 'open') $dashboard_status = 'Open';
        elseif (($dashboard_sys['control_mode'] ?? 'schedule') === 'closed') $dashboard_status = 'Closed';
        elseif ($dashboard_start && $dashboard_end) {
            $dashboard_now = new DateTimeImmutable('now', $dashboard_tz);
            if ($dashboard_now >= $dashboard_end) $dashboard_status = 'Closed';
            elseif ($dashboard_now >= $dashboard_start) $dashboard_status = 'Open';
        }
        $dashboard_window = ($dashboard_start && $dashboard_end) ? $dashboard_start->format('M j, Y') . ' – ' . $dashboard_end->format('M j, Y') : 'Not configured';
        $dashboard_open_display = $dashboard_start ? $dashboard_start->format('M j, Y g:i A') : 'Not configured';
        $dashboard_close_display = $dashboard_end ? $dashboard_end->format('M j, Y g:i A') : 'Not configured';
        $dashboard_status_class = strtolower($dashboard_status);

        // ── STAFF EVALUATION QUESTIONNAIRE ─────────────────────────────
        // Non-teaching Staff evaluate the Executive Assistant. Teaching Staff
        // instead evaluate the Dean and/or Principal, chosen by which
        // level(s) they teach (see $staff_teaches_college / $staff_teaches_basic_ed
        // above) — never the EA. The Questionnaire feature is the single
        // source of truth: questions are read directly from evaluation_questions
        // using eval_type='staff' and the selected target_type (Dean / Principal / EA).
        $staff_eval_targets = [];

        if ($staff_has_teaching_assignment) {
            $wanted_roles = [];
            if ($staff_teaches_college)  $wanted_roles[] = 'dean';
            if ($staff_teaches_basic_ed) $wanted_roles[] = 'principal';

            if (!empty($wanted_roles)) {
                $rolesInClause = "'" . implode("','", array_map(fn($r) => $mysqli->real_escape_string($r), $wanted_roles)) . "'";
                $dnprStmt = $mysqli->query("
                    SELECT id, full_name, designation, photo, role
                    FROM users
                    WHERE role IN ($rolesInClause)
                      AND is_active=1
                      AND account_status='approved'
                    ORDER BY FIELD(role,'dean','principal'), full_name ASC
                ");
                if ($dnprStmt) {
                    while ($row = $dnprStmt->fetch_assoc()) {
                        $staff_eval_targets[] = [
                            'id' => (int)$row['id'],
                            'full_name' => $row['full_name'],
                            'designation' => $row['designation'] ?? '',
                            'photo' => $row['photo'] ?? '',
                            'target_type' => strtolower($row['role']) === 'dean' ? 'Dean' : 'Principal',
                            'target_label' => ucfirst(strtolower($row['role']))
                        ];
                    }
                    $dnprStmt->free();
                }
            }
        } else {
            $ea_candidates = ea_get_executive_assistants($mysqli, (int)($period['id'] ?? 0));
            foreach ($ea_candidates as $ea) {
                $staff_eval_targets[] = [
                    'id' => (int)$ea['id'],
                    'full_name' => $ea['full_name'] ?? 'Executive Assistant',
                    'designation' => $ea['position'] ?? ($ea['designation'] ?? ''),
                    'photo' => $ea['photo'] ?? '',
                    'target_type' => 'EA',
                    'target_label' => 'Executive Assistant'
                ];
            }
        }

        $staff_eval_target = null;
        if (isset($_GET['tid'])) {
            $requestedStaffTargetId = (int)$_GET['tid'];
            foreach ($staff_eval_targets as $candidate) {
                if ((int)$candidate['id'] === $requestedStaffTargetId) {
                    $staff_eval_target = $candidate;
                    break;
                }
            }
        }
        if (!$staff_eval_target && !empty($staff_eval_targets)) {
            $staff_eval_target = $staff_eval_targets[0];
        }

        $staff_eval_questions = [];
        $staff_eval_categories = [];
        $staff_eval_already_done = false;

        if (in_array($page, ['staff_eval','staff_eval_form'], true) && $staff_eval_target) {
            $q = $mysqli->prepare("
                SELECT id, category, question_text
                FROM user_questions
                WHERE user_id=?
                  AND target_type=?
                  AND eval_type='general'
                ORDER BY category ASC, id ASC
            ");
            $q->bind_param('is', $staff_eval_target['id'], $staff_eval_target['target_type']);
            $q->execute();
            $staff_eval_questions = $q->get_result()->fetch_all(MYSQLI_ASSOC);
            $q->close();

            foreach ($staff_eval_questions as $qrow) {
                $cat = trim($qrow['category'] ?? '') ?: 'General';
                $staff_eval_categories[$cat][] = $qrow;
            }

            if ($period) {
                $d = $mysqli->prepare("
                    SELECT id
                    FROM evaluation_tracker
                    WHERE evaluator_id=?
                      AND target_user_id=?
                      AND period_id=?
                      AND eval_type='staff'
                      AND status='submitted'
                    LIMIT 1
                ");
                $d->bind_param('iii', $user_id, $staff_eval_target['id'], $period['id']);
                $d->execute();
                $staff_eval_already_done = (bool)$d->get_result()->fetch_assoc();
                $d->close();
            }
        }

        // ── MY EVALUATION RESULTS ─────────────────────────────────────
        // Reads from evaluation_tracker + questionnaire_answers (student evaluations of this staff)
        $my_avg    = null;
        $my_total  = 0;
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

        // ── PEER EVALUATION GROUPING (same rules as Faculty dashboard) ───
        // A user's Peer Evaluation bucket is based on their actual function:
        //   • Faculty accounts -> Faculty bucket
        //   • Staff accounts WITH a teaching assignment -> Faculty bucket
        //   • Staff accounts WITHOUT a teaching assignment -> Staff bucket
        // This is deliberately DB-backed so a Staff member becomes part of the
        // Faculty peer roster as soon as the EA assigns a teaching level.
        function isNonTeachingStaff(mysqli $mysqli, int $user_id): bool {
            $stmt = $mysqli->prepare(
                "SELECT NOT EXISTS(
                    SELECT 1 FROM user_year_levels yl WHERE yl.user_id=?
                 ) AS is_non_teaching"
            );
            $stmt->bind_param('i', $user_id);
            $stmt->execute();
            $row = $stmt->get_result()->fetch_assoc();
            $stmt->close();
            return (bool)($row['is_non_teaching'] ?? false);
        }

        function resolve_peer_group(mysqli $mysqli, array $u): ?string {
            // Keep Staff classification independent from optional shared helper
            // functions. A Staff account with at least one EA-assigned teaching
            // level belongs in the Faculty/Teacher peer bucket; a Staff account
            // without a teaching assignment belongs in the Non-Teaching Staff bucket.
            $role = strtolower(trim((string)($u['role'] ?? '')));
            if (in_array($role, ['teacher', 'faculty'], true)) return 'teacher';
            if ($role === 'staff') {
                return isNonTeachingStaff($mysqli, (int)($u['id'] ?? 0)) ? 'staff' : 'teacher';
            }
            return null;
        }

        // Show Teaching Staff clearly inside the Faculty bucket while leaving
        // their stored role/designation untouched.
        function peer_display_label(mysqli $mysqli, array $p, string $peer_group, array $peer_group_labels): string {
            $role = strtolower(trim($p['role'] ?? ''));
            if ($peer_group === 'teacher' && $role === 'staff') {
                $lvlQ = $mysqli->prepare("SELECT year_level FROM user_year_levels WHERE user_id=? ORDER BY year_level ASC");
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
        $peer_group_labels = ['teacher' => 'Faculty', 'staff' => 'Staff', 'dean' => 'Dean', 'principal' => 'Principal'];

        // ── ADD peer_group COLUMN TO evaluation_tracker (idempotent) ──
        // Stores which of the two designation groups (Teacher/Staff) the
        // evaluator picked in Step 1, alongside the existing tracker row.
        $colChk = $mysqli->query("SHOW COLUMNS FROM evaluation_tracker LIKE 'peer_group'");
        if ($colChk && $colChk->num_rows === 0) {
            $mysqli->query("ALTER TABLE evaluation_tracker ADD COLUMN peer_group VARCHAR(20) NULL AFTER eval_type");
        }

        // ── ENSURE A UNIQUE CONSTRAINT BACKS THE DUPLICATE-EVAL CHECK ──
        // The application-level duplicate check below (SELECT ... then INSERT)
        // has a race window between two near-simultaneous submissions. This
        // unique index makes the DB itself the source of truth: a second insert
        // for the same (evaluator, target, eval_type, period) will fail with a
        // duplicate-key error instead of silently creating a second row.
        $idxChk = $mysqli->query("SHOW INDEX FROM evaluation_tracker WHERE Key_name = 'uniq_eval_submission'");
        if ($idxChk && $idxChk->num_rows === 0) {
            $mysqli->query("ALTER TABLE evaluation_tracker ADD UNIQUE INDEX uniq_eval_submission (evaluator_id, target_user_id, eval_type, period_id)");
        }

        // ── REPAIR LEGACY STAFF-EVALUATION TRACKER ROWS ───────────────
        // Older Staff Evaluation submissions were written with the target
        // type (Dean/Principal/EA) in evaluation_tracker.eval_type instead
        // of the dedicated 'staff' evaluation type. Normalize those rows so
        // the Staff Evaluation report and completion checks can see them.
        // Do not touch a row if a correct 'staff' row already exists for the
        // same evaluator/target/period, avoiding a unique-key collision.
        $mysqli->query("
            UPDATE evaluation_tracker bad
            LEFT JOIN evaluation_tracker good
              ON good.evaluator_id = bad.evaluator_id
             AND good.target_user_id = bad.target_user_id
             AND good.period_id = bad.period_id
             AND good.eval_type = 'staff'
            SET bad.eval_type = 'staff'
            WHERE bad.peer_group = 'Staff Evaluation'
              AND bad.eval_type IN ('Dean','Principal','EA')
              AND good.id IS NULL
        ");

        // ── SUBMIT STAFF EVALUATION ────────────────────────────────────
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_staff_evaluation'])) {
            if (!csrf_check()) {
                $_SESSION['toast_error'] = "Your session expired or the request could not be verified. Please try again.";
                header("Location: staff_dashboard.php?page=$staff_eval_home_page"); exit;
            }

            // No teaching-assignment gate here: $staff_eval_targets was just
            // freshly re-derived above from $my_levels/$staff_teaches_college/
            // $staff_teaches_basic_ed on THIS request, so the foreach lookup
            // below already rejects any target_id that isn't currently valid
            // for this Staff account (EA for non-teaching, Dean/Principal
            // matching their current teaching scope) — no separate re-check
            // is needed.

            $tid = (int)($_POST['target_id'] ?? 0);
            $target = null;
            foreach ($staff_eval_targets as $candidate) {
                if ((int)$candidate['id'] === $tid) {
                    $target = $candidate;
                    break;
                }
            }

            $ratings = $_POST['ratings'] ?? [];
            $comments = trim($_POST['comments'] ?? '');
            $errors = [];

            if (!staff_schedule_is_open($mysqli)) {
                $errors[] = "Evaluation is currently closed. Please return at the scheduled opening time.";
            } elseif (!$target) {
                $errors[] = "The {$staff_eval_feature_label} target is not currently available.";
            } else {
                // Use the live active period instead of trusting a stale page.
                $currentPeriodResult = $mysqli->query("SELECT * FROM evaluation_periods WHERE is_active=1 LIMIT 1");
                $currentPeriod = $currentPeriodResult ? $currentPeriodResult->fetch_assoc() : null;
                if (!$currentPeriod) {
                    $errors[] = "No evaluation period is currently open.";
                } else {
                    $period = $currentPeriod;
                $validStmt = $mysqli->prepare("
                    SELECT id
                    FROM user_questions
                    WHERE user_id=?
                      AND eval_type='general'
                      AND target_type=?
                ");
                $validStmt->bind_param('is', $target['id'], $target['target_type']);
                $validStmt->execute();
                $validRes = $validStmt->get_result();
                $valid_question_ids = [];
                while ($vq = $validRes->fetch_assoc()) $valid_question_ids[] = (int)$vq['id'];
                $validStmt->close();

                $ratings = array_filter($ratings, function($val, $qid) use ($valid_question_ids) {
                    return in_array((int)$qid, $valid_question_ids, true);
                }, ARRAY_FILTER_USE_BOTH);

                if (empty($valid_question_ids) || count($ratings) !== count($valid_question_ids)) {
                    $errors[] = 'Please rate all questions before submitting.';
                } else {
                    foreach ($ratings as $qid => $score) {
                        $score = (int)$score;
                        if ($score < 1 || $score > 5) {
                            $errors[] = 'Please use a rating from 1 to 5 for every question.';
                            break;
                        }
                    }
                }

                if (!$errors && in_array($target['target_type'], ['Dean','Principal','EA'], true)) {
                    $dup = $mysqli->prepare("
                        SELECT id
                        FROM evaluation_tracker
                        WHERE evaluator_id=?
                          AND target_user_id=?
                          AND period_id=?
                          AND eval_type='staff'
                        LIMIT 1
                    ");
                    $dup->bind_param('iii', $user_id, $tid, $period['id']);
                    $dup->execute();
                    $already = (bool)$dup->get_result()->fetch_assoc();
                    $dup->close();
                    if ($already) $errors[] = 'You have already evaluated this person for this period.';
                }
                }
            }

            if (!$errors) {
                $overallScore = round(array_sum($ratings) / count($ratings), 2);
                $periodId = (int)$period['id'];

                // Use a real active form id when available for tracker compatibility.
                $formId = 0;
                $formLookup = $mysqli->query("SELECT id FROM questionnaire_forms WHERE is_active=1 ORDER BY id DESC LIMIT 1");
                if ($formLookup && ($fr = $formLookup->fetch_assoc())) $formId = (int)$fr['id'];
                if ($formLookup) $formLookup->free();

                $mysqli->begin_transaction();
                try {
                    $trk = $mysqli->prepare("
                        INSERT INTO evaluation_tracker
                        (evaluator_id,target_user_id,form_id,period_id,eval_type,peer_group,score,remarks,status,submitted_at)
                        VALUES (?,?,?,?,?,?,?,?,'submitted',NOW())
                    ");
                    $peerGroupLabel = 'Staff Evaluation';
                    $evalType = 'staff';
                    $trk->bind_param('iiiissds', $user_id, $tid, $formId, $periodId, $evalType, $peerGroupLabel, $overallScore, $comments);
                    $trk->execute();
                    $trackerId = $mysqli->insert_id;
                    $trk->close();

                    $ans = $mysqli->prepare("
                        INSERT INTO questionnaire_answers
                        (tracker_id,question_id,question_source,user_question_id,answer_score,submitted_at)
                        VALUES (?,NULL,'user',?,?,NOW())
                    ");
                    foreach ($ratings as $qid => $rating) {
                        $qid = (int)$qid;
                        $score = min(5, max(1, (int)$rating));
                        $ans->bind_param('iii', $trackerId, $qid, $score);
                        $ans->execute();
                    }
                    $ans->close();

                    $mysqli->commit();

                    $targetLabel = $target['target_label'];
                    $notifMsg = "You have received a new Staff Evaluation.";
                    $nins = $mysqli->prepare("INSERT INTO notifications (type, user_id, message, extra_data) VALUES ('evaluation_received', ?, ?, ?)");
                    $extra = json_encode([
                        'evaluation_type' => 'staff',
                        'target_type' => $target['target_type'],
                        'target_label' => $targetLabel
                    ]);
                    $nins->bind_param('iss', $tid, $notifMsg, $extra);
                    $nins->execute();
                    $nins->close();

                    $_SESSION['toast'] = "{$staff_eval_feature_label} for {$target['target_label']} submitted successfully.";
                    header("Location: staff_dashboard.php?page=$staff_eval_home_page"); exit;
                } catch (Throwable $e) {
                    $mysqli->rollback();
                    error_log('[staff_dashboard] submit staff evaluation failed for evaluator=' . $user_id . ' target=' . $tid . ': ' . $e->getMessage());
                    $_SESSION['toast_error'] = "Unable to submit the {$staff_eval_feature_label}. Please try again.";
                    header('Location: staff_dashboard.php?page=staff_eval_form&tid=' . $tid); exit;
                }
            }

            $_SESSION['toast_error'] = implode(' ', $errors);
            header('Location: staff_dashboard.php?page=staff_eval_form&tid=' . $tid); exit;
        }

        // ── PEER EVALUATION ───────────────────────────────────────────
        // 'dean' and 'principal' are Peer Evaluation designations too now
        // (folded in from the old Staff Evaluation page) — they use
        // $staff_eval_targets (already scoped to this Staff account's
        // teaching level) instead of the teacher/staff peers query, and
        // their "done" state comes from eval_type='staff' (the existing
        // Dean/Principal tracker rows), not 'staff_peer'.
        $peers_all  = [];   // all eligible Teacher/Staff peers, unfiltered — used for counts
        $peers      = [];   // peers/targets filtered down to the selected group
        $done_peers = [];
        $done_supervisors = []; // Dean/Principal target_user_ids already evaluated this period
        $peer_group = null; // 'teacher' | 'staff' | 'dean' | 'principal' | null (Step 1 not yet completed)

        if ($page === 'peer' && $staff_has_teaching_assignment) {
            $pr2 = $mysqli->prepare("SELECT id, full_name, designation, photo, role FROM users WHERE role IN ('teacher','staff','faculty') AND is_active=1 AND account_status='approved' AND id != ? ORDER BY full_name ASC");
            $pr2->bind_param("i", $user_id);
            $pr2->execute();
            $pr2res = $pr2->get_result();
            if ($pr2res) $peers_all = $pr2res->fetch_all(MYSQLI_ASSOC);
            $pr2->close();

            // Done if already has a Peer Evaluation tracker entry for this person in the current period
            if ($period) {
                $dpStmt = $mysqli->prepare("SELECT target_user_id FROM evaluation_tracker WHERE evaluator_id=? AND period_id=? AND eval_type IN ('faculty_peer','peer','staff_peer')");
                $dpStmt->bind_param("ii", $user_id, $period['id']);
            } else {
                $dpStmt = $mysqli->prepare("SELECT target_user_id FROM evaluation_tracker WHERE evaluator_id=? AND eval_type IN ('faculty_peer','peer','staff_peer')");
                $dpStmt->bind_param("i", $user_id);
            }
            $dpStmt->execute();
            $dp = $dpStmt->get_result();
            if ($dp) while ($r = $dp->fetch_assoc()) $done_peers[] = $r['target_user_id'];
            $dpStmt->close();

            // Done if already has an eval_type='staff' tracker entry (Dean/Principal)
            $dsStmt = $mysqli->prepare("SELECT target_user_id FROM evaluation_tracker WHERE evaluator_id=? AND eval_type='staff'");
            $dsStmt->bind_param("i", $user_id);
            $dsStmt->execute();
            $ds = $dsStmt->get_result();
            if ($ds) while ($r = $ds->fetch_assoc()) $done_supervisors[] = $r['target_user_id'];
            $dsStmt->close();

            if (isset($_GET['group']) && in_array($_GET['group'], ['teacher', 'staff', 'dean', 'principal'], true)) {
                $peer_group = $_GET['group'];
                if (in_array($peer_group, ['dean', 'principal'], true)) {
                    $wantedTargetType = ucfirst($peer_group);
                    $peers = array_values(array_filter($staff_eval_targets, fn($t) => $t['target_type'] === $wantedTargetType));
                } else {
                    $peers = array_values(array_filter($peers_all, function ($p) use ($mysqli, $peer_group) {
                        return resolve_peer_group($mysqli, $p) === $peer_group;
                    }));
                }
            }
        }

        // ── PEER EVAL FORM ────────────────────────────────────────────
        $peer_target       = null;
        $peer_questions    = [];
        $peer_categories   = [];
        $peer_eval_group   = null;  // group carried over from Step 1, validated below
        $peer_group_error  = '';    // set when tid/group don't match, shown in the invalid-target view

        if ($page === 'peer_eval' && $staff_has_teaching_assignment && isset($_GET['tid'])) {
            $tid = intval($_GET['tid']);
            $req_group = $_GET['group'] ?? null;

            $tu  = $mysqli->prepare("SELECT * FROM users WHERE id=? AND role IN ('teacher','staff','faculty') AND is_active=1 AND account_status='approved' LIMIT 1");
            $tu->bind_param("i", $tid); $tu->execute();
            $peer_target = $tu->get_result()->fetch_assoc(); $tu->close();

            if ($peer_target && !in_array($req_group, ['teacher', 'staff'], true)) {
                $peer_group_error = "Please select a designation.";
                $peer_target = null;
            } elseif ($peer_target) {
                $actual_group = resolve_peer_group($mysqli, $peer_target);
                if ($actual_group !== $req_group) {
                    $peer_group_error = "The selected user does not belong to the selected designation.";
                    $peer_target = null;
                } else {
                    $peer_eval_group = $req_group;
                }
            }

            if ($peer_target) {
                // Questionnaire Management assigns questions differently per bucket:
                // Non-Teaching Staff targets use their per-user Staff questionnaire, while
                // Faculty and Teaching Staff targets use the shared Faculty questionnaire.
                // A Teaching Staff account is therefore evaluated exactly like a Faculty
                // target once an EA assigns teaching responsibility.
                if ($peer_eval_group === 'staff') {
                    $qs = $mysqli->prepare("SELECT * FROM user_questions WHERE user_id=? AND target_type='Staff' AND eval_type='general' ORDER BY category ASC, id ASC");
                    $qs->bind_param("i", $tid); $qs->execute();
                } else {
                    $qs = $mysqli->prepare("SELECT * FROM evaluation_questions WHERE target_type='Faculty' AND eval_type='general' AND evaluator_role='shared' AND is_active=1 ORDER BY category ASC, id ASC");
                    $qs->execute();
                }
                $peer_questions = $qs->get_result()->fetch_all(MYSQLI_ASSOC); $qs->close();
                foreach ($peer_questions as $q) {
                    $cat = $q['category'] ?: 'General';
                    $peer_categories[$cat][] = $q;
                }
            }
        }

        // ── SUBMIT PEER EVAL ──────────────────────────────────────────
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_peer'])) {
            if (!csrf_check()) {
                $_SESSION['toast_error'] = "Your session expired or the request could not be verified. Please try again.";
                header("Location: staff_dashboard.php?page=peer"); exit;
            }

            // Strict re-check, mirroring the Staff Evaluation submit handler:
            // eligibility can change between page load and submit, so this is
            // re-derived from $my_levels, never trusted from the rendered form.
            if (!$staff_has_teaching_assignment) {
                $_SESSION['toast_error'] = "Peer Evaluation is only available to Staff with a teaching assignment.";
                header("Location: staff_dashboard.php?page=dashboard"); exit;
            }

            $submitted_group = $_POST['group'] ?? '';
            $tid_raw         = $_POST['target_id'] ?? '';
            $ratings         = $_POST['ratings'] ?? [];
            $comments        = trim($_POST['comments'] ?? '');

            // Re-check the live schedule on every POST. The browser may have
            // loaded the form before the schedule changed or may be stale.
            if (!staff_schedule_is_open($mysqli)) {
                $_SESSION['toast_error'] = "Evaluation is currently closed. Please return at the scheduled opening time.";
                header("Location: staff_dashboard.php?page=peer"); exit;
            }

            // ── Validation (per the Peer Evaluation designation-selection update) ──
            if (empty($submitted_group) || !in_array($submitted_group, ['teacher', 'staff'], true)) {
                $_SESSION['toast_error'] = "Please select a designation.";
                header("Location: staff_dashboard.php?page=peer"); exit;
            }
            if (empty($tid_raw)) {
                $_SESSION['toast_error'] = "Please select a user to evaluate.";
                header("Location: staff_dashboard.php?page=peer&group=" . urlencode($submitted_group)); exit;
            }
            $tid = intval($tid_raw);

            $tchk = $mysqli->prepare("SELECT id, designation, role FROM users WHERE id=? AND role IN ('teacher','staff','faculty') AND is_active=1 AND account_status='approved' LIMIT 1");
            $tchk->bind_param("i", $tid); $tchk->execute();
            $tchkRow = $tchk->get_result()->fetch_assoc(); $tchk->close();

            if (!$tchkRow) {
                $_SESSION['toast_error'] = "The selected user does not exist.";
                header("Location: staff_dashboard.php?page=peer&group=" . urlencode($submitted_group)); exit;
            }
            if (resolve_peer_group($mysqli, $tchkRow) !== $submitted_group) {
                $_SESSION['toast_error'] = "The selected user does not belong to the selected designation.";
                header("Location: staff_dashboard.php?page=peer&group=" . urlencode($submitted_group)); exit;
            }

            // Eligibility check — peer evaluations have no level restriction (per requirements,
            // staff/faculty can evaluate anyone they've worked with), but this still blocks
            // self-evaluation and invalid targets before anything gets inserted.
            [$eligible, $eligMsg] = canPeerEvaluate($mysqli, $user_id, $tid);

            if (!$eligible) {
                $_SESSION['toast_error'] = $eligMsg;
                header("Location: staff_dashboard.php?page=peer&group=" . urlencode($submitted_group)); exit;
            }

            // Gate on an active evaluation period -- no period open, no submissions.
            $periodStmt = $mysqli->query("SELECT id FROM evaluation_periods WHERE is_active=1 LIMIT 1");
            $activePeriod = $periodStmt ? $periodStmt->fetch_assoc() : null;
            if (!$activePeriod) {
                $_SESSION['toast_error'] = "No evaluation period is currently open.";
                header("Location: staff_dashboard.php?page=peer&group=" . urlencode($submitted_group)); exit;
            }
            $period_id = (int)$activePeriod['id'];

            // Only accept ratings for question IDs that actually belong to this
            // target's EA-assigned peer question set — anything else in the POST
            // is ignored rather than trusted, so a tampered request can't insert
            // stray rows. Mirrors the display-side fetch: per-user user_questions
            // for Non-Teaching Staff targets, shared Faculty-bucket questions otherwise.
            if ($submitted_group === 'staff') {
                $validQStmt = $mysqli->prepare("SELECT id FROM user_questions WHERE user_id=? AND target_type='Staff' AND eval_type='general'");
                $validQStmt->bind_param("i", $tid);
            } else {
                $validQStmt = $mysqli->prepare("SELECT id FROM evaluation_questions WHERE target_type='Faculty' AND eval_type='general' AND evaluator_role='shared' AND is_active=1");
            }
            $validQStmt->execute();
            $validQRes = $validQStmt->get_result();
            $valid_question_ids = [];
            if ($validQRes) while ($vq = $validQRes->fetch_assoc()) $valid_question_ids[] = (int)$vq['id'];
            $validQStmt->close();

            $ratings = array_filter($ratings, function ($val, $qid) use ($valid_question_ids) {
                return in_array((int)$qid, $valid_question_ids, true);
            }, ARRAY_FILTER_USE_BOTH);

            if (empty($ratings) || count($ratings) !== count($valid_question_ids)) {
                $_SESSION['toast_error'] = "Please rate all questions before submitting.";
                header("Location: staff_dashboard.php?page=peer_eval&tid=" . $tid . "&group=" . urlencode($submitted_group)); exit;
            }

            // Check duplicate for this period -- evaluator_id is this staff's user_id
            $dup = $mysqli->prepare("SELECT id FROM evaluation_tracker WHERE evaluator_id=? AND target_user_id=? AND period_id=? AND eval_type IN ('faculty_peer','peer','staff_peer') LIMIT 1");
            $dup->bind_param("iii", $user_id, $tid, $period_id); $dup->execute(); $dup->store_result();

            if ($dup->num_rows === 0) {
                $dup->close();
                try {
                    $mysqli->begin_transaction();
$eval_type         = 'faculty_peer';
$overall           = count($ratings) ? round(array_sum($ratings)/count($ratings), 2) : 0;
$peer_group_label  = $peer_group_labels[$submitted_group] ?? ucfirst($submitted_group);

$trk = $mysqli->prepare("INSERT INTO evaluation_tracker (evaluator_id, target_user_id, form_id, period_id, eval_type, peer_group, score, remarks, status, submitted_at) VALUES (?,?,?,?,?,?,?,?,'submitted',NOW())");
$trk->bind_param("iiiissds", $user_id, $tid, $peer_form_id, $period_id, $eval_type, $peer_group_label, $overall, $comments);
$trk->execute();
$tracker_id = $mysqli->insert_id; $trk->close();

                    $questionSource = $submitted_group === 'teacher' ? 'evaluation' : 'user';
                    $ins = $mysqli->prepare("INSERT INTO questionnaire_answers (tracker_id, question_id, question_source, user_question_id, answer_score, submitted_at) VALUES (?, ?, ?, ?, ?, NOW())");
                    foreach ($ratings as $qid => $rating) {
                        $qid   = intval($qid);
                        $score = min(5, max(1, intval($rating)));
                        $questionId = $questionSource === 'evaluation' ? $qid : null;
                        $userQuestionId = $questionSource === 'user' ? $qid : null;
                        $ins->bind_param("iisii", $tracker_id, $questionId, $questionSource, $userQuestionId, $score);
                        $ins->execute();
                    }
                    $ins->close();
                    $mysqli->commit();

                    // Notify the evaluated staff member — anonymously, no evaluator identity attached.
                    $notif_msg = "You have received a new peer evaluation.";
                    $nins = $mysqli->prepare("INSERT INTO notifications (type, user_id, message) VALUES ('evaluation_received', ?, ?)");
                    $nins->bind_param("is", $tid, $notif_msg);
                    $nins->execute(); $nins->close();

                    $_SESSION['toast'] = "Peer evaluation submitted!";
                } catch (Exception $e) {
                    $mysqli->rollback();
                    error_log('[staff_dashboard] submit_peer failed for evaluator=' . $user_id . ' target=' . $tid . ': ' . $e->getMessage());
                    // A duplicate-key error from the new unique index lands here too
                    // (race between two near-simultaneous submissions) — same
                    // user-facing message either way, no internal detail leaked.
                    $_SESSION['toast_error'] = (($mysqli->errno ?? 0) === 1062)
                        ? "You already evaluated this staff member."
                        : "Submission failed. Please try again.";
                }
            } else {
                $dup->close();
                $_SESSION['toast_error'] = "You already evaluated this staff member.";
            }
            header("Location: staff_dashboard.php?page=peer&group=" . urlencode($submitted_group)); exit;
        }


        // ── RECENT SUBMISSIONS ────────────────────────────────────────
        $recent_subs = [];
        $rsStmt = $mysqli->prepare("
            SELECT et.id as tracker_id, et.submitted_at,
                   (SELECT AVG(qa.answer_score) FROM questionnaire_answers qa WHERE qa.tracker_id = et.id) as overall_score
            FROM evaluation_tracker et
            WHERE et.target_user_id = ?
            ORDER BY et.submitted_at DESC LIMIT 5
        ");
        $rsStmt->bind_param("i", $user_id);
        $rsStmt->execute();
        $rs = $rsStmt->get_result();
        if ($rs) $recent_subs = $rs->fetch_all(MYSQLI_ASSOC);
        $rsStmt->close();

        // ── MY NOTIFICATIONS (bell) ──────────────────────────────────
        $my_notifications = [];
        $unread_count     = 0;
        $nq = $mysqli->prepare("SELECT id, type, message, is_read, created_at FROM notifications WHERE user_id=? ORDER BY created_at DESC, id DESC LIMIT 15");
        $nq->bind_param("i", $user_id);
        $nq->execute();
        $nres = $nq->get_result();
        if ($nres) $my_notifications = $nres->fetch_all(MYSQLI_ASSOC);
        $nq->close();
        foreach ($my_notifications as $n) if (empty($n['is_read'])) $unread_count++;

        // ── DESIGNATION SUGGESTIONS ───────────────────────────────────
        $desig_suggestions = ['Personnel','Registrar','Cashier','Bookkeeper','Librarian','Guidance','Nurse','Coordinator','Teacher','Adviser','Department Head'];

        $toast       = $_SESSION['toast']       ?? ''; unset($_SESSION['toast']);
        $toast_error = $_SESSION['toast_error'] ?? ''; unset($_SESSION['toast_error']);

        // Helpers
        $first_name = explode(',', $full_name)[0] ?? $full_name;
        $parts      = explode(' ', trim($full_name));
        $initials   = strtoupper(substr($parts[0], 0, 1) . (isset($parts[1]) ? substr($parts[1], 0, 1) : ''));
        ?>
        <!DOCTYPE html>
        <html lang="en">
        <head>
        <meta charset="UTF-8"/>
        <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
        <title>Staff Dashboard — PBI</title>
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
            --dark:#0A192F;--mid:#172A45;--inner:#0F1F3D;
            --accent:#16A34A;--hover:#15803D;
            --teal:#16A34A;--teal-hover:#15803D;
            --light:#E0E6F0;--muted:#A0B3C6;
            --danger:#F05454;--success:#22C55E;
            --border:rgba(255,255,255,0.08);--radius:10px;--shadow:0 4px 20px rgba(0,0,0,0.35);
            --sidebar-w:240px;
        }
        body.light-theme{
            --dark:#F3F6FB;--mid:#FFFFFF;--inner:#EEF2F8;
            --accent:#16A34A;--hover:#15803D;
            --teal:#16A34A;--teal-hover:#15803D;
            --light:#16263B;--muted:#5B7186;
            --danger:#DC2626;--success:#16A34A;
            --border:rgba(15,31,61,0.10);--shadow:0 4px 20px rgba(15,31,61,.08);
        }
        body.light-theme .sidebar-brand:hover{background:rgba(15,31,61,.04);}
        body.light-theme .profile-dd-btn:hover{background:rgba(15,31,61,.06);}
        body.light-theme .dd-appearance-val{background:rgba(15,31,61,.06);}
        body.light-theme .nav-link:hover{background:rgba(15,31,61,.05);}
        body.light-theme .notif-btn{background:rgba(15,31,61,.06);}
        body.light-theme .notif-item{border-bottom:1px solid rgba(15,31,61,.07);}
        body.light-theme .cat-bar-bg{background:rgba(15,31,61,.08);}
        body.light-theme .peer-card-btn.done{background:rgba(15,31,61,.06);}
        body.light-theme .sidebar-title,
        body.light-theme .nav-page-title,
        body.light-theme .notif-header-title,
        body.light-theme .photo-modal-title,
        body.light-theme .dashboard-intro .status-pill.closed{background:#FFF1F1;border-color:#F3B0B0;color:#B42318;}
body.light-theme .welcome-bar h2,
        body.light-theme .stat-box-val,
        body.light-theme .section-card-title,
        body.light-theme .eval-modal-title,
        body.light-theme .eval-info-value,
        body.light-theme .eval-q-text,
        body.light-theme .profile-hero-name,
        body.light-theme .role-card-title,
        body.light-theme .student-mini-name,
        body.light-theme .peer-card-name,
        body.light-theme .eval-name,
        body.light-theme .q-text{color:var(--light);}
        body.light-theme .ea-tab{background:rgba(124,58,237,.10);color:#4c1d95;border-color:rgba(124,58,237,.24);}
        body.light-theme .ea-tab .count{background:rgba(124,58,237,.14);color:#4c1d95;}
        body.light-theme .ea-toolbar{background:#FFFFFF;}
        body.light-theme .ea-search-input{background:#F3F6FB;border-color:rgba(15,31,61,.12);color:#16263B;}
        body.light-theme .ea-search-icon{color:#8892a6;}
        body.light-theme .ea-export-btn{background:rgba(124,58,237,.08);color:#5b21b6;border-color:rgba(124,58,237,.28);}
        body.light-theme .ea-table-card{background:#FFFFFF;}
        body.light-theme .ea-table th{background:#EEF2F8;color:#5B7186;}
        body.light-theme .ea-table td{color:#16263B;border-top-color:rgba(15,31,61,.08);}
        body.light-theme .ea-table tr:hover{background:rgba(124,58,237,.04);}
        body.light-theme .ea-avatar{border-color:rgba(15,31,61,.12);background:#EEF2F8;color:#5b21b6;}
        body.light-theme .ea-name{color:#16263B;}
        body.light-theme .ea-role-pill{background:rgba(124,58,237,.10);color:#5b21b6;border-color:rgba(124,58,237,.20);}
        body.light-theme .ea-status{background:rgba(100,116,139,.12);color:#475569;}
        body.light-theme .ea-status.done{background:rgba(34,197,94,.12);color:#15803d;}
        body.light-theme .ea-table-footer{color:#5B7186;border-top-color:rgba(15,31,61,.08);}
        body.light-theme .level-view-empty,
        body.light-theme .peer-select-hint.warn{color:#B45309;}
        body.light-theme .notif-btn:hover,
        body.light-theme .notif-btn.has-unread{color:#B45309;}
        body.light-theme .info-note i{color:#2563EB;}
        body.light-theme .toast-success{color:#15803D;}
        body.light-theme .toast-error{color:#B91C1C;}
        body.light-theme .level-view-pill{color:#0F766E;}
        *,*::before,*::after{box-sizing:border-box;margin:0;padding:0;}
        body{font-family:'DM Sans',sans-serif;background:var(--dark);color:var(--light);min-height:100vh;display:flex;}

        /* SIDEBAR */
        .sidebar{width:var(--sidebar-w);flex-shrink:0;background:var(--mid);border-right:1px solid var(--border);display:flex;flex-direction:column;position:fixed;top:0;left:0;height:100vh;z-index:40;transition:transform .3s;}
        .sidebar-brand{padding:16px 18px;border-bottom:1px solid var(--border);display:flex;align-items:center;gap:12px;cursor:pointer;transition:background .2s;position:relative;}
        .sidebar-brand:hover{background:rgba(255,255,255,.04);}
        .brand-avatar{width:42px;height:42px;border-radius:50%;flex-shrink:0;border:2px solid var(--teal);box-shadow:0 0 10px rgba(99,102,241,.3);overflow:hidden;background:var(--inner);display:flex;align-items:center;justify-content:center;}
        .brand-avatar img{width:100%;height:100%;object-fit:cover;display:block;}
        .brand-avatar .brand-initials{font-family:'Rajdhani',sans-serif;font-size:15px;font-weight:700;color:var(--teal-hover);line-height:1;}
        .sidebar-title{font-family:'Rajdhani',sans-serif;font-size:14px;font-weight:700;letter-spacing:.4px;color:#fff;line-height:1.3;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:118px;}
        .sidebar-sub{font-size:11px;color:var(--teal-hover);letter-spacing:.2px;margin-top:2px;font-weight:600;}
        .sidebar-caret{font-size:11px;color:var(--muted);flex-shrink:0;transition:transform .2s;}
        .sidebar-profile-dropdown{margin:0 12px;max-height:0;opacity:0;overflow:hidden;background:var(--inner);border-radius:10px;transition:max-height .2s ease,opacity .2s ease,margin .2s ease;}
        .sidebar-profile-dropdown.open{max-height:260px;opacity:1;margin:8px 12px 10px;border:1px solid var(--border);}
        .profile-dd-btn{width:100%;padding:10px 12px;border-radius:8px;border:none;background:none;color:var(--light);font-size:13px;font-family:'DM Sans',sans-serif;cursor:pointer;display:flex;align-items:center;gap:10px;transition:background .18s;text-align:left;text-decoration:none;}
        .profile-dd-btn:hover{background:rgba(255,255,255,.06);}
        .profile-dd-btn i{width:16px;text-align:center;color:var(--muted);}
        .profile-dd-btn i.dd-icon-blue{color:#3B82F6;}
        .profile-dd-btn i.dd-icon-purple{color:#8B5CF6;}
        .nav-icon-blue{color:#3B82F6;}
        .nav-icon-purple{color:#8B5CF6;}
        .nav-icon-green{color:#22C55E;}
        .nav-icon-orange{color:#F97316;}
        .profile-dd-divider{height:1px;background:var(--border);margin:6px 4px;}
        .profile-dd-btn.logout{color:#f87171;}
        .profile-dd-btn.logout i{color:#f87171;}
        .dd-appearance-val{margin-left:auto;font-size:11px;color:var(--muted);font-weight:700;background:rgba(255,255,255,.06);padding:3px 9px;border-radius:20px;flex-shrink:0;}
        .profile-dd-btn:hover .dd-appearance-val{color:var(--teal-hover);}
        .sidebar-nav{flex:1;padding:16px 10px;overflow-y:auto;}
        .nav-section-label{font-size:11px;font-weight:900;text-transform:uppercase;letter-spacing:1.5px;color:#99F6E4;padding:0 8px;margin-bottom:7px;margin-top:16px;text-align:center;text-shadow:0 1px 8px rgba(45,212,191,.14);}
        .nav-section-label:first-child{margin-top:0;}
        .nav-link{display:flex;align-items:center;gap:10px;padding:10px 12px;border-radius:8px;color:var(--muted);text-decoration:none;font-size:14px;font-weight:600;transition:all .2s;margin-bottom:2px;}
        .nav-link:hover{background:rgba(255,255,255,.05);color:var(--light);}
        .nav-link.active{background:rgba(99,102,241,.18);color:var(--teal-hover);}
        .nav-link.active i{color:var(--teal);}
        .nav-link i{font-size:15px;width:18px;text-align:center;}
        .nav-link .badge{margin-left:auto;background:var(--teal);color:#fff;font-size:10px;font-weight:700;padding:1px 7px;border-radius:20px;}
        .sidebar-footer{padding:14px 16px;border-top:1px solid var(--border);}
        .btn-logout-side{display:flex;align-items:center;gap:7px;width:100%;padding:9px 12px;border:1px solid rgba(240,84,84,.3);background:rgba(240,84,84,.08);border-radius:8px;color:#f87171;font-size:13px;font-weight:600;cursor:pointer;text-decoration:none;transition:all .2s;font-family:'DM Sans',sans-serif;}
        .btn-logout-side:hover{background:rgba(240,84,84,.18);}
        .btn-view-all-evals{
        width:100%;
        display:flex;
        align-items:center;
        gap:10px;
        background:rgba(99,102,241,.12);
        border:1px solid rgba(99,102,241,.3);
        color:var(--teal-hover);
        font-size:13.5px;
        font-weight:700;
        padding:12px 16px;
        border-radius:10px;
        cursor:pointer;
        font-family:'DM Sans',sans-serif;
        transition:background .2s;
    }
    .btn-view-all-evals:hover{background:rgba(99,102,241,.2);}

        /* TOP NAV */
        .top-nav{position:fixed;top:0;left:var(--sidebar-w);right:0;height:60px;z-index:30;background:var(--mid);border-bottom:1px solid var(--border);display:flex;align-items:center;justify-content:space-between;padding:0 28px;box-shadow:var(--shadow);}
        .nav-page-title{font-family:'Rajdhani',sans-serif;font-size:20px;font-weight:700;letter-spacing:.5px;color:#fff;}
        .nav-right{display:flex;align-items:center;gap:14px;}
        .period-badge{background:rgba(99,102,241,.15);border:1px solid rgba(99,102,241,.3);color:var(--teal-hover);padding:5px 12px;border-radius:20px;font-size:12px;font-weight:600;display:flex;align-items:center;gap:6px;}
        .hamburger{display:none;background:none;border:none;color:var(--light);font-size:20px;cursor:pointer;}

        /* NOTIFICATION BELL */
        .notif-wrap{position:relative;display:flex;align-items:center;}
        .notif-btn{width:36px;height:36px;border-radius:50%;background:rgba(255,255,255,.06);border:1px solid var(--border);display:flex;align-items:center;justify-content:center;color:var(--muted);font-size:15px;cursor:pointer;transition:all .2s;position:relative;}
        .notif-btn:hover,.notif-btn.has-unread{color:#facc15;border-color:rgba(250,204,21,.4);background:rgba(250,204,21,.08);}
        .notif-badge{position:absolute;top:-4px;right:-4px;min-width:18px;height:18px;border-radius:9px;background:#ef4444;color:#fff;font-size:10px;font-weight:700;display:flex;align-items:center;justify-content:center;padding:0 4px;border:2px solid var(--mid);}
        .notif-dropdown{position:absolute;top:calc(100% + 10px);right:0;width:320px;background:var(--mid);border:1px solid var(--border);border-radius:14px;box-shadow:0 16px 48px rgba(0,0,0,.55);opacity:0;visibility:hidden;transform:translateY(-8px);transition:all .2s ease;z-index:120;overflow:hidden;}
        .notif-dropdown.show{opacity:1;visibility:visible;transform:translateY(0);}
        .notif-header{display:flex;align-items:center;justify-content:space-between;padding:14px 16px;border-bottom:1px solid var(--border);}
        .notif-header-title{font-size:13px;font-weight:700;color:#fff;display:flex;align-items:center;gap:7px;}
        .notif-mark-read{font-size:11px;color:var(--teal-hover);cursor:pointer;font-weight:600;background:none;border:none;font-family:'DM Sans',sans-serif;padding:0;}
        .notif-list{max-height:340px;overflow-y:auto;}
        .notif-item{display:flex;align-items:flex-start;gap:10px;padding:11px 14px;border-bottom:1px solid rgba(255,255,255,.05);position:relative;}
        .notif-item:last-child{border-bottom:none;}
        .notif-item.unread{background:rgba(99,102,241,.07);}
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


        /* PROFILE PHOTO MODAL */
        .photo-modal-overlay{position:fixed;inset:0;background:rgba(0,0,0,.75);z-index:300;display:none;align-items:center;justify-content:center;padding:20px;}
        .photo-modal-overlay.open{display:flex;}
        .photo-modal{background:var(--mid);border:1px solid var(--border);border-radius:18px;width:100%;max-width:400px;box-shadow:0 20px 60px rgba(0,0,0,.6);overflow:hidden;}
        .photo-modal-header{padding:20px 24px 16px;border-bottom:1px solid var(--border);display:flex;align-items:center;justify-content:space-between;}
        .photo-modal-title{font-family:'Rajdhani',sans-serif;font-size:20px;font-weight:700;color:#fff;}
        .photo-modal-body{padding:24px;}
        .photo-upload-circle{width:110px;height:110px;border-radius:50%;border:3px dashed rgba(99,102,241,.5);display:flex;align-items:center;justify-content:center;margin:0 auto 20px;cursor:pointer;transition:border-color .2s;overflow:hidden;position:relative;background:var(--inner);}
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

        /* MAIN */
        .main{margin-left:var(--sidebar-w);margin-top:60px;padding:28px;flex:1;min-height:calc(100vh - 60px);}

        /* TOAST */
        .toast{border-radius:8px;padding:12px 18px;font-size:13px;margin-bottom:22px;display:flex;align-items:center;gap:8px;animation:fadeIn .3s ease;}
        .toast-success{background:rgba(34,197,94,.12);border:1px solid rgba(34,197,94,.3);color:#86efac;}
        .toast-error{background:rgba(240,84,84,.12);border:1px solid rgba(240,84,84,.3);color:#fca5a5;}
        @keyframes fadeIn{from{opacity:0;transform:translateY(-6px)}to{opacity:1;transform:none}}

        /* DASHBOARD */
        .evaluation-schedule-card{background:var(--mid);border:1px solid var(--border);border-radius:16px;padding:22px 26px;margin:0 0 22px;box-shadow:0 5px 18px rgba(15,35,60,.035);}
        .schedule-summary-grid{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:20px;align-items:start;}
        .schedule-summary-item{min-width:0;}
        .schedule-label{display:block;font-size:11px;font-weight:800;letter-spacing:.08em;text-transform:uppercase;color:#E19A1D;margin-bottom:7px;}
        .schedule-summary-item strong{display:block;font-size:15px;line-height:1.25;color:#fff;font-weight:700;white-space:normal;}
        .schedule-status{display:inline-flex;align-items:center;padding:6px 14px;border-radius:999px;font-size:12px;font-weight:700;border:1px solid rgba(239,68,68,.35);background:rgba(239,68,68,.10);color:#DC2626;}
        .schedule-status.open{border-color:rgba(34,197,94,.35);background:rgba(34,197,94,.12);color:#22C55E;}
        .schedule-status.closed{border-color:rgba(239,68,68,.35);background:rgba(239,68,68,.10);color:#F87171;}
        .schedule-times{margin-top:19px;padding-top:16px;border-top:1px solid var(--border);}
        .schedule-message{display:flex;align-items:center;gap:7px;flex-wrap:wrap;font-size:13px;color:var(--muted);}
        .schedule-message i{color:#E19A1D;font-size:15px;margin-right:2px;}
        .schedule-message strong{color:#fff;}
        @media(max-width:900px){.schedule-summary-grid{grid-template-columns:repeat(2,minmax(0,1fr));}}
        @media(max-width:600px){.evaluation-schedule-card{padding:18px 20px;}.schedule-summary-grid{grid-template-columns:1fr 1fr;gap:16px;}.schedule-message{display:block;line-height:1.7;}.schedule-message i{margin-right:5px;}}
        .welcome-bar{background:linear-gradient(135deg,var(--mid) 0%,rgba(99,102,241,.15) 100%);border:1px solid rgba(99,102,241,.2);border-radius:14px;padding:24px 28px;margin-bottom:24px;display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:16px;}
        .welcome-bar h2{font-family:'Rajdhani',sans-serif;font-size:24px;font-weight:700;color:#fff;margin-bottom:4px;}
        .welcome-bar p{font-size:13px;color:var(--muted);}
        .score-chip{background:rgba(99,102,241,.2);border:1px solid rgba(99,102,241,.35);border-radius:12px;padding:14px 22px;text-align:center;}
        .score-chip .big{font-size:32px;font-weight:700;color:var(--teal-hover);}
        .score-chip .lbl{font-size:11px;color:var(--muted);text-transform:uppercase;letter-spacing:.5px;}
        .stats-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(180px,1fr));gap:16px;margin-bottom:24px;}
        .stat-box{background:var(--mid);border:1px solid var(--border);border-radius:12px;padding:18px 20px;}
        .stat-box-lbl{font-size:11px;text-transform:uppercase;letter-spacing:.8px;color:var(--muted);margin-bottom:8px;}
        .stat-box-val{font-size:26px;font-weight:700;color:#fff;}
        .stat-box-val.teal{color:var(--teal-hover);}
        .stat-box-val.gold{color:#F59E0B;}
        .section-card{background:var(--mid);border:1px solid var(--border);border-radius:14px;padding:22px 24px;margin-bottom:22px;}
        .section-card-title{font-family:'Rajdhani',sans-serif;font-size:18px;font-weight:700;color:#fff;margin-bottom:16px;display:flex;align-items:center;gap:8px;}
        .cat-row{display:flex;align-items:center;gap:12px;margin-bottom:10px;}
        .cat-name{font-size:13px;color:var(--light);width:200px;flex-shrink:0;}
        .cat-bar-bg{flex:1;height:7px;background:rgba(255,255,255,.07);border-radius:4px;overflow:hidden;}
        .cat-bar-fill{height:100%;border-radius:4px;transition:width .5s ease;}
        .cat-score{font-size:13px;font-weight:700;width:36px;text-align:right;}
        .sub-item{background:var(--inner);border:1px solid var(--border);border-radius:8px;padding:12px 16px;margin-bottom:8px;display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:8px;}
        .sub-meta{font-size:12px;color:var(--muted);}
        .sub-score-badge{padding:4px 12px;border-radius:20px;font-size:13px;font-weight:700;}
        .sub-item-clickable{cursor:pointer;transition:background .15s,border-color .15s;}
        .sub-item-clickable:hover{background:rgba(99,102,241,.08);border-color:rgba(99,102,241,.35);}
        .btn-view-details{background:rgba(99,102,241,.14);border:1px solid rgba(99,102,241,.3);color:var(--teal-hover);font-size:11px;font-weight:700;padding:5px 12px;border-radius:20px;cursor:pointer;white-space:nowrap;font-family:'DM Sans',sans-serif;}
        .btn-view-details:hover{background:var(--teal);color:#fff;}
        .empty-state{text-align:center;padding:48px 20px;color:var(--muted);}
        .empty-state i{font-size:38px;opacity:.3;display:block;margin-bottom:14px;}
        .back-link{display:inline-flex;align-items:center;gap:7px;background:var(--inner);border:1px solid var(--border);color:var(--light);padding:8px 16px;border-radius:8px;font-size:13px;font-weight:600;text-decoration:none;margin-bottom:18px;transition:background .2s;}
        .back-link:hover{background:var(--accent);}

        /* ══ EVALUATION DETAILS MODAL ══ */
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
        .eval-q-card{background:var(--mid);border:1px solid var(--border);border-radius:12px;padding:14px 16px;margin-bottom:10px;}
        .eval-q-no{font-size:11px;color:var(--muted);text-transform:uppercase;letter-spacing:.8px;margin-bottom:5px;font-weight:700;}
        .eval-q-text{font-size:14px;font-weight:600;color:#fff;margin-bottom:8px;line-height:1.5;}
        .eval-cat-header{display:flex;align-items:center;gap:8px;font-size:11px;font-weight:800;text-transform:uppercase;letter-spacing:1.5px;color:var(--teal-hover);margin:18px 0 10px;padding-bottom:8px;border-bottom:1px solid rgba(99,102,241,.2);}
        .eval-cat-header:first-child{margin-top:0;}

        /* ══ MY PROFILE / ROLE ASSIGNMENT ══ */
        .profile-hero{background:linear-gradient(135deg,var(--mid) 0%,rgba(99,102,241,.18) 100%);border:1px solid rgba(99,102,241,.25);border-radius:16px;padding:28px;margin-bottom:22px;display:flex;align-items:center;justify-content:space-between;gap:22px;flex-wrap:wrap;}
        .profile-hero-left{display:flex;align-items:center;gap:22px;flex-wrap:wrap;}
        .profile-hero-avatar{width:80px;height:80px;border-radius:50%;object-fit:cover;border:3px solid var(--teal);box-shadow:0 0 18px rgba(99,102,241,.4);flex-shrink:0;display:flex;align-items:center;justify-content:center;color:var(--teal-hover);font-size:30px;background:var(--inner);}
        .profile-hero-name{font-family:'Rajdhani',sans-serif;font-size:26px;font-weight:700;color:#fff;margin-bottom:6px;}
        .profile-hero-desig{display:inline-flex;align-items:center;gap:7px;background:rgba(99,102,241,.2);border:1px solid rgba(99,102,241,.35);color:var(--teal-hover);font-size:13px;font-weight:700;padding:5px 14px;border-radius:20px;}

        /* ROLE ASSIGNMENT CARD */
        .role-card{background:var(--mid);border:2px solid rgba(99,102,241,.3);border-radius:16px;padding:26px;margin-bottom:22px;position:relative;overflow:hidden;}
        .role-card::before{content:'';position:absolute;top:0;left:0;right:0;height:3px;background:linear-gradient(90deg,var(--teal),var(--teal-hover));}
        .role-card-title{font-family:'Rajdhani',sans-serif;font-size:20px;font-weight:700;color:#fff;margin-bottom:4px;display:flex;align-items:center;gap:9px;}
        .role-card-sub{font-size:13px;color:var(--muted);margin-bottom:22px;}

        /* Quick-pick chips */
        .role-chips-label{font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:1px;color:var(--muted);margin-bottom:10px;display:flex;align-items:center;gap:6px;}
        .role-chips{display:flex;flex-wrap:wrap;gap:8px;margin-bottom:20px;}
        .role-chip{padding:8px 16px;border-radius:8px;font-size:13px;font-weight:600;cursor:pointer;border:2px solid var(--border);background:var(--inner);color:var(--muted);transition:all .22s;display:flex;align-items:center;gap:6px;}
        .role-chip:hover{border-color:var(--teal);color:var(--teal-hover);background:rgba(99,102,241,.1);}
        .role-chip.is-current{border-color:var(--teal);background:rgba(99,102,241,.18);color:var(--teal-hover);pointer-events:none;}
        .role-chip.is-current::after{content:'✓';margin-left:2px;font-size:12px;}
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
        body.light-theme .eval-group-title{color:var(--light);}
        body.light-theme .eval-group-card:hover{border-color:#15803D;box-shadow:0 8px 24px rgba(21,128,61,.08);}

        /* Text input row */
        .custom-role-label{font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:1px;color:var(--muted);margin-bottom:8px;display:flex;align-items:center;gap:6px;}
        .custom-role-row{display:flex;gap:10px;flex-wrap:wrap;}
        .custom-role-input{flex:1;min-width:220px;background:var(--inner);border:2px solid var(--border);color:var(--light);padding:13px 16px;border-radius:10px;font-size:14px;font-family:'DM Sans',sans-serif;outline:none;transition:border-color .2s,box-shadow .2s;}
        .custom-role-input:focus{border-color:var(--teal);box-shadow:0 0 0 3px rgba(99,102,241,.2);}
        .custom-role-input::placeholder{color:var(--muted);}
        .btn-save-role{background:var(--teal);color:#fff;border:none;padding:13px 28px;border-radius:10px;font-size:14px;font-weight:700;cursor:pointer;display:flex;align-items:center;gap:8px;transition:all .2s;font-family:'DM Sans',sans-serif;white-space:nowrap;}
        .btn-save-role:hover{background:var(--teal-hover);transform:translateY(-1px);}

        .info-note{background:rgba(43,108,176,.08);border:1px solid rgba(43,108,176,.2);border-radius:10px;padding:14px 18px;font-size:13px;color:var(--muted);display:flex;gap:10px;align-items:flex-start;margin-top:20px;}
        .info-note i{color:#60a5fa;flex-shrink:0;margin-top:1px;}

        /* TEACHING LEVEL DROPDOWN (header quick-access, mirrors faculty dashboard UX) */
        .level-view-wrap{flex-shrink:0;background:var(--inner);border:1px solid var(--border);border-radius:12px;padding:14px 18px;min-width:220px;max-width:320px;transition:box-shadow .3s ease,border-color .3s ease;}
        .level-view-wrap.flash{border-color:var(--teal);box-shadow:0 0 0 3px rgba(13,148,136,.25);}
        .level-view-label{font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.8px;color:var(--muted);display:flex;align-items:center;gap:7px;margin-bottom:9px;}
        .level-view-pills{display:flex;flex-wrap:wrap;gap:6px;}
        .level-view-pill{background:rgba(13,148,136,.15);color:#5eead4;border:1px solid rgba(13,148,136,.35);font-size:12px;font-weight:700;padding:4px 12px;border-radius:20px;}
        .level-view-empty{font-size:11.5px;color:#fcd34d;display:flex;align-items:center;gap:6px;line-height:1.5;}
        .level-view-hint{font-size:11px;color:var(--muted);margin-top:9px;}

        /* Toggle switch for notification preferences */
        .toggle-switch{position:relative;display:inline-block;width:42px;height:24px;flex-shrink:0;cursor:pointer;}
        .toggle-switch input{display:none;}
        .toggle-slider{position:absolute;inset:0;background:var(--border);border-radius:20px;transition:background .2s;}
        .toggle-slider::before{content:"";position:absolute;width:18px;height:18px;left:3px;top:3px;background:#fff;border-radius:50%;transition:transform .2s;}
        .toggle-switch input:checked + .toggle-slider{background:var(--teal);}
        .toggle-switch input:checked + .toggle-slider::before{transform:translateX(18px);}

        /* STUDENT MINI CARDS */
        .students-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(150px,1fr));gap:16px;}
        .student-mini-card{background:var(--mid);border:2px solid var(--border);border-radius:12px;padding:16px 12px;display:flex;flex-direction:column;align-items:center;gap:6px;text-align:center;}
        .student-mini-photo{width:60px;height:60px;border-radius:50%;object-fit:cover;border:2px solid var(--border);}
        .student-mini-photo-ph{width:60px;height:60px;border-radius:50%;background:var(--inner);border:2px solid var(--border);display:flex;align-items:center;justify-content:center;color:var(--muted);font-size:20px;}
        .student-mini-name{font-size:13px;font-weight:600;color:#fff;line-height:1.3;}
        .student-year-badge{font-size:10px;font-weight:700;color:#a5b4fc;background:rgba(99,102,241,.15);border:1px solid rgba(99,102,241,.3);border-radius:20px;padding:2px 10px;margin-top:2px;}

        /* ══ PEER EVALUATION — PHOTO CARD GRID ══ */
        .peer-card-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(190px,1fr));gap:16px;}
        .peer-card{background:var(--inner);border:1px solid var(--border);border-radius:12px;padding:20px 16px;display:flex;flex-direction:column;align-items:center;text-align:center;gap:4px;transition:border-color .2s,transform .2s;}
        .peer-card:hover{border-color:rgba(99,102,241,.4);transform:translateY(-2px);}
        .peer-card.is-done{opacity:.65;}
        .peer-card-photo{width:64px;height:64px;border-radius:50%;object-fit:cover;border:2px solid var(--border);margin-bottom:8px;}
        .peer-card-photo-ph{display:flex;align-items:center;justify-content:center;background:var(--mid);color:var(--muted);font-size:22px;}
        .peer-card-name{font-size:14px;font-weight:700;color:#fff;line-height:1.3;}
        .peer-card-desig{font-size:12px;color:var(--teal-hover);margin-bottom:12px;}
        .peer-card-btn{display:inline-flex;align-items:center;justify-content:center;gap:6px;width:100%;padding:9px 0;border-radius:8px;border:none;background:var(--teal);color:#fff;font-size:13px;font-weight:700;cursor:pointer;text-decoration:none;transition:background .2s;font-family:'DM Sans',sans-serif;}
        .peer-card-btn:hover{background:var(--teal-hover);}
        .peer-card-btn.done{background:rgba(255,255,255,.06);color:var(--muted);cursor:not-allowed;}

        /* ══ PEER EVALUATION — DROPDOWN SELECTOR ══ */
        .peer-select-card{display:flex;flex-direction:column;gap:14px;}
        .peer-select-row{display:flex;gap:10px;flex-wrap:wrap;align-items:flex-start;}
        .peer-select-input{flex:1;min-width:240px;background:var(--inner);border:2px solid var(--border);color:var(--light);padding:13px 16px;border-radius:10px;font-size:14px;font-family:'DM Sans',sans-serif;outline:none;transition:border-color .2s,box-shadow .2s;appearance:none;-webkit-appearance:none;background-image:url("data:image/svg+xml;utf8,<svg xmlns='http://www.w3.org/2000/svg' width='14' height='14' viewBox='0 0 24 24' fill='none' stroke='%23A0B3C6' stroke-width='2.5'><polyline points='6 9 12 15 18 9'/></svg>");background-repeat:no-repeat;background-position:right 14px center;padding-right:38px;}
        .peer-select-input:focus{border-color:var(--teal);box-shadow:0 0 0 3px rgba(99,102,241,.2);}
        .peer-select-input option:disabled{color:#5a6b80;}
        .peer-select-hint{font-size:12px;color:var(--muted);display:flex;align-items:center;gap:6px;}
        .peer-select-hint.warn{color:#fcd34d;}
        .btn-proceed-peer{background:var(--teal);color:#fff;border:none;padding:13px 26px;border-radius:10px;font-size:14px;font-weight:700;cursor:pointer;display:flex;align-items:center;gap:8px;transition:all .2s;font-family:'DM Sans',sans-serif;white-space:nowrap;}
        .btn-proceed-peer:hover:not(:disabled){background:var(--teal-hover);transform:translateY(-1px);}
        .btn-proceed-peer:disabled{opacity:.45;cursor:not-allowed;}

        /* PEER EVAL FORM */
        .eval-header-bar{background:var(--mid);border:1px solid var(--border);border-radius:14px;padding:20px 24px;margin-bottom:20px;display:flex;align-items:center;gap:16px;}
        .eval-photo-lg{width:60px;height:60px;border-radius:50%;object-fit:cover;border:2px solid var(--teal);}
        .eval-photo-ph{width:60px;height:60px;border-radius:50%;background:var(--inner);border:2px solid var(--teal);display:flex;align-items:center;justify-content:center;color:var(--muted);font-size:22px;}
        .eval-name{font-family:'Rajdhani',sans-serif;font-size:18px;font-weight:700;color:#fff;}
        .eval-desig{font-size:12px;color:var(--muted);}
        .scale-legend{position:sticky;top:60px;z-index:20;background:var(--mid);border:1px solid var(--border);border-radius:12px;padding:10px 14px;margin-bottom:20px;display:flex;align-items:center;gap:10px;flex-wrap:wrap;box-shadow:var(--shadow);}
        .scale-legend-item{display:flex;align-items:center;gap:7px;background:var(--inner);border:1px solid var(--border);border-radius:8px;padding:5px 10px;}
        .scale-legend-num{width:20px;height:20px;border-radius:5px;background:var(--teal);color:#fff;font-size:11px;font-weight:700;display:flex;align-items:center;justify-content:center;flex-shrink:0;}
        .scale-legend-lbl{font-size:12px;font-weight:600;color:var(--light);}
        .cat-group{margin-bottom:26px;}
        .cat-group-title{display:flex;align-items:center;gap:9px;font-family:'Rajdhani',sans-serif;font-size:15px;font-weight:700;color:var(--teal-hover);text-transform:uppercase;letter-spacing:1px;padding-bottom:8px;margin-bottom:14px;border-bottom:1px solid var(--border);}
        .q-card{background:var(--mid);border:1px solid var(--border);border-radius:12px;padding:18px 20px;margin-bottom:14px;}
        .q-no{font-size:11px;color:var(--muted);text-transform:uppercase;letter-spacing:.8px;margin-bottom:5px;}
        .q-text{font-size:14px;font-weight:600;color:#fff;margin-bottom:14px;line-height:1.5;}
        .star-group{display:flex;gap:8px;flex-wrap:wrap;flex:1;}
        .star-btn{flex:1;min-width:78px;display:flex;flex-direction:column;align-items:center;gap:3px;padding:9px 6px;border-radius:8px;border:2px solid var(--border);background:var(--inner);color:var(--muted);cursor:pointer;transition:all .18s;font-family:'DM Sans',sans-serif;}
        .star-btn .sb-num{font-size:14px;font-weight:700;}
        .star-btn .sb-txt{font-size:10px;font-weight:600;letter-spacing:.3px;text-transform:uppercase;}
        .star-btn:hover,.star-btn.sel,.star-btn.selected{background:var(--teal);border-color:var(--teal);color:#fff;transform:translateY(-1px);}
        .comments-card{background:var(--mid);border:1px solid var(--border);border-radius:12px;padding:18px 20px;margin-bottom:18px;}
        .comments-box{width:100%;min-height:90px;resize:vertical;background:var(--inner);border:1px solid var(--border);border-radius:8px;color:var(--light);font-family:'DM Sans',sans-serif;font-size:13px;padding:12px 14px;outline:none;transition:border-color .2s;}
        .comments-box:focus{border-color:var(--teal);}
        .comments-box::placeholder{color:var(--muted);}
        .submit-row{background:var(--mid);border:1px solid var(--border);border-radius:12px;padding:18px 22px;display:flex;justify-content:space-between;align-items:center;gap:14px;flex-wrap:wrap;}
        .btn-submit{background:var(--teal);color:#fff;border:none;padding:12px 30px;border-radius:var(--radius);font-size:14px;font-weight:700;cursor:pointer;transition:all .2s;display:flex;align-items:center;gap:8px;font-family:'DM Sans',sans-serif;}
        .btn-submit:hover{background:var(--teal-hover);transform:translateY(-1px);}

        /* ══ STAFF EVALUATION ══ */
        .ea-eval-wrap{width:100%;max-width:1180px;}
        .ea-tab-row{display:flex;align-items:stretch;gap:10px;margin-bottom:18px;flex-wrap:wrap;}
        .ea-tab{display:flex;align-items:center;gap:9px;min-width:190px;padding:12px 16px;border:1px solid rgba(99,102,241,.25);border-radius:12px;background:rgba(99,102,241,.08);color:var(--muted);text-decoration:none;font-size:13px;font-weight:700;transition:all .2s ease;}
        .ea-tab i{font-size:15px;color:var(--teal-hover);}
        .ea-tab:hover{background:rgba(99,102,241,.14);border-color:rgba(129,140,248,.45);color:var(--light);transform:translateY(-1px);}
        .ea-tab.active{background:linear-gradient(135deg,rgba(99,102,241,.24),rgba(99,102,241,.12));border-color:var(--teal);box-shadow:0 8px 24px rgba(0,0,0,.18);color:#fff;}
        .ea-tab.active i{color:#fff;}
        .ea-tab .count{margin-left:auto;min-width:24px;height:24px;padding:0 7px;border-radius:999px;display:inline-flex;align-items:center;justify-content:center;background:rgba(255,255,255,.08);color:var(--light);font-size:11px;font-weight:800;}
        .ea-tab.active .count{background:rgba(255,255,255,.16);color:#fff;}
        .ea-toolbar{display:flex;align-items:center;justify-content:space-between;gap:18px;background:var(--mid);border:1px solid var(--border);border-radius:14px;padding:18px 20px;margin-bottom:18px;box-shadow:var(--shadow);}
        .ea-toolbar > div:first-child{min-width:0;}
        .ea-status{display:inline-flex;align-items:center;gap:7px;white-space:nowrap;padding:8px 12px;border-radius:999px;background:rgba(250,204,21,.10);border:1px solid rgba(250,204,21,.18);color:#facc15;font-size:12px;font-weight:700;}
        .ea-status.done{background:rgba(34,197,94,.10);border-color:rgba(34,197,94,.2);color:#86efac;}
        .ea-table-card{background:var(--mid);border:1px solid var(--border);border-radius:14px;overflow:hidden;box-shadow:var(--shadow);}
        .ea-table{width:100%;border-collapse:collapse;}
        .ea-table th{padding:13px 16px;background:var(--inner);color:var(--muted);text-align:left;font-size:10px;text-transform:uppercase;letter-spacing:.9px;font-weight:800;}
        .ea-table td{padding:16px;border-top:1px solid var(--border);color:var(--light);font-size:13px;vertical-align:middle;}
        .ea-table tbody tr{transition:background .18s ease;}
        .ea-table tbody tr:hover{background:rgba(99,102,241,.05);}
        .ea-profile{display:flex;align-items:center;gap:12px;min-width:220px;}
        .ea-avatar{width:42px;height:42px;flex:0 0 42px;border-radius:50%;display:flex;align-items:center;justify-content:center;overflow:hidden;background:var(--inner);border:1px solid rgba(129,140,248,.26);color:var(--teal-hover);}
        img.ea-avatar{object-fit:cover;display:block;}
        .ea-name{font-size:14px;font-weight:700;color:#fff;line-height:1.25;}
        .ea-sub{font-size:11px;color:var(--muted);margin-top:3px;}
        .ea-role-pill{display:inline-flex;align-items:center;padding:6px 10px;border-radius:999px;background:rgba(124,58,237,.12);border:1px solid rgba(124,58,237,.22);color:#c4b5fd;font-size:11px;font-weight:800;}
        .ea-evaluate-btn{display:inline-flex;align-items:center;justify-content:center;gap:7px;padding:8px 13px;border-radius:8px;background:var(--teal);border:1px solid var(--teal);color:#fff;text-decoration:none;font-size:12px;font-weight:700;white-space:nowrap;transition:all .2s;}
        .ea-evaluate-btn:hover{background:var(--teal-hover);border-color:var(--teal-hover);transform:translateY(-1px);}
        .ea-evaluate-btn.done{background:rgba(34,197,94,.10);border-color:rgba(34,197,94,.2);color:#86efac;cursor:default;}
        .ea-evaluate-btn.done:hover{transform:none;}
        body.light-theme .ea-tab{background:rgba(124,58,237,.06);color:#5b21b6;border-color:rgba(124,58,237,.20);}
        body.light-theme .ea-tab.active{background:rgba(124,58,237,.12);border-color:#7c3aed;color:#4c1d95;}
        body.light-theme .ea-tab.active i{color:#5b21b6;}
        @media(max-width:760px){
            .ea-toolbar{align-items:flex-start;flex-direction:column;}
            .ea-tab{flex:1 1 220px;min-width:0;}
            .ea-table-card{overflow-x:auto;}
            .ea-table{min-width:760px;}
        }


        /* RESPONSIVE */
        @media(max-width:900px){.sidebar{transform:translateX(-100%);}.sidebar.open{transform:translateX(0);}.top-nav{left:0;}.main{margin-left:0;}.hamburger{display:block;}}
        @media(max-width:600px){.main{padding:16px;}.stats-grid{grid-template-columns:1fr 1fr;}.students-grid{grid-template-columns:repeat(auto-fill,minmax(130px,1fr));}.scale-legend{top:0;}.star-btn{min-width:60px;}.custom-role-row{flex-direction:column;}.role-chips{gap:6px;}.eval-group-grid{grid-template-columns:1fr;}.peer-select-row{flex-direction:column;}.btn-proceed-peer{width:100%;justify-content:center;}}
        
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

        .settings-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:16px;margin-top:18px;}
        .settings-card{background:var(--inner);border:1px solid var(--border);border-radius:14px;padding:18px;}
        .settings-card.full{grid-column:1/-1;}
        .settings-card-head{display:flex;align-items:center;gap:11px;margin-bottom:14px;}
        .settings-card-head .sicon{width:38px;height:38px;border-radius:10px;display:flex;align-items:center;justify-content:center;background:rgba(99,102,241,.12);color:var(--teal-hover);}
        .settings-card-head h3{margin:0;color:var(--light);font-size:15px;}
        .settings-card-head p{margin:3px 0 0;color:var(--muted);font-size:11.5px;}
        .setting-row{display:flex;align-items:center;justify-content:space-between;gap:18px;padding:12px 0;border-top:1px solid var(--border);}
        .setting-row:first-of-type{border-top:0;} .setting-row strong{display:block;color:var(--light);font-size:13px;}
        .setting-row span{display:block;color:var(--muted);font-size:11px;margin-top:3px;line-height:1.45;}
        .setting-toggle{position:relative;width:44px;height:24px;flex:0 0 44px;} .setting-toggle input{display:none;}
        .setting-toggle .slider{position:absolute;inset:0;border-radius:20px;background:var(--border);cursor:pointer;transition:.2s;}
        .setting-toggle .slider:before{content:'';position:absolute;width:18px;height:18px;left:3px;top:3px;background:#fff;border-radius:50%;transition:.2s;}
        .setting-toggle input:checked+.slider{background:var(--teal);} .setting-toggle input:checked+.slider:before{transform:translateX(20px);}
        .settings-actions{display:flex;justify-content:flex-end;margin-top:16px;} .settings-save{border:0;border-radius:9px;background:var(--teal);color:#fff;font-weight:700;padding:10px 16px;cursor:pointer;text-decoration:none;}
        .settings-save:hover{background:var(--teal-hover);} .account-facts{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:10px;}
        .account-fact{background:var(--mid);border:1px solid var(--border);border-radius:10px;padding:12px;}
        .account-fact label{display:block;font-size:10px;text-transform:uppercase;letter-spacing:.7px;color:var(--muted);margin-bottom:4px;} .account-fact b{color:var(--light);font-size:12.5px;}
        @media(max-width:760px){.settings-grid{grid-template-columns:1fr}.settings-card.full{grid-column:auto}.account-facts{grid-template-columns:1fr}}

/* Shared compact questionnaire tables (Staff + Peer evaluations) */
.compact-eval-table-wrap{background:var(--mid);border:1px solid var(--border);border-radius:12px;overflow:hidden;margin-bottom:16px;}
.compact-eval-table{width:100%;border-collapse:collapse;table-layout:fixed;}
.compact-eval-table th{background:rgba(255,255,255,.035);color:var(--muted);font-size:10px;font-weight:800;letter-spacing:.7px;text-transform:uppercase;text-align:center;padding:11px 7px;border-bottom:1px solid var(--border)}
.compact-eval-table th:first-child{text-align:left;width:auto;padding-left:15px}.compact-eval-table th:not(:first-child){width:60px}
.compact-eval-table td{padding:10px 7px;border-bottom:1px solid rgba(255,255,255,.06);vertical-align:middle;text-align:center}.compact-eval-table tr:last-child td{border-bottom:none}.compact-eval-table td:first-child{text-align:left;padding-left:15px;padding-right:12px}
.compact-eval-qtext{font-size:13px;color:var(--light);line-height:1.45}.compact-eval-qno{color:var(--teal-hover);font-weight:800;margin-right:7px}.compact-eval-rating{display:flex;justify-content:center}.compact-eval-rating input{position:absolute;opacity:0;pointer-events:none}.compact-eval-rating label{width:38px;height:32px;display:flex;align-items:center;justify-content:center;padding:0;border-radius:7px;border:1px solid var(--border);background:var(--inner);color:var(--muted);font-size:13px;font-weight:800;cursor:pointer;transition:.15s ease;user-select:none}.compact-eval-rating label:hover{border-color:var(--teal);background:rgba(20,184,166,.08);color:var(--light)}.compact-eval-rating input:checked+label{background:var(--teal);border-color:var(--teal);color:#fff;box-shadow:0 0 0 2px rgba(20,184,166,.12)}
@media(max-width:760px){.compact-eval-table th:not(:first-child){width:48px}.compact-eval-rating label{width:32px;height:30px}.compact-eval-qtext{font-size:12px}}



/* ── Light theme refinement: white surface + coordinated text/tab/icon states ── */
body.light-theme .nav-section-label{
    color:#4F46E5;
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
    background:rgba(99,102,241,.11);
    color:#4F46E5;
    font-weight:700;
}
body.light-theme .nav-link.active i{
    color:#4F46E5;
}
body.light-theme .nav-link .badge{
    background:#6366F1;
    color:#FFFFFF;
}
body.light-theme .period-badge{
    background:rgba(99,102,241,.09);
    border-color:rgba(99,102,241,.22);
    color:#4F46E5;
}
body.light-theme .sidebar-sub{
    color:#4F46E5;
}
body.light-theme .brand-avatar{
    border-color:#6366F1;
    box-shadow:0 0 10px rgba(99,102,241,.16);
    background:#EEF2F8;
}
body.light-theme .brand-avatar .brand-initials{
    color:#4F46E5;
}
body.light-theme .profile-dd-btn{
    color:#16263B;
}
body.light-theme .profile-dd-btn:hover{
    background:rgba(15,31,61,.055);
}
body.light-theme .dd-appearance-val{
    color:#52677A;
    background:#EEF2F8;
}
body.light-theme .ea-tab{
    background:rgba(99,102,241,.06);
    color:#4338CA;
    border-color:rgba(99,102,241,.20);
}
body.light-theme .ea-tab i{
    color:#4F46E5;
}
body.light-theme .ea-tab:hover{
    background:rgba(99,102,241,.10);
    border-color:rgba(99,102,241,.30);
    color:#3730A3;
}
body.light-theme .ea-tab.active{
    background:rgba(99,102,241,.12);
    border-color:#6366F1;
    color:#3730A3;
    box-shadow:0 6px 20px rgba(15,31,61,.08);
}
body.light-theme .ea-tab.active i{
    color:#4F46E5;
}
body.light-theme .ea-tab .count{
    background:rgba(99,102,241,.10);
    color:#4338CA;
}
body.light-theme .ea-tab.active .count{
    background:rgba(99,102,241,.16);
    color:#3730A3;
}
body.light-theme .peer-card,
body.light-theme .role-card,
body.light-theme .section-card,
body.light-theme .stat-card,

/* Required-rating feedback */
.eval-validation-alert{display:none;margin:0 0 12px;padding:11px 14px;border-radius:9px;border:1px solid rgba(240,84,84,.34);background:rgba(240,84,84,.10);color:#fca5a5;font-size:13px;line-height:1.5;}
.eval-validation-alert.show{display:block;}
.eval-validation-alert strong{color:#fff;}
.eval-question-row.eval-unanswered{background:rgba(240,84,84,.06);}
.eval-question-row.eval-unanswered td:first-child{border-left:3px solid #f87171;}
.eval-progress{display:inline-flex;align-items:center;padding:6px 10px;margin-right:auto;border-radius:7px;background:rgba(255,255,255,.05);color:var(--muted);font-size:12px;font-weight:600;}
.eval-progress.is-complete{color:#86efac;background:rgba(34,197,94,.10);}
body.light-theme .eval-modal,
body.light-theme .eval-item,
body.light-theme .ea-table-card,
body.light-theme .compact-eval-table-wrap{
    box-shadow:0 6px 22px rgba(15,31,61,.055);
}
body.light-theme .peer-card:hover{
    border-color:rgba(99,102,241,.24);
}
body.light-theme .peer-card.is-done{
    background:#F8FAFD;
}
body.light-theme .peer-select-input{
    background-color:#FFFFFF;
    color:#16263B;
}
body.light-theme .peer-select-input::placeholder{
    color:#7A8B9B;
}
body.light-theme .compact-eval-table th{
    background:#EEF2F8;
    color:#5B7186;
}
body.light-theme .compact-eval-table td{
    border-bottom-color:rgba(15,31,61,.07);
}
body.light-theme .btn-save-photo,
body.light-theme .btn-proceed-peer{
    color:#FFFFFF;
}

    
/* Core light surfaces — Staff */
body.light-theme,
body.light-theme .main{background:#F3F6FB;color:#16263B;}
body.light-theme .sidebar,
body.light-theme .top-nav{background:#FFFFFF;}
body.light-theme .nav-page-title,
body.light-theme .sidebar-title,
body.light-theme .welcome-bar h2,
body.light-theme .section-title,
body.light-theme .stat-box .stat-value,
body.light-theme .peer-name,
body.light-theme .eval-name{color:#16263B;}

/* ── Final Staff white + green role theme ────────────────────────────── */
body.light-theme{
    --accent:#16A34A;
    --hover:#15803D;
    --teal:#16A34A;
    --teal-hover:#15803D;
    --dark:#F3F6FB;
    --mid:#FFFFFF;
    --inner:#F8FAFD;
    --light:#16263B;
    --muted:#52677A;
    --border:rgba(15,31,61,.10);
    --shadow:0 6px 22px rgba(15,31,61,.055);
}
body.light-theme .nav-section-label{color:#15803D;text-shadow:none;}
body.light-theme .nav-link{color:#52677A;}
body.light-theme .nav-link:hover{background:rgba(22,163,74,.055);color:#16263B;}
body.light-theme .nav-link.active{background:rgba(22,163,74,.10);color:#15803D;border-left:3px solid #16A34A;padding-left:9px;}
body.light-theme .nav-link.active i{color:#16A34A;}
body.light-theme .nav-link .badge{background:#16A34A;color:#fff;}
body.light-theme .period-badge{background:rgba(22,163,74,.08);border-color:rgba(22,163,74,.20);color:#15803D;}
body.light-theme .sidebar-sub{color:#15803D;}
body.light-theme .brand-avatar{border-color:#16A34A;box-shadow:0 0 10px rgba(22,163,74,.16);background:#EEF2F8;}
body.light-theme .brand-avatar .brand-initials{color:#15803D;}
body.light-theme .welcome-bar{background:linear-gradient(135deg,#FFFFFF 0%,rgba(22,163,74,.055) 100%);border-color:rgba(22,163,74,.16);}
body.light-theme .welcome-bar h2{color:#16263B;}
body.light-theme .welcome-bar strong{color:#16A34A;}
body.light-theme .score-chip{background:rgba(22,163,74,.07);border-color:rgba(22,163,74,.18);}
body.light-theme .score-chip .sc-val{color:#15803D;}
body.light-theme .section-card-title i,
body.light-theme .section-title i{color:#16A34A !important;}
body.light-theme .ea-tab{background:rgba(22,163,74,.06);color:#166534;border-color:rgba(22,163,74,.18);}
body.light-theme .ea-tab i{color:#15803D;}
body.light-theme .ea-tab:hover{background:rgba(22,163,74,.10);border-color:rgba(22,163,74,.28);color:#166534;}
body.light-theme .ea-tab.active{background:rgba(22,163,74,.12);border-color:#16A34A;color:#166534;box-shadow:0 6px 20px rgba(15,31,61,.08);}
body.light-theme .ea-tab.active i{color:#15803D;}
body.light-theme .ea-tab .count{background:rgba(22,163,74,.10);color:#166534;}
body.light-theme .ea-tab.active .count{background:rgba(22,163,74,.16);color:#166534;}
body.light-theme .ea-export-btn{background:rgba(22,163,74,.07);color:#166534;border-color:rgba(22,163,74,.22);}
body.light-theme .ea-table tr:hover{background:rgba(22,163,74,.04);}
body.light-theme .ea-avatar{color:#166534;}
body.light-theme .ea-role-pill{background:rgba(22,163,74,.08);color:#166534;border-color:rgba(22,163,74,.18);}
body.light-theme .peer-card:hover{border-color:rgba(22,163,74,.24);box-shadow:0 8px 24px rgba(22,163,74,.08);}
body.light-theme .peer-card-btn,
body.light-theme .btn-save-photo,
body.light-theme .btn-proceed-peer,
body.light-theme .btn-submit{background:#16A34A;}
body.light-theme .compact-eval-qno{color:#15803D;}
body.light-theme .compact-eval-rating label:hover{border-color:#16A34A;background:rgba(22,163,74,.07);color:#16263B;}
body.light-theme .compact-eval-rating input:checked+label{background:#16A34A;border-color:#16A34A;box-shadow:0 0 0 2px rgba(22,163,74,.12);}
body.light-theme .profile-hero{background:linear-gradient(135deg,#FFFFFF 0%,rgba(22,163,74,.06) 100%);border-color:rgba(22,163,74,.18);}
body.light-theme .profile-hero-avatar{border-color:#16A34A;box-shadow:0 0 18px rgba(22,163,74,.20);color:#15803D;}
body.light-theme .profile-desig-badge{background:rgba(22,163,74,.08);border-color:rgba(22,163,74,.20);color:#166534;}
body.light-theme .suggestion-chip:hover{background:rgba(22,163,74,.08);border-color:rgba(22,163,74,.24);color:#166534;}
body.light-theme .suggestion-chip.is-current{background:rgba(22,163,74,.11);border-color:#16A34A;color:#166534;}
body.light-theme .profile-dd-btn:hover .profile-dd-icon{background:rgba(22,163,74,.12);color:#15803D;}
body.light-theme .notif-item.unread{background:rgba(22,163,74,.05);}
body.light-theme .settings-card-head .sicon{background:rgba(22,163,74,.09);color:#15803D;}

        
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
    color:#4ADE80 !important;
}
body.light-theme .sidebar-caret{
    color:#A0B3C6 !important;
}
body.light-theme .brand-avatar{
    border-color:#16A34A !important;
    background:#0F1F3D !important;
    box-shadow:0 0 12px rgba(22,163,74,.28) !important;
}
body.light-theme .brand-avatar .brand-initials{
    color:#4ADE80 !important;
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
    color:#86EFAC !important;
    text-shadow:0 1px 8px rgba(22,163,74,.12) !important;
}
body.light-theme .nav-link{
    color:#A0B3C6 !important;
}
body.light-theme .nav-link:hover{
    background:rgba(255,255,255,.06) !important;
    color:#FFFFFF !important;
}
body.light-theme .nav-link.active{
    background:rgba(22,163,74,.18) !important;
    color:#FFFFFF !important;
    border-left:3px solid #16A34A !important;
}
body.light-theme .nav-link.active i{
    color:#22C55E !important;
}
body.light-theme .nav-link .badge{
    background:#16A34A !important;
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
   REFERENCE-STYLE LIGHT DASHBOARD — STAFF
   Dark role sidebar + airy white workspace + green staff accent.
   ================================================================ */
body.light-theme{
    --light:#13263F;
    --muted:#536A82;
    --dark:#F4F7FB;
    --mid:#FFFFFF;
    --inner:#F7F9FC;
    --border:#DFE6EE;
    --accent:#16A34A;
    --hover:#15803D;
    --teal:#16A34A;
    --teal-hover:#15803D;
    --teal-light:rgba(22,163,74,.10);
    --shadow:0 8px 26px rgba(18,43,73,.06);
}
body.light-theme .sidebar{
    background:#081B2E !important;border-right:1px solid rgba(255,255,255,.08) !important;
}
body.light-theme .sidebar-brand,
body.light-theme .sidebar-footer{background:#081B2E !important;}
body.light-theme .sidebar-brand{border-bottom-color:rgba(255,255,255,.08) !important;}
body.light-theme .sidebar-title{color:#fff !important;}
body.light-theme .sidebar-sub{color:#4ADE80 !important;}
body.light-theme .nav-section-label{color:#86EFAC !important;}
body.light-theme .nav-link{color:#B6C5D6 !important;}
body.light-theme .nav-link:hover{background:rgba(255,255,255,.06) !important;color:#fff !important;}
body.light-theme .nav-link.active{background:rgba(22,163,74,.16) !important;color:#fff !important;border-left:3px solid #16A34A !important;}
body.light-theme .nav-link.active i{color:#4ADE80 !important;}
body.light-theme .sidebar-caret{color:#AFC0D2 !important;}
body.light-theme .brand-avatar{border-color:#16A34A !important;box-shadow:0 0 14px rgba(22,163,74,.25) !important;}
body.light-theme .sidebar-profile-dropdown{background:#0E2944 !important;border-color:rgba(255,255,255,.08) !important;}
body.light-theme .profile-dd-btn{color:#E6EDF5 !important;}
body.light-theme .profile-dd-btn:hover{background:rgba(255,255,255,.06) !important;}
body.light-theme .dd-appearance-val{background:rgba(255,255,255,.08) !important;color:#B6C5D6 !important;}
body.light-theme .sidebar-footer{border-top-color:rgba(255,255,255,.08) !important;}
body.light-theme .btn-logout-side{background:rgba(248,113,113,.08) !important;color:#FCA5A5 !important;border-color:rgba(248,113,113,.28) !important;}
body.light-theme .btn-logout-side:hover{background:rgba(248,113,113,.15) !important;}
body.light-theme .top-nav{background:#FFFFFF !important;border-bottom-color:#DEE6EF !important;box-shadow:0 2px 14px rgba(15,34,55,.04) !important;}
body.light-theme .nav-page-title{color:#13263F !important;}
body.light-theme .period-badge{background:rgba(22,163,74,.07) !important;border-color:rgba(22,163,74,.20) !important;color:#15803D !important;}
body.light-theme .main{background:#F4F7FB !important;color:#13263F !important;padding:24px 28px 42px !important;}

body.light-theme .dashboard-shell{
    background:linear-gradient(180deg,#FFFFFF 0%,#F8FAFD 100%);
    border:1px solid #E1E8F0;border-radius:22px;padding:22px 28px 180px;
    box-shadow:0 10px 28px rgba(19,38,63,.045);min-height:calc(100vh - 114px);
}
body.light-theme .evaluation-schedule-card{background:#FFFFFF;border-color:rgba(30,82,144,.13);box-shadow:0 5px 18px rgba(30,82,144,.04);}
body.light-theme .schedule-summary-item strong{color:#10243E;}
body.light-theme .schedule-message strong{color:#10243E;}
body.light-theme .dashboard-intro{margin:2px 2px 18px;}
body.light-theme .dashboard-kicker{color:#D97706;font-size:11px;font-weight:800;letter-spacing:1.2px;text-transform:uppercase;display:flex;align-items:center;gap:7px;margin-bottom:5px;}
body.light-theme .dashboard-intro h1{margin:0;font-family:'Rajdhani',sans-serif;font-size:28px;line-height:1.05;font-weight:800;letter-spacing:.2px;color:#10243E;}
body.light-theme .dashboard-intro p{margin-top:7px;font-size:13px;color:#5C7187;}
body.light-theme .dashboard-intro .status-pill{display:inline-flex;align-items:center;gap:7px;margin-top:14px;padding:6px 12px;border-radius:20px;background:#E8FFF1;border:1px solid #91E7B2;color:#168447;font-size:11.5px;font-weight:700;}
body.light-theme .welcome-bar{background:linear-gradient(135deg,#FFFFFF 0%,#F4FBF6 100%) !important;border:1px solid rgba(22,163,74,.20) !important;border-radius:17px !important;padding:24px 28px !important;margin-bottom:20px !important;box-shadow:0 5px 18px rgba(22,163,74,.05);}
body.light-theme .welcome-bar h2{color:#11263F !important;font-size:23px !important;}
body.light-theme .welcome-bar p{color:#5E7388 !important;}
body.light-theme .welcome-bar p strong{color:#15803D !important;}
body.light-theme .score-chip{background:#EDF9F1 !important;border:1px solid #BFE6CA !important;border-radius:15px !important;max-width:340px;min-width:230px;padding:16px 26px !important;}
body.light-theme .score-chip .big{color:#15803D !important;font-size:39px !important;}
body.light-theme .score-chip .lbl{color:#66798D !important;}
body.light-theme .stats-grid{gap:14px !important;margin-bottom:20px !important;}
body.light-theme .stat-box{position:relative;background:#FFFFFF !important;border:1px solid #E0E7EF !important;border-radius:15px !important;padding:18px 20px 20px !important;box-shadow:0 6px 18px rgba(20,42,67,.045) !important;overflow:hidden;}
body.light-theme .stat-box::before{content:'';position:absolute;left:0;right:0;top:0;height:3px;background:#16A34A;}
body.light-theme .stat-box:nth-child(2)::before{background:#10B981;}
body.light-theme .stat-box:nth-child(3)::before{background:#F59E0B;}
body.light-theme .stat-box:nth-child(4)::before{background:#3B82F6;}
body.light-theme .stat-box-icon{width:34px;height:34px;border-radius:10px;display:flex;align-items:center;justify-content:center;margin-bottom:12px;font-size:15px;}
body.light-theme .stat-box-icon.green{background:#E5F9ED;color:#16A34A;}
body.light-theme .stat-box-icon.mint{background:#E7FAF0;color:#10B981;}
body.light-theme .stat-box-icon.gold{background:#FFF4D8;color:#D88900;}
body.light-theme .stat-box-icon.sky{background:#E5F0FF;color:#3478E5;}
body.light-theme .stat-box-lbl{color:#5E7388 !important;font-size:10.5px !important;letter-spacing:.9px !important;}
body.light-theme .stat-box-val{color:#12263F !important;font-size:28px !important;}
body.light-theme .stat-box-val.teal{color:#15803D !important;}
body.light-theme .stat-box-val.gold{color:#D88900 !important;}
body.light-theme .section-card{background:#FFFFFF !important;border:1px solid #E1E8F0 !important;border-radius:16px !important;padding:20px 22px !important;margin-bottom:18px !important;box-shadow:0 5px 18px rgba(20,42,67,.04) !important;}
body.light-theme .section-card-title{color:#13263F !important;font-size:17px !important;}
body.light-theme .section-card-title i{color:#16A34A !important;}
body.light-theme .btn-view-all-evals{background:#EDF9F1 !important;border-color:#C6E8D0 !important;color:#15803D !important;border-radius:10px !important;padding:12px 15px !important;}
body.light-theme .btn-view-all-evals:hover{background:#E5F6EA !important;}
body.light-theme .sub-item{background:#F8FAFD !important;border:1px solid #E1E8F0 !important;}
body.light-theme .sub-meta{color:#6E8093 !important;}
body.light-theme .btn-view-details{background:#EDF9F1 !important;border-color:#C6E8D0 !important;color:#15803D !important;}
body.light-theme .btn-view-details:hover{background:#E2F5E8 !important;}
body.light-theme .profile-hero{background:linear-gradient(135deg,#FFFFFF 0%,#F1FBF4 100%) !important;border-color:#C8E8D0 !important;}
body.light-theme .profile-hero-name{color:#11263F !important;}
body.light-theme .profile-hero-desig{background:#EDFAF1 !important;border-color:#C8E8D0 !important;color:#15803D !important;}
body.light-theme .role-card{background:#FFFFFF !important;border-color:#CDE6D3 !important;}
body.light-theme .role-card-title{color:#13263F !important;}
body.light-theme .role-card::before{background:linear-gradient(90deg,#16A34A,#4ADE80) !important;}
body.light-theme .role-chip{background:#F7F9FC !important;border-color:#DDE5EE !important;color:#51677F !important;}
body.light-theme .role-chip:hover,.role-chip.is-current{background:#EDFAF1 !important;border-color:#AFE0BC !important;color:#15803D !important;}
body.light-theme .level-view-wrap{background:#F8FAFD !important;border-color:#DDE6EF !important;}
body.light-theme .level-view-pill{background:#E9F8EE !important;color:#15803D !important;border-color:#C5E7CE !important;}
@media(max-width:900px){body.light-theme .dashboard-shell{padding:18px 16px 130px;border-radius:16px;}body.light-theme .main{padding:18px 14px 28px !important;}}

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

/* Unified Student-style sidebar structure for Staff; existing role accent retained. */
:root { --sidebar-w:248px; --role-accent:#16A34A; --role-accent-light:#4ADE80; }
.sidebar { width:var(--sidebar-w) !important; background:#0A192F !important; border-right:1px solid #172A45 !important; box-shadow:6px 0 20px rgba(0,0,0,.16); }
.sidebar-brand.portal-brand { min-height:68px; padding:14px 17px !important; border-bottom:1px solid rgba(255,255,255,.08) !important; display:flex; align-items:center; gap:11px; cursor:default; }
.portal-brand-logo { width:38px; height:38px; flex:0 0 38px; border-radius:11px; display:flex; align-items:center; justify-content:center; overflow:hidden; background:#0F1F3D; border:1px solid #16A34A; box-shadow:0 0 14px rgba(22,163,74,.18); }
.portal-brand-logo img { width:100%; height:100%; object-fit:cover; display:block; }
.portal-brand-copy { min-width:0; display:flex; flex-direction:column; line-height:1.2; }
.portal-brand-copy strong { font-family:'Rajdhani',sans-serif; font-size:15px; font-weight:700; color:#F8FAFC; letter-spacing:.25px; }
.portal-brand-copy span { margin-top:3px; font-size:10px; color:#8FA6BF; font-weight:600; letter-spacing:.15px; white-space:nowrap; }
.portal-sidebar-profile { padding:18px 16px 17px; text-align:center; border-bottom:1px solid rgba(255,255,255,.08); }
.portal-profile-avatar-wrap { margin:0 auto 11px; display:flex; justify-content:center; }
.portal-profile-avatar { width:64px; height:64px; border-radius:50%; object-fit:cover; border:2px solid #16A34A; background:#0F1F3D; box-shadow:0 0 15px rgba(22,163,74,.18); display:flex; align-items:center; justify-content:center; }
.portal-profile-fallback { color:#4ADE80; font-family:'Rajdhani',sans-serif; font-size:17px; font-weight:700; }
.portal-profile-name { color:#F8FAFC; font-size:13px; line-height:1.35; font-weight:700; word-break:break-word; }
.portal-profile-role { margin-top:3px; color:#8FA6BF; font-size:10px; text-transform:uppercase; letter-spacing:.7px; font-weight:700; line-height:1.35; }
.portal-sidebar-nav { flex:1; padding:16px 10px !important; overflow-y:auto; }
.portal-sidebar-nav .nav-section-label { padding:0 8px !important; margin:0 0 7px !important; font-size:9.5px !important; font-weight:800 !important; letter-spacing:1.25px !important; text-align:left !important; color:#8FA6BF !important; text-shadow:none !important; }
.portal-sidebar-nav .nav-section-label.sidebar-section-secondary { margin-top:17px !important; }
.portal-sidebar-nav .nav-link { min-height:40px; margin:2px 2px !important; padding:9px 11px !important; border-left:0 !important; border-radius:8px !important; gap:10px; color:#CBD8E8 !important; font-size:13px !important; font-weight:500 !important; line-height:1.2; }
.portal-sidebar-nav .nav-link i { width:18px; font-size:14px; color:#8FA6BF !important; text-align:center; flex:0 0 18px; }
.portal-sidebar-nav .nav-link:hover { background:rgba(255,255,255,.07) !important; color:#FFFFFF !important; }
.portal-sidebar-nav .nav-link:hover i { color:#4ADE80 !important; }
.portal-sidebar-nav .nav-link.active { background:linear-gradient(90deg,rgba(22,163,74,.18),rgba(255,255,255,.025)) !important; color:#FFFFFF !important; font-weight:700 !important; box-shadow:inset 3px 0 0 #4ADE80; }
.portal-sidebar-nav .nav-link.active i { color:#4ADE80 !important; }
.portal-sidebar-nav .side-nav-badge, .portal-sidebar-nav .nav-badge { margin-left:auto; background:rgba(22,163,74,.22) !important; color:#BBF7D0 !important; border-radius:20px; padding:2px 7px; font-size:9px; font-weight:800; }
.sidebar-footer { padding:13px 14px 15px !important; border-top:1px solid rgba(255,255,255,.08) !important; }
.btn-logout-side { padding:9px 11px !important; border-radius:8px !important; }
@media(max-width:900px) { .sidebar { width:248px !important; transform:translateX(-100%); transition:transform .22s ease; } .sidebar.open { transform:translateX(0); box-shadow:12px 0 34px rgba(0,0,0,.45); } .top-nav { left:0 !important; } .main { margin-left:0 !important; } }
@media(max-width:520px) { .portal-sidebar-nav { padding-left:9px !important; padding-right:9px !important; } }

</style>
        </head>
        <body class="light-theme">

        <!-- SIDEBAR -->
                <aside class="sidebar" id="sidebar">
            <div class="sidebar-brand portal-brand">
                <div class="portal-brand-logo"><img src="../image/pbi_logo" alt="PBI" onerror="this.style.display='none'"/></div>
                <div class="portal-brand-copy">
                    <strong>Staff Workspace</strong>
                    <span>Evaluation Workspace</span>
                </div>
            </div>

            <div class="portal-sidebar-profile">
                <div class="portal-profile-avatar-wrap">
                    <?php if ($staff_photo): ?>
                    <img class="portal-profile-avatar" src="<?= htmlspecialchars($photo_url) ?>" alt="<?= htmlspecialchars($full_name) ?>"/>
                    <?php else: ?>
                    <span class="portal-profile-avatar portal-profile-fallback"><?= htmlspecialchars($initials) ?></span>
                    <?php endif; ?>
                </div>
                <div class="portal-profile-name"><?= htmlspecialchars($full_name) ?></div>
                <div class="portal-profile-role"><?= $staff_has_teaching_assignment ? 'TEACHING STAFF' : 'NON-TEACHING STAFF' ?><?= !empty($my_levels) ? ' · ' . htmlspecialchars(implode(', ', $my_levels)) : '' ?></div>
            </div>

            <nav class="sidebar-nav portal-sidebar-nav">
                <div class="nav-section-label">Main</div>
                <a href="staff_dashboard.php?page=dashboard" class="nav-link <?= $page==='dashboard'?'active':'' ?>">
                    <i class="fa-solid fa-house"></i><span>Dashboard</span>
                </a>
                <a href="staff_dashboard.php?page=profile" class="nav-link <?= $page==='profile'?'active':'' ?>">
                    <i class="fa-solid fa-id-badge"></i><span>Role &amp; Designation</span>
                </a>

                <div class="nav-section-label sidebar-section-secondary">Evaluation</div>
                <a href="staff_dashboard.php?page=my_results" class="nav-link <?= $page==='my_results'?'active':'' ?>">
                    <i class="fa-solid fa-chart-bar"></i><span>My Results</span>
                </a>
                <?php if (!$staff_has_teaching_assignment): ?>
                <a href="staff_dashboard.php?page=staff_eval" class="nav-link <?= in_array($page,['staff_eval','staff_eval_form'])?'active':'' ?>">
                    <i class="fa-solid fa-users"></i><span>Staff Evaluation</span>
                    <?php if (!empty($staff_eval_targets) && $page==='staff_eval'): ?>
                    <span class="side-nav-badge"><?= count($staff_eval_targets) ?></span>
                    <?php endif; ?>
                </a>
                <?php else: ?>
                <a href="staff_dashboard.php?page=peer" class="nav-link <?= in_array($page,['peer','peer_eval','staff_eval','staff_eval_form'])?'active':'' ?>">
                    <i class="fa-solid fa-people-arrows"></i><span>Peer Evaluation</span>
                </a>
                <?php endif; ?>

                <div class="nav-section-label sidebar-section-secondary">Support</div>
                <a href="staff_dashboard.php?page=settings" class="nav-link <?= $page==='settings'?'active':'' ?>">
                    <i class="fa-solid fa-gear"></i><span>Settings</span>
                </a>
            </nav>

            <div class="sidebar-footer">
                <a href="staff_logout.php" class="btn-logout-side" onclick="return confirm('Log out of your staff session?')">
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
                            <img id="photoPreviewImg" src="<?= $staff_photo ? htmlspecialchars($photo_url) : '' ?>"
                                 style="<?= $staff_photo ? 'display:block' : '' ?>"/>
                            <i class="fa-solid fa-camera upload-icon-modal" id="uploadIconEl" style="<?= $staff_photo ? 'display:none' : '' ?>"></i>
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

        <!-- TOP NAV -->
        <nav class="top-nav">
            <div style="display:flex;align-items:center;gap:14px;">
                <button class="hamburger" onclick="document.getElementById('sidebar').classList.toggle('open')">
                    <i class="fa-solid fa-bars"></i>
                </button>
                <div class="nav-page-title">
                    <?php
                    $titles = ['dashboard'=>'Dashboard','profile'=>'Role & Designation','my_results'=>'My Evaluation Results',
                        'staff_eval'=> $staff_has_teaching_assignment ? 'Peer Evaluation' : 'Staff Evaluation',
                        'staff_eval_form'=> $staff_has_teaching_assignment ? 'Peer Evaluation' : 'Staff Evaluation',
                        'peer'=>'Peer Evaluation','peer_eval'=>'Peer Evaluation','settings'=>'Settings'];
                    echo $titles[$page] ?? 'Dashboard';
                    ?>
                </div>
            </div>
            <div class="nav-right">
                <?php if ($period): ?>
                <div class="period-badge">
                    <i class="fa-solid fa-calendar-check"></i>
                    <?= htmlspecialchars($period['period_label']) ?>
                </div>
                <?php endif; ?>

                <div class="notif-wrap" id="notifWrap">
                    <button class="notif-btn <?= $unread_count>0?'has-unread':'' ?>" id="notifBtn" onclick="toggleNotifDropdown(event)" title="Notifications">
                        <i class="fa-regular fa-bell"></i>
                        <?php if ($unread_count > 0): ?>
                        <span class="notif-badge" id="notifBadge"><?= $unread_count > 99 ? '99+' : $unread_count ?></span>
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

        <!-- MAIN -->
        <main class="main">

        <?php if ($toast): ?>
        <div class="toast toast-success"><i class="fa-solid fa-circle-check"></i><?= htmlspecialchars($toast) ?></div>
        <?php endif; ?>
        <?php if ($toast_error): ?>
        <div class="toast toast-error"><i class="fa-solid fa-circle-exclamation"></i><?= htmlspecialchars($toast_error) ?></div>
        <?php endif; ?>

        <!-- ══════════ DASHBOARD ══════════ -->
        <?php if ($page === 'dashboard'): ?>

        <div class="dashboard-shell">
            <div class="dashboard-intro">
                <div class="dashboard-kicker"><i class="fa-solid fa-chart-line"></i> Dashboard Overview</div>
                <h1>Staff Evaluation Workspace</h1>
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
                <div class="schedule-summary-item"><span class="schedule-label">Academic Year</span><strong><?= htmlspecialchars($dashboard_acad_year ?: '—') ?></strong></div>
                <div class="schedule-summary-item"><span class="schedule-label">Evaluation Opens</span><strong><?= htmlspecialchars($dashboard_open_display) ?></strong></div>
                <div class="schedule-summary-item"><span class="schedule-label">Evaluation Closes</span><strong><?= htmlspecialchars($dashboard_close_display) ?></strong></div>
                <div class="schedule-summary-item schedule-status-item"><span class="schedule-label">Status</span><span class="schedule-status <?= htmlspecialchars($dashboard_status_class) ?>"><?= htmlspecialchars($dashboard_status) ?></span></div>
            </div>
        </div>

        <div class="welcome-bar">
            <div>
                <h2>Welcome, <?= htmlspecialchars(explode(',', $full_name)[0] ?? $full_name) ?>!</h2>
                <p><?= htmlspecialchars($designation) ?> &nbsp;·&nbsp;
                <?= $period ? htmlspecialchars($period['period_label']) : 'No active evaluation period' ?></p>
            </div>
            <div class="score-chip">
                <div class="big"><?= $my_avg !== null ? number_format($my_avg, 2) : '—' ?></div>
                <div class="lbl">Your Avg Score</div>
            </div>
        </div>

        <div class="stats-grid">
            <div class="stat-box"><div class="stat-box-icon green"><i class="fa-solid fa-users"></i></div><div class="stat-box-lbl">Evaluations Received</div><div class="stat-box-val teal"><?= $my_total ?></div></div>
            <div class="stat-box"><div class="stat-box-icon mint"><i class="fa-solid fa-circle-check"></i></div><div class="stat-box-lbl">Overall Average</div><div class="stat-box-val gold"><?= $my_avg !== null ? number_format($my_avg,2).' / 5' : '—' ?></div></div>
            <div class="stat-box"><div class="stat-box-icon gold"><i class="fa-solid fa-award"></i></div><div class="stat-box-lbl">Performance Level</div>
                <div class="stat-box-val" style="font-size:18px;color:<?= $my_avg===null?'#6b7280':($my_avg>=4?'#4ade80':($my_avg>=3?'#facc15':'#f87171')) ?>">
                    <?= $my_avg===null?'—':($my_avg>=4?'Excellent':($my_avg>=3?'Good':'Needs Improvement')) ?>
                </div></div>
            <div class="stat-box"><div class="stat-box-icon sky"><i class="fa-solid fa-calendar"></i></div><div class="stat-box-lbl">Current Period</div>
                <div class="stat-box-val" style="font-size:16px;color:var(--teal-hover)"><?= $period?htmlspecialchars($period['semester']):'None' ?></div></div>
        </div>

        <?php if (!empty($my_scores)): ?>
        <div class="section-card">
            <div class="section-card-title"><i class="fa-solid fa-layer-group" style="color:var(--teal)"></i> Performance by Category</div>
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
    <div class="section-card-title"><i class="fa-solid fa-clock-rotate-left" style="color:var(--accent)"></i> Evaluations Received</div>
    <button type="button" class="btn-view-all-evals" id="viewEvalsBtn" onclick="toggleRecentEvals()">
        <i class="fa-solid fa-eye"></i> View Evaluations Received
        <i class="fa-solid fa-chevron-down" id="recentEvalsCaret" style="transition:transform .2s;margin-left:auto;"></i>
    </button>
    <div id="recentEvalsList" style="display:none;margin-top:14px;">
    <?php if (empty($recent_subs)): ?>
    <div class="empty-state"><i class="fa-solid fa-inbox"></i><p>No evaluations received yet.</p></div>
    <?php else: foreach ($recent_subs as $s):
                $sc = $s['overall_score'];
                $col = $sc>=4?'#4ade80':($sc>=3?'#facc15':'#f87171');
            ?>
            <div class="sub-item sub-item-clickable" onclick="openEvalDetails(<?= (int)$s['tracker_id'] ?>)">
                <div>
                    <div style="font-size:13px;color:var(--light);font-weight:600;"><i class="fa-solid fa-eye-slash" style="color:var(--muted);margin-right:5px"></i>Anonymous Evaluator</div>
                    <div class="sub-meta"><?= date('M d, Y', strtotime($s['submitted_at'])) ?></div>
                </div>
                <div style="display:flex;flex-direction:column;align-items:flex-end;gap:8px;">
                    <div class="sub-score-badge" style="background:<?= $col ?>22;color:<?= $col ?>;border:1px solid <?= $col ?>44">
                        <?= $sc !== null ? number_format($sc,2) : '—' ?> / 5
                    </div>
                    <?php if (!empty($user_prefs['show_result_details'])): ?>
                    <button type="button" class="btn-view-details" onclick="event.stopPropagation(); openEvalDetails(<?= (int)$s['tracker_id'] ?>)">
                        View Details <i class="fa-solid fa-chevron-right"></i>
                    </button>
                    <?php endif; ?>
                </div>
            </div>
    <?php endforeach; endif; ?>
    </div>
</div>

        </div>
        <!-- ══════════ MY PROFILE & ROLE ══════════ -->
        <?php elseif ($page === 'profile'): ?>

        <!-- Hero card with photo, name, and current role. -->
        <div class="profile-hero">
            <div class="profile-hero-left">
                <div style="position:relative;">
                    <?php if ($photo_url): ?>
                    <img class="profile-hero-avatar" src="<?= htmlspecialchars($photo_url) ?>" alt="<?= htmlspecialchars($full_name) ?>" style="display:block;"/>
                    <?php else: ?>
                    <div class="profile-hero-avatar"><i class="fa-solid fa-briefcase"></i></div>
                    <?php endif; ?>
                    <button type="button" onclick="openPhotoModal()" title="Update Profile Photo"
                            style="position:absolute;bottom:-2px;right:-2px;width:30px;height:30px;border-radius:50%;background:var(--teal);border:2px solid var(--mid);color:#fff;cursor:pointer;display:flex;align-items:center;justify-content:center;font-size:13px;">
                        <i class="fa-solid fa-camera"></i>
                    </button>
                </div>
                <div>
                    <div class="profile-hero-name"><?= htmlspecialchars($full_name) ?></div>
                    <div class="profile-hero-desig">
                        <i class="fa-solid fa-id-badge"></i> <?= htmlspecialchars($designation) ?>
                    </div>
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

        <!-- ROLE ASSIGNMENT CARD -->
        <div class="role-card">
            <div class="role-card-title">
                <i class="fa-solid fa-tags" style="color:var(--teal)"></i>
                Assign / Update My Role
            </div>
            <div class="role-card-sub">
                Your role determines which evaluation questions apply to you and how you appear in the questionnaire.
                Changes take effect immediately and the admin is notified.
            </div>

            <!-- Quick pick chips -->
            <div class="role-chips-label">
                <i class="fa-solid fa-bolt" style="color:var(--teal)"></i> Quick Pick
            </div>
            <div class="role-chips">
                <?php foreach ($desig_suggestions as $d): ?>
                <div class="role-chip <?= ($d === $designation) ? 'is-current' : '' ?>"
                     onclick="pickRole('<?= htmlspecialchars(addslashes($d)) ?>')">
                    <?= htmlspecialchars($d) ?>
                </div>
                <?php endforeach; ?>
            </div>

            <!-- Custom text input -->
            <div class="custom-role-label">
                <i class="fa-solid fa-pen-to-square" style="color:var(--teal)"></i> Or type a custom role
            </div>
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
                <span>Your role change is logged and sent to the admin automatically so they can confirm or reassign you if needed.</span>
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
<div class="settings-card full"><div class="settings-card-head"><div class="sicon"><i class="fa-solid fa-user-shield"></i></div><div><h3>Account Overview</h3><p>Your access is controlled by the System Admin.</p></div></div><div class="account-facts"><div class="account-fact"><label>Account Name</label><b><?= htmlspecialchars($full_name) ?></b></div><div class="account-fact"><label>System Role</label><b>Staff</b></div><div class="account-fact"><label>Evaluation Access</label><b><?= htmlspecialchars($staff_can_evaluate_label) ?></b></div></div></div>
            <div class="settings-card"><div class="settings-card-head"><div class="sicon"><i class="fa-solid fa-bell"></i></div><div><h3>Notifications</h3><p>Choose the updates that matter to you.</p></div></div><form method="POST"><input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>"><input type="hidden" name="save_preferences" value="1"><div class="setting-row"><div><strong>Designation updates</strong><span>Notify me about designation changes.</span></div><label class="setting-toggle"><input type="checkbox" name="email_on_designation_update" <?= !empty($user_prefs['email_on_designation_update'])?'checked':'' ?>><span class="slider"></span></label></div><div class="setting-row"><div><strong>New evaluation results</strong><span>Notify me when a new evaluation is received.</span></div><label class="setting-toggle"><input type="checkbox" name="email_on_new_evaluation" <?= !empty($user_prefs['email_on_new_evaluation'])?'checked':'' ?>><span class="slider"></span></label></div><div class="setting-row"><div><strong>Evaluation details</strong><span>Show detailed entries in My Results.</span></div><label class="setting-toggle"><input type="checkbox" name="show_result_details" <?= !empty($user_prefs['show_result_details'])?'checked':'' ?>><span class="slider"></span></label></div><div class="settings-actions"><button class="settings-save" type="submit"><i class="fa-solid fa-check"></i> Save Preferences</button></div></form></div>
            <div class="settings-card"><div class="settings-card-head"><div class="sicon"><i class="fa-solid fa-lock"></i></div><div><h3>Security</h3><p>Keep your staff account protected.</p></div></div><div class="setting-row"><div><strong>Password</strong><span>Update your password securely.</span></div><a href="change_password.php" class="settings-save">Change</a></div><div class="setting-row"><div><strong>Role protection</strong><span>Your system role remains administrator-controlled.</span></div><i class="fa-solid fa-shield-halved" style="color:var(--success)"></i></div></div>
            <div class="settings-card full"><div class="settings-card-head"><div class="sicon"><i class="fa-solid fa-briefcase"></i></div><div><h3>Staff Evaluation Access</h3><p>Your permitted actions in the Employee Performance Management System.</p></div></div><div class="account-facts"><div class="account-fact"><label>Can Evaluate</label><b><?= htmlspecialchars($staff_can_evaluate_label) ?></b></div><div class="account-fact"><label>Can View</label><b>Own Evaluation Results</b></div><div class="account-fact"><label>Privacy</label><b>Evaluator identity remains protected</b></div></div></div>
        </div>

        <!-- ══════════ MY RESULTS ══════════ -->
        <?php elseif ($page === 'my_results'): ?>

        <div class="section-card">
            <div class="section-card-title"><i class="fa-solid fa-chart-bar" style="color:var(--teal)"></i> Your Evaluation Summary</div>
            <div style="display:flex;gap:28px;flex-wrap:wrap;margin-bottom:20px;">
                <div>
                    <div style="font-size:11px;color:var(--muted);text-transform:uppercase;letter-spacing:.8px;margin-bottom:5px;">Overall Average</div>
                    <div style="font-size:36px;font-weight:700;color:<?= $my_avg===null?'#6b7280':($my_avg>=4?'#4ade80':($my_avg>=3?'#facc15':'#f87171')) ?>">
                        <?= $my_avg !== null ? number_format($my_avg,2) : '—' ?><span style="font-size:16px;color:var(--muted)"> / 5</span>
                    </div>
                </div>
                <div>
                    <div style="font-size:11px;color:var(--muted);text-transform:uppercase;letter-spacing:.8px;margin-bottom:5px;">Total Responses</div>
                    <div style="font-size:36px;font-weight:700;color:var(--teal-hover)"><?= $my_total ?></div>
                </div>
            </div>
            <?php if (!empty($my_scores)): ?>
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
            <?php else: ?>
            <div class="empty-state"><i class="fa-solid fa-chart-simple"></i><p>No category data available yet.</p></div>
            <?php endif; ?>
        </div>

        <div class="section-card">
<div class="section-card">
    <div class="section-card-title"><i class="fa-solid fa-list" style="color:var(--accent)"></i> Evaluations Received (Anonymous)</div>
    <button type="button" class="btn-view-all-evals" id="viewAllEvalsBtn" onclick="toggleAllEvals()">
        <i class="fa-solid fa-eye"></i> View Evaluations Received
        <i class="fa-solid fa-chevron-down" id="allEvalsCaret" style="transition:transform .2s;margin-left:auto;"></i>
    </button>
    <div id="allEvalsList" style="display:none;margin-top:14px;">
    <?php if (empty($recent_subs)): ?>
            <div class="empty-state"><i class="fa-solid fa-inbox"></i><p>No evaluations recorded yet.</p></div>
            <?php else:
                $allStmt = $mysqli->prepare("
                    SELECT et.id AS tracker_id, et.submitted_at,
                           (SELECT AVG(qa.answer_score) FROM questionnaire_answers qa WHERE qa.tracker_id = et.id) as overall_score
                    FROM evaluation_tracker et
                    WHERE et.target_user_id=?
                    ORDER BY et.submitted_at DESC
                ");
                $allStmt->bind_param("i", $user_id);
                $allStmt->execute();
                $all_subs = $allStmt->get_result();
                if ($all_subs) while ($s = $all_subs->fetch_assoc()):
                    $col = $s['overall_score']>=4?'#4ade80':($s['overall_score']>=3?'#facc15':'#f87171');
            ?>
            <div class="sub-item sub-item-clickable" onclick="openEvalDetails(<?= (int)$s['tracker_id'] ?>)">
                <div>
                    <div style="font-size:13px;color:var(--light);font-weight:600;"><i class="fa-solid fa-eye-slash" style="color:var(--muted);margin-right:5px"></i>Anonymous</div>
                    <div class="sub-meta"><?= date('M d, Y g:i A', strtotime($s['submitted_at'])) ?></div>
                </div>
                <div style="display:flex;flex-direction:column;align-items:flex-end;gap:8px;">
                    <div class="sub-score-badge" style="background:<?= $col ?>22;color:<?= $col ?>;border:1px solid <?= $col ?>44">
                        <?= $s['overall_score'] !== null ? number_format($s['overall_score'],2) : '—' ?> / 5
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

        <!-- ══════════ STAFF EVALUATION (EA / Dean / Principal, per role) ══════════ -->
        <?php elseif ($page === 'staff_eval' && !$staff_has_teaching_assignment): ?>

        <!-- ══════════ STAFF EVALUATION (Executive Assistant, non-teaching Staff only) ══════════ -->
        <div class="ea-eval-wrap">
            <div class="ea-tab-row">
                <?php foreach ($staff_eval_targets as $target): ?>
                    <?php $isDoneTarget = false;
                    if ($period) {
                        $chk = $mysqli->prepare("SELECT id FROM evaluation_tracker WHERE evaluator_id=? AND target_user_id=? AND period_id=? AND eval_type='staff' AND status='submitted' LIMIT 1");
                        $chk->bind_param('iii', $user_id, $target['id'], $period['id']);
                        $chk->execute();
                        $isDoneTarget = (bool)$chk->get_result()->fetch_assoc();
                        $chk->close();
                    }
                    ?>
                    <a class="ea-tab <?= $staff_eval_target && $staff_eval_target['target_type'] === $target['target_type'] ? 'active' : '' ?>"
                       href="staff_dashboard.php?page=<?= $staff_eval_home_page ?>&tid=<?= (int)$target['id'] ?>">
                        <i class="fa-solid <?= $target['target_type']==='Dean' ? 'fa-graduation-cap' : ($target['target_type']==='Principal' ? 'fa-user-tie' : 'fa-user-shield') ?>"></i>
                        <?= htmlspecialchars($target['target_label']) ?>
                        <span class="count"><?= $isDoneTarget ? '✓' : '1' ?></span>
                    </a>
                <?php endforeach; ?>
            </div>

            <div class="ea-toolbar">
                <div>
                    <div style="font-size:12px;text-transform:uppercase;letter-spacing:.7px;color:var(--muted);font-weight:700;">Staff Evaluation</div>
                    <div style="font-size:14px;color:var(--light);margin-top:4px;">Evaluate the Executive Assistant using the questionnaire configured by the admin.</div>
                </div>
                <span class="ea-status <?= $staff_eval_already_done ? 'done' : '' ?>">
                    <i class="fa-solid <?= $staff_eval_already_done ? 'fa-circle-check' : 'fa-clipboard-list' ?>"></i>
                    <?= $staff_eval_already_done ? 'Completed' : 'Ready' ?>
                </span>
            </div>

            <div class="ea-table-card">
                <?php if (empty($staff_eval_targets)): ?>
                    <div class="empty-state">
                        <i class="fa-solid fa-users"></i>
                        <p>No Executive Assistant is currently available for Staff Evaluation.</p>
                    </div>
                <?php else: ?>
                    <table class="ea-table">
                        <thead>
                            <tr>
                                <th style="width:31%">Target</th>
                                <th style="width:18%">Role</th>
                                <th style="width:18%">Questionnaire</th>
                                <th style="width:18%">Evaluation Status</th>
                                <th style="width:15%">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($staff_eval_targets as $target): ?>
                            <?php if (!$staff_eval_target || (int)$target['id'] !== (int)$staff_eval_target['id']) continue; ?>
                            <?php
                            $done = false; $last_eval = null; $qCount = 0;
                            if ($period) {
                                $chk = $mysqli->prepare("SELECT submitted_at FROM evaluation_tracker WHERE evaluator_id=? AND target_user_id=? AND period_id=? AND eval_type='staff' AND status='submitted' ORDER BY submitted_at DESC LIMIT 1");
                                $chk->bind_param('iii', $user_id, $target['id'], $period['id']);
                                $chk->execute();
                                $doneRow = $chk->get_result()->fetch_assoc();
                                if ($doneRow) { $done = true; $last_eval = $doneRow['submitted_at']; }
                                $chk->close();
                            }
                            $qc = $mysqli->prepare("SELECT COUNT(*) AS c FROM user_questions WHERE user_id=? AND target_type=? AND eval_type='general'");
                            $qc->bind_param('is', $target['id'], $target['target_type']);
                            $qc->execute();
                            $qCount = (int)($qc->get_result()->fetch_assoc()['c'] ?? 0);
                            $qc->close();
                            ?>
                            <tr>
                                <td>
                                    <div class="ea-profile">
                                        <?php if (!empty($target['photo'])): ?>
                                            <img class="ea-avatar" src="../image/<?= htmlspecialchars($target['photo']) ?>" alt="">
                                        <?php else: ?>
                                            <div class="ea-avatar"><i class="fa-solid <?= $target['target_type']==='Dean' ? 'fa-graduation-cap' : ($target['target_type']==='Principal' ? 'fa-user-tie' : 'fa-user-shield') ?>"></i></div>
                                        <?php endif; ?>
                                        <div>
                                            <div class="ea-name"><?= htmlspecialchars($target['full_name']) ?></div>
                                            <div class="ea-sub"><?= htmlspecialchars($target['designation'] ?: $target['target_label']) ?></div>
                                        </div>
                                    </div>
                                </td>
                                <td><span class="ea-role-pill"><?= htmlspecialchars($target['target_label']) ?></span></td>
                                <td><?= $qCount ?> question<?= $qCount === 1 ? '' : 's' ?></td>
                                <td><span class="ea-status <?= $done ? 'done' : '' ?>"><i class="fa-solid <?= $done ? 'fa-circle-check' : 'fa-hourglass-half' ?>"></i><?= $done ? 'Evaluated' : 'Not Started' ?></span></td>
                                <td>
                                    <?php if ($done): ?>
                                        <span class="ea-evaluate-btn done"><i class="fa-solid fa-check"></i> Evaluated</span>
                                    <?php elseif (!$evaluation_open): ?>
                                        <span class="ea-evaluate-btn" style="opacity:.55;cursor:not-allowed;"><i class="fa-solid fa-clock"></i> Scheduled</span>
                                    <?php else: ?>
                                        <a class="ea-evaluate-btn" href="staff_dashboard.php?page=staff_eval_form&tid=<?= (int)$target['id'] ?>"><i class="fa-solid fa-pen"></i> Evaluate</a>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
        </div>

        <?php elseif ($page === 'staff_eval_form'): ?>

        <a href="staff_dashboard.php?page=<?= $staff_eval_home_page ?><?= $staff_has_teaching_assignment ? '' : '&tid=' . (int)($staff_eval_target['id'] ?? 0) ?>" class="back-link"><i class="fa-solid fa-arrow-left"></i> Back to <?= $staff_has_teaching_assignment ? 'Peer Evaluation' : 'Staff Evaluation' ?></a>
        <div class="section-card">
            <?php if (!$evaluation_open): ?>
                <div class="empty-state"><i class="fa-solid fa-clock"></i><p>Evaluation is currently closed. It will open at the scheduled time set by the administrator.</p></div>
            <?php elseif (!$staff_eval_target): ?>
                <div class="empty-state"><i class="fa-solid fa-users"></i><p>No <?= htmlspecialchars($staff_eval_feature_label) ?> target is available.</p></div>
            <?php elseif (empty($staff_eval_questions)): ?>
                <div class="empty-state"><i class="fa-solid fa-clipboard-question"></i><p>The <?= htmlspecialchars($staff_eval_target['target_label']) ?> questionnaire has not been configured yet.</p></div>
            <?php elseif ($staff_eval_already_done): ?>
                <div class="empty-state"><i class="fa-solid fa-circle-check"></i><p>You have already evaluated <?= htmlspecialchars($staff_eval_target['full_name']) ?> for this period.</p></div>
            <?php else: ?>
                <div class="section-card-title">
                    <i class="fa-solid <?= $staff_eval_target['target_type']==='Dean' ? 'fa-graduation-cap' : ($staff_eval_target['target_type']==='Principal' ? 'fa-user-tie' : 'fa-user-shield') ?>" style="color:var(--teal)"></i>
                    Evaluate <?= htmlspecialchars($staff_eval_target['full_name']) ?>
                </div>
                <p style="font-size:13px;color:var(--muted);margin-bottom:22px;">
                    <?= htmlspecialchars($staff_eval_feature_label) ?> · <?= htmlspecialchars($staff_eval_target['target_label']) ?> · responses are confidential.
                </p>

                <form method="POST" action="staff_dashboard.php?page=staff_eval_form&tid=<?= (int)$staff_eval_target['id'] ?>" id="staffEvalForm" novalidate>
                    <div class="eval-validation-alert" id="staffEvalValidation" role="alert" aria-live="assertive"></div>
                    <div class="eval-progress" id="staffEvalProgress" aria-live="polite">0 of 0 questions answered</div>
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
                    <input type="hidden" name="target_id" value="<?= (int)$staff_eval_target['id'] ?>">
                    <input type="hidden" name="submit_staff_evaluation" value="1">

                    <?php $staffQNo=1; foreach ($staff_eval_categories as $category => $questions): ?>
                        <div style="font-size:12px;text-transform:uppercase;letter-spacing:.7px;color:var(--teal-hover);font-weight:700;margin:0 0 9px;"><?= htmlspecialchars($category) ?></div>
                        <div class="compact-eval-table-wrap">
                            <table class="compact-eval-table">
                                <thead><tr><th>Question</th><th>5</th><th>4</th><th>3</th><th>2</th><th>1</th></tr></thead>
                                <tbody>
                                <?php foreach ($questions as $q): ?>
                                <tr class="eval-question-row staff-eval-question-row">
                                    <td><div class="compact-eval-qtext"><span class="compact-eval-qno"><?= $staffQNo++ ?>.</span><?= htmlspecialchars($q['question_text']) ?></div></td>
                                    <?php for($r=5;$r>=1;$r--): $optId='staff_r_' . (int)$q['id'] . '_' . $r; ?><td><div class="compact-eval-rating"><input type="radio" name="ratings[<?= (int)$q['id'] ?>]" id="<?= $optId ?>" value="<?= $r ?>"><label for="<?= $optId ?>"><?= $r ?></label></div></td><?php endfor; ?>
                                </tr>
                                <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endforeach; ?>

                    <div class="comments-card">
                        <div style="font-size:13px;font-weight:700;color:var(--teal-hover);margin-bottom:10px;">
                            <i class="fa-solid fa-comment-dots"></i> Comments &amp; Suggestions
                           
                        </div>
                        <textarea class="comments-box" name="comments" placeholder="Share your thoughts about this <?= htmlspecialchars($staff_eval_target['target_label']) ?>…"></textarea>
                    </div>
                    <div class="submit-row">
                        <span style="font-size:13px;color:var(--muted);">
                            <i class="fa-solid fa-circle-info" style="color:#60a5fa;margin-right:5px"></i>
                            Rate each question 1 (Never) to 5 (Always).
                        </span>
                        <button type="submit" class="btn-submit" >
                            <i class="fa-solid fa-paper-plane"></i> Submit Evaluation
                        </button>
                    </div>
                </form>
            <?php endif; ?>
        </div>

        <script>
        function staffRate(id,val){
            const radio=document.querySelector('input[name="ratings['+id+']"][value="'+val+'"]');
            if (radio) radio.checked=true;
        }
        function validateRatingForm(form, alertBoxId) {
    if (!form) return false;
    const radios = [...form.querySelectorAll('input[type="radio"][name^="ratings["]')];
    const groups = [...new Set(radios.map(r => r.name))];
    const missing = groups.filter(name => !form.querySelector('input[name="' + CSS.escape(name) + '"]:checked'));
    const alertBox = document.getElementById(alertBoxId);
    form.querySelectorAll('.eval-question-row.eval-unanswered').forEach(row => row.classList.remove('eval-unanswered'));

    if (!groups.length) {
        if (alertBox) { alertBox.innerHTML = '<strong>No questions found.</strong> Please close and try again.'; alertBox.classList.add('show'); }
        return false;
    }
    if (missing.length) {
        const labels=[];
        missing.forEach(name => {
            const input=form.querySelector('input[name="'+CSS.escape(name)+'"]');
            const row=input ? input.closest('tr') : null;
            if(row){
                row.classList.add('eval-unanswered');
                const q=row.querySelector('.compact-eval-qno');
                labels.push(q ? q.textContent.replace(/[^0-9]/g,'') : '?');
            }
        });
        const questionWord=missing.length===1?'question is':'questions are';
        if(alertBox){
            alertBox.innerHTML='<strong>Please complete all required questions.</strong> '+missing.length+' '+questionWord+' unanswered.'+(labels.length?' Missing: <strong>Q'+labels.join(', Q')+'.</strong>':'');
            alertBox.classList.add('show');
            alertBox.scrollIntoView({behavior:'smooth',block:'center'});
        }
        const firstRow=form.querySelector('.eval-question-row.eval-unanswered');
        if(firstRow) setTimeout(()=>firstRow.scrollIntoView({behavior:'smooth',block:'center'}),80);
        return false;
    }
    if(alertBox) alertBox.classList.remove('show');
    return true;
}
function updateRatingProgress(form, counterId, alertId) {
    const counter=document.getElementById(counterId);
    if(!form || !counter) return;
    const groups=[...new Set([...form.querySelectorAll('input[type="radio"][name^="ratings["]')].map(r=>r.name))];
    const answered=groups.filter(name=>form.querySelector('input[name="'+CSS.escape(name)+'"]:checked')).length;
    counter.textContent=answered+' of '+groups.length+' questions answered';
    counter.classList.toggle('is-complete',groups.length>0 && answered===groups.length);
    if(groups.length && answered===groups.length) document.getElementById(alertId)?.classList.remove('show');
}
function checkStaffAll(){ return validateRatingForm(document.getElementById('staffEvalForm'),'staffEvalValidation'); }
function checkAll(){ return validateRatingForm(document.getElementById('evalForm'),'peerEvalValidation'); }

const staffEvalForm=document.getElementById('staffEvalForm');
if(staffEvalForm){
    staffEvalForm.addEventListener('change',function(e){
        if(!e.target.matches('input[type="radio"][name^="ratings["]')) return;
        const row=e.target.closest('.eval-question-row'); if(row) row.classList.remove('eval-unanswered');
        updateRatingProgress(staffEvalForm,'staffEvalProgress','staffEvalValidation');
    });
    staffEvalForm.addEventListener('submit',function(e){ if(!checkStaffAll()) e.preventDefault(); });
    updateRatingProgress(staffEvalForm,'staffEvalProgress','staffEvalValidation');
}
const peerEvalForm=document.getElementById('evalForm');
if(peerEvalForm){
    peerEvalForm.addEventListener('change',function(e){
        if(!e.target.matches('input[type="radio"][name^="ratings["]')) return;
        const row=e.target.closest('.eval-question-row'); if(row) row.classList.remove('eval-unanswered');
        updateRatingProgress(peerEvalForm,'peerEvalProgress','peerEvalValidation');
    });
    peerEvalForm.addEventListener('submit',function(e){ if(!checkAll()) e.preventDefault(); });
    updateRatingProgress(peerEvalForm,'peerEvalProgress','peerEvalValidation');
}
        </script>

        <!-- ══════════ PEER EVALUATION — LOCKED FOR NON-TEACHING STAFF ══════════ -->
        <?php elseif (in_array($page, ['peer','peer_eval'], true) && !$staff_has_teaching_assignment): ?>

        <div class="section-card">
            <div class="empty-state"><i class="fa-solid fa-lock"></i><p>Peer Evaluation is only available to Staff with a teaching assignment. Use Staff Evaluation instead.</p></div>
        </div>

        <!-- ══════════ PEER EVALUATION — STEP 1: CHOOSE DESIGNATION ══════════ -->
        <?php elseif ($page === 'peer' && $peer_group === null): ?>

        <div class="section-card">
            <div class="section-card-title"><i class="fa-solid fa-users" style="color:var(--teal)"></i> Peer Evaluation</div>
            <p style="font-size:13px;color:var(--muted);margin-bottom:20px;">First, choose which designation you'd like to evaluate. You'll then pick a specific person from that list.</p>
            <?php if (!$evaluation_open): ?>
            <div class="no-period-warn"><i class="fa-solid fa-clock"></i> Evaluation is currently closed until the scheduled opening time.</div>
            <?php endif; ?>

            <?php
                $teacher_count   = count(array_filter($peers_all, fn($p) => resolve_peer_group($mysqli, $p) === 'teacher'));
                $staff_count     = count(array_filter($peers_all, fn($p) => resolve_peer_group($mysqli, $p) === 'staff'));
                $dean_count      = count(array_filter($staff_eval_targets, fn($t) => $t['target_type'] === 'Dean'));
                $principal_count = count(array_filter($staff_eval_targets, fn($t) => $t['target_type'] === 'Principal'));
                $anything_to_evaluate = !empty($peers_all) || $staff_teaches_college || $staff_teaches_basic_ed;
            ?>
            <?php if (!$anything_to_evaluate): ?>
            <div class="empty-state"><i class="fa-solid fa-users"></i><p>No eligible Faculty/Staff peers are currently available.</p></div>
            <?php else: ?>
            <div class="role-chips-label"><i class="fa-solid fa-bolt" style="color:var(--teal)"></i> Step 1: Select Evaluation Group</div>
            <div class="eval-group-grid">
                <a href="staff_dashboard.php?page=peer&group=teacher" class="eval-group-card">
                    <div class="eval-group-icon blue"><i class="fa-solid fa-chalkboard-user"></i></div>
                    <div class="eval-group-title">Faculty</div>
                    <div class="eval-group-count"><?= $teacher_count ?> member<?= $teacher_count == 1 ? '' : 's' ?></div>
                </a>
                <a href="staff_dashboard.php?page=peer&group=staff" class="eval-group-card">
                    <div class="eval-group-icon green"><i class="fa-solid fa-briefcase"></i></div>
                    <div class="eval-group-title">Staff</div>
                    <div class="eval-group-count"><?= $staff_count ?> member<?= $staff_count == 1 ? '' : 's' ?></div>
                </a>
                <?php if ($staff_teaches_college): ?>
                <a href="staff_dashboard.php?page=peer&group=dean" class="eval-group-card">
                    <div class="eval-group-icon gold"><i class="fa-solid fa-graduation-cap"></i></div>
                    <div class="eval-group-title">Dean</div>
                    <div class="eval-group-count"><?= $dean_count ?> member<?= $dean_count == 1 ? '' : 's' ?></div>
                </a>
                <?php endif; ?>
                <?php if ($staff_teaches_basic_ed): ?>
                <a href="staff_dashboard.php?page=peer&group=principal" class="eval-group-card">
                    <div class="eval-group-icon purple"><i class="fa-solid fa-user-tie"></i></div>
                    <div class="eval-group-title">Principal</div>
                    <div class="eval-group-count"><?= $principal_count ?> member<?= $principal_count == 1 ? '' : 's' ?></div>
                </a>
                <?php endif; ?>
            </div>
            <div class="peer-select-hint"><i class="fa-solid fa-circle-info"></i> Please select a designation before proceeding.</div>
            <?php endif; ?>
        </div>

        <!-- ══════════ PEER EVALUATION — STEP 2: FILTERED USER LIST (CARD GRID) ══════════ -->
        <?php elseif ($page === 'peer' && $peer_group !== null): ?>

        <?php $is_supervisor_group = in_array($peer_group, ['dean', 'principal'], true); ?>
        <a href="staff_dashboard.php?page=peer" class="back-link"><i class="fa-solid fa-arrow-left"></i> Change Designation</a>

        <div class="section-card">
            <div class="section-card-title">
                <i class="fa-solid <?= $is_supervisor_group ? ($peer_group === 'dean' ? 'fa-graduation-cap' : 'fa-user-tie') : 'fa-users' ?>" style="color:var(--teal)"></i>
                <?= $is_supervisor_group ? htmlspecialchars($peer_group_labels[$peer_group]) : 'Fellow ' . htmlspecialchars($peer_group_labels[$peer_group]) . ' Members' ?>
            </div>
            <?php if (!$evaluation_open): ?>
            <div class="no-period-warn"><i class="fa-solid fa-clock"></i> Evaluation is currently closed until the scheduled opening time.</div>
            <?php endif; ?>
            <p style="font-size:13px;color:var(--muted);margin-bottom:20px;">
                <?= $is_supervisor_group ? 'Evaluate your ' . htmlspecialchars(strtolower($peer_group_labels[$peer_group])) . ' using the questionnaire configured by the admin.' : 'Select a colleague to evaluate. Your identity will be kept confidential.' ?>
            </p>

            <?php if (empty($peers)): ?>
            <div class="empty-state"><i class="fa-solid fa-users"></i><p>No <?= $is_supervisor_group ? htmlspecialchars(strtolower($peer_group_labels[$peer_group])) : 'registered ' . htmlspecialchars(strtolower($peer_group_labels[$peer_group])) ?><?= $is_supervisor_group ? ' is currently available for your teaching level(s).' : ' users found.' ?></p></div>
            <?php else: ?>

            <!-- Cards populated dynamically from active users whose designation resolves
                 to the selected group (excluding self). Newly added accounts appear
                 automatically — no code change needed. -->
            <div class="peer-card-grid">
                <?php foreach ($peers as $p): $done = $is_supervisor_group ? in_array($p['id'], $done_supervisors) : in_array($p['id'], $done_peers); ?>
                <div class="peer-card <?= $done ? 'is-done' : '' ?>">
                    <?php if (!empty($p['photo'])): ?>
                    <img class="peer-card-photo" src="../image/<?= htmlspecialchars($p['photo']) ?>" alt="<?= htmlspecialchars($p['full_name']) ?>"/>
                    <?php else: ?>
                    <div class="peer-card-photo peer-card-photo-ph"><i class="fa-solid fa-user"></i></div>
                    <?php endif; ?>
                    <div class="peer-card-name"><?= htmlspecialchars($p['full_name']) ?></div>
                    <div class="peer-card-desig"><?= htmlspecialchars(peer_display_label($mysqli, $p, $peer_group, $peer_group_labels)) ?></div>
                    <?php if ($done): ?>
                    <button type="button" class="peer-card-btn done" disabled><i class="fa-solid fa-circle-check"></i> Evaluated</button>
                    <?php elseif (!$evaluation_open): ?>
                    <span class="peer-card-btn" style="opacity:.55;cursor:not-allowed;"><i class="fa-solid fa-clock"></i> Scheduled</span>
                    <?php elseif ($is_supervisor_group): ?>
                    <a href="staff_dashboard.php?page=staff_eval_form&tid=<?= (int)$p['id'] ?>" class="peer-card-btn">Evaluate</a>
                    <?php else: ?>
                    <a href="staff_dashboard.php?page=peer_eval&tid=<?= (int)$p['id'] ?>&group=<?= urlencode($peer_group) ?>" class="peer-card-btn">Evaluate</a>
                    <?php endif; ?>
                </div>
                <?php endforeach; ?>
            </div>

            <?php $done_in_group = count(array_intersect(array_column($peers, 'id'), $is_supervisor_group ? $done_supervisors : $done_peers)); ?>
            <?php if ($done_in_group > 0): ?>
            <div class="peer-select-hint warn" style="margin-top:18px;"><i class="fa-solid fa-circle-check"></i> You've already evaluated <?= $done_in_group ?> of <?= count($peers) ?> <?= htmlspecialchars(strtolower($peer_group_labels[$peer_group])) ?><?= $is_supervisor_group ? '' : ' members' ?> this period.</div>
            <?php endif; ?>

            <?php endif; ?>
        </div>

        <!-- ══════════ PEER EVAL FORM ══════════ -->
        <?php elseif ($page === 'peer_eval' && $peer_target): ?>

        <a href="staff_dashboard.php?page=peer&group=<?= urlencode($peer_eval_group) ?>" class="back-link"><i class="fa-solid fa-arrow-left"></i> Back to <?= htmlspecialchars($peer_group_labels[$peer_eval_group]) ?> List</a>

        <?php if (!$evaluation_open): ?>
        <div class="no-period-warn"><i class="fa-solid fa-clock"></i> Evaluation is currently closed until the scheduled opening time.</div>
        <?php endif; ?>

        <div class="eval-header-bar">
            <?php if (!empty($peer_target['photo'])): ?>
            <img class="eval-photo-lg" src="<?= UPLOAD_URL . htmlspecialchars($peer_target['photo']) ?>" alt=""/>
            <?php else: ?>
            <div class="eval-photo-ph"><i class="fa-solid fa-user"></i></div>
            <?php endif; ?>
            <div>
                <div class="eval-name"><?= htmlspecialchars($peer_target['full_name']) ?></div>
                <div class="eval-desig"><?= htmlspecialchars($peer_target['designation'] ?? 'Staff') ?></div>
            </div>
        </div>

        <?php if (!$evaluation_open): ?>
        <div class="empty-state"><i class="fa-solid fa-clock"></i><p>Evaluation is currently closed. It will open at the scheduled time set by the administrator.</p></div>
        <?php elseif (empty($peer_questions)): ?>
        <div style="background:rgba(251,191,36,.08);border:1px solid rgba(251,191,36,.2);border-radius:10px;padding:18px;color:#fcd34d;font-size:13px;display:flex;gap:10px;">
            <i class="fa-solid fa-triangle-exclamation"></i> No peer evaluation questions set up yet. Please contact the admin.
        </div>
        <?php else: ?>

        <div class="scale-legend">
            <?php foreach ([5=>'Always',4=>'Often',3=>'Sometimes',2=>'Rarely',1=>'Never'] as $n=>$lbl): ?>
            <div class="scale-legend-item">
                <div class="scale-legend-num"><?= $n ?></div>
                <div class="scale-legend-lbl"><?= $lbl ?></div>
            </div>
            <?php endforeach; ?>
        </div>

        <form method="POST" action="staff_dashboard.php?page=peer_eval&tid=<?= $peer_target['id'] ?>&group=<?= urlencode($peer_eval_group) ?>" id="evalForm" novalidate>
            <div class="eval-validation-alert" id="peerEvalValidation" role="alert" aria-live="assertive"></div>
            <div class="eval-progress" id="peerEvalProgress" aria-live="polite">0 of 0 questions answered</div>
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>"/>
            <input type="hidden" name="submit_peer" value="1"/>
            <input type="hidden" name="target_id"   value="<?= $peer_target['id'] ?>"/>
            <input type="hidden" name="group"       value="<?= htmlspecialchars($peer_eval_group) ?>"/>

            <?php $qno=1; foreach ($peer_categories as $cat_name => $cat_qs): ?>
            <div class="cat-group-title" style="margin-bottom:9px;"><i class="fa-solid fa-layer-group"></i> <?= htmlspecialchars($cat_name) ?></div>
            <div class="compact-eval-table-wrap">
                <table class="compact-eval-table">
                    <thead><tr><th>Question</th><th>5</th><th>4</th><th>3</th><th>2</th><th>1</th></tr></thead>
                    <tbody>
                    <?php foreach($cat_qs as $q): ?>
                    <tr class="eval-question-row peer-eval-question-row">
                        <td><div class="compact-eval-qtext"><span class="compact-eval-qno"><?= $qno++ ?>.</span><?= htmlspecialchars($q['question_text']) ?></div></td>
                        <?php for($r=5;$r>=1;$r--): $optId='peer_r_' . (int)$q['id'] . '_' . $r; ?><td><div class="compact-eval-rating"><input type="radio" name="ratings[<?= (int)$q['id'] ?>]" id="<?= $optId ?>" value="<?= $r ?>"><label for="<?= $optId ?>"><?= $r ?></label></div></td><?php endfor; ?>
                    </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php endforeach; ?>

            <div class="comments-card">
                <div style="font-size:13px;font-weight:700;color:var(--teal-hover);margin-bottom:10px;"><i class="fa-solid fa-comment-dots"></i> Comments &amp; Suggestions</div>
                <textarea class="comments-box" name="comments" placeholder="Share your thoughts about this person's performance…"></textarea>
            </div>

            <div class="submit-row">
                <span style="font-size:13px;color:var(--muted);"><i class="fa-solid fa-circle-info" style="color:#60a5fa;margin-right:5px"></i>Rate each question 1 (Never) to 5 (Always). All required.</span>
                <button type="submit" class="btn-submit" ><i class="fa-solid fa-paper-plane"></i> Submit Evaluation</button>
            </div>
        </form>
        <?php endif; ?>

        <!-- ══════════ PEER EVAL — INVALID / MISSING TARGET ══════════ -->
        <?php elseif ($page === 'peer_eval' && !$peer_target): ?>

        <a href="staff_dashboard.php?page=peer" class="back-link"><i class="fa-solid fa-arrow-left"></i> Back to Peer Selection</a>

        <div style="background:rgba(240,84,84,.08);border:1px solid rgba(240,84,84,.25);border-radius:10px;padding:18px;color:#fca5a5;font-size:13px;display:flex;gap:10px;">
            <i class="fa-solid fa-circle-exclamation"></i>
            <?= htmlspecialchars($peer_group_error ?: "Selected user does not exist, is inactive, or is not a valid Staff account. Please choose someone from the list.") ?>
        </div>

        <?php endif; ?>

        </main>

        <script>
        // Quick-pick role chip → fill input
        function pickRole(name) {
            document.getElementById('roleInput').value = name;
            document.querySelectorAll('.role-chip').forEach(c => c.classList.remove('is-current'));
            event.target.classList.add('is-current');
        }

        function eaRate(qid, val) {
            document.querySelectorAll(`#ea_grp_${qid} .star-btn`).forEach(b => b.classList.toggle('sel', parseInt(b.dataset.val) === val));
            const input=document.getElementById(`ea_r_${qid}`);
            if (input) input.value=val;
        }
        function checkEaAll() {
            const missing=[...document.querySelectorAll('input[id^=ea_r_][required]')].some(i => !i.value);
            if (missing) { alert('Please rate every question before submitting.'); return false; }
            return true;
        }

        // Peer evaluation rating controls use real radio buttons so the
        // 5-4-3-2-1 choices remain directly clickable even when JavaScript
        // is delayed or unavailable.
        function rate(qid, val) {
            const radio=document.querySelector('input[name="ratings['+qid+']"][value="'+val+'"]');
            if (radio) radio.checked=true;
        }
        function checkAll() {
            const radios=document.querySelectorAll('#evalForm input[type="radio"][name^="ratings["]');
            if(!radios.length){ alert('No questions found. Please close and try again.'); return false; }
            const names=[...new Set([...radios].map(r=>r.name))];
            for(const n of names){
                if(!document.querySelector('#evalForm input[name="'+CSS.escape(n)+'"]:checked')){
                    alert('Please rate all questions.');
                    return false;
                }
            }
            return true;
        }

        // ── Evaluation details modal ──
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
                html += `<div class="section-card-title" style="font-size:14px;margin-bottom:12px;"><i class="fa-solid fa-layer-group" style="color:var(--teal)"></i> Performance by Category</div>`;
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
                html += `<div class="section-card-title" style="font-size:14px;margin:20px 0 12px;"><i class="fa-solid fa-list-check" style="color:var(--accent)"></i> Question-by-Question Results</div>`;
                let lastCat = null;
                data.questions.forEach((q, idx) => {
                    if (q.category !== lastCat) {
                        html += `<div class="eval-cat-header">${escapeHtml(q.category)}</div>`;
                        lastCat = q.category;
                    }
                    html += `<div class="eval-q-card">
                        <div class="eval-q-no">Question ${idx + 1}</div>
                        <div class="eval-q-text">${escapeHtml(q.question_text)}</div>
                        <div style="display:flex;align-items:center;gap:10px;">
                            <div>${starsHtml(q.score)}</div>
                            <div style="font-size:12px;color:var(--muted);font-weight:700;">Score: ${q.score} / 5</div>
                        </div>
                    </div>`;
                });
            }

            html += `<div class="section-card-title" style="font-size:14px;margin:20px 0 10px;"><i class="fa-solid fa-comment-dots" style="color:var(--teal-hover)"></i> Comments / Feedback</div>`;
            html += data.comment
                ? `<div class="eval-comment-box">"${escapeHtml(data.comment)}"</div>`
                : `<div class="eval-comment-box empty">No written feedback was provided.</div>`;

            return html;
        }
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

        function escapeHtml(str) {
            if (str === null || str === undefined) return '';
            const d = document.createElement('div');
            d.textContent = str;
            return d.innerHTML;
        }

        // Change-password confirm check
        const pwForm = document.getElementById('pwForm');
        if (pwForm) {
            pwForm.addEventListener('submit', function(e) {
                const newPw  = pwForm.querySelector('[name="new_password"]').value;
                const confPw = pwForm.querySelector('[name="confirm_password"]').value;
                if (newPw !== confPw) {
                    e.preventDefault();
                    alert('New password and confirmation do not match.');
                }
            });
        }

        // Mobile sidebar
        document.addEventListener('click', function(e) {
            const sb = document.getElementById('sidebar');
            if (sb.classList.contains('open') && !sb.contains(e.target) && !e.target.closest('.hamburger')) {
                sb.classList.remove('open');
            }
        });
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
                return `<div class="notif-item ${unread ? 'unread' : ''}" data-notification-id="${Number(n.id) || 0}">\n` +
                    `  <div class="notif-icon" style="color:${v.color};background:${v.color}22;"><i class="fa-solid ${v.icon}"></i></div>\n` +
                    `  <div style="flex:1;min-width:0;">\n` +
                    `    <div class="notif-text">${escapeNotificationHtml(n.message)}</div>\n` +
                    `    <div class="notif-meta">${escapeNotificationHtml(n.created_at_label)}</div>\n` +
                    `  </div>\n` +
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