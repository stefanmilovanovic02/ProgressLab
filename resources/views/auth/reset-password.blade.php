<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>New password</title>

  <link rel="stylesheet" href="{{ asset('css/auth.css') }}">
</head>
<body class="auth-body">

  <main class="auth-wrapper">
    <section class="auth-card" aria-label="Reset password form">

      <header class="auth-header">
        <h1 class="auth-title">New password</h1>
        <p class="auth-subtitle">
          Choose a new password for your account.
        </p>
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
              placeholder="Enter Your Email"
              autocomplete="email"
              value="{{ old('email', $email) }}"
            />
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
              placeholder="New Password"
              autocomplete="new-password"
            />
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
              placeholder="Confirm Password"
              autocomplete="new-password"
            />
          </div>

          <button class="auth-button" type="submit">Update password</button>
        </form>
      </div>

      <footer class="auth-footer">
        <p class="auth-footer-text">
          Back to
          <a class="auth-link" href="{{ route('login') }}">sign in</a>
        </p>
      </footer>

    </section>
  </main>
</body>
</html>
