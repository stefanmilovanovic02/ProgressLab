<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
  public function up(): void {
    Schema::create('achievements', function (Blueprint $table) {
      $table->id();
      $table->string('code')->unique();                 // stable key (slug)
      $table->string('title');
      $table->text('description')->nullable();
      $table->string('category')->index();              // milestone|nutrition|workout|social...
      $table->string('rarity')->index();                // common|uncommon|rare|epic|legendary
      $table->unsignedInteger('points')->default(0);    // optional
      $table->boolean('is_active')->default(true);
      $table->json('criteria')->nullable();             // rule config (our engine)
      $table->timestamps();
    });
  }

  public function down(): void {
    Schema::dropIfExists('achievements');
  }
};
