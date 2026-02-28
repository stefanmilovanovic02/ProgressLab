<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Add Today • ProgressLab</title>
  <link rel="stylesheet" href="{{ asset('css/auth.css') }}">
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
          <h3 class="ws-subtitle" data-ws-title>Workout Exercises</h3>

          <div class="ws-list" data-ws-list></div>
        </div>

      </section>

  </main>
    @php
    $workoutsForJs = $workouts->map(function ($w) {
        return [
            'id' => $w->id,
            'name' => $w->name,
            'exercises' => $w->exercises->map(function ($e) {
                return [
                    'id' => $e->id,
                    'name' => $e->name,
                    'muscle_group' => $e->muscle_group ?? null,
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
            await fetch(saveUrl, {
                method: 'POST',
                headers: {
                'Accept': 'application/json',
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': token
                },
                body: JSON.stringify(payload)
            });
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

        const select  = document.getElementById('wsWorkoutSelect');
        const empty   = document.querySelector('[data-ws-empty]');
        const content = document.querySelector('[data-ws-content]');
        const title   = document.querySelector('[data-ws-title]');
        const list    = document.querySelector('[data-ws-list]');

        if (!select || !empty || !content || !title || !list) return;

        const SAVE_URL = "{{ route('add-today.workout.save') }}";
        const LOAD_URL = "{{ route('add-today.workout.today') }}";
        const CSRF     = "{{ csrf_token() }}";

        function esc(s){
          return String(s).replace(/[&<>"']/g, m => ({
            '&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'
          }[m]));
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
              const inputs = row.querySelectorAll('input.ws-in');
              const repsVal = inputs[0]?.value ?? '';
              const wVal    = inputs[1]?.value ?? '';

              return {
                set_number: idx + 1,
                reps: repsVal === '' ? null : Number(repsVal),
                weight_kg: wVal === '' ? null : Number(wVal),
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
              <div class="ws-ex__chev">⌄</div>
            </button>

            <div class="ws-ex__body">
              <div class="ws-row ws-th">
                <div>Set</div>
                <div>Reps</div>
                <div>Weight (kg)</div>
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

          function addSet(prefill = {}) {
            const setIndex = setsWrap.querySelectorAll('.ws-row').length + 1;

            const row = document.createElement('div');
            row.className = 'ws-row';
            row.innerHTML = `
              <div class="ws-setnum">${setIndex}</div>
              <div><input class="ws-in" type="number" min="0" placeholder="12" value="${prefill.reps ?? ''}"></div>
              <div><input class="ws-in" type="number" min="0" step="0.5" placeholder="80" value="${prefill.weight_kg ?? ''}"></div>
              <div class="ws-act">
                <button type="button" class="ws-remove" title="Remove set" aria-label="Remove set">–</button>
              </div>
            `;

            row.querySelector('.ws-remove').addEventListener('click', () => {
              row.remove();
              renumber();
              scheduleSave();
            });

            setsWrap.appendChild(row);
          }

          // Prefill sets if saved, else create defaults
          if (savedSets && savedSets.length) {
            savedSets.forEach(s => addSet(s));
          } else {
            for (let i = 0; i < (ex.default_sets || 3); i++) addSet();
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

        function renderWorkout(workoutId, savedLog) {
          list.innerHTML = '';

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

</body>
</html>