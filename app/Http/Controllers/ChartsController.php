<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

use App\Models\NutritionEntry;
use App\Models\WorkoutLogExercise;
use App\Models\WorkoutLogSet;

class ChartsController extends Controller
{
    public function index(Request $request)
    {
        // Defaults on first load
        $defaultMacro = 'calories';
        $defaultPeriod = 'month';

        $user = $request->user();

        // Exercises user has EVER logged (for dropdown)
        $exercises = WorkoutLogExercise::query()
            ->join('workout_logs', 'workout_logs.id', '=', 'workout_log_exercises.workout_log_id')
            ->join('exercises', 'exercises.id', '=', 'workout_log_exercises.exercise_id')
            ->where('workout_logs.user_id', $user->id)
            ->select('exercises.id', 'exercises.name')
            ->distinct()
            ->orderBy('exercises.name')
            ->get();

        return view('charts.index', compact('defaultMacro', 'defaultPeriod', 'exercises'));
    }

    public function macros(Request $request)
    {
        $user = $request->user();

        $macro = $request->query('macro', 'calories');
        $period = $request->query('period', 'month');

        // Map macro -> DB column + label + color
        $map = [
            'calories' => ['col' => 'calories', 'label' => 'Calories (kcal)', 'color' => '#ff4d4d'],
            'protein'  => ['col' => 'protein_g', 'label' => 'Protein (g)', 'color' => '#3b82f6'],
            'carbs'    => ['col' => 'carbs_g', 'label' => 'Carbs (g)', 'color' => '#fbbf24'],
            'fat'      => ['col' => 'fat_g', 'label' => 'Fat (g)', 'color' => '#fb923c'],
            'creatine' => ['col' => 'creatine_g', 'label' => 'Creatine (g)', 'color' => '#a855f7'],
            'water'    => ['col' => 'water_ml', 'label' => 'Water (ml)', 'color' => '#22d3ee'],
        ];

        if (!isset($map[$macro])) {
            return response()->json(['message' => 'Invalid macro'], 422);
        }

        $to = Carbon::today();
        $from = match ($period) {
            'week' => $to->copy()->subDays(6),
            'month' => $to->copy()->subDays(29),
            'year' => $to->copy()->subDays(364),
            default => null, // all time
        };

        $q = NutritionEntry::query()
            ->where('user_id', $user->id)
            ->orderBy('entry_date');

        if ($from) {
            $q->whereDate('entry_date', '>=', $from)
              ->whereDate('entry_date', '<=', $to);
        }

        $col = $map[$macro]['col'];
        $rows = $q->get(['entry_date', $col]);

        $labels = [];
        $values = [];

        foreach ($rows as $r) {
            $labels[] = Carbon::parse($r->entry_date)->format('M j');
            $values[] = (float) ($r->{$col} ?? 0);
        }

        return response()->json([
            'meta' => [
                'macro' => $macro,
                'period' => $period,
                'label' => $map[$macro]['label'],
                'color' => $map[$macro]['color'],
                'points' => count($values),
            ],
            'labels' => $labels,
            'values' => $values,
        ]);
    }

    // Exercise Progress endpoint
        public function exerciseData(Request $request)
        {
            $user = $request->user();

            $validated = $request->validate([
                'exercise_id' => ['required', 'integer'],
                'period' => ['nullable', 'in:week,month,year,all'],
            ]);

            $exerciseId = (int) $validated['exercise_id'];
            $period = $validated['period'] ?? 'month';

            $to = Carbon::today();
            $from = match ($period) {
                'week' => $to->copy()->subDays(6),
                'month' => $to->copy()->subDays(29),
                'year' => $to->copy()->subDays(364),
                default => null, // all time
            };

            // Pull all sets (date + reps + weight), then choose "heaviest set" per day
            $q = WorkoutLogSet::query()
                ->join('workout_log_exercises as le', 'le.id', '=', 'workout_log_sets.workout_log_exercise_id')
                ->join('workout_logs as wl', 'wl.id', '=', 'le.workout_log_id')
                ->where('wl.user_id', $user->id)
                ->where('le.exercise_id', $exerciseId)
                ->whereNotNull('workout_log_sets.weight_kg');

            if ($from) {
                $q->whereDate('wl.entry_date', '>=', $from->toDateString())
                ->whereDate('wl.entry_date', '<=', $to->toDateString());
            }

            // Get all sets in range (ordered so the "best" set per day comes first)
            $sets = $q->selectRaw("date(wl.entry_date) as d")
                ->addSelect([
                    'workout_log_sets.reps',
                    'workout_log_sets.weight_kg',
                ])
                ->orderBy('d', 'asc')
                ->orderBy('workout_log_sets.weight_kg', 'desc')
                ->orderBy(DB::raw('COALESCE(workout_log_sets.reps, 0)'), 'desc')
                ->get();

            // Pick the first (heaviest) set per day
            $bestPerDay = $sets->groupBy('d')->map(function ($daySets) {
                $best = $daySets->first(); // heaviest weight (tie: highest reps)
                return [
                    'd' => $best->d,
                    'reps' => (int) ($best->reps ?? 0),
                    'weight' => (float) ($best->weight_kg ?? 0),
                ];
            })->values();

            $labels = [];
            $reps = [];
            $weight = [];

            foreach ($bestPerDay as $row) {
                $labels[] = Carbon::parse($row['d'])->format('M j');
                $reps[] = $row['reps'];
                $weight[] = $row['weight'];
            }

            return response()->json([
                'labels' => $labels,
                'reps' => $reps,
                'weight' => $weight,
                'days' => count($labels),
            ]);
        }
    }