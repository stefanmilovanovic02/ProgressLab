<!doctype html>
<html lang="en">
<head>
  <x-seo title="User Statistics" description="ProgressLab user statistics." robots="noindex, nofollow, noarchive" />
  <link rel="stylesheet" href="{{ asset('css/auth.css') }}?v={{ filemtime(public_path('css/auth.css')) }}">
  <link rel="stylesheet" href="{{ asset('css/admin.css') }}?v={{ filemtime(public_path('css/admin.css')) }}">
  <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
</head>
<body class="auth-body">
  <x-navbar />
  @php $weightUnit = $user->weightUnit(); @endphp
  <main class="pl-container ad-wrap">
    <header class="ad-head">
      <div class="ad-profile-title">
        <img src="{{ $user->avatar_url }}" alt="" width="64" height="64">
        <div><span class="ad-eyebrow">User statistics</span><h1>{{ $user->full_name ?? $user->name }}</h1><p>{{ $user->email }} · {{ '@' . ($user->username ?? '—') }}</p></div>
      </div>
      <div class="ad-actions">
        <span class="ad-role-pill ad-role-pill--{{ $user->role->value }}">{{ $user->role->label() }}</span>
        @if(!$user->isOwner() && (auth()->user()->isOwner() || !$user->isAdmin()))
          <a class="ad-button ad-button--secondary" href="{{ route('admin.users.edit', $user) }}">Edit account</a>
        @endif
      </div>
    </header>
    @include('admin.partials.navigation')

    @unless(auth()->user()->isOwner())
      <div class="ad-privacy-banner">
        <strong>Privacy protected</strong>
        Progress photos are never loaded or displayed for administrators. Streaks below are calculated from activity and are view-only.
      </div>
    @endunless

    <section class="ad-stat-grid ad-stat-grid--user">
      @foreach([
        ['Rank', $stats['summary']['rank']['rank'] . ' ' . $stats['summary']['rank']['level'], '🏅'],
        ['Total XP', number_format($stats['summary']['total_xp']), '◆'],
        ['Workouts', number_format($stats['summary']['workouts']), '🏋️'],
        ['Nutrition days', number_format($stats['summary']['nutrition_days']), '🥗'],
        ['Achievements', number_format($stats['summary']['achievements']), '🏆'],
        ['Friends', number_format($stats['summary']['friends']), '👥'],
      ] as [$label, $value, $icon])
        <article class="ad-stat"><span class="ad-stat__icon">{{ $icon }}</span><strong>{{ $value }}</strong><span>{{ $label }}</span></article>
      @endforeach
    </section>

    <section class="ad-card">
      <div class="ad-card__head"><div><span class="ad-eyebrow">View only</span><h2>Current streaks</h2></div><span class="ad-lock">🔒 Not editable</span></div>
      <div class="ad-streak-grid">
        <div><strong>{{ $stats['streaks']['login'] }}</strong><span>Login streak</span></div>
        <div><strong>{{ $stats['streaks']['workout'] }}</strong><span>Workout streak</span></div>
        <div><strong>{{ $stats['streaks']['nutrition'] }}</strong><span>Nutrition streak</span></div>
      </div>
    </section>

    <div data-staff-charts>
      <section class="pl-card ch-card ad-chart-card" aria-label="User macronutrient progress">
        <div class="ch-head"><div class="ch-head__left"><div class="ch-icon">💧</div><div><span class="ad-eyebrow">User chart</span><h2 class="ch-title">Macronutrient Progress</h2></div></div></div>
        <div class="ch-controls">
          <div class="ch-control">
            <span class="ch-label" id="adminMacroLabel">Select Macronutrient</span>
            <div class="ad-chart-picker" data-admin-macro data-value="calories">
              <button class="ad-chart-picker__trigger" type="button" aria-haspopup="listbox" aria-expanded="false" aria-labelledby="adminMacroLabel adminMacroValue" data-admin-picker-trigger>
                <span class="ch-dot" data-admin-macro-dot></span>
                <span id="adminMacroValue" data-admin-picker-label>Calories</span>
                <span class="ad-chart-picker__arrow" aria-hidden="true"></span>
              </button>
              <div class="ad-chart-picker__menu" role="listbox" aria-labelledby="adminMacroLabel" data-admin-picker-menu hidden>
                @foreach(['calories' => 'Calories', 'protein' => 'Protein', 'carbs' => 'Carbohydrates', 'fat' => 'Fat', 'creatine' => 'Creatine', 'water' => 'Water'] as $value => $label)
                  <button type="button" role="option" aria-selected="{{ $loop->first ? 'true' : 'false' }}" data-admin-picker-option="{{ $value }}">{{ $label }}</button>
                @endforeach
              </div>
            </div>
          </div>
          <div class="ch-control"><label class="ch-label">Time Period</label><div class="ch-seg"><button type="button" class="ch-segbtn" data-admin-macro-period="week">This Week</button><button type="button" class="ch-segbtn is-active" data-admin-macro-period="month">This Month</button><button type="button" class="ch-segbtn" data-admin-macro-period="year">This Year</button><button type="button" class="ch-segbtn" data-admin-macro-period="all">All Time</button></div></div>
        </div>
        <div class="ch-chartwrap"><canvas id="adminMacroChart" height="120"></canvas></div>
        <div class="ad-insight-grid"><article><span>Latest</span><strong data-macro-insight="latest">—</strong></article><article><span>Average</span><strong data-macro-insight="average">—</strong></article><article><span>Highest</span><strong data-macro-insight="highest">—</strong></article><article><span>Period change</span><strong data-macro-insight="change_percent">—</strong></article></div>
        <div class="ch-footer"><div class="ch-legend"><span class="ch-dot ch-dot--legend" data-admin-macro-legend-dot></span><span data-admin-macro-label>Calories (kcal)</span></div><div class="ch-meta" data-admin-macro-meta>—</div></div>
      </section>

      <section class="pl-card ch-card ad-chart-card" aria-label="User exercise progress">
        <div class="ch-head"><div class="ch-head__left"><div class="ch-icon">🏋️</div><div><span class="ad-eyebrow">User chart</span><h2 class="ch-title">Exercise Progress</h2></div></div></div>
        <div class="ch-controls ch-controls--exercise">
          <div class="ch-control">
            <label class="ch-label" for="adminExerciseSearch">Select Exercise</label>
            <div class="ch-exercise-search" data-admin-exercise-picker>
              <div class="ch-exercise-search__field">
                <input id="adminExerciseSearch" type="search" inputmode="search" autocomplete="off" placeholder="Search this user's exercises..." role="combobox" aria-autocomplete="list" aria-expanded="false" aria-controls="adminExerciseOptions" data-admin-exercise-search>
                <button type="button" data-admin-exercise-clear aria-label="Clear selected exercise" hidden>&times;</button>
              </div>
              <input id="adminExerciseSelect" type="hidden" value="" data-admin-exercise>
              <div id="adminExerciseOptions" class="ch-exercise-search__options" role="listbox" data-admin-exercise-options hidden></div>
            </div>
          </div>
          <div class="ch-control"><label class="ch-label">Time Period</label><div class="ch-seg"><button type="button" class="ch-segbtn" data-admin-exercise-period="week">This Week</button><button type="button" class="ch-segbtn" data-admin-exercise-period="month">This Month</button><button type="button" class="ch-segbtn" data-admin-exercise-period="year">This Year</button><button type="button" class="ch-segbtn is-active" data-admin-exercise-period="all">All Time</button></div></div>
        </div>
        <div class="ch-toggles"><div class="ch-label">Show Data</div><label class="ch-check is-reps"><input type="checkbox" data-admin-show-reps checked><span class="ch-check__box"></span><span class="ch-check__text">Reps</span></label><label class="ch-check is-weight"><input type="checkbox" data-admin-show-weight checked><span class="ch-check__box"></span><span class="ch-check__text">Weight ({{ $weightUnit }})</span></label></div>
        <div class="ch-chartwrap ch-chartwrap--exercise"><canvas id="adminExerciseChart" height="120"></canvas></div>
        <div class="ad-insight-grid"><article><span>Latest weight</span><strong data-exercise-insight="latest-weight">—</strong></article><article><span>Best weight</span><strong data-exercise-insight="highest-weight">—</strong></article><article><span>Latest reps</span><strong data-exercise-insight="latest-reps">—</strong></article><article><span>Weight change</span><strong data-exercise-insight="change-weight">—</strong></article></div>
        <div class="ch-footer"><div class="ch-legend ch-legend--multi"><span data-admin-exercise-name>—</span><span class="ch-dot ch-dot--legend ch-dot--green"></span><span>Reps</span><span class="ch-dot ch-dot--legend ch-dot--blue"></span><span>Weight ({{ $weightUnit }})</span></div><div class="ch-meta" data-admin-exercise-days>0 days of data</div></div>
      </section>
    </div>

    <div class="ad-dashboard-grid">
      <section class="ad-card">
        <div class="ad-card__head"><h2>Recent nutrition</h2></div>
        <div class="ad-table-wrap"><table class="ad-table"><thead><tr><th>Date</th><th>Calories</th><th>Protein</th><th>Carbs</th><th>Fat</th></tr></thead><tbody>
          @forelse($stats['recentNutrition'] as $entry)<tr><td>{{ \Illuminate\Support\Carbon::parse($entry->entry_date)->format('M j, Y') }}</td><td>{{ $entry->calories }}</td><td>{{ $entry->protein_g }}g</td><td>{{ $entry->carbs_g }}g</td><td>{{ $entry->fat_g }}g</td></tr>@empty<tr><td colspan="5" class="ad-empty">No nutrition records.</td></tr>@endforelse
        </tbody></table></div>
      </section>
      <section class="ad-card">
        <div class="ad-card__head"><h2>Recent workouts</h2></div>
        <div class="ad-table-wrap"><table class="ad-table"><thead><tr><th>Date</th><th>Workout</th><th>Status</th></tr></thead><tbody>
          @forelse($stats['recentWorkouts'] as $workout)<tr><td>{{ \Illuminate\Support\Carbon::parse($workout->entry_date)->format('M j, Y') }}</td><td>{{ $workout->name ?? 'Workout' }}</td><td>{{ $workout->completed_at ? 'Completed' : 'In progress' }}</td></tr>@empty<tr><td colspan="3" class="ad-empty">No workout records.</td></tr>@endforelse
        </tbody></table></div>
      </section>
    </div>

    @if($ownerData)
      <section class="ad-card">
        <div class="ad-card__head">
          <div><span class="ad-eyebrow">Owner only</span><h2>Subscriptions</h2></div>
          <a class="ad-button ad-button--secondary" href="{{ route('admin.subscriptions.create', ['user_id' => $user->id]) }}">Add subscription</a>
        </div>
        @if($ownerData['direct_access'])
          <div class="ad-direct-access">
            <div>
              <span>Current access</span>
              <strong>{{ $user->isTrainer() ? 'Trainer' : 'ProgressLab+' }}</strong>
            </div>
            <div>
              <span>Status</span>
              <strong>Complimentary direct grant</strong>
            </div>
            <p>This access was granted through the account role, so it has no payment or subscription record and does not count as revenue.</p>
          </div>
        @endif
        <div class="ad-table-wrap"><table class="ad-table"><thead><tr><th>Plan</th><th>Status</th><th>Paid</th><th>Starts</th><th>Ends</th><th></th></tr></thead><tbody>
          @forelse($ownerData['subscriptions'] as $subscription)
            <tr><td>{{ ucfirst($subscription->plan) }}<small>{{ $subscription->is_complimentary ? 'Complimentary' : 'Billable' }}</small></td><td><span class="ad-status ad-status--{{ $subscription->status }}">{{ ucfirst($subscription->status) }}</span></td><td>{{ $subscription->is_complimentary ? 'Free' : '€' . number_format((float) $subscription->amount_paid, 2) }}</td><td>{{ $subscription->starts_on->format('M j, Y') }}</td><td>{{ $subscription->ends_on?->format('M j, Y') ?? '—' }}</td><td><a class="ad-table-link" href="{{ route('admin.subscriptions.edit', $subscription) }}">Edit</a></td></tr>
          @empty<tr><td colspan="6" class="ad-empty">{{ $ownerData['direct_access'] ? 'No billing record—access is granted directly.' : 'No subscriptions recorded for this user.' }}</td></tr>@endforelse
        </tbody></table></div>
      </section>

      <section class="ad-card">
        <div class="ad-card__head"><div><span class="ad-eyebrow">Owner only · private</span><h2>Progress photos</h2></div><span class="ad-lock">🔒 Protected files</span></div>
        <p class="ad-section-copy">These images are served from private storage and cannot be opened by Admin, Trainer, Paid, or User accounts.</p>
        <div class="ad-photo-history">
          @forelse($ownerData['photos'] as $photoSet)
            <article class="ad-photo-set">
              <div class="ad-photo-set__date">{{ $photoSet->captured_on->format('M j, Y') }}</div>
              <div class="ad-photo-set__grid">
                @foreach(['front', 'side', 'back'] as $view)
                  <a href="{{ route('admin.progress-photos.show', [$photoSet, $view]) }}" target="_blank" rel="noopener">
                    <img src="{{ route('admin.progress-photos.show', [$photoSet, $view]) }}" alt="{{ ucfirst($view) }} progress photo from {{ $photoSet->captured_on->format('M j, Y') }}" loading="lazy">
                    <span>{{ ucfirst($view) }}</span>
                  </a>
                @endforeach
              </div>
            </article>
          @empty
            <div class="ad-empty">This user has not uploaded any progress photos.</div>
          @endforelse
        </div>
      </section>
    @endif

    @if(!$user->isOwner() && !auth()->user()->is($user) && (auth()->user()->isOwner() || !$user->isAdmin()))
      <section class="ad-card ad-danger-zone">
        <div><h2>Delete account</h2><p>Permanently deletes this account and its tracking data. This cannot be undone.</p></div>
        <form method="POST" action="{{ route('admin.users.destroy', $user) }}" onsubmit="return confirm('Delete this user and all associated tracking data?')">@csrf @method('DELETE')<button class="ad-button ad-button--danger" type="submit">Delete user</button></form>
      </section>
    @endif
  </main>
  <x-footer />
  <script>
    (() => {
      const root = document.querySelector('[data-staff-charts]');
      if (!root || typeof Chart === 'undefined') return;
      const ticks = 'rgba(255,255,255,.72)';
      const grid = 'rgba(255,255,255,.09)';
      const options = { responsive: true, maintainAspectRatio: false, plugins: { legend: { display: false } }, scales: { x: { ticks: { color: ticks }, grid: { color: grid } }, y: { beginAtZero: true, ticks: { color: ticks }, grid: { color: grid } } } };
      const number = value => value == null ? '—' : Number(value).toLocaleString(undefined, { maximumFractionDigits: 1 });
      const change = value => value == null ? '—' : `${value > 0 ? '+' : ''}${number(value)}%`;

      const macroSelect = root.querySelector('[data-admin-macro]');
      const macroTrigger = macroSelect.querySelector('[data-admin-picker-trigger]');
      const macroMenu = macroSelect.querySelector('[data-admin-picker-menu]');
      const macroValueLabel = macroSelect.querySelector('[data-admin-picker-label]');
      const macroOptions = [...macroSelect.querySelectorAll('[data-admin-picker-option]')];
      const macroPeriods = [...root.querySelectorAll('[data-admin-macro-period]')];
      const macroChart = new Chart(document.getElementById('adminMacroChart'), { type: 'line', data: { labels: [], datasets: [{ data: [], borderWidth: 2, pointRadius: 3, tension: .35 }] }, options });
      let macroPeriod = 'month';

      async function loadMacro() {
        const url = new URL(@json(route('admin.users.charts.macros', $user)), window.location.origin);
        url.searchParams.set('macro', macroSelect.dataset.value);
        url.searchParams.set('period', macroPeriod);
        const response = await fetch(url, { headers: { Accept: 'application/json' } });
        if (!response.ok) return;
        const data = await response.json();
        const color = data.meta?.color || '#fff';
        macroChart.data.labels = data.labels || [];
        macroChart.data.datasets[0].data = data.values || [];
        macroChart.data.datasets[0].borderColor = color;
        macroChart.data.datasets[0].backgroundColor = color;
        macroChart.update();
        root.querySelector('[data-admin-macro-dot]').style.background = color;
        root.querySelector('[data-admin-macro-legend-dot]').style.background = color;
        root.querySelector('[data-admin-macro-label]').textContent = data.meta?.label || '';
        root.querySelector('[data-admin-macro-meta]').textContent = `${data.meta?.points || 0} days of data`;
        ['latest', 'average', 'highest'].forEach(key => root.querySelector(`[data-macro-insight="${key}"]`).textContent = number(data.insights?.[key]));
        root.querySelector('[data-macro-insight="change_percent"]').textContent = change(data.insights?.change_percent);
      }

      function setMacroOpen(open, focusSelected = false) {
        macroSelect.classList.toggle('is-open', open);
        macroTrigger.setAttribute('aria-expanded', String(open));
        macroMenu.hidden = !open;
        if (open && focusSelected) {
          (macroOptions.find(option => option.getAttribute('aria-selected') === 'true') || macroOptions[0]).focus();
        }
      }

      function chooseMacro(option) {
        macroSelect.dataset.value = option.dataset.adminPickerOption;
        macroValueLabel.textContent = option.textContent.trim();
        macroOptions.forEach(candidate => candidate.setAttribute('aria-selected', String(candidate === option)));
        setMacroOpen(false);
        macroTrigger.focus();
        loadMacro();
      }

      macroTrigger.addEventListener('click', () => setMacroOpen(macroMenu.hidden, false));
      macroTrigger.addEventListener('keydown', event => {
        if (event.key === 'ArrowDown' || event.key === 'ArrowUp') {
          event.preventDefault();
          setMacroOpen(true, true);
        }
      });
      macroOptions.forEach((option, index) => {
        option.addEventListener('click', () => chooseMacro(option));
        option.addEventListener('keydown', event => {
          if (event.key === 'Escape') {
            event.preventDefault();
            setMacroOpen(false);
            macroTrigger.focus();
            return;
          }
          if (!['ArrowDown', 'ArrowUp', 'Home', 'End'].includes(event.key)) return;
          event.preventDefault();
          const next = event.key === 'Home' ? 0 : event.key === 'End'
            ? macroOptions.length - 1
            : (index + (event.key === 'ArrowDown' ? 1 : -1) + macroOptions.length) % macroOptions.length;
          macroOptions[next].focus();
        });
      });
      macroPeriods.forEach(button => button.addEventListener('click', () => {
        macroPeriods.forEach(item => item.classList.remove('is-active'));
        button.classList.add('is-active');
        macroPeriod = button.dataset.adminMacroPeriod;
        loadMacro();
      }));

      const exerciseSelect = root.querySelector('[data-admin-exercise]');
      const exercisePicker = root.querySelector('[data-admin-exercise-picker]');
      const exerciseSearch = root.querySelector('[data-admin-exercise-search]');
      const exerciseOptions = root.querySelector('[data-admin-exercise-options]');
      const exerciseClear = root.querySelector('[data-admin-exercise-clear]');
      const exercises = @json($chartExercises->map(fn ($exercise) => ['id' => (string) $exercise->id, 'name' => $exercise->name])->values());
      const exercisePeriods = [...root.querySelectorAll('[data-admin-exercise-period]')];
      const repsToggle = root.querySelector('[data-admin-show-reps]');
      const weightToggle = root.querySelector('[data-admin-show-weight]');
      const exerciseChart = new Chart(document.getElementById('adminExerciseChart'), {
        type: 'line',
        data: { labels: [], datasets: [
          { label: 'Reps', data: [], borderColor: '#22c55e', pointBackgroundColor: '#22c55e', backgroundColor: 'transparent', borderWidth: 2, pointRadius: 3, tension: .35 },
          { label: 'Weight ({{ $weightUnit }})', data: [], borderColor: '#3b82f6', pointBackgroundColor: '#3b82f6', backgroundColor: 'transparent', borderWidth: 2, pointRadius: 3, tension: .35 }
        ] },
        options
      });
      let exercisePeriod = 'all';
      let selectedExerciseName = '';

      function syncExerciseToggles() {
        exerciseChart.data.datasets[0].hidden = !repsToggle.checked;
        exerciseChart.data.datasets[1].hidden = !weightToggle.checked;
        exerciseChart.update();
      }

      async function loadExercise() {
        if (!exerciseSelect.value) {
          exerciseChart.data.labels = [];
          exerciseChart.data.datasets.forEach(dataset => dataset.data = []);
          exerciseChart.update();
          root.querySelector('[data-admin-exercise-name]').textContent = '—';
          root.querySelector('[data-admin-exercise-days]').textContent = '0 days of data';
          root.querySelectorAll('[data-exercise-insight]').forEach(item => item.textContent = '—');
          return;
        }
        const url = new URL(@json(route('admin.users.charts.exercise-data', $user)), window.location.origin);
        url.searchParams.set('exercise_id', exerciseSelect.value);
        url.searchParams.set('period', exercisePeriod);
        const response = await fetch(url, { headers: { Accept: 'application/json' } });
        if (!response.ok) return;
        const data = await response.json();
        exerciseChart.data.labels = data.labels || [];
        exerciseChart.data.datasets[0].data = data.reps || [];
        exerciseChart.data.datasets[1].data = data.weight || [];
        syncExerciseToggles();
        root.querySelector('[data-admin-exercise-name]').textContent = selectedExerciseName || '—';
        root.querySelector('[data-admin-exercise-days]').textContent = `${data.days || 0} days of data`;
        root.querySelector('[data-exercise-insight="latest-weight"]').textContent = data.insights?.weight?.latest == null ? '—' : `${number(data.insights.weight.latest)} ${data.weight_unit || @json($weightUnit)}`;
        root.querySelector('[data-exercise-insight="highest-weight"]').textContent = data.insights?.weight?.highest == null ? '—' : `${number(data.insights.weight.highest)} ${data.weight_unit || @json($weightUnit)}`;
        root.querySelector('[data-exercise-insight="latest-reps"]').textContent = number(data.insights?.reps?.latest);
        root.querySelector('[data-exercise-insight="change-weight"]').textContent = change(data.insights?.weight?.change_percent);
      }

      function closeExerciseResults() {
        exerciseOptions.hidden = true;
        exerciseSearch.setAttribute('aria-expanded', 'false');
      }

      function chooseExercise(exercise) {
        exerciseSelect.value = exercise.id;
        selectedExerciseName = exercise.name;
        exerciseSearch.value = exercise.name;
        exerciseClear.hidden = false;
        closeExerciseResults();
        loadExercise();
      }

      function renderExerciseResults() {
        const query = exerciseSearch.value.trim().toLocaleLowerCase();
        exerciseOptions.replaceChildren();

        if (!query || (exerciseSelect.value && exerciseSearch.value === selectedExerciseName)) {
          closeExerciseResults();
          return;
        }

        const matches = exercises
          .filter(exercise => exercise.name.toLocaleLowerCase().includes(query))
          .sort((left, right) => {
            const leftStarts = left.name.toLocaleLowerCase().startsWith(query);
            const rightStarts = right.name.toLocaleLowerCase().startsWith(query);
            return Number(rightStarts) - Number(leftStarts) || left.name.localeCompare(right.name);
          })
          .slice(0, 10);

        if (!matches.length) {
          const empty = document.createElement('span');
          empty.className = 'ch-exercise-search__empty';
          empty.textContent = 'No matching exercises in this user’s history.';
          exerciseOptions.appendChild(empty);
        } else {
          matches.forEach(exercise => {
            const option = document.createElement('button');
            option.type = 'button';
            option.className = 'ch-exercise-search__option';
            option.role = 'option';
            option.textContent = exercise.name;
            option.addEventListener('click', () => chooseExercise(exercise));
            exerciseOptions.appendChild(option);
          });
        }

        exerciseOptions.hidden = false;
        exerciseSearch.setAttribute('aria-expanded', 'true');
      }

      exerciseSearch.addEventListener('input', () => {
        if (exerciseSearch.value !== selectedExerciseName) {
          exerciseSelect.value = '';
          selectedExerciseName = '';
          exerciseClear.hidden = !exerciseSearch.value;
        }
        renderExerciseResults();
      });
      exerciseSearch.addEventListener('keydown', event => {
        if (event.key === 'Escape') {
          closeExerciseResults();
          exerciseSearch.blur();
        }
        if (event.key === 'Enter' && !exerciseOptions.hidden) {
          const first = exerciseOptions.querySelector('.ch-exercise-search__option');
          if (first) {
            event.preventDefault();
            first.click();
          }
        }
      });
      exerciseClear.addEventListener('click', () => {
        exerciseSelect.value = '';
        selectedExerciseName = '';
        exerciseSearch.value = '';
        exerciseClear.hidden = true;
        closeExerciseResults();
        loadExercise();
        exerciseSearch.focus();
      });
      document.addEventListener('pointerdown', event => {
        if (!macroSelect.contains(event.target)) setMacroOpen(false);
        if (!exercisePicker.contains(event.target)) closeExerciseResults();
      });
      exercisePeriods.forEach(button => button.addEventListener('click', () => {
        exercisePeriods.forEach(item => item.classList.remove('is-active'));
        button.classList.add('is-active');
        exercisePeriod = button.dataset.adminExercisePeriod;
        loadExercise();
      }));
      repsToggle.addEventListener('change', syncExerciseToggles);
      weightToggle.addEventListener('change', syncExerciseToggles);
      loadMacro();
    })();
  </script>
</body>
</html>
