<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class ActivityCalendarService
{
    public function build(User $user, ?Carbon $anchor = null): array
    {
        $anchor = ($anchor ?? now())->copy()->startOfDay();
        $yearStart = $anchor->copy()->startOfYear();
        $yearEnd = $anchor->copy()->endOfYear();
        $start = $yearStart->copy()->startOfWeek(Carbon::SUNDAY);
        $end = $yearEnd->copy()->endOfWeek(Carbon::SATURDAY);

        $nutritionDates = DB::table('nutrition_entries')
            ->where('user_id', $user->id)
            ->whereDate('entry_date', '>=', $yearStart->toDateString())
            ->whereDate('entry_date', '<=', $anchor->toDateString())
            ->where(function ($query) {
                $query->where('calories', '>', 0)
                    ->orWhere('protein_g', '>', 0)
                    ->orWhere('carbs_g', '>', 0)
                    ->orWhere('fat_g', '>', 0)
                    ->orWhere('creatine_g', '>', 0)
                    ->orWhere('water_ml', '>', 0);
            })
            ->selectRaw('date(entry_date) as activity_date')
            ->distinct()
            ->pluck('activity_date')
            ->flip();

        $workoutDates = DB::table('workout_logs as logs')
            ->join('workout_log_exercises as logged', 'logged.workout_log_id', '=', 'logs.id')
            ->join('workout_log_sets as sets', 'sets.workout_log_exercise_id', '=', 'logged.id')
            ->where('logs.user_id', $user->id)
            ->whereDate('logs.entry_date', '>=', $yearStart->toDateString())
            ->whereDate('logs.entry_date', '<=', $anchor->toDateString())
            ->where(function ($query) {
                $query->whereNotNull('sets.reps')->orWhereNotNull('sets.weight_kg');
            })
            ->selectRaw('date(logs.entry_date) as activity_date')
            ->distinct()
            ->pluck('activity_date')
            ->flip();

        $days = collect();
        $activeDays = 0;
        $completeDays = 0;

        for ($date = $start->copy(); $date->lte($end); $date->addDay()) {
            $key = $date->toDateString();
            $outsideYear = $date->year !== $anchor->year;
            $future = $date->gt($anchor);
            $nutrition = !$outsideYear && !$future && $nutritionDates->has($key);
            $workout = !$outsideYear && !$future && $workoutDates->has($key);
            $level = (int) $nutrition + (int) $workout;

            if ($level > 0) {
                $activeDays++;
            }
            if ($level === 2) {
                $completeDays++;
            }

            $activity = match ($level) {
                2 => 'Workout and nutrition logged',
                1 => $workout ? 'Workout logged' : 'Nutrition logged',
                default => 'No workout or nutrition logged',
            };

            $days->push([
                'date' => $key,
                'label' => $date->format('M j, Y'),
                'level' => $level,
                'future' => $future,
                'outside_year' => $outsideYear,
                'activity' => $outsideYear ? '' : ($future ? 'Future date' : $activity),
            ]);
        }

        $weeks = $days->chunk(7)->values();
        $months = collect(array_fill(0, $weeks->count(), ''));
        for ($month = 1; $month <= 12; $month++) {
            $monthStart = $yearStart->copy()->month($month)->startOfMonth();
            $weekIndex = intdiv($start->diffInDays($monthStart), 7);
            $months[$weekIndex] = $monthStart->format('M');
        }

        return [
            'year' => $anchor->year,
            'start' => $yearStart->toDateString(),
            'end' => $yearEnd->toDateString(),
            'days' => $days,
            'months' => $months,
            'week_count' => $weeks->count(),
            'active_days' => $activeDays,
            'complete_days' => $completeDays,
        ];
    }
}
