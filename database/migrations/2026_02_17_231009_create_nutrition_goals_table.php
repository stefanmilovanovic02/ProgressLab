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
        Schema::create('nutrition_goals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();

            $table->enum('goal', ['bulk', 'cut', 'recomp']);

            $table->unsignedSmallInteger('calorie_target');
            $table->unsignedSmallInteger('protein_g');
            $table->unsignedSmallInteger('fat_g');
            $table->unsignedSmallInteger('carbs_g');

            $table->decimal('fat_percent', 5, 2)->default(30.00);
            $table->decimal('protein_g_per_kg', 4, 2)->default(1.80);

            $table->enum('bulk_type', ['lean', 'standard'])->nullable();
            $table->enum('cut_type', ['moderate', 'aggressive'])->nullable();

            $table->timestamps();

            $table->unique('user_id'); // one current goal per user (for now)
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('nutrition_goals');
    }
};
