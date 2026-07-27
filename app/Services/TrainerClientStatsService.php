<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class TrainerClientStatsService
{
    public function streaks(User $user): array
    {
        return [
            'login' => $this->consecutive(DB::table('login_logs')->where('user_id', $user->id)->pluck('login_date')),
            'workout' => $this->consecutive(DB::table('workout_logs')->where('user_id', $user->id)->pluck('entry_date')),
            'nutrition' => $this->consecutive(DB::table('nutrition_entries')->where('user_id', $user->id)->pluck('entry_date')),
        ];
    }

    private function consecutive(Collection $dates): int
    {
        $set = $dates->map(fn ($date) => Carbon::parse($date)->toDateString())->unique()->flip();
        $cursor = now()->startOfDay();
        if (!$set->has($cursor->toDateString())) {
            $cursor->subDay();
            if (!$set->has($cursor->toDateString())) {
                return 0;
            }
        }

        $streak = 0;
        while ($set->has($cursor->toDateString())) {
            $streak++;
            $cursor->subDay();
        }
        return $streak;
    }
}
