<!doctype html>
<html lang="en">
<head>
  <x-seo
    title="Calculate Your Fitness Targets"
    description="Calculate personalized maintenance calories and fitness targets while setting up your ProgressLab account."
    robots="noindex, follow"
    :canonical="route('register.macros')"
  />

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
        <div class="stepper" aria-label="Registration progress">
          <div class="stepper-item is-complete">
            <div class="stepper-dot">1</div>
            <div class="stepper-label">Profile</div>
          </div>

          <div class="stepper-line is-complete"></div>

          <div class="stepper-item is-current" aria-current="step">
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
          <p class="step-desc">We use the Mifflin–St Jeor equation and your selected activity multiplier to estimate your maintenance.</p>

          @php
            $unitSystem = old('unit_system', $data['unit_system'] ?? 'metric');
            $heightValue = old('height', $data['height_input'] ?? (isset($data['height_cm']) ? \App\Support\UnitConverter::lengthFromCm($data['height_cm'], $unitSystem) : ''));
            $weightValue = old('weight', $data['weight_input'] ?? (isset($data['weight_kg']) ? \App\Support\UnitConverter::weightFromKg($data['weight_kg'], $unitSystem) : ''));
          @endphp

          <div class="field">
            <label class="field-label" for="unit_system">MEASUREMENT SYSTEM</label>
            <select class="field-input field-select" id="unit_system" name="unit_system">
              <option value="metric" @selected($unitSystem === 'metric')>Metric — kilograms and centimetres</option>
              <option value="imperial" @selected($unitSystem === 'imperial')>Imperial — pounds and inches</option>
            </select>
            <p class="field-help">You can change this later from your Profile.</p>
          </div>

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
              <label class="field-label" for="height">HEIGHT (<span data-height-unit>{{ $unitSystem === 'imperial' ? 'IN' : 'CM' }}</span>)</label>
              <input
                class="field-input @error('height') is-invalid @enderror"
                id="height"
                name="height"
                type="number"
                step="0.1"
                min="{{ $unitSystem === 'imperial' ? 47 : 120 }}"
                max="{{ $unitSystem === 'imperial' ? 91 : 230 }}"
                placeholder="{{ $unitSystem === 'imperial' ? 'e.g. 71' : 'e.g. 180' }}"
                value="{{ $heightValue }}"
              />
              @error('height')
                <p class="field-error">{{ $message }}</p>
              @enderror
            </div>

            <div class="field">
              <label class="field-label" for="weight">WEIGHT (<span data-weight-unit>{{ $unitSystem === 'imperial' ? 'LB' : 'KG' }}</span>)</label>
              <input
                class="field-input @error('weight') is-invalid @enderror"
                id="weight"
                name="weight"
                type="number"
                step="0.1"
                min="{{ $unitSystem === 'imperial' ? 77 : 35 }}"
                max="{{ $unitSystem === 'imperial' ? 551 : 250 }}"
                placeholder="{{ $unitSystem === 'imperial' ? 'e.g. 176' : 'e.g. 80' }}"
                value="{{ $weightValue }}"
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
              <option value="1.5"  {{ $actVal==='1.5'  ? 'selected' : '' }}>1.5 — Light (gym 1–3x/week OR regular walks)</option>
              <option value="1.65" {{ $actVal==='1.65' ? 'selected' : '' }}>1.65 — Light/Moderate (gym 3–4x/week + decent steps)</option>
              <option value="1.7"  {{ $actVal==='1.7'  ? 'selected' : '' }}>1.7 — Moderate (gym 4–5x/week + 10k steps/day)</option>
              <option value="1.8"  {{ $actVal==='1.8'  ? 'selected' : '' }}>1.8 — Moderate/High (hard training + high daily activity)</option>
              <option value="2.0"  {{ $actVal==='2.0'  ? 'selected' : '' }}>2.0 — Highly active (physical job + training)</option>
              <option value="2.2"  {{ $actVal==='2.2'  ? 'selected' : '' }}>2.2 — Very active (intense sport + high activity)</option>
            </select>
            @error('activity')
              <p class="field-error">{{ $message }}</p>
            @enderror
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
  <script>
  (() => {
    const units = document.getElementById('unit_system');
    const height = document.getElementById('height');
    const weight = document.getElementById('weight');
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
      document.querySelector('[data-height-unit]').textContent = imperial ? 'IN' : 'CM';
      document.querySelector('[data-weight-unit]').textContent = imperial ? 'LB' : 'KG';
      height.min = imperial ? '47' : '120';
      height.max = imperial ? '91' : '230';
      height.placeholder = imperial ? 'e.g. 71' : 'e.g. 180';
      weight.min = imperial ? '77' : '35';
      weight.max = imperial ? '551' : '250';
      weight.placeholder = imperial ? 'e.g. 176' : 'e.g. 80';
      previous = next;
    });
  })();
  </script>
  <x-achievement-toasts />
</body>
</html>
