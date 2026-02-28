<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Charts • ProgressLab</title>
  <link rel="stylesheet" href="{{ asset('css/auth.css') }}">

  {{-- Chart.js --}}
  <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
</head>
<body class="auth-body">

  <x-navbar />

  <main class="pl-container">

    {{-- Page Header --}}
    <div class="pl-pagehead">
      <div class="pl-pagehead__title">
        <div class="pl-pagehead__icon">📈</div>
        <h1>Progress Charts</h1>
      </div>
      <p class="pl-pagehead__sub">Track your nutrition and workouts over time.</p>
    </div>

    {{-- MACROS CARD --}}
    <section class="pl-card ch-card" aria-label="Macronutrient Progress">

      <div class="ch-head">
        <div class="ch-head__left">
          <div class="ch-icon">💧</div>
          <h2 class="ch-title">Macronutrient Progress</h2>
        </div>
      </div>

      <div class="ch-controls">
        <div class="ch-control">
          <label class="ch-label" for="macroSelect">Select Macronutrient</label>

          <div class="ch-selectwrap">
            <span class="ch-dot" data-macro-dot></span>
            <select id="macroSelect" class="ch-select">
              <option value="calories">Calories</option>
              <option value="protein">Protein</option>
              <option value="carbs">Carbs</option>
              <option value="fat">Fat</option>
              <option value="creatine">Creatine</option>
              <option value="water">Water</option>
            </select>
            <span class="ch-chevron">⌄</span>
          </div>
        </div>

        <div class="ch-control">
          <label class="ch-label">Time Period</label>

          <div class="ch-seg">
            <button type="button" class="ch-segbtn" data-period="week">This Week</button>
            <button type="button" class="ch-segbtn" data-period="month">This Month</button>
            <button type="button" class="ch-segbtn" data-period="year">This Year</button>
            <button type="button" class="ch-segbtn is-active" data-period="all">All Time</button>
          </div>
        </div>
      </div>

      <div class="ch-chartwrap">
        <canvas id="macroChart" height="120"></canvas>
      </div>

      <div class="ch-footer">
        <div class="ch-legend">
          <span class="ch-dot ch-dot--legend" data-legend-dot></span>
          <span data-legend-label>Calories (kcal)</span>
        </div>
        <div class="ch-meta" data-legend-meta>—</div>
      </div>

    </section>

    {{-- =========================
    Exercise Progress
    ========================= --}}
        <section class="pl-card ch-card" aria-label="Exercise Progress">
        <div class="ch-head">
            <div class="ch-head__left">
            <div class="ch-icon" aria-hidden="true">🏋️</div>
            <h2 class="ch-title">Exercise Progress</h2>
            </div>
        </div>

        {{-- Reuse same controls grid --}}
        <div class="ch-controls ch-controls--exercise">
            <div class="ch-control">
            <label class="ch-label" for="epExerciseSelect">Select Exercise</label>

            <div class="ch-selectwrap">
                <select id="epExerciseSelect" class="ch-select">
                <option value="">Choose an exercise...</option>
                @foreach($exercises as $ex)
                    <option value="{{ $ex->id }}">{{ $ex->name }}</option>
                @endforeach
                </select>
                <span class="ch-chevron" aria-hidden="true">⌄</span>
            </div>
            </div>

            <div class="ch-control">
            <label class="ch-label">Time Period</label>

            {{-- IMPORTANT: use .ch-seg + .ch-segbtn --}}
            <div class="ch-seg">
                <button type="button" class="ch-segbtn" data-ep-period="week">This Week</button>
                <button type="button" class="ch-segbtn" data-ep-period="month">This Month</button>
                <button type="button" class="ch-segbtn is-active" data-ep-period="all">All Time</button>
            </div>
            </div>
        </div>

        {{-- Show Data (custom) --}}
        <div class="ch-toggles">
            <div class="ch-label">Show Data</div>

            <label class="ch-check is-reps" for="epShowReps">
            <input type="checkbox" id="epShowReps" checked>
            <span class="ch-check__box" aria-hidden="true"></span>
            <span class="ch-check__text">Reps</span>
            </label>

            <label class="ch-check is-weight" for="epShowWeight">
            <input type="checkbox" id="epShowWeight" checked>
            <span class="ch-check__box" aria-hidden="true"></span>
            <span class="ch-check__text">Weight (kg)</span>
            </label>
        </div>

        {{-- IMPORTANT: use .ch-chartwrap so it matches macro styling --}}
        <div class="ch-chartwrap ch-chartwrap--exercise">
            <canvas id="epChart" height="120"></canvas>
        </div>

        <div class="ch-footer">
            <div class="ch-legend ch-legend--multi" id="epLegend">
            <span id="epLegendExercise">—</span>

            <span class="ch-dot ch-dot--legend ch-dot--green"></span>
            <span>Reps</span>

            <span class="ch-dot ch-dot--legend ch-dot--blue"></span>
            <span>Weight (kg)</span>
            </div>

            <div class="ch-meta" id="epDaysText">0 days of data</div>
        </div>
        </section>

  </main>

  <script>
    (function () {
      const macroSelect = document.getElementById('macroSelect');
      const periodBtns = document.querySelectorAll('[data-period]');
      const dot = document.querySelector('[data-macro-dot]');
      const legendDot = document.querySelector('[data-legend-dot]');
      const legendLabel = document.querySelector('[data-legend-label]');
      const legendMeta = document.querySelector('[data-legend-meta]');

      const defaultMacro = @json($defaultMacro);
      const defaultPeriod = @json($defaultPeriod);

      // match UI default (you can change)
      macroSelect.value = defaultMacro;

      // If you prefer default to month, set the correct button active:
      function setActivePeriod(p){
        periodBtns.forEach(b => b.classList.toggle('is-active', b.dataset.period === p));
      }
      // pick default button
      setActivePeriod(defaultPeriod === 'month' ? 'month' : 'all');

      const ctx = document.getElementById('macroChart');

      const chart = new Chart(ctx, {
        type: 'line',
        data: {
          labels: [],
          datasets: [{
            label: '',
            data: [],
            borderWidth: 2,
            pointRadius: 3,
            tension: 0.35
          }]
        },
        options: {
          responsive: true,
          maintainAspectRatio: false,
          plugins: {
            legend: { display: false },
            tooltip: { enabled: true }
          },
          scales: {
            x: {
              ticks: { color: 'rgba(255,255,255,.75)' },
              grid: { color: 'rgba(255,255,255,.08)' }
            },
            y: {
              ticks: { color: 'rgba(255,255,255,.75)' },
              grid: { color: 'rgba(255,255,255,.10)' }
            }
          }
        }
      });

      async function loadData() {
        const macro = macroSelect.value;
        const activeBtn = document.querySelector('.ch-segbtn.is-active');
        const period = activeBtn ? activeBtn.dataset.period : 'month';

        const url = new URL(@json(route('charts.macros')), window.location.origin);
        url.searchParams.set('macro', macro);
        url.searchParams.set('period', period);

        const res = await fetch(url.toString(), { headers: { 'Accept': 'application/json' }});
        const json = await res.json();

        const color = json?.meta?.color || '#ffffff';
        const label = json?.meta?.label || '';
        const points = json?.meta?.points ?? 0;

        chart.data.labels = json.labels || [];
        chart.data.datasets[0].data = json.values || [];
        chart.data.datasets[0].label = label;
        chart.data.datasets[0].borderColor = color;
        chart.data.datasets[0].backgroundColor = color;

        chart.update();

        dot.style.background = color;
        legendDot.style.background = color;
        legendLabel.textContent = label;
        legendMeta.textContent = `${points} days of data`;
      }

      macroSelect.addEventListener('change', loadData);

      periodBtns.forEach(btn => {
        btn.addEventListener('click', () => {
          setActivePeriod(btn.dataset.period);
          loadData();
        });
      });

      // initial load
      loadData();
    })();
  </script>

  <script>
    (function () {
    const select = document.getElementById('epExerciseSelect');
    const repsToggle = document.getElementById('epShowReps');
    const weightToggle = document.getElementById('epShowWeight');
    const legendExercise = document.getElementById('epLegendExercise');
    const daysText = document.getElementById('epDaysText');

    if (!select || !repsToggle || !weightToggle) return;

    const apiUrl = "{{ route('charts.exercise-data') }}";
    let period = 'all';

    const ctx = document.getElementById('epChart').getContext('2d');

    const chart = new Chart(ctx, {
        type: 'line',
        data: {
        labels: [],
        datasets: [
            {
            label: 'Reps',
            data: [],
            tension: 0.35,
            pointRadius: 3,
            borderWidth: 2
            },
            {
            label: 'Weight (kg)',
            data: [],
            tension: 0.35,
            pointRadius: 3,
            borderWidth: 2
            }
        ]
        },
        options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: { legend: { display: false } },
        scales: {
            x: { grid: { display: true } },
            y: { beginAtZero: true, grid: { display: true } }
        }
        }
    });

    // ✅ Color mapping to match design
    // (Chart.js needs actual colors; keeping it minimal)
    chart.data.datasets[0].borderColor = '#22c55e'; // green
    chart.data.datasets[0].backgroundColor = 'transparent';
    chart.data.datasets[0].pointBackgroundColor = '#22c55e';

    chart.data.datasets[1].borderColor = '#3b82f6'; // blue
    chart.data.datasets[1].backgroundColor = 'transparent';
    chart.data.datasets[1].pointBackgroundColor = '#3b82f6';

    async function fetchAndRender() {
        const exerciseId = select.value;
        if (!exerciseId) {
        chart.data.labels = [];
        chart.data.datasets[0].data = [];
        chart.data.datasets[1].data = [];
        chart.update();
        legendExercise.textContent = '—';
        daysText.textContent = '0 days of data';
        return;
        }

        const res = await fetch(`${apiUrl}?exercise_id=${encodeURIComponent(exerciseId)}&period=${encodeURIComponent(period)}`, {
        headers: { 'Accept': 'application/json' }
        });

        const data = await res.json();

        chart.data.labels = data.labels || [];
        chart.data.datasets[0].data = data.reps || [];
        chart.data.datasets[1].data = data.weight || [];

        // Apply toggles
        chart.data.datasets[0].hidden = !repsToggle.checked;
        chart.data.datasets[1].hidden = !weightToggle.checked;

        chart.update();

        // Footer
        legendExercise.textContent = select.options[select.selectedIndex].text;
        daysText.textContent = `${data.days || 0} days of data`;
    }

    // Period buttons
    document.querySelectorAll('[data-ep-period]').forEach(btn => {
        btn.addEventListener('click', () => {
        document.querySelectorAll('[data-ep-period]').forEach(b => b.classList.remove('is-active'));
        btn.classList.add('is-active');
        period = btn.dataset.epPeriod;
        fetchAndRender();
        });
    });

    // Events
    select.addEventListener('change', fetchAndRender);
    repsToggle.addEventListener('change', () => { chart.data.datasets[0].hidden = !repsToggle.checked; chart.update(); });
    weightToggle.addEventListener('change', () => { chart.data.datasets[1].hidden = !weightToggle.checked; chart.update(); });

    })();
    </script>

</body>
</html>