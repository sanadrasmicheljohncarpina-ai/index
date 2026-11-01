<?php
/**
 * Shared evaluation countdown widget.
 *
 * Drop it into ANY dashboard (student, teacher, dean, staff, principal, admin):
 *
 *     require_once dirname(__DIR__) . '/shared/evaluation_countdown.php';
 *     ...
 *     <?= ss_countdown_html($mysqli) ?>
 *
 * - Obeys the "Show a countdown" switch in Admin > Settings > System & Period.
 * - Counts down to when evaluation OPENS, then to when it CLOSES, using Asia/Manila
 *   time from the server (a wrong clock on the visitor's device does not matter).
 * - Prints nothing in maintenance mode, when "Keep open"/"Keep closed" overrides the
 *   schedule, or when there is no valid upcoming/running schedule.
 */
require_once __DIR__ . '/system_settings_service.php';

/** Pure renderer (takes the settings array) so it is easy to reuse and test. */
function ss_countdown_render(array $sys, bool $ignoreToggle = false): string {
    if (!$ignoreToggle && empty($sys['rule_countdown'])) return '';
    if (!empty($sys['maintenance'])) return '';

    $state = ss_schedule_state($sys);
    if (($state['mode'] ?? '') !== 'schedule') return '';
    if (!in_array($state['reason'] ?? '', ['before_start', 'scheduled_open'], true)) return '';

    $nowMs   = (int)($state['now']->getTimestamp() * 1000);
    $startMs = (int)($state['start']->getTimestamp() * 1000);
    $endMs   = (int)($state['end']->getTimestamp() * 1000);

    $before = $state['reason'] === 'before_start';
    $label  = $before ? 'Evaluation opens in' : 'Evaluation closes in';
    $target = $before ? $startMs : $endMs;
    $secs   = max(0, (int)floor(($target - $nowMs) / 1000));
    $d = intdiv($secs, 86400); $h = intdiv($secs % 86400, 3600);
    $m = intdiv($secs % 3600, 60); $s = $secs % 60;
    $p = static fn(int $n): string => str_pad((string)$n, 2, '0', STR_PAD_LEFT);

    static $assetsPrinted = false;
    $out = '';
    if (!$assetsPrinted) {
        $assetsPrinted = true;
        $out .= <<<'HTML'
<style>
.ssc{display:flex;align-items:center;gap:14px;flex-wrap:wrap;padding:12px 16px;border:1px solid #D8E5F4;border-radius:12px;background:#fff;font-family:Inter,system-ui,sans-serif;color:#0B1F3A}
.ssc[data-phase="open"]{border-color:#B9E4D3;background:#F1FBF6}
.ssc[data-phase="soon"]{border-color:#CFE0F7;background:#F4F8FF}
.ssc[data-phase="closed"]{border-color:#F1B7C0;background:#FFF0F2}
.ssc-label{font-size:12px;font-weight:800;letter-spacing:.05em;text-transform:uppercase;color:#5C6B8A}
.ssc-units{display:flex;gap:8px}
.ssc-u{min-width:52px;text-align:center;padding:6px 8px;border-radius:9px;background:#fff;border:1px solid #D8E5F4}
.ssc-u b{display:block;font-size:20px;font-weight:800;line-height:1.1;font-variant-numeric:tabular-nums}
.ssc-u span{font-size:10px;font-weight:700;color:#67819E;text-transform:uppercase;letter-spacing:.05em}
.ssc[data-phase="closed"] .ssc-units{display:none}
.ssc-done{display:none;font-size:14px;font-weight:700;color:#A62A42}
.ssc[data-phase="closed"] .ssc-done{display:block}
html.dark-theme .ssc,html[data-theme="dark"] .ssc,body.dark-theme .ssc{background:#121f33;border-color:#22344d;color:#E7EEF8}
html.dark-theme .ssc-label,html[data-theme="dark"] .ssc-label,body.dark-theme .ssc-label{color:#8BA0BD}
html.dark-theme .ssc-u,html[data-theme="dark"] .ssc-u,body.dark-theme .ssc-u{background:#16243a;border-color:#25374f}
html.dark-theme .ssc-u span,html[data-theme="dark"] .ssc-u span,body.dark-theme .ssc-u span{color:#8BA0BD}
html.dark-theme .ssc-done,html[data-theme="dark"] .ssc-done,body.dark-theme .ssc-done{color:#F28B9B}
</style>
<script>
(function(){
  if (window.__sscInit) return; window.__sscInit = true;
  function pad(n){ return (n<10?'0':'')+n; }
  function tick(){
    document.querySelectorAll('.ssc').forEach(function(el){
      var off=Number(el.dataset.offset)||0, now=Date.now()+off;
      var start=Number(el.dataset.start), end=Number(el.dataset.end);
      var phase, target, label;
      if (now < start){ phase='soon'; target=start; label='Evaluation opens in'; }
      else if (now < end){ phase='open'; target=end; label='Evaluation closes in'; }
      else { phase='closed'; target=end; label='Evaluation has closed'; }
      var secs=Math.max(0, Math.floor((target-now)/1000));
      var u={d:Math.floor(secs/86400),h:Math.floor(secs%86400/3600),m:Math.floor(secs%3600/60),s:secs%60};
      el.dataset.phase=phase;
      el.querySelector('.ssc-label').textContent=label;
      ['d','h','m','s'].forEach(function(k){ var n=el.querySelector('[data-u="'+k+'"]'); if(n) n.textContent=k==='d'?u[k]:pad(u[k]); });
    });
  }
  tick(); setInterval(tick, 1000);
})();
</script>
HTML;
    }
    $out .= '<div class="ssc" role="timer" aria-live="off" data-phase="' . ($before ? 'soon' : 'open') . '"'
          . ' data-offset="0" data-start="' . $startMs . '" data-end="' . $endMs . '">'
          . '<span class="ssc-label">' . htmlspecialchars($label, ENT_QUOTES, 'UTF-8') . '</span>'
          . '<div class="ssc-units">'
          . '<div class="ssc-u"><b data-u="d">' . $d . '</b><span>days</span></div>'
          . '<div class="ssc-u"><b data-u="h">' . $p($h) . '</b><span>hrs</span></div>'
          . '<div class="ssc-u"><b data-u="m">' . $p($m) . '</b><span>min</span></div>'
          . '<div class="ssc-u"><b data-u="s">' . $p($s) . '</b><span>sec</span></div>'
          . '</div><span class="ssc-done">The evaluation window has ended.</span></div>';
    // Sync the browser clock to the server clock so a wrong device clock cannot skew the count.
    $out .= '<script>(function(){var s=' . $nowMs . ',o=s-Date.now();document.querySelectorAll(".ssc:not([data-synced])").forEach(function(e){e.dataset.offset=o;e.dataset.synced="1";});})();</script>';
    return $out;
}

function ss_countdown_html(mysqli $mysqli, bool $ignoreToggle = false): string {
    return ss_countdown_render(ss_raw($mysqli), $ignoreToggle);
}
