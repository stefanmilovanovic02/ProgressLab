<?php

namespace App\Console\Commands;

use App\Services\SubscriptionAccessService;
use Illuminate\Console\Command;

class MaintainSubscriptions extends Command
{
    protected $signature = 'subscriptions:maintain';

    protected $description = 'Expire ended plans, send renewal reminders, and synchronize paid account roles';

    public function handle(SubscriptionAccessService $subscriptions): int
    {
        $stats = $subscriptions->maintain();

        $this->info(
            "Subscriptions maintained: {$stats['expired']} expired, "
            . "{$stats['downgraded']} role changes, {$stats['reminded']} reminders."
        );

        return self::SUCCESS;
    }
}
