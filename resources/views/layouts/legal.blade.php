<!doctype html>
<html lang="en">
<head>
  <x-seo
    :title="trim($__env->yieldContent('title'))"
    :description="trim($__env->yieldContent('description'))"
    :canonical="url()->current()"
  />
  <link rel="stylesheet" href="{{ asset('css/legal.css') }}?v={{ filemtime(public_path('css/legal.css')) }}">
</head>
<body class="legal-body">
  <header class="legal-nav">
    <div class="legal-nav__inner">
      <a class="legal-nav__brand" href="{{ auth()->check() ? route('home') : route('login') }}" aria-label="ProgressLab home">
        <img src="{{ asset('images/branding/progresslab-app-192.png') }}" alt="" width="38" height="38" decoding="async">
        <span>ProgressLab</span>
      </a>

      <nav class="legal-nav__links" aria-label="Legal pages">
        <a class="{{ request()->routeIs('legal.privacy') ? 'is-active' : '' }}" href="{{ route('legal.privacy') }}">Privacy</a>
        <a class="{{ request()->routeIs('legal.terms') ? 'is-active' : '' }}" href="{{ route('legal.terms') }}">Terms</a>
        <a class="legal-nav__back" href="{{ auth()->check() ? route('home') : route('login') }}">
          {{ auth()->check() ? 'Back to app' : 'Sign in' }}
        </a>
      </nav>
    </div>
  </header>

  <main>@yield('content')</main>

  <footer class="legal-footer">
    <div>
      <a class="legal-footer__brand" href="{{ auth()->check() ? route('home') : route('login') }}">ProgressLab</a>
      <p>Track your progress. Build consistency.</p>
    </div>
    <nav aria-label="Footer links">
      <a href="{{ route('legal.privacy') }}">Privacy Policy</a>
      <a href="{{ route('legal.terms') }}">Terms of Use</a>
      <a href="mailto:progresslabsupport@gmail.com">Contact</a>
    </nav>
    <p>&copy; {{ now()->year }} ProgressLab. All rights reserved.</p>
  </footer>
</body>
</html>
