<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\NutritionEntry;
use App\Models\Workout;
use App\Models\WorkoutLog;
use App\Models\WorkoutLogExercise;
use App\Models\WorkoutLogSet;
use App\Models\FriendActivity;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;
use App\Services\ExerciseHistoryService;
use App\Services\NotificationService;



class AddTodayController extends Controller
{
    public function index(Request $request, ExerciseHistoryService $exerciseHistoryService){
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
        
            return view('add-today.index', compact(
                'entry',
                'targets',
                'workouts',
                'exerciseHistory',
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

            $unlocked = app(\App\Services\AchievementService::class)->evaluate($request->user());
            if (!empty($unlocked)) {
                session()->flash('unlocked', $unlocked);
            }

        return back()->with('success', 'Nutrition entry updated successfully!');
    }

    public function getTodayWorkout(Request $request)
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
        $out = [
            'id' => $log->id,
            'workout_id' => $log->workout_id,
            'workout_name' => $log->workout?->name,
            'exercises' => $log->exercises->map(function ($le) {
            return [
                'exercise_id' => $le->exercise_id,
                'name' => $le->exercise?->name,
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

        public function saveTodayWorkout(Request $request)
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

            DB::transaction(function () use ($user, $today, $workoutId, $validated) {

                // One workout per day per user (unique index)
                $log = WorkoutLog::updateOrCreate(
                ['user_id' => $user->id, 'entry_date' => $today],
                ['workout_id' => $workoutId]
                );

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
            });

            // For Achievement and friend activity
            $workoutName = Workout::where('id', $workoutId)->value('name');
            $this->syncWorkoutActivity($user->id, $workoutName);

        $unlocked = app(\App\Services\AchievementService::class)->evaluate($user);
        if ($request->wantsJson()) {
            return response()->json(['ok' => true, 'unlocked' => $unlocked]);
        }
        return back()->with('status', 'Workout saved.')->with('unlocked', $unlocked);
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
