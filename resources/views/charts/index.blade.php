<!doctype html>
<html lang="en">
<head>
  <x-seo
    title="Fitness Progress Charts"
    description="Visualize nutrition trends, workout volume, and exercise strength progress with interactive ProgressLab charts."
    robots="noindex, nofollow, noarchive"
  />
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
            <button type="button" class="ch-segbtn is-active" data-period="month">This Month</button>
            @if($hasFullChartAccess)
              <button type="button" class="ch-segbtn" data-period="year">This Year</button>
              <button type="button" class="ch-segbtn" data-period="all">All Time</button>
            @else
              <button type="button" class="ch-segbtn ch-segbtn--locked" data-macro-locked-period="year">🔒 This Year</button>
              <button type="button" class="ch-segbtn ch-segbtn--locked" data-macro-locked-period="all">🔒 All Time</button>
            @endif
          </div>
        </div>
      </div>

      <div class="ch-chartwrap ch-chartwrap--lockable">
        <canvas id="macroChart" height="120"></canvas>
        @unless($hasFullChartAccess)
          <div class="ch-upgrade-overlay" data-macro-upgrade hidden>
            <span class="ch-upgrade-overlay__icon" aria-hidden="true">✦</span>
            <strong>Upgrade to see the full insights</strong>
            <p><span data-macro-upgrade-period>Year</span> analytics, long-term changes, and complete history are available with ProgressLab+.</p>
            <a class="pl-btn pl-btn--light" href="{{ route('plans.index') }}">Compare plans</a>
          </div>
        @endunless
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
                <button type="button" class="ch-segbtn is-active" data-ep-period="month">This Month</button>
                @if($hasFullChartAccess)
                  <button type="button" class="ch-segbtn" data-ep-period="year">This Year</button>
                  <button type="button" class="ch-segbtn" data-ep-period="all">All Time</button>
                @else
                  <button type="button" class="ch-segbtn ch-segbtn--locked" data-exercise-locked-period="year">🔒 This Year</button>
                  <button type="button" class="ch-segbtn ch-segbtn--locked" data-exercise-locked-period="all">🔒 All Time</button>
                @endif
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
        <div class="ch-chartwrap ch-chartwrap--exercise ch-chartwrap--lockable">
            <canvas id="epChart" height="120"></canvas>
            @unless($hasFullChartAccess)
              <div class="ch-upgrade-overlay" data-exercise-upgrade hidden>
                <span class="ch-upgrade-overlay__icon" aria-hidden="true">🏋️</span>
                <strong>Upgrade to see the full insights</strong>
                <p><span data-exercise-upgrade-period>Year</span> strength history and long-term comparisons are available with ProgressLab+.</p>
                <a class="pl-btn pl-btn--light" href="{{ route('plans.index') }}">Compare plans</a>
              </div>
            @endunless
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

        {{-- Progress Photo Comparison --}}
        <section class="pl-card ch-card pc-card" aria-label="Progress Photo Comparison" data-photo-comparison>
          <div class="ch-head pc-head">
            <div class="ch-head__left">
              <div class="ch-icon" aria-hidden="true">📸</div>
              <div>
                <h2 class="ch-title">Progress Photo Comparison</h2>
                <p class="pc-subtitle">Drag the divider to compare your private check-ins.</p>
              </div>
            </div>
            <span class="pc-private">🔒 Private</span>
          </div>

          @if($progressPhotos->count() < 2)
            <div class="pc-empty">
              <div class="pc-empty__icon" aria-hidden="true">◫</div>
              <h3>{{ $progressPhotos->isEmpty() ? 'No progress photos yet' : 'One more check-in needed' }}</h3>
              <p>Save at least two Front, Side, and Back photo check-ins to compare your progress.</p>
              <a class="pl-btn pl-btn--light" href="{{ route('add-today') }}#progress-photos">Add progress photos</a>
            </div>
          @else
            <div class="pc-controls">
              <div class="pc-control">
                <span class="ch-label">Starting photo</span>
                <div class="pc-start">
                  <span>First check-in</span>
                  <strong data-pc-before-control-date>{{ $progressPhotos->first()['label'] }}</strong>
                </div>
              </div>

              <div class="pc-control pc-control--period">
                <span class="ch-label">Compare with</span>
                <div class="ch-seg pc-periods">
                  <button type="button" class="ch-segbtn is-active" data-pc-period="latest">Latest</button>
                  <button type="button" class="ch-segbtn" data-pc-period="week">Last Week</button>
                  <button type="button" class="ch-segbtn" data-pc-period="month">Last Month</button>
                  <button type="button" class="ch-segbtn" data-pc-period="year">Last Year</button>
                </div>
              </div>

              <div class="pc-control pc-control--date">
                <label class="ch-label" for="pcCustomDate">Or choose a date</label>
                <input
                  class="pc-date"
                  id="pcCustomDate"
                  type="date"
                  min="{{ $progressPhotos->get(1)['date'] }}"
                  max="{{ $progressPhotos->last()['date'] }}"
                  value="{{ $progressPhotos->last()['date'] }}"
                >
              </div>
            </div>

            <div class="pc-tabs" role="tablist" aria-label="Photo angle">
              @foreach(['front' => 'Front', 'side' => 'Side', 'back' => 'Back'] as $angle => $label)
                <button
                  class="pc-tab {{ $loop->first ? 'is-active' : '' }}"
                  type="button"
                  role="tab"
                  aria-selected="{{ $loop->first ? 'true' : 'false' }}"
                  data-pc-angle="{{ $angle }}"
                >{{ $label }}</button>
              @endforeach
            </div>

            <div class="pc-compare" data-pc-stage style="--compare-position: 50%;">
              <img class="pc-image pc-image--after" data-pc-after-image alt="After progress photo">
              <div class="pc-before-layer">
                <img class="pc-image pc-image--before" data-pc-before-image alt="Before progress photo">
              </div>

              <span class="pc-image-label pc-image-label--before">Before</span>
              <span class="pc-image-label pc-image-label--after">After</span>

              <div class="pc-divider" aria-hidden="true">
                <span class="pc-divider__handle">↔</span>
              </div>
              <input
                class="pc-range"
                type="range"
                min="0"
                max="100"
                value="50"
                aria-label="Adjust before and after comparison"
                data-pc-range
              >
            </div>

            <div class="ch-footer pc-footer">
              <div>
                <span class="pc-footer__label">Before</span>
                <strong data-pc-before-date>{{ $progressPhotos->first()['label'] }}</strong>
              </div>
              <p data-pc-date-note>Comparing with your latest check-in.</p>
              <div class="pc-footer__after">
                <span class="pc-footer__label">After</span>
                <strong data-pc-after-date>{{ $progressPhotos->last()['label'] }}</strong>
              </div>
            </div>
          @endif
        </section>

        <section class="pl-card ch-card wr-card" aria-labelledby="weekly-report-title">
          <div class="wr-head">
            <div class="ch-head__left">
              <div class="ch-icon" aria-hidden="true">&#128196;</div>
              <div>
                <span class="wr-eyebrow">Monday - Sunday summary</span>
                <h2 class="ch-title" id="weekly-report-title">Weekly Report</h2>
                <p class="wr-subtitle">Keep a permanent snapshot of your nutrition, training, weight, and body measurements.</p>
              </div>
            </div>
            @if($hasFullChartAccess)
              <span class="wr-period">{{ $weeklyReport['period']['label'] }}</span>
            @else
              <span class="wr-lock">&#128274; ProgressLab+</span>
            @endif
          </div>

          @if($hasFullChartAccess)
            <div class="wr-stats">
              <article>
                <span>Nutrition logged</span>
                <strong>{{ $weeklyReport['nutrition_days_logged'] }}/7 days</strong>
              </article>
              <article>
                <span>Workouts</span>
                <strong>{{ $weeklyReport['training']['workouts'] }}</strong>
              </article>
              <article>
                <span>Sets completed</span>
                <strong>{{ number_format($weeklyReport['training']['sets']) }}</strong>
              </article>
              <article>
                <span>Training volume</span>
                <strong>{{ number_format($weeklyReport['training']['volume_kg'], 0) }} kg</strong>
              </article>
            </div>

            <div class="wr-details">
              <div>
                <span>Current weight</span>
                <strong>
                  {{ $weeklyReport['weight']['current'] === null ? 'No entry' : number_format($weeklyReport['weight']['current'], 1).' kg' }}
                </strong>
              </div>
              <div>
                <span>Body check-ins</span>
                <strong>{{ $weeklyReport['body_checkins'] }}</strong>
              </div>
              <div>
                <span>Nutrition targets</span>
                <strong>{{ $weeklyReport['nutrition']->first()['target'] ? 'Included' : 'Not configured' }}</strong>
              </div>
            </div>

            <div class="wr-actions">
              <p>The PDF includes macro totals and averages, targets, every logged workout, sets, repetitions, volume, weight change, and your latest measurements. Progress photos are never included.</p>
              <a class="wr-download" href="{{ route('charts.weekly-report.download') }}">
                <span aria-hidden="true">&#8595;</span>
                Download weekly PDF
              </a>
            </div>
          @else
            <div class="wr-locked">
              <div class="wr-locked__icon" aria-hidden="true">&#128274;</div>
              <div>
                <h3>Save your full weekly progress</h3>
                <p>Weekly summaries and downloadable PDF reports are available with ProgressLab+ and Trainer plans.</p>
              </div>
              <a class="pl-btn pl-btn--light" href="{{ route('plans.index') }}">View plans</a>
            </div>
          @endif
        </section>

        <section class="pl-card ch-card ac-card" aria-labelledby="activity-calendar-title">
          <div class="ac-head">
            <div class="ch-head__left">
              <div class="ch-icon" aria-hidden="true">&#9638;</div>
              <div>
                <span class="wr-eyebrow">{{ $activityCalendar['year'] }} consistency</span>
                <h2 class="ch-title" id="activity-calendar-title">Activity Calendar</h2>
                <p class="wr-subtitle">Your workout and nutrition consistency at a glance.</p>
              </div>
            </div>
            <div class="ac-summary">
              <strong>{{ $activityCalendar['active_days'] }}</strong>
              <span>active days</span>
              <i></i>
              <strong>{{ $activityCalendar['complete_days'] }}</strong>
              <span>complete days</span>
            </div>
          </div>

          <div class="ac-scroll" tabindex="0" aria-label="Scrollable yearly activity calendar">
            <div class="ac-calendar">
              <div class="ac-month-spacer" aria-hidden="true"></div>
              <div class="ac-months" style="--ac-weeks: {{ $activityCalendar['week_count'] }}" aria-hidden="true">
                @foreach($activityCalendar['months'] as $month)
                  <span>{{ $month }}</span>
                @endforeach
              </div>

              <div class="ac-weekdays" aria-hidden="true">
                <span></span><span>Mon</span><span></span><span>Wed</span><span></span><span>Fri</span><span></span>
              </div>
              <div class="ac-days" style="--ac-weeks: {{ $activityCalendar['week_count'] }}">
                @foreach($activityCalendar['days'] as $day)
                  <span
                    class="ac-day {{ $day['future'] ? 'is-future' : '' }} {{ $day['outside_year'] ? 'is-outside-year' : '' }}"
                    data-level="{{ $day['level'] }}"
                    data-date="{{ $day['date'] }}"
                    @if(!$day['outside_year']) title="{{ $day['label'] }} — {{ $day['activity'] }}" aria-label="{{ $day['label'] }}: {{ $day['activity'] }}" @endif
                  ></span>
                @endforeach
              </div>
            </div>
          </div>

          <div class="ac-footer">
            <span>{{ \Illuminate\Support\Carbon::parse($activityCalendar['start'])->format('M j, Y') }} – {{ \Illuminate\Support\Carbon::parse($activityCalendar['end'])->format('M j, Y') }}</span>
            <div class="ac-legend" aria-label="Activity calendar legend">
              <span>No activity</span><i data-level="0"></i>
              <i data-level="1"></i><span>Workout or nutrition</span>
              <i data-level="2"></i><span>Both completed</span>
              <i class="is-future"></i><span>Upcoming</span>
            </div>
          </div>
        </section>

  </main>

  <script>
    (function () {
      const macroSelect = document.getElementById('macroSelect');
      const periodBtns = document.querySelectorAll('[data-period]');
      const lockedPeriodBtns = document.querySelectorAll('[data-macro-locked-period]');
      const upgradeOverlay = document.querySelector('[data-macro-upgrade]');
      const upgradePeriod = document.querySelector('[data-macro-upgrade-period]');
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
        const activeBtn = macroSelect.closest('.ch-card')?.querySelector('[data-period].is-active');
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
          lockedPeriodBtns.forEach(item => item.classList.remove('is-active'));
          if (upgradeOverlay) upgradeOverlay.hidden = true;
          setActivePeriod(btn.dataset.period);
          loadData();
        });
      });

      lockedPeriodBtns.forEach(btn => {
        btn.addEventListener('click', () => {
          periodBtns.forEach(item => item.classList.remove('is-active'));
          lockedPeriodBtns.forEach(item => item.classList.toggle('is-active', item === btn));
          if (upgradePeriod) {
            upgradePeriod.textContent = btn.dataset.macroLockedPeriod === 'all' ? 'All-time' : 'Year';
          }
          if (upgradeOverlay) upgradeOverlay.hidden = false;
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
    const lockedExerciseBtns = document.querySelectorAll('[data-exercise-locked-period]');
    const exerciseUpgrade = document.querySelector('[data-exercise-upgrade]');
    const exerciseUpgradePeriod = document.querySelector('[data-exercise-upgrade-period]');

    if (!select || !repsToggle || !weightToggle) return;

    const apiUrl = "{{ route('charts.exercise-data') }}";
    let period = 'month';

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
        lockedExerciseBtns.forEach(item => item.classList.remove('is-active'));
        if (exerciseUpgrade) exerciseUpgrade.hidden = true;
        document.querySelectorAll('[data-ep-period]').forEach(b => b.classList.remove('is-active'));
        btn.classList.add('is-active');
        period = btn.dataset.epPeriod;
        fetchAndRender();
        });
    });

    lockedExerciseBtns.forEach(btn => {
        btn.addEventListener('click', () => {
        document.querySelectorAll('[data-ep-period]').forEach(item => item.classList.remove('is-active'));
        lockedExerciseBtns.forEach(item => item.classList.toggle('is-active', item === btn));
        if (exerciseUpgradePeriod) {
            exerciseUpgradePeriod.textContent = btn.dataset.exerciseLockedPeriod === 'all' ? 'All-time' : 'Year';
        }
        if (exerciseUpgrade) exerciseUpgrade.hidden = false;
        });
    });

    // Events
    select.addEventListener('change', fetchAndRender);
    repsToggle.addEventListener('change', () => { chart.data.datasets[0].hidden = !repsToggle.checked; chart.update(); });
    weightToggle.addEventListener('change', () => { chart.data.datasets[1].hidden = !weightToggle.checked; chart.update(); });

    })();
    </script>

    @if($progressPhotos->count() >= 2)
    <script>
    (() => {
      const photos = @json($progressPhotos);
      const card = document.querySelector('[data-photo-comparison]');
      if (!card || photos.length < 2) return;

      const beforePhoto = photos[0];
      const comparisonPhotos = photos.slice(1);
      const stage = card.querySelector('[data-pc-stage]');
      const beforeImage = card.querySelector('[data-pc-before-image]');
      const afterImage = card.querySelector('[data-pc-after-image]');
      const beforeDate = card.querySelector('[data-pc-before-date]');
      const afterDate = card.querySelector('[data-pc-after-date]');
      const dateNote = card.querySelector('[data-pc-date-note]');
      const dateInput = card.querySelector('#pcCustomDate');
      const range = card.querySelector('[data-pc-range]');
      const periodButtons = [...card.querySelectorAll('[data-pc-period]')];
      const angleButtons = [...card.querySelectorAll('[data-pc-angle]')];
      let activeAngle = 'front';
      let afterPhoto = comparisonPhotos[comparisonPhotos.length - 1];

      function asDate(value) {
        return new Date(`${value}T12:00:00`);
      }

      function photoOnOrBefore(target) {
        const eligible = comparisonPhotos.filter(photo => asDate(photo.date) <= target);
        return eligible.length ? eligible[eligible.length - 1] : comparisonPhotos[0];
      }

      function renderImages() {
        beforeImage.src = beforePhoto.urls[activeAngle];
        afterImage.src = afterPhoto.urls[activeAngle];
        beforeImage.alt = `Before ${activeAngle} progress photo from ${beforePhoto.label}`;
        afterImage.alt = `After ${activeAngle} progress photo from ${afterPhoto.label}`;
        beforeDate.textContent = beforePhoto.label;
        afterDate.textContent = afterPhoto.label;
        dateInput.value = afterPhoto.date;
      }

      function selectPeriod(period) {
        periodButtons.forEach(button => {
          const active = button.dataset.pcPeriod === period;
          button.classList.toggle('is-active', active);
          button.setAttribute('aria-pressed', active ? 'true' : 'false');
        });

        if (period === 'latest') {
          afterPhoto = comparisonPhotos[comparisonPhotos.length - 1];
          dateNote.textContent = 'Comparing with your latest check-in.';
          renderImages();
          return;
        }

        const target = new Date();
        if (period === 'week') target.setDate(target.getDate() - 7);
        if (period === 'month') target.setMonth(target.getMonth() - 1);
        if (period === 'year') target.setFullYear(target.getFullYear() - 1);

        afterPhoto = photoOnOrBefore(target);
        const periodLabels = { week: 'one week ago', month: 'one month ago', year: 'one year ago' };
        dateNote.textContent = asDate(afterPhoto.date) <= target
          ? `Closest saved check-in on or before ${periodLabels[period]}.`
          : `No check-in existed by ${periodLabels[period]}, so your earliest comparison is shown.`;
        renderImages();
      }

      periodButtons.forEach(button => {
        button.addEventListener('click', () => selectPeriod(button.dataset.pcPeriod));
      });

      dateInput.addEventListener('change', () => {
        if (!dateInput.value) return;
        periodButtons.forEach(button => {
          button.classList.remove('is-active');
          button.setAttribute('aria-pressed', 'false');
        });
        afterPhoto = photoOnOrBefore(asDate(dateInput.value));
        dateNote.textContent = afterPhoto.date === dateInput.value
          ? 'Comparing with the check-in saved on your selected date.'
          : 'No check-in exists on that exact date, so the closest earlier check-in is shown.';
        renderImages();
      });

      angleButtons.forEach(button => {
        button.addEventListener('click', () => {
          activeAngle = button.dataset.pcAngle;
          angleButtons.forEach(candidate => {
            const active = candidate === button;
            candidate.classList.toggle('is-active', active);
            candidate.setAttribute('aria-selected', active ? 'true' : 'false');
          });
          renderImages();
        });
      });

      range.addEventListener('input', () => {
        stage.style.setProperty('--compare-position', `${range.value}%`);
      });

      renderImages();
    })();
    </script>
    @endif

<x-achievement-toasts />
</body>
</html>
