<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Workouts • GymTracker</title>

  <link rel="stylesheet" href="{{ asset('css/auth.css') }}">
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
          $preview = $workout->exercises->take(3);
          $moreCount = max(0, $exerciseCount - $preview->count());
        @endphp

        <article class="wo-card">
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
            <div class="wo-label">Exercises</div>

            <ul class="wo-list" role="list">
              @foreach($preview as $ex)
                <li class="wo-row">
                  <span class="wo-row__name">{{ $ex->name }}</span>
                  <span class="wo-chip">{{ $ex->muscle_group ?? '—' }}</span>
                </li>
              @endforeach
            </ul>

            @if($moreCount > 0)
              <div class="wo-more">+{{ $moreCount }} more {{ $moreCount === 1 ? 'exercise' : 'exercises' }}</div>
            @endif
          </div>

          <footer class="wo-card__foot">
            <div class="wo-footline"></div>
            <div class="wo-total">
              <span class="wo-total__label">Total Exercises</span>
              <span class="wo-total__value">{{ $exerciseCount }}</span>
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
  <div class="wo-modal" data-create-modal>
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
          <input class="wo-input" name="name" type="text" placeholder="e.g., Chest & Triceps" required>
        </div>

        <div class="wo-exhead">
          <div class="wo-label2">Exercises</div>
          <button class="wo-addbtn" type="button" id="addExerciseRow">＋ Add Exercise</button>
        </div>

        <div class="wo-exlist" id="exerciseRows"></div>

        <div class="wo-modal__actions">
          <button class="pl-btn pl-btn--ghost" type="button" data-close-create>Cancel</button>
          <button class="pl-btn pl-btn--light" type="submit">Save Workout</button>
        </div>
      </form>
    </div>
  </div>

 <script>
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
  }
  function closeModal() {
    modal.classList.remove('is-active');
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
          <div class="wo-suggest" hidden></div>
        </div>

        <select class="wo-input wo-select" disabled>
          <option value="">Select muscle group (optional)</option>
        </select>
      </div>

      <button class="wo-trash" type="button" aria-label="Remove">🗑️</button>
    `;

    const textInput = row.querySelector('.wo-exinput');
    const hiddenId  = row.querySelector('.wo-exid');
    const suggest   = row.querySelector('.wo-suggest');
    const select    = row.querySelector('.wo-select');
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
      select.innerHTML = `<option value="">Select muscle group (optional)</option>`;
      select.disabled = true;
    }

    function showSuggest(items) {
      suggest.innerHTML = '';

      if (!items.length) {
        suggest.hidden = true;
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

          select.innerHTML = `
            <option value="">Select muscle group (optional)</option>
            <option selected>${item.muscle_group ?? '—'}</option>
          `;
          select.disabled = true;

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
    const select    = row.querySelector('.wo-select');
    const suggest   = row.querySelector('.wo-suggest');

    textInput.value = ex.name || '';
    hiddenId.value  = ex.id || '';

    select.innerHTML = `
      <option value="">Select muscle group (optional)</option>
      <option selected>${ex.muscle_group ?? '—'}</option>
    `;
    select.disabled = true;

    if (suggest) suggest.hidden = true;
  }

  addRowBtn.addEventListener('click', () => addRow());

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

})();
</script>

</body>
</html>