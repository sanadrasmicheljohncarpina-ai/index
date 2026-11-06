<?php
// student/security_questions.php
// Retired: students now set their security questions from the modal on the Settings page of
// student_dashboard.php. Kept as a redirect so old links and bookmarks still work.
header('Location: student_dashboard.php?view=settings');
exit;
