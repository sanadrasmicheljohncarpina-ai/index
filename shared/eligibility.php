<?php
/**
 * eligibility.php
 *
 * Independent eligibility checks for evaluation_tracker submissions.
 */

function canStudentEvaluate(mysqli $mysqli, int $studentId, int $targetUserId): array {
    $stmt = $mysqli->prepare("SELECT education_level, year_level FROM users WHERE id = ? LIMIT 1");
    if (!$stmt) return [false, 'Unable to verify your student profile.'];
    $stmt->bind_param("i", $studentId);
    $stmt->execute();
    $student = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    if (!$student || empty($student['education_level']) || empty($student['year_level'])) {
        return [false, 'Your year level is not set on your profile. Please contact the registrar before submitting an evaluation.'];
    }
    $studentLevel = $student['education_level'];
    $studentYear = $student['year_level'];
    $eduBucketMap = [
        'elementary' => ['basic education'], 'junior_high' => ['basic education'],
        'senior_high' => ['basic education'], 'basic education' => ['basic education'],
        'college' => ['college', 'higher education', 'college / university', 'college/university'],
        'higher education' => ['college', 'higher education', 'college / university', 'college/university'],
        'college / university' => ['college', 'higher education', 'college / university', 'college/university'],
        'college/university' => ['college', 'higher education', 'college / university', 'college/university'],
    ];
    $levelKey = strtolower(trim($studentLevel));
    $levelVariants = $eduBucketMap[$levelKey] ?? [$levelKey];
    $stmt = $mysqli->prepare("SELECT role, is_active FROM users WHERE id = ? LIMIT 1");
    if (!$stmt) return [false, 'Unable to verify the selected evaluator target.'];
    $stmt->bind_param("i", $targetUserId);
    $stmt->execute();
    $target = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    if (!$target) return [false, 'The selected faculty/staff member could not be found.'];
    if (empty($target['is_active'])) return [false, 'This account is not currently active.'];
    if (!in_array($target['role'], ['teacher', 'staff'], true)) return [false, 'Student evaluations can only be submitted for faculty or staff members.'];
    $placeholders = implode(',', array_fill(0, count($levelVariants), '?'));
    $stmt = $mysqli->prepare("SELECT EXISTS(SELECT 1 FROM teaching_assignments ta WHERE ta.user_id = ? AND LOWER(TRIM(ta.education_level)) IN ($placeholders) AND LOWER(TRIM(ta.year_level)) = LOWER(TRIM(?))) OR EXISTS(SELECT 1 FROM user_year_levels uyl WHERE uyl.user_id = ? AND LOWER(TRIM(uyl.year_level)) = LOWER(TRIM(?))) AS is_assigned");
    if (!$stmt) return [false, 'Unable to verify the teaching assignment.'];
    $params = array_merge([$targetUserId], $levelVariants, [$studentYear, $targetUserId, $studentYear]);
    $types = 'i' . str_repeat('s', count($levelVariants)) . 's' . 'i' . 's';
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $isAssigned = (bool)($row['is_assigned'] ?? false);
    $stmt->close();
    if (!$isAssigned) return [false, 'You are not eligible to evaluate this faculty/staff member because they are not assigned to your education level and year level.'];
    return [true, $studentLevel];
}

function canPeerEvaluate(mysqli $mysqli, int $evaluatorId, int $targetUserId): array {
    if ($evaluatorId === $targetUserId) return [false, 'You cannot submit a peer evaluation for yourself.'];
    $stmt = $mysqli->prepare("SELECT id, role FROM users WHERE id = ? LIMIT 1");
    $stmt->bind_param("i", $targetUserId);
    $stmt->execute();
    $target = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    if (!$target) return [false, 'The selected colleague could not be found.'];
    if (!in_array($target['role'], ['teacher', 'staff'], true)) return [false, 'Peer evaluations can only be submitted for faculty or staff members.'];
    return [true, null];
}

function canSchoolHeadEvaluate(mysqli $mysqli, int $schoolHeadId, int $targetUserId): array {
    $stmt = $mysqli->prepare("SELECT id, role, is_active, account_status FROM users WHERE id = ? LIMIT 1");
    $stmt->bind_param("i", $targetUserId);
    $stmt->execute();
    $target = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    if (!$target) return [false, 'The selected personnel record could not be found.'];
    if (!in_array($target['role'], ['teacher', 'staff'], true)) return [false, 'School Head evaluations can only be submitted for faculty or staff members.'];
    if (empty($target['is_active']) || $target['account_status'] !== 'approved') return [false, 'This account is not currently active/approved and cannot be evaluated.'];
    return [true, null];
}

/* Shared portal background for faculty, staff, and student pages. */
if (!empty($_SERVER['SCRIPT_NAME']) && preg_match('#/(faculty|student)/#', $_SERVER['SCRIPT_NAME'])) {
    ob_start(function ($html) {
        $css = '<style id="pbi-portal-background">html,body{min-height:100%;}body{background-color:#0A192F!important;background-image:linear-gradient(rgba(10,25,47,.28),rgba(10,25,47,.28)),url(\'../bacjground.png\')!important;background-position:center center!important;background-repeat:no-repeat!important;background-size:cover!important;background-attachment:fixed!important;}</style>';
        if (stripos($html, '</head>') !== false) return preg_replace('/<\/head>/i', $css.'</head>', $html, 1);
        return $css.$html;
    });
}
