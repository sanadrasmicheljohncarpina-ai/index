/**
 * faculty/live_updates.js
 *
 * Shared real-time layer for the Faculty and Staff dashboards.
 *
 * The dashboards already poll notifications_api.php every 2 seconds. After each
 * poll they dispatch a "portal:notifications" event. This file listens to it and
 * keeps the page itself current, so a new evaluation shows up on its own:
 *
 *   - "Evaluations Received" page  -> list, averages, category bars refresh in place
 *   - Dashboard                    -> schedule card, score chip and stat cards refresh
 *
 * A refresh is triggered by EITHER of these, so evaluations written straight into
 * evaluation_tracker by another portal are picked up even before any notification
 * row exists for them:
 *   1. api.received.stamp changed  (count / newest id / newest submit time)
 *   2. a new unread notification of a type that affects the current page
 *
 * Safety rules: never refreshes while the user is typing in a form, while a modal
 * is open, or on pages that are not read-only (Evaluate Others, Settings, ...).
 * The Evaluations Received filters (sort, "With comments only") are preserved.
 * Evaluator identity is never read or shown here.
 */
(function () {
    'use strict';
    if (window.PortalLive) return;

    var params   = new URLSearchParams(window.location.search);
    var page     = params.get('page') || 'dashboard';
    var isReport = params.get('report') === '1';

    // What may be refreshed on each page, and which notification types should trigger it.
    var PAGES = {
        my_results: {
            sections: ['.rr-shell'],
            types: ['evaluation_received']
        },
        dashboard: {
            sections: ['.dashboard-intro', '.evaluation-schedule-card', '.welcome-bar', '.stats-grid'],
            types: ['evaluation_received', 'evaluation_schedule', 'academic_period', 'teaching_assignment', 'designation_update']
        }
    };
    var plan = (!isReport && PAGES[page]) ? PAGES[page] : null;

    var lastStamp   = null;
    var pending     = false;
    var busy        = false;
    var retryTimer  = null;
    var MIN_GAP_MS  = 1500;
    var lastRunAt   = 0;

    // ── styles (flash highlight + screen-reader live region) ────────────────
    var css = document.createElement('style');
    css.textContent =
        '@keyframes portalLiveFlash{0%{box-shadow:0 0 0 0 color-mix(in srgb,var(--teal,#0d9488) 55%,transparent);}' +
        '100%{box-shadow:0 0 0 14px transparent;}}' +
        '.live-flash{animation:portalLiveFlash 1.2s ease-out 1;}' +
        '.rr-item.rr-new{border-color:var(--teal,#0d9488);animation:portalLiveFlash 1.6s ease-out 2;}' +
        '.rr-item.rr-new .rr-eyebrow::after{content:"NEW";margin-left:8px;font:800 10px "DM Sans",sans-serif;' +
        'letter-spacing:.08em;padding:2px 7px;border-radius:10px;background:var(--teal,#0d9488);color:#fff;}' +
        '#portalLiveStatus{position:absolute;width:1px;height:1px;overflow:hidden;clip:rect(0 0 0 0);white-space:nowrap;}' +
        '@media(prefers-reduced-motion:reduce){.live-flash,.rr-item.rr-new{animation:none;}}';
    document.head.appendChild(css);

    var live = document.createElement('div');
    live.id = 'portalLiveStatus';
    live.setAttribute('role', 'status');
    live.setAttribute('aria-live', 'polite');
    document.body.appendChild(live);

    // ── helpers ─────────────────────────────────────────────────────────────
    function userIsBusy() {
        var ae = document.activeElement;
        if (ae && /^(INPUT|TEXTAREA|SELECT)$/.test(ae.tagName) && !ae.closest('.rr-shell')) return true;
        // Same "something is open" test the dashboards use for their dialogs.
        return !!document.querySelector('[class*="modal"].open, [class*="overlay"].open, [class*="modal"].show');
    }

    function currentArgs() {
        var set = {};
        document.querySelectorAll('#rrList .rr-item[data-arg]').forEach(function (el) {
            set[el.getAttribute('data-arg')] = true;
        });
        return set;
    }

    function capturePrefs() {
        var sort = document.getElementById('rrSort');
        var only = document.getElementById('rrOnlyComments');
        return { sort: sort ? sort.value : null, only: only ? only.checked : false };
    }

    function restorePrefs(p) {
        var sort = document.getElementById('rrSort');
        var only = document.getElementById('rrOnlyComments');
        if (sort && p.sort) sort.value = p.sort;
        if (only) only.checked = !!p.only;
        if (typeof window.rrApply === 'function') window.rrApply();
    }

    // ── the soft refresh ────────────────────────────────────────────────────
    function softRefresh() {
        if (!plan || busy) return;
        if (userIsBusy() || document.hidden) {           // try again shortly, do not lose the update
            pending = true;
            clearTimeout(retryTimer);
            retryTimer = setTimeout(function () { if (pending) softRefresh(); }, 1500);
            return;
        }
        if (Date.now() - lastRunAt < MIN_GAP_MS) {
            pending = true;
            clearTimeout(retryTimer);
            retryTimer = setTimeout(function () { if (pending) softRefresh(); }, MIN_GAP_MS);
            return;
        }

        busy = true; pending = false; lastRunAt = Date.now();
        fetch(window.location.pathname + window.location.search, {
            method: 'GET',
            credentials: 'same-origin',
            cache: 'no-store',
            headers: { 'X-Requested-With': 'live-refresh' }
        }).then(function (res) {
            if (!res.ok) throw new Error('HTTP ' + res.status);
            // A redirect to the login page means the session ended - leave the page alone.
            if (res.redirected && /login/i.test(res.url)) throw new Error('session ended');
            return res.text();
        }).then(function (html) {
            var doc = new DOMParser().parseFromString(html, 'text/html');
            var before = currentArgs();
            var prefs  = capturePrefs();
            var changedRr = false;

            plan.sections.forEach(function (sel) {
                var oldEl = document.querySelector(sel);
                var newEl = doc.querySelector(sel);
                if (!oldEl || !newEl) return;
                if (oldEl.outerHTML === newEl.outerHTML) return;
                var adopted = document.importNode(newEl, true);
                oldEl.replaceWith(adopted);
                if (sel === '.rr-shell') changedRr = true;
                else adopted.classList.add('live-flash');
            });

            if (changedRr) {
                restorePrefs(prefs);
                var added = 0;
                document.querySelectorAll('#rrList .rr-item[data-arg]').forEach(function (el) {
                    if (!before[el.getAttribute('data-arg')]) { el.classList.add('rr-new'); added++; }
                });
                if (added) live.textContent = added === 1
                    ? 'A new evaluation was received.'
                    : added + ' new evaluations were received.';
            }
        }).catch(function (err) {
            console.debug('Live refresh:', err.message);
        }).then(function () { busy = false; });
    }

    // ── react to every notification poll ────────────────────────────────────
    document.addEventListener('portal:notifications', function (ev) {
        var d = (ev && ev.detail) || {};
        var data = d.data || {};
        var stamp = data.received && data.received.stamp != null ? String(data.received.stamp) : null;
        var needs = false;

        if (stamp !== null) {
            if (lastStamp !== null && stamp !== lastStamp) needs = true;
            lastStamp = stamp;
        }
        if (plan && Array.isArray(d.newUnread)) {
            needs = needs || d.newUnread.some(function (n) { return plan.types.indexOf(n.type) !== -1; });
        }
        if (needs) softRefresh();
    });

    // Pick up anything that happened while the tab was in the background.
    document.addEventListener('visibilitychange', function () {
        if (!document.hidden && pending) softRefresh();
    });
    // Let a deferred refresh run as soon as the user leaves a field.
    document.addEventListener('focusout', function () {
        if (pending) setTimeout(softRefresh, 250);
    });

    window.PortalLive = { refresh: softRefresh };
})();
