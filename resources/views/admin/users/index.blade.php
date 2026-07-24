<!doctype html>
<html lang="en">
<head>
  <x-seo title="Manage Users" description="ProgressLab user administration." robots="noindex, nofollow, noarchive" />
  <link rel="stylesheet" href="{{ asset('css/auth.css') }}">
  <link rel="stylesheet" href="{{ asset('css/admin.css') }}">
</head>
<body class="auth-body">
  <x-navbar />
  <main class="pl-container ad-wrap">
    <header class="ad-head">
      <div><span class="ad-eyebrow">Administration</span><h1>Users</h1><p>Manage accounts and review tracking statistics.</p></div>
      <a class="ad-button" href="{{ route('admin.users.create') }}">＋ New user</a>
    </header>
    @include('admin.partials.navigation')

    <section class="ad-card">
      <form class="ad-toolbar" method="GET">
        <label><span class="ad-label">Search</span><input name="search" value="{{ $search }}" placeholder="Name, username, or email"></label>
        <label><span class="ad-label">Role</span>
          <select name="role">
            <option value="">All roles</option>
            @foreach(\App\Enums\UserRole::cases() as $roleOption)
              <option value="{{ $roleOption->value }}" @selected($role === $roleOption->value)>{{ $roleOption->label() }}</option>
            @endforeach
          </select>
        </label>
        <button class="ad-button ad-button--secondary" type="submit">Filter</button>
      </form>

      <div class="ad-table-wrap">
        <table class="ad-table">
          <thead><tr><th>User</th><th>Role</th><th>Rank activity</th><th>Joined</th><th>Actions</th></tr></thead>
          <tbody>
            @forelse($users as $user)
              <tr>
                <td>
                  <div class="ad-user-cell">
                    <img src="{{ $user->avatar_url }}" alt="" width="42" height="42">
                    <div><strong>{{ $user->full_name ?? $user->name }}</strong><small>{{ $user->email }} · {{ '@' . ($user->username ?? '—') }}</small></div>
                  </div>
                </td>
                <td><span class="ad-role-pill ad-role-pill--{{ $user->role->value }}">{{ $user->role->label() }}</span></td>
                <td>{{ $user->experience_events_count }} XP events · {{ $user->exercise_ranks_count }} exercise ranks</td>
                <td>{{ $user->created_at?->format('M j, Y') }}</td>
                <td><div class="ad-actions"><a href="{{ route('admin.users.show', $user) }}">Stats</a><a href="{{ route('admin.users.edit', $user) }}">Edit</a></div></td>
              </tr>
            @empty
              <tr><td colspan="5" class="ad-empty">No users match these filters.</td></tr>
            @endforelse
          </tbody>
        </table>
      </div>

      <div class="ad-pagination">
        @if($users->previousPageUrl())<a href="{{ $users->previousPageUrl() }}">← Previous</a>@endif
        <span>Page {{ $users->currentPage() }} of {{ $users->lastPage() }}</span>
        @if($users->nextPageUrl())<a href="{{ $users->nextPageUrl() }}">Next →</a>@endif
      </div>
    </section>
  </main>
  <x-footer />
</body>
</html>
