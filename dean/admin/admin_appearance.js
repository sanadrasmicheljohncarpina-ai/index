/* ============================================================
   PBI Admin — shared Appearance engine (Density / Motion / Accent / Theme)
   Include as a normal blocking <script src="admin_appearance.js"></script>
   near the end of <head> (after theme CSS) on every admin page so saved
   preferences apply before first paint. Pair with admin_appearance.css.
   The controls in settings.php read/write the same localStorage keys via
   window.PBIAppearance so there is one source of truth.
   ============================================================ */
(function () {
  var KEYS = { density: 'pbiDensity', motion: 'pbiReduceMotion', accent: 'pbiAccent', theme: 'pbiTheme' };
  var DEFAULTS = { density: 'compact', motion: false, accent: '#2f6ee2', theme: 'light' };

  function read() {
    return {
      density: localStorage.getItem(KEYS.density) || DEFAULTS.density,
      motion: localStorage.getItem(KEYS.motion) === '1',
      accent: localStorage.getItem(KEYS.accent) || DEFAULTS.accent,
      theme: localStorage.getItem(KEYS.theme) || DEFAULTS.theme,
    };
  }

  function resolveTheme(pref) {
    if (pref === 'system') {
      try { return window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light'; }
      catch (e) { return 'light'; }
    }
    return pref;
  }

  function apply() {
    var a = read();
    var html = document.documentElement;
    var density = a.density === 'comfortable' ? 'comfortable' : 'compact';
    html.classList.toggle('density-comfortable', density === 'comfortable');
    html.classList.toggle('density-compact', density === 'compact');
    html.setAttribute('data-density', density);
    html.classList.toggle('reduce-motion', a.motion);
    html.setAttribute('data-theme', resolveTheme(a.theme));
    html.style.setProperty('--accent', a.accent);
  }

  try {
    apply();
  } catch (e) { /* best-effort: never block page load on this */ }

  try {
    window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', function () {
      if (read().theme === 'system') apply();
    });
  } catch (e) {}

  window.addEventListener('storage', function (e) {
    if (e.key === KEYS.density || e.key === KEYS.motion || e.key === KEYS.accent || e.key === KEYS.theme) apply();
  });

  window.PBIAppearance = { KEYS: KEYS, DEFAULTS: DEFAULTS, read: read, resolveTheme: resolveTheme, apply: apply };
})();
