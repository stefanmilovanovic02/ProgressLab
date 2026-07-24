<!doctype html>
<html lang="en">
<head><x-seo title="Subscriptions" description="ProgressLab owner subscription management." robots="noindex, nofollow, noarchive" /><link rel="stylesheet" href="{{ asset('css/auth.css') }}"><link rel="stylesheet" href="{{ asset('css/admin.css') }}"></head>
<body class="auth-body"><x-navbar /><main class="pl-container ad-wrap">
  <header class="ad-head"><div><span class="ad-eyebrow">Owner only</span><h1>Subscriptions</h1><p>Manage billing records used by the business overview.</p></div><a class="ad-button" href="{{ route('admin.subscriptions.create') }}">＋ New subscription</a></header>
  @include('admin.partials.navigation')
  <section class="ad-card">
    <form class="ad-toolbar" method="GET">
      <label><span class="ad-label">Search users</span><input name="search" value="{{ $search }}" placeholder="Name, username, or email"></label>
      <label><span class="ad-label">Status</span><select name="status"><option value="">All statuses</option>@foreach($statuses as $value => $label)<option value="{{ $value }}" @selected($status === $value)>{{ $label }}</option>@endforeach</select></label>
      <button class="ad-button ad-button--secondary" type="submit">Filter</button>
    </form>
    <div class="ad-table-wrap"><table class="ad-table"><thead><tr><th>User</th><th>Plan</th><th>Status</th><th>Paid</th><th>Period</th><th></th></tr></thead><tbody>
      @forelse($subscriptions as $subscription)
        <tr>
          <td><strong>{{ $subscription->user->full_name ?? $subscription->user->name }}</strong><small>{{ $subscription->user->email }}</small></td>
          <td>{{ ucfirst($subscription->plan) }}</td>
          <td><span class="ad-status ad-status--{{ $subscription->status }}">{{ ucfirst($subscription->status) }}</span></td>
          <td>€{{ number_format((float) $subscription->amount_paid, 2) }}</td>
          <td>{{ $subscription->starts_on->format('M j, Y') }}<small>{{ $subscription->ends_on ? 'to ' . $subscription->ends_on->format('M j, Y') : 'No end date' }}</small></td>
          <td><a class="ad-table-link" href="{{ route('admin.subscriptions.edit', $subscription) }}">Edit</a></td>
        </tr>
      @empty<tr><td colspan="6" class="ad-empty">No subscription records match your filters.</td></tr>@endforelse
    </tbody></table></div>
    <div class="ad-pagination">@if($subscriptions->previousPageUrl())<a href="{{ $subscriptions->previousPageUrl() }}">← Previous</a>@endif<span>Page {{ $subscriptions->currentPage() }} of {{ $subscriptions->lastPage() }}</span>@if($subscriptions->nextPageUrl())<a href="{{ $subscriptions->nextPageUrl() }}">Next →</a>@endif</div>
  </section>
</main><x-footer /></body></html>
