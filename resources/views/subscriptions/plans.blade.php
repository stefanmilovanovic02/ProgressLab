<!doctype html>
<html lang="en">
<head>
  <x-seo title="Plans" description="Compare ProgressLab Free, ProgressLab+, and Trainer access." robots="noindex, nofollow, noarchive" />
  <link rel="stylesheet" href="{{ asset('css/auth.css') }}?v={{ filemtime(public_path('css/auth.css')) }}">
  <link rel="stylesheet" href="{{ asset('css/pricing.css') }}?v={{ filemtime(public_path('css/pricing.css')) }}">
</head>
<body class="auth-body">
  <x-navbar />
  <main class="pr-page">
    <div class="pr-glow pr-glow--left" aria-hidden="true"></div>
    <div class="pr-glow pr-glow--right" aria-hidden="true"></div>

    <header class="pr-hero">
      <span class="pr-eyebrow">ProgressLab membership</span>
      <h1>Choose how you want to grow</h1>
      <p>Keep tracking for free, unlock your complete history with ProgressLab+, or manage clients as a Trainer.</p>
      <span class="pr-coming">Manual PayPal activation · one payment gives 30 days of access</span>
    </header>

    @if(session('status'))
      <div class="pr-alert pr-alert--success">{{ session('status') }}</div>
    @endif
    @if($errors->any())
      <div class="pr-alert pr-alert--error">
        @foreach($errors->all() as $error)<span>{{ $error }}</span>@endforeach
      </div>
    @endif
    @if($pendingRequest)
      <div class="pr-alert pr-alert--pending">
        <strong>Activation pending</strong>
        Your {{ $pendingRequest->plan === 'trainer' ? 'Trainer' : 'ProgressLab+' }} payment claim was submitted on {{ $pendingRequest->created_at->format('M j') }} and is waiting for Owner verification.
      </div>
    @endif

    <section class="pr-grid" aria-label="ProgressLab plans">
      <article class="pr-card {{ $currentRole === 'user' ? 'is-current' : '' }}">
        <div class="pr-card__top">
          <div><span class="pr-plan-kicker">Start tracking</span><h2>Free</h2></div>
          <span class="pr-plan-icon" aria-hidden="true">◎</span>
        </div>
        <p class="pr-description">Everything needed to build the habit and record consistent fitness progress.</p>
        <div class="pr-price"><strong>€0</strong><span>/month</span></div>
        <div class="pr-divider"></div>
        <h3>What’s included</h3>
        <ul>
          <li>Workout and nutrition logging</li>
          <li>Week and month progress charts</li>
          <li>Streaks and achievements</li>
          <li>Friends, notifications, and leaderboards</li>
          <li>Private progress photo comparisons</li>
        </ul>
        <button class="pr-button pr-button--muted" type="button" disabled>{{ $currentRole === 'user' ? 'Your current plan' : 'Included with every account' }}</button>
      </article>

      <article class="pr-card pr-card--plus {{ $currentRole === 'paid' ? 'is-current' : '' }}">
        <div class="pr-ribbon">Most popular</div>
        <div class="pr-card__top">
          <div><span class="pr-plan-kicker">See the full picture</span><h2>ProgressLab+</h2></div>
          <span class="pr-plan-icon" aria-hidden="true">✦</span>
        </div>
        <p class="pr-description">Long-term analytics and deeper insights for members serious about measurable progress.</p>
        <div class="pr-price"><strong>€{{ number_format($prices['paid'], 2) }}</strong><span>/30 days</span></div>
        <div class="pr-divider"></div>
        <h3>Everything in Free, plus</h3>
        <ul>
          <li>Year and all-time progress charts</li>
          <li>Complete nutrition and strength insights</li>
          <li>Long-term change and average comparisons</li>
          <li>Full body-weight history</li>
          <li>Future premium analytics</li>
        </ul>
        <a class="pr-button" href="https://paypal.me/StefanMilovanovic02/{{ number_format($prices['paid'], 2, '.', '') }}EUR?locale.x=en_US&amp;country.x=RS" target="_blank" rel="noopener noreferrer">
          {{ $currentRole === 'paid' ? 'Renew ProgressLab+ with PayPal' : 'Pay €' . number_format($prices['paid'], 2) . ' with PayPal' }}
        </a>
      </article>

      <article class="pr-card pr-card--trainer {{ $currentRole === 'trainer' ? 'is-current' : '' }}">
        <div class="pr-card__top">
          <div><span class="pr-plan-kicker">Coach with clarity</span><h2>Trainer</h2></div>
          <span class="pr-plan-icon" aria-hidden="true">🤝</span>
        </div>
        <p class="pr-description">A private coaching workspace for Trainers and clients who explicitly approve access.</p>
        <div class="pr-price"><strong>€{{ number_format($prices['trainer'], 2) }}</strong><span>/30 days</span></div>
        <div class="pr-divider"></div>
        <h3>Everything in ProgressLab+, plus</h3>
        <ul>
          <li>Dedicated Clients dashboard</li>
          <li>Consent-based client chart access</li>
          <li>Client activity and streak-risk signals</li>
          <li>Recent personal-record feed</li>
          <li>Private Trainer notes</li>
        </ul>
        <a class="pr-button pr-button--trainer" href="https://paypal.me/StefanMilovanovic02/{{ number_format($prices['trainer'], 2, '.', '') }}EUR?locale.x=en_US&amp;country.x=RS" target="_blank" rel="noopener noreferrer">
          {{ $currentRole === 'trainer' ? 'Renew Trainer with PayPal' : 'Pay €' . number_format($prices['trainer'], 2) . ' with PayPal' }}
        </a>
      </article>
    </section>

    <section class="pr-activation" id="activation">
      <div>
        <span class="pr-eyebrow">After payment</span>
        <h2>Request account activation</h2>
        <p>PayPal.Me cannot notify ProgressLab which account paid. Submit the PayPal email and transaction ID from your receipt so the Owner can verify it. A payment link click alone never changes your role. Do not mark a paid plan as a Friends and Family payment if PayPal asks for the payment type.</p>
      </div>
      <form method="POST" action="{{ route('plans.request-activation') }}">
        @csrf
        <label>
          <span>Plan paid for</span>
          <select name="plan" required>
            <option value="paid" @selected(old('plan') === 'paid')>ProgressLab+ — €{{ number_format($prices['paid'], 2) }}</option>
            <option value="trainer" @selected(old('plan') === 'trainer')>Trainer — €{{ number_format($prices['trainer'], 2) }}</option>
          </select>
        </label>
        <label>
          <span>PayPal email</span>
          <input type="email" name="paypal_email" required maxlength="255" value="{{ old('paypal_email', auth()->user()->email) }}" autocomplete="email">
        </label>
        <label>
          <span>PayPal transaction ID</span>
          <input type="text" name="paypal_transaction_id" required minlength="8" maxlength="100" value="{{ old('paypal_transaction_id') }}" placeholder="Example: 12A34567BC890123D">
        </label>
        <button class="pr-button" type="submit" @disabled($pendingRequest)>{{ $pendingRequest ? 'Activation already pending' : 'I have paid — request activation' }}</button>
      </form>
    </section>

    <p class="pr-footnote">Payments, activation, renewal, and expiration are manually managed by the Owner. PayPal may charge purchase, international, or currency-conversion fees. Progress photos remain private and are not included in Trainer access.</p>
  </main>
  <x-footer />
</body>
</html>
