<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Register • Step 1</title>

  {{-- Auth pages stylesheet (Login + Register) --}}
  <link rel="stylesheet" href="{{ asset('css/auth.css') }}">
</head>
<body class="auth-body">
  <main class="auth-wrapper">
    <section class="auth-card auth-card--register" aria-label="Register step 1">

      <header class="auth-header">
        <h1 class="auth-title">Create account</h1>
        <p class="auth-subtitle">
          Already have an account?
          <a class="auth-link" href="{{ route('login') }}">Sign in</a>
        </p>
      </header>

      
      <div class="auth-panel">
        {{-- Stepper --}}
        <div class="stepper" aria-label="Registration steps">
          <div class="stepper-item is-active">
            <div class="stepper-dot">1</div>
            <div class="stepper-label">Profile</div>
          </div>

          <div class="stepper-line"></div>

          <div class="stepper-item">
            <div class="stepper-dot">2</div>
            <div class="stepper-label">TDEE</div>
          </div>

          <div class="stepper-line"></div>

          <div class="stepper-item">
            <div class="stepper-dot">3</div>
            <div class="stepper-label">Goal</div>
          </div>
        </div>

        <form class="auth-form" action="{{ route('register.store.step1') }}" method="POST">
          @csrf

          <h2 class="step-title">Basic information</h2>
          <p class="step-desc">Enter your profile details to create your account.</p>

          <div class="grid-2">
            <div class="field">
              <label class="field-label" for="full_name">FULL NAME</label>
              <input
                class="field-input @error('full_name') is-invalid @enderror"
                id="full_name"
                name="full_name"
                type="text"
                placeholder="Enter your full name"
                value="{{ old('full_name', $data['full_name'] ?? '') }}"
              />
              @error('full_name')
                <p class="field-error">{{ $message }}</p>
              @enderror
            </div>

            <div class="field">
              <label class="field-label" for="username">USERNAME</label>
              <input
                class="field-input @error('username') is-invalid @enderror"
                id="username"
                name="username"
                type="text"
                placeholder="Choose a username"
                value="{{ old('username', $data['username'] ?? '') }}"
              />
              @error('username')
                <p class="field-error">{{ $message }}</p>
              @enderror
            </div>
          </div>

          <div class="field">
            <label class="field-label" for="email">EMAIL</label>
            <input
              class="field-input @error('email') is-invalid @enderror"
              id="email"
              name="email"
              type="email"
              placeholder="Enter your email"
              value="{{ old('email', $data['email'] ?? '') }}"
            />
            @error('email')
              <p class="field-error">{{ $message }}</p>
            @enderror
          </div>

          <div class="grid-2">
            <div class="field">
              <label class="field-label" for="password">PASSWORD</label>
              <input
                class="field-input @error('password') is-invalid @enderror"
                id="password"
                name="password"
                type="password"
                placeholder="Create a password"
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
                placeholder="Repeat password"
              />
            </div>
          </div>

          <div class="step-actions">
            <a class="auth-button auth-button--ghost" href="{{ route('login') }}">Back</a>
            <button class="auth-button" type="submit">Next</button>
          </div>
        </form>
      </div>

      <footer class="auth-footer">
        <p class="auth-footer-text">Step 1 of 3</p>
      </footer>

    </section>
  </main>
</body>
</html>
