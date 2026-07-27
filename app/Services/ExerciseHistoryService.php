<?php

namespace App\Services;

use App\Models\User;
use App\Models\WorkoutLogExercise;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class ExerciseHistoryService
{
    public function latestForUser(User $user, Collection|array $exerciseIds, ?Carbon $before = null): array
    {
        $exerciseIds = collect($exerciseIds)
            ->map(fn ($id) => (int) $id)
            ->filter()
            ->unique()
            ->values();

        if ($exerciseIds->isEmpty()) {
            return [];
        }

        $before ??= Carbon::today();

        return WorkoutLogExercise::query()
            ->with(['sets' => fn ($query) => $query->orderBy('set_number')])
            ->join('workout_logs', 'workout_logs.id', '=', 'workout_log_exercises.workout_log_id')
            ->where('workout_logs.user_id', $user->id)
            ->whereDate('workout_logs.entry_date', '<', $before->toDateString())
            ->whereIn('workout_log_exercises.exercise_id', $exerciseIds)
            ->whereHas('sets', function ($query) {
                $query->whereNotNull('reps')->orWhereNotNull('weight_kg');
            })
            ->orderByDesc('workout_logs.entry_date')
            ->orderByDesc('workout_logs.id')
            ->select('workout_log_exercises.*', 'workout_logs.entry_date as history_entry_date')
            ->get()
            ->unique('exercise_id')
            ->mapWithKeys(function (WorkoutLogExercise $logExercise) {
                $sets = $logExercise->sets
                    ->map(fn ($set) => [
                        'set_number' => (int) $set->set_number,
                        'set_type' => $set->set_type ?? 'normal',
                        'reps' => $set->reps === null ? null : (int) $set->reps,
                        'weight_kg' => $set->weight_kg === null ? null : (float) $set->weight_kg,
                        'drop_reps' => ($set->drop_reps ?? null) === null ? null : (int) $set->drop_reps,
                        'drop_weight_kg' => ($set->drop_weight_kg ?? null) === null ? null : (float) $set->drop_weight_kg,
                    ])
                    ->values();
                $workingSets = $sets->where('set_type', '!=', 'warmup');

                return [(string) $logExercise->exercise_id => [
                    'date' => Carbon::parse($logExercise->history_entry_date)->toDateString(),
                    'sets' => $sets->all(),
                    'max_reps' => $workingSets->pluck('reps')->filter(fn ($value) => $value !== null)->max(),
                    'max_weight_kg' => $workingSets->pluck('weight_kg')->filter(fn ($value) => $value !== null)->max(),
                ]];
            })
            ->all();
    }
}
