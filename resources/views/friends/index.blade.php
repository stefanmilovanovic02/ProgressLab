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

</body>
</html>