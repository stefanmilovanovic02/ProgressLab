<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('user_metrics', function (Blueprint $table) {
            $table->id();
           $table->foreignId('user_id')->constrained()->onDelete('cascade');


            $table->enum('gender', ['male', 'female']);
            $table->unsignedTinyInteger('age');
            $table->unsignedSmallInteger('height_cm');
            $table->decimal('weight_kg', 5, 2);

            $table->decimal('activity_multiplier', 3, 2); // e.g. 1.70
            $table->decimal('bmr', 8, 2);
            $table->unsignedSmallInteger('tdee'); // kcal/day (rounded int)

            $table->timestamps();

             $table->unique('user_id'); // one metrics row per user (for now)
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_metrics');
    }
};
