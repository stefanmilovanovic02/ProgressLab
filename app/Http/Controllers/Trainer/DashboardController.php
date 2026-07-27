<?php

namespace App\Http\Controllers\Trainer;

use App\Http\Controllers\Controller;
use App\Models\TrainerClient;
use App\Services\TrainerClientAccessService;
use App\Services\TrainerClientStatsService;
use App\Services\UserChartDataService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $trainer = $request->user();
        $relationships = TrainerClient::query()
            ->with('client:id,name,full_name,username,email,avatar_path')
            ->where('trainer_id', $trainer->id)
            ->whereIn('status', [TrainerClient::STATUS_ACCEPTED, TrainerClient::STATUS_PENDING])
            ->latest('updated_at')
            ->get();

        $accepted = $relationships->where('status', TrainerClient::STATUS_ACCEPTED);
        $streakClientIds = $accepted->where('can_view_streaks', true)->pluck('client_id');
        $nutritionClientIds = $accepted->where('can_view_nutrition', true)->pluck('client_id');
        $exerciseClientIds = $accepted->where('can_view_exercises', true)->pluck('client_id');
        $weekStart = now()->subDays(6)->toDateString();
        $today = now()->toDateString();

        $activeThisWeek = $streakClientIds->isEmpty() ? 0 : DB::table('login_logs')
            ->whereIn('user_id', $streakClientIds)
            ->whereDate('login_date', '>=', $weekStart)
            ->distinct('user_id')
            ->count('user_id');
        $nutritionToday = $nutritionClientIds->isEmpty() ? collect() : DB::table('nutrition_entries')
            ->whereIn('user_id', $nutritionClientIds)
            ->whereDate('entry_date', $today)
            ->pluck('user_id');
        $lastLogins = $streakClientIds->isEmpty() ? collect() : DB::table('login_logs')
            ->whereIn('user_id', $streakClientIds)
            ->groupBy('user_id')
            ->selectRaw('user_id, MAX(login_date) as last_login')
            ->pluck('last_login', 'user_id');

        $clientRows = $accepted->map(function (TrainerClient $relationship) use ($nutritionToday, $lastLogins) {
            $client = $relationship->client;
            $sharesNutrition = $relationship->can_view_nutrition;
            $sharesStreaks = $relationship->can_view_streaks;
            $lastLogin = $sharesStreaks ? ($lastLogins[$client->id] ?? null) : null;

            return [
                'relationship' => $relationship,
                'client' => $client,
                'nutrition_today' => $sharesNutrition ? $nutritionToday->contains($client->id) : null,
                'last_login' => $lastLogin,
                'streak_at_risk' => $sharesStreaks
                    ? (!$lastLogin || \Illuminate\Support\Carbon::parse($lastLogin)->lt(now()->subDay()->startOfDay()))
                    : null,
            ];
        });

        $recentRecords = $exerciseClientIds->isEmpty() ? collect() : DB::table('workout_log_sets as sets')
            ->join('workout_log_exercises as logged', 'logged.id', '=', 'sets.workout_log_exercise_id')
            ->join('workout_logs as logs', 'logs.id', '=', 'logged.workout_log_id')
            ->join('exercises', 'exercises.id', '=', 'logged.exercise_id')
            ->join('users', 'users.id', '=', 'logs.user_id')
            ->whereIn('logs.user_id', $exerciseClientIds)
            ->whereDate('logs.entry_date', '>=', now()->subDays(13)->toDateString())
            ->whereNotNull('sets.weight_kg')
            ->whereRaw('sets.weight_kg = (
                SELECT MAX(all_sets.weight_kg)
                FROM workout_log_sets AS all_sets
                INNER JOIN workout_log_exercises AS all_logged ON all_logged.id = all_sets.workout_log_exercise_id
                INNER JOIN workout_logs AS all_logs ON all_logs.id = all_logged.workout_log_id
                WHERE all_logs.user_id = logs.user_id AND all_logged.exercise_id = logged.exercise_id
            )')
            ->latest('logs.entry_date')
            ->limit(8)
            ->get([
                'users.name as client_name',
                'users.full_name as client_full_name',
                'exercises.name as exercise_name',
                'sets.weight_kg',
                'sets.reps',
                'logs.entry_date',
            ]);

        return view('trainer.index', [
            'relationships' => $relationships,
            'clients' => $clientRows,
            'pending' => $relationships->where('status', TrainerClient::STATUS_PENDING),
            'summary' => [
                'total' => $accepted->count(),
                'active_this_week' => $activeThisWeek,
                'missing_nutrition' => max(0, $nutritionClientIds->count() - $nutritionToday->unique()->count()),
                'streak_at_risk' => $clientRows->where('streak_at_risk', true)->count(),
            ],
            'recentRecords' => $recentRecords,
        ]);
    }

    public function show(
        Request $request,
        \App\Models\User $user,
        TrainerClientAccessService $access,
        TrainerClientStatsService $stats,
        UserChartDataService $charts
    ) {
        $relationship = $access->relationship($request->user(), $user);

        return view('trainer.show', [
            'client' => $user,
            'relationship' => $relationship,
            'streaks' => $relationship->can_view_streaks ? $stats->streaks($user) : null,
            'chartExercises' => $relationship->can_view_exercises ? $charts->exercises($user) : collect(),
            'trainerWorkouts' => Schema::hasTable('workouts') && Schema::hasTable('exercise_workout')
                ? \App\Models\Workout::query()
                    ->where('user_id', $request->user()->id)
                    ->withCount('exercises')
                    ->orderBy('name')
                    ->get()
                : collect(),
            'assignments' => Schema::hasTable('trainer_workout_assignments')
                ? $relationship->workoutAssignments()
                    ->with(['clientWorkout.exercises:id,name'])
                    ->latest('assigned_at')
                    ->get()
                : collect(),
            'nutritionGoal' => $relationship->can_view_nutrition && Schema::hasTable('nutrition_goals')
                ? $user->nutritionGoal
                : null,
        ]);
    }

    public function updateNotes(Request $request, \App\Models\User $user, TrainerClientAccessService $access)
    {
        $relationship = $access->relationship($request->user(), $user);
        $validated = $request->validate(['trainer_notes' => ['nullable', 'string', 'max:5000']]);
        $relationship->update($validated);

        return back()->with('status', 'Private client notes saved.');
    }
}
