/* Small, non-invasive interaction polish for admin feature pages. */
(function(){
  'use strict';
  document.addEventListener('click', function(e){
    const el=e.target.closest('button,.btn,[type="button"],[type="submit"],.nav-item,.quick-action,.action-btn');
    if(!el || el.disabled || el.dataset.noRipple==='true') return;
    const r=el.getBoundingClientRect();
    const ripple=document.createElement('span');
    ripple.className='admin-ripple';
    const size=Math.max(r.width,r.height)*1.15;
    ripple.style.width=ripple.style.height=size+'px';
    ripple.style.left=(e.clientX-r.left-size/2)+'px';
    ripple.style.top=(e.clientY-r.top-size/2)+'px';
    el.appendChild(ripple);
    window.setTimeout(()=>ripple.remove(),420);
  }, {passive:true});

  const style=document.createElement('style');
  style.textContent='.admin-ripple{position:absolute;border-radius:50%;pointer-events:none;background:rgba(255,255,255,.32);transform:scale(0);animation:adminRipple .4s ease-out;z-index:10}@keyframes adminRipple{to{transform:scale(1);opacity:0}}button,.btn,.nav-item,.quick-action,.action-btn{position:relative;overflow:hidden}';
  document.head.appendChild(style);
})();
