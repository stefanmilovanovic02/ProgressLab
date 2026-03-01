<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Achievements • GymTracker</title>

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
          <label class="ach-label" for="rarityFilter">Filter by Rarity</label>
          <div class="ach-selectwrap">
            <select id="rarityFilter" class="ach-select">
              <option value="all">All Rarities</option>
              <option value="common">Common</option>
              <option value="uncommon">Uncommon</option>
              <option value="rare">Rare</option>
              <option value="epic">Epic</option>
              <option value="legendary">Legendary</option>
            </select>
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
          data-desc="{{ strtolower($a['desc']) }}"
        >
          <div class="ach-card__top">
            <div class="ach-badge" aria-hidden="true">{{ $a['icon'] }}</div>
          </div>

          <h3 class="ach-card__title">{{ $a['title'] }}</h3>

          <div class="ach-card__meta">
            <span class="ach-dot"></span>
            <span>{{ $a['category'] }}</span>
          </div>

          <p class="ach-card__desc">{{ $a['desc'] }}</p>

          <div class="ach-card__foot">
            <span class="ach-pill">
              {{ ucfirst($a['rarity']) }} ({{ $a['percent'] }}%)
            </span>
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

  {{-- UI-only filter logic --}}
  <script>
    (function () {
      const rarity = document.getElementById('rarityFilter');
      const search = document.getElementById('searchAch');
      const segBtns = document.querySelectorAll('.ach-segbtn');
      const cards = Array.from(document.querySelectorAll('.ach-card'));

      let status = 'all';

      function apply() {
        const r = rarity.value;
        const q = (search.value || '').trim().toLowerCase();

        cards.forEach(card => {
          const matchesRarity = (r === 'all') || (card.dataset.rarity === r);
          const matchesStatus = (status === 'all') || (card.dataset.status === status);
          const matchesSearch = !q || card.dataset.title.includes(q) || card.dataset.desc.includes(q);

          card.style.display = (matchesRarity && matchesStatus && matchesSearch) ? '' : 'none';
        });
      }

      rarity.addEventListener('change', apply);
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

  <script>
(function(){
  const audio = new Audio('/sfx/achievement.mp3');

  function toast(item){
    const el = document.createElement('div');
    el.className = 'ach-toast';
    el.innerHTML = 
      <div class="ach-toast__title">Achievement Unlocked!</div>
      <div class="ach-toast__name">${item.title}</div>
      <div class="ach-toast__desc">${item.description ?? ''}</div>
    ;
    document.body.appendChild(el);

    // play sound
    audio.currentTime = 0;
    audio.play().catch(()=>{});

    requestAnimationFrame(()=> el.classList.add('is-in'));
    setTimeout(()=>{
      el.classList.remove('is-in');
      setTimeout(()=> el.remove(), 250);
    }, 3500);
  }

  async function poll(){
    try{
      const res = await fetch("{{ route('achievements.notifications') }}", { headers: { 'Accept':'application/json' }});
      if(!res.ok) return;
      const data = await res.json();
      (data.items || []).forEach(toast);
    }catch(e){}
  }

  poll();
  setInterval(poll, 10000);
})();
</script>

</body>
</html>