<?php
// principal_evaluation_tracker.php
// PRIMARY PURPOSE: track JHS/SHS student participation in Student Evaluation.
// OPTIONAL PURPOSE: show the Principal's own Faculty/Teaching Staff evaluation
// progress as a secondary section. Optional faculty work never changes the
// student-participation totals above it.

require_once 'principal_common.php';

$principalId = (int)$_SESSION['user_id'];

$settings       = $schoolHeadSettings;
$period_id_int  = (int)($settings['period_id'] ?? 0);
$hasPeriod      = $period_id_int > 0;
$evalOpen       = !empty($settings['school_head_is_open']);

// ── FILTER / SORT INPUT FOR THE PRIMARY STUDENT TRACKER ──────────────
$search = trim($_GET['search'] ?? '');
$levelFilter = trim($_GET['level'] ?? '');
$gradeFilter = trim($_GET['grade'] ?? '');
$status = trim($_GET['status'] ?? '');

if ($myLevel !== 'both') $levelFilter = '';
if (!in_array($levelFilter, ['', 'junior_high', 'senior_high'], true)) $levelFilter = '';
if (!in_array($gradeFilter, $scopeGrades, true)) $gradeFilter = '';
if (!in_array($status, ['', 'not_started', 'in_progress', 'completed'], true)) $status = '';

$studentSorts = ['name','grade','status'];
$sort = $_GET['sort'] ?? 'name';
if (!in_array($sort, $studentSorts, true)) $sort = 'name';
$dir = strtolower($_GET['dir'] ?? 'asc') === 'desc' ? 'desc' : 'asc';
$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = 5;

// ── PRIMARY TRACKER OUTPUTS ─────────────────────────────────────────
$students = [];
$pageStudents = [];
$studentsAssigned = 0;
$studentsSubmitted = 0;
$pendingStudents = 0;
$completionPct = 0;
$requiredTotal = 0;
$totalPages = 1;
$jhsStudentCount = 0;
$shsStudentCount = 0;
$requiredFaculty = 0;
$requiredStaff = 0;
$requiredMulti = 0;
$requiredPrincipal = 0;

// ── SECONDARY OPTIONAL FACULTY TRACKER OUTPUTS ──────────────────────
$faculty = [];
$facultyAssigned = 0;
$facultyEvaluated = 0;
$pendingFaculty = 0;
$facultyCompletionPct = 0;
$facultyQuestionCount = 0;

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

function ptr_grade_sort(array $grades): string {
    $n = array_map('intval', $grades);
    sort($n, SORT_NUMERIC);
    return implode(',', $n);
}

function ptr_student_level_label(string $rawYear): string {
    $g = ptr_grade($rawYear);
    if ($g === null) return 'Basic Education';
    return in_array($g, ['7','8','9','10'], true) ? 'Junior High' : 'Senior High';
}

function ptr_context_for_submission(array $row, int $principalId, array $facultyIds, array $staffIds, array $multiIds, bool $principalRequired): ?string {
    $tid = (int)$row['target_user_id'];
    $bucket = strtolower(trim((string)($row['eval_bucket'] ?? '')));
    $context = strtolower(trim((string)($row['evaluation_context'] ?? '')));

    // Principal is a valid School Head student-evaluation target only for the
    // Basic Education side; College students do not belong to this tracker.
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

    // Current Principal scope is the final authority for who belongs to the
    // Faculty and Staff contexts. This also prevents an old/stale bucket from
    // turning a current Non-Teaching Staff person into a Faculty completion.
    if (isset($staffIds[$tid]) && ($explicitStaff || (!$explicitFaculty && !$explicitMulti))) {
        return 'staff:' . $tid;
    }
    if (isset($facultyIds[$tid]) && ($explicitFaculty || (!$explicitStaff && !$explicitMulti))) {
        return 'faculty:' . $tid;
    }

    return null;
}

if ($structureActive) {
    // ── BUILD THE CURRENT PRINCIPAL BASIC-ED PERSONNEL CONTEXT ────────
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

    $facultyIds = [];
    $staffIds = [];
    $multiIds = [];

    // Faculty is required only when the Student Evaluation Faculty question
    // bank actually contains questions. The optional Principal-to-Faculty
    // section below may still list the personnel even when that bank is empty.
    $studentFacultyQuestionCount = (int)(safe_scalar($mysqli,
        "SELECT COUNT(*) FROM evaluation_questions
         WHERE target_type='Faculty' AND eval_type='general' AND evaluator_role='shared' AND is_active=1"
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
        $isFaculty = $hasTeaching && !empty($grades);
        $isStaff = ($u['role'] ?? '') === 'staff' && ptr_is_non_teaching_staff($mysqli, $uid);

        // Faculty/Teaching Staff required contexts are limited to JHS/SHS.
        if ($isFaculty && $studentFacultyQuestionCount > 0) $facultyIds[$uid] = true;
        if ($isStaff) {
            $staffHasQuestions = (int)(safe_scalar($mysqli,
                "SELECT COUNT(*) FROM user_questions WHERE user_id=? AND target_type='Staff' AND eval_type='general'",
                'i', [$uid]
            ) ?? 0) > 0;
            if ($staffHasQuestions) $staffIds[$uid] = true;
        }

        // Multi-Role is a distinct student-evaluation context. Only a person
        // already in the Principal's Faculty scope can contribute here.
        if ($isFaculty) {
            $hasMrQuestions = (int)(safe_scalar($mysqli,
                "SELECT COUNT(*) FROM user_questions WHERE user_id=? AND target_type IN ('Staff','Dean','Principal','EA') AND eval_type='general'",
                'i', [$uid]
            ) ?? 0) > 0;
            if ($hasMrQuestions) $multiIds[$uid] = true;
        }

        // Optional Principal -> Faculty roster (kept separate from student
        // participation). This is intentionally JHS/SHS only.
        if ($isFaculty) {
            $gradeList = array_keys($grades);
            usort($gradeList, fn($a,$b) => (int)$a <=> (int)$b);
            $bucketList = array_keys($buckets);
            $faculty[] = [
                'id' => $uid,
                'name' => (string)$u['full_name'],
                'photo' => (string)($u['photo'] ?? ''),
                'designation' => (string)($u['designation'] ?? ''),
                'department' => (string)($u['department'] ?? ''),
                'role_label' => ($u['role'] === 'staff') ? 'Teaching Staff' : 'Faculty',
                'grades' => $gradeList,
                'grade_label' => implode(', ', array_map(fn($g) => 'Grade ' . $g, $gradeList)),
                'level_label' => ptr_level_label($bucketList),
                'level_sort' => $bucketList[0] ?? '',
                'grade_sort' => ptr_grade_sort($gradeList),
                'status' => 'not_started',
                'status_label' => 'Not Started',
                'completed' => 0,
                'last_evaluated_at' => null,
            ];
        }
    }

    $requiredFaculty = count($facultyIds);
    $requiredStaff = count($staffIds);
    $requiredMulti = count($multiIds);

    // Principal's own Student Evaluation questions are a single optional
    // leadership target for JHS/SHS students. It is intentionally absent from
    // the Dean/College tracker.
    $principalQuestionCount = (int)(safe_scalar($mysqli,
        "SELECT COUNT(*) FROM user_questions WHERE user_id=? AND target_type='Principal' AND eval_type='general'",
        'i', [$principalId]
    ) ?? 0);
    $requiredPrincipal = $principalQuestionCount > 0 ? 1 : 0;

    // Each target/context pair is one required Student Evaluation. A person
    // can legitimately contribute both Faculty and Multi-Role contexts.
    $requiredTotal = $requiredFaculty + $requiredStaff + $requiredMulti + $requiredPrincipal;

    // ── STUDENT ROSTER: ONLY JHS/SHS, NEVER COLLEGE ───────────────────
    $studentRows = safe_rows($mysqli, "
        SELECT id, full_name, photo, year_level, education_level
        FROM users
        WHERE role='student'
          AND is_active=1
          AND account_status='approved'
        ORDER BY full_name ASC
    ");

    $eligibleStudentIds = [];
    foreach ($studentRows as $s) {
        $raw = trim((string)($s['year_level'] ?? ''));
        $g = ptr_grade($raw);
        if ($g !== null && in_array($g, $scopeGrades, true)) {
            $eligibleStudentIds[(int)$s['id']] = true;
        }
    }

    // If the roster stores education_level but the year_level text is blank,
    // keep the student visible only when the Principal's scope itself is one
    // single division; we never use a College value here.
    if (!empty($studentRows)) {
        foreach ($studentRows as $s) {
            $sid = (int)$s['id'];
            if (isset($eligibleStudentIds[$sid])) continue;
            $edu = strtolower(trim((string)($s['education_level'] ?? '')));
            $allowedEdu = [];
            if (in_array('junior_high', $scopeAcademicLevels, true)) $allowedEdu[] = 'junior_high';
            if (in_array('senior_high', $scopeAcademicLevels, true)) $allowedEdu[] = 'senior_high';
            if (in_array($edu, $allowedEdu, true) && trim((string)($s['year_level'] ?? '')) === '') {
                $eligibleStudentIds[$sid] = true;
            }
        }
    }

    $eligibleStudentRows = [];
    foreach ($studentRows as $s) {
        $sid = (int)$s['id'];
        if (!isset($eligibleStudentIds[$sid])) continue;
        $raw = trim((string)($s['year_level'] ?? ''));
        $g = ptr_grade($raw);
        $level = ptr_student_level_label($raw);
        if ($g === null) {
            $edu = strtolower(trim((string)($s['education_level'] ?? '')));
            if ($edu === 'junior_high') $level = 'Junior High';
            elseif ($edu === 'senior_high') $level = 'Senior High';
        }
        $eligibleStudentRows[] = [
            'id' => $sid,
            'name' => (string)$s['full_name'],
            'photo' => (string)($s['photo'] ?? ''),
            'grade' => $g,
            'year_level' => $g ? ('Grade ' . $g) : 'Basic Education',
            'level' => $level,
        ];
    }

    // Stable level counters are independent of table filters.
    foreach ($eligibleStudentRows as $s) {
        if ($s['level'] === 'Junior High') $jhsStudentCount++;
        if ($s['level'] === 'Senior High') $shsStudentCount++;
    }

    // ── SUBMISSIONS / PROGRESS FOR PRIMARY STUDENT TRACKER ────────────
    $studentContextMap = [];
    if ($hasPeriod && $requiredTotal > 0 && !empty($eligibleStudentRows)) {
        $ids = array_map(fn($s) => (int)$s['id'], $eligibleStudentRows);
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

        $facultyKeySet = $facultyIds;
        $staffKeySet = $staffIds;
        $multiKeySet = $multiIds;

        foreach ($submissionRows as $row) {
            $contextKey = ptr_context_for_submission($row, $principalId, $facultyKeySet, $staffKeySet, $multiKeySet, $requiredPrincipal > 0);
            if ($contextKey === null) continue;

            $sid = (int)$row['evaluator_id'];
            if (!isset($eligibleStudentIds[$sid])) continue;

            $isCompleted = in_array($row['status'], ['submitted','approved'], true);
            $timeValue = $row['submitted_at'] ?: ($row['updated_at'] ?? null);

            if (!isset($studentContextMap[$sid][$contextKey])) {
                $studentContextMap[$sid][$contextKey] = [
                    'state' => $isCompleted ? 'completed' : 'in_progress',
                    'at' => $timeValue,
                ];
            } elseif ($isCompleted && $studentContextMap[$sid][$contextKey]['state'] !== 'completed') {
                $studentContextMap[$sid][$contextKey] = [
                    'state' => 'completed',
                    'at' => $timeValue,
                ];
            }
        }
    }

    foreach ($eligibleStudentRows as $s) {
        $sid = $s['id'];
        $contexts = $studentContextMap[$sid] ?? [];
        $completed = 0;
        $started = false;
        $latest = null;
        foreach ($contexts as $state) {
            if (($state['state'] ?? '') === 'completed') $completed++;
            if (($state['state'] ?? '') === 'in_progress') $started = true;
            if (!empty($state['at']) && (!$latest || strtotime((string)$state['at']) > strtotime((string)$latest))) {
                $latest = $state['at'];
            }
        }

        $completed = min($requiredTotal, $completed);
        $progress = $requiredTotal > 0 ? min(100, (int)round(($completed / $requiredTotal) * 100)) : 0;
        if ($requiredTotal > 0 && $completed >= $requiredTotal) {
            $state = 'completed';
            $label = 'Completed';
        } elseif ($completed > 0 || $started) {
            $state = 'in_progress';
            $label = 'In Progress';
        } else {
            $state = 'not_started';
            $label = 'Not Started';
        }

        $students[] = [
            'id' => $sid,
            'name' => $s['name'],
            'photo' => $s['photo'],
            'year_level' => $s['year_level'],
            'level' => $s['level'],
            'grade' => $s['grade'],
            'required' => $requiredTotal,
            'completed' => $completed,
            'progress' => $progress,
            'status' => $state,
            'status_label' => $label,
            'submitted_at' => $latest,
        ];
    }

    // ── STUDENT FILTERING / SORTING / PAGINATION ──────────────────────
    if ($search !== '') {
        $needle = mb_strtolower($search);
        $students = array_values(array_filter($students, function ($s) use ($needle) {
            return str_contains(mb_strtolower($s['name'] . ' ' . $s['year_level'] . ' ' . $s['level']), $needle);
        }));
    }
    if ($levelFilter !== '') {
        $students = array_values(array_filter($students, fn($s) => $s['level'] === ($levelFilter === 'junior_high' ? 'Junior High' : 'Senior High')));
    }
    if ($gradeFilter !== '') {
        $students = array_values(array_filter($students, fn($s) => $s['grade'] === $gradeFilter));
    }
    if ($status !== '') {
        $students = array_values(array_filter($students, fn($s) => $s['status'] === $status));
    }

    usort($students, function ($a, $b) use ($sort, $dir) {
        if ($sort === 'grade') {
            $av = $a['grade'] === null ? 999 : (int)$a['grade'];
            $bv = $b['grade'] === null ? 999 : (int)$b['grade'];
            $cmp = $av <=> $bv;
        } elseif ($sort === 'status') {
            $rank = ['not_started'=>1,'in_progress'=>2,'completed'=>3];
            $cmp = ($rank[$a['status']] ?? 0) <=> ($rank[$b['status']] ?? 0);
        } else {
            $cmp = strnatcasecmp($a['name'], $b['name']);
        }
        if ($cmp === 0) $cmp = strnatcasecmp($a['name'], $b['name']);
        return $dir === 'desc' ? -$cmp : $cmp;
    });

    $studentsAssigned = count($students);
    $studentsSubmitted = count(array_filter($students, fn($s) => $s['status'] === 'completed'));
    $pendingStudents = count(array_filter($students, fn($s) => $s['status'] !== 'completed'));
    $completionPct = $studentsAssigned > 0 ? (int)round(($studentsSubmitted / $studentsAssigned) * 100) : 0;

    // Primary export.
    if (($_GET['export'] ?? '') === 'csv') {
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="principal_student_tracker_' . date('Ymd_His') . '.csv"');
        $out = fopen('php://output', 'w');
        fputcsv($out, ['Student', 'Level', 'Required', 'Completed', 'Status', 'Progress', 'Last Activity']);
        foreach ($students as $s) {
            fputcsv($out, [
                $s['name'], $s['level'], $s['required'], $s['completed'] . ' / ' . $s['required'],
                $s['status_label'], $s['progress'] . '%', $s['submitted_at'] ?? ''
            ]);
        }
        fclose($out);
        exit;
    }

    $totalPages = max(1, (int)ceil($studentsAssigned / $perPage));
    $page = max(1, min($totalPages, $page));
    $pageStudents = array_slice($students, ($page - 1) * $perPage, $perPage);

    // ── OPTIONAL PRINCIPAL-TO-FACULTY STATUS ─────────────────────────
    $facultyQuestionCount = (int)(safe_scalar($mysqli,
        "SELECT COUNT(*) FROM evaluation_questions
         WHERE target_type='Faculty' AND eval_type='general' AND evaluator_role='shared' AND is_active=1"
    ) ?? 0);

    if ($hasPeriod && !empty($faculty)) {
        $fids = array_map(fn($f) => (int)$f['id'], $faculty);
        $ph = implode(',', array_fill(0, count($fids), '?'));
        $facultyStatusRows = safe_rows($mysqli, "
            SELECT target_user_id,
                   MAX(CASE WHEN status IN ('submitted','approved') THEN submitted_at END) AS completed_at,
                   MAX(CASE WHEN status='in_progress' THEN updated_at END) AS progress_at
            FROM evaluation_tracker
            WHERE evaluator_id=?
              AND eval_type='school_head'
              AND period_id=?
              AND target_user_id IN ($ph)
            GROUP BY target_user_id
        ", 'ii' . str_repeat('i', count($fids)), array_merge([$principalId, $period_id_int], $fids));

        foreach ($facultyStatusRows as $row) {
            $fid = (int)$row['target_user_id'];
            foreach ($faculty as &$f) {
                if ((int)$f['id'] !== $fid) continue;
                if (!empty($row['completed_at'])) {
                    $f['status'] = 'completed';
                    $f['status_label'] = 'Completed';
                    $f['completed'] = 1;
                    $f['last_evaluated_at'] = $row['completed_at'];
                } elseif (!empty($row['progress_at'])) {
                    $f['status'] = 'in_progress';
                    $f['status_label'] = 'In Progress';
                    $f['last_evaluated_at'] = $row['progress_at'];
                }
                break;
            }
            unset($f);
        }
    }

    $facultyAssigned = count($faculty);
    $facultyEvaluated = count(array_filter($faculty, fn($f) => $f['status'] === 'completed'));
    $pendingFaculty = count(array_filter($faculty, fn($f) => $f['status'] !== 'completed'));
    $facultyCompletionPct = $facultyAssigned > 0 ? (int)round(($facultyEvaluated / $facultyAssigned) * 100) : 0;

    // ── LIVE JSON ENDPOINT — PRIMARY STUDENT TRACKER ────────────────
    if (($_GET['ajax'] ?? '') === '1') {
        header('Content-Type: application/json; charset=utf-8');
        header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
        echo json_encode([
            'ok' => true,
            'hasPeriod' => $hasPeriod,
            'evalOpen' => $evalOpen,
            'requiredTotal' => $requiredTotal,
            'requiredFaculty' => $requiredFaculty,
            'requiredStaff' => $requiredStaff,
            'requiredMulti' => $requiredMulti,
            'requiredPrincipal' => $requiredPrincipal,
            'studentsAssigned' => $studentsAssigned,
            'studentsSubmitted' => $studentsSubmitted,
            'pendingStudents' => $pendingStudents,
            'completionPct' => $completionPct,
            'jhsStudentCount' => $jhsStudentCount,
            'shsStudentCount' => $shsStudentCount,
            'totalPages' => $totalPages,
            'page' => $page,
            'students' => $pageStudents,
            'facultyAssigned' => $facultyAssigned,
            'facultyEvaluated' => $facultyEvaluated,
            'pendingFaculty' => $pendingFaculty,
            'facultyCompletionPct' => $facultyCompletionPct,
            'checkedAt' => date('c'),
        ], JSON_UNESCAPED_SLASHES);
        exit;
    }
}

function principal_tracker_qs(array $overrides = []): string {
    $params = array_merge($_GET, $overrides);
    $params['page'] = $overrides['page'] ?? 1;
    unset($params['ajax']);
    return htmlspecialchars('?' . http_build_query($params));
}
function principal_tracker_sort_url(string $col, string $curSort, string $curDir): string {
    $newDir = ($curSort === $col && $curDir === 'asc') ? 'desc' : 'asc';
    return principal_tracker_qs(['sort' => $col, 'dir' => $newDir]);
}
function principal_tracker_sort_icon(string $col, string $curSort, string $curDir): string {
    if ($curSort !== $col) return 'fa-sort';
    return $curDir === 'asc' ? 'fa-sort-up' : 'fa-sort-down';
}
function principal_tracker_initials(string $name): string {
    $p = preg_split('/\s+/', trim($name));
    return strtoupper(($p[0][0] ?? '') . (count($p) > 1 ? ($p[count($p)-1][0] ?? '') : ''));
}

html_head_open('PBI — Principal Evaluation Tracker');
?>
<style id="principal-student-tracker">
:root{
  --trk-page:#F5F8FC;--trk-card:#FFF;--trk-line:#DCE7F1;--trk-line-strong:#AABCCD;
  --trk-text:#12263A;--trk-muted:#6D8194;--trk-teal:#19B39D;--trk-teal-soft:#E9F8F5;--trk-teal-border:#74CFC3;
  --trk-green:#0F9F6E;--trk-green-soft:#EAF8F2;--trk-amber:#B7791F;--trk-amber-soft:#FFF8E8;
  --trk-blue:#2563EB;--trk-shadow:0 4px 16px rgba(28,64,92,.07);
}
html,body{background:var(--trk-page)!important;color:var(--trk-text)!important}
.main{flex:1;min-width:0;padding:34px 40px 42px;background:var(--trk-page)!important}
.page-header{display:flex;justify-content:space-between;align-items:flex-start;gap:18px;flex-wrap:wrap;margin-bottom:20px}
.page-title{font-size:28px;font-weight:700;letter-spacing:-.02em;color:var(--trk-text)!important}.page-sub{font-size:13px;color:var(--trk-muted)!important;margin-top:5px;line-height:1.5}
.period-badge{display:inline-flex;align-items:center;gap:8px;padding:8px 13px;border-radius:999px;border:1px solid var(--trk-line);background:#fff;color:#587086;font-size:12px;font-weight:700;box-shadow:0 1px 2px rgba(15,23,42,.03)}.period-badge i{color:var(--trk-teal)!important}
.period-badge.closed{background:#FFF4F5;border-color:#F2C7CC;color:#A94250}.period-badge.closed i{color:#D6455D!important}.period-badge.gray{color:var(--trk-muted)}
.structure-note{display:flex;align-items:flex-start;gap:12px;padding:16px 18px;background:#fff;border:1px solid var(--trk-line);border-radius:12px;margin-bottom:18px;box-shadow:var(--trk-shadow)}.structure-note i{color:var(--trk-blue);font-size:17px;margin-top:2px}.structure-note p{font-size:13px;color:#445B70;line-height:1.6}.structure-note p b{color:var(--trk-text)}
.stat-row{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:14px;margin-bottom:18px}.stat{background:#fff;border:1px solid var(--trk-line);border-radius:12px;padding:15px 16px;box-shadow:0 2px 8px rgba(15,23,42,.045)}.stat-icon{font-size:16px;color:var(--trk-teal);margin-bottom:8px}.stat-num{font-size:23px;font-weight:800;color:#10263A}.stat-label{font-size:11.5px;font-weight:800;color:#445B70;margin-top:2px}.stat-caption{font-size:10px;color:#8193A3;margin-top:3px}
.tracker-card{background:#fff;border:1px solid var(--trk-line);border-radius:12px;box-shadow:var(--trk-shadow);overflow:visible;margin-bottom:18px}.tracker-toolbar{display:flex;align-items:center;justify-content:space-between;gap:16px;padding:18px 18px 14px;border-bottom:1px solid #E7EEF4;flex-wrap:wrap}.tracker-heading{display:flex;align-items:center;gap:10px;min-width:0}.tracker-heading h2{font-size:18px;font-weight:700;color:var(--trk-text)!important}.tracker-heading .count{font-size:12px;color:var(--trk-muted)!important}.toolbar-actions{display:flex;align-items:center;gap:8px;position:relative}.filter-wrap{position:relative}.filter-toggle,.export-btn{height:36px;padding:0 13px;display:inline-flex;align-items:center;gap:8px;border-radius:8px;font-size:12px;font-weight:700;font-family:inherit;text-decoration:none;cursor:pointer}.filter-toggle{background:#fff;border:1px solid #C9D7E2;color:#334C60}.filter-toggle:hover{background:#F8FAFC;border-color:#AFC1D0}.filter-toggle.active{border-color:var(--trk-teal-border);color:#128D7E;background:var(--trk-teal-soft)}.filter-count{min-width:18px;height:18px;padding:0 5px;border-radius:999px;display:inline-flex;align-items:center;justify-content:center;background:var(--trk-teal);color:#fff;font-size:10px;line-height:1}.export-btn{background:#F4FBFA;border:1px solid #BEE6DF;color:#138D7D}.export-btn:hover{background:#E8F7F4}
.filter-menu{position:absolute;right:0;top:44px;width:320px;background:#fff;border:1px solid var(--trk-line);border-radius:12px;box-shadow:0 12px 30px rgba(30,70,100,.12);padding:14px;z-index:30;display:none}.filter-menu.open{display:block}.filter-menu-title{font-size:12px;font-weight:800;color:var(--trk-text);margin-bottom:10px}.filter-field{display:flex;flex-direction:column;gap:6px;margin-bottom:10px}.filter-field label{font-size:10px;font-weight:800;letter-spacing:.08em;text-transform:uppercase;color:#71869A}.filter-field input,.filter-field select{width:100%;height:36px;background:#fff;border:1px solid #C8D6E1;border-radius:8px;color:var(--trk-text);font:12px 'DM Sans',sans-serif;padding:0 11px;outline:none}.filter-field input:focus,.filter-field select:focus{border-color:var(--trk-teal-border);box-shadow:0 0 0 3px rgba(25,179,157,.10)}.filter-menu-actions{display:flex;justify-content:flex-end;gap:8px;padding-top:3px}.filter-apply,.filter-clear{height:34px;padding:0 12px;border-radius:8px;font:700 12px 'DM Sans',sans-serif;cursor:pointer;text-decoration:none;display:inline-flex;align-items:center;gap:7px}.filter-apply{border:1px solid var(--trk-teal);background:var(--trk-teal);color:#fff}.filter-clear{border:1px solid #C8D6E1;background:#fff;color:#61768A}
.table-wrap{overflow:auto}.table-scroll{border-top:1px solid var(--trk-line)}table.data{width:100%;border-collapse:separate;border-spacing:0;font-size:13px;min-width:860px}table.data th{height:58px;text-align:left;color:#657A8E;font-size:10.5px;font-weight:800;text-transform:uppercase;letter-spacing:.05em;padding:0 15px;border-bottom:1px solid var(--trk-line-strong);background:#FCFDFE;white-space:nowrap}table.data th:first-child{padding-left:16px;width:34%}table.data th:nth-child(2){width:15%}table.data th:nth-child(3){width:12%}table.data th:nth-child(4){width:12%}table.data th:nth-child(5){width:14%}table.data th:nth-child(6){width:25%}table.data th a{color:inherit;text-decoration:none}table.data td{height:90px;padding:0 15px;border-bottom:1px solid #E4EDF4;vertical-align:middle;background:#fff;color:var(--trk-text)}table.data tbody tr:hover td{background:#FBFEFD}table.data tbody tr:last-child td{border-bottom:none}
.stu-cell{display:flex;align-items:center;gap:12px;min-width:0}.stu-avatar{width:48px;height:48px;border-radius:50%;flex:0 0 48px;display:flex;align-items:center;justify-content:center;background:var(--trk-teal-soft);border:1px solid #B6E6DE;color:#159C8A;font-size:17px;overflow:hidden}.stu-avatar img{width:100%;height:100%;object-fit:cover}.stu-copy{min-width:0}.stu-name{font-size:14px;font-weight:700;color:#10263A;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}.stu-sub{font-size:11px;color:#8092A3;margin-top:3px}.level-pill{display:inline-flex;align-items:center;justify-content:center;min-width:92px;height:42px;padding:0 13px;border-radius:12px;border:1px solid var(--trk-teal-border);background:var(--trk-teal-soft);color:#128D7D;font-size:11px;font-weight:800}.req-number,.completed-number{font-size:14px;color:#344D60;font-weight:500}.status-pill{display:inline-flex;align-items:center;justify-content:center;padding:7px 12px;border-radius:999px;font-size:11px;font-weight:800;white-space:nowrap}.status-pill.not_started{background:#F3F7FA;color:#6E8396}.status-pill.in_progress{background:var(--trk-amber-soft);color:var(--trk-amber)}.status-pill.completed{background:var(--trk-green-soft);color:var(--trk-green)}.progress-cell{display:flex;align-items:center;gap:10px;min-width:0}.progress-pct{width:36px;flex:0 0 36px;font-size:12px;font-weight:600;color:#607589}.progress-main{min-width:130px;flex:1}.progress-track{width:100%;height:7px;border-radius:999px;background:#DDE7EF;overflow:hidden}.progress-fill{height:100%;border-radius:inherit;background:#15A57D;transition:width .2s}.progress-last{font-size:10.5px;color:#7C8FA0;margin-top:6px;white-space:nowrap}.progress-chevron{width:14px;flex:0 0 14px;color:#486176;font-size:17px;text-align:right}.empty-note{color:#8295A6;font-size:13px;padding:26px 16px;text-align:center}.table-footer{display:flex;justify-content:space-between;align-items:center;padding:13px 16px 14px;font-size:12px;color:#7890A3;gap:12px;flex-wrap:wrap}.pagination{display:flex;align-items:center;gap:5px}.page-btn{min-width:30px;height:30px;padding:0 8px;display:flex;align-items:center;justify-content:center;border-radius:7px;background:#fff;border:1px solid #C9D7E2;color:#6D8194;text-decoration:none;font-size:12px;font-weight:700}.page-btn.active{background:#E8F7F4;color:#118E7E;border-color:#8BD6CB}.page-btn.disabled{opacity:.35;pointer-events:none}.page-ellipsis{color:#91A2B0;padding:0 3px}
.info-banner{display:flex;align-items:flex-start;gap:12px;background:#fff;border:1px solid var(--trk-line);border-radius:12px;padding:15px 17px;margin-top:16px;box-shadow:var(--trk-shadow)}.info-banner i{color:var(--trk-blue);font-size:15px;margin-top:2px}.info-banner b{display:block;color:var(--trk-text);font-size:12.5px;margin-bottom:2px}.info-banner p{font-size:12px;color:#74899B}.info-banner.closed{background:#FFF7F7;border-color:#F2D1D5}.info-banner.closed i{color:#D6455D}.live-tracker{display:inline-flex;align-items:center;gap:6px;padding:4px 8px;border-radius:999px;background:#ECFDF5;border:1px solid #A7F3D0;color:#0F9F6E;font-size:10.5px;font-weight:800}.live-tracker.offline{background:#F8FAFC;border-color:#DCE7F1;color:#8092A2}.live-dot{width:6px;height:6px;border-radius:50%;background:#0F9F6E;display:inline-block;animation:livePulse 2s ease-in-out infinite}.live-tracker.offline .live-dot{background:#9AA9B5;animation:none}@keyframes livePulse{0%,100%{opacity:1;transform:scale(1)}50%{opacity:.4;transform:scale(.8)}}
.optional-wrap{margin-top:22px}.optional-card{background:#fff;border:1.5px solid #E8C979;border-radius:14px;box-shadow:0 5px 20px rgba(161,98,7,.10);overflow:hidden;position:relative}.optional-card::before{content:'';display:block;height:4px;background:#D6A63A}.optional-card summary{list-style:none;cursor:pointer;min-height:82px;box-sizing:border-box;padding:19px 22px;display:flex;align-items:center;justify-content:space-between;gap:20px;background:linear-gradient(180deg,#FFFCF5 0%,#FFF9EC 100%)}.optional-card summary::-webkit-details-marker{display:none}.optional-card summary:hover{background:#FFF7E3}.optional-title{display:flex;align-items:center;gap:13px;min-width:0}.optional-title i{width:38px;height:38px;min-width:38px;border-radius:10px;display:flex;align-items:center;justify-content:center;background:#FFF0C8;border:1px solid #E7C873;color:#A16207;font-size:16px}.optional-title strong{font-size:17px;font-weight:800;color:var(--trk-text);line-height:1.25}.optional-title span{display:block;font-size:12px;color:#7A6A4A;margin-top:3px}.optional-meta{display:flex;gap:9px;align-items:center;justify-content:flex-end;flex-wrap:wrap}.optional-pill{padding:7px 11px;border-radius:999px;font-size:10.5px;font-weight:800;background:#FFFDF7;color:#A16207;border:1px solid #E5C36F;white-space:nowrap}.optional-body{padding:0 22px 22px;border-top:1px solid #F0DFB2;background:#FFFEFB}.optional-note{font-size:12px;color:#6E7780;line-height:1.65;padding:14px 0}.optional-table{width:100%;border-collapse:separate;border-spacing:0;min-width:760px;font-size:12.5px}.optional-table th{padding:11px 12px;color:#657A8E;background:#FCFDFE;text-transform:uppercase;font-size:10px;border-bottom:1px solid var(--trk-line-strong);text-align:left}.optional-table td{padding:12px;border-bottom:1px solid #E8EEF3;color:#344D60;background:#fff}.optional-table tr:last-child td{border-bottom:none}.optional-person{display:flex;align-items:center;gap:10px}.optional-avatar{width:38px;height:38px;border-radius:50%;display:flex;align-items:center;justify-content:center;background:#FFF8E8;border:1px solid #EBCB8A;color:#A16207;font-size:12px;font-weight:800;overflow:hidden}.optional-avatar img{width:100%;height:100%;object-fit:cover}.optional-status{font-size:10.5px;font-weight:800;padding:6px 10px;border-radius:999px}.optional-status.not_started{background:#F3F7FA;color:#6E8396}.optional-status.in_progress{background:#FFF8E8;color:#A16207}.optional-status.completed{background:#EAF8F2;color:#0F9F6E}
@media(max-width:1100px){.stat-row{grid-template-columns:repeat(2,minmax(0,1fr))}.main{padding:26px 22px 36px}.period-badge{width:100%;justify-content:flex-start}}
@media(max-width:768px){body{flex-direction:column}.main{padding:20px 14px 30px}.page-title{font-size:24px}.tracker-toolbar{padding:15px 14px}.toolbar-actions{width:100%;justify-content:stretch}.filter-wrap,.filter-toggle,.export-btn{flex:1}.filter-toggle,.export-btn{justify-content:center}.filter-menu{width:min(310px,calc(100vw - 28px));right:0}.stat-row{grid-template-columns:1fr}}
</style>
</head>
<body>
<?php
render_principal_sidebar('tracker', $me, $scopeLabel, $photo_src);
?>
<main class="main">
    <div class="page-header">
        <div>
            <div class="page-title">Evaluation Tracker</div>
            <div class="page-sub">Monitor JHS and SHS student participation in Student Evaluation. Your own Faculty evaluations are optional and tracked separately below.</div>
        </div>
        <?php render_period_badge($settings); ?>
    </div>

    <?php if (!$structureActive): ?>
        <?php render_scope_status($settings, 'tracker'); ?>
    <?php else: ?>

    <div class="stat-row">
        <div class="stat"><div class="stat-icon"><i class="fa-solid fa-users"></i></div><div class="stat-num" id="statStudentsAssigned"><?= number_format($studentsAssigned) ?></div><div class="stat-label">Students in Scope</div><div class="stat-caption">JHS / SHS only</div></div>
        <div class="stat"><div class="stat-icon"><i class="fa-solid fa-circle-check"></i></div><div class="stat-num" id="statStudentsCompleted"><?= number_format($studentsSubmitted) ?></div><div class="stat-label">Completed</div><div class="stat-caption" id="statCompletionCaption"><?= $completionPct ?>% of students</div></div>
        <div class="stat"><div class="stat-icon"><i class="fa-solid fa-hourglass-half"></i></div><div class="stat-num" id="statStudentsPending"><?= number_format($pendingStudents) ?></div><div class="stat-label">Pending</div><div class="stat-caption">Not fully submitted</div></div>
        <div class="stat"><div class="stat-icon"><i class="fa-solid fa-chart-simple"></i></div><div class="stat-num" id="statStudentsCompletion"><?= $completionPct ?>%</div><div class="stat-label">Student Completion</div><div class="stat-caption"><?= number_format($requiredTotal) ?> required evaluation targets per student</div></div>
    </div>

    <div class="tracker-card">
        <div class="tracker-toolbar">
            <div class="tracker-heading">
                <h2>Students Evaluation Tracker</h2>
                <span class="count" id="trackerStudentCount"><?= number_format($studentsAssigned) ?> student<?= $studentsAssigned === 1 ? '' : 's' ?></span>
                <span class="live-tracker" id="trackerLiveStatus"><span class="live-dot"></span> Live</span>
            </div>
            <div class="toolbar-actions">
                <div class="filter-wrap">
                    <?php $activeFilterCount = ($search !== '' ? 1 : 0) + ($levelFilter !== '' ? 1 : 0) + ($gradeFilter !== '' ? 1 : 0) + ($status !== '' ? 1 : 0); ?>
                    <button type="button" class="filter-toggle<?= $activeFilterCount ? ' active' : '' ?>" id="filterToggle" aria-expanded="false" aria-controls="trackerFilterMenu">
                        <i class="fa-solid fa-filter"></i> Filter<?php if ($activeFilterCount): ?> <span class="filter-count"><?= $activeFilterCount ?></span><?php endif; ?>
                    </button>
                    <form class="filter-menu" id="trackerFilterMenu" method="GET" action="principal_evaluation_tracker.php">
                        <div class="filter-menu-title">Filter Students</div>
                        <div class="filter-field"><label for="filterSearch">Search Student</label><input id="filterSearch" type="text" name="search" placeholder="Student name..." value="<?= htmlspecialchars($search) ?>"></div>
                        <?php if ($myLevel === 'both'): ?>
                        <div class="filter-field"><label for="filterLevel">Academic Level</label><select id="filterLevel" name="level"><option value="">All Levels</option><option value="junior_high" <?= $levelFilter === 'junior_high' ? 'selected' : '' ?>>Junior High</option><option value="senior_high" <?= $levelFilter === 'senior_high' ? 'selected' : '' ?>>Senior High</option></select></div>
                        <?php endif; ?>
                        <div class="filter-field"><label for="filterGrade">Grade Level</label><select id="filterGrade" name="grade"><option value="">All Grades</option><?php foreach ($scopeGrades as $g): ?><option value="<?= htmlspecialchars($g) ?>" <?= $gradeFilter === $g ? 'selected' : '' ?>>Grade <?= htmlspecialchars($g) ?></option><?php endforeach; ?></select></div>
                        <div class="filter-field"><label for="filterStatus">Evaluation Status</label><select id="filterStatus" name="status"><option value="">All Statuses</option><option value="not_started" <?= $status === 'not_started' ? 'selected' : '' ?>>Not Started</option><option value="in_progress" <?= $status === 'in_progress' ? 'selected' : '' ?>>In Progress</option><option value="completed" <?= $status === 'completed' ? 'selected' : '' ?>>Completed</option></select></div>
                        <input type="hidden" name="page" value="1">
                        <div class="filter-menu-actions"><a class="filter-clear" href="principal_evaluation_tracker.php"><i class="fa-solid fa-rotate-left"></i> Clear</a><button type="submit" class="filter-apply"><i class="fa-solid fa-check"></i> Apply Filters</button></div>
                    </form>
                </div>
                <a class="export-btn" href="<?= principal_tracker_qs(['export' => 'csv']) ?>"><i class="fa-solid fa-download"></i> Export</a>
            </div>
        </div>

        <div id="trackerTableState">
        <?php if (!$hasPeriod): ?>
            <div class="table-empty"><p class="empty-note">No active evaluation period right now.</p></div>
        <?php else: ?>
            <div class="table-wrap"><div class="table-scroll"><table class="data"><thead><tr>
                <th><a href="<?= principal_tracker_sort_url('name', $sort, $dir) ?>">Student <i class="fa-solid <?= principal_tracker_sort_icon('name', $sort, $dir) ?>"></i></a></th>
                <th>Level</th>
                <th><a href="<?= principal_tracker_sort_url('grade', $sort, $dir) ?>">Grade <i class="fa-solid <?= principal_tracker_sort_icon('grade', $sort, $dir) ?>"></i></a></th>
                <th>Required</th><th>Completed</th>
                <th><a href="<?= principal_tracker_sort_url('status', $sort, $dir) ?>">Status <i class="fa-solid <?= principal_tracker_sort_icon('status', $sort, $dir) ?>"></i></a></th>
                <th>Progress</th>
            </tr></thead><tbody id="trackerTableBody">
            <?php if (empty($pageStudents)): ?><tr><td colspan="7"><p class="empty-note">No students match the current filters.</p></td></tr>
            <?php else: foreach ($pageStudents as $s): ?>
                <tr>
                    <td><div class="stu-cell"><span class="stu-avatar"><?php if ($s['photo'] !== ''): ?><img src="../image/<?= htmlspecialchars($s['photo']) ?>" alt=""><?php else: ?><i class="fa-solid fa-user"></i><?php endif; ?></span><div class="stu-copy"><div class="stu-name"><?= htmlspecialchars($s['name']) ?></div><div class="stu-sub">Student Evaluation participant</div></div></div></td>
                    <td><span class="level-pill"><?= htmlspecialchars($s['level']) ?></span></td>
                    <td><?= htmlspecialchars($s['year_level']) ?></td>
                    <td><span class="req-number"><?= number_format($s['required']) ?></span></td>
                    <td><span class="completed-number"><?= number_format($s['completed']) ?> / <?= number_format($s['required']) ?></span></td>
                    <td><span class="status-pill <?= htmlspecialchars($s['status']) ?>"><?= htmlspecialchars($s['status_label']) ?></span></td>
                    <td><div class="progress-cell"><span class="progress-pct"><?= (int)$s['progress'] ?>%</span><div class="progress-main"><div class="progress-track"><div class="progress-fill" style="width:<?= (int)$s['progress'] ?>%;"></div></div><?php if (!empty($s['submitted_at'])): ?><div class="progress-last">Last: <?= htmlspecialchars(date('M j, Y g:i A', strtotime($s['submitted_at']))) ?></div><?php endif; ?></div><span class="progress-chevron">›</span></div></td>
                </tr>
            <?php endforeach; endif; ?>
            </tbody></table></div></div>
            <div class="table-footer"><div id="trackerShowingText"><?php if ($studentsAssigned > 0): ?>Showing <?= (($page - 1) * $perPage) + 1 ?>–<?= min($studentsAssigned, $page * $perPage) ?> of <?= $studentsAssigned ?> students<?php else: ?>No students to display<?php endif; ?></div><div class="pagination" id="trackerPagination">
            <?php if ($totalPages > 1): $shown=[]; for($i=1;$i<=$totalPages;$i++){ if($i===1||$i===$totalPages||abs($i-$page)<=2)$shown[]=$i; } $prev=null; ?>
                <a class="page-btn <?= $page<=1?'disabled':'' ?>" href="<?= principal_tracker_qs(['page'=>max(1,$page-1)]) ?>"><i class="fa-solid fa-chevron-left"></i></a>
                <?php foreach($shown as $i): ?><?php if($prev!==null&&$i-$prev>1): ?><span class="page-ellipsis">…</span><?php endif; ?><a class="page-btn <?= $i===$page?'active':'' ?>" href="<?= principal_tracker_qs(['page'=>$i]) ?>"><?= $i ?></a><?php $prev=$i; endforeach; ?>
                <a class="page-btn <?= $page>=$totalPages?'disabled':'' ?>" href="<?= principal_tracker_qs(['page'=>min($totalPages,$page+1)]) ?>"><i class="fa-solid fa-chevron-right"></i></a>
            <?php endif; ?></div></div>
        <?php endif; ?>
        </div>
    </div>

    <?php if ($hasPeriod): ?>
    <div class="info-banner <?= $evalOpen ? '' : 'closed' ?>">
        <i class="fa-solid fa-circle-info"></i>
        <div>
            <b>Student Evaluation tracking is currently <?= $evalOpen ? 'open' : 'closed' ?>.</b>
            <p><?= $evalOpen
                ? 'This tracker measures whether JHS/SHS students have completed the Student Evaluation contexts required for your Basic Education scope. College students are excluded, and your own Faculty evaluations do not affect these totals.'
                : 'No new student submissions will be recorded until the evaluation window reopens.' ?></p>
        </div>
    </div>
    <?php endif; ?>

    <div class="optional-wrap">
        <details class="optional-card">
            <summary>
                <div class="optional-title"><i class="fa-solid fa-user-pen"></i><strong>Optional: Principal Faculty Evaluations</strong><span>JHS/SHS Faculty and Teaching Staff only</span></div>
                <div class="optional-meta"><span class="optional-pill"><?= number_format($facultyAssigned) ?> Faculty</span><span class="optional-pill"><?= number_format($facultyEvaluated) ?> Completed</span><span class="optional-pill"><?= number_format($pendingFaculty) ?> Pending</span></div>
            </summary>
            <div class="optional-body">
                <div class="optional-note">These are the Principal's own school-head evaluations of Faculty/Teaching Staff. They are optional and are intentionally separated from the primary Student Evaluation participation tracker. College-only personnel are not included.</div>
                <?php if (empty($faculty)): ?>
                    <div class="empty-note">No JHS/SHS Faculty or Teaching Staff are currently within your evaluation scope.</div>
                <?php else: ?>
                <div class="table-wrap"><table class="optional-table"><thead><tr><th>Faculty / Teaching Staff</th><th>Teaching Level</th><th>Grade</th><th>Status</th><th>Last Evaluated</th></tr></thead><tbody>
                <?php foreach($faculty as $f): ?>
                    <tr><td><div class="optional-person"><span class="optional-avatar"><?php if($f['photo']!==''): ?><img src="../image/<?= htmlspecialchars($f['photo']) ?>" alt=""><?php else: ?><?= htmlspecialchars(principal_tracker_initials($f['name'])) ?><?php endif; ?></span><div><strong><?= htmlspecialchars($f['name']) ?></strong><div style="font-size:10.5px;color:#8092A3;margin-top:2px"><?= htmlspecialchars($f['designation'] !== '' ? $f['designation'] : $f['role_label']) ?></div></div></div></td><td><?= htmlspecialchars($f['level_label']) ?></td><td><?= htmlspecialchars($f['grade_label']) ?></td><td><span class="optional-status <?= htmlspecialchars($f['status']) ?>"><?= htmlspecialchars($f['status_label']) ?></span></td><td><?= !empty($f['last_evaluated_at']) ? htmlspecialchars(date('M j, Y g:i A', strtotime($f['last_evaluated_at']))) : '—' ?></td></tr>
                <?php endforeach; ?>
                </tbody></table></div>
                <?php endif; ?>
            </div>
        </details>
    </div>

    <?php endif; ?>
</main>
<script>
(function(){
    const toggle=document.getElementById('filterToggle');
    const menu=document.getElementById('trackerFilterMenu');
    if(toggle&&menu){
        toggle.addEventListener('click',e=>{e.stopPropagation();const open=menu.classList.toggle('open');toggle.setAttribute('aria-expanded',open?'true':'false');});
        menu.addEventListener('click',e=>e.stopPropagation());
        document.addEventListener('click',()=>{if(menu.classList.contains('open')){menu.classList.remove('open');toggle.setAttribute('aria-expanded','false');}});
    }

    const live=document.getElementById('trackerLiveStatus');
    const body=document.getElementById('trackerTableBody');
    const showing=document.getElementById('trackerShowingText');
    const pagination=document.getElementById('trackerPagination');
    if(!live||!body)return;

    let busy=false,lastSig='';
    const esc=v=>String(v??'').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;').replace(/'/g,'&#039;');
    const initials=n=>{const p=String(n||'').trim().split(/\s+/);return ((p[0]||'')[0]||'').toUpperCase()+(p.length>1?((p[p.length-1]||'')[0]||'').toUpperCase():'');};
    const fmtDate=v=>{const d=new Date(v);if(Number.isNaN(d.getTime()))return v;return d.toLocaleString(undefined,{month:'short',day:'numeric',year:'numeric',hour:'numeric',minute:'2-digit'});};
    function setLive(text,off){live.classList.toggle('offline',!!off);live.innerHTML='<span class="live-dot"></span> '+esc(text);}
    function row(s){
        const avatar=s.photo?'<img src="../image/'+esc(s.photo)+'" alt="">':'<i class="fa-solid fa-user"></i>';
        const last=s.submitted_at?'<div class="progress-last">Last: '+esc(fmtDate(s.submitted_at))+'</div>':'';
        return '<tr><td><div class="stu-cell"><span class="stu-avatar">'+avatar+'</span><div class="stu-copy"><div class="stu-name">'+esc(s.name)+'</div><div class="stu-sub">Student Evaluation participant</div></div></div></td><td><span class="level-pill">'+esc(s.level)+'</span></td><td>'+esc(s.year_level)+'</td><td><span class="req-number">'+Number(s.required||0).toLocaleString()+'</span></td><td><span class="completed-number">'+Number(s.completed||0)+' / '+Number(s.required||0)+'</span></td><td><span class="status-pill '+esc(s.status)+'">'+esc(s.status_label)+'</span></td><td><div class="progress-cell"><span class="progress-pct">'+Number(s.progress||0)+'%</span><div class="progress-main"><div class="progress-track"><div class="progress-fill" style="width:'+Number(s.progress||0)+'%"></div></div>'+last+'</div><span class="progress-chevron">›</span></div></td></tr>';
    }
    function pageUrl(page){const u=new URL(window.location.href);u.searchParams.set('page',String(page));u.searchParams.delete('ajax');u.searchParams.delete('export');return u.toString();}
    function renderPagination(page,total){if(!pagination)return;if(total<=1){pagination.innerHTML='';return;}const shown=[];for(let i=1;i<=total;i++)if(i===1||i===total||Math.abs(i-page)<=2)shown.push(i);let html='<a class="page-btn '+(page<=1?'disabled':'')+'" href="'+esc(pageUrl(Math.max(1,page-1)))+'"><i class="fa-solid fa-chevron-left"></i></a>';let prev=null;shown.forEach(i=>{if(prev!==null&&i-prev>1)html+='<span class="page-ellipsis">…</span>';html+='<a class="page-btn '+(i===page?'active':'')+'" href="'+esc(pageUrl(i))+'">'+i+'</a>';prev=i;});html+='<a class="page-btn '+(page>=total?'disabled':'')+'" href="'+esc(pageUrl(Math.min(total,page+1)))+'"><i class="fa-solid fa-chevron-right"></i></a>';pagination.innerHTML=html;}
    function signature(d){return JSON.stringify([d.requiredTotal,d.studentsAssigned,d.studentsSubmitted,d.pendingStudents,d.completionPct,d.totalPages,d.page,(d.students||[]).map(s=>[s.id,s.completed,s.required,s.progress,s.status,s.submitted_at])]);}
    async function refresh(){if(busy)return;busy=true;try{const u=new URL(window.location.href);u.searchParams.set('ajax','1');u.searchParams.set('_ts',Date.now().toString());u.searchParams.delete('export');const res=await fetch(u.toString(),{credentials:'same-origin',cache:'no-store',headers:{'Accept':'application/json'}});if(!res.ok)throw new Error('Tracker update failed');const d=await res.json();if(!d.ok)throw new Error('Tracker update failed');const sig=signature(d);setLive('Live');if(sig===lastSig){busy=false;return;}lastSig=sig;
        const ids=[['statStudentsAssigned',d.studentsAssigned],['statStudentsCompleted',d.studentsSubmitted],['statStudentsPending',d.pendingStudents]];ids.forEach(([id,val])=>{const el=document.getElementById(id);if(el)el.textContent=Number(val||0).toLocaleString();});
        const pct=document.getElementById('statStudentsCompletion');if(pct)pct.textContent=Number(d.completionPct||0)+'%';const cap=document.getElementById('statCompletionCaption');if(cap)cap.textContent=Number(d.completionPct||0)+'% of students';const count=document.getElementById('trackerStudentCount');if(count)count.textContent=Number(d.studentsAssigned||0).toLocaleString()+' student'+(Number(d.studentsAssigned||0)===1?'':'s');
        body.innerHTML=(d.students||[]).length?d.students.map(row).join(''):'<tr><td colspan="7"><p class="empty-note">No students match the current filters.</p></td></tr>';
        if(showing){if(Number(d.studentsAssigned||0)>0){const first=((Number(d.page||1)-1)*<?= (int)$perPage ?>)+1;const last=Math.min(Number(d.studentsAssigned||0),Number(d.page||1)*<?= (int)$perPage ?>);showing.textContent='Showing '+first+'–'+last+' of '+Number(d.studentsAssigned||0).toLocaleString()+' students';}else showing.textContent='No students to display';}renderPagination(Number(d.page||1),Number(d.totalPages||1));
    }catch(e){setLive('Offline',true);}finally{busy=false;}}
    refresh();setInterval(refresh,5000);
})();
</script>
</body>
</html>
<?php if($mysqli->ping())$mysqli->close(); ?>
