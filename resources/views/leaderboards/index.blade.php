<!doctype html>
<html lang="en">
<head>
  <x-seo
    title="Fitness Leaderboards"
    description="Compare login streaks, recent activity, and exercise strength with friends and the ProgressLab community."
    robots="noindex, nofollow, noarchive"
  />
  <link rel="stylesheet" href="{{ asset('css/auth.css') }}">
  <link rel="stylesheet" href="{{ asset('css/leaderboards.css') }}">
</head>
<body class="auth-body lb-body">
  <x-navbar />

  <main class="pl-container lb-wrap" data-leaderboard data-endpoint="{{ route('leaderboards.data') }}" data-friends-url="{{ route('friends.index') }}">
    <header class="lb-head">
      <div class="lb-head__icon" aria-hidden="true">🏆</div>
      <div>
        <h1>Leaderboards</h1>
        <p>See who is building the strongest habits and making the biggest lifts.</p>
      </div>
    </header>

    <section class="lb-card" aria-labelledby="leaderboardTitle">
      <div class="lb-scope" role="tablist" aria-label="Leaderboard group">
        <button class="lb-scope__tab is-active" type="button" role="tab" aria-selected="true" data-scope="friends">
          <span aria-hidden="true">👥</span> Friends
        </button>
        <button class="lb-scope__tab" type="button" role="tab" aria-selected="false" data-scope="global">
          <span aria-hidden="true">🌍</span> Global
        </button>
      </div>

      <div class="lb-toolbar">
        <div>
          <span class="lb-label">Rank by</span>
          <div class="lb-metrics" aria-label="Ranking type">
            <button class="lb-filter is-active" type="button" aria-pressed="true" data-metric="login">Login streak</button>
            <button class="lb-filter" type="button" aria-pressed="false" data-metric="active">Last online</button>
            <button class="lb-filter" type="button" aria-pressed="false" data-metric="ranked">Account rank</button>
            <button class="lb-filter" type="button" aria-pressed="false" data-metric="exercise">Exercise strength</button>
          </div>
        </div>

        <div class="lb-exercise-controls" data-exercise-filter hidden>
          <label class="lb-exercise">
            <span class="lb-label">Exercise</span>
            <select data-exercise-select>
              @foreach($exercises as $exercise)
                <option value="{{ $exercise->id }}">{{ $exercise->name }}</option>
              @endforeach
            </select>
          </label>
          <div>
            <span class="lb-label">Compare by</span>
            <div class="lb-mode" aria-label="Exercise comparison type">
              <button class="lb-mode__button is-active" type="button" aria-pressed="true" data-exercise-mode="weight">Weight</button>
              <button class="lb-mode__button" type="button" aria-pressed="false" data-exercise-mode="ranked">Rank</button>
            </div>
          </div>
        </div>
      </div>

      <div class="lb-titlebar">
        <div>
          <span class="lb-titlebar__eyebrow" data-scope-label>Friends leaderboard</span>
          <h2 id="leaderboardTitle" data-title>Login streak</h2>
        </div>
        <span class="lb-count" data-count aria-live="polite"></span>
      </div>

      <div class="lb-list" data-list aria-live="polite" aria-busy="true">
        <div class="lb-loading"><span></span><span></span><span></span><b>Loading leaderboard…</b></div>
      </div>
    </section>
  </main>

  <x-footer />

  <script>
    (() => {
      const root = document.querySelector('[data-leaderboard]');
      if (!root) return;

      const list = root.querySelector('[data-list]');
      const count = root.querySelector('[data-count]');
      const title = root.querySelector('[data-title]');
      const scopeLabel = root.querySelector('[data-scope-label]');
      const exerciseWrap = root.querySelector('[data-exercise-filter]');
      const exerciseSelect = root.querySelector('[data-exercise-select]');
      const state = { scope: 'friends', metric: 'login', exerciseMode: 'weight' };
      let activeRequest;

      const text = (tag, className, value) => {
        const node = document.createElement(tag);
        if (className) node.className = className;
        node.textContent = value;
        return node;
      };

      const showLoading = () => {
        list.replaceChildren();
        const loader = text('div', 'lb-loading', 'Loading leaderboard…');
        list.append(loader);
        list.setAttribute('aria-busy', 'true');
      };

      const emptyState = () => {
        const rankedExercise = state.metric === 'exercise' && state.exerciseMode === 'ranked';
        const empty = document.createElement('div');
        empty.className = 'lb-empty';
        empty.append(text('div', 'lb-empty__icon', state.metric === 'exercise' || state.metric === 'ranked' ? '🏋️' : '🏆'));
        empty.append(text('h3', '', state.metric === 'exercise' ? 'No strength records yet' : state.scope === 'friends' ? 'No friends to rank yet' : 'No leaderboard activity yet'));
        empty.append(text(
          'p',
          '',
          state.metric === 'exercise'
            ? rankedExercise
              ? 'Only people who earned a rank for this exercise appear here.'
              : 'Only people who logged weight for this exercise appear here.'
            : state.scope === 'friends'
              ? 'Add friends to start your private leaderboard.'
              : 'Check back after the community logs some activity.'
        ));
        if (state.scope === 'friends' && state.metric !== 'exercise') {
          const link = text('a', 'lb-empty__action', 'Find friends');
          link.href = root.dataset.friendsUrl;
          empty.append(link);
        }
        list.replaceChildren(empty);
      };

      const renderRow = row => {
        const item = document.createElement('article');
        item.className = `lb-row${row.rank <= 3 ? ` lb-row--top lb-row--${row.rank}` : ''}`;

        const rank = text('div', 'lb-rank', row.rank <= 3 ? ['🥇', '🥈', '🥉'][row.rank - 1] : row.rank);
        rank.setAttribute('aria-label', `Rank ${row.rank}`);

        const avatar = document.createElement('img');
        avatar.className = 'lb-avatar';
        avatar.src = row.avatar_url;
        avatar.alt = '';
        avatar.loading = 'lazy';

        const identity = document.createElement('div');
        identity.className = 'lb-person';
        const nameLine = document.createElement('div');
        nameLine.className = 'lb-person__name';
        nameLine.append(document.createTextNode(row.name));
        if (row.is_you) nameLine.append(text('span', 'lb-you', 'You'));
        identity.append(nameLine);
        if (row.username) identity.append(text('span', 'lb-person__username', `@${row.username}`));

        const result = document.createElement('div');
        result.className = 'lb-result';
        if (row.badge_url) {
          const badge = document.createElement('img');
          badge.className = 'lb-result__badge';
          badge.src = row.badge_url;
          badge.alt = '';
          badge.width = 54;
          badge.height = 54;
          badge.style.setProperty('--leaderboard-rank-color', row.badge_color);
          result.append(badge);
        }
        const resultCopy = document.createElement('div');
        resultCopy.className = 'lb-result__copy';
        resultCopy.append(text('strong', '', row.value));
        resultCopy.append(text('span', '', row.detail));
        result.append(resultCopy);

        item.append(rank, avatar, identity, result);
        return item;
      };

      const load = async () => {
        activeRequest?.abort();
        activeRequest = new AbortController();
        showLoading();

        const params = new URLSearchParams({ scope: state.scope, metric: state.metric });
        if (state.metric === 'exercise' && exerciseSelect.value) {
          params.set('exercise_id', exerciseSelect.value);
          params.set('exercise_mode', state.exerciseMode);
        }

        try {
          const response = await fetch(`${root.dataset.endpoint}?${params}`, {
            headers: { Accept: 'application/json' },
            signal: activeRequest.signal
          });
          if (!response.ok) throw new Error('Unable to load leaderboard');
          const data = await response.json();

          scopeLabel.textContent = `${state.scope === 'friends' ? 'Friends' : 'Global'} leaderboard`;
          title.textContent = state.metric === 'login'
            ? 'Login streak'
            : state.metric === 'active'
              ? 'Last online'
              : state.metric === 'ranked'
                ? 'Account rank'
                : `${data.meta.exercise_name} · ${state.exerciseMode === 'ranked' ? 'Rank' : 'Weight'}`;
          count.textContent = `${data.rows.length} ${data.rows.length === 1 ? 'person' : 'people'}`;
          list.setAttribute('aria-busy', 'false');
          if (!data.rows.length) return emptyState();
          list.replaceChildren(...data.rows.map(renderRow));
        } catch (error) {
          if (error.name === 'AbortError') return;
          list.setAttribute('aria-busy', 'false');
          const retry = text('button', 'lb-empty__action', 'Try again');
          retry.type = 'button';
          retry.addEventListener('click', load);
          const errorBox = document.createElement('div');
          errorBox.className = 'lb-empty';
          errorBox.append(text('div', 'lb-empty__icon', '⚠️'), text('h3', '', 'Could not load the leaderboard'), retry);
          list.replaceChildren(errorBox);
        }
      };

      root.querySelectorAll('[data-scope]').forEach(button => button.addEventListener('click', () => {
        state.scope = button.dataset.scope;
        root.querySelectorAll('[data-scope]').forEach(item => {
          const selected = item === button;
          item.classList.toggle('is-active', selected);
          item.setAttribute('aria-selected', selected ? 'true' : 'false');
        });
        load();
      }));

      root.querySelectorAll('[data-metric]').forEach(button => button.addEventListener('click', () => {
        state.metric = button.dataset.metric;
        root.querySelectorAll('[data-metric]').forEach(item => {
          const selected = item === button;
          item.classList.toggle('is-active', selected);
          item.setAttribute('aria-pressed', selected ? 'true' : 'false');
        });
        exerciseWrap.hidden = state.metric !== 'exercise';
        if (state.metric === 'exercise' && !exerciseSelect.value) return emptyState();
        load();
      }));

      exerciseSelect.addEventListener('change', load);
      root.querySelectorAll('[data-exercise-mode]').forEach(button => button.addEventListener('click', () => {
        state.exerciseMode = button.dataset.exerciseMode;
        root.querySelectorAll('[data-exercise-mode]').forEach(item => {
          const selected = item === button;
          item.classList.toggle('is-active', selected);
          item.setAttribute('aria-pressed', selected ? 'true' : 'false');
        });
        load();
      }));
      load();
    })();
  </script>
</body>
</html>
