<?php

namespace Tests\Feature;

use App\Http\Middleware\TrackDailyLogin;
use App\Models\Exercise;
use App\Models\ExerciseRankStandard;
use App\Models\User;
use App\Models\UserExerciseRank;
use App\Models\Workout;
use App\Services\AchievementService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WorkoutSetTypesTest extends TestCase
{
    use RefreshDatabase;

    public function test_warmup_and_drop_sets_are_saved_and_warmups_do_not_inflate_strength_rank(): void
    {
        $this->withoutMiddleware(TrackDailyLogin::class);

        $user = User::factory()->create();
        $workout = Workout::query()->create([
            'user_id' => $user->id,
            'name' => 'Push Day',
        ]);
        $exercise = Exercise::query()->create([
            'name' => 'Bench Press',
            'muscle_group' => 'Chest',
        ]);
        $workout->exercises()->attach($exercise->id, ['sort_order' => 1]);

        ExerciseRankStandard::query()->create([
            'exercise_id' => $exercise->id,
            'scoring_type' => 'estimated_1rm_absolute',
            'olympian_target' => 100,
            'unit' => 'kg',
            'is_active' => true,
        ]);

        $this->mock(AchievementService::class, function ($mock) {
            $mock->shouldReceive('evaluate')->once()->andReturn([]);
        });

        $payload = [
            'workout_id' => $workout->id,
            'exercises' => [[
                'exercise_id' => $exercise->id,
                'sets' => [
                    [
                        'set_number' => 1,
                        'set_type' => 'warmup',
                        'reps' => 10,
                        'weight_kg' => 100,
                        'drop_reps' => null,
                        'drop_weight_kg' => null,
                    ],
                    [
                        'set_number' => 2,
                        'set_type' => 'drop',
                        'reps' => 5,
                        'weight_kg' => 50,
                        'drop_reps' => 8,
                        'drop_weight_kg' => 35,
                    ],
                ],
            ]],
        ];

        $this->actingAs($user)
            ->postJson(route('add-today.workout.save'), $payload)
            ->assertOk();

        $this->assertDatabaseHas('workout_log_sets', [
            'set_number' => 1,
            'set_type' => 'warmup',
            'reps' => 10,
            'weight_kg' => 100,
            'drop_reps' => null,
            'drop_weight_kg' => null,
        ]);
        $this->assertDatabaseHas('workout_log_sets', [
            'set_number' => 2,
            'set_type' => 'drop',
            'reps' => 5,
            'weight_kg' => 50,
            'drop_reps' => 8,
            'drop_weight_kg' => 35,
        ]);

        $this->actingAs($user)
            ->getJson(route('add-today.workout.today'))
            ->assertOk()
            ->assertJsonPath('log.exercises.0.sets.0.set_type', 'warmup')
            ->assertJsonPath('log.exercises.0.sets.1.set_type', 'drop')
            ->assertJsonPath('log.exercises.0.sets.1.drop_reps', 8)
            ->assertJsonPath('log.exercises.0.sets.1.drop_weight_kg', 35);

        $rank = UserExerciseRank::query()
            ->whereBelongsTo($user)
            ->where('exercise_id', $exercise->id)
            ->firstOrFail();

        $this->assertEqualsWithDelta(58.33, (float) $rank->best_estimated_1rm, 0.01);
    }
}
