<?php

namespace App\Services;

use App\Models\User;

class ChartAccessService
{
    public const FREE_PERIODS = ['week', 'month'];
    public const FULL_PERIODS = ['week', 'month', 'year', 'all'];

    public function periodsFor(User $user): array
    {
        return $user->hasFullChartAccess() ? self::FULL_PERIODS : self::FREE_PERIODS;
    }

    public function authorizePeriod(User $user, string $period): void
    {
        abort_unless(
            in_array($period, $this->periodsFor($user), true),
            403,
            'Year and all-time analytics require Paid access.'
        );
    }
}
