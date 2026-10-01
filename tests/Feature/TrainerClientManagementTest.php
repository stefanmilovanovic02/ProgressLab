<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Http\Middleware\TrackDailyLogin;
use App\Models\AppNotification;
use App\Models\Exercise;
use App\Models\TrainerClient;
use App\Models\TrainerWorkoutAssignment;
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

        $this->actingAs($trainer)
            ->patch(route('trainer.clients.nutrition-entry.update', $client), ['calories' => 2200])
            ->assertForbidden();
    }

    public function test_trainer_can_view_and_update_clients_today_nutrition_when_shared(): void
    {
        $this->withoutMiddleware(TrackDailyLogin::class);
        $trainer = User::factory()->create(['role' => UserRole::Trainer]);
        $client = User::factory()->create(['role' => UserRole::User]);
        TrainerClient::query()->create([
            'trainer_id' => $trainer->id,
            'client_id' => $client->id,
            'status' => TrainerClient::STATUS_ACCEPTED,
            'can_view_nutrition' => true,
            'accepted_at' => now(),
        ]);
        $client->nutritionGoal()->create([
            'goal' => 'recomp',
            'calorie_target' => 2400,
            'protein_g' => 180,
            'carbs_g' => 260,
            'fat_g' => 75,
            'water_l' => 3,
            'creatine_g' => 5,
        ]);

        $this->mock(NotificationService::class, function ($mock) {
            $mock->shouldReceive('syncForUser')->zeroOrMoreTimes();
            $mock->shouldReceive('unreadCount')->zeroOrMoreTimes()->andReturn(0);
            $mock->shouldReceive('notifyFriendActivity')->once();
            $mock->shouldReceive('sendSystem')->once()->andReturn(new AppNotification());
        });

        $this->actingAs($trainer)
            ->get(route('trainer.clients.show', $client))
            ->assertOk()
            ->assertSee('Client nutrition')
            ->assertSee('of 2,400 kcal')
            ->assertSee('Edit today’s entry');

        $this->actingAs($trainer)
            ->patch(route('trainer.clients.nutrition-entry.update', $client), [
                'calories' => 2250,
                'protein_g' => 175,
                'carbs_g' => 240,
                'fat_g' => 70,
                'water_ml' => 2800,
                'creatine_g' => 5,
            ])
            ->assertRedirect()
            ->assertSessionHas('status');

        $this->assertDatabaseHas('nutrition_entries', [
            'user_id' => $client->id,
            'entry_date' => now()->toDateString(),
            'calories' => 2250,
            'protein_g' => 175,
            'water_ml' => 2800,
        ]);
        $this->assertDatabaseHas('experience_events', [
            'user_id' => $client->id,
            'source_type' => 'nutrition_log',
            'source_key' => now()->toDateString(),
        ]);
    }

    public function test_trainer_can_open_and_edit_an_assigned_client_workout_log(): void
    {
        $this->withoutMiddleware(TrackDailyLogin::class);
        $trainer = User::factory()->create(['role' => UserRole::Trainer]);
        $client = User::factory()->create(['role' => UserRole::User]);
        $relationship = TrainerClient::query()->create([
            'trainer_id' => $trainer->id,
            'client_id' => $client->id,
            'status' => TrainerClient::STATUS_ACCEPTED,
            'can_view_exercises' => true,
            'accepted_at' => now(),
        ]);
        $sourceWorkout = Workout::query()->create(['user_id' => $trainer->id, 'name' => 'Push Template']);
        $clientWorkout = Workout::query()->create(['user_id' => $client->id, 'name' => 'Client Push']);
        $exercise = Exercise::query()->create(['name' => 'Bench Press', 'muscle_group' => 'Chest']);
        $sourceWorkout->exercises()->attach($exercise->id, ['sort_order' => 0]);
        $clientWorkout->exercises()->attach($exercise->id, ['sort_order' => 0]);
        $assignment = TrainerWorkoutAssignment::query()->create([
            'trainer_client_id' => $relationship->id,
            'source_workout_id' => $sourceWorkout->id,
            'client_workout_id' => $clientWorkout->id,
            'assigned_at' => now(),
        ]);

        $this->mock(NotificationService::class, function ($mock) {
            $mock->shouldReceive('syncForUser')->zeroOrMoreTimes();
            $mock->shouldReceive('unreadCount')->zeroOrMoreTimes()->andReturn(0);
            $mock->shouldReceive('notifyFriendActivity')->once();
            $mock->shouldReceive('sendSystem')->once()->andReturn(new AppNotification());
        });

        $this->actingAs($trainer)
            ->get(route('trainer.clients.workout-logs.edit', [$client, $assignment]))
            ->assertOk()
            ->assertSee('Client Push')
            ->assertSee('Bench Press')
            ->assertSee('Save client workout')
            ->assertSee('data-add-set', false);

        $this->actingAs($trainer)
            ->put(route('trainer.clients.workout-logs.update', [$client, $assignment]), [
                'entry_date' => now()->toDateString(),
                'exercises' => [[
                    'exercise_id' => $exercise->id,
                    'sets' => [[
                        'set_number' => 1,
                        'set_type' => 'drop',
                        'reps' => 10,
                        'weight_kg' => 80,
                        'drop_reps' => 8,
                        'drop_weight_kg' => 60,
                    ]],
                ]],
            ])
            ->assertRedirect()
            ->assertSessionHas('status');

        $this->assertDatabaseHas('workout_logs', [
            'user_id' => $client->id,
            'workout_id' => $clientWorkout->id,
            'entry_date' => now()->toDateString(),
        ]);
        $this->assertDatabaseHas('workout_log_sets', [
            'set_number' => 1,
            'set_type' => 'drop',
            'reps' => 10,
            'weight_kg' => 80,
            'drop_reps' => 8,
            'drop_weight_kg' => 60,
        ]);
    }

    public function test_trainer_workout_log_requires_exercise_permission_and_matching_assignment(): void
    {
        $this->withoutMiddleware(TrackDailyLogin::class);
        $trainer = User::factory()->create(['role' => UserRole::Trainer]);
        $client = User::factory()->create(['role' => UserRole::User]);
        $otherClient = User::factory()->create(['role' => UserRole::User]);
        $relationship = TrainerClient::query()->create([
            'trainer_id' => $trainer->id,
            'client_id' => $client->id,
            'status' => TrainerClient::STATUS_ACCEPTED,
            'can_view_exercises' => false,
            'accepted_at' => now(),
        ]);
        $clientWorkout = Workout::query()->create(['user_id' => $client->id, 'name' => 'Private Workout']);
        $assignment = TrainerWorkoutAssignment::query()->create([
            'trainer_client_id' => $relationship->id,
            'client_workout_id' => $clientWorkout->id,
            'assigned_at' => now(),
        ]);

        $this->actingAs($trainer)
            ->get(route('trainer.clients.workout-logs.edit', [$client, $assignment]))
            ->assertForbidden();

        $relationship->update(['can_view_exercises' => true]);
        $this->actingAs($trainer)
            ->get(route('trainer.clients.workout-logs.edit', [$otherClient, $assignment]))
            ->assertForbidden();
    }
}
