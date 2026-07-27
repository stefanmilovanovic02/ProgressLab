<?php

namespace App\Providers;

use App\Services\NotificationService;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        RateLimiter::for('login', function (Request $request) {
            return Limit::perMinute(5)->by(
                strtolower((string) $request->input('email')) . '|' . $request->ip()
            );
        });

        RateLimiter::for('registration', function (Request $request) {
            return Limit::perHour(5)->by($request->ip());
        });

        RateLimiter::for('password-reset', function (Request $request) {
            return Limit::perMinute(3)->by(
                strtolower((string) $request->input('email')) . '|' . $request->ip()
            );
        });

        View::composer('components.navbar', function ($view) {
            $unreadNotificationCount = 0;

            if (Auth::check()) {
                $service = app(NotificationService::class);
                $service->syncForUser(Auth::user());
                $unreadNotificationCount = $service->unreadCount(Auth::user());
            }

            $view->with('unreadNotificationCount', $unreadNotificationCount);
        });
    }
}
