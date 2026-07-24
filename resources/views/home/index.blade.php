<!doctype html>
<html lang="en">
<head>
    <x-seo
        title="Home Dashboard"
        description="Track today's nutrition, workouts, streaks, achievements, and weekly fitness progress in your ProgressLab dashboard."
        robots="noindex, nofollow, noarchive"
    />
    <link rel="stylesheet" href="{{ asset('css/auth.css') }}">
    <link rel="stylesheet" href="{{ asset('css/ranked-xp.css') }}">
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
</head>
<body class="auth-body">

<x-navbar />

<main class="hm-wrap">
    <div class="hm-grid">

        {{-- LEFT --}}
    <aside class="hm-left">
        <section class="hm-card hm-profile">
            <div class="hm-profile__top">
                <div class="hm-profile__avatar">
                    <img src="{{ $profile['avatar_url'] }}" alt="{{ $profile['name'] }}">
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
        >
            <div class="rank-card__head">
                <div class="rank-card__badge">
                    <img
                        src="{{ asset('images/ranks/' . $rankProgress['rank_slug'] . '.png') }}"
                        alt="{{ $rankProgress['rank'] }} rank badge"
                        width="82"
                        height="82"
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
                <a href="{{ url('/add-today') }}" class="hm-workout__link">
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

                    <div class="hm-graph__canvasWrap">
                        <canvas id="homeWeeklyChart" height="120"></canvas>
                    </div>

                    <div class="hm-graph__stats">
                        <div class="hm-graph__stat">
                            <div class="hm-graph__statValue">{{ number_format($weeklyProgress['total_volume'] / 1000, 1) }}k</div>
                            <div class="hm-graph__statLabel">Total Volume</div>
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
                <h2 class="hm-block-title">Friends Activity</h2>

                <div class="hm-activity-list">
                    @forelse($friendsActivity as $activity)
                        <div class="hm-activity">
                            <div class="hm-activity__avatar">
                                <img src="{{ $activity['avatar'] ?? asset('images/default-avatar.png') }}" alt="user">
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
                            No friend activity yet.
                        </div>
                    @endforelse
                </div>

                <span class="hm-btn">View All Activity</span>
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
                            <img src="{{ $achievement['image'] }}" alt="{{ $achievement['title'] }}">
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

    <script>
    (function () {
        const canvas = document.getElementById('homeWeeklyChart');
        if (!canvas) return;

        const labels = @json($weeklyProgress['labels']);
        const values = @json($weeklyProgress['values']);

        new Chart(canvas, {
            type: 'line',
            data: {
                labels: labels,
                datasets: [{
                    data: values,
                    borderColor: '#00d084',
                    backgroundColor: 'transparent',
                    pointBackgroundColor: '#00d084',
                    pointBorderColor: '#00d084',
                    pointRadius: 4,
                    pointHoverRadius: 5,
                    borderWidth: 2.5,
                    tension: 0.4,
                    fill: false,
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        enabled: true,
                        callbacks: {
                            label: function(context) {
                                return 'Volume: ' + context.raw + ' kg';
                            }
                        }
                    }
                },
                scales: {
                    x: {
                        ticks: { color: 'rgba(255,255,255,.72)' },
                        grid: { color: 'rgba(255,255,255,.08)' }
                    },
                    y: {
                        beginAtZero: true,
                        ticks: { color: 'rgba(255,255,255,.72)' },
                        grid: { color: 'rgba(255,255,255,.08)' }
                    }
                }
            }
        });
    })();
    </script>
<x-achievement-toasts />
<x-footer />
</body>
</html>
