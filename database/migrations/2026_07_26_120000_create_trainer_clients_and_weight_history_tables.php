<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('trainer_clients', function (Blueprint $table) {
            $table->id();
            $table->foreignId('trainer_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('client_id')->constrained('users')->cascadeOnDelete();
            $table->string('status', 20)->default('pending');
            $table->boolean('can_view_nutrition')->default(true);
            $table->boolean('can_view_exercises')->default(true);
            $table->boolean('can_view_weight')->default(true);
            $table->boolean('can_view_streaks')->default(true);
            $table->text('trainer_notes')->nullable();
            $table->timestamp('accepted_at')->nullable();
            $table->timestamp('revoked_at')->nullable();
            $table->timestamps();

            $table->unique(['trainer_id', 'client_id']);
            $table->index(['client_id', 'status']);
            $table->index(['trainer_id', 'status']);
        });

        Schema::create('weight_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->date('recorded_on');
            $table->decimal('weight_kg', 5, 2);
            $table->string('source', 30)->default('profile');
            $table->timestamps();

            $table->unique(['user_id', 'recorded_on']);
            $table->index(['user_id', 'recorded_on']);
        });

        if (Schema::hasTable('user_metrics')) {
            $now = now();
            DB::table('user_metrics')
                ->whereNotNull('weight_kg')
                ->orderBy('user_id')
                ->chunkById(250, function ($metrics) use ($now) {
                    DB::table('weight_entries')->insertOrIgnore(
                        $metrics->map(fn ($metric) => [
                            'user_id' => $metric->user_id,
                            'recorded_on' => $now->toDateString(),
                            'weight_kg' => $metric->weight_kg,
                            'source' => 'backfill',
                            'created_at' => $now,
                            'updated_at' => $now,
                        ])->all()
                    );
                });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('weight_entries');
        Schema::dropIfExists('trainer_clients');
    }
};
