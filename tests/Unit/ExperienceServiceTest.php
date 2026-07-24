<?php

namespace Tests\Unit;

use App\Models\ExperienceEvent;
use App\Models\NutritionEntry;
use App\Models\NutritionGoal;
use App\Models\User;
use App\Services\ExperienceService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class ExperienceServiceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->string('password');
            $table->rememberToken();
            $table->timestamps();
        });

        Schema::create('nutrition_goals', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->unsignedInteger('calorie_target')->nullable();
            $table->unsignedInteger('protein_g')->nullable();
            $table->unsignedInteger('carbs_g')->nullable();
            $table->unsignedInteger('fat_g')->nullable();
            $table->decimal('water_l', 5, 2)->nullable();
            $table->unsignedInteger('creatine_g')->nullable();
            $table->timestamps();
        });

        Schema::create('nutrition_entries', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->date('entry_date');
            $table->unsignedInteger('calories')->default(0);
            $table->unsignedInteger('protein_g')->default(0);
            $table->unsignedInteger('carbs_g')->default(0);
            $table->unsignedInteger('fat_g')->default(0);
            $table->unsignedInteger('creatine_g')->default(0);
            $table->unsignedInteger('water_ml')->default(0);
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
        Schema::dropIfExists('nutrition_entries');
        Schema::dropIfExists('nutrition_goals');
        Schema::dropIfExists('users');

        parent::tearDown();
    }

    public function test_nutrition_and_goal_xp_can_only_be_earned_once_per_day(): void
    {
        $user = $this->user();
        NutritionGoal::query()->create([
            'user_id' => $user->id,
            'calorie_target' => 2000,
            'protein_g' => 150,
            'carbs_g' => 220,
            'fat_g' => 70,
            'creatine_g' => 5,
            'water_l' => 2.5,
        ]);
        $entry = NutritionEntry::query()->create([
            'user_id' => $user->id,
            'entry_date' => '2026-07-24',
            'calories' => 2100,
            'protein_g' => 160,
            'carbs_g' => 230,
            'fat_g' => 75,
            'creatine_g' => 5,
            'water_ml' => 2600,
        ]);

        $service = app(ExperienceService::class);
        $service->awardNutrition($user, $entry);
        $service->awardNutrition($user, $entry);

        $this->assertSame(7, ExperienceEvent::query()->count());
        $this->assertSame(110, (int) ExperienceEvent::query()->sum('points'));
    }

    public function test_rank_progress_moves_to_the_next_level_at_a_boundary(): void
    {
        $user = $this->user();
        $service = app(ExperienceService::class);

        $service->award($user, 'test', 'boundary', 100);
        $progress = $service->progress($user);

        $this->assertSame('Bronze', $progress['rank']);
        $this->assertSame(2, $progress['level']);
        $this->assertSame(0, $progress['level_xp']);
        $this->assertSame(150, $progress['required_xp']);
    }

    private function user(): User
    {
        return User::query()->create([
            'name' => 'Rank Tester',
            'email' => fake()->unique()->safeEmail(),
            'password' => 'password',
        ]);
    }
}
