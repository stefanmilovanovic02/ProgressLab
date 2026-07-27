<?php

namespace App\Services;

use App\Enums\UserRole;
use App\Models\Subscription;
use App\Models\TrainerClient;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class SubscriptionAccessService
{
    public function __construct(private readonly NotificationService $notifications)
    {
    }

    /**
     * Expire ended subscriptions, notify members, and synchronize account roles.
     *
     * @return array{expired:int, downgraded:int, reminded:int}
     */
    public function maintain(?Carbon $date = null): array
    {
        $date ??= today();
        $stats = ['expired' => 0, 'downgraded' => 0, 'reminded' => 0];

        if (!Schema::hasTable('subscriptions')) {
            return $stats;
        }

        Subscription::query()
            ->with('user')
            ->whereIn('status', ['active', 'trial'])
            ->whereNotNull('ends_on')
            ->whereDate('ends_on', '<', $date)
            ->orderBy('id')
            ->chunkById(100, function ($subscriptions) use ($date, &$stats) {
                foreach ($subscriptions as $subscription) {
                    DB::transaction(function () use ($subscription, $date, &$stats) {
                        $locked = Subscription::query()->lockForUpdate()->find($subscription->id);

                        if (
                            !$locked
                            || !in_array($locked->status, ['active', 'trial'], true)
                            || !$locked->ends_on
                            || !$locked->ends_on->lt($date)
                        ) {
                            return;
                        }

                        $locked->update(['status' => 'expired']);
                        $stats['expired']++;

                        $user = $locked->user()->first();
                        if (!$user) {
                            return;
                        }

                        if ($this->syncUserAccess($user, true)) {
                            $stats['downgraded']++;
                        }

                        $this->notifications->sendSystem(
                            $user,
                            'subscription-expired-' . $locked->id,
                            'Your plan has expired',
                            'Your paid ProgressLab access ended. Renew your plan to unlock the paid features again.',
                            route('plans.index', [], false)
                        );
                    });
                }
            });

        foreach ([0, 3] as $daysUntilExpiry) {
            $expiryDate = $date->copy()->addDays($daysUntilExpiry);

            Subscription::query()
                ->with('user')
                ->currentlyActive($date)
                ->where('is_complimentary', false)
                ->whereDate('ends_on', $expiryDate)
                ->orderBy('id')
                ->chunkById(100, function ($subscriptions) use ($daysUntilExpiry, &$stats) {
                    foreach ($subscriptions as $subscription) {
                        if (!$subscription->user) {
                            continue;
                        }

                        $message = $daysUntilExpiry === 0
                            ? 'Your plan expires today. Renew it to keep your paid ProgressLab access.'
                            : 'Your plan expires in 3 days. Renew it to avoid losing paid ProgressLab access.';

                        $this->notifications->sendSystem(
                            $subscription->user,
                            'subscription-expiry-reminder-' . $subscription->id . '-' . $subscription->ends_on->toDateString(),
                            $daysUntilExpiry === 0 ? 'Your plan expires today' : 'Your plan expires soon',
                            $message,
                            route('plans.index', [], false)
                        );
                        $stats['reminded']++;
                    }
                });
        }

        return $stats;
    }

    /**
     * Synchronize a Paid or Trainer role with current subscription records.
     *
     * Direct Owner-granted roles with no subscription history are preserved.
     */
    public function syncUserAccess(User $user, bool $subscriptionManaged = false): bool
    {
        $user->refresh();

        if ($user->isAdmin()) {
            return false;
        }

        $activePlans = $user->subscriptions()
            ->currentlyActive()
            ->pluck('plan');

        $targetRole = match (true) {
            $activePlans->contains('trainer') => UserRole::Trainer,
            $activePlans->contains('paid') => UserRole::Paid,
            $activePlans->isNotEmpty() => $user->role,
            default => null,
        };

        if ($targetRole === null) {
            $hasSubscriptionHistory = $user->subscriptions()->exists();

            if (!$subscriptionManaged && !$hasSubscriptionHistory) {
                return false;
            }

            $targetRole = UserRole::User;
        }

        if ($user->role === $targetRole) {
            return false;
        }

        $wasTrainer = $user->isTrainer();
        $user->forceFill(['role' => $targetRole])->save();

        if (
            $wasTrainer
            && $targetRole !== UserRole::Trainer
            && Schema::hasTable('trainer_clients')
        ) {
            TrainerClient::query()
                ->where('trainer_id', $user->id)
                ->whereIn('status', [
                    TrainerClient::STATUS_PENDING,
                    TrainerClient::STATUS_ACCEPTED,
                ])
                ->update([
                    'status' => 'revoked',
                    'revoked_at' => now(),
                    'updated_at' => now(),
                ]);
        }

        return true;
    }
}
