<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Carbon\Carbon;

use App\Models\LoginLog;
use App\Models\NutritionEntry;
use App\Models\WorkoutLog;

class StreaksController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $today = Carbon::today();

        // ---------- helpers ----------
        $calcStreakFromDateSet = function (array $dateSet) use ($today) {
            // dateSet: ['2026-03-01' => true, ...]
            $count = 0;
            $d = $today->copy();

            while (isset($dateSet[$d->toDateString()])) {
                $count++;
                $d->subDay();
            }

            return $count;
        };

        $macroStreak = function (string $col) use ($user, $today, $calcStreakFromDateSet) {
            // pull last ~400 days where that macro > 0
            $rows = NutritionEntry::query()
                ->where('user_id', $user->id)
                ->whereDate('entry_date', '<=', $today->toDateString())
                ->where($col, '>', 0)
                ->orderBy('entry_date', 'desc')
                ->limit(400)
                ->pluck('entry_date');

            $set = [];
            foreach ($rows as $d) {
                $set[Carbon::parse($d)->toDateString()] = true;
            }

            return $calcStreakFromDateSet($set);
        };

        // ---------- Login streak ----------
        $loginDates = LoginLog::query()
            ->where('user_id', $user->id)
            ->whereDate('login_date', '<=', $today->toDateString())
            ->orderBy('login_date', 'desc')
            ->limit(400)
            ->pluck('login_date');

        $loginSet = [];
        foreach ($loginDates as $d) {
            $loginSet[Carbon::parse($d)->toDateString()] = true;
        }
        $loginStreak = $calcStreakFromDateSet($loginSet);

        // ---------- Macro streaks (value > 0 that day) ----------
        $caloriesStreak = $macroStreak('calories');
        $proteinStreak  = $macroStreak('protein_g');
        $carbsStreak    = $macroStreak('carbs_g');
        $fatStreak      = $macroStreak('fat_g');
        $creatineStreak = $macroStreak('creatine_g');
        $waterStreak    = $macroStreak('water_ml');

        // ---------- Workout streak (workout_logs row exists for the day) ----------
        $workoutDates = WorkoutLog::query()
            ->where('user_id', $user->id)
            ->whereDate('entry_date', '<=', $today->toDateString())
            ->orderBy('entry_date', 'desc')
            ->limit(400)
            ->pluck('entry_date');

        $workoutSet = [];
        foreach ($workoutDates as $d) {
            $workoutSet[Carbon::parse($d)->toDateString()] = true;
        }
        $workoutStreak = $calcStreakFromDateSet($workoutSet);

        // ---------- daily “random” message (changes each day) ----------
        $messages = [
            "Small steps daily. Big results soon.",
            "Consistency beats motivation every time.",
            "Log it today. Thank yourself later.",
            "Your streak is your superpower.",
            "One more day. Keep the fire alive.",
            "Show up. Even if it's not perfect.",
        ];
        $seed = crc32($user->id . '|' . $today->toDateString());
        $dailyMessage = $messages[$seed % count($messages)];

        // LOGIN FIRST (as you asked)
        $streaks = [
            ['key'=>'login','title'=>'Login Streak','days'=>$loginStreak,'icon'=>'👥','accent'=>'login'],
            ['key'=>'calories','title'=>'Calories Streak','days'=>$caloriesStreak,'icon'=>'🔥','accent'=>'calories'],
            ['key'=>'protein','title'=>'Protein Streak','days'=>$proteinStreak,'icon'=>'🥩','accent'=>'protein'],
            ['key'=>'carbs','title'=>'Carbs Streak','days'=>$carbsStreak,'icon'=>'🍚','accent'=>'carbs'],
            ['key'=>'fat','title'=>'Fat Streak','days'=>$fatStreak,'icon'=>'🥜','accent'=>'fat'],
            ['key'=>'creatine','title'=>'Creatine Streak','days'=>$creatineStreak,'icon'=>'🧬','accent'=>'creatine'],
            ['key'=>'water','title'=>'Water Streak','days'=>$waterStreak,'icon'=>'💧','accent'=>'water'],
            ['key'=>'workout','title'=>'Workout Streak','days'=>$workoutStreak,'icon'=>'💪','accent'=>'workout'],
        ];

        $streaks = collect($streaks)->map(function (array $streak) {
            $days = $streak['days'];
            $rank = $days >= 100 ? intdiv($days, 100) : 0;

            $streak['flame_tier'] = match (true) {
                $days >= 300 => 'legendary',
                $days >= 200 => 'cosmic',
                $days >= 100 => 'inferno',
                $days >= 50 => 'blazing',
                $days >= 10 => 'ignited',
                default => 'spark',
            };
            $streak['flame_rank'] = $rank;
            $streak['milestone'] = match (true) {
                $days === 10 => 10,
                $days === 50 => 50,
                $days >= 100 && $days % 100 === 0 => $days,
                default => 0,
            };

            return $streak;
        })->all();

        return view('streaks.index', [
            'streaks' => $streaks,
            'dailyMessage' => $dailyMessage,
        ]);
    }
}
