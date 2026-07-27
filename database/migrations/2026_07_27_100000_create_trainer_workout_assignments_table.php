<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('trainer_workout_assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('trainer_client_id')->constrained()->cascadeOnDelete();
            $table->foreignId('source_workout_id')->nullable()->constrained('workouts')->nullOnDelete();
            $table->foreignId('client_workout_id')->constrained('workouts')->cascadeOnDelete();
            $table->text('instructions')->nullable();
            $table->timestamp('assigned_at');
            $table->timestamps();

            $table->index(['trainer_client_id', 'assigned_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('trainer_workout_assignments');
    }
};
