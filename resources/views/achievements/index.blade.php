<!doctype html>
<html lang="en">
<head>
  <x-seo
    title="Fitness Achievements"
    description="Unlock achievements for workout, nutrition, hydration, consistency, and fitness milestones in ProgressLab."
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
        <h1 class="ach-h1">
          <span class="ach-h1__icon">🏆</span>
          Achievements
        </h1>
      </div>
      <p class="pl-pagehead__sub pl-pagehead__sub--center">
        Unlock milestones and show off your progress.
      </p>
      <div class="ach-submeta">{{ $unlockedCount }} of {{ $totalCount }} achievements unlocked</div>
    </div>

    {{-- Filters --}}
    <section class="pl-card ach-filters" aria-label="Achievement filters">
      <div class="ach-filters__grid">
        <div class="ach-filter">
          <span class="ach-label" id="rarityFilterLabel">Filter by Rarity</span>
          <div class="ach-rarity" data-rarity-picker data-value="all">
            <button
              id="rarityFilter"
              class="ach-rarity__trigger"
              type="button"
              aria-haspopup="listbox"
              aria-expanded="false"
              aria-labelledby="rarityFilterLabel rarityFilterValue"
              data-rarity-trigger
            >
              <span id="rarityFilterValue" data-rarity-value-label>All Rarities</span>
              <span class="ach-rarity__arrow" aria-hidden="true"></span>
            </button>
            <div class="ach-rarity__menu" role="listbox" aria-labelledby="rarityFilterLabel" data-rarity-menu hidden>
              <button type="button" role="option" aria-selected="true" data-rarity-option="all">All Rarities</button>
              <button type="button" role="option" aria-selected="false" data-rarity-option="common">Common</button>
              <button type="button" role="option" aria-selected="false" data-rarity-option="uncommon">Uncommon</button>
              <button type="button" role="option" aria-selected="false" data-rarity-option="rare">Rare</button>
              <button type="button" role="option" aria-selected="false" data-rarity-option="epic">Epic</button>
              <button type="button" role="option" aria-selected="false" data-rarity-option="legendary">Legendary</button>
            </div>
            <span class="ach-chevron">⌄</span>
          </div>
        </div>

        <div class="ach-filter">
          <label class="ach-label">Filter by Status</label>
          <div class="ach-seg" role="tablist" aria-label="Status filter">
            <button type="button" class="ach-segbtn is-active" data-status="all">All</button>
            <button type="button" class="ach-segbtn" data-status="unlocked">Unlocked</button>
            <button type="button" class="ach-segbtn" data-status="locked">Locked</button>
          </div>
        </div>

        <div class="ach-filter">
          <label class="ach-label" for="searchAch">Search Achievements</label>
          <div class="ach-searchwrap">
            <span class="ach-searchicon">🔍</span>
            <input id="searchAch" class="ach-search" type="text" placeholder="Search achievements..." autocomplete="off" />
          </div>
        </div>
      </div>
    </section>

    {{-- Grid --}}
    <section class="ach-grid" aria-label="Achievements list">
      @foreach($achievements as $a)
        <article
          class="ach-card {{ $a['unlocked'] ? 'is-unlocked' : 'is-locked' }} ach-{{ $a['rarity'] }}"
          data-rarity="{{ $a['rarity'] }}"
          data-status="{{ $a['unlocked'] ? 'unlocked' : 'locked' }}"
          data-title="{{ strtolower($a['title']) }}"
          data-desc="{{ strtolower($a['desc'] ?? '') }}"
        >
          <div class="ach-card__top">
            {{-- image (db) --}}
            <div class="ach-img">
              <img
                src="{{ $a['image_path'] }}"
                data-fallback="{{ $a['fallback_image_path'] }}"
                alt="{{ $a['title'] }}"
                loading="lazy"
                onerror="this.onerror=null;this.src=this.dataset.fallback"
              >
            </div>

            {{-- badge/icon --}}
            {{-- <div class="ach-badge" aria-hidden="true">{{ $a['icon'] }}</div> --}}
          </div>

          <h3 class="ach-card__title">{{ $a['title'] }}</h3>

          <div class="ach-card__meta">
            <span class="ach-cat-icon" aria-hidden="true">{{ $a['category_icon'] }}</span>
            <span class="ach-dot"></span>
            <span>{{ $a['category'] }}</span>
          </div>

          <p class="ach-card__desc">{{ $a['desc'] }}</p>

          <div class="ach-card__foot">
            <span class="ach-pill">
              {{ ucfirst($a['rarity']) }} ({{ $a['percent'] }}%)
            </span>
          </div>

          {{-- Hover tooltip --}}
          <div class="ach-tip">
            <div class="ach-tip__row"><strong>Status:</strong> {{ $a['unlocked'] ? 'Unlocked' : 'Locked' }}</div>
            <div class="ach-tip__row">
              <strong>Unlocked at:</strong>
              {{ $a['unlocked_at'] ? \Carbon\Carbon::parse($a['unlocked_at'])->format('M j, Y') : '—' }}
            </div>
            <div class="ach-tip__row"><strong>Users:</strong> {{ $a['unlocked_users'] }} ({{ $a['percent'] }}%)</div>
          </div>

          @if(!$a['unlocked'])
            <div class="ach-lockedOverlay" aria-hidden="true">
              <div class="ach-lock">🔒</div>
            </div>
          @endif
        </article>
      @endforeach
    </section>

  </main>

  <script>
    window.__unlocked = @json(session('unlocked', []));
  </script>

  {{-- UI-only filter logic --}}
  <script>
    (function () {
      const rarity = document.querySelector('[data-rarity-picker]');
      const rarityTrigger = rarity.querySelector('[data-rarity-trigger]');
      const rarityMenu = rarity.querySelector('[data-rarity-menu]');
      const rarityValueLabel = rarity.querySelector('[data-rarity-value-label]');
      const rarityOptions = Array.from(rarity.querySelectorAll('[data-rarity-option]'));
      const search = document.getElementById('searchAch');
      const segBtns = document.querySelectorAll('.ach-segbtn');
      const cards = Array.from(document.querySelectorAll('.ach-card'));

      let status = 'all';

      function apply() {
        const r = rarity.dataset.value;
        const q = (search.value || '').trim().toLowerCase();

        cards.forEach(card => {
          const matchesRarity = (r === 'all') || (card.dataset.rarity === r);
          const matchesStatus = (status === 'all') || (card.dataset.status === status);
          const matchesSearch = !q || card.dataset.title.includes(q) || card.dataset.desc.includes(q);

          card.style.display = (matchesRarity && matchesStatus && matchesSearch) ? '' : 'none';
        });
      }

      function setRarityOpen(open, focusSelected = false) {
        rarity.classList.toggle('is-open', open);
        rarityTrigger.setAttribute('aria-expanded', String(open));
        rarityMenu.hidden = !open;

        if (open && focusSelected) {
          (rarityOptions.find(option => option.getAttribute('aria-selected') === 'true') || rarityOptions[0]).focus();
        }
      }

      function chooseRarity(option) {
        rarity.dataset.value = option.dataset.rarityOption;
        rarityValueLabel.textContent = option.textContent.trim();
        rarityOptions.forEach(candidate => candidate.setAttribute('aria-selected', String(candidate === option)));
        setRarityOpen(false);
        rarityTrigger.focus();
        apply();
      }

      rarityTrigger.addEventListener('click', () => setRarityOpen(rarityMenu.hidden, !rarityMenu.hidden));
      rarityTrigger.addEventListener('keydown', event => {
        if (event.key === 'ArrowDown' || event.key === 'ArrowUp') {
          event.preventDefault();
          setRarityOpen(true, true);
        }
      });
      rarityOptions.forEach((option, index) => {
        option.addEventListener('click', () => chooseRarity(option));
        option.addEventListener('keydown', event => {
          if (event.key === 'Escape') {
            event.preventDefault();
            setRarityOpen(false);
            rarityTrigger.focus();
            return;
          }

          if (!['ArrowDown', 'ArrowUp', 'Home', 'End'].includes(event.key)) return;
          event.preventDefault();
          const nextIndex = event.key === 'Home'
            ? 0
            : event.key === 'End'
              ? rarityOptions.length - 1
              : (index + (event.key === 'ArrowDown' ? 1 : -1) + rarityOptions.length) % rarityOptions.length;
          rarityOptions[nextIndex].focus();
        });
      });
      document.addEventListener('pointerdown', event => {
        if (!rarity.contains(event.target)) setRarityOpen(false);
      });
      search.addEventListener('input', apply);

      segBtns.forEach(btn => {
        btn.addEventListener('click', () => {
          segBtns.forEach(b => b.classList.remove('is-active'));
          btn.classList.add('is-active');
          status = btn.dataset.status;
          apply();
        });
      });

      apply();
    })();
  </script>


<x-achievement-toasts />
<x-footer />
</body>
</html>
