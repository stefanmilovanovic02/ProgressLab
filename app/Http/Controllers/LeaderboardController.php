<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\ExperienceService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class LeaderboardController extends Controller
{
    public function index()
    {
        $exercises = DB::table('exercises')
            ->orderBy('name')
            ->get(['id', 'name']);

        return view('leaderboards.index', compact('exercises'));
    }

    public function data(Request $request, ExperienceService $experienceService)
    {
        $validated = $request->validate([
            'scope' => ['required', Rule::in(['friends', 'global'])],
            'metric' => ['required', Rule::in(['login', 'active', 'ranked', 'exercise'])],
            'exercise_mode' => [
                'nullable',
                Rule::in(['weight', 'ranked']),
            ],
            'exercise_id' => [
                'nullable',
                'required_if:metric,exercise',
                'integer',
                Rule::exists('exercises', 'id'),
            ],
        ]);

        $scope = $validated['scope'];
        $metric = $validated['metric'];
        $exerciseMode = $validated['exercise_mode'] ?? 'weight';
        $auth = $request->user();

        $friendIds = $scope === 'friends'
            ? $auth->friends()->pluck('users.id')
            : collect();

        $users = User::query()
            ->when($scope === 'friends', fn ($query) => $query->whereIn('id', $friendIds))
            ->get(['id', 'name', 'full_name', 'username', 'avatar_path']);

        if ($users->isEmpty()) {
            return response()->json([
                'rows' => [],
                'meta' => $this->meta($scope, $metric, null, $exerciseMode),
            ]);
        }

        $rows = match ($metric) {
            'active' => $this->activeRows($users, $auth->id),
            'ranked' => $this->accountRankRows($users, $auth->id, $experienceService),
            'exercise' => $this->exerciseRows(
                $users,
                (int) $validated['exercise_id'],
                $auth->id,
                $exerciseMode
            ),
            default => $this->loginRows($users, $auth->id),
        };

        $exerciseName = $metric === 'exercise'
            ? DB::table('exercises')->where('id', $validated['exercise_id'])->value('name')
            : null;

        return response()->json([
            'rows' => $this->rank($rows),
            'meta' => $this->meta($scope, $metric, $exerciseName, $exerciseMode),
        ]);
    }

    private function accountRankRows(
        Collection $users,
        int $authId,
        ExperienceService $experienceService
    ): Collection {
        $totals = DB::table('experience_events')
            ->whereIn('user_id', $users->pluck('id'))
            ->selectRaw('user_id, SUM(points) as total_xp')
            ->groupBy('user_id')
            ->pluck('total_xp', 'user_id');

        return $users->map(function (User $user) use ($totals, $authId, $experienceService) {
            $totalXp = (int) ($totals[$user->id] ?? 0);
            $progress = $experienceService->progressForXp($totalXp);

            return $this->baseRow($user, $authId) + [
                '_score' => $totalXp,
                'value' => $progress['rank'] . ' ' . $this->romanLevel($progress['level']),
                'detail' => number_format($totalXp) . ' total XP',
                'badge_url' => asset('images/ranks/' . $progress['rank_slug'] . '.png'),
                'badge_color' => $progress['color'],
            ];
        });
    }

    private function loginRows(Collection $users, int $authId): Collection
    {
        $today = now()->toDateString();
        $datesByUser = DB::table('login_logs')
            ->whereIn('user_id', $users->pluck('id'))
            ->whereDate('login_date', '<=', $today)
            ->orderByDesc('login_date')
            ->get(['user_id', 'login_date'])
            ->groupBy('user_id');

        return $users->map(function (User $user) use ($datesByUser, $authId) {
            $dates = collect($datesByUser[$user->id] ?? [])->pluck('login_date');
            $streak = $this->consecutiveStreak($dates);

            return $this->baseRow($user, $authId) + [
                '_score' => $streak,
                'value' => $streak . ' ' . ($streak === 1 ? 'day' : 'days'),
                'detail' => 'Login streak',
            ];
        });
    }

    private function activeRows(Collection $users, int $authId): Collection
    {
        $activity = DB::table('login_logs')
            ->whereIn('user_id', $users->pluck('id'))
            ->select('user_id', DB::raw('MAX(updated_at) as last_active'))
            ->groupBy('user_id')
            ->pluck('last_active', 'user_id');

        return $users->map(function (User $user) use ($activity, $authId) {
            $lastActive = $activity[$user->id] ?? null;

            return $this->baseRow($user, $authId) + [
                '_score' => $lastActive ? Carbon::parse($lastActive)->timestamp : 0,
                'value' => $lastActive ? $this->relativeActivity($lastActive) : 'Never active',
                'detail' => $lastActive ? $this->presence($lastActive) : 'Offline',
            ];
        });
    }

    private function exerciseRows(
        Collection $users,
        int $exerciseId,
        int $authId,
        string $mode
    ): Collection
    {
        if ($mode === 'ranked') {
            return $this->exerciseRankRows($users, $exerciseId, $authId);
        }

        $weights = DB::table('workout_log_sets as sets')
            ->join('workout_log_exercises as logged', 'logged.id', '=', 'sets.workout_log_exercise_id')
            ->join('workout_logs as logs', 'logs.id', '=', 'logged.workout_log_id')
            ->whereIn('logs.user_id', $users->pluck('id'))
            ->where('logged.exercise_id', $exerciseId)
            ->whereNotNull('sets.weight_kg')
            ->groupBy('logs.user_id')
            ->selectRaw('logs.user_id as user_id, MAX(sets.weight_kg) as best_weight')
            ->pluck('best_weight', 'user_id');

        return $users
            ->filter(fn (User $user) => isset($weights[$user->id]))
            ->map(function (User $user) use ($weights, $authId) {
                $weight = (float) $weights[$user->id];
                $formatted = rtrim(rtrim(number_format($weight, 2, '.', ''), '0'), '.');

                return $this->baseRow($user, $authId) + [
                    '_score' => $weight,
                    'value' => $formatted . ' kg',
                    'detail' => 'Highest logged weight',
                ];
            });
    }

    private function exerciseRankRows(Collection $users, int $exerciseId, int $authId): Collection
    {
        $records = DB::table('user_exercise_ranks')
            ->whereIn('user_id', $users->pluck('id'))
            ->where('exercise_id', $exerciseId)
            ->get(['user_id', 'score', 'rank'])
            ->keyBy('user_id');

        return $users
            ->filter(fn (User $user) => $records->has($user->id))
            ->map(function (User $user) use ($records, $authId) {
                $record = $records->get($user->id);
                $score = round((float) $record->score, 1);
                $rankSlug = strtolower((string) $record->rank);

                return $this->baseRow($user, $authId) + [
                    '_score' => $score,
                    'value' => (string) $record->rank,
                    'detail' => $score . ' / 100 strength score',
                    'badge_url' => asset('images/ranks/' . $rankSlug . '.png'),
                    'badge_color' => $this->rankColor((string) $record->rank),
                ];
            });
    }

    private function rank(Collection $rows): array
    {
        return $rows
            ->sort(function (array $first, array $second) {
                $scoreComparison = $second['_score'] <=> $first['_score'];

                return $scoreComparison !== 0
                    ? $scoreComparison
                    : strcasecmp($first['name'], $second['name']);
            })
            ->values()
            ->map(function (array $row, int $index) {
                unset($row['_score']);
                $row['rank'] = $index + 1;

                return $row;
            })
            ->all();
    }

    private function baseRow(User $user, int $authId): array
    {
        return [
            'name' => $user->full_name ?? $user->name ?? 'ProgressLab User',
            'username' => $user->username ?? '',
            'avatar_url' => $user->avatar_url,
            'is_you' => $user->id === $authId,
        ];
    }

    private function consecutiveStreak(Collection $dates): int
    {
        $dateSet = $dates
            ->map(fn ($date) => Carbon::parse($date)->toDateString())
            ->unique()
            ->flip();
        $cursor = now()->startOfDay();

        if (!$dateSet->has($cursor->toDateString())) {
            $cursor->subDay();
            if (!$dateSet->has($cursor->toDateString())) {
                return 0;
            }
        }

        $streak = 0;
        while ($dateSet->has($cursor->toDateString())) {
            $streak++;
            $cursor->subDay();
        }

        return $streak;
    }

    private function presence(string $lastActive): string
    {
        $seconds = (int) Carbon::parse($lastActive)->diffInSeconds(now(), true);

        return match (true) {
            $seconds <= 300 => 'Online',
            $seconds <= 7200 => 'Recently active',
            default => 'Offline',
        };
    }

    private function relativeActivity(string $lastActive): string
    {
        $time = Carbon::parse($lastActive);
        $seconds = (int) $time->diffInSeconds(now(), true);

        if ($seconds <= 15) return 'Just now';
        if ($seconds < 60) return $seconds . ' seconds ago';

        $minutes = intdiv($seconds, 60);
        if ($minutes < 60) return $minutes . ' minute' . ($minutes === 1 ? '' : 's') . ' ago';

        $hours = intdiv($minutes, 60);
        if ($hours < 24) return $hours . ' hour' . ($hours === 1 ? '' : 's') . ' ago';

        $days = intdiv($hours, 24);
        return $days . ' day' . ($days === 1 ? '' : 's') . ' ago';
    }

    private function meta(
        string $scope,
        string $metric,
        ?string $exerciseName,
        string $exerciseMode = 'weight'
    ): array
    {
        return [
            'scope' => $scope,
            'metric' => $metric,
            'exercise_name' => $exerciseName,
            'exercise_mode' => $exerciseMode,
        ];
    }

    private function romanLevel(int $level): string
    {
        return [1 => 'I', 2 => 'II', 3 => 'III', 4 => 'IV'][$level] ?? 'I';
    }

    private function rankColor(string $rank): string
    {
        return match (strtolower($rank)) {
            'silver' => '#bfc7d5',
            'gold' => '#f6c945',
            'platinum' => '#65e6d4',
            'diamond' => '#62b7ff',
            'master' => '#a875ff',
            'titan' => '#ef5b78',
            'olympian' => '#fff2a8',
            default => '#b87333',
        };
    }
}
