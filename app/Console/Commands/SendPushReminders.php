<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Services\NotificationService;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class SendPushReminders extends Command
{
    protected $signature = 'notifications:send-reminders {--now= : Override the current time for testing}';

    protected $description = 'Send daytime nutrition, evening goal, workout, and streak-expiry reminders';

    public function handle(NotificationService $notifications): int
    {
        if (!Schema::hasTable('push_subscriptions') || !Schema::hasTable('app_notifications')) {
            $this->warn('Push subscription or notification tables are not available.');
            return self::SUCCESS;
        }

        $timezone = (string) config('app.timezone', 'UTC');
        $now = $this->option('now')
            ? Carbon::parse((string) $this->option('now'), $timezone)
            : Carbon::now($timezone);

        if ($now->hour < 8 || $now->hour >= 22) {
            $this->info('Quiet hours are active; no reminders were created.');
            return self::SUCCESS;
        }

        $sent = 0;
        User::query()->whereHas('pushSubscriptions')->chunkById(
            100,
            function ($users) use ($notifications, $now, &$sent) {
                foreach ($users as $user) {
                    $sent += $this->sendNutritionReminders($user, $notifications, $now);
                    $sent += $this->sendWorkoutReminder($user, $notifications, $now);
                    $sent += $this->sendStreakExpiryReminders($user, $notifications, $now);
                }
            }
        );

        $this->info("Created {$sent} reminder notification(s).");
        return self::SUCCESS;
    }

    private function sendNutritionReminders(User $user, NotificationService $notifications, Carbon $now): int
    {
        if (!Schema::hasTable('nutrition_entries')) return 0;

        $today = $now->toDateString();
        $entry = DB::table('nutrition_entries')
            ->where('user_id', $user->id)
            ->whereDate('entry_date', $today)
            ->first();
        $created = 0;

        $middayHour = 11 + (abs(crc32($user->id.'|'.$today)) % 5);
        if (!$entry && $now->hour >= $middayHour && $now->hour < 20) {
            $created += $this->notifyOnce(
                $notifications,
                $user,
                'nutrition-midday-'.$today,
                'Quick nutrition check-in',
                'You have not logged today’s food yet. Add what you have eaten so far while it is still easy to remember.',
                route('add-today', [], false)
            );
        }

        if ($now->hour < 20) return $created;

        if (!$entry) {
            return $created + $this->notifyOnce(
                $notifications,
                $user,
                'nutrition-evening-'.$today,
                'Don’t forget today’s nutrition',
                'The day is nearly over and today’s macros are still empty. Log them now to protect your nutrition streak.',
                route('add-today', [], false)
            );
        }

        if (!Schema::hasTable('nutrition_goals')) return $created;
        $goal = DB::table('nutrition_goals')->where('user_id', $user->id)->first();
        if (!$goal) return $created;

        $targets = [
            'calories' => ['target' => 'calorie_target', 'label' => 'calories'],
            'protein_g' => ['target' => 'protein_g', 'label' => 'protein'],
            'carbs_g' => ['target' => 'carbs_g', 'label' => 'carbohydrates'],
            'fat_g' => ['target' => 'fat_g', 'label' => 'fat'],
        ];
        $unfinished = [];
        foreach ($targets as $entryField => $definition) {
            $target = (float) ($goal->{$definition['target']} ?? 0);
            if ($target > 0 && (float) ($entry->{$entryField} ?? 0) < $target) {
                $unfinished[] = $definition['label'];
            }
        }

        if ($unfinished === []) return $created;
        $summary = count($unfinished) > 2
            ? implode(', ', array_slice($unfinished, 0, 2)).' and more'
            : implode(' and ', $unfinished);

        return $created + $this->notifyOnce(
            $notifications,
            $user,
            'nutrition-goal-evening-'.$today,
            'Your nutrition goal is not finished',
            'You are still below today’s '.$summary.' target. Check whether you forgot to log a meal.',
            route('add-today', [], false)
        );
    }

    private function sendWorkoutReminder(User $user, NotificationService $notifications, Carbon $now): int
    {
        if ($now->hour < 20 || !Schema::hasTable('workout_logs')) return 0;

        $today = $now->toDateString();
        $logged = DB::table('workout_logs')
            ->where('user_id', $user->id)
            ->whereDate('entry_date', $today)
            ->exists();
        if ($logged) return 0;

        return $this->notifyOnce(
            $notifications,
            $user,
            'workout-evening-'.$today,
            'No workout logged today',
            'If today was a training day, log your workout before the day ends so your progress and streak stay accurate.',
            route('add-today', [], false)
        );
    }

    private function sendStreakExpiryReminders(User $user, NotificationService $notifications, Carbon $now): int
    {
        $sources = [
            'login' => ['table' => 'login_logs', 'title' => 'Your login streak is close to expiring', 'message' => 'Open ProgressLab now to keep your login streak active.', 'url' => route('home', [], false)],
            'nutrition' => ['table' => 'nutrition_entries', 'title' => 'Your nutrition streak is close to expiring', 'message' => 'Log today’s macros before your 24-hour nutrition window closes.', 'url' => route('add-today', [], false)],
            'workout' => ['table' => 'workout_logs', 'title' => 'Your workout streak is close to expiring', 'message' => 'Log your latest training session before your workout streak window closes.', 'url' => route('add-today', [], false)],
        ];
        $created = 0;

        foreach ($sources as $type => $source) {
            if (!Schema::hasTable($source['table']) || !Schema::hasColumn($source['table'], 'created_at')) continue;

            $last = DB::table($source['table'])
                ->where('user_id', $user->id)
                ->whereNotNull('created_at')
                ->orderByDesc('created_at')
                ->first(['id', 'created_at']);
            if (!$last) continue;

            $expiresAt = Carbon::parse($last->created_at, config('app.timezone'))->addHours(24);
            $remindAt = $this->reminderTime($expiresAt);
            if ($now->lt($remindAt) || $now->gte($expiresAt)) continue;

            $created += $this->notifyOnce(
                $notifications,
                $user,
                $type.'-streak-expiry-'.$last->id,
                $source['title'],
                $source['message'],
                $source['url']
            );
        }

        return $created;
    }

    private function reminderTime(Carbon $expiresAt): Carbon
    {
        if ($expiresAt->hour < 8) return $expiresAt->copy()->subDay()->setTime(20, 0);
        if ($expiresAt->hour >= 22) return $expiresAt->copy()->setTime(20, 0);
        return $expiresAt->copy()->subHours(2);
    }

    private function notifyOnce(
        NotificationService $notifications,
        User $user,
        string $key,
        string $title,
        string $message,
        string $url
    ): int {
        $notification = $notifications->sendSystem($user, $key, $title, $message, $url);
        return $notification->wasRecentlyCreated ? 1 : 0;
    }
}
