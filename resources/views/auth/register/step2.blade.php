<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Register • Step 2</title>

  <link rel="stylesheet" href="{{ asset('css/auth.css') }}">
</head>
<body class="auth-body">
  <main class="auth-wrapper">
    <section class="auth-card auth-card--register" aria-label="Register step 2">

      <header class="auth-header">
        <h1 class="auth-title">Create account</h1>
        <p class="auth-subtitle">
          Step 2: Calculate your maintenance
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

          <div class="stepper-item is-active">
            <div class="stepper-dot">2</div>
            <div class="stepper-label">TDEE</div>
          </div>

          <div class="stepper-line"></div>

          <div class="stepper-item">
            <div class="stepper-dot">3</div>
            <div class="stepper-label">Goal</div>
          </div>
        </div>

        <form class="auth-form" action="{{ route('register.store.macros') }}" method="POST">
          @csrf

          <h2 class="step-title">TDEE calculator</h2>
          <p class="step-desc">Enter details so we can calculate your maintenance calories.</p>

          <div class="grid-2">
            <div class="field">
              <label class="field-label" for="gender">GENDER</label>
              <select class="field-input field-select @error('gender') is-invalid @enderror" id="gender" name="gender">
                @php $genderVal = old('gender', $data['gender'] ?? '') @endphp
                <option value="" disabled {{ $genderVal==='' ? 'selected' : '' }}>Select</option>
                <option value="male" {{ $genderVal==='male' ? 'selected' : '' }}>Male</option>
                <option value="female" {{ $genderVal==='female' ? 'selected' : '' }}>Female</option>
              </select>
              @error('gender')
                <p class="field-error">{{ $message }}</p>
              @enderror
            </div>

            <div class="field">
              <label class="field-label" for="age">AGE</label>
              <input
                class="field-input @error('age') is-invalid @enderror"
                id="age"
                name="age"
                type="number"
                min="13"
                max="90"
                placeholder="e.g. 23"
                value="{{ old('age', $data['age'] ?? '') }}"
              />
              @error('age')
                <p class="field-error">{{ $message }}</p>
              @enderror
            </div>
          </div>

          <div class="grid-2">
            <div class="field">
              <label class="field-label" for="height">HEIGHT (CM)</label>
              <input
                class="field-input @error('height') is-invalid @enderror"
                id="height"
                name="height"
                type="number"
                min="120"
                max="230"
                placeholder="e.g. 180"
                value="{{ old('height', $data['height_cm'] ?? $data['height'] ?? '') }}"
              />
              @error('height')
                <p class="field-error">{{ $message }}</p>
              @enderror
            </div>

            <div class="field">
              <label class="field-label" for="weight">WEIGHT (KG)</label>
              <input
                class="field-input @error('weight') is-invalid @enderror"
                id="weight"
                name="weight"
                type="number"
                step="0.1"
                min="35"
                max="250"
                placeholder="e.g. 80"
                value="{{ old('weight', $data['weight_kg'] ?? $data['weight'] ?? '') }}"
              />
              @error('weight')
                <p class="field-error">{{ $message }}</p>
              @enderror
            </div>
          </div>

          <div class="field">
            <label class="field-label" for="activity">ACTIVITY MULTIPLIER</label>
            @php $actVal = (string) old('activity', $data['activity_multiplier'] ?? $data['activity'] ?? '') @endphp
            <select class="field-input field-select @error('activity') is-invalid @enderror" id="activity" name="activity">
              <option value="" disabled {{ $actVal==='' ? 'selected' : '' }}>Select</option>
              <option value="1.2"  {{ $actVal==='1.2'  ? 'selected' : '' }}>1.2 — Sedentary (desk job, little movement)</option>
              <option value="1.5"  {{ $actVal==='1.5'  ? 'selected' : '' }}>1.5 — Light (gym 1-3x/week OR regular walks)</option>
              <option value="1.65" {{ $actVal==='1.65' ? 'selected' : '' }}>1.65 — Light/Moderate (gym 3-4x/week + decent steps)</option>
              <option value="1.7"  {{ $actVal==='1.7'  ? 'selected' : '' }}>1.7 — Moderate (gym 4-5x/week + 10k steps/day)</option>
              <option value="1.8"  {{ $actVal==='1.8'  ? 'selected' : '' }}>1.8 — Moderate/High (hard training + high daily activity)</option>
              <option value="2.0"  {{ $actVal==='2.0'  ? 'selected' : '' }}>2.0 — Highly active (physical job + training)</option>
              <option value="2.2"  {{ $actVal==='2.2'  ? 'selected' : '' }}>2.2 — Very active (intense sport + high activity)</option>
            </select>
            @error('activity')
              <p class="field-error">{{ $message }}</p>
            @enderror
          </div>

          <div class="tdee-preview" aria-label="Maintenance preview">
            <div class="tdee-preview-row">
              <span class="tdee-label">Estimated maintenance</span>
              <span class="tdee-value">{{ $tdee ? $tdee.' kcal' : '— kcal' }}</span>
            </div>
            <p class="tdee-note">Calculated with Mifflin-St Jeor (we’ll refine UI later).</p>
          </div>

          <div class="step-actions">
            <a class="auth-button auth-button--ghost" href="{{ route('register') }}">Back</a>
            <button class="auth-button" type="submit">Next</button>
          </div>
        </form>
      </div>

      <footer class="auth-footer">
        <p class="auth-footer-text">Step 2 of 3</p>
      </footer>

    </section>
  </main>
</body>
</html>
