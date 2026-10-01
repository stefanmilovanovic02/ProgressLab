<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Workout;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class StreaksTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_workout_streak_remains_visible_until_a_full_day_is_missed(): void
    {
        Carbon::setTestNow('2026-07-27 12:00:00');

        $user = User::factory()->create();
        $workout = Workout::query()->create([
            'user_id' => $user->id,
            'name' => 'Push',
        ]);

        foreach (['2026-07-24', '2026-07-25', '2026-07-26'] as $date) {
            DB::table('workout_logs')->insert([
                'user_id' => $user->id,
                'workout_id' => $workout->id,
                'entry_date' => $date,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $this->actingAs($user)
            ->get(route('streaks.index'))
            ->assertOk()
            ->assertViewHas('streaks', fn (array $streaks) =>
                collect($streaks)->firstWhere('key', 'workout')['days'] === 3
            );

        Carbon::setTestNow('2026-07-28 12:00:00');

        $this->actingAs($user)
            ->get(route('streaks.index'))
            ->assertOk()
            ->assertViewHas('streaks', fn (array $streaks) =>
                collect($streaks)->firstWhere('key', 'workout')['days'] === 0
            );
    }
}
