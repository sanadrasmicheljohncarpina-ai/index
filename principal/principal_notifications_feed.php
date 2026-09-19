<?php
/* =========================================================================
   principal_notifications_feed.php
   -------------------------------------------------------------------------
   Single source of truth for the Principal's notification list.

   Used by:
     principal_dashboard.php  — initial server-side render of the bell
     principal_notifications.php — JSON endpoint polled by the bell for
                                   real-time updates

   Both paths MUST call the same function so the badge count can never drift
   from what the dropdown shows. Do not duplicate notification rules in the
   dashboard again.
   ========================================================================= */

// ── SAFE QUERY HELPERS (never fatal-error on a schema mismatch) ─────────
// Guarded with function_exists so this file can be included alongside a page
// that already declares them.
if (!function_exists('safe_scalar')) {
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
}
if (!function_exists('esc_list')) {
    function esc_list(mysqli $mysqli, array $vals): string {
        if (empty($vals)) return "''";
        return "'" . implode("','", array_map([$mysqli, 'real_escape_string'], $vals)) . "'";
    }
}

/**
 * Resolve the Principal's Basic Ed scope from their education_level.
 *
 * @return array{levels: string[], grades: string[], label: string}
 */
function principal_scope(?string $educationLevel): array {
    $lvl = $educationLevel ?: 'both';
    if ($lvl === 'junior_high') {
        return ['levels' => ['junior_high'], 'grades' => ['7','8','9','10'], 'label' => 'Junior High School'];
    }
    if ($lvl === 'senior_high') {
        return ['levels' => ['senior_high'], 'grades' => ['11','12'], 'label' => 'Senior High School'];
    }
    return [
        'levels' => ['junior_high','senior_high'],
        'grades' => ['7','8','9','10','11','12'],
        'label'  => 'Junior High & Senior High',
    ];
}

/**
 * Build the Principal's notification list.
 *
 * Every item carries a STABLE id: the bell uses it to tell "already seen"
 * from "new since last poll". Ids must therefore be derived from the
 * notification's meaning, never from the current time or array position —
 * a time-based id would make every item look new on every poll.
 *
 * @return array<int, array{id:string, text:string, level:string}>
 */
function principal_build_notifications(mysqli $mysqli, int $userId, ?array $settings = null): array {
    if ($settings === null) {
        $settings = get_system_settings($mysqli);
    }

    $edu = safe_scalar($mysqli, "SELECT education_level FROM users WHERE id=? LIMIT 1", "i", [$userId]);
    $scope           = principal_scope(is_string($edu) ? $edu : null);
    $scopeAcademicIn = esc_list($mysqli, $scope['levels']);
    $scopeGradesIn   = esc_list($mysqli, $scope['grades']);

    $structureActive = ($settings['academic_structure'] !== 'college');
    $period_id_int   = $settings['period_id'] ?? 0;
    $hasPeriod       = $period_id_int > 0;

    $daysRemaining = null;
    if ($settings['eval_end']) {
        $daysRemaining = (int)ceil((strtotime($settings['eval_end']) - strtotime(date('Y-m-d'))) / 86400);
    }

    $out = [];

    // Schedule-driven notices straight from the shared service.
    foreach ($settings['notifications'] as $n) {
        $out[] = ['id' => 'sys-' . substr(md5($n), 0, 10), 'text' => $n, 'level' => 'info'];
    }

    if (!$structureActive) {
        $out[] = [
            'id'    => 'structure-inactive',
            'text'  => 'Basic Education is not the active academic structure right now.',
            'level' => 'warn',
        ];
        return $out;
    }

    $teacherCount = (int)(safe_scalar($mysqli, "
        SELECT COUNT(*) c FROM users
        WHERE role='teacher' AND is_active=1 AND account_status='approved'
          AND academic_level IN ($scopeAcademicIn)
    ") ?? 0);

    $staffCount = (int)(safe_scalar($mysqli, "
        SELECT COUNT(*) c FROM users
        WHERE role='staff' AND is_active=1 AND account_status='approved'
          AND academic_level IN ($scopeAcademicIn)
    ") ?? 0);

    $studentCount = (int)(safe_scalar($mysqli, "
        SELECT COUNT(*) c FROM users
        WHERE role='student' AND is_active=1 AND account_status='approved'
          AND grade_level IN ($scopeGradesIn)
    ") ?? 0);

    $studentsSubmitted = 0;
    $targetsEvaluated  = 0;
    if ($hasPeriod) {
        $studentsSubmitted = (int)(safe_scalar($mysqli, "
            SELECT COUNT(DISTINCT et.evaluator_id) c
            FROM evaluation_tracker et
            INNER JOIN users u ON u.id = et.evaluator_id
            WHERE et.eval_type='student' AND et.period_id=?
              AND u.role='student' AND u.grade_level IN ($scopeGradesIn)
        ", "i", [$period_id_int]) ?? 0);

        $targetsEvaluated = (int)(safe_scalar($mysqli, "
            SELECT COUNT(DISTINCT et.target_user_id) c
            FROM evaluation_tracker et
            INNER JOIN users u ON u.id = et.target_user_id
            WHERE et.eval_type='student' AND et.period_id=?
              AND u.role IN ('teacher','staff') AND u.academic_level IN ($scopeAcademicIn)
        ", "i", [$period_id_int]) ?? 0);
    }

    $totalTargets         = $teacherCount + $staffCount;
    $evaluationCompletion = $totalTargets > 0 ? (int)round($targetsEvaluated / $totalTargets * 100) : 0;

    if ($teacherCount === 0 && $staffCount === 0) {
        $out[] = [
            'id'    => 'no-roster',
            'text'  => 'No approved and active Teacher or Staff accounts found for your scope.',
            'level' => 'warn',
        ];
    }

    if (!$hasPeriod) {
        $out[] = [
            'id'    => 'no-period',
            'text'  => 'No active evaluation period yet — figures stay at 0% until one is opened.',
            'level' => 'info',
        ];
    }

    if ($hasPeriod && $daysRemaining !== null && $daysRemaining >= 0 && $daysRemaining <= 3) {
        $out[] = [
            'id'    => 'closing-' . $daysRemaining,
            'text'  => "Evaluation closes in {$daysRemaining} day" . ($daysRemaining === 1 ? '' : 's') . '.',
            'level' => 'warn',
        ];
    }

    if ($hasPeriod) {
        $notSubmitted = $studentCount - $studentsSubmitted;
        if ($notSubmitted > 0) {
            // Id deliberately excludes the count, so the item does not
            // re-alert every time one more student submits.
            $out[] = [
                'id'    => 'students-pending',
                'text'  => "{$notSubmitted} student" . ($notSubmitted === 1 ? '' : 's') . ' have not submitted.',
                'level' => 'info',
            ];
        }
    }

    if ($totalTargets > 0 && $evaluationCompletion === 100) {
        $out[] = [
            'id'    => 'targets-complete',
            'text'  => 'Teacher and staff evaluations are complete.',
            'level' => 'good',
        ];
    }

    if (empty($out)) {
        $out[] = ['id' => 'all-clear', 'text' => 'No urgent items right now.', 'level' => 'good'];
    }

    return $out;
}

