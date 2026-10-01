<!doctype html>
<html lang="en">
<head>
  <x-seo title="Client Workout Log" description="Edit a permitted client workout log." robots="noindex, nofollow, noarchive" />
  <link rel="stylesheet" href="{{ asset('css/auth.css') }}?v={{ filemtime(public_path('css/auth.css')) }}">
  <link rel="stylesheet" href="{{ asset('css/admin.css') }}?v={{ filemtime(public_path('css/admin.css')) }}">
</head>
<body class="auth-body">
  <x-navbar />
  <main class="pl-container ad-wrap tr-log-page">
    <header class="ad-head">
      <div class="ad-profile-title">
        <img src="{{ $client->avatar_url }}" alt="" width="64" height="64">
        <div><span class="ad-eyebrow">Assigned workout log</span><h1>{{ $workout->name }}</h1><p>{{ $client->full_name ?? $client->name }} · {{ '@' . ($client->username ?? 'user') }}</p></div>
      </div>
      <a class="ad-button ad-button--secondary" href="{{ route('trainer.clients.show', $client) }}">Back to client</a>
    </header>

    @if(session('status'))<div class="ad-alert ad-alert--success">{{ session('status') }}</div>@endif
    @if($errors->any())<div class="ad-alert ad-alert--error"><strong>Please correct these fields:</strong><ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif

    <div class="ad-privacy-banner"><strong>Trainer entry</strong> Changes are saved to the client’s real workout history and may update their exercise ranks, XP, achievements, and workout activity.</div>

    <section class="ad-card tr-log-toolbar">
      <form method="GET" action="{{ route('trainer.clients.workout-logs.edit', [$client, $assignment]) }}">
        <label><span class="ad-label">Workout date</span><input type="date" name="date" max="{{ now()->toDateString() }}" value="{{ $selectedDate }}"></label>
        <button class="ad-button ad-button--secondary" type="submit">Load date</button>
      </form>
      @if($recentLogs->isNotEmpty())
        <div class="tr-log-dates" aria-label="Recent workout dates"><span>Recent:</span>@foreach($recentLogs as $recentLog)<a class="{{ $recentLog->entry_date === $selectedDate ? 'is-active' : '' }}" href="{{ route('trainer.clients.workout-logs.edit', [$client, $assignment, 'date' => $recentLog->entry_date]) }}">{{ \Illuminate\Support\Carbon::parse($recentLog->entry_date)->format('M j') }}</a>@endforeach</div>
      @endif
    </section>

    @if($assignment->instructions)<section class="tr-log-instructions"><strong>Workout instructions</strong><p>{{ $assignment->instructions }}</p></section>@endif

    @if($conflictingLog)
      <section class="ad-card tr-log-conflict"><h2>Another workout is already logged</h2><p>{{ $client->full_name ?? $client->name }} logged “{{ $conflictingLog->workout?->name ?? 'another workout' }}” on {{ \Illuminate\Support\Carbon::parse($selectedDate)->format('M j, Y') }}. Choose a different date so that entry is not replaced.</p></section>
    @else
      @php
        $weightUnit = $client->weightUnit();
        $displayWeight = fn ($value) => \App\Support\UnitConverter::weightFromKg($value, $client->unit_system);
        $loggedExercises = $log?->exercises?->keyBy('exercise_id') ?? collect();
        $oldExercises = collect(old('exercises', []))->keyBy(fn ($item) => (int) ($item['exercise_id'] ?? 0));
      @endphp
      <form method="POST" action="{{ route('trainer.clients.workout-logs.update', [$client, $assignment]) }}" data-trainer-log-form>
        @csrf @method('PUT')
        <input type="hidden" name="entry_date" value="{{ $selectedDate }}">
        <div class="tr-log-exercises">
          @foreach($workout->exercises as $exerciseIndex => $exercise)
            @php
              $loggedExercise = $loggedExercises->get($exercise->id);
              $oldExercise = $oldExercises->get($exercise->id);
              $savedSets = $loggedExercise ? $loggedExercise->sets->map(fn ($set) => ['set_number'=>$set->set_number,'set_type'=>$set->set_type ?? 'normal','reps'=>$set->reps,'weight_kg'=>$displayWeight($set->weight_kg),'drop_reps'=>$set->drop_reps,'drop_weight_kg'=>$displayWeight($set->drop_weight_kg)])->values()->all() : [];
              $sets = $oldExercise['sets'] ?? $savedSets;
              if (empty($sets)) $sets = array_map(fn ($number) => ['set_number'=>$number,'set_type'=>'normal'], range(1, 3));
              $previous = collect($history[(string) $exercise->id]['sets'] ?? [])->map(fn ($set) => array_merge($set, ['weight_kg'=>$displayWeight($set['weight_kg'] ?? null),'drop_weight_kg'=>$displayWeight($set['drop_weight_kg'] ?? null)]))->all();
            @endphp
            <section class="ad-card tr-log-exercise" data-log-exercise data-exercise-index="{{ $exerciseIndex }}">
              <input type="hidden" name="exercises[{{ $exerciseIndex }}][exercise_id]" value="{{ $exercise->id }}">
              <header><div><span class="ad-eyebrow">{{ $exercise->muscle_group ?? 'Exercise' }}</span><h2>{{ $exercise->name }}</h2></div>@if(isset($history[(string) $exercise->id]))<span class="tr-log-last">Last logged {{ \Illuminate\Support\Carbon::parse($history[(string) $exercise->id]['date'])->format('M j') }}</span>@endif</header>
              <div class="tr-set-head" aria-hidden="true"><span>Set</span><span>Type</span><span>Reps</span><span>Weight ({{ $weightUnit }})</span><span></span></div>
              <div class="tr-set-list" data-set-list>
                @foreach($sets as $setIndex => $set)
                  @php $type=$set['set_type'] ?? 'normal'; $previousSet=$previous[$setIndex] ?? []; @endphp
                  <div class="tr-set-row {{ $type === 'warmup' ? 'is-warmup' : '' }} {{ $type === 'drop' ? 'is-drop' : '' }}" data-set-row>
                    <span class="tr-set-number" data-set-number>{{ $setIndex + 1 }}</span>
                    <input type="hidden" data-set-number-input name="exercises[{{ $exerciseIndex }}][sets][{{ $setIndex }}][set_number]" value="{{ $setIndex + 1 }}">
                    <select data-set-type name="exercises[{{ $exerciseIndex }}][sets][{{ $setIndex }}][set_type]"><option value="normal" @selected($type === 'normal')>Working</option><option value="warmup" @selected($type === 'warmup')>Warm-up</option><option value="drop" @selected($type === 'drop')>Drop set</option></select>
                    <input type="number" inputmode="numeric" min="0" max="300" name="exercises[{{ $exerciseIndex }}][sets][{{ $setIndex }}][reps]" value="{{ $set['reps'] ?? '' }}" placeholder="{{ $previousSet['reps'] ?? 12 }}" aria-label="Set {{ $setIndex + 1 }} reps">
                    <input type="number" inputmode="decimal" min="0" max="2205" step=".1" name="exercises[{{ $exerciseIndex }}][sets][{{ $setIndex }}][weight_kg]" value="{{ $set['weight_kg'] ?? '' }}" placeholder="{{ $previousSet['weight_kg'] ?? 0 }}" aria-label="Set {{ $setIndex + 1 }} weight in {{ $weightUnit }}">
                    <button type="button" class="tr-set-remove" data-remove-set aria-label="Remove set">×</button>
                    <div class="tr-drop-fields" data-drop-fields {{ $type !== 'drop' ? 'hidden' : '' }}><span>Drop to</span><input type="number" inputmode="numeric" min="0" max="300" name="exercises[{{ $exerciseIndex }}][sets][{{ $setIndex }}][drop_reps]" value="{{ $set['drop_reps'] ?? '' }}" placeholder="{{ $previousSet['drop_reps'] ?? 8 }}" aria-label="Drop set reps"><input type="number" inputmode="decimal" min="0" max="2205" step=".1" name="exercises[{{ $exerciseIndex }}][sets][{{ $setIndex }}][drop_weight_kg]" value="{{ $set['drop_weight_kg'] ?? '' }}" placeholder="{{ $previousSet['drop_weight_kg'] ?? 0 }}" aria-label="Drop set weight in {{ $weightUnit }}"></div>
                  </div>
                @endforeach
              </div>
              <button type="button" class="tr-add-set" data-add-set>+ Add set</button>
            </section>
          @endforeach
        </div>
        <div class="tr-log-savebar"><div><strong>{{ $log ? 'Editing saved workout' : 'New workout entry' }}</strong><span>{{ \Illuminate\Support\Carbon::parse($selectedDate)->format('F j, Y') }}</span></div><button class="ad-button" type="submit">Save client workout</button></div>
      </form>
    @endif
  </main>
  <x-footer />

  <template data-set-template><div class="tr-set-row" data-set-row><span class="tr-set-number" data-set-number></span><input type="hidden" data-set-number-input><select data-set-type><option value="normal">Working</option><option value="warmup">Warm-up</option><option value="drop">Drop set</option></select><input type="number" inputmode="numeric" min="0" max="300" placeholder="12" data-field="reps" aria-label="Set reps"><input type="number" inputmode="decimal" min="0" max="2205" step=".1" placeholder="0" data-field="weight_kg" aria-label="Set weight in {{ $weightUnit }}"><button type="button" class="tr-set-remove" data-remove-set aria-label="Remove set">×</button><div class="tr-drop-fields" data-drop-fields hidden><span>Drop to</span><input type="number" inputmode="numeric" min="0" max="300" placeholder="8" data-field="drop_reps" aria-label="Drop set reps"><input type="number" inputmode="decimal" min="0" max="2205" step=".1" placeholder="0" data-field="drop_weight_kg" aria-label="Drop set weight in {{ $weightUnit }}"></div></div></template>
  <script>
    (()=>{const template=document.querySelector('[data-set-template]');const renumber=exercise=>{const exerciseIndex=exercise.dataset.exerciseIndex;exercise.querySelectorAll('[data-set-row]').forEach((row,index)=>{const number=index+1;row.querySelector('[data-set-number]').textContent=number;const hidden=row.querySelector('[data-set-number-input]');hidden.name=`exercises[${exerciseIndex}][sets][${index}][set_number]`;hidden.value=number;row.querySelector('[data-set-type]').name=`exercises[${exerciseIndex}][sets][${index}][set_type]`;['reps','weight_kg','drop_reps','drop_weight_kg'].forEach(field=>{const input=row.querySelector(`[data-field="${field}"]`)||row.querySelector(`[name$="[${field}]"]`);if(input)input.name=`exercises[${exerciseIndex}][sets][${index}][${field}]`;});});};const syncType=row=>{const type=row.querySelector('[data-set-type]').value;row.classList.toggle('is-warmup',type==='warmup');row.classList.toggle('is-drop',type==='drop');row.querySelector('[data-drop-fields]').hidden=type!=='drop';};document.querySelectorAll('[data-log-exercise]').forEach(exercise=>{exercise.addEventListener('change',event=>{if(event.target.matches('[data-set-type]'))syncType(event.target.closest('[data-set-row]'));});exercise.addEventListener('click',event=>{if(event.target.matches('[data-add-set]')){exercise.querySelector('[data-set-list]').append(template.content.firstElementChild.cloneNode(true));renumber(exercise);}if(event.target.matches('[data-remove-set]')){const rows=exercise.querySelectorAll('[data-set-row]');if(rows.length>1)event.target.closest('[data-set-row]').remove();renumber(exercise);}});exercise.querySelectorAll('[data-set-row]').forEach(syncType);renumber(exercise);});})();
  </script>
</body>
</html>
