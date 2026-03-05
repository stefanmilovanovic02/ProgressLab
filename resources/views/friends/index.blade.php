<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Friends • ProgressLab</title>

    <link rel="stylesheet" href="{{ asset('css/auth.css') }}">
    <meta name="csrf-token" content="{{ csrf_token() }}">
</head>

<body class="fr-body">

<x-navbar />

<div class="fr-wrap">

    <!-- Header -->
    <div class="fr-head">
        <h1 class="fr-h1">
            <span class="fr-h1__icon">👥</span>
            Friends
        </h1>
        <p class="fr-sub">
            Track your friends' progress and achievements.
        </p>
        <div class="fr-meta">
            {{ $friendsCount }} friend{{ $friendsCount == 1 ? '' : 's' }}
        </div>
    </div>

    <!-- Main Card -->
    <div class="fr-card">
        <div class="fr-card__grid">

            <!-- Left side -->
            <div>
                <h5 class="fr-card__title">Add Friend</h5>
                <p class="fr-card__hint">
                    Search by name, username or email
                </p>
            </div>

            <!-- Right side -->
            <div class="fr-search">
                <div class="fr-search__field">
                    <span class="fr-search__ic">🔎</span>
                    <input
                        id="friendSearch"
                        type="text"
                        class="fr-search__input"
                        placeholder="Search users..."
                        autocomplete="off">
                </div>

                <div class="fr-search__ghost"></div>
            </div>

        </div>

        <!-- Search Results -->
        <div id="searchResults" class="fr-results"></div>

        {{-- Pending sent (you sent requests) --}}
        @if($pendingSent->count())
            <div style="margin-top:16px;">
                <div class="fr-card__title" style="margin-bottom:8px;">Pending requests</div>

                <div class="fr-results">
                    @foreach($pendingSent as $req)
                        @php $u = $req->receiver; @endphp
                        <div class="fr-result">
                            <div class="fr-result__left" style="display:flex; gap:10px; align-items:center;">
                                <img
                                    src="{{ $u->avatar_path ? asset($u->avatar_path) : asset('images/default-avatar.png') }}"
                                    alt="avatar"
                                    style="width:34px;height:34px;border-radius:999px;object-fit:cover;border:1px solid rgba(255,255,255,.10);"
                                >
                                <div style="min-width:0;">
                                    <p class="fr-result__name">
                                        {{ $u->name }}
                                        <span style="opacity:.6">(<span>@</span>{{ $u->username }})</span>
                                    </p>
                                    <div class="fr-result__sub">{{ $u->email }}</div>
                                </div>
                            </div>

                            <button class="fr-btn fr-btn--pending" disabled>Pending</button>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif

        {{-- Incoming requests (accept/decline) --}}
        @if($incomingRequests->count())
            <div style="margin-top:16px;">
                <div class="fr-card__title" style="margin-bottom:8px;">Friend requests</div>

                <div class="fr-results" id="incomingList">
                    @foreach($incomingRequests as $req)
                        @php $u = $req->sender; @endphp
                        <div class="fr-result" data-req-id="{{ $req->id }}">
                            <div class="fr-result__left" style="display:flex; gap:10px; align-items:center;">
                                <img
                                    src="{{ $u->avatar_path ? asset($u->avatar_path) : asset('images/default-avatar.png') }}"
                                    alt="avatar"
                                    style="width:34px;height:34px;border-radius:999px;object-fit:cover;border:1px solid rgba(255,255,255,.10);"
                                >
                                <div style="min-width:0;">
                                    <p class="fr-result__name">
                                        {{ $u->name }}
                                        <span style="opacity:.6">(<span>@</span>{{ $u->username }})</span>
                                    </p>
                                    <div class="fr-result__sub">{{ $u->email }}</div>
                                </div>
                            </div>

                            <div style="display:flex; gap:8px; flex-wrap:wrap; justify-content:flex-end;">
                                <button class="fr-btn fr-btn--add js-accept" data-req="{{ $req->id }}">Accept</button>
                                <button class="fr-btn js-decline" data-req="{{ $req->id }}">Decline</button>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif

    </div>
</div>

{{-- Friends Grid --}}
    @if(isset($friendsCards) && count($friendsCards))
    <div class="fr-grid">
        @foreach($friendsCards as $f)
        <div class="fr-fcard" data-friend-id="{{ $f['id'] }}">
            <div class="fr-fcard__avatarWrap">
            <img class="fr-fcard__avatar" src="{{ $f['avatar_url'] }}" alt="avatar">
            <span class="fr-fcard__dot fr-dot--{{ $f['dot'] }}"></span>
            </div>

            <h3 class="fr-fcard__name">{{ $f['name'] }}</h3>
            <div class="fr-fcard__status">{{ $f['status'] }}</div>

            <div class="fr-fcard__pill">
            <span aria-hidden="true">🔥</span>
            <strong>{{ $f['streak'] }}</strong>
            <span>Login streak</span>
            </div>

            <div class="fr-fcard__last">{{ $f['last_seen'] }}</div>
        </div>
        @endforeach
    </div>
    @endif


    <div class="fr-modal" id="friendModal" aria-hidden="true">
  <div class="fr-modal__backdrop" data-close-modal></div>

  <div class="fr-modal__panel" role="dialog" aria-modal="true">
    <div class="fr-modal__top">
      <div class="fr-modal__cover" id="fmCover" style="display:none;"></div>

      <button class="fr-modal__close" type="button" data-close-modal>✕</button>

      <div class="fr-modal__head">
        <div class="fr-modal__avatarWrap">
          <img id="fmAvatar" class="fr-modal__avatar" src="{{ asset('images/default-avatar.png') }}" alt="avatar">
          <span id="fmDot" class="fr-modal__dot fr-dot--offline"></span>
        </div>

        <div>
          <h2 id="fmName" class="fr-modal__name">Friend Name</h2>

          <div class="fr-modal__meta">
            <span class="fr-pill" id="fmStatus">🟢 Online</span>
            <span class="fr-pill" id="fmLast">Last active: —</span>
          </div>

          <div class="fr-modal__meta" style="margin-top:8px;">
            <span class="fr-pill" id="fmUser">@username</span>
            <span class="fr-pill" id="fmEmail">email</span>
          </div>
        </div>
      </div>
    </div>

    <div class="fr-section">
      <div class="fr-section__title">🏆 Quick Stats</div>
      <div class="fr-cards4">
        <div class="fr-mini">
          <div class="fr-mini__icon">🏋️</div>
          <div class="fr-mini__value" id="qsWorkouts">0</div>
          <div class="fr-mini__label">Workouts Logged</div>
        </div>
        <div class="fr-mini">
          <div class="fr-mini__icon">🗓️</div>
          <div class="fr-mini__value" id="qsDays">0</div>
          <div class="fr-mini__label">Days This Month</div>
        </div>
        <div class="fr-mini">
          <div class="fr-mini__icon">👥</div>
          <div class="fr-mini__value" id="qsFriends">0</div>
          <div class="fr-mini__label">Friends</div>
        </div>
        <div class="fr-mini">
          <div class="fr-mini__icon">📅</div>
          <div class="fr-mini__value" id="qsJoined">—</div>
          <div class="fr-mini__label">Joined</div>
        </div>
      </div>
    </div>

    <div class="fr-section">
      <div class="fr-section__title">🔥 Current Streaks</div>
      <div class="fr-cards3" id="streakWrap"></div>
    </div>

    <div class="fr-section">
      <div class="fr-section__title">🏅 Achievements <span style="opacity:.6; font-weight:800;" id="achCount"></span></div>
      <div class="fr-achRow" id="achWrap"></div>
    </div>
  </div>
</div>

<script>
(() => {
    const input = document.getElementById('friendSearch');
    const results = document.getElementById('searchResults');
    const csrf = document.querySelector('meta[name="csrf-token"]').content;

    const DEFAULT_AVATAR = "{{ asset('images/default-avatar.png') }}";

    let timeout = null;

    function escapeHtml(str){
        return String(str ?? '').replace(/[&<>"']/g, s => ({
            '&':'&amp;',
            '<':'&lt;',
            '>':'&gt;',
            '"':'&quot;',
            "'":'&#039;'
        }[s]));
    }

    function avatarHTML(url){
        const src = url ? url : DEFAULT_AVATAR;
        return `
            <img
                src="${src}"
                alt="avatar"
                style="width:34px;height:34px;border-radius:999px;object-fit:cover;border:1px solid rgba(255,255,255,.10);"
            >
        `;
    }

    function buttonHTML(state, id){
        if(state === 'friends'){
            return `<button class="fr-btn fr-btn--pending" disabled>Friends</button>`;
        }
        if(state === 'pending'){
            return `<button class="fr-btn fr-btn--pending" disabled>Pending</button>`;
        }
        if(state === 'incoming'){
            return `<button class="fr-btn fr-btn--pending" disabled>Incoming</button>`;
        }
        return `<button class="fr-btn fr-btn--add js-add" data-id="${id}">Add</button>`;
    }

    function render(users){
        if(!users.length){
            results.innerHTML = '';
            return;
        }

        results.innerHTML = users.map(u => `
            <div class="fr-result">
                <div class="fr-result__left" style="display:flex; gap:10px; align-items:center;">
                    ${avatarHTML(u.avatar_url)}
                    <div style="min-width:0;">
                        <p class="fr-result__name">
                            ${escapeHtml(u.name)}
                            <span style="opacity:.6">
                                (<span>@</span>${escapeHtml(u.username ?? '')})
                            </span>
                        </p>
                        <div class="fr-result__sub">
                            ${escapeHtml(u.email ?? '')}
                        </div>
                    </div>
                </div>
                ${buttonHTML(u.state, u.id)}
            </div>
        `).join('');
    }

    async function search(q){
        if(q.length < 2){
            results.innerHTML = '';
            return;
        }

        const res = await fetch(`{{ route('friends.search') }}?q=${encodeURIComponent(q)}`, {
            headers: { 'Accept': 'application/json' }
        });

        const json = await res.json();
        render(json.data || []);
    }

    input.addEventListener('input', () => {
        clearTimeout(timeout);
        timeout = setTimeout(() => search(input.value.trim()), 300);
    });

    // Add request
    document.addEventListener('click', async (e) => {
        const btn = e.target.closest('.js-add');
        if(!btn) return;

        const id = btn.dataset.id;

        btn.disabled = true;
        btn.textContent = '...';

        try{
            const res = await fetch(`{{ route('friends.request') }}`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrf
                },
                body: JSON.stringify({ user_id: id })
            });

            const json = await res.json();

            btn.classList.remove('fr-btn--add','js-add');
            btn.classList.add('fr-btn--pending');
            btn.textContent = json.status === 'friends' ? 'Friends' : 'Pending';
            btn.disabled = true;

        } catch(err){
            console.error(err);
            btn.disabled = false;
            btn.textContent = 'Add';
        }
    });

    // Accept / Decline request
    document.addEventListener('click', async (e) => {
        const acceptBtn = e.target.closest('.js-accept');
        const declineBtn = e.target.closest('.js-decline');
        if(!acceptBtn && !declineBtn) return;

        const reqId = (acceptBtn || declineBtn).dataset.req;
        const row = document.querySelector(`.fr-result[data-req-id="${reqId}"]`);

        if (acceptBtn) acceptBtn.disabled = true;
        if (declineBtn) declineBtn.disabled = true;

        try{
            const url = acceptBtn
                ? `{{ url('/friends/requests') }}/${reqId}/accept`
                : `{{ url('/friends/requests') }}/${reqId}/decline`;

            const res = await fetch(url, {
                method: 'POST',
                headers: {
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrf
                }
            });

            if(!res.ok) throw new Error('Request failed');

            if(row) row.remove();

        } catch(err){
            console.error(err);
            alert('Something went wrong. Try again.');

            if (acceptBtn) acceptBtn.disabled = false;
            if (declineBtn) declineBtn.disabled = false;
        }
    });

})();
</script>

<script>
(() => {
  const modal = document.getElementById('friendModal');
  const closeEls = modal.querySelectorAll('[data-close-modal]');

  const fmCover = document.getElementById('fmCover');
  const fmAvatar = document.getElementById('fmAvatar');
  const fmDot = document.getElementById('fmDot');
  const fmName = document.getElementById('fmName');
  const fmStatus = document.getElementById('fmStatus');
  const fmLast = document.getElementById('fmLast');
  const fmUser = document.getElementById('fmUser');
  const fmEmail = document.getElementById('fmEmail');

  const qsWorkouts = document.getElementById('qsWorkouts');
  const qsDays = document.getElementById('qsDays');
  const qsFriends = document.getElementById('qsFriends');
  const qsJoined = document.getElementById('qsJoined');

  const streakWrap = document.getElementById('streakWrap');
  const achWrap = document.getElementById('achWrap');
  const achCount = document.getElementById('achCount');

  function openModal(){
    modal.classList.add('is-open');
    document.body.style.overflow = 'hidden';
  }

  function closeModal(){
    modal.classList.remove('is-open');
    document.body.style.overflow = '';
  }

  closeEls.forEach(el => el.addEventListener('click', closeModal));
  document.addEventListener('keydown', (e) => { if(e.key === 'Escape') closeModal(); });

  function setDot(dot){
    fmDot.classList.remove('fr-dot--online','fr-dot--recent','fr-dot--offline');
    fmDot.classList.add(dot === 'online' ? 'fr-dot--online' : dot === 'recent' ? 'fr-dot--recent' : 'fr-dot--offline');
  }

  function renderStreaks(streaks){
    streakWrap.innerHTML = (streaks || []).map(s => `
      <div class="fr-mini">
        <div class="fr-mini__icon">${s.icon ?? '🔥'}</div>
        <div class="fr-mini__value">${s.value ?? 0} <span style="font-size:14px; opacity:.8;">days</span></div>
        <div class="fr-mini__label">${s.label ?? 'Streak'}</div>
      </div>
    `).join('');
  }

  function renderAchievements(list){
  const arr = list || [];
  achWrap.innerHTML = arr.length
    ? arr.map(a => `
        <div class="fr-ach">
          ${
            a.image_url
              ? `<img src="${a.image_url}" alt="" style="width:26px;height:26px;object-fit:cover;border-radius:8px;">`
              : `🏆`
          }
          <div style="margin-top:8px;">${a.title}</div>
        </div>
      `).join('')
    : `<div class="fr-ach" style="grid-column:1/-1; opacity:.7;">No achievements to display yet</div>`;
}

  async function loadFriend(userId){
    const res = await fetch(`{{ url('/friends') }}/${userId}/summary`, { headers: { 'Accept':'application/json' }});
    if(!res.ok) throw new Error('Failed to load');

    const data = await res.json();

    fmName.textContent = data.user.name || 'Friend';
    fmUser.textContent = '@' + (data.user.username || '—');
    fmEmail.textContent = data.user.email || '—';

    fmAvatar.src = data.user.avatar_url || "{{ asset('images/default-avatar.png') }}";

    // cover background
    if(data.user.cover_url){
      fmCover.style.display = 'block';
      fmCover.style.backgroundImage = `url("${data.user.cover_url}")`;
    } else {
      fmCover.style.display = 'none';
      fmCover.style.backgroundImage = '';
    }

    // status pill + dot
    const statusText = data.user.status || 'Offline';
    fmStatus.textContent = (statusText === 'Online' ? '🟢 ' : statusText === 'Recently Active' ? '🟡 ' : '⚫ ') + statusText;

    // Decide dot by last active time (simple)
    // If backend later provides dot, use it; for now map:
    const dot = statusText === 'Online' ? 'online' : statusText === 'Recently Active' ? 'recent' : 'offline';
    setDot(dot);

    fmLast.textContent = 'Last active: ' + (data.user.last_active || '—');

    // quick stats
    qsWorkouts.textContent = data.quick.workouts_logged ?? 0;
    qsDays.textContent = data.quick.days_this_month ?? 0;
    qsFriends.textContent = data.quick.friends ?? 0;
    qsJoined.textContent = data.quick.joined ?? '—';

    // streaks + achievements
    renderStreaks(data.streaks);
    renderAchievements(data.achievements);
  }

  // Click friend card => open modal
  document.addEventListener('click', async (e) => {
    const card = e.target.closest('.fr-fcard[data-friend-id]');
    if(!card) return;

    const id = card.dataset.friendId;

    try{
      // reset lightweight
      fmName.textContent = 'Loading...';
      streakWrap.innerHTML = '';
      achWrap.innerHTML = '';
      openModal();
      await loadFriend(id);
    } catch(err){
      console.error(err);
      closeModal();
      alert('Could not load friend details.');
    }
  });
})();
</script>

</body>
</html>