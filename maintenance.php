<?php
session_start();
$role = strtolower((string)($_SESSION['role'] ?? ''));
$userId = (int)($_SESSION['user_id'] ?? 0);
if (!$userId) {
    header('Location: index.php');
    exit;
}

$destinations = [
    'student' => 'student/student_dashboard.php',
    'teacher' => 'faculty/faculty_dashboard.php',
    'staff' => 'faculty/staff_dashboard.php',
    'principal' => 'principal/principal_dashboard.php',
    'dean' => 'dean/dean_dashboard.php',
    'admin' => 'admin/admin_dashboard.php',
    'superadmin' => 'admin/admin_dashboard.php',
    'registrar' => 'admin/admin_dashboard.php',
];
$destination = $destinations[$role] ?? 'index.php';
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>System Maintenance</title>
  <style>
    :root{font-family:Inter,Segoe UI,Arial,sans-serif;color:#10243e;background:#f3f7fc}
    *{box-sizing:border-box}body{min-height:100vh;margin:0;display:grid;place-items:center;padding:24px}
    main{width:min(560px,100%);padding:40px;border:1px solid #d7e3f2;border-radius:20px;background:#fff;box-shadow:0 18px 50px #10243e12;text-align:center}
    .icon{width:58px;height:58px;margin:0 auto 20px;display:grid;place-items:center;border-radius:18px;background:#fff4d8;color:#c27a00;font-size:28px}
    h1{margin:0 0 12px;font-size:25px}p{margin:0;color:#5c7187;line-height:1.65}
    .small{margin-top:22px;font-size:13px}
  </style>
</head>
<body>
  <main>
    <div class="icon" aria-hidden="true">⚙</div>
    <h1>System maintenance</h1>
    <p>The system is temporarily unavailable while maintenance is in progress. Your evaluation schedule remains in effect.</p>
    <p class="small" id="status" role="status">This page will return you to the system when access is restored.</p>
  </main>
  <script>
    (() => {
      const destination = <?= json_encode($destination, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
      const status = document.getElementById('status');
      async function checkMaintenance() {
        try {
          const response = await fetch('admin/eval_status.php', {credentials:'same-origin', cache:'no-store'});
          if (!response.ok) throw new Error('status unavailable');
          const data = await response.json();
          if (data.maintenance === false) {
            status.textContent = 'Access restored. Returning to your dashboard…';
            location.replace(destination);
            return;
          }
        } catch (_) {}
        setTimeout(checkMaintenance, 5000);
      }
      checkMaintenance();
    })();
  </script>
</body>
</html>
