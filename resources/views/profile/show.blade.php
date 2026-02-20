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

  @php
    $user = auth()->user();

    $avatarUrl = $user->avatar_path ? asset($user->avatar_path) : 'https://i.pravatar.cc/140?img=12';
    $coverUrl  = $user->cover_path ? asset($user->cover_path) : null;

    $memberSince = $user->created_at ? $user->created_at->format('F Y') : '—';
    $usernameText = '@' . ($user->username ?? 'username');

    $gender = $user->metric?->gender; 
  @endphp

  <main class="pl-container">

    {{-- Page Header --}}
    <div class="pl-pagehead">
      <div class="pl-pagehead__title">
        <div class="pl-pagehead__icon">👤</div>
        <h1>My Profile</h1>
      </div>
      <p class="pl-pagehead__sub">Manage your personal information and settings.</p>
    </div>

    {{-- Flash message --}}
    @if(session('status'))
      <div class="pl-alert">{{ session('status') }}</div>
    @endif

    {{-- Profile Summary Card --}}
    <section class="pl-card pl-profilecard {{ $coverUrl ? 'has-cover' : '' }}"
      @if($coverUrl) style="--cover-url: url('{{ $coverUrl }}');" @endif
    >
      <div class="pl-profilecard__left">
        <div class="pl-avatar">
          <img src="{{ $avatarUrl }}" alt="Profile picture">
        </div>

        {{-- Upload controls (enabled only in edit mode via JS) --}}
      <div class="pl-media-actions" data-media-actions style ="display:none;">
        <form action="{{ route('profile.photo.update') }}" method="POST" enctype="multipart/form-data" data-avatar-form>
          @csrf
          @method('PUT')
          <input type="file" name="avatar" accept="image/*" class="pl-file" data-avatar-input disabled>
          <button type="button" class="pl-btn pl-btn--ghost" data-avatar-btn disabled>Change Photo</button>
        </form>

        <form action="{{ route('profile.cover.update') }}" method="POST" enctype="multipart/form-data" data-cover-form>
          @csrf
          @method('PUT')
          <input type="file" name="cover" accept="image/*" class="pl-file" data-cover-input disabled>
          <button type="button" class="pl-btn pl-btn--ghost" data-cover-btn disabled>Change Cover</button>
        </form>
      </div>

        <div class="pl-profilecard__meta">
          <h2 class="pl-profilecard__name">
            {{ $user->full_name ?? $user->name ?? 'Your Name' }}
          </h2>

          <div class="pl-profilecard__handle">{{ $usernameText }}</div>

          <div class="pl-profilecard__badges">
            <span class="pl-pill">
              <span aria-hidden="true">🔥</span>
              <strong>23</strong>
              <span>day streak</span>
            </span>
          </div>

          <div class="pl-profilecard__since">
            Member since {{ $memberSince }}
          </div>
        </div>
      </div>

      <div class="pl-profilecard__right">
        <button class="pl-btn pl-btn--light" type="button" data-edit-toggle>
          ✎ Edit Profile
        </button>
      </div>
    </section>

    {{-- Personal Info Card (editable) --}}
    <section class="pl-card pl-infocard">
      <h3 class="pl-card__title">Personal Information</h3>

      <form action="{{ route('profile.update') }}" method="POST" class="pl-formgrid" data-profile-form>
        @csrf
        @method('PUT')

        <div class="pl-field">
          <label class="pl-label" for="full_name">Full Name</label>
          <input
            class="pl-input pl-input--field @error('full_name') is-invalid @enderror"
            id="full_name"
            name="full_name"
            type="text"
            value="{{ old('full_name', $user->full_name ?? $user->name ?? '') }}"
            disabled
            autocomplete="name"
          />
          @error('full_name') <p class="field-error">{{ $message }}</p> @enderror
        </div>

        <div class="pl-field">
          <label class="pl-label" for="email">Email</label>
          <input
            class="pl-input pl-input--field @error('email') is-invalid @enderror"
            id="email"
            name="email"
            type="email"
            value="{{ old('email', $user->email ?? '') }}"
            disabled
            autocomplete="email"
          />
          @error('email') <p class="field-error">{{ $message }}</p> @enderror
        </div>

        <div class="pl-field">
          <label class="pl-label" for="username">Username</label>
          <input
            class="pl-input pl-input--field @error('username') is-invalid @enderror"
            id="username"
            name="username"
            type="text"
            value="{{ old('username', $user->username ?? '') }}"
            disabled
            autocomplete="username"
          />
          @error('username') <p class="field-error">{{ $message }}</p> @enderror
        </div>

        <div class="pl-field">
          <label class="pl-label" for="date_of_birth">Date of Birth</label>
          <input
            class="pl-input pl-input--field @error('date_of_birth') is-invalid @enderror"
            id="date_of_birth"
            name="date_of_birth"
            type="date"
            value="{{ old('date_of_birth', $user->date_of_birth ?? '') }}"
            disabled
          />
          @error('date_of_birth') <p class="field-error">{{ $message }}</p> @enderror
        </div>

        <div class="pl-field">
  <label class="pl-label" for="gender">Gender</label>

  <select
    class="pl-input pl-input--field @error('gender') is-invalid @enderror"
    id="gender"
    name="gender"
    disabled
  >
    <option value="">Select gender</option>
    <option value="male" {{ old('gender', $user->gender) === 'male' ? 'selected' : '' }}>Male</option>
    <option value="female" {{ old('gender', $user->gender) === 'female' ? 'selected' : '' }}>Female</option>
  </select>

  @error('gender')
    <p class="field-error">{{ $message }}</p>
  @enderror
</div>


        <div class="pl-field">
          <label class="pl-label" for="location">Location</label>
          <input
            class="pl-input pl-input--field @error('location') is-invalid @enderror"
            id="location"
            name="location"
            type="text"
            value="{{ old('location', $user->location ?? '') }}"
            disabled
            placeholder="City, Country"
          />
          @error('location') <p class="field-error">{{ $message }}</p> @enderror
        </div>

        {{-- Save button (hidden until edit mode) --}}
        <div class="pl-form-actions">
          <button class="pl-btn pl-btn--light" type="submit" data-save-btn style="display:none;">
            Save Changes
          </button>
          <button class="pl-btn pl-btn--ghost" type="button" data-cancel-btn style="display:none;">
            Cancel
          </button>
        </div>
      </form>
    </section>

  </main>

  <script>
    (function () {
      const toggleBtn = document.querySelector('[data-edit-toggle]');
      const form = document.querySelector('[data-profile-form]');
      const saveBtn = document.querySelector('[data-save-btn]');
      const cancelBtn = document.querySelector('[data-cancel-btn]');
      const mediaActions = document.querySelector('[data-media-actions]');
      const avatarBtn = document.querySelector('[data-avatar-btn]');
      const avatarInput = document.querySelector('[data-avatar-input]');
      const coverBtn = document.querySelector('[data-cover-btn]');
      const coverInput = document.querySelector('[data-cover-input]');

      if (!toggleBtn || !form || !saveBtn || !cancelBtn) return;

      const fields = Array.from(form.querySelectorAll('input.pl-input--field, select.pl-input--field, textarea.pl-input--field'));
      let isEdit = false;

      // Save original values so Cancel can revert
      const original = new Map(fields.map(i => [i.name, i.value]));

      function setEditMode(on) {
        isEdit = on;
        fields.forEach(i => {
          // keep Gender disabled always (no name attribute anyway)
          if (!i.name) return;
          i.disabled = !on;
          if (mediaActions) mediaActions.style.display = on ? 'flex' : 'none';
          if (avatarBtn && avatarInput) {
            avatarBtn.disabled = !on;
            avatarInput.disabled = !on;
          }
          if (coverBtn && coverInput) {
            coverBtn.disabled = !on;
            coverInput.disabled = !on;
          }
        });

        saveBtn.style.display = on ? 'inline-flex' : 'none';
        cancelBtn.style.display = on ? 'inline-flex' : 'none';

        toggleBtn.textContent = on ? '💾 Save Changes' : '✎ Edit Profile';

        // If edit mode, focus first input
        if (on) {
          const first = fields.find(i => i.name === 'full_name');
          if (first) first.focus();
        }
      }

      toggleBtn.addEventListener('click', function () {
        if (!isEdit) {
          setEditMode(true);
        } else {
          // When button shows "Save Changes" we submit
          form.requestSubmit();
        }
      });

      cancelBtn.addEventListener('click', function () {
        fields.forEach(i => {
          if (!i.name) return;
          if (original.has(i.name)) i.value = original.get(i.name);
        });
        setEditMode(false);
      });

      if (avatarBtn && avatarInput) {
        avatarBtn.addEventListener('click', () => avatarInput.click());
        avatarInput.addEventListener('change', () => {
          if (avatarInput.files && avatarInput.files[0]) {
            document.querySelector('[data-avatar-form]').submit();
          }
        });
}

      if (coverBtn && coverInput) {
          coverBtn.addEventListener('click', () => coverInput.click());
          coverInput.addEventListener('change', () => {
            if (coverInput.files && coverInput.files[0]) {
              document.querySelector('[data-cover-form]').submit();
            }
          });
        }


      // If there are validation errors, auto-enable edit mode
      const hasErrors = {{ $errors->any() ? 'true' : 'false' }};
      if (hasErrors) setEditMode(true);
    })();
  </script>

</body>
</html>
