<?php

use Illuminate\Support\Facades\Route;

// Controllers
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\Auth\ForgotPasswordController;
use App\Http\Controllers\Auth\ResetPasswordController;

use App\Http\Controllers\HomeController;
use App\Http\Controllers\AddTodayController;
use App\Http\Controllers\WorkoutController;
use App\Http\Controllers\ChartsController;
use App\Http\Controllers\StreaksController;
use App\Http\Controllers\AchievementsController;
use App\Http\Controllers\FriendsController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\NotificationsController;
use App\Http\Controllers\PushSubscriptionController;
use App\Http\Controllers\ProgressPhotoController;
use App\Http\Controllers\MeasurementsController;
use App\Http\Controllers\WeeklyReportController;
use App\Http\Controllers\LeaderboardController;
use App\Http\Controllers\Admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\Admin\UserController as AdminUserController;
use App\Http\Controllers\Admin\ExerciseController as AdminExerciseController;
use App\Http\Controllers\Admin\SubscriptionController as AdminSubscriptionController;
use App\Http\Controllers\Admin\OwnerProgressPhotoController;
use App\Http\Controllers\Admin\UserChartController as AdminUserChartController;
use App\Http\Controllers\TrainerRelationshipController;
use App\Http\Controllers\Trainer\DashboardController as TrainerDashboardController;
use App\Http\Controllers\Trainer\ChartController as TrainerChartController;
use App\Http\Controllers\Trainer\ClientManagementController as TrainerClientManagementController;
use App\Http\Controllers\SubscriptionPlansController;
use App\Http\Controllers\Admin\SubscriptionRequestController as AdminSubscriptionRequestController;

// Guest (Not logged in)
 Route::middleware('guest')->group(function () {

    // Login
    Route::get('/login', [LoginController::class, 'show'])->name('login');
    Route::post('/login', [LoginController::class, 'store'])
        ->middleware('throttle:login')
        ->name('login.store');

    // Register (basic info + multi-step macros wizard)
    Route::get('/register', [RegisterController::class, 'showStep1'])->name('register');
    Route::post('/register', [RegisterController::class, 'storeStep1'])
        ->middleware('throttle:registration')
        ->name('register.store.step1');

    Route::get('/register/macros', [RegisterController::class, 'showMacros'])->name('register.macros');
    Route::post('/register/macros', [RegisterController::class, 'storeMacros'])->name('register.store.macros');

    // Step 3 (Goal + macros)
Route::get('/register/goal', [RegisterController::class, 'showGoal'])->name('register.goal');
Route::post('/register/goal', [RegisterController::class, 'storeGoal'])->name('register.store.goal');


    // Forgot Password
    Route::get('/forgot-password', [ForgotPasswordController::class, 'show'])
        ->name('password.request');
    Route::post('/forgot-password', [ForgotPasswordController::class, 'send'])
        ->middleware('throttle:password-reset')
        ->name('password.email');

    // Reset Password (user arrives here from email link)
    Route::get('/reset-password/{token}', [ResetPasswordController::class, 'show'])
        ->name('password.reset');
    Route::post('/reset-password', [ResetPasswordController::class, 'update'])
        ->middleware('throttle:password-reset')
        ->name('password.update');
});

// Logout (auth-only)
Route::post('/logout', [LoginController::class, 'destroy'])
    ->middleware('auth')
    ->name('logout');

// App (Logged in users)
Route::middleware(['auth', 'track.daily.login'])->group(function () {

    // 3) Home
    // 3) Home
    Route::middleware('auth')->group(function () {
    Route::get('/', [HomeController::class, 'index'])->name('home');
});

    // 4) Add Today (nutrition + workout logs)
    Route::get('/add-today', [AddTodayController::class, 'index'])->name('add-today');
    Route::post('/add-today/nutrition', [AddTodayController::class, 'storeNutrition'])->name('add-today.nutrition.store');
    Route::post('/add-today/workout', [AddTodayController::class, 'storeWorkout'])->name('add-today.workout.store');
    Route::get('/add-today/workout/today', [AddTodayController::class, 'getTodayWorkout'])->name('add-today.workout.today');
    Route::post('/add-today/workout/save', [AddTodayController::class, 'saveTodayWorkout'])->name('add-today.workout.save');
    Route::post('/add-today/measurements/goals', [MeasurementsController::class, 'updateGoals'])
        ->name('add-today.measurements.goals');
    Route::post('/add-today/measurements/body', [MeasurementsController::class, 'storeBody'])
        ->name('add-today.measurements.body');
    Route::post('/progress-photos', [ProgressPhotoController::class, 'store'])->name('progress-photos.store');
    Route::get('/progress-photos/{progressPhoto}/{view}', [ProgressPhotoController::class, 'show'])
        ->where('view', 'front|side|back')
        ->name('progress-photos.show');

    // 5) Workouts (create workouts from favorite exercises)
    Route::get('/workouts', [WorkoutController::class, 'index'])->name('workouts.index');
    Route::get('/workouts/create', [WorkoutController::class, 'create'])->name('workouts.create');
    Route::post('/workouts', [WorkoutController::class, 'store'])->name('workouts.store');
    Route::get('/workouts/{workout}', [WorkoutController::class, 'show'])->name('workouts.show');
    Route::get('/workouts/{workout}/edit-data', [WorkoutController::class, 'editData'])->name('workouts.editData');
    Route::put('/workouts/{workout}', [WorkoutController::class, 'update'])->name('workouts.update');
    Route::delete('/workouts/{workout}', [WorkoutController::class, 'destroy'])->name('workouts.destroy');
    Route::get('/exercises/search', [WorkoutController::class, 'searchExercises'])->name('exercises.search');

    // (optional) favorites/exercises helpers
    Route::post('/exercises/{exercise}/favorite', [WorkoutController::class, 'favorite'])->name('exercises.favorite');
    Route::delete('/exercises/{exercise}/favorite', [WorkoutController::class, 'unfavorite'])->name('exercises.unfavorite');

    // 6) Charts (macros + workouts)
    Route::get('/charts', [ChartsController::class, 'index'])->name('charts.index');
    Route::get('/charts/macros', [ChartsController::class, 'macros'])->name('charts.macros');
    Route::get('/charts/exercise-data', [ChartsController::class, 'exerciseData'])->name('charts.exercise-data');
    Route::get('/charts/weekly-report.pdf', [WeeklyReportController::class, 'download'])
        ->name('charts.weekly-report.download');

    // 7) Streaks
    Route::get('/streaks', [StreaksController::class, 'index'])->name('streaks.index');

    // Leaderboards
    Route::get('/leaderboards', [LeaderboardController::class, 'index'])->name('leaderboards.index');
    Route::get('/leaderboards/data', [LeaderboardController::class, 'data'])->name('leaderboards.data');

    Route::get('/plans', [SubscriptionPlansController::class, 'index'])->name('plans.index');
    Route::post('/plans/request-activation', [SubscriptionPlansController::class, 'requestActivation'])
        ->name('plans.request-activation');

    // 8) Achievements
    Route::get('/achievements', [AchievementsController::class, 'index'])->name('achievements.index');
    Route::middleware('auth')->group(function () {
        Route::get('/achievements/notifications', [AchievementsController::class, 'notifications'])
            ->name('achievements.notifications');
    });

    // Notifications
    Route::get('/notifications', [NotificationsController::class, 'index'])->name('notifications.index');
    Route::post('/notifications/read-all', [NotificationsController::class, 'readAll'])->name('notifications.read-all');
    Route::post('/notifications/{notification}/read', [NotificationsController::class, 'read'])->name('notifications.read');
    Route::post('/notifications/{notification}/open', [NotificationsController::class, 'open'])->name('notifications.open');
    Route::post('/push-subscriptions', [PushSubscriptionController::class, 'store'])->name('push-subscriptions.store');
    Route::delete('/push-subscriptions', [PushSubscriptionController::class, 'destroy'])->name('push-subscriptions.destroy');
    Route::post('/push-subscriptions/test', [PushSubscriptionController::class, 'test'])->name('push-subscriptions.test');

    // 9) Friends (list, add/remove, profiles, compare)
    Route::middleware(['auth'])->group(function () {
    Route::get('/friends', [FriendsController::class, 'index'])->name('friends.index');

    Route::get('/friends/search', [FriendsController::class, 'search'])->name('friends.search');
    Route::post('/friends/request', [FriendsController::class, 'sendRequest'])->name('friends.request');
    Route::post('/friends/requests/{friendRequest}/accept', [FriendsController::class, 'accept'])->name('friends.requests.accept');
    Route::post('/friends/requests/{friendRequest}/decline', [FriendsController::class, 'decline'])->name('friends.requests.decline');
    Route::delete('/friends/{user}', [FriendsController::class, 'destroy'])->name('friends.destroy');
    
    Route::get('/friends/{user}/summary', [\App\Http\Controllers\FriendsController::class, 'summary'])->name('friends.summary');
        
    Route::get('/friends/{user}/comparison-exercises', [FriendsController::class, 'comparisonExercises'])
    ->name('friends.comparison-exercises');

    Route::get('/friends/{user}/exercise-comparison', [FriendsController::class, 'exerciseComparison'])
        ->name('friends.exercise-comparison');

    Route::post('/friends/{user}/trainer-invitation', [TrainerRelationshipController::class, 'invite'])
        ->name('trainer-invitations.store');
    Route::post('/trainer-invitations/{trainerClient}/accept', [TrainerRelationshipController::class, 'accept'])
        ->name('trainer-invitations.accept');
    Route::post('/trainer-invitations/{trainerClient}/decline', [TrainerRelationshipController::class, 'decline'])
        ->name('trainer-invitations.decline');
    Route::patch('/trainer-access/{trainerClient}', [TrainerRelationshipController::class, 'updatePermissions'])
        ->name('trainer-access.update');
    Route::delete('/trainer-access/{trainerClient}', [TrainerRelationshipController::class, 'destroy'])
        ->name('trainer-access.destroy');
    });

    Route::prefix('trainer')
        ->name('trainer.')
        ->middleware('role:trainer')
        ->group(function () {
            Route::get('/', [TrainerDashboardController::class, 'index'])->name('dashboard');
            Route::get('/clients/{user}', [TrainerDashboardController::class, 'show'])->name('clients.show');
            Route::patch('/clients/{user}/notes', [TrainerDashboardController::class, 'updateNotes'])->name('clients.notes');
            Route::post('/clients/{user}/workouts', [TrainerClientManagementController::class, 'assignWorkout'])->name('clients.workouts.store');
            Route::patch('/clients/{user}/nutrition-targets', [TrainerClientManagementController::class, 'updateNutrition'])->name('clients.nutrition-targets.update');
            Route::get('/clients/{user}/weekly-report.pdf', [TrainerClientManagementController::class, 'report'])->name('clients.weekly-report');
            Route::get('/clients/{user}/charts/macros', [TrainerChartController::class, 'macros'])->name('clients.charts.macros');
            Route::get('/clients/{user}/charts/exercise-data', [TrainerChartController::class, 'exerciseData'])->name('clients.charts.exercise-data');
            Route::get('/clients/{user}/charts/weight', [TrainerChartController::class, 'weight'])->name('clients.charts.weight');
        });
    
    // 10) Profile (view + update)
    Route::get('/profile', [ProfileController::class, 'show'])->name('profile.show');
    Route::put('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::put('/profile/photo', [ProfileController::class, 'updatePhoto'])->name('profile.photo.update');
    Route::put('/profile/cover', [ProfileController::class, 'updateCover'])->name('profile.cover.update');
    Route::get('/profile/password', [\App\Http\Controllers\PasswordController::class, 'edit'])->name('password.edit');
    Route::put('/profile/password', [\App\Http\Controllers\PasswordController::class, 'update'])->name('profile.password.update');
    Route::delete('/profile', [\App\Http\Controllers\ProfileController::class, 'destroy'])->name('profile.destroy');

    Route::prefix('admin')
        ->name('admin.')
        ->middleware('role:admin,owner')
        ->group(function () {
            Route::get('/', [AdminDashboardController::class, 'index'])->name('dashboard');
            Route::get('/users/{user}/charts/macros', [AdminUserChartController::class, 'macros'])
                ->name('users.charts.macros');
            Route::get('/users/{user}/charts/exercise-data', [AdminUserChartController::class, 'exerciseData'])
                ->name('users.charts.exercise-data');
            Route::resource('users', AdminUserController::class);
            Route::resource('exercises', AdminExerciseController::class)->except('show');

            Route::middleware('role:owner')->group(function () {
                Route::resource('subscriptions', AdminSubscriptionController::class)->except('show');
                Route::post('/subscription-requests/{subscriptionRequest}/approve', [AdminSubscriptionRequestController::class, 'approve'])
                    ->name('subscription-requests.approve');
                Route::post('/subscription-requests/{subscriptionRequest}/reject', [AdminSubscriptionRequestController::class, 'reject'])
                    ->name('subscription-requests.reject');
                Route::get('/progress-photos/{progressPhoto}/{view}', [OwnerProgressPhotoController::class, 'show'])
                    ->where('view', 'front|side|back')
                    ->name('progress-photos.show');
            });
        });

});
