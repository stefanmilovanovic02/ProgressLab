<!doctype html>
<html lang="en">
<head>
    <x-seo
        title="Friends and Comparisons"
        description="Connect with friends, follow activity, and compare shared exercise progress in ProgressLab."
        robots="noindex, nofollow, noarchive"
    />

    <link rel="stylesheet" href="{{ asset('css/auth.css') }}?v={{ filemtime(public_path('css/auth.css')) }}">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
    <script src="{{ asset('js/image-optimizer.js') }}?v={{ filemtime(public_path('js/image-optimizer.js')) }}"></script>
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
                        <div class="fr-result" data-sent-request-id="{{ $req->id }}">
                            <div class="fr-result__left" style="display:flex; gap:10px; align-items:center;">
                                <img
                                    src="{{ $u->avatar_url }}"
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

                            <button class="fr-btn fr-btn--cancel js-cancel-request" data-req="{{ $req->id }}">Revoke request</button>
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
                                    src="{{ $u->avatar_url }}"
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

@if($suggestedPeople->isNotEmpty())
  <section class="fr-suggest" aria-labelledby="suggestedPeopleTitle">
    <div class="fr-suggest__head">
      <div>
        <span>Discover</span>
        <h2 id="suggestedPeopleTitle">Suggested people</h2>
      </div>
      <p>People near you first, then active ProgressLab members.</p>
    </div>
    <div class="fr-suggest__rail">
      @foreach($suggestedPeople as $person)
        <article class="fr-suggest__person" data-profile-id="{{ $person['id'] }}" tabindex="0" role="button" aria-label="Open {{ $person['name'] }}'s profile">
          <div class="fr-suggest__avatar">
            <img src="{{ $person['avatar_url'] }}" alt="" loading="lazy" decoding="async">
            <button type="button" class="fr-suggest__add js-add" data-id="{{ $person['id'] }}" aria-label="Add {{ $person['name'] }} as a friend">+</button>
          </div>
          <strong>{{ $person['name'] }}</strong>
          <span>{{ $person['nearby'] ? 'Near you' : '@'.($person['username'] ?: 'member') }}</span>
        </article>
      @endforeach
    </div>
  </section>
@endif

@if(isset($friendsCards) && count($friendsCards))
    <div class="fr-grid">
        @foreach($friendsCards as $f)
            <div class="fr-fcard" data-friend-id="{{ $f['id'] }}" data-profile-id="{{ $f['id'] }}" tabindex="0" role="button">
                <div class="fr-fcard__avatarWrap">
                    <img class="fr-fcard__avatar" src="{{ $f['avatar_url'] }}" alt="avatar" loading="lazy" decoding="async">
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
        <video class="fr-profile-background-video" id="fmProfileBackgroundVideo" autoplay muted loop playsinline preload="metadata" hidden></video>
        <section class="fr-profile-hero">
            <div class="fr-profile-cover" id="fmProfileCover"></div>
            <div class="fr-profile-hero__actions">
                <button class="fr-profile-add fr-btn fr-btn--add" type="button" data-profile-add hidden>Add friend</button>
                <button class="fr-profile-customize fr-btn fr-btn--light" type="button" data-profile-customize hidden>Customize Profile</button>
                <button class="fr-unfriend" type="button" data-profile-unfriend>Unfriend</button>
                <button class="fr-modal__close fr-profile-close" type="button" data-close-modal aria-label="Close profile">&times;</button>
            </div>
            <div class="fr-profile-identity">
                <div class="fr-modal__avatarWrap">
                <img id="fmProfileAvatar" class="fr-modal__avatar" src="{{ asset('images/default-avatar.png') }}" alt="" decoding="async">
                    <span id="fmProfileDot" class="fr-modal__dot fr-dot--offline"></span>
                </div>
                <div>
                    <h2 id="fmProfileName" class="fr-modal__name">Member</h2>
                    <div class="fr-profile-handle"><span id="fmProfileUser">@username</span><span id="fmProfileLocation"></span></div>
                    <div class="fr-modal__meta">
                        <span class="fr-pill" id="fmProfileStatus">Offline</span>
                        <span class="fr-pill" id="fmProfileLast">Last active: —</span>
                    </div>
                </div>
                <nav class="fr-profile-socials" id="fmProfileSocials" aria-label="Social profiles"></nav>
            </div>
            <blockquote class="fr-profile-quote" id="fmProfileQuote">Building progress one day at a time.</blockquote>
        </section>

        <aside class="fr-profile-editor" data-profile-editor hidden
          data-avatar-url="{{ route('profile.photo.update') }}"
          data-cover-url="{{ route('profile.cover.update') }}"
          data-showcase-url="{{ route('profile.showcase.update') }}"
          data-theme-url="{{ route('profile.theme.update') }}">
            <div class="fr-profile-editor__head">
                <div><span>Live editor</span><strong>Customize your profile</strong></div>
                <button type="button" data-profile-editor-close aria-label="Close editor">&times;</button>
            </div>
            <div class="fr-profile-editor__uploads">
                <input type="file" accept="image/jpeg,image/png,image/webp" data-profile-file="avatar" hidden>
                <button type="button" data-profile-upload="avatar">Change Profile Photo</button>
                <div data-profile-plus-controls hidden>
                    <input type="file" accept="image/jpeg,image/png,image/webp,image/gif,video/mp4,video/webm" data-profile-file="cover" hidden>
                    <input type="file" accept="image/jpeg,image/png,image/webp,image/gif" data-profile-file="showcase" hidden>
                    <button type="button" data-profile-upload="cover">Change Background</button>
                    <button type="button" data-profile-upload="showcase">Change Showcase</button>
                </div>
            </div>
            <form class="fr-profile-editor__theme" data-profile-theme-form hidden>
                @foreach([
                    ['profile_accent_color', 'profile_accent_opacity', 'Accent', '#329ff2', 100],
                    ['profile_secondary_color', 'profile_secondary_opacity', 'Secondary', '#2dd4ff', 100],
                    ['profile_surface_color', 'profile_surface_opacity', 'Cards', '#0b1422', 92],
                    ['profile_text_color', 'profile_text_opacity', 'Text', '#f5f8ff', 100],
                ] as [$field, $opacityField, $label, $default, $defaultOpacity])
                    <label class="fr-theme-control" data-theme-control>
                        <span>{{ $label }}</span>
                        <input class="fr-theme-control__hex" type="text" name="{{ $field }}" value="{{ $default }}" maxlength="7" pattern="#[0-9A-Fa-f]{6}" spellcheck="false" aria-label="{{ $label }} HEX color">
                        <input class="fr-theme-control__swatch" type="color" value="{{ $default }}" data-theme-swatch aria-label="Choose {{ strtolower($label) }} color">
                        <span class="fr-theme-control__alpha"><input type="number" name="{{ $opacityField }}" value="{{ $defaultOpacity }}" min="0" max="100" step="1" aria-label="{{ $label }} opacity"><b>%</b></span>
                    </label>
                @endforeach
                <button type="submit">Save Colors</button>
            </form>
            <a class="fr-profile-editor__upgrade" href="{{ route('plans.index') }}" data-profile-upgrade hidden>Unlock backgrounds and colors with ProgressLab+</a>
            <p class="fr-profile-editor__status" data-profile-editor-status aria-live="polite"></p>
        </aside>

        <div class="fr-profile-layout">
            <section class="fr-profile-showcase">
                <img id="fmProfileShowcase" src="" alt="Profile showcase" loading="lazy" decoding="async">
                <div class="fr-profile-showcase__empty" id="fmProfileShowcaseEmpty">No showcase image added yet.</div>
            </section>
            <aside class="fr-profile-sidebar">
                <article class="fr-rank-card" id="fmProfileRankCard">
                    <img id="fmProfileRankIcon" src="{{ asset('images/ranks/bronze.png') }}" alt="">
                    <div><span>Current rank</span><strong id="fmProfileRankName">Bronze I</strong><small id="fmProfileRankXp">0 XP</small></div>
                </article>
                <div class="fr-profile-side-title">Current streaks</div>
                <div class="fr-profile-streaks" id="fmProfileStreaks"></div>
                <div class="fr-profile-side-title">Recent achievements <span id="fmProfileAchievementCount"></span></div>
                <div class="fr-profile-achievements" id="fmProfileAchievements"></div>
            </aside>
        </div>
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

        <div class="fr-section" id="trainerAccessSection" hidden>
            <div class="fr-section__title">🤝 Trainer Access</div>
            <div class="fr-trainer-access">
                <div>
                    <strong id="trainerAccessTitle">Client access</strong>
                    <p id="trainerAccessCopy">Trainer access is separate from friendship and requires approval.</p>
                </div>
                <div class="fr-trainer-actions" id="trainerAccessActions"></div>
                <form class="fr-trainer-permissions" id="trainerPermissions" hidden>
                    <label><input type="checkbox" name="can_view_nutrition"> Nutrition charts</label>
                    <label><input type="checkbox" name="can_view_exercises"> Exercise and strength charts</label>
                    <label><input type="checkbox" name="can_view_weight"> Body-weight history</label>
                    <label><input type="checkbox" name="can_view_streaks"> Streaks</label>
                    <label class="is-disabled"><input type="checkbox" disabled> Progress photos (not available to Trainers)</label>
                </form>
                <p class="fr-unfriend-confirm__error" id="trainerAccessError" role="alert" hidden></p>
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
                <div class="fr-exercise-picker">
                    <input id="fcExerciseSearch" class="fr-compareSelect" type="search" placeholder="Search a shared exercise..." autocomplete="off">
                    <input id="fcExerciseSelect" type="hidden" value="">
                    <div id="fcExerciseOptions" class="fr-exercise-options" hidden></div>
                </div>

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
        e.stopImmediatePropagation();

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
        const button = e.target.closest('.js-cancel-request');
        if (!button) return;
        const requestId = button.dataset.req;
        button.disabled = true;
        button.textContent = 'Revoking...';
        try {
            const response = await fetch(`{{ url('/friends/requests') }}/${requestId}`, {
                method: 'DELETE',
                headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': csrf }
            });
            if (!response.ok) throw new Error('Request could not be revoked.');
            document.querySelector(`[data-sent-request-id="${CSS.escape(requestId)}"]`)?.remove();
        } catch (error) {
            button.disabled = false;
            button.textContent = 'Revoke request';
            alert(error.message);
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
    const modalPanel = modal.querySelector('.fr-modal__panel');
    const closeEls = modal.querySelectorAll('[data-close-modal]');

    const fmCover = document.getElementById('fmCover');
    const fmAvatar = document.getElementById('fmAvatar');
    const fmDot = document.getElementById('fmDot');
    const fmName = document.getElementById('fmName');
    const fmStatus = document.getElementById('fmStatus');
    const fmLast = document.getElementById('fmLast');
    const fmUser = document.getElementById('fmUser');
    const fmEmail = document.getElementById('fmEmail');
    const profileCover = document.getElementById('fmProfileCover');
    const profileAvatar = document.getElementById('fmProfileAvatar');
    const profileDot = document.getElementById('fmProfileDot');
    const profileName = document.getElementById('fmProfileName');
    const profileUser = document.getElementById('fmProfileUser');
    const profileLocation = document.getElementById('fmProfileLocation');
    const profileStatus = document.getElementById('fmProfileStatus');
    const profileLast = document.getElementById('fmProfileLast');
    const profileQuote = document.getElementById('fmProfileQuote');
    const profileSocials = document.getElementById('fmProfileSocials');
    const profileShowcase = document.getElementById('fmProfileShowcase');
    const profileShowcaseEmpty = document.getElementById('fmProfileShowcaseEmpty');
    const profileBackgroundVideo = document.getElementById('fmProfileBackgroundVideo');
    const profileRankCard = document.getElementById('fmProfileRankCard');
    const profileRankIcon = document.getElementById('fmProfileRankIcon');
    const profileRankName = document.getElementById('fmProfileRankName');
    const profileRankXp = document.getElementById('fmProfileRankXp');
    const profileStreaks = document.getElementById('fmProfileStreaks');
    const profileAchievements = document.getElementById('fmProfileAchievements');
    const profileAchievementCount = document.getElementById('fmProfileAchievementCount');
    const profileAdd = modal.querySelector('[data-profile-add]');
    const customizeButton = modal.querySelector('[data-profile-customize]');
    const profileEditor = modal.querySelector('[data-profile-editor]');
    const editorClose = modal.querySelector('[data-profile-editor-close]');
    const plusControls = modal.querySelector('[data-profile-plus-controls]');
    const themeForm = modal.querySelector('[data-profile-theme-form]');
    const upgradeLink = modal.querySelector('[data-profile-upgrade]');
    const editorStatus = modal.querySelector('[data-profile-editor-status]');
    const strengthSection = document.getElementById('strengthComparisonSection') || document.querySelector('.fr-compareControls')?.closest('.fr-section');

    const qsWorkouts = document.getElementById('qsWorkouts');
    const qsDays = document.getElementById('qsDays');
    const qsFriends = document.getElementById('qsFriends');
    const qsJoined = document.getElementById('qsJoined');

    const streakWrap = document.getElementById('streakWrap');
    const achWrap = document.getElementById('achWrap');
    const achCount = document.getElementById('achCount');

    const fcExerciseSelect = document.getElementById('fcExerciseSelect');
    const fcExerciseSearch = document.getElementById('fcExerciseSearch');
    const fcExerciseOptions = document.getElementById('fcExerciseOptions');
    const fcEmpty = document.getElementById('fcEmpty');
    const csrf = document.querySelector('meta[name="csrf-token"]').content;
    const unfriendOpen = modal.querySelector('[data-profile-unfriend]');
    const unfriendConfirm = modal.querySelector('[data-unfriend-confirm]');
    const unfriendName = modal.querySelector('[data-unfriend-name]');
    const unfriendButton = modal.querySelector('[data-unfriend-confirm-button]');
    const unfriendError = modal.querySelector('[data-unfriend-error]');
    const trainerAccessSection = document.getElementById('trainerAccessSection');
    const trainerAccessTitle = document.getElementById('trainerAccessTitle');
    const trainerAccessCopy = document.getElementById('trainerAccessCopy');
    const trainerAccessActions = document.getElementById('trainerAccessActions');
    const trainerPermissions = document.getElementById('trainerPermissions');
    const trainerAccessError = document.getElementById('trainerAccessError');

    let currentFriendId = null;
    let currentTrainerAccess = null;
    let fcChart = null;
    let comparisonExercises = [];
    const urlParameters = new URLSearchParams(window.location.search);
    const returnToProfile = urlParameters.get('return_to') === 'profile';
    const escapeHtml = value => String(value ?? '').replace(/[&<>"']/g, character => ({
        '&':'&amp;', '<':'&lt;', '>':'&gt;', '"':'&quot;', "'":'&#039;'
    }[character]));
    const validThemeColor = (value, fallback) => /^#[0-9a-f]{6}$/i.test(value || '') ? value : fallback;
    const validOpacity = (value, fallback = 100) => Math.min(100, Math.max(0, Number.isFinite(Number(value)) ? Number(value) : fallback));
    const colorWithOpacity = (color, opacity) => {
        const hex = validThemeColor(color, '#329ff2');
        const alpha = Math.round(validOpacity(opacity) * 2.55).toString(16).padStart(2, '0');
        return `${hex}${alpha}`;
    };

    function applyProfileTheme(theme = {}) {
        modalPanel.style.setProperty('--profile-accent', colorWithOpacity(theme.accent, theme.accent_opacity ?? 100));
        modalPanel.style.setProperty('--profile-secondary', colorWithOpacity(theme.secondary, theme.secondary_opacity ?? 100));
        modalPanel.style.setProperty('--profile-surface', colorWithOpacity(theme.surface, theme.surface_opacity ?? 92));
        modalPanel.style.setProperty('--profile-text', colorWithOpacity(theme.text, theme.text_opacity ?? 100));
    }

    function openModal(){
        modal.classList.add('is-open');
        document.body.style.overflow = 'hidden';
    }

    function closeModal(){
        closeUnfriendConfirm();
        profileEditor.hidden = true;
        profileBackgroundVideo.pause();
        modal.classList.remove('is-open');
        document.body.style.overflow = '';
        if (returnToProfile) {
            window.location.replace("{{ route('profile.show') }}");
        }
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
    customizeButton.addEventListener('click', () => {
        profileEditor.hidden = false;
        editorStatus.textContent = '';
    });
    editorClose.addEventListener('click', () => {
        profileEditor.hidden = true;
    });
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
        [fmDot, profileDot].filter(Boolean).forEach(element => {
          element.classList.remove('fr-dot--online','fr-dot--recent','fr-dot--offline');
          element.classList.add(
            dot === 'online'
                ? 'fr-dot--online'
                : dot === 'recent'
                    ? 'fr-dot--recent'
                    : 'fr-dot--offline'
          );
        });
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

    function renderProfileStreaks(streaks) {
        profileStreaks.innerHTML = (streaks || []).slice(0, 3).map(streak => `
          <article><span>${streak.icon || '🔥'}</span><div><strong>${escapeHtml(streak.value ?? 0)} days</strong><small>${escapeHtml(streak.label || 'Streak')}</small></div></article>
        `).join('');
    }

    function renderProfileAchievements(achievements, total) {
        const items = achievements || [];
        profileAchievementCount.textContent = total ? `(${total})` : '';
        profileAchievements.innerHTML = items.length ? items.map(item => `
          <article title="${escapeHtml(item.title)}"><img src="${escapeHtml(item.image_url)}" alt=""><span>${escapeHtml(item.title)}</span></article>
        `).join('') : '<p>No achievements yet.</p>';
    }

    function renderSocials(socials) {
        const labels = { instagram: 'IG', tiktok: 'TT', snapchat: 'SC', linkedin: 'IN' };
        profileSocials.innerHTML = Object.entries(socials || {}).map(([network, url]) => `
          <a href="${escapeHtml(url)}" target="_blank" rel="noopener noreferrer" aria-label="${network}">${labels[network] || network.slice(0, 2).toUpperCase()}</a>
        `).join('');
        profileSocials.hidden = profileSocials.innerHTML === '';
    }

    function permissionPayload() {
        return {
            can_view_nutrition: trainerPermissions.elements.can_view_nutrition.checked,
            can_view_exercises: trainerPermissions.elements.can_view_exercises.checked,
            can_view_weight: trainerPermissions.elements.can_view_weight.checked,
            can_view_streaks: trainerPermissions.elements.can_view_streaks.checked,
        };
    }

    function renderTrainerAccess(access) {
        currentTrainerAccess = access;
        trainerAccessError.hidden = true;
        trainerAccessActions.innerHTML = '';
        trainerPermissions.hidden = true;

        if (!access || access.viewer_mode === 'none') {
            trainerAccessSection.hidden = true;
            return;
        }

        trainerAccessSection.hidden = false;
        Object.entries(access.permissions || {}).forEach(([key, value]) => {
            const field = trainerPermissions.elements[`can_view_${key}`];
            if (field) field.checked = Boolean(value);
        });

        if (access.viewer_mode === 'trainer') {
            trainerAccessTitle.textContent = 'Client relationship';
            if (access.status === 'accepted') {
                trainerAccessCopy.textContent = 'This client controls which read-only areas you can access.';
                trainerAccessActions.innerHTML = `
                    <a class="pl-btn pl-btn--light" href="${access.dashboard_url}">Open Client Dashboard</a>
                    <button class="pl-btn pl-btn--ghost" type="button" data-trainer-remove>Remove Client</button>`;
            } else if (access.status === 'pending') {
                trainerAccessCopy.textContent = 'Invitation sent. The user must accept and choose their permissions.';
                trainerAccessActions.innerHTML = '<button class="pl-btn pl-btn--ghost" type="button" disabled>Invitation Pending</button>';
            } else if (access.target_can_be_client) {
                trainerAccessCopy.textContent = 'Invite this friend to share selected charts with you.';
                trainerAccessActions.innerHTML = '<button class="pl-btn pl-btn--light" type="button" data-trainer-invite>Invite as Client</button>';
            } else {
                trainerAccessCopy.textContent = 'This account cannot become a Trainer client.';
            }
            return;
        }

        trainerAccessTitle.textContent = 'Trainer invitation';
        if (access.status === 'pending') {
            trainerAccessCopy.textContent = 'Choose what this Trainer may view before accepting.';
            trainerPermissions.hidden = false;
            trainerAccessActions.innerHTML = `
                <button class="pl-btn pl-btn--light" type="button" data-trainer-accept>Accept Invitation</button>
                <button class="pl-btn pl-btn--ghost" type="button" data-trainer-decline>Decline</button>`;
        } else if (access.status === 'accepted') {
            trainerAccessCopy.textContent = 'You can change shared areas or revoke access at any time.';
            trainerPermissions.hidden = false;
            trainerAccessActions.innerHTML = `
                <button class="pl-btn pl-btn--light" type="button" data-trainer-save>Save Access</button>
                <button class="pl-btn pl-btn--ghost" type="button" data-trainer-remove>Revoke Access</button>`;
        } else {
            trainerAccessCopy.textContent = 'No active Trainer invitation.';
        }
    }

    async function trainerRequest(url, method = 'POST', body = null) {
        trainerAccessError.hidden = true;
        const response = await fetch(url, {
            method,
            headers: {
                'Accept': 'application/json',
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrf,
            },
            body: body ? JSON.stringify(body) : null,
        });
        const data = await response.json().catch(() => ({}));
        if (!response.ok) throw new Error(data.message || 'Trainer access could not be updated.');
        return data;
    }

    trainerAccessActions.addEventListener('click', async event => {
        const button = event.target.closest('button');
        if (!button || !currentFriendId) return;
        button.disabled = true;
        try {
            if (button.matches('[data-trainer-invite]')) {
                const data = await trainerRequest(`{{ url('/friends') }}/${currentFriendId}/trainer-invitation`);
                renderTrainerAccess({ ...currentTrainerAccess, ...data.relationship, viewer_mode: 'trainer', target_can_be_client: true });
            } else if (button.matches('[data-trainer-accept]')) {
                const data = await trainerRequest(`{{ url('/trainer-invitations') }}/${currentTrainerAccess.id}/accept`, 'POST', permissionPayload());
                renderTrainerAccess({ ...currentTrainerAccess, ...data.relationship, viewer_mode: 'client' });
            } else if (button.matches('[data-trainer-decline]')) {
                await trainerRequest(`{{ url('/trainer-invitations') }}/${currentTrainerAccess.id}/decline`);
                renderTrainerAccess({ ...currentTrainerAccess, status: 'declined' });
            } else if (button.matches('[data-trainer-save]')) {
                const data = await trainerRequest(`{{ url('/trainer-access') }}/${currentTrainerAccess.id}`, 'PATCH', permissionPayload());
                renderTrainerAccess({ ...currentTrainerAccess, ...data.relationship, viewer_mode: 'client' });
            } else if (button.matches('[data-trainer-remove]')) {
                if (!confirm(currentTrainerAccess.viewer_mode === 'trainer' ? 'Remove this client?' : 'Revoke this Trainer’s access?')) return;
                await trainerRequest(`{{ url('/trainer-access') }}/${currentTrainerAccess.id}`, 'DELETE');
                renderTrainerAccess({ ...currentTrainerAccess, status: 'revoked' });
            }
        } catch (error) {
            trainerAccessError.textContent = error.message;
            trainerAccessError.hidden = false;
            button.disabled = false;
        }
    });

    function resetComparisonUI() {
        fcExerciseSelect.value = '';
        fcExerciseSearch.value = '';
        fcExerciseOptions.innerHTML = '';
        fcExerciseOptions.hidden = true;
        comparisonExercises = [];
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

        comparisonExercises = items;

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
                    tooltip: {
                        enabled: true,
                        callbacks: {
                            label: context => `${context.dataset.label}: ${context.raw} ${data.weight_unit || 'kg'}`
                        }
                    }
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
        profileName.textContent = data.user.name || 'Member';
        profileUser.textContent = '@' + (data.user.username || 'member');
        profileLocation.textContent = data.user.location ? `${String.fromCharCode(183)} ${data.user.location}` : '';
        profileAvatar.src = data.user.avatar_url || "{{ asset('images/default-avatar.png') }}";
        profileQuote.textContent = data.user.quote || 'Building progress one day at a time.';
        profileCover.style.backgroundImage = data.user.cover_url ? `url("${data.user.cover_url}")` : '';
        profileCover.classList.toggle('has-image', Boolean(data.user.cover_url));
        modalPanel.style.setProperty('--profile-cover-image', data.user.cover_url ? `url("${data.user.cover_url}")` : 'none');
        applyProfileTheme(data.user.theme);
        if (data.user.background_video_url) {
            if (profileBackgroundVideo.src !== data.user.background_video_url) {
                profileBackgroundVideo.src = data.user.background_video_url;
            }
            profileBackgroundVideo.hidden = false;
            profileBackgroundVideo.play().catch(() => {});
        } else {
            profileBackgroundVideo.pause();
            profileBackgroundVideo.removeAttribute('src');
            profileBackgroundVideo.load();
            profileBackgroundVideo.hidden = true;
        }
        profileShowcase.hidden = !data.user.showcase_url;
        profileShowcaseEmpty.hidden = Boolean(data.user.showcase_url);
        if (data.user.showcase_url) profileShowcase.src = data.user.showcase_url;
        renderSocials(data.user.socials);

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
        profileStatus.classList.remove('is-online', 'is-recent', 'is-offline');
        profileStatus.classList.add(`is-${dot}`);
        profileStatus.textContent = statusText;

        fmLast.textContent = 'Last active: ' + (data.user.last_active || '—');

        profileLast.textContent = 'Last active: ' + (data.user.last_active || '—');
        qsWorkouts.textContent = data.quick.workouts_logged ?? 0;
        qsDays.textContent = data.quick.days_this_month ?? 0;
        qsFriends.textContent = data.quick.friends ?? 0;
        qsJoined.textContent = data.quick.joined ?? '—';

        renderStreaks(data.streaks);
        renderAchievements(data.achievements);
        renderProfileStreaks(data.streaks);
        renderProfileAchievements(data.achievements, data.achievements_unlocked);
        const roman = ['I', 'II', 'III', 'IV'][Math.max(0, (data.rank?.level || 1) - 1)];
        profileRankName.textContent = `${data.rank?.name || 'Bronze'} ${roman}`;
        profileRankXp.textContent = `${Number(data.rank?.total_xp || 0).toLocaleString()} XP`;
        profileRankIcon.src = data.rank?.icon_url || "{{ asset('images/ranks/bronze.png') }}";
        profileRankCard.style.setProperty('--profile-rank-color', data.rank?.color || '#b87333');
        const relationship = data.relationship || {};
        unfriendOpen.hidden = !relationship.is_friend;
        profileAdd.hidden = relationship.is_friend || relationship.is_self;
        customizeButton.hidden = !relationship.is_self;
        profileEditor.hidden = true;
        plusControls.hidden = !relationship.can_customize;
        themeForm.hidden = !relationship.can_customize;
        upgradeLink.hidden = Boolean(relationship.can_customize);
        const themeValues = {
            profile_accent_color: data.user.theme?.accent || '#329ff2',
            profile_accent_opacity: data.user.theme?.accent_opacity ?? 100,
            profile_secondary_color: data.user.theme?.secondary || '#2dd4ff',
            profile_secondary_opacity: data.user.theme?.secondary_opacity ?? 100,
            profile_surface_color: data.user.theme?.surface || '#0b1422',
            profile_surface_opacity: data.user.theme?.surface_opacity ?? 92,
            profile_text_color: data.user.theme?.text || '#f5f8ff',
            profile_text_opacity: data.user.theme?.text_opacity ?? 100,
        };
        Object.entries(themeValues).forEach(([name, value]) => {
            const input = themeForm.elements.namedItem(name);
            if (input) input.value = name.endsWith('_color') ? validThemeColor(value, '#329ff2').toUpperCase() : validOpacity(value);
        });
        themeForm.querySelectorAll('[data-theme-control]').forEach(control => {
            const hex = control.querySelector('.fr-theme-control__hex');
            const swatch = control.querySelector('[data-theme-swatch]');
            swatch.value = validThemeColor(hex.value, '#329ff2');
        });
        profileAdd.disabled = relationship.state !== 'add';
        profileAdd.textContent = relationship.state === 'pending' ? 'Request pending' : relationship.state === 'incoming' ? 'Request received' : 'Add friend';
        strengthSection.hidden = !relationship.can_compare;
        renderTrainerAccess(data.trainer_access);
        return data;
    }

    const showExerciseOptions = () => {
        const query = fcExerciseSearch.value.trim().toLowerCase();
        const matches = comparisonExercises.filter(item => !query || item.name.toLowerCase().includes(query)).slice(0, 8);
        fcExerciseOptions.innerHTML = matches.map(item => `<button type="button" data-exercise-id="${item.id}" data-exercise-name="${escapeHtml(item.name)}">${escapeHtml(item.name)}</button>`).join('');
        fcExerciseOptions.hidden = matches.length === 0;
    };
    fcExerciseSearch.addEventListener('focus', showExerciseOptions);
    fcExerciseSearch.addEventListener('input', showExerciseOptions);
    fcExerciseOptions.addEventListener('click', async event => {
        const option = event.target.closest('[data-exercise-id]');
        if (!option || !currentFriendId) return;
        fcExerciseSelect.value = option.dataset.exerciseId;
        fcExerciseSearch.value = option.dataset.exerciseName;
        fcExerciseOptions.hidden = true;
        try {
            await loadComparisonChart(currentFriendId, fcExerciseSelect.value);
        } catch (error) {
            console.error(error);
            fcEmpty.textContent = 'Could not load comparison chart.';
            fcEmpty.style.display = 'block';
        }
    });

    async function openProfileById(id) {
        try{
            fmName.textContent = 'Loading...';
            streakWrap.innerHTML = '';
            achWrap.innerHTML = '';
            achCount.textContent = '';
            currentFriendId = id;
            resetComparisonUI();

            openModal();
            const data = await loadFriend(id);
            if (data.relationship?.can_compare) await loadComparisonExercises(id);
        } catch(err){
            console.error(err);
            closeModal();
            alert('Could not load friend details.');
        }
    }

    document.addEventListener('click', async (e) => {
        const card = e.target.closest('[data-profile-id]');
        if(!card) return;
        await openProfileById(card.dataset.profileId);
    });

    document.addEventListener('keydown', event => {
        const card = event.target.closest('[data-profile-id]');
        if (card && (event.key === 'Enter' || event.key === ' ')) {
            event.preventDefault();
            card.click();
        }
    });

    profileAdd.addEventListener('click', async () => {
        if (!currentFriendId || profileAdd.disabled) return;
        profileAdd.disabled = true;
        profileAdd.textContent = 'Sending...';
        const response = await fetch(`{{ route('friends.request') }}`, {
            method: 'POST',
            headers: {'Content-Type':'application/json','Accept':'application/json','X-CSRF-TOKEN':csrf},
            body: JSON.stringify({user_id: currentFriendId})
        });
        profileAdd.textContent = response.ok ? 'Request pending' : 'Add friend';
        profileAdd.disabled = response.ok;
    });

    themeForm.querySelectorAll('[data-theme-control]').forEach(control => {
        const hex = control.querySelector('.fr-theme-control__hex');
        const swatch = control.querySelector('[data-theme-swatch]');
        swatch.addEventListener('input', () => {
            hex.value = swatch.value.toUpperCase();
            themeForm.dispatchEvent(new Event('input', {bubbles: true}));
        });
        hex.addEventListener('input', () => {
            if (/^#[0-9a-f]{6}$/i.test(hex.value)) swatch.value = hex.value;
        });
        hex.addEventListener('blur', () => {
            hex.value = validThemeColor(hex.value, swatch.value).toUpperCase();
        });
    });

    themeForm.addEventListener('input', event => {
        if (event.target.matches('[data-theme-swatch]')) return;
        applyProfileTheme({
            accent: themeForm.elements.profile_accent_color.value,
            accent_opacity: themeForm.elements.profile_accent_opacity.value,
            secondary: themeForm.elements.profile_secondary_color.value,
            secondary_opacity: themeForm.elements.profile_secondary_opacity.value,
            surface: themeForm.elements.profile_surface_color.value,
            surface_opacity: themeForm.elements.profile_surface_opacity.value,
            text: themeForm.elements.profile_text_color.value,
            text_opacity: themeForm.elements.profile_text_opacity.value,
        });
    });

    themeForm.addEventListener('submit', async event => {
        event.preventDefault();
        editorStatus.textContent = 'Saving colors...';
        const response = await fetch(profileEditor.dataset.themeUrl, {
            method: 'PATCH',
            headers: {'Content-Type':'application/json','Accept':'application/json','X-CSRF-TOKEN':csrf},
            body: JSON.stringify(Object.fromEntries(new FormData(themeForm))),
        });
        const payload = await response.json().catch(() => ({}));
        editorStatus.textContent = response.ok ? 'Colors saved.' : (payload.message || 'Colors could not be saved.');
    });

    modal.querySelectorAll('[data-profile-upload]').forEach(button => {
        button.addEventListener('click', () => {
            modal.querySelector(`[data-profile-file="${button.dataset.profileUpload}"]`)?.click();
        });
    });

    modal.querySelectorAll('[data-profile-file]').forEach(input => {
        input.addEventListener('change', async () => {
            let file = input.files?.[0];
            if (!file) return;
            const kind = input.dataset.profileFile;
            const button = modal.querySelector(`[data-profile-upload="${kind}"]`);
            button.disabled = true;
            editorStatus.textContent = `Updating ${kind === 'avatar' ? 'profile photo' : kind}...`;
            try {
                if (file.size > 25 * 1024 * 1024) {
                    throw new Error('Background videos must be 25 MB or smaller.');
                }
                if (file.type.startsWith('image/') && file.type !== 'image/gif' && window.ProgressLabImageOptimizer) {
                    const optimized = await window.ProgressLabImageOptimizer.optimize(file, {
                        maxDimension: kind === 'cover' ? 1920 : 1600,
                        targetBytes: kind === 'avatar' ? 500 * 1024 : 900 * 1024,
                        quality: .85,
                        baseName: kind,
                    });
                    file = optimized.file;
                }
                const formData = new FormData();
                formData.append('_method', 'PUT');
                formData.append(kind, file);
                const endpoint = profileEditor.dataset[`${kind}Url`];
                const response = await fetch(endpoint, {
                    method: 'POST',
                    headers: {'Accept':'application/json','X-CSRF-TOKEN':csrf},
                    body: formData,
                });
                const payload = await response.json().catch(() => ({}));
                if (!response.ok) throw new Error(payload.message || 'Image could not be updated.');
                window.location.reload();
            } catch (error) {
                editorStatus.textContent = error.message;
                button.disabled = false;
                input.value = '';
            }
        });
    });

    const profileFromUrl = urlParameters.get('open_profile') || urlParameters.get('open_friend');
    if (profileFromUrl && /^\d+$/.test(profileFromUrl)) {
        openProfileById(profileFromUrl);
    }
})();
</script>

<x-achievement-toasts />
<x-footer />
</body>
</html>
