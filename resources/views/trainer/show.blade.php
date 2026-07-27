<!doctype html>
<html lang="en">
<head>
  <x-seo title="Client Progress" description="Read-only Trainer client analytics." robots="noindex, nofollow, noarchive" />
  <link rel="stylesheet" href="{{ asset('css/auth.css') }}?v={{ filemtime(public_path('css/auth.css')) }}">
  <link rel="stylesheet" href="{{ asset('css/admin.css') }}?v={{ filemtime(public_path('css/admin.css')) }}">
  <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
</head>
<body class="auth-body">
  <x-navbar />
  <main class="pl-container ad-wrap">
    <header class="ad-head">
      <div class="ad-profile-title">
        <img src="{{ $client->avatar_url }}" alt="" width="64" height="64">
        <div><span class="ad-eyebrow">Read-only client dashboard</span><h1>{{ $client->full_name ?? $client->name }}</h1><p>{{ '@' . ($client->username ?? 'user') }}</p></div>
      </div>
      <div class="ad-actions">
        @if($relationship->can_view_nutrition || $relationship->can_view_exercises || $relationship->can_view_weight)
          <a class="ad-button" href="{{ route('trainer.clients.weekly-report', $client) }}">Download weekly PDF</a>
        @endif
        <a class="ad-button ad-button--secondary" href="{{ route('trainer.dashboard') }}">Back to clients</a>
      </div>
    </header>

    @if(session('status'))<div class="ad-alert ad-alert--success">{{ session('status') }}</div>@endif
    @if($errors->any())
      <div class="ad-alert ad-alert--error"><strong>Please correct these fields:</strong><ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>
    @endif
    <div class="ad-privacy-banner"><strong>Consent-based access</strong> Shared analytics remain view-only. Planning tools can assign new workouts and nutrition targets, while progress photos remain unavailable.</div>

    <section class="ad-card">
      <div class="ad-card__head"><div><span class="ad-eyebrow">Client controlled</span><h2>Shared areas</h2></div><span class="ad-lock">🔒 Progress photos excluded</span></div>
      <div class="tr-access-grid">
        @foreach(['nutrition' => 'Nutrition charts', 'exercises' => 'Exercise & strength', 'weight' => 'Body-weight history', 'streaks' => 'Streaks'] as $key => $label)
          <div class="{{ $relationship->getAttribute('can_view_' . $key) ? 'is-shared' : '' }}"><span>{{ $relationship->getAttribute('can_view_' . $key) ? '✓' : '—' }}</span>{{ $label }}</div>
        @endforeach
      </div>
    </section>

    <div class="tr-planning-grid">
      <section class="ad-card">
        <div class="ad-card__head"><div><span class="ad-eyebrow">Training plan</span><h2>Assign workout</h2></div><span class="ad-lock">Creates a new client workout</span></div>
        @if($trainerWorkouts->isNotEmpty())
          <form method="POST" action="{{ route('trainer.clients.workouts.store', $client) }}" class="ad-form-grid">
            @csrf
            <label class="ad-field-wide"><span class="ad-label">Your workout template</span>
              <select name="workout_id" required>
                @foreach($trainerWorkouts as $workout)
                  <option value="{{ $workout->id }}">{{ $workout->name }} ({{ $workout->exercises_count }} exercises)</option>
                @endforeach
              </select>
            </label>
            <label class="ad-field-wide"><span class="ad-label">Client workout name (optional)</span><input name="name" maxlength="60" value="{{ old('name') }}" placeholder="Uses template name by default"></label>
            <label class="ad-field-wide"><span class="ad-label">Instructions (optional)</span><textarea name="instructions" rows="3" maxlength="2000" placeholder="Rest times, intensity, weekly schedule...">{{ old('instructions') }}</textarea></label>
            <div class="ad-form-actions ad-field-wide"><button class="ad-button" type="submit">Assign workout</button></div>
          </form>
        @else
          <div class="ad-empty">Create a workout on your own Workouts page first, then use it as a client template.</div>
        @endif

        @if($assignments->isNotEmpty())
          <div class="tr-assignment-list">
            @foreach($assignments as $assignment)
              <article>
                <div><strong>{{ $assignment->clientWorkout->name }}</strong><span>{{ $assignment->clientWorkout->exercises->count() }} exercises · assigned {{ $assignment->assigned_at->format('M j, Y') }}</span></div>
                @if($assignment->instructions)<p>{{ $assignment->instructions }}</p>@endif
              </article>
            @endforeach
          </div>
        @endif
      </section>

      <section class="ad-card">
        <div class="ad-card__head"><div><span class="ad-eyebrow">Client targets</span><h2>Nutrition goals</h2></div><span class="ad-lock">{{ $relationship->can_view_nutrition ? 'Trainer managed' : 'Not shared' }}</span></div>
        @if($relationship->can_view_nutrition)
          <form method="POST" action="{{ route('trainer.clients.nutrition-targets.update', $client) }}" class="ad-form-grid">
            @csrf @method('PATCH')
            <label><span class="ad-label">Goal</span><select name="goal" required>
              @foreach(['bulk' => 'Build muscle', 'cut' => 'Lose fat', 'recomp' => 'Recomposition'] as $value => $label)
                <option value="{{ $value }}" @selected(old('goal', $nutritionGoal?->goal ?? 'recomp') === $value)>{{ $label }}</option>
              @endforeach
            </select></label>
            <label><span class="ad-label">Calories</span><input type="number" name="calorie_target" min="800" max="8000" required value="{{ old('calorie_target', $nutritionGoal?->calorie_target) }}"></label>
            <label><span class="ad-label">Protein (g)</span><input type="number" name="protein_g" min="0" max="500" required value="{{ old('protein_g', $nutritionGoal?->protein_g) }}"></label>
            <label><span class="ad-label">Carbs (g)</span><input type="number" name="carbs_g" min="0" max="1200" required value="{{ old('carbs_g', $nutritionGoal?->carbs_g) }}"></label>
            <label><span class="ad-label">Fat (g)</span><input type="number" name="fat_g" min="0" max="400" required value="{{ old('fat_g', $nutritionGoal?->fat_g) }}"></label>
            <label><span class="ad-label">Water (L)</span><input type="number" name="water_l" min="0" max="10" step=".1" value="{{ old('water_l', $nutritionGoal?->water_l) }}"></label>
            <label><span class="ad-label">Creatine (g)</span><input type="number" name="creatine_g" min="0" max="20" step=".1" value="{{ old('creatine_g', $nutritionGoal?->creatine_g) }}"></label>
            <div class="ad-form-actions ad-field-wide"><button class="ad-button" type="submit">Save client targets</button></div>
          </form>
        @else
          <div class="ad-empty">The client must share Nutrition charts before targets can be managed.</div>
        @endif
      </section>
    </div>

    @if($relationship->can_view_streaks && $streaks)
      <section class="ad-card">
        <div class="ad-card__head"><div><span class="ad-eyebrow">View only</span><h2>Current streaks</h2></div><span class="ad-lock">Not editable</span></div>
        <div class="ad-streak-grid">
          <div><strong>{{ $streaks['login'] }}</strong><span>Login streak</span></div>
          <div><strong>{{ $streaks['workout'] }}</strong><span>Workout streak</span></div>
          <div><strong>{{ $streaks['nutrition'] }}</strong><span>Nutrition streak</span></div>
        </div>
      </section>
    @endif

    <div data-trainer-charts>
      @if($relationship->can_view_nutrition)
        <section class="pl-card ch-card ad-chart-card">
          <div class="ch-head"><div class="ch-head__left"><div class="ch-icon">🥗</div><div><span class="ad-eyebrow">Shared by client</span><h2 class="ch-title">Macronutrient Progress</h2></div></div></div>
          <div class="ch-controls">
            <div class="ch-control"><label class="ch-label">Macronutrient</label><div class="ch-selectwrap"><select class="ch-select" data-tr-macro><option value="calories">Calories</option><option value="protein">Protein</option><option value="carbs">Carbohydrates</option><option value="fat">Fat</option><option value="creatine">Creatine</option><option value="water">Water</option></select></div></div>
            @include('trainer._periods', ['attribute' => 'data-tr-macro-period', 'active' => 'month'])
          </div>
          <div class="ch-chartwrap"><canvas data-tr-macro-chart height="120"></canvas></div>
          @include('trainer._insights', ['prefix' => 'macro'])
        </section>
      @endif

      @if($relationship->can_view_exercises)
        <section class="pl-card ch-card ad-chart-card">
          <div class="ch-head"><div class="ch-head__left"><div class="ch-icon">🏋️</div><div><span class="ad-eyebrow">Shared by client</span><h2 class="ch-title">Exercise Progress</h2></div></div></div>
          <div class="ch-controls">
            <div class="ch-control"><label class="ch-label">Exercise</label><div class="ch-selectwrap"><select class="ch-select" data-tr-exercise><option value="">Choose an exercise...</option>@foreach($chartExercises as $exercise)<option value="{{ $exercise->id }}">{{ $exercise->name }}</option>@endforeach</select></div></div>
            @include('trainer._periods', ['attribute' => 'data-tr-exercise-period', 'active' => 'all'])
          </div>
          <div class="ch-chartwrap ch-chartwrap--exercise"><canvas data-tr-exercise-chart height="120"></canvas></div>
          @include('trainer._insights', ['prefix' => 'exercise'])
        </section>
      @endif

      @if($relationship->can_view_weight)
        <section class="pl-card ch-card ad-chart-card">
          <div class="ch-head"><div class="ch-head__left"><div class="ch-icon">⚖️</div><div><span class="ad-eyebrow">Shared by client</span><h2 class="ch-title">Body-weight History</h2></div></div></div>
          <div class="ch-controls"><div></div>@include('trainer._periods', ['attribute' => 'data-tr-weight-period', 'active' => 'all'])</div>
          <div class="ch-chartwrap"><canvas data-tr-weight-chart height="120"></canvas></div>
          @include('trainer._insights', ['prefix' => 'weight'])
        </section>
      @endif
    </div>

    <section class="ad-card">
      <div class="ad-card__head"><div><span class="ad-eyebrow">Trainer only</span><h2>Private notes</h2></div><span class="ad-lock">Not visible to client</span></div>
      <form method="POST" action="{{ route('trainer.clients.notes', $client) }}">@csrf @method('PATCH')
        <textarea class="tr-notes" name="trainer_notes" rows="6" maxlength="5000" placeholder="Goals, check-in notes, programming ideas...">{{ old('trainer_notes', $relationship->trainer_notes) }}</textarea>
        <div class="ad-form-actions"><button class="ad-button" type="submit">Save notes</button></div>
      </form>
    </section>
  </main>
  <x-footer />
  <script>
    (() => {
      const root = document.querySelector('[data-trainer-charts]');
      if (!root || typeof Chart === 'undefined') return;
      const commonOptions = { responsive:true, maintainAspectRatio:false, plugins:{legend:{display:false}}, scales:{x:{ticks:{color:'rgba(255,255,255,.7)'},grid:{color:'rgba(255,255,255,.08)'}},y:{beginAtZero:false,ticks:{color:'rgba(255,255,255,.7)'},grid:{color:'rgba(255,255,255,.08)'}}} };
      const makeChart = (canvas, datasets) => canvas ? new Chart(canvas, {type:'line',data:{labels:[],datasets},options:commonOptions}) : null;
      const single = color => [{data:[],borderColor:color,backgroundColor:'transparent',pointBackgroundColor:color,borderWidth:2,pointRadius:3,tension:.35}];
      const format = value => value == null ? '—' : Number(value).toLocaleString(undefined,{maximumFractionDigits:1});
      const updateInsights = (prefix, insights) => ['latest','average','highest','change_percent'].forEach(key => {
        const el = root.querySelector(`[data-${prefix}-insight="${key}"]`);
        if (el) el.textContent = key === 'change_percent' && insights?.[key] != null ? `${insights[key] > 0 ? '+' : ''}${format(insights[key])}%` : format(insights?.[key]);
      });
      const wirePeriods = (selector, active, callback) => {
        const buttons = [...root.querySelectorAll(selector)];
        let period = active;
        buttons.forEach(button => button.addEventListener('click', () => {
          buttons.forEach(item => item.classList.remove('is-active')); button.classList.add('is-active'); period = button.dataset.periodValue; callback(period);
        }));
        return () => period;
      };

      const macroSelect = root.querySelector('[data-tr-macro]');
      const macroChart = makeChart(root.querySelector('[data-tr-macro-chart]'), single('#ff4d4d'));
      let macroPeriod = 'month';
      const loadMacro = async period => {
        macroPeriod = period || macroPeriod;
        const url = new URL(@json(route('trainer.clients.charts.macros', $client)), location.origin);
        url.searchParams.set('macro', macroSelect.value); url.searchParams.set('period', macroPeriod);
        const response = await fetch(url,{headers:{Accept:'application/json'}}); if(!response.ok)return;
        const data = await response.json(); const color = data.meta?.color || '#4b91ff';
        macroChart.data.labels=data.labels||[]; macroChart.data.datasets[0].data=data.values||[]; macroChart.data.datasets[0].borderColor=color; macroChart.update(); updateInsights('macro',data.insights);
      };
      if (macroSelect) { macroSelect.addEventListener('change',()=>loadMacro()); wirePeriods('[data-tr-macro-period]','month',loadMacro); loadMacro(); }

      const exerciseSelect = root.querySelector('[data-tr-exercise]');
      const exerciseChart = makeChart(root.querySelector('[data-tr-exercise-chart]'), [
        {label:'Reps',data:[],borderColor:'#22c55e',backgroundColor:'transparent',pointBackgroundColor:'#22c55e',borderWidth:2,pointRadius:3,tension:.35},
        {label:'Weight',data:[],borderColor:'#3b82f6',backgroundColor:'transparent',pointBackgroundColor:'#3b82f6',borderWidth:2,pointRadius:3,tension:.35}
      ]);
      let exercisePeriod='all';
      const loadExercise = async period => {
        exercisePeriod=period||exercisePeriod; if(!exerciseSelect.value)return;
        const url=new URL(@json(route('trainer.clients.charts.exercise-data',$client)),location.origin); url.searchParams.set('exercise_id',exerciseSelect.value);url.searchParams.set('period',exercisePeriod);
        const response=await fetch(url,{headers:{Accept:'application/json'}});if(!response.ok)return;const data=await response.json();
        exerciseChart.data.labels=data.labels||[];exerciseChart.data.datasets[0].data=data.reps||[];exerciseChart.data.datasets[1].data=data.weight||[];exerciseChart.update();updateInsights('exercise',data.insights?.weight);
      };
      if(exerciseSelect){exerciseSelect.addEventListener('change',()=>loadExercise());wirePeriods('[data-tr-exercise-period]','all',loadExercise);}

      const weightChart=makeChart(root.querySelector('[data-tr-weight-chart]'),single('#a875ff'));let weightPeriod='all';
      const loadWeight=async period=>{weightPeriod=period||weightPeriod;const url=new URL(@json(route('trainer.clients.charts.weight',$client)),location.origin);url.searchParams.set('period',weightPeriod);const response=await fetch(url,{headers:{Accept:'application/json'}});if(!response.ok)return;const data=await response.json();weightChart.data.labels=data.labels||[];weightChart.data.datasets[0].data=data.values||[];weightChart.update();updateInsights('weight',data.insights);};
      if(weightChart){wirePeriods('[data-tr-weight-period]','all',loadWeight);loadWeight();}
    })();
  </script>
</body>
</html>
