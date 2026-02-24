<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
  public function up(): void
  {
    Schema::create('exercises', function (Blueprint $table) {
      $table->id();
      $table->string('name');
      $table->string('muscle_group')->nullable();  // Chest, Back, Legs...
      $table->string('image_path')->nullable();    // optional for later
      $table->timestamps();

      $table->index('name');
    });
  }

  public function down(): void
  {
    Schema::dropIfExists('exercises');
  }
};