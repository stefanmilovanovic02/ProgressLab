<!doctype html>
<html lang="en">
<head>
  <x-seo
    title="Your Profile"
    description="Manage your private ProgressLab profile, fitness metrics, nutrition targets, and account preferences."
    robots="noindex, nofollow, noarchive"
  />
  <link rel="stylesheet" href="{{ asset('css/auth.css') }}?v={{ filemtime(public_path('css/auth.css')) }}">
</head>
<body class="auth-body">

  <x-navbar />

  @php
    $user = auth()->user();
    $metric = $user->metric;
    $goalRow = $user->nutritionGoal;

    $avatarUrl = $user->avatar_url;
    $coverUrl  = $user->cover_url;

    $memberSince = $user->created_at ? $user->created_at->format('F Y') : '—';
    $usernameText = '@' . ($user->username ?? 'username');

    $gender = $user->metric?->gender; 
    $unitSystem = $user->unit_system ?? 'metric';
    $heightDisplay = \App\Support\UnitConverter::lengthFromCm($metric?->height_cm, $unitSystem);
    $weightDisplay = \App\Support\UnitConverter::weightFromKg($metric?->weight_kg, $unitSystem);
    $canCustomizeProfile = $user->canCustomizeSocialProfile();
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

        <div class="pl-profilecard__meta">
          <h2 class="pl-profilecard__name">
            {{ $user->full_name ?? $user->name ?? 'Your Name' }}
          </h2>

          <div class="pl-profilecard__handle">{{ $usernameText }}</div>

          <div class="pl-profilecard__badges">
            <span class="pl-pill">
              <span aria-hidden="true">🔥</span>
              <strong>{{ $loginStreak }}</strong>
              <span>day streak</span>
            </span>
          </div>

          <div class="pl-profilecard__since">
            Member since {{ $memberSince }}
          </div>
        </div>
      </div>

      <div class="pl-profilecard__right">
        <a class="pl-btn pl-btn--ghost" href="{{ route('friends.index', ['open_profile' => $user->id, 'return_to' => 'profile']) }}">
          Preview Public Profile
        </a>
        <button class="pl-btn pl-btn--light" type="button" data-edit-toggle>
          ✎ Edit Profile
        </button>
      </div>
    </section>

    <div class="cards-wrapper">
      <form action="{{ route('profile.update') }}" method="POST" data-profile-form>
        @csrf
            @method('PUT')

{{-- Personal Info Card (editable) --}}
    <section class="pl-card pl-infocard">
      <h3 class="pl-card__title">Personal Information</h3>

        <div class="pl-formgrid">
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
      </div> 
    </section>

    <section class="pl-card pl-infocard pl-social-profile" style="margin-top: 22px;">
      <div class="pl-card__head">
        <div class="pl-card__head-left">
          <div class="pl-card__icon" aria-hidden="true">✦</div>
          <div><h3 class="pl-card__title">Social Profile</h3><p class="pl-card__subtitle">Customize the profile people see from Friends.</p></div>
        </div>
        @unless($canCustomizeProfile)<a class="pl-btn pl-btn--light" href="{{ route('plans.index') }}">Unlock with ProgressLab+</a>@endunless
      </div>
      @if($canCustomizeProfile)
        <div class="pl-formgrid">
          <div class="pl-field pl-field--wide">
            <label class="pl-label" for="profile_quote">Profile quote</label>
            <input class="pl-input pl-input--field" id="profile_quote" name="profile_quote" maxlength="180" value="{{ old('profile_quote', $user->profile_quote) }}" placeholder="A short line that represents your journey" disabled>
          </div>
          @foreach(['instagram'=>'Instagram','tiktok'=>'TikTok','snapchat'=>'Snapchat','linkedin'=>'LinkedIn'] as $network => $label)
            <div class="pl-field">
              <label class="pl-label" for="social_{{ $network }}">{{ $label }} URL</label>
              <input class="pl-input pl-input--field" id="social_{{ $network }}" name="social_{{ $network }}" type="url" value="{{ old('social_'.$network, $user->{'social_'.$network}) }}" placeholder="https://..." disabled>
              @error('social_'.$network)<p class="field-error">{{ $message }}</p>@enderror
            </div>
          @endforeach
        </div>
        <p class="pl-social-profile__help">Use <strong>Preview Public Profile</strong> above to change your profile photo, full-screen background, showcase image, and profile colors while seeing the result in context.</p>
      @else
        <div class="pl-social-profile__locked"><strong>Your profile already uses the new layout.</strong><span>ProgressLab+ unlocks a full-screen background, animated showcase, custom colors, quote, and social links. Profile photo changes remain free.</span></div>
      @endif
    </section>


    {{-- Fitness Info Card --}}
<section class="pl-card pl-infocard" style="margin-top: 22px;">
  <div class="pl-card__head">
    <div class="pl-card__head-left">
      <div class="pl-card__icon" aria-hidden="true">🏋️</div>
      <h3 class="pl-card__title">Fitness Information</h3>
    </div>
  </div>

  <div class="pl-formgrid">
    <div class="pl-field">
      <label class="pl-label" for="unit_system">Measurement System</label>
      <select class="pl-input pl-input--field" id="unit_system" name="unit_system" disabled>
        <option value="metric" @selected($unitSystem === 'metric')>Metric (kg, cm)</option>
        <option value="imperial" @selected($unitSystem === 'imperial')>Imperial (lb, in)</option>
      </select>
    </div>

    <div class="pl-field">
      <label class="pl-label" for="height_cm">Height (<span data-profile-height-unit>{{ $unitSystem === 'imperial' ? 'in' : 'cm' }}</span>)</label>
      <input
        class="pl-input pl-input--field"
        id="height_cm"
        name="height_cm"
        type="number"
        min="{{ $unitSystem === 'imperial' ? 47 : 120 }}"
        max="{{ $unitSystem === 'imperial' ? 91 : 230 }}"
        step="0.1"
        placeholder="{{ $unitSystem === 'imperial' ? 'e.g. 73' : 'e.g. 185' }}"
        value="{{ old('height_cm', $heightDisplay) }}"
        disabled
      />
    </div>

    <div class="pl-field">
      <label class="pl-label" for="weight_kg">Weight (<span data-profile-weight-unit>{{ $unitSystem === 'imperial' ? 'lb' : 'kg' }}</span>)</label>
      <input
        class="pl-input pl-input--field"
        id="weight_kg"
        name="weight_kg"
        type="number"
        min="{{ $unitSystem === 'imperial' ? 77 : 35 }}"
        max="{{ $unitSystem === 'imperial' ? 551 : 250 }}"
        step="0.1"
        placeholder="{{ $unitSystem === 'imperial' ? 'e.g. 176' : 'e.g. 80' }}"
        value="{{ old('weight_kg', $weightDisplay) }}"
        disabled
      />
    </div>

    <div class="pl-field">
      <label class="pl-label" for="activity_multiplier">Activity Multiplier</label>
      <select
        class="pl-input pl-input--field"
        id="activity_multiplier"
        name="activity_multiplier"
        disabled
      >
        <option value="">Select</option>
         @foreach([1.2, 1.55, 1.7, 2.0, 2.2] as $m)
    <option value="{{ $m }}"
      {{ (string) old('activity_multiplier', $metric->activity_multiplier ?? '') === (string) $m ? 'selected' : '' }}>
      {{ $m == 1.2 ? 'Sedentary' : ($m == 1.55 ? 'Light' : ($m == 1.7 ? 'Moderate' : ($m == 2.0 ? 'Very Active' : 'Athlete'))) }} ({{ $m }})
    </option>
  @endforeach
</select>
    </div>

    <div class="pl-field">
      <label class="pl-label" for="goal">Fitness Goal</label>
      <select
        class="pl-input pl-input--field"
        id="goal"
        name="goal"
        disabled
      >
        <option value="">Select</option>
        <option value="bulk"  {{ old('goal', $goalRow->goal ?? '') === 'bulk' ? 'selected' : '' }}>Bulk</option>
  <option value="cut"   {{ old('goal', $goalRow->goal ?? '') === 'cut' ? 'selected' : '' }}>Cut</option>
  <option value="recomp"{{ old('goal', $goalRow->goal ?? '') === 'recomp' ? 'selected' : '' }}>Recomp</option>
      </select>
    </div>

    {{-- Macros --}}
    <div class="pl-field">
      <label class="pl-label" for="calorie_target">Calories (kcal)</label>
      <input
        class="pl-input pl-input--field"
        id="calorie_target"
        name="calorie_target"
        type="number"
        min="800"
        max="8000"
        placeholder="e.g. 2800"
        value="{{ old('calorie_target', $goalRow->calorie_target ?? '') }}"
        disabled
      />
    </div>

    <div class="pl-field">
      <label class="pl-label" for="protein_g">Protein (g)</label>
      <input
        class="pl-input pl-input--field"
        id="protein_g"
        name="protein_g"
        type="number"
        min="0"
        max="400"
        placeholder="e.g. 160"
        value="{{ old('protein_g', $goalRow->protein_g ?? '') }}"
        disabled
      />
    </div>

    <div class="pl-field">
      <label class="pl-label" for="fat_g">Fat (g)</label>
      <input
        class="pl-input pl-input--field"
        id="fat_g"
        name="fat_g"
        type="number"
        min="0"
        max="300"
        placeholder="e.g. 70"
        value="{{ old('fat_g', $goalRow->fat_g ?? '') }}"
        disabled
      />
    </div>

    <div class="pl-field">
      <label class="pl-label" for="carbs_g">Carbs (g)</label>
      <input
        class="pl-input pl-input--field"
        id="carbs_g"
        name="carbs_g"
        type="number"
        min="0"
        max="900"
        placeholder="e.g. 320"
        value="{{ old('carbs_g', $goalRow->carbs_g ?? '') }}"
        disabled
      />
    </div>

    {{-- Supplements / hydration --}}
    <div class="pl-field">
      <label class="pl-label" for="water_l">Water (L/day)</label>
      <input
        class="pl-input pl-input--field"
        id="water_l"
        name="water_l"
        type="number"
        min="0"
        max="10"
        step="0.1"
        placeholder="e.g. 3.0"
        value="{{ old('water_l', $goalRow->water_l ?? '') }}"
        disabled
      />
    </div>

    <div class="pl-field">
      <label class="pl-label" for="creatine_g">Creatine (g/day)</label>
      <input
        class="pl-input pl-input--field"
        id="creatine_g"
        name="creatine_g"
        type="number"
        min="0"
        max="20"
        step="0.5"
        placeholder="e.g. 5"
        value="{{ old('creatine_g', $goalRow->creatine_g ?? '') }}"
        disabled
      />
    </div>
  </div>
  </section>


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

    {{-- Security & Settings --}}
<section class="pl-card pl-securitycard">
  <div class="pl-securitycard__head">
    <div class="pl-securitycard__titlewrap">
      <div class="pl-securitycard__icon" aria-hidden="true">🛡️</div>
      <h3 class="pl-securitycard__title">Security &amp; Settings</h3>
    </div>
  </div>

  <div class="pl-securitycard__subhead">
    <div class="pl-securitycard__subicon" aria-hidden="true">🔑</div>
    <div class="pl-securitycard__subtitle">Security</div>
  </div>

  <div class="pl-securityrow">
    <div class="pl-securityrow__left">
      <div class="pl-securityrow__label">Password</div>
      <div class="pl-securityrow__hint">Last changed <span class="pl-securityrow__muted">—</span></div>
    </div>

    <div class="pl-securityrow__right">
      <a class="pl-btn pl-btn--light" style="text-decoration: none;" href="{{ route('password.edit') }}">
        Change Password
      </a>
    </div>
  </div>
</section>

{{-- Danger Zone --}}
<section class="pl-card pl-dangercard">
  <div class="pl-dangercard__head">
    <span class="pl-dangercard__dot"></span>
    <h3>Danger Zone</h3>
  </div>

  <div class="pl-dangerbox">
    <div>
      <div class="pl-dangerbox__title">Delete Account</div>
      <div class="pl-dangerbox__desc">
        Permanently delete your account and all data.
      </div>
    </div>

    <button class="pl-btn pl-btn--danger" type="button" data-open-delete>
      Delete Account
    </button>
  </div>
</section>



</div>
  </main>
  <div class="pl-modal" data-delete-modal>
  <div class="pl-modal__backdrop"></div>

  <div class="pl-modal__content">
    <h3>Are you absolutely sure?</h3>
    <p>
      This action cannot be undone. This will permanently delete your account
      and remove all of your data.
    </p>

    <form action="{{ route('profile.destroy') }}" method="POST">
      @csrf
      @method('DELETE')

      <div class="pl-field">
        <label class="pl-label" for="delete_account_password">Enter your password to confirm</label>
        <input id="delete_account_password" type="password" name="password" class="pl-input pl-input--field" autocomplete="current-password" required>
      </div>

      <div class="pl-modal__actions">
        <button type="button" class="pl-btn pl-btn--ghost" data-close-delete>
          Cancel
        </button>

        <button type="submit" class="pl-btn pl-btn--danger">
          Yes, Delete My Account
        </button>
      </div>
    </form>
  </div>
</div>

  <script src="{{ asset('js/image-optimizer.js') }}?v={{ filemtime(public_path('js/image-optimizer.js')) }}"></script>
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
      const showcaseBtn = document.querySelector('[data-showcase-btn]');
      const showcaseInput = document.querySelector('[data-showcase-input]');

      document.querySelectorAll('.pl-color-field input[type="color"]').forEach(input => {
        const output = input.closest('.pl-color-field')?.querySelector('[data-color-value]');
        input.addEventListener('input', () => {
          if (output) output.textContent = input.value.toUpperCase();
        });
      });

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
          if (showcaseBtn && showcaseInput) {
            showcaseBtn.disabled = !on;
            showcaseInput.disabled = !on;
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

      async function optimizeAndSubmit(uploadForm, input, button, options) {
        const original = input.files?.[0];
        if (!original || !uploadForm) return;

        const originalLabel = button.textContent;
        button.disabled = true;
        button.textContent = 'Optimizingâ€¦';

        try {
          const result = await window.ProgressLabImageOptimizer.optimize(original, options);
          const data = new FormData(uploadForm);
          data.set(input.name, result.file, result.file.name);
          button.textContent = 'Uploadingâ€¦';

          const response = await fetch(uploadForm.action, {
            method: 'POST',
            headers: { 'Accept': 'text/html' },
            body: data,
          });
          if (!response.ok) throw new Error('The image could not be uploaded.');

          window.location.assign(response.url || @json(route('profile.show')));
        } catch (error) {
          alert(error.message || 'The image could not be prepared.');
          input.value = '';
          button.disabled = false;
          button.textContent = originalLabel;
        }
      }

      if (avatarBtn && avatarInput) {
        avatarBtn.addEventListener('click', () => avatarInput.click());
        avatarInput.addEventListener('change', () => {
          if (avatarInput.files && avatarInput.files[0]) {
            optimizeAndSubmit(
              document.querySelector('[data-avatar-form]'),
              avatarInput,
              avatarBtn,
              { maxDimension: 900, targetBytes: 320 * 1024, quality: .86, baseName: 'avatar' }
            );
          }
        });
}

      if (coverBtn && coverInput) {
          coverBtn.addEventListener('click', () => coverInput.click());
          coverInput.addEventListener('change', () => {
            if (coverInput.files && coverInput.files[0]) {
              if (coverInput.files[0].type === 'image/gif') {
                coverBtn.textContent = 'Uploading…';
                document.querySelector('[data-cover-form]').requestSubmit();
                return;
              }
              optimizeAndSubmit(
                document.querySelector('[data-cover-form]'),
                coverInput,
                coverBtn,
                { maxDimension: 1920, targetBytes: 850 * 1024, quality: .84, baseName: 'cover' }
              );
            }
          });
        }

      if (showcaseBtn && showcaseInput) {
        showcaseBtn.addEventListener('click', () => showcaseInput.click());
        showcaseInput.addEventListener('change', () => {
          if (!showcaseInput.files?.[0]) return;
          if (showcaseInput.files[0].type === 'image/gif') {
            showcaseBtn.textContent = 'Uploading…';
            document.querySelector('[data-showcase-form]').requestSubmit();
            return;
          }
          optimizeAndSubmit(
            document.querySelector('[data-showcase-form]'),
            showcaseInput,
            showcaseBtn,
            { maxDimension: 1600, targetBytes: 900 * 1024, quality: .86, baseName: 'showcase' }
          );
        });
      }


      // If there are validation errors, auto-enable edit mode
      const hasErrors = {{ $errors->any() ? 'true' : 'false' }};
      if (hasErrors) setEditMode(true);
    })();

  (function () {
    const openBtn = document.querySelector('[data-open-delete]');
    const modal = document.querySelector('[data-delete-modal]');
    const closeBtn = document.querySelector('[data-close-delete]');
    const backdrop = modal?.querySelector('.pl-modal__backdrop');

    if (!openBtn || !modal) return;

    openBtn.addEventListener('click', () => {
      modal.classList.add('is-active');
    });

    closeBtn?.addEventListener('click', () => {
      modal.classList.remove('is-active');
    });

    backdrop?.addEventListener('click', () => {
      modal.classList.remove('is-active');
    });
  })();

  </script>

  <script>
  (() => {
    const units = document.getElementById('unit_system');
    const height = document.getElementById('height_cm');
    const weight = document.getElementById('weight_kg');
    if (!units || !height || !weight) return;
    let previous = units.value;
    const round = value => Math.round(value * 10) / 10;

    units.addEventListener('change', () => {
      const next = units.value;
      const heightValue = Number.parseFloat(height.value);
      const weightValue = Number.parseFloat(weight.value);
      if (previous !== next) {
        if (Number.isFinite(heightValue)) height.value = round(next === 'imperial' ? heightValue / 2.54 : heightValue * 2.54);
        if (Number.isFinite(weightValue)) weight.value = round(next === 'imperial' ? weightValue * 2.2046226218 : weightValue / 2.2046226218);
      }
      const imperial = next === 'imperial';
      document.querySelector('[data-profile-height-unit]').textContent = imperial ? 'in' : 'cm';
      document.querySelector('[data-profile-weight-unit]').textContent = imperial ? 'lb' : 'kg';
      height.min = imperial ? '47' : '120';
      height.max = imperial ? '91' : '230';
      weight.min = imperial ? '77' : '35';
      weight.max = imperial ? '551' : '250';
      previous = next;
    });
  })();
  </script>

<x-achievement-toasts />
<x-footer />
</body>
</html>
