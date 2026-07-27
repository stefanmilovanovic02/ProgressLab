<?php

namespace Tests\Feature;

use App\Models\Exercise;
use App\Models\User;
use App\Models\Workout;
use App\Services\ActivityCalendarService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ActivityCalendarTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_calendar_distinguishes_empty_single_and_complete_activity_days(): void
    {
        Carbon::setTestNow('2026-07-27 12:00:00');
        $user = User::factory()->create();
        $workout = Workout::query()->create(['user_id' => $user->id, 'name' => 'Push']);
        $exercise = Exercise::query()->create(['name' => 'Bench Press', 'muscle_group' => 'Chest']);

        DB::table('nutrition_entries')->insert([
            [
                'user_id' => $user->id,
                'entry_date' => '2026-07-25',
                'calories' => 2100,
                'protein_g' => 170,
                'carbs_g' => 200,
                'fat_g' => 70,
                'creatine_g' => 5,
                'water_ml' => 2500,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'user_id' => $user->id,
                'entry_date' => '2026-07-26',
                'calories' => 2100,
                'protein_g' => 170,
                'carbs_g' => 200,
                'fat_g' => 70,
                'creatine_g' => 5,
                'water_ml' => 2500,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'user_id' => $user->id,
                'entry_date' => '2026-07-27',
                'calories' => 0,
                'protein_g' => 0,
                'carbs_g' => 0,
                'fat_g' => 0,
                'creatine_g' => 0,
                'water_ml' => 0,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        $logId = DB::table('workout_logs')->insertGetId([
            'user_id' => $user->id,
            'workout_id' => $workout->id,
            'entry_date' => '2026-07-26',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $loggedExerciseId = DB::table('workout_log_exercises')->insertGetId([
            'workout_log_id' => $logId,
            'exercise_id' => $exercise->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('workout_log_sets')->insert([
            'workout_log_exercise_id' => $loggedExerciseId,
            'set_number' => 1,
            'set_type' => 'normal',
            'reps' => 8,
            'weight_kg' => 80,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $calendar = app(ActivityCalendarService::class)->build($user);
        $days = $calendar['days']->keyBy('date');

        $this->assertSame(1, $days['2026-07-25']['level']);
        $this->assertSame(2, $days['2026-07-26']['level']);
        $this->assertSame(0, $days['2026-07-27']['level']);
        $this->assertSame(2, $calendar['active_days']);
        $this->assertSame(1, $calendar['complete_days']);
        $this->assertSame('2026-01-01', $calendar['start']);
        $this->assertSame('2026-12-31', $calendar['end']);
        $this->assertSame('Jan', $calendar['months']->first());
        $this->assertContains('Dec', $calendar['months']->all());

        $this->actingAs($user)
            ->get(route('charts.index'))
            ->assertOk()
            ->assertSee('Activity Calendar')
            ->assertSee('Both completed');
    }
}
