<?php

namespace App\Http\Controllers\Trainer;

use App\Http\Controllers\Controller;
use App\Models\TrainerWorkoutAssignment;
use App\Models\User;
use App\Models\Workout;
use App\Models\WorkoutLog;
use App\Models\WorkoutLogExercise;
use App\Models\WorkoutLogSet;
use App\Models\FriendActivity;
use App\Models\NutritionEntry;
use App\Services\ExerciseHistoryService;
use App\Services\ExerciseRankService;
use App\Services\ExperienceService;
use App\Services\NotificationService;
use App\Services\TrainerClientAccessService;
use App\Services\WeeklyReportService;
use App\Support\UnitConverter;
use Dompdf\Dompdf;
use Dompdf\Options;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Carbon;
use Illuminate\Validation\ValidationException;

class ClientManagementController extends Controller
{
    public function editWorkoutLog(
        Request $request,
        User $user,
        TrainerWorkoutAssignment $assignment,
        TrainerClientAccessService $access,
        ExerciseHistoryService $history
    ) {
        $relationship = $this->authorizedAssignment($request, $user, $assignment, $access);
        $workout = $assignment->clientWorkout()->with('exercises:id,name,muscle_group')->firstOrFail();
        $date = $this->requestedLogDate($request);
        $logForDate = WorkoutLog::query()
            ->with(['workout:id,name', 'exercises.exercise:id,name,muscle_group', 'exercises.sets' => fn ($query) => $query->orderBy('set_number')])
            ->where('user_id', $user->id)
            ->whereDate('entry_date', $date)
            ->first();
        $log = $logForDate && (int) $logForDate->workout_id === (int) $workout->id ? $logForDate : null;

        return view('trainer.workout-log', [
            'client' => $user,
            'relationship' => $relationship,
            'assignment' => $assignment,
            'workout' => $workout,
            'selectedDate' => $date,
            'log' => $log,
            'conflictingLog' => $logForDate && !$log ? $logForDate : null,
            'history' => $history->latestForUser($user, $workout->exercises->pluck('id'), Carbon::parse($date)),
            'recentLogs' => WorkoutLog::query()
                ->where('user_id', $user->id)
                ->where('workout_id', $workout->id)
                ->latest('entry_date')
                ->limit(12)
                ->get(['id', 'entry_date']),
        ]);
    }

    public function updateWorkoutLog(
        Request $request,
        User $user,
        TrainerWorkoutAssignment $assignment,
        TrainerClientAccessService $access,
        ExerciseRankService $ranks,
        ExperienceService $experience,
        NotificationService $notifications
    ) {
        $this->authorizedAssignment($request, $user, $assignment, $access);
        $workout = $assignment->clientWorkout()->with(['exercises.rankStandard'])->firstOrFail();
        $validated = $request->validate([
            'entry_date' => ['required', 'date_format:Y-m-d', 'before_or_equal:today'],
            'exercises' => ['required', 'array', 'min:1'],
            'exercises.*.exercise_id' => ['required', 'integer', 'distinct', 'exists:exercises,id'],
            'exercises.*.sets' => ['required', 'array', 'min:1', 'max:50'],
            'exercises.*.sets.*.set_number' => ['required', 'integer', 'min:1', 'max:50'],
            'exercises.*.sets.*.set_type' => ['required', 'in:normal,warmup,drop'],
            'exercises.*.sets.*.reps' => ['nullable', 'integer', 'min:0', 'max:300'],
            'exercises.*.sets.*.weight_kg' => ['nullable', 'numeric', 'min:0', 'max:'.($user->usesImperialUnits() ? 2205 : 999.99)],
            'exercises.*.sets.*.drop_reps' => ['nullable', 'integer', 'min:0', 'max:300'],
            'exercises.*.sets.*.drop_weight_kg' => ['nullable', 'numeric', 'min:0', 'max:'.($user->usesImperialUnits() ? 2205 : 999.99)],
        ]);

        foreach ($validated['exercises'] as &$exerciseData) {
            foreach ($exerciseData['sets'] as &$set) {
                foreach (['weight_kg', 'drop_weight_kg'] as $field) {
                    if (filled($set[$field] ?? null)) {
                        $set[$field] = UnitConverter::weightToKg((float) $set[$field], $user->unit_system);
                    }
                }
            }
            unset($set);
        }
        unset($exerciseData);

        $expectedExerciseIds = $workout->exercises->pluck('id')->map(fn ($id) => (int) $id)->sort()->values();
        $incomingExerciseIds = collect($validated['exercises'])->pluck('exercise_id')->map(fn ($id) => (int) $id)->sort()->values();
        if ($expectedExerciseIds->all() !== $incomingExerciseIds->all()) {
            throw ValidationException::withMessages(['exercises' => 'The workout exercises no longer match this assignment.']);
        }

        $date = $validated['entry_date'];
        $existing = WorkoutLog::query()->where('user_id', $user->id)->whereDate('entry_date', $date)->first();
        if ($existing && (int) $existing->workout_id !== (int) $workout->id) {
            throw ValidationException::withMessages([
                'entry_date' => 'The client already logged a different workout on this date. Choose another date instead of replacing it.',
            ]);
        }

        $supportsSetTypes = Schema::hasColumn('workout_log_sets', 'set_type');
        $supportsDropDetails = Schema::hasColumn('workout_log_sets', 'drop_reps') && Schema::hasColumn('workout_log_sets', 'drop_weight_kg');

        $result = DB::transaction(function () use ($user, $workout, $validated, $date, $supportsSetTypes, $supportsDropDetails) {
            $log = WorkoutLog::query()->firstOrCreate(
                ['user_id' => $user->id, 'entry_date' => $date],
                ['workout_id' => $workout->id]
            );
            $log->workout_id = $workout->id;
            $log->save();

            foreach ($validated['exercises'] as $exerciseData) {
                $logExercise = WorkoutLogExercise::query()->firstOrCreate([
                    'workout_log_id' => $log->id,
                    'exercise_id' => (int) $exerciseData['exercise_id'],
                ]);
                $incomingNumbers = collect($exerciseData['sets'])->pluck('set_number')->map(fn ($number) => (int) $number);
                WorkoutLogSet::query()->where('workout_log_exercise_id', $logExercise->id)->whereNotIn('set_number', $incomingNumbers)->delete();

                foreach ($exerciseData['sets'] as $set) {
                    $type = $set['set_type'] ?? 'normal';
                    $values = [
                        'reps' => filled($set['reps'] ?? null) ? (int) $set['reps'] : null,
                        'weight_kg' => filled($set['weight_kg'] ?? null) ? (float) $set['weight_kg'] : null,
                    ];
                    if ($supportsSetTypes) $values['set_type'] = $type;
                    if ($supportsDropDetails) {
                        $values['drop_reps'] = $type === 'drop' && filled($set['drop_reps'] ?? null) ? (int) $set['drop_reps'] : null;
                        $values['drop_weight_kg'] = $type === 'drop' && filled($set['drop_weight_kg'] ?? null) ? (float) $set['drop_weight_kg'] : null;
                    }
                    WorkoutLogSet::query()->updateOrCreate(
                        ['workout_log_exercise_id' => $logExercise->id, 'set_number' => (int) $set['set_number']],
                        $values
                    );
                }
            }

            WorkoutLogExercise::query()->where('workout_log_id', $log->id)
                ->whereNotIn('exercise_id', collect($validated['exercises'])->pluck('exercise_id'))
                ->delete();

            $allSets = collect($validated['exercises'])->flatMap(fn ($exercise) => $exercise['sets']);
            $completeSets = $allSets->filter(fn ($set) => filled($set['reps'] ?? null) && filled($set['weight_kg'] ?? null));
            $isComplete = $allSets->isNotEmpty() && $completeSets->count() === $allSets->count();
            $justCompleted = false;

            if ($date === now()->toDateString() && $completeSets->isNotEmpty() && !$log->started_at) $log->started_at = now();
            if ($date === now()->toDateString() && $isComplete && $log->started_at && !$log->completed_at) {
                $log->completed_at = now();
                $log->duration_seconds = max(1, (int) $log->started_at->diffInSeconds($log->completed_at));
                $justCompleted = true;
                if (!$workout->estimated_duration_seconds) $workout->update(['estimated_duration_seconds' => $log->duration_seconds]);
            }
            if ($log->isDirty()) $log->save();

            return compact('log', 'isComplete', 'justCompleted');
        });

        foreach ($validated['exercises'] as $exerciseData) {
            $sets = collect($exerciseData['sets']);
            if ($sets->every(fn ($set) => filled($set['reps'] ?? null) && filled($set['weight_kg'] ?? null))) {
                $experience->award($user, 'exercise_completed', $result['log']->id . ':' . $exerciseData['exercise_id'], ExperienceService::EXERCISE_COMPLETED_XP, 'Completed an exercise', [
                    'workout_log_id' => $result['log']->id,
                    'exercise_id' => (int) $exerciseData['exercise_id'],
                    'entered_by_trainer' => $request->user()->id,
                ]);
            }
            $exercise = $workout->exercises->firstWhere('id', (int) $exerciseData['exercise_id']);
            if ($exercise) $ranks->evaluate($user, $exercise, $exerciseData['sets']);
        }

        if ($result['isComplete']) {
            $experience->award($user, 'workout_completed', (string) $result['log']->id, ExperienceService::WORKOUT_COMPLETED_XP, 'Completed ' . $workout->name);
            app(\App\Services\AchievementService::class)->evaluate($user);
        }
        if ($result['justCompleted']) $this->syncWorkoutActivity($user, $workout->name, $notifications);

        $notifications->sendSystem(
            $user,
            'trainer-workout-log-' . $assignment->id . '-' . $date . '-' . now()->format('YmdHis'),
            'Workout log updated',
            ($request->user()->full_name ?: $request->user()->name) . ' updated your ' . $workout->name . ' workout log for ' . Carbon::parse($date)->format('M j') . '.',
            $date === now()->toDateString() ? route('add-today', [], false) : route('charts.index', [], false)
        );

        return redirect()->route('trainer.clients.workout-logs.edit', [$user, $assignment, 'date' => $date])
            ->with('status', 'Client workout log saved.');
    }

    public function assignWorkout(
        Request $request,
        User $user,
        TrainerClientAccessService $access,
        NotificationService $notifications
    ) {
        $trainer = $request->user();
        $relationship = $access->relationship($trainer, $user);
        $validated = $request->validate([
            'workout_id' => ['required', 'integer', 'exists:workouts,id'],
            'name' => ['nullable', 'string', 'min:2', 'max:60'],
            'instructions' => ['nullable', 'string', 'max:2000'],
        ]);

        $source = Workout::query()
            ->with('exercises:id')
            ->where('user_id', $trainer->id)
            ->findOrFail($validated['workout_id']);
        abort_if($source->exercises->isEmpty(), 422, 'The selected workout has no exercises.');

        $assignment = DB::transaction(function () use ($relationship, $source, $user, $validated) {
            $copy = Workout::query()->create([
                'user_id' => $user->id,
                'name' => trim($validated['name'] ?? '') ?: $source->name,
                'estimated_duration_seconds' => $source->estimated_duration_seconds,
            ]);

            $copy->exercises()->attach(
                $source->exercises->values()->mapWithKeys(
                    fn ($exercise, $index) => [$exercise->id => ['sort_order' => $index]]
                )->all()
            );

            return TrainerWorkoutAssignment::query()->create([
                'trainer_client_id' => $relationship->id,
                'source_workout_id' => $source->id,
                'client_workout_id' => $copy->id,
                'instructions' => $validated['instructions'] ?? null,
                'assigned_at' => now(),
            ]);
        });

        $notifications->sendSystem(
            $user,
            'trainer-workout-'.$assignment->id,
            'New workout assigned',
            ($trainer->full_name ?: $trainer->name).' assigned “'.$assignment->clientWorkout->name.'” to you.',
            route('workouts.index', [], false)
        );

        return back()->with('status', 'Workout assigned to the client.');
    }

    public function updateNutrition(
        Request $request,
        User $user,
        TrainerClientAccessService $access,
        NotificationService $notifications
    ) {
        $trainer = $request->user();
        $access->relationship($trainer, $user, 'nutrition');
        $validated = $request->validateWithBag('nutrition', [
            'goal' => ['required', 'in:bulk,cut,recomp'],
            'calorie_target' => ['required', 'integer', 'min:800', 'max:8000'],
            'protein_g' => ['required', 'integer', 'min:0', 'max:500'],
            'carbs_g' => ['required', 'integer', 'min:0', 'max:1200'],
            'fat_g' => ['required', 'integer', 'min:0', 'max:400'],
            'water_l' => ['nullable', 'numeric', 'min:0', 'max:10'],
            'creatine_g' => ['nullable', 'numeric', 'min:0', 'max:20'],
        ]);

        $user->nutritionGoal()->updateOrCreate(['user_id' => $user->id], $validated);

        $notifications->sendSystem(
            $user,
            'trainer-nutrition-'.$user->id.'-'.now()->format('YmdHi'),
            'Nutrition targets updated',
            ($trainer->full_name ?: $trainer->name).' updated your nutrition targets.',
            route('add-today', [], false).'#measurements'
        );

        return back()->with('status', 'Client nutrition targets updated.');
    }

    public function updateDailyNutrition(
        Request $request,
        User $user,
        TrainerClientAccessService $access,
        ExperienceService $experience,
        NotificationService $notifications
    ) {
        $trainer = $request->user();
        $access->relationship($trainer, $user, 'nutrition');
        $validated = $request->validateWithBag('dailyNutrition', [
            'calories' => ['nullable', 'integer', 'min:0', 'max:50000'],
            'protein_g' => ['nullable', 'integer', 'min:0', 'max:1000'],
            'carbs_g' => ['nullable', 'integer', 'min:0', 'max:2000'],
            'fat_g' => ['nullable', 'integer', 'min:0', 'max:1000'],
            'creatine_g' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'water_ml' => ['nullable', 'integer', 'min:0', 'max:10000'],
        ]);
        $date = now()->toDateString();
        $values = collect(['calories', 'protein_g', 'carbs_g', 'fat_g', 'creatine_g', 'water_ml'])
            ->mapWithKeys(fn (string $field) => [$field => filled($validated[$field] ?? null) ? $validated[$field] : 0])
            ->all();

        $entry = NutritionEntry::query()->updateOrCreate(
            ['user_id' => $user->id, 'entry_date' => $date],
            $values
        );

        if (collect($values)->contains(fn ($value) => (float) $value > 0)) {
            $this->syncNutritionActivity($user, $notifications);
        }
        $experience->awardNutrition($user, $entry);
        app(\App\Services\AchievementService::class)->evaluate($user);

        $notifications->sendSystem(
            $user,
            'trainer-nutrition-entry-' . $trainer->id . '-' . $user->id . '-' . $date,
            'Today’s nutrition updated',
            ($trainer->full_name ?: $trainer->name) . ' updated your nutrition entry for today.',
            route('add-today', [], false)
        );

        return back()->with('status', 'Today’s client nutrition saved.');
    }

    public function report(
        Request $request,
        User $user,
        TrainerClientAccessService $access,
        WeeklyReportService $reports
    ) {
        $relationship = $access->relationship($request->user(), $user);
        $visibility = [
            'nutrition' => (bool) $relationship->can_view_nutrition,
            'training' => (bool) $relationship->can_view_exercises,
            'weight' => (bool) $relationship->can_view_weight,
        ];
        abort_unless(in_array(true, $visibility, true), 403, 'The client has not shared report data.');

        $report = $reports->build($user);
        $options = new Options();
        $options->set('defaultFont', 'DejaVu Sans');
        $options->set('isRemoteEnabled', false);
        $options->set('isPhpEnabled', false);

        $pdf = new Dompdf($options);
        $pdf->loadHtml(view('reports.weekly', compact('report', 'visibility'))->render(), 'UTF-8');
        $pdf->setPaper('a4', 'portrait');
        $pdf->render();

        return response($pdf->output(), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="client-weekly-report-'.$report['period']['start'].'.pdf"',
            'Cache-Control' => 'private, no-store, max-age=0',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    private function authorizedAssignment(
        Request $request,
        User $client,
        TrainerWorkoutAssignment $assignment,
        TrainerClientAccessService $access
    ) {
        $relationship = $access->relationship($request->user(), $client, 'exercises');
        abort_unless((int) $assignment->trainer_client_id === (int) $relationship->id, 404);
        $assignment->loadMissing('clientWorkout:id,user_id,name');
        abort_unless((int) $assignment->clientWorkout?->user_id === (int) $client->id, 404);

        return $relationship;
    }

    private function requestedLogDate(Request $request): string
    {
        $value = (string) $request->query('date', now()->toDateString());
        try {
            $date = Carbon::createFromFormat('Y-m-d', $value)->startOfDay();
        } catch (\Throwable) {
            $date = now()->startOfDay();
        }

        return $date->isFuture() ? now()->toDateString() : $date->toDateString();
    }

    private function syncWorkoutActivity(User $client, string $workoutName, NotificationService $notifications): void
    {
        $activity = FriendActivity::query()
            ->where('user_id', $client->id)
            ->where('type', 'workout')
            ->whereDate('created_at', now()->toDateString())
            ->first() ?? new FriendActivity([
                'user_id' => $client->id,
                'type' => 'workout',
            ]);

        $activity->text = 'completed "' . $workoutName . '" workout.';
        $activity->meta = ['date' => now()->toDateString(), 'entered_by_trainer' => true];
        $activity->save();
        $notifications->notifyFriendActivity($activity);
    }

    private function syncNutritionActivity(User $client, NotificationService $notifications): void
    {
        $activity = FriendActivity::query()
            ->where('user_id', $client->id)
            ->where('type', 'nutrition')
            ->whereDate('created_at', now()->toDateString())
            ->first() ?? new FriendActivity([
                'user_id' => $client->id,
                'type' => 'nutrition',
            ]);

        $activity->text = 'logged nutrition for today.';
        $activity->meta = ['date' => now()->toDateString(), 'entered_by_trainer' => true];
        $activity->save();
        $notifications->notifyFriendActivity($activity);
    }
}
