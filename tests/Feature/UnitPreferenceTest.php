<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class UnitPreferenceTest extends TestCase
{
    use RefreshDatabase;

    public function test_registration_converts_imperial_metrics_to_canonical_values(): void
    {
        $response = $this->withSession([
            'register.step1' => [
                'full_name' => 'Imperial Member',
                'username' => 'imperial_member',
                'email' => 'imperial@example.test',
                'password_hash' => Hash::make('Password123!'),
            ],
        ])->post(route('register.store.macros'), [
            'unit_system' => 'imperial',
            'gender' => 'male',
            'age' => 30,
            'height' => 70.87,
            'weight' => 176.37,
            'activity' => '1.5',
        ]);

        $response->assertRedirect(route('register.goal'));
        $response->assertSessionHas('register.step2.unit_system', 'imperial');
        $this->assertEqualsWithDelta(180, session('register.step2.height_cm'), 0.02);
        $this->assertEqualsWithDelta(80, session('register.step2.weight_kg'), 0.02);
    }

    public function test_registration_defaults_to_metric_when_no_preference_is_sent(): void
    {
        $response = $this->withSession([
            'register.step1' => [
                'full_name' => 'Metric Member',
                'username' => 'metric_member',
                'email' => 'metric@example.test',
                'password_hash' => Hash::make('Password123!'),
            ],
        ])->post(route('register.store.macros'), [
            'gender' => 'female',
            'age' => 28,
            'height' => 168,
            'weight' => 62,
            'activity' => '1.5',
        ]);

        $response->assertRedirect(route('register.goal'));
        $response->assertSessionHas('register.step2.unit_system', 'metric');
        $this->assertSame(168.0, session('register.step2.height_cm'));
        $this->assertSame(62.0, session('register.step2.weight_kg'));
    }
}
