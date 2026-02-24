<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
  public function up(): void {
    Schema::create('workout_logs', function (Blueprint $table) {
      $table->id();
      $table->foreignId('user_id')->constrained()->cascadeOnDelete();
      $table->foreignId('workout_id')->constrained()->cascadeOnDelete();
      $table->date('entry_date');
      $table->timestamps();

      $table->unique(['user_id', 'entry_date']); // one workout logged per day
    });
  }

  public function down(): void {
    Schema::dropIfExists('workout_logs');
  }
};
