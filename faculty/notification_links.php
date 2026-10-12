<?php
// Maps a notification type to the dashboard page it should open when clicked.
// Shared by faculty_dashboard.php, staff_dashboard.php and notifications_api.php
// so the bell, the live toast and the API always agree.

if (!function_exists('notif_link_for')) {
    function notif_link_for(string $type, string $role): string {
        $base = ($role === 'staff') ? 'staff_dashboard.php' : 'faculty_dashboard.php';
        switch ($type) {
            case 'evaluation_received':
                $page = 'my_results';   // Evaluations Received
                break;
            case 'designation_update':
            case 'teaching_assignment':
                $page = 'profile';      // Role & Designation
                break;
            case 'academic_period':
            case 'evaluation_schedule':
            default:
                $page = 'dashboard';    // period / schedule info lives on the dashboard
                break;
        }
        return $base . '?page=' . $page;
    }
}
