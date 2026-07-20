<!doctype html>
<html lang="en">
<head>
  <x-seo
    title="Fitness Streaks"
    description="Review your login, nutrition, hydration, and workout consistency streaks in ProgressLab."
    robots="noindex, nofollow, noarchive"
  />

  <link rel="stylesheet" href="{{ asset('css/auth.css') }}">
</head>
<body class="auth-body">

  <x-navbar />

  <main class="pl-container">

    {{-- Page Header --}}
    <div class="pl-pagehead">
      <div class="pl-pagehead__title pl-pagehead__title--center">
        <div class="st-headicon" aria-hidden="true">🔥</div>
        <h1>Your Streaks</h1>
      </div>

      <p class="pl-pagehead__sub pl-pagehead__sub--center">
        Keep the fire alive by logging daily progress.
      </p>
    </div>

    {{-- Cards Grid --}}
    <section class="st-grid" aria-label="Streaks">
      @foreach($streaks as $s)
        <article class="st-card" data-accent="{{ $s['accent'] }}">
          <div class="st-card__icon" aria-hidden="true">{{ $s['icon'] }}</div>

          <div class="st-card__row">
            <span class="st-flame" aria-hidden="true">🔥</span>
            <span class="st-days">{{ $s['days'] }}</span>
            <span class="st-dayslabel">Days</span>
          </div>

          <div class="st-title">{{ $s['title'] }}</div>
        </article>
      @endforeach
    </section>

    {{-- Big Motivational Card --}}
    <section class="st-big" aria-label="Motivation">
      <div class="st-big__flames" aria-hidden="true">🔥 🔥 🔥</div>
      <div class="st-big__title">You're on Fire!</div>
      <div class="st-big__sub">
        {{ $dailyMessage }}
      </div>
    </section>

  </main>
<x-achievement-toasts />
<x-footer />
</body>
</html>
