<!doctype html>
<html lang="en">
<head>
    <x-seo
        title="Home Dashboard"
        description="Track today's nutrition, workouts, streaks, achievements, and weekly fitness progress in your ProgressLab dashboard."
        robots="noindex, nofollow, noarchive"
        :load-numeric-inputs="false"
        :load-password-toggle="false"
    />
    <link rel="stylesheet" href="{{ asset('css/home.min.css') }}?v={{ filemtime(public_path('css/home.min.css')) }}">
    <link rel="stylesheet" href="{{ asset('css/ranked-xp.min.css') }}?v={{ filemtime(public_path('css/ranked-xp.min.css')) }}">
</head>
<body class="auth-body">

<x-navbar />

<main class="hm-wrap">
    <h1 class="sr-only">Your ProgressLab fitness dashboard</h1>
    <div class="hm-grid">

        {{-- LEFT --}}
    <aside class="hm-left">
        <section class="hm-card hm-profile">
            <div class="hm-profile__top">
                <div class="hm-profile__avatar">
                    <img src="{{ $profile['avatar_url'] }}" alt="{{ $profile['name'] }}" width="58" height="58" decoding="async" fetchpriority="high">
                </div>
                <div>
                    <h2 class="hm-profile__name">{{ $profile['name'] }}</h2>
                    <div class="hm-profile__meta">Member since {{ $profile['member_since'] }}</div>
                </div>
            </div>

            <div class="hm-streak">
                <div class="hm-streak__icon">🔥</div>
                <div>
                    <div class="hm-streak__title">{{ $profile['streak'] }} Day Streak</div>
                    <div class="hm-streak__sub">
                        {{ $profile['streak'] > 0 ? 'Keep it up!' : 'Start your streak today!' }}
                    </div>
                </div>
            </div>
        </section>

        <section class="hm-card hm-motivation">
            <h3 class="hm-section-title">Daily Motivation</h3>
            <p class="hm-motivation__quote">"{{ $motivation['quote'] }}"</p>

            <div class="hm-progress">
                <div class="hm-progress__bar" style="width: {{ $motivation['progress'] }}%;"></div>
            </div>

            <div class="hm-motivation__sub">{{ $motivation['subtext'] }}</div>
        </section>

        <section
            class="hm-card rank-card rank-card--{{ $rankProgress['rank_slug'] }}"
            style="--rank-color: {{ $rankProgress['color'] }}; --rank-next-color: {{ $rankProgress['next_color'] }};"
            role="button"
            tabindex="0"
            aria-haspopup="dialog"
            aria-controls="rankOverviewDialog"
            aria-expanded="false"
            data-rank-open
        >
            <div class="rank-card__head">
                <div class="rank-card__badge">
                    <img
                        src="{{ asset('images/ranks/thumbs/' . $rankProgress['rank_slug'] . '.png') }}"
                        alt="{{ $rankProgress['rank'] }} rank badge"
                        width="82"
                        height="82"
                        decoding="async"
                    >
                </div>
                <div class="rank-card__identity">
                    <div class="rank-card__eyebrow">Your Rank</div>
                    <h3 class="rank-card__name">{{ $rankProgress['rank'] }}</h3>
                    <div class="rank-card__level">Level {{ $rankProgress['level'] }} / {{ $rankProgress['level_count'] }}</div>
                </div>
                <div class="rank-card__total">
                    <strong>{{ number_format($rankProgress['total_xp']) }}</strong>
                    <span>Total XP</span>
                </div>
            </div>

            <div class="rank-card__progress-head">
                <span>{{ $rankProgress['is_max'] ? 'Maximum rank reached' : 'Progress to ' . $rankProgress['next_label'] }}</span>
                <span>{{ $rankProgress['percent'] }}%</span>
            </div>
            <div
                class="rank-card__track"
                role="progressbar"
                aria-label="Rank progress"
                aria-valuemin="0"
                aria-valuemax="{{ $rankProgress['required_xp'] }}"
                aria-valuenow="{{ $rankProgress['level_xp'] }}"
            >
                <div class="rank-card__fill" style="width: {{ $rankProgress['percent'] }}%;"></div>
            </div>
            <div class="rank-card__xp">
                @if($rankProgress['is_max'])
                    Olympian IV complete
                @else
                    {{ number_format($rankProgress['level_xp']) }} / {{ number_format($rankProgress['required_xp']) }} XP
                @endif
            </div>
            <div class="rank-card__view">View all ranks</div>
        </section>
    </aside>    

        {{-- MIDDLE --}}
        <section class="hm-middle">
            <section class="hm-card">
                <h2 class="hm-block-title">Today’s Nutrition</h2>

                <div class="hm-nutrition-grid">
                    @foreach($nutrition as $item)
                        <a href="{{ url('/add-today') }}" class="hm-nutri-link">
                            <div class="hm-nutri-card">
                                <div class="hm-nutri-card__top">
                                    <div>
                                        <div class="hm-nutri-card__label">{{ $item['label'] }}</div>
                                    </div>
                                    <div class="hm-nutri-card__icon {{ $item['class'] }}">{{ $item['icon'] }}</div>
                                </div>

                                <div class="hm-nutri-card__value">
                                    {{ $item['value'] }} <span>/ {{ $item['target'] }} {{ $item['unit'] }}</span>
                                </div>

                                <div class="hm-nutri-card__percent">{{ $item['percent'] }}% complete</div>

                                <div class="hm-progress hm-progress--small">
                                    <div class="hm-progress__bar {{ $item['class'] }}" style="width: {{ $item['percent'] }}%;"></div>
                                </div>
                            </div>
                        </a>
                    @endforeach
                </div>
            </section>

            <section class="hm-card hm-workout">
                <a href="{{ route('add-today') }}#workout-selection" class="hm-workout__link">
                    <div class="hm-workout__head">
                        <h2 class="hm-block-title">Today’s Workout</h2>

                        @if($todayWorkout)
                            <div class="hm-workout__meta">
                                <span>{{ $todayWorkout['date'] }}</span>
                            </div>
                        @endif
                    </div>

                    @if($todayWorkout)
                        <div class="hm-workout__topline">
                            <div class="hm-workout__name">{{ $todayWorkout['name'] }}</div>
                        </div>

                        <div class="hm-workout__list">
                            @foreach($todayWorkout['exercises'] as $exercise)
                                <div class="hm-exercise">
                                    <h4 class="hm-exercise__name">{{ $exercise['name'] }}</h4>

                                    <div class="hm-set-grid">
                                        @foreach($exercise['sets'] as $index => $set)
                                            <div class="hm-set">
                                                <div class="hm-set__label">Set {{ $index + 1 }}</div>
                                                <div class="hm-set__value">{{ $set }}</div>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div class="hm-workout__empty">
                            No workout logged for today yet.
                        </div>
                    @endif
                </a>
            </section>

            <section class="hm-card hm-graph">
                <a href="{{ route('charts.index') }}" class="hm-graph__link">
                    <div class="hm-graph__head">
                        <div>
                            <h2 class="hm-block-title">Weekly Progress</h2>
                        </div>
                        <div class="hm-graph__more">View Full Charts ↗</div>
                    </div>

                    @php
                        $homeChartValues = collect($weeklyProgress['values'])->map(fn ($value) => (float) $value)->values();
                        $homeChartMax = max(1, (float) $homeChartValues->max());
                        $homeChartPoints = $homeChartValues->map(function ($value, $index) use ($homeChartMax) {
                            $x = 34 + ($index * (632 / 6));
                            $y = 18 + ((1 - ($value / $homeChartMax)) * 150);
                            return number_format($x, 1, '.', '') . ',' . number_format($y, 1, '.', '');
                        })->implode(' ');
                    @endphp
                    <div class="hm-graph__canvasWrap">
                        <svg class="hm-weekly-chart" viewBox="0 0 700 210" role="img" aria-labelledby="homeWeeklyChartTitle homeWeeklyChartDesc" preserveAspectRatio="none">
                            <title id="homeWeeklyChartTitle">Weekly workout volume</title>
                            <desc id="homeWeeklyChartDesc">Workout volume for Monday through Sunday in {{ $weeklyProgress['weight_unit'] }}.</desc>
                            @foreach([18, 68, 118, 168] as $gridY)
                                <line class="hm-weekly-chart__grid" x1="34" y1="{{ $gridY }}" x2="666" y2="{{ $gridY }}" />
                            @endforeach
                            <polyline class="hm-weekly-chart__line" points="{{ $homeChartPoints }}" />
                            @foreach($homeChartValues as $index => $value)
                                @php
                                    $pointX = 34 + ($index * (632 / 6));
                                    $pointY = 18 + ((1 - ($value / $homeChartMax)) * 150);
                                @endphp
                                <circle class="hm-weekly-chart__point" cx="{{ number_format($pointX, 1, '.', '') }}" cy="{{ number_format($pointY, 1, '.', '') }}" r="4" />
                                <text class="hm-weekly-chart__label" x="{{ number_format($pointX, 1, '.', '') }}" y="198" text-anchor="middle">{{ $weeklyProgress['labels'][$index] }}</text>
                            @endforeach
                        </svg>
                    </div>

                    <div class="hm-graph__stats">
                        <div class="hm-graph__stat">
                            <div class="hm-graph__statValue">{{ number_format($weeklyProgress['display_total_volume'] / 1000, 1) }}k</div>
                            <div class="hm-graph__statLabel">Total Volume ({{ $weeklyProgress['weight_unit'] }})</div>
                        </div>

                        <div class="hm-graph__stat">
                            <div class="hm-graph__statValue">{{ $weeklyProgress['workouts'] }}</div>
                            <div class="hm-graph__statLabel">Workouts</div>
                        </div>

                        <div class="hm-graph__stat">
                            <div class="hm-graph__statValue {{ $weeklyProgress['vs_last_week'] >= 0 ? 'is-positive' : 'is-negative' }}">
                                {{ $weeklyProgress['vs_last_week'] >= 0 ? '+' : '' }}{{ $weeklyProgress['vs_last_week'] }}%
                            </div>
                            <div class="hm-graph__statLabel">vs Last Week</div>
                        </div>
                    </div>
                </a>
            </section>
        </section>

        {{-- RIGHT --}}
        <aside class="hm-right">
            <section class="hm-card">
                <a href="{{ route('notifications.index') }}" class="hm-graph__link">
                <h2 class="hm-block-title">Recent Activity</h2>

                <div class="hm-activity-list">
                    @forelse($friendsActivity as $activity)
                        <div class="hm-activity">
                            <div class="hm-activity__avatar">
                                <img src="{{ $activity['avatar'] ?? asset('images/default-avatar.png') }}" alt="" width="42" height="42" loading="lazy" decoding="async">
                            </div>
                            <div class="hm-activity__body">
                                <div class="hm-activity__text">
                                    {{ $activity['text'] }}
                                </div>
                                <div class="hm-activity__time">{{ $activity['time'] }}</div>
                            </div>
                            <div class="hm-activity__badge">{{ $activity['icon'] }}</div>
                        </div>
                    @empty
                        <div class="hm-workout__empty">
                            No recent activity yet.
                        </div>
                    @endforelse
                </div>

                <span class="hm-btn">View All Notifications</span>
                </a>
            </section>

            <section class="hm-card">
            <div class="hm-workout__head">
                <h2 class="hm-block-title">Latest Achievements</h2>
            </div>

            <div class="hm-ach-list">
                @forelse($recentAchievements as $achievement)
                    <div class="hm-ach">
                        <div class="hm-ach__thumb">
                            <img src="{{ $achievement['image'] }}" alt="" width="46" height="46" loading="lazy" decoding="async">
                        </div>

                        <div class="hm-ach__body">
                            <div class="hm-ach__title">{{ $achievement['title'] }}</div>
                            <div class="hm-ach__desc">{{ $achievement['desc'] }}</div>
                            <div class="hm-ach__time">{{ $achievement['unlocked_at'] }}</div>
                        </div>

                        <div class="hm-ach__rarity hm-ach__rarity--{{ $achievement['rarity'] }}">
                            {{ ucfirst($achievement['rarity']) }}
                        </div>
                    </div>
                @empty
                    <div class="hm-workout__empty">
                        No achievements unlocked yet.
                    </div>
                @endforelse
            </div>
        </section>
        </aside>

    </div>
</main>

<div class="rank-modal" data-rank-modal hidden>
    <section
        id="rankOverviewDialog"
        class="rank-modal__panel"
        role="dialog"
        aria-modal="true"
        aria-labelledby="rankOverviewTitle"
    >
        <header class="rank-modal__head">
            <div>
                <span class="rank-modal__eyebrow">ProgressLab ranking</span>
                <h2 id="rankOverviewTitle">All ranks</h2>
                <p>Every rank contains four levels. Earn XP to move through them.</p>
            </div>
            <button class="rank-modal__close" type="button" aria-label="Close rank overview" data-rank-close>&times;</button>
        </header>

        <div class="rank-modal__current">
            <span>Current rank</span>
            <strong style="--item-rank-color: {{ $rankProgress['color'] }}">{{ $rankProgress['rank'] }} {{ ['I', 'II', 'III', 'IV'][$rankProgress['level'] - 1] }}</strong>
            <small>{{ number_format($rankProgress['total_xp']) }} total XP</small>
        </div>

        <div class="rank-modal__grid">
            @foreach($rankCatalog as $rank)
                <article
                    class="rank-modal__item {{ $rank['index'] === $rankProgress['rank_index'] ? 'is-current' : '' }} {{ $rank['index'] < $rankProgress['rank_index'] ? 'is-complete' : '' }}"
                    style="--item-rank-color: {{ $rank['color'] }}"
                >
                    <img src="{{ asset('images/ranks/thumbs/' . $rank['slug'] . '.png') }}" alt="" width="86" height="86" loading="lazy" decoding="async">
                    <div>
                        <strong>{{ $rank['name'] }}</strong>
                        <span>Levels I–IV</span>
                        <small>
                            {{ $rank['starting_xp'] === 0 ? 'Starting rank' : number_format($rank['starting_xp']) . ' XP to enter' }}
                        </small>
                    </div>
                    @if($rank['index'] === $rankProgress['rank_index'])
                        <b>Current</b>
                    @elseif($rank['index'] < $rankProgress['rank_index'])
                        <b>Complete</b>
                    @endif
                </article>
            @endforeach
        </div>
    </section>
</div>

    <script>
    (() => {
        const trigger = document.querySelector('[data-rank-open]');
        const modal = document.querySelector('[data-rank-modal]');
        const closeButton = modal?.querySelector('[data-rank-close]');
        if (!trigger || !modal || !closeButton) return;

        const open = () => {
            modal.hidden = false;
            document.body.classList.add('rank-modal-open');
            trigger.setAttribute('aria-expanded', 'true');
            requestAnimationFrame(() => modal.classList.add('is-open'));
            closeButton.focus();
        };

        const close = () => {
            modal.classList.remove('is-open');
            document.body.classList.remove('rank-modal-open');
            trigger.setAttribute('aria-expanded', 'false');
            modal.hidden = true;
            trigger.focus();
        };

        trigger.addEventListener('click', open);
        trigger.addEventListener('keydown', event => {
            if (event.key === 'Enter' || event.key === ' ') {
                event.preventDefault();
                open();
            }
        });
        closeButton.addEventListener('click', close);
        modal.addEventListener('click', event => {
            if (event.target === modal) close();
        });
        document.addEventListener('keydown', event => {
            if (event.key === 'Escape' && !modal.hidden) close();
        });
    })();
    </script>
<x-achievement-toasts />
<x-footer />
</body>
</html>
