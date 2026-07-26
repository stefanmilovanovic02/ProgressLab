<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\UserChartDataService;
use Illuminate\Http\Request;

class UserChartController extends Controller
{
    public function macros(Request $request, User $user, UserChartDataService $charts)
    {
        $validated = $request->validate([
            'macro' => ['nullable', 'in:calories,protein,carbs,fat,creatine,water'],
            'period' => ['nullable', 'in:week,month,year,all'],
        ]);

        return response()->json($charts->macro(
            $user,
            $validated['macro'] ?? 'calories',
            $validated['period'] ?? 'month'
        ));
    }

    public function exerciseData(Request $request, User $user, UserChartDataService $charts)
    {
        $validated = $request->validate([
            'exercise_id' => ['required', 'integer', 'exists:exercises,id'],
            'period' => ['nullable', 'in:week,month,year,all'],
        ]);

        return response()->json($charts->exercise(
            $user,
            (int) $validated['exercise_id'],
            $validated['period'] ?? 'all'
        ));
    }
}
