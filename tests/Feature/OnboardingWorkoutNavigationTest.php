<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Http\Middleware\TrackDailyLogin;
use App\Models\User;
use App\Services\AchievementService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class OnboardingWorkoutNavigationTest extends TestCase
{
    use RefreshDatabase;

    public function test_completed_registration_authenticates_user_and_redirects_home(): void
    {
        $this->mock(AchievementService::class, function ($mock) {
            $mock->shouldReceive('evaluate')->once()->andReturn([]);
        });

        $response = $this->withSession([
            'register' => [
                'step1' => [
                    'full_name' => 'New Member',
                    'username' => 'new_member',
                    'email' => 'new-member@example.test',
                    'password_hash' => Hash::make('Password123!'),
                ],
                'step2' => [
                    'gender' => 'male',
                    'age' => 25,
                    'height_cm' => 180,
                    'weight_kg' => 80,
                    'activity_multiplier' => 1.5,
                ],
                'bmr' => 1800,
                'tdee' => 2700,
            ],
        ])->post(route('register.store.goal'), [
            'goal' => 'recomp',
            'fat_percent' => 30,
            'protein_g_per_kg' => 1.8,
        ]);

        $user = User::query()->where('email', 'new-member@example.test')->firstOrFail();

        $response
            ->assertRedirect(route('home'))
            ->assertSessionHas('status', 'Account created. Welcome to ProgressLab!')
            ->assertSessionMissing('register');
        $this->assertAuthenticatedAs($user);
        $this->assertSame(UserRole::User, $user->role);
    }

    public function test_workout_links_target_selection_and_open_creation_modal(): void
    {
        $this->withoutMiddleware(TrackDailyLogin::class);
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('home'))
            ->assertOk()
            ->assertSee(route('add-today') . '#workout-selection', false);

        $createUrl = route('workouts.index', ['create' => 1]);
        $this->actingAs($user)
            ->get(route('add-today'))
            ->assertOk()
            ->assertSee('id="workout-selection"', false)
            ->assertSee('You don’t have a workout yet')
            ->assertSee('Create Your First Workout')
            ->assertSee($createUrl, false);

        $this->actingAs($user)
            ->get($createUrl)
            ->assertOk()
            ->assertSee('data-auto-open="true"', false)
            ->assertSee("if (modal.dataset.autoOpen === 'true')", false);
    }
}
