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

$displayName = (string)($me['full_name'] ?? 'Dean');
$initials = '';
foreach (preg_split('/\s+/', trim($displayName)) as $part) {
    if ($part !== '') $initials .= strtoupper(substr($part, 0, 1));
    if (strlen($initials) >= 2) break;
}
if ($initials === '') $initials = 'D';
$hasPhoto = !empty($me['photo']);
$profileFallbackSrc = '../image/pbi_logo';

$navItems = [
    'dashboard'  => ['dean_dashboard.php',          'fa-gauge',           'Dashboard'],
    'evaluation' => ['dean_evaluation.php',         'fa-clipboard-check', 'My Evaluation'],
    'tracker'    => ['dean_evaluation_tracker.php', 'fa-satellite-dish',  'Evaluation Tracker'],
    'results'    => ['dean_results.php',            'fa-star-half-stroke','View Results'],
    'reports'    => ['dean_reports.php',            'fa-chart-line',      'Evaluation Reports'],
    'settings'   => ['dean_account_settings.php',   'fa-gear',            'Account Settings'],
];
?>
<aside class="sidebar" id="sidebar">
    <div class="sidebar-brand portal-brand">
        <div class="portal-brand-logo">
            <img src="../image/pbi_logo" alt="PBI" onerror="this.style.display='none'"/>
        </div>
        <div class="portal-brand-copy">
            <strong>Dean Workspace</strong>
            <span>Evaluation Workspace</span>
        </div>
    </div>

    <div class="portal-sidebar-profile">
        <div class="portal-profile-avatar-wrap">
            <img class="portal-profile-avatar"
                 src="<?= htmlspecialchars($hasPhoto ? $photo_src : $profileFallbackSrc) ?>"
                 alt="Profile"
                 onerror="if (this.dataset.fallbackApplied !== '1') { this.dataset.fallbackApplied='1'; this.src='<?= htmlspecialchars($profileFallbackSrc) ?>'; }"/>
        </div>
        <div class="portal-profile-name"><?= htmlspecialchars($me['full_name'] ?? 'Dean') ?></div>
        <div class="portal-profile-role">DEAN</div>
        <?php if (!empty($sidebarScope)): ?>
        <div class="portal-profile-scope"><?= htmlspecialchars($sidebarScope) ?></div>
        <?php endif; ?>
    </div>

    <nav class="sidebar-nav portal-sidebar-nav">
        <div class="nav-section-label">Main</div>
        <?php foreach ($navItems as $key => [$href, $icon, $label]): ?>
            <?php if ($key === 'settings'): ?>
                <div class="nav-section-label sidebar-section-secondary">Account</div>
            <?php endif; ?>
            <a href="<?= htmlspecialchars($href) ?>" class="nav-link <?= (isset($active) && $active === $key) ? 'active' : '' ?>">
                <i class="fa-solid <?= htmlspecialchars($icon) ?>"></i><span><?= htmlspecialchars($label) ?></span>
            </a>
        <?php endforeach; ?>
    </nav>

    <div class="sidebar-footer">
        <a href="dean_logout.php" class="btn-logout-side" onclick="return confirm('Log out of your dean session?')">
            <i class="fa-solid fa-power-off"></i><span>Log Out</span>
        </a>
    </div>

    <script>
    // Appearance toggle (Dark / Light) — shared PBI theme preference.
    function applyAppearance(mode) {
        const normalized = mode === 'dark' ? 'dark' : 'light';
        document.documentElement.classList.toggle('dark-theme', normalized === 'dark');
        const value = document.getElementById('appearanceVal');
        if (value) value.textContent = normalized === 'light' ? 'Light' : 'Dark';
        const lightBtn = document.getElementById('appearanceLightBtn');
        const darkBtn  = document.getElementById('appearanceDarkBtn');
        if (lightBtn) lightBtn.classList.toggle('active', normalized === 'light');
        if (darkBtn) darkBtn.classList.toggle('active', normalized === 'dark');
    }
    function setAppearance(mode) {
        const normalized = mode === 'dark' ? 'dark' : 'light';
        localStorage.setItem('pbi_theme', normalized);
        applyAppearance(normalized);
    }
    function toggleAppearance(e) {
        if (e) e.stopPropagation();
        const current = localStorage.getItem('pbi_theme') === 'dark' ? 'dark' : 'light';
        setAppearance(current === 'dark' ? 'light' : 'dark');
    }
    document.addEventListener('DOMContentLoaded', function() {
        const savedTheme = localStorage.getItem('pbi_theme');
        const theme = savedTheme === 'dark' ? 'dark' : 'light';
        localStorage.setItem('pbi_theme', theme);
        applyAppearance(theme);
    });
    </script>
</aside>