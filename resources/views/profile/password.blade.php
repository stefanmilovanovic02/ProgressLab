<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Change Password • GymTracker</title>
  <link rel="stylesheet" href="{{ asset('css/auth.css') }}">
</head>
<body class="auth-body">

  <x-navbar />

  <main class="pl-container">
    <div class="pl-pagehead">
      <div class="pl-pagehead__title">
        <div class="pl-pagehead__icon">🛡️</div>
        <h1>Change Password</h1>
      </div>
      <p class="pl-pagehead__sub">Update your password to keep your account secure.</p>
    </div>

    <section class="pl-card pl-infocard">
      <h3 class="pl-card__title">Password</h3>

      <form action="{{ route('profile.password.update') }}" method="POST" class="pl-formgrid">
        @csrf
        @method('PUT')

        <div class="pl-field" style="grid-column: 1 / -1;">
          <label class="pl-label" for="current_password">Current Password</label>
          <input
            class="pl-input pl-input--field @error('current_password') is-invalid @enderror"
            id="current_password"
            name="current_password"
            type="password"
            autocomplete="current-password"
            placeholder="Enter current password"
          />
          @error('current_password') <p class="field-error">{{ $message }}</p> @enderror
        </div>

        <div class="pl-field">
          <label class="pl-label" for="password">New Password</label>
          <input
            class="pl-input pl-input--field @error('password') is-invalid @enderror"
            id="password"
            name="password"
            type="password"
            autocomplete="new-password"
            placeholder="Enter new password"
          />
          @error('password') <p class="field-error">{{ $message }}</p> @enderror
        </div>

        <div class="pl-field">
          <label class="pl-label" for="password_confirmation">Confirm New Password</label>
          <input
            class="pl-input pl-input--field"
            id="password_confirmation"
            name="password_confirmation"
            type="password"
            autocomplete="new-password"
            placeholder="Confirm new password"
          />
        </div>

        <div class="pl-form-actions" style="grid-column: 1 / -1;">
          <a class="pl-btn pl-btn--ghost" href="{{ route('profile.show') }}">Cancel</a>
          <button class="pl-btn pl-btn--light" type="submit">Update Password</button>
        </div>
      </form>
    </section>
  </main>

</body>
</html>
