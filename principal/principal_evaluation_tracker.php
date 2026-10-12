<?php
// principal_evaluation_tracker.php
//
// Principal Evaluation Tracker — same layout as the Dean's tracker
// (Students / Teachers tabs, per-grade summary cards, grade pills, status
// filter, search, export and an expandable progress table), but the data
// is strictly limited to Basic Education:
//
//   Students tab  -> active JHS/SHS students (Grade 7-12) and the Student
//                    Evaluation targets each of them is expected to finish.
//   Teachers tab  -> active JHS/SHS Faculty / Teaching Staff and the
//                    peer evaluations each of them is expected to finish.
//
// College students, College-only teachers, Dean, Executive Assistant and
// the Principal's own account never appear as a tracked row. The Principal's
// scope (junior_high / senior_high / both) is honoured through $scopeGrades.

require_once 'principal_common.php';

$principalId = (int)$_SESSION['user_id'];

$settings      = $schoolHeadSettings;
$period_id_int = (int)($settings['period_id'] ?? 0);
$hasPeriod     = $period_id_int > 0;
$evalOpen      = !empty($settings['school_head_is_open']);

// ── INPUT ───────────────────────────────────────────────────────────
$tabParam    = $_GET['tab'] ?? 'students';
$tab         = (is_string($tabParam) && in_array($tabParam, ['students', 'teachers', 'staff'], true)) ? $tabParam : 'students';
$search      = trim((string)($_GET['search'] ?? ''));
$gradeFilter = trim((string)($_GET['grade'] ?? ''));
$status      = trim((string)($_GET['status'] ?? ''));
$page        = max(1, (int)($_GET['page'] ?? 1));
$perPage     = 10;

if (!in_array($gradeFilter, $scopeGrades, true)) $gradeFilter = '';
if (!in_array($status, ['', 'not_started', 'in_progress', 'completed'], true)) $status = '';

$isAjax = (($_GET['ajax'] ?? '') === '1');

// ── HELPERS ─────────────────────────────────────────────────────────
function ptk_h($v): string {
    return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
}

function ptk_has_column(mysqli $mysqli, string $table, string $column): bool {
    try {
        $r = @$mysqli->query("SHOW COLUMNS FROM `" . $mysqli->real_escape_string($table) . "` LIKE '" . $mysqli->real_escape_string($column) . "'");
        if (!$r) return false;
        $ok = $r->num_rows > 0;
        $r->free();
        return $ok;
    } catch (Throwable $e) {
        return false;
    }
}

function ptr_user_levels(mysqli $mysqli, int $userId): array {
    $rows = safe_rows($mysqli,
        "SELECT year_level FROM user_year_levels WHERE user_id=? ORDER BY year_level ASC",
        'i', [$userId]
    );
    $out = [];
    foreach ($rows as $row) {
        $v = trim((string)($row['year_level'] ?? ''));
        if ($v !== '') $out[] = $v;
    }
    return array_values(array_unique($out));
}

function ptr_grade(string $value): ?string {
    $value = trim($value);
    if (preg_match('/grade[[:space:]_-]*(7|8|9|10|11|12)\b/i', $value, $m)) return $m[1];
    return null;
}

function ptr_is_teaching(mysqli $mysqli, array $u): bool {
    if (($u['role'] ?? '') === 'teacher') return true;
    if (strtolower((string)($u['secondary_role'] ?? '')) === 'teacher') return true;
    if (strcasecmp((string)($u['sector'] ?? ''), 'Teacher') === 0) return true;

    $uid = (int)($u['id'] ?? 0);
    if ($uid <= 0) return false;
    $rows = safe_rows($mysqli, "
        SELECT 1 FROM teaching_assignments WHERE user_id=?
        UNION ALL
        SELECT 1 FROM user_year_levels WHERE user_id=?
        LIMIT 1
    ", 'ii', [$uid, $uid]);
    return !empty($rows);
}

function ptr_is_non_teaching_staff(mysqli $mysqli, int $uid): bool {
    $rows = safe_rows($mysqli, "
        SELECT NOT EXISTS(SELECT 1 FROM teaching_assignments WHERE user_id=?)
           AND NOT EXISTS(SELECT 1 FROM user_year_levels WHERE user_id=?) AS ok
    ", 'ii', [$uid, $uid]);
    return !empty($rows) && (int)($rows[0]['ok'] ?? 0) === 1;
}

function ptr_level_bucket(string $grade): string {
    return in_array($grade, ['7','8','9','10'], true) ? 'junior_high' : 'senior_high';
}

function ptr_level_label(array $buckets): string {
    $j = in_array('junior_high', $buckets, true);
    $s = in_array('senior_high', $buckets, true);
    if ($j && $s) return 'JHS & SHS';
    if ($j) return 'Junior High';
    if ($s) return 'Senior High';
    return '—';
}

function ptr_student_level_label(string $rawYear): string {
    $g = ptr_grade($rawYear);
    if ($g === null) return 'Basic Education';
    return in_array($g, ['7','8','9','10'], true) ? 'Junior High' : 'Senior High';
}

/**
 * Which Student Evaluation context does a submitted row belong to?
 * Unchanged rules: the Principal's CURRENT scope is the final authority, so a
 * stale bucket can never turn a Non-Teaching Staff person into a Faculty row.
 */
function ptr_context_for_submission(array $row, int $principalId, array $facultyIds, array $staffIds, array $multiIds, bool $principalRequired): ?string {
    $tid = (int)$row['target_user_id'];
    $bucket = strtolower(trim((string)($row['eval_bucket'] ?? '')));
    $context = strtolower(trim((string)($row['evaluation_context'] ?? '')));

    if ($principalRequired && $tid === $principalId &&
        ($bucket === 'school head' || $bucket === 'school_head' || $bucket === 'principal' ||
         $context === 'school_head' || $context === 'principal' || $bucket === '' || $context === '')) {
        return 'school_head:' . $tid;
    }

    $explicitMulti = in_array($bucket, ['multi-role','multi_role'], true)
        || in_array($context, ['multi-role','multi_role'], true)
        || !empty($row['multi_answer']);
    if ($explicitMulti && isset($multiIds[$tid])) return 'multi:' . $tid;

    $explicitStaff = in_array($bucket, ['staff','non-teaching staff','non_teaching_staff'], true)
        || $context === 'staff';
    $explicitFaculty = in_array($bucket, ['faculty','teacher'], true)
        || in_array($context, ['faculty','teacher'], true);

    if (isset($staffIds[$tid]) && ($explicitStaff || (!$explicitFaculty && !$explicitMulti))) {
        return 'staff:' . $tid;
    }
    if (isset($facultyIds[$tid]) && ($explicitFaculty || (!$explicitStaff && !$explicitMulti))) {
        return 'faculty:' . $tid;
    }
    return null;
}

/**
 * Which peer-evaluation target does a teacher's submission belong to?
 * Targets are the Principal, other in-scope teachers and Non-Teaching Staff.
 * A teacher can never complete their own row.
 */
function ptk_teacher_context(array $row, array $targetDefs): ?string {
    $evaluator = (int)$row['evaluator_id'];
    $tid = (int)$row['target_user_id'];
    if ($tid === $evaluator) return null;
    foreach (['principal', 'faculty', 'staff'] as $prefix) {
        $key = $prefix . ':' . $tid;
        if (isset($targetDefs[$key])) return $key;
    }
    return null;
}

/** [singular, plural, column header] wording for each tab. */
function ptk_nouns(string $tab): array {
    if ($tab === 'teachers') return ['teacher', 'teachers', 'Teacher'];
    if ($tab === 'staff')    return ['staff member', 'staff', 'Staff'];
    return ['student', 'students', 'Student'];
}

function ptk_status(int $completed, int $required, bool $started): array {
    if ($required > 0 && $completed >= $required) return ['completed', 'Completed'];
    if ($completed > 0 || $started)               return ['in_progress', 'In Progress'];
    return ['not_started', 'Not Started'];
}

function ptk_type_rank(string $type): int {
    return ['Faculty' => 1, 'Staff' => 2, 'Multi-Role' => 3, 'Principal' => 4, 'Executive Assistant' => 5][$type] ?? 9;
}

/** Per-row breakdown: every required target and where the person stands. */
function ptk_build_details(array $defs, array $contexts): array {
    $out = [];
    foreach ($defs as $key => $def) {
        $out[] = [
            'label' => $def['label'],
            'type'  => $def['type'],
            'state' => $contexts[$key]['state'] ?? 'pending',
        ];
    }
    usort($out, function ($a, $b) {
        $c = ptk_type_rank($a['type']) <=> ptk_type_rank($b['type']);
        return $c !== 0 ? $c : strnatcasecmp($a['label'], $b['label']);
    });
    return $out;
}

function ptk_grades_text(array $grades): string {
    if (empty($grades)) return '—';
    return (count($grades) === 1 ? 'Grade ' : 'Grades ') . implode(', ', $grades);
}

function ptk_csv_cell($v) {
    $v = (string)$v;
    return ($v !== '' && strpos("=+-@\t\r", $v[0]) !== false) ? "'" . $v : $v;
}

function ptk_url(array $over = []): string {
    $p = $_GET;
    unset($p['ajax'], $p['export'], $p['_ts']);
    $p = array_merge($p, $over);
    if (!array_key_exists('page', $over)) unset($p['page']);
    foreach ($p as $k => $v) {
        if ($v === '' || $v === null) unset($p[$k]);
    }
    $qs = http_build_query($p);
    return 'principal_evaluation_tracker.php' . ($qs !== '' ? '?' . $qs : '');
}

// ── RENDER FUNCTIONS (shared by the first page load and the live JSON) ─
function ptk_render_tabs(string $tab, int $studentTotal, int $teacherTotal, int $staffTotal): string {
    $tabs = [
        'students' => ['Students', $studentTotal],
        'teachers' => ['Teachers', $teacherTotal],
        'staff'    => ['Staff', $staffTotal],
    ];
    ob_start();
    foreach ($tabs as $key => [$label, $count]) {
        $active = $key === $tab;
        ?><a class="ptk-tab<?= $active ? ' active' : '' ?>" data-nav href="<?= ptk_h(ptk_url(['tab' => $key])) ?>"<?= $active ? ' aria-current="page"' : '' ?>><?= ptk_h($label) ?><span class="ptk-tab-count"><?= number_format($count) ?></span></a><?php
    }
    return ob_get_clean();
}

function ptk_render_cards(string $tab, array $cards): string {
    $icon = $tab === 'teachers' ? 'fa-chalkboard-user' : ($tab === 'staff' ? 'fa-briefcase' : 'fa-graduation-cap');
    ob_start();
    foreach ($cards as $c) {
        ?><div class="ptk-card">
            <div class="ptk-card-top">
                <span class="ptk-card-label"><?= ptk_h($c['label']) ?></span>
                <span class="ptk-card-icon"><i class="fa-solid <?= $icon ?>"></i></span>
            </div>
            <div class="ptk-card-num"><?= number_format($c['total']) ?></div>
            <?php if (isset($c['foot'])): ?><div class="ptk-card-foot"><span><?= ptk_h($c['foot']) ?></span></div><?php else: ?><div class="ptk-card-foot"><b><?= number_format($c['completed']) ?> completed</b><span class="ptk-card-sep">|</span><span><?= number_format($c['pending']) ?> pending</span></div><?php endif; ?>
        </div><?php
    }
    return ob_get_clean();
}

function ptk_render_pills(string $tab, array $scopeGrades, string $gradeFilter): string {
    if ($tab === 'staff') return '';   // Non-teaching staff have no grade level
    ob_start();
    ?><a class="ptk-pill<?= $gradeFilter === '' ? ' active' : '' ?>" data-nav href="<?= ptk_h(ptk_url(['grade' => ''])) ?>">All Levels</a><?php
    foreach ($scopeGrades as $g) {
        ?><a class="ptk-pill<?= $gradeFilter === $g ? ' active' : '' ?>" data-nav href="<?= ptk_h(ptk_url(['grade' => $g])) ?>">Grade <?= ptk_h($g) ?></a><?php
    }
    return ob_get_clean();
}

function ptk_render_detail(array $r): string {
    ob_start();
    $details = $r['details'] ?? [];
    if (empty($details)) {
        ?><p class="ptk-detail-empty">No required evaluation targets are configured for this <?= ptk_h($r['noun']) ?> yet.</p><?php
        return ob_get_clean();
    }
    ?><div class="ptk-detail-head"><strong><?= (int)$r['completed'] ?> of <?= (int)$r['required'] ?> required evaluations completed</strong><span><?= ptk_h(['teacher' => 'Peer evaluations the teacher must submit', 'staff member' => 'Evaluations the staff member must submit'][$r['noun']] ?? 'Targets the student must evaluate') ?></span></div>
    <ul class="ptk-targets"><?php
    foreach ($details as $d) {
        $state = $d['state'];
        $icon  = $state === 'completed' ? 'fa-circle-check' : ($state === 'in_progress' ? 'fa-circle-half-stroke' : 'fa-circle');
        $text  = $state === 'completed' ? 'Done' : ($state === 'in_progress' ? 'In progress' : 'Pending');
        ?><li class="ptk-target <?= ptk_h($state) ?>"><i class="fa-solid <?= $icon ?>"></i><span class="ptk-target-name"><?= ptk_h($d['label']) ?></span><span class="ptk-target-type"><?= ptk_h($d['type']) ?></span><span class="ptk-target-state"><?= $text ?></span></li><?php
    }
    ?></ul><?php
    return ob_get_clean();
}

function ptk_render_list(array $pageRows, string $tab, bool $hasPeriod, int $total, int $page, int $perPage, int $totalPages): string {
    [$noun, $plural, $colHead] = ptk_nouns($tab);
    ob_start();
    ?><div class="ptk-list-scroll"><div class="ptk-list-inner" role="table" aria-label="<?= ptk_h($colHead) ?> evaluation progress">
        <div class="ptk-head" role="row">
            <div role="columnheader"><?= ptk_h($colHead) ?></div>
            <div role="columnheader">Level</div>
            <div role="columnheader">Required</div>
            <div role="columnheader">Completed</div>
            <div role="columnheader">Status</div>
            <div role="columnheader">Progress</div>
        </div>
        <?php if (!$hasPeriod): ?>
            <p class="ptk-empty">No active evaluation period right now.</p>
        <?php elseif (empty($pageRows)): ?>
            <p class="ptk-empty">No <?= ptk_h($plural) ?> match the current filters.</p>
        <?php else: foreach ($pageRows as $r): ?>
            <div class="ptk-item" data-key="<?= ptk_h($r['key']) ?>">
                <div class="ptk-row" role="row" tabindex="0" aria-expanded="false">
                    <div class="ptk-c-person" role="cell">
                        <span class="ptk-avatar"><?php if ($r['photo'] !== ''): ?><img src="../image/<?= ptk_h($r['photo']) ?>" alt=""><?php else: ?><i class="fa-solid fa-user"></i><?php endif; ?></span>
                        <span class="ptk-person-copy"><span class="ptk-name"><?= ptk_h($r['name']) ?></span><span class="ptk-sub"><?= ptk_h($r['sub']) ?></span></span>
                    </div>
                    <div role="cell"><span class="ptk-level"><?= ptk_h($r['level_pill']) ?></span></div>
                    <div class="ptk-num" role="cell"><?= number_format($r['required']) ?></div>
                    <div class="ptk-num" role="cell"><?= number_format($r['completed']) ?> / <?= number_format($r['required']) ?></div>
                    <div role="cell"><span class="ptk-status <?= ptk_h($r['status']) ?>"><?= ptk_h($r['status_label']) ?></span></div>
                    <div class="ptk-c-prog" role="cell">
                        <span class="ptk-pct"><?= (int)$r['progress'] ?>%</span>
                        <span class="ptk-bar-wrap">
                            <span class="ptk-bar"><i style="width:<?= (int)$r['progress'] ?>%"></i></span>
                            <?php if (!empty($r['last_at'])): ?><span class="ptk-last">Last: <?= ptk_h(date('M j, Y g:i A', strtotime((string)$r['last_at']))) ?></span><?php endif; ?>
                        </span>
                        <span class="ptk-chev" aria-hidden="true"><i class="fa-solid fa-chevron-right"></i></span>
                    </div>
                </div>
                <div class="ptk-detail" hidden><?= ptk_render_detail($r) ?></div>
            </div>
        <?php endforeach; endif; ?>
    </div></div>
    <div class="ptk-foot">
        <div><?php if ($total > 0): ?>Showing <?= (($page - 1) * $perPage) + 1 ?>–<?= min($total, $page * $perPage) ?> of <?= number_format($total) ?> <?= ptk_h($total === 1 ? $noun : $plural) ?><?php else: ?>No <?= ptk_h($plural) ?> to display<?php endif; ?></div>
        <div class="ptk-pager"><?php
        if ($totalPages > 1) {
            $shown = [];
            for ($i = 1; $i <= $totalPages; $i++) {
                if ($i === 1 || $i === $totalPages || abs($i - $page) <= 2) $shown[] = $i;
            }
            ?><a class="ptk-page<?= $page <= 1 ? ' disabled' : '' ?>" data-nav aria-label="Previous page" href="<?= ptk_h(ptk_url(['page' => max(1, $page - 1)])) ?>"><i class="fa-solid fa-chevron-left"></i></a><?php
            $prev = null;
            foreach ($shown as $i) {
                if ($prev !== null && $i - $prev > 1) { ?><span class="ptk-ellipsis">…</span><?php }
                ?><a class="ptk-page<?= $i === $page ? ' active' : '' ?>" data-nav href="<?= ptk_h(ptk_url(['page' => $i])) ?>"><?= $i ?></a><?php
                $prev = $i;
            }
            ?><a class="ptk-page<?= $page >= $totalPages ? ' disabled' : '' ?>" data-nav aria-label="Next page" href="<?= ptk_h(ptk_url(['page' => min($totalPages, $page + 1)])) ?>"><i class="fa-solid fa-chevron-right"></i></a><?php
        }
        ?></div>
    </div><?php
    return ob_get_clean();
}

// ── DATA ────────────────────────────────────────────────────────────
$studentRows   = [];   // every in-scope student, unfiltered
$teacherRows   = [];   // every in-scope teacher, unfiltered
$staffRows     = [];   // every non-teaching staff member, unfiltered
$requiredTotal = 0;    // evaluation targets each student must finish
$requiredPrincipal = 0;

if ($structureActive) {
    // Active, approved Basic-Ed personnel (teachers + staff accounts).
    $personnel = [];
    $uRes = $mysqli->query("SELECT id, full_name, designation, photo, role, secondary_role, sector, department
                            FROM users
                            WHERE role IN ('teacher','staff')
                              AND is_active=1
                              AND account_status='approved'
                            ORDER BY full_name ASC");
    if ($uRes) while ($u = $uRes->fetch_assoc()) {
        $personnel[(int)$u['id']] = $u;
    }

    $facultyIds = [];   // Student Evaluation: Faculty context
    $staffIds   = [];   // Student Evaluation + Teacher peer: Non-Teaching Staff
    $multiIds   = [];   // Student Evaluation: Multi-Role context
    $teachers   = [];   // JHS/SHS teacher roster (Teachers tab)
    $staffRoster = [];  // Non-Teaching Staff roster (Staff tab)

    $studentFacultyQuestionCount = (int)(safe_scalar($mysqli,
        "SELECT COUNT(*) FROM evaluation_questions
         WHERE target_type='Faculty' AND eval_type='general' AND evaluator_role='shared' AND is_active=1"
    ) ?? 0);
    $peerFacultyQuestionCount = (int)(safe_scalar($mysqli,
        "SELECT COUNT(*) FROM evaluation_questions WHERE target_type='Faculty' AND is_active=1"
    ) ?? 0);

    foreach ($personnel as $uid => $u) {
        $levels = ptr_user_levels($mysqli, $uid);
        $grades = [];
        $buckets = [];
        foreach ($levels as $lvl) {
            $g = ptr_grade($lvl);
            if ($g === null || !in_array($g, $scopeGrades, true)) continue;
            $grades[$g] = true;
            $buckets[ptr_level_bucket($g)] = true;
        }

        $hasTeaching = ptr_is_teaching($mysqli, $u);
        $isFaculty = $hasTeaching && !empty($grades);          // JHS/SHS teaching scope only
        $isStaff = ($u['role'] ?? '') === 'staff' && ptr_is_non_teaching_staff($mysqli, $uid);

        if ($isStaff) {
            $staffRoster[] = [
                'id' => $uid, 'name' => (string)$u['full_name'], 'photo' => (string)($u['photo'] ?? ''),
                'designation' => (string)($u['designation'] ?? ''), 'department' => (string)($u['department'] ?? ''),
            ];
        }
        if ($isFaculty && $studentFacultyQuestionCount > 0) $facultyIds[$uid] = true;
        if ($isStaff) {
            $staffHasQuestions = (int)(safe_scalar($mysqli,
                "SELECT COUNT(*) FROM user_questions WHERE user_id=? AND target_type='Staff' AND eval_type='general'",
                'i', [$uid]
            ) ?? 0) > 0;
            if ($staffHasQuestions) $staffIds[$uid] = true;
        }
        if ($isFaculty) {
            $hasMrQuestions = (int)(safe_scalar($mysqli,
                "SELECT COUNT(*) FROM user_questions WHERE user_id=? AND target_type IN ('Staff','Dean','Principal','EA') AND eval_type='general'",
                'i', [$uid]
            ) ?? 0) > 0;
            if ($hasMrQuestions) $multiIds[$uid] = true;
        }

        if ($isFaculty) {
            // PHP turns numeric array keys into ints; keep grades as strings so
            // the strict in_array() filters against $scopeGrades match.
            $gradeList = array_map('strval', array_keys($grades));
            usort($gradeList, fn($a, $b) => (int)$a <=> (int)$b);
            $teachers[] = [
                'id'          => $uid,
                'name'        => (string)$u['full_name'],
                'photo'       => (string)($u['photo'] ?? ''),
                'designation' => (string)($u['designation'] ?? ''),
                'department'  => (string)($u['department'] ?? ''),
                'grades'      => $gradeList,
                'level_label' => ptr_level_label(array_keys($buckets)),
            ];
        }
    }

    // The Principal is a target only when their own question set exists.
    $principalQuestionCount = (int)(safe_scalar($mysqli,
        "SELECT COUNT(*) FROM user_questions WHERE user_id=? AND target_type='Principal' AND eval_type='general'",
        'i', [$principalId]
    ) ?? 0);
    $requiredPrincipal = $principalQuestionCount > 0 ? 1 : 0;
    $principalName = trim((string)($me['full_name'] ?? '')) ?: 'Principal';

    // ── REQUIRED TARGETS ────────────────────────────────────────────
    // Students: Faculty + Staff + Multi-Role (+ Principal).
    $studentTargetDefs = [];
    foreach ($facultyIds as $uid => $_) $studentTargetDefs['faculty:' . $uid]    = ['label' => $personnel[$uid]['full_name'], 'type' => 'Faculty'];
    foreach ($staffIds   as $uid => $_) $studentTargetDefs['staff:' . $uid]      = ['label' => $personnel[$uid]['full_name'], 'type' => 'Staff'];
    foreach ($multiIds   as $uid => $_) $studentTargetDefs['multi:' . $uid]      = ['label' => $personnel[$uid]['full_name'], 'type' => 'Multi-Role'];
    if ($requiredPrincipal > 0)          $studentTargetDefs['school_head:' . $principalId] = ['label' => $principalName, 'type' => 'Principal'];
    $requiredTotal = count($studentTargetDefs);

    // Teachers: every other JHS/SHS teacher + Non-Teaching Staff (+ Principal).
    $teacherTargetDefs = [];
    if ($peerFacultyQuestionCount > 0) {
        foreach ($teachers as $t) $teacherTargetDefs['faculty:' . $t['id']] = ['label' => $t['name'], 'type' => 'Faculty'];
    }
    foreach ($staffIds as $uid => $_) $teacherTargetDefs['staff:' . $uid] = ['label' => $personnel[$uid]['full_name'], 'type' => 'Staff'];
    if ($requiredPrincipal > 0)       $teacherTargetDefs['principal:' . $principalId] = ['label' => $principalName, 'type' => 'Principal'];

    // ── STUDENT ROSTER: ONLY JHS/SHS, NEVER COLLEGE ─────────────────
    $rawStudents = safe_rows($mysqli, "
        SELECT id, full_name, photo, year_level, education_level
        FROM users
        WHERE role='student'
          AND is_active=1
          AND account_status='approved'
        ORDER BY full_name ASC
    ");

    $allowedEdu = [];
    if (in_array('junior_high', $scopeAcademicLevels, true)) $allowedEdu[] = 'junior_high';
    if (in_array('senior_high', $scopeAcademicLevels, true)) $allowedEdu[] = 'senior_high';

    $eligibleStudents = [];
    foreach ($rawStudents as $s) {
        $sid = (int)$s['id'];
        $raw = trim((string)($s['year_level'] ?? ''));
        $g = ptr_grade($raw);
        $edu = strtolower(trim((string)($s['education_level'] ?? '')));

        if ($g !== null) {
            if (!in_array($g, $scopeGrades, true)) continue;      // out of scope (or College text) -> skip
            $level = ptr_student_level_label($raw);
        } elseif ($raw === '' && in_array($edu, $allowedEdu, true)) {
            $level = $edu === 'junior_high' ? 'Junior High' : 'Senior High';
        } else {
            continue;                                              // College / unknown -> never tracked
        }
        $eligibleStudents[$sid] = [
            'id' => $sid, 'name' => (string)$s['full_name'], 'photo' => (string)($s['photo'] ?? ''),
            'grade' => $g, 'level' => $level,
        ];
    }

    // ── STUDENT PROGRESS ────────────────────────────────────────────
    $studentContextMap = [];
    if ($hasPeriod && $requiredTotal > 0 && !empty($eligibleStudents)) {
        $ids = array_keys($eligibleStudents);
        $ph = implode(',', array_fill(0, count($ids), '?'));
        $submissionRows = safe_rows($mysqli, "
            SELECT et.id, et.evaluator_id, et.target_user_id, et.eval_bucket,
                   et.evaluation_context, et.status, et.submitted_at, et.updated_at,
                   EXISTS(
                       SELECT 1
                       FROM questionnaire_answers qam
                       JOIN user_questions uqm ON uqm.id=qam.user_question_id
                       WHERE qam.tracker_id=et.id
                         AND uqm.eval_type='student'
                         AND uqm.target_type='Multi-Role'
                   ) AS multi_answer
            FROM evaluation_tracker et
            WHERE et.eval_type='student'
              AND et.period_id=?
              AND et.evaluator_id IN ($ph)
              AND et.status IN ('submitted','approved','in_progress')
            ORDER BY et.updated_at DESC, et.id DESC
        ", 'i' . str_repeat('i', count($ids)), array_merge([$period_id_int], $ids));

        foreach ($submissionRows as $row) {
            $contextKey = ptr_context_for_submission($row, $principalId, $facultyIds, $staffIds, $multiIds, $requiredPrincipal > 0);
            if ($contextKey === null) continue;
            $sid = (int)$row['evaluator_id'];
            if (!isset($eligibleStudents[$sid])) continue;

            $isCompleted = in_array($row['status'], ['submitted','approved'], true);
            $timeValue = $row['submitted_at'] ?: ($row['updated_at'] ?? null);

            if (!isset($studentContextMap[$sid][$contextKey])) {
                $studentContextMap[$sid][$contextKey] = ['state' => $isCompleted ? 'completed' : 'in_progress', 'at' => $timeValue];
            } elseif ($isCompleted && $studentContextMap[$sid][$contextKey]['state'] !== 'completed') {
                $studentContextMap[$sid][$contextKey] = ['state' => 'completed', 'at' => $timeValue];
            }
        }
    }

    foreach ($eligibleStudents as $sid => $s) {
        $contexts = $studentContextMap[$sid] ?? [];
        $completed = 0; $started = false; $latest = null;
        foreach ($contexts as $c) {
            if ($c['state'] === 'completed') $completed++;
            if ($c['state'] === 'in_progress') $started = true;
            if (!empty($c['at']) && (!$latest || strtotime((string)$c['at']) > strtotime((string)$latest))) $latest = $c['at'];
        }
        $completed = min($requiredTotal, $completed);
        [$state, $label] = ptk_status($completed, $requiredTotal, $started);

        $studentRows[] = [
            'key' => 's' . $sid, 'id' => $sid, 'noun' => 'student',
            'name' => $s['name'], 'photo' => $s['photo'],
            'grades' => $s['grade'] !== null ? [$s['grade']] : [],
            'level_pill' => $s['grade'] !== null ? 'Grade ' . $s['grade'] : 'Basic Education',
            'sub' => ($s['grade'] !== null ? 'Grade ' . $s['grade'] . ' · ' : '') . $s['level'],
            'level_text' => $s['level'],
            'required' => $requiredTotal, 'completed' => $completed,
            'progress' => $requiredTotal > 0 ? min(100, (int)round(($completed / $requiredTotal) * 100)) : 0,
            'status' => $state, 'status_label' => $label, 'last_at' => $latest,
            'details' => ptk_build_details($studentTargetDefs, $contexts),
        ];
    }

    // ── TEACHER PROGRESS (peer evaluations) ─────────────────────────
    $teacherContextMap = [];
    if ($hasPeriod && !empty($teachers) && !empty($teacherTargetDefs)) {
        $tids = array_map(fn($t) => (int)$t['id'], $teachers);
        $ph = implode(',', array_fill(0, count($tids), '?'));
        $peerGroupClause = ptk_has_column($mysqli, 'evaluation_tracker', 'peer_group')
            ? " OR LOWER(TRIM(COALESCE(et.peer_group,''))) IN ('faculty','teacher','staff','teaching staff')"
            : '';
        $peerRows = safe_rows($mysqli, "
            SELECT et.evaluator_id, et.target_user_id, et.status, et.submitted_at, et.updated_at
            FROM evaluation_tracker et
            WHERE et.period_id=?
              AND et.evaluator_id IN ($ph)
              AND et.status IN ('submitted','approved','in_progress')
              AND (et.eval_type IN ('peer','faculty_peer','staff_peer')$peerGroupClause)
            ORDER BY et.updated_at DESC, et.id DESC
        ", 'i' . str_repeat('i', count($tids)), array_merge([$period_id_int], $tids));

        foreach ($peerRows as $row) {
            $key = ptk_teacher_context($row, $teacherTargetDefs);
            if ($key === null) continue;
            $eid = (int)$row['evaluator_id'];
            $isCompleted = in_array($row['status'], ['submitted','approved'], true);
            $timeValue = $row['submitted_at'] ?: ($row['updated_at'] ?? null);

            if (!isset($teacherContextMap[$eid][$key])) {
                $teacherContextMap[$eid][$key] = ['state' => $isCompleted ? 'completed' : 'in_progress', 'at' => $timeValue];
            } elseif ($isCompleted && $teacherContextMap[$eid][$key]['state'] !== 'completed') {
                $teacherContextMap[$eid][$key] = ['state' => 'completed', 'at' => $timeValue];
            }
        }
    }

    foreach ($teachers as $t) {
        $tid = (int)$t['id'];
        $defs = $teacherTargetDefs;
        unset($defs['faculty:' . $tid]);                 // nobody evaluates themselves
        $required = count($defs);
        $contexts = $teacherContextMap[$tid] ?? [];

        $completed = 0; $started = false; $latest = null;
        foreach ($defs as $key => $_) {
            if (!isset($contexts[$key])) continue;
            $c = $contexts[$key];
            if ($c['state'] === 'completed') $completed++;
            if ($c['state'] === 'in_progress') $started = true;
            if (!empty($c['at']) && (!$latest || strtotime((string)$c['at']) > strtotime((string)$latest))) $latest = $c['at'];
        }
        $completed = min($required, $completed);
        [$state, $label] = ptk_status($completed, $required, $started);

        $teacherRows[] = [
            'key' => 't' . $tid, 'id' => $tid, 'noun' => 'teacher',
            'name' => $t['name'], 'photo' => $t['photo'],
            'grades' => $t['grades'],
            'level_pill' => $t['level_label'],
            'sub' => ptk_grades_text($t['grades']),
            'level_text' => $t['level_label'],
            'designation' => $t['designation'], 'department' => $t['department'],
            'required' => $required, 'completed' => $completed,
            'progress' => $required > 0 ? min(100, (int)round(($completed / $required) * 100)) : 0,
            'status' => $state, 'status_label' => $label, 'last_at' => $latest,
            'details' => ptk_build_details($defs, $contexts),
        ];
    }

    // ── STAFF PROGRESS (Non-Teaching Staff -> Executive Assistant) ────
    // The Executive Assistant is the single active superadmin account, the
    // same rule the Evaluate Others page uses.
    $eaRow = safe_rows($mysqli, "SELECT id, full_name FROM users WHERE role='superadmin' AND is_active=1 AND account_status='approved' ORDER BY updated_at DESC, id DESC LIMIT 1");
    $eaId = !empty($eaRow) ? (int)$eaRow[0]['id'] : 0;
    $staffTargetDefs = $eaId > 0 ? ['ea:' . $eaId => ['label' => (string)$eaRow[0]['full_name'], 'type' => 'Executive Assistant']] : [];

    $staffContextMap = [];
    if ($hasPeriod && !empty($staffRoster) && $eaId > 0) {
        $sids = array_map(fn($t) => (int)$t['id'], $staffRoster);
        $ph = implode(',', array_fill(0, count($sids), '?'));
        $staffGroupClause = ptk_has_column($mysqli, 'evaluation_tracker', 'peer_group')
            ? " OR LOWER(TRIM(COALESCE(et.peer_group,''))) = 'staff evaluation'"
            : '';
        $staffSubs = safe_rows($mysqli, "
            SELECT et.evaluator_id, et.target_user_id, et.status, et.submitted_at, et.updated_at
            FROM evaluation_tracker et
            WHERE et.period_id=?
              AND et.evaluator_id IN ($ph)
              AND et.target_user_id=?
              AND et.status IN ('submitted','approved','in_progress')
              AND (et.eval_type='staff'$staffGroupClause)
            ORDER BY et.updated_at DESC, et.id DESC
        ", 'i' . str_repeat('i', count($sids)) . 'i', array_merge([$period_id_int], $sids, [$eaId]));

        foreach ($staffSubs as $row) {
            $eid = (int)$row['evaluator_id'];
            $isCompleted = in_array($row['status'], ['submitted','approved'], true);
            $timeValue = $row['submitted_at'] ?: ($row['updated_at'] ?? null);
            $key = 'ea:' . $eaId;
            if (!isset($staffContextMap[$eid][$key])) {
                $staffContextMap[$eid][$key] = ['state' => $isCompleted ? 'completed' : 'in_progress', 'at' => $timeValue];
            } elseif ($isCompleted && $staffContextMap[$eid][$key]['state'] !== 'completed') {
                $staffContextMap[$eid][$key] = ['state' => 'completed', 'at' => $timeValue];
            }
        }
    }

    foreach ($staffRoster as $m) {
        $mid = (int)$m['id'];
        $contexts = $staffContextMap[$mid] ?? [];
        $required = count($staffTargetDefs);
        $completed = 0; $started = false; $latest = null;
        foreach ($contexts as $c) {
            if ($c['state'] === 'completed') $completed++;
            if ($c['state'] === 'in_progress') $started = true;
            if (!empty($c['at']) && (!$latest || strtotime((string)$c['at']) > strtotime((string)$latest))) $latest = $c['at'];
        }
        $completed = min($required, $completed);
        [$state, $label] = ptk_status($completed, $required, $started);

        $staffRows[] = [
            'key' => 'f' . $mid, 'id' => $mid, 'noun' => 'staff member',
            'name' => $m['name'], 'photo' => $m['photo'],
            'grades' => [],
            'level_pill' => 'Non-Teaching',
            'sub' => $m['designation'] !== '' ? $m['designation'] : ($m['department'] !== '' ? $m['department'] : 'Non-Teaching Staff'),
            'level_text' => 'Non-Teaching Staff',
            'designation' => $m['designation'], 'department' => $m['department'],
            'required' => $required, 'completed' => $completed,
            'progress' => $required > 0 ? min(100, (int)round(($completed / $required) * 100)) : 0,
            'status' => $state, 'status_label' => $label, 'last_at' => $latest,
            'details' => ptk_build_details($staffTargetDefs, $contexts),
        ];
    }

    usort($studentRows, fn($a, $b) => strnatcasecmp($a['name'], $b['name']));
    usort($staffRows, fn($a, $b) => strnatcasecmp($a['name'], $b['name']));
    usort($teacherRows, fn($a, $b) => strnatcasecmp($a['name'], $b['name']));
}

// ── ACTIVE TAB: SUMMARY CARDS, FILTERS, PAGING ──────────────────────
$activeAll = $tab === 'teachers' ? $teacherRows : ($tab === 'staff' ? $staffRows : $studentRows);

$cards = [];
if ($tab === 'staff') {
    $stTotal = count($staffRows);
    $stDone  = count(array_filter($staffRows, fn($r) => $r['status'] === 'completed'));
    $stProg  = count(array_filter($staffRows, fn($r) => $r['status'] === 'in_progress'));
    $stNone  = $stTotal - $stDone - $stProg;
    $pctOf   = fn($n) => ($stTotal > 0 ? (int)round($n / $stTotal * 100) : 0) . '% of staff';
    $cards = [
        ['label' => 'Non-Teaching Staff', 'total' => $stTotal, 'completed' => $stDone, 'pending' => $stTotal - $stDone],
        ['label' => 'Not Started', 'total' => $stNone, 'foot' => $pctOf($stNone)],
        ['label' => 'In Progress', 'total' => $stProg, 'foot' => $pctOf($stProg)],
        ['label' => 'Completed',   'total' => $stDone, 'foot' => $pctOf($stDone)],
    ];
}
foreach ($tab === 'staff' ? [] : $scopeGrades as $g) {
    $inGrade = array_filter($activeAll, fn($r) => in_array($g, $r['grades'], true));
    $done = count(array_filter($inGrade, fn($r) => $r['status'] === 'completed'));
    $cards[] = [
        'label'     => 'Grade ' . $g . ' · ' . (in_array($g, ['7','8','9','10'], true) ? 'JHS' : 'SHS'),
        'total'     => count($inGrade),
        'completed' => $done,
        'pending'   => count($inGrade) - $done,
    ];
}

$filtered = $activeAll;
if ($search !== '') {
    $needle = mb_strtolower($search);
    $filtered = array_values(array_filter($filtered, function ($r) use ($needle) {
        $hay = $r['name'] . ' ' . $r['sub'] . ' ' . $r['level_text'] . ' ' . ($r['designation'] ?? '') . ' ' . ($r['department'] ?? '');
        return str_contains(mb_strtolower($hay), $needle);
    }));
}
if ($gradeFilter !== '' && $tab !== 'staff') {
    $filtered = array_values(array_filter($filtered, fn($r) => in_array($gradeFilter, $r['grades'], true)));
}
if ($status !== '') {
    $filtered = array_values(array_filter($filtered, fn($r) => $r['status'] === $status));
}

$totalRows  = count($filtered);
$totalPages = max(1, (int)ceil($totalRows / $perPage));
$page       = max(1, min($totalPages, $page));

// CSV export of the active tab (respects the current filters).
if (($_GET['export'] ?? '') === 'csv' && $structureActive) {
    $noun = $tab === 'teachers' ? 'teacher' : ($tab === 'staff' ? 'staff' : 'student');
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="principal_' . $noun . '_tracker_' . date('Ymd_His') . '.csv"');
    $out = fopen('php://output', 'w');
    fputcsv($out, [ptk_nouns($tab)[2], 'Level', 'Grade', 'Required', 'Completed', 'Status', 'Progress', 'Last Activity']);
    foreach ($filtered as $r) {
        fputcsv($out, [
            ptk_csv_cell($r['name']), $r['level_text'], ptk_grades_text($r['grades']),
            $r['required'], $r['completed'] . ' / ' . $r['required'],
            $r['status_label'], $r['progress'] . '%', $r['last_at'] ?? '',
        ]);
    }
    fclose($out);
    exit;
}

$pageRows = array_slice($filtered, ($page - 1) * $perPage, $perPage);

// Live JSON used by the page to refresh without a reload.
if ($isAjax) {
    header('Content-Type: application/json; charset=utf-8');
    header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
    if (!$structureActive) {
        echo json_encode(['ok' => false]);
        exit;
    }
    echo json_encode([
        'ok'        => true,
        'tab'       => $tab,
        'n'         => $tab === 'staff' ? 4 : count($scopeGrades),
        'tabs'      => ptk_render_tabs($tab, count($studentRows), count($teacherRows), count($staffRows)),
        'cards'     => ptk_render_cards($tab, $cards),
        'pills'     => ptk_render_pills($tab, $scopeGrades, $gradeFilter),
        'list'      => ptk_render_list($pageRows, $tab, $hasPeriod, $totalRows, $page, $perPage, $totalPages),
        'checkedAt' => date('c'),
    ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
    exit;
}

html_head_open('PBI — Principal Evaluation Tracker');
?>
<style id="principal-tracker-dean-layout">
/* Principal Evaluation Tracker — Dean-style layout, Principal amber accent.
   Everything is scoped under #ptk so the global Principal theme layers
   (light/dark/compact) cannot restyle it by accident. */
#ptk{
  --ptk-ink:#12263A;--ptk-muted:#6D8194;--ptk-line:#E1E9F1;--ptk-line-2:#CFDBE7;
  --ptk-surface:#FFFFFF;--ptk-soft:#F5F8FC;--ptk-hover:#FAFBFD;
  --ptk-accent:#D99A2B;--ptk-accent-ink:#8F5F00;--ptk-accent-soft:#FFF6E1;--ptk-accent-line:#EFCF86;--ptk-on-accent:#1F1500;
  --ptk-green:#0F9F6E;--ptk-green-soft:#EAF8F2;
  --ptk-amber:#A86B05;--ptk-amber-soft:#FFF4DB;
  --ptk-gray:#617689;--ptk-gray-soft:#EEF3F8;
  --ptk-track:#E3EAF1;--ptk-shadow:0 2px 10px rgba(20,42,67,.05);
  color:var(--ptk-ink);font-family:'DM Sans',sans-serif;
}
html.principal-theme-dark #ptk{
  --ptk-ink:#EAF0F7;--ptk-muted:#9FB2C6;--ptk-line:rgba(255,255,255,.09);--ptk-line-2:rgba(255,255,255,.16);
  --ptk-surface:#172A45;--ptk-soft:#0F1F3D;--ptk-hover:#1B3150;
  --ptk-accent:#F0B84D;--ptk-accent-ink:#F0B84D;--ptk-accent-soft:rgba(217,154,43,.14);--ptk-accent-line:rgba(240,184,77,.36);--ptk-on-accent:#1F1500;
  --ptk-green:#6EE7B7;--ptk-green-soft:rgba(16,185,129,.13);
  --ptk-amber:#F0B84D;--ptk-amber-soft:rgba(217,154,43,.14);
  --ptk-gray:#A9B7C7;--ptk-gray-soft:#26364A;
  --ptk-track:rgba(255,255,255,.11);--ptk-shadow:0 4px 18px rgba(0,0,0,.28);
}
#ptk,#ptk *,#ptk *::before,#ptk *::after{box-sizing:border-box;}
#ptk .ptk-sr{position:absolute!important;width:1px;height:1px;margin:-1px;padding:0;overflow:hidden;clip:rect(0,0,0,0);white-space:nowrap;border:0;}

/* Tabs */
#ptk .ptk-topline{display:flex;align-items:center;justify-content:space-between;gap:14px;flex-wrap:wrap;margin:0 0 20px;}
#ptk .ptk-tabs{display:inline-flex;gap:4px;padding:6px;background:var(--ptk-surface);border:1px solid var(--ptk-line);border-radius:14px;box-shadow:var(--ptk-shadow);}
#ptk .ptk-tab{display:inline-flex;align-items:center;gap:9px;height:42px;padding:0 20px;border-radius:10px;color:var(--ptk-muted)!important;font-size:15px;font-weight:700;text-decoration:none!important;transition:background .15s,color .15s;}
#ptk .ptk-tab:hover{color:var(--ptk-ink)!important;background:var(--ptk-soft);}
#ptk .ptk-tab.active{background:linear-gradient(180deg,#E6AD3E,var(--ptk-accent))!important;color:var(--ptk-on-accent)!important;box-shadow:0 3px 10px rgba(217,154,43,.30);}
#ptk .ptk-tab-count{min-width:24px;height:22px;padding:0 8px;display:inline-flex;align-items:center;justify-content:center;border-radius:999px;background:var(--ptk-accent-soft);color:var(--ptk-accent-ink);font-size:12px;font-weight:800;}
#ptk .ptk-tab.active .ptk-tab-count{background:rgba(31,21,0,.16);color:var(--ptk-on-accent);}
#ptk .ptk-live{display:inline-flex;align-items:center;gap:7px;padding:6px 11px;border-radius:999px;background:var(--ptk-green-soft);color:var(--ptk-green);font-size:11px;font-weight:800;}
#ptk .ptk-live::before{content:'';width:7px;height:7px;border-radius:50%;background:currentColor;animation:ptkPulse 2s ease-in-out infinite;}
#ptk .ptk-live.offline{background:var(--ptk-gray-soft);color:var(--ptk-gray);}
#ptk .ptk-live.offline::before{animation:none;}
@keyframes ptkPulse{0%,100%{opacity:1;transform:scale(1)}50%{opacity:.35;transform:scale(.75)}}

/* Summary cards (one per Grade in the Principal's scope) */
#ptk .ptk-cards{display:grid;grid-template-columns:repeat(var(--ptk-n,4),minmax(0,1fr));gap:14px;margin:0 0 20px;}
#ptk .ptk-card{background:var(--ptk-surface);border:1px solid var(--ptk-line);border-top:3px solid var(--ptk-accent);border-radius:14px;padding:16px 18px 14px;box-shadow:var(--ptk-shadow);}
#ptk .ptk-card-top{display:flex;align-items:flex-start;justify-content:space-between;gap:10px;}
#ptk .ptk-card-label{font-size:11px;font-weight:800;letter-spacing:.09em;text-transform:uppercase;color:var(--ptk-muted);padding-top:4px;}
#ptk .ptk-card-icon{width:38px;height:38px;flex:0 0 38px;border-radius:10px;display:flex;align-items:center;justify-content:center;background:var(--ptk-accent-soft);color:var(--ptk-accent-ink);font-size:15px;}
#ptk .ptk-card-num{margin:2px 0 12px;font-size:30px;line-height:1;font-weight:800;color:var(--ptk-ink);}
#ptk .ptk-card-foot{display:flex;align-items:center;gap:9px;padding-top:11px;border-top:1px solid var(--ptk-line);font-size:13px;color:var(--ptk-muted);white-space:nowrap;}
#ptk .ptk-card-foot b{color:var(--ptk-accent-ink);font-weight:700;}
#ptk .ptk-card-sep{color:var(--ptk-line-2);}

/* Filters */
#ptk .ptk-filters{display:flex;align-items:center;justify-content:space-between;gap:12px 16px;flex-wrap:wrap;margin:0 0 16px;}
#ptk .ptk-pills{display:flex;gap:10px;flex-wrap:wrap;}
#ptk .ptk-pill{display:inline-flex;align-items:center;height:44px;padding:0 20px;border-radius:12px;border:1px solid var(--ptk-line-2);background:var(--ptk-surface);color:var(--ptk-ink)!important;font-size:14px;font-weight:600;text-decoration:none!important;transition:background .15s,border-color .15s;}
#ptk .ptk-pill:hover{background:var(--ptk-soft);border-color:var(--ptk-accent-line);}
#ptk .ptk-pill.active{background:linear-gradient(180deg,#E6AD3E,var(--ptk-accent))!important;border-color:var(--ptk-accent)!important;color:var(--ptk-on-accent)!important;font-weight:800;}
#ptk .ptk-controls{display:flex;align-items:center;gap:10px;flex-wrap:wrap;margin-left:auto;}
#ptk #ptkStatus{height:44px;min-width:140px;padding:0 38px 0 16px;border-radius:12px;border:1px solid var(--ptk-line-2)!important;background-color:var(--ptk-surface)!important;color:var(--ptk-ink)!important;font:600 14px 'DM Sans',sans-serif;appearance:none;-webkit-appearance:none;cursor:pointer;
  background-image:url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='8' viewBox='0 0 12 8'%3E%3Cpath d='M1 1.5l5 5 5-5' fill='none' stroke='%236D8194' stroke-width='1.8' stroke-linecap='round' stroke-linejoin='round'/%3E%3C/svg%3E")!important;background-repeat:no-repeat!important;background-position:right 14px center!important;}
#ptk #ptkStatus option{background:var(--ptk-surface);color:var(--ptk-ink);}
#ptk .ptk-search{position:relative;display:block;width:270px;max-width:100%;}
#ptk .ptk-search i{position:absolute;left:14px;top:50%;transform:translateY(-50%);color:var(--ptk-accent-ink);font-size:14px;pointer-events:none;}
#ptk #ptkSearch{width:100%;height:44px;padding:0 14px 0 40px;border-radius:12px;border:1px solid var(--ptk-line-2)!important;background:var(--ptk-surface)!important;color:var(--ptk-ink)!important;font:500 14px 'DM Sans',sans-serif;outline:none;-webkit-appearance:none;appearance:none;}
#ptk #ptkSearch::placeholder{color:var(--ptk-muted);opacity:1;}
#ptk #ptkStatus:focus,#ptk #ptkSearch:focus{border-color:var(--ptk-accent)!important;box-shadow:0 0 0 3px rgba(217,154,43,.18);}
#ptk .ptk-export{display:inline-flex;align-items:center;gap:8px;height:44px;padding:0 18px;border-radius:12px;border:1px solid var(--ptk-accent-line);background:var(--ptk-accent-soft);color:var(--ptk-accent-ink)!important;font-size:14px;font-weight:700;text-decoration:none!important;white-space:nowrap;}
#ptk .ptk-export:hover{border-color:var(--ptk-accent);}

/* Table (CSS grid — avoids the global <table> overrides) */
#ptk .ptk-list{background:var(--ptk-surface);border:1px solid var(--ptk-line);border-radius:14px;box-shadow:var(--ptk-shadow);overflow:hidden;}
#ptk .ptk-list-scroll{overflow-x:auto;}
#ptk .ptk-list-inner{min-width:900px;}
#ptk .ptk-head,#ptk .ptk-row{display:grid;grid-template-columns:minmax(230px,2.5fr) minmax(120px,1.2fr) minmax(80px,.8fr) minmax(100px,1fr) minmax(120px,1.1fr) minmax(220px,2.2fr);align-items:center;column-gap:14px;padding:0 22px;}
#ptk .ptk-head{height:56px;background:var(--ptk-soft);border-bottom:1px solid var(--ptk-line);font-size:11px;font-weight:800;letter-spacing:.09em;text-transform:uppercase;color:var(--ptk-muted);}
#ptk .ptk-item{border-bottom:1px solid var(--ptk-line);}
#ptk .ptk-item:last-child{border-bottom:0;}
#ptk .ptk-row{min-height:88px;cursor:pointer;transition:background .12s;outline:none;}
#ptk .ptk-row:hover,#ptk .ptk-row:focus-visible{background:var(--ptk-hover);}
#ptk .ptk-row:focus-visible{box-shadow:inset 3px 0 0 var(--ptk-accent);}
#ptk .ptk-c-person{display:flex;align-items:center;gap:14px;min-width:0;}
#ptk .ptk-avatar{width:54px;height:54px;flex:0 0 54px;border-radius:50%;display:flex;align-items:center;justify-content:center;overflow:hidden;background:var(--ptk-accent-soft);border:1px solid var(--ptk-accent-line);color:var(--ptk-accent-ink);font-size:19px;}
#ptk .ptk-avatar img{width:100%;height:100%;object-fit:cover;}
#ptk .ptk-person-copy{display:flex;flex-direction:column;gap:3px;min-width:0;}
#ptk .ptk-name{font-size:15px;font-weight:700;color:var(--ptk-ink);overflow:hidden;text-overflow:ellipsis;white-space:nowrap;}
#ptk .ptk-sub{font-size:12.5px;color:var(--ptk-muted);overflow:hidden;text-overflow:ellipsis;white-space:nowrap;}
#ptk .ptk-level{display:inline-flex;align-items:center;justify-content:center;min-height:44px;padding:0 18px;border-radius:12px;border:1px solid var(--ptk-accent-line);background:var(--ptk-accent-soft);color:var(--ptk-accent-ink);font-size:13px;font-weight:700;white-space:nowrap;}
#ptk .ptk-num{font-size:16px;font-weight:500;color:var(--ptk-ink);}
#ptk .ptk-status{display:inline-flex;align-items:center;padding:7px 14px;border-radius:999px;font-size:12.5px;font-weight:700;white-space:nowrap;}
#ptk .ptk-status.not_started{background:var(--ptk-gray-soft);color:var(--ptk-gray);}
#ptk .ptk-status.in_progress{background:var(--ptk-amber-soft);color:var(--ptk-amber);}
#ptk .ptk-status.completed{background:var(--ptk-green-soft);color:var(--ptk-green);}
#ptk .ptk-c-prog{display:flex;align-items:center;gap:12px;min-width:0;}
#ptk .ptk-pct{width:40px;flex:0 0 40px;font-size:14px;font-weight:600;color:var(--ptk-ink);}
#ptk .ptk-bar-wrap{flex:1;min-width:110px;display:flex;flex-direction:column;gap:6px;}
#ptk .ptk-bar{display:block;height:7px;border-radius:999px;background:var(--ptk-track);overflow:hidden;}
#ptk .ptk-bar i{display:block;height:100%;border-radius:inherit;background:linear-gradient(90deg,#C98A1C,var(--ptk-accent));transition:width .25s;}
#ptk .ptk-last{font-size:11.5px;color:var(--ptk-muted);white-space:nowrap;}
#ptk .ptk-chev{width:16px;flex:0 0 16px;text-align:right;color:var(--ptk-muted);font-size:13px;transition:transform .18s;}
#ptk .ptk-item.open .ptk-chev{transform:rotate(90deg);}
#ptk .ptk-item.open .ptk-row{background:var(--ptk-hover);}
#ptk .ptk-empty{margin:0;padding:34px 20px;text-align:center;font-size:14px;color:var(--ptk-muted);}

/* Expanded per-row breakdown */
#ptk .ptk-detail{padding:16px 22px 20px;background:var(--ptk-soft);border-top:1px solid var(--ptk-line);}
#ptk .ptk-detail[hidden]{display:none;}
#ptk .ptk-detail-head{display:flex;align-items:baseline;gap:12px;flex-wrap:wrap;margin:0 0 12px;}
#ptk .ptk-detail-head strong{font-size:13.5px;color:var(--ptk-ink);}
#ptk .ptk-detail-head span{font-size:12px;color:var(--ptk-muted);}
#ptk .ptk-detail-empty{margin:0;font-size:13px;color:var(--ptk-muted);}
#ptk .ptk-targets{list-style:none;margin:0;padding:0;display:grid;grid-template-columns:repeat(auto-fill,minmax(270px,1fr));gap:8px;}
#ptk .ptk-target{display:flex;align-items:center;gap:10px;padding:9px 12px;border-radius:10px;background:var(--ptk-surface);border:1px solid var(--ptk-line);font-size:13px;min-width:0;}
#ptk .ptk-target i{flex:0 0 auto;font-size:14px;}
#ptk .ptk-target.completed i{color:var(--ptk-green);}
#ptk .ptk-target.in_progress i{color:var(--ptk-amber);}
#ptk .ptk-target.pending i{color:var(--ptk-muted);opacity:.55;}
#ptk .ptk-target-name{flex:1;min-width:0;font-weight:600;color:var(--ptk-ink);overflow:hidden;text-overflow:ellipsis;white-space:nowrap;}
#ptk .ptk-target-type{flex:0 0 auto;padding:2px 8px;border-radius:999px;background:var(--ptk-accent-soft);color:var(--ptk-accent-ink);font-size:10.5px;font-weight:800;}
#ptk .ptk-target-state{flex:0 0 auto;font-size:11.5px;color:var(--ptk-muted);}

/* Footer / pager */
#ptk .ptk-foot{display:flex;align-items:center;justify-content:space-between;gap:12px;flex-wrap:wrap;padding:14px 22px;border-top:1px solid var(--ptk-line);font-size:13px;color:var(--ptk-muted);}
#ptk .ptk-pager{display:flex;align-items:center;gap:6px;}
#ptk .ptk-page{min-width:34px;height:34px;padding:0 10px;display:inline-flex;align-items:center;justify-content:center;border-radius:9px;border:1px solid var(--ptk-line-2);background:var(--ptk-surface);color:var(--ptk-muted)!important;font-size:13px;font-weight:700;text-decoration:none!important;}
#ptk .ptk-page:hover{border-color:var(--ptk-accent-line);color:var(--ptk-ink)!important;}
#ptk .ptk-page.active{background:var(--ptk-accent-soft);border-color:var(--ptk-accent);color:var(--ptk-accent-ink)!important;}
#ptk .ptk-page.disabled{opacity:.4;pointer-events:none;}
#ptk .ptk-ellipsis{color:var(--ptk-muted);padding:0 3px;}


/* Six grades (JHS + SHS) only fit in one row on wide screens; otherwise 3 x 2. */
@media(max-width:1749px){#ptk .ptk-cards[data-n="6"]{grid-template-columns:repeat(3,minmax(0,1fr));}}
@media(max-width:1000px){#ptk .ptk-cards[data-n="4"]{grid-template-columns:repeat(2,minmax(0,1fr));}}
@media(max-width:900px){
  #ptk .ptk-controls{width:100%;margin-left:0;}
  #ptk .ptk-search{flex:1 1 200px;width:auto;}
  #ptk .ptk-tab{padding:0 14px;font-size:14px;}
}
@media(max-width:560px){
  #ptk .ptk-cards,#ptk .ptk-cards[data-n="6"]{grid-template-columns:repeat(2,minmax(0,1fr));}
  #ptk .ptk-pill{padding:0 14px;height:40px;}
}
@media print{#ptk .ptk-live,#ptk .ptk-controls,#ptk .ptk-pager{display:none!important;}}
</style>
<?php render_principal_sidebar('tracker', $me, $scopeLabel, $photo_src); ?>
<main class="main principal-feature-page">
<div id="principalFeatureWorkspace" class="principal-feature-shell">
<div id="ptk">
    <h1 class="ptk-sr">Evaluation Tracker</h1>

<?php if (!$structureActive): ?>
    <?php render_scope_status($settings, 'tracker'); ?>
<?php else: ?>
    <div class="ptk-topline">
        <nav class="ptk-tabs" id="ptkTabs" aria-label="Tracker sections"><?= ptk_render_tabs($tab, count($studentRows), count($teacherRows), count($staffRows)) ?></nav>
        <span class="ptk-live" id="ptkLive">Live</span>
    </div>

    <?php $cardsN = $tab === 'staff' ? 4 : (int)min(6, max(1, count($scopeGrades))); ?><section class="ptk-cards" id="ptkCards" data-n="<?= $cardsN ?>" style="--ptk-n:<?= $cardsN ?>" aria-label="Progress by grade level"><?= ptk_render_cards($tab, $cards) ?></section>

    <form class="ptk-filters" id="ptkFilters" method="get" action="principal_evaluation_tracker.php">
        <input type="hidden" name="tab" value="<?= ptk_h($tab) ?>">
        <input type="hidden" name="grade" value="<?= ptk_h($gradeFilter) ?>">
        <div class="ptk-pills" id="ptkPills"><?= ptk_render_pills($tab, $scopeGrades, $gradeFilter) ?></div>
        <div class="ptk-controls">
            <select id="ptkStatus" name="status" aria-label="Filter by status">
                <option value="">All Status</option>
                <option value="not_started" <?= $status === 'not_started' ? 'selected' : '' ?>>Not Started</option>
                <option value="in_progress" <?= $status === 'in_progress' ? 'selected' : '' ?>>In Progress</option>
                <option value="completed" <?= $status === 'completed' ? 'selected' : '' ?>>Completed</option>
            </select>
            <label class="ptk-search"><i class="fa-solid fa-magnifying-glass"></i><input id="ptkSearch" type="search" name="search" autocomplete="off" placeholder="Search <?= ptk_h(strtolower(ptk_nouns($tab)[0])) ?> by name…" value="<?= ptk_h($search) ?>"></label>
            <a class="ptk-export" id="ptkExport" href="<?= ptk_h(ptk_url(['export' => 'csv'])) ?>"><i class="fa-solid fa-download"></i> Export</a>
            <noscript><button type="submit">Apply</button></noscript>
        </div>
    </form>

    <section class="ptk-list" id="ptkList" aria-live="polite"><?= ptk_render_list($pageRows, $tab, $hasPeriod, $totalRows, $page, $perPage, $totalPages) ?></section>

<?php endif; ?>
</div>
</div>
</main>

<script>
(function(){
    const root = document.getElementById('ptk');
    if (!root || !document.getElementById('ptkList')) return;

    const $ = id => document.getElementById(id);
    const els = {
        tabs: $('ptkTabs'), cards: $('ptkCards'), pills: $('ptkPills'), list: $('ptkList'),
        live: $('ptkLive'), search: $('ptkSearch'), status: $('ptkStatus'), exp: $('ptkExport'),
        form: $('ptkFilters')
    };
    const BASE = 'principal_evaluation_tracker.php';
    const open = new Set();
    let seq = 0, lastSig = '', timer = null;

    function setLive(text, off) { els.live.textContent = text; els.live.classList.toggle('offline', !!off); }

    function currentUrl() { return new URL(window.location.href); }

    function exportHref() {
        const u = currentUrl();
        u.searchParams.delete('ajax'); u.searchParams.delete('_ts'); u.searchParams.delete('page');
        u.searchParams.set('export', 'csv');
        return BASE + '?' + u.searchParams.toString();
    }

    function syncControls() {
        const p = currentUrl().searchParams;
        if (document.activeElement !== els.search) els.search.value = p.get('search') || '';
        els.status.value = p.get('status') || '';
        const t = p.get('tab');
        const tab = (t === 'teachers' || t === 'staff') ? t : 'students';
        els.search.placeholder = 'Search ' + ({ teachers: 'teacher', staff: 'staff member', students: 'student' })[tab] + ' by name…';
        els.form.elements['tab'].value = tab;
        els.form.elements['grade'].value = p.get('grade') || '';
        els.exp.href = exportHref();
    }

    function restoreOpen() {
        els.list.querySelectorAll('.ptk-item').forEach(item => {
            if (open.has(item.dataset.key)) setOpen(item, true);
        });
    }

    function setOpen(item, on) {
        const row = item.querySelector('.ptk-row');
        const detail = item.querySelector('.ptk-detail');
        item.classList.toggle('open', on);
        if (row) row.setAttribute('aria-expanded', on ? 'true' : 'false');
        if (detail) detail.hidden = !on;
        if (on) open.add(item.dataset.key); else open.delete(item.dataset.key);
    }

    async function load(force) {
        const my = ++seq;
        try {
            const u = currentUrl();
            u.searchParams.set('ajax', '1');
            u.searchParams.set('_ts', Date.now().toString());
            u.searchParams.delete('export');
            const res = await fetch(u.toString(), { credentials: 'same-origin', cache: 'no-store', headers: { 'Accept': 'application/json' } });
            if (!res.ok) throw new Error('bad status');
            const d = await res.json();
            if (my !== seq) return;                 // a newer request superseded this one
            if (!d.ok) throw new Error('not ok');
            setLive('Live', false);

            const sig = d.tabs + '|' + d.cards + '|' + d.pills + '|' + d.list;
            if (!force && (lastSig === '' || sig === lastSig)) { lastSig = sig; return; }
            lastSig = sig;

            if (d.n) { els.cards.dataset.n = d.n; els.cards.style.setProperty('--ptk-n', d.n); }
            els.tabs.innerHTML = d.tabs;
            els.cards.innerHTML = d.cards;
            els.pills.innerHTML = d.pills;
            els.list.innerHTML = d.list;
            restoreOpen();
            syncControls();
        } catch (e) {
            if (my === seq) setLive('Offline', true);
        }
    }

    function navigate(url, replace) {
        const u = new URL(url, window.location.href);
        u.searchParams.delete('ajax'); u.searchParams.delete('_ts'); u.searchParams.delete('export');
        window.history[replace ? 'replaceState' : 'pushState']({}, '', u.toString());
        syncControls();
        load(true);
    }

    function patch(over) {
        const u = currentUrl();
        Object.keys(over).forEach(k => {
            if (over[k] === '' || over[k] == null) u.searchParams.delete(k); else u.searchParams.set(k, over[k]);
        });
        if (!('page' in over)) u.searchParams.delete('page');
        return u.toString();
    }

    root.addEventListener('click', e => {
        const a = e.target.closest('a[data-nav]');
        if (a && root.contains(a)) {
            if (e.metaKey || e.ctrlKey || e.shiftKey || e.button === 1) return;
            e.preventDefault();
            if (!a.classList.contains('disabled')) navigate(a.href, false);
            return;
        }
        const row = e.target.closest('.ptk-row');
        if (row) { const item = row.closest('.ptk-item'); setOpen(item, !item.classList.contains('open')); }
    });
    root.addEventListener('keydown', e => {
        if ((e.key === 'Enter' || e.key === ' ') && e.target.classList && e.target.classList.contains('ptk-row')) {
            e.preventDefault();
            const item = e.target.closest('.ptk-item');
            setOpen(item, !item.classList.contains('open'));
        }
    });

    els.status.addEventListener('change', () => navigate(patch({ status: els.status.value }), false));
    let st = null;
    els.search.addEventListener('input', () => {
        clearTimeout(st);
        st = setTimeout(() => navigate(patch({ search: els.search.value.trim() }), true), 300);
    });
    els.form.addEventListener('submit', e => { e.preventDefault(); clearTimeout(st); navigate(patch({ search: els.search.value.trim() }), true); });
    window.addEventListener('popstate', () => { syncControls(); load(true); });

    syncControls();
    load(false);
    timer = setInterval(() => { if (!document.hidden) load(false); }, 5000);
})();
</script>
</body>
</html>
<?php if ($mysqli->ping()) $mysqli->close(); ?>