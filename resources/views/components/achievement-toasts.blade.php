{{-- Global Achievement Toasts (include once per page) --}}
<script>
  window.ACHIEVEMENT_UNLOCKED = @json(session('unlocked', []));
</script>

<div id="achToastStack" class="ach-toaststack" aria-live="polite" aria-atomic="true"></div>

<script>
(function () {
  const stack = document.getElementById('achToastStack');
  if (!stack) return;

  // Use one audio instance for all toasts
  const audio = new Audio('/sfx/achievement.mp3');

  function esc(s){
    return String(s ?? '').replace(/[&<>"']/g, m => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[m]));
  }

  function toast(item){
    const el = document.createElement('div');
    el.className = 'ach-toast';
    el.innerHTML = 
      <div class="ach-toast__top">
        <div class="ach-toast__label">Achievement Unlocked!</div>
        <div class="ach-toast__rarity">${esc(item.rarity ?? '')}</div>
      </div>
      <div class="ach-toast__name">${esc(item.title)}</div>
      <div class="ach-toast__desc">${esc(item.description)}</div>
    ;
    stack.appendChild(el);

    // sound
    audio.currentTime = 0;
    audio.play().catch(()=>{});

    requestAnimationFrame(() => el.classList.add('is-in'));

    setTimeout(() => {
      el.classList.remove('is-in');
      setTimeout(() => el.remove(), 250);
    }, 3500);
  }

  // 1) Show any flash achievements (redirect flow)
  (window.ACHIEVEMENT_UNLOCKED  []).forEach(toast);

  // 2) Allow any page JS/AJAX to trigger toasts:
  window.showAchievementToasts = function(items){
    (items  []).forEach(toast);
  };
})();
</script>