<?php
// includes/dean_sidebar.php
// Shared Dean sidebar — SINGLE SOURCE OF TRUTH for nav items, so no
// individual dean_*.php page ever hardcodes its own copy again.
//
// Usage from any dean_*.php page (after $me and $photo_src are set):
//
//   $active = 'dashboard';                      // dashboard|evaluation|tracker|results|reports|settings
//   $sidebarScope = HIGHER_ED_LABEL . ' Division'; // or any string you want under the name/role
//   include __DIR__ . '/includes/dean_sidebar.php';
//
// Phase 2 revision: the Dean Portal is scoped to evaluation duties only
// (evaluate Teachers/Staff/Executive Assistant, monitor student
// participation, view own results, run reports). Personnel management
// (Faculty/Staff directories) is out of scope for the Dean and was
// removed from the nav — see dean_faculty.php / dean_staff.php, which
// are no longer linked from this portal. The underlying roster/service
// functions those pages used are untouched since other roles still rely
// on them.

$navItems = [
    'dashboard'  => ['dean_dashboard.php',          'fa-gauge',           'Dashboard'],
    'evaluation' => ['dean_evaluation.php',         'fa-clipboard-check', 'My Evaluation'],
    'tracker'    => ['dean_evaluation_tracker.php', 'fa-satellite-dish',  'Evaluation Tracker'],
    'results'    => ['dean_results.php',            'fa-star-half-stroke','View Results'],
    'reports'    => ['dean_reports.php',            'fa-chart-line',      'Evaluation Reports'],
    'settings'   => ['dean_account_settings.php',   'fa-gear',            'Account Settings'],
];
?>
<style id="dean-sidebar-section-heading">
.sb-nav .sb-section-title{
    width:100%;
    padding:0 6px;
    margin:8px 0 3px;
    color:#C4B5FD;
    font-size:11px;
    font-weight:900;
    letter-spacing:1.5px;
    line-height:1.25;
    text-align:center;
    text-transform:uppercase;
    text-shadow:0 1px 8px rgba(167,139,250,.16);
}
.sb-nav .sb-section-title:first-child{margin-top:0;}
</style>
<aside class="sidebar">
    <div class="sb-profile">
        <img class="sb-photo" src="<?= htmlspecialchars($photo_src) ?>" alt="Profile"/>
        <div class="sb-name"><?= htmlspecialchars($me['full_name'] ?? 'Dean') ?></div>
        <div class="sb-role"><?= htmlspecialchars($me['designation'] ?? 'Dean') ?></div>
        <?php if (!empty($sidebarScope)): ?>
        <div class="sb-scope"><?= htmlspecialchars($sidebarScope) ?></div>
        <?php endif; ?>
    </div>
    <nav class="sb-nav">
        <div class="sb-section-title">MAIN</div>
        <?php foreach ($navItems as $key => [$href, $icon, $label]): ?>
            <?php if ($key === 'settings'): ?>
                <div class="sb-section-title sb-section-title-account">ACCOUNT</div>
            <?php endif; ?>
            <a href="<?= htmlspecialchars($href) ?>" class="<?= (isset($active) && $active === $key) ? 'active' : '' ?>">
                <i class="fa-solid <?= htmlspecialchars($icon) ?>"></i> <?= htmlspecialchars($label) ?>
            </a>
        <?php endforeach; ?>
    </nav>
    <div class="sb-logout">
        <a href="dean_logout.php"><i class="fa-solid fa-right-from-bracket"></i> Log Out</a>
    </div>
</aside>