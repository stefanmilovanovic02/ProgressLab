<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Profile • GymTracker</title>
  <link rel="stylesheet" href="{{ asset('css/auth.css') }}">
</head>
<body class="auth-body">

  <x-navbar />

  <main class="pl-container">

    {{-- Page Header --}}
    <div class="pl-pagehead">
      <div class="pl-pagehead__title">
        <div class="pl-pagehead__icon">👤</div>
        <h1>My Profile</h1>
      </div>
      <p class="pl-pagehead__sub">
        Manage your personal information and settings.
      </p>
    </div>

    {{-- Profile Summary Card --}}
    <section class="pl-card pl-profilecard">
      <div class="pl-profilecard__left">

        <div class="pl-avatar">
          <img src="https://i.pravatar.cc/140?img=12" alt="">
        </div>

        <div>
          <h2 class="pl-profilecard__name">
            {{ auth()->user()->full_name ?? auth()->user()->name }}
          </h2>

          <div class="pl-profilecard__handle">
            @{{ auth()->user()->username ?? 'username' }}
          </div>

          <div class="pl-profilecard__badges">
            <span class="pl-pill">
              🔥 <strong>23</strong> day streak
            </span>
          </div>

          <div class="pl-profilecard__since">
            Member since August 2023
          </div>
        </div>

      </div>

      <div>
        <button class="pl-btn pl-btn--light" disabled>
          ✎ Edit Profile
        </button>
      </div>
    </section>

    {{-- Personal Info Card --}}
    <section class="pl-card pl-infocard">
      <h3 class="pl-card__title">Personal Information</h3>

      <div class="pl-formgrid">

        <div class="pl-field">
          <label class="pl-label">Full Name</label>
          <div class="pl-input">
            {{ auth()->user()->full_name ?? auth()->user()->name }}
          </div>
        </div>

        <div class="pl-field">
          <label class="pl-label">Email</label>
          <div class="pl-input">
            {{ auth()->user()->email }}
          </div>
        </div>

        <div class="pl-field">
          <label class="pl-label">Username</label>
          <div class="pl-input">
            @{{ auth()->user()->username }}
          </div>
        </div>

        <div class="pl-field">
          <label class="pl-label">Gender</label>
          <div class="pl-input">
            Male
          </div>
        </div>

      </div>
    </section>

  </main>

</body>
</html>
