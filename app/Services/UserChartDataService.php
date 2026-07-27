<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class UserChartDataService
{
    private const MACROS = [
        'calories' => ['column' => 'calories', 'label' => 'Calories (kcal)', 'color' => '#ff4d4d'],
        'protein' => ['column' => 'protein_g', 'label' => 'Protein (g)', 'color' => '#3b82f6'],
        'carbs' => ['column' => 'carbs_g', 'label' => 'Carbs (g)', 'color' => '#fbbf24'],
        'fat' => ['column' => 'fat_g', 'label' => 'Fat (g)', 'color' => '#fb923c'],
        'creatine' => ['column' => 'creatine_g', 'label' => 'Creatine (g)', 'color' => '#f4f7fc'],
        'water' => ['column' => 'water_ml', 'label' => 'Water (ml)', 'color' => '#22d3ee'],
    ];

    public function exercises(User $user)
    {
        return DB::table('workout_log_exercises as logged')
            ->join('workout_logs as logs', 'logs.id', '=', 'logged.workout_log_id')
            ->join('exercises', 'exercises.id', '=', 'logged.exercise_id')
            ->where('logs.user_id', $user->id)
            ->select('exercises.id', 'exercises.name')
            ->distinct()
            ->orderBy('exercises.name')
            ->get();
    }

    public function macro(User $user, string $macro, string $period): array
    {
        abort_unless(isset(self::MACROS[$macro]), 422, 'Invalid macro.');
        $range = $this->range($period);
        $config = self::MACROS[$macro];

        $query = DB::table('nutrition_entries')
            ->where('user_id', $user->id)
            ->orderBy('entry_date');

        if ($range) {
            $query->whereDate('entry_date', '>=', $range[0])
                ->whereDate('entry_date', '<=', $range[1]);
        }

        $rows = $query->get(['entry_date', $config['column']]);
        $values = $rows->map(fn ($row) => (float) ($row->{$config['column']} ?? 0))->all();

        return [
            'meta' => [
                'macro' => $macro,
                'period' => $period,
                'label' => $config['label'],
                'color' => $config['color'],
                'points' => count($values),
            ],
            'labels' => $rows->map(fn ($row) => Carbon::parse($row->entry_date)->format('M j'))->all(),
            'values' => $values,
            'insights' => $this->insights($values),
        ];
    }

    public function exercise(User $user, int $exerciseId, string $period): array
    {
        $range = $this->range($period);
        $query = DB::table('workout_log_sets as sets')
            ->join('workout_log_exercises as logged', 'logged.id', '=', 'sets.workout_log_exercise_id')
            ->join('workout_logs as logs', 'logs.id', '=', 'logged.workout_log_id')
            ->where('logs.user_id', $user->id)
            ->where('logged.exercise_id', $exerciseId)
            ->whereNotNull('sets.weight_kg');

        if ($range) {
            $query->whereDate('logs.entry_date', '>=', $range[0])
                ->whereDate('logs.entry_date', '<=', $range[1]);
        }

        if (Schema::hasColumn('workout_log_sets', 'set_type')) {
            $query->where('sets.set_type', '!=', 'warmup');
        }

        $sets = $query
            ->selectRaw('date(logs.entry_date) as day')
            ->addSelect(['sets.reps', 'sets.weight_kg'])
            ->orderBy('day')
            ->orderByDesc('sets.weight_kg')
            ->orderByRaw('COALESCE(sets.reps, 0) DESC')
            ->get();

        $best = $sets->groupBy('day')->map(fn ($daySets) => $daySets->first())->values();
        $reps = $best->map(fn ($set) => (int) ($set->reps ?? 0))->all();
        $weight = $best->map(fn ($set) => (float) ($set->weight_kg ?? 0))->all();

        return [
            'labels' => $best->map(fn ($set) => Carbon::parse($set->day)->format('M j'))->all(),
            'reps' => $reps,
            'weight' => $weight,
            'days' => $best->count(),
            'insights' => [
                'reps' => $this->insights($reps),
                'weight' => $this->insights($weight),
            ],
        ];
    }

    public function weight(User $user, string $period): array
    {
        $range = $this->range($period);
        $query = DB::table('weight_entries')
            ->where('user_id', $user->id)
            ->orderBy('recorded_on');

        if ($range) {
            $query->whereDate('recorded_on', '>=', $range[0])
                ->whereDate('recorded_on', '<=', $range[1]);
        }

        $rows = $query->get(['recorded_on', 'weight_kg']);
        $values = $rows->map(fn ($row) => (float) $row->weight_kg)->all();

        return [
            'labels' => $rows->map(fn ($row) => Carbon::parse($row->recorded_on)->format('M j'))->all(),
            'values' => $values,
            'days' => $rows->count(),
            'insights' => $this->insights($values),
        ];
    }

    private function range(string $period): ?array
    {
        $today = Carbon::today();
        $from = match ($period) {
            'week' => $today->copy()->subDays(6),
            'month' => $today->copy()->subDays(29),
            'year' => $today->copy()->subDays(364),
            'all' => null,
            default => abort(422, 'Invalid period.'),
        };

        return $from ? [$from->toDateString(), $today->toDateString()] : null;
    }

    private function insights(array $values): array
    {
        if ($values === []) {
            return ['latest' => null, 'average' => null, 'highest' => null, 'change_percent' => null];
        }

        $first = (float) reset($values);
        $latest = (float) end($values);

        return [
            'latest' => round($latest, 1),
            'average' => round(array_sum($values) / count($values), 1),
            'highest' => round(max($values), 1),
            'change_percent' => $first > 0 ? round((($latest - $first) / $first) * 100, 1) : null,
        ];
    }
}
