<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class WeeklyReportService
{
    public function build(User $user, ?Carbon $anchor = null): array
    {
        $anchor ??= now();
        $weekStart = $anchor->copy()->startOfWeek(Carbon::MONDAY)->startOfDay();
        $weekEnd = $anchor->copy()->endOfWeek(Carbon::SUNDAY)->endOfDay();
        $startDate = $weekStart->toDateString();
        $endDate = $weekEnd->toDateString();

        $nutritionEntries = Schema::hasTable('nutrition_entries')
            ? DB::table('nutrition_entries')
                ->where('user_id', $user->id)
                ->whereDate('entry_date', '>=', $startDate)
                ->whereDate('entry_date', '<=', $endDate)
                ->orderBy('entry_date')
                ->get()
            : collect();

        $loggedNutrition = $nutritionEntries->filter(fn ($entry) => collect([
            $entry->calories,
            $entry->protein_g,
            $entry->carbs_g,
            $entry->fat_g,
            $entry->creatine_g,
            $entry->water_ml,
        ])->contains(fn ($value) => (float) $value > 0));

        $goal = Schema::hasTable('nutrition_goals') ? $user->nutritionGoal : null;
        $nutrition = collect([
            'calories' => ['label' => 'Calories', 'unit' => 'kcal', 'field' => 'calories', 'target' => $goal?->calorie_target],
            'protein' => ['label' => 'Protein', 'unit' => 'g', 'field' => 'protein_g', 'target' => $goal?->protein_g],
            'carbs' => ['label' => 'Carbohydrates', 'unit' => 'g', 'field' => 'carbs_g', 'target' => $goal?->carbs_g],
            'fat' => ['label' => 'Fat', 'unit' => 'g', 'field' => 'fat_g', 'target' => $goal?->fat_g],
            'creatine' => ['label' => 'Creatine', 'unit' => 'g', 'field' => 'creatine_g', 'target' => $goal?->creatine_g],
            'water' => ['label' => 'Water', 'unit' => 'L', 'field' => 'water_ml', 'target' => $goal?->water_l],
        ])->map(function (array $macro) use ($loggedNutrition) {
            $total = (float) $loggedNutrition->sum($macro['field']);
            $average = $loggedNutrition->isEmpty() ? 0 : $total / $loggedNutrition->count();

            if ($macro['field'] === 'water_ml') {
                $total /= 1000;
                $average /= 1000;
            }

            $target = $macro['target'] !== null ? (float) $macro['target'] : null;

            return [
                'label' => $macro['label'],
                'unit' => $macro['unit'],
                'total' => round($total, 1),
                'average' => round($average, 1),
                'target' => $target,
                'target_percent' => $target && $target > 0
                    ? min(999, (int) round(($average / $target) * 100))
                    : null,
            ];
        });

        $hasWorkoutTables = collect([
            'workouts',
            'workout_logs',
            'workout_log_exercises',
            'workout_log_sets',
        ])->every(fn (string $table) => Schema::hasTable($table));

        $supportsDropVolume = $hasWorkoutTables
            && Schema::hasColumn('workout_log_sets', 'drop_reps')
            && Schema::hasColumn('workout_log_sets', 'drop_weight_kg');
        $totalRepsExpression = $supportsDropVolume
            ? 'COALESCE(SUM(COALESCE(sets.reps, 0) + COALESCE(sets.drop_reps, 0)), 0)'
            : 'COALESCE(SUM(sets.reps), 0)';
        $volumeExpression = $supportsDropVolume
            ? 'COALESCE(SUM((COALESCE(sets.reps, 0) * COALESCE(sets.weight_kg, 0)) + (COALESCE(sets.drop_reps, 0) * COALESCE(sets.drop_weight_kg, 0))), 0)'
            : 'COALESCE(SUM(sets.reps * sets.weight_kg), 0)';

        $workouts = $hasWorkoutTables
            ? DB::table('workout_logs as wl')
                ->leftJoin('workouts as w', 'w.id', '=', 'wl.workout_id')
                ->leftJoin('workout_log_exercises as wle', 'wle.workout_log_id', '=', 'wl.id')
                ->leftJoin('workout_log_sets as sets', 'sets.workout_log_exercise_id', '=', 'wle.id')
                ->where('wl.user_id', $user->id)
                ->whereDate('wl.entry_date', '>=', $startDate)
                ->whereDate('wl.entry_date', '<=', $endDate)
                ->groupBy('wl.id', 'wl.entry_date', 'w.name')
                ->orderBy('wl.entry_date')
                ->orderBy('wl.id')
                ->get([
                    'wl.id',
                    'wl.entry_date',
                    DB::raw("COALESCE(w.name, 'Workout') as workout_name"),
                    DB::raw('COUNT(DISTINCT wle.id) as exercise_count'),
                    DB::raw('COUNT(sets.id) as set_count'),
                    DB::raw("{$totalRepsExpression} as total_reps"),
                    DB::raw("{$volumeExpression} as volume_kg"),
                    DB::raw('COALESCE(MAX(sets.weight_kg), 0) as max_weight_kg'),
                ])
                ->map(fn ($workout) => [
                    'id' => (int) $workout->id,
                    'date' => Carbon::parse($workout->entry_date)->format('D, M j'),
                    'name' => $workout->workout_name,
                    'exercises' => (int) $workout->exercise_count,
                    'sets' => (int) $workout->set_count,
                    'reps' => (int) $workout->total_reps,
                    'volume_kg' => round((float) $workout->volume_kg, 1),
                    'max_weight_kg' => round((float) $workout->max_weight_kg, 1),
                ])
            : collect();

        $weightEntries = Schema::hasTable('weight_entries')
            ? DB::table('weight_entries')
                ->where('user_id', $user->id)
                ->whereDate('recorded_on', '>=', $startDate)
                ->whereDate('recorded_on', '<=', $endDate)
                ->orderBy('recorded_on')
                ->orderBy('id')
                ->get(['recorded_on', 'weight_kg'])
            : collect();

        $bodyMeasurements = Schema::hasTable('body_measurements')
            ? DB::table('body_measurements')
                ->where('user_id', $user->id)
                ->whereDate('recorded_on', '>=', $startDate)
                ->whereDate('recorded_on', '<=', $endDate)
                ->orderBy('recorded_on')
                ->orderBy('id')
                ->get()
            : collect();

        $latestBody = Schema::hasTable('body_measurements')
            ? DB::table('body_measurements')
                ->where('user_id', $user->id)
                ->whereDate('recorded_on', '<=', $endDate)
                ->orderByDesc('recorded_on')
                ->orderByDesc('id')
                ->first()
            : null;

        $weightStart = $weightEntries->isNotEmpty() ? (float) $weightEntries->first()->weight_kg : null;
        $weightEnd = $weightEntries->isNotEmpty() ? (float) $weightEntries->last()->weight_kg : null;
        $currentWeight = $weightEnd
            ?? ($latestBody?->weight_kg !== null ? (float) $latestBody->weight_kg : null)
            ?? (
                Schema::hasTable('user_metrics') && $user->metric?->weight_kg !== null
                    ? (float) $user->metric->weight_kg
                    : null
            );

        return [
            'user' => [
                'name' => $user->full_name ?: $user->name ?: $user->username ?: 'ProgressLab member',
                'email' => $user->email,
            ],
            'period' => [
                'start' => $startDate,
                'end' => $endDate,
                'label' => $weekStart->format('M j').' - '.$weekEnd->format('M j, Y'),
                'generated_at' => now()->format('M j, Y H:i'),
            ],
            'nutrition' => $nutrition,
            'nutrition_days_logged' => $loggedNutrition->count(),
            'workouts' => $workouts,
            'training' => [
                'workouts' => $workouts->count(),
                'exercises' => $workouts->sum('exercises'),
                'sets' => $workouts->sum('sets'),
                'reps' => $workouts->sum('reps'),
                'volume_kg' => round((float) $workouts->sum('volume_kg'), 1),
                'max_weight_kg' => round((float) $workouts->max('max_weight_kg'), 1),
            ],
            'weight' => [
                'entries' => $weightEntries->count(),
                'start' => $weightStart,
                'end' => $weightEnd,
                'change' => $weightStart !== null && $weightEnd !== null
                    ? round($weightEnd - $weightStart, 2)
                    : null,
                'current' => $currentWeight,
            ],
            'body_checkins' => $bodyMeasurements->count(),
            'latest_body' => $latestBody ? [
                'date' => Carbon::parse($latestBody->recorded_on)->format('M j, Y'),
                'waist_cm' => $this->number($latestBody->waist_cm),
                'arms_cm' => $this->number($latestBody->arms_cm),
                'thighs_cm' => $this->number($latestBody->thighs_cm),
                'hips_cm' => $this->number($latestBody->hips_cm),
                'glutes_cm' => $this->number($latestBody->glutes_cm),
            ] : null,
        ];
    }

    private function number($value): ?float
    {
        return $value === null ? null : round((float) $value, 1);
    }
}
