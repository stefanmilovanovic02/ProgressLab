<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class AdminUserStatsService
{
    public function build(User $user): array
    {
        $rank = app(ExperienceService::class)->progress($user);
        $start = now()->subDays(29)->startOfDay();

        $nutrition = DB::table('nutrition_entries')
            ->where('user_id', $user->id)
            ->whereDate('entry_date', '>=', $start->toDateString())
            ->orderBy('entry_date')
            ->get(['entry_date', 'calories', 'protein_g', 'carbs_g', 'fat_g']);

        $volumes = DB::table('workout_logs as logs')
            ->leftJoin('workout_log_exercises as logged', 'logged.workout_log_id', '=', 'logs.id')
            ->leftJoin('workout_log_sets as sets', 'sets.workout_log_exercise_id', '=', 'logged.id')
            ->where('logs.user_id', $user->id)
            ->whereDate('logs.entry_date', '>=', $start->toDateString())
            ->groupBy('logs.entry_date')
            ->selectRaw('logs.entry_date, SUM(COALESCE(sets.reps, 0) * COALESCE(sets.weight_kg, 0)) as volume')
            ->pluck('volume', 'entry_date');

        $nutritionByDate = $nutrition->keyBy(
            fn ($entry) => Carbon::parse($entry->entry_date)->toDateString()
        );
        $labels = [];
        $calories = [];
        $protein = [];
        $workoutVolume = [];

        for ($day = 0; $day < 30; $day++) {
            $date = $start->copy()->addDays($day);
            $key = $date->toDateString();
            $entry = $nutritionByDate->get($key);

            $labels[] = $date->format('M j');
            $calories[] = (int) ($entry->calories ?? 0);
            $protein[] = (int) ($entry->protein_g ?? 0);
            $workoutVolume[] = round((float) ($volumes[$key] ?? 0), 1);
        }

        $loginDates = DB::table('login_logs')
            ->where('user_id', $user->id)
            ->orderByDesc('login_date')
            ->pluck('login_date');
        $workoutDates = DB::table('workout_logs')
            ->where('user_id', $user->id)
            ->orderByDesc('entry_date')
            ->pluck('entry_date');
        $nutritionDates = DB::table('nutrition_entries')
            ->where('user_id', $user->id)
            ->orderByDesc('entry_date')
            ->pluck('entry_date');

        return [
            'summary' => [
                'workouts' => DB::table('workout_logs')->where('user_id', $user->id)->count(),
                'nutrition_days' => DB::table('nutrition_entries')->where('user_id', $user->id)->count(),
                'achievements' => DB::table('user_achievements')->where('user_id', $user->id)->whereNotNull('unlocked_at')->count(),
                'friends' => DB::table('friends')->where('user_id', $user->id)->count(),
                'total_xp' => $rank['total_xp'],
                'rank' => $rank,
            ],
            'streaks' => [
                'login' => $this->consecutiveStreak($loginDates),
                'workout' => $this->consecutiveStreak($workoutDates),
                'nutrition' => $this->consecutiveStreak($nutritionDates),
            ],
            'charts' => compact('labels', 'calories', 'protein', 'workoutVolume'),
            'recentNutrition' => DB::table('nutrition_entries')
                ->where('user_id', $user->id)
                ->latest('entry_date')
                ->limit(8)
                ->get(['entry_date', 'calories', 'protein_g', 'carbs_g', 'fat_g', 'water_ml']),
            'recentWorkouts' => DB::table('workout_logs as logs')
                ->leftJoin('workouts', 'workouts.id', '=', 'logs.workout_id')
                ->where('logs.user_id', $user->id)
                ->latest('logs.entry_date')
                ->limit(8)
                ->get([
                    'logs.entry_date',
                    'logs.duration_seconds',
                    'logs.completed_at',
                    'workouts.name',
                ]),
            'recentAchievements' => DB::table('user_achievements as unlocked')
                ->join('achievements', 'achievements.id', '=', 'unlocked.achievement_id')
                ->where('unlocked.user_id', $user->id)
                ->whereNotNull('unlocked.unlocked_at')
                ->latest('unlocked.unlocked_at')
                ->limit(8)
                ->get(['achievements.title', 'achievements.rarity', 'unlocked.unlocked_at']),
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
}
