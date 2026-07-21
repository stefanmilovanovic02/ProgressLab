<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
  public function up(): void {
    if (Schema::hasTable('user_achievements')) {
      return;
    }

    Schema::create('user_achievements', function (Blueprint $table) {
      $table->id();
      $table->foreignId('user_id')->constrained()->cascadeOnDelete();
      $table->foreignId('achievement_id')->constrained('achievements')->cascadeOnDelete();
      $table->timestamp('unlocked_at')->nullable();
      $table->timestamp('notified_at')->nullable(); // for “Steam popup” once
      $table->json('progress')->nullable();         // optional
      $table->timestamps();

      $table->unique(['user_id', 'achievement_id']);
      $table->index(['user_id', 'unlocked_at']);
    });
  }

  public function down(): void {
    Schema::dropIfExists('user_achievements');
  }
};
