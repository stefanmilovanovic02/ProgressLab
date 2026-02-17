<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Sign in</title>

  {{-- Login page stylesheet --}}
  <link rel="stylesheet" href="{{ asset('css/auth.css') }}">
</head>
<body class="auth-body">

  <main class="auth-wrapper">
    <section class="auth-card" aria-label="Sign in form">

      <header class="auth-header">
        <h1 class="auth-title">Sign in</h1>
        <p class="auth-subtitle">
          Don’t have an account yet?
          <a class="auth-link" href="{{ route('register') }}">Register here</a>
        </p>
      </header>

      <div class="auth-panel">
        <form class="auth-form" action="{{ route('login.store') }}" method="POST">
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

          <div class="field">
            <label class="field-label" for="password">PASSWORD</label>
            <input
              class="field-input @error('password') is-invalid @enderror"
              id="password"
              name="password"
              type="password"
              placeholder="Password"
              autocomplete="current-password"
            />
            @error('password')
                <p class="field-error">{{ $message }}</p>
            @enderror
          </div>

          <div class="auth-row">
            <label class="checkbox">
              <input type="checkbox" name="remember" {{ old('remember') ? 'checked' : '' }} />
              <span class="checkbox-text">Remember me</span>
            </label>
          </div>

          <button class="auth-button" type="submit">Sign in</button>
        </form>
      </div>

      <footer class="auth-footer">
        <p class="auth-footer-text">
          Forgot your password?
          <a class="auth-link" href="{{ route('password.request') }}">Reset</a>
        </p>
      </footer>

    </section>
  </main>

</body>
</html>
