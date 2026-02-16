<?php

use Illuminate\Support\Facades\Route;

// Controllers (create later)
// use App\Http\Controllers\Auth\LoginController;
// use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\AddTodayController;
use App\Http\Controllers\WorkoutController;
use App\Http\Controllers\ChartsController;
use App\Http\Controllers\StreaksController;
use App\Http\Controllers\AchievementsController;
use App\Http\Controllers\FriendsController;
use App\Http\Controllers\ProfileController;

// Guest (Not logged in)
 // Login
Route::get('/login', [\App\Http\Controllers\Auth\LoginController::class, 'show'])
    ->name('login');
Route::post('/login', [\App\Http\Controllers\Auth\LoginController::class, 'store'])
    ->name('login.store');

// Register (basic info + multi-step macros wizard)
Route::get('/register', [\App\Http\Controllers\Auth\RegisterController::class, 'showStep1'])
    ->name('register');
Route::post('/register', [\App\Http\Controllers\Auth\RegisterController::class, 'storeStep1'])
    ->name('register.store.step1');

Route::get('/register/macros', [\App\Http\Controllers\Auth\RegisterController::class, 'showMacros'])
    ->name('register.macros');
Route::post('/register/macros', [\App\Http\Controllers\Auth\RegisterController::class, 'storeMacros'])
    ->name('register.store.macros');

// Logout (auth-only)
Route::post('/logout', [\App\Http\Controllers\Auth\LoginController::class, 'destroy'])
    ->middleware('auth')
    ->name('logout');

// App (Logged in users)
Route::middleware(['auth'])->group(function () {

    // 3) Home
    Route::get('/', [HomeController::class, 'index'])->name('home');

    // 4) Add Today (nutrition + workout logs)
    Route::get('/add-today', [AddTodayController::class, 'index'])->name('add-today');
    Route::post('/add-today/nutrition', [AddTodayController::class, 'storeNutrition'])->name('add-today.nutrition.store');
    Route::post('/add-today/workout', [AddTodayController::class, 'storeWorkout'])->name('add-today.workout.store');

    // 5) Workouts (create workouts from favorite exercises)
    Route::get('/workouts', [WorkoutController::class, 'index'])->name('workouts.index');
    Route::get('/workouts/create', [WorkoutController::class, 'create'])->name('workouts.create');
    Route::post('/workouts', [WorkoutController::class, 'store'])->name('workouts.store');
    Route::get('/workouts/{workout}', [WorkoutController::class, 'show'])->name('workouts.show');
    Route::get('/workouts/{workout}/edit', [WorkoutController::class, 'edit'])->name('workouts.edit');
    Route::put('/workouts/{workout}', [WorkoutController::class, 'update'])->name('workouts.update');
    Route::delete('/workouts/{workout}', [WorkoutController::class, 'destroy'])->name('workouts.destroy');

    // (optional) favorites/exercises helpers
    Route::post('/exercises/{exercise}/favorite', [WorkoutController::class, 'favorite'])->name('exercises.favorite');
    Route::delete('/exercises/{exercise}/favorite', [WorkoutController::class, 'unfavorite'])->name('exercises.unfavorite');

    // 6) Charts (nutrition + workouts)
    Route::get('/charts', [ChartsController::class, 'index'])->name('charts.index');
    Route::get('/charts/nutrition', [ChartsController::class, 'nutrition'])->name('charts.nutrition');
    Route::get('/charts/workouts', [ChartsController::class, 'workouts'])->name('charts.workouts');

    // 7) Streaks
    Route::get('/streaks', [StreaksController::class, 'index'])->name('streaks.index');

    // 8) Achievements
    Route::get('/achievements', [AchievementsController::class, 'index'])->name('achievements.index');

    // 9) Friends (list, add/remove, profiles, compare)
    Route::get('/friends', [FriendsController::class, 'index'])->name('friends.index');
    Route::post('/friends/request', [FriendsController::class, 'sendRequest'])->name('friends.request.send');
    Route::post('/friends/request/{request}/accept', [FriendsController::class, 'acceptRequest'])->name('friends.request.accept');
    Route::delete('/friends/request/{request}/decline', [FriendsController::class, 'declineRequest'])->name('friends.request.decline');
    Route::delete('/friends/{friend}', [FriendsController::class, 'remove'])->name('friends.remove');

    Route::get('/friends/{user}', [FriendsController::class, 'showProfile'])->name('friends.profile');
    Route::get('/compare/{user}', [FriendsController::class, 'compare'])->name('friends.compare');

    // 10) Profile (view + update)
    Route::get('/profile', [ProfileController::class, 'show'])->name('profile.show');
    Route::put('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::put('/profile/photo', [ProfileController::class, 'updatePhoto'])->name('profile.photo.update');
});