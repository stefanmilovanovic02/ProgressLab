<!doctype html>
<html lang="en">
<head>
  <x-seo
    title="Workout Plans"
    description="Create, organize, and manage reusable workout plans and exercises with ProgressLab."
    robots="noindex, nofollow, noarchive"
  />

  <link rel="stylesheet" href="{{ asset('css/auth.css') }}">
  <link rel="stylesheet" href="{{ asset('css/workout-duration.css') }}">
</head>
<body class="auth-body">

  <x-navbar />

  <main class="pl-container">

    {{-- Page Header --}}
    <div class="pl-pagehead">
      <div class="pl-pagehead__title pl-pagehead__title--center">
        <h1>My Workouts</h1>
      </div>
      <p class="pl-pagehead__sub pl-pagehead__sub--center">
        View, create, and manage your workout plans.
      </p>
    </div>

    {{-- Flash message --}}
    @if(session('status'))
      <div class="pl-alert">{{ session('status') }}</div>
    @endif

    {{-- Grid --}}
    <section class="wo-grid" aria-label="Workout list">

      {{-- Workout cards --}}
      @forelse($workouts as $workout)
        @php
          $exerciseCount = $workout->exercises->count();
          $moreCount = max(0, $exerciseCount - 3);
        @endphp

        <article class="wo-card" data-workout-card>
          <header class="wo-card__head">
            <div class="wo-card__titlewrap">
              <span class="wo-icon" aria-hidden="true">🏋️</span>
              <h3 class="wo-card__title">{{ $workout->name }}</h3>
            </div>

            <div class="icons-wrapper">
            {{-- Edit button --}}
            <button
              class="wo-iconbtn"
              type="button"
              data-edit-workout
              data-workout-id="{{ $workout->id }}"
              aria-label="Edit workout"
              title="Edit"
            >
              ✏️
            </button>

            {{-- Delete (real) --}}
            <form action="{{ route('workouts.destroy', $workout) }}" method="POST">
              @csrf
              @method('DELETE')
              <button class="wo-iconbtn" type="submit" aria-label="Delete workout" title="Delete">
                🗑️
              </button>
            </form>
            </div>
          </header>

          <div class="wo-card__body">
            @if($workout->trainerAssignment)
              <div class="wo-trainer-plan">
                <strong>Trainer assigned</strong>
                <span>From {{ $workout->trainerAssignment->relationship->trainer->full_name ?? $workout->trainerAssignment->relationship->trainer->name }}</span>
                @if($workout->trainerAssignment->instructions)
                  <p>{{ $workout->trainerAssignment->instructions }}</p>
                @endif
              </div>
            @endif
            <div class="wo-label">Exercises</div>

            <ul class="wo-list" role="list">
              @foreach($workout->exercises as $index => $ex)
                <li class="wo-row {{ $index >= 3 ? 'wo-row--extra' : '' }}" {{ $index >= 3 ? 'hidden' : '' }}>
                  <span class="wo-row__name">{{ $ex->name }}</span>
                  <span class="wo-chip">{{ $ex->muscle_group ?? '—' }}</span>
                </li>
              @endforeach
            </ul>

            @if($moreCount > 0)
              <button
                class="wo-more"
                type="button"
                aria-expanded="false"
                data-workout-expand
                data-collapsed-label="+{{ $moreCount }} more {{ $moreCount === 1 ? 'exercise' : 'exercises' }}"
              >
                +{{ $moreCount }} more {{ $moreCount === 1 ? 'exercise' : 'exercises' }}
              </button>
            @endif
          </div>

          <footer class="wo-card__foot">
            <div class="wo-footline"></div>
            <div class="wo-total">
              <div class="wo-total__item">
                <span class="wo-total__label">Estimated Time</span>
                <span class="wo-total__value">{{ $workout->estimated_duration_label ?? '—' }}</span>
              </div>
              <div class="wo-total__item wo-total__item--right">
                <span class="wo-total__label">Total Exercises</span>
                <span class="wo-total__value">{{ $exerciseCount }}</span>
              </div>
            </div>
          </footer>
        </article>

      @empty
        <div class="wo-empty">
          <div class="wo-empty__title">No workouts yet</div>
          <div class="wo-empty__sub">Create your first workout to start tracking progress.</div>
        </div>
      @endforelse

      {{-- Add new workout card (always visible) --}}
      <button class="wo-add" type="button" data-open-create>
        <div class="wo-add__inner">
          <div class="wo-add__plus">+</div>
          <div class="wo-add__text">Add New Workout</div>
        </div>
      </button>

    </section>
  </main>

  {{-- Create Workout Modal --}}
  <div class="wo-modal" data-create-modal data-auto-open="{{ request()->boolean('create') || $errors->any() ? 'true' : 'false' }}">
    <div class="wo-modal__backdrop" data-close-create></div>

    <div class="wo-modal__panel" role="dialog" aria-modal="true" aria-label="Add New Workout">
      <div class="wo-modal__top">
        <div class="wo-modal__titlewrap">
          <span class="wo-icon" aria-hidden="true">🏋️</span>
          <div class="wo-modal__title" data-modal-title>Add New Workout</div>
        </div>
        <button class="wo-modal__x" type="button" data-close-create aria-label="Close">✕</button>
      </div>

      <form action="{{ route('workouts.store') }}" method="POST" id="createWorkoutForm">
        @csrf
      <input type="hidden" name="_method" value="POST" data-form-method>
        <div class="wo-field">
          <label class="wo-label2">Workout Name</label>
          <input class="wo-input" name="name" type="text" placeholder="e.g., Chest & Triceps" maxlength="60" required>
        </div>

        <div class="wo-builder-grid">
          <div class="wo-exercise-picker">
            <div class="wo-exhead">
              <div>
                <div class="wo-label2">Find exercises</div>
                <p>Search, then select as many exercises as you need.</p>
              </div>
            </div>

            <label class="wo-search-label" for="workoutExerciseSearch">Exercise or muscle group</label>
            <input class="wo-input wo-picker-search" id="workoutExerciseSearch" type="search" placeholder="Start typing to search..." autocomplete="off">

            <div class="wo-picker-status" data-picker-status aria-live="polite">Start typing to find exercises.</div>
            <div class="wo-picker-results" data-picker-results role="listbox" aria-label="Exercise search results" aria-multiselectable="true" hidden></div>
          </div>

          <div class="wo-selected-panel">
            <div class="wo-selected-head">
              <div>
                <div class="wo-label2">Workout exercises</div>
                <strong data-selected-count>0 selected</strong>
              </div>
              <button type="button" data-clear-selected>Clear all</button>
            </div>
            <div class="wo-selected-list" data-selected-list></div>
            <div class="wo-picker-error" data-picker-error hidden>Please select at least one exercise.</div>
          </div>
        </div>

        <div class="wo-modal__actions">
          <button class="pl-btn pl-btn--ghost" type="button" data-close-create>Cancel</button>
          <button class="pl-btn pl-btn--light" type="submit">Save Workout</button>
        </div>
      </form>
    </div>
  </div>

 <script>
(function () {
  document.querySelectorAll('[data-workout-expand]').forEach(button => {
    button.addEventListener('click', () => {
      const card = button.closest('[data-workout-card]');
      if (!card) return;

      const expanded = button.getAttribute('aria-expanded') !== 'true';
      card.querySelectorAll('.wo-row--extra').forEach(row => {
        row.hidden = !expanded;
      });
      card.classList.toggle('is-expanded', expanded);
      button.setAttribute('aria-expanded', expanded ? 'true' : 'false');
      button.textContent = expanded ? 'Show less' : button.dataset.collapsedLabel;
    });
  });
})();

(function () {
  const openBtns = document.querySelectorAll('[data-open-create]');
  const modal = document.querySelector('[data-create-modal]');
  const closeBtns = document.querySelectorAll('[data-close-create]');
  const rowsWrap = document.getElementById('exerciseRows');
  const addRowBtn = document.getElementById('addExerciseRow');

  const form = document.getElementById('createWorkoutForm');
  const methodInput = form?.querySelector('[data-form-method]');
  const modalTitle = document.querySelector('[data-modal-title]');
  const nameInput = form?.querySelector('input[name="name"]');

  if (!modal || !rowsWrap || !addRowBtn || !form || !methodInput || !modalTitle || !nameInput) return;

  const searchUrl = "{{ route('exercises.search') }}";
  const storeUrl = "{{ route('workouts.store') }}";
  const editDataUrlBase = "{{ url('/workouts') }}"; // /workouts/{id}/edit-data
  const updateUrlBase   = "{{ url('/workouts') }}"; // /workouts/{id}

  function openModal() {
    modal.classList.add('is-active');
    document.body.classList.add('wo-modal-open');
  }
  function closeModal() {
    modal.classList.remove('is-active');
    document.body.classList.remove('wo-modal-open');
    rowsWrap.innerHTML = '';
    form.reset();
  }

  openBtns.forEach(btn => btn.addEventListener('click', () => {
    openModal();
    setCreateMode();
  }));
  closeBtns.forEach(btn => btn.addEventListener('click', closeModal));

  // ✅ Close all suggestion dropdowns
  function closeAllSuggest(exceptRow = null) {
    document.querySelectorAll('.wo-exrow').forEach(r => {
      if (exceptRow && r === exceptRow) return;
      const s = r.querySelector('.wo-suggest');
      if (s) s.hidden = true;
    });
  }

  // ✅ CREATE MODE
  function setCreateMode() {
    modalTitle.textContent = 'Add New Workout';
    form.action = storeUrl;
    methodInput.value = 'POST';

    form.reset();
    rowsWrap.innerHTML = '';
    addRow(); // start with 1 row
  }

  // ✅ EDIT MODE
  function setEditMode(data) {
    modalTitle.textContent = 'Edit Workout';
    form.action = `${updateUrlBase}/${data.id}`;
    methodInput.value = 'PUT';

    nameInput.value = data.name || '';
    rowsWrap.innerHTML = '';

    (data.exercises || []).forEach(ex => addRowPrefilled(ex));
    if ((data.exercises || []).length === 0) addRow();
  }

  // ✅ Add row (empty)
  function addRow() {
    const row = document.createElement('div');
    row.className = 'wo-exrow';
    row.innerHTML = `
      <div class="wo-excard">
        <div class="wo-exinputwrap">
          <input class="wo-input wo-exinput" type="text" placeholder="Exercise name (e.g., Bench Press)" autocomplete="off">
          <input type="hidden" name="exercise_ids[]" class="wo-exid">
          <div class="wo-suggest" role="listbox" aria-label="Exercise search results" hidden></div>
        </div>
      </div>

      <button class="wo-trash" type="button" aria-label="Remove">🗑️</button>
    `;

    const textInput = row.querySelector('.wo-exinput');
    const hiddenId  = row.querySelector('.wo-exid');
    const suggest   = row.querySelector('.wo-suggest');
    const trash     = row.querySelector('.wo-trash');

    trash.addEventListener('click', () => row.remove());

    // prevent click inside dropdown from closing it
    suggest.addEventListener('click', (e) => e.stopPropagation());

    let lastController = null;

    async function fetchResults(q) {
      if (lastController) lastController.abort();
      lastController = new AbortController();

      const res = await fetch(`${searchUrl}?q=${encodeURIComponent(q)}`, {
        headers: { 'Accept': 'application/json' },
        signal: lastController.signal
      });

      if (!res.ok) return [];
      return await res.json();
    }

    function clearPicked() {
      hiddenId.value = '';
    }

    function showSuggest(items) {
      suggest.innerHTML = '';

      if (!items.length) {
        suggest.innerHTML = '<div class="wo-suggest__empty">No matching exercises found. Try another name.</div>';
        suggest.hidden = false;
        return;
      }

      items.forEach(item => {
        const btn = document.createElement('button');
        btn.type = 'button';
        btn.className = 'wo-suggest__item';
        btn.innerHTML = `
          <div class="wo-suggest__name">${item.name}</div>
          <div class="wo-suggest__meta">${item.muscle_group ?? ''}</div>
        `;

        btn.addEventListener('click', () => {
          textInput.value = item.name;
          hiddenId.value = item.id;
          suggest.hidden = true;
          closeAllSuggest();
        });

        suggest.appendChild(btn);
      });

      suggest.hidden = false;
    }

    textInput.addEventListener('focus', () => closeAllSuggest(row));

    textInput.addEventListener('blur', () => {
      setTimeout(() => { suggest.hidden = true; }, 120);
    });

    textInput.addEventListener('input', async () => {
      const q = textInput.value.trim();

      closeAllSuggest(row);
      clearPicked();

      if (q.length < 1) { suggest.hidden = true; return; }

      const items = await fetchResults(q);
      showSuggest(items);
    });

    rowsWrap.appendChild(row);
    return row;
  }

  // ✅ Add row (prefilled)
  function addRowPrefilled(ex) {
    const row = addRow();

    const textInput = row.querySelector('.wo-exinput');
    const hiddenId  = row.querySelector('.wo-exid');
    const suggest   = row.querySelector('.wo-suggest');

    textInput.value = ex.name || '';
    hiddenId.value  = ex.id || '';

    if (suggest) suggest.hidden = true;
  }

  addRowBtn.addEventListener('click', () => {
    const row = addRow();
    row.querySelector('.wo-exinput')?.focus();
  });

  // ✅ Global click-away closes all suggestions
  document.addEventListener('click', function (e) {
    const insideRow = e.target.closest('.wo-exrow');
    if (!insideRow) closeAllSuggest();
  });

  // ✅ Edit button handler (fetch workout data + open in edit mode)
  document.addEventListener('click', async (e) => {
    const btn = e.target.closest('[data-edit-workout]');
    if (!btn) return;

    const id = btn.getAttribute('data-workout-id');
    if (!id) return;

    openModal();

    try {
      const res = await fetch(`${editDataUrlBase}/${id}/edit-data`, {
        headers: { 'Accept': 'application/json' }
      });

      if (!res.ok) throw new Error('Failed to load workout');

      const data = await res.json();
      setEditMode(data);

    } catch (err) {
      // fallback to create mode if something fails
      setCreateMode();
    }
  });

  if (modal.dataset.autoOpen === 'true') {
    setCreateMode();
    openModal();
    window.setTimeout(() => nameInput.focus(), 0);
  }

})();

(function () {
  const modal = document.querySelector('[data-create-modal]');
  const form = document.getElementById('createWorkoutForm');
  const methodInput = form?.querySelector('[data-form-method]');
  const modalTitle = document.querySelector('[data-modal-title]');
  const nameInput = form?.querySelector('input[name="name"]');
  const searchInput = document.getElementById('workoutExerciseSearch');
  const results = document.querySelector('[data-picker-results]');
  const selectedList = document.querySelector('[data-selected-list]');
  const selectedCount = document.querySelector('[data-selected-count]');
  const pickerStatus = document.querySelector('[data-picker-status]');
  const pickerError = document.querySelector('[data-picker-error]');
  const clearSelected = document.querySelector('[data-clear-selected]');

  if (!modal || !form || !methodInput || !modalTitle || !nameInput || !searchInput || !results || !selectedList || !selectedCount || !pickerStatus || !pickerError || !clearSelected) return;

  const searchUrl = "{{ route('exercises.search') }}";
  const storeUrl = "{{ route('workouts.store') }}";
  const workoutUrl = "{{ url('/workouts') }}";
  const oldSelectedExercises = @json($oldSelectedExercises);
  const oldWorkoutName = @json(old('name', ''));
  let selectedExercises = [];
  let visibleResults = [];
  let searchTimer = null;
  let searchController = null;

  const normalizeExercise = exercise => ({
    id: Number(exercise.id),
    name: String(exercise.name || ''),
    muscle_group: String(exercise.muscle_group || ''),
  });
  const isSelected = id => selectedExercises.some(exercise => exercise.id === Number(id));

  function openModal() {
    modal.classList.add('is-active');
    document.body.classList.add('wo-modal-open');
  }

  function closeModal() {
    modal.classList.remove('is-active');
    document.body.classList.remove('wo-modal-open');
    if (searchController) searchController.abort();
    form.reset();
  }

  function renderSelected() {
    selectedList.innerHTML = '';
    selectedCount.textContent = `${selectedExercises.length} selected`;
    clearSelected.disabled = selectedExercises.length === 0;
    pickerError.hidden = selectedExercises.length > 0;

    if (selectedExercises.length === 0) {
      const empty = document.createElement('div');
      empty.className = 'wo-selected-empty';
      empty.textContent = 'No exercises selected yet. Choose several from the list above.';
      selectedList.appendChild(empty);
      return;
    }

    selectedExercises.forEach((exercise, index) => {
      const row = document.createElement('div');
      row.className = 'wo-selected-item';

      const order = document.createElement('span');
      order.className = 'wo-selected-order';
      order.textContent = String(index + 1);

      const copy = document.createElement('div');
      const name = document.createElement('strong');
      name.textContent = exercise.name;
      const group = document.createElement('small');
      group.textContent = exercise.muscle_group || 'Other';
      copy.append(name, group);

      const hidden = document.createElement('input');
      hidden.type = 'hidden';
      hidden.name = 'exercise_ids[]';
      hidden.value = String(exercise.id);

      const remove = document.createElement('button');
      remove.type = 'button';
      remove.className = 'wo-selected-remove';
      remove.textContent = 'Remove';
      remove.setAttribute('aria-label', `Remove ${exercise.name}`);
      remove.addEventListener('click', () => {
        selectedExercises = selectedExercises.filter(item => item.id !== exercise.id);
        syncPicker();
      });

      row.append(order, copy, hidden, remove);
      selectedList.appendChild(row);
    });
  }

  function renderResults() {
    results.innerHTML = '';

    if (visibleResults.length === 0) {
      const empty = document.createElement('div');
      empty.className = 'wo-picker-empty';
      empty.textContent = 'No matching exercises found. Try an exercise or muscle-group name.';
      results.appendChild(empty);
      return;
    }

    visibleResults.forEach(exercise => {
      const selected = isSelected(exercise.id);
      const button = document.createElement('button');
      button.type = 'button';
      button.className = `wo-picker-option${selected ? ' is-selected' : ''}`;
      button.setAttribute('role', 'option');
      button.setAttribute('aria-selected', selected ? 'true' : 'false');

      const copy = document.createElement('span');
      const name = document.createElement('strong');
      name.textContent = exercise.name;
      const group = document.createElement('small');
      group.textContent = exercise.muscle_group || 'Other';
      copy.append(name, group);

      const state = document.createElement('span');
      state.className = 'wo-picker-option__state';
      state.textContent = selected ? 'Selected' : 'Select';
      button.append(copy, state);
      button.addEventListener('click', () => {
        selectedExercises = selected
          ? selectedExercises.filter(item => item.id !== exercise.id)
          : [...selectedExercises, exercise];
        syncPicker();
      });
      results.appendChild(button);
    });
  }

  function syncPicker() {
    renderSelected();
    renderResults();
  }

  function resetSearchResults() {
    if (searchController) searchController.abort();
    visibleResults = [];
    results.innerHTML = '';
    results.hidden = true;
    pickerStatus.textContent = 'Start typing to find exercises.';
  }

  async function loadExercises(query = '') {
    if (!query) {
      resetSearchResults();
      return;
    }
    if (searchController) searchController.abort();
    searchController = new AbortController();
    results.hidden = false;
    pickerStatus.textContent = 'Searching exercises...';

    try {
      const response = await fetch(`${searchUrl}?q=${encodeURIComponent(query)}`, {
        headers: { 'Accept': 'application/json' },
        signal: searchController.signal,
      });
      if (!response.ok) throw new Error('Exercise search failed.');

      visibleResults = (await response.json()).map(normalizeExercise);
      pickerStatus.textContent = visibleResults.length >= 80
        ? 'Showing the first 80 matches. Type more to narrow the list.'
        : `${visibleResults.length} ${visibleResults.length === 1 ? 'exercise' : 'exercises'} shown`;
      renderResults();
    } catch (error) {
      if (error.name === 'AbortError') return;
      visibleResults = [];
      pickerStatus.textContent = 'Exercises could not be loaded. Try searching again.';
      renderResults();
    }
  }

  function setCreateMode(restoreOld = false) {
    modalTitle.textContent = 'Add New Workout';
    form.action = storeUrl;
    methodInput.value = 'POST';
    form.reset();
    nameInput.value = restoreOld ? oldWorkoutName : '';
    searchInput.value = '';
    selectedExercises = restoreOld ? oldSelectedExercises.map(normalizeExercise) : [];
    syncPicker();
    resetSearchResults();
  }

  function setEditMode(data) {
    modalTitle.textContent = 'Edit Workout';
    form.action = `${workoutUrl}/${data.id}`;
    methodInput.value = 'PUT';
    nameInput.value = data.name || '';
    searchInput.value = '';
    selectedExercises = (data.exercises || []).map(normalizeExercise);
    syncPicker();
    resetSearchResults();
  }

  document.querySelectorAll('[data-open-create]').forEach(button => button.addEventListener('click', () => {
    setCreateMode();
    openModal();
    window.setTimeout(() => nameInput.focus(), 0);
  }));
  document.querySelectorAll('[data-close-create]').forEach(button => button.addEventListener('click', closeModal));

  searchInput.addEventListener('input', () => {
    window.clearTimeout(searchTimer);
    const query = searchInput.value.trim();
    if (!query) {
      resetSearchResults();
      return;
    }
    searchTimer = window.setTimeout(() => loadExercises(query), 180);
  });

  clearSelected.addEventListener('click', () => {
    selectedExercises = [];
    syncPicker();
  });

  form.addEventListener('submit', event => {
    if (selectedExercises.length > 0) return;
    event.preventDefault();
    pickerError.hidden = false;
    searchInput.focus();
  });

  document.addEventListener('click', async event => {
    const button = event.target.closest('[data-edit-workout]');
    if (!button) return;

    const id = button.getAttribute('data-workout-id');
    if (!id) return;
    openModal();
    pickerStatus.textContent = 'Loading workout...';

    try {
      const response = await fetch(`${workoutUrl}/${id}/edit-data`, { headers: { 'Accept': 'application/json' } });
      if (!response.ok) throw new Error('Failed to load workout.');
      setEditMode(await response.json());
    } catch (error) {
      setCreateMode();
    }
  });

  if (modal.dataset.autoOpen === 'true') {
    setCreateMode({{ $errors->any() ? 'true' : 'false' }});
    openModal();
    window.setTimeout(() => nameInput.focus(), 0);
  }

  document.addEventListener('keydown', event => {
    if (event.key === 'Escape' && modal.classList.contains('is-active')) closeModal();
  });
})();
</script>
<x-achievement-toasts />
<x-footer />
</body>
</html>
