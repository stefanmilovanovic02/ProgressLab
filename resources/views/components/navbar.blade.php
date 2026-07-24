<nav class="pl-nav">
  <div class="pl-nav__inner">

    {{-- Left: Brand --}}
    <a href="{{ route('home') }}" class="pl-nav__brand" aria-label="ProgressLab home">
      <img
        class="pl-nav__brand-logo"
        src="{{ asset('images/branding/progresslab-logo.png') }}?v=2"
        alt=""
        width="34"
        height="34"
      >
      <span class="pl-nav__brand-text">ProgressLab</span>
    </a>

    {{-- Mobile toggle --}}
    <button class="pl-nav__toggle" type="button" aria-label="Toggle menu" data-pl-nav-toggle>
      <span class="pl-nav__toggle-line"></span>
      <span class="pl-nav__toggle-line"></span>
      <span class="pl-nav__toggle-line"></span>
    </button>

    {{-- Center/Right: Links --}}
    <div class="pl-nav__menu" data-pl-nav-menu>
      <a class="pl-nav__link {{ request()->routeIs('home') ? 'is-active' : '' }}" href="{{ route('home') }}">Home</a>
      <a class="pl-nav__link {{ request()->routeIs('add-today') ? 'is-active' : '' }}" href="{{ route('add-today') }}">Add Today</a>
      <a class="pl-nav__link {{ request()->routeIs('workouts.*') ? 'is-active' : '' }}" href="{{ route('workouts.index') }}">Workouts</a>
      <a class="pl-nav__link {{ request()->routeIs('charts.*') ? 'is-active' : '' }}" href="{{ route('charts.index') }}">Charts</a>
      <a class="pl-nav__link {{ request()->routeIs('streaks.*') ? 'is-active' : '' }}" href="{{ route('streaks.index') }}">Streaks</a>
      <a class="pl-nav__link {{ request()->routeIs('achievements.*') ? 'is-active' : '' }}" href="{{ route('achievements.index') }}">Achievements</a>
      <a class="pl-nav__link {{ request()->routeIs('friends.*') ? 'is-active' : '' }}" href="{{ route('friends.index') }}">Friends</a>
      <a class="pl-nav__link {{ request()->routeIs('leaderboards.*') ? 'is-active' : '' }}" href="{{ route('leaderboards.index') }}">Leaderboard</a>
      <a class="pl-nav__link {{ request()->routeIs('profile.*') ? 'is-active' : '' }}" href="{{ route('profile.show') }}">Profile</a>
      @if(auth()->user()?->isAdmin())
        <a class="pl-nav__link {{ request()->routeIs('admin.*') ? 'is-active' : '' }}" href="{{ route('admin.dashboard') }}">Admin</a>
      @endif

      <a
        class="pl-nav__notifications {{ request()->routeIs('notifications.*') ? 'is-active' : '' }}"
        href="{{ route('notifications.index') }}"
        aria-label="Notifications{{ $unreadNotificationCount ? ': ' . $unreadNotificationCount . ' unread' : '' }}"
      >
        <svg class="pl-nav__notifications-icon" viewBox="0 0 24 24" aria-hidden="true">
          <path d="M18 8a6 6 0 0 0-12 0c0 7-3 7-3 9h18c0-2-3-2-3-9"></path>
          <path d="M10 21h4"></path>
        </svg>
        <span class="pl-nav__notifications-label">Notifications</span>
        @if($unreadNotificationCount > 0)
          <span class="pl-nav__notifications-badge" aria-hidden="true">
            {{ $unreadNotificationCount > 99 ? '99+' : $unreadNotificationCount }}
          </span>
        @endif
      </a>

      <form class="pl-nav__logout" action="{{ route('logout') }}" method="POST">
        @csrf
        <button type="submit" class="pl-nav__logout-btn">
          <span class="pl-nav__logout-ic" aria-hidden="true">↳</span>
          <span>Log Out</span>
        </button>
      </form>
    </div>

  </div>

  {{-- Tiny JS for responsive toggle --}}
  <script>
    (function () {
      const toggle = document.querySelector('[data-pl-nav-toggle]');
      const menu = document.querySelector('[data-pl-nav-menu]');
      if (!toggle || !menu) return;

      toggle.addEventListener('click', () => {
        menu.classList.toggle('is-open');
      });
    })();
  </script>
</nav>
