<?php

namespace Tests\Feature;

use App\Http\Middleware\TrackDailyLogin;
use App\Models\User;
use App\Models\Workout;
use App\Services\AchievementService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class WorkoutDurationTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware(TrackDailyLogin::class);
        Carbon::setTestNow('2026-07-24 18:00:00');

        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->string('password');
            $table->rememberToken();
            $table->timestamps();
        });

        Schema::create('workouts', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->string('name');
            $table->unsignedInteger('estimated_duration_seconds')->nullable();
            $table->timestamps();
        });

        Schema::create('exercises', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->timestamps();
        });

        Schema::create('workout_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('workout_id');
            $table->date('entry_date');
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->unsignedInteger('duration_seconds')->nullable();
            $table->timestamps();
            $table->unique(['user_id', 'entry_date']);
        });

        Schema::create('workout_log_exercises', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('workout_log_id');
            $table->unsignedBigInteger('exercise_id');
            $table->timestamps();
            $table->unique(['workout_log_id', 'exercise_id']);
        });

        Schema::create('workout_log_sets', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('workout_log_exercise_id');
            $table->unsignedTinyInteger('set_number');
            $table->unsignedSmallInteger('reps')->nullable();
            $table->decimal('weight_kg', 6, 2)->nullable();
            $table->timestamps();
            $table->unique(['workout_log_exercise_id', 'set_number']);
        });

        Schema::create('friend_activities', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->string('type');
            $table->string('text');
            $table->json('meta')->nullable();
            $table->timestamps();
        });

        Schema::create('experience_events', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->string('source_type');
            $table->string('source_key');
            $table->unsignedInteger('points');
            $table->string('description')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->unique(['user_id', 'source_type', 'source_key']);
        });
    }

    protected function tearDown(): void
    {
        Schema::dropIfExists('experience_events');
        Schema::dropIfExists('friend_activities');
        Schema::dropIfExists('workout_log_sets');
        Schema::dropIfExists('workout_log_exercises');
        Schema::dropIfExists('workout_logs');
        Schema::dropIfExists('exercises');
        Schema::dropIfExists('workouts');
        Schema::dropIfExists('users');
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_first_complete_set_starts_timer_and_last_set_finishes_first_estimate(): void
    {
        $user = User::query()->create([
            'name' => 'Timer User',
            'email' => 'timer@example.test',
            'password' => 'password',
        ]);
        $workout = Workout::query()->create([
            'user_id' => $user->id,
            'name' => 'Push Day',
        ]);
        $exerciseId = DB::table('exercises')->insertGetId([
            'name' => 'Bench Press',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->mock(AchievementService::class, function ($mock) {
            $mock->shouldReceive('evaluate')->once()->andReturn([]);
        });

        $runningPayload = $this->payload($workout, $exerciseId, [
            ['set_number' => 1, 'reps' => 8, 'weight_kg' => 80],
            ['set_number' => 2, 'reps' => null, 'weight_kg' => null],
        ]);

        $this->actingAs($user)
            ->postJson(route('add-today.workout.save'), $runningPayload)
            ->assertOk()
            ->assertJsonPath('timing.status', 'running')
            ->assertJsonPath('timing.duration_seconds', null);

        $this->assertDatabaseHas('workout_logs', [
            'user_id' => $user->id,
            'workout_id' => $workout->id,
            'started_at' => '2026-07-24 18:00:00',
            'completed_at' => null,
        ]);
        $this->assertNull($workout->fresh()->estimated_duration_seconds);
        $this->assertSame(0, DB::table('friend_activities')->count());

        Carbon::setTestNow('2026-07-24 18:15:00');
        $completePayload = $this->payload($workout, $exerciseId, [
            ['set_number' => 1, 'reps' => 8, 'weight_kg' => 80],
            ['set_number' => 2, 'reps' => 7, 'weight_kg' => 82.5],
        ]);

        $this->actingAs($user)
            ->postJson(route('add-today.workout.save'), $completePayload)
            ->assertOk()
            ->assertJsonPath('timing.status', 'completed')
            ->assertJsonPath('timing.duration_seconds', 900)
            ->assertJsonPath('timing.estimated_duration_seconds', 900);

        $this->assertSame(900, $workout->fresh()->estimated_duration_seconds);
        $this->assertSame('15 min', $workout->fresh()->estimated_duration_label);
        $this->assertSame(1, DB::table('friend_activities')->count());
        $this->assertSame(2, DB::table('experience_events')->count());

        Carbon::setTestNow('2026-07-24 18:30:00');
        $this->actingAs($user)
            ->postJson(route('add-today.workout.save'), $completePayload)
            ->assertOk()
            ->assertJsonPath('timing.duration_seconds', 900);

        $this->assertSame(900, $workout->fresh()->estimated_duration_seconds);
        $this->assertSame(1, DB::table('friend_activities')->count());
        $this->assertSame(2, DB::table('experience_events')->count());
    }

    private function payload(Workout $workout, int $exerciseId, array $sets): array
    {
        return [
            'workout_id' => $workout->id,
            'exercises' => [[
                'exercise_id' => $exerciseId,
                'sets' => $sets,
            ]],
        ];
    }
}
