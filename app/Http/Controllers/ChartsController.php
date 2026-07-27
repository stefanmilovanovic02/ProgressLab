<?php

namespace App\Http\Controllers;

use App\Models\WorkoutLogExercise;
use App\Services\ChartAccessService;
use App\Services\UserChartDataService;
use App\Services\WeeklyReportService;
use App\Services\ActivityCalendarService;
use Illuminate\Http\Request;

class ChartsController extends Controller
{
    public function index(
        Request $request,
        ChartAccessService $access,
        WeeklyReportService $weeklyReports,
        ActivityCalendarService $activityCalendars
    )
    {
        $user = $request->user();
        $defaultMacro = 'calories';
        $defaultPeriod = 'month';
        $availablePeriods = $access->periodsFor($user);
        $hasFullChartAccess = $user->hasFullChartAccess();
        $weeklyReport = $hasFullChartAccess ? $weeklyReports->build($user) : null;
        $activityCalendar = $activityCalendars->build($user);

        $exercises = WorkoutLogExercise::query()
            ->join('workout_logs', 'workout_logs.id', '=', 'workout_log_exercises.workout_log_id')
            ->join('exercises', 'exercises.id', '=', 'workout_log_exercises.exercise_id')
            ->where('workout_logs.user_id', $user->id)
            ->select('exercises.id', 'exercises.name')
            ->distinct()
            ->orderBy('exercises.name')
            ->get();

        $progressPhotos = $user->progressPhotoSets()
            ->orderBy('captured_on')
            ->orderBy('id')
            ->get(['id', 'captured_on'])
            ->map(fn ($photoSet) => [
                'id' => $photoSet->id,
                'date' => $photoSet->captured_on->toDateString(),
                'label' => $photoSet->captured_on->format('M j, Y'),
                'urls' => [
                    'front' => route('progress-photos.show', [$photoSet, 'front'], false),
                    'side' => route('progress-photos.show', [$photoSet, 'side'], false),
                    'back' => route('progress-photos.show', [$photoSet, 'back'], false),
                ],
            ])
            ->values();

        return view('charts.index', compact(
            'defaultMacro',
            'defaultPeriod',
            'exercises',
            'progressPhotos',
            'availablePeriods',
            'hasFullChartAccess',
            'weeklyReport',
            'activityCalendar'
        ));
    }

    public function macros(Request $request, ChartAccessService $access, UserChartDataService $charts)
    {
        $validated = $request->validate([
            'macro' => ['nullable', 'in:calories,protein,carbs,fat,creatine,water'],
            'period' => ['nullable', 'in:week,month,year,all'],
        ]);
        $period = $validated['period'] ?? 'month';
        $access->authorizePeriod($request->user(), $period);

        return response()->json(
            $charts->macro($request->user(), $validated['macro'] ?? 'calories', $period)
        );
    }

    public function exerciseData(Request $request, ChartAccessService $access, UserChartDataService $charts)
    {
        $validated = $request->validate([
            'exercise_id' => ['required', 'integer', 'exists:exercises,id'],
            'period' => ['nullable', 'in:week,month,year,all'],
        ]);
        $period = $validated['period'] ?? 'month';
        $access->authorizePeriod($request->user(), $period);

        return response()->json(
            $charts->exercise($request->user(), (int) $validated['exercise_id'], $period)
        );
    }
}
