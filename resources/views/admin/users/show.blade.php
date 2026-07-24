<!doctype html>
<html lang="en">
<head>
  <x-seo title="User Statistics" description="ProgressLab user statistics." robots="noindex, nofollow, noarchive" />
  <link rel="stylesheet" href="{{ asset('css/auth.css') }}">
  <link rel="stylesheet" href="{{ asset('css/admin.css') }}">
  <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
</head>
<body class="auth-body">
  <x-navbar />
  <main class="pl-container ad-wrap">
    <header class="ad-head">
      <div class="ad-profile-title">
        <img src="{{ $user->avatar_url }}" alt="" width="64" height="64">
        <div><span class="ad-eyebrow">User statistics</span><h1>{{ $user->full_name ?? $user->name }}</h1><p>{{ $user->email }} · {{ '@' . ($user->username ?? '—') }}</p></div>
      </div>
      <div class="ad-actions">
        <span class="ad-role-pill ad-role-pill--{{ $user->role->value }}">{{ $user->role->label() }}</span>
        @if(!$user->isOwner() && (auth()->user()->isOwner() || !$user->isAdmin()))
          <a class="ad-button ad-button--secondary" href="{{ route('admin.users.edit', $user) }}">Edit account</a>
        @endif
      </div>
    </header>
    @include('admin.partials.navigation')

    <div class="ad-privacy-banner">
      <strong>Privacy protected</strong>
      Progress photos are never loaded or displayed in the admin area. Streaks below are calculated from activity and are view-only.
    </div>

    <section class="ad-stat-grid ad-stat-grid--user">
      @foreach([
        ['Rank', $stats['summary']['rank']['rank'] . ' ' . $stats['summary']['rank']['level'], '🏅'],
        ['Total XP', number_format($stats['summary']['total_xp']), '◆'],
        ['Workouts', number_format($stats['summary']['workouts']), '🏋️'],
        ['Nutrition days', number_format($stats['summary']['nutrition_days']), '🥗'],
        ['Achievements', number_format($stats['summary']['achievements']), '🏆'],
        ['Friends', number_format($stats['summary']['friends']), '👥'],
      ] as [$label, $value, $icon])
        <article class="ad-stat"><span class="ad-stat__icon">{{ $icon }}</span><strong>{{ $value }}</strong><span>{{ $label }}</span></article>
      @endforeach
    </section>

    <section class="ad-card">
      <div class="ad-card__head"><div><span class="ad-eyebrow">View only</span><h2>Current streaks</h2></div><span class="ad-lock">🔒 Not editable</span></div>
      <div class="ad-streak-grid">
        <div><strong>{{ $stats['streaks']['login'] }}</strong><span>Login streak</span></div>
        <div><strong>{{ $stats['streaks']['workout'] }}</strong><span>Workout streak</span></div>
        <div><strong>{{ $stats['streaks']['nutrition'] }}</strong><span>Nutrition streak</span></div>
      </div>
    </section>

    <div class="ad-dashboard-grid">
      <section class="ad-card"><div class="ad-card__head"><div><span class="ad-eyebrow">Last 30 days</span><h2>Nutrition</h2></div></div><div class="ad-chart"><canvas id="adminNutritionChart"></canvas></div></section>
      <section class="ad-card"><div class="ad-card__head"><div><span class="ad-eyebrow">Last 30 days</span><h2>Workout volume</h2></div></div><div class="ad-chart"><canvas id="adminWorkoutChart"></canvas></div></section>
    </div>

    <div class="ad-dashboard-grid">
      <section class="ad-card">
        <div class="ad-card__head"><h2>Recent nutrition</h2></div>
        <div class="ad-table-wrap"><table class="ad-table"><thead><tr><th>Date</th><th>Calories</th><th>Protein</th><th>Carbs</th><th>Fat</th></tr></thead><tbody>
          @forelse($stats['recentNutrition'] as $entry)<tr><td>{{ \Illuminate\Support\Carbon::parse($entry->entry_date)->format('M j, Y') }}</td><td>{{ $entry->calories }}</td><td>{{ $entry->protein_g }}g</td><td>{{ $entry->carbs_g }}g</td><td>{{ $entry->fat_g }}g</td></tr>@empty<tr><td colspan="5" class="ad-empty">No nutrition records.</td></tr>@endforelse
        </tbody></table></div>
      </section>
      <section class="ad-card">
        <div class="ad-card__head"><h2>Recent workouts</h2></div>
        <div class="ad-table-wrap"><table class="ad-table"><thead><tr><th>Date</th><th>Workout</th><th>Status</th></tr></thead><tbody>
          @forelse($stats['recentWorkouts'] as $workout)<tr><td>{{ \Illuminate\Support\Carbon::parse($workout->entry_date)->format('M j, Y') }}</td><td>{{ $workout->name ?? 'Workout' }}</td><td>{{ $workout->completed_at ? 'Completed' : 'In progress' }}</td></tr>@empty<tr><td colspan="3" class="ad-empty">No workout records.</td></tr>@endforelse
        </tbody></table></div>
      </section>
    </div>

    @if(!$user->isOwner() && !auth()->user()->is($user) && (auth()->user()->isOwner() || !$user->isAdmin()))
      <section class="ad-card ad-danger-zone">
        <div><h2>Delete account</h2><p>Permanently deletes this account and its tracking data. This cannot be undone.</p></div>
        <form method="POST" action="{{ route('admin.users.destroy', $user) }}" onsubmit="return confirm('Delete this user and all associated tracking data?')">@csrf @method('DELETE')<button class="ad-button ad-button--danger" type="submit">Delete user</button></form>
      </section>
    @endif
  </main>
  <x-footer />
  <script>
    (() => {
      const labels = @json($stats['charts']['labels']);
      const grid = 'rgba(255,255,255,.07)';
      const ticks = 'rgba(225,232,244,.55)';
      const scales = { x: { grid: { display: false }, ticks: { color: ticks, maxTicksLimit: 8 } }, y: { beginAtZero: true, grid: { color: grid }, ticks: { color: ticks } } };
      new Chart(document.getElementById('adminNutritionChart'), { type: 'line', data: { labels, datasets: [{ label: 'Calories', data: @json($stats['charts']['calories']), borderColor: '#ef5b78', tension: .32 }, { label: 'Protein (g)', data: @json($stats['charts']['protein']), borderColor: '#65e6d4', tension: .32 }] }, options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { labels: { color: ticks } } }, scales } });
      new Chart(document.getElementById('adminWorkoutChart'), { type: 'bar', data: { labels, datasets: [{ label: 'Volume (kg)', data: @json($stats['charts']['workoutVolume']), backgroundColor: 'rgba(59,130,246,.58)', borderRadius: 5 }] }, options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { display: false } }, scales } });
    })();
  </script>
</body>
</html>
