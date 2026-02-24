<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
  public function up(): void
  {
    Schema::create('exercise_workout', function (Blueprint $table) {
      $table->id();
      $table->foreignId('workout_id')->constrained()->cascadeOnDelete();
      $table->foreignId('exercise_id')->constrained()->cascadeOnDelete();
      $table->unsignedSmallInteger('sort_order')->default(0);
      $table->timestamps();

      $table->unique(['workout_id', 'exercise_id']); // avoid duplicates
      $table->index(['workout_id', 'sort_order']);
    });
  }

  public function down(): void
  {
    Schema::dropIfExists('exercise_workout');
  }
};