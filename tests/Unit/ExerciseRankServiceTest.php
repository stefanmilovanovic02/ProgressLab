<?php

namespace Tests\Unit;

use App\Models\Exercise;
use App\Models\ExerciseRankStandard;
use App\Models\User;
use App\Models\UserExerciseRank;
use App\Services\ExerciseRankService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class ExerciseRankServiceTest extends TestCase
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

        Schema::create('user_metrics', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->decimal('weight_kg', 6, 2)->nullable();
            $table->timestamps();
        });

        Schema::create('exercises', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('muscle_group')->nullable();
            $table->timestamps();
        });

        Schema::create('exercise_rank_standards', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('exercise_id')->unique();
            $table->string('scoring_type');
            $table->decimal('olympian_target', 8, 2);
            $table->string('unit');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('user_exercise_ranks', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('exercise_id');
            $table->decimal('best_value', 10, 3);
            $table->decimal('best_estimated_1rm', 10, 2)->nullable();
            $table->decimal('score', 6, 2);
            $table->string('rank');
            $table->timestamp('ranked_at')->nullable();
            $table->timestamps();
            $table->unique(['user_id', 'exercise_id']);
        });
    }

    protected function tearDown(): void
    {
        Schema::dropIfExists('user_exercise_ranks');
        Schema::dropIfExists('exercise_rank_standards');
        Schema::dropIfExists('exercises');
        Schema::dropIfExists('user_metrics');
        Schema::dropIfExists('users');

        parent::tearDown();
    }

    public function test_bodyweight_standard_uses_estimated_one_rep_max_and_only_promotes_up(): void
    {
        $user = User::query()->create([
            'name' => 'Lifter',
            'email' => 'lifter@example.test',
            'password' => 'password',
        ]);
        $user->metric()->create(['weight_kg' => 80]);
        $exercise = Exercise::query()->create([
            'name' => 'Barbell Bench Press',
            'muscle_group' => 'Chest',
        ]);
        ExerciseRankStandard::query()->create([
            'exercise_id' => $exercise->id,
            'scoring_type' => 'estimated_1rm_bodyweight',
            'olympian_target' => 1.5,
            'unit' => 'ratio',
            'is_active' => true,
        ]);

        $service = app(ExerciseRankService::class);
        $firstPromotion = $service->evaluate($user, $exercise, [
            ['reps' => 8, 'weight_kg' => 60],
        ]);
        $sameResult = $service->evaluate($user, $exercise, [
            ['reps' => 8, 'weight_kg' => 60],
        ]);
        $higherPromotion = $service->evaluate($user, $exercise, [
            ['reps' => 5, 'weight_kg' => 120],
        ]);

        $this->assertSame('Platinum', $firstPromotion['rank']);
        $this->assertNull($sameResult);
        $this->assertSame('Olympian', $higherPromotion['rank']);
        $this->assertSame('Platinum', $higherPromotion['previous_rank']);
        $this->assertSame(1, UserExerciseRank::query()->count());
        $this->assertSame(100.0, UserExerciseRank::query()->first()->score);
    }

    public function test_repetition_standard_does_not_require_bodyweight(): void
    {
        $user = User::query()->create([
            'name' => 'Calisthenics User',
            'email' => 'calisthenics@example.test',
            'password' => 'password',
        ]);
        $exercise = Exercise::query()->create([
            'name' => 'Push Ups',
            'muscle_group' => 'Chest',
        ]);
        ExerciseRankStandard::query()->create([
            'exercise_id' => $exercise->id,
            'scoring_type' => 'repetitions',
            'olympian_target' => 75,
            'unit' => 'reps',
            'is_active' => true,
        ]);

        $promotion = app(ExerciseRankService::class)->evaluate($user, $exercise, [
            ['reps' => 30, 'weight_kg' => 0],
        ]);

        $this->assertSame('Gold', $promotion['rank']);
        $this->assertSame(40.0, $promotion['score']);
    }
}
