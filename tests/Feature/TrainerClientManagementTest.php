<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Http\Middleware\TrackDailyLogin;
use App\Models\AppNotification;
use App\Models\Exercise;
use App\Models\TrainerClient;
use App\Models\User;
use App\Models\Workout;
use App\Services\NotificationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TrainerClientManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_trainer_can_assign_workout_set_targets_and_download_shared_report(): void
    {
        $this->withoutMiddleware(TrackDailyLogin::class);
        $trainer = User::factory()->create(['role' => UserRole::Trainer]);
        $client = User::factory()->create(['role' => UserRole::User]);
        $relationship = TrainerClient::query()->create([
            'trainer_id' => $trainer->id,
            'client_id' => $client->id,
            'status' => TrainerClient::STATUS_ACCEPTED,
            'can_view_nutrition' => true,
            'can_view_exercises' => true,
            'can_view_weight' => true,
            'can_view_streaks' => true,
            'accepted_at' => now(),
        ]);
        $template = Workout::query()->create([
            'user_id' => $trainer->id,
            'name' => 'Trainer Push Day',
        ]);
        $exercise = Exercise::query()->create([
            'name' => 'Bench Press',
            'muscle_group' => 'Chest',
        ]);
        $template->exercises()->attach($exercise->id, ['sort_order' => 0]);

        $this->mock(NotificationService::class, function ($mock) {
            $mock->shouldReceive('sendSystem')->twice()->andReturn(new AppNotification());
        });

        $this->actingAs($trainer)
            ->post(route('trainer.clients.workouts.store', $client), [
                'workout_id' => $template->id,
                'name' => 'Client Push A',
                'instructions' => 'Keep two reps in reserve.',
            ])
            ->assertRedirect()
            ->assertSessionHas('status');

        $clientWorkout = Workout::query()
            ->where('user_id', $client->id)
            ->where('name', 'Client Push A')
            ->firstOrFail();
        $this->assertTrue($clientWorkout->exercises()->whereKey($exercise->id)->exists());
        $this->assertDatabaseHas('trainer_workout_assignments', [
            'trainer_client_id' => $relationship->id,
            'source_workout_id' => $template->id,
            'client_workout_id' => $clientWorkout->id,
            'instructions' => 'Keep two reps in reserve.',
        ]);

        $this->actingAs($trainer)
            ->patch(route('trainer.clients.nutrition-targets.update', $client), [
                'goal' => 'recomp',
                'calorie_target' => 2400,
                'protein_g' => 180,
                'carbs_g' => 250,
                'fat_g' => 75,
                'water_l' => 3.2,
                'creatine_g' => 5,
            ])
            ->assertRedirect()
            ->assertSessionHas('status');

        $this->assertDatabaseHas('nutrition_goals', [
            'user_id' => $client->id,
            'calorie_target' => 2400,
            'protein_g' => 180,
        ]);

        $this->actingAs($trainer)
            ->get(route('trainer.clients.weekly-report', $client))
            ->assertOk()
            ->assertHeader('Content-Type', 'application/pdf');
    }

    public function test_unaccepted_or_unpermitted_clients_cannot_be_managed(): void
    {
        $this->withoutMiddleware(TrackDailyLogin::class);
        $trainer = User::factory()->create(['role' => UserRole::Trainer]);
        $client = User::factory()->create(['role' => UserRole::User]);
        TrainerClient::query()->create([
            'trainer_id' => $trainer->id,
            'client_id' => $client->id,
            'status' => TrainerClient::STATUS_ACCEPTED,
            'can_view_nutrition' => false,
            'can_view_exercises' => false,
            'can_view_weight' => false,
            'can_view_streaks' => false,
            'accepted_at' => now(),
        ]);

        $this->actingAs($trainer)
            ->patch(route('trainer.clients.nutrition-targets.update', $client), [
                'goal' => 'recomp',
                'calorie_target' => 2400,
                'protein_g' => 180,
                'carbs_g' => 250,
                'fat_g' => 75,
            ])
            ->assertForbidden();

        $this->actingAs($trainer)
            ->get(route('trainer.clients.weekly-report', $client))
            ->assertForbidden();
    }
}
