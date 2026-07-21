<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Reset password</title>

  <link rel="stylesheet" href="{{ asset('css/auth.css') }}">
</head>
<body class="auth-body">

  <main class="auth-wrapper">
    <section class="auth-card" aria-label="Forgot password form">

      <header class="auth-header">
        <h1 class="auth-title">Reset password</h1>
        <p class="auth-subtitle">
          Enter your email and we will send you a reset link.
        </p>
      </header>

      <div class="auth-panel">
        @if (session('status'))
          <p class="auth-status">{{ session('status') }}</p>
        @endif

        <form class="auth-form" action="{{ route('password.email') }}" method="POST">
          @csrf

          <div class="field">
            <label class="field-label" for="email">EMAIL</label>
            <input
              class="field-input @error('email') is-invalid @enderror"
              id="email"
              name="email"
              type="email"
              placeholder="Enter Your Email"
              autocomplete="email"
              value="{{ old('email') }}"
            />
            @error('email')
              <p class="field-error">{{ $message }}</p>
            @enderror
          </div>

          <button class="auth-button" type="submit">Send reset link</button>
        </form>
      </div>

      <footer class="auth-footer">
        <p class="auth-footer-text">
          Remember your password?
          <a class="auth-link" href="{{ route('login') }}">Sign in</a>
        </p>
      </footer>

    </section>
  </main>
</body>
</html>
