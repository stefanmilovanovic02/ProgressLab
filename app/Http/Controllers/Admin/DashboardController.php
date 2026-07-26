<?php

namespace App\Http\Controllers\Admin;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $stats = [
            'users' => User::query()->count(),
            'paid' => User::query()->where('role', UserRole::Paid->value)->count(),
            'trainers' => User::query()->where('role', UserRole::Trainer->value)->count(),
            'staff' => User::query()->whereIn('role', [UserRole::Admin->value, UserRole::Owner->value])->count(),
            'exercises' => DB::table('exercises')->count(),
            'workouts' => DB::table('workout_logs')->count(),
        ];

        $roleCounts = User::query()
            ->selectRaw('role, COUNT(*) as total')
            ->groupBy('role')
            ->pluck('total', 'role');

        $activity = DB::table('login_logs')
            ->whereDate('login_date', '>=', now()->subDays(13)->toDateString())
            ->selectRaw('date(login_date) as day, COUNT(DISTINCT user_id) as total')
            ->groupByRaw('date(login_date)')
            ->pluck('total', 'day');

        $activityLabels = [];
        $activityValues = [];
        for ($day = 13; $day >= 0; $day--) {
            $date = now()->subDays($day);
            $activityLabels[] = $date->format('M j');
            $activityValues[] = (int) ($activity[$date->toDateString()] ?? 0);
        }

        $recentUsers = User::query()
            ->latest()
            ->limit(8)
            ->get(['id', 'name', 'full_name', 'username', 'email', 'role', 'created_at']);

        $ownerMetrics = null;
        if ($request->user()->isOwner()) {
            $ownerMetrics = [
                'subscriptions' => DB::table('subscriptions')->where('is_complimentary', false)->count(),
                'active_subscriptions' => DB::table('subscriptions')
                    ->where('is_complimentary', false)
                    ->where('status', 'active')
                    ->where(fn ($query) => $query->whereNull('ends_on')->orWhereDate('ends_on', '>=', today()))
                    ->count(),
                'complimentary_access' => DB::table('subscriptions')
                    ->where('is_complimentary', true)
                    ->where('status', 'active')
                    ->where(fn ($query) => $query->whereNull('ends_on')->orWhereDate('ends_on', '>=', today()))
                    ->count(),
                'revenue' => (float) DB::table('subscriptions')
                    ->where('is_complimentary', false)
                    ->whereNotIn('status', ['refunded'])
                    ->sum('amount_paid'),
                'monthly_revenue' => (float) DB::table('subscriptions')
                    ->where('is_complimentary', false)
                    ->whereNotIn('status', ['refunded'])
                    ->whereBetween('paid_at', [now()->startOfMonth(), now()->endOfMonth()])
                    ->sum('amount_paid'),
            ];
        }

        return view('admin.dashboard', compact(
            'stats',
            'ownerMetrics',
            'roleCounts',
            'activityLabels',
            'activityValues',
            'recentUsers'
        ));
    }
}
