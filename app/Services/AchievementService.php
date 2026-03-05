<?php
namespace App\Services;

use App\Models\Achievement;
use App\Models\UserAchievement;
use App\Models\NutritionEntry;
use App\Models\WorkoutLog;
use App\Models\Workout;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class AchievementService
{
  public function evaluate($user): array
  {
    $newlyUnlocked = [];

    $achievements = Achievement::query()
      ->where('is_active', true)
      ->get();

    foreach ($achievements as $a) {
      // already unlocked?
      $ua = UserAchievement::where('user_id', $user->id)
        ->where('achievement_id', $a->id)
        ->first();

      if ($ua && $ua->unlocked_at) continue;

      if ($this->meetsCriteria($user, $a)) {
        $ua = UserAchievement::updateOrCreate(
          ['user_id' => $user->id, 'achievement_id' => $a->id],
          ['unlocked_at' => now(), 'notified_at' => null]
        );

        $newlyUnlocked[] = [
          'id' => $a->id,
          'title' => $a->title,
          'description' => $a->description,
          'rarity' => $a->rarity,
        ];
      }
    }

    return $newlyUnlocked;
  }

  private function meetsCriteria($user, Achievement $a): bool
  {
    $c = $a->criteria ?? null;
    if (!$c || empty($c['metric'])) return false;

    return match ($c['metric']) {
      'account_created' => true,

      // totals
      'nutrition_days' => $this->nutritionDays($user->id) >= (int)($c['target'] ?? 1),
      'workout_days' => $this->workoutDays($user->id) >= (int)($c['target'] ?? 1),
      'workouts_created' => Workout::where('user_id', $user->id)->count() >= (int)($c['target'] ?? 1),

      // streaks
      'login_streak' => $this->loginStreak($user->id) >= (int)($c['target'] ?? 1),
      'workout_streak' => $this->workoutStreak($user->id) >= (int)($c['target'] ?? 1),

      // daily goal met “today” counts as unlock trigger (or you can do “ever met”)
      'protein_goal_met' => $this->macroGoalMetToday($user, 'protein_g'),
      'carbs_goal_met' => $this->macroGoalMetToday($user, 'carbs_g'),
      'fat_goal_met' => $this->macroGoalMetToday($user, 'fat_g'),
      'calories_goal_met' => $this->macroGoalMetToday($user, 'calories'),
      'creatine_goal_met' => $this->macroGoalMetToday($user, 'creatine_g'),
      'water_goal_met' => $this->macroGoalMetToday($user, 'water_ml'),

      default => false,
    };
  }

  private function nutritionDays(int $userId): int
  {
    return NutritionEntry::where('user_id', $userId)
      ->where(function($q){
        $q->where('calories', '>', 0)
          ->orWhere('protein_g', '>', 0)
          ->orWhere('carbs_g', '>', 0)
          ->orWhere('fat_g', '>', 0)
          ->orWhere('creatine_g', '>', 0)
          ->orWhere('water_ml', '>', 0);
      })
      ->distinct(DB::raw("date(entry_date)"))
      ->count(DB::raw("date(entry_date)"));
  }

  private function workoutDays(int $userId): int
  {
    return WorkoutLog::where('user_id', $userId)
      ->distinct(DB::raw("date(entry_date)"))
      ->count(DB::raw("date(entry_date)"));
  }

  private function loginStreak(int $userId): int
  {
    // Uses login_logs table you already added for streaks (date per day).
    $dates = DB::table('login_logs')
      ->where('user_id', $userId)
      ->orderBy('login_date', 'desc')
      ->pluck(DB::raw("date(login_date)"))
      ->map(fn($d) => Carbon::parse($d)->toDateString())
      ->values();

    if ($dates->isEmpty()) return 0;

    $streak = 0;
    $cursor = Carbon::today();

    foreach ($dates as $d) {
      if ($d === $cursor->toDateString()) {
        $streak++;
        $cursor->subDay();
        continue;
      }
      // if user missed a day => stop
      if ($d < $cursor->toDateString()) break;
    }
    return $streak;
  }

  private function workoutStreak(int $userId): int
  {
    $dates = WorkoutLog::where('user_id', $userId)
      ->orderBy('entry_date', 'desc')
      ->pluck('entry_date')
      ->map(fn($d) => Carbon::parse($d)->toDateString())
      ->unique()
      ->values();

    if ($dates->isEmpty()) return 0;

    $streak = 0;
    $cursor = Carbon::today();

    foreach ($dates as $d) {
      if ($d === $cursor->toDateString()) {
        $streak++;
        $cursor->subDay();
        continue;
      }
      if ($d < $cursor->toDateString()) break;
    }
    return $streak;
  }

  private function macroGoalMetToday($user, string $field): bool
  {
    $today = Carbon::today()->toDateString();

    $entry = NutritionEntry::where('user_id', $user->id)
      ->whereDate('entry_date', $today)
      ->first();

    if (!$entry) return false;

    $goal = $user->nutritionGoal;
    if (!$goal) return false;

    $target = match ($field) {
      'calories' => (int)($goal->calorie_target ?? 0),
      'protein_g' => (int)($goal->protein_g ?? 0),
      'carbs_g' => (int)($goal->carbs_g ?? 0),
      'fat_g' => (int)($goal->fat_g ?? 0),
      'creatine_g' => (int)($goal->creatine_g ?? 0),
      'water_ml' => $goal->water_l ? (int) round($goal->water_l * 1000) : 0,
      default => 0
    };

    if ($target <= 0) return false;
    return (int)($entry->{$field} ?? 0) >= $target;
  }
}