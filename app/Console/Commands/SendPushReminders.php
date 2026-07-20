<?php

namespace App\Console\Commands;

use App\Models\LoginLog;
use App\Models\User;
use App\Services\NotificationService;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Schema;

class SendPushReminders extends Command
{
    protected $signature = 'notifications:send-reminders';

    protected $description = 'Send streak-expiry and gentle return reminders to opted-in users';

    public function handle(NotificationService $notifications): int
    {
        if (!Schema::hasTable('push_subscriptions') || !Schema::hasTable('login_logs')) {
            $this->warn('Push subscription or login log tables are not available.');

            return self::SUCCESS;
        }

        $today = Carbon::today();
        $sent = 0;

        User::query()
            ->whereHas('pushSubscriptions')
            ->chunkById(100, function ($users) use ($notifications, $today, &$sent) {
                foreach ($users as $user) {
                    $dates = LoginLog::query()
                        ->where('user_id', $user->id)
                        ->whereDate('login_date', '<=', $today->toDateString())
                        ->latest('login_date')
                        ->limit(400)
                        ->pluck('login_date')
                        ->map(fn ($date) => Carbon::parse($date)->toDateString())
                        ->unique()
                        ->values();

                    if ($dates->isEmpty() || $dates->contains($today->toDateString())) {
                        continue;
                    }

                    $yesterday = $today->copy()->subDay();

                    if ($dates->contains($yesterday->toDateString())) {
                        $streakDays = 0;
                        $cursor = $yesterday->copy();
                        $dateSet = $dates->flip();

                        while ($dateSet->has($cursor->toDateString())) {
                            $streakDays++;
                            $cursor->subDay();
                        }

                        $notifications->sendSystem(
                            $user,
                            'streak-expiring-' . $today->toDateString(),
                            'Your streak expires tonight 🔥',
                            'Open ProgressLab today to protect your ' . $streakDays . '-day login streak.',
                            route('home', [], false)
                        );
                        $sent++;

                        continue;
                    }

                    $lastLogin = Carbon::parse($dates->first());
                    if ($lastLogin->diffInDays($today) >= 3) {
                        $notifications->sendSystem(
                            $user,
                            'return-reminder-' . $today->copy()->startOfWeek()->toDateString(),
                            'Ready for your next step?',
                            'A quick ProgressLab check-in keeps your goals and training progress moving.',
                            route('add-today', [], false)
                        );
                        $sent++;
                    }
                }
            });

        $this->info("Created {$sent} reminder notification(s).");

        return self::SUCCESS;
    }
}
