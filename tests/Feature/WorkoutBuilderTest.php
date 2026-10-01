<?php

namespace Tests\Feature;

use App\Http\Middleware\TrackDailyLogin;
use App\Models\Exercise;
use App\Models\User;
use App\Models\Workout;
use App\Services\AchievementService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WorkoutBuilderTest extends TestCase
{
    use RefreshDatabase;

    public function test_exercise_picker_browses_and_searches_a_large_multi_select_list(): void
    {
        $this->withoutMiddleware(TrackDailyLogin::class);
        $user = User::factory()->create();
        Exercise::query()->create(['name' => 'Lat Pulldown', 'muscle_group' => 'Back']);
        Exercise::query()->create(['name' => 'Seated Cable Row', 'muscle_group' => 'Back']);
        Exercise::query()->create(['name' => 'Bench Press', 'muscle_group' => 'Chest']);

        $this->actingAs($user)
            ->getJson(route('exercises.search'))
            ->assertOk()
            ->assertJsonCount(3)
            ->assertJsonPath('0.name', 'Bench Press');

        $this->actingAs($user)
            ->getJson(route('exercises.search', ['q' => 'Back']))
            ->assertOk()
            ->assertJsonCount(2);

        $this->actingAs($user)
            ->get(route('workouts.index', ['create' => 1]))
            ->assertOk()
            ->assertSee('data-picker-results', false)
            ->assertSee('data-selected-list', false)
            ->assertSee('aria-multiselectable="true"', false)
            ->assertSee('data-picker-results role="listbox"', false)
            ->assertSee('aria-multiselectable="true" hidden', false)
            ->assertSee('Start typing to find exercises.')
            ->assertSee('Search, then select as many exercises as you need.');
    }

    public function test_workout_can_be_created_with_multiple_exercises_in_selected_order(): void
    {
        $this->withoutMiddleware(TrackDailyLogin::class);
        $user = User::factory()->create();
        $first = Exercise::query()->create(['name' => 'Lat Pulldown', 'muscle_group' => 'Back']);
        $second = Exercise::query()->create(['name' => 'Cable Curl', 'muscle_group' => 'Biceps']);
        $third = Exercise::query()->create(['name' => 'Leg Press', 'muscle_group' => 'Legs']);

        $this->mock(AchievementService::class, function ($mock) {
            $mock->shouldReceive('evaluate')->once()->andReturn([]);
        });

        $this->actingAs($user)
            ->post(route('workouts.store'), [
                'name' => 'Full Body Selection',
                'exercise_ids' => [$second->id, $first->id, $third->id],
            ])
            ->assertRedirect(route('workouts.index'))
            ->assertSessionHas('status', 'Workout created.');

        $workout = Workout::query()->where('user_id', $user->id)->firstOrFail();
        $this->assertSame(
            [$second->id, $first->id, $third->id],
            $workout->exercises()->pluck('exercises.id')->map(fn ($id) => (int) $id)->all()
        );
    }
}
