<!doctype html>
<html lang="en">
<head>
  <x-seo
    title="Choose Your Fitness Goal"
    description="Choose a bulk, cut, or recomposition goal and finish setting up your personalized ProgressLab targets."
    robots="noindex, follow"
    :canonical="route('register.goal')"
  />

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
        <div class="stepper" aria-label="Registration progress">
          <div class="stepper-item is-complete">
            <div class="stepper-dot">1</div>
            <div class="stepper-label">Profile</div>
          </div>

          <div class="stepper-line is-complete"></div>

          <div class="stepper-item is-complete">
            <div class="stepper-dot">2</div>
            <div class="stepper-label">TDEE</div>
          </div>

          <div class="stepper-line is-complete"></div>

          <div class="stepper-item is-current" aria-current="step">
            <div class="stepper-dot">3</div>
            <div class="stepper-label">Goal</div>
          </div>
        </div>

        <form class="auth-form" action="{{ route('register.store.goal') }}" method="POST">
          @csrf

          <h2 class="step-title">Choose your goal</h2>
          <p class="step-desc">Choose your direction, then fine-tune fat and protein. Your macro preview updates instantly.</p>

          <div class="tdee-preview" aria-label="Maintenance">
            <div class="tdee-preview-row">
              <span class="tdee-label">Your maintenance</span>
              <span class="tdee-value">{{ $tdee ? $tdee.' kcal' : '— kcal' }}</span>
            </div>
            <p class="tdee-note">Mifflin–St Jeor starting estimate. Compare it with your weight trend for 2–3 weeks and adjust if needed.</p>
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
                <div class="goal-title">Maintain / Recomp</div>
                <div class="goal-desc">Stay near maintenance while improving body composition.</div>
              </div>
            </label>
          </div>

          @error('goal')
            <p class="field-error">{{ $message }}</p>
          @enderror

          <div class="grid-2 goal-adjustments">
            <div class="field">
              <label class="field-label" for="fat_percent">
                FAT % OF CALORIES (20–35)
                <span class="help-badge" tabindex="0">?</span>

                <span class="help-tooltip">
                  Most people do well with 20–35% of calories from fat.
                  The default is 30%. Choose a lower value if you prefer more carbohydrates.
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
                  Protein depends on your goal.
                  Bulk/Maintain: 1.6–2.2 g/kg. Cut: 1.8–2.7 g/kg.
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
          </div>

          <div
            class="macros-preview macros-preview--live"
            aria-label="Calculated daily targets"
            aria-live="polite"
            data-macro-preview
            data-maintenance="{{ $tdee }}"
            data-weight="{{ $weightKg }}"
          >
            <h3>Your daily targets</h3>
            <div class="tdee-preview-row">
              <span class="tdee-label">Calories</span>
              <span class="tdee-value" data-preview-calories>Choose a goal</span>
            </div>
            <div class="tdee-preview-row">
              <span class="tdee-label">Protein</span>
              <span class="tdee-value" data-preview-protein>— g</span>
            </div>
            <div class="tdee-preview-row">
              <span class="tdee-label">Fats</span>
              <span class="tdee-value" data-preview-fat>— g</span>
            </div>
            <div class="tdee-preview-row">
              <span class="tdee-label">Carbs</span>
              <span class="tdee-value" data-preview-carbs>— g</span>
            </div>
            <p class="tdee-note">These are starting targets and will be saved to your profile.</p>
          </div>

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
  <script>
    (() => {
      const preview = document.querySelector('[data-macro-preview]');
      if (!preview) return;

      const maintenance = Number(preview.dataset.maintenance);
      const weight = Number(preview.dataset.weight);
      const fatInput = document.getElementById('fat_percent');
      const proteinInput = document.getElementById('protein_g_per_kg');
      const outputs = {
        calories: preview.querySelector('[data-preview-calories]'),
        protein: preview.querySelector('[data-preview-protein]'),
        fat: preview.querySelector('[data-preview-fat]'),
        carbs: preview.querySelector('[data-preview-carbs]'),
      };

      const updatePreview = () => {
        const selected = document.querySelector('input[name="goal"]:checked');
        if (!selected || !maintenance || !weight) return;

        const goal = selected.value;
        const calories = Math.round(goal === 'bulk' ? maintenance * 1.08 : goal === 'cut' ? maintenance * .85 : maintenance);
        const proteinPerKg = Number(proteinInput.value) || (goal === 'cut' ? 2.2 : 1.8);
        const fatPercent = Number(fatInput.value) || 30;
        const protein = Math.round(weight * proteinPerKg);
        const fat = Math.round((calories * (fatPercent / 100)) / 9);
        const carbs = Math.max(0, Math.round((calories - (protein * 4) - (fat * 9)) / 4));

        outputs.calories.textContent = `${calories} kcal`;
        outputs.protein.textContent = `${protein} g`;
        outputs.fat.textContent = `${fat} g`;
        outputs.carbs.textContent = `${carbs} g`;
      };

      document.querySelectorAll('input[name="goal"]').forEach((input) => input.addEventListener('change', updatePreview));
      [fatInput, proteinInput].forEach((input) => input.addEventListener('input', updatePreview));
      updatePreview();
    })();
  </script>
  <x-achievement-toasts />
</body>
</html>
