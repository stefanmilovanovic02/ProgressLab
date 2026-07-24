<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\NutritionEntry;
use App\Models\Exercise;
use App\Models\Workout;
use App\Models\WorkoutLog;
use App\Models\WorkoutLogExercise;
use App\Models\WorkoutLogSet;
use App\Models\FriendActivity;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;
use App\Services\ExerciseHistoryService;
use App\Services\ExerciseRankService;
use App\Services\ExperienceService;
use App\Services\NotificationService;



class AddTodayController extends Controller
{
    public function index(
        Request $request,
        ExerciseHistoryService $exerciseHistoryService,
        ExerciseRankService $exerciseRankService
    ){
        $user = $request->user();
        $today = now()->format('Y-m-d');

        // Create empty row for today if missing (so the page always has values)
        $entry = NutritionEntry::where('user_id', $user->id)->whereDate( 'entry_date', $today)->first();
        if (!$entry) {
            $entry = NutritionEntry::create([
                'user_id' => $user->id,
                'entry_date' => $today,
                'calories' => 0,
                'protein_g' => 0,
                'carbs_g' => 0,
                'fat_g' => 0,
                'creatine_g' => 0,
                'water_ml' => 0
            ]);

            
        }

        // Target from profile (for placeholders)
        $goal = $user->nutritionGoal; // Relationship to fetch user's nutrition goals from profile

        $targets = [
            'calories' => $goal?->calorie_target,
            'protein_g' => $goal?->protein_g,
            'carbs_g' => $goal?->carbs_g,
            'fat_g' => $goal?->fat_g,
            'creatine_g' => $goal?->creatine_g,
            'water_ml' => $goal?->water_l ? (int) round($goal->water_l * 1000) : null, // Convert liters to ml if set
        ];
            // Workouts
            $workouts = Workout::query()->where('user_id', $user->id)->with(['exercises:id,name,muscle_group'])->orderBy('name')->get(['id', 'name']);
            $exerciseHistory = $exerciseHistoryService->latestForUser(
                $user,
                $workouts->flatMap(fn ($workout) => $workout->exercises->pluck('id'))
            );
            $progressPhotoCount = $user->progressPhotoSets()->count();
            $latestProgressPhotoDate = $user->progressPhotoSets()->max('captured_on');
            $exerciseRanks = $exerciseRankService->currentForUser($user);
        
            return view('add-today.index', compact(
                'entry',
                'targets',
                'workouts',
                'exerciseHistory',
                'exerciseRanks',
                'progressPhotoCount',
                'latestProgressPhotoDate'
            ));
    }

    public function storeNutrition(Request $request){
        $user = $request->user();
        $today = now()->toDateString();

        $validated = $request->validate([
            'calories' => ['nullable', 'integer', 'min:0', 'max:50000'],
            'protein_g' => ['nullable', 'integer', 'min:0', 'max:1000'],
            'carbs_g' => ['nullable', 'integer', 'min:0', 'max:2000'],
            'fat_g' => ['nullable', 'integer', 'min:0', 'max:1000'],
            'creatine_g' => ['nullable', 'integer', 'min:0', 'max:100'],
            'water_ml' => ['nullable', 'integer', 'min:0', 'max:10000']
        ]);

        $entry = NutritionEntry::updateOrCreate(
            ['user_id' => $user->id, 'entry_date' => $today],
            [
                'calories' => (int) ($validated['calories'] ?? 0),
                'protein_g' => (int) ($validated['protein_g'] ?? 0),
                'carbs_g' => (int) ($validated['carbs_g'] ?? 0),
                'fat_g' => (int) ($validated['fat_g'] ?? 0),
                'creatine_g' => (int) ($validated['creatine_g'] ?? 0),
                'water_ml' => (int) ($validated['water_ml'] ?? 0),
            ]
        );
            // For Achievement and AJAX and Friend Activity
            if (
                $entry->calories > 0 ||
                $entry->protein_g > 0 ||
                $entry->carbs_g > 0 ||
                $entry->fat_g > 0 ||
                $entry->creatine_g > 0 ||
                $entry->water_ml > 0 
            ) {
                $this->syncNutritionActivity($user->id);
            }

            app(ExperienceService::class)->awardNutrition($user, $entry);
            $unlocked = app(\App\Services\AchievementService::class)->evaluate($request->user());
            if (!empty($unlocked)) {
                session()->flash('unlocked', $unlocked);
            }

        return back()->with('success', 'Nutrition entry updated successfully!');
    }

        public function getTodayWorkout(Request $request, ExerciseRankService $exerciseRankService)
        {
        $user = $request->user();
        $today = now()->format('Y-m-d');

        $log = WorkoutLog::with(['workout.exercises', 'exercises.exercise', 'exercises.sets'])
            ->where('user_id', $user->id)
            ->where('entry_date', $today)
            ->first();

        if (!$log) {
            return response()->json(['log' => null]);
        }

        // Shape data for the frontend
        $exerciseRanks = $exerciseRankService->currentForUser($user);
        $out = [
            'id' => $log->id,
            'workout_id' => $log->workout_id,
            'workout_name' => $log->workout?->name,
            'timing' => $this->workoutTiming($log),
            'exercises' => $log->exercises->map(function ($le) use ($exerciseRanks) {
            return [
                'exercise_id' => $le->exercise_id,
                'name' => $le->exercise?->name,
                'rank' => $exerciseRanks->get((string) $le->exercise_id),
                'sets' => $le->sets->sortBy('set_number')->values()->map(fn($s) => [
                'set_number' => $s->set_number,
                'reps' => $s->reps,
                'weight_kg' => $s->weight_kg,
                ]),
            ];
            })->values(),
        ];

        return response()->json(['log' => $out]);
        }

        public function saveTodayWorkout(
            Request $request,
            ExerciseRankService $exerciseRankService
        )
            {
            $user = $request->user();
            $today = now()->format('Y-m-d');

            $validated = $request->validate([
                'workout_id' => ['required','exists:workouts,id'],
                'exercises' => ['required','array'],
                'exercises.*.exercise_id' => ['required','exists:exercises,id'],
                'exercises.*.sets' => ['required','array'],
                'exercises.*.sets.*.set_number' => ['required','integer','min:1','max:50'],
                'exercises.*.sets.*.reps' => ['nullable','integer','min:0','max:300'],
                'exercises.*.sets.*.weight_kg' => ['nullable','numeric','min:0','max:999.99'],
            ]);

            $workoutId = (int) $validated['workout_id'];
            $workout = Workout::query()
                ->where('user_id', $user->id)
                ->findOrFail($workoutId);

            $result = DB::transaction(function () use ($user, $today, $workout, $validated) {

                // One workout per day per user (unique index)
                $log = WorkoutLog::firstOrCreate(
                    [
                        'user_id' => $user->id,
                        'entry_date' => $today,
                    ],
                    ['workout_id' => $workout->id]
                );
                $workoutChanged = (int) $log->workout_id !== (int) $workout->id;

                if ($workoutChanged) {
                    $log->exercises()->delete();
                    $log->started_at = null;
                    $log->completed_at = null;
                    $log->duration_seconds = null;
                }

                $log->workout_id = $workout->id;
                $log->save();

                $incomingExerciseIds = collect($validated['exercises'])->pluck('exercise_id')->map(fn($v)=>(int)$v)->values();

                // delete exercises not present anymore (and cascade deletes sets)
                WorkoutLogExercise::where('workout_log_id', $log->id)
                ->whereNotIn('exercise_id', $incomingExerciseIds)
                ->delete();

                foreach ($validated['exercises'] as $ex) {
                $exerciseId = (int) $ex['exercise_id'];

                $logEx = WorkoutLogExercise::firstOrCreate([
                    'workout_log_id' => $log->id,
                    'exercise_id' => $exerciseId,
                ]);

                $incomingSetNumbers = collect($ex['sets'])->pluck('set_number')->map(fn($v)=>(int)$v)->values();

                // delete removed sets
                WorkoutLogSet::where('workout_log_exercise_id', $logEx->id)
                    ->whereNotIn('set_number', $incomingSetNumbers)
                    ->delete();

                // upsert sets
                foreach ($ex['sets'] as $set) {
                    WorkoutLogSet::updateOrCreate(
                    ['workout_log_exercise_id' => $logEx->id, 'set_number' => (int)$set['set_number']],
                    [
                        'reps' => isset($set['reps']) ? (int)$set['reps'] : null,
                        'weight_kg' => isset($set['weight_kg']) ? (float)$set['weight_kg'] : null,
                    ]
                    );
                }
                }

                $completeSets = collect($validated['exercises'])
                    ->flatMap(fn (array $exercise) => $exercise['sets'] ?? [])
                    ->filter(fn (array $set) =>
                        array_key_exists('reps', $set)
                        && $set['reps'] !== null
                        && array_key_exists('weight_kg', $set)
                        && $set['weight_kg'] !== null
                    );
                $setCount = collect($validated['exercises'])
                    ->sum(fn (array $exercise) => count($exercise['sets'] ?? []));
                $hasStarted = $completeSets->isNotEmpty();
                $isComplete = $setCount > 0 && $completeSets->count() === $setCount;
                $justCompleted = false;

                if ($hasStarted && !$log->started_at) {
                    $log->started_at = now();
                }

                if ($isComplete && $log->started_at && !$log->completed_at) {
                    $log->completed_at = now();
                    $log->duration_seconds = max(
                        1,
                        (int) $log->started_at->diffInSeconds($log->completed_at)
                    );
                    $justCompleted = true;

                    if (!$workout->estimated_duration_seconds) {
                        $workout->update([
                            'estimated_duration_seconds' => $log->duration_seconds,
                        ]);
                    }
                }

                if ($log->isDirty()) {
                    $log->save();
                }

                $completedExerciseIds = collect($validated['exercises'])
                    ->filter(function (array $exercise) {
                        $sets = collect($exercise['sets'] ?? []);

                        return $sets->isNotEmpty() && $sets->every(fn (array $set) =>
                            array_key_exists('reps', $set)
                            && $set['reps'] !== null
                            && array_key_exists('weight_kg', $set)
                            && $set['weight_kg'] !== null
                        );
                    })
                    ->pluck('exercise_id')
                    ->map(fn ($id) => (int) $id)
                    ->values()
                    ->all();

                return compact('log', 'justCompleted', 'completedExerciseIds');
            });

        $experience = app(ExperienceService::class);
        foreach ($result['completedExerciseIds'] as $exerciseId) {
            $experience->award(
                $user,
                'exercise_completed',
                $result['log']->id . ':' . $exerciseId,
                ExperienceService::EXERCISE_COMPLETED_XP,
                'Completed an exercise',
                ['workout_log_id' => $result['log']->id, 'exercise_id' => $exerciseId]
            );
        }

        $rankUps = [];
        $rankedExercises = Exercise::query()
            ->with('rankStandard')
            ->whereIn('id', collect($validated['exercises'])->pluck('exercise_id'))
            ->get()
            ->keyBy('id');

        foreach ($validated['exercises'] as $exerciseData) {
            $exercise = $rankedExercises->get((int) $exerciseData['exercise_id']);
            if (!$exercise) {
                continue;
            }

            $promotion = $exerciseRankService->evaluate(
                $user,
                $exercise,
                $exerciseData['sets'] ?? []
            );

            if ($promotion) {
                $rankUps[] = $promotion;
            }
        }

        // Achievements and friend activity belong to a finished workout, not a partial autosave.
        $unlocked = [];
        if ($result['justCompleted']) {
            $experience->award(
                $user,
                'workout_completed',
                (string) $result['log']->id,
                ExperienceService::WORKOUT_COMPLETED_XP,
                'Completed ' . $workout->name
            );
            $this->syncWorkoutActivity($user->id, $workout->name);
            $unlocked = app(\App\Services\AchievementService::class)->evaluate($user);
        }

        if ($request->wantsJson()) {
            return response()->json([
                'ok' => true,
                'unlocked' => $unlocked,
                'rank_ups' => $rankUps,
                'timing' => $this->workoutTiming($result['log']->fresh()),
            ]);
        }
        return back()->with('status', 'Workout saved.')->with('unlocked', $unlocked);
    }

    private function workoutTiming(WorkoutLog $log): array
    {
        return [
            'status' => $log->completed_at
                ? 'completed'
                : ($log->started_at ? 'running' : 'not_started'),
            'started_at' => $log->started_at?->toIso8601String(),
            'completed_at' => $log->completed_at?->toIso8601String(),
            'duration_seconds' => $log->duration_seconds,
            'estimated_duration_seconds' => $log->workout?->estimated_duration_seconds,
        ];
    }

    // friend activity code
    private function syncNutritionActivity(int $userId): void
    {
        $today = Carbon::today();

        $existing = FriendActivity::query()
            ->where('user_id', $userId)
            ->where('type', 'nutrition')
            ->whereDate('created_at', $today->toDateString())
            ->first();

        if ($existing) {
            $existing->update([
                'text' => 'logged nutrition for today.',
                'updated_at' => now(),
            ]);
            app(NotificationService::class)->notifyFriendActivity($existing);
            return;
        }

        $activity = FriendActivity::create([
            'user_id' => $userId,
            'type' => 'nutrition',
            'text' => 'logged nutrition for today.',
            'meta' => ['date' => $today->toDateString()],
        ]);

        app(NotificationService::class)->notifyFriendActivity($activity);
    }

    private function syncWorkoutActivity(int $userId, ?string $workoutName = null): void
    {
        $today = Carbon::today();

        $existing = FriendActivity::query()
            ->where('user_id', $userId)
            ->where('type', 'workout')
            ->whereDate('created_at', $today->toDateString())
            ->first();

        $text = $workoutName
            ? 'completed "' . $workoutName . '" workout.'
            : 'completed a workout.';

        if ($existing) {
            $existing->update([
                'text' => $text,
                'updated_at' => now(),
            ]);
            app(NotificationService::class)->notifyFriendActivity($existing);
            return;
        }

        $activity = FriendActivity::create([
            'user_id' => $userId,
            'type' => 'workout',
            'text' => $text,
            'meta' => ['date' => $today->toDateString()],
        ]);

        app(NotificationService::class)->notifyFriendActivity($activity);
    }
}
