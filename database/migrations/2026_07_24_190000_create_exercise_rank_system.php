<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('exercise_rank_standards', function (Blueprint $table) {
            $table->id();
            $table->foreignId('exercise_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('scoring_type', 40)->default('estimated_1rm_absolute');
            $table->decimal('olympian_target', 8, 2)->nullable();
            $table->string('unit', 20)->default('kg');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('user_exercise_ranks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('exercise_id')->constrained()->cascadeOnDelete();
            $table->decimal('best_value', 10, 3)->default(0);
            $table->decimal('best_estimated_1rm', 10, 2)->nullable();
            $table->decimal('score', 6, 2)->default(0);
            $table->string('rank', 20);
            $table->timestamp('ranked_at')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'exercise_id']);
            $table->index(['user_id', 'rank']);
        });

        $now = now();
        $standards = DB::table('exercises')
            ->select(['id', 'name', 'muscle_group'])
            ->orderBy('id')
            ->get()
            ->map(function ($exercise) use ($now) {
                [$type, $target, $unit, $active] = $this->standardFor(
                    (string) $exercise->name,
                    (string) $exercise->muscle_group
                );

                return [
                    'exercise_id' => $exercise->id,
                    'scoring_type' => $type,
                    'olympian_target' => $target,
                    'unit' => $unit,
                    'is_active' => $active,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            })
            ->all();

        if ($standards) {
            DB::table('exercise_rank_standards')->insert($standards);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('user_exercise_ranks');
        Schema::dropIfExists('exercise_rank_standards');
    }

    private function standardFor(string $name, string $muscleGroup): array
    {
        $normalized = strtolower($name);

        if ($normalized === 'rest') {
            return ['disabled', null, 'none', false];
        }

        if ($normalized === 'pull ups') {
            return ['repetitions', 30, 'reps', true];
        }

        if ($normalized === 'push ups') {
            return ['repetitions', 75, 'reps', true];
        }

        if (str_contains($normalized, 'assisted pull up')) {
            return ['assisted_bodyweight', 1.30, 'ratio', true];
        }

        $bodyweightTargets = [
            'barbell deadlift' => 2.50,
            'barbell squad' => 2.00,
            'barbell bench press' => 1.50,
            'barbell incline bench press' => 1.30,
            'barbell overhead press' => 1.00,
            'barbell hip thrust' => 2.25,
            'dumbbell romanian deadlift' => 1.00,
            'dumbbell bench press' => 0.70,
            'dumbbell incline bench press' => 0.62,
            'dumbbell overhead press' => 0.48,
            'dumbbell seated overhead press' => 0.45,
            'dumbbell arnold press' => 0.42,
            'dumbbell seated arnold press' => 0.40,
            'dumbbell goblet squat' => 0.80,
            'dumbbell bulgarian split squat' => 0.65,
            'dumbbell hip thrust' => 1.15,
            'dumbbell glute bridge' => 1.10,
            'step up' => 0.65,
            'barbell shrug' => 2.00,
            'dumbbell shrug' => 1.00,
            'dumbbell seated shrug' => 0.85,
            'dumbbell silverback shrug' => 0.90,
            'smith machine standing shrugs' => 2.10,
            'lat pulldown' => 1.45,
            'close grip lat pulldown' => 1.40,
            'iso lateral lat pulldown' => 0.85,
            'machine lat pulldown' => 1.55,
            'seated cable row' => 1.45,
            'machine chest supported t bar row' => 1.60,
            'leg press' => 4.00,
            'machine horizontal leg press' => 3.50,
            'hack squat' => 2.50,
            'pendulum squat' => 2.25,
        ];

        if (isset($bodyweightTargets[$normalized])) {
            return ['estimated_1rm_bodyweight', $bodyweightTargets[$normalized], 'ratio', true];
        }

        $absoluteTarget = match (strtolower($muscleGroup)) {
            'biceps' => str_contains($normalized, 'dumbbell') ? 35 : 80,
            'triceps' => str_contains($normalized, 'dumbbell') ? 40 : 110,
            'forearms' => 60,
            'shoulders' => str_contains($normalized, 'raise') || str_contains($normalized, 'fly')
                ? 28
                : 120,
            'back' => str_contains($normalized, 'extension') ? 80 : 130,
            'chest' => str_contains($normalized, 'fly') ? 110 : 160,
            'legs' => str_contains($normalized, 'calf') ? 180 : 170,
            'hamstring' => 130,
            'gluteus' => 120,
            'traps' => 120,
            'abs' => 100,
            default => 100,
        };

        return ['estimated_1rm_absolute', $absoluteTarget, 'kg', true];
    }
};
