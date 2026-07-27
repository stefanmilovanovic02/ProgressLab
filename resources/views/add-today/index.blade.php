<!doctype html>
<html lang="en">
<head>
  <x-seo
    title="Log Today's Progress"
    description="Log today's calories, macros, hydration, creatine, exercises, sets, reps, and workout weights in ProgressLab."
    robots="noindex, nofollow, noarchive"
  />
  <link rel="stylesheet" href="{{ asset('css/auth.css') }}">
  <link rel="stylesheet" href="{{ asset('css/workout-duration.css') }}">
  <link rel="stylesheet" href="{{ asset('css/exercise-ranks.css') }}">
</head>
<body class="auth-body">

  <x-navbar />

  <main class="pl-container">

    {{-- Page Header --}}
    <div class="pl-pagehead">
      <div class="pl-pagehead__title pl-pagehead__title--center">
        <h1>Log Today's Progress</h1>
      </div>
      <p class="pl-pagehead__sub pl-pagehead__sub--center">
        Track your nutrition and workouts to keep the streak alive.
      </p>
    </div>

    {{-- Nutrition Entry Card --}}
    <section class="pl-card at-card" aria-label="Nutrition Entry">

      <div class="at-head">
        <div class="at-head__left">
          <div class="at-icon">💧</div>
          <h2 class="at-title">Nutrition Entry</h2>
        </div>
      </div>

      <form id="nutritionForm" action = "{{ route('add-today.nutrition.store') }}" method="POST">
        @csrf
       
      <div class="at-grid">
        {{-- Calories --}}
        <div class="at-box">
          <div class="at-box__top">
            <div class="at-miniicon is-red">🔥</div>
            <div>
              <div class="at-label">Calories</div>
              <div class="at-sub">kcal</div>
            </div>
          </div>

          <div class="at-inputwrap">
            <input class="at-input" type="number" name="calories" data-field="calories" value="{{ $entry->calories > 0 ? $entry->calories : '' }}" placeholder="{{ $targets['calories'] ?? 0 }}" />
            <span class="at-unit">kcal</span>
          </div>

          <button class="at-btn" type="button" data-inc="100" data-target="calories">＋ 100 kcal</button>
        </div>

        {{-- Protein --}}
        <div class="at-box">
          <div class="at-box__top">
            <div class="at-miniicon is-blue">💧</div>
            <div>
              <div class="at-label">Protein</div>
              <div class="at-sub">g</div>
            </div>
          </div>

          <div class="at-inputwrap">
            <input class="at-input" type="number" name="protein_g" data-field="protein_g" value="{{ $entry->protein_g > 0 ? $entry->protein_g : '' }}" placeholder="{{ $targets['protein_g'] ?? 0 }}" />
            <span class="at-unit">g</span>
          </div>

          <button class="at-btn" type="button" data-inc="10" data-target="protein_g">＋ 10 g</button>
        </div>

        {{-- Carbs --}}
        <div class="at-box">
          <div class="at-box__top">
            <div class="at-miniicon is-yellow">💧</div>
            <div>
              <div class="at-label">Carbohydrates</div>
              <div class="at-sub">g</div>
            </div>
          </div>

          <div class="at-inputwrap">
            <input class="at-input" type="number" name="carbs_g" data-field="carbs_g" value="{{ $entry->carbs_g > 0 ? $entry->carbs_g : '' }}" placeholder="{{ $targets['carbs_g'] ?? 0 }}" />
            <span class="at-unit">g</span>
          </div>

          <button class="at-btn" type="button" data-inc="20" data-target="carbs_g">＋ 20 g</button>
        </div>

        {{-- Fat --}}
        <div class="at-box">
          <div class="at-box__top">
            <div class="at-miniicon is-orange">💧</div>
            <div>
              <div class="at-label">Fat</div>
              <div class="at-sub">g</div>
            </div>
          </div>

          <div class="at-inputwrap">
            <input class="at-input" type="number" name="fat_g" data-field="fat_g" value="{{ $entry->fat_g > 0 ? $entry->fat_g : '' }}" placeholder="{{ $targets['fat_g'] ?? 0 }}" />
            <span class="at-unit">g</span>
          </div>

          <button class="at-btn" type="button" data-inc="5" data-target="fat_g">＋ 5 g</button>
        </div>

        {{-- Creatine --}}
        <div class="at-box">
          <div class="at-box__top">
            <div class="at-miniicon is-purple">💧</div>
            <div>
              <div class="at-label">Creatine</div>
              <div class="at-sub">g</div>
            </div>
          </div>

          <div class="at-inputwrap">
            <input class="at-input" type="number" name="creatine_g" data-field="creatine_g" value="{{ $entry->creatine_g > 0 ? $entry->creatine_g : '' }}" placeholder="{{ $targets['creatine_g'] ?? 0 }}" />
            <span class="at-unit">g</span>
          </div>

          <button class="at-btn" type="button" data-inc="1" data-target="creatine_g">＋ 1 g</button>
        </div>

        {{-- Water --}}
        <div class="at-box">
          <div class="at-box__top">
            <div class="at-miniicon is-cyan">💧</div>
            <div>
              <div class="at-label">Water</div>
              <div class="at-sub">ml</div>
            </div>
          </div>

          <div class="at-inputwrap">
            <input class="at-input" type="number" name="water_ml" data-field="water_ml" value="{{ $entry->water_ml > 0 ? $entry->water_ml : '' }}" placeholder="{{ $targets['water_ml'] ?? 0 }}" />
            <span class="at-unit">ml</span>
          </div>

          <button class="at-btn" type="button" data-inc="250" data-target="water_ml">＋ 250 ml</button>
        </div>
      </div>
    
      <div class="at-foot">
        <div class="at-foot__left">Quick Fill:</div>
        <div class="at-foot__right">
          <button class="at-pillbtn" type="button" id="standardDayBtn">Standard Day</button>
        </div>
      </div>
    </form>
    </section>


    {{-- =========================
   Workout Selection (UI only)
   ========================= --}}
      @php
        
      @endphp

      <section class="pl-card ws-card" aria-label="Workout Selection">

        <div class="ws-head">
          <div class="ws-head__left">
            <div class="ws-icon" aria-hidden="true">🏋️</div>
            <h2 class="ws-title">Workout Selection</h2>
          </div>
        </div>

        {{-- Select workout --}}
        <div class="ws-field">
          <label class="ws-label" for="wsWorkoutSelect">Select Workout</label>

          <div class="ws-selectwrap">
            <select id="wsWorkoutSelect" class="ws-select">
              <option value="">Choose a workout routine...</option>
              @foreach($workouts as $w)
                <option value="{{ $w->id }}">{{ $w->name }}</option>
              @endforeach
            </select>
            <div class="ws-chevron" aria-hidden="true">⌄</div>
          </div>
        </div>

        {{-- Empty hint --}}
        <div class="ws-empty" data-ws-empty>
          <div class="ws-empty__icon" aria-hidden="true">🏋️</div>
          <div class="ws-empty__text">Select a workout to start logging exercises</div>
        </div>

        {{-- Selected workout content --}}
        <div class="ws-content" data-ws-content hidden>
          <div class="ws-content__head">
            <h3 class="ws-subtitle" data-ws-title>Workout Exercises</h3>
            <div class="ws-timer" data-ws-timer data-status="not_started">
              <span data-ws-timer-label>Starts with your first complete set</span>
              <strong data-ws-timer-value>--:--</strong>
            </div>
          </div>

          <div class="ws-list" data-ws-list></div>
        </div>

      </section>

      @php
        $measurementTab = $errors->getBag('body')->any()
          ? 'body'
          : session('measurement_tab', 'goals');
      @endphp

      <section
        class="pl-card bm-card"
        id="measurements"
        aria-labelledby="measurements-title"
        data-measurements-card
        data-default-tab="{{ $measurementTab }}"
      >
        <div class="bm-head">
          <div class="bm-head__icon" aria-hidden="true">&#128207;</div>
          <div>
            <span class="bm-eyebrow">Profile & body check-in</span>
            <h2 id="measurements-title">Goals & Measurements</h2>
          </div>
        </div>

        <p class="bm-intro">
          Update your daily nutrition targets or save today’s body measurements without leaving this page.
        </p>

        @if(session('measurement_status'))
          <div class="bm-success" role="status">{{ session('measurement_status') }}</div>
        @endif

        <div class="bm-tabs" role="tablist" aria-label="Measurement sections">
          <button
            class="bm-tab {{ $measurementTab === 'goals' ? 'is-active' : '' }}"
            id="bm-tab-goals"
            type="button"
            role="tab"
            aria-controls="bm-panel-goals"
            aria-selected="{{ $measurementTab === 'goals' ? 'true' : 'false' }}"
            data-bm-tab="goals"
          >
            Nutrition Goals
          </button>
          <button
            class="bm-tab {{ $measurementTab === 'body' ? 'is-active' : '' }}"
            id="bm-tab-body"
            type="button"
            role="tab"
            aria-controls="bm-panel-body"
            aria-selected="{{ $measurementTab === 'body' ? 'true' : 'false' }}"
            data-bm-tab="body"
          >
            Body Measurements
          </button>
        </div>

        <div
          class="bm-panel {{ $measurementTab === 'goals' ? 'is-active' : '' }}"
          id="bm-panel-goals"
          role="tabpanel"
          aria-labelledby="bm-tab-goals"
          data-bm-panel="goals"
          {{ $measurementTab === 'goals' ? '' : 'hidden' }}
        >
          <div class="bm-panel__head">
            <div>
              <h3>Daily targets</h3>
              <p>These values also update the targets shown across your Home and Add Today pages.</p>
            </div>
          </div>

          @if($errors->getBag('goals')->any())
            <div class="bm-error" role="alert">{{ $errors->getBag('goals')->first() }}</div>
          @endif

          <form action="{{ route('add-today.measurements.goals') }}" method="POST">
            @csrf
            <div class="bm-grid">
              <label class="bm-field">
                <span>Goal</span>
                <select name="goal" required>
                  <option value="bulk" @selected(old('goal', $goal?->goal) === 'bulk')>Build muscle</option>
                  <option value="cut" @selected(old('goal', $goal?->goal) === 'cut')>Lose fat</option>
                  <option value="recomp" @selected(old('goal', $goal?->goal) === 'recomp')>Body recomposition</option>
                </select>
              </label>

              <label class="bm-field">
                <span>Calories <small>kcal</small></span>
                <input type="number" inputmode="numeric" name="calorie_target" min="800" max="8000" step="1" required value="{{ old('calorie_target', $goal?->calorie_target) }}">
              </label>

              <label class="bm-field">
                <span>Protein <small>g</small></span>
                <input type="number" inputmode="numeric" name="protein_g" min="0" max="500" step="1" required value="{{ old('protein_g', $goal?->protein_g) }}">
              </label>

              <label class="bm-field">
                <span>Carbohydrates <small>g</small></span>
                <input type="number" inputmode="numeric" name="carbs_g" min="0" max="1200" step="1" required value="{{ old('carbs_g', $goal?->carbs_g) }}">
              </label>

              <label class="bm-field">
                <span>Fat <small>g</small></span>
                <input type="number" inputmode="numeric" name="fat_g" min="0" max="400" step="1" required value="{{ old('fat_g', $goal?->fat_g) }}">
              </label>

              <label class="bm-field">
                <span>Water <small>L/day</small></span>
                <input type="number" inputmode="decimal" name="water_l" min="0" max="10" step="0.1" value="{{ old('water_l', $goal?->water_l) }}">
              </label>

              <label class="bm-field">
                <span>Creatine <small>g/day</small></span>
                <input type="number" inputmode="decimal" name="creatine_g" min="0" max="20" step="0.1" value="{{ old('creatine_g', $goal?->creatine_g) }}">
              </label>
            </div>

            <div class="bm-actions">
              <span>Changes apply immediately to your current profile goals.</span>
              <button type="submit">Save nutrition goals</button>
            </div>
          </form>
        </div>

        <div
          class="bm-panel {{ $measurementTab === 'body' ? 'is-active' : '' }}"
          id="bm-panel-body"
          role="tabpanel"
          aria-labelledby="bm-tab-body"
          data-bm-panel="body"
          {{ $measurementTab === 'body' ? '' : 'hidden' }}
        >
          <div class="bm-panel__head">
            <div>
              <h3>Today’s body check-in</h3>
              <p>Measure consistently, ideally at the same time of day and without flexing.</p>
            </div>
            @if($latestBodyMeasurement)
              <span class="bm-last">Last saved {{ \Illuminate\Support\Carbon::parse($latestBodyMeasurement->recorded_on)->format('M j, Y') }}</span>
            @endif
          </div>

          @if($errors->getBag('body')->any())
            <div class="bm-error" role="alert">{{ $errors->getBag('body')->first() }}</div>
          @endif

          <form action="{{ route('add-today.measurements.body') }}" method="POST">
            @csrf
            <div class="bm-grid">
              <label class="bm-field">
                <span>Weight <small>kg</small></span>
                <input type="number" inputmode="decimal" name="weight_kg" min="20" max="400" step="0.1" placeholder="e.g. 82.5" value="{{ old('weight_kg', $currentWeight) }}">
              </label>

              <label class="bm-field">
                <span>Waist <small>cm</small></span>
                <input type="number" inputmode="decimal" name="waist_cm" min="30" max="250" step="0.1" placeholder="e.g. 84" value="{{ old('waist_cm', $latestBodyMeasurement?->waist_cm) }}">
              </label>

              <label class="bm-field">
                <span>Arms <small>cm</small></span>
                <input type="number" inputmode="decimal" name="arms_cm" min="10" max="100" step="0.1" placeholder="e.g. 39" value="{{ old('arms_cm', $latestBodyMeasurement?->arms_cm) }}">
              </label>

              <label class="bm-field">
                <span>Thighs <small>cm</small></span>
                <input type="number" inputmode="decimal" name="thighs_cm" min="20" max="150" step="0.1" placeholder="e.g. 61" value="{{ old('thighs_cm', $latestBodyMeasurement?->thighs_cm) }}">
              </label>

              <label class="bm-field">
                <span>Hips <small>cm</small></span>
                <input type="number" inputmode="decimal" name="hips_cm" min="30" max="250" step="0.1" placeholder="e.g. 96" value="{{ old('hips_cm', $latestBodyMeasurement?->hips_cm) }}">
              </label>

              <label class="bm-field">
                <span>Glutes / Seat <small>cm</small></span>
                <input type="number" inputmode="decimal" name="glutes_cm" min="30" max="250" step="0.1" placeholder="e.g. 101" value="{{ old('glutes_cm', $latestBodyMeasurement?->glutes_cm) }}">
              </label>
            </div>

            <div class="bm-actions">
              <span>Saving weight also updates your weight chart and current profile weight.</span>
              <button type="submit">Save today’s measurements</button>
            </div>
          </form>
        </div>
      </section>

      <section class="pl-card pp-card" id="progress-photos" aria-labelledby="progress-photos-title">
        <div class="pp-head">
          <div class="pp-head__title">
            <div class="pp-head__icon" aria-hidden="true">📸</div>
            <div>
              <span class="pp-eyebrow">Private check-in</span>
              <h2 id="progress-photos-title">Progress Photos</h2>
            </div>
          </div>
          <div class="pp-history">
            <strong>{{ $progressPhotoCount }}</strong>
            <span>{{ Str::plural('check-in', $progressPhotoCount) }} saved</span>
          </div>
        </div>

        <p class="pp-intro">
          Add three consistent angles to create a useful visual record. Your photos are stored privately and are only available to your account.
        </p>

        @if(session('progress_photo_status'))
          <div class="pp-success" role="status">{{ session('progress_photo_status') }}</div>
        @endif

        @if($errors->hasAny(['front_photo', 'side_photo', 'back_photo']))
          <div class="pp-error" role="alert">
            {{ $errors->first('front_photo') ?: ($errors->first('side_photo') ?: $errors->first('back_photo')) }}
          </div>
        @endif

        <div class="pp-stepper" aria-label="Progress photo steps">
          <div class="pp-stepper__line"><span data-pp-progress></span></div>
          @foreach([
            ['Front', 'Face the camera'],
            ['Side', 'Turn 90 degrees'],
            ['Back', 'Face away'],
          ] as $index => [$label, $hint])
            <button class="pp-step-dot {{ $index === 0 ? 'is-active' : '' }}" type="button" data-pp-step-button="{{ $index }}">
              <span>{{ $index + 1 }}</span>
              <strong>{{ $label }}</strong>
              <small>{{ $hint }}</small>
            </button>
          @endforeach
        </div>

        <form
          class="pp-form"
          action="{{ route('progress-photos.store') }}"
          method="POST"
          enctype="multipart/form-data"
          data-progress-photo-form
          data-success-url="{{ route('add-today') }}#progress-photos"
        >
          @csrf

          @foreach([
            ['front_photo', 'Front view', 'Stand straight and face the camera.', 'Front'],
            ['side_photo', 'Side view', 'Turn sideways and keep the same posture.', 'Side'],
            ['back_photo', 'Back view', 'Face away from the camera with a relaxed posture.', 'Back'],
          ] as $index => [$field, $titleText, $descriptionText, $shortLabel])
            <div class="pp-panel {{ $index === 0 ? 'is-active' : '' }}" data-pp-panel="{{ $index }}" {{ $index === 0 ? '' : 'hidden' }}>
              <div class="pp-panel__copy">
                <span class="pp-panel__count">Step {{ $index + 1 }} of 3</span>
                <h3>{{ $titleText }}</h3>
                <p>{{ $descriptionText }} Try to use similar lighting, distance, and clothing each time.</p>
                <ul>
                  <li>Keep your full body visible</li>
                  <li>Use natural, even lighting</li>
                  <li>Stand in a neutral pose</li>
                </ul>
              </div>

              <label class="pp-upload" for="{{ $field }}" data-pp-upload>
                <input
                  id="{{ $field }}"
                  name="{{ $field }}"
                  type="file"
                  accept="image/jpeg,image/png,image/webp,image/heic,image/heif"
                  data-pp-input
                  required
                >
                <span class="pp-upload__empty" data-pp-empty>
                  <span class="pp-upload__figure" aria-hidden="true">{{ $index === 1 ? '◐' : ($index === 2 ? '◒' : '◉') }}</span>
                  <strong>Add {{ strtolower($shortLabel) }} photo</strong>
                  <small>Take a photo or choose one from your library</small>
                </span>
                <img data-pp-preview alt="{{ $shortLabel }} progress photo preview" hidden>
                <span class="pp-upload__change" data-pp-change hidden>Choose another photo</span>
              </label>
              <p class="pp-file-status" data-pp-file-status>No photo selected</p>
            </div>
          @endforeach

          <div class="pp-form__message" data-pp-message role="alert" hidden></div>

          <div class="pp-actions">
            <button class="pp-btn pp-btn--secondary" type="button" data-pp-back hidden>Back</button>
            <span class="pp-privacy">🔒 Private storage</span>
            <button class="pp-btn pp-btn--primary" type="button" data-pp-next>Next: Side view</button>
            <button class="pp-btn pp-btn--primary" type="submit" data-pp-save hidden>Save progress photos</button>
          </div>
        </form>

        @if($latestProgressPhotoDate)
          <p class="pp-latest">Last check-in: {{ \Illuminate\Support\Carbon::parse($latestProgressPhotoDate)->format('M j, Y') }}</p>
        @endif
      </section>

  </main>
    @php
    $workoutsForJs = $workouts->map(function ($w) use ($exerciseRanks) {
        return [
            'id' => $w->id,
            'name' => $w->name,
            'exercises' => $w->exercises->map(function ($e) use ($exerciseRanks) {
                return [
                    'id' => $e->id,
                    'name' => $e->name,
                    'muscle_group' => $e->muscle_group ?? null,
                    'rank' => $exerciseRanks->get((string) $e->id),
                    'default_sets' => 3,
                ];
            })->values(),
        ];
    })->values();
  @endphp
  
  <script>
    // NUTRITION JS - Auto-save and Quick Fill logic
        (function(){
        const form = document.getElementById('nutritionForm');
        if (!form) return;

        const saveUrl = form.getAttribute('action');
        const token = form.querySelector('input[name="_token"]').value;

        const fields = Array.from(form.querySelectorAll('[data-field]'));

        // Targets from backend placeholders (we reuse placeholders as target values)
        function getTargets() {
            const out = {};
            fields.forEach(i => {
            const key = i.dataset.field;
            const val = parseInt(i.getAttribute('placeholder') || '0', 10);
            out[key] = isNaN(val) ? 0 : val;
            });
            return out;
        }

        let saveTimer = null;

        async function saveNow() {
            const payload = {};
            fields.forEach(i => {
            payload[i.name] = i.value === '' ? 0 : parseInt(i.value, 10);
            });

            try {
            const res = await fetch(saveUrl, {
              method: 'POST',
              headers: {
                'Accept': 'application/json',
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': token
              },
              body: JSON.stringify(payload)
            });

            if (!res.ok) {
              const txt = await res.text();
              console.error('Nutrition save failed:', res.status, txt);
              return;
            }

            const data = await res.json();

            if (window.showAchievementToasts) {
              window.showAchievementToasts(data.unlocked || []);
            }
            } catch (e) {
            // (optional) you can show a toast later
            console.error(e);
            }
        }

        function scheduleSave() {
            clearTimeout(saveTimer);
            saveTimer = setTimeout(saveNow, 350);
        }

        // Auto-save when typing
        fields.forEach(i => i.addEventListener('input', scheduleSave));

        // + buttons increment and save
        document.addEventListener('click', (e) => {
            const btn = e.target.closest('[data-inc][data-target]');
            if (!btn) return;

            const key = btn.dataset.target;
            const inc = parseInt(btn.dataset.inc, 10) || 0;
            const input = form.querySelector(`[name="${key}"]`);
            if (!input) return;

            const curr = parseInt(input.value || '0', 10) || 0;
            input.value = curr + inc;
            scheduleSave();
        });

        // Standard Day fills from profile targets and saves
        const standardBtn = document.getElementById('standardDayBtn');
        if (standardBtn) {
            standardBtn.addEventListener('click', () => {
            const targets = getTargets();
            fields.forEach(i => {
                const key = i.dataset.field;
                i.value = targets[key] ?? 0;
            });
            saveNow();
            });
        }
        })();
        </script>

    <script>
      (function () {
        const workouts = @json($workoutsForJs);
        const exerciseHistory = @json($exerciseHistory);

        const select  = document.getElementById('wsWorkoutSelect');
        const empty   = document.querySelector('[data-ws-empty]');
        const content = document.querySelector('[data-ws-content]');
        const title   = document.querySelector('[data-ws-title]');
        const list    = document.querySelector('[data-ws-list]');
        const timer   = document.querySelector('[data-ws-timer]');
        const timerLabel = document.querySelector('[data-ws-timer-label]');
        const timerValue = document.querySelector('[data-ws-timer-value]');

        if (!select || !empty || !content || !title || !list || !timer || !timerLabel || !timerValue) return;

        const SAVE_URL = "{{ route('add-today.workout.save') }}";
        const LOAD_URL = "{{ route('add-today.workout.today') }}";
        const CSRF     = "{{ csrf_token() }}";
        let timingInterval = null;
        let currentTiming = { status: 'not_started' };
        const rankUpQueue = [];
        let rankUpVisible = false;

        function updateExerciseRankChip(rankUp) {
          const card = list.querySelector(`.ws-ex[data-exercise-id="${rankUp.exercise_id}"]`);
          const chip = card?.querySelector('[data-exercise-rank]');
          if (!chip) return;

          chip.hidden = false;
          chip.dataset.rank = rankUp.rank_slug;
          chip.style.setProperty('--exercise-rank-color', rankUp.color);
          chip.innerHTML = `<img src="${esc(rankUp.icon)}" alt="" width="30" height="30"><span>${esc(rankUp.rank)}</span>`;
        }

        function queueRankUps(rankUps = []) {
          rankUps.forEach(rankUp => {
            updateExerciseRankChip(rankUp);
            rankUpQueue.push(rankUp);
          });
          showNextRankUp();
        }

        function showNextRankUp() {
          const overlay = document.getElementById('exerciseRankUp');
          if (rankUpVisible || !overlay || rankUpQueue.length === 0) return;

          rankUpVisible = true;
          const rankUp = rankUpQueue.shift();
          overlay.style.setProperty('--rank-up-color', rankUp.color);
          overlay.querySelector('[data-rank-up-image]').src = rankUp.icon;
          overlay.querySelector('[data-rank-up-image]').alt = `${rankUp.rank} rank badge`;
          overlay.querySelector('[data-rank-up-name]').textContent = rankUp.rank;
          overlay.querySelector('[data-rank-up-exercise]').textContent = rankUp.exercise_name;
          overlay.querySelector('[data-rank-up-score]').textContent = `${rankUp.score} / 100 strength score`;
          overlay.hidden = false;

          requestAnimationFrame(() => overlay.classList.add('is-visible'));

          const close = () => {
            overlay.classList.remove('is-visible');
            window.setTimeout(() => {
              overlay.hidden = true;
              rankUpVisible = false;
              showNextRankUp();
            }, 350);
          };

          const timeout = window.setTimeout(close, 3600);
          overlay.querySelector('[data-rank-up-close]').onclick = () => {
            window.clearTimeout(timeout);
            close();
          };
        }

        function esc(s){
          return String(s).replace(/[&<>"']/g, m => ({
            '&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'
          }[m]));
        }

        function formatElapsed(totalSeconds) {
          const seconds = Math.max(0, Math.floor(Number(totalSeconds) || 0));
          const hours = Math.floor(seconds / 3600);
          const minutes = Math.floor((seconds % 3600) / 60);
          const remainder = seconds % 60;
          const parts = [minutes, remainder].map(value => String(value).padStart(2, '0'));

          return hours > 0
            ? `${String(hours).padStart(2, '0')}:${parts.join(':')}`
            : parts.join(':');
        }

        function updateTimerClock() {
          if (currentTiming.status !== 'running' || !currentTiming.started_at) return;

          const startedAt = Date.parse(currentTiming.started_at);
          if (Number.isNaN(startedAt)) return;

          timerValue.textContent = formatElapsed((Date.now() - startedAt) / 1000);
        }

        function applyTiming(nextTiming = null) {
          clearInterval(timingInterval);
          timingInterval = null;
          currentTiming = nextTiming || { status: 'not_started' };
          timer.dataset.status = currentTiming.status;

          if (currentTiming.status === 'completed') {
            timerLabel.textContent = 'Workout completed in';
            timerValue.textContent = formatElapsed(currentTiming.duration_seconds);
            return;
          }

          if (currentTiming.status === 'running') {
            timerLabel.textContent = 'Workout in progress';
            updateTimerClock();
            timingInterval = setInterval(updateTimerClock, 1000);
            return;
          }

          timerLabel.textContent = 'Starts with your first complete set';
          timerValue.textContent = '--:--';
        }

        // =========
        // AUTOSAVE
        // =========
        let saveTimer = null;
        function scheduleSave(){
          clearTimeout(saveTimer);
          saveTimer = setTimeout(saveNow, 450);
        }

        function buildPayload(){
          const workoutId = select.value;
          if (!workoutId) return null;

          const exercises = Array.from(list.querySelectorAll('.ws-ex')).map(exCard => {
            const exerciseId = Number(exCard.dataset.exerciseId);

            const rows = Array.from(exCard.querySelectorAll('.ws-sets .ws-row')).map((row, idx) => {
              const repsVal = row.querySelector('.ws-reps')?.value ?? '';
              const wVal = row.querySelector('.ws-weight')?.value ?? '';
              const setType = row.querySelector('.ws-settype')?.value ?? 'normal';
              const dropRepsVal = row.querySelector('.ws-drop-reps')?.value ?? '';
              const dropWeightVal = row.querySelector('.ws-drop-weight')?.value ?? '';

              return {
                set_number: idx + 1,
                set_type: setType,
                reps: repsVal === '' ? null : Number(repsVal),
                weight_kg: wVal === '' ? null : Number(wVal),
                drop_reps: setType === 'drop' && dropRepsVal !== '' ? Number(dropRepsVal) : null,
                drop_weight_kg: setType === 'drop' && dropWeightVal !== '' ? Number(dropWeightVal) : null,
              };
            });

            return { exercise_id: exerciseId, sets: rows };
          });

          return { workout_id: Number(workoutId), exercises };
        }

        async function saveNow(){
          const payload = buildPayload();
          if (!payload) return;

          try{
            const res = await fetch(SAVE_URL, {
            method: 'POST',
            headers: {
              'Accept': 'application/json',
              'Content-Type': 'application/json',
              'X-CSRF-TOKEN': CSRF
            },
            body: JSON.stringify(payload)
          });

          if (!res.ok) {
            const txt = await res.text();
            console.error('Save failed:', res.status, txt);
            return;
          }

          const data = await res.json();
          applyTiming(data.timing);

          if (window.showAchievementToasts) {
            window.showAchievementToasts(data.unlocked || []);
          }
          queueRankUps(data.rank_ups || []);

            // Optional: debug errors
            if (!res.ok) {
              const txt = await res.text();
              console.error('Save failed:', res.status, txt);
            }
          } catch(e){
            console.error(e);
          }
        }

        // Close: when user changes select, we save (after render)
        select.addEventListener('change', () => {
          renderWorkout(select.value, null);
          scheduleSave();
        });

        // Save on typing reps/weight
        document.addEventListener('input', (e) => {
          if (e.target.closest('.ws-card') && e.target.classList.contains('ws-in')) {
            const row = e.target.closest('.ws-row');

            if (
              currentTiming.status === 'not_started'
              && row?.querySelector('.ws-reps')?.value !== ''
              && row?.querySelector('.ws-weight')?.value !== ''
            ) {
              applyTiming({
                status: 'running',
                started_at: new Date().toISOString(),
              });
            }

            scheduleSave();
          }
        });

        document.addEventListener('change', (e) => {
          if (e.target.closest('.ws-card') && e.target.classList.contains('ws-settype')) {
            syncSetType(e.target.closest('.ws-row'));
            scheduleSave();
          }
        });

        // Save on add/remove set
        document.addEventListener('click', (e) => {
          if (e.target.closest('.ws-card') && (e.target.closest('.ws-addset') || e.target.closest('.ws-remove'))) {
            scheduleSave();
          }
        });

        // =========
        // UI BUILD
        // =========
        function buildExerciseCard(ex, savedSets = null) {
          const history = exerciseHistory[String(ex.id)] || null;
          const rank = ex.rank || null;
          const wrap = document.createElement('div');
          wrap.className = 'ws-ex';
          wrap.dataset.exerciseId = ex.id;

          wrap.innerHTML = `
            <button type="button" class="ws-ex__head" aria-expanded="false">
              <div class="ws-ex__left">
                <div class="ws-ex__mini">🏋️</div>
                <div>
                  <span class="ws-ex__name">${esc(ex.name)}</span>
                  <span class="ws-ex__sets">(${savedSets ? savedSets.length : (ex.default_sets || 3)} sets)</span>
                </div>
              </div>
              <div class="ws-ex__right">
                <span
                  class="ws-ex__rank"
                  data-exercise-rank
                  data-rank="${rank ? esc(rank.rank_slug) : ''}"
                  style="${rank ? `--exercise-rank-color: ${rank.color}` : ''}"
                  ${rank ? '' : 'hidden'}
                >
                  ${rank ? `<img src="${esc(rank.icon)}" alt="" width="30" height="30"><span>${esc(rank.rank)}</span>` : ''}
                </span>
                <div class="ws-ex__chev">⌄</div>
              </div>
            </button>

            <div class="ws-ex__body">
              <div class="ws-row ws-th">
                <div>Set</div>
                <div>Reps</div>
                <div>Weight (kg)</div>
                <div>Type</div>
                <div>Actions</div>
              </div>

              <div class="ws-sets"></div>

              <button type="button" class="ws-addset">
                <span class="ws-addset__inner">
                  <span class="ws-addset__plus">＋</span>
                  <span>Add Set</span>
                </span>
              </button>
            </div>
          `;

          const head     = wrap.querySelector('.ws-ex__head');
          const setsWrap = wrap.querySelector('.ws-sets');
          const addSetBtn= wrap.querySelector('.ws-addset');

          function renumber(){
            Array.from(setsWrap.querySelectorAll('.ws-row')).forEach((r, idx) => {
              const n = r.querySelector('.ws-setnum');
              if (n) n.textContent = String(idx + 1);
            });
          }

          function addSet(prefill = {}, historySet = null) {
            const setIndex = setsWrap.querySelectorAll('.ws-row').length + 1;
            const previousSet = historySet
              || history?.sets?.find(set => Number(set.set_number) === setIndex)
              || null;
            const repsPlaceholder = previousSet?.reps ?? history?.max_reps ?? 12;
            const weightPlaceholder = previousSet?.weight_kg ?? history?.max_weight_kg ?? 80;
            const setType = ['normal', 'warmup', 'drop'].includes(prefill.set_type)
              ? prefill.set_type
              : (['normal', 'warmup', 'drop'].includes(previousSet?.set_type) ? previousSet.set_type : 'normal');
            const dropRepsPlaceholder = previousSet?.drop_reps ?? 8;
            const dropWeightPlaceholder = previousSet?.drop_weight_kg ?? Math.max(0, Number(weightPlaceholder) * 0.7);

            const row = document.createElement('div');
            row.className = 'ws-row';
            row.innerHTML = `
              <div class="ws-setnum">${setIndex}</div>
              <div class="ws-field" data-label="Reps"><input class="ws-in ws-reps" type="number" inputmode="numeric" min="0" placeholder="${repsPlaceholder}" value="${prefill.reps ?? ''}" aria-label="Set ${setIndex} reps; previous ${repsPlaceholder}"></div>
              <div class="ws-field" data-label="Weight (kg)"><input class="ws-in ws-weight" type="number" inputmode="decimal" min="0" step="0.5" placeholder="${weightPlaceholder}" value="${prefill.weight_kg ?? ''}" aria-label="Set ${setIndex} weight in kilograms; previous ${weightPlaceholder}"></div>
              <div class="ws-field" data-label="Set type">
                <select class="ws-settype" aria-label="Set ${setIndex} type">
                  <option value="normal" ${setType === 'normal' ? 'selected' : ''}>Normal</option>
                  <option value="warmup" ${setType === 'warmup' ? 'selected' : ''}>Warm-up</option>
                  <option value="drop" ${setType === 'drop' ? 'selected' : ''}>Drop set</option>
                </select>
              </div>
              <div class="ws-drop-fields" hidden>
                <div class="ws-drop-copy">
                  <strong>Drop set</strong>
                  <span>Log the reduced weight immediately after the main set.</span>
                </div>
                <label>
                  <span>Drop reps</span>
                  <input class="ws-in ws-drop-reps" type="number" inputmode="numeric" min="0" placeholder="${dropRepsPlaceholder}" value="${prefill.drop_reps ?? ''}">
                </label>
                <label>
                  <span>Drop weight (kg)</span>
                  <input class="ws-in ws-drop-weight" type="number" inputmode="decimal" min="0" step="0.5" placeholder="${Number(dropWeightPlaceholder).toFixed(1)}" value="${prefill.drop_weight_kg ?? ''}">
                </label>
              </div>
              <div class="ws-act">
                <button type="button" class="ws-remove" title="Remove set" aria-label="Remove set">–</button>
              </div>
            `;

            syncSetType(row);

            row.querySelector('.ws-remove').addEventListener('click', () => {
              row.remove();
              renumber();
              scheduleSave();
            });

            setsWrap.appendChild(row);
          }

          // Prefill sets if saved, else create defaults
          if (savedSets && savedSets.length) {
            savedSets.forEach((set, index) => addSet(set, history?.sets?.[index] || null));
          } else {
            for (let i = 0; i < (ex.default_sets || 3); i++) {
              addSet({}, history?.sets?.[i] || null);
            }
          }

          // Toggle accordion
          head.addEventListener('click', () => {
            const open = wrap.classList.toggle('is-open');
            head.setAttribute('aria-expanded', open ? 'true' : 'false');
          });

          // Add set
          addSetBtn.addEventListener('click', () => {
            addSet();
            scheduleSave();
          });

          return wrap;
        }

        function syncSetType(row) {
          if (!row) return;

          const setType = row.querySelector('.ws-settype')?.value ?? 'normal';
          row.classList.toggle('is-warmup', setType === 'warmup');
          row.classList.toggle('is-drop', setType === 'drop');

          const dropFields = row.querySelector('.ws-drop-fields');
          if (dropFields) {
            dropFields.hidden = setType !== 'drop';
          }
        }

        function renderWorkout(workoutId, savedLog) {
          list.innerHTML = '';
          applyTiming(savedLog?.timing || null);

          const w = workouts.find(x => String(x.id) === String(workoutId));
          if (!w) {
            content.hidden = true;
            empty.hidden = false;
            return;
          }

          title.textContent = `${w.name} Exercises`;

          // Create map of saved sets by exercise_id (if savedLog exists)
          const savedMap = new Map();
          if (savedLog && Array.isArray(savedLog.exercises)) {
            savedLog.exercises.forEach(ex => {
              savedMap.set(String(ex.exercise_id), ex.sets || []);
            });
          }

          (w.exercises || []).forEach(ex => {
            const savedSets = savedMap.get(String(ex.id)) || null;
            list.appendChild(buildExerciseCard(ex, savedSets));
          });

          empty.hidden = true;
          content.hidden = false;
        }

        // =========
        // LOAD TODAY LOG (prefill)
        // =========
        async function loadToday() {
          try {
            const res = await fetch(LOAD_URL, { headers: { 'Accept': 'application/json' }});
            if (!res.ok) return;

            const data = await res.json();
            if (!data || !data.log) return;

            // set dropdown
            select.value = String(data.log.workout_id || '');
            if (select.value) {
              renderWorkout(select.value, data.log);
            }
          } catch (e) {
            console.error(e);
          }
        }

        // Start with saved log if exists
        loadToday();

      })();
      </script>

      <script>
      (() => {
        const form = document.querySelector('[data-progress-photo-form]');
        if (!form) return;

        const panels = [...form.querySelectorAll('[data-pp-panel]')];
        const inputs = [...form.querySelectorAll('[data-pp-input]')];
        const stepButtons = [...document.querySelectorAll('[data-pp-step-button]')];
        const progress = document.querySelector('[data-pp-progress]');
        const backButton = form.querySelector('[data-pp-back]');
        const nextButton = form.querySelector('[data-pp-next]');
        const saveButton = form.querySelector('[data-pp-save]');
        const message = form.querySelector('[data-pp-message]');
        const stepNames = ['Front', 'Side', 'Back'];
        const previewUrls = new Map();
        let currentStep = 0;

        function showMessage(text) {
          message.textContent = text;
          message.hidden = !text;
        }

        function canVisit(step) {
          return inputs.slice(0, step).every(input => input.files.length > 0);
        }

        function render() {
          panels.forEach((panel, index) => {
            panel.hidden = index !== currentStep;
            panel.classList.toggle('is-active', index === currentStep);
          });

          stepButtons.forEach((button, index) => {
            button.classList.toggle('is-active', index === currentStep);
            button.classList.toggle('is-complete', index < currentStep && inputs[index].files.length > 0);
            button.setAttribute('aria-current', index === currentStep ? 'step' : 'false');
          });

          progress.style.width = `${currentStep * 50}%`;
          backButton.hidden = currentStep === 0;
          nextButton.hidden = currentStep === panels.length - 1;
          saveButton.hidden = currentStep !== panels.length - 1;
          if (currentStep < panels.length - 1) {
            nextButton.textContent = `Next: ${stepNames[currentStep + 1]} view`;
          }
        }

        function goTo(step) {
          if (step < 0 || step >= panels.length) return;
          if (!canVisit(step)) {
            const missing = inputs.findIndex((input, index) => index < step && !input.files.length);
            showMessage(`Add your ${stepNames[missing].toLowerCase()} photo before continuing.`);
            return;
          }
          currentStep = step;
          showMessage('');
          render();
        }

        inputs.forEach((input, index) => {
          input.addEventListener('change', () => {
            const file = input.files[0];
            const panel = panels[index];
            const preview = panel.querySelector('[data-pp-preview]');
            const empty = panel.querySelector('[data-pp-empty]');
            const change = panel.querySelector('[data-pp-change]');
            const status = panel.querySelector('[data-pp-file-status]');

            if (previewUrls.has(index)) {
              URL.revokeObjectURL(previewUrls.get(index));
              previewUrls.delete(index);
            }

            if (!file) {
              preview.removeAttribute('src');
              preview.hidden = true;
              empty.hidden = false;
              change.hidden = true;
              status.textContent = 'No photo selected';
              render();
              return;
            }

            const url = URL.createObjectURL(file);
            previewUrls.set(index, url);
            preview.src = url;
            preview.hidden = false;
            empty.hidden = true;
            change.hidden = false;
            status.textContent = `${file.name} · ${(file.size / 1024 / 1024).toFixed(1)} MB`;
            showMessage('');
            render();
          });
        });

        stepButtons.forEach((button, index) => {
          button.addEventListener('click', () => goTo(index));
        });

        backButton.addEventListener('click', () => goTo(currentStep - 1));
        nextButton.addEventListener('click', () => {
          if (!inputs[currentStep].files.length) {
            showMessage(`Add your ${stepNames[currentStep].toLowerCase()} photo before continuing.`);
            return;
          }
          goTo(currentStep + 1);
        });

        async function decodeImage(file) {
          if ('createImageBitmap' in window) {
            return createImageBitmap(file, { imageOrientation: 'from-image' });
          }

          return new Promise((resolve, reject) => {
            const url = URL.createObjectURL(file);
            const image = new Image();
            image.onload = () => {
              URL.revokeObjectURL(url);
              resolve(image);
            };
            image.onerror = () => {
              URL.revokeObjectURL(url);
              reject(new Error('This image format could not be prepared by your browser.'));
            };
            image.src = url;
          });
        }

        async function preparePhoto(file, name) {
          let source;
          try {
            source = await decodeImage(file);
          } catch (error) {
            if (file.size <= 1900 * 1024) return file;
            throw new Error('This photo is too large for your browser to prepare. Please choose a smaller photo.');
          }

          const sourceWidth = source.width || source.naturalWidth;
          const sourceHeight = source.height || source.naturalHeight;
          const scale = Math.min(1, 1440 / Math.max(sourceWidth, sourceHeight));
          const canvas = document.createElement('canvas');
          canvas.width = Math.max(1, Math.round(sourceWidth * scale));
          canvas.height = Math.max(1, Math.round(sourceHeight * scale));
          canvas.getContext('2d').drawImage(source, 0, 0, canvas.width, canvas.height);
          if (typeof source.close === 'function') source.close();

          const blob = await new Promise(resolve => canvas.toBlob(resolve, 'image/jpeg', .8));
          if (!blob) throw new Error('This photo could not be prepared. Please choose another image.');

          const prepared = blob.size < file.size || file.size > 1900 * 1024
            ? new File([blob], `${name}.jpg`, { type: 'image/jpeg', lastModified: Date.now() })
            : file;

          if (prepared.size > 2 * 1024 * 1024) {
            throw new Error('This photo is still too large. Please choose a smaller image.');
          }

          return prepared;
        }

        form.addEventListener('submit', async event => {
          event.preventDefault();

          const missing = inputs.findIndex(input => !input.files.length);
          if (missing !== -1) {
            goTo(missing);
            showMessage(`Add your ${stepNames[missing].toLowerCase()} photo before saving.`);
            return;
          }

          saveButton.disabled = true;
          saveButton.textContent = 'Preparing photos…';
          showMessage('');

          try {
            const data = new FormData(form);
            const fields = ['front_photo', 'side_photo', 'back_photo'];

            for (let index = 0; index < inputs.length; index++) {
              const prepared = await preparePhoto(inputs[index].files[0], stepNames[index].toLowerCase());
              data.set(fields[index], prepared, prepared.name);
            }

            saveButton.textContent = 'Saving…';
            const response = await fetch(form.action, {
              method: 'POST',
              headers: { 'Accept': 'application/json' },
              body: data,
            });

            if (!response.ok) {
              const result = await response.json().catch(() => ({}));
              const firstError = result.errors ? Object.values(result.errors).flat()[0] : null;
              throw new Error(firstError || 'Your photos could not be saved. Please try again.');
            }

            window.location.assign(form.dataset.successUrl);
          } catch (error) {
            showMessage(error.message || 'Your photos could not be saved. Please try again.');
            saveButton.disabled = false;
            saveButton.textContent = 'Save progress photos';
          }
        });

        window.addEventListener('pagehide', () => {
          previewUrls.forEach(url => URL.revokeObjectURL(url));
        });

        render();
      })();
      </script>

<script>
  (() => {
    const card = document.querySelector('[data-measurements-card]');
    if (!card) return;

    const tabs = Array.from(card.querySelectorAll('[data-bm-tab]'));
    const panels = Array.from(card.querySelectorAll('[data-bm-panel]'));

    function activate(name, focus = false) {
      tabs.forEach(tab => {
        const active = tab.dataset.bmTab === name;
        tab.classList.toggle('is-active', active);
        tab.setAttribute('aria-selected', active ? 'true' : 'false');
        tab.tabIndex = active ? 0 : -1;
        if (active && focus) tab.focus();
      });

      panels.forEach(panel => {
        const active = panel.dataset.bmPanel === name;
        panel.classList.toggle('is-active', active);
        panel.hidden = !active;
      });
    }

    tabs.forEach((tab, index) => {
      tab.addEventListener('click', () => activate(tab.dataset.bmTab));
      tab.addEventListener('keydown', event => {
        if (!['ArrowLeft', 'ArrowRight'].includes(event.key)) return;
        event.preventDefault();
        const direction = event.key === 'ArrowRight' ? 1 : -1;
        const next = tabs[(index + direction + tabs.length) % tabs.length];
        activate(next.dataset.bmTab, true);
      });
    });

    activate(card.dataset.defaultTab || 'goals');
  })();
</script>

<div class="exercise-rank-up" id="exerciseRankUp" hidden role="dialog" aria-modal="true" aria-labelledby="exerciseRankUpTitle">
  <div class="exercise-rank-up__light" aria-hidden="true"></div>
  <div class="exercise-rank-up__content">
    <div class="exercise-rank-up__eyebrow">Exercise rank upgraded</div>
    <img class="exercise-rank-up__badge" data-rank-up-image src="" alt="" width="260" height="260">
    <h2 class="exercise-rank-up__rank" id="exerciseRankUpTitle" data-rank-up-name></h2>
    <p class="exercise-rank-up__exercise" data-rank-up-exercise></p>
    <p class="exercise-rank-up__score" data-rank-up-score></p>
    <button class="exercise-rank-up__close" type="button" data-rank-up-close>Continue</button>
  </div>
</div>

<x-achievement-toasts />
<x-footer />
</body>
</html>
