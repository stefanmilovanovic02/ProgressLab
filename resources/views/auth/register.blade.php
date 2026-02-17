<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Register</title>

  {{-- Auth pages stylesheet (Login + Register) --}}
  <link rel="stylesheet" href="{{ asset('css/auth.css') }}">
</head>
<body class="auth-body">

  <main class="auth-wrapper">
    <section class="auth-card auth-card--register" aria-label="Register form">

      <header class="auth-header">
        <h1 class="auth-title">Create account</h1>
        <p class="auth-subtitle">
          Already have an account?
          <a class="auth-link" href="{{ route('login') }}">Sign in</a>
        </p>
      </header>

      <div class="auth-panel">
        {{-- Design only (we’ll wire to controller later) --}}
        <form class="auth-form" action="#" method="POST">
          {{-- @csrf later --}}

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

          {{-- Steps container --}}
          <div class="steps">

            {{-- STEP 1: Basic profile --}}
            <section class="step is-active" data-step="1">
              <h2 class="step-title">Basic information</h2>
              <p class="step-desc">Enter your profile details to create your account.</p>

              <div class="grid-2">
                <div class="field">
                  <label class="field-label" for="full_name">FULL NAME</label>
                  <input class="field-input" id="full_name" name="full_name" type="text" placeholder="Enter your full name" />
                </div>

                <div class="field">
                  <label class="field-label" for="username">USERNAME</label>
                  <input class="field-input" id="username" name="username" type="text" placeholder="Choose a username" />
                </div>
              </div>

              <div class="field">
                <label class="field-label" for="email">EMAIL</label>
                <input class="field-input" id="email" name="email" type="email" placeholder="Enter your email" />
              </div>

              <div class="grid-2">
                <div class="field">
                  <label class="field-label" for="password">PASSWORD</label>
                  <input class="field-input" id="password" name="password" type="password" placeholder="Create a password" />
                </div>

                <div class="field">
                  <label class="field-label" for="password_confirmation">CONFIRM PASSWORD</label>
                  <input class="field-input" id="password_confirmation" name="password_confirmation" type="password" placeholder="Repeat password" />
                </div>
              </div>

              <div class="step-actions">
                <a class="auth-button auth-button--ghost" href="{{ route('login') }}">Back</a>
                <button class="auth-button" type="button" data-next>Next</button>
              </div>
            </section>

            {{-- STEP 2: TDEE calculator inputs (design only) --}}
            <section class="step" data-step="2">
              <h2 class="step-title">TDEE calculator</h2>
              <p class="step-desc">Enter details so we can calculate your maintenance calories.</p>

              <div class="grid-2">
                <div class="field">
                  <label class="field-label" for="gender">GENDER</label>
                  <select class="field-input field-select" id="gender" name="gender">
                    <option value="" selected disabled>Select</option>
                    <option value="male">Male</option>
                    <option value="female">Female</option>
                  </select>
                </div>

                <div class="field">
                  <label class="field-label" for="age">AGE</label>
                  <input class="field-input" id="age" name="age" type="number" min="10" max="100" placeholder="e.g. 23" />
                </div>
              </div>

              <div class="grid-2">
                <div class="field">
                  <label class="field-label" for="height">HEIGHT (CM)</label>
                  <input class="field-input" id="height" name="height" type="number" min="100" max="230" placeholder="e.g. 180" />
                </div>

                <div class="field">
                  <label class="field-label" for="weight">WEIGHT (KG)</label>
                  <input class="field-input" id="weight" name="weight" type="number" min="30" max="250" placeholder="e.g. 80" />
                </div>
              </div>

              <div class="field">
                <label class="field-label" for="activity">ACTIVITY LEVEL</label>
                <select class="field-input field-select" id="activity" name="activity">
                  <option value="" selected disabled>Select activity</option>
                  <option value="sedentary">Sedentary (little/no exercise)</option>
                  <option value="light">Light (1–3 days/week)</option>
                  <option value="moderate">Moderate (3–5 days/week)</option>
                  <option value="active">Active (6–7 days/week)</option>
                  <option value="very_active">Very active (hard training + active job)</option>
                </select>
              </div>

              <div class="tdee-preview" aria-label="Maintenance preview (placeholder)">
                <div class="tdee-preview-row">
                  <span class="tdee-label">Estimated maintenance</span>
                  <span class="tdee-value">— kcal</span>
                </div>
                <p class="tdee-note">We’ll calculate this after we wire up the equations.</p>
              </div>

              <div class="step-actions">
                <button class="auth-button auth-button--ghost" type="button" data-prev>Back</button>
                <button class="auth-button" type="button" data-next>Next</button>
              </div>
            </section>

            {{-- STEP 3: Goal (bulk/cut) design only --}}
            <section class="step" data-step="3">
              <h2 class="step-title">Choose your goal</h2>
              <p class="step-desc">Pick a goal — we’ll calculate macros based on your maintenance.</p>

              <div class="goal-grid">
                <label class="goal-card">
                  <input type="radio" name="goal" value="cut" />
                  <div class="goal-card-inner">
                    <div class="goal-title">Cut</div>
                    <div class="goal-desc">Fat loss with a calorie deficit.</div>
                  </div>
                </label>

                <label class="goal-card">
                  <input type="radio" name="goal" value="bulk" />
                  <div class="goal-card-inner">
                    <div class="goal-title">Bulk</div>
                    <div class="goal-desc">Muscle gain with a surplus.</div>
                  </div>
                </label>

                <label class="goal-card">
                  <input type="radio" name="goal" value="maintain" />
                  <div class="goal-card-inner">
                    <div class="goal-title">Maintain</div>
                    <div class="goal-desc">Stay at maintenance and recomposition.</div>
                  </div>
                </label>
              </div>

              <div class="macros-preview" aria-label="Macros preview (placeholder)">
                <div class="tdee-preview-row">
                  <span class="tdee-label">Calories</span>
                  <span class="tdee-value">— kcal</span>
                </div>
                <div class="tdee-preview-row">
                  <span class="tdee-label">Protein</span>
                  <span class="tdee-value">— g</span>
                </div>
                <div class="tdee-preview-row">
                  <span class="tdee-label">Carbs</span>
                  <span class="tdee-value">— g</span>
                </div>
                <div class="tdee-preview-row">
                  <span class="tdee-label">Fats</span>
                  <span class="tdee-value">— g</span>
                </div>
                <p class="tdee-note">Preview only — calculation comes later.</p>
              </div>

              <div class="step-actions">
                <button class="auth-button auth-button--ghost" type="button" data-prev>Back</button>
                <button class="auth-button" type="submit">Create account</button>
              </div>
            </section>

          </div>
        </form>
      </div>

      <footer class="auth-footer">
        <p class="auth-footer-text">
          By creating an account, you agree to the app rules.
        </p>
      </footer>

    </section>
  </main>

  {{-- Optional: only for switching steps visually (design demo).
       Remove later when we wire real multi-step logic. --}}
  <script>
    (function () {
      const steps = Array.from(document.querySelectorAll('.step'));
      const stepperItems = Array.from(document.querySelectorAll('.stepper-item'));
      let current = 0;

      function render() {
        steps.forEach((s, i) => s.classList.toggle('is-active', i === current));
        stepperItems.forEach((item, i) => item.classList.toggle('is-active', i <= current));
      }

      document.addEventListener('click', (e) => {
        if (e.target.matches('[data-next]')) {
          current = Math.min(current + 1, steps.length - 1);
          render();
        }
        if (e.target.matches('[data-prev]')) {
          current = Math.max(current - 1, 0);
          render();
        }
      });

      render();
    })();
  </script>

</body>
</html>