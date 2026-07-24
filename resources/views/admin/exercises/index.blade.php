<!doctype html>
<html lang="en">
<head><x-seo title="Manage Exercises" description="ProgressLab exercise administration." robots="noindex, nofollow, noarchive" /><link rel="stylesheet" href="{{ asset('css/auth.css') }}"><link rel="stylesheet" href="{{ asset('css/admin.css') }}"></head>
<body class="auth-body"><x-navbar /><main class="pl-container ad-wrap">
  <header class="ad-head"><div><span class="ad-eyebrow">Administration</span><h1>Exercises</h1><p>Manage the global exercise catalogue and strength standards.</p></div><a class="ad-button" href="{{ route('admin.exercises.create') }}">＋ New exercise</a></header>
  @include('admin.partials.navigation')
  <section class="ad-card">
    <form class="ad-toolbar" method="GET"><label><span class="ad-label">Search</span><input name="search" value="{{ $search }}" placeholder="Exercise or muscle group"></label><button class="ad-button ad-button--secondary" type="submit">Filter</button></form>
    <div class="ad-table-wrap"><table class="ad-table"><thead><tr><th>Exercise</th><th>Ranking method</th><th>Olympian target</th><th>Usage</th><th></th></tr></thead><tbody>
      @forelse($exercises as $exercise)
        <tr>
          <td><strong>{{ $exercise->name }}</strong><small>{{ $exercise->muscle_group ?: 'No muscle group' }}</small></td>
          <td>{{ str_replace('_', ' ', $exercise->rankStandard?->scoring_type ?? 'not configured') }}</td>
          <td>{{ $exercise->rankStandard?->olympian_target ? rtrim(rtrim(number_format($exercise->rankStandard->olympian_target, 2), '0'), '.') . ' ' . $exercise->rankStandard->unit : 'Disabled' }}</td>
          <td>{{ $exercise->workouts_count }} plans · {{ $exercise->user_ranks_count }} ranked users</td>
          <td><a class="ad-table-link" href="{{ route('admin.exercises.edit', $exercise) }}">Edit</a></td>
        </tr>
      @empty<tr><td colspan="5" class="ad-empty">No exercises match your search.</td></tr>@endforelse
    </tbody></table></div>
    <div class="ad-pagination">@if($exercises->previousPageUrl())<a href="{{ $exercises->previousPageUrl() }}">← Previous</a>@endif<span>Page {{ $exercises->currentPage() }} of {{ $exercises->lastPage() }}</span>@if($exercises->nextPageUrl())<a href="{{ $exercises->nextPageUrl() }}">Next →</a>@endif</div>
  </section>
</main><x-footer /></body></html>
