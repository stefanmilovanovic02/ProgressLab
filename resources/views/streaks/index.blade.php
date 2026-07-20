<!doctype html>
<html lang="en">
<head>
  <x-seo
    title="Fitness Streaks"
    description="Review your login, nutrition, hydration, and workout consistency streaks in ProgressLab."
    robots="noindex, nofollow, noarchive"
  />

  <link rel="stylesheet" href="{{ asset('css/auth.css') }}">
</head>
<body class="auth-body">

  <x-navbar />

  <main class="pl-container">

    {{-- Page Header --}}
    <div class="pl-pagehead">
      <div class="pl-pagehead__title pl-pagehead__title--center">
        <div class="st-headicon" aria-hidden="true">🔥</div>
        <h1>Your Streaks</h1>
      </div>

      <p class="pl-pagehead__sub pl-pagehead__sub--center">
        Keep the fire alive by logging daily progress.
      </p>
    </div>

    {{-- Cards Grid --}}
    <section class="st-grid" aria-label="Streaks" data-streak-grid data-user-id="{{ auth()->id() }}">
      @foreach($streaks as $s)
        <article
          class="st-card"
          data-accent="{{ $s['accent'] }}"
          data-flame-tier="{{ $s['flame_tier'] }}"
          data-flame-rank="{{ $s['flame_rank'] }}"
          data-streak-key="{{ $s['key'] }}"
          data-streak-title="{{ $s['title'] }}"
          data-streak-days="{{ $s['days'] }}"
          data-streak-milestone="{{ $s['milestone'] }}"
          style="--st-index: {{ $loop->index }}; --st-upgrade-hue: {{ $s['flame_rank'] > 0 ? 300 - ((($s['flame_rank'] - 1) * 28) % 140) : 320 }};"
        >
          <div class="st-card__icon" aria-hidden="true">{{ $s['icon'] }}</div>

          <div class="st-card__row">
            <span class="st-flame" aria-hidden="true"><span class="st-flame__aura"></span></span>
            <span class="st-days">{{ $s['days'] }}</span>
            <span class="st-dayslabel">Days</span>
          </div>

          <div class="st-title">{{ $s['title'] }}</div>
        </article>
      @endforeach
    </section>

    {{-- Big Motivational Card --}}
    <section class="st-big" aria-label="Motivation">
      <div class="st-big__flames" aria-hidden="true">🔥 🔥 🔥</div>
      <div class="st-big__title">You're on Fire!</div>
      <div class="st-big__sub">
        {{ $dailyMessage }}
      </div>
    </section>

    <div class="st-milestone" data-streak-milestone-modal hidden>
      <button class="st-milestone__backdrop" type="button" aria-label="Close milestone celebration" data-streak-milestone-close></button>
      <section class="st-milestone__dialog" role="dialog" aria-modal="true" aria-labelledby="stMilestoneTitle">
        <button class="st-milestone__close" type="button" aria-label="Close" data-streak-milestone-close>✕</button>
        <span class="st-milestone__eyebrow">Flame upgraded</span>
        <div class="st-milestone__visual" aria-hidden="true">
          <span class="st-flame"><span class="st-flame__aura"></span></span>
        </div>
        <h2 id="stMilestoneTitle" data-streak-milestone-title>Milestone unlocked</h2>
        <div class="st-milestone__tier" data-streak-milestone-tier></div>
        <p data-streak-milestone-message></p>
        <button class="pl-btn pl-btn--light st-milestone__continue" type="button" data-streak-milestone-continue>Keep the fire alive</button>
      </section>
    </div>

  </main>

<script>
(() => {
  const grid = document.querySelector('[data-streak-grid]');
  const modal = document.querySelector('[data-streak-milestone-modal]');
  if (!grid || !modal) return;

  const cards = [...grid.querySelectorAll('[data-streak-key]')];
  const title = modal.querySelector('[data-streak-milestone-title]');
  const tier = modal.querySelector('[data-streak-milestone-tier]');
  const message = modal.querySelector('[data-streak-milestone-message]');
  const continueButton = modal.querySelector('[data-streak-milestone-continue]');
  const tierNames = {
    ignited: 'Ignited Flame',
    blazing: 'Blazing Flame',
    inferno: 'Purple Inferno',
    cosmic: 'Cosmic Flame',
    legendary: 'Legendary Flame',
  };
  const queue = [];
  let active = null;

  function storageKey(card) {
    return `progresslab.streak-milestone.${grid.dataset.userId}.${card.dataset.streakKey}`;
  }

  function readState(card) {
    try {
      return JSON.parse(localStorage.getItem(storageKey(card))) || { lastDays: 0, milestone: 0 };
    } catch (error) {
      return { lastDays: 0, milestone: 0 };
    }
  }

  function saveState(card, state) {
    try {
      localStorage.setItem(storageKey(card), JSON.stringify(state));
    } catch (error) {
      // The celebration still works when private browsing blocks storage.
    }
  }

  cards.forEach(card => {
    const days = Number(card.dataset.streakDays || 0);
    const milestone = Number(card.dataset.streakMilestone || 0);
    const state = readState(card);

    if (days < Number(state.lastDays || 0)) state.milestone = 0;
    state.lastDays = days;
    saveState(card, state);

    if (milestone > 0 && milestone !== Number(state.milestone || 0)) {
      queue.push({ card, milestone, state });
    }
  });

  function showNext() {
    active = queue.shift() || null;
    if (!active) {
      modal.hidden = true;
      document.body.classList.remove('st-milestone-open');
      return;
    }

    const { card, milestone } = active;
    const flameTier = card.dataset.flameTier;
    const accent = getComputedStyle(card).getPropertyValue('--st-accent-rgb').trim();
    const upgradeHue = getComputedStyle(card).getPropertyValue('--st-upgrade-hue').trim();

    modal.dataset.accent = card.dataset.accent;
    modal.dataset.flameTier = flameTier;
    modal.style.setProperty('--st-accent-rgb', accent);
    modal.style.setProperty('--st-upgrade-hue', upgradeHue);
    title.textContent = `${milestone}-Day ${card.dataset.streakTitle}`;
    tier.textContent = tierNames[flameTier] || 'Upgraded Flame';
    message.textContent = milestone >= 100
      ? `You kept this streak alive for ${milestone} days. Your flame has reached a new rank.`
      : `You reached ${milestone} consistent days. Your streak flame just evolved.`;
    continueButton.textContent = queue.length ? 'Next upgrade' : 'Keep the fire alive';

    modal.hidden = false;
    document.body.classList.add('st-milestone-open');
    continueButton.focus();
  }

  function dismiss() {
    if (active) {
      active.state.milestone = active.milestone;
      active.state.lastDays = Number(active.card.dataset.streakDays || 0);
      saveState(active.card, active.state);
    }
    showNext();
  }

  modal.querySelectorAll('[data-streak-milestone-close]').forEach(button => button.addEventListener('click', dismiss));
  continueButton.addEventListener('click', dismiss);
  document.addEventListener('keydown', event => {
    if (event.key === 'Escape' && !modal.hidden) dismiss();
  });

  showNext();
})();
</script>
<x-achievement-toasts />
<x-footer />
</body>
</html>
