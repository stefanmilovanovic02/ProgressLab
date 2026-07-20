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

            $updated = LoginLog::query()
                ->where('user_id', $user->id)
                ->where('login_date', $today)
                ->update(['updated_at' => now()]);

            if ($updated === 0) {
                LoginLog::create([
                    'user_id' => $user->id,
                    'login_date' => $today,
                ]);
            }
        }

        return $next($request);
    }
}
