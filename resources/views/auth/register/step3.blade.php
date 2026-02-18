<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Register • Step 3</title>

  <link rel="stylesheet" href="{{ asset('css/auth.css') }}">
</head>
<body class="auth-body">
  <main class="auth-wrapper">
    <section class="auth-card auth-card--register" aria-label="Register step 3">

      <header class="auth-header">
        <h1 class="auth-title">Create account</h1>
        <p class="auth-subtitle">
          Step 3: Choose your goal
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

          <div class="stepper-item is-active">
            <div class="stepper-dot">3</div>
            <div class="stepper-label">Goal</div>
          </div>
        </div>

        <form class="auth-form" action="{{ route('register.store.goal') }}" method="POST">
          @csrf

          <h2 class="step-title">Goal</h2>
          <p class="step-desc">We’ll generate calories & macros based on your maintenance.</p>

          <div class="tdee-preview" aria-label="Maintenance">
            <div class="tdee-preview-row">
              <span class="tdee-label">Your maintenance</span>
              <span class="tdee-value">{{ $tdee ? $tdee.' kcal' : '— kcal' }}</span>
            </div>
          </div>

          @php $goalVal = old('goal', $data['goal'] ?? '') @endphp

          <div class="goal-grid">
            <label class="goal-card">
              <input type="radio" name="goal" value="cut" {{ $goalVal==='cut' ? 'checked' : '' }}>
              <div class="goal-card-inner">
                <div class="goal-title">Cut</div>
                <div class="goal-desc">Calories below maintenance.</div>
              </div>
            </label>

            <label class="goal-card">
              <input type="radio" name="goal" value="bulk" {{ $goalVal==='bulk' ? 'checked' : '' }}>
              <div class="goal-card-inner">
                <div class="goal-title">Bulk</div>
                <div class="goal-desc">Calories above maintenance.</div>
              </div>
            </label>

            <label class="goal-card">
              <input type="radio" name="goal" value="recomp" {{ $goalVal==='recomp' ? 'checked' : '' }}>
              <div class="goal-card-inner">
                <div class="goal-title">Recomposition</div>
                <div class="goal-desc">Approximately maintenance calories.</div>
              </div>
            </label>
          </div>

          @error('goal')
            <p class="field-error">{{ $message }}</p>
          @enderror

          {{-- Optional controls (we can use later; keep for now) --}}
          <div class="grid-2" style="margin-top: 10px;">
            <div class="field">
              <label class="field-label" for="fat_percent">
                FAT % OF CALORIES (20–35)
                <span class="help-badge" tabindex="0">?</span>

                <span class="help-tooltip">
                  Most lifters do best with 20–35% of calories from fat.
                  A simple default is 30%. If you feel low energy/hormones, go higher (30–35%).
                  If you prefer more carbs, go lower (20–25%).
                </span>
              </label>

              <input
                class="field-input @error('fat_percent') is-invalid @enderror"
                id="fat_percent"
                name="fat_percent"
                type="number"
                min="20"
                max="35"
                step="0.1"
                placeholder="Default: 30"
                value="{{ old('fat_percent', $data['fat_percent'] ?? '') }}"
              />

              @error('fat_percent')
                <p class="field-error">{{ $message }}</p>
              @enderror
            </div>

            <div class="field">
              <label class="field-label" for="protein_g_per_kg">
                PROTEIN (g/kg)
                <span class="help-badge" tabindex="0">?</span>

                <span class="help-tooltip">
                  Protein depends on your goal:
                  • Bulk/Recomp: 1.6–2.2 g/kg
                  • Cut: 1.8–2.7 g/kg
                  If unsure, use 1.8 (bulk/recomp) or 2.2 (cut).
                </span>
              </label>

              <input
                class="field-input @error('protein_g_per_kg') is-invalid @enderror"
                id="protein_g_per_kg"
                name="protein_g_per_kg"
                type="number"
                min="1.6"
                max="2.7"
                step="0.1"
                placeholder="Auto default by goal"
                value="{{ old('protein_g_per_kg', $data['protein_g_per_kg'] ?? '') }}"
              />

              @error('protein_g_per_kg')
                <p class="field-error">{{ $message }}</p>
              @enderror
            </div>

          {{-- Preview (if controller passes it) --}}
          @if(!empty($macros_preview))
            <div class="macros-preview" aria-label="Macros preview">
              <div class="tdee-preview-row">
                <span class="tdee-label">Calories</span>
                <span class="tdee-value">{{ $macros_preview['calories'] ?? '—' }} kcal</span>
              </div>
              <div class="tdee-preview-row">
                <span class="tdee-label">Protein</span>
                <span class="tdee-value">{{ $macros_preview['protein_g'] ?? '—' }} g</span>
              </div>
              <div class="tdee-preview-row">
                <span class="tdee-label">Fats</span>
                <span class="tdee-value">{{ $macros_preview['fat_g'] ?? '—' }} g</span>
              </div>
              <div class="tdee-preview-row">
                <span class="tdee-label">Carbs</span>
                <span class="tdee-value">{{ $macros_preview['carb_g'] ?? '—' }} g</span>
              </div>
              <p class="tdee-note">Preview based on your selected goal.</p>
            </div>
          @endif

          <div class="step-actions">
            <a class="auth-button auth-button--ghost" href="{{ route('register.macros') }}">Back</a>
            <button class="auth-button" type="submit">Create account</button>
          </div>
        </form>
      </div>

      <footer class="auth-footer">
        <p class="auth-footer-text">Step 3 of 3</p>
      </footer>

    </section>
  </main>
</body>
</html>
