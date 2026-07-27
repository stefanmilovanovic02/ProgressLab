<!doctype html>
<html lang="en">
<head>
  <x-seo
    title="Reset Password"
    description="Request a secure ProgressLab password reset link."
    robots="noindex, nofollow, noarchive"
    :canonical="route('password.request')"
  />
  <link rel="stylesheet" href="{{ asset('css/auth.css') }}">
</head>
<body class="auth-body">
  <main class="auth-wrapper">
    <section class="auth-card" aria-labelledby="reset-title">
      <header class="auth-header">
        <h1 class="auth-title" id="reset-title">Reset password</h1>
        <p class="auth-subtitle">Enter your account email and we will send you a secure reset link.</p>
      </header>

      <div class="auth-panel">
        @if(session('status'))
          <p class="auth-status" role="status">{{ session('status') }}</p>
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
              autocomplete="email"
              required
              autofocus
              value="{{ old('email') }}"
              placeholder="Enter your email"
            >
            @error('email')
              <p class="field-error">{{ $message }}</p>
            @enderror
          </div>

          <button class="auth-button" type="submit">Send reset link</button>
        </form>
      </div>

      <footer class="auth-footer">
        <p class="auth-footer-text">
          Remembered your password?
          <a class="auth-link" href="{{ route('login') }}">Return to sign in</a>
        </p>
      </footer>
    </section>
  </main>
</body>
</html>
