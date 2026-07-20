<?php

namespace App\Services;

use App\Models\Achievement;
use App\Models\NutritionEntry;
use App\Models\UserAchievement;
use App\Models\Workout;
use App\Models\WorkoutLog;
use App\Models\FriendActivity;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AchievementService
{
    public function evaluate($user): array
    {
        if (!$user) {
            return [];
        }

        $newlyUnlocked = [];

        $achievements = Achievement::query()
            ->where('is_active', true)
            ->get();

        foreach ($achievements as $achievement) {
            $ua = UserAchievement::where('user_id', $user->id)
                ->where('achievement_id', $achievement->id)
                ->first();

            if ($ua && $ua->unlocked_at) {
                continue;
            }

            if ($this->meetsCriteria($user, $achievement)) {
                $unlock = UserAchievement::updateOrCreate(
                    [
                        'user_id' => $user->id,
                        'achievement_id' => $achievement->id,
                    ],
                    [
                        'unlocked_at' => now(),
                        'notified_at' => null,
                    ]
                );

                $activity = FriendActivity::create([
                    'user_id' => $user->id,
                    'type' => 'achievement',
                    'text' => 'unlocked the achievement "' .$achievement->title . '".',
                ]);

                app(NotificationService::class)->notifyAchievementUnlocked(
                    $user,
                    $unlock,
                    $achievement,
                    $activity
                );

                $newlyUnlocked[] = [
                    'id' => $achievement->id,
                    'title' => $achievement->title,
                    'description' => $achievement->description,
                    'rarity' => $achievement->rarity,
                    'image_path' => $achievement->image_path ? asset($achievement->image_path) : asset('images/achievements/default.png'),
                ];
            }
        }

        return $newlyUnlocked;
    }

    private function meetsCriteria($user, Achievement $achievement): bool
    {
        $criteria = $achievement->criteria ?? null;

        if (!$criteria || empty($criteria['metric'])) {
            return false;
        }

        $metric = (string) $criteria['metric'];
        $target = (int) ($criteria['target'] ?? 1);
        $tolerance = (float) ($criteria['tolerance'] ?? 10);

        return match ($metric) {
            // basics
            'account_created' => true,
            'profile_complete' => $this->profileComplete($user),
            'bmr_tdee_generated' => $this->bmrTdeeGenerated($user),
            'nutrition_goals_set' => $this->nutritionGoalsSet($user),
            'workouts_created' => $this->workoutsCreated($user->id) >= $target,
            'workout_plan_edited' => $this->workoutPlanEdited($user->id),
            'structured_workout_plans_followed' => $this->structuredWorkoutPlansFollowed($user->id) >= $target,

            // totals
            'nutrition_days' => $this->nutritionDays($user->id) >= $target,
            'workout_days' => $this->workoutDays($user->id) >= $target,
            'app_days_total' => $this->appDaysTotal($user->id) >= $target,
            'activity_days_total' => $this->activityDaysTotal($user->id) >= $target,
            'active_months_total' => $this->activeMonthsTotal($user->id) >= $target,
            'nutrition_active_months_total' => $this->nutritionActiveMonthsTotal($user->id) >= $target,
            'total_sets_lifetime' => $this->totalSetsLifetime($user->id) >= $target,
            'unique_exercises_added' => $this->uniqueExercisesAdded($user->id) >= $target,
            'water_days' => $this->waterDays($user->id) >= $target,
            'protein_goal_total_days' => $this->proteinGoalTotalDays($user) >= $target,
            'calorie_target_total_days' => $this->calorieTargetTotalDays($user, $tolerance) >= $target,
            'achievements_unlock_percent' => $this->achievementUnlockPercent($user->id) >= $target,

            // streaks
            'login_streak' => $this->loginStreak($user->id) >= $target,
            'workout_streak' => $this->workoutStreak($user->id) >= $target,
            'nutrition_streak' => $this->nutritionStreak($user->id) >= $target,
            'activity_streak' => $this->activityStreak($user->id) >= $target,
            'protein_goal_streak' => $this->proteinGoalStreak($user) >= $target,
            'cut_streak' => $this->cutStreak($user) >= $target,
            'bulk_streak' => $this->bulkStreak($user) >= $target,
            'under_calorie_limit_streak' => $this->underCalorieLimitStreak($user) >= $target,
            'all_macro_targets_streak' => $this->allMacroTargetsStreak($user, $target, $tolerance) >= $target,
            'calorie_precision_streak' => $this->caloriePrecisionStreak($user, $tolerance) >= $target,
            'active_year_month_streak' => $this->activeMonthStreak($user->id) >= $target,

            // day conditions
            'protein_goal_met' => $this->macroGoalMetToday($user, 'protein_g'),
            'carbs_goal_met' => $this->macroGoalMetToday($user, 'carbs_g'),
            'fat_goal_met' => $this->macroGoalMetToday($user, 'fat_g'),
            'calories_goal_met' => $this->macroGoalMetToday($user, 'calories'),
            'water_goal_met' => $this->macroGoalMetToday($user, 'water_ml'),
            'creatine_goal_met' => $this->macroGoalMetToday($user, 'creatine_g'),
            'bulk_day' => $this->bulkDay($user),
            'cut_day' => $this->cutDay($user),
            'under_calorie_limit_day' => $this->underCalorieLimitDay($user),

            // precision / macro consistency
            'macro_balanced_day' => $this->macroBalancedDay($user, 10),
            'calorie_precision_days' => $this->caloriePrecisionDays($user, $tolerance) >= $target,
            'calorie_precision_days_in_month' => $this->maxCaloriePrecisionDaysInAnyMonth($user, $tolerance) >= $target,
            'all_macro_targets_times' => $this->allMacroTargetsTimes($user, 10) >= $target,
            'macro_split_correct_days' => $this->macroSplitCorrectDays($user, 10) >= $target,

            // mixed tracking
            'workout_and_nutrition_same_day' => $this->workoutAndNutritionSameDayCount($user->id) >= $target,
            'workout_and_nutrition_same_week' => $this->workoutAndNutritionSameWeekCount($user->id) >= $target,
            'dual_tracker_totals' => $this->workoutDays($user->id) >= (int) ($criteria['workout_target'] ?? 10)
                && $this->nutritionDays($user->id) >= (int) ($criteria['nutrition_target'] ?? 10),

            // workout frequency
            'workouts_in_week' => $this->maxWorkoutsInAnyWeek($user->id) >= $target,
            'workouts_in_month' => $this->maxWorkoutsInAnyMonth($user->id) >= $target,
            'weeks_with_two_workouts' => $this->consecutiveWeeksWithAtLeastNWorkouts($user->id, 2) >= $target,
            'weeks_with_three_workouts' => $this->consecutiveWeeksWithAtLeastNWorkouts($user->id, 3) >= $target,
            'weeks_with_four_workouts' => $this->consecutiveWeeksWithAtLeastNWorkouts($user->id, 4) >= $target,

            // cardio only
            'cardio_session_logged' => $this->cardioSessionsTotal($user->id) >= $target,
            'cardio_sessions_in_week' => $this->maxCardioSessionsInAnyWeek($user->id) >= $target,
            'cardio_sessions_total' => $this->cardioSessionsTotal($user->id) >= $target,

            // strength / progression
            'exercise_weight_increase' => $this->exerciseImprovementEvents($user->id) >= $target,
            'exercise_weight_increase_distinct' => $this->distinctExercisesImproved($user->id) >= $target,
            'same_exercise_improvements' => $this->exerciseImprovementEvents($user->id) >= $target,
            'personal_records_logged' => $this->exerciseImprovementEvents($user->id) >= $target,
            'workout_with_exercise_count' => $this->hasWorkoutWithExerciseCount($user->id, $target),
            'same_workout_plan_weeks' => $this->sameWorkoutPlanWeeks($user->id) >= $target,

            // analytics / events
            'progress_graph_views' => $this->eventCount($user->id, 'progress_graph_viewed') >= $target,
            'goal_updated_and_tracked_days' => $this->goalUpdatedAndTrackedDays($user) >= $target,

            default => false,
        };
    }

    // ---------------------------
    // Basics
    // ---------------------------

    private function profileComplete($user): bool
    {
        $metric = $user->metric;
        $goal = $user->nutritionGoal;

        return !empty($user->full_name ?? $user->name)
            && !empty($user->username)
            && !empty($user->email)
            && !empty($user->avatar_path)
            && $metric
            && !empty($metric->height_cm)
            && !empty($metric->weight_kg)
            && $goal
            && !empty($goal->goal)
            && !empty($goal->calorie_target)
            && !empty($goal->protein_g)
            && !empty($goal->fat_g)
            && !empty($goal->carbs_g);
    }

    private function bmrTdeeGenerated($user): bool
    {
        $metric = $user->metric;
        return $metric && (float) ($metric->bmr ?? 0) > 0 && (float) ($metric->tdee ?? 0) > 0;
    }

    private function nutritionGoalsSet($user): bool
    {
        $goal = $user->nutritionGoal;
        return $goal
            && !empty($goal->goal)
            && (int) ($goal->calorie_target ?? 0) > 0
            && (int) ($goal->protein_g ?? 0) > 0
            && (int) ($goal->fat_g ?? 0) > 0
            && (int) ($goal->carbs_g ?? 0) > 0;
    }

    private function workoutsCreated(int $userId): int
    {
        if (!Schema::hasTable('workouts')) {
            return 0;
        }

        return Workout::where('user_id', $userId)->count();
    }

    private function workoutPlanEdited(int $userId): bool
    {
        if (!Schema::hasTable('workouts')) {
            return false;
        }

        return DB::table('workouts')
            ->where('user_id', $userId)
            ->whereColumn('updated_at', '>', 'created_at')
            ->exists();
    }

    private function structuredWorkoutPlansFollowed(int $userId): int
    {
        if (!Schema::hasTable('workouts') || !Schema::hasTable('workout_logs')) {
            return 0;
        }

        return DB::table('workout_logs as wl')
            ->join('workouts as w', 'w.id', '=', 'wl.workout_id')
            ->where('wl.user_id', $userId)
            ->where('w.user_id', $userId)
            ->distinct()
            ->count('wl.workout_id');
    }

    // ---------------------------
    // Totals
    // ---------------------------

    private function nutritionDays(int $userId): int
    {
        return NutritionEntry::where('user_id', $userId)
            ->where(function ($q) {
                $q->where('calories', '>', 0)
                    ->orWhere('protein_g', '>', 0)
                    ->orWhere('carbs_g', '>', 0)
                    ->orWhere('fat_g', '>', 0)
                    ->orWhere('creatine_g', '>', 0)
                    ->orWhere('water_ml', '>', 0);
            })
            ->get()
            ->pluck('entry_date')
            ->map(fn ($d) => Carbon::parse($d)->toDateString())
            ->unique()
            ->count();
    }

    private function workoutDays(int $userId): int
    {
        if (!Schema::hasTable('workout_logs')) {
            return 0;
        }

        return WorkoutLog::where('user_id', $userId)
            ->get()
            ->pluck('entry_date')
            ->map(fn ($d) => Carbon::parse($d)->toDateString())
            ->unique()
            ->count();
    }

    private function appDaysTotal(int $userId): int
    {
        if (!Schema::hasTable('login_logs')) {
            return 0;
        }

        return DB::table('login_logs')
            ->where('user_id', $userId)
            ->pluck('login_date')
            ->map(fn ($d) => Carbon::parse($d)->toDateString())
            ->unique()
            ->count();
    }

    private function activityDaysTotal(int $userId): int
    {
        return $this->activityDates($userId)->count();
    }

    private function activeMonthsTotal(int $userId): int
    {
        return $this->activityDates($userId)
            ->map(fn ($d) => Carbon::parse($d)->format('Y-m'))
            ->unique()
            ->count();
    }

    private function nutritionActiveMonthsTotal(int $userId): int
    {
        return NutritionEntry::where('user_id', $userId)
            ->where(function ($q) {
                $q->where('calories', '>', 0)
                    ->orWhere('protein_g', '>', 0)
                    ->orWhere('carbs_g', '>', 0)
                    ->orWhere('fat_g', '>', 0)
                    ->orWhere('creatine_g', '>', 0)
                    ->orWhere('water_ml', '>', 0);
            })
            ->get()
            ->pluck('entry_date')
            ->map(fn ($d) => Carbon::parse($d)->format('Y-m'))
            ->unique()
            ->count();
    }

    private function totalSetsLifetime(int $userId): int
    {
        if (
            !Schema::hasTable('workout_log_sets') ||
            !Schema::hasTable('workout_log_exercises') ||
            !Schema::hasTable('workout_logs')
        ) {
            return 0;
        }

        return DB::table('workout_log_sets as s')
            ->join('workout_log_exercises as e', 'e.id', '=', 's.workout_log_exercise_id')
            ->join('workout_logs as l', 'l.id', '=', 'e.workout_log_id')
            ->where('l.user_id', $userId)
            ->count();
    }

    private function uniqueExercisesAdded(int $userId): int
    {
        if (!Schema::hasTable('exercise_workout') || !Schema::hasTable('workouts')) {
            return 0;
        }

        return DB::table('exercise_workout as ew')
            ->join('workouts as w', 'w.id', '=', 'ew.workout_id')
            ->where('w.user_id', $userId)
            ->distinct()
            ->count('ew.exercise_id');
    }

    private function waterDays(int $userId): int
    {
        if (!Schema::hasTable('nutrition_entries')) {
            return 0;
        }

        return DB::table('nutrition_entries')
            ->where('user_id', $userId)
            ->where('water_ml', '>', 0)
            ->get()
            ->pluck('entry_date')
            ->map(fn ($d) => Carbon::parse($d)->toDateString())
            ->unique()
            ->count();
    }

    private function proteinGoalTotalDays($user): int
    {
        $goal = $user->nutritionGoal;
        if (!$goal || (int) ($goal->protein_g ?? 0) <= 0) {
            return 0;
        }

        return NutritionEntry::where('user_id', $user->id)
            ->where('protein_g', '>=', (int) $goal->protein_g)
            ->get()
            ->pluck('entry_date')
            ->map(fn ($d) => Carbon::parse($d)->toDateString())
            ->unique()
            ->count();
    }

    private function calorieTargetTotalDays($user, float $tolerance = 0): int
    {
        $goal = $user->nutritionGoal;
        if (!$goal || (int) ($goal->calorie_target ?? 0) <= 0) {
            return 0;
        }

        return NutritionEntry::where('user_id', $user->id)
            ->get()
            ->filter(fn ($entry) => $this->withinTolerance(
                (int) ($entry->calories ?? 0),
                (int) ($goal->calorie_target ?? 0),
                $tolerance
            ))
            ->pluck('entry_date')
            ->map(fn ($d) => Carbon::parse($d)->toDateString())
            ->unique()
            ->count();
    }

    private function achievementUnlockPercent(int $userId): float
    {
        $total = Achievement::where('is_active', true)->count();
        if ($total <= 0) {
            return 0;
        }

        $unlocked = UserAchievement::where('user_id', $userId)
            ->whereNotNull('unlocked_at')
            ->count();

        return ($unlocked / $total) * 100;
    }

    // ---------------------------
    // Streaks
    // ---------------------------

    private function loginStreak(int $userId): int
    {
        if (!Schema::hasTable('login_logs')) {
            return 0;
        }

        $dates = DB::table('login_logs')
            ->where('user_id', $userId)
            ->orderByDesc('login_date')
            ->pluck('login_date')
            ->all();

        return $this->consecutiveDaysStreak($dates);
    }

    private function workoutStreak(int $userId): int
    {
        if (!Schema::hasTable('workout_logs')) {
            return 0;
        }

        $dates = DB::table('workout_logs')
            ->where('user_id', $userId)
            ->orderByDesc('entry_date')
            ->pluck('entry_date')
            ->all();

        return $this->consecutiveDaysStreak($dates);
    }

    private function nutritionStreak(int $userId): int
    {
        if (!Schema::hasTable('nutrition_entries')) {
            return 0;
        }

        $dates = DB::table('nutrition_entries')
            ->where('user_id', $userId)
            ->where(function ($q) {
                $q->where('calories', '>', 0)
                    ->orWhere('protein_g', '>', 0)
                    ->orWhere('carbs_g', '>', 0)
                    ->orWhere('fat_g', '>', 0)
                    ->orWhere('creatine_g', '>', 0)
                    ->orWhere('water_ml', '>', 0);
            })
            ->orderByDesc('entry_date')
            ->pluck('entry_date')
            ->all();

        return $this->consecutiveDaysStreak($dates);
    }

    private function activityStreak(int $userId): int
    {
        return $this->consecutiveDaysStreak($this->activityDates($userId)->all());
    }

    private function proteinGoalStreak($user): int
    {
        $goal = $user->nutritionGoal;
        if (!$goal || (int) ($goal->protein_g ?? 0) <= 0) {
            return 0;
        }

        $dates = NutritionEntry::where('user_id', $user->id)
            ->where('protein_g', '>=', (int) $goal->protein_g)
            ->orderByDesc('entry_date')
            ->pluck('entry_date')
            ->all();

        return $this->consecutiveDaysStreak($dates);
    }

    private function cutStreak($user): int
    {
        $goal = $user->nutritionGoal;
        if (($goal->goal ?? null) !== 'cut' || (int) ($goal->calorie_target ?? 0) <= 0) {
            return 0;
        }

        $dates = NutritionEntry::where('user_id', $user->id)
            ->get()
            ->filter(fn ($e) => (int) ($e->calories ?? 0) > 0 && (int) $e->calories < (int) $goal->calorie_target)
            ->pluck('entry_date')
            ->all();

        return $this->consecutiveDaysStreak($dates);
    }

    private function bulkStreak($user): int
    {
        $goal = $user->nutritionGoal;
        if (($goal->goal ?? null) !== 'bulk' || (int) ($goal->calorie_target ?? 0) <= 0) {
            return 0;
        }

        $dates = NutritionEntry::where('user_id', $user->id)
            ->get()
            ->filter(fn ($e) => (int) ($e->calories ?? 0) > (int) $goal->calorie_target)
            ->pluck('entry_date')
            ->all();

        return $this->consecutiveDaysStreak($dates);
    }

    private function underCalorieLimitStreak($user): int
    {
        $goal = $user->nutritionGoal;
        if (!$goal || (int) ($goal->calorie_target ?? 0) <= 0) {
            return 0;
        }

        $dates = NutritionEntry::where('user_id', $user->id)
            ->where('calories', '>', 0)
            ->where('calories', '<=', (int) $goal->calorie_target)
            ->orderByDesc('entry_date')
            ->pluck('entry_date')
            ->all();

        return $this->consecutiveDaysStreak($dates);
    }

    private function allMacroTargetsStreak($user, int $target, float $tolerance): int
    {
        $dates = NutritionEntry::where('user_id', $user->id)
            ->get()
            ->filter(fn ($entry) => $this->isBalancedEntry($user, $entry, $tolerance, true))
            ->pluck('entry_date')
            ->all();

        return $this->consecutiveDaysStreak($dates);
    }

    private function caloriePrecisionStreak($user, float $tolerance): int
    {
        $goal = $user->nutritionGoal;
        if (!$goal || (int) ($goal->calorie_target ?? 0) <= 0) {
            return 0;
        }

        $dates = NutritionEntry::where('user_id', $user->id)
            ->get()
            ->filter(fn ($entry) => $this->withinTolerance(
                (int) ($entry->calories ?? 0),
                (int) ($goal->calorie_target ?? 0),
                $tolerance
            ))
            ->pluck('entry_date')
            ->all();

        return $this->consecutiveDaysStreak($dates);
    }

    private function activeMonthStreak(int $userId): int
    {
        $months = $this->activityDates($userId)
            ->map(fn ($d) => Carbon::parse($d)->format('Y-m'))
            ->unique()
            ->values();

        if ($months->isEmpty()) {
            return 0;
        }

        $streak = 0;
        $cursor = now()->startOfMonth();

        if (!$months->contains($cursor->format('Y-m'))) {
            $cursor->subMonth();
            if (!$months->contains($cursor->format('Y-m'))) {
                return 0;
            }
        }

        while ($months->contains($cursor->format('Y-m'))) {
            $streak++;
            $cursor->subMonth();
        }

        return $streak;
    }

    // ---------------------------
    // Daily conditions
    // ---------------------------

    private function macroGoalMetToday($user, string $field): bool
    {
        $today = Carbon::today()->toDateString();

        $entry = NutritionEntry::where('user_id', $user->id)
            ->whereDate('entry_date', $today)
            ->first();

        if (!$entry) {
            return false;
        }

        $goal = $user->nutritionGoal;
        if (!$goal) {
            return false;
        }

        $target = match ($field) {
            'calories' => (int) ($goal->calorie_target ?? 0),
            'protein_g' => (int) ($goal->protein_g ?? 0),
            'carbs_g' => (int) ($goal->carbs_g ?? 0),
            'fat_g' => (int) ($goal->fat_g ?? 0),
            'creatine_g' => (int) ($goal->creatine_g ?? 0),
            'water_ml' => $goal->water_l ? (int) round($goal->water_l * 1000) : 0,
            default => 0,
        };

        if ($target <= 0) {
            return false;
        }

        return (int) ($entry->{$field} ?? 0) >= $target;
    }

    private function bulkDay($user): bool
    {
        $goal = $user->nutritionGoal;
        if (($goal->goal ?? null) !== 'bulk') {
            return false;
        }

        $entry = $this->todayNutritionEntry($user->id);
        $target = (int) ($goal->calorie_target ?? 0);

        return $entry && $target > 0 && (int) $entry->calories > $target;
    }

    private function cutDay($user): bool
    {
        $goal = $user->nutritionGoal;
        if (($goal->goal ?? null) !== 'cut') {
            return false;
        }

        $entry = $this->todayNutritionEntry($user->id);
        $target = (int) ($goal->calorie_target ?? 0);

        return $entry && $target > 0 && (int) $entry->calories < $target;
    }

    private function underCalorieLimitDay($user): bool
    {
        $goal = $user->nutritionGoal;
        $entry = $this->todayNutritionEntry($user->id);
        $target = (int) ($goal->calorie_target ?? 0);

        return $entry && $target > 0 && (int) $entry->calories > 0 && (int) $entry->calories <= $target;
    }

    // ---------------------------
    // Precision / macro consistency
    // ---------------------------

    private function macroBalancedDay($user, float $tolerance = 10): bool
    {
        $today = Carbon::today()->toDateString();
        return $this->isBalancedDay($user, $today, $tolerance, true);
    }

    private function caloriePrecisionDays($user, float $tolerance): int
    {
        $goal = $user->nutritionGoal;
        if (!$goal || (int) ($goal->calorie_target ?? 0) <= 0) {
            return 0;
        }

        return NutritionEntry::where('user_id', $user->id)
            ->get()
            ->filter(fn ($entry) => $this->withinTolerance(
                (int) ($entry->calories ?? 0),
                (int) ($goal->calorie_target ?? 0),
                $tolerance
            ))
            ->pluck('entry_date')
            ->map(fn ($d) => Carbon::parse($d)->toDateString())
            ->unique()
            ->count();
    }

    private function maxCaloriePrecisionDaysInAnyMonth($user, float $tolerance): int
    {
        $goal = $user->nutritionGoal;
        if (!$goal || (int) ($goal->calorie_target ?? 0) <= 0) {
            return 0;
        }

        $months = NutritionEntry::where('user_id', $user->id)
            ->get()
            ->filter(fn ($entry) => $this->withinTolerance(
                (int) ($entry->calories ?? 0),
                (int) ($goal->calorie_target ?? 0),
                $tolerance
            ))
            ->groupBy(fn ($entry) => Carbon::parse($entry->entry_date)->format('Y-m'))
            ->map(function ($entries) {
                return $entries->pluck('entry_date')
                    ->map(fn ($d) => Carbon::parse($d)->toDateString())
                    ->unique()
                    ->count();
            });

        return $months->max() ?? 0;
    }

    private function allMacroTargetsTimes($user, float $tolerance = 10): int
    {
        return NutritionEntry::where('user_id', $user->id)
            ->get()
            ->filter(fn ($entry) => $this->isBalancedEntry($user, $entry, $tolerance, true))
            ->pluck('entry_date')
            ->map(fn ($d) => Carbon::parse($d)->toDateString())
            ->unique()
            ->count();
    }

    private function macroSplitCorrectDays($user, float $tolerance = 10): int
    {
        return NutritionEntry::where('user_id', $user->id)
            ->get()
            ->filter(fn ($entry) => $this->isBalancedEntry($user, $entry, $tolerance, false))
            ->pluck('entry_date')
            ->map(fn ($d) => Carbon::parse($d)->toDateString())
            ->unique()
            ->count();
    }

    // ---------------------------
    // Mixed tracking
    // ---------------------------

    private function workoutAndNutritionSameDayCount(int $userId): int
    {
        $workoutDays = Schema::hasTable('workout_logs')
            ? DB::table('workout_logs')->where('user_id', $userId)->pluck('entry_date')->map(fn ($d) => Carbon::parse($d)->toDateString())->unique()
            : collect();

        $nutritionDays = Schema::hasTable('nutrition_entries')
            ? DB::table('nutrition_entries')
                ->where('user_id', $userId)
                ->where(function ($q) {
                    $q->where('calories', '>', 0)
                        ->orWhere('protein_g', '>', 0)
                        ->orWhere('carbs_g', '>', 0)
                        ->orWhere('fat_g', '>', 0)
                        ->orWhere('creatine_g', '>', 0)
                        ->orWhere('water_ml', '>', 0);
                })
                ->pluck('entry_date')
                ->map(fn ($d) => Carbon::parse($d)->toDateString())
                ->unique()
            : collect();

        return $workoutDays->intersect($nutritionDays)->count();
    }

    private function workoutAndNutritionSameWeekCount(int $userId): int
    {
        $workoutWeeks = Schema::hasTable('workout_logs')
            ? DB::table('workout_logs')
                ->where('user_id', $userId)
                ->pluck('entry_date')
                ->map(fn ($d) => $this->yearWeek(Carbon::parse($d)))
                ->unique()
            : collect();

        $nutritionWeeks = Schema::hasTable('nutrition_entries')
            ? DB::table('nutrition_entries')
                ->where('user_id', $userId)
                ->where(function ($q) {
                    $q->where('calories', '>', 0)
                        ->orWhere('protein_g', '>', 0)
                        ->orWhere('carbs_g', '>', 0)
                        ->orWhere('fat_g', '>', 0)
                        ->orWhere('creatine_g', '>', 0)
                        ->orWhere('water_ml', '>', 0);
                })
                ->pluck('entry_date')
                ->map(fn ($d) => $this->yearWeek(Carbon::parse($d)))
                ->unique()
            : collect();

        return $workoutWeeks->intersect($nutritionWeeks)->count();
    }

    // ---------------------------
    // Workout frequency
    // ---------------------------

    private function maxWorkoutsInAnyWeek(int $userId): int
    {
        if (!Schema::hasTable('workout_logs')) {
            return 0;
        }

        $weeks = DB::table('workout_logs')
            ->where('user_id', $userId)
            ->pluck('entry_date')
            ->map(fn ($d) => $this->yearWeek(Carbon::parse($d)));

        return $weeks->isEmpty() ? 0 : max($weeks->countBy()->all());
    }

    private function maxWorkoutsInAnyMonth(int $userId): int
    {
        if (!Schema::hasTable('workout_logs')) {
            return 0;
        }

        $months = DB::table('workout_logs')
            ->where('user_id', $userId)
            ->pluck('entry_date')
            ->map(fn ($d) => Carbon::parse($d)->format('Y-m'));

        return $months->isEmpty() ? 0 : max($months->countBy()->all());
    }

    private function consecutiveWeeksWithAtLeastNWorkouts(int $userId, int $minimumPerWeek): int
    {
        if (!Schema::hasTable('workout_logs')) {
            return 0;
        }

        $weekCounts = DB::table('workout_logs')
            ->where('user_id', $userId)
            ->pluck('entry_date')
            ->map(fn ($d) => $this->yearWeek(Carbon::parse($d)))
            ->countBy();

        if ($weekCounts->isEmpty()) {
            return 0;
        }

        $streak = 0;
        $cursor = now()->startOfWeek(Carbon::MONDAY);

        if (($weekCounts[$this->yearWeek($cursor)] ?? 0) < $minimumPerWeek) {
            $cursor->subWeek();
            if (($weekCounts[$this->yearWeek($cursor)] ?? 0) < $minimumPerWeek) {
                return 0;
            }
        }

        while (($weekCounts[$this->yearWeek($cursor)] ?? 0) >= $minimumPerWeek) {
            $streak++;
            $cursor->subWeek();
        }

        return $streak;
    }

    // ---------------------------
    // Cardio
    // ---------------------------

    private function cardioSessionsTotal(int $userId): int
    {
        if (!Schema::hasTable('workout_logs') || !Schema::hasTable('workouts')) {
            return 0;
        }

        $logs = DB::table('workout_logs as wl')
            ->join('workouts as w', 'w.id', '=', 'wl.workout_id')
            ->where('wl.user_id', $userId)
            ->select('wl.id', 'wl.workout_id', 'wl.entry_date', 'w.name')
            ->get();

        return $logs->filter(fn ($log) => $this->isCardioWorkout((array) $log))->count();
    }

    private function maxCardioSessionsInAnyWeek(int $userId): int
    {
        if (!Schema::hasTable('workout_logs') || !Schema::hasTable('workouts')) {
            return 0;
        }

        $logs = DB::table('workout_logs as wl')
            ->join('workouts as w', 'w.id', '=', 'wl.workout_id')
            ->where('wl.user_id', $userId)
            ->select('wl.id', 'wl.workout_id', 'wl.entry_date', 'w.name')
            ->get()
            ->filter(fn ($log) => $this->isCardioWorkout((array) $log));

        if ($logs->isEmpty()) {
            return 0;
        }

        $weeks = $logs->map(fn ($log) => $this->yearWeek(Carbon::parse($log->entry_date)));

        return max($weeks->countBy()->all());
    }

    private function isCardioWorkout(array $log): bool
    {
        $name = strtolower((string) ($log['name'] ?? ''));
        $keywords = ['cardio', 'run', 'running', 'walk', 'walking', 'bike', 'cycling', 'row', 'rowing', 'treadmill', 'stair'];

        foreach ($keywords as $kw) {
            if (str_contains($name, $kw)) {
                return true;
            }
        }

        if (Schema::hasTable('exercise_workout') && Schema::hasTable('exercises') && !empty($log['workout_id'])) {
            $muscles = DB::table('exercise_workout as ew')
                ->join('exercises as ex', 'ex.id', '=', 'ew.exercise_id')
                ->where('ew.workout_id', $log['workout_id'])
                ->pluck('ex.muscle_group')
                ->map(fn ($m) => strtolower((string) $m));

            foreach ($muscles as $muscle) {
                if (str_contains($muscle, 'cardio')) {
                    return true;
                }
            }
        }

        return false;
    }

    // ---------------------------
    // Strength / progression
    // ---------------------------

    private function exerciseImprovementEvents(int $userId): int
    {
        $rows = $this->exerciseDailyBestWeights($userId);
        if ($rows->isEmpty()) {
            return 0;
        }

        $events = 0;

        foreach ($rows->groupBy('exercise_id') as $exerciseRows) {
            $best = null;

            foreach ($exerciseRows->sortBy('day') as $row) {
                $weight = (float) $row->best_weight;

                if ($best !== null && $weight > $best) {
                    $events++;
                }

                $best = max($best ?? 0, $weight);
            }
        }

        return $events;
    }

    private function distinctExercisesImproved(int $userId): int
    {
        $rows = $this->exerciseDailyBestWeights($userId);
        if ($rows->isEmpty()) {
            return 0;
        }

        $improved = 0;

        foreach ($rows->groupBy('exercise_id') as $exerciseRows) {
            $best = null;
            $hadImprovement = false;

            foreach ($exerciseRows->sortBy('day') as $row) {
                $weight = (float) $row->best_weight;

                if ($best !== null && $weight > $best) {
                    $hadImprovement = true;
                    break;
                }

                $best = max($best ?? 0, $weight);
            }

            if ($hadImprovement) {
                $improved++;
            }
        }

        return $improved;
    }

    private function exerciseDailyBestWeights(int $userId): Collection
    {
        if (
            !Schema::hasTable('workout_log_sets') ||
            !Schema::hasTable('workout_log_exercises') ||
            !Schema::hasTable('workout_logs')
        ) {
            return collect();
        }

        return DB::table('workout_log_sets as s')
            ->join('workout_log_exercises as e', 'e.id', '=', 's.workout_log_exercise_id')
            ->join('workout_logs as l', 'l.id', '=', 'e.workout_log_id')
            ->where('l.user_id', $userId)
            ->where('s.weight_kg', '>', 0)
            ->select(
                'e.exercise_id',
                DB::raw('date(l.entry_date) as day'),
                DB::raw('MAX(s.weight_kg) as best_weight')
            )
            ->groupBy('e.exercise_id', DB::raw('date(l.entry_date)'))
            ->get();
    }

    private function hasWorkoutWithExerciseCount(int $userId, int $target): bool
    {
        if (!Schema::hasTable('workouts') || !Schema::hasTable('exercise_workout')) {
            return false;
        }

        return DB::table('exercise_workout as ew')
            ->join('workouts as w', 'w.id', '=', 'ew.workout_id')
            ->where('w.user_id', $userId)
            ->select('ew.workout_id', DB::raw('COUNT(DISTINCT ew.exercise_id) as exercise_count'))
            ->groupBy('ew.workout_id')
            ->havingRaw('COUNT(DISTINCT ew.exercise_id) >= ?', [$target])
            ->exists();
    }

    private function sameWorkoutPlanWeeks(int $userId): int
    {
        if (!Schema::hasTable('workout_logs')) {
            return 0;
        }

        $weeksPerWorkout = DB::table('workout_logs')
            ->where('user_id', $userId)
            ->get(['workout_id', 'entry_date'])
            ->groupBy('workout_id')
            ->map(function ($rows) {
                return $rows->pluck('entry_date')
                    ->map(fn ($d) => $this->yearWeek(Carbon::parse($d)))
                    ->unique()
                    ->count();
            });

        return $weeksPerWorkout->max() ?? 0;
    }

    // ---------------------------
    // Analytics / events
    // ---------------------------

    private function eventCount(int $userId, string $event): int
    {
        if (!Schema::hasTable('analytics_events')) {
            return 0;
        }

        return DB::table('analytics_events')
            ->where('user_id', $userId)
            ->where('event', $event)
            ->count();
    }

    private function goalUpdatedAndTrackedDays($user): int
    {
        $goal = $user->nutritionGoal;
        if (!$goal) {
            return 0;
        }

        if (!$goal->updated_at || !$goal->created_at || $goal->updated_at->lte($goal->created_at)) {
            return 0;
        }

        $since = $goal->updated_at->toDateString();
        return $this->activityDates($user->id)
            ->filter(fn ($d) => $d >= $since)
            ->count();
    }

    // ---------------------------
    // Generic helpers
    // ---------------------------

    private function todayNutritionEntry(int $userId)
    {
        return NutritionEntry::where('user_id', $userId)
            ->whereDate('entry_date', Carbon::today()->toDateString())
            ->first();
    }

    private function withinTolerance(int|float $value, int|float $target, float $tolerancePercent): bool
    {
        if ($target <= 0) {
            return false;
        }

        $low = $target * (1 - ($tolerancePercent / 100));
        $high = $target * (1 + ($tolerancePercent / 100));

        return $value >= $low && $value <= $high;
    }

    private function isBalancedDay($user, string $date, float $tolerance, bool $includeCalories): bool
    {
        $entry = NutritionEntry::where('user_id', $user->id)
            ->whereDate('entry_date', $date)
            ->first();

        return $entry ? $this->isBalancedEntry($user, $entry, $tolerance, $includeCalories) : false;
    }

    private function isBalancedEntry($user, $entry, float $tolerance, bool $includeCalories): bool
    {
        $goal = $user->nutritionGoal;
        if (!$goal) {
            return false;
        }

        $checks = [
            $this->withinTolerance((int) ($entry->protein_g ?? 0), (int) ($goal->protein_g ?? 0), $tolerance),
            $this->withinTolerance((int) ($entry->carbs_g ?? 0), (int) ($goal->carbs_g ?? 0), $tolerance),
            $this->withinTolerance((int) ($entry->fat_g ?? 0), (int) ($goal->fat_g ?? 0), $tolerance),
        ];

        if ($includeCalories) {
            $checks[] = $this->withinTolerance((int) ($entry->calories ?? 0), (int) ($goal->calorie_target ?? 0), $tolerance);
        }

        return !in_array(false, $checks, true);
    }

    private function activityDates(int $userId): Collection
    {
        $dates = collect();

        if (Schema::hasTable('login_logs')) {
            $dates = $dates->merge(
                DB::table('login_logs')->where('user_id', $userId)->pluck('login_date')
            );
        }

        if (Schema::hasTable('workout_logs')) {
            $dates = $dates->merge(
                DB::table('workout_logs')->where('user_id', $userId)->pluck('entry_date')
            );
        }

        if (Schema::hasTable('nutrition_entries')) {
            $dates = $dates->merge(
                DB::table('nutrition_entries')
                    ->where('user_id', $userId)
                    ->where(function ($q) {
                        $q->where('calories', '>', 0)
                            ->orWhere('protein_g', '>', 0)
                            ->orWhere('carbs_g', '>', 0)
                            ->orWhere('fat_g', '>', 0)
                            ->orWhere('creatine_g', '>', 0)
                            ->orWhere('water_ml', '>', 0);
                    })
                    ->pluck('entry_date')
            );
        }

        return $dates
            ->map(fn ($d) => Carbon::parse($d)->toDateString())
            ->unique()
            ->sort()
            ->values();
    }

    private function consecutiveDaysStreak(array $dateStrings): int
    {
        $days = collect($dateStrings)
            ->map(fn ($d) => Carbon::parse($d)->toDateString())
            ->unique()
            ->values();

        if ($days->isEmpty()) {
            return 0;
        }

        $streak = 0;
        $cursor = Carbon::today()->toDateString();

        if (!$days->contains($cursor)) {
            $yesterday = Carbon::yesterday()->toDateString();
            if ($days->contains($yesterday)) {
                $cursor = $yesterday;
            } else {
                return 0;
            }
        }

        while ($days->contains($cursor)) {
            $streak++;
            $cursor = Carbon::parse($cursor)->subDay()->toDateString();
        }

        return $streak;
    }

    private function yearWeek(Carbon $date): string
    {
        return $date->isoWeekYear . '-W' . str_pad((string) $date->isoWeek, 2, '0', STR_PAD_LEFT);
    }
}
