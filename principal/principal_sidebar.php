<?php
// principal_sidebar.php — one shared Principal sidebar used by every Principal page.
// The visual structure follows the Staff portal sidebar; the Principal role keeps
// its own amber accent color. Appearance is handled globally by principal_theme.js.

if (!function_exists('principal_sidebar_assets')) {
    function principal_sidebar_assets(): void {
        static $done = false;
        if ($done) return;
        $done = true;
        ?>
<link href="https://fonts.googleapis.com/css2?family=Rajdhani:wght@600;700&family=DM+Sans:wght@400;500;600;700&display=swap" rel="stylesheet"/>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css"/>
<style id="principal-sidebar-unified">
#pbiSidebar{box-sizing:border-box!important;width:248px!important;min-width:248px!important;max-width:248px!important;flex:0 0 248px!important;position:fixed!important;top:0!important;left:0!important;height:100vh!important;min-height:100vh!important;margin:0!important;padding:0!important;overflow:hidden!important;display:flex!important;flex-direction:column!important;background:#0A192F!important;color:#E0E6F0!important;border-right:1px solid #172A45!important;box-shadow:6px 0 20px rgba(0,0,0,.16)!important;border-radius:0!important;z-index:40!important;font-family:'DM Sans',sans-serif!important;font-size:14px!important;line-height:normal!important;text-align:left!important;}
#pbiSidebar *,#pbiSidebar *::before,#pbiSidebar *::after{box-sizing:border-box!important;}
#pbiSidebar .portal-brand{min-height:68px!important;padding:14px 17px!important;border-bottom:1px solid rgba(255,255,255,.08)!important;display:flex!important;align-items:center!important;gap:11px!important;background:transparent!important;}
#pbiSidebar .portal-brand-logo{width:38px!important;height:38px!important;flex:0 0 38px!important;border-radius:11px!important;display:flex!important;align-items:center!important;justify-content:center!important;overflow:hidden!important;background:#0F1F3D!important;border:1px solid #D99A2B!important;box-shadow:0 0 14px rgba(217,154,43,.18)!important;}
#pbiSidebar .portal-brand-logo img{width:100%!important;height:100%!important;object-fit:cover!important;display:block!important;}
#pbiSidebar .portal-brand-copy{min-width:0!important;display:flex!important;flex-direction:column!important;line-height:1.2!important;}
#pbiSidebar .portal-brand-copy strong{font-family:'Rajdhani',sans-serif!important;font-size:15px!important;font-weight:700!important;color:#F8FAFC!important;letter-spacing:.25px!important;}
#pbiSidebar .portal-brand-copy span{margin-top:3px!important;font-size:10px!important;color:#8FA6BF!important;font-weight:600!important;letter-spacing:.15px!important;white-space:nowrap!important;}
#pbiSidebar .portal-sidebar-profile{padding:18px 16px 17px!important;text-align:center!important;border-bottom:1px solid rgba(255,255,255,.08)!important;background:transparent!important;}
#pbiSidebar .portal-profile-avatar-wrap{margin:0 auto 11px!important;display:flex!important;justify-content:center!important;}
#pbiSidebar .portal-profile-avatar{width:64px!important;height:64px!important;border-radius:50%!important;object-fit:cover!important;border:2px solid #D99A2B!important;background:#0F1F3D!important;box-shadow:0 0 15px rgba(217,154,43,.18)!important;display:flex!important;align-items:center!important;justify-content:center!important;}
#pbiSidebar .portal-profile-fallback{color:#F0B84D!important;font-family:'Rajdhani',sans-serif!important;font-size:17px!important;font-weight:700!important;}
#pbiSidebar .portal-profile-name{color:#F8FAFC!important;font-size:13px!important;line-height:1.35!important;font-weight:700!important;word-break:break-word!important;}
#pbiSidebar .portal-profile-role{margin-top:3px!important;color:#8FA6BF!important;font-size:10px!important;text-transform:uppercase!important;letter-spacing:.7px!important;font-weight:700!important;line-height:1.35!important;}
#pbiSidebar .portal-profile-scope{margin-top:3px!important;color:#8FA6BF!important;font-size:10px!important;line-height:1.35!important;}
#pbiSidebar .portal-sidebar-nav{flex:1!important;padding:16px 10px!important;overflow-y:auto!important;overflow-x:hidden!important;background:transparent!important;}
#pbiSidebar .portal-sidebar-nav .sb-nav-section-label{display:block!important;width:auto!important;padding:0 8px!important;margin:0 0 7px!important;font-family:'DM Sans',sans-serif!important;font-size:9.5px!important;font-weight:800!important;letter-spacing:1.25px!important;line-height:1.2!important;text-align:left!important;text-transform:uppercase!important;color:#8FA6BF!important;text-shadow:none!important;background:none!important;border:0!important;}
#pbiSidebar .portal-sidebar-nav .sb-nav-section-label.sidebar-section-secondary{margin-top:17px!important;}
#pbiSidebar .portal-sidebar-nav a{display:flex!important;align-items:center!important;gap:10px!important;width:100%!important;min-height:40px!important;margin:2px 2px!important;padding:9px 11px!important;border:0!important;border-radius:8px!important;background:transparent!important;color:#CBD8E8!important;font-family:'DM Sans',sans-serif!important;font-size:13px!important;font-weight:500!important;line-height:1.2!important;letter-spacing:0!important;text-decoration:none!important;transition:background .2s,color .2s,box-shadow .2s!important;}
#pbiSidebar .portal-sidebar-nav a::before,#pbiSidebar .portal-sidebar-nav a::after{content:none!important;display:none!important;}
#pbiSidebar .portal-sidebar-nav a i{display:inline-block!important;width:18px!important;flex:0 0 18px!important;font-size:14px!important;color:#8FA6BF!important;text-align:center!important;margin:0!important;}
#pbiSidebar .portal-sidebar-nav a:hover{background:rgba(255,255,255,.07)!important;color:#FFFFFF!important;}
#pbiSidebar .portal-sidebar-nav a:hover i{color:#F0B84D!important;}
#pbiSidebar .portal-sidebar-nav a.active{background:linear-gradient(90deg,rgba(217,154,43,.18),rgba(255,255,255,.025))!important;color:#FFFFFF!important;font-weight:700!important;box-shadow:inset 3px 0 0 #F0B84D!important;}
#pbiSidebar .portal-sidebar-nav a.active i{color:#F0B84D!important;}
#pbiSidebar .portal-sidebar-nav .nav-badge,#pbiSidebar .portal-sidebar-nav .side-nav-badge{margin-left:auto!important;background:rgba(217,154,43,.22)!important;color:#FDE68A!important;border-radius:20px!important;padding:2px 7px!important;font-size:9px!important;font-weight:800!important;}
#pbiSidebar .sidebar-footer{padding:13px 14px 15px!important;border-top:1px solid rgba(255,255,255,.08)!important;background:transparent!important;}
#pbiSidebar .btn-logout-side{display:flex!important;align-items:center!important;gap:7px!important;width:100%!important;padding:9px 11px!important;border:1px solid rgba(240,84,84,.30)!important;background:rgba(240,84,84,.08)!important;border-radius:8px!important;color:#FCA5A5!important;font-size:13px!important;font-weight:600!important;text-decoration:none!important;transition:all .2s!important;box-sizing:border-box!important;}
#pbiSidebar .btn-logout-side:hover{background:rgba(240,84,84,.15)!important;color:#fff!important;}
@media(max-width:768px){#pbiSidebar{position:static!important;width:100%!important;min-width:0!important;max-width:none!important;flex:0 0 auto!important;height:auto!important;min-height:auto!important;overflow:visible!important;} }
@media print{#pbiSidebar{display:none!important;}}
</style>
        <?php
    }
}

if (!function_exists('render_principal_sidebar')) {
    function render_principal_sidebar(string $active, array $me, string $scopeLabel, string $photo_src): void {
        principal_sidebar_assets();
        if ($active === 'evaluation') $active = 'evaluations';

        $fullName = (string)($me['full_name'] ?? 'Principal');
        $parts = preg_split('/\s+/', trim($fullName));
        $initials = strtoupper(substr($parts[0] ?? 'P', 0, 1) . substr($parts[1] ?? '', 0, 1));
        if ($initials === '') $initials = 'P';

        $links = [
            'dashboard'   => ['principal_dashboard.php',          'fa-house',            'Dashboard'],
            'evaluations' => ['principal_evaluations.php',        'fa-clipboard-list',   'Evaluate Others'],
            'tracker'     => ['principal_evaluation_tracker.php', 'fa-satellite-dish',   'Evaluation Tracker'],
            'results'     => ['principal_results.php',            'fa-chart-bar',        "Feedback's Received"],
            'reports'     => ['principal_reports.php',             'fa-chart-line',      'Evaluation Reports'],
            'settings'    => ['principal_account_settings.php',   'fa-gear',            'Settings'],
        ];
        ?>
<aside class="sidebar" id="pbiSidebar">
    <div class="portal-brand sidebar-brand">
        <div class="portal-brand-logo"><img src="../image/pbi_logo" alt="PBI" onerror="this.style.display='none'"/></div>
        <div class="portal-brand-copy">
            <strong>Principal Portal</strong>
            <span>Evaluation Workspace</span>
        </div>
    </div>

    <div class="portal-sidebar-profile">
        <div class="portal-profile-avatar-wrap">
            <?php if (!empty($photo_src)): ?>
                <img class="portal-profile-avatar" src="<?= htmlspecialchars($photo_src) ?>" alt="<?= htmlspecialchars($fullName) ?>"/>
            <?php else: ?>
                <span class="portal-profile-avatar portal-profile-fallback"><?= htmlspecialchars($initials) ?></span>
            <?php endif; ?>
        </div>
        <div class="portal-profile-name"><?= htmlspecialchars($fullName) ?></div>
        <div class="portal-profile-role">PRINCIPAL</div>
        <div class="portal-profile-scope"><?= htmlspecialchars($scopeLabel) ?></div>
    </div>

    <nav class="sidebar-nav portal-sidebar-nav" aria-label="Principal navigation">
        <div class="nav-section-label sb-nav-section-label">Main</div>
        <?php foreach (['dashboard', 'evaluations', 'tracker', 'results', 'reports'] as $key): ?>
            <?php [$href, $icon, $label] = $links[$key]; ?>
            <a href="<?= htmlspecialchars($href) ?>" class="nav-link <?= $key === $active ? 'active' : '' ?>"<?= $key === $active ? ' aria-current="page"' : '' ?>>
                <i class="fa-solid <?= htmlspecialchars($icon) ?>"></i><span><?= htmlspecialchars($label) ?></span>
            </a>
        <?php endforeach; ?>

        <div class="nav-section-label sb-nav-section-label sidebar-section-secondary">Administration</div>
        <?php [$href, $icon, $label] = $links['settings']; ?>
        <a href="<?= htmlspecialchars($href) ?>" class="nav-link <?= $active === 'settings' ? 'active' : '' ?>"<?= $active === 'settings' ? ' aria-current="page"' : '' ?>>
            <i class="fa-solid <?= htmlspecialchars($icon) ?>"></i><span><?= htmlspecialchars($label) ?></span>
        </a>
    </nav>

    <div class="sidebar-footer">
        <a href="../logout.php" class="btn-logout-side" onclick="return confirm('Log out of your principal session?')">
            <i class="fa-solid fa-power-off"></i><span>Log Out</span>
        </a>
    </div>
</aside>
        <?php
    }
}
