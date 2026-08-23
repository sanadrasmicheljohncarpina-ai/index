<?php
    session_start();
    require_once 'db.php';
require_once '../shared/EvaluationContextService.php';

    // ── ENSURE account_status COLUMN EXISTS ─────────────────────────
    // Same gate manage_privileged_accounts.php uses — only approved accounts
    // should ever be pulled into the evaluation pools below.
    $colStatus = $mysqli->query("SHOW COLUMNS FROM users LIKE 'account_status'");
    if ($colStatus && $colStatus->num_rows === 0) {
        $mysqli->query("ALTER TABLE users ADD COLUMN account_status VARCHAR(10) NOT NULL DEFAULT 'pending'");
        $mysqli->query("UPDATE users SET account_status = 'approved' WHERE account_status = 'pending'");
    }

    // ── ENSURE secondary_role COLUMN EXISTS ──────────────────────────
    // Teacher/Staff accounts register with ONE primary role (users.role).
    // After logging in, a person can self-assign the other role from their
    // own dashboard (e.g. a Teacher who also does Staff work) — that gets
    // recorded here.
    $colSecondary = $mysqli->query("SHOW COLUMNS FROM users LIKE 'secondary_role'");
    if ($colSecondary && $colSecondary->num_rows === 0) {
        $mysqli->query("ALTER TABLE users ADD COLUMN secondary_role VARCHAR(20) NULL DEFAULT NULL");
    }

    // ── KEEP NON-TEACHING STAFF AS AN EA-ONLY EVALUATION CONTEXT ────
    // Non-Teaching Staff remains part of the normal Staff context for
    // student/peer evaluation, but it also has its own EA evaluation context.
    // Do NOT migrate or delete these rows: EA questions are intentionally
    // scoped by eval_type='ea' and target_type='Non-Teaching Staff'.

    // ── SEED DEFAULT CATEGORIES (shared pool) ────────────────────
    $cat_count = $mysqli->query("SELECT COUNT(*) as c FROM question_categories WHERE eval_type='student' AND target_type IN ('Teacher','Staff')")->fetch_assoc()['c'];
    if ($cat_count == 0) {
        $defaults = [
            'Teacher' => ['Teaching Effectiveness','Subject Mastery','Professionalism','Communication','Student Engagement'],
            'Staff'   => ['Service Quality','Work Performance','Professionalism','Communication','Responsiveness'],
        ];
        $ins = $mysqli->prepare("INSERT IGNORE INTO question_categories (target_type, category_name, eval_type, sort_order) VALUES (?,?,'student',?)");
        foreach ($defaults as $type => $cats) {
            foreach ($cats as $i => $cat) { $ins->bind_param("ssi", $type, $cat, $i); $ins->execute(); }
        }
        $ins->close();
    }

    $peer_cat_count = $mysqli->query("SELECT COUNT(*) as c FROM question_categories WHERE eval_type='peer' AND target_type IN ('Teacher','Staff')")->fetch_assoc()['c'];
    if ($peer_cat_count == 0) {
        $peer_defaults = [
            'Teacher' => ['Collaboration','Professionalism','Communication','Initiative','Dependability'],
            'Staff'   => ['Teamwork','Professionalism','Communication','Reliability','Cooperation'],
        ];
        $ins2 = $mysqli->prepare("INSERT IGNORE INTO question_categories (target_type, category_name, eval_type, sort_order) VALUES (?,?,'peer',?)");
        foreach ($peer_defaults as $type => $cats) {
            foreach ($cats as $i => $cat) { $ins2->bind_param("ssi", $type, $cat, $i); $ins2->execute(); }
        }
        $ins2->close();
    }

    // Seed Multi-Role default categories
    $mr_student = $mysqli->query("SELECT COUNT(*) as c FROM question_categories WHERE target_type='Multi-Role' AND eval_type='student'")->fetch_assoc()['c'];
    if ($mr_student == 0) {
        $mr_cats = ['General Performance','Cross-Role Responsibilities','Professionalism','Adaptability','Communication'];
        $ins3 = $mysqli->prepare("INSERT IGNORE INTO question_categories (target_type, category_name, eval_type, sort_order) VALUES ('Multi-Role',?,'student',?)");
        foreach ($mr_cats as $i => $cat) { $ins3->bind_param("si", $cat, $i); $ins3->execute(); }
        $ins3->close();
    }
    $mr_peer = $mysqli->query("SELECT COUNT(*) as c FROM question_categories WHERE target_type='Multi-Role' AND eval_type='peer'")->fetch_assoc()['c'];
    if ($mr_peer == 0) {
        $mr_peer_cats = ['Cross-Role Collaboration','Professionalism','Adaptability','Communication','Initiative'];
        $ins4 = $mysqli->prepare("INSERT IGNORE INTO question_categories (target_type, category_name, eval_type, sort_order) VALUES ('Multi-Role',?,'peer',?)");
        foreach ($mr_peer_cats as $i => $cat) { $ins4->bind_param("si", $cat, $i); $ins4->execute(); }
        $ins4->close();
    }

    // ── ACTIVE EVAL TYPE ─────────────────────────────────────────
    $active_eval = $_GET['eval_type'] ?? $_POST['eval_type'] ?? 'student';
    if (!in_array($active_eval, ['student','peer','school_head','ea'])) $active_eval = 'student';

    // ── CONSTANTS ─────────────────────────────────────────────────
    // Questionnaire contexts are role-based.
    $system_categories      = ['Teacher', 'Staff', 'Multi-Role'];
    $school_head_categories = ['Principal', 'Dean'];
    $ea_categories          = ['Principal', 'Dean', 'Non-Teaching Staff'];
    $active_categories = $active_eval === 'school_head'
        ? $school_head_categories
        : ($active_eval === 'ea' ? $ea_categories : $system_categories);

    // Staff, Principal, and Dean are per-person question sets. Multi-Role
    // is a separate shared question pool and is NEVER created merely because
    // a person has a teaching assignment. Teaching assignments only affect
    // where a person is visible to students.
   $per_user_targets = ['Staff', 'Principal', 'Dean', 'Multi-Role', 'Non-Teaching Staff'];
    // "Staff/non-teaching function present" per the Multi-Role spec — true
    // for anyone whose role or self-assigned secondary role is 'staff'.
    // This is an actual assigned function (role / secondary_role), never
    // guessed from the free-text designation field.
    function hasStaffFunction(array $u): bool {
        return $u['role'] === 'staff' || $u['secondary_role'] === 'staff';
    }

    // Additional-role detection. Personnel Registry stores multiple
    // designations as comma-separated tags. Multi-Role is an additional
    // evaluation context; it does not remove the person from Staff/Teacher.
    function hasAdditionalRole(array $u): bool {
        return ec_has_additional_role($u);
    }

    // Cosmetic label → icon/color for a Staff member's specific job title.
    // Purely for display; it has no bearing on eligibility by itself.
    $staff_subrole_labels = [
        'Registrar'  => ['icon'=>'fa-id-badge',        'color'=>'#60a5fa'],
        'Cashier'    => ['icon'=>'fa-cash-register',    'color'=>'#34d399'],
        'Bookkeeper' => ['icon'=>'fa-book',             'color'=>'#a78bfa'],
        'Librarian'  => ['icon'=>'fa-book-open-reader', 'color'=>'#f472b6'],
        'Guidance'   => ['icon'=>'fa-heart',            'color'=>'#fb923c'],
        'Nurse'      => ['icon'=>'fa-kit-medical',      'color'=>'#f87171'],
        'Personnel'  => ['icon'=>'fa-briefcase',        'color'=>'#94a3b8'],
        'Staff'      => ['icon'=>'fa-briefcase',        'color'=>'#94a3b8'],
    ];

    // getSubRole is cosmetic-only: it picks a badge label from the free-text
    // `designation` field (e.g. "Registrar") to show on a Staff card/row. It
    // has no effect on Teacher/Staff/Multi-Role bucketing.
    function getSubRole($u) {
        $desig = trim($u['designation'] ?? '');
        if ($desig === '') return ($u['role'] === 'teacher') ? 'Teacher' : 'Personnel';
        $old_exact = [
            'Registrar'=>'Registrar','Cashier'=>'Cashier','Bookkeeper'=>'Bookkeeper',
            'Librarian'=>'Librarian','Guidance'=>'Guidance','Nurse'=>'Nurse',
            'Personnel'=>'Personnel','Staff'=>'Staff',
        ];
        $tokens = array_filter(array_map('trim', explode(',', $desig)), fn($t) => $t !== '');
        if (empty($tokens)) $tokens = [$desig];
        $first = $tokens[0];
        if (isset($old_exact[$first])) return $first;
        $lower = strtolower($first);
        foreach (['registrar','cashier','bookkeeper','librarian','guidance','nurse'] as $kw) {
            if (strpos($lower, $kw) !== false) return ucfirst($kw);
        }
        return 'Personnel';
    }

    // ── ACTION HANDLERS ───────────────────────────────────────────
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['form_action'])) {
        $action          = $_POST['form_action'];
        $redirect_target = $_POST['target_type'] ?? 'Teacher';
        $post_eval_type  = $_POST['eval_type'] ?? 'student';
        if (!in_array($post_eval_type, ['student','peer','school_head'])) $post_eval_type = 'student';
        $message         = '';
        $post_user_id    = isset($_POST['user_id']) ? (int)$_POST['user_id'] : 0;
        $is_per_user_post = in_array($redirect_target, $per_user_targets) && $post_user_id > 0;

        // ── PER-USER ACTIONS (Staff, Principal, Dean) ──────────
        if ($action === 'user_insert' && $is_per_user_post) {
            $question_text = trim($_POST['question_text']);
            $category      = trim($_POST['category'] ?? 'General');
            $et            = $post_eval_type;
            $tt            = $redirect_target;
            if (!empty($question_text)) {
                $max = $mysqli->query("SELECT MAX(sort_order) as m FROM user_questions WHERE user_id=$post_user_id AND eval_type='".mysqli_real_escape_string($mysqli,$et)."'")->fetch_assoc()['m'] ?? 0;
                $next = $max + 1;
                $stmt = $mysqli->prepare("INSERT INTO user_questions (user_id, target_type, eval_type, category, question_text, sort_order) VALUES (?,?,?,?,?,?)");
                $stmt->bind_param("issssi", $post_user_id, $tt, $et, $category, $question_text, $next);
                $stmt->execute(); $stmt->close();
                $message = "Question added.";
            }
        }
        if ($action === 'user_update' && $is_per_user_post) {
            $q_id = (int)$_POST['question_id'];
            $question_text = trim($_POST['question_text']);
            if (!empty($question_text)) {
                $stmt = $mysqli->prepare("UPDATE user_questions SET question_text=? WHERE id=? AND user_id=?");
                $stmt->bind_param("sii", $question_text, $q_id, $post_user_id);
                $stmt->execute(); $stmt->close();
                $message = "Question updated.";
            }
        }
        if ($action === 'user_delete' && $is_per_user_post) {
            $q_id = (int)$_POST['question_id'];
            $stmt = $mysqli->prepare("DELETE FROM user_questions WHERE id=? AND user_id=?");
            $stmt->bind_param("ii", $q_id, $post_user_id);
            $stmt->execute(); $stmt->close();
            $message = "Question removed.";
        }
        if ($action === 'user_add_category' && $is_per_user_post) {
            $category_name = trim($_POST['category_name'] ?? '');
            $et = $post_eval_type; $tt = $redirect_target;
            if (!empty($category_name)) {
                $max  = $mysqli->query("SELECT MAX(sort_order) as m FROM user_question_categories WHERE user_id=$post_user_id AND eval_type='".mysqli_real_escape_string($mysqli,$et)."'")->fetch_assoc()['m'] ?? 0;
                $next = $max + 1;
                $stmt = $mysqli->prepare("INSERT IGNORE INTO user_question_categories (user_id, target_type, eval_type, category_name, sort_order) VALUES (?,?,?,?,?)");
                $stmt->bind_param("isssi", $post_user_id, $tt, $et, $category_name, $next);
                $stmt->execute();
                $message = ($stmt->affected_rows > 0)
                    ? "Category \"$category_name\" added."
                    : "Category \"$category_name\" already exists for this person.";
                $stmt->close();
            }
        }
        if ($action === 'user_rename_category' && $is_per_user_post) {
            $cat_id   = (int)$_POST['cat_id'];
            $old_name = trim($_POST['old_name']);
            $new_name = trim($_POST['new_name'] ?? '');
            $et = $post_eval_type;
            if (!empty($new_name) && $new_name !== $old_name) {
                $stmt = $mysqli->prepare("UPDATE user_question_categories SET category_name=? WHERE id=? AND user_id=?");
                $stmt->bind_param("sii", $new_name, $cat_id, $post_user_id); $stmt->execute(); $stmt->close();
                $stmt2 = $mysqli->prepare("UPDATE user_questions SET category=? WHERE category=? AND user_id=? AND eval_type=?");
                $stmt2->bind_param("ssis", $new_name, $old_name, $post_user_id, $et); $stmt2->execute(); $stmt2->close();
                $message = "Category renamed.";
            }
        }
        if ($action === 'user_delete_category' && $is_per_user_post) {
            $cat_id   = (int)$_POST['cat_id'];
            $cat_name = trim($_POST['cat_name']);
            $et = $post_eval_type;
            $general = 'General';
            $stmt = $mysqli->prepare("UPDATE user_questions SET category=? WHERE category=? AND user_id=? AND eval_type=?");
            $stmt->bind_param("ssis", $general, $cat_name, $post_user_id, $et); $stmt->execute(); $stmt->close();
            $stmt2 = $mysqli->prepare("DELETE FROM user_question_categories WHERE id=? AND user_id=?");
            $stmt2->bind_param("ii", $cat_id, $post_user_id); $stmt2->execute(); $stmt2->close();
            $message = "Category deleted. Questions moved to General.";
        }

        // ── SHARED POOL ACTIONS (Teacher + Multi-Role + legacy) ───
        if ($action === 'insert') {
            $target_type   = $_POST['target_type'];
            $question_text = trim($_POST['question_text']);
            $category      = trim($_POST['category'] ?? 'General');
            $et            = $post_eval_type;
            if (!empty($question_text)) {
                $stmt = $mysqli->prepare("INSERT INTO evaluation_questions (target_type, question_text, category, eval_type) VALUES (?,?,?,?)");
                $stmt->bind_param("ssss", $target_type, $question_text, $category, $et);
                $stmt->execute(); $stmt->close();
                $message = "Question added successfully.";
            }
        }
        if ($action === 'update') {
            $q_id = (int)$_POST['question_id'];
            $question_text = trim($_POST['question_text']);
            if (!empty($question_text)) {
                $stmt = $mysqli->prepare("UPDATE evaluation_questions SET question_text=? WHERE id=?");
                $stmt->bind_param("si", $question_text, $q_id);
                $stmt->execute(); $stmt->close();
                $message = "Question updated.";
            }
        }
        if ($action === 'delete') {
            $q_id = (int)$_POST['question_id'];
            $stmt = $mysqli->prepare("DELETE FROM evaluation_questions WHERE id=?");
            $stmt->bind_param("i", $q_id); $stmt->execute(); $stmt->close();
            $message = "Question removed.";
        }
        if ($action === 'add_category') {
            $target_type   = trim($_POST['target_type']);
            $category_name = trim($_POST['category_name'] ?? '');
            $et            = $post_eval_type;
            if (!empty($category_name)) {
                $max  = $mysqli->query("SELECT MAX(sort_order) as m FROM question_categories WHERE target_type='".mysqli_real_escape_string($mysqli,$target_type)."' AND eval_type='$et'")->fetch_assoc()['m'] ?? 0;
                $next = $max + 1;
                $stmt = $mysqli->prepare("INSERT IGNORE INTO question_categories (target_type, category_name, eval_type, sort_order) VALUES (?,?,?,?)");
                $stmt->bind_param("sssi", $target_type, $category_name, $et, $next);
                $stmt->execute(); $stmt->close();
                $message = "Category \"$category_name\" added.";
            }
        }
        if ($action === 'rename_category') {
            $cat_id   = (int)$_POST['cat_id'];
            $old_name = trim($_POST['old_name']);
            $new_name = trim($_POST['new_name'] ?? '');
            $target   = trim($_POST['target_type']);
            $et       = $post_eval_type;
            if (!empty($new_name) && $new_name !== $old_name) {
                $stmt = $mysqli->prepare("UPDATE question_categories SET category_name=? WHERE id=?");
                $stmt->bind_param("si", $new_name, $cat_id); $stmt->execute(); $stmt->close();
                $stmt2 = $mysqli->prepare("UPDATE evaluation_questions SET category=? WHERE category=? AND target_type=? AND eval_type=?");
                $stmt2->bind_param("ssss", $new_name, $old_name, $target, $et); $stmt2->execute(); $stmt2->close();
                $message = "Category renamed.";
            }
        }
        if ($action === 'delete_category') {
            $cat_id   = (int)$_POST['cat_id'];
            $cat_name = trim($_POST['cat_name']);
            $target   = trim($_POST['target_type']);
            $et       = $post_eval_type;
            $general  = 'General';
            $stmt = $mysqli->prepare("UPDATE evaluation_questions SET category=? WHERE category=? AND target_type=? AND eval_type=?");
            $stmt->bind_param("ssss", $general, $cat_name, $target, $et); $stmt->execute(); $stmt->close();
            $stmt2 = $mysqli->prepare("DELETE FROM question_categories WHERE id=?");
            $stmt2->bind_param("i", $cat_id); $stmt2->execute(); $stmt2->close();
            $message = "Category deleted. Questions moved to General.";
        }

        $view    = $_POST['view'] ?? 'manage';
        $uid_str = $post_user_id ? '&user_id='.$post_user_id : '';
        $mr_filter_str = isset($_POST['mr_filter']) ? '&mr_filter='.urlencode($_POST['mr_filter']) : '';
        header("Location: ?view=$view&target=".urlencode($redirect_target)."&eval_type=".urlencode($post_eval_type)."$uid_str$mr_filter_str&msg=".urlencode($message));
        exit();
    }

    // ── VIEWS ─────────────────────────────────────────────────────
    $current_view    = $_GET['view']   ?? 'dashboard';
    $selected_target = $_GET['target'] ?? $active_categories[0];
    if (!in_array($selected_target, $active_categories)) $selected_target = $active_categories[0];
    $selected_user   = isset($_GET['user_id']) ? (int)$_GET['user_id'] : null;
    $mr_filter       = $_GET['mr_filter'] ?? 'all';
    if (!in_array($mr_filter, ['all','teacher','staff'])) $mr_filter = 'all';

    // Is this a per-user target (Staff, Principal, Dean)?
    $is_per_user_target = in_array($selected_target, $per_user_targets);

    // ── FETCH ALL USERS ───────────────────────────────────────────
    $card_data_student    = ['Teacher' => ['count'=>0,'users'=>[]], 'Staff' => ['count'=>0,'users'=>[]], 'Multi-Role' => ['count'=>0,'users'=>[]]];
    $card_data_peer       = ['Teacher' => ['count'=>0,'users'=>[]], 'Staff' => ['count'=>0,'users'=>[]], 'Multi-Role' => ['count'=>0,'users'=>[]]];
    $card_data_schoolhead = ['Principal' => ['count'=>0,'users'=>[]], 'Dean' => ['count'=>0,'users'=>[]]];
    $card_data_ea         = ['Principal' => ['count'=>0,'users'=>[]], 'Dean' => ['count'=>0,'users'=>[]], 'Non-Teaching Staff' => ['count'=>0,'users'=>[]]];

    // For per-user targets, question counts come from user_questions
    // For Multi-Role, question counts come from evaluation_questions
    $res = $mysqli->query("SELECT target_type, eval_type, COUNT(*) as total FROM evaluation_questions GROUP BY target_type, eval_type");
    if ($res) while ($r = $res->fetch_assoc()) {
        if ($r['eval_type'] === 'student'    && isset($card_data_student[$r['target_type']]))    $card_data_student[$r['target_type']]['count']    = $r['total'];
        if ($r['eval_type'] === 'peer'       && isset($card_data_peer[$r['target_type']]))       $card_data_peer[$r['target_type']]['count']       = $r['total'];
        if ($r['eval_type'] === 'school_head' && isset($card_data_schoolhead[$r['target_type']])) $card_data_schoolhead[$r['target_type']]['count'] = $r['total'];
        if ($r['eval_type'] === 'ea' && isset($card_data_ea[$r['target_type']])) $card_data_ea[$r['target_type']]['count'] = $r['total'];
    }

    // Faculty and Staff are now sourced directly from Manage Privileged:
    // approved, active accounts whose role IS teacher or staff. No separate
    // "add evaluator" step exists anymore — this list is always live.
    $ures = $mysqli->query("
        SELECT id, full_name, designation, photo, source, role, secondary_role,
               COALESCE(source, 'login') as src
        FROM users
        WHERE role IN ('teacher','staff','faculty')
          AND is_active = 1
          AND (account_status = 'approved' OR source = 'admin_nologin')
        ORDER BY full_name ASC
    ");
    $all_users = [];
    if ($ures) while ($u = $ures->fetch_assoc()) $all_users[] = $u;

    $sh_res = $mysqli->query("
        SELECT id, full_name, designation, photo, source, role, secondary_role
        FROM users
        WHERE role IN ('principal','dean')
          AND is_active = 1
          AND account_status = 'approved'
        ORDER BY full_name ASC
    ");
    $school_head_users = [];
    if ($sh_res) while ($u = $sh_res->fetch_assoc()) $school_head_users[] = $u;

    // Non-Teaching Staff = active Staff users with no teaching/year-level assignment.
    // They may still have additional roles; that does not remove their Staff context.
    $nts_res = $mysqli->query("
        SELECT u.id, u.full_name, u.designation, u.photo, u.source, u.role, u.secondary_role
        FROM users u
        WHERE u.role='staff'
          AND u.is_active=1
          AND (u.account_status='approved' OR u.source='admin_nologin')
          AND NOT EXISTS (
              SELECT 1 FROM user_year_levels uyl WHERE uyl.user_id=u.id
          )
        ORDER BY u.full_name ASC
    ");
    $non_teaching_staff_users = [];
    if ($nts_res) while ($u = $nts_res->fetch_assoc()) $non_teaching_staff_users[] = $u;

    // ── PLACE EACH USER INTO EVALUATION CONTEXTS ──────────────────
    // Staff is never removed just because the person also has another role.
    foreach ($all_users as $u) {
        $has_teacher   = ec_has_teacher_function($u);
        $has_staff     = ec_has_staff_function($u);
        // Multi-Role is an additional responsibility/context. It is not
        // inferred from teaching assignments. A Teacher+Staff account is
        // still Multi-Role because it explicitly carries an additional base
        // function; otherwise use the configured designation/secondary role.
        $is_multi_role = hasAdditionalRole($u);
        if ($has_teacher) {
            $card_data_student['Teacher']['users'][] = $u;
            $card_data_peer['Teacher']['users'][] = $u;
        }
        if ($has_staff) {
            $card_data_student['Staff']['users'][] = $u;
            $card_data_peer['Staff']['users'][] = $u;
        }
        if ($is_multi_role) {
            $card_data_student['Multi-Role']['users'][] = $u;
            $card_data_peer['Multi-Role']['users'][] = $u;
        }
    }

    // School Head tab is Principal/Dean only — built from its own user
    // source, not the Teacher/Staff pool above.
    $card_data_schoolhead = ['Principal' => ['count'=>0,'users'=>[]], 'Dean' => ['count'=>0,'users'=>[]]];
    foreach ($school_head_users as $u) {
        if ($u['role'] === 'principal') $card_data_schoolhead['Principal']['users'][] = $u;
        if ($u['role'] === 'dean')      $card_data_schoolhead['Dean']['users'][]      = $u;
        if ($u['role'] === 'principal') $card_data_ea['Principal']['users'][] = $u;
        if ($u['role'] === 'dean')      $card_data_ea['Dean']['users'][]      = $u;
    }
    foreach ($non_teaching_staff_users as $u) {
        $card_data_ea['Non-Teaching Staff']['users'][] = $u;
    }

    if ($active_eval === 'peer') {
        $card_data = $card_data_peer;
    } elseif ($active_eval === 'school_head') {
        $card_data = $card_data_schoolhead;
    } elseif ($active_eval === 'ea') {
        $card_data = $card_data_ea;
    } else {
        $card_data = $card_data_student;
    }

    // Per-user question counts (for sidebar badges) — used by Staff,
    // Principal, and Dean.
    // Keyed by target_type as well as eval_type so a count never leaks
    // from one designation into the other.
    $user_q_counts = [];
    $uqres = $mysqli->query("SELECT user_id, target_type, eval_type, COUNT(*) as total FROM user_questions GROUP BY user_id, target_type, eval_type");
    if ($uqres) while ($uqr = $uqres->fetch_assoc()) {
        $user_q_counts[$uqr['user_id']][$uqr['target_type']][$uqr['eval_type']] = $uqr['total'];
    }

    // ── MANAGE VIEW DATA ──────────────────────────────────────────
    $categories_list      = [];
    $questions_list       = [];
    $user_categories_list = [];
    $user_questions_list  = [];
    $target_users         = [];
    $selected_user_data   = null;

    if (in_array($current_view, ['manage','user_questions'])) {
        // Shared pool categories + questions (used for Teacher + Multi-Role)
        $cres = $mysqli->prepare("SELECT * FROM question_categories WHERE target_type=? AND eval_type=? ORDER BY sort_order, category_name");
        $cres->bind_param("ss", $selected_target, $active_eval); $cres->execute();
        $categories_list = $cres->get_result()->fetch_all(MYSQLI_ASSOC); $cres->close();

        $stmt = $mysqli->prepare("SELECT * FROM evaluation_questions WHERE target_type=? AND eval_type=? ORDER BY category, id");
        $stmt->bind_param("ss", $selected_target, $active_eval); $stmt->execute();
        $questions_list = $stmt->get_result()->fetch_all(MYSQLI_ASSOC); $stmt->close();

        $all_target_users = $card_data[$selected_target]['users'] ?? [];
        if ($selected_target === 'Multi-Role' && $mr_filter !== 'all') {
            $target_users = array_values(array_filter($all_target_users, fn($u) => ($mr_filter === 'teacher' ? ec_has_teacher_function($u) : ec_has_staff_function($u))));
        } else {
            $target_users = $all_target_users;
        }

        if ($selected_user && $is_per_user_target) {
            $ur = $mysqli->prepare("SELECT id, full_name, designation, photo, source, role, secondary_role FROM users WHERE id=? LIMIT 1");
            $ur->bind_param("i", $selected_user); $ur->execute();
            $selected_user_data = $ur->get_result()->fetch_assoc(); $ur->close();

            // Per-user categories (scoped to the current designation)
            $ucres = $mysqli->prepare("SELECT * FROM user_question_categories WHERE user_id=? AND target_type=? AND eval_type=? ORDER BY sort_order, category_name");
            $ucres->bind_param("iss", $selected_user, $selected_target, $active_eval); $ucres->execute();
            $user_categories_list = $ucres->get_result()->fetch_all(MYSQLI_ASSOC); $ucres->close();

            // Per-user questions (same scoping)
            $uqstmt = $mysqli->prepare("SELECT * FROM user_questions WHERE user_id=? AND target_type=? AND eval_type=? ORDER BY category, sort_order, id");
            $uqstmt->bind_param("iss", $selected_user, $selected_target, $active_eval); $uqstmt->execute();
            $user_questions_list = $uqstmt->get_result()->fetch_all(MYSQLI_ASSOC); $uqstmt->close();

        } elseif ($selected_user && !$is_per_user_target) {
            $ur = $mysqli->prepare("SELECT id, full_name, designation, photo, source, role, secondary_role FROM users WHERE id=? LIMIT 1");
            $ur->bind_param("i", $selected_user); $ur->execute();
            $selected_user_data = $ur->get_result()->fetch_assoc(); $ur->close();
        }
    }

    $icons = [
        'Teacher'             => 'fa-chalkboard-user',
        'Staff'               => 'fa-briefcase',
        'Multi-Role'          => 'fa-layer-group',
        'Principal'           => 'fa-user-tie',
        'Dean'                => 'fa-graduation-cap',
        'Non-Teaching Staff'   => 'fa-users-gear',
    ];
    $eval_theme = [
        'student'     => ['label' => 'Student Evaluation',      'color' => '#3B82F6', 'bg' => 'rgba(59,130,246,.07)',  'border' => 'rgba(59,130,246,.22)', 'desc' => 'Students evaluate teacher and staff they interact with.'],
        'peer'        => ['label' => 'Peer-to-Peer Evaluation',  'color' => '#7C3AED', 'bg' => 'rgba(124,58,237,.07)', 'border' => 'rgba(124,58,237,.22)', 'desc' => 'Teacher and staff evaluate colleagues they work with directly.'],
        'school_head' => ['label' => 'School Head Evaluation',   'color' => '#D97706', 'bg' => 'rgba(217,119,6,.08)',  'border' => 'rgba(217,119,6,.24)',  'desc' => 'The School Head evaluates teacher and staff under their supervision.'],
        'ea'          => ['label' => 'EA Evaluation',              'color' => '#7C3AED', 'bg' => 'rgba(124,58,237,.08)', 'border' => 'rgba(124,58,237,.24)', 'desc' => 'The Executive Assistant evaluates the active Principal, Dean, and non-teaching staff for the current evaluation period.'],
    ];
    $eval_label        = $eval_theme[$active_eval]['label'];
    $eval_color        = $eval_theme[$active_eval]['color'];
    $eval_color_bg     = $eval_theme[$active_eval]['bg'];
    $eval_color_border = $eval_theme[$active_eval]['border'];
    $eval_desc         = $eval_theme[$active_eval]['desc'];

    $mr_all_users     = $card_data['Multi-Role']['users'] ?? [];
    $mr_teacher_count = count(array_filter($mr_all_users, fn($u) => $u['role'] === 'teacher'));
    $mr_staff_count   = count(array_filter($mr_all_users, fn($u) => $u['role'] === 'staff'));

    // Small helper: does this row hold a role beyond their primary one?
    function secondaryRoleLabel($u) {
        if (empty($u['secondary_role'])) return null;
        return $u['secondary_role'] === 'teacher' ? 'Teacher' : ($u['secondary_role'] === 'staff' ? 'Staff' : null);
    }
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
    <meta charset="UTF-8"/>
    <meta name="viewport" content="width=device-width, initial-scale=1"/>
    <title>Questionnaire — PBI Admin</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css"/>
    <link href="https://fonts.googleapis.com/css2?family=Rajdhani:wght@600;700&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet"/>
    <style>
    :root{
      --page-bg:#19365A;--card-bg:#1F3E64;--card-border:#2E4F74;
      --text-dark:#EAF0F9;--text-dim:#9FB2C9;--track-bg:#2E4F74;
      --radius:10px;--card-shadow:0 1px 2px rgba(0,0,0,.15),0 4px 12px rgba(0,0,0,.18);
      --danger:#F87171;
      --eval-color:<?= $eval_color ?>;
      --eval-bg:<?= $eval_color_bg ?>;
      --eval-border:<?= $eval_color_border ?>;
      --mr:#D97706;--mr-bg:rgba(217,119,6,.08);--mr-border:rgba(217,119,6,.24);
      --staff:#7C3AED;--staff-bg:rgba(124,58,237,.07);--staff-border:rgba(124,58,237,.22);
    }
    *,*::before,*::after{box-sizing:border-box;margin:0;padding:0;}
    body{font-family:'Inter',sans-serif;background:var(--page-bg);color:var(--text-dark);min-height:100vh;padding:36px 28px;}
    .toast{display:flex;align-items:center;gap:10px;background:#E4F7F0;border:1px solid #BEEBD8;border-radius:8px;padding:12px 18px;font-size:14px;color:#0D7A4E;margin-bottom:24px;box-shadow:var(--card-shadow);}

    /* ── EVAL SWITCHER ── */
    .eval-switcher{display:flex;gap:0;background:var(--card-bg);border:1px solid var(--card-border);border-radius:12px;overflow:hidden;margin-bottom:28px;width:fit-content;box-shadow:var(--card-shadow);}
    .eval-tab{display:flex;align-items:center;gap:9px;padding:13px 26px;font-size:14px;font-weight:600;cursor:pointer;text-decoration:none;color:var(--text-dim);border:none;background:none;font-family:'Inter',sans-serif;transition:all .22s;position:relative;}
    .eval-tab:hover{color:var(--text-dark);background:var(--page-bg);}
    .eval-tab.active-student{color:#3B82F6;background:rgba(59,130,246,.07);}
    .eval-tab.active-peer{color:#7C3AED;background:rgba(124,58,237,.07);}
    .eval-tab.active-schoolhead{color:#D97706;background:rgba(217,119,6,.07);}
    .eval-tab.active-student::after,.eval-tab.active-peer::after,.eval-tab.active-schoolhead::after{content:'';position:absolute;bottom:0;left:0;right:0;height:2px;border-radius:2px 2px 0 0;}
    .eval-tab.active-student::after{background:#3B82F6;}
    .eval-tab.active-peer::after{background:#7C3AED;}
    .eval-tab.active-schoolhead::after{background:#D97706;}
    .eval-tab .tab-badge{font-size:10px;font-weight:700;padding:2px 8px;border-radius:20px;background:var(--page-bg);color:var(--text-dim);}
    .eval-tab.active-student .tab-badge{background:rgba(59,130,246,.15);color:#3B82F6;}
    .eval-tab.active-peer .tab-badge{background:rgba(124,58,237,.15);color:#7C3AED;}
    .eval-tab.active-schoolhead .tab-badge{background:rgba(217,119,6,.15);color:#D97706;}
    .eval-divider{width:1px;background:var(--card-border);margin:8px 0;}

    /* ── PAGE HEADER ── */
    .page-header{display:flex;justify-content:space-between;align-items:flex-start;margin-bottom:24px;padding-bottom:20px;border-bottom:1px solid var(--card-border);}
    .page-header h1{font-family:'Rajdhani',sans-serif;font-size:30px;font-weight:700;color:var(--text-dark);margin-bottom:4px;}
    .page-header p{font-size:13px;color:var(--text-dim);}
    .btn{display:inline-flex;align-items:center;gap:7px;padding:10px 20px;border-radius:var(--radius);font-size:13px;font-weight:600;cursor:pointer;border:none;text-decoration:none;transition:all .2s;font-family:'Inter',sans-serif;}
    .btn-primary{background:var(--eval-color);color:#fff;box-shadow:var(--card-shadow);}
    .btn-primary:hover{opacity:.88;}
    .btn-back{background:var(--card-bg);color:var(--text-dark);border:1px solid var(--card-border);}
    .btn-back:hover{background:var(--page-bg);border-color:#C7D2E3;}

    /* ── DASHBOARD CARDS ── */
    .sector-row{display:grid;grid-template-columns:repeat(auto-fill,minmax(300px,1fr));gap:20px;margin-bottom:32px;}
    .sector-card{background:var(--card-bg);border:1px solid var(--card-border);border-top:4px solid var(--eval-color);border-radius:14px;padding:24px;display:flex;flex-direction:column;gap:14px;box-shadow:var(--card-shadow);transition:transform .2s,box-shadow .2s;cursor:pointer;}
    .sector-card:hover{transform:translateY(-3px);box-shadow:0 10px 24px rgba(15,23,42,.1);}
    .sector-card.staff-card{border-top-color:var(--staff);}
    .sector-card.multi-role-card{border-top-color:var(--mr);}
    .sector-card-top{display:flex;justify-content:space-between;align-items:center;}
    .sector-label{font-family:'Rajdhani',sans-serif;font-size:22px;font-weight:700;display:flex;align-items:center;gap:8px;}
    .sector-label.dark{color:var(--text-dark);}
    .sector-label.staff-color{color:var(--staff);}
    .sector-label.mr-color{color:var(--mr);}
    .sector-badge{color:#fff;font-size:11px;font-weight:700;padding:3px 10px;border-radius:20px;}
    .sector-badge.student{background:#3B82F6;}
    .sector-badge.peer{background:#7C3AED;}
    .sector-badge.staff-b{background:var(--staff);}
    .sector-badge.multi{background:var(--eval-color);}
    .sector-badge.per-user-badge{background:var(--page-bg);color:var(--text-dark);font-size:10px;}
    .sector-badge.per-user-badge-staff{background:var(--staff-bg);color:var(--staff);}
    .card-avatars{display:flex;align-items:center;}
    .card-avatar{width:30px;height:30px;border-radius:50%;object-fit:cover;border:2px solid var(--card-bg);margin-left:-8px;box-shadow:0 0 0 1px var(--card-border);}
    .card-avatar:first-child{margin-left:0;}
    .card-avatar-ph{width:30px;height:30px;border-radius:50%;background:var(--page-bg);border:2px solid var(--card-bg);box-shadow:0 0 0 1px var(--card-border);display:flex;align-items:center;justify-content:center;color:var(--text-dim);font-size:12px;margin-left:-8px;}
    .card-avatar-ph:first-child{margin-left:0;}
    .card-avatar-ph.staff-ph{background:var(--staff-bg);color:var(--staff);}
    .card-avatar-ph.mr-ph{background:var(--mr-bg);color:var(--mr);}
    .card-avatar-more{width:30px;height:30px;border-radius:50%;background:#0F2740;border:2px solid var(--card-bg);display:flex;align-items:center;justify-content:center;color:#fff;font-size:10px;font-weight:700;margin-left:-8px;}
    .card-avatar-more.staff-more{background:var(--staff);}
    .card-avatar-more.mr-more{background:var(--mr);}
    .subrole-chips{display:flex;gap:5px;flex-wrap:wrap;}
    .subrole-chip{font-size:10px;font-weight:700;padding:2px 8px;border-radius:20px;display:inline-flex;align-items:center;gap:4px;background:var(--staff-bg);color:var(--staff);border:1px solid var(--staff-border);}
    .mr-role-chips{display:flex;gap:6px;flex-wrap:wrap;}
    .mr-role-chip{font-size:10px;font-weight:700;padding:2px 9px;border-radius:20px;display:inline-flex;align-items:center;gap:4px;}
    .mr-role-chip.fac{background:rgba(59,130,246,.1);color:#3B82F6;border:1px solid rgba(59,130,246,.25);}
    .mr-role-chip.sta{background:var(--staff-bg);color:var(--staff);border:1px solid var(--staff-border);}
    .sector-meta{display:flex;gap:16px;flex-wrap:wrap;}
    .sector-stat-lbl{font-size:11px;text-transform:uppercase;letter-spacing:.5px;}
    .sector-stat-lbl.dark{color:var(--text-dim);}
    .sector-stat-lbl.staff-l{color:var(--staff);opacity:.75;}
    .sector-stat-lbl.mr-l{color:var(--mr);opacity:.75;}
    .sector-stat-val{font-size:18px;font-weight:700;}
    .sector-stat-val.dark{color:var(--text-dark);}
    .sector-stat-val.staff-v{color:var(--staff);}
    .sector-stat-val.mr-v{color:var(--mr);}
    .sector-stat-val.muted-v{font-size:14px;color:var(--text-dim);}
    .sector-actions{display:flex;gap:8px;}
    .btn-sector{flex:1;padding:9px 0;border:none;border-radius:7px;font-family:'Inter',sans-serif;font-size:13px;font-weight:700;cursor:pointer;transition:all .2s;}
    .btn-view-dark{background:var(--eval-color);color:#fff;}
    .btn-view-dark:hover{opacity:.88;}
    .btn-edit-light{background:var(--page-bg);color:var(--text-dark);border:1px solid var(--card-border);}
    .btn-edit-light:hover{background:#E4E9F2;}
    .btn-view-staff{background:var(--staff);color:#fff;}
    .btn-view-staff:hover{opacity:.88;}
    .btn-edit-staff{background:var(--staff-bg);color:var(--staff);border:1px solid var(--staff-border);}
    .btn-edit-staff:hover{background:rgba(124,58,237,.14);}
    .btn-view-mr{background:var(--mr);color:#fff;}
    .btn-view-mr:hover{opacity:.88;}
    .btn-edit-mr{background:var(--mr-bg);color:var(--mr);border:1px solid var(--mr-border);}
    .btn-edit-mr:hover{background:rgba(217,119,6,.16);}

    /* Per-user indicator on dashboard card */
    .per-user-pill{display:inline-flex;align-items:center;gap:5px;background:rgba(59,130,246,.08);border:1px solid rgba(59,130,246,.22);border-radius:20px;padding:3px 10px;font-size:10px;font-weight:700;color:#3B82F6;}
    .per-user-pill.staff-pill{background:var(--staff-bg);border-color:var(--staff-border);color:var(--staff);}

    /* Secondary-role badge (Multi-Role indicator inline on a row/header) */
    .secondary-role-tag{display:inline-flex;align-items:center;gap:4px;font-size:9px;font-weight:700;padding:1px 7px;border-radius:20px;background:var(--mr-bg);color:var(--mr);border:1px solid var(--mr-border);flex-shrink:0;}

    /* ── MANAGE LAYOUT ── */
    .manage-layout{display:grid;grid-template-columns:300px 1fr;gap:20px;align-items:start;}
    .sidebar{background:var(--card-bg);border:1px solid var(--card-border);border-radius:14px;overflow:hidden;position:sticky;top:20px;box-shadow:var(--card-shadow);}
    .sidebar-title{padding:14px 18px;font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:1.2px;color:var(--text-dim);border-bottom:1px solid var(--card-border);display:flex;align-items:center;gap:7px;}
    .sidebar-count{background:var(--page-bg);border-radius:20px;padding:1px 8px;font-size:10px;font-weight:700;margin-left:auto;}
    .per-user-mode-notice{padding:10px 14px;background:rgba(59,130,246,.05);border-bottom:1px solid rgba(59,130,246,.15);font-size:11px;color:#3B82F6;display:flex;align-items:center;gap:6px;}
    .per-user-mode-notice.staff-notice{background:var(--staff-bg);border-color:var(--staff-border);color:var(--staff);}
    .mr-filter-tabs{display:flex;border-bottom:1px solid var(--card-border);}
    .mr-filter-tab{flex:1;padding:9px 0;text-align:center;font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.8px;cursor:pointer;text-decoration:none;color:var(--text-dim);transition:all .2s;border:none;background:none;}
    .mr-filter-tab:hover{color:var(--text-dark);}
    .mr-filter-tab.active-all{color:var(--mr);background:var(--mr-bg);border-bottom:2px solid var(--mr);}
    .mr-filter-tab.active-teacher{color:#3B82F6;background:rgba(59,130,246,.07);border-bottom:2px solid #3B82F6;}
    .mr-filter-tab.active-staff{color:var(--staff);background:var(--staff-bg);border-bottom:2px solid var(--staff);}
    .mr-filter-count{font-size:9px;padding:1px 5px;border-radius:20px;background:rgba(0,0,0,.06);margin-left:3px;}
    .user-list{list-style:none;max-height:68vh;overflow-y:auto;}
    .user-list::-webkit-scrollbar{width:4px;}
    .user-list::-webkit-scrollbar-track{background:transparent;}
    .user-list::-webkit-scrollbar-thumb{background:var(--card-border);border-radius:4px;}
    .user-list-item{display:flex;align-items:center;gap:10px;padding:11px 14px;cursor:pointer;border-bottom:1px solid var(--card-border);transition:background .18s;position:relative;}
    .user-list-item:last-child{border-bottom:none;}
    .user-list-item:hover{background:var(--page-bg);}
    .user-list-item.active{background:rgba(59,130,246,.06);border-left:3px solid var(--eval-color);}
    .user-list-item.active.staff-active{background:var(--staff-bg);border-left-color:var(--staff);}
    .user-list-item.active.mr-active{background:var(--mr-bg);border-left-color:var(--mr);}
    .user-list-avatar{width:36px;height:36px;border-radius:50%;object-fit:cover;border:2px solid var(--card-border);flex-shrink:0;}
    .user-list-avatar-ph{width:36px;height:36px;border-radius:50%;background:var(--page-bg);border:2px solid var(--card-border);display:flex;align-items:center;justify-content:center;color:var(--text-dim);font-size:14px;flex-shrink:0;}
    .user-list-name{font-size:13px;font-weight:600;color:var(--text-dark);line-height:1.3;}
    .user-list-desig{font-size:10px;color:var(--text-dim);overflow:hidden;text-overflow:ellipsis;white-space:nowrap;max-width:130px;}
    .user-list-empty{padding:20px 16px;font-size:12px;color:var(--text-dim);font-style:italic;text-align:center;line-height:1.6;}
    .source-dot{width:7px;height:7px;border-radius:50%;flex-shrink:0;}
    .source-dot.login{background:#059669;}
    .source-dot.nologin{background:#D97706;}

    /* Per-user Q badge — shows individual question count */
    .q-count-badge{border:1px solid var(--eval-border);color:var(--eval-color);font-size:10px;font-weight:700;padding:2px 7px;border-radius:20px;white-space:nowrap;flex-shrink:0;background:var(--eval-bg);}
    .q-count-badge.staff-q{border-color:var(--staff-border);color:var(--staff);background:var(--staff-bg);}
    .q-count-badge.mr-q{border-color:var(--mr-border);color:var(--mr);background:var(--mr-bg);}
    .q-count-badge.no-q{border-color:var(--card-border);color:var(--text-dim);background:transparent;}

    .subrole-mini{font-size:9px;font-weight:700;padding:1px 6px;border-radius:20px;flex-shrink:0;background:var(--staff-bg);color:var(--staff);border:1px solid var(--staff-border);}
    .role-mini-badge{font-size:9px;font-weight:700;padding:1px 6px;border-radius:20px;flex-shrink:0;}
    .role-mini-badge.fac{background:rgba(59,130,246,.12);color:#3B82F6;}
    .role-mini-badge.sta{background:var(--staff-bg);color:var(--staff);}
    .sidebar-legend{display:flex;gap:12px;padding:10px 14px;background:var(--page-bg);border-top:1px solid var(--card-border);font-size:10px;color:var(--text-dim);}
    .legend-item{display:flex;align-items:center;gap:5px;}
    .legend-dot{width:7px;height:7px;border-radius:50%;}

    /* ── CONTENT PANEL ── */
    .content-panel{background:var(--card-bg);border:1px solid var(--card-border);border-radius:14px;padding:28px;box-shadow:var(--card-shadow);}
    .mr-notice{background:var(--mr-bg);border:1px solid var(--mr-border);border-radius:10px;padding:14px 18px;margin-bottom:20px;display:flex;gap:12px;align-items:flex-start;}
    .mr-notice i{color:var(--mr);flex-shrink:0;margin-top:2px;}
    .mr-notice p{font-size:13px;color:var(--text-dim);line-height:1.6;}
    .shared-banner{background:var(--eval-bg);border:1px solid var(--eval-border);border-radius:10px;padding:14px 18px;margin-bottom:20px;display:flex;gap:12px;align-items:flex-start;}
    .shared-banner i{color:var(--eval-color);flex-shrink:0;margin-top:2px;}
    .shared-banner p{font-size:13px;color:var(--text-dim);line-height:1.6;}

    /* ── PER-USER MODE BANNER ── */
    .per-user-banner{border-radius:10px;padding:14px 18px;margin-bottom:20px;display:flex;gap:12px;align-items:flex-start;}
    .per-user-banner.teacher-pu{background:rgba(59,130,246,.05);border:1px solid rgba(59,130,246,.18);}
    .per-user-banner.staff-pu{background:var(--staff-bg);border:1px solid var(--staff-border);}
    .per-user-banner i{flex-shrink:0;margin-top:2px;}
    .per-user-banner p{font-size:13px;color:var(--text-dim);line-height:1.6;}

    .user-header{display:flex;align-items:center;gap:16px;padding:16px 20px;background:var(--page-bg);border-radius:10px;margin-bottom:18px;border:1px solid var(--card-border);}
    .user-header-avatar{width:54px;height:54px;border-radius:50%;object-fit:cover;border:2px solid var(--eval-color);}
    .user-header-avatar.staff-av{border-color:var(--staff);}
    .user-header-avatar.mr-av{border-color:var(--mr);}
    .user-header-avatar-ph{width:54px;height:54px;border-radius:50%;background:var(--card-bg);border:2px solid var(--card-border);display:flex;align-items:center;justify-content:center;color:var(--text-dim);font-size:22px;}
    .user-header-name{font-family:'Rajdhani',sans-serif;font-size:20px;font-weight:700;color:var(--text-dark);}
    .user-header-desig{font-size:11px;color:var(--text-dim);margin-top:2px;line-height:1.5;}
    .user-header-right{margin-left:auto;display:flex;flex-direction:column;align-items:flex-end;gap:5px;}
    .user-header-tag{background:var(--eval-bg);border:1px solid var(--eval-border);color:var(--eval-color);font-size:11px;font-weight:700;padding:4px 12px;border-radius:20px;}
    .user-header-tag.staff-tag{background:var(--staff-bg);border-color:var(--staff-border);color:var(--staff);}
    .user-header-tag.mr-tag{background:var(--mr-bg);border-color:var(--mr-border);color:var(--mr);}
    .source-tag{font-size:10px;padding:2px 9px;border-radius:20px;font-weight:600;}
    .source-tag.login{background:rgba(5,150,105,.1);color:#059669;border:1px solid rgba(5,150,105,.25);}
    .source-tag.nologin{background:rgba(217,119,6,.1);color:#D97706;border:1px solid rgba(217,119,6,.25);}
    .desig-tag-row{display:flex;flex-wrap:wrap;gap:4px;margin-top:4px;}
    .desig-tag{font-size:10px;font-weight:600;padding:2px 8px;border-radius:20px;background:var(--mr-bg);color:var(--mr);border:1px solid var(--mr-border);}

    .cat-manager{background:var(--page-bg);border:1px solid var(--card-border);border-radius:10px;padding:18px;margin-bottom:20px;}
    .cat-manager-title{font-size:12px;font-weight:700;text-transform:uppercase;letter-spacing:1px;color:var(--eval-color);display:flex;align-items:center;gap:7px;margin-bottom:14px;}
    .cat-manager-title.staff-ct{color:var(--staff);}
    .cat-manager-title.mr-ct{color:var(--mr);}
    .cat-chips{display:flex;flex-wrap:wrap;gap:8px;margin-bottom:12px;}
    .cat-chip{display:inline-flex;align-items:center;gap:6px;background:var(--eval-bg);border:1px solid var(--eval-border);border-radius:20px;padding:5px 8px 5px 12px;font-size:12px;font-weight:600;color:var(--eval-color);}
    .cat-chip.staff-chip{background:var(--staff-bg);border-color:var(--staff-border);color:var(--staff);}
    .cat-chip.mr-chip{background:var(--mr-bg);border-color:var(--mr-border);color:var(--mr);}
    .cat-chip-btn{background:none;border:none;cursor:pointer;font-size:11px;padding:2px 4px;border-radius:4px;color:var(--text-dim);transition:color .2s;}
    .cat-chip-btn.edit:hover{color:var(--eval-color);}
    .cat-chip-btn.del:hover{color:var(--danger);}
    .cat-add-row{display:flex;gap:8px;}
    .field{background:var(--card-bg);border:1px solid var(--card-border);color:var(--text-dark);padding:9px 13px;border-radius:8px;font-size:13px;font-family:'Inter',sans-serif;outline:none;transition:border-color .2s;}
    .field:focus{border-color:var(--eval-color);}
    .field-grow{flex:1;}
    .btn-sm{padding:9px 16px;border:none;border-radius:8px;font-weight:700;font-size:13px;cursor:pointer;display:flex;align-items:center;gap:6px;transition:opacity .2s;font-family:'Inter',sans-serif;}
    .btn-eval{background:var(--eval-color);color:#fff;}
    .btn-eval:hover{opacity:.88;}
    .btn-staff{background:var(--staff);color:#fff;}
    .btn-staff:hover{opacity:.88;}
    .btn-mr{background:var(--mr);color:#fff;}
    .btn-mr:hover{opacity:.88;}

    .add-form-row{display:flex;gap:12px;align-items:stretch;background:var(--page-bg);border:1px solid var(--card-border);border-radius:10px;padding:18px;margin-bottom:24px;flex-wrap:wrap;}
    .add-form-row select{background:var(--card-bg);border:1px solid var(--card-border);color:var(--eval-color);padding:11px 14px;border-radius:8px;font-size:13px;font-family:'Inter',sans-serif;outline:none;min-width:180px;cursor:pointer;font-weight:600;}
    .add-form-row select:focus{border-color:var(--eval-color);}
    .add-form-row input[type="text"]{background:var(--card-bg);border:1px solid var(--card-border);color:var(--text-dark);padding:11px 14px;border-radius:8px;font-size:14px;font-family:'Inter',sans-serif;outline:none;flex:1;min-width:200px;}
    .add-form-row input[type="text"]:focus{border-color:var(--eval-color);}
    .add-form-row input::placeholder{color:#A6B2C4;}

    .questions-section{margin-bottom:28px;}
    .section-heading{font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:1.5px;color:var(--eval-color);margin-bottom:12px;padding-bottom:6px;border-bottom:1px solid var(--eval-border);display:flex;align-items:center;gap:8px;}
    .section-heading.staff-sh{color:var(--staff);border-color:var(--staff-border);}
    .section-heading.mr-sh{color:var(--mr);border-color:var(--mr-border);}
    .q-table{width:100%;border-collapse:collapse;}
    .q-table th{font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.8px;color:var(--text-dim);padding:10px 14px;background:var(--page-bg);border-bottom:1px solid var(--card-border);text-align:left;}
    .q-table td{padding:12px 14px;border-bottom:1px solid var(--card-border);vertical-align:middle;}
    .q-table tr:last-child td{border-bottom:none;}
    .q-table tr:hover td{background:var(--page-bg);}
    .q-num{font-size:13px;font-weight:700;color:var(--text-dim);}
    .inline-input{background:transparent;border:1px solid transparent;color:var(--text-dark);width:100%;padding:7px 9px;font-size:14px;font-family:'Inter',sans-serif;border-radius:6px;outline:none;transition:all .2s;}
    .inline-input:focus{background:var(--card-bg);border-color:var(--eval-color);box-shadow:0 0 0 3px var(--eval-bg);}
    .action-btns{display:flex;justify-content:center;gap:10px;}
    .icon-btn{background:none;border:none;cursor:pointer;font-size:16px;padding:5px 8px;border-radius:6px;transition:background .15s;}
    .icon-btn:hover{background:var(--page-bg);}
    .save-btn{color:var(--eval-color);}
    .del-btn{color:var(--danger);}
    .empty-state{text-align:center;padding:48px 20px;color:var(--text-dim);}
    .empty-state i{font-size:36px;margin-bottom:14px;opacity:.4;display:block;}
    .pick-prompt{text-align:center;padding:48px 20px;color:var(--text-dim);}
    .pick-prompt i{font-size:48px;margin-bottom:16px;display:block;opacity:.2;}
    .pick-prompt p{font-size:14px;line-height:1.6;}

    /* No-user selected for per-user targets */
    .select-person-prompt{display:flex;flex-direction:column;align-items:center;justify-content:center;padding:60px 20px;text-align:center;gap:16px;}
    .select-person-prompt .prompt-icon{width:72px;height:72px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:28px;margin-bottom:4px;}
    .select-person-prompt h3{font-family:'Rajdhani',sans-serif;font-size:22px;font-weight:700;color:var(--text-dark);}
    .select-person-prompt p{font-size:13px;color:var(--text-dim);max-width:320px;line-height:1.7;}
    .select-person-prompt.teacher-prompt .prompt-icon{background:rgba(59,130,246,.08);color:#3B82F6;border:1px solid rgba(59,130,246,.22);}
    .select-person-prompt.staff-prompt .prompt-icon{background:var(--staff-bg);color:var(--staff);border:1px solid var(--staff-border);}
    .select-person-prompt.generic-prompt .prompt-icon{background:var(--eval-bg);color:var(--eval-color);border:1px solid var(--eval-border);}

    /* Modal */
    .modal-overlay{position:fixed;inset:0;background:rgba(15,23,42,.55);z-index:200;display:none;align-items:center;justify-content:center;padding:20px;}
    .modal-overlay.open{display:flex;}
    .modal{background:var(--card-bg);border:1px solid var(--card-border);border-radius:14px;padding:28px;width:100%;max-width:400px;box-shadow:0 24px 64px rgba(15,23,42,.35);}
    .modal-title{font-family:'Rajdhani',sans-serif;font-size:20px;font-weight:700;color:var(--text-dark);margin-bottom:6px;}
    .modal-sub{font-size:13px;color:var(--text-dim);margin-bottom:18px;}
    .modal-input{width:100%;padding:11px 13px;background:var(--page-bg);border:1px solid var(--card-border);border-radius:8px;color:var(--text-dark);font-size:14px;font-family:'Inter',sans-serif;outline:none;margin-bottom:16px;}
    .modal-input:focus{border-color:var(--eval-color);}
    .modal-actions{display:flex;gap:10px;}
    .btn-cancel{flex:1;padding:10px;background:var(--page-bg);border:1px solid var(--card-border);border-radius:var(--radius);color:var(--text-dark);font-size:14px;font-weight:600;cursor:pointer;font-family:'Inter',sans-serif;}
    .btn-confirm{flex:1;padding:10px;background:var(--eval-color);border:none;border-radius:var(--radius);color:#fff;font-size:14px;font-weight:700;cursor:pointer;font-family:'Inter',sans-serif;}

    @media(max-width:920px){.manage-layout{grid-template-columns:1fr;}.add-form-row{flex-direction:column;}body{padding:20px 14px;}}
    
    /* ── LIGHT QUESTIONNAIRE + EA EVALUATION LAYOUT ─────────────── */
    :root{
      --page-bg:#F4F7FC;--card-bg:#FFFFFF;--card-border:#DCE5F1;
      --text-dark:#24334A;--text-dim:#66758A;--track-bg:#E8EEF7;
      --card-shadow:0 6px 18px rgba(30,55,90,.08);
      --eval-color:#3F72C5;--eval-bg:#EEF4FF;--eval-border:#CFE0FB;
    }
    body{background:var(--page-bg);color:var(--text-dark);}
    .eval-switcher{background:#fff;border-color:#DCE5F1;margin-bottom:26px;}
    .eval-tab{color:#56657A;padding:14px 28px;background:#fff;}
    .eval-tab:hover{background:#F7F9FD;color:#24334A;}
    .eval-tab.active-ea{color:#5B3EC8;background:#F7F4FF;}
    .eval-tab.active-ea::after{content:'';position:absolute;bottom:0;left:0;right:0;height:3px;background:#6D3FD1;}
    .eval-tab.active-ea .tab-badge{background:#EEE8FF;color:#6840C7;}
    .eval-tab .tab-badge{background:#EEF2F8;color:#526174;}
    .page-header h1,.sector-label.dark,.sector-stat-val.dark,.user-list-name,.user-header-name{color:#24334A;}
    .page-header p,.sector-stat-lbl.dark,.user-list-desig,.user-header-desig{color:#66758A;}
    .sector-card,.sidebar,.content-panel{background:#fff;border-color:#DCE5F1;}
    .btn-edit-light,.btn-back{background:#fff;color:#334155;border-color:#DCE5F1;}
    .btn-edit-light:hover,.btn-back:hover{background:#F5F8FC;}
    .card-avatar-ph,.user-list-avatar-ph{background:#F3F6FB;border-color:#DCE5F1;color:#7A8AA0;}
    .card-avatar-more{background:#334155;border-color:#fff;}
    .user-header{background:#F8FAFD;border-color:#E1E8F2;}
    .sidebar-legend{background:#F8FAFD;border-color:#E1E8F2;}
    .eval-tab.active-student{background:#EEF4FF;}
    .eval-tab.active-peer{background:#F7F4FF;}
    .eval-tab.active-schoolhead{background:#FFF8ED;}

    .ea-dashboard{max-width:1500px;}
    .ea-heading{margin:6px 0 12px;}
    .ea-heading h1{font-family:'Rajdhani',sans-serif;font-size:34px;color:#24334A;margin:0 0 4px;display:flex;align-items:center;gap:10px;}
    .ea-heading h1 i{color:#5B43C8;}
    .ea-heading p{font-size:14px;color:#5E6C80;margin:4px 0;line-height:1.55;}
    .ea-period-card{background:#fff;border:1px solid #DCE5F1;border-radius:14px;box-shadow:var(--card-shadow);padding:17px 22px;margin:18px 0 18px;display:grid;grid-template-columns:1.6fr .8fr .8fr .8fr auto;gap:18px;align-items:center;}
    .ea-period-item{min-width:0;padding-right:18px;border-right:1px solid #E7EDF5;}
    .ea-period-item:last-of-type{border-right:0;}
    .ea-period-label{font-size:11px;color:#748196;margin-bottom:7px;}
    .ea-period-value{font-size:15px;font-weight:700;color:#26364E;}
    .ea-open-pill{display:inline-flex;align-items:center;gap:6px;padding:6px 13px;border-radius:999px;background:#E8F5EC;color:#237346;font-size:11px;font-weight:800;letter-spacing:.5px;}
    .ea-open-pill.closed{background:#FDECEC;color:#B33A3A;}
    .ea-period-action{display:inline-flex;align-items:center;gap:8px;padding:10px 16px;border:1px solid #D7C9FB;border-radius:8px;color:#5B3EC8;background:#fff;text-decoration:none;font-size:12px;font-weight:700;white-space:nowrap;}
    .ea-section-title{font-size:16px;font-weight:800;color:#2A394F;margin:16px 0 12px;}
    .ea-category-grid{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:22px;margin-bottom:18px;}
    .ea-category-card{display:flex;align-items:center;gap:14px;padding:20px;background:#fff;border:1px solid #DCE5F1;border-radius:14px;box-shadow:var(--card-shadow);text-decoration:none;min-height:106px;transition:.2s;}
    .ea-category-card:hover{transform:translateY(-2px);border-color:#B9A6F3;}
    .ea-category-card.active{border-color:#B9A6F3;background:#FCFBFF;}
    .ea-category-icon{width:54px;height:54px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:22px;flex:0 0 auto;}
    .ea-category-icon.principal{background:#F1EBFF;color:#6840C7;}
    .ea-category-icon.dean{background:#EEF4FF;color:#3F72C5;}
    .ea-category-icon.nts{background:#EAF7EF;color:#168653;}
    .ea-category-copy{min-width:0;flex:1;}
    .ea-category-copy strong{display:block;font-size:16px;color:#2B394F;margin-bottom:5px;}
    .ea-category-copy span{display:block;font-size:12px;color:#617086;line-height:1.5;}
    .ea-category-count{margin-left:auto;width:34px;height:34px;border-radius:50%;background:#F0F4FA;color:#5B3EC8;display:flex;align-items:center;justify-content:center;font-size:13px;font-weight:800;}
    .ea-detail{display:grid;grid-template-columns:390px 1fr;background:#fff;border:1px solid #DCE5F1;border-radius:14px;box-shadow:var(--card-shadow);min-height:360px;overflow:hidden;}
    .ea-target-list{border-right:1px solid #E4EAF2;background:#FBFCFE;}
    .ea-target-list-head{padding:16px 18px;border-bottom:1px solid #E4EAF2;font-size:15px;font-weight:800;color:#2A394F;display:flex;justify-content:space-between;align-items:center;}
    .ea-target-row{display:flex;align-items:center;gap:11px;padding:12px 15px;border-bottom:1px solid #EDF1F6;text-decoration:none;color:inherit;}
    .ea-target-row.active{background:#F4F1FF;box-shadow:inset 3px 0 0 #6D3FD1;}
    .ea-target-avatar{width:43px;height:43px;border-radius:50%;object-fit:cover;background:#EEF3FA;border:1px solid #DCE5F1;display:flex;align-items:center;justify-content:center;color:#6A7890;}
    .ea-target-meta{min-width:0;flex:1;}
    .ea-target-name{font-size:13px;font-weight:800;color:#34435A;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;}
    .ea-target-role{font-size:11px;color:#738198;margin-top:3px;}
    .ea-target-status{font-size:10px;font-weight:800;padding:4px 9px;border-radius:999px;background:#FFF4E5;color:#B66A0B;}
    .ea-target-chevron{color:#7B8797;font-size:14px;}
    .ea-detail-main{padding:24px 26px;}
    .ea-detail-top{display:flex;justify-content:space-between;gap:20px;align-items:flex-start;}
    .ea-person{display:flex;gap:13px;align-items:center;}
    .ea-person-avatar{width:58px;height:58px;border-radius:50%;object-fit:cover;background:#EEF3FA;border:1px solid #DCE5F1;display:flex;align-items:center;justify-content:center;color:#69788F;}
    .ea-person h2{font-size:18px;color:#2C3B51;margin:0 0 5px;}
    .ea-role-chip{display:inline-block;background:#F0EDFF;color:#6042BF;border-radius:999px;padding:5px 11px;font-size:10px;font-weight:800;}
    .ea-actions{display:flex;flex-direction:column;gap:9px;min-width:170px;}
    .ea-start-btn{background:#6B39D0;color:#fff;border:0;border-radius:8px;padding:12px 16px;text-align:center;text-decoration:none;font-size:13px;font-weight:800;}
    .ea-instructions{background:#fff;color:#4C5B70;border:1px solid #DCE5F1;border-radius:8px;padding:11px 16px;text-align:center;text-decoration:none;font-size:12px;font-weight:700;}
    .ea-info-banner{margin:18px 0 22px;padding:13px 15px;border:1px solid #D8CEF7;border-radius:10px;background:#FBFAFF;color:#52627A;font-size:12px;line-height:1.55;}
    .ea-info-banner i{color:#5D43BF;margin-right:8px;}
    .ea-info-title{font-size:15px;font-weight:800;color:#334259;border-bottom:1px solid #E6ECF3;padding-bottom:10px;margin-bottom:16px;}
    .ea-info-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:22px;}
    .ea-info-grid small{display:block;color:#77859A;margin-bottom:5px;font-size:11px;}
    .ea-info-grid b{font-size:13px;color:#35445A;}
    .ea-about{margin-top:22px;padding:17px 18px;border:1px solid #D8CEF7;background:#FCFBFF;border-radius:10px;color:#59687D;font-size:12px;line-height:1.6;}
    .ea-about strong{display:block;color:#5A43B8;font-size:14px;margin-bottom:6px;}
    @media(max-width:980px){.ea-period-card{grid-template-columns:1fr 1fr}.ea-category-grid{grid-template-columns:1fr}.ea-detail{grid-template-columns:1fr}.ea-target-list{border-right:0;border-bottom:1px solid #E4EAF2}.ea-detail-top{flex-direction:column}.ea-actions{width:100%;flex-direction:row}.ea-actions a{flex:1}.ea-info-grid{grid-template-columns:1fr;gap:14px}}

    </style>
    </head>
    <body>

    <?php if (isset($_GET['msg']) && $_GET['msg']): ?>
    <div class="toast"><i class="fa-solid fa-circle-check"></i> <?= htmlspecialchars($_GET['msg']) ?></div>
    <?php endif; ?>

    <?php
    $total_student_q     = $mysqli->query("SELECT COUNT(*) as c FROM evaluation_questions WHERE eval_type='student'")->fetch_assoc()['c'];
    $total_peer_q        = $mysqli->query("SELECT COUNT(*) as c FROM evaluation_questions WHERE eval_type='peer'")->fetch_assoc()['c'];
    $total_schoolhead_q  = $mysqli->query("SELECT COUNT(*) as c FROM evaluation_questions WHERE eval_type='school_head'")->fetch_assoc()['c'];
    $total_ea_q          = $mysqli->query("SELECT COUNT(*) as c FROM evaluation_questions WHERE eval_type='ea'")->fetch_assoc()['c'];

    // Teacher/Multi-Role live in the shared pool, already counted above.
    // Staff/Principal/Dean contribute from user_questions.
    $total_student_uq    = $mysqli->query("SELECT COUNT(*) as c FROM user_questions WHERE eval_type='student'")->fetch_assoc()['c'];
    $total_peer_uq       = $mysqli->query("SELECT COUNT(*) as c FROM user_questions WHERE eval_type='peer'")->fetch_assoc()['c'];
    $total_schoolhead_uq = $mysqli->query("SELECT COUNT(*) as c FROM user_questions WHERE eval_type='school_head'")->fetch_assoc()['c'];
    $total_ea_uq         = $mysqli->query("SELECT COUNT(*) as c FROM user_questions WHERE eval_type='ea'")->fetch_assoc()['c'];
    $total_student_q    += $total_student_uq;
    $total_peer_q        += $total_peer_uq;
    $total_schoolhead_q  += $total_schoolhead_uq;
    $total_ea_q          += $total_ea_uq;

    $view_param      = $current_view === 'manage' ? 'manage' : 'dashboard';
    $target_param    = $current_view === 'manage' ? '&target='.urlencode($selected_target) : '';
    $uid_param       = $selected_user ? '&user_id='.$selected_user : '';
    $mr_param        = ($current_view === 'manage' && $selected_target === 'Multi-Role') ? '&mr_filter='.$mr_filter : '';
    ?>
    <div class="eval-switcher">
        <a href="?view=<?= $view_param ?>&eval_type=student<?= $target_param.$uid_param.$mr_param ?>"
           class="eval-tab <?= $active_eval === 'student' ? 'active-student' : '' ?>">
            <i class="fa-solid fa-graduation-cap"></i> Student Evaluation
            <span class="tab-badge"><?= $total_student_q ?> Q</span>
        </a>
        <div class="eval-divider"></div>
        <a href="?view=<?= $view_param ?>&eval_type=peer<?= $target_param.$uid_param.$mr_param ?>"
           class="eval-tab <?= $active_eval === 'peer' ? 'active-peer' : '' ?>">
            <i class="fa-solid fa-people-arrows"></i> Peer-to-Peer Evaluation
            <span class="tab-badge"><?= $total_peer_q ?> Q</span>
        </a>
        <div class="eval-divider"></div>
        <a href="?view=<?= $view_param ?>&eval_type=school_head<?= $target_param.$uid_param.$mr_param ?>"
           class="eval-tab <?= $active_eval === 'school_head' ? 'active-schoolhead' : '' ?>">
            <i class="fa-solid fa-user-tie"></i> School Head Evaluation
            <span class="tab-badge"><?= $total_schoolhead_q ?> Q</span>
        </a>
        <div class="eval-divider"></div>
        <a href="?view=<?= $view_param ?>&eval_type=ea<?= $target_param.$uid_param ?>"
           class="eval-tab <?= $active_eval === 'ea' ? 'active-ea' : '' ?>">
            <i class="fa-solid fa-user-check"></i> EA Evaluation
            <span class="tab-badge"><?= $total_ea_q ?> Q</span>
        </a>
    </div>

    <?php if ($current_view === 'dashboard'): ?>
    <!-- ══ DASHBOARD ══ -->
    <div class="page-header">
        <div>
            <h1><?= $eval_label ?></h1>
            <p><?= $eval_desc ?> Teacher &amp; Multi-Role share one question set each. Staff/Principal/Dean/Non-Teaching Staff questions are assigned per person. EA Evaluation is triggered by an open evaluation period and targets the active Principal, active Dean, and staff with no teaching assignment. Teaching assignments control the student's base Teacher/Staff visibility. An additional responsibility creates a separate Multi-Role context; it never replaces the base role. Faculty/Staff lists are pulled live from Manage Privileged — nothing to add here manually.</p>
        </div>
        <a href="?view=manage&target=Teacher&eval_type=<?= $active_eval ?>" class="btn btn-primary">
            <i class="fa-solid fa-circle-plus"></i> Manage Questions
        </a>
    </div>

    <?php if ($active_eval === 'ea'): ?>
    <?php
        $ea_period = null;
        $ea_period_res = $mysqli->query("SELECT id, period_label, school_year, semester, date_start, date_end, is_active FROM evaluation_periods WHERE is_active=1 LIMIT 1");
        if ($ea_period_res) $ea_period = $ea_period_res->fetch_assoc();

        $ea_period_label = $ea_period['period_label'] ?? 'No active evaluation period';
        $ea_period_open = !empty($ea_period);
        $ea_selected_type = $_GET['target'] ?? 'Principal';
        if (!in_array($ea_selected_type, $ea_categories, true)) $ea_selected_type = 'Principal';
        $ea_targets = $card_data_ea[$ea_selected_type]['users'] ?? [];
        $ea_selected_id = isset($_GET['user_id']) ? (int)$_GET['user_id'] : 0;
        $ea_selected_person = null;
        foreach ($ea_targets as $ea_u) {
            if ($ea_selected_id > 0 && (int)$ea_u['id'] === $ea_selected_id) { $ea_selected_person = $ea_u; break; }
        }
        if (!$ea_selected_person && !empty($ea_targets)) {
            $ea_selected_person = $ea_targets[0];
            $ea_selected_id = (int)$ea_selected_person['id'];
        }
        $ea_start = $ea_period['date_start'] ?? null;
        $ea_end = $ea_period['date_end'] ?? null;
        $ea_format_date = function($d) {
            if (!$d) return '—';
            $t = strtotime($d);
            return $t ? date('M j, Y', $t) : $d;
        };
        $ea_icon = ['Principal'=>'principal','Dean'=>'dean','Non-Teaching Staff'=>'nts'];
        $ea_fa = ['Principal'=>'fa-user-tie','Dean'=>'fa-graduation-cap','Non-Teaching Staff'=>'fa-users'];
    ?>
    <div class="ea-dashboard">
        <div class="ea-heading">
            <h1><i class="fa-solid fa-user-check"></i> EA Evaluation</h1>
            <p>Evaluate the Principal, Dean, and Non-Teaching Staff for the active evaluation period.</p>
            <p>These evaluations are managed and answered by the Executive Assistant.</p>
        </div>

        <div class="ea-period-card">
            <div class="ea-period-item">
                <div class="ea-period-label">Evaluation Period</div>
                <div class="ea-period-value"><?= htmlspecialchars($ea_period_label) ?></div>
            </div>
            <div class="ea-period-item">
                <div class="ea-period-label">Period Status</div>
                <div><?= $ea_period_open ? '<span class="ea-open-pill"><i class="fa-solid fa-circle" style="font-size:7px"></i> OPEN</span>' : '<span class="ea-open-pill closed">CLOSED</span>' ?></div>
            </div>
            <div class="ea-period-item">
                <div class="ea-period-label">Start Date</div>
                <div class="ea-period-value"><?= htmlspecialchars($ea_format_date($ea_start)) ?></div>
            </div>
            <div class="ea-period-item">
                <div class="ea-period-label">End Date</div>
                <div class="ea-period-value"><?= htmlspecialchars($ea_format_date($ea_end)) ?></div>
            </div>
            <a class="ea-period-action" href="../admin/manage_periods.php"><i class="fa-solid fa-calendar-days"></i> View Period Details</a>
        </div>

        <div class="ea-section-title">EA Evaluation Categories</div>
        <div class="ea-category-grid">
            <?php foreach ($ea_categories as $ea_type):
                $ea_count = count($card_data_ea[$ea_type]['users'] ?? []);
            ?>
            <a class="ea-category-card <?= $ea_selected_type === $ea_type ? 'active' : '' ?>"
               href="?view=dashboard&eval_type=ea&target=<?= urlencode($ea_type) ?>">
                <div class="ea-category-icon <?= $ea_icon[$ea_type] ?>"><i class="fa-solid <?= $ea_fa[$ea_type] ?>"></i></div>
                <div class="ea-category-copy">
                    <strong><?= htmlspecialchars($ea_type) ?></strong>
                    <span><?= $ea_type === 'Principal' ? 'Evaluate the overall performance and leadership of the Principal.' : ($ea_type === 'Dean' ? 'Evaluate the overall performance and academic leadership of the Dean.' : 'Evaluate staff members who are not assigned to teach any year level.') ?></span>
                </div>
                <div class="ea-category-count"><?= $ea_count ?></div>
                <i class="fa-solid fa-chevron-right" style="color:#718096;font-size:13px"></i>
            </a>
            <?php endforeach; ?>
        </div>

        <div class="ea-detail">
            <aside class="ea-target-list">
                <div class="ea-target-list-head">
                    <span><?= htmlspecialchars($ea_selected_type) ?></span>
                    <span style="background:#EEE8FF;color:#6042BF;border-radius:999px;padding:3px 9px;font-size:10px"><?= count($ea_targets) ?></span>
                </div>
                <?php if (empty($ea_targets)): ?>
                    <div style="padding:28px 18px;color:#748196;font-size:12px;line-height:1.6;">No active <?= htmlspecialchars(strtolower($ea_selected_type)) ?> is currently available for EA evaluation.</div>
                <?php else: foreach ($ea_targets as $ea_u): ?>
                    <a class="ea-target-row <?= (int)$ea_u['id'] === $ea_selected_id ? 'active' : '' ?>"
                       href="?view=dashboard&eval_type=ea&target=<?= urlencode($ea_selected_type) ?>&user_id=<?= (int)$ea_u['id'] ?>">
                        <?php if (!empty($ea_u['photo'])): ?>
                            <img class="ea-target-avatar" src="../image/<?= htmlspecialchars($ea_u['photo']) ?>" alt="">
                        <?php else: ?><div class="ea-target-avatar"><i class="fa-solid fa-user"></i></div><?php endif; ?>
                        <div class="ea-target-meta">
                            <div class="ea-target-name"><?= htmlspecialchars($ea_u['full_name']) ?></div>
                            <div class="ea-target-role"><?= htmlspecialchars($ea_type) ?></div>
                        </div>
                        <span class="ea-target-status">Pending</span>
                        <i class="fa-solid fa-chevron-right ea-target-chevron"></i>
                    </a>
                <?php endforeach; endif; ?>
            </aside>

            <section class="ea-detail-main">
                <?php if ($ea_selected_person): ?>
                    <?php $ea_q_count = $user_q_counts[$ea_selected_id][$ea_selected_type]['ea'] ?? 0; ?>
                    <div class="ea-detail-top">
                        <div>
                            <div class="ea-person">
                                <?php if (!empty($ea_selected_person['photo'])): ?>
                                    <img class="ea-person-avatar" src="../image/<?= htmlspecialchars($ea_selected_person['photo']) ?>" alt="">
                                <?php else: ?><div class="ea-person-avatar"><i class="fa-solid fa-user"></i></div><?php endif; ?>
                                <div>
                                    <h2><?= htmlspecialchars($ea_selected_person['full_name']) ?></h2>
                                    <span class="ea-role-chip"><?= htmlspecialchars($ea_selected_type) ?> Account</span>
                                </div>
                            </div>
                        </div>
                        <div class="ea-actions">
                            <a class="ea-start-btn" href="../executive_assistant/ea_evaluation.php?type=<?= urlencode($ea_selected_type) ?>&target=<?= $ea_selected_id ?>"><i class="fa-solid fa-pen-to-square"></i> Start Evaluation</a>
                            <a class="ea-instructions" href="?view=manage&target=<?= urlencode($ea_selected_type) ?>&eval_type=ea&user_id=<?= $ea_selected_id ?>"><i class="fa-solid fa-eye"></i> View Questions (<?= $ea_q_count ?>)</a>
                        </div>
                    </div>

                    <div class="ea-info-banner"><i class="fa-solid fa-circle-info"></i>The Executive Assistant evaluates this <?= strtolower(htmlspecialchars($ea_selected_type)) ?> using the EA evaluation question set assigned to this person.</div>
                    <div class="ea-info-title">Evaluation Information</div>
                    <div class="ea-info-grid">
                        <div><small>Role</small><b><?= htmlspecialchars($ea_selected_type) ?></b></div>
                        <div><small>Evaluator</small><b>Executive Assistant</b></div>
                        <div><small>Evaluation Type</small><b>EA Evaluation</b></div>
                        <div><small>Question Set</small><b><?= $ea_q_count ?> assigned question<?= $ea_q_count===1?'':'s' ?></b></div>
                    </div>
                    <div class="ea-about">
                        <strong><i class="fa-solid fa-circle-info"></i> About EA Evaluation</strong>
                        The Executive Assistant evaluates the Principal, Dean, and Non-Teaching Staff. Non-Teaching Staff are active staff members who are not assigned to teach any year level during the evaluation period.
                    </div>
                <?php else: ?>
                    <div style="height:100%;min-height:250px;display:flex;align-items:center;justify-content:center;color:#748196;text-align:center;font-size:13px;line-height:1.6;">Select a person from the list to view their EA evaluation details.</div>
                <?php endif; ?>
            </section>
        </div>
    </div>

    <?php else: ?>
    <div class="sector-row">
    <?php
    $student_counts = []; $peer_counts = []; $schoolhead_counts = [];
    $qres = $mysqli->query("SELECT target_type, eval_type, COUNT(*) as total FROM evaluation_questions GROUP BY target_type, eval_type");
    if ($qres) while ($qr = $qres->fetch_assoc()) {
        if ($qr['eval_type'] === 'student')     $student_counts[$qr['target_type']]    = $qr['total'];
        if ($qr['eval_type'] === 'peer')        $peer_counts[$qr['target_type']]       = $qr['total'];
        if ($qr['eval_type'] === 'school_head') $schoolhead_counts[$qr['target_type']] = $qr['total'];
    }
    $counts_by_eval = ['student' => $student_counts, 'peer' => $peer_counts, 'school_head' => $schoolhead_counts];

    // Per-user question totals for the Staff/Principal/Dean cards
    // (Teacher/Multi-Role use shared $counts_by_eval above).
    $per_user_uq_by_type_eval = [];
    $puq_res = $mysqli->query("SELECT target_type, eval_type, COUNT(*) as total FROM user_questions GROUP BY target_type, eval_type");
    if ($puq_res) while ($pr = $puq_res->fetch_assoc()) {
        $per_user_uq_by_type_eval[$pr['target_type']][$pr['eval_type']] = $pr['total'];
    }

    // Cosmetic sub-role chips (Registrar/Cashier/etc.), computed for the
    // Staff card's per-person breakdown. Only meaningful for Staff.
    $subroles_by_type = [];
    if (in_array('Staff', $per_user_targets)) {
        $subroles_by_type['Staff'] = [];
        foreach (($card_data['Staff']['users'] ?? []) as $su) {
            $sr = getSubRole($su);
            $subroles_by_type['Staff'][$sr] = ($subroles_by_type['Staff'][$sr] ?? 0) + 1;
        }
    }
    $staff_subroles = $subroles_by_type['Staff'] ?? [];

    foreach ($card_data as $type => $data):
        $icon        = $icons[$type] ?? 'fa-user';
        $users       = $data['users'];
        $is_mr       = ($type === 'Multi-Role');
        $is_staff    = in_array($type, $per_user_targets); // Staff, Principal, Dean
        $is_fac      = ($type === 'Teacher');
        $fac_c       = $is_mr ? count(array_filter($users, fn($u) => $u['role'] === 'teacher')) : 0;
        $sta_c       = $is_mr ? count(array_filter($users, fn($u) => $u['role'] === 'staff'))   : 0;
        $card_class  = $is_mr ? 'multi-role-card' : ($is_staff ? 'staff-card' : '');
        $label_class = $is_mr ? 'mr-color' : ($is_staff ? 'staff-color' : 'dark');
        $icon_color  = $is_mr ? 'var(--mr)' : ($is_staff ? 'var(--staff)' : 'var(--eval-color)');

        // Badge count: shared pool for Teacher/Multi-Role, per-user total for per-user targets.
        if ($is_staff) {
            $badge_count = $per_user_uq_by_type_eval[$type][$active_eval] ?? 0;
            $badge_label = 'total Qs';
            $users_with_q = count(array_filter($users, fn($u) => ($user_q_counts[$u['id']][$type][$active_eval] ?? 0) > 0));
        } else {
            $badge_count = $counts_by_eval[$active_eval][$type] ?? 0;
            $badge_label = 'shared Qs';
            $users_with_q = 0;
        }
    ?>
    <div class="sector-card <?= $card_class ?>"
         onclick="window.location='?view=manage&target=<?= urlencode($type) ?>&eval_type=<?= $active_eval ?>'">
        <div class="sector-card-top">
            <span class="sector-label <?= $label_class ?>">
                <i class="fa-solid <?= $icon ?>" style="color:<?= $icon_color ?>"></i>
                <?= htmlspecialchars($type) ?>
            </span>
            <?php if ($is_staff): ?>
            <span class="per-user-pill staff-pill">
                <i class="fa-solid fa-user-pen" style="font-size:9px"></i> Per-Person
            </span>
            <?php else: ?>
            <span class="sector-badge multi"><?= $badge_count ?> Q</span>
            <?php endif; ?>
        </div>

        <?php if ($is_staff): ?>
            <?php if ($type === 'Staff' && !empty($staff_subroles)): ?>
            <div class="subrole-chips">
                <?php foreach ($staff_subroles as $sr => $cnt): ?>
                <span class="subrole-chip"><i class="fa-solid <?= $staff_subrole_labels[$sr]['icon'] ?? 'fa-briefcase' ?>" style="font-size:9px;color:<?= $staff_subrole_labels[$sr]['color'] ?? '#94a3b8' ?>"></i> <?= $sr ?> (<?= $cnt ?>)</span>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
            <?php if (!empty($users)): ?>
            <div class="card-avatars">
                <?php $show = array_slice($users, 0, 4); $extra = count($users) - count($show);
                foreach ($show as $cu): ?>
                    <?php if ($cu['photo']): ?>
                    <img class="card-avatar" src="../image/<?= htmlspecialchars($cu['photo']) ?>" title="<?= htmlspecialchars($cu['full_name']) ?>"/>
                    <?php else: ?>
                    <div class="card-avatar-ph staff-ph" title="<?= htmlspecialchars($cu['full_name']) ?>"><i class="fa-solid fa-user" style="font-size:10px"></i></div>
                    <?php endif; ?>
                <?php endforeach; ?>
                <?php if ($extra > 0): ?><div class="card-avatar-more staff-more">+<?= $extra ?></div><?php endif; ?>
                <span style="font-size:11px;color:var(--text-dim);margin-left:8px;"><?= count($users) ?> person<?= count($users)>1?'s':'' ?></span>
            </div>
            <?php endif; ?>
            <div class="sector-meta">
                <div><div class="sector-stat-lbl staff-l">Total Questions</div><div class="sector-stat-val staff-v"><?= $badge_count ?></div></div>
                <div><div class="sector-stat-lbl staff-l">Assigned</div><div class="sector-stat-val staff-v"><?= $users_with_q ?>/<?= count($users) ?></div></div>
            </div>
            <div class="sector-actions">
                <button class="btn-sector btn-view-staff" onclick="event.stopPropagation();window.location='?view=manage&target=<?= urlencode($type) ?>&eval_type=<?= $active_eval ?>'"><i class="fa-solid fa-user-pen"></i> Assign Questions</button>
            </div>

        <?php elseif ($is_mr): ?>
            <div class="mr-role-chips">
                <span class="mr-role-chip fac"><i class="fa-solid fa-chalkboard-user" style="font-size:9px"></i> <?= $fac_c ?> Teacher</span>
                <span class="mr-role-chip sta"><i class="fa-solid fa-briefcase" style="font-size:9px"></i> <?= $sta_c ?> Staff</span>
            </div>
            <?php if (!empty($users)): ?>
            <div class="card-avatars">
                <?php $show = array_slice($users, 0, 4); $extra = count($users) - count($show);
                foreach ($show as $cu): ?>
                    <?php if ($cu['photo']): ?>
                    <img class="card-avatar" src="../image/<?= htmlspecialchars($cu['photo']) ?>" title="<?= htmlspecialchars($cu['full_name']) ?>"/>
                    <?php else: ?>
                    <div class="card-avatar-ph mr-ph" title="<?= htmlspecialchars($cu['full_name']) ?>"><i class="fa-solid fa-user" style="font-size:10px"></i></div>
                    <?php endif; ?>
                <?php endforeach; ?>
                <?php if ($extra > 0): ?><div class="card-avatar-more mr-more">+<?= $extra ?></div><?php endif; ?>
                <span style="font-size:11px;color:var(--text-dim);margin-left:8px;"><?= count($users) ?> person<?= count($users)>1?'s':'' ?></span>
            </div>
            <?php else: ?>
            <div style="font-size:11px;color:var(--mr);opacity:.7;font-style:italic;"><i class="fa-solid fa-user-slash" style="font-size:10px"></i> No multi-role users yet</div>
            <?php endif; ?>
            <div class="sector-meta">
                <div><div class="sector-stat-lbl mr-l">Shared Questions</div><div class="sector-stat-val mr-v"><?= $badge_count ?></div></div>
                <div><div class="sector-stat-lbl mr-l">People</div><div class="sector-stat-val mr-v"><?= count($users) ?></div></div>
            </div>
            <div class="sector-actions">
                <button class="btn-sector btn-view-mr" onclick="event.stopPropagation();window.location='?view=manage&target=Multi-Role&eval_type=<?= $active_eval ?>'"><i class="fa-solid fa-eye"></i> View</button>
                <button class="btn-sector btn-edit-mr" onclick="event.stopPropagation();window.location='?view=manage&target=Multi-Role&eval_type=<?= $active_eval ?>'"><i class="fa-solid fa-pen"></i> Edit</button>
            </div>

        <?php else: /* Teacher — shared pool, like Multi-Role */ ?>
            <?php if (!empty($users)): ?>
            <div class="card-avatars">
                <?php $show = array_slice($users, 0, 4); $extra = count($users) - count($show);
                foreach ($show as $cu): ?>
                    <?php if ($cu['photo']): ?>
                    <img class="card-avatar" src="../image/<?= htmlspecialchars($cu['photo']) ?>" title="<?= htmlspecialchars($cu['full_name']) ?>"/>
                    <?php else: ?>
                    <div class="card-avatar-ph" title="<?= htmlspecialchars($cu['full_name']) ?>"><i class="fa-solid fa-user"></i></div>
                    <?php endif; ?>
                <?php endforeach; ?>
                <?php if ($extra > 0): ?><div class="card-avatar-more">+<?= $extra ?></div><?php endif; ?>
                <span style="font-size:11px;color:var(--text-dim);margin-left:8px;"><?= count($users) ?> person<?= count($users)>1?'s':'' ?></span>
            </div>
            <?php else: ?>
            <div style="font-size:11px;color:var(--text-dim);font-style:italic;"><i class="fa-solid fa-user-slash" style="font-size:10px"></i> No teacher users yet</div>
            <?php endif; ?>
            <div class="sector-meta">
                <div><div class="sector-stat-lbl dark">Shared Questions</div><div class="sector-stat-val dark"><?= $badge_count ?></div></div>
                <div><div class="sector-stat-lbl dark">People</div><div style="font-size:18px;font-weight:700;color:var(--text-dark);"><?= count($users) ?></div></div>
            </div>
            <div class="sector-actions">
                <button class="btn-sector btn-view-dark" onclick="event.stopPropagation();window.location='?view=manage&target=Teacher&eval_type=<?= $active_eval ?>'"><i class="fa-solid fa-eye"></i> View</button>
                <button class="btn-sector btn-edit-light" onclick="event.stopPropagation();window.location='?view=manage&target=Teacher&eval_type=<?= $active_eval ?>'"><i class="fa-solid fa-pen"></i> Edit</button>
            </div>
        <?php endif; ?>
    </div>
    <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <?php elseif ($current_view === 'manage'): ?>
    <!-- ══ MANAGE ══ -->
    <?php
    $is_mr_manage    = ($selected_target === 'Multi-Role');
    $is_staff_manage = in_array($selected_target, $per_user_targets); // Staff, Principal, Dean
    $is_fac_manage   = ($selected_target === 'Teacher');
    $is_shared_manage = $is_fac_manage; // only Teacher uses the shared pool
    $accent_class    = $is_mr_manage ? 'mr' : ($is_staff_manage ? 'staff' : 'fac');
    $hdr_color       = $is_mr_manage ? 'var(--mr)' : ($is_staff_manage ? 'var(--staff)' : 'var(--eval-color)');

    // Cosmetic per-user theming classes — only Staff gets the dedicated
    // "staff" visual treatment (purple + briefcase icon language baked into
    // the CSS above); Principal/Dean reuse the neutral eval-color theme so
    // they don't visually masquerade as Staff members.
    $pu_is_true_staff = ($selected_target === 'Staff');
    $pu_av_cls   = $pu_is_true_staff ? 'staff-av'   : '';
    $pu_tag_cls  = $pu_is_true_staff ? 'staff-tag'  : '';
    $pu_chip_cls = $pu_is_true_staff ? 'staff-chip' : '';
    $pu_sh_cls   = $pu_is_true_staff ? 'staff-sh'   : '';
    $pu_ct_cls   = $pu_is_true_staff ? 'staff-ct'   : '';
    $pu_btn_cls  = $pu_is_true_staff ? 'btn-staff'  : 'btn-eval';
    $pu_prompt_cls = $pu_is_true_staff ? 'staff-prompt' : 'generic-prompt';
    ?>
    <div class="page-header">
        <div>
            <h1>
                <i class="fa-solid <?= $icons[$selected_target] ?? 'fa-user' ?>"
                   style="color:<?= $hdr_color ?>;margin-right:8px"></i>
                <?= htmlspecialchars($selected_target) ?>
                <span style="color:<?= $hdr_color ?>;font-size:20px;font-weight:400"> — <?= $eval_label ?></span>
            </h1>
            <p>
                <?php if ($is_shared_manage): ?>
                    Manage the shared <?= htmlspecialchars($selected_target) ?> question pool. Every <?= htmlspecialchars($selected_target) ?> member uses the same questions.
                <?php else: ?>
                    Select a person from the list to assign their individual questions.
                <?php endif; ?>
            </p>
        </div>
        <div style="display:flex;gap:10px;align-items:center;">
            <select onchange="window.location='?view=manage&target='+this.value+'&eval_type=<?= $active_eval ?>'"
                style="background:var(--card-bg);border:1px solid var(--card-border);color:var(--text-dark);padding:9px 14px;border-radius:var(--radius);font-size:13px;font-family:'Inter',sans-serif;cursor:pointer;outline:none;">
                <?php foreach ($active_categories as $sc): ?>
                <option value="<?= $sc ?>" <?= $selected_target === $sc ? 'selected' : '' ?>><?= $sc ?></option>
                <?php endforeach; ?>
            </select>
            <a href="?view=dashboard&eval_type=<?= $active_eval ?>" class="btn btn-back"><i class="fa-solid fa-circle-chevron-left"></i> Back</a>
        </div>
    </div>

    <div class="manage-layout">
        <!-- SIDEBAR -->
        <div class="sidebar">
            <div class="sidebar-title">
                <i class="fa-solid <?= $icons[$selected_target] ?? 'fa-users' ?>"
                   style="<?= $is_staff_manage?'color:var(--staff)':($is_mr_manage?'color:var(--mr)':'') ?>"></i>
                <?= htmlspecialchars($selected_target) ?>
                <span class="sidebar-count"><?= count($target_users) ?></span>
            </div>

            <?php if ($is_per_user_target): ?>
            <div class="per-user-mode-notice <?= $is_staff_manage ? 'staff-notice' : '' ?>">
                <i class="fa-solid fa-user-pen" style="font-size:10px"></i>
                Click a person to manage their questions
            </div>
            <?php elseif ($is_fac_manage): ?>
            <div class="per-user-mode-notice">
                <i class="fa-solid fa-layer-group" style="font-size:10px"></i>
                Click a person to preview the shared questions
            </div>
            <?php endif; ?>

            <?php if ($is_mr_manage): ?>
            <div class="mr-filter-tabs">
                <a href="?view=manage&target=Multi-Role&eval_type=<?= $active_eval ?>&mr_filter=all<?= $uid_param ?>"
                   class="mr-filter-tab <?= $mr_filter==='all'?'active-all':'' ?>">
                    All <span class="mr-filter-count"><?= count($mr_all_users) ?></span>
                </a>
                <a href="?view=manage&target=Multi-Role&eval_type=<?= $active_eval ?>&mr_filter=teacher<?= $uid_param ?>"
                   class="mr-filter-tab <?= $mr_filter==='teacher'?'active-teacher':'' ?>">
                    Teacher <span class="mr-filter-count"><?= $mr_teacher_count ?></span>
                </a>
                <a href="?view=manage&target=Multi-Role&eval_type=<?= $active_eval ?>&mr_filter=staff<?= $uid_param ?>"
                   class="mr-filter-tab <?= $mr_filter==='staff'?'active-staff':'' ?>">
                    Staff <span class="mr-filter-count"><?= $mr_staff_count ?></span>
                </a>
            </div>
            <?php endif; ?>

            <?php if (empty($target_users)): ?>
            <div class="user-list-empty">
                <i class="fa-solid <?= $icons[$selected_target] ?? 'fa-user-slash' ?>" style="font-size:22px;opacity:.3;display:block;margin-bottom:8px;<?= $is_staff_manage?'color:var(--staff)':($is_mr_manage?'color:var(--mr)':'') ?>"></i>
                <?php if ($is_staff_manage): ?>
                    No approved <?= htmlspecialchars($selected_target) ?> accounts yet.<br><small>Approve <?= htmlspecialchars($selected_target) ?> registrations in Manage Privileged and they'll appear here automatically.</small>
                <?php elseif ($is_mr_manage): ?>
                    No multi-role <?= $mr_filter !== 'all' ? $mr_filter : 'users' ?> found.<br><small>A user appears here when they have an additional role/responsibility. Teaching assignments do not replace their Staff or Teacher context.</small>
                <?php else: ?>
                    No approved Faculty accounts yet.<br><small>Approve Faculty registrations in Manage Privileged and they'll appear here automatically.</small>
                <?php endif; ?>
            </div>
            <?php else: ?>
            <ul class="user-list">
                <?php
                foreach ($target_users as $tu):
                    $is_active_item = ($selected_user === $tu['id']);
                    $is_nologin     = ($tu['source'] === 'admin_nologin');
                    $active_cls     = $is_active_item ? ('active'.($is_staff_manage?' staff-active':($is_mr_manage||$is_fac_manage?' mr-active':''))) : '';
                    $q_badge_cls    = $is_staff_manage ? 'staff-q' : 'mr-q';
                    $sub            = ($selected_target === 'Staff') ? getSubRole($tu) : null;
                    $sec_label      = secondaryRoleLabel($tu);

                    // Badge: per-user count for per-user targets, shared pool count for Teacher/Multi-Role
                    if ($is_per_user_target) {
                        $uq_cnt = $user_q_counts[$tu['id']][$selected_target][$active_eval] ?? 0;
                        $badge_text = $uq_cnt > 0 ? $uq_cnt.'Q' : '0Q';
                        $badge_extra = $uq_cnt === 0 ? ' no-q' : '';
                    } else {
                        $badge_text = count($questions_list).'Q';
                        $badge_extra = '';
                    }
                ?>
                <li class="user-list-item <?= $active_cls ?>"
                    onclick="window.location='?view=manage&target=<?= urlencode($selected_target) ?>&eval_type=<?= $active_eval ?>&user_id=<?= $tu['id'] ?><?= $is_mr_manage ? '&mr_filter='.$mr_filter : '' ?>'">
                    <?php if ($tu['photo']): ?>
                    <img class="user-list-avatar" src="../image/<?= htmlspecialchars($tu['photo']) ?>" alt=""/>
                    <?php else: ?>
                    <div class="user-list-avatar-ph"><i class="fa-solid fa-user"></i></div>
                    <?php endif; ?>
                    <div style="flex:1;min-width:0;">
                        <div class="user-list-name"><?= htmlspecialchars($tu['full_name']) ?></div>
                        <div class="user-list-desig" title="<?= htmlspecialchars($tu['designation']) ?>">
                            <?= htmlspecialchars($tu['designation'] ?: ($tu['role'] === 'teacher' ? 'Teacher' : 'Personnel')) ?>
                        </div>
                    </div>
                    <?php if (!$is_mr_manage && $sec_label): ?>
                    <span class="secondary-role-tag" title="Also assigned as <?= htmlspecialchars($sec_label) ?>"><i class="fa-solid fa-layer-group" style="font-size:8px"></i> +<?= htmlspecialchars($sec_label) ?></span>
                    <?php endif; ?>
                    <?php if ($sub): ?>
                    <span class="subrole-mini"><?= htmlspecialchars($sub) ?></span>
                    <?php endif; ?>
                    <?php if ($is_mr_manage && $mr_filter === 'all'): ?>
                    <span class="role-mini-badge <?= $tu['role']==='teacher'?'fac':'sta' ?>">
                        <?= $tu['role']==='teacher'?'Fac':'Staff' ?>
                    </span>
                    <?php endif; ?>
                    <span class="q-count-badge <?= $q_badge_cls.$badge_extra ?>"><?= $badge_text ?></span>
                    <span class="source-dot <?= $is_nologin ? 'nologin' : 'login' ?>"
                          title="<?= $is_nologin ? 'Personnel Registry (admin-added)' : 'Login account (User Management)' ?>"></span>
                </li>
                <?php endforeach; ?>
            </ul>
            <div class="sidebar-legend">
                <div class="legend-item"><div class="legend-dot" style="background:#059669"></div> Login account</div>
                <div class="legend-item"><div class="legend-dot" style="background:#D97706"></div> Personnel Registry</div>
            </div>
            <?php endif; ?>
        </div>

        <!-- CONTENT PANEL -->
        <div class="content-panel">

            <?php if ($is_shared_manage): ?>
            <!-- ══ TEACHER / MULTI-ROLE: SHARED POOL ══ -->
            <?php if ($is_mr_manage): ?>
            <div class="mr-notice">
                <i class="fa-solid fa-layer-group"></i>
                <p><strong style="color:var(--mr)">Multi-Role Questions</strong> — Shared pool. A person appears here when they have an additional role/responsibility. Their Multi-Role context is separate from their base Teacher/Staff context and is visible across applicable student year levels.</p>
            </div>
            <?php else: ?>
            <div class="mr-notice" style="background:var(--eval-bg);border-color:var(--eval-border)">
                <i class="fa-solid fa-layer-group" style="color:var(--eval-color)"></i>
                <p><strong style="color:var(--eval-color)">Teacher Questions</strong> — Shared pool. Every approved Teacher member is evaluated with this same question set.</p>
            </div>
            <?php endif; ?>
            <div class="shared-banner" style="<?= $is_mr_manage ? 'background:var(--mr-bg);border-color:var(--mr-border)' : '' ?>">
                <i class="fa-solid fa-circle-info" style="color:<?= $is_mr_manage ? 'var(--mr)' : 'var(--eval-color)' ?>"></i>
                <p>
                    <strong style="color:<?= $is_mr_manage ? 'var(--mr)' : 'var(--eval-color)' ?>">Questions are shared</strong>
                    — all <?= count($target_users) ?> <?= htmlspecialchars($selected_target) ?> personnel use this same question set.
                    Click a person on the left to preview.
                </p>
            </div>

            <?php if ($selected_user && $selected_user_data): ?>
            <?php
            $desig_tokens = array_filter(array_map('trim', explode(',', $selected_user_data['designation'] ?? '')), fn($t) => $t !== '');
            $sel_sec_label = secondaryRoleLabel($selected_user_data);
            ?>
            <div class="user-header">
                <?php if ($selected_user_data['photo']): ?>
                <img class="user-header-avatar <?= $is_mr_manage ? 'mr-av' : '' ?>" src="../image/<?= htmlspecialchars($selected_user_data['photo']) ?>" alt=""/>
                <?php else: ?>
                <div class="user-header-avatar-ph"><i class="fa-solid fa-user"></i></div>
                <?php endif; ?>
                <div>
                    <div class="user-header-name"><?= htmlspecialchars($selected_user_data['full_name']) ?></div>
                    <?php if (count($desig_tokens) > 1): ?>
                    <div class="desig-tag-row">
                        <?php foreach ($desig_tokens as $dt): ?><span class="desig-tag"><?= htmlspecialchars($dt) ?></span><?php endforeach; ?>
                    </div>
                    <?php else: ?>
                    <div class="user-header-desig"><?= htmlspecialchars($selected_user_data['designation'] ?: $selected_target) ?></div>
                    <?php endif; ?>
                </div>
                <div class="user-header-right">
                    <div class="user-header-tag <?= $is_mr_manage ? 'mr-tag' : '' ?>"><i class="fa-solid fa-clipboard-list" style="margin-right:5px"></i><?= count($questions_list) ?> questions</div>
                    <?php if (!$is_mr_manage && $sel_sec_label): ?>
                    <span class="secondary-role-tag"><i class="fa-solid fa-layer-group" style="font-size:8px"></i> Also <?= htmlspecialchars($sel_sec_label) ?></span>
                    <?php endif; ?>
                    <?php $is_nl = ($selected_user_data['source'] === 'admin_nologin'); ?>
                    <span class="source-tag <?= $is_nl?'nologin':'login' ?>"><?= $is_nl?'Personnel Registry':'Login Account' ?></span>
                </div>
            </div>
            <?php elseif (!empty($target_users)): ?>
            <div class="pick-prompt">
                <i class="fa-solid fa-hand-pointer"></i>
                <p>Select a person from the list to preview their <?= htmlspecialchars($selected_target) ?> evaluation form.</p>
            </div>
            <?php endif; ?>

            <!-- CATEGORY MANAGER (shared pool) -->
            <div class="cat-manager">
                <div class="cat-manager-title <?= $is_mr_manage ? 'mr-ct' : '' ?>"><i class="fa-solid fa-tags"></i> Categories <span style="font-size:10px;color:var(--text-dim);font-weight:400;text-transform:none;letter-spacing:0">(<?= count($categories_list) ?> total)</span></div>
                <div class="cat-chips">
                    <?php if (empty($categories_list)): ?>
                    <span style="font-size:12px;color:var(--text-dim);font-style:italic;">No categories yet.</span>
                    <?php else: foreach ($categories_list as $cat): ?>
                    <div class="cat-chip <?= $is_mr_manage ? 'mr-chip' : '' ?>">
                        <?= htmlspecialchars($cat['category_name']) ?>
                        <button class="cat-chip-btn edit" onclick="openRename(<?= $cat['id'] ?>,'<?= htmlspecialchars(addslashes($cat['category_name'])) ?>')"><i class="fa-solid fa-pen"></i></button>
                        <button class="cat-chip-btn del" onclick="deleteCategory(<?= $cat['id'] ?>,'<?= htmlspecialchars(addslashes($cat['category_name'])) ?>')"><i class="fa-solid fa-xmark"></i></button>
                    </div>
                    <?php endforeach; endif; ?>
                </div>
                <form method="POST">
                    <input type="hidden" name="form_action" value="add_category"/>
                    <input type="hidden" name="target_type" value="<?= htmlspecialchars($selected_target) ?>"/>
                    <input type="hidden" name="eval_type" value="<?= $active_eval ?>"/>
                    <input type="hidden" name="view" value="manage"/>
                    <?php if ($selected_user): ?><input type="hidden" name="user_id" value="<?= $selected_user ?>"/><?php endif; ?>
                    <?php if ($is_mr_manage): ?><input type="hidden" name="mr_filter" value="<?= $mr_filter ?>"/><?php endif; ?>
                    <div class="cat-add-row">
                        <input class="field field-grow" type="text" name="category_name" placeholder="New category name..." required/>
                        <button type="submit" class="btn-sm <?= $is_mr_manage ? 'btn-mr' : 'btn-eval' ?>"><i class="fa-solid fa-plus"></i> Add Category</button>
                    </div>
                </form>
            </div>

            <!-- ADD QUESTION (shared pool) -->
            <form method="POST">
                <input type="hidden" name="form_action" value="insert"/>
                <input type="hidden" name="target_type" value="<?= htmlspecialchars($selected_target) ?>"/>
                <input type="hidden" name="eval_type" value="<?= $active_eval ?>"/>
                <input type="hidden" name="view" value="manage"/>
                <?php if ($selected_user): ?><input type="hidden" name="user_id" value="<?= $selected_user ?>"/><?php endif; ?>
                <?php if ($is_mr_manage): ?><input type="hidden" name="mr_filter" value="<?= $mr_filter ?>"/><?php endif; ?>
                <div class="add-form-row">
                    <select name="category" required style="<?= $is_mr_manage ? 'color:var(--mr)' : '' ?>">
                        <option value="" disabled selected hidden style="color:#A6B2C4">Category</option>
                        <?php foreach ($categories_list as $c): ?>
                        <option value="<?= htmlspecialchars($c['category_name']) ?>"><?= htmlspecialchars($c['category_name']) ?></option>
                        <?php endforeach; ?>
                        <?php if (empty($categories_list)): ?><option value="General">General</option><?php endif; ?>
                    </select>
                    <input type="text" name="question_text" placeholder="Type a new <?= htmlspecialchars($selected_target) ?> question..." required/>
                    <button type="submit" class="btn-sm <?= $is_mr_manage ? 'btn-mr' : 'btn-eval' ?>"><i class="fa-solid fa-plus"></i> Add</button>
                </div>
            </form>

            <!-- QUESTIONS TABLE (shared) -->
            <?php if (empty($questions_list)): ?>
            <div class="empty-state">
                <i class="fa-solid <?= $icons[$selected_target] ?? 'fa-layer-group' ?>" style="color:<?= $hdr_color ?>"></i>
                <p>No questions yet for <?= htmlspecialchars($selected_target) ?> <?= $eval_label ?>.</p>
            </div>
            <?php else:
                $grouped_qs = [];
                foreach ($questions_list as $q) $grouped_qs[$q['category'] ?? 'General'][] = $q;
                $num = 1;
                foreach ($grouped_qs as $section => $section_qs):
            ?>
            <div class="questions-section">
                <div class="section-heading <?= $is_mr_manage ? 'mr-sh' : '' ?>">
                    <i class="fa-solid fa-layer-group" style="font-size:10px"></i>
                    <?= htmlspecialchars($section) ?>
                    <span style="font-size:10px;color:var(--text-dim);font-weight:400">(<?= count($section_qs) ?>)</span>
                </div>
                <table class="q-table">
                    <thead><tr><th style="width:48px">No.</th><th>Question</th><th style="width:90px;text-align:center">Actions</th></tr></thead>
                    <tbody>
                    <?php foreach ($section_qs as $row): ?>
                    <tr>
                        <td><span class="q-num"><?= $num++ ?></span></td>
                        <td>
                            <form id="upd-<?= $row['id'] ?>" method="POST" style="margin:0">
                                <input type="hidden" name="form_action" value="update"/>
                                <input type="hidden" name="question_id" value="<?= $row['id'] ?>"/>
                                <input type="hidden" name="target_type" value="<?= htmlspecialchars($selected_target) ?>"/>
                                <input type="hidden" name="eval_type" value="<?= $active_eval ?>"/>
                                <input type="hidden" name="view" value="manage"/>
                                <?php if ($selected_user): ?><input type="hidden" name="user_id" value="<?= $selected_user ?>"/><?php endif; ?>
                                <?php if ($is_mr_manage): ?><input type="hidden" name="mr_filter" value="<?= $mr_filter ?>"/><?php endif; ?>
                                <input class="inline-input" type="text" name="question_text" value="<?= htmlspecialchars($row['question_text']) ?>"/>
                            </form>
                        </td>
                        <td>
                            <div class="action-btns">
                                <button type="submit" form="upd-<?= $row['id'] ?>" class="icon-btn save-btn" title="Save"><i class="fa-solid fa-floppy-disk"></i></button>
                                <form method="POST" style="margin:0" onsubmit="return confirm('Delete this question?')">
                                    <input type="hidden" name="form_action" value="delete"/>
                                    <input type="hidden" name="question_id" value="<?= $row['id'] ?>"/>
                                    <input type="hidden" name="target_type" value="<?= htmlspecialchars($selected_target) ?>"/>
                                    <input type="hidden" name="eval_type" value="<?= $active_eval ?>"/>
                                    <input type="hidden" name="view" value="manage"/>
                                    <?php if ($selected_user): ?><input type="hidden" name="user_id" value="<?= $selected_user ?>"/><?php endif; ?>
                                    <?php if ($is_mr_manage): ?><input type="hidden" name="mr_filter" value="<?= $mr_filter ?>"/><?php endif; ?>
                                    <button type="submit" class="icon-btn del-btn" title="Delete"><i class="fa-solid fa-trash-can"></i></button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php endforeach; endif; ?>

            <?php else: /* Staff / Principal / Dean — PER-USER MODE */ ?>

            <?php if (!$selected_user || !$selected_user_data): ?>
            <!-- No person selected yet -->
            <div class="select-person-prompt <?= $pu_prompt_cls ?>">
                <div class="prompt-icon">
                    <i class="fa-solid <?= $icons[$selected_target] ?? 'fa-briefcase' ?>"></i>
                </div>
                <h3>Select a <?= htmlspecialchars($selected_target) ?></h3>
                <p>Choose a person from the list on the left to manage their individual <?= $eval_label ?> questions.</p>
                <p style="font-size:12px;color:#9AA6B8;margin-top:4px">Each person has their own unique set of questions.</p>
            </div>

            <?php else: /* Person is selected — show per-user question manager */ ?>

            <?php
            $uq_total = count($user_questions_list);
            $sel_sec_label = secondaryRoleLabel($selected_user_data);
            ?>

            <!-- Per-user banner -->
            <div class="per-user-banner staff-pu">
                <i class="fa-solid fa-user-pen" style="color:<?= $hdr_color ?>"></i>
                <p>
                    <strong style="color:<?= $hdr_color ?>">Individual question set</strong>
                    — Questions added here are exclusive to this person. Other <?= htmlspecialchars($selected_target) ?> members are not affected.
                </p>
            </div>

            <!-- User header -->
            <div class="user-header">
                <?php if ($selected_user_data['photo']): ?>
                <img class="user-header-avatar <?= $pu_av_cls ?>" src="../image/<?= htmlspecialchars($selected_user_data['photo']) ?>" alt=""/>
                <?php else: ?>
                <div class="user-header-avatar-ph"><i class="fa-solid fa-user"></i></div>
                <?php endif; ?>
                <div>
                    <div class="user-header-name"><?= htmlspecialchars($selected_user_data['full_name']) ?></div>
                    <div class="user-header-desig">
                        <?= htmlspecialchars($selected_user_data['designation'] ?: $selected_target) ?>
                    </div>
                </div>
                <div class="user-header-right">
                    <div class="user-header-tag <?= $pu_tag_cls ?>">
                        <i class="fa-solid fa-clipboard-list" style="margin-right:5px"></i><?= $uq_total ?> question<?= $uq_total !== 1 ? 's' : '' ?>
                    </div>
                    <?php if ($sel_sec_label): ?>
                    <span class="secondary-role-tag"><i class="fa-solid fa-layer-group" style="font-size:8px"></i> Also <?= htmlspecialchars($sel_sec_label) ?></span>
                    <?php endif; ?>
                    <?php $is_nl = ($selected_user_data['source'] === 'admin_nologin'); ?>
                    <span class="source-tag <?= $is_nl?'nologin':'login' ?>"><?= $is_nl?'Personnel Registry':'Login Account' ?></span>
                    <?php if ($pu_is_true_staff): ?>
                    <span class="subrole-mini" style="font-size:10px;padding:3px 10px"><?= getSubRole($selected_user_data) ?></span>
                    <?php endif; ?>
                </div>
            </div>

            <!-- PER-USER CATEGORY MANAGER -->
            <div class="cat-manager">
                <div class="cat-manager-title <?= $pu_ct_cls ?>">
                    <i class="fa-solid fa-tags"></i> Categories for this person
                    <span style="font-size:10px;color:var(--text-dim);font-weight:400;text-transform:none;letter-spacing:0">(<?= count($user_categories_list) ?> total)</span>
                </div>
                <div class="cat-chips">
                    <?php if (empty($user_categories_list)): ?>
                    <span style="font-size:12px;color:var(--text-dim);font-style:italic;">No categories yet. Add one below.</span>
                    <?php else: foreach ($user_categories_list as $cat): ?>
                    <div class="cat-chip <?= $pu_chip_cls ?>">
                        <?= htmlspecialchars($cat['category_name']) ?>
                        <button class="cat-chip-btn edit" onclick="openUserRename(<?= $cat['id'] ?>,'<?= htmlspecialchars(addslashes($cat['category_name'])) ?>')"><i class="fa-solid fa-pen"></i></button>
                        <button class="cat-chip-btn del" onclick="deleteUserCategory(<?= $cat['id'] ?>,'<?= htmlspecialchars(addslashes($cat['category_name'])) ?>')"><i class="fa-solid fa-xmark"></i></button>
                    </div>
                    <?php endforeach; endif; ?>
                </div>
                <form method="POST">
                    <input type="hidden" name="form_action" value="user_add_category"/>
                    <input type="hidden" name="target_type" value="<?= htmlspecialchars($selected_target) ?>"/>
                    <input type="hidden" name="eval_type" value="<?= $active_eval ?>"/>
                    <input type="hidden" name="view" value="manage"/>
                    <input type="hidden" name="user_id" value="<?= $selected_user ?>"/>
                    <div class="cat-add-row">
                        <input class="field field-grow" type="text" name="category_name" placeholder="New category name..." required/>
                        <button type="submit" class="btn-sm <?= $pu_btn_cls ?>"><i class="fa-solid fa-plus"></i> Add Category</button>
                    </div>
                </form>
            </div>

            <!-- ADD PER-USER QUESTION FORM -->
            <form method="POST">
                <input type="hidden" name="form_action" value="user_insert"/>
                <input type="hidden" name="target_type" value="<?= htmlspecialchars($selected_target) ?>"/>
                <input type="hidden" name="eval_type" value="<?= $active_eval ?>"/>
                <input type="hidden" name="view" value="manage"/>
                <input type="hidden" name="user_id" value="<?= $selected_user ?>"/>
                <div class="add-form-row">
                    <select name="category" required style="color:var(--staff)">
                        <option value="" disabled selected hidden style="color:#A6B2C4">Category</option>
                        <?php foreach ($user_categories_list as $c): ?>
                        <option value="<?= htmlspecialchars($c['category_name']) ?>"><?= htmlspecialchars($c['category_name']) ?></option>
                        <?php endforeach; ?>
                        <?php if (empty($user_categories_list)): ?><option value="General">General</option><?php endif; ?>
                    </select>
                    <input type="text" name="question_text"
                           placeholder="Write a question for <?= htmlspecialchars($selected_user_data['full_name']) ?>..." required/>
                    <button type="submit" class="btn-sm <?= $pu_btn_cls ?>"><i class="fa-solid fa-plus"></i> Add</button>
                </div>
            </form>

            <!-- PER-USER QUESTIONS TABLE -->
            <?php if (empty($user_questions_list)): ?>
            <div class="empty-state">
                <i class="fa-solid <?= $icons[$selected_target] ?? 'fa-briefcase' ?>" style="color:<?= $hdr_color ?>"></i>
                <p>No questions yet for <strong><?= htmlspecialchars($selected_user_data['full_name']) ?></strong>.<br>
                Add categories above, then write their questions.</p>
            </div>
            <?php else:
                $grouped_uqs = [];
                foreach ($user_questions_list as $q) $grouped_uqs[$q['category'] ?? 'General'][] = $q;
                $num = 1;
                foreach ($grouped_uqs as $section => $section_qs):
            ?>
            <div class="questions-section">
                <div class="section-heading <?= $pu_sh_cls ?>">
                    <i class="fa-solid fa-layer-group" style="font-size:10px"></i>
                    <?= htmlspecialchars($section) ?>
                    <span style="font-size:10px;color:var(--text-dim);font-weight:400">(<?= count($section_qs) ?>)</span>
                </div>
                <table class="q-table">
                    <thead><tr><th style="width:48px">No.</th><th>Question</th><th style="width:90px;text-align:center">Actions</th></tr></thead>
                    <tbody>
                    <?php foreach ($section_qs as $row): ?>
                    <tr>
                        <td><span class="q-num"><?= $num++ ?></span></td>
                        <td>
                            <form id="uupd-<?= $row['id'] ?>" method="POST" style="margin:0">
                                <input type="hidden" name="form_action" value="user_update"/>
                                <input type="hidden" name="question_id" value="<?= $row['id'] ?>"/>
                                <input type="hidden" name="target_type" value="<?= htmlspecialchars($selected_target) ?>"/>
                                <input type="hidden" name="eval_type" value="<?= $active_eval ?>"/>
                                <input type="hidden" name="view" value="manage"/>
                                <input type="hidden" name="user_id" value="<?= $selected_user ?>"/>
                                <input class="inline-input" type="text" name="question_text"
                                       value="<?= htmlspecialchars($row['question_text']) ?>"/>
                            </form>
                        </td>
                        <td>
                            <div class="action-btns">
                                <button type="submit" form="uupd-<?= $row['id'] ?>" class="icon-btn save-btn" title="Save"><i class="fa-solid fa-floppy-disk"></i></button>
                                <form method="POST" style="margin:0" onsubmit="return confirm('Delete this question?')">
                                    <input type="hidden" name="form_action" value="user_delete"/>
                                    <input type="hidden" name="question_id" value="<?= $row['id'] ?>"/>
                                    <input type="hidden" name="target_type" value="<?= htmlspecialchars($selected_target) ?>"/>
                                    <input type="hidden" name="eval_type" value="<?= $active_eval ?>"/>
                                    <input type="hidden" name="view" value="manage"/>
                                    <input type="hidden" name="user_id" value="<?= $selected_user ?>"/>
                                    <button type="submit" class="icon-btn del-btn" title="Delete"><i class="fa-solid fa-trash-can"></i></button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php endforeach; endif; ?>

            <?php endif; /* end person selected check */ ?>
            <?php endif; /* end is_shared_manage check */ ?>

        </div><!-- /content-panel -->
    </div><!-- /manage-layout -->
    <?php endif; /* end manage view */ ?>

    <!-- SHARED POOL RENAME MODAL (Teacher + Multi-Role) -->
    <div class="modal-overlay" id="renameModal">
        <div class="modal">
            <div class="modal-title">Rename Category</div>
            <div class="modal-sub">All questions in this category will be updated automatically.</div>
            <form method="POST">
                <input type="hidden" name="form_action" value="rename_category"/>
                <input type="hidden" name="target_type" value="<?= htmlspecialchars($selected_target) ?>"/>
                <input type="hidden" name="eval_type" value="<?= $active_eval ?>"/>
                <input type="hidden" name="view" value="manage"/>
                <?php if ($selected_user): ?><input type="hidden" name="user_id" value="<?= $selected_user ?>"/><?php endif; ?>
                <?php if (isset($is_mr_manage) && $is_mr_manage): ?><input type="hidden" name="mr_filter" value="<?= $mr_filter ?>"/><?php endif; ?>
                <input type="hidden" name="cat_id" id="renameCatId"/>
                <input type="hidden" name="old_name" id="renameOldName"/>
                <input class="modal-input" type="text" name="new_name" id="renameInput" placeholder="New name..." required/>
                <div class="modal-actions">
                    <button type="button" class="btn-cancel" onclick="closeRename()">Cancel</button>
                    <button type="submit" class="btn-confirm"><i class="fa-solid fa-floppy-disk"></i> Save</button>
                </div>
            </form>
        </div>
    </div>

    <form method="POST" id="deleteCatForm" style="display:none">
        <input type="hidden" name="form_action" value="delete_category"/>
        <input type="hidden" name="target_type" value="<?= htmlspecialchars($selected_target) ?>"/>
        <input type="hidden" name="eval_type" value="<?= $active_eval ?>"/>
        <input type="hidden" name="view" value="manage"/>
        <?php if ($selected_user): ?><input type="hidden" name="user_id" value="<?= $selected_user ?>"/><?php endif; ?>
        <?php if (isset($is_mr_manage) && $is_mr_manage): ?><input type="hidden" name="mr_filter" value="<?= $mr_filter ?>"/><?php endif; ?>
        <input type="hidden" name="cat_id" id="deleteCatId"/>
        <input type="hidden" name="cat_name" id="deleteCatName"/>
    </form>

    <!-- PER-USER RENAME MODAL (Staff / Principal / Dean) -->
    <div class="modal-overlay" id="userRenameModal">
        <div class="modal">
            <div class="modal-title">Rename Category</div>
            <div class="modal-sub">All questions in this category for this person will be updated.</div>
            <form method="POST">
                <input type="hidden" name="form_action" value="user_rename_category"/>
                <input type="hidden" name="target_type" value="<?= htmlspecialchars($selected_target) ?>"/>
                <input type="hidden" name="eval_type" value="<?= $active_eval ?>"/>
                <input type="hidden" name="view" value="manage"/>
                <input type="hidden" name="user_id" value="<?= $selected_user ?? 0 ?>"/>
                <input type="hidden" name="cat_id" id="userRenameCatId"/>
                <input type="hidden" name="old_name" id="userRenameOldName"/>
                <input class="modal-input" type="text" name="new_name" id="userRenameInput" placeholder="New name..." required/>
                <div class="modal-actions">
                    <button type="button" class="btn-cancel" onclick="closeUserRename()">Cancel</button>
                    <button type="submit" class="btn-confirm"><i class="fa-solid fa-floppy-disk"></i> Save</button>
                </div>
            </form>
        </div>
    </div>

    <form method="POST" id="deleteUserCatForm" style="display:none">
        <input type="hidden" name="form_action" value="user_delete_category"/>
        <input type="hidden" name="target_type" value="<?= htmlspecialchars($selected_target) ?>"/>
        <input type="hidden" name="eval_type" value="<?= $active_eval ?>"/>
        <input type="hidden" name="view" value="manage"/>
        <input type="hidden" name="user_id" value="<?= $selected_user ?? 0 ?>"/>
        <input type="hidden" name="cat_id" id="deleteUserCatId"/>
        <input type="hidden" name="cat_name" id="deleteUserCatName"/>
    </form>

    <script>
    // Shared pool category modal (Teacher + Multi-Role)
    function openRename(id, name) {
        document.getElementById('renameCatId').value   = id;
        document.getElementById('renameOldName').value = name;
        document.getElementById('renameInput').value   = name;
        document.getElementById('renameModal').classList.add('open');
        setTimeout(() => document.getElementById('renameInput').select(), 100);
    }
    function closeRename() { document.getElementById('renameModal').classList.remove('open'); }
    document.getElementById('renameModal').addEventListener('click', e => {
        if (e.target === document.getElementById('renameModal')) closeRename();
    });
    function deleteCategory(id, name) {
        if (!confirm(`Delete category "${name}"?\n\nAll questions in it will be moved to "General".`)) return;
        document.getElementById('deleteCatId').value   = id;
        document.getElementById('deleteCatName').value = name;
        document.getElementById('deleteCatForm').submit();
    }

    // Per-user category modal (Staff / Principal / Dean)
    function openUserRename(id, name) {
        document.getElementById('userRenameCatId').value   = id;
        document.getElementById('userRenameOldName').value = name;
        document.getElementById('userRenameInput').value   = name;
        document.getElementById('userRenameModal').classList.add('open');
        setTimeout(() => document.getElementById('userRenameInput').select(), 100);
    }
    function closeUserRename() { document.getElementById('userRenameModal').classList.remove('open'); }
    document.getElementById('userRenameModal').addEventListener('click', e => {
        if (e.target === document.getElementById('userRenameModal')) closeUserRename();
    });
    function deleteUserCategory(id, name) {
        if (!confirm(`Delete category "${name}" for this person?\n\nAll their questions in it will be moved to "General".`)) return;
        document.getElementById('deleteUserCatId').value   = id;
        document.getElementById('deleteUserCatName').value = name;
        document.getElementById('deleteUserCatForm').submit();
    }
    </script>
    <?php $mysqli->close(); ?>
    </body>
    </html>