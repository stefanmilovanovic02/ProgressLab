<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('nutrition_goals', function (Blueprint $table) {
            $table->decimal('water_l', 4, 1)->nullable()->after('carbs_g');     // e.g. 3.0
            $table->decimal('creatine_g', 4, 1)->nullable()->after('water_l');  // e.g. 5.0
        });
    }

    public function down(): void
    {
        Schema::table('nutrition_goals', function (Blueprint $table) {
            $table->dropColumn(['water_l', 'creatine_g']);
        });
    }
};
