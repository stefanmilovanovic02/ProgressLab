<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MeasurementsTest extends TestCase
{
    use RefreshDatabase;

    public function test_add_today_displays_the_measurement_tabs_before_progress_photos(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('add-today'));

        $response
            ->assertOk()
            ->assertSeeInOrder([
                'Goals &amp; Measurements',
                'Nutrition Goals',
                'Body Measurements',
                'Progress Photos',
            ], false);
    }

    public function test_user_can_update_nutrition_goals_from_add_today(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post(route('add-today.measurements.goals'), [
                'goal' => 'recomp',
                'calorie_target' => 2450,
                'protein_g' => 175,
                'carbs_g' => 260,
                'fat_g' => 72,
                'water_l' => 3.2,
                'creatine_g' => 5,
            ])
            ->assertRedirect(route('add-today').'#measurements')
            ->assertSessionHas('measurement_tab', 'goals');

        $this->assertDatabaseHas('nutrition_goals', [
            'user_id' => $user->id,
            'goal' => 'recomp',
            'calorie_target' => 2450,
            'protein_g' => 175,
            'carbs_g' => 260,
            'fat_g' => 72,
        ]);
    }

    public function test_user_can_save_body_measurements_and_weight_history(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post(route('add-today.measurements.body'), [
                'weight_kg' => 82.5,
                'waist_cm' => 84,
                'arms_cm' => 39.5,
                'thighs_cm' => 61,
                'hips_cm' => 96,
                'glutes_cm' => 101.5,
            ])
            ->assertRedirect(route('add-today').'#measurements')
            ->assertSessionHas('measurement_tab', 'body');

        $this->assertDatabaseHas('body_measurements', [
            'user_id' => $user->id,
            'recorded_on' => now()->toDateString(),
            'weight_kg' => 82.5,
            'waist_cm' => 84,
            'arms_cm' => 39.5,
            'thighs_cm' => 61,
            'hips_cm' => 96,
            'glutes_cm' => 101.5,
        ]);

        $this->assertDatabaseHas('weight_entries', [
            'user_id' => $user->id,
            'recorded_on' => now()->toDateString(),
            'weight_kg' => 82.5,
            'source' => 'add_today',
        ]);
    }
}
