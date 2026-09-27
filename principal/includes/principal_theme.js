(function(){
  'use strict';

  var STORAGE_KEY = 'pbi_theme';
  var THEME_CSS = 'includes/principal_theme.css?v=20260927.3';
  var DARK_REPAIRS_CSS = 'includes/principal_dark_repairs.css?v=20260927.3';

  function savedTheme(){
    try { return localStorage.getItem(STORAGE_KEY) === 'dark' ? 'dark' : 'light'; }
    catch (e) { return 'light'; }
  }

  function applyTheme(mode){
    var normalized = mode === 'dark' ? 'dark' : 'light';
    var dark = normalized === 'dark';
    var body = document.body;
    var html = document.documentElement;

    if (body) {
      body.classList.toggle('light-theme', !dark);
      body.classList.toggle('dark-theme', dark);
    }
    html.classList.toggle('principal-theme-dark', dark);
    html.classList.remove('principal-theme-dark-pending');

    var value = document.getElementById('appearanceVal');
    if (value) value.textContent = dark ? 'Dark' : 'Light';

    var lightBtn = document.getElementById('appearanceLightBtn');
    var darkBtn = document.getElementById('appearanceDarkBtn');
    if (lightBtn) lightBtn.classList.toggle('active', !dark);
    if (darkBtn) darkBtn.classList.toggle('active', dark);
  }

  window.setPrincipalAppearance = function(mode){
    var normalized = mode === 'dark' ? 'dark' : 'light';
    try { localStorage.setItem(STORAGE_KEY, normalized); } catch (e) {}
    applyTheme(normalized);
  };

  function addFinalCascadeStylesheet(){
    if (!document.querySelector('link[data-principal-theme-final="1"]')) {
      var link = document.createElement('link');
      link.rel = 'stylesheet';
      link.href = THEME_CSS;
      link.setAttribute('data-principal-theme-final', '1');
      document.head.appendChild(link);
    }

    /* Append the repair layer at the very end of <body>. Several Principal
       pages contain legacy light-mode <style> blocks after </html>; placing
       this stylesheet at body-end makes the dark cascade win over those
       late rules as well. */
    if (!document.querySelector('link[data-principal-dark-repairs="1"]')) {
      var repairLink = document.createElement('link');
      repairLink.rel = 'stylesheet';
      repairLink.href = DARK_REPAIRS_CSS;
      repairLink.setAttribute('data-principal-dark-repairs', '1');
      (document.body || document.documentElement).appendChild(repairLink);
    }
  }

  document.addEventListener('DOMContentLoaded', function(){
    applyTheme(savedTheme());
    addFinalCascadeStylesheet();
  });
})();
