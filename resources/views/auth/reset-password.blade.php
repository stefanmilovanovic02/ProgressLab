<!doctype html>
<html lang="en">
<head>
  <x-seo
    title="Choose New Password"
    description="Choose a new password for your ProgressLab account."
    robots="noindex, nofollow, noarchive"
  />
  <link rel="stylesheet" href="{{ asset('css/auth.css') }}">
</head>
<body class="auth-body">
  <main class="auth-wrapper">
    <section class="auth-card" aria-labelledby="new-password-title">
      <header class="auth-header">
        <h1 class="auth-title" id="new-password-title">Choose a new password</h1>
        <p class="auth-subtitle">Use at least 8 characters and include both letters and numbers.</p>
      </header>

      <div class="auth-panel">
        <form class="auth-form" action="{{ route('password.update') }}" method="POST">
          @csrf
          <input type="hidden" name="token" value="{{ $token }}">

          <div class="field">
            <label class="field-label" for="email">EMAIL</label>
            <input
              class="field-input @error('email') is-invalid @enderror"
              id="email"
              name="email"
              type="email"
              autocomplete="email"
              required
              value="{{ old('email', $email) }}"
            >
            @error('email')
              <p class="field-error">{{ $message }}</p>
            @enderror
          </div>

          <div class="field">
            <label class="field-label" for="password">NEW PASSWORD</label>
            <input
              class="field-input @error('password') is-invalid @enderror"
              id="password"
              name="password"
              type="password"
              autocomplete="new-password"
              required
              autofocus
            >
            @error('password')
              <p class="field-error">{{ $message }}</p>
            @enderror
          </div>

          <div class="field">
            <label class="field-label" for="password_confirmation">CONFIRM PASSWORD</label>
            <input
              class="field-input"
              id="password_confirmation"
              name="password_confirmation"
              type="password"
              autocomplete="new-password"
              required
            >
          </div>

          <button class="auth-button" type="submit">Save new password</button>
        </form>
      </div>
    </section>
  </main>
</body>
</html>
