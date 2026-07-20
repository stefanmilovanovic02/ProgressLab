<!doctype html>
<html lang="en">
<head>
    <x-seo
        title="Friends and Comparisons"
        description="Connect with friends, follow activity, and compare shared exercise progress in ProgressLab."
        robots="noindex, nofollow, noarchive"
    />

    <link rel="stylesheet" href="{{ asset('css/auth.css') }}">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
</head>

<body class="fr-body">

<x-navbar />

<div class="fr-wrap">

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

    <div class="fr-card">
        <div class="fr-card__grid">

            <div>
                <h5 class="fr-card__title">Add Friend</h5>
                <p class="fr-card__hint">
                    Search by name, username or email
                </p>
            </div>

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

        <div id="searchResults" class="fr-results"></div>

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
                        <span class="fr-pill" id="fmStatus">⚫ Offline</span>
                        <span class="fr-pill" id="fmLast">Last active: —</span>
                    </div>

                    <div class="fr-modal__meta" style="margin-top:8px;">
                        <span class="fr-pill" id="fmUser">@username</span>
                        <span class="fr-pill" id="fmEmail">email</span>
                    </div>
                </div>

                <button class="fr-unfriend" type="button" data-unfriend-open>Unfriend</button>
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
            <div class="fr-section__title">
                🏅 Achievements
                <span style="opacity:.6; font-weight:800;" id="achCount"></span>
            </div>
            <div class="fr-achRow" id="achWrap"></div>
        </div>

        <div class="fr-section">
            <div class="fr-section__title">📈 Strength Comparison</div>

            <div class="fr-compareControls">
                <select id="fcExerciseSelect" class="fr-compareSelect">
                    <option value="">Choose an exercise...</option>
                </select>

                <div class="fr-compareLegend">
                    <span class="fr-legendItem">
                        <span class="fr-legendDot fr-legendDot--me"></span>You
                    </span>
                    <span class="fr-legendItem">
                        <span class="fr-legendDot fr-legendDot--friend"></span>Friend
                    </span>
                </div>
            </div>

            <div class="fr-compareChartWrap">
                <canvas id="fcChart" height="110"></canvas>
            </div>

            <div id="fcEmpty" class="fr-compareEmpty">Select an exercise to compare progress.</div>
        </div>
    </div>

    <div class="fr-unfriend-confirm" data-unfriend-confirm aria-hidden="true">
        <button class="fr-unfriend-confirm__backdrop" type="button" aria-label="Cancel removing friend" data-unfriend-cancel></button>
        <section class="fr-unfriend-confirm__dialog" role="alertdialog" aria-modal="true" aria-labelledby="unfriendConfirmTitle" aria-describedby="unfriendConfirmText">
            <div class="fr-unfriend-confirm__icon" aria-hidden="true">👥</div>
            <h2 id="unfriendConfirmTitle">Remove friend?</h2>
            <p id="unfriendConfirmText">Are you sure you want to remove <strong data-unfriend-name>this friend</strong> from your friends list?</p>
            <div class="fr-unfriend-confirm__actions">
                <button class="pl-btn pl-btn--ghost" type="button" data-unfriend-cancel>Cancel</button>
                <button class="fr-unfriend-confirm__yes" type="button" data-unfriend-confirm-button>Yes, unfriend</button>
            </div>
            <p class="fr-unfriend-confirm__error" data-unfriend-error role="alert" hidden></p>
        </section>
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
                            <span style="opacity:.6">(<span>@</span>${escapeHtml(u.username ?? '')})</span>
                        </p>
                        <div class="fr-result__sub">${escapeHtml(u.email ?? '')}</div>
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

    const fcExerciseSelect = document.getElementById('fcExerciseSelect');
    const fcEmpty = document.getElementById('fcEmpty');
    const csrf = document.querySelector('meta[name="csrf-token"]').content;
    const unfriendOpen = modal.querySelector('[data-unfriend-open]');
    const unfriendConfirm = modal.querySelector('[data-unfriend-confirm]');
    const unfriendName = modal.querySelector('[data-unfriend-name]');
    const unfriendButton = modal.querySelector('[data-unfriend-confirm-button]');
    const unfriendError = modal.querySelector('[data-unfriend-error]');

    let currentFriendId = null;
    let fcChart = null;

    function openModal(){
        modal.classList.add('is-open');
        document.body.style.overflow = 'hidden';
    }

    function closeModal(){
        closeUnfriendConfirm();
        modal.classList.remove('is-open');
        document.body.style.overflow = '';
    }

    function openUnfriendConfirm(){
        if (!currentFriendId) return;
        unfriendName.textContent = fmName.textContent || 'this friend';
        unfriendError.hidden = true;
        unfriendError.textContent = '';
        unfriendButton.disabled = false;
        unfriendButton.textContent = 'Yes, unfriend';
        unfriendConfirm.classList.add('is-open');
        unfriendConfirm.setAttribute('aria-hidden', 'false');
        unfriendButton.focus();
    }

    function closeUnfriendConfirm(){
        unfriendConfirm.classList.remove('is-open');
        unfriendConfirm.setAttribute('aria-hidden', 'true');
    }

    closeEls.forEach(el => el.addEventListener('click', closeModal));
    unfriendOpen.addEventListener('click', openUnfriendConfirm);
    unfriendConfirm.querySelectorAll('[data-unfriend-cancel]').forEach(button => {
        button.addEventListener('click', closeUnfriendConfirm);
    });

    unfriendButton.addEventListener('click', async () => {
        if (!currentFriendId || unfriendButton.disabled) return;

        unfriendButton.disabled = true;
        unfriendButton.textContent = 'Removing…';
        unfriendError.hidden = true;

        try {
            const response = await fetch(`{{ url('/friends') }}/${currentFriendId}`, {
                method: 'DELETE',
                headers: {
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrf,
                },
            });

            if (!response.ok) throw new Error('Friend could not be removed.');
            window.location.reload();
        } catch (error) {
            unfriendError.textContent = error.message || 'Friend could not be removed. Please try again.';
            unfriendError.hidden = false;
            unfriendButton.disabled = false;
            unfriendButton.textContent = 'Yes, unfriend';
        }
    });

    document.addEventListener('keydown', (e) => {
        if(e.key !== 'Escape') return;
        if(unfriendConfirm.classList.contains('is-open')) {
            closeUnfriendConfirm();
            return;
        }
        closeModal();
    });

    function setDot(dot){
        fmDot.classList.remove('fr-dot--online','fr-dot--recent','fr-dot--offline');
        fmDot.classList.add(
            dot === 'online'
                ? 'fr-dot--online'
                : dot === 'recent'
                    ? 'fr-dot--recent'
                    : 'fr-dot--offline'
        );
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
        achCount.textContent = arr.length ? `(${arr.length})` : '';

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

    function resetComparisonUI() {
        fcExerciseSelect.innerHTML = `<option value="">Choose an exercise...</option>`;
        fcEmpty.textContent = 'Select an exercise to compare progress.';
        fcEmpty.style.display = 'block';

        if (fcChart) {
            fcChart.destroy();
            fcChart = null;
        }
    }

    async function loadComparisonExercises(userId){
        const res = await fetch(`{{ url('/friends') }}/${userId}/comparison-exercises`, {
            headers: { 'Accept':'application/json' }
        });
        if (!res.ok) throw new Error('Failed to load exercises');

        const data = await res.json();
        const items = data.items || [];

        fcExerciseSelect.innerHTML =
            `<option value="">Choose an exercise...</option>` +
            items.map(x => `<option value="${x.id}">${x.name}</option>`).join('');

        if (!items.length) {
            fcEmpty.textContent = 'No shared logged exercises yet.';
            fcEmpty.style.display = 'block';
        }
    }

    async function loadComparisonChart(userId, exerciseId){
        if (!exerciseId) {
            if (fcChart) {
                fcChart.destroy();
                fcChart = null;
            }
            fcEmpty.textContent = 'Select an exercise to compare progress.';
            fcEmpty.style.display = 'block';
            return;
        }

        const res = await fetch(`{{ url('/friends') }}/${userId}/exercise-comparison?exercise_id=${encodeURIComponent(exerciseId)}&period=all`, {
            headers: { 'Accept':'application/json' }
        });
        if (!res.ok) throw new Error('Failed to load comparison chart');

        const data = await res.json();

        const canvas = document.getElementById('fcChart');
        if (fcChart) fcChart.destroy();

        fcChart = new Chart(canvas, {
            type: 'line',
            data: {
                labels: data.labels || [],
                datasets: [
                    {
                        label: data.user_name || 'You',
                        data: data.user || [],
                        borderColor: '#3b82f6',
                        backgroundColor: 'transparent',
                        pointBackgroundColor: '#3b82f6',
                        pointBorderColor: '#3b82f6',
                        pointRadius: 3,
                        spanGaps: true,
                        tension: 0.35,
                        borderWidth: 2
                    },
                    {
                        label: data.friend_name || 'Friend',
                        data: data.friend || [],
                        borderColor: '#ef4444',
                        backgroundColor: 'transparent',
                        pointBackgroundColor: '#ef4444',
                        pointBorderColor: '#ef4444',
                        pointRadius: 3,
                        spanGaps: true,
                        tension: 0.35,
                        borderWidth: 2
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false },
                    tooltip: { enabled: true }
                },
                scales: {
                    x: {
                        ticks: { color: 'rgba(255,255,255,.72)' },
                        grid: { color: 'rgba(255,255,255,.08)' }
                    },
                    y: {
                        beginAtZero: true,
                        ticks: { color: 'rgba(255,255,255,.72)' },
                        grid: { color: 'rgba(255,255,255,.08)' }
                    }
                }
            }
        });

        fcEmpty.style.display = (data.labels && data.labels.length) ? 'none' : 'block';
        if (!data.labels || !data.labels.length) {
            fcEmpty.textContent = 'No comparison data for this exercise yet.';
        }
    }

    async function loadFriend(userId){
        const res = await fetch(`{{ url('/friends') }}/${userId}/summary`, {
            headers: { 'Accept':'application/json' }
        });
        if(!res.ok) throw new Error('Failed to load');

        const data = await res.json();

        fmName.textContent = data.user.name || 'Friend';
        fmUser.textContent = '@' + (data.user.username || '—');
        fmEmail.textContent = data.user.email || '—';
        fmAvatar.src = data.user.avatar_url || "{{ asset('images/default-avatar.png') }}";

        if(data.user.cover_url){
            fmCover.style.display = 'block';
            fmCover.style.backgroundImage = `url("${data.user.cover_url}")`;
        } else {
            fmCover.style.display = 'none';
            fmCover.style.backgroundImage = '';
        }

        const statusText = data.user.status || 'Offline';
        fmStatus.classList.remove('is-online', 'is-recent', 'is-offline');
        fmStatus.textContent =
            (statusText === 'Online'
                ? '🟢 '
                : statusText === 'Recently Active'
                    ? '🟡 '
                    : '⚫ ') + statusText;

        const dot = data.user.dot || (statusText === 'Online'
            ? 'online'
            : statusText === 'Recently Active'
                ? 'recent'
                : 'offline');

        setDot(dot);
        fmStatus.classList.add(`is-${dot}`);

        fmLast.textContent = 'Last active: ' + (data.user.last_active || '—');

        qsWorkouts.textContent = data.quick.workouts_logged ?? 0;
        qsDays.textContent = data.quick.days_this_month ?? 0;
        qsFriends.textContent = data.quick.friends ?? 0;
        qsJoined.textContent = data.quick.joined ?? '—';

        renderStreaks(data.streaks);
        renderAchievements(data.achievements);
    }

    fcExerciseSelect.addEventListener('change', async () => {
        if (!currentFriendId) return;

        try {
            await loadComparisonChart(currentFriendId, fcExerciseSelect.value);
        } catch (err) {
            console.error(err);
            fcEmpty.textContent = 'Could not load comparison chart.';
            fcEmpty.style.display = 'block';
        }
    });

    document.addEventListener('click', async (e) => {
        const card = e.target.closest('.fr-fcard[data-friend-id]');
        if(!card) return;

        const id = card.dataset.friendId;

        try{
            fmName.textContent = 'Loading...';
            streakWrap.innerHTML = '';
            achWrap.innerHTML = '';
            achCount.textContent = '';
            currentFriendId = id;
            resetComparisonUI();

            openModal();
            await loadFriend(id);
            await loadComparisonExercises(id);
        } catch(err){
            console.error(err);
            closeModal();
            alert('Could not load friend details.');
        }
    });
})();
</script>

<x-achievement-toasts />
<x-footer />
</body>
</html>
