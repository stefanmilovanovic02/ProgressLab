<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('notifications:send-reminders')
    ->hourly()
    ->between('18:00', '23:00')
    ->timezone(config('app.timezone'))
    ->withoutOverlapping();

Schedule::command('subscriptions:maintain')
    ->hourlyAt(10)
    ->timezone(config('app.timezone'))
    ->withoutOverlapping();

Schedule::command('database:backup')
    ->dailyAt('03:30')
    ->timezone(config('app.timezone'))
    ->withoutOverlapping();
