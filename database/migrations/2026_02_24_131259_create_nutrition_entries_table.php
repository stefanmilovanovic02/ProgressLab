<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    
    public function up(): void
    {
        Schema::create('nutrition_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();

            // one entry per day, per user
            $table->date('entry_date');

            $table->unsignedSmallInteger('calories')->default(0);
            $table->unsignedSmallInteger('protein_g')->default(0);
            $table->unsignedSmallInteger('carbs_g')->default(0);
            $table->unsignedSmallInteger('fat_g')->default(0);
            $table->unsignedSmallInteger('creatine_g')->default(0);
            $table->unsignedSmallInteger('water_ml')->default(0);

            $table->timestamps();

            $table->unique(['user_id', 'entry_date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('nutrition_entries');
    }
};
