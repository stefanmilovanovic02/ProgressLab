<!doctype html>
<html lang="en">
<head>
  <x-seo title="Admin Dashboard" description="ProgressLab administration dashboard." robots="noindex, nofollow, noarchive" />
  <link rel="stylesheet" href="{{ asset('css/auth.css') }}?v={{ filemtime(public_path('css/auth.css')) }}">
  <link rel="stylesheet" href="{{ asset('css/admin.css') }}?v={{ filemtime(public_path('css/admin.css')) }}">
  <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
</head>
<body class="auth-body">
  <x-navbar />
  <main class="pl-container ad-wrap">
    <header class="ad-head">
      <div>
        <span class="ad-eyebrow">Administration</span>
        <h1>Dashboard</h1>
        <p>Manage the platform while respecting private user data.</p>
      </div>
      <span class="ad-role">{{ auth()->user()->role->label() }}</span>
    </header>

    @include('admin.partials.navigation')

    @if($ownerMetrics)
      <section class="ad-owner-panel">
        <div class="ad-card__head">
          <div>
            <span class="ad-eyebrow">Owner only</span>
            <h2>Business overview</h2>
          </div>
          <a class="ad-button ad-button--secondary" href="{{ route('admin.subscriptions.index') }}">Manage subscriptions</a>
        </div>
        <div class="ad-owner-metrics">
          <article><span>Total users</span><strong>{{ number_format($stats['users']) }}</strong></article>
          <article><span>Active subscriptions</span><strong>{{ number_format($ownerMetrics['active_subscriptions']) }}</strong><small>{{ number_format($ownerMetrics['subscriptions']) }} recorded</small></article>
          <article><span>Complimentary access</span><strong>{{ number_format($ownerMetrics['complimentary_access']) }}</strong><small>Not counted as paid</small></article>
          <article><span>Total revenue</span><strong>€{{ number_format($ownerMetrics['revenue'], 2) }}</strong></article>
          <article><span>This month</span><strong>€{{ number_format($ownerMetrics['monthly_revenue'], 2) }}</strong></article>
          <article><span>Pending payments</span><strong>{{ number_format($ownerMetrics['pending_requests']) }}</strong><small>Needs verification</small></article>
        </div>
      </section>

      <section class="ad-card">
        <div class="ad-card__head">
          <div><span class="ad-eyebrow">Owner only · verify in PayPal</span><h2>Activation requests</h2></div>
          <span class="ad-lock">{{ $pendingPaymentRequests->count() }} pending</span>
        </div>
        <p class="ad-section-copy">Approve only after the PayPal email, exact amount, and transaction ID match a completed payment in your PayPal Activity.</p>
        <div class="ad-table-wrap">
          <table class="ad-table">
            <thead><tr><th>User</th><th>Plan</th><th>Amount</th><th>PayPal details</th><th>Submitted</th><th>Review</th></tr></thead>
            <tbody>
              @forelse($pendingPaymentRequests as $paymentRequest)
                <tr>
                  <td><strong>{{ $paymentRequest->user->full_name ?? $paymentRequest->user->name }}</strong><small>{{ $paymentRequest->user->email }}</small></td>
                  <td><span class="ad-role-pill ad-role-pill--{{ $paymentRequest->plan }}">{{ $paymentRequest->plan === 'trainer' ? 'Trainer' : 'ProgressLab+' }}</span></td>
                  <td>€{{ number_format((float) $paymentRequest->amount, 2) }}</td>
                  <td><strong>{{ $paymentRequest->paypal_transaction_id }}</strong><small>{{ $paymentRequest->paypal_email }}</small></td>
                  <td>{{ $paymentRequest->created_at->diffForHumans() }}</td>
                  <td>
                    <div class="ad-payment-actions">
                      <form method="POST" action="{{ route('admin.subscription-requests.approve', $paymentRequest) }}" onsubmit="return confirm('I verified this completed PayPal payment. Activate the account for 30 days?')">
                        @csrf
                        <button class="ad-button" type="submit">Verify & activate</button>
                      </form>
                      <form method="POST" action="{{ route('admin.subscription-requests.reject', $paymentRequest) }}" onsubmit="return confirm('Reject this activation request?')">
                        @csrf
                        <input type="hidden" name="owner_notes" value="Payment could not be verified.">
                        <button class="ad-button ad-button--danger" type="submit">Reject</button>
                      </form>
                    </div>
                  </td>
                </tr>
              @empty
                <tr><td colspan="6" class="ad-empty">No payment claims are waiting for verification.</td></tr>
              @endforelse
            </tbody>
          </table>
        </div>
      </section>
    @endif

    <section class="ad-stat-grid">
      @foreach([
        ['Users', $stats['users'], '👥'],
        ['Paid', $stats['paid'], '◆'],
        ['Trainers', $stats['trainers'], '🏋️'],
        ['Staff', $stats['staff'], '🛡️'],
        ['Exercises', $stats['exercises'], '＋'],
        ['Workout logs', $stats['workouts'], '📈'],
      ] as [$label, $value, $icon])
        <article class="ad-stat">
          <span class="ad-stat__icon">{{ $icon }}</span>
          <strong>{{ number_format($value) }}</strong>
          <span>{{ $label }}</span>
        </article>
      @endforeach
    </section>

    <div class="ad-dashboard-grid">
      <section class="ad-card">
        <div class="ad-card__head">
          <div><span class="ad-eyebrow">Last 14 days</span><h2>Active users</h2></div>
        </div>
        <div class="ad-chart"><canvas id="adminActivityChart"></canvas></div>
      </section>

      <section class="ad-card">
        <div class="ad-card__head">
          <div><span class="ad-eyebrow">Accounts</span><h2>Role distribution</h2></div>
        </div>
        <div class="ad-chart ad-chart--small"><canvas id="adminRolesChart"></canvas></div>
      </section>
    </div>

    <section class="ad-card">
      <div class="ad-card__head">
        <div><span class="ad-eyebrow">Recently joined</span><h2>Latest users</h2></div>
        <a class="ad-button ad-button--secondary" href="{{ route('admin.users.index') }}">Manage users</a>
      </div>
      <div class="ad-table-wrap">
        <table class="ad-table">
          <thead><tr><th>User</th><th>Email</th><th>Role</th><th>Joined</th><th></th></tr></thead>
          <tbody>
            @forelse($recentUsers as $user)
              <tr>
                <td><strong>{{ $user->full_name ?? $user->name }}</strong><small>{{ '@' . ($user->username ?? '—') }}</small></td>
                <td>{{ $user->email }}</td>
                <td><span class="ad-role-pill ad-role-pill--{{ $user->role->value }}">{{ $user->role->label() }}</span></td>
                <td>{{ $user->created_at?->format('M j, Y') }}</td>
                <td><a class="ad-table-link" href="{{ route('admin.users.show', $user) }}">View</a></td>
              </tr>
            @empty
              <tr><td colspan="5" class="ad-empty">No users yet.</td></tr>
            @endforelse
          </tbody>
        </table>
      </div>
    </section>
  </main>
  <x-footer />
  <script>
    (() => {
      const grid = 'rgba(255,255,255,.07)';
      const ticks = 'rgba(225,232,244,.55)';
      new Chart(document.getElementById('adminActivityChart'), {
        type: 'line',
        data: { labels: @json($activityLabels), datasets: [{ data: @json($activityValues), borderColor: '#3b82f6', backgroundColor: 'rgba(59,130,246,.12)', fill: true, tension: .35, pointRadius: 3 }] },
        options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { display: false } }, scales: { x: { grid: { display: false }, ticks: { color: ticks } }, y: { beginAtZero: true, grid: { color: grid }, ticks: { color: ticks, precision: 0 } } } }
      });
      const roleData = @json($roleCounts);
      new Chart(document.getElementById('adminRolesChart'), {
        type: 'doughnut',
        data: { labels: Object.keys(roleData), datasets: [{ data: Object.values(roleData), backgroundColor: ['#4b91ff','#a875ff','#f6c945','#ef5b78','#fff2a8'], borderColor: '#0c1423', borderWidth: 3 }] },
        options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { position: 'bottom', labels: { color: ticks, usePointStyle: true } } } }
      });
    })();
  </script>
</body>
</html>
