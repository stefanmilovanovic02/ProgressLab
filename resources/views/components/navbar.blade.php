<nav class="pl-nav">
  <div class="pl-nav__inner">

    {{-- Left: Brand --}}
    <a href="{{ route('home') }}" class="pl-nav__brand">
      <span class="pl-nav__brand-badge" aria-hidden="true">
        {{-- simple logo mark (you can swap later) --}}
        <span class="pl-nav__brand-dot"></span>
      </span>
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
      <a class="pl-nav__link {{ request()->routeIs('profile.*') ? 'is-active' : '' }}" href="{{ route('profile.show') }}">Profile</a>

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
