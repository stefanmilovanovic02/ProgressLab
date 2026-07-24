<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use App\Models\NutritionEntry;
use App\Models\FriendActivity;
use App\Models\UserAchievement;
use App\Services\ExperienceService;

class HomeController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        // profile logic
        $profile = [
            'name' => $user->full_name ?? $user->name ?? 'User Name',
            'member_since' => $user->created_at ? $user->created_at->format('M Y') : '—',
            'avatar_url' => $this->publicImageUrl($user->avatar_path),
            'streak' => $this->loginStreak($user->id),
        ];
        //quote logic
        $motivation = $this->motivationProgress($profile['streak']);
        $rankProgress = app(ExperienceService::class)->progress($user);
        // nutrition logic
        $todayEntry = \App\Models\NutritionEntry::query()
            ->where('user_id', $user->id)
            ->whereDate('entry_date', now()->toDateString())
            ->first();

        $goal = $user->nutritionGoal;

        $nutrition = [
            $this->nutritionCard(
                label: 'Calories',
                value: (int) ($todayEntry->calories ?? 0),
                target: (int) ($goal->calorie_target ?? 0),
                unit: 'kcal',
                icon: '🔥',
                colorClass: 'is-calories'
            ),
            $this->nutritionCard(
                label: 'Protein',
                value: (int) ($todayEntry->protein_g ?? 0),
                target: (int) ($goal->protein_g ?? 0),
                unit: 'g',
                icon: '🥩',
                colorClass: 'is-protein'
            ),
            $this->nutritionCard(
                label: 'Carbohydrates',
                value: (int) ($todayEntry->carbs_g ?? 0),
                target: (int) ($goal->carbs_g ?? 0),
                unit: 'g',
                icon: '🍚',
                colorClass: 'is-carbs'
            ),
            $this->nutritionCard(
                label: 'Fat',
                value: (int) ($todayEntry->fat_g ?? 0),
                target: (int) ($goal->fat_g ?? 0),
                unit: 'g',
                icon: '🥜',
                colorClass: 'is-fat'
            ),
            $this->nutritionCard(
                label: 'Creatine',
                value: (int) ($todayEntry->creatine_g ?? 0),
                target: (int) ($goal->creatine_g ?? 0),
                unit: 'g',
                icon: '🧬',
                colorClass: 'is-creatine'
            ),
            $this->nutritionCard(
                label: 'Water',
                value: round(((int) ($todayEntry->water_ml ?? 0)) / 1000, 1),
                target: (float) ($goal->water_l ?? 0),
                unit: 'L',
                icon: '💧',
                colorClass: 'is-water'
            ),
        ];
        // workout logic
        $todayLog = DB::table('workout_logs as wl')
            ->join('workouts as w', 'w.id', '=', 'wl.workout_id')
            ->where('wl.user_id', $user->id)
            ->whereDate('wl.entry_date', now()->toDateString())
            ->select(
                'wl.id as workout_log_id',
                'wl.entry_date',
                'w.name as workout_name'
            )
            ->latest('wl.id')
            ->first();

        $todayWorkout = null;

        if ($todayLog) {
            $exerciseRows = DB::table('workout_log_exercises as wle')
                ->join('exercises as e', 'e.id', '=', 'wle.exercise_id')
                ->where('wle.workout_log_id', $todayLog->workout_log_id)
                ->select(
                    'wle.id as workout_log_exercise_id',
                    'e.name as exercise_name'
                )
                ->get();

            $exerciseIds = $exerciseRows->pluck('workout_log_exercise_id');

            $setRows = DB::table('workout_log_sets')
                ->whereIn('workout_log_exercise_id', $exerciseIds)
                ->orderBy('set_number')
                ->get([
                    'workout_log_exercise_id',
                    'set_number',
                    'reps',
                    'weight_kg',
                ])
                ->groupBy('workout_log_exercise_id');

            $todayWorkout = [
                'name' => $todayLog->workout_name,
                'date' => Carbon::parse($todayLog->entry_date)->format('F j, Y'),
                'exercises' => $exerciseRows->map(function ($exercise) use ($setRows) {
                    $sets = collect($setRows[$exercise->workout_log_exercise_id] ?? [])->map(function ($set) {
                        $reps = $set->reps ?? 0;
                        $weight = $set->weight_kg ?? 0;
                        return "{$reps} × {$weight}kg";
                    })->values()->all();

                    return [
                        'name' => $exercise->exercise_name,
                        'sets' => $sets,
                    ];
                })->values()->all(),
            ];
        }
        
        // graph logic
        $weekStart = now()->startOfWeek(Carbon::MONDAY);
        $weekEnd = now()->endOfWeek(Carbon::SUNDAY);

        $volumeRows = DB::table('workout_log_sets as s')
            ->join('workout_log_exercises as e', 'e.id', '=', 's.workout_log_exercise_id')
            ->join('workout_logs as l', 'l.id', '=', 'e.workout_log_id')
            ->where('l.user_id', $user->id)
            ->whereDate('l.entry_date', '>=', $weekStart->toDateString())
            ->whereDate('l.entry_date', '<=', $weekEnd->toDateString())
            ->selectRaw('date(l.entry_date) as day')
            ->selectRaw('SUM(COALESCE(s.reps, 0) * COALESCE(s.weight_kg, 0)) as total_volume')
            ->groupByRaw('date(l.entry_date)')
            ->pluck('total_volume', 'day');

        $labels = [];
        $values = [];
        $weeklyTotalVolume = 0;

        for ($i = 0; $i < 7; $i++) {
            $date = $weekStart->copy()->addDays($i);
            $dayKey = $date->toDateString();
            $volume = (float) ($volumeRows[$dayKey] ?? 0);

            $labels[] = $date->format('D');
            $values[] = round($volume, 1);
            $weeklyTotalVolume += $volume;
        }

        $workoutsThisWeek = DB::table('workout_logs')
            ->where('user_id', $user->id)
            ->whereDate('entry_date', '>=', $weekStart->toDateString())
            ->whereDate('entry_date', '<=', $weekEnd->toDateString())
            ->count();

        $lastWeekStart = now()->subWeek()->startOfWeek(Carbon::MONDAY);
        $lastWeekEnd = now()->subWeek()->endOfWeek(Carbon::SUNDAY);

        $lastWeekTotalVolume = (float) (
            DB::table('workout_log_sets as s')
                ->join('workout_log_exercises as e', 'e.id', '=', 's.workout_log_exercise_id')
                ->join('workout_logs as l', 'l.id', '=', 'e.workout_log_id')
                ->where('l.user_id', $user->id)
                ->whereDate('l.entry_date', '>=', $lastWeekStart->toDateString())
                ->whereDate('l.entry_date', '<=', $lastWeekEnd->toDateString())
                ->selectRaw('SUM(COALESCE(s.reps, 0) * COALESCE(s.weight_kg, 0)) as total_volume')
                ->value('total_volume') ?? 0
        );

        $vsLastWeek = 0;
        if ($lastWeekTotalVolume > 0) {
            $vsLastWeek = round((($weeklyTotalVolume - $lastWeekTotalVolume) / $lastWeekTotalVolume) * 100);
        }

        $weeklyProgress = [
            'labels' => $labels,
            'values' => $values,
            'total_volume' => round($weeklyTotalVolume, 1),
            'workouts' => $workoutsThisWeek,
            'vs_last_week' => $vsLastWeek,
        ];

        // Friend Logic
        $friendIds = DB::table('friends')
            ->where('user_id', $user->id)
            ->pluck('friend_id');

        $friendsActivity = FriendActivity::query()
            ->with('user:id,full_name,name,avatar_path')
            ->whereIn('user_id', $friendIds)
            ->latest()
            ->limit(6)
            ->get()
            ->map(function ($activity) {
                $friendName = $activity->user?->full_name ?? $activity->user?->name ?? 'Friend';

                return [
                    'text' => $friendName . ' ' . $activity->text,
                    'time' => $activity->created_at ? $activity->created_at->diffForHumans() : '',
                    'icon' => match ($activity->type) {
                        'achievement' => '🏆',
                        'workout' => '🏋️',
                        'nutrition' => '🍽️',
                        'streak' => '🔥',
                        default => '✨',
                    },
                ];
            })
            ->values();

            // Achievements Logic
        $recentAchievements = UserAchievement::query()
            ->join('achievements as a', 'a.id', '=', 'user_achievements.achievement_id')
            ->where('user_achievements.user_id', $user->id)
            ->whereNotNull('user_achievements.unlocked_at')
            ->orderByDesc('user_achievements.unlocked_at')
            ->limit(3)
            ->get([
                'a.title',
                'a.description',
                'a.image_path',
                'a.rarity',
                'user_achievements.unlocked_at',
            ])
            ->map(function ($achievement) {
                return [
                    'title' => $achievement->title,
                    'desc' => $achievement->description,
                    'image' => $achievement->image_path
                        ? asset($achievement->image_path)
                        : asset('images/achievements/default.png'),
                    'rarity' => $achievement->rarity,
                    'unlocked_at' => $achievement->unlocked_at
                        ? Carbon::parse($achievement->unlocked_at)->diffForHumans()
                        : '',
                ];
            })
            ->values();

        return view('home.index', compact(
            'profile',
            'motivation',
            'nutrition',
            'todayWorkout',
            'friendsActivity',
            'recentAchievements',
            'weeklyProgress',
            'rankProgress'
        ));
        
    }

    private function publicImageUrl(?string $path): string
    {
        if (!$path) {
            return asset('images/default-avatar.png');
        }

        if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
            return $path;
        }

        if (str_starts_with($path, 'storage/')) {
            return asset($path);
        }

        if (str_starts_with($path, '/storage/')) {
            return asset(ltrim($path, '/'));
        }

        return asset('storage/' . ltrim($path, '/'));
    }

    private function loginStreak(int $userId): int
    {
        $today = Carbon::today();

        $loginDates = DB::table('login_logs')
            ->where('user_id', $userId)
            ->whereDate('login_date', '<=', $today->toDateString())
            ->orderBy('login_date', 'desc')
            ->pluck('login_date');

        $loginSet = [];
        foreach ($loginDates as $d) {
            $loginSet[Carbon::parse($d)->toDateString()] = true;
        }

        $count = 0;
        $cursor = $today->copy();

        while (isset($loginSet[$cursor->toDateString()])) {
            $count++;
            $cursor->subDay();
        }

        return $count;
    }

    private function motivationProgress(int $streak): array
    {
        if ($streak === 0) {
            return [
                'quote' => 'Start tracking today and build your streak.',
                'progress' => 0,
                'subtext' => 'Day 0 of 100 · Start your first cycle today',
            ];
        }

        $completedCycles = intdiv($streak, 100);
        $cycleDay = $streak % 100;

        if ($cycleDay === 0) {
            return [
                'quote' => 'You completed 100 days. You did it — now do it again.',
                'progress' => 100,
                'subtext' => 'Cycle ' . $completedCycles . ' complete · 100 of 100 days',
            ];
        }

        $cycle = $completedCycles + 1;

        return [
            'quote' => $this->motivationMessage($cycleDay, $completedCycles),
            'progress' => $cycleDay,
            'subtext' => 'Cycle ' . $cycle . ' · Day ' . $cycleDay . ' of 100',
        ];
    }

    private function motivationMessage(int $cycleDay, int $completedCycles): string
    {
        if ($completedCycles > 0 && $cycleDay === 1) {
            return 'You did it. Now do it again — your next 100 days start today.';
        }

        return match (true) {
            $cycleDay >= 75 => 'The finish line is close. Keep showing up.',
            $cycleDay >= 50 => 'Halfway through this cycle. Stay locked in.',
            $cycleDay >= 30 => 'A full month of consistency. Keep pushing.',
            $cycleDay >= 14 => 'Two weeks strong. Momentum is building.',
            $cycleDay >= 7 => 'One week down. Great discipline.',
            $cycleDay >= 1 => 'You showed up today. Keep it going.',
            default => 'Start tracking today and build your streak.',
        };
    }

    // Nutrition helper
        private function nutritionCard(
        string $label,
        int|float $value,
        int|float $target,
        string $unit,
        string $icon,
        string $colorClass
    ): array {
        $percent = 0;

        if ($target > 0) {
            $percent = min(100, (int) round(($value / $target) * 100));
        }

        $remaining = max(0, $target - $value);

        return [
            'label' => $label,
            'value' => $value,
            'target' => $target,
            'remaining' => $remaining,
            'unit' => $unit,
            'percent' => $percent,
            'icon' => $icon,
            'class' => $colorClass,
        ];
    }
}
