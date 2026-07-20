<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\WorkoutLog;
use App\Models\WorkoutLogExercise;
use App\Models\WorkoutLogSet;
use App\Services\ExerciseHistoryService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class ExerciseHistoryTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow('2026-07-20 12:00:00');

        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->string('password');
            $table->rememberToken();
            $table->timestamps();
        });

        Schema::create('workout_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('workout_id');
            $table->date('entry_date');
            $table->timestamps();
        });

        Schema::create('workout_log_exercises', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('workout_log_id');
            $table->unsignedBigInteger('exercise_id');
            $table->timestamps();
        });

        Schema::create('workout_log_sets', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('workout_log_exercise_id');
            $table->unsignedTinyInteger('set_number');
            $table->unsignedSmallInteger('reps')->nullable();
            $table->decimal('weight_kg', 6, 2)->nullable();
            $table->timestamps();
        });
    }

    protected function tearDown(): void
    {
        Schema::dropIfExists('workout_log_sets');
        Schema::dropIfExists('workout_log_exercises');
        Schema::dropIfExists('workout_logs');
        Schema::dropIfExists('users');
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_it_uses_each_exercises_latest_previous_session_and_ignores_today(): void
    {
        $user = User::query()->create([
            'name' => 'History User',
            'email' => 'history@example.test',
            'password' => 'password',
        ]);

        $this->createExerciseLog($user->id, 10, '2026-07-18', [[12, 70], [10, 75]]);
        $this->createExerciseLog($user->id, 10, '2026-07-19', [[10, 80], [8, 85], [6, 90]]);
        $this->createExerciseLog($user->id, 10, '2026-07-19', []);
        $this->createExerciseLog($user->id, 10, '2026-07-20', [[3, 120]]);

        $history = app(ExerciseHistoryService::class)->latestForUser($user, [10, 20]);

        $this->assertArrayHasKey('10', $history);
        $this->assertArrayNotHasKey('20', $history);
        $this->assertSame('2026-07-19', $history['10']['date']);
        $this->assertSame(10, $history['10']['sets'][0]['reps']);
        $this->assertSame(80.0, $history['10']['sets'][0]['weight_kg']);
        $this->assertSame(10, $history['10']['max_reps']);
        $this->assertSame(90.0, $history['10']['max_weight_kg']);
    }

    private function createExerciseLog(int $userId, int $exerciseId, string $date, array $sets): void
    {
        $log = WorkoutLog::query()->create([
            'user_id' => $userId,
            'workout_id' => 1,
            'entry_date' => $date,
        ]);
        $exercise = WorkoutLogExercise::query()->create([
            'workout_log_id' => $log->id,
            'exercise_id' => $exerciseId,
        ]);

        foreach ($sets as $index => [$reps, $weight]) {
            WorkoutLogSet::query()->create([
                'workout_log_exercise_id' => $exercise->id,
                'set_number' => $index + 1,
                'reps' => $reps,
                'weight_kg' => $weight,
            ]);
        }
    }
}
