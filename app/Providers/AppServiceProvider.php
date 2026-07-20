<?php

namespace App\Providers;

use App\Services\NotificationService;
use Illuminate\Support\Facades\Auth;
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
