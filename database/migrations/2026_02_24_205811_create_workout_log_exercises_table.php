<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
  public function up(): void {
    Schema::create('workout_log_exercises', function (Blueprint $table) {
      $table->id();
      $table->foreignId('workout_log_id')->constrained('workout_logs')->cascadeOnDelete();
      $table->foreignId('exercise_id')->constrained()->cascadeOnDelete();
      $table->timestamps();

      $table->unique(['workout_log_id', 'exercise_id']);
    });
  }

  public function down(): void {
    Schema::dropIfExists('workout_log_exercises');
  }
};