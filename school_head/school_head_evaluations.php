<?php
// session_bootstrap.php — include this BEFORE session_start() everywhere
session_set_cookie_params([
    'lifetime' => 0,
    'path'     => '/',
    'domain'   => '',
    'secure'   => false,
    'httponly' => true,
    'samesite' => 'Lax',
]);
session_start();
require_once 'db.php';

// ── AUTH GUARD ────────────────────────────────────────────
if (empty($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'school_head') {
    header("Location: school_head_login.php");
    exit;
}
$user_id = $_SESSION['user_id'];

// Pull fresh profile info for the sidebar
$stmt = $mysqli->prepare("SELECT full_name, username, email, designation, photo FROM users WHERE id = ? LIMIT 1");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$me = $stmt->get_result()->fetch_assoc();
$stmt->close();
$photo_src = !empty($me['photo']) ? '../image/' . $me['photo'] : '../image/pbi_logo';

// ── ACTIVE EVALUATION PERIOD ──────────────────────────────
$period = null;
$pr = $mysqli->query("SELECT * FROM evaluation_periods WHERE is_active=1 LIMIT 1");
if ($pr) $period = $pr->fetch_assoc();
$period_id = $period ? (int)$period['id'] : 0;

// ── FETCH ALL APPROVED TEACHER/STAFF ──────────────────────
$ures = $mysqli->query("
    SELECT id, full_name, designation, photo, role, secondary_role
    FROM users
    WHERE role IN ('teacher','staff') AND is_active=1 AND account_status='approved'
    ORDER BY full_name ASC
");
$all_users = [];
if ($ures) while ($u = $ures->fetch_assoc()) $all_users[] = $u;

// Same bucket resolution admin_questionnaire.php uses: someone holding
// both roles (primary + self-assigned secondary) is bucketed as
// Multi-Role; otherwise they're bucketed under their single role.
function resolve_bucket($u) {
    $roles = [];
    if ($u['role'] === 'teacher') $roles[] = 'teacher';
    if ($u['role'] === 'staff')   $roles[] = 'staff';
    if (!empty($u['secondary_role']) && !in_array($u['secondary_role'], $roles, true)) {
        $roles[] = $u['secondary_role'];
    }
    if (count($roles) >= 2) return 'Multi-Role';
    return $roles[0] === 'teacher' ? 'Teacher' : 'Staff';
}

// ── WHICH TARGETS HAS THIS SCHOOL HEAD ALREADY EVALUATED (this period)? ──
$done_ids = [];
if ($period_id) {
    $dstmt = $mysqli->prepare("SELECT DISTINCT target_user_id FROM evaluation_tracker WHERE evaluator_id=? AND eval_type='school_head' AND period_id=?");
    $dstmt->bind_param("ii", $user_id, $period_id);
    $dstmt->execute();
    $dres = $dstmt->get_result();
    if ($dres) while ($r = $dres->fetch_assoc()) $done_ids[] = (int)$r['target_user_id'];
    $dstmt->close();
}

$mysqli->close();

// ── FILTER (bucket + status) ──────────────────────────────
$bucket_filter = $_GET['bucket'] ?? 'all';
if (!in_array($bucket_filter, ['all','Teacher','Staff','Multi-Role'], true)) $bucket_filter = 'all';
$status_filter = $_GET['status'] ?? 'all';
if (!in_array($status_filter, ['all','pending','done'], true)) $status_filter = 'all';

$rows = [];
foreach ($all_users as $u) {
    $bucket = resolve_bucket($u);
    $is_done = in_array((int)$u['id'], $done_ids, true);
    if ($bucket_filter !== 'all' && $bucket !== $bucket_filter) continue;
    if ($status_filter === 'pending' && $is_done) continue;
    if ($status_filter === 'done' && !$is_done) continue;
    $rows[] = ['user' => $u, 'bucket' => $bucket, 'done' => $is_done];
}

$total_count   = count($all_users);
$done_total    = count($done_ids);
$pending_total = max(0, $total_count - $done_total);

$toast       = $_SESSION['toast']       ?? ''; unset($_SESSION['toast']);
$toast_error = $_SESSION['toast_error'] ?? ''; unset($_SESSION['toast_error']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"/>
<meta name="viewport" content="width=device-width, initial-scale=1.0"/>
<title>PBI — School Head Evaluations</title>
<link href="https://fonts.googleapis.com/css2?family=Rajdhani:wght@600;700&family=DM+Sans:wght@400;500;600&display=swap" rel="stylesheet"/>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css"/>
<style>
:root{--dark:#0A192F;--mid:#172A45;--inner:#0F1F3D;--emerald:#059669;--emerald-h:#10B981;--light:#E0E6F0;--muted:#A0B3C6;--radius:10px;--shadow:0 8px 32px rgba(0,0,0,0.45);--border:rgba(255,255,255,.08);}
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0;}
body{min-height:100vh;background:var(--dark);font-family:'DM Sans',sans-serif;color:var(--light);display:flex;}

/* SIDEBAR (shared with dashboard) */
.sidebar{width:250px;flex-shrink:0;background:rgba(23,42,69,.9);border-right:1px solid var(--border);min-height:100vh;padding:28px 20px;display:flex;flex-direction:column;}
.sb-profile{text-align:center;margin-bottom:26px;}
.sb-photo{width:72px;height:72px;border-radius:50%;object-fit:cover;border:2.5px solid var(--emerald);box-shadow:0 0 18px rgba(5,150,105,.4);margin:0 auto 10px;display:block;}
.sb-name{font-weight:700;font-size:15px;color:#fff;}
.sb-role{font-size:11px;color:var(--emerald-h);text-transform:uppercase;letter-spacing:.6px;margin-top:2px;}
.sb-nav{display:flex;flex-direction:column;gap:4px;margin-top:10px;}
.sb-nav a{display:flex;align-items:center;gap:10px;padding:11px 14px;border-radius:8px;color:var(--muted);text-decoration:none;font-size:14px;font-weight:500;transition:background .2s,color .2s;}
.sb-nav a:hover,.sb-nav a.active{background:rgba(5,150,105,.15);color:#fff;}
.sb-nav a i{width:18px;text-align:center;color:var(--emerald-h);}
.sb-logout{margin-top:auto;}
.sb-logout a{display:flex;align-items:center;gap:10px;padding:11px 14px;border-radius:8px;color:#fca5a5;text-decoration:none;font-size:14px;font-weight:500;transition:background .2s;}
.sb-logout a:hover{background:rgba(240,84,84,.12);}

/* MAIN */
.main{flex:1;padding:36px 44px;}
.page-header{display:flex;justify-content:space-between;align-items:flex-start;flex-wrap:wrap;gap:14px;margin-bottom:24px;}
.page-title{font-family:'Rajdhani',sans-serif;font-size:28px;font-weight:700;color:#fff;letter-spacing:1px;}
.page-sub{font-size:13px;color:var(--muted);margin-top:4px;}
.period-badge{background:rgba(5,150,105,.14);border:1px solid rgba(5,150,105,.3);color:var(--emerald-h);padding:6px 14px;border-radius:20px;font-size:12px;font-weight:600;display:flex;align-items:center;gap:7px;}
.period-badge.closed{background:rgba(240,84,84,.1);border-color:rgba(240,84,84,.3);color:#fca5a5;}

.stat-strip{display:flex;gap:14px;margin-bottom:22px;flex-wrap:wrap;}
.stat-pill{background:var(--mid);border:1px solid var(--border);border-radius:12px;padding:14px 20px;flex:1;min-width:150px;}
.stat-pill .n{font-size:24px;font-weight:700;color:#fff;}
.stat-pill .l{font-size:11px;color:var(--muted);text-transform:uppercase;letter-spacing:.6px;margin-top:3px;}
.stat-pill.emerald .n{color:var(--emerald-h);}
.stat-pill.amber .n{color:#fbbf24;}

.filter-bar{display:flex;gap:10px;flex-wrap:wrap;margin-bottom:20px;}
.filter-group{display:flex;background:var(--mid);border:1px solid var(--border);border-radius:10px;overflow:hidden;}
.filter-chip{padding:9px 16px;font-size:12px;font-weight:600;color:var(--muted);text-decoration:none;transition:all .2s;white-space:nowrap;}
.filter-chip:hover{color:var(--light);background:rgba(255,255,255,.04);}
.filter-chip.active{background:rgba(5,150,105,.16);color:var(--emerald-h);}

.roster-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(260px,1fr));gap:16px;}
.roster-card{background:var(--mid);border:1px solid var(--border);border-radius:14px;padding:20px;display:flex;flex-direction:column;gap:10px;box-shadow:var(--shadow);}
.roster-card.done{opacity:.72;}
.rc-top{display:flex;align-items:center;gap:12px;}
.rc-photo{width:50px;height:50px;border-radius:50%;object-fit:cover;border:2px solid var(--border);flex-shrink:0;}
.rc-photo-ph{width:50px;height:50px;border-radius:50%;background:var(--inner);border:2px solid var(--border);display:flex;align-items:center;justify-content:center;color:var(--muted);font-size:18px;flex-shrink:0;}
.rc-name{font-size:14px;font-weight:700;color:#fff;line-height:1.3;}
.rc-desig{font-size:11.5px;color:var(--muted);margin-top:1px;}
.rc-bucket{display:inline-flex;align-items:center;gap:5px;font-size:10px;font-weight:700;padding:3px 10px;border-radius:20px;width:fit-content;}
.rc-bucket.b-teacher{background:rgba(13,148,136,.15);color:#5eead4;border:1px solid rgba(13,148,136,.3);}
.rc-bucket.b-staff{background:rgba(16,185,129,.12);color:var(--emerald-h);border:1px solid rgba(16,185,129,.28);}
.rc-bucket.b-multi{background:rgba(245,158,11,.12);color:#fbbf24;border:1px solid rgba(245,158,11,.28);}
.rc-btn{margin-top:4px;display:flex;align-items:center;justify-content:center;gap:7px;padding:10px 0;border-radius:8px;border:none;font-size:13px;font-weight:700;cursor:pointer;text-decoration:none;transition:background .2s;font-family:'DM Sans',sans-serif;}
.rc-btn.pending{background:var(--emerald);color:#fff;}
.rc-btn.pending:hover{background:var(--emerald-h);}
.rc-btn.done{background:rgba(255,255,255,.06);color:var(--muted);cursor:default;}
.empty-state{text-align:center;padding:60px 20px;color:var(--muted);}
.empty-state i{font-size:38px;opacity:.3;display:block;margin-bottom:14px;}
.no-period-warn{background:rgba(251,191,36,.08);border:1px solid rgba(251,191,36,.2);border-radius:10px;padding:16px 20px;margin-bottom:20px;display:flex;gap:10px;align-items:center;font-size:13px;color:#fcd34d;}
.toast{border-radius:9px;padding:12px 18px;font-size:13px;margin-bottom:20px;display:flex;align-items:center;gap:9px;}
.toast-success{background:rgba(34,197,94,.1);border:1px solid rgba(34,197,94,.28);color:#86efac;}
.toast-error{background:rgba(240,84,84,.1);border:1px solid rgba(240,84,84,.28);color:#fca5a5;}

@media(max-width:768px){body{flex-direction:column;}.sidebar{width:100%;min-height:auto;}}
</style>
</head>
<body>

<aside class="sidebar">
    <div class="sb-profile">
        <img class="sb-photo" src="<?= htmlspecialchars($photo_src) ?>" alt="Profile"/>
        <div class="sb-name"><?= htmlspecialchars($me['full_name'] ?? 'School Head') ?></div>
        <div class="sb-role"><?= htmlspecialchars($me['designation'] ?? 'School Head') ?></div>
    </div>
    <nav class="sb-nav">
        <a href="school_head_dashboard.php"><i class="fa-solid fa-gauge"></i> Dashboard</a>
        <a href="school_head_evaluations.php" class="active"><i class="fa-solid fa-clipboard-list"></i> Evaluations</a>
        <a href="school_head_personnel.php"><i class="fa-solid fa-users"></i> Personnel</a>
        <a href="#"><i class="fa-solid fa-chart-line"></i> Reports</a>
        <a href="#"><i class="fa-solid fa-gear"></i> Account Settings</a>
    </nav>
    <div class="sb-logout">
        <a href="school_head_logout.php"><i class="fa-solid fa-right-from-bracket"></i> Log Out</a>
    </div>
</aside>

<main class="main">
    <div class="page-header">
        <div>
            <div class="page-title">Evaluations</div>
            <div class="page-sub">Evaluate faculty &amp; staff system-wide</div>
        </div>
        <?php if ($period): ?>
        <div class="period-badge"><i class="fa-solid fa-calendar-check"></i> <?= htmlspecialchars($period['period_label'] ?? ($period['semester'] ?? 'Active Period')) ?></div>
        <?php else: ?>
        <div class="period-badge closed"><i class="fa-solid fa-calendar-xmark"></i> No Active Period</div>
        <?php endif; ?>
    </div>

    <?php if ($toast): ?>
    <div class="toast toast-success"><i class="fa-solid fa-circle-check"></i> <?= htmlspecialchars($toast) ?></div>
    <?php endif; ?>
    <?php if ($toast_error): ?>
    <div class="toast toast-error"><i class="fa-solid fa-circle-exclamation"></i> <?= htmlspecialchars($toast_error) ?></div>
    <?php endif; ?>

    <?php if (!$period): ?>
    <div class="no-period-warn"><i class="fa-solid fa-clock"></i> No active evaluation period. You can browse the roster, but submissions are closed until an admin opens a period.</div>
    <?php endif; ?>

    <div class="stat-strip">
        <div class="stat-pill"><div class="n"><?= $total_count ?></div><div class="l">Total Personnel</div></div>
        <div class="stat-pill emerald"><div class="n"><?= $done_total ?></div><div class="l">Evaluated</div></div>
        <div class="stat-pill amber"><div class="n"><?= $pending_total ?></div><div class="l">Pending</div></div>
    </div>

    <div class="filter-bar">
        <div class="filter-group">
            <a href="?bucket=all&status=<?= $status_filter ?>" class="filter-chip <?= $bucket_filter==='all'?'active':'' ?>">All</a>
            <a href="?bucket=Teacher&status=<?= $status_filter ?>" class="filter-chip <?= $bucket_filter==='Teacher'?'active':'' ?>">Teacher</a>
            <a href="?bucket=Staff&status=<?= $status_filter ?>" class="filter-chip <?= $bucket_filter==='Staff'?'active':'' ?>">Staff</a>
            <a href="?bucket=Multi-Role&status=<?= $status_filter ?>" class="filter-chip <?= $bucket_filter==='Multi-Role'?'active':'' ?>">Multi-Role</a>
        </div>
        <div class="filter-group">
            <a href="?bucket=<?= $bucket_filter ?>&status=all" class="filter-chip <?= $status_filter==='all'?'active':'' ?>">All Status</a>
            <a href="?bucket=<?= $bucket_filter ?>&status=pending" class="filter-chip <?= $status_filter==='pending'?'active':'' ?>">Pending</a>
            <a href="?bucket=<?= $bucket_filter ?>&status=done" class="filter-chip <?= $status_filter==='done'?'active':'' ?>">Evaluated</a>
        </div>
    </div>

    <?php if (empty($rows)): ?>
    <div class="empty-state"><i class="fa-solid fa-users-slash"></i><p>No personnel match this filter.</p></div>
    <?php else: ?>
    <div class="roster-grid">
        <?php foreach ($rows as $r): $u = $r['user']; $bucket = $r['bucket']; $done = $r['done'];
            $bucket_cls = $bucket === 'Teacher' ? 'b-teacher' : ($bucket === 'Staff' ? 'b-staff' : 'b-multi');
        ?>
        <div class="roster-card <?= $done ? 'done' : '' ?>">
            <div class="rc-top">
                <?php if (!empty($u['photo'])): ?>
                <img class="rc-photo" src="../image/<?= htmlspecialchars($u['photo']) ?>" alt=""/>
                <?php else: ?>
                <div class="rc-photo-ph"><i class="fa-solid fa-user"></i></div>
                <?php endif; ?>
                <div>
                    <div class="rc-name"><?= htmlspecialchars($u['full_name']) ?></div>
                    <div class="rc-desig"><?= htmlspecialchars($u['designation'] ?: ucfirst($u['role'])) ?></div>
                </div>
            </div>
            <span class="rc-bucket <?= $bucket_cls ?>"><i class="fa-solid fa-layer-group" style="font-size:9px"></i> <?= htmlspecialchars($bucket) ?></span>
            <?php if ($done): ?>
            <button class="rc-btn done" disabled><i class="fa-solid fa-circle-check"></i> Evaluated</button>
            <?php else: ?>
            <a class="rc-btn pending" href="school_head_evaluate.php?tid=<?= (int)$u['id'] ?>"><i class="fa-solid fa-pen-to-square"></i> Evaluate</a>
            <?php endif; ?>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
</main>

</body>
</html>