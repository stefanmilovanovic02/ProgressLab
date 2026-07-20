<!doctype html>
<html lang="en">
<head>
  <x-seo
    title="Notifications"
    description="Review your private ProgressLab friend activity, achievements, requests, and system notifications."
    robots="noindex, nofollow, noarchive"
  />
  <link rel="stylesheet" href="{{ asset('css/auth.css') }}">
</head>
<body class="auth-body">

  <x-navbar />

  <main class="pl-container nt-page">
    <header class="pl-pagehead nt-head">
      <div class="pl-pagehead__title pl-pagehead__title--center">
        <div class="pl-pagehead__icon" aria-hidden="true">🔔</div>
        <h1>Notifications</h1>
      </div>
      <p class="pl-pagehead__sub pl-pagehead__sub--center">
        Friend activity, achievements, requests, and ProgressLab updates in one place.
      </p>
    </header>

    @if(session('status'))
      <div class="nt-status" role="status">{{ session('status') }}</div>
    @endif

    <section
      class="nt-push"
      data-push-settings
      data-public-key="{{ config('services.webpush.public_key') }}"
      data-store-url="{{ route('push-subscriptions.store') }}"
      data-destroy-url="{{ route('push-subscriptions.destroy') }}"
      data-test-url="{{ route('push-subscriptions.test') }}"
      aria-labelledby="push-settings-title"
    >
      <div class="nt-push__icon" aria-hidden="true">📲</div>
      <div class="nt-push__content">
        <div class="nt-push__heading">
          <div>
            <span class="nt-push__eyebrow">Mobile alerts</span>
            <h2 id="push-settings-title">Never lose a streak</h2>
          </div>
          <span class="nt-push__live">Android + iPhone</span>
        </div>
        <p>Get friend achievement alerts, ProgressLab updates, and a reminder before your login streak expires.</p>
        <p class="nt-push__status" data-push-status role="status">Checking this device…</p>
        <div class="nt-push__install" data-push-install-hint hidden>
          <strong>iPhone setup:</strong> In Safari, tap Share → Add to Home Screen. Open ProgressLab from that icon, return here, and enable notifications.
        </div>
      </div>
      <div class="nt-push__actions">
        <button class="nt-action nt-action--primary" type="button" data-push-enable hidden>Enable push</button>
        <button class="nt-action nt-action--primary" type="button" data-push-test hidden>Send test</button>
        <button class="nt-action" type="button" data-push-disable hidden>Disable on device</button>
      </div>
    </section>

    <section class="nt-toolbar" aria-label="Notification controls">
      <nav class="nt-filters" aria-label="Notification filters">
        @foreach([
          'all' => 'All',
          'unread' => 'Unread',
          'friend' => 'Friends',
          'achievement' => 'Achievements',
          'system' => 'System',
        ] as $key => $label)
          <a
            class="nt-filter {{ $filter === $key ? 'is-active' : '' }}"
            href="{{ route('notifications.index', $key === 'all' ? [] : ['filter' => $key]) }}"
          >
            <span>{{ $label }}</span>
            <strong>{{ $counts[$key] }}</strong>
          </a>
        @endforeach
      </nav>

      @if($counts['unread'] > 0)
        <form action="{{ route('notifications.read-all') }}" method="POST">
          @csrf
          <button class="nt-read-all" type="submit">Mark all as read</button>
        </form>
      @endif
    </section>

    <section class="nt-list" aria-label="Notification list">
      @forelse($notifications as $notification)
        <article class="nt-card {{ $notification->isUnread() ? 'is-unread' : '' }}">
          <div class="nt-card__icon nt-card__icon--{{ $notification->category }}" aria-hidden="true">
            {{ $notification->icon ?: '🔔' }}
          </div>

          <div class="nt-card__content">
            <div class="nt-card__topline">
              <h2>{{ $notification->title }}</h2>
              @if($notification->isUnread())
                <span class="nt-card__unread">New</span>
              @endif
            </div>
            <p>{{ $notification->message }}</p>
            <div class="nt-card__meta">
              <span class="nt-card__category">{{ ucfirst($notification->category) }}</span>
              <span aria-hidden="true">•</span>
              <time datetime="{{ $notification->created_at->toIso8601String() }}">
                {{ $notification->created_at->diffForHumans() }}
              </time>
            </div>
          </div>

          <div class="nt-card__actions">
            @if($notification->action_url)
              <form action="{{ route('notifications.open', $notification) }}" method="POST">
                @csrf
                <button class="nt-action nt-action--primary" type="submit">View</button>
              </form>
            @endif

            @if($notification->isUnread())
              <form action="{{ route('notifications.read', $notification) }}" method="POST">
                @csrf
                <button class="nt-action" type="submit">Mark read</button>
              </form>
            @endif
          </div>
        </article>
      @empty
        <div class="nt-empty">
          <div class="nt-empty__icon" aria-hidden="true">🔕</div>
          <h2>No notifications here</h2>
          <p>New friend activity, achievements, and ProgressLab updates will appear here.</p>
        </div>
      @endforelse
    </section>

    @if($notifications->hasPages())
      <nav class="nt-pagination" aria-label="Notification pages">
        @if($notifications->onFirstPage())
          <span class="is-disabled">Previous</span>
        @else
          <a href="{{ $notifications->previousPageUrl() }}" rel="prev">Previous</a>
        @endif

        <span>Page {{ $notifications->currentPage() }} of {{ $notifications->lastPage() }}</span>

        @if($notifications->hasMorePages())
          <a href="{{ $notifications->nextPageUrl() }}" rel="next">Next</a>
        @else
          <span class="is-disabled">Next</span>
        @endif
      </nav>
    @endif
  </main>

  <x-footer />
  <x-achievement-toasts />
</body>
</html>
