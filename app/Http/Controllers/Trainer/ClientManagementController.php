<?php

namespace App\Http\Controllers\Trainer;

use App\Http\Controllers\Controller;
use App\Models\TrainerWorkoutAssignment;
use App\Models\User;
use App\Models\Workout;
use App\Services\NotificationService;
use App\Services\TrainerClientAccessService;
use App\Services\WeeklyReportService;
use Dompdf\Dompdf;
use Dompdf\Options;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ClientManagementController extends Controller
{
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
}
