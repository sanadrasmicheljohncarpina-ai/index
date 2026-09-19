<?php
    session_start();
    require_once 'db.php';
require_once '../shared/EvaluationContextService.php';
    require_once '../shared/SchoolHeadAssignmentService.php';
    sh_ensure_assignment_table($mysqli);
    $isAdminQuestionnaireUser = !empty($_SESSION['user_id']) && in_array($_SESSION['role'] ?? '', ['superadmin','admin'], true);

    // ── MULTI-CATEGORY QUESTION ASSIGNMENTS ────────────────────────
    // A shared question is a reusable item.  Categories are now tags/
    // assignments rather than a single category stored on the question.
    // The legacy `category` column remains as the primary/display category
    // for older pages and reports that still read it directly.
    $mysqli->query("CREATE TABLE IF NOT EXISTS evaluation_question_categories (
        question_id INT UNSIGNED NOT NULL,
        category_id INT UNSIGNED NOT NULL,
        PRIMARY KEY (question_id, category_id),
        KEY idx_eqc_category (category_id),
        CONSTRAINT fk_eqc_question FOREIGN KEY (question_id) REFERENCES evaluation_questions(id) ON DELETE CASCADE,
        CONSTRAINT fk_eqc_category FOREIGN KEY (category_id) REFERENCES question_categories(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    // One-time/idempotent migration of existing single-category questions.
    $mysqli->query("INSERT IGNORE INTO evaluation_question_categories (question_id, category_id)
                    SELECT q.id, c.id
                    FROM evaluation_questions q
                    INNER JOIN question_categories c
                      ON c.target_type COLLATE utf8mb4_general_ci = q.target_type COLLATE utf8mb4_general_ci
                     AND c.eval_type COLLATE utf8mb4_general_ci = q.eval_type COLLATE utf8mb4_general_ci
                     AND c.category_name COLLATE utf8mb4_general_ci = q.category COLLATE utf8mb4_general_ci");

    // ── ENSURE target_type COLUMNS ACCEPT Faculty/EA (School Head Eval) ──
    // These columns were originally ENUM'd to the older target list
    // (Teacher/Staff/Principal/Dean/School/...). 'Faculty' and
    // 'EA' were added later for School Head Evaluation, but an ENUM column
    // silently rejects/blanks out any value not in its list — INSERT IGNORE
    // swallows the warning, so the row looks "saved" but never matches the
    // SELECT that reads it back by target_type. Convert to plain VARCHAR so
    // new target types never need a column migration again.
    function ec_widen_target_type_column(mysqli $mysqli, string $table): void {
        $col = $mysqli->query("SHOW COLUMNS FROM `$table` LIKE 'target_type'");
        if (!$col) return;
        $row = $col->fetch_assoc();
        if (!$row) return;
        if (stripos($row['Type'], 'enum') === 0) {
            $mysqli->query("ALTER TABLE `$table` MODIFY target_type VARCHAR(50) NOT NULL");
        }
    }
    ec_widen_target_type_column($mysqli, 'question_categories');
    ec_widen_target_type_column($mysqli, 'evaluation_questions');
    ec_widen_target_type_column($mysqli, 'user_question_categories');
    ec_widen_target_type_column($mysqli, 'user_questions');

    // Widen eval_type as well so newly-added evaluation directions such as
    // Staff Evaluation cannot be silently rejected by a legacy ENUM.
    function ec_widen_eval_type_column(mysqli $mysqli, string $table): void {
        $col = $mysqli->query("SHOW COLUMNS FROM `$table` LIKE 'eval_type'");
        if (!$col) return;
        $row = $col->fetch_assoc();
        if (!$row) return;
        if (stripos($row['Type'], 'enum') === 0) {
            $mysqli->query("ALTER TABLE `$table` MODIFY eval_type VARCHAR(50) NOT NULL");
        }
    }
    ec_widen_eval_type_column($mysqli, 'question_categories');
    ec_widen_eval_type_column($mysqli, 'evaluation_questions');
    ec_widen_eval_type_column($mysqli, 'user_question_categories');
    ec_widen_eval_type_column($mysqli, 'user_questions');

    // ── ENSURE evaluator_role COLUMN (Dean vs Principal own question banks) ──
    // Dean / Principal Evaluation now keeps two independent shared banks per
    // Faculty/Staff/EA target — one the Dean edits, one the Principal edits —
    // instead of one bank both evaluators shared. Every other eval_type
    // (student/peer/ea) has exactly one bank, so its rows simply carry the
    // sentinel 'shared' and are unaffected by this split.
    function ec_ensure_evaluator_role_column(mysqli $mysqli, string $table): void {
        $col = $mysqli->query("SHOW COLUMNS FROM `$table` LIKE 'evaluator_role'");
        if ($col && $col->num_rows === 0) {
            $mysqli->query("ALTER TABLE `$table` ADD COLUMN evaluator_role VARCHAR(10) NOT NULL DEFAULT 'shared'");
        }
    }
    ec_ensure_evaluator_role_column($mysqli, 'question_categories');
    ec_ensure_evaluator_role_column($mysqli, 'evaluation_questions');

    // ── ENSURE question_categories IS UNIQUE PER (target_type, eval_type,
    // category_name) — NOT globally or per eval_type alone ─────────────
    // Symptom this fixes: adding "Professionalism" under Faculty/school_head
    // gets rejected as "already exists" even though the Faculty list is
    // empty, because "Professionalism" already exists as a category for a
    // different target_type and an overly broad
    // UNIQUE key collides across target types.
    function ec_ensure_category_unique_key(mysqli $mysqli): array {
        $notices = [];
        $res = $mysqli->query("SHOW INDEX FROM question_categories WHERE Non_unique = 0 AND Key_name != 'PRIMARY'");
        if (!$res) {
            $notices[] = "Could not read indexes on question_categories: " . $mysqli->error;
            return $notices;
        }
        $indexes = [];
        while ($row = $res->fetch_assoc()) {
            $indexes[$row['Key_name']][(int)$row['Seq_in_index']] = $row['Column_name'];
        }
        if (empty($indexes)) {
            $notices[] = "No unique index currently exists on question_categories (besides PRIMARY).";
        }
        $correct = ['category_name', 'eval_type', 'evaluator_role', 'target_type'];
        $has_correct = false;
        foreach ($indexes as $key_name => $cols) {
            ksort($cols);
            $cols_sorted = $cols; sort($cols_sorted);
            if ($cols_sorted === $correct) {
                $has_correct = true;
            } else {
                $notices[] = "Found wrongly-scoped unique key `$key_name` on (" . implode(', ', array_values($cols)) . ")";
                if (!$mysqli->query("ALTER TABLE question_categories DROP INDEX `$key_name`")) {
                    $notices[] = "FAILED to drop index `$key_name`: " . $mysqli->error;
                } else {
                    $notices[] = "Dropped wrongly-scoped index `$key_name`.";
                }
            }
        }
        if (!$has_correct) {
            if (!$mysqli->query("ALTER TABLE question_categories ADD UNIQUE KEY uniq_category_scope (target_type, eval_type, evaluator_role, category_name)")) {
                $notices[] = "FAILED to add correct unique key uniq_category_scope: " . $mysqli->error;
            } else {
                $notices[] = "Added correct unique key uniq_category_scope(target_type, eval_type, evaluator_role, category_name).";
            }
        }
        return $notices;
    }
    $ec_schema_heal_notices = ec_ensure_category_unique_key($mysqli);


    // ── MIGRATE LEGACY EA QUESTION SETS INTO THE DEDICATED EA BANK ──
    // Older builds displayed an Executive Assistant Evaluation section but
    // stored its questions in Student/Staff or School Head pools. Copy those
    // existing per-person questions/categories into eval_type='ea' so the EA
    // evaluation page can now read the dedicated EA bank directly. Source
    // rows are retained for backward compatibility and historical answers.
    function ec_migrate_legacy_ea_bank(mysqli $mysqli): void {
        $migrations = [
            ['student', 'Staff'],
            ['school_head', 'Dean'],
            ['school_head', 'Principal'],
        ];

        foreach ($migrations as [$source_eval, $target_type]) {
            $cat = $mysqli->prepare("
                INSERT INTO user_question_categories
                    (user_id, target_type, eval_type, category_name, sort_order)
                SELECT src.user_id, src.target_type, 'ea', src.category_name, src.sort_order
                FROM user_question_categories src
                WHERE src.target_type=?
                  AND src.eval_type=?
                  AND NOT EXISTS (
                      SELECT 1
                      FROM user_question_categories dst
                      WHERE dst.user_id=src.user_id
                        AND dst.target_type=src.target_type
                        AND dst.eval_type='ea'
                        AND dst.category_name=src.category_name
                  )
            ");
            if ($cat) {
                $cat->bind_param('ss', $target_type, $source_eval);
                $cat->execute();
                $cat->close();
            }

            $q = $mysqli->prepare("
                INSERT INTO user_questions
                    (user_id, target_type, eval_type, category, question_text, sort_order)
                SELECT src.user_id, src.target_type, 'ea', src.category, src.question_text, src.sort_order
                FROM user_questions src
                WHERE src.target_type=?
                  AND src.eval_type=?
                  AND NOT EXISTS (
                      SELECT 1
                      FROM user_questions dst
                      WHERE dst.user_id=src.user_id
                        AND dst.target_type=src.target_type
                        AND dst.eval_type='ea'
                        AND dst.category=src.category
                        AND dst.question_text=src.question_text
                  )
            ");
            if ($q) {
                $q->bind_param('ss', $target_type, $source_eval);
                $q->execute();
                $q->close();
            }
        }
    }
    ec_migrate_legacy_ea_bank($mysqli);

    // ── ENSURE account_status COLUMN EXISTS ─────────────────────────
    // Same gate manage_privileged_accounts.php uses — only approved accounts
    // should ever be pulled into the evaluation pools below.
    $colStatus = $mysqli->query("SHOW COLUMNS FROM users LIKE 'account_status'");
    if ($colStatus && $colStatus->num_rows === 0) {
        $mysqli->query("ALTER TABLE users ADD COLUMN account_status VARCHAR(10) NOT NULL DEFAULT 'pending'");
        $mysqli->query("UPDATE users SET account_status = 'approved' WHERE account_status = 'pending'");
    }

    // ── FOLD LEGACY "Non-Teaching Staff" DATA INTO "Staff" ───────────
    // Non-Teaching Staff is not its own questionnaire tab — it remains a
    // system classification only. Executive Assistant Evaluation now has its
    // own dedicated per-person question set; the legacy EA migration above
    // copies the former source rows into that dedicated scope. Anything filed
    // under
    // 'Non-Teaching Staff' moves into Staff; true duplicates (same
    // category name already exists under Staff) are dropped rather than
    // left orphaned. Idempotent: after the first run there is nothing left
    // tagged 'Non-Teaching Staff', so this is a cheap no-op afterwards.
    $mysqli->query("UPDATE IGNORE user_questions SET target_type='Staff' WHERE target_type='Non-Teaching Staff'");
    $mysqli->query("DELETE FROM user_questions WHERE target_type='Non-Teaching Staff'");
    $mysqli->query("UPDATE IGNORE user_question_categories SET target_type='Staff' WHERE target_type='Non-Teaching Staff'");
    $mysqli->query("DELETE FROM user_question_categories WHERE target_type='Non-Teaching Staff'");
    $mysqli->query("UPDATE IGNORE question_categories SET target_type='Staff' WHERE target_type='Non-Teaching Staff'");
    $mysqli->query("DELETE FROM question_categories WHERE target_type='Non-Teaching Staff'");
    $mysqli->query("UPDATE IGNORE evaluation_questions SET target_type='Staff' WHERE target_type='Non-Teaching Staff'");
    $mysqli->query("DELETE FROM evaluation_questions WHERE target_type='Non-Teaching Staff'");

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

    // ── SEED EXECUTIVE ASSISTANT EVALUATION CATEGORIES ────────────
    $ea_cat_count = $mysqli->query("SELECT COUNT(*) as c FROM question_categories WHERE eval_type='ea' AND target_type='EA'")->fetch_assoc()['c'];
    if ($ea_cat_count == 0) {
        $ea_cats = ['Leadership & Coordination','Administrative Management','Communication','Professionalism','Responsiveness'];
        $ins_ea = $mysqli->prepare("INSERT IGNORE INTO question_categories (target_type, category_name, eval_type, sort_order) VALUES ('EA',?,'ea',?)");
        foreach ($ea_cats as $i => $cat) { $ins_ea->bind_param('si', $cat, $i); $ins_ea->execute(); }
        $ins_ea->close();
    }

    // ── SEED STAFF EVALUATION CATEGORIES ───────────────────────────
    // Staff Evaluation is a staff-led evaluation direction. Staff members
    // evaluate the Dean, Principal, and Executive Assistant using one
    // reusable question bank per target.
    $staff_eval_defaults = [
        'Dean'      => ['Leadership & Governance','Communication','Professionalism','Responsiveness','Support & Decision-Making'],
        'Principal' => ['Leadership & Governance','Communication','Professionalism','Responsiveness','Support & Decision-Making'],
        'EA'        => ['Administrative Support','Communication','Professionalism','Responsiveness','Service & Coordination'],
    ];
    foreach ($staff_eval_defaults as $staff_target => $staff_cats) {
        $staff_cat_count = $mysqli->prepare("SELECT COUNT(*) AS c FROM question_categories WHERE eval_type='staff' AND target_type=? AND evaluator_role='shared'");
        $staff_cat_count->bind_param('s', $staff_target);
        $staff_cat_count->execute();
        $staff_n = (int)($staff_cat_count->get_result()->fetch_assoc()['c'] ?? 0);
        $staff_cat_count->close();
        if ($staff_n === 0) {
            $staff_ins = $mysqli->prepare("INSERT IGNORE INTO question_categories (target_type, category_name, eval_type, sort_order, evaluator_role) VALUES (?,?,'staff',?,'shared')");
            foreach ($staff_cats as $i => $cat) {
                $staff_ins->bind_param('ssi', $staff_target, $cat, $i);
                $staff_ins->execute();
            }
            $staff_ins->close();
        }
    }

    // ── SPLIT EXISTING DEAN/PRINCIPAL BANK INTO PER-ROLE COPIES ─────
    // Before the evaluator_role column existed, Faculty/Staff/EA under
    // school_head was ONE bank both Dean and Principal drew from (tagged
    // 'shared' by the column migration above). The EA now needs to edit a
    // separate bank per evaluator, so this one-time, idempotent step
    // duplicates every existing school_head category/question into its own
    // 'dean' and 'principal' copy (carrying over category-tag links), then
    // retires the old 'shared' rows. Running this twice is a safe no-op —
    // it bails out as soon as any real dean/principal row already exists.
    function sh_split_evaluator_role_pools(mysqli $mysqli): void {
        $already = $mysqli->query("SELECT 1 FROM question_categories WHERE eval_type='school_head' AND evaluator_role IN ('dean','principal') LIMIT 1");
        if ($already && $already->num_rows > 0) return;

        $cat_id_map = ['dean' => [], 'principal' => []];
        $cats = $mysqli->query("SELECT id, target_type, category_name, sort_order FROM question_categories WHERE eval_type='school_head' AND evaluator_role='shared'");
        if ($cats && $cats->num_rows > 0) {
            $rows = $cats->fetch_all(MYSQLI_ASSOC);
            $ins = $mysqli->prepare("INSERT INTO question_categories (target_type, category_name, eval_type, sort_order, evaluator_role) VALUES (?,?,'school_head',?,?)");
            foreach ($rows as $row) {
                foreach (['dean', 'principal'] as $role) {
                    $ins->bind_param('ssis', $row['target_type'], $row['category_name'], $row['sort_order'], $role);
                    $ins->execute();
                    $cat_id_map[$role][(int)$row['id']] = $ins->insert_id;
                }
            }
            $ins->close();
        }

        $qs = $mysqli->query("SELECT id, target_type, category, question_text FROM evaluation_questions WHERE eval_type='school_head' AND evaluator_role='shared'");
        if ($qs && $qs->num_rows > 0) {
            $qrows   = $qs->fetch_all(MYSQLI_ASSOC);
            $qins    = $mysqli->prepare("INSERT INTO evaluation_questions (target_type, category, question_text, eval_type, evaluator_role) VALUES (?,?,?,'school_head',?)");
            $linkq   = $mysqli->prepare("SELECT category_id FROM evaluation_question_categories WHERE question_id=?");
            $linkins = $mysqli->prepare("INSERT IGNORE INTO evaluation_question_categories (question_id, category_id) VALUES (?,?)");
            foreach ($qrows as $qrow) {
                $linkq->bind_param('i', $qrow['id']);
                $linkq->execute();
                $old_cat_ids = array_column($linkq->get_result()->fetch_all(MYSQLI_ASSOC), 'category_id');
                foreach (['dean', 'principal'] as $role) {
                    $qins->bind_param('ssss', $qrow['target_type'], $qrow['category'], $qrow['question_text'], $role);
                    $qins->execute();
                    $new_qid = $qins->insert_id;
                    foreach ($old_cat_ids as $old_cat_id) {
                        $new_cat_id = $cat_id_map[$role][(int)$old_cat_id] ?? null;
                        if ($new_cat_id) {
                            $linkins->bind_param('ii', $new_qid, $new_cat_id);
                            $linkins->execute();
                        }
                    }
                }
            }
            $qins->close(); $linkq->close(); $linkins->close();
        }

        $mysqli->query("DELETE FROM evaluation_questions WHERE eval_type='school_head' AND evaluator_role='shared'");
        $mysqli->query("DELETE FROM question_categories WHERE eval_type='school_head' AND evaluator_role='shared'");
    }
    sh_split_evaluator_role_pools($mysqli);

    // ── SEED DEAN / PRINCIPAL EVALUATION CATEGORIES ─────────────
    // Shared question pool defaults for the supervision side: Faculty and
    // the EA. Staff (non-teaching) is its own per-person question set (like
    // Peer-to-Peer's Staff tab), managed via user_questions instead, so it
    // is intentionally not seeded here. Seeded independently per evaluator
    // role so a brand-new install gives Dean and Principal their own
    // starting sets.
    $dnpr_defaults = [
        'Faculty' => ['Teaching Effectiveness','Professionalism','Communication','Classroom Management','Dependability'],
        'EA'      => ['Leadership & Coordination','Administrative Management','Communication','Professionalism','Responsiveness'],
    ];
    foreach (['dean', 'principal'] as $dnpr_role) {
        foreach ($dnpr_defaults as $dnpr_target => $dnpr_cats) {
            $dnpr_count = $mysqli->prepare("SELECT COUNT(*) AS c FROM question_categories WHERE eval_type='school_head' AND evaluator_role=? AND target_type=?");
            $dnpr_count->bind_param('ss', $dnpr_role, $dnpr_target);
            $dnpr_count->execute();
            $dnpr_n = (int)($dnpr_count->get_result()->fetch_assoc()['c'] ?? 0);
            $dnpr_count->close();
            if ($dnpr_n === 0) {
                $dnpr_ins = $mysqli->prepare("INSERT IGNORE INTO question_categories (target_type, category_name, eval_type, sort_order, evaluator_role) VALUES (?,?,'school_head',?,?)");
                foreach ($dnpr_cats as $i => $cat) {
                    $dnpr_ins->bind_param('ssis', $dnpr_target, $cat, $i, $dnpr_role);
                    $dnpr_ins->execute();
                }
                $dnpr_ins->close();
            }
        }
    }

    // ── ACTIVE EVAL TYPE ─────────────────────────────────────────
    $active_eval = $_GET['eval_type'] ?? $_POST['eval_type'] ?? 'student';
    if (!in_array($active_eval, ['student','peer','school_head','ea','staff'])) $active_eval = 'student';

    // ── SELECTED DEAN/PRINCIPAL EVALUATOR ROLE ──────────────────────
    // Dean / Principal Evaluation now keeps two independent shared banks
    // (Faculty/Staff/EA) — one per evaluator role — so the EA can give the
    // Dean and Principal different questions. Irrelevant outside school_head,
    // where every row is tagged with the 'shared' sentinel instead.
    $sh_role = $_GET['sh_role'] ?? $_POST['sh_role'] ?? 'dean';
    if (!in_array($sh_role, ['dean', 'principal'], true)) $sh_role = 'dean';
    $sh_row_role = ($active_eval === 'school_head') ? $sh_role : 'shared';

    // ── CONSTANTS ─────────────────────────────────────────────────
    // Visible questionnaire designations: Teacher, Staff,
    // School Head. Non-Teaching Staff is a system classification only
    // now (see hasStaffFunction()/userHasTeachingAssignment() below) —
    // it never gets its own tab.
    //
    // School Head Evaluation: Principal and Dean are the EVALUATORS here,
    // not evaluation targets — they are the School Heads who evaluate the
    // people under their supervision. What the EA assigns questions to
    // under this tab are those evaluation targets, not the School Heads
    // themselves:
    //   - Faculty: every Faculty Member + Teaching Staff person (shared pool).
    //   - Staff: non-teaching Staff only (shared pool).
    //   - EA: the current Executive Assistant account (shared pool).
    //
    // NOTE: admin/ea_evaluate.php's EA-evaluates-Principal/Dean flow still
    // reads user_questions where eval_type='school_head' AND
    // target_type IN ('Principal','Dean') — that per-person pool has no
    // assignment path from this tab any more (this UI now only writes
    // target_type IN ('Faculty','EA') here). Old Principal/Dean rows, if
    // any, are unaffected in the DB but effectively orphaned from Manage
    // Questions. Flag to the client: ea_evaluate.php's Principal/Dean
    // flow needs its own follow-up fix (or intentional retirement) since
    // the evaluation direction has been reversed.
    $system_categories = ['Teacher', 'Staff', 'School Head'];
    // Peer-to-Peer's "Staff" card is filtered down to Non-Teaching Staff only
    // (see hasNonTeachingStaffFunction() below) and displayed under that label.
    // "School" is a Peer-only per-person context for Principal + Dean, entirely
    // separate from School Head Evaluation's Faculty/EA question pools.
    $peer_categories   = ['Teacher', 'Staff', 'School'];
    $school_head_categories = ['Faculty', 'Staff', 'EA'];
    $ea_categories = ['Staff', 'Dean', 'Principal'];
    $staff_eval_categories = ['Dean', 'Principal', 'EA'];
    $active_categories = ($active_eval === 'school_head')
        ? $school_head_categories
        : (($active_eval === 'peer') ? $peer_categories : (($active_eval === 'ea') ? $ea_categories : (($active_eval === 'staff') ? $staff_eval_categories : $system_categories)));


    // Staff, Principal, and Dean are per-person question sets.
    // They are never created merely
    // because a person has a teaching assignment. Teaching assignments
    // only affect where a person is visible to students.
$per_user_targets = ['Staff', 'Principal', 'Dean', 'School', 'School Head'];
    // "Staff/non-teaching function present" is based on the primary role.
    // guessed from the free-text designation field.
    function hasStaffFunction(array $u): bool {
        return $u['role'] === 'staff';
    }
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

    function hasAnyYearLevelAssignment(mysqli $mysqli, int $user_id): bool {
        $stmt = $mysqli->prepare(
            "SELECT 1 FROM (
                SELECT user_id FROM user_year_levels WHERE user_id=?
                UNION ALL
                SELECT user_id FROM teaching_assignments WHERE user_id=?
            ) x LIMIT 1"
        );
        $stmt->bind_param('ii', $user_id, $user_id);
        $stmt->execute();
        $has_any = (bool)$stmt->get_result()->fetch_row();
        $stmt->close();
        return $has_any;
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
    // has no effect on Teacher/Staff bucketing.
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

    // Resolve the synthetic Student Evaluation -> School Head group to the
    // person's real database target type. Questions remain stored against
    // Principal/Dean, matching the existing per-person architecture.
    function resolvePerUserTargetType(mysqli $mysqli, string $requested_target, int $user_id): ?string {
        $permitted = ['Staff', 'Principal', 'Dean', 'School'];
        if ($requested_target === 'Teaching Staff') return 'Teacher';
        if ($requested_target !== 'School Head') {
            return in_array($requested_target, $permitted, true) ? $requested_target : null;
        }

        $stmt = $mysqli->prepare(
            "SELECT role FROM users
             WHERE id=?
               AND role IN ('principal','dean')
               AND is_active=1
               AND account_status='approved'
             LIMIT 1"
        );
        $stmt->bind_param('i', $user_id);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if (!$row) return null;
        return $row['role'] === 'principal' ? 'Principal' : 'Dean';
    }

    // ── DEAN / PRINCIPAL TARGET ELIGIBILITY HELPERS ───────────────
    // The EA controls teaching assignments through Manage Privileged.
    // A teacher/teaching staff member is visible to:
    //   - Principal: if assigned to any High School level (Grade 7–12)
    //   - Dean: if assigned to any College level (1st–4th Year)
    //   - Both: when the same person has both High School and College assignments.
    // Non-teaching Staff and the EA are eligible for either evaluator.
    function sh_user_year_levels(mysqli $mysqli, int $user_id): array {
        $stmt = $mysqli->prepare("SELECT year_level FROM user_year_levels WHERE user_id=? ORDER BY year_level ASC");
        if (!$stmt) return [];
        $stmt->bind_param('i', $user_id);
        $stmt->execute();
        $res = $stmt->get_result();
        $levels = [];
        while ($r = $res->fetch_assoc()) $levels[] = (string)$r['year_level'];
        $stmt->close();
        return $levels;
    }

    function sh_school_scope(array $levels): array {
        $high = false; $college = false;
        foreach ($levels as $yl) {
            $yl = trim((string)$yl);
            if (preg_match('/^Grade\\s*(7|8|9|10|11|12)\\b/i', $yl)) $high = true;
            if (preg_match('/college/i', $yl) || preg_match('/^(1st|2nd|3rd|4th)\\s*Year\\b/i', $yl)) $college = true;
        }
        return ['high_school'=>$high, 'college'=>$college];
    }

    function sh_is_non_teaching_staff(mysqli $mysqli, int $user_id): bool {
        $stmt = $mysqli->prepare(
            "SELECT
                NOT EXISTS(SELECT 1 FROM teaching_assignments ta WHERE ta.user_id=?)
                AND NOT EXISTS(SELECT 1 FROM user_year_levels yl WHERE yl.user_id=?) AS is_non_teaching"
        );
        if (!$stmt) return false;
        $stmt->bind_param('ii', $user_id, $user_id);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        return (bool)($row['is_non_teaching'] ?? false);
    }

    function sh_is_teaching_person(mysqli $mysqli, array $u): bool {
        if (($u['role'] ?? '') === 'teacher') return true;
        $uid = (int)($u['id'] ?? 0);
        if ($uid <= 0) return false;
        $stmt = $mysqli->prepare(
            "SELECT EXISTS(SELECT 1 FROM teaching_assignments ta WHERE ta.user_id=?)
                    OR EXISTS(SELECT 1 FROM user_year_levels yl WHERE yl.user_id=?) AS has_teaching"
        );
        if (!$stmt) return false;
        $stmt->bind_param('ii', $uid, $uid);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        return (bool)($row['has_teaching'] ?? false);
    }

    function sh_target_allowed_for_evaluator(mysqli $mysqli, int $evaluator_id, int $target_id): bool {
        $ev = $mysqli->prepare("SELECT role FROM users WHERE id=? AND role IN ('principal','dean') AND is_active=1 AND account_status='approved' LIMIT 1");
        if (!$ev) return false;
        $ev->bind_param('i', $evaluator_id);
        $ev->execute();
        $ev_row = $ev->get_result()->fetch_assoc();
        $ev->close();
        if (!$ev_row) return false;

        $tu = $mysqli->prepare("SELECT id, role FROM users WHERE id=? AND is_active=1 AND account_status='approved' LIMIT 1");
        if (!$tu) return false;
        $tu->bind_param('i', $target_id);
        $tu->execute();
        $target = $tu->get_result()->fetch_assoc();
        $tu->close();
        if (!$target) return false;

        // The current Executive Assistant account is the sole EA target.
        if (($target['role'] ?? '') === 'superadmin') {
            return true;
        }

        if (($target['role'] ?? '') === 'staff' && sh_is_non_teaching_staff($mysqli, $target_id)) return true;

        if (!sh_is_teaching_person($mysqli, $target)) return false;
        $scope = sh_school_scope(sh_user_year_levels($mysqli, $target_id));
        return $ev_row['role'] === 'principal' ? $scope['high_school'] : $scope['college'];
    }

    // ── ACTION HANDLERS ───────────────────────────────────────────
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['form_action'])) {
        $action          = $_POST['form_action'];
        $redirect_target = $_POST['target_type'] ?? 'Teacher';
        $post_eval_type  = $_POST['eval_type'] ?? 'student';
        if (!in_array($post_eval_type, ['student','peer','school_head','ea','staff'])) $post_eval_type = 'student';
        $post_sh_role    = $_POST['sh_role'] ?? 'dean';
        if (!in_array($post_sh_role, ['dean', 'principal'], true)) $post_sh_role = 'dean';
        $post_sh_row_role = ($post_eval_type === 'school_head') ? $post_sh_role : 'shared';
        $message         = '';
        $post_user_id    = isset($_POST['user_id']) ? (int)$_POST['user_id'] : 0;
        $effective_user_target = $post_user_id > 0
            ? resolvePerUserTargetType($mysqli, $redirect_target, $post_user_id)
            : null;
        $is_per_user_post = in_array($redirect_target, array_merge($per_user_targets, ['Teaching Staff']), true)
            && $post_user_id > 0
            && $effective_user_target !== null;

        // ── SCHOOL HEAD EVALUATION ASSIGNMENT ACTION ─────────────────────
        // Evaluator-specific target/question assignments are the source of truth
        // consumed by Dean/Principal evaluation pages.
        if ($action === 'schoolhead_assignment_save' && $post_eval_type === 'school_head') {
            if (!$isAdminQuestionnaireUser) {
                http_response_code(403);
                exit('You are not authorized to manage School Head Evaluation assignments.');
            }
            $evaluator_id = isset($_POST['evaluator_id']) ? (int)$_POST['evaluator_id'] : 0;
            $assignment_target_id = isset($_POST['assignment_target_id']) ? (int)$_POST['assignment_target_id'] : 0;
            $assignment_question_ids = (array)($_POST['question_ids'] ?? []);
            if (!sh_target_allowed_for_evaluator($mysqli, $evaluator_id, $assignment_target_id)) {
                $message = 'This target is not eligible for the selected Dean/Principal based on the assigned school levels.';
                $redirect_e = $evaluator_id > 0 ? '&evaluator_id='.$evaluator_id : '';
                header("Location: ?view=school_head_assignments&eval_type=school_head$redirect_e&msg=".urlencode($message));
                exit();
            }
            [$ok, $assignment_message] = sh_save_assignment($mysqli, $evaluator_id, $assignment_target_id, $assignment_question_ids);
            $message = $assignment_message;
            $redirect_e = $evaluator_id > 0 ? '&evaluator_id='.$evaluator_id : '';
            $redirect_t = $assignment_target_id > 0 ? '&assignment_target_id='.$assignment_target_id : '';
            header("Location: ?view=school_head_assignments&eval_type=school_head$redirect_e$redirect_t&msg=".urlencode($message));
            exit();
        }

        // ── PER-USER ACTIONS (Staff, Principal, Dean, School Head) ──
        if ($action === 'user_insert' && $is_per_user_post) {
            $question_text = trim($_POST['question_text']);
            $category      = trim($_POST['category'] ?? 'General');
            // EA Evaluation has its own dedicated per-person question bank.
            // Keep its rows under eval_type='ea' so the EA evaluation form
            // consumes exactly what is managed in this questionnaire section.
            $et            = $post_eval_type;
            $tt            = $effective_user_target;
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
            $et = $post_eval_type; $tt = $effective_user_target;
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

        // ── SHARED POOL ACTIONS (Teacher + legacy) ───────────────
        if ($action === 'insert') {
            $target_type   = $_POST['target_type'];
            $question_text = trim($_POST['question_text']);
            $category_ids  = array_values(array_unique(array_filter(array_map('intval', (array)($_POST['category_ids'] ?? [])))));
            $et            = $post_eval_type;
            if (!empty($question_text)) {
                // Keep the first selected category in the legacy column so
                // older evaluation/report pages continue to work.
                $category = 'General';
                if (!empty($category_ids)) {
                    $cid = $category_ids[0];
                    $cs = $mysqli->prepare("SELECT category_name FROM question_categories WHERE id=? AND target_type=? AND eval_type=? LIMIT 1");
                    $cs->bind_param('iss', $cid, $target_type, $et); $cs->execute();
                    $catrow = $cs->get_result()->fetch_assoc(); $cs->close();
                    if ($catrow) $category = $catrow['category_name'];
                }
                $stmt = $mysqli->prepare("INSERT INTO evaluation_questions (target_type, question_text, category, eval_type, evaluator_role) VALUES (?,?,?,?,?)");
                $stmt->bind_param("sssss", $target_type, $question_text, $category, $et, $post_sh_row_role);
                $stmt->execute();
                $new_qid = $stmt->insert_id;
                $stmt->close();
                if (!empty($category_ids)) {
                    $as = $mysqli->prepare("INSERT IGNORE INTO evaluation_question_categories (question_id, category_id) SELECT ?, id FROM question_categories WHERE id=? AND target_type=? AND eval_type=?");
                    foreach ($category_ids as $cid) { $as->bind_param('iiss', $new_qid, $cid, $target_type, $et); $as->execute(); }
                    $as->close();
                }
                $message = "Question added successfully.";
            }
        }
        if ($action === 'update') {
            $q_id = (int)$_POST['question_id'];
            $question_text = trim($_POST['question_text']);
            $category_ids = array_values(array_unique(array_filter(array_map('intval', (array)($_POST['category_ids'] ?? [])))));
            if (!empty($question_text)) {
                $stmt = $mysqli->prepare("UPDATE evaluation_questions SET question_text=? WHERE id=?");
                $stmt->bind_param("si", $question_text, $q_id);
                $stmt->execute(); $stmt->close();

                // Replace this question's category assignments.
                $del = $mysqli->prepare("DELETE FROM evaluation_question_categories WHERE question_id=?");
                $del->bind_param('i', $q_id); $del->execute(); $del->close();
                $target_for_q = $redirect_target;
                $et = $post_eval_type;
                if (!empty($category_ids)) {
                    $first_name = null;
                    $as = $mysqli->prepare("INSERT IGNORE INTO evaluation_question_categories (question_id, category_id) SELECT ?, id FROM question_categories WHERE id=? AND target_type=? AND eval_type=?");
                    foreach ($category_ids as $cid) {
                        $as->bind_param('iiss', $q_id, $cid, $target_for_q, $et); $as->execute();
                        if ($first_name === null) {
                            $cs = $mysqli->prepare("SELECT category_name FROM question_categories WHERE id=? AND target_type=? AND eval_type=? LIMIT 1");
                            $cs->bind_param('iss', $cid, $target_for_q, $et); $cs->execute();
                            $r = $cs->get_result()->fetch_assoc(); $cs->close();
                            if ($r) $first_name = $r['category_name'];
                        }
                    }
                    $as->close();
                }
                if ($first_name === null) $first_name = 'General';
                $up = $mysqli->prepare("UPDATE evaluation_questions SET category=? WHERE id=?");
                $up->bind_param('si', $first_name, $q_id); $up->execute(); $up->close();
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
            $er            = $post_sh_row_role;
            if (!empty($category_name)) {
                $max  = $mysqli->query("SELECT MAX(sort_order) as m FROM question_categories WHERE target_type='".mysqli_real_escape_string($mysqli,$target_type)."' AND eval_type='$et' AND evaluator_role='".mysqli_real_escape_string($mysqli,$er)."'")->fetch_assoc()['m'] ?? 0;
                $next = $max + 1;
                $stmt = $mysqli->prepare("INSERT IGNORE INTO question_categories (target_type, category_name, eval_type, sort_order, evaluator_role) VALUES (?,?,?,?,?)");
                if (!$stmt) {
                    $message = "DB error preparing insert: " . $mysqli->error;
                } else {
                    $stmt->bind_param("sssis", $target_type, $category_name, $et, $next, $er);
                    if (!$stmt->execute()) {
                        $message = "DB error adding category: " . $stmt->error;
                    } elseif ($stmt->affected_rows > 0) {
                        // Verify it actually landed under the target_type we sent —
                        // catches ENUM columns silently coercing the value.
                        $verify = $mysqli->prepare("SELECT target_type FROM question_categories WHERE category_name=? AND eval_type=? AND evaluator_role=? ORDER BY id DESC LIMIT 1");
                        $verify->bind_param("sss", $category_name, $et, $er);
                        $verify->execute();
                        $vrow = $verify->get_result()->fetch_assoc();
                        $verify->close();
                        $stored_as = $vrow['target_type'] ?? '(unknown)';
                        $message = ($stored_as === $target_type)
                            ? "Category \"$category_name\" added."
                            : "Category \"$category_name\" was saved but stored under target_type=\"$stored_as\" instead of \"$target_type\" — check the target_type column definition.";
                    } else {
                        $exists = $mysqli->prepare("SELECT 1 FROM question_categories WHERE target_type=? AND eval_type=? AND evaluator_role=? AND category_name=? LIMIT 1");
                        $exists->bind_param("ssss", $target_type, $et, $er, $category_name);
                        $exists->execute();
                        $already_here = (bool)$exists->get_result()->fetch_row();
                        $exists->close();
                        $message = $already_here
                            ? "Category \"$category_name\" already exists for $target_type / $et."
                            : "Category \"$category_name\" was rejected by the database, likely due to a unique key that isn't scoped to target_type. Please refresh and try again — this should now be fixed automatically.";
                    }
                    $stmt->close();
                }
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
                $stmt2 = $mysqli->prepare("UPDATE evaluation_questions SET category=? WHERE category=? AND target_type=? AND eval_type=? AND evaluator_role=?");
                $stmt2->bind_param("sssss", $new_name, $old_name, $target, $et, $post_sh_row_role); $stmt2->execute(); $stmt2->close();
                $message = "Category renamed. Questions assigned to it remain linked automatically.";
            }
        }
        if ($action === 'delete_category') {
            $cat_id   = (int)$_POST['cat_id'];
            $target   = trim($_POST['target_type']);
            $et       = $post_eval_type;
            // Preserve the legacy display category for affected questions
            // before the category row (and its assignments) is removed.
            $affected = [];
            $ar = $mysqli->prepare("SELECT question_id FROM evaluation_question_categories WHERE category_id=?");
            $ar->bind_param('i', $cat_id); $ar->execute();
            $rr = $ar->get_result(); while ($x=$rr->fetch_assoc()) $affected[]=(int)$x['question_id']; $ar->close();
            $stmt2 = $mysqli->prepare("DELETE FROM question_categories WHERE id=? AND target_type=? AND eval_type=?");
            $stmt2->bind_param("iss", $cat_id, $target, $et); $stmt2->execute(); $stmt2->close();
            foreach ($affected as $qid) {
                $name = 'General';
                $cs = $mysqli->prepare("SELECT c.category_name FROM evaluation_question_categories a JOIN question_categories c ON c.id=a.category_id WHERE a.question_id=? ORDER BY c.sort_order,c.id LIMIT 1");
                $cs->bind_param('i',$qid); $cs->execute(); $r=$cs->get_result()->fetch_assoc(); $cs->close();
                if ($r) $name=$r['category_name'];
                $up=$mysqli->prepare("UPDATE evaluation_questions SET category=? WHERE id=?"); $up->bind_param('si',$name,$qid); $up->execute(); $up->close();
            }
            $message = "Category deleted. Questions kept in any other assigned categories.";
        }

        $view    = $_POST['view'] ?? 'manage';
        $uid_str = $post_user_id ? '&user_id='.$post_user_id : '';
        $mr_filter_str = '';
        $sh_role_str = ($post_eval_type === 'school_head') ? '&sh_role='.urlencode($post_sh_role) : '';
        header("Location: ?view=$view&target=".urlencode($redirect_target)."&eval_type=".urlencode($post_eval_type)."$uid_str$mr_filter_str$sh_role_str&msg=".urlencode($message));
        exit();
    }

    // ── VIEWS ─────────────────────────────────────────────────────
    $current_view    = $_GET['view']   ?? 'dashboard';
    $selected_target = $_GET['target'] ?? $active_categories[0];
    if (!in_array($selected_target, $active_categories)) $selected_target = $active_categories[0];
    $selected_user   = isset($_GET['user_id']) ? (int)$_GET['user_id'] : null;
    $mr_filter       = 'all';

    // Is this a per-user target (Staff, Principal, Dean)?
    $is_per_user_target = in_array($selected_target, array_merge($per_user_targets, ['Teaching Staff']), true);

    // ── FETCH ALL USERS ───────────────────────────────────────────
    $card_data_student    = ['Teacher' => ['count'=>0,'users'=>[]], 'Staff' => ['count'=>0,'users'=>[]], 'School Head' => ['count'=>0,'users'=>[]]];
    $card_data_peer       = ['Teacher' => ['count'=>0,'users'=>[]], 'Staff' => ['count'=>0,'users'=>[]]];
    $card_data_schoolhead = ['Teacher' => ['count'=>0,'users'=>[]], 'Staff' => ['count'=>0,'users'=>[]]];

    // Per-user question counts come from user_questions. Teacher remains the
    // only shared evaluation_questions target in this questionnaire UI.
    $res = $mysqli->query("SELECT target_type, eval_type, evaluator_role, COUNT(*) as total FROM evaluation_questions GROUP BY target_type, eval_type, evaluator_role");
    if ($res) while ($r = $res->fetch_assoc()) {
        if ($r['eval_type'] === 'student'    && isset($card_data_student[$r['target_type']]))    $card_data_student[$r['target_type']]['count']    = $r['total'];
        if ($r['eval_type'] === 'peer'       && isset($card_data_peer[$r['target_type']]))       $card_data_peer[$r['target_type']]['count']       = $r['total'];
        // school_head now has a separate bank per evaluator role — only
        // fold the currently selected role's count into the visible badge.
        if ($r['eval_type'] === 'school_head' && $r['evaluator_role'] === $sh_role && isset($card_data_schoolhead[$r['target_type']])) $card_data_schoolhead[$r['target_type']]['count'] = $r['total'];
    }

    // Faculty and Non-Teaching Staff are sourced from the exact personnel pool
    // used by Manage Registrations: approved + active Teacher/Staff accounts.
    // IMPORTANT: do NOT filter the query itself to only teaching personnel.
    // We need the complete pool first, then bucket each person into Faculty
    // and/or Non-Teaching Staff below. Otherwise genuine Staff accounts are
    // discarded before the Non-Teaching Staff card can see them.
    $ures = $mysqli->query("
        SELECT id, full_name, designation, photo, source, role, sector,
               COALESCE(source, 'login') as src,
               EXISTS(
                   SELECT 1
                   FROM teaching_assignments ta
                   WHERE ta.user_id = users.id
               ) AS has_teaching_assignment,
               (
                   role = 'teacher'
                   
                   OR sector = 'Teacher'
                   OR EXISTS(
                       SELECT 1
                       FROM teaching_assignments ta2
                       WHERE ta2.user_id = users.id
                   )
                   OR EXISTS(
                       SELECT 1
                       FROM user_year_levels yl2
                       WHERE yl2.user_id = users.id
                   )
               ) AS is_teaching_staff
        FROM users
        WHERE role IN ('teacher','staff')
          AND is_active = 1
          AND account_status = 'approved'
        ORDER BY full_name ASC
    ");
    $all_users = [];
    if ($ures) while ($u = $ures->fetch_assoc()) $all_users[] = $u;

    $sh_res = $mysqli->query("
        SELECT id, full_name, designation, photo, source, role
        FROM users
        WHERE role IN ('principal','dean')
          AND is_active = 1
          AND account_status = 'approved'
        ORDER BY full_name ASC
    ");
    $school_head_users = [];
    if ($sh_res) while ($u = $sh_res->fetch_assoc()) $school_head_users[] = $u;

    // Student Evaluation -> School Head uses the same active Principal/Dean
    // pool as the system already uses elsewhere. The card is a grouping label;
    // each person's questions remain stored under their real Principal/Dean
    // target_type in user_questions.
    $card_data_student['School Head']['users'] = $school_head_users;

    // ── PLACE EACH USER INTO EVALUATION CONTEXTS ──────────────────
    // Staff is never removed just because the person also has another role.
    // A person with an actual teaching assignment is also placed in Faculty;
    // this lets teaching Staff members receive the shared Faculty peer
    // question set while the Non-Teaching Staff card below remains limited
    // to people with no teaching assignment/year-level scope.
    // $faculty_users doubles as the School Head Evaluation -> Faculty
    // roster: same has_teacher pool (Faculty Members + Teaching Staff),
    // one shared list, built once here rather than re-queried below.
    $faculty_users = [];
    $non_teaching_staff_users = [];
    foreach ($all_users as $u) {
        // Faculty/Teaching Staff is determined by an actual teaching
        // assignment as well as the normal Teacher/Faculty role. This is
        // important for personnel whose primary role is Staff but who are
        // also assigned to teach a year level/section: they must still be
        // available under Peer-to-Peer -> Faculty for question assignment.
        // Faculty/Teaching Staff roster is sourced from the same personnel
        // records used by Manage Registrations: a Teacher role/sector, an
        // explicit secondary Teacher role, or an actual teaching assignment.
        $has_teacher   = ((int)($u['is_teaching_staff'] ?? 0) === 1)
            || ec_has_teacher_function($u);
        $has_staff     = ec_has_staff_function($u);
        if ($has_teacher) {
            $card_data_student['Teacher']['users'][] = $u;
            $card_data_peer['Teacher']['users'][] = $u;
            $faculty_users[] = $u;
        }
        if ($has_staff) {
            // Student Evaluation's "Staff" card is Non-Teaching Staff only —
            // same rule as Peer-to-Peer below. A Staff member who teaches or
            // is scoped to a year level is already placed under Teacher
            // above (Faculty) and must not also appear under Staff here,
            // or students would be able to assign/see duplicate question
            // sets for the same person under two designations.
            $is_non_teaching_staff = isNonTeachingStaff($mysqli, (int)$u['id']);
            if ($is_non_teaching_staff) {
                $card_data_student['Staff']['users'][] = $u;
                $non_teaching_staff_users[] = $u;
            }
            // Peer-to-Peer's "Staff" card is Non-Teaching Staff only — a
            // Staff member who teaches or is scoped to a year level should
            // evaluate/be evaluated as Faculty in this tab, not appear twice.
            if ($is_non_teaching_staff) {
                $card_data_peer['Staff']['users'][] = $u;
            }
        }
    }

    // School Head tab: Faculty (Faculty Members + Teaching Staff, same
    // has_teacher pool collected into $faculty_users above) and EA (the
    // current Executive Assistant account). Principal/Dean are the
    // evaluators for this eval_type, not targets, so they are deliberately
    // NOT placed into this roster — $school_head_users above is only used
    // for the separate Peer-to-Peer "School" card further down.
    $current_ea = null;
    $session_ea_id = (int)($_SESSION['user_id'] ?? 0);
    if ($session_ea_id > 0) {
        $eaStmt = $mysqli->prepare("SELECT id, full_name, designation, photo, source, role FROM users WHERE id=? AND role='superadmin' AND is_active=1 AND account_status='approved' LIMIT 1");
        $eaStmt->bind_param('i', $session_ea_id);
        $eaStmt->execute();
        $current_ea = $eaStmt->get_result()->fetch_assoc();
        $eaStmt->close();
    }
    // Fall back to the most recently active approved EA account when this
    // page isn't being viewed in an EA's own session (e.g. an admin/
    // registrar teammate managing questions on the EA's behalf) — the EA
    // target must not silently disappear just because the viewer isn't
    // the EA themselves. Do not create a placeholder/duplicate account.
    if (!$current_ea) {
        $eaRes = $mysqli->query("SELECT id, full_name, designation, photo, source, role FROM users WHERE role='superadmin' AND is_active=1 AND account_status='approved' ORDER BY updated_at DESC LIMIT 1");
        $current_ea = $eaRes ? $eaRes->fetch_assoc() : null;
    }
    $card_data_schoolhead = [
        'Faculty' => ['count' => 0, 'users' => $faculty_users],
        'Staff'   => ['count' => 0, 'users' => $non_teaching_staff_users],
        'EA'      => ['count' => 0, 'users' => $current_ea ? [$current_ea] : []],
    ];

    // Dean / Principal Evaluation — Faculty eligibility is strictly scoped
    // by teaching assignment: the Dean only evaluates College-assigned
    // Faculty, the Principal only evaluates High School/Senior High
    // (Grade 7-12) Faculty. A teacher assigned to both appears for both.
    // Faculty with no relevant year-level assignment is eligible for
    // neither. Staff and the EA are not scoped this way — both evaluators
    // share the same Staff and EA targets.
    if ($active_eval === 'school_head') {
        $sh_scoped_faculty = [];
        foreach ($faculty_users as $fu) {
            $fu_scope = sh_school_scope(sh_user_year_levels($mysqli, (int)$fu['id']));
            $eligible = ($sh_role === 'dean') ? $fu_scope['college'] : $fu_scope['high_school'];
            if ($eligible) $sh_scoped_faculty[] = $fu;
        }
        $card_data_schoolhead['Faculty']['users'] = $sh_scoped_faculty;
    }

    // Executive Assistant Evaluation targets: Staff, Dean, and Principal.
    // Staff here means the Staff roster with no teaching/year-level assignment.
    $card_data_ea = [
        'Staff'         => ['count' => 0, 'users' => $non_teaching_staff_users],
        'Dean'          => ['count' => 0, 'users' => []],
        'Principal'     => ['count' => 0, 'users' => []],
    ];
    foreach ($school_head_users as $shu) {
        if (($shu['role'] ?? '') === 'dean') $card_data_ea['Dean']['users'][] = $shu;
        if (($shu['role'] ?? '') === 'principal') $card_data_ea['Principal']['users'][] = $shu;
    }

    // Staff Evaluation targets: Staff evaluators assess the Dean, Principal,
    // and Executive Assistant. These are direct target persons, not Staff
    // members being evaluated.
    $staff_eval_targets = [
        'Dean'      => ['count' => 0, 'users' => []],
        'Principal' => ['count' => 0, 'users' => []],
        'EA'        => ['count' => 0, 'users' => $current_ea ? [$current_ea] : []],
    ];
    foreach ($school_head_users as $shu) {
        if (($shu['role'] ?? '') === 'dean')      $staff_eval_targets['Dean']['users'] = [$shu];
        if (($shu['role'] ?? '') === 'principal') $staff_eval_targets['Principal']['users'] = [$shu];
    }

    $staff_q_count_res = $mysqli->query("SELECT target_type, COUNT(*) AS total FROM evaluation_questions WHERE eval_type='staff' AND evaluator_role='shared' GROUP BY target_type");
    if ($staff_q_count_res) while ($r = $staff_q_count_res->fetch_assoc()) {
        if (isset($staff_eval_targets[$r['target_type']])) $staff_eval_targets[$r['target_type']]['count'] = (int)$r['total'];
    }

    // Peer-to-Peer's "Dean / Principal" group: Principal + Dean together,
    // evaluated by colleagues. Its database target_type remains 'School' for
    // compatibility, while the interface uses the clearer Dean / Principal label.
    // Its questions remain separate from the Dean / Principal Evaluation
    // Faculty/EA question pools.
    $card_data_peer['School'] = ['count' => 0, 'users' => $school_head_users];

    if ($active_eval === 'peer') {
        // Peer-to-Peer has Faculty, Staff, and Dean / Principal contexts.

        $card_data = $card_data_peer;
    } elseif ($active_eval === 'school_head') {
        $card_data = $card_data_schoolhead;
    } elseif ($active_eval === 'ea') {
        $card_data = $card_data_ea;
    } elseif ($active_eval === 'staff') {
        $card_data = $staff_eval_targets;
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
        // Shared pool categories + questions (used for Teacher)
        // School Head is a UI grouping for Principal/Dean. Resolve the
        // selected person's real target type before reading per-person data.
        $selected_user_target = $selected_user ? resolvePerUserTargetType($mysqli, $selected_target, $selected_user) : null;
        $category_target = ($selected_target === 'School Head' && $selected_user_target)
            ? $selected_user_target
            : $selected_target;

        $cres = $mysqli->prepare("SELECT * FROM question_categories WHERE target_type=? AND eval_type=? AND evaluator_role=? ORDER BY sort_order, category_name");
        $cres->bind_param("sss", $category_target, $active_eval, $sh_row_role); $cres->execute();
        $categories_list = $cres->get_result()->fetch_all(MYSQLI_ASSOC); $cres->close();

        $stmt = $mysqli->prepare("SELECT q.*,
                    COALESCE(GROUP_CONCAT(DISTINCT c.category_name ORDER BY c.sort_order,c.id SEPARATOR '||'), q.category) AS category_labels,
                    COALESCE(SUBSTRING_INDEX(GROUP_CONCAT(DISTINCT c.category_name ORDER BY c.sort_order,c.id SEPARATOR '||'),'||',1), q.category) AS primary_category
                 FROM evaluation_questions q
                 LEFT JOIN evaluation_question_categories a ON a.question_id=q.id
                 LEFT JOIN question_categories c ON c.id=a.category_id
                 WHERE q.target_type=? AND q.eval_type=? AND q.evaluator_role=?
                 GROUP BY q.id
                 ORDER BY primary_category, q.id");
        $question_target = ($selected_target === 'School Head' && $selected_user_target)
            ? $selected_user_target
            : $selected_target;
        $stmt->bind_param("sss", $question_target, $active_eval, $sh_row_role); $stmt->execute();
        $questions_list = $stmt->get_result()->fetch_all(MYSQLI_ASSOC); $stmt->close();

        $all_target_users = $card_data[$selected_target]['users'] ?? [];
        if (false) {
            $target_users = array_values(array_filter($all_target_users, fn($u) => ($mr_filter === 'teacher' ? ec_has_teacher_function($u) : ec_has_staff_function($u))));
        } else {
            $target_users = $all_target_users;
        }

        if ($selected_user && $is_per_user_target) {
            $ur = $mysqli->prepare("SELECT id, full_name, designation, photo, source, role FROM users WHERE id=? LIMIT 1");
            $ur->bind_param("i", $selected_user); $ur->execute();
            $selected_user_data = $ur->get_result()->fetch_assoc(); $ur->close();

            // Per-user categories (scoped to the current designation)
            $user_target = ($selected_target === 'School Head' && $selected_user_target)
                ? $selected_user_target
                : $selected_target;
            // EA Evaluation uses its dedicated per-person question set.
            $user_eval_type = $active_eval;
            $ucres = $mysqli->prepare("SELECT * FROM user_question_categories WHERE user_id=? AND target_type=? AND eval_type=? ORDER BY sort_order, category_name");
            $ucres->bind_param("iss", $selected_user, $user_target, $user_eval_type); $ucres->execute();
            $user_categories_list = $ucres->get_result()->fetch_all(MYSQLI_ASSOC); $ucres->close();

            // Per-user questions (same scoping)
            $uqstmt = $mysqli->prepare("SELECT * FROM user_questions WHERE user_id=? AND target_type=? AND eval_type=? ORDER BY category, sort_order, id");
            $uqstmt->bind_param("iss", $selected_user, $user_target, $user_eval_type); $uqstmt->execute();
            $user_questions_list = $uqstmt->get_result()->fetch_all(MYSQLI_ASSOC); $uqstmt->close();

        } elseif ($selected_user && !$is_per_user_target) {
            $ur = $mysqli->prepare("SELECT id, full_name, designation, photo, source, role FROM users WHERE id=? LIMIT 1");
            $ur->bind_param("i", $selected_user); $ur->execute();
            $selected_user_data = $ur->get_result()->fetch_assoc(); $ur->close();
        }
    }

    $icons = [
        'Teacher'             => 'fa-chalkboard-user',
        'Staff'               => 'fa-briefcase',
        'Principal'           => 'fa-user-tie',
        'Dean'                => 'fa-graduation-cap',
        'School'              => 'fa-building-columns',
        'Faculty'             => 'fa-users',
        'EA'                  => 'fa-user-shield',
        'Teaching Staff'      => 'fa-chalkboard-user',
        'School Head'          => 'fa-user-tie',
    ];

    // Visible labels are context-aware; database target keys remain unchanged.
    // Peer-to-Peer: Staff means the non-teaching staff roster, while School
    // represents the actual Dean/Principal targets.
    function displayTargetLabel(string $type, string $active_eval): string {
        if ($active_eval === 'peer' && $type === 'Staff') return 'Staff';
        if ($active_eval === 'peer' && $type === 'School') return 'Dean / Principal';
        if ($active_eval === 'ea' && $type === 'Staff') return 'Staff';
        if ($type === 'Teacher') return 'Faculty';
        if ($type === 'School Head') return 'Dean / Principal';
        if ($active_eval === 'staff' && $type === 'EA') return 'Executive Assistant';
        return $type;
    }
    $eval_theme = [
        'student'     => ['label' => 'Student Evaluation',      'color' => '#3B82F6', 'bg' => 'rgba(59,130,246,.07)',  'border' => 'rgba(59,130,246,.22)', 'desc' => 'Students evaluate teacher, staff, and eligible school heads.'],
        'peer'        => ['label' => 'Peer-to-Peer Evaluation',  'color' => '#7C3AED', 'bg' => 'rgba(124,58,237,.07)', 'border' => 'rgba(124,58,237,.22)', 'desc' => 'Teacher and staff evaluate colleagues they work with directly.'],
        'school_head' => ['label' => 'Dean / Principal Evaluation',   'color' => '#D97706', 'bg' => 'rgba(217,119,6,.08)',  'border' => 'rgba(217,119,6,.24)',  'desc' => 'The Dean or Principal evaluates faculty, teaching staff, and the EA under their supervision.'],
        'ea'          => ['label' => 'Executive Assistant Evaluation', 'color' => '#0F9F6E', 'bg' => 'rgba(15,159,110,.08)', 'border' => 'rgba(15,159,110,.22)', 'desc' => 'Authorized personnel evaluate the Executive Assistant.'],
        'staff'       => ['label' => 'Staff Evaluation',             'color' => '#0891B2', 'bg' => 'rgba(8,145,178,.08)', 'border' => 'rgba(8,145,178,.22)', 'desc' => 'Staff members evaluate the Dean, Principal, and Executive Assistant.'],
    ];
    $eval_label        = $eval_theme[$active_eval]['label'];
    $eval_color        = $eval_theme[$active_eval]['color'];
    $eval_color_bg     = $eval_theme[$active_eval]['bg'];
    $eval_color_border = $eval_theme[$active_eval]['border'];
    $eval_desc         = $eval_theme[$active_eval]['desc'];

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
     --school-head:#D97706;--school-head-bg:rgba(217,119,6,.09);--school-head-border:rgba(217,119,6,.24);
      --page-bg:#F8FAFC;--card-bg:#FFFFFF;--card-border:#CBD5E1;
      --text-dark:#0F172A;--text-dim:#475569;--track-bg:#CBD5E1;
      --radius:10px;--card-shadow:0 1px 2px rgba(15,23,42,.06),0 4px 12px rgba(15,23,42,.06);
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

    /* ── EVALUATION TYPE TABLE ── */
    .eval-overview-wrap{background:var(--card-bg);border:1px solid var(--card-border);border-radius:14px;box-shadow:var(--card-shadow);margin-bottom:28px;overflow:hidden;}
    .eval-overview-head{display:flex;justify-content:space-between;align-items:center;gap:16px;padding:18px 20px;border-bottom:1px solid var(--card-border);background:linear-gradient(180deg,#fff 0%,#fbfdff 100%);}
    .eval-overview-title{font-size:14px;font-weight:800;color:var(--text-dark);display:flex;align-items:center;gap:9px;}
    .eval-overview-title i{color:#3B82F6;font-size:15px;}
    .eval-overview-sub{font-size:11px;color:var(--text-dim);margin-top:4px;}
    .eval-overview-total{font-size:11px;font-weight:700;color:var(--text-dim);padding:7px 10px;border:1px solid var(--card-border);border-radius:20px;background:#fff;white-space:nowrap;}
    .eval-overview-total i{margin-right:5px;color:#64748B;}
    .eval-table-scroll{width:100%;overflow-x:auto;}
    .eval-overview-table{width:100%;border-collapse:collapse;min-width:760px;}
    .eval-overview-table th{padding:11px 18px;background:var(--page-bg);border-bottom:1px solid var(--card-border);font-size:10px;text-transform:uppercase;letter-spacing:.9px;color:var(--text-dim);text-align:left;white-space:nowrap;}
    .eval-overview-table th.eval-table-number,.eval-overview-table td.eval-table-number{text-align:center;width:110px;}
    .eval-overview-table th.eval-table-action,.eval-overview-table td.eval-table-action{text-align:right;width:120px;}
    .eval-table-row{cursor:pointer;transition:background .18s ease,box-shadow .18s ease;}
    .eval-table-row td{padding:14px 18px;border-bottom:1px solid var(--card-border);vertical-align:middle;}
    .eval-table-row:last-child td{border-bottom:none;}
    .eval-table-row:hover td{background:#F8FAFC;}
    .eval-table-row.is-active td{background:#F8FAFC;}
    .eval-table-row.student-row.is-active{box-shadow:inset 3px 0 0 #3B82F6;}
    .eval-table-row.peer-row.is-active{box-shadow:inset 3px 0 0 #7C3AED;}
    .eval-table-row.schoolhead-row.is-active{box-shadow:inset 3px 0 0 #D97706;}
    .eval-type-cell{display:flex;align-items:center;gap:11px;min-width:255px;}
    .eval-type-icon{width:34px;height:34px;border-radius:9px;display:inline-flex;align-items:center;justify-content:center;flex:0 0 34px;font-size:14px;}
    .student-icon{background:rgba(59,130,246,.1);color:#2563EB;}
    .peer-icon{background:rgba(124,58,237,.1);color:#7C3AED;}
    .schoolhead-icon{background:rgba(217,119,6,.11);color:#D97706;}
    .eval-type-name{font-size:13px;font-weight:800;color:var(--text-dark);line-height:1.25;}
    .eval-type-tag{font-size:10px;color:var(--text-dim);margin-top:3px;}
    .eval-purpose{font-size:11px;line-height:1.5;color:var(--text-dim);max-width:540px;}
    .eval-q-badge{display:inline-flex;align-items:center;justify-content:center;min-width:50px;padding:5px 9px;border-radius:20px;font-size:10px;font-weight:800;}
    .student-badge{background:rgba(59,130,246,.1);color:#2563EB;}
    .peer-badge{background:rgba(124,58,237,.1);color:#7C3AED;}
    .schoolhead-badge{background:rgba(217,119,6,.11);color:#D97706;}
    .eval-open-btn{display:inline-flex;align-items:center;justify-content:center;gap:6px;font-size:10px;font-weight:800;white-space:nowrap;padding:7px 10px;border-radius:7px;min-width:72px;}
    .student-open{color:#2563EB;background:rgba(59,130,246,.08);}
    .peer-open{color:#7C3AED;background:rgba(124,58,237,.08);}
    .schoolhead-open{color:#B45309;background:rgba(217,119,6,.09);}
    .eval-table-row:hover .eval-open-btn{transform:translateX(2px);}

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
    
     .sector-card.school-head-card{border-top-color:var(--school-head);}
     .sector-label.school-head-color{color:var(--school-head);}
     .school-head-pill{background:var(--school-head-bg);border-color:var(--school-head-border);color:var(--school-head);}
     .card-avatar-ph.school-head-ph{background:var(--school-head-bg);color:var(--school-head);}
     .card-avatar-more.school-head-more{background:var(--school-head);}
     .sector-stat-lbl.school-head-l{color:var(--school-head);opacity:.78;}
     .sector-stat-val.school-head-v{color:var(--school-head);}
     .btn-view-school-head{background:var(--school-head);color:#fff;}
     .btn-view-school-head:hover{opacity:.88;}
     .school-head-q{border-color:var(--school-head-border);color:var(--school-head);background:var(--school-head-bg);}
     .user-list-item.active.school-head-active{background:var(--school-head-bg);border-left-color:var(--school-head);}
     .school-head-pu{background:var(--school-head-bg);border:1px solid var(--school-head-border);}
     .school-head-pu i{color:var(--school-head);}
     .school-head-av{border-color:var(--school-head);}
     .user-header-tag.school-head-tag{background:var(--school-head-bg);border-color:var(--school-head-border);color:var(--school-head);}
     .school-head-ct{color:var(--school-head);}
     .cat-chip.school-head-chip{background:var(--school-head-bg);border-color:var(--school-head-border);color:var(--school-head);}
     .section-heading.school-head-sh{color:var(--school-head);border-color:var(--school-head-border);}
     .btn-school-head{background:var(--school-head);color:#fff;}
     .btn-school-head:hover{opacity:.88;}
     .school-head-prompt .prompt-icon{color:var(--school-head);}

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
    .btn-edit-light:hover{background:#E2E8F0;}
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

    /* Role badge styling */
    .secondary-role-tag{display:inline-flex;align-items:center;gap:4px;font-size:9px;font-weight:700;padding:1px 7px;border-radius:20px;background:var(--mr-bg);color:var(--mr);border:1px solid var(--mr-border);flex-shrink:0;}

    /* ── MANAGE LAYOUT ── */
    .manage-layout{display:grid;grid-template-columns:280px minmax(0,1fr);gap:20px;align-items:start;}
    .sidebar{background:var(--card-bg);border:1px solid var(--card-border);border-radius:14px;overflow:hidden;position:sticky;top:20px;box-shadow:var(--card-shadow);}
    .sidebar-title{padding:14px 18px;font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:1.2px;color:var(--text-dim);border-bottom:1px solid var(--card-border);display:flex;align-items:center;gap:7px;}
    .sidebar-count{background:var(--page-bg);border-radius:20px;padding:1px 8px;font-size:10px;font-weight:700;margin-left:auto;}
    .per-user-mode-notice{padding:10px 14px;background:rgba(59,130,246,.05);border-bottom:1px solid rgba(59,130,246,.15);font-size:11px;color:#3B82F6;display:flex;align-items:center;gap:6px;}
    .per-user-mode-notice.staff-notice{background:var(--staff-bg);border-color:var(--staff-border);color:var(--staff);}
    .eval-table-status{width:112px;text-align:center;}
    .eval-assign-btn{display:inline-flex;align-items:center;justify-content:center;gap:7px;min-width:148px;padding:8px 12px;border-radius:9px;text-decoration:none;font-size:11.5px;font-weight:800;border:1px solid transparent;transition:.18s ease;white-space:nowrap;}
    .eval-assign-btn:hover{transform:translateY(-1px);box-shadow:0 5px 12px rgba(15,23,42,.10);}
    .student-assign{background:#EFF6FF;border-color:#BFDBFE;color:#2563EB;}
    .student-assign:hover{background:#DBEAFE;}
    .peer-assign{background:#F5F3FF;border-color:#DDD6FE;color:#7C3AED;}
    .peer-assign:hover{background:#EDE9FE;}
    .schoolhead-assign{background:#FFF7ED;border-color:#FED7AA;color:#D97706;}
    .schoolhead-assign:hover{background:#FFEDD5;}
    .ea-icon{background:rgba(15,159,110,.10);color:#0F9F6E;}
    .ea-badge{background:rgba(15,159,110,.10);color:#0F9F6E;}
    .ea-open{color:#047857;background:rgba(15,159,110,.09);}
    .eval-table-row.ea-row.is-active{box-shadow:inset 3px 0 0 #0F9F6E;}
    .ea-assign{background:#ECFDF5;border-color:#A7F3D0;color:#059669;}
    .ea-assign:hover{background:#D1FAE5;}
    .staff-eval-icon{background:rgba(8,145,178,.10);color:#0891B2;}
    .staff-eval-badge{background:rgba(8,145,178,.10);color:#0E7490;}
    .staff-eval-open{color:#0E7490;background:rgba(8,145,178,.09);}
    .eval-table-row.staff-eval-row.is-active{box-shadow:inset 3px 0 0 #0891B2;}
    .staff-eval-assign{background:#ECFEFF;border-color:#A5F3FC;color:#0E7490;}
    .staff-eval-assign:hover{background:#CFFAFE;}

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
    .user-list-desig{font-size:10px;color:var(--text-dim);white-space:normal;overflow-wrap:anywhere;line-height:1.25;max-width:170px;}
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
    .content-panel{background:var(--card-bg);border:1px solid var(--card-border);border-radius:14px;padding:22px;box-shadow:var(--card-shadow);min-width:0;}
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
    .per-user-desig-tags{max-width:520px;align-items:flex-start;}
    .per-user-desig-tags .desig-tag{white-space:normal;line-height:1.25;word-break:break-word;}
    .per-user-desig-tags .staff-desig-tag{background:var(--staff-bg);color:var(--staff);border-color:var(--staff-border);}
    .per-user-banner.mr-pu{background:var(--mr-bg);border-color:var(--mr-border);}
    .per-user-banner.mr-pu strong{color:var(--mr);}

    .user-header > div:nth-child(2){min-width:0;flex:1;}


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
    .cat-add-row{display:grid;grid-template-columns:minmax(220px,420px) auto;gap:10px;align-items:center;}
    .cat-add-row .field-grow{min-width:0;width:100%;}
    .cat-add-row .btn-sm{white-space:nowrap;}
    .field{background:var(--card-bg);border:1px solid var(--card-border);color:var(--text-dark);padding:9px 13px;border-radius:8px;font-size:13px;font-family:'Inter',sans-serif;outline:none;transition:border-color .2s;}
    .field:focus{border-color:var(--eval-color);}
    .field-grow{flex:1;}
    .btn-sm{width:max-content;justify-self:start;padding:9px 16px;border:none;border-radius:8px;font-weight:700;font-size:13px;cursor:pointer;display:inline-flex;align-items:center;gap:6px;transition:opacity .2s;font-family:'Inter',sans-serif;}
    .btn-eval{background:var(--eval-color);color:#fff;}
    .btn-eval:hover{opacity:.88;}
    .btn-staff{background:var(--staff);color:#fff;}
    .btn-staff:hover{opacity:.88;}
    .btn-mr{background:var(--mr);color:#fff;}
    .btn-mr:hover{opacity:.88;}

    /* ── QUESTION BUILDER ── */
    .add-form-row{display:grid;grid-template-columns:300px minmax(600px,1fr) auto;gap:12px;align-items:center;background:var(--page-bg);border:1px solid var(--card-border);border-radius:10px;padding:16px;margin-bottom:24px;}
    .add-form-row select{background:var(--card-bg);border:1px solid var(--card-border);color:var(--text-dark);padding:11px 14px;border-radius:8px;font-size:13px;font-family:'Inter',sans-serif;outline:none;width:100%;min-width:0;cursor:pointer;font-weight:600;}
    .add-form-row select:focus{border-color:var(--eval-color);}
    .add-form-row input[type="text"]{background:var(--card-bg);border:1px solid var(--card-border);color:var(--text-dark);padding:11px 14px;border-radius:8px;font-size:14px;font-family:'Inter',sans-serif;outline:none;min-width:0;width:100%;}
    .add-form-row input[type="text"]:focus{border-color:var(--eval-color);}
    .add-form-row input::placeholder{color:#A6B2C4;}
    .category-picker{grid-column:1 / -1;display:flex;flex-wrap:wrap;gap:6px;align-items:center;background:var(--card-bg);border:1px solid var(--card-border);border-radius:8px;padding:9px 10px;min-width:0;width:100%;}
    .category-picker::before{content:'Categories';font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:.7px;color:var(--text-dim);margin-right:3px;}
    .category-check{display:inline-flex;align-items:center;gap:5px;font-size:11px;color:var(--text-dim);padding:5px 8px;border-radius:6px;background:var(--page-bg);cursor:pointer;white-space:nowrap;}
    .category-check input{accent-color:var(--eval-color);margin:0;}
    .category-check:hover{color:var(--text-dark);}
    .category-labels{display:flex;flex-wrap:wrap;gap:5px;margin-top:7px;}
    .category-label{display:inline-flex;align-items:center;padding:3px 8px;border-radius:10px;background:var(--eval-bg);border:1px solid var(--eval-border);color:var(--eval-color);font-size:9px;font-weight:700;}
    .category-assignment-row{display:flex;flex-wrap:wrap;align-items:center;gap:5px;margin-top:8px;padding-top:8px;border-top:1px dashed var(--card-border);}
    .category-assignment-label{font-size:9px;font-weight:700;text-transform:uppercase;letter-spacing:.6px;color:var(--text-dim);margin-right:2px;}

    .questions-section{margin-bottom:18px;width:100%;}
    .section-heading{font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:1.5px;color:var(--eval-color);margin-bottom:10px;padding-bottom:7px;border-bottom:1px solid var(--eval-border);display:flex;align-items:center;gap:8px;}
    .section-heading.staff-sh{color:var(--staff);border-color:var(--staff-border);}
    .section-heading.mr-sh{color:var(--mr);border-color:var(--mr-border);}
    .q-table{width:100%;border-collapse:collapse;}
    .q-table th{font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.8px;color:var(--text-dim);padding:10px 14px;background:var(--page-bg);border-bottom:1px solid var(--card-border);text-align:left;}
    .q-table td{padding:13px 14px;border-bottom:1px solid var(--card-border);vertical-align:middle;}
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

    @media(max-width:1100px){
      .manage-layout{grid-template-columns:260px minmax(0,1fr);}
      .content-panel{padding:20px;}
    }
    @media(max-width:920px){
      body{padding:20px 14px;}
      .manage-layout{grid-template-columns:1fr;}
      .sidebar{position:relative;top:auto;}
      .cat-add-row{grid-template-columns:1fr;}
      .cat-add-row .btn-sm{width:100%;justify-content:center;}
      .add-form-row{grid-template-columns:1fr;}
      .add-form-row .btn-sm{width:100%;justify-content:center;}
    }
    @media(max-width:640px){
      .content-panel{padding:14px;}
      .page-header{flex-direction:column;gap:14px;}
      .page-header .btn{width:100%;justify-content:center;}
      .eval-overview-head{align-items:flex-start;flex-direction:column;}
      .eval-overview-total{align-self:flex-start;}
      .eval-overview-table{min-width:680px;}
      .q-table{display:block;overflow-x:auto;}
      .q-table th,.q-table td{padding:10px 9px;}
      .category-picker::before{width:100%;margin-bottom:1px;}
    }
    
/* Admin Module light design system — matches the dashboard */
:root{
  --page-bg:#FFFFFF; --card-bg:#FFFFFF; --card-border:#E2E8F0;
  --inner:#F4F7FB; --text-dark:#172033; --text-dim:#475569;
  --light:#172033; --muted:#475569; --dark:#FFFFFF; --mid:#FFFFFF;
  --border:#E2E8F0; --accent:#0F9F6E; --blue:#0F9F6E;
  --gold:#D97706; --gold-h:#F59E0B; --teal:#0D9488; --violet:#7C3AED;
  --danger:#DC2626; --success:#059669; --radius:12px;
  --card-shadow:0 2px 4px rgba(15,23,42,.05),0 6px 16px rgba(15,23,42,.06);
}
html{background:#FFFFFF;color-scheme:light;}
body{background:#FFFFFF !important;color:#172033 !important;}
a{color:inherit;}
.page-header h1,.page-title,.et-title,.section-title{color:#172033 !important;}
.page-header p,.page-sub,.et-sub,.et-updated,.muted,.hint{color:#475569 !important;}
input,select,textarea{background:#fff !important;color:#172033 !important;border-color:#CBD5E1 !important;}
button{font-family:inherit;}
.table-wrap,.content-panel,.create-panel,.period-card,.stat-card,.sector-card,.person-row,
.sum-card,.standing-panel,.eval-card,.eval-banner,.info-banner,.section,.shell .section,
.history-card,.gl-card,.amber-card,.green-card,.red-card{
  background:#fff !important;border-color:#E2E8F0 !important;box-shadow:0 2px 4px rgba(15,23,42,.04),0 6px 16px rgba(15,23,42,.05) !important;
}
.sector-tabs,.eval-switcher,.tabs,.level-tabs,.status-tabs{
  background:#fff !important;border-color:#E2E8F0 !important;box-shadow:0 2px 4px rgba(15,23,42,.04) !important;
}
.sector-tab,.eval-tab,.tab,.level-tab,.status-tab{color:#475569 !important;}
.sector-tab:hover,.eval-tab:hover,.tab:hover,.level-tab:hover,.status-tab:hover{color:#172033 !important;background:#F4F7FB !important;}
thead tr{background:#F8FAFC !important;}
tbody tr:hover{background:#F8FAFC !important;}
.btn-cancel,.btn-icon,.btn-back{background:#fff !important;color:#172033 !important;border-color:#CBD5E1 !important;}
.empty-state,.empty-cta{color:#475569 !important;}
::-webkit-scrollbar-track{background:#fff;}
::-webkit-scrollbar-thumb{background:#CBD5E1;border:2px solid #fff;}

body{padding:28px !important;}
.sector-card{background:#fff !important;}
.card-avatar-more{background:#334155 !important;}
.category-picker,.cat-manager,.category-assignment-row,.category-assignment-label{
  background:#F8FAFC !important;border-color:#E2E8F0 !important;color:#172033 !important;
}
.desig-subtabs{background:#FFF7ED !important;border-color:#FED7AA !important;}
.desig-subtab{background:#fff !important;color:#475569 !important;}
.desig-subtab.active{background:#FFF7ED !important;color:#D97706 !important;border-color:#FDBA74 !important;}
.peer-info-note{background:#F5F3FF !important;border-color:#DDD6FE !important;color:#475569 !important;}


/* ── SHARP LIGHT ADMIN UI ── */
html { background:#F8FAFC; }
body {
  color:#0F172A !important;
  background:#F8FAFC !important;
  -webkit-font-smoothing:antialiased;
  text-rendering:optimizeLegibility;
}
h1,h2,h3,h4,h5,h6 { color:#0F172A; letter-spacing:-.01em; }
p, .subtitle, .description, .helper, .muted, small { color:#475569; }
label, th { color:#334155; font-weight:600; }
td { color:#0F172A; }
input, select, textarea {
  color:#0F172A;
  background:#FFFFFF;
  border-color:#CBD5E1;
}
input::placeholder, textarea::placeholder { color:#94A3B8; }
.card, .panel, .section, .table-card, .content-card {
  border-color:#CBD5E1;
  box-shadow:0 4px 14px rgba(15,23,42,.07);
}
button, .btn { font-weight:700; }
a { color:inherit; }

/* Persistent evaluation-tab icon coding */
.eval-tab:has(.fa-graduation-cap) > i{color:#2563EB !important;}
.eval-tab:has(.fa-people-arrows) > i{color:#7C3AED !important;}
.eval-tab:has(.fa-user-tie) > i{color:#D97706 !important;}


/* ═══════════════════════════════════════════════════════════════
   QUESTIONNAIRE 2.0 — clearer hierarchy and assignment UX
   ═══════════════════════════════════════════════════════════════ */
.qx-page-header{margin-top:4px;}
.qx-scope-card,.qx-panel,.qx-assignment-card{
  background:#fff;border:1px solid #E2E8F0;border-radius:14px;
  box-shadow:0 4px 16px rgba(15,23,42,.06);margin-bottom:20px;
}
.qx-scope-head,.qx-panel-head,.qx-assignment-head{
  display:flex;justify-content:space-between;gap:18px;align-items:flex-start;
  padding:20px 22px;border-bottom:1px solid #E2E8F0;
}
.qx-eyebrow{font-size:10px;text-transform:uppercase;letter-spacing:1.2px;font-weight:800;color:var(--eval-color);display:flex;align-items:center;gap:7px;margin-bottom:6px}
.qx-eyebrow i{font-size:10px}
.qx-scope-head h2,.qx-panel-head h2,.qx-assignment-head h2{font-family:'Rajdhani',sans-serif;font-size:24px;line-height:1.05;color:#0F172A;margin:0 0 5px}
.qx-scope-head p,.qx-panel-head p,.qx-assignment-head p{font-size:12px;line-height:1.6;color:#64748B;max-width:760px}
.qx-scope-total{font-size:10px;font-weight:800;padding:6px 10px;border:1px solid #E2E8F0;border-radius:999px;color:#475569;white-space:nowrap}
.qx-scope-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(250px,1fr));gap:10px;padding:14px}
.qx-scope-item{display:flex;align-items:center;gap:12px;padding:14px;border:1px solid #E2E8F0;border-radius:12px;text-decoration:none;background:#fff;transition:.18s ease}
.qx-scope-item:hover{border-color:#CBD5E1;transform:translateY(-1px);box-shadow:0 6px 16px rgba(15,23,42,.06)}
.qx-scope-item.active{background:#F8FAFC;border-color:var(--eval-color);box-shadow:0 0 0 2px var(--eval-bg)}
.qx-scope-icon{width:38px;height:38px;border-radius:10px;display:flex;align-items:center;justify-content:center;background:var(--eval-bg);color:var(--eval-color);flex:0 0 38px}
.qx-scope-copy{display:flex;flex-direction:column;gap:2px;min-width:0;flex:1}
.qx-scope-copy strong{font-size:13px;color:#0F172A}
.qx-scope-copy span{font-size:10px;color:#64748B}
.qx-scope-count{font-size:10px;font-weight:800;color:#475569;white-space:nowrap}
.qx-scope-arrow{font-size:10px;color:#94A3B8}
.qx-kpi-row{display:flex;gap:8px;flex-wrap:wrap}
.qx-kpi-row span{font-size:10px;color:#64748B;background:#F8FAFC;border:1px solid #E2E8F0;border-radius:999px;padding:6px 9px;white-space:nowrap}
.qx-kpi-row b{color:#0F172A}
.qx-panel{padding:0 22px 22px}
.qx-panel-head{margin:0 -22px 20px}
.qx-info-banner{display:flex;gap:10px;align-items:flex-start;background:#F8FAFC;border:1px solid #E2E8F0;border-radius:10px;padding:12px 14px;font-size:11px;color:#64748B;line-height:1.5;margin-bottom:18px}
.qx-info-banner i{color:var(--eval-color);margin-top:2px}
.qx-section-block{border:1px solid #E2E8F0;border-radius:12px;overflow:hidden;margin-bottom:18px}
.qx-section-title{display:flex;justify-content:space-between;gap:14px;align-items:center;padding:14px 16px;background:#F8FAFC;border-bottom:1px solid #E2E8F0}
.qx-section-title small{display:block;font-size:10px;color:#64748B;margin-top:3px}
.qx-inline-add,.qx-new-question{display:grid;grid-template-columns:minmax(170px,260px) auto;gap:8px;align-items:center}
.qx-new-question{grid-template-columns:220px minmax(240px,1fr) auto;padding:14px;background:#fff;border:1px solid #E2E8F0;border-radius:10px;margin-bottom:16px}
.qx-inline-add .field{min-width:180px}
.qx-new-question select,.qx-new-question input{padding:10px 12px;border:1px solid #CBD5E1;border-radius:8px;font:inherit;outline:none}
.qx-new-question select:focus,.qx-new-question input:focus{border-color:var(--eval-color);box-shadow:0 0 0 3px var(--eval-bg)}
.qx-category-table,.qx-question-table,.qx-person-table,.qx-assignment-table{width:100%;border-collapse:collapse}
.qx-category-table th,.qx-question-table th,.qx-person-table th,.qx-assignment-table th{padding:10px 12px;text-align:left;background:#F8FAFC;color:#64748B;font-size:10px;text-transform:uppercase;letter-spacing:.7px;border-bottom:1px solid #E2E8F0}
.qx-category-table td,.qx-question-table td,.qx-person-table td,.qx-assignment-table td{padding:12px;border-bottom:1px solid #E2E8F0;vertical-align:middle}
.qx-category-table tr:last-child td,.qx-question-table tr:last-child td,.qx-person-table tr:last-child td,.qx-assignment-table tr:last-child td{border-bottom:none}
.qx-category-name{font-size:12px;font-weight:700;color:#0F172A}
.qx-count-pill{display:inline-flex;min-width:28px;justify-content:center;padding:4px 8px;border-radius:999px;background:var(--eval-bg);color:var(--eval-color);font-size:10px;font-weight:800}
.qx-actions-cell{display:flex;gap:10px}
.qx-action-link{border:0;background:none;color:#475569;font:700 10px Inter,sans-serif;cursor:pointer;padding:4px}
.qx-action-link:hover{color:var(--eval-color)}
.qx-action-link.danger:hover{color:#DC2626}
.qx-question-block{padding-bottom:12px}
.qx-table-wrap,.qx-category-table-wrap,.qx-person-table-wrap,.qx-assignment-table-wrap{overflow:auto}
.qx-question-input{width:100%;padding:9px 10px;border:1px solid transparent;background:transparent;border-radius:7px;color:#0F172A;font:500 13px Inter,sans-serif;outline:none}
.qx-question-input:focus{background:#fff;border-color:var(--eval-color);box-shadow:0 0 0 3px var(--eval-bg)}
.qx-tag-list{display:flex;gap:5px;flex-wrap:wrap;margin-bottom:5px}
.qx-tag,.qx-chip{display:inline-flex;align-items:center;gap:5px;padding:3px 8px;border-radius:999px;background:var(--eval-bg);border:1px solid var(--eval-border);color:var(--eval-color);font-size:9px;font-weight:700}
.qx-muted{font-size:10px;color:#94A3B8}
.qx-category-details summary{font-size:9px;color:#64748B;cursor:pointer;list-style:none}
.qx-category-details summary::-webkit-details-marker{display:none}
.qx-checkbox-grid{display:flex;gap:5px;flex-wrap:wrap;padding-top:7px}
.qx-checkbox-grid label{font-size:9px;padding:5px 7px;background:#F8FAFC;border:1px solid #E2E8F0;border-radius:6px;color:#475569}
.qx-checkbox-grid input{accent-color:var(--eval-color)}
.qx-row-actions{display:flex;gap:6px;align-items:center}
.qx-primary-icon,.qx-danger-icon{border:1px solid #E2E8F0;border-radius:7px;background:#fff;cursor:pointer;font:700 10px Inter,sans-serif;padding:7px 9px}
.qx-primary-icon{color:var(--eval-color);border-color:var(--eval-border)}
.qx-danger-icon{color:#DC2626}
.qx-empty-state{text-align:center;padding:48px 20px;color:#64748B}
.qx-empty-state.compact{padding:30px 20px}
.qx-empty-state i{font-size:34px;opacity:.25;margin-bottom:10px}
.qx-empty-state h3{font-size:16px;color:#0F172A;margin-bottom:5px}
.qx-empty-state p{font-size:11px;line-height:1.6}
.qx-empty-mini{padding:18px 16px;color:#64748B;font-size:11px}
.qx-person-main{display:flex;align-items:center;gap:10px;text-decoration:none}
.qx-person-main.static{cursor:default}
.qx-person-avatar{width:36px;height:36px;border-radius:50%;overflow:hidden;display:flex;align-items:center;justify-content:center;background:#F8FAFC;border:1px solid #E2E8F0;color:#64748B;flex:0 0 36px}
.qx-person-avatar.small{width:30px;height:30px;flex-basis:30px}
.qx-person-avatar img{width:100%;height:100%;object-fit:cover}
.qx-person-main strong{display:block;font-size:12px;color:#0F172A}
.qx-person-main small{display:block;font-size:9px;color:#94A3B8;margin-top:2px}
.qx-role-cell{display:flex;align-items:center;gap:6px;flex-wrap:wrap;font-size:10px;color:#475569}
.qx-role-badge{display:inline-flex;align-items:center;gap:5px;padding:4px 8px;border-radius:999px;background:#F8FAFC;border:1px solid #E2E8F0;color:#475569;font-size:9px;font-weight:700;white-space:nowrap}
.qx-role-badge.faculty{background:#EFF6FF;border-color:#BFDBFE;color:#2563EB}
.qx-role-badge.teaching-staff{background:#ECFDF5;border-color:#A7F3D0;color:#059669}
.qx-role-badge.dean{background:#F5F3FF;border-color:#DDD6FE;color:#7C3AED}
.qx-role-badge.principal{background:#FFF7ED;border-color:#FED7AA;color:#D97706}
.qx-role-badge.ea{background:#F0FDFA;border-color:#99F6E4;color:#0F766E}
.qx-role-badge i{font-size:8px}
.qx-scope-badge{display:inline-flex;align-items:center;justify-content:center;padding:4px 8px;border-radius:999px;font-size:9px;font-weight:700;white-space:nowrap;border:1px solid #E2E8F0;background:#F8FAFC;color:#475569}.qx-scope-badge.high{background:#EFF6FF;border-color:#BFDBFE;color:#2563EB}.qx-scope-badge.college{background:#F0FDFA;border-color:#99F6E4;color:#0F766E}.qx-scope-badge.both{background:#F5F3FF;border-color:#DDD6FE;color:#7C3AED}.qx-scope-badge.all{background:#F8FAFC;border-color:#CBD5E1;color:#475569}
.qx-q-count{display:inline-flex;padding:5px 8px;border-radius:999px;background:#F8FAFC;border:1px solid #E2E8F0;color:#94A3B8;font-size:9px;font-weight:800}
.qx-q-count.has{background:var(--eval-bg);border-color:var(--eval-border);color:var(--eval-color)}
.qx-manage-person{display:inline-flex;align-items:center;justify-content:center;gap:6px;padding:8px 11px;border-radius:8px;text-decoration:none;background:#F8FAFC;border:1px solid #E2E8F0;color:#475569;font-size:10px;font-weight:800}
.qx-manage-person:hover,.qx-manage-person.active{background:var(--eval-bg);border-color:var(--eval-border);color:var(--eval-color)}
.qx-person-table tr.selected,.qx-assignment-table tr.selected{background:#FBFDFF}
.qx-selected-person{margin-top:20px;border:1px solid #CBD5E1;border-radius:12px;padding:16px;background:#fff}
.qx-selected-person-head{display:flex;justify-content:space-between;gap:14px;align-items:flex-start;padding-bottom:14px;border-bottom:1px solid #E2E8F0;margin-bottom:14px}
.qx-selected-person-head h3{font-family:'Rajdhani',sans-serif;font-size:22px;color:#0F172A}
.qx-selected-person-head p{font-size:10px;color:#64748B;margin-top:4px}
.qx-chip-row{display:flex;gap:7px;flex-wrap:wrap;padding:14px 16px}
.qx-chip button{border:0;background:none;color:inherit;cursor:pointer;font-size:9px;padding:0 2px}
.qx-person-question-form{margin:12px 0 16px}
.qx-assignment-card{padding:0}
.qx-assignment-head{margin:0}
.qx-evaluator-tabs{display:flex;gap:8px;flex-wrap:wrap;justify-content:flex-end}
.qx-evaluator-tabs a{display:flex;align-items:center;gap:8px;padding:8px 10px;border:1px solid #E2E8F0;border-radius:10px;text-decoration:none;background:#fff}
.qx-evaluator-tabs a.active{border-color:var(--school-head-border);background:var(--school-head-bg)}
.qx-evaluator-tabs a.qx-eval-dean.active{border-color:#DDD6FE;background:#F5F3FF}
.qx-evaluator-tabs a.qx-eval-principal.active{border-color:#FED7AA;background:#FFF7ED}
.qx-evaluator-tabs strong{display:block;font-size:10px;color:#0F172A}
.qx-evaluator-tabs small{display:block;font-size:9px;color:#64748B;margin-top:1px}
.qx-eval-role{display:inline-flex;align-items:center;gap:4px;margin-top:3px;padding:2px 6px;border-radius:999px;font-size:8px;font-weight:800}
.qx-eval-role.dean{background:#F5F3FF;color:#7C3AED;border:1px solid #DDD6FE}
.qx-eval-role.principal{background:#FFF7ED;color:#D97706;border:1px solid #FED7AA}
.qx-assignment-table-wrap{padding:14px}
.qx-assignment-question-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:10px;padding:18px}
.qx-assignment-question{display:flex;gap:10px;align-items:flex-start;padding:14px;border:1px solid #E2E8F0;border-radius:10px;background:#fff;cursor:pointer;transition:.16s}
.qx-assignment-question:hover{border-color:#CBD5E1;background:#FCFDFE}
.qx-assignment-question.checked{border-color:var(--school-head-border);background:var(--school-head-bg)}
.qx-assignment-question input{position:absolute;opacity:0;pointer-events:none}
.qx-check-box{width:19px;height:19px;border:1px solid #CBD5E1;border-radius:5px;display:flex;align-items:center;justify-content:center;flex:0 0 19px;color:transparent;background:#fff}
.qx-assignment-question.checked .qx-check-box{background:var(--school-head);border-color:var(--school-head);color:#fff}
.qx-check-box i{font-size:10px}
.qx-assignment-question-copy strong{display:block;font-size:9px;color:var(--school-head);text-transform:uppercase;letter-spacing:.7px;margin-bottom:3px}
.qx-assignment-question-copy span{display:block;font-size:12px;line-height:1.5;color:#0F172A}
.qx-assignment-editor{margin-top:20px}
.qx-assignment-footer{display:flex;align-items:center;gap:12px;padding:14px 18px;border-top:1px solid #E2E8F0;background:#F8FAFC}
.qx-assignment-footer span{font-size:10px;color:#64748B}
.qx-schoolhead-save{background:var(--school-head)!important}
.qx-selection-hint{display:flex;align-items:center;gap:9px;margin:14px 0 24px;padding:14px 16px;border:1px dashed var(--school-head-border);background:var(--school-head-bg);border-radius:10px;font-size:11px;color:#64748B}
.qx-selection-hint i{color:var(--school-head)}
@media(max-width:980px){
 .qx-new-question{grid-template-columns:1fr}
 .qx-inline-add{grid-template-columns:1fr}
 .qx-assignment-question-grid{grid-template-columns:1fr}
 .qx-scope-grid{grid-template-columns:1fr}
 .qx-assignment-head{flex-direction:column}
 .qx-evaluator-tabs{justify-content:flex-start}
}
@media(max-width:700px){
 .qx-panel{padding:0 14px 14px}
 .qx-panel-head{margin:0 -14px 16px;padding:16px 14px}
 .qx-scope-head{padding:16px}
 .qx-assignment-footer{align-items:flex-start;flex-direction:column}
}

</style>
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
</head>
    <body class="feature-compact">

    <?php if (isset($_GET['msg']) && $_GET['msg']): ?>
    <div class="toast"><i class="fa-solid fa-circle-check"></i> <?= htmlspecialchars($_GET['msg']) ?></div>
    <?php endif; ?>

    <?php if ($isAdminQuestionnaireUser && !empty($ec_schema_heal_notices)): ?>
    <div class="toast" style="background:#FFF7ED;border-color:#FED7AA;color:#9A3412;flex-direction:column;align-items:flex-start;gap:4px;">
        <div><i class="fa-solid fa-database"></i> <strong>Schema self-heal (question_categories):</strong></div>
        <?php foreach ($ec_schema_heal_notices as $n): ?>
        <div style="font-size:12.5px;padding-left:20px"><?= htmlspecialchars($n) ?></div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <?php
    $total_student_q     = $mysqli->query("SELECT COUNT(*) as c FROM evaluation_questions WHERE eval_type='student'")->fetch_assoc()['c'];
    $total_peer_q        = $mysqli->query("SELECT COUNT(*) as c FROM evaluation_questions WHERE eval_type='peer'")->fetch_assoc()['c'];
    $total_schoolhead_q  = $mysqli->query("SELECT COUNT(*) as c FROM evaluation_questions WHERE eval_type='school_head'")->fetch_assoc()['c'];
    // EA Evaluation has its own dedicated per-person question pool.
    $total_ea_q           = (int)($mysqli->query("SELECT COUNT(*) AS c FROM user_questions WHERE eval_type='ea' AND target_type IN ('Staff','Dean','Principal')")->fetch_assoc()['c'] ?? 0);
    $total_staff_eval_q  = $mysqli->query("SELECT COUNT(*) as c FROM evaluation_questions WHERE eval_type='staff' AND target_type IN ('Dean','Principal','EA')")->fetch_assoc()['c'];

    // Shared evaluation question totals are shown in the table. Staff,
    // Principal, Dean, and Teacher/Staff contribute from per-person user_questions.
    $total_student_uq    = $mysqli->query("SELECT COUNT(*) as c FROM user_questions WHERE eval_type='student'")->fetch_assoc()['c'];
    $total_peer_uq       = $mysqli->query("SELECT COUNT(*) as c FROM user_questions WHERE eval_type='peer'")->fetch_assoc()['c'];
    $total_schoolhead_uq = $mysqli->query("SELECT COUNT(*) as c FROM user_questions WHERE eval_type='school_head'")->fetch_assoc()['c'];
    $total_student_q    += $total_student_uq;
    $total_peer_q        += $total_peer_uq;
    $total_schoolhead_q  += $total_schoolhead_uq;

    $view_param      = $current_view === 'manage' ? 'manage' : 'dashboard';
    $target_param    = $current_view === 'manage' ? '&target='.urlencode($selected_target) : '';
    $uid_param       = $selected_user ? '&user_id='.$selected_user : '';
    $mr_param        = '';
    ?>
    <!-- ══ EVALUATION TYPE TABLE ══ -->
    <div class="eval-overview-wrap">
        <div class="eval-overview-head">
            <div>
                <div class="eval-overview-title"><i class="fa-solid fa-table-list"></i> Evaluation Types</div>
                <div class="eval-overview-sub">Choose an evaluation type, then manage its question bank or assignment rules.</div>
            </div>
            <span class="eval-overview-total"><i class="fa-solid fa-layer-group"></i> <?= (int)$total_student_q + (int)$total_peer_q + (int)$total_schoolhead_q + (int)$total_ea_q + (int)$total_staff_eval_q ?> Total Questions</span>
        </div>
        <div class="eval-table-scroll">
            <table class="eval-overview-table">
                <thead>
                    <tr>
                        <th>Evaluation Type</th>
                        <th>Purpose</th>
                        <th class="eval-table-number">Questions</th>
                        <th class="eval-table-status">Status</th>
                        <th class="eval-table-action">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <tr class="eval-table-row <?= $active_eval === 'student' ? 'is-active student-row' : '' ?>"
                        onclick="window.location='?view=<?= $view_param ?>&eval_type=student<?= $target_param.$uid_param.$mr_param ?>'">
                        <td>
                            <div class="eval-type-cell">
                                <span class="eval-type-icon student-icon"><i class="fa-solid fa-graduation-cap"></i></span>
                                <div>
                                    <div class="eval-type-name">Student Evaluation</div>
                                    <div class="eval-type-tag">Students evaluate personnel</div>
                                </div>
                            </div>
                        </td>
                        <td class="eval-purpose">Students evaluate teacher, staff, and eligible school heads.</td>
                        <td class="eval-table-number"><span class="eval-q-badge student-badge"><?= $total_student_q ?> Q</span></td>
                        <td class="eval-table-status">
                            <span class="eval-open-btn student-open"><i class="fa-solid fa-circle-check"></i> <?= $active_eval === 'student' ? 'Active' : 'Open' ?></span>
                        </td>
                        <td class="eval-table-action" onclick="event.stopPropagation();">
                            <a class="eval-assign-btn student-assign" href="?view=manage&target=Teacher&eval_type=student">
                                <i class="fa-solid fa-user-pen"></i> Manage Questionnaire
                            </a>
                        </td>
                    </tr>
                    <tr class="eval-table-row <?= $active_eval === 'peer' ? 'is-active peer-row' : '' ?>"
                        onclick="window.location='?view=<?= $view_param ?>&eval_type=peer<?= $target_param.$uid_param.$mr_param ?>'">
                        <td>
                            <div class="eval-type-cell">
                                <span class="eval-type-icon peer-icon"><i class="fa-solid fa-people-arrows"></i></span>
                                <div>
                                    <div class="eval-type-name">Peer-to-Peer Evaluation</div>
                                    <div class="eval-type-tag">Colleagues evaluate colleagues</div>
                                </div>
                            </div>
                        </td>
                        <td class="eval-purpose">Teacher and staff evaluate colleagues they work with directly.</td>
                        <td class="eval-table-number"><span class="eval-q-badge peer-badge"><?= $total_peer_q ?> Q</span></td>
                        <td class="eval-table-status">
                            <span class="eval-open-btn peer-open"><i class="fa-solid fa-circle-check"></i> <?= $active_eval === 'peer' ? 'Active' : 'Open' ?></span>
                        </td>
                        <td class="eval-table-action" onclick="event.stopPropagation();">
                            <a class="eval-assign-btn peer-assign" href="?view=manage&target=Teacher&eval_type=peer">
                                <i class="fa-solid fa-user-pen"></i> Manage Questionnaire
                            </a>
                        </td>
                    </tr>
                    <tr class="eval-table-row <?= $active_eval === 'staff' ? 'is-active staff-eval-row' : '' ?>"
                        onclick="window.location='?view=<?= $view_param ?>&eval_type=staff<?= $target_param.$uid_param ?>'">
                        <td>
                            <div class="eval-type-cell">
                                <span class="eval-type-icon staff-eval-icon"><i class="fa-solid fa-users"></i></span>
                                <div>
                                    <div class="eval-type-name">Staff Evaluation</div>
                                    <div class="eval-type-tag">Staff evaluate leadership</div>
                                </div>
                            </div>
                        </td>
                        <td class="eval-purpose">Staff members evaluate the Dean, Principal, and Executive Assistant.</td>
                        <td class="eval-table-number"><span class="eval-q-badge staff-eval-badge"><?= $total_staff_eval_q ?> Q</span></td>
                        <td class="eval-table-status">
                            <span class="eval-open-btn staff-eval-open"><i class="fa-solid fa-circle-check"></i> <?= $active_eval === 'staff' ? 'Active' : 'Open' ?></span>
                        </td>
                        <td class="eval-table-action" onclick="event.stopPropagation();">
                            <a class="eval-assign-btn staff-eval-assign" href="?view=manage&target=Dean&eval_type=staff">
                                <i class="fa-solid fa-user-pen"></i> Manage Questionnaire
                            </a>
                        </td>
                    </tr>
                    <tr class="eval-table-row <?= $active_eval === 'school_head' ? 'is-active schoolhead-row' : '' ?>"
                        onclick="window.location='?view=<?= $view_param ?>&eval_type=school_head<?= $target_param.$uid_param.$mr_param ?>'">
                        <td>
                            <div class="eval-type-cell">
                                <span class="eval-type-icon schoolhead-icon"><i class="fa-solid fa-user-tie"></i></span>
                                <div>
                                    <div class="eval-type-name">Dean / Principal Evaluation</div>
                                    <div class="eval-type-tag">Leadership evaluation</div>
                                </div>
                            </div>
                        </td>
                        <td class="eval-purpose">The Dean or Principal evaluates faculty, teaching staff, and the EA under their supervision.</td>
                        <td class="eval-table-number"><span class="eval-q-badge schoolhead-badge"><?= $total_schoolhead_q ?> Q</span></td>
                        <td class="eval-table-status">
                            <span class="eval-open-btn schoolhead-open"><i class="fa-solid fa-circle-check"></i> <?= $active_eval === 'school_head' ? 'Active' : 'Open' ?></span>
                        </td>
                        <td class="eval-table-action" onclick="event.stopPropagation();">
                            <a class="eval-assign-btn schoolhead-assign" href="?view=manage&target=Faculty&eval_type=school_head&sh_role=dean">
                                <i class="fa-solid fa-user-pen"></i> Manage Questionnaire
                            </a>
                        </td>
                    </tr>
                                    <tr class="eval-table-row <?= $active_eval === 'ea' ? 'is-active ea-row' : '' ?>"
                        onclick="window.location='?view=<?= $view_param ?>&eval_type=ea<?= $target_param.$uid_param.$mr_param ?>'">
                        <td>
                            <div class="eval-type-cell">
                                <span class="eval-type-icon ea-icon"><i class="fa-solid fa-user-shield"></i></span>
                                <div>
                                    <div class="eval-type-name">Executive Assistant Evaluation</div>
                                    <div class="eval-type-tag">Authorized personnel evaluate the EA</div>
                                </div>
                            </div>
                        </td>
                        <td class="eval-purpose">The Executive Assistant evaluates Staff, the Dean, and the Principal.</td>
                        <td class="eval-table-number"><span class="eval-q-badge ea-badge"><?= $total_ea_q ?> Q</span></td>
                        <td class="eval-table-status">
                            <span class="eval-open-btn ea-open"><i class="fa-solid fa-circle-check"></i> <?= $active_eval === 'ea' ? 'Active' : 'Open' ?></span>
                        </td>
                        <td class="eval-table-action" onclick="event.stopPropagation();">
                            <a class="eval-assign-btn ea-assign" href="?view=manage&target=Staff&eval_type=ea">
                                <i class="fa-solid fa-user-pen"></i> Manage Questionnaire
                            </a>
                        </td>
                    </tr>
</tbody>
            </table>
        </div>
    </div>

    <?php if ($current_view === 'dashboard'): ?>
    <!-- The evaluation-type table is now the primary questionnaire workspace.
         Target cards/tabs were removed to keep this page focused and compact. -->

    <?php /* LEGACY — superseded by the Faculty/Staff/EA shared-bank "manage"
             view below (now split per Dean/Principal evaluator role). No
             link in the UI points here any more; left in place, unlinked,
             only so any still-open bookmark/tab doesn't hard-error. Safe to
             delete outright in a future pass once confirmed unused. */ ?>
    <?php elseif ($current_view === 'school_head_assignments' && $active_eval === 'school_head'): ?>
    <?php if (!$isAdminQuestionnaireUser) { http_response_code(403); exit('You are not authorized to manage Dean / Principal Evaluation assignments.'); } ?>
    <!-- ══ DEAN / PRINCIPAL EVALUATION ASSIGNMENTS ══ -->
    <?php
    $sh_evaluators = [];
    $evr = $mysqli->query("SELECT id, full_name, role, designation FROM users
        WHERE role IN ('principal','dean') AND is_active=1 AND account_status='approved'
        ORDER BY FIELD(role,'dean','principal'), full_name ASC");
    if ($evr) $sh_evaluators = $evr->fetch_all(MYSQLI_ASSOC);

    $sh_targets = [];

    // Faculty = teachers + teaching staff who have at least one school-level
    // teaching assignment. Their eligibility is filtered per selected evaluator.
    foreach (($faculty_users ?? []) as $tu) {
        $tu['assignment_group'] = 'Faculty';
        $tu['assignment_label'] = (($tu['role'] ?? '') === 'staff') ? 'Teaching Staff' : 'Faculty';
        $tu['year_levels'] = sh_user_year_levels($mysqli, (int)$tu['id']);
        $tu['school_scope'] = sh_school_scope($tu['year_levels']);
        $sh_targets[] = $tu;
    }

    // Staff = non-teaching staff only. A Staff member who teaches or has a
    // year-level assignment remains in Faculty/Teaching Staff instead.
    foreach (($non_teaching_staff_users ?? []) as $tu) {
        $tu['assignment_group'] = 'Staff';
        $tu['assignment_label'] = 'Staff';
        $tu['year_levels'] = [];
        $tu['school_scope'] = ['high_school'=>false,'college'=>false];
        $sh_targets[] = $tu;
    }

    if (!empty($current_ea)) {
        $ea_target = $current_ea;
        $ea_target['assignment_group'] = 'EA';
        $ea_target['assignment_label'] = 'Executive Assistant';
        $ea_target['year_levels'] = [];
        $ea_target['school_scope'] = ['high_school'=>false,'college'=>false];
        $sh_targets[] = $ea_target;
    }

    $sh_selected_evaluator = isset($_GET['evaluator_id']) ? (int)$_GET['evaluator_id'] : ($sh_evaluators[0]['id'] ?? 0);
    if (!array_filter($sh_evaluators, fn($e) => (int)$e['id'] === $sh_selected_evaluator)) {
        $sh_selected_evaluator = $sh_evaluators[0]['id'] ?? 0;
    }

    // Filter the target table according to the selected evaluator's scope.
    $selected_eval_role = '';
    foreach ($sh_evaluators as $ev) {
        if ((int)$ev['id'] === $sh_selected_evaluator) { $selected_eval_role = $ev['role']; break; }
    }
    $sh_targets = array_values(array_filter($sh_targets, function(array $t) use ($selected_eval_role) {
        if ($t['assignment_group'] === 'Staff' || $t['assignment_group'] === 'EA') return true;
        $scope = $t['school_scope'] ?? ['high_school'=>false,'college'=>false];
        return $selected_eval_role === 'principal' ? !empty($scope['high_school']) : !empty($scope['college']);
    }));

    $sh_selected_target = isset($_GET['assignment_target_id']) ? (int)$_GET['assignment_target_id'] : 0;
    if ($sh_selected_target && !array_filter($sh_targets, fn($t) => (int)$t['id'] === $sh_selected_target)) {
        $sh_selected_target = 0;
    }

    // Assignment counts for the selected Dean/Principal, used in the target table.
    $sh_assigned_counts = [];
    if ($sh_selected_evaluator) {
        $arc = $mysqli->prepare("SELECT target_user_id, COUNT(*) AS total
                                 FROM school_head_evaluation_assignments
                                 WHERE evaluator_id=?
                                 GROUP BY target_user_id");
        if ($arc) {
            $arc->bind_param('i', $sh_selected_evaluator);
            $arc->execute();
            $arr = $arc->get_result();
            while ($rr = $arr->fetch_assoc()) $sh_assigned_counts[(int)$rr['target_user_id']] = (int)$rr['total'];
            $arc->close();
        }
    }

    $sh_target_row = null;
    foreach ($sh_targets as $t) if ((int)$t['id'] === $sh_selected_target) { $sh_target_row = $t; break; }

    $sh_questions = [];
    $sh_existing_ids = [];
    if ($sh_target_row) {
        $sh_target_type = $sh_target_row['assignment_group'] ?? 'Faculty';
        $qst = $mysqli->prepare("SELECT id, category, question_text FROM evaluation_questions
                                 WHERE eval_type='school_head' AND target_type=?
                                 ORDER BY category, id");
        $qst->bind_param('s', $sh_target_type);
        $qst->execute();
        $sh_questions = $qst->get_result()->fetch_all(MYSQLI_ASSOC);
        $qst->close();

        $existing_stmt = $mysqli->prepare("SELECT question_id
                                           FROM school_head_evaluation_assignments
                                           WHERE evaluator_id=? AND target_user_id=?
                                           ORDER BY question_id");
        if ($existing_stmt) {
            $existing_stmt->bind_param('ii', $sh_selected_evaluator, $sh_selected_target);
            $existing_stmt->execute();
            $res = $existing_stmt->get_result();
            while ($rr = $res->fetch_assoc()) $sh_existing_ids[] = (int)$rr['question_id'];
            $existing_stmt->close();
        }
    }
    ?>
    <div class="page-header qx-page-header">
        <div>
            <h1><i class="fa-solid fa-user-tie" style="color:var(--school-head);margin-right:8px"></i>Dean / Principal Evaluation Assignments</h1>
            <p>Choose a Dean or Principal, then assign questions to the eligible Faculty, Teaching Staff, Staff, and Executive Assistant targets under their supervision.</p>
        </div>
        <a href="?view=dashboard&eval_type=school_head" class="btn btn-back"><i class="fa-solid fa-arrow-left"></i> Back to Evaluation Types</a>
    </div>

    <?php if (empty($sh_evaluators)): ?>
        <div class="qx-empty-state">
            <i class="fa-solid fa-user-tie"></i>
            <h3>No active Dean or Principal evaluators</h3>
            <p>Approved, active Dean and Principal accounts appear here automatically. Faculty targets are filtered by the teaching year levels assigned in Manage Privileged.</p>
        </div>
    <?php else: ?>
        <section class="qx-assignment-card">
            <div class="qx-assignment-head">
                <div>
                    <div class="qx-eyebrow"><i class="fa-solid fa-user-check"></i> Evaluator</div>
                    <h2>Select the Dean or Principal who will conduct the evaluation</h2>
                </div>
                <div class="qx-evaluator-tabs">
                    <?php foreach ($sh_evaluators as $ev): ?>
                        <a class="<?= (int)$ev['id']===$sh_selected_evaluator ? 'active ' : '' ?>qx-eval-<?= $ev['role']==='dean' ? 'dean' : 'principal' ?>"
                           href="?view=school_head_assignments&eval_type=school_head&evaluator_id=<?= (int)$ev['id'] ?>">
                            <span class="qx-person-avatar small"><i class="fa-solid <?= $ev['role']==='dean' ? 'fa-graduation-cap' : 'fa-user-tie' ?>"></i></span>
                            <span>
                                <strong><?= htmlspecialchars($ev['full_name']) ?></strong>
                                <small><?= htmlspecialchars(ucfirst($ev['role'])) ?></small>
                                <span class="qx-eval-role <?= $ev['role']==='dean' ? 'dean' : 'principal' ?>">
                                    <i class="fa-solid <?= $ev['role']==='dean' ? 'fa-graduation-cap' : 'fa-user-tie' ?>"></i><?= htmlspecialchars(ucfirst($ev['role'])) ?>
                                </span>
                            </span>
                        </a>
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="qx-assignment-table-wrap">
                <table class="qx-assignment-table">
                    <thead><tr><th>Target person</th><th>Evaluation role</th><th>Teaching scope</th><th>Assigned questions</th><th style="width:185px">Action</th></tr></thead>
                    <tbody>
                    <?php foreach ($sh_targets as $t): ?>
                        <?php $cnt=(int)($sh_assigned_counts[(int)$t['id']] ?? 0); ?>
                        <tr class="<?= $sh_selected_target === (int)$t['id'] ? 'selected' : '' ?>">
                            <td>
                                <div class="qx-person-main static">
                                    <span class="qx-person-avatar">
                                        <?php if (!empty($t['photo'])): ?><img src="../image/<?= htmlspecialchars($t['photo']) ?>" alt=""/>
                                        <?php else: ?><i class="fa-solid fa-user"></i><?php endif; ?>
                                    </span>
                                    <span><strong><?= htmlspecialchars($t['full_name']) ?></strong><small><?= htmlspecialchars($t['assignment_group']) ?></small></span>
                                </div>
                            </td>
                            <td>
                                <?php
                                    $role_cls = 'faculty';
                                    $role_icon = 'fa-users';
                                    if ($t['assignment_group'] === 'Faculty' && $t['role'] === 'staff') { $role_cls = 'teaching-staff'; $role_icon = 'fa-person-chalkboard'; }
                                    elseif ($t['assignment_group'] === 'EA') { $role_cls = 'ea'; $role_icon = 'fa-user-shield'; }
                                ?>
                                <span class="qx-role-badge <?= $role_cls ?>"><i class="fa-solid <?= $role_icon ?>"></i><?= htmlspecialchars($t['assignment_label']) ?></span>
                            </td>
                            <td>
                                <?php
                                    if ($t['assignment_group'] === 'Staff' || $t['assignment_group'] === 'EA') {
                                        $scope_label = 'All levels';
                                        $scope_cls = 'all';
                                    } elseif (!empty($t['school_scope']['high_school']) && !empty($t['school_scope']['college'])) {
                                        $scope_label = 'High School + College';
                                        $scope_cls = 'both';
                                    } elseif (!empty($t['school_scope']['high_school'])) {
                                        $scope_label = 'High School';
                                        $scope_cls = 'high';
                                    } else {
                                        $scope_label = 'College';
                                        $scope_cls = 'college';
                                    }
                                ?>
                                <span class="qx-scope-badge <?= $scope_cls ?>"><?= htmlspecialchars($scope_label) ?></span>
                            </td>
                            <td><span class="qx-q-count <?= $cnt > 0 ? 'has' : '' ?>"><?= $cnt ?> assigned</span></td>
                            <td>
                                <a class="qx-manage-person <?= $sh_selected_target === (int)$t['id'] ? 'active' : '' ?>"
                                   href="?view=school_head_assignments&eval_type=school_head&evaluator_id=<?= $sh_selected_evaluator ?>&assignment_target_id=<?= (int)$t['id'] ?>">
                                    <i class="fa-solid fa-sliders"></i> Assign Questions
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </section>

        <?php if ($sh_target_row): ?>
            <section class="qx-assignment-card qx-assignment-editor">
                <div class="qx-assignment-head">
                    <div>
                        <div class="qx-eyebrow"><i class="fa-solid fa-list-check"></i> Assignment editor</div>
                        <h2><?= htmlspecialchars($sh_target_row['full_name']) ?></h2>
                        <p><?= htmlspecialchars($sh_target_row['assignment_label']) ?> · <?= htmlspecialchars($sh_evaluators[array_search($sh_selected_evaluator, array_column($sh_evaluators,'id'))]['full_name'] ?? '') ?> as evaluator<?= $selected_eval_role === 'principal' ? ' (High School scope)' : ' (College scope)' ?>.</p>
                    </div>
                    <span class="qx-kpi-row"><span><b><?= count($sh_existing_ids) ?></b> selected</span><span><b><?= count($sh_questions) ?></b> available</span></span>
                </div>

                <?php if (empty($sh_questions)): ?>
                    <div class="qx-empty-state compact">
                        <i class="fa-solid fa-clipboard-list"></i>
                        <h3>No questions available</h3>
                        <p>Create questions in the Dean / Principal Evaluation question bank first.</p>
                    </div>
                <?php else: ?>
                    <form method="POST">
                        <input type="hidden" name="form_action" value="schoolhead_assignment_save">
                        <input type="hidden" name="eval_type" value="school_head">
                        <input type="hidden" name="evaluator_id" value="<?= $sh_selected_evaluator ?>">
                        <input type="hidden" name="assignment_target_id" value="<?= $sh_selected_target ?>">

                        <div class="qx-assignment-question-grid">
                            <?php foreach ($sh_questions as $q): ?>
                                <label class="qx-assignment-question <?= in_array((int)$q['id'],$sh_existing_ids,true) ? 'checked' : '' ?>">
                                    <input type="checkbox" name="question_ids[]" value="<?= (int)$q['id'] ?>" <?= in_array((int)$q['id'],$sh_existing_ids,true)?'checked':'' ?>>
                                    <span class="qx-check-box"><i class="fa-solid fa-check"></i></span>
                                    <span class="qx-assignment-question-copy">
                                        <strong><?= htmlspecialchars($q['category'] ?: 'General') ?></strong>
                                        <span><?= htmlspecialchars($q['question_text']) ?></span>
                                    </span>
                                </label>
                            <?php endforeach; ?>
                        </div>

                        <div class="qx-assignment-footer">
                            <button type="submit" class="btn btn-primary qx-schoolhead-save"><i class="fa-solid fa-floppy-disk"></i> Save Assignment</button>
                            <span>Only the checked questions will be available to this Dean/Principal for this target.</span>
                        </div>
                    </form>
                <?php endif; ?>
            </section>
        <?php else: ?>
            <div class="qx-selection-hint"><i class="fa-solid fa-hand-pointer"></i><strong>Select a target above</strong><span>Use the “Assign Questions” action for a person to open their assignment editor.</span></div>
        <?php endif; ?>
    <?php endif; ?>

    <?php elseif ($current_view === 'manage'): ?>
    <!-- ══ MANAGE QUESTIONNAIRE ══ -->
    <?php
    $is_mr_manage    = false;
    $is_staff_manage = ($selected_target === 'Staff');
    $is_school_head_manage = ($selected_target === 'School Head' && $active_eval === 'student');
    $is_staff_eval_manage = ($active_eval === 'staff' && in_array($selected_target, ['Dean','Principal','EA'], true));
    $is_ea_manage = ($active_eval === 'ea' && in_array($selected_target, ['Staff','Dean','Principal'], true));

    // Shared-pool targets use evaluation_questions/question_categories.
    // Per-person targets use user_questions/user_question_categories.
    // Dean / Principal Evaluation: Faculty and the EA are shared question
    // banks (one reusable set each), but Staff (non-teaching) gets its own
    // individual question set per person. Executive Assistant Evaluation
    // likewise uses dedicated per-person question sets for Staff, Dean, and
    // Principal, so its evaluation form reads exactly what is managed here.
    $is_fac_manage   = ($selected_target === 'Teacher')
        || ($active_eval === 'school_head' && in_array($selected_target, ['Faculty', 'EA'], true))
        || $is_staff_eval_manage;
    $is_shared_manage = $is_fac_manage;
    $is_per_user_target = in_array($selected_target, $per_user_targets, true) || $is_ea_manage;

    $accent_class    = $is_staff_manage ? 'staff' : ($is_staff_eval_manage ? 'staff-eval' : 'fac');
    $hdr_color       = $is_school_head_manage ? 'var(--school-head)' : ($is_staff_manage ? 'var(--staff)' : ($is_staff_eval_manage ? '#0891B2' : 'var(--eval-color)'));

    $scope_cards = [];
    foreach ($active_categories as $scope) {
        $scope_users = $card_data[$scope]['users'] ?? [];
        $scope_cards[] = [
            'key' => $scope,
            'label' => displayTargetLabel($scope, $active_eval),
            'count' => count($scope_users),
            'shared' => ($scope === 'Teacher' || ($active_eval === 'school_head' && in_array($scope, ['Faculty','EA'], true)) || false || $is_staff_eval_manage)
        ];
    }

    // Category question counts for the shared bank.
    $shared_cat_counts = [];
    if ($is_shared_manage) {
        foreach ($categories_list as $cat) $shared_cat_counts[(int)$cat['id']] = 0;
        foreach ($questions_list as $qq) {
            $assigned_names = array_filter(explode('||', $qq['category_labels'] ?? ''));
            foreach ($categories_list as $cat) {
                if (in_array($cat['category_name'], $assigned_names, true)) {
                    $shared_cat_counts[(int)$cat['id']]++;
                }
            }
        }
    }

    // For per-person pages, build a compact person table instead of a permanent sidebar.
    $selected_person_role_label = '';
    if ($selected_user_data) {
        if ($selected_target === 'School Head') {
            $selected_person_role_label = ($selected_user_data['role'] === 'principal') ? 'Principal' : 'Dean';
        } else {
            $selected_person_role_label = displayTargetLabel($selected_target, $active_eval);
        }
    }
    ?>
    <div class="page-header qx-page-header">
        <div>
            <h1>
                <i class="fa-solid <?= $active_eval === 'student' ? 'fa-graduation-cap' : ($active_eval === 'peer' ? 'fa-people-arrows' : ($active_eval === 'ea' ? 'fa-user-shield' : ($active_eval === 'staff' ? 'fa-users' : 'fa-user-tie'))) ?>"
                   style="color:<?= $eval_color ?>;margin-right:8px"></i>
                <?= htmlspecialchars($eval_label) ?>
            </h1>
            <p>Manage the question bank and assignment rules for this evaluation.</p>
        </div>
        <a href="?view=dashboard&eval_type=<?= $active_eval ?>" class="btn btn-back">
            <i class="fa-solid fa-arrow-left"></i> Back to Evaluation Types
        </a>
    </div>

    <?php if ($active_eval === 'school_head'):
        $sh_dean_user = null; $sh_principal_user = null;
        foreach ($school_head_users as $shu) {
            if ($shu['role'] === 'dean' && !$sh_dean_user) $sh_dean_user = $shu;
            if ($shu['role'] === 'principal' && !$sh_principal_user) $sh_principal_user = $shu;
        }
        $sh_target_qs = urlencode($selected_target);
    ?>
    <!-- EVALUATOR ROLE — Dean and Principal each keep their own Faculty/Staff/EA banks -->
    <section class="qx-scope-card" style="margin-bottom:16px;">
        <div class="qx-scope-head">
            <div>
                <div class="qx-eyebrow"><i class="fa-solid fa-user-check"></i> Evaluator</div>
                <h2>Editing the questionnaire for</h2>
                <p>The Dean and Principal each get their own Faculty, Staff, and Executive Assistant question banks — switch tabs to edit the other one.</p>
            </div>
            <div class="qx-evaluator-tabs">
                <a class="qx-eval-dean <?= $sh_role === 'dean' ? 'active' : '' ?>"
                   href="?view=manage&target=<?= $sh_target_qs ?>&eval_type=school_head&sh_role=dean">
                    <span class="qx-person-avatar small"><i class="fa-solid fa-graduation-cap"></i></span>
                    <span>
                        <strong><?= htmlspecialchars($sh_dean_user['full_name'] ?? 'Dean') ?></strong>
                        <small>Dean</small>
                        <span class="qx-eval-role dean"><i class="fa-solid fa-graduation-cap"></i>Dean</span>
                    </span>
                </a>
                <a class="qx-eval-principal <?= $sh_role === 'principal' ? 'active' : '' ?>"
                   href="?view=manage&target=<?= $sh_target_qs ?>&eval_type=school_head&sh_role=principal">
                    <span class="qx-person-avatar small"><i class="fa-solid fa-user-tie"></i></span>
                    <span>
                        <strong><?= htmlspecialchars($sh_principal_user['full_name'] ?? 'Principal') ?></strong>
                        <small>Principal</small>
                        <span class="qx-eval-role principal"><i class="fa-solid fa-user-tie"></i>Principal</span>
                    </span>
                </a>
            </div>
        </div>
    </section>
    <?php endif; ?>

    <!-- QUESTIONNAIRE SCOPE -->
    <section class="qx-scope-card">
        <div class="qx-scope-head">
            <div>
                <div class="qx-eyebrow"><i class="fa-solid fa-layer-group"></i> Questionnaire scope</div>
                <h2><?= htmlspecialchars($eval_label) ?> targets</h2>
                <p>Select what kind of person or group the questions apply to. Shared pools use one reusable question bank; individual targets have their own question set.</p>
            </div>
            <span class="qx-scope-total"><?= count($active_categories) ?> scopes</span>
        </div>
        <div class="qx-scope-grid">
            <?php foreach ($scope_cards as $scope): ?>
                <a class="qx-scope-item <?= $selected_target === $scope['key'] ? 'active' : '' ?>"
                   href="?view=manage&target=<?= urlencode($scope['key']) ?>&eval_type=<?= $active_eval ?><?= $active_eval === 'school_head' ? '&sh_role='.$sh_role : '' ?>">
                    <div class="qx-scope-icon"><i class="fa-solid <?= $icons[$scope['key']] ?? 'fa-users' ?>"></i></div>
                    <div class="qx-scope-copy">
                        <strong><?= htmlspecialchars($scope['label']) ?></strong>
                        <span><?= $scope['shared'] ? 'Shared question bank' : 'Individual question sets' ?></span>
                    </div>
                    <span class="qx-scope-count"><?= (int)$scope['count'] ?> people</span>
                    <i class="fa-solid fa-chevron-right qx-scope-arrow"></i>
                </a>
            <?php endforeach; ?>
        </div>
    </section>

    <?php if ($is_shared_manage): ?>
        <!-- ══ SHARED QUESTION BANK ══ -->
        <section class="qx-panel">
            <div class="qx-panel-head">
                <div>
                    <div class="qx-eyebrow"><i class="fa-solid fa-database"></i> Shared question bank</div>
                    <h2><?= htmlspecialchars(displayTargetLabel($selected_target, $active_eval)) ?></h2>
                    <p>One reusable set of questions is used for the selected <?= htmlspecialchars(displayTargetLabel($selected_target, $active_eval)) ?> target.<?php if ($active_eval === 'school_head' && $selected_target === 'Faculty'): ?> <?= $sh_role === 'dean' ? 'Only <strong>College</strong>-assigned Faculty are eligible for the Dean.' : 'Only <strong>High School / Senior High</strong>-assigned Faculty are eligible for the Principal.' ?><?php elseif ($active_eval === 'staff'): ?> Staff members will use this question bank when evaluating the selected leader.<?php endif; ?></p>
                </div>
                <?php if ($active_eval === 'school_head' && $selected_target === 'Faculty'): ?>
                <span class="qx-eval-role <?= $sh_role ?>"><i class="fa-solid <?= $sh_role === 'dean' ? 'fa-graduation-cap' : 'fa-user-tie' ?>"></i><?= $sh_role === 'dean' ? 'College only' : 'High School / SHS only' ?></span>
                <?php endif; ?>
                <div class="qx-kpi-row">
                    <span><b><?= count($questions_list) ?></b> questions</span>
                    <span><b><?= count($categories_list) ?></b> categories</span>
                </div>
            </div>

            <div class="qx-section-block">
                <div class="qx-section-title">
                    <div>
                        <span class="qx-eyebrow"><i class="fa-solid fa-tags"></i> Categories</span>
                        <small>Organize and reuse questions without duplicating them.</small>
                    </div>
                    <form method="POST" class="qx-inline-add">
                        <input type="hidden" name="form_action" value="add_category"/>
                        <input type="hidden" name="target_type" value="<?= htmlspecialchars($selected_target) ?>"/>
                        <input type="hidden" name="eval_type" value="<?= $active_eval ?>"/>
                        <?php if ($active_eval === 'school_head'): ?><input type="hidden" name="sh_role" value="<?= $sh_role ?>"/><?php endif; ?>
                        <input type="hidden" name="view" value="manage"/>
                        <input class="field" type="text" name="category_name" placeholder="New category name..." required/>
                        <button type="submit" class="btn-sm <?= $is_mr_manage ? 'btn-mr' : 'btn-eval' ?>"><i class="fa-solid fa-plus"></i> Add</button>
                    </form>
                </div>

                <?php if ($active_eval !== 'student' && $active_eval !== 'peer' && $active_eval !== 'staff' && $active_eval !== 'school_head'): ?>
                <?php if (empty($categories_list)): ?>
                    <div class="qx-empty-mini"><i class="fa-solid fa-tags"></i> No categories yet. Add the first category above.</div>
                <?php else: ?>
                    <div class="qx-category-table-wrap">
                        <table class="qx-category-table">
                            <thead><tr><th>Category</th><th>Questions</th><th>Actions</th></tr></thead>
                            <tbody>
                            <?php foreach ($categories_list as $cat): ?>
                                <tr>
                                    <td><span class="qx-category-name"><?= htmlspecialchars($cat['category_name']) ?></span></td>
                                    <td><span class="qx-count-pill"><?= (int)($shared_cat_counts[(int)$cat['id']] ?? 0) ?></span></td>
                                    <td class="qx-actions-cell">
                                        <button class="qx-action-link" type="button" onclick="openRename(<?= (int)$cat['id'] ?>,'<?= htmlspecialchars(addslashes($cat['category_name'])) ?>')"><i class="fa-solid fa-pen"></i> Rename</button>
                                        <button class="qx-action-link danger" type="button" onclick="deleteCategory(<?= (int)$cat['id'] ?>,'<?= htmlspecialchars(addslashes($cat['category_name'])) ?>')"><i class="fa-solid fa-trash-can"></i> Delete</button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
                <?php endif; ?>
            </div>



            <div class="qx-section-block qx-question-block">
                <div class="qx-section-title">
                    <div>
                        <span class="qx-eyebrow"><i class="fa-solid fa-list-check"></i> Question bank</span>
                        <small>Create reusable questions, then assign them to the relevant categories.</small>
                    </div>
                </div>

                <form method="POST" class="qx-new-question">
                    <input type="hidden" name="form_action" value="insert"/>
                    <input type="hidden" name="target_type" value="<?= htmlspecialchars($selected_target) ?>"/>
                    <input type="hidden" name="eval_type" value="<?= $active_eval ?>"/>
                        <?php if ($active_eval === 'school_head'): ?><input type="hidden" name="sh_role" value="<?= $sh_role ?>"/><?php endif; ?>
                    <input type="hidden" name="view" value="manage"/>
                    <select name="category_ids[]" required>
                        <option value="" disabled selected hidden>Primary category</option>
                        <?php foreach ($categories_list as $c): ?>
                            <option value="<?= (int)$c['id'] ?>"><?= htmlspecialchars($c['category_name']) ?></option>
                        <?php endforeach; ?>
                        <?php if (empty($categories_list)): ?><option value="0" selected>General</option><?php endif; ?>
                    </select>
                    <input type="text" name="question_text" placeholder="Write a new question..." required/>
                    <button type="submit" class="btn-sm <?= $is_mr_manage ? 'btn-mr' : 'btn-eval' ?>"><i class="fa-solid fa-plus"></i> Add Question</button>
                </form>

                <?php if (empty($questions_list)): ?>
                    <div class="qx-empty-state">
                        <i class="fa-solid fa-clipboard-list"></i>
                        <h3>No questions in this bank yet</h3>
                        <p>Add the first question using the form above.</p>
                    </div>
                <?php else: ?>
                    <div class="qx-table-wrap">
                        <table class="qx-question-table">
                            <thead>
                                <tr><th style="width:54px">No.</th><th>Question</th><th style="width:260px">Categories</th><th style="width:135px">Actions</th></tr>
                            </thead>
                            <tbody>
                            <?php $num=1; foreach ($questions_list as $row): ?>
                                <?php $assigned_names = array_filter(explode('||', $row['category_labels'] ?? '')); ?>
                                <?php
                                    $assigned_ids=[];
                                    $aq=$mysqli->prepare("SELECT category_id FROM evaluation_question_categories WHERE question_id=?");
                                    $aq->bind_param('i',$row['id']); $aq->execute(); $ar=$aq->get_result();
                                    while($ax=$ar->fetch_assoc()) $assigned_ids[(int)$ax['category_id']]=true;
                                    $aq->close();
                                ?>
                                <tr>
                                    <td><span class="q-num"><?= $num++ ?></span></td>
                                    <td>
                                        <form id="upd-<?= (int)$row['id'] ?>" method="POST" style="margin:0">
                                            <input type="hidden" name="form_action" value="update"/>
                                            <input type="hidden" name="question_id" value="<?= (int)$row['id'] ?>"/>
                                            <input type="hidden" name="target_type" value="<?= htmlspecialchars($selected_target) ?>"/>
                                            <input type="hidden" name="eval_type" value="<?= $active_eval ?>"/>
                        <?php if ($active_eval === 'school_head'): ?><input type="hidden" name="sh_role" value="<?= $sh_role ?>"/><?php endif; ?>
                                            <input type="hidden" name="view" value="manage"/>
                                            <input class="qx-question-input" type="text" name="question_text" value="<?= htmlspecialchars($row['question_text']) ?>"/>
                                        </form>
                                    </td>
                                    <td>
                                        <div class="qx-tag-list">
                                            <?php if (empty($assigned_names)): ?><span class="qx-muted">General</span><?php endif; ?>
                                            <?php foreach ($assigned_names as $an): ?><span class="qx-tag"><?= htmlspecialchars($an) ?></span><?php endforeach; ?>
                                        </div>
                                        <details class="qx-category-details">
                                            <summary><i class="fa-solid fa-sliders"></i> Assign categories</summary>
                                            <div class="qx-checkbox-grid">
                                                <?php foreach ($categories_list as $c): ?>
                                                    <label><input type="checkbox" name="category_ids[]" value="<?= (int)$c['id'] ?>" form="upd-<?= (int)$row['id'] ?>" <?= isset($assigned_ids[(int)$c['id']])?'checked':'' ?>/> <?= htmlspecialchars($c['category_name']) ?></label>
                                                <?php endforeach; ?>
                                            </div>
                                        </details>
                                    </td>
                                    <td>
                                        <div class="qx-row-actions">
                                            <button type="submit" form="upd-<?= (int)$row['id'] ?>" class="qx-primary-icon" title="Save question and category assignments"><i class="fa-solid fa-floppy-disk"></i> Save</button>
                                            <form method="POST" style="margin:0" onsubmit="return confirm('Delete this question?')">
                                                <input type="hidden" name="form_action" value="delete"/>
                                                <input type="hidden" name="question_id" value="<?= (int)$row['id'] ?>"/>
                                                <input type="hidden" name="target_type" value="<?= htmlspecialchars($selected_target) ?>"/>
                                                <input type="hidden" name="eval_type" value="<?= $active_eval ?>"/>
                        <?php if ($active_eval === 'school_head'): ?><input type="hidden" name="sh_role" value="<?= $sh_role ?>"/><?php endif; ?>
                                                <input type="hidden" name="view" value="manage"/>
                                                <button type="submit" class="qx-danger-icon" title="Delete question"><i class="fa-solid fa-trash-can"></i></button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </section>

    <?php else: ?>
        <!-- ══ INDIVIDUAL QUESTION SETS ══ -->
        <section class="qx-panel">
            <div class="qx-panel-head">
                <div>
                    <div class="qx-eyebrow"><i class="fa-solid fa-user-pen"></i> Individual question sets</div>
                    <h2><?= htmlspecialchars(displayTargetLabel($selected_target, $active_eval)) ?></h2>
                    <p>Each person has their own questionnaire. Select a person below to manage their questions.</p>
                </div>
                <?php if ($selected_user && $selected_user_data): ?>
                    <span class="qx-kpi-row"><span><b><?= count($user_questions_list) ?></b> questions for <?= htmlspecialchars($selected_user_data['full_name']) ?></span></span>
                <?php endif; ?>
            </div>

            <?php
            // Flag anyone in this list with zero configured questions --
            // that's a silent dead end on the student/evaluator side (the
            // Evaluate button leads to a "no questions set up" error and no
            // submission is ever recorded), so surface it here before it
            // turns into a missing row in Evaluation Report.
            $missing_q_people = [];
            if ($is_per_user_target) {
                foreach ($target_users as $tu) {
                    $uq_target = $selected_target === 'School Head'
                        ? ($tu['role'] === 'principal' ? 'Principal' : 'Dean')
                        : $selected_target;
                    $uq_eval = ($active_eval === 'ea')
                        ? (($uq_target === 'Staff') ? 'student' : 'school_head')
                        : $active_eval;
                    $cnt = $user_q_counts[$tu['id']][$uq_target][$uq_eval] ?? 0;
                    if ($cnt === 0) {
                        $missing_q_people[] = $tu['full_name'] . ($selected_target === 'School Head' ? ' (' . $uq_target . ')' : '');
                    }
                }
            }
            ?>
            <?php if (!empty($missing_q_people)): ?>
            <div class="qx-info-banner" style="border-color:#FCA5A5;background:#FEF2F2;margin-bottom:16px;">
                <i class="fa-solid fa-triangle-exclamation" style="color:#DC2626"></i>
                <div><strong style="color:#B91C1C">No questions configured yet</strong><br>
                    <?= htmlspecialchars(implode(', ', $missing_q_people)) ?> — <?= count($missing_q_people) === 1 ? 'has' : 'have' ?> zero questions set up. Anyone who tries to evaluate <?= count($missing_q_people) === 1 ? 'this person' : 'these people' ?> will hit a dead end and their evaluation won't be recorded. Click Manage below to add questions.
                </div>
            </div>
            <?php endif; ?>

            <!-- Personnel table -->
            <?php if (empty($target_users)): ?>
                <div class="qx-empty-state">
                    <i class="fa-solid <?= $icons[$selected_target] ?? 'fa-users' ?>"></i>
                    <h3>No eligible <?= htmlspecialchars(displayTargetLabel($selected_target, $active_eval)) ?> yet</h3>
                    <p>This list updates automatically from approved, active personnel records and current role/assignment rules.</p>
                </div>
            <?php else: ?>
                <div class="qx-person-table-wrap">
                    <table class="qx-person-table">
                        <thead><tr><th>Person</th><th>Role / Designation</th><th>Question Set</th><th style="width:150px">Action</th></tr></thead>
                        <tbody>
                        <?php foreach ($target_users as $tu): ?>
                            <?php
                                $is_nologin = ($tu['source'] === 'admin_nologin');
                                if ($is_per_user_target) {
                                    $uq_target = $selected_target === 'School Head'
                                        ? ($tu['role'] === 'principal' ? 'Principal' : 'Dean')
                                        : $selected_target;
                                    $uq_eval = ($active_eval === 'ea')
                                        ? (($uq_target === 'Staff') ? 'student' : 'school_head')
                                        : $active_eval;
                                    $uq_cnt = $user_q_counts[$tu['id']][$uq_target][$uq_eval] ?? 0;
                                } else {
                                    $uq_cnt = count($questions_list);
                                }
                                $person_role = $selected_target === 'School Head'
                                    ? ($tu['role'] === 'principal' ? 'Principal' : 'Dean')
                                    : (($active_eval === 'peer' && $selected_target === 'School')
                                        ? ($tu['role'] === 'principal' ? 'Principal' : 'Dean')
                                        : (($active_eval === 'peer' && $selected_target === 'Staff')
                                            ? 'Staff'
                                            : (($active_eval === 'ea' && $selected_target === 'Staff') ? 'Staff' : ($tu['designation'] ?: ($tu['role'] === 'teacher' ? 'Teacher' : 'Personnel')))));
                            ?>
                            <tr class="<?= $selected_user === (int)$tu['id'] ? 'selected' : '' ?>">
                                <td>
                                    <a class="qx-person-main" href="?view=manage&target=<?= urlencode($selected_target) ?>&eval_type=<?= $active_eval ?>&user_id=<?= (int)$tu['id'] ?><?= $active_eval === 'school_head' ? '&sh_role='.$sh_role : '' ?>">
                                        <span class="qx-person-avatar">
                                            <?php if (!empty($tu['photo'])): ?><img src="../image/<?= htmlspecialchars($tu['photo']) ?>" alt=""/>
                                            <?php else: ?><i class="fa-solid <?= $icons[$selected_target] ?? 'fa-user' ?>"></i><?php endif; ?>
                                        </span>
                                        <span><strong><?= htmlspecialchars($tu['full_name']) ?></strong><small><?= $is_nologin ? 'Personnel Registry' : 'Login Account' ?></small></span>
                                    </a>
                                </td>
                                <td>
                                    <div class="qx-role-cell">
                                        <span><?= htmlspecialchars($person_role) ?></span>
                                    </div>
                                </td>
                                <td><span class="qx-q-count <?= $uq_cnt > 0 ? 'has' : '' ?>"><?= (int)$uq_cnt ?> question<?= $uq_cnt === 1 ? '' : 's' ?></span></td>
                                <td>
                                    <a class="qx-manage-person <?= $selected_user === (int)$tu['id'] ? 'active' : '' ?>"
                                       href="?view=manage&target=<?= urlencode($selected_target) ?>&eval_type=<?= $active_eval ?>&user_id=<?= (int)$tu['id'] ?><?= $active_eval === 'school_head' ? '&sh_role='.$sh_role : '' ?>">
                                        <i class="fa-solid fa-sliders"></i> Manage
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>

            <?php if ($selected_user && $selected_user_data): ?>
                <div class="qx-selected-person">
                    <div class="qx-selected-person-head">
                        <div>
                            <div class="qx-eyebrow"><i class="fa-solid fa-user-check"></i> Editing question set</div>
                            <h3><?= htmlspecialchars($selected_user_data['full_name']) ?></h3>
                            <p><?= htmlspecialchars($selected_person_role_label) ?> · <?= htmlspecialchars($active_eval === 'student' ? 'Student Evaluation' : ($active_eval === 'peer' ? 'Peer-to-Peer Evaluation' : ($active_eval === 'ea' ? 'Executive Assistant Evaluation' : ($active_eval === 'staff' ? 'Staff Evaluation' : 'Dean / Principal Evaluation')))) ?></p>
                        </div>
                        <span class="qx-kpi-row"><span><b><?= count($user_questions_list) ?></b> questions</span></span>
                    </div>

                    <div class="qx-info-banner">
                        <i class="fa-solid fa-circle-info"></i>
                        <div><strong>Individual question set</strong><br>Changes here affect only <b><?= htmlspecialchars($selected_user_data['full_name']) ?></b>.</div>
                    </div>

                    <div class="qx-section-block">
                        <div class="qx-section-title">
                            <div>
                                <span class="qx-eyebrow"><i class="fa-solid fa-tags"></i> Categories for this person</span>
                                <small>Categories are local to this question set.</small>
                            </div>
                            <form method="POST" class="qx-inline-add">
                                <input type="hidden" name="form_action" value="user_add_category"/>
                                <input type="hidden" name="target_type" value="<?= htmlspecialchars($selected_target) ?>"/>
                                <input type="hidden" name="eval_type" value="<?= $active_eval ?>"/>
                        <?php if ($active_eval === 'school_head'): ?><input type="hidden" name="sh_role" value="<?= $sh_role ?>"/><?php endif; ?>
                                <input type="hidden" name="view" value="manage"/>
                                <input type="hidden" name="user_id" value="<?= (int)$selected_user ?>"/>
                                <input class="field" type="text" name="category_name" placeholder="New category name..." required/>
                                <button type="submit" class="btn-sm <?= $is_staff_manage ? 'btn-staff' : ($is_mr_manage ? 'btn-mr' : 'btn-eval') ?>"><i class="fa-solid fa-plus"></i> Add</button>
                            </form>
                        </div>
                        <div class="qx-chip-row">
                            <?php if (empty($user_categories_list)): ?>
                                <span class="qx-muted">No categories yet.</span>
                            <?php else: foreach ($user_categories_list as $cat): ?>
                                <span class="qx-chip">
                                    <?= htmlspecialchars($cat['category_name']) ?>
                                    <button type="button" onclick="openUserRename(<?= (int)$cat['id'] ?>,'<?= htmlspecialchars(addslashes($cat['category_name'])) ?>')" aria-label="Rename"><i class="fa-solid fa-pen"></i></button>
                                    <button type="button" onclick="deleteUserCategory(<?= (int)$cat['id'] ?>,'<?= htmlspecialchars(addslashes($cat['category_name'])) ?>')" aria-label="Delete"><i class="fa-solid fa-xmark"></i></button>
                                </span>
                            <?php endforeach; endif; ?>
                        </div>
                    </div>

                    <form method="POST" class="qx-new-question qx-person-question-form">
                        <input type="hidden" name="form_action" value="user_insert"/>
                        <input type="hidden" name="target_type" value="<?= htmlspecialchars($selected_target) ?>"/>
                        <input type="hidden" name="eval_type" value="<?= $active_eval ?>"/>
                        <?php if ($active_eval === 'school_head'): ?><input type="hidden" name="sh_role" value="<?= $sh_role ?>"/><?php endif; ?>
                        <input type="hidden" name="view" value="manage"/>
                        <input type="hidden" name="user_id" value="<?= (int)$selected_user ?>"/>
                        <select name="category" required>
                            <option value="" disabled selected hidden>Category</option>
                            <?php foreach ($user_categories_list as $c): ?>
                                <option value="<?= htmlspecialchars($c['category_name']) ?>"><?= htmlspecialchars($c['category_name']) ?></option>
                            <?php endforeach; ?>
                            <?php if (empty($user_categories_list)): ?><option value="General">General</option><?php endif; ?>
                        </select>
                        <input type="text" name="question_text" placeholder="Write a question for <?= htmlspecialchars($selected_user_data['full_name']) ?>..." required/>
                        <button type="submit" class="btn-sm <?= $is_staff_manage ? 'btn-staff' : ($is_mr_manage ? 'btn-mr' : 'btn-eval') ?>"><i class="fa-solid fa-plus"></i> Add Question</button>
                    </form>

                    <?php if (empty($user_questions_list)): ?>
                        <div class="qx-empty-state compact">
                            <i class="fa-solid fa-clipboard-list"></i>
                            <h3>No questions assigned yet</h3>
                            <p>Add the first question for this person.</p>
                        </div>
                    <?php else: ?>
                        <div class="qx-table-wrap">
                            <table class="qx-question-table">
                                <thead><tr><th style="width:54px">No.</th><th>Question</th><th style="width:135px">Actions</th></tr></thead>
                                <tbody>
                                <?php $num=1; foreach ($user_questions_list as $row): ?>
                                    <tr>
                                        <td><span class="q-num"><?= $num++ ?></span></td>
                                        <td>
                                            <form id="uupd-<?= (int)$row['id'] ?>" method="POST" style="margin:0">
                                                <input type="hidden" name="form_action" value="user_update"/>
                                                <input type="hidden" name="question_id" value="<?= (int)$row['id'] ?>"/>
                                                <input type="hidden" name="target_type" value="<?= htmlspecialchars($selected_target) ?>"/>
                                                <input type="hidden" name="eval_type" value="<?= $active_eval ?>"/>
                        <?php if ($active_eval === 'school_head'): ?><input type="hidden" name="sh_role" value="<?= $sh_role ?>"/><?php endif; ?>
                                                <input type="hidden" name="view" value="manage"/>
                                                <input type="hidden" name="user_id" value="<?= (int)$selected_user ?>"/>
                                                <input class="qx-question-input" type="text" name="question_text" value="<?= htmlspecialchars($row['question_text']) ?>"/>
                                            </form>
                                        </td>
                                        <td>
                                            <div class="qx-row-actions">
                                                <button type="submit" form="uupd-<?= (int)$row['id'] ?>" class="qx-primary-icon" title="Save question"><i class="fa-solid fa-floppy-disk"></i> Save</button>
                                                <form method="POST" style="margin:0" onsubmit="return confirm('Delete this question?')">
                                                    <input type="hidden" name="form_action" value="user_delete"/>
                                                    <input type="hidden" name="question_id" value="<?= (int)$row['id'] ?>"/>
                                                    <input type="hidden" name="target_type" value="<?= htmlspecialchars($selected_target) ?>"/>
                                                    <input type="hidden" name="eval_type" value="<?= $active_eval ?>"/>
                        <?php if ($active_eval === 'school_head'): ?><input type="hidden" name="sh_role" value="<?= $sh_role ?>"/><?php endif; ?>
                                                    <input type="hidden" name="view" value="manage"/>
                                                    <input type="hidden" name="user_id" value="<?= (int)$selected_user ?>"/>
                                                    <button type="submit" class="qx-danger-icon" title="Delete question"><i class="fa-solid fa-trash-can"></i></button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </section>
    <?php endif; ?>
    <?php endif; /* end manage view */ ?>

    <!-- SHARED POOL RENAME MODAL (Teacher) -->
    <div class="modal-overlay" id="renameModal">
        <div class="modal">
            <div class="modal-title">Rename Category</div>
            <div class="modal-sub">All questions in this category will be updated automatically.</div>
            <form method="POST">
                <input type="hidden" name="form_action" value="rename_category"/>
                <input type="hidden" name="target_type" value="<?= htmlspecialchars($selected_target) ?>"/>
                <input type="hidden" name="eval_type" value="<?= $active_eval ?>"/>
                        <?php if ($active_eval === 'school_head'): ?><input type="hidden" name="sh_role" value="<?= $sh_role ?>"/><?php endif; ?>
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
                        <?php if ($active_eval === 'school_head'): ?><input type="hidden" name="sh_role" value="<?= $sh_role ?>"/><?php endif; ?>
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
                        <?php if ($active_eval === 'school_head'): ?><input type="hidden" name="sh_role" value="<?= $sh_role ?>"/><?php endif; ?>
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
                        <?php if ($active_eval === 'school_head'): ?><input type="hidden" name="sh_role" value="<?= $sh_role ?>"/><?php endif; ?>
        <input type="hidden" name="view" value="manage"/>
        <input type="hidden" name="user_id" value="<?= $selected_user ?? 0 ?>"/>
        <input type="hidden" name="cat_id" id="deleteUserCatId"/>
        <input type="hidden" name="cat_name" id="deleteUserCatName"/>
    </form>

    <script>
    // Shared pool category modal (Teacher)
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
        if (!confirm(`Delete category "${name}"?\n\nQuestions can remain in any other categories; only this category assignment is removed.`)) return;
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