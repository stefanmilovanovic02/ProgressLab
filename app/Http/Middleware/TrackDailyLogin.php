<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Models\LoginLog;

class TrackDailyLogin
{
    public function handle(Request $request, Closure $next)
    {
        $user = $request->user();

        if ($user) {
            $activityAt = now();
            $loginDate = $activityAt->copy()->startOfDay();

            LoginLog::query()->upsert(
                [[
                    'user_id' => $user->id,
                    'login_date' => $loginDate,
                    'created_at' => $activityAt,
                    'updated_at' => $activityAt,
                ]],
                ['user_id', 'login_date'],
                ['updated_at']
            );
        }

        return $next($request);
    }
}
