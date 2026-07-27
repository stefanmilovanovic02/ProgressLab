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
        if (
            Schema::hasColumn('nutrition_entries', 'fats_g')
            && !Schema::hasColumn('nutrition_entries', 'fat_g')
        ) {
            Schema::table('nutrition_entries', function (Blueprint $table) {
                $table->renameColumn('fats_g', 'fat_g');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (
            Schema::hasColumn('nutrition_entries', 'fat_g')
            && !Schema::hasColumn('nutrition_entries', 'fats_g')
        ) {
            Schema::table('nutrition_entries', function (Blueprint $table) {
                $table->renameColumn('fat_g', 'fats_g');
            });
        }
    }
};
