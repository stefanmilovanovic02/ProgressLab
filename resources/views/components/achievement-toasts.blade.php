{{-- Global Achievement Toasts (include once per page) --}}
<script>
  window.ACHIEVEMENT_UNLOCKED = @json(session('unlocked', []));
</script>

<div id="achToastStack" class="ach-toaststack" aria-live="polite" aria-atomic="true"></div>

<script>
(function () {
  const stack = document.getElementById('achToastStack');
  if (!stack) return;

  const audio = new Audio('/sfx/achievement.mp3');

  function esc(s) {
    return String(s ?? '').replace(/[&<>"']/g, m => ({
      '&': '&amp;',
      '<': '&lt;',
      '>': '&gt;',
      '"': '&quot;',
      "'": '&#39;'
    }[m]));
  }

  function toast(item) {
    const image = item.image_path || '/images/achievements/default.png';

    const el = document.createElement('div');
    el.className = 'ach-toast';
    el.innerHTML = `
      <div class="ach-toast__media">
        <img src="${esc(image)}" alt="${esc(item.title)}">
      </div>
      <div class="ach-toast__content">
        <div class="ach-toast__title">${esc(item.title)}</div>
        <div class="ach-toast__desc">${esc(item.description)}</div>
        <div class="ach-toast__meta">
          ${esc(item.rarity ?? '')}
        </div>
      </div>
    `;
    stack.appendChild(el);

    audio.currentTime = 0;
    audio.play().catch(() => {});

    requestAnimationFrame(() => el.classList.add('is-in'));

    setTimeout(() => {
      el.classList.remove('is-in');
      setTimeout(() => el.remove(), 250);
    }, 4000);
  }

  (window.ACHIEVEMENT_UNLOCKED || []).forEach(toast);

  window.showAchievementToasts = function(items) {
    (items || []).forEach(toast);
  };
})();
</script>