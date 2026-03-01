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
            $today = now()->toDateString();

            $already = LoginLog::where('user_id', $user->id)
                ->whereDate('login_date', $today)
                ->exists();

            if (!$already) {
                LoginLog::create([
                    'user_id' => $user->id,
                    // store as start-of-day (safe for datetime columns)
                    'login_date' => now()->startOfDay(),
                ]);
            }
        }

        return $next($request);
    }
}