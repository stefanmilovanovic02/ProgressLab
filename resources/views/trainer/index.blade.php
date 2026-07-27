<!doctype html>
<html lang="en">
<head>
  <x-seo title="Trainer Clients" description="Manage accepted ProgressLab clients and review shared progress." robots="noindex, nofollow, noarchive" />
  <link rel="stylesheet" href="{{ asset('css/auth.css') }}?v={{ filemtime(public_path('css/auth.css')) }}">
  <link rel="stylesheet" href="{{ asset('css/admin.css') }}?v={{ filemtime(public_path('css/admin.css')) }}">
</head>
<body class="auth-body">
  <x-navbar />
  <main class="pl-container ad-wrap">
    <header class="ad-head">
      <div><span class="ad-eyebrow">Trainer workspace</span><h1>Clients</h1><p>Review only the information each client has explicitly shared.</p></div>
      <span class="ad-role ad-role-pill--trainer">Trainer</span>
    </header>

    @if(session('status'))<div class="ad-alert ad-alert--success">{{ session('status') }}</div>@endif

    <section class="ad-stat-grid ad-stat-grid--trainer">
      @foreach([
        ['Total clients', $summary['total'], '🤝'],
        ['Active this week', $summary['active_this_week'], '⚡'],
        ['Missing nutrition today', $summary['missing_nutrition'], '🥗'],
        ['Streak at risk', $summary['streak_at_risk'], '🔥'],
      ] as [$label, $value, $icon])
        <article class="ad-stat"><span class="ad-stat__icon">{{ $icon }}</span><strong>{{ $value }}</strong><span>{{ $label }}</span></article>
      @endforeach
    </section>

    @if($pending->isNotEmpty())
      <section class="ad-card">
        <div class="ad-card__head"><div><span class="ad-eyebrow">Waiting for consent</span><h2>Pending invitations</h2></div><span class="ad-lock">{{ $pending->count() }} pending</span></div>
        <div class="tr-client-grid">
          @foreach($pending as $relationship)
            <article class="tr-client-card is-pending">
              <img src="{{ $relationship->client->avatar_url }}" alt="" width="48" height="48">
              <div><strong>{{ $relationship->client->full_name ?? $relationship->client->name }}</strong><span>{{ '@' . ($relationship->client->username ?? 'user') }}</span></div>
              <span class="ad-status ad-status--pending">Pending</span>
            </article>
          @endforeach
        </div>
      </section>
    @endif

    <section class="ad-card">
      <div class="ad-card__head"><div><span class="ad-eyebrow">Accepted access</span><h2>Client list</h2></div><input class="tr-search" type="search" placeholder="Search clients..." data-client-search></div>
      <div class="ad-table-wrap">
        <table class="ad-table">
          <thead><tr><th>Client</th><th>Last login</th><th>Nutrition today</th><th>Shared areas</th><th></th></tr></thead>
          <tbody data-client-list>
            @forelse($clients as $row)
              <tr data-client-name="{{ strtolower(($row['client']->full_name ?? $row['client']->name) . ' ' . $row['client']->email) }}">
                <td><div class="ad-user-cell"><img src="{{ $row['client']->avatar_url }}" alt="" width="38" height="38"><div><strong>{{ $row['client']->full_name ?? $row['client']->name }}</strong><small>{{ $row['client']->email }}</small></div></div></td>
                <td>{{ $row['relationship']->can_view_streaks ? ($row['last_login'] ? \Illuminate\Support\Carbon::parse($row['last_login'])->diffForHumans() : 'No login recorded') : 'Not shared' }}@if($row['streak_at_risk'])<small class="tr-risk">Streak may be at risk</small>@endif</td>
                <td>@if($row['nutrition_today'] === null)<span class="ad-status">Not shared</span>@else<span class="ad-status {{ $row['nutrition_today'] ? 'ad-status--active' : 'ad-status--pending' }}">{{ $row['nutrition_today'] ? 'Logged' : 'Missing' }}</span>@endif</td>
                <td><div class="tr-permission-dots">
                  @foreach(['nutrition' => 'N', 'exercises' => 'E', 'weight' => 'W', 'streaks' => 'S'] as $permission => $letter)
                    <span class="{{ $row['relationship']->getAttribute('can_view_' . $permission) ? 'is-on' : '' }}" title="{{ ucfirst($permission) }}">{{ $letter }}</span>
                  @endforeach
                </div></td>
                <td><a class="ad-table-link" href="{{ route('trainer.clients.show', $row['client']) }}">Open dashboard</a></td>
              </tr>
            @empty
              <tr><td colspan="5" class="ad-empty">No accepted clients yet. Open a friend’s profile and choose “Invite as Client.”</td></tr>
            @endforelse
          </tbody>
        </table>
      </div>
    </section>

    <section class="ad-card">
      <div class="ad-card__head"><div><span class="ad-eyebrow">Shared exercise data</span><h2>Recent personal records</h2></div><span class="ad-lock">Last 14 days</span></div>
      <div class="tr-record-grid">
        @forelse($recentRecords as $record)
          <article><span>🏆</span><div><strong>{{ $record->client_full_name ?? $record->client_name }}</strong><p>{{ $record->exercise_name }} · {{ (float) $record->weight_kg }} kg × {{ $record->reps ?? '—' }}</p></div><time>{{ \Illuminate\Support\Carbon::parse($record->entry_date)->format('M j') }}</time></article>
        @empty
          <div class="ad-empty">No shared personal records in the last 14 days.</div>
        @endforelse
      </div>
    </section>

    <div class="ad-privacy-banner"><strong>Client privacy</strong> Friendship alone never grants access. Clients choose each shared area and may revoke access at any time. Progress photos are never available here.</div>
  </main>
  <x-footer />
  <script>
    (() => {
      const input = document.querySelector('[data-client-search]');
      if (!input) return;
      input.addEventListener('input', () => {
        const query = input.value.trim().toLowerCase();
        document.querySelectorAll('[data-client-name]').forEach(row => {
          row.hidden = !row.dataset.clientName.includes(query);
        });
      });
    })();
  </script>
</body>
</html>
