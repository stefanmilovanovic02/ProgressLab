<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Friends • GymTracker</title>
  <link rel="stylesheet" href="{{ asset('css/auth.css') }}">
</head>
<body class="auth-body">

<x-navbar />

<main class="pl-container">

  <div class="pl-pagehead">
    <div class="pl-pageheadtitle pl-pageheadtitle--center">
      <h1 class="fr-h1">
        <span class="fr-h1icon">👥</span>
        Friends
      </h1>
    </div>
    <p class="pl-pageheadsub pl-pageheadsub--center">
      Track your friends' progress and achievements.
    </p>
    <div class="fr-submeta">{{ $onlineCount }} of {{ $friends->count() }} friends online</div>
  </div>

  <section class="fr-add pl-card" aria-label="Add Friend">
    <div class="fr-addleft">
      <div class="fr-addtitle">Add Friend</div>
      <div class="fr-addsub">Search by name, username or email</div>
    </div>

    <div class="fr-add__right">
      <div class="fr-searchwrap">
        <span class="fr-searchicon">🔍</span>
        <input id="frSearch" class="fr-search" placeholder="Search users..." autocomplete="off">
      </div>

      <div class="fr-results" id="frResults" hidden></div>
    </div>
  </section>

  @if($incoming->count())
  <section class="pl-card fr-req" aria-label="Friend requests">
    <div class="fr-req__title">Friend Requests</div>

    <div class="fr-req__list">
      @foreach($incoming as $r)
        <div class="fr-req__item">
          <div class="fr-req__left">
            <img class="fr-req__av"
              src="{{ $r->requester && $r->requester->avatar_path
                ? asset(\Illuminate\Support\Str::startsWith($r->requester->avatar_path, 'storage/') ? $r->requester->avatar_path : 'storage/'.$r->requester->avatar_path)
                : 'https://via.placeholder.com/40?text=U' }}"
              alt="">
            <div>
              <div class="fr-req__name">{{ $r->requester?->name }}</div>
              <div class="fr-req__user">{{ '@'.$r->requester?->username }}</div>
            </div>
          </div>

          <div class="fr-req__actions">
            <button class="fr-req__btn is-yes" type="button" data-accept="{{ $r->user_id }}">✅</button>
            <button class="fr-req__btn is-no" type="button" data-decline="{{ $r->user_id }}">❌</button>
          </div>
        </div>
      @endforeach
    </div>
  </section>
@endif

  <section class="fr-grid" aria-label="Friends list">
    @forelse($friends as $f)
      <button type="button" class="fr-card" data-open-friend="{{ $f->id }}">
        <div class="fr-avatarwrap">
          <img class="fr-avatar"
               src="{{ $f->avatar_path
                ? asset(\Illuminate\Support\Str::startsWith($f->avatar_path, 'storage/') ? $f->avatar_path : 'storage/'.$f->avatar_path)
                : 'https://via.placeholder.com/96?text=U' }}"
               alt="{{ $f->name }}">
          <span class="fr-statusdot is-{{ strtolower(str_replace(' ','', $f->status)) }}"></span>
        </div>

        <div class="fr-name">{{ $f->name }}</div>
        <div class="fr-status">{{ $f->status }}</div>

        <div class="fr-pill">
          <span class="fr-pillicon">🔥</span>
          <span class="fr-pillnum">{{ (int)$f->login_streak }}</span>
          <span class="fr-pilllabel">Login streak</span>
        </div>

        <div class="fr-foot">{{ $f->last_active_text ? 'Last seen: '.$f->last_active_text : '' }}</div>
      </button>
    @empty
      <div class="fr-empty pl-card">
        <div class="fr-emptytitle">No friends yet</div>
        <div class="fr-empty__sub">Use the search above to add your first friend.</div>
      </div>
    @endforelse
  </section>

</main>

{{-- Friend modal --}}
<div class="fr-modal" data-fr-modal>
  <div class="fr-modalbackdrop" data-fr-close></div>

  <div class="fr-modalpanel" role="dialog" aria-modal="true" aria-label="Friend details">
    <button class="fr-modalx" type="button" data-fr-close aria-label="Close">✕</button>

    <div class="fr-modalhero" data-fr-hero>
      <div class="fr-modalheroInner">
        <div class="fr-modalavatarWrap">
          <img class="fr-modalavatar" data-fr-avatar alt="">
          <span class="fr-modaldot" data-fr-dot></span>
        </div>

        <div>
          <div class="fr-modalname" data-fr-name>—</div>
          <div class="fr-modalbadge" data-fr-status>—</div>
          <div class="fr-modal__last" data-fr-last>Last active: —</div>
        </div>
      </div>
    </div>

    <div class="fr-modalsection">
      <div class="fr-secTitle">🏆 Quick Stats</div>
      <div class="fr-stats">
        <div class="fr-stat">
          <div class="fr-staticon">🏋️</div>
          <div class="fr-statnum" data-fr-workouts>—</div>
          <div class="fr-statlbl">Workouts Logged</div>
        </div>
        <div class="fr-stat">
          <div class="fr-staticon">📅</div>
          <div class="fr-statnum" data-fr-days>—</div>
          <div class="fr-statlbl">Days This Month</div>
        </div>
        <div class="fr-stat">
          <div class="fr-staticon">👥</div>
          <div class="fr-statnum" data-fr-friends>—</div>
          <div class="fr-statlbl">Friends</div>
        </div>
        <div class="fr-stat">
          <div class="fr-staticon">🗓️</div>
          <div class="fr-statnum" data-fr-joined>—</div>
          <div class="fr-statlbl">Joined</div>
        </div>
      </div>
    </div>

    <div class="fr-modalsection">
      <div class="fr-secTitle">🔥 Current Streaks</div>
      <div class="fr-streaks">
        <div class="fr-streak">
          <div class="fr-streakicon">🔥</div>
          <div class="fr-streaknum" data-fr-loginStreak>—</div>
          <div class="fr-streaklbl">Login Streak</div>
        </div>
      </div>
    </div>

    <div class="fr-modalsection">
      <div class="fr-secTitle">🏆 Achievements (<span data-fr-ach>0</span> unlocked)</div>
      <div class="fr-achNote">We’ll show the unlocked tiles here next.</div>
    </div>

  </div>
</div>

<script>
(() => {

  const token = "{{ csrf_token() }}";
  // -----------------------
  // Add friend search
  // -----------------------

  const input = document.getElementById('frSearch');
  const results = document.getElementById('frResults');
  let timer = null;
  async function doSearch(q) {

    const url = `{{ route('friends.search') }}?q=${encodeURIComponent(q)}`;
    const res = await fetch(url, { headers: { 'Accept': 'application/json' } });
    const contentType = res.headers.get('content-type') || '';

    if (!contentType.includes('application/json')) {
      console.error('Search did not return JSON. Status:', res.status);
      return { items: [] };
    }

    return await res.json();

  }

  function renderResults(items) {
    results.innerHTML = '';
    if (!items || items.length === 0) {
      results.hidden = true;
      return;
    }

    items.forEach(u => {
      const row = document.createElement('div');
      row.className = 'fr-result';
      const avatarHtml = u.avatar_url
        ? `<img class="fr-result__img" src="${u.avatar_url}" alt="">`
        : `<div class="fr-result__avatar">${(u.name || '?')[0].toUpperCase()}</div>`;
      row.innerHTML = `
        <div class="fr-result__left">
          ${avatarHtml}
          <div>
            <div class="fr-result__name">${u.name ?? ''}</div>
            <div class="fr-result__meta">@${u.username ?? ''}</div>
          </div>
        </div>
        <button type="button" class="fr-result__btn">Add</button>

      `;

      row.querySelector('.fr-result__btn').addEventListener('click', async () => {
        const r = await fetch(`{{ route('friends.request') }}`, {
          method: 'POST',
          headers: {
            'Accept': 'application/json',
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': token
          },

          body: JSON.stringify({ friend_id: u.id })

        });

        if (r.ok) {
          const b = row.querySelector('.fr-result__btn');
          b.textContent = 'Requested';
          b.disabled = true;
        } else {
          console.error('Request failed:', r.status, await r.text());
        }

      });

      results.appendChild(row);
    });

    results.hidden = false;

  }

  if (input && results) {
    input.addEventListener('input', () => {
      clearTimeout(timer);
      const q = input.value.trim();
      if (q.length < 2) {
        results.hidden = true;
        return;
      }

      timer = setTimeout(async () => {
        const data = await doSearch(q);
        renderResults(data.items || []);
      }, 250);

    });

    document.addEventListener('click', (e) => {
      if (!results.contains(e.target) && e.target !== input) {
        results.hidden = true;
      }

    });

  }

  // -----------------------
  // Accept / Decline requests
  // -----------------------

  document.addEventListener('click', async (e) => {
    const yes = e.target.closest('[data-accept]');
    const no  = e.target.closest('[data-decline]');
    if (!yes && !no) return;
    const requesterId = yes ? yes.dataset.accept : no.dataset.decline;
    const url = yes ? `{{ route('friends.accept') }}` : `{{ route('friends.decline') }}`;

    const res = await fetch(url, {
      method: 'POST',
      headers: {
        'Accept': 'application/json',
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': token
      },

      body: JSON.stringify({ requester_id: requesterId })

    });

    if (!res.ok) {
      console.error('Accept/Decline failed:', res.status, await res.text());
      return;
    }

    window.location.reload();
  });

  // -----------------------
  // Friend modal (summary)
  // -----------------------

  const modal = document.querySelector('[data-fr-modal]');
  const hero = document.querySelector('[data-fr-hero]');
  const closeBtns = document.querySelectorAll('[data-fr-close]');

  function openModal(){ modal?.classList.add('is-active'); }
  function closeModal(){ modal?.classList.remove('is-active'); }
  closeBtns.forEach(b => b.addEventListener('click', closeModal));


  async function loadFriend(userId){
    const res = await fetch(`{{ url('/friends') }}/${userId}/summary`, { headers: { 'Accept': 'application/json' } });
    if(!res.ok) return null;
    return await res.json();

  }

  function setHeroBackground(url){
    if(!hero) return;
    if(url){
      hero.style.backgroundImage = `linear-gradient(rgba(10,14,20,.65), rgba(10,14,20,.9)), url('${url}')`;
      hero.style.backgroundSize = 'cover';
      hero.style.backgroundPosition = 'center';
    }else{
      hero.style.backgroundImage = '';

    }

  }

  document.querySelectorAll('[data-open-friend]').forEach(btn => {
    btn.addEventListener('click', async () => {
     const userId = btn.dataset.openFriend;
      openModal();

      const data = await loadFriend(userId);
      if(!data) return;

      const u = data.user || {};
      const s = data.stats || {};
      const st = data.streaks || {};

      setHeroBackground(u.cover || null);

      const av = document.querySelector('[data-fr-avatar]');
      if (av) av.src = u.avatar || 'https://via.placeholder.com/96?text=U';

      document.querySelector('[data-fr-name]')?.textContent = u.name || '—';
      document.querySelector('[data-fr-status]')?.textContent = u.status || '—';
      document.querySelector('[data-fr-last]')?.textContent = `Last active: ${u.last_active || '—'}`;
      document.querySelector('[data-fr-workouts]')?.textContent = s.workouts_logged ?? '—';
      document.querySelector('[data-fr-days]')?.textContent = s.days_this_month ?? '—';
      document.querySelector('[data-fr-friends]')?.textContent = s.friends ?? '—';
      document.querySelector('[data-fr-joined]')?.textContent = s.joined ?? '—';
      document.querySelector('[data-fr-loginStreak]')?.textContent = `${st.login ?? 0} days`;
      document.querySelector('[data-fr-ach]')?.textContent = (data.recent_achievements || []).length;

    });

  });

})();

</script>
</body>
</html>