<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
  public function up(): void {
    Schema::create('workout_log_sets', function (Blueprint $table) {
      $table->id();
      $table->foreignId('workout_log_exercise_id')->constrained('workout_log_exercises')->cascadeOnDelete();

      $table->unsignedTinyInteger('set_number'); // 1..n
      $table->unsignedSmallInteger('reps')->nullable();
      $table->decimal('weight_kg', 6, 2)->nullable();

      $table->timestamps();

      $table->unique(['workout_log_exercise_id', 'set_number']);
    });
  }

  public function down(): void {
    Schema::dropIfExists('workout_log_sets');
  }
};