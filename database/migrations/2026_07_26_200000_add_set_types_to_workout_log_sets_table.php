<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('workout_log_sets', function (Blueprint $table) {
            $table->string('set_type', 20)->default('normal')->after('set_number');
            $table->unsignedSmallInteger('drop_reps')->nullable()->after('weight_kg');
            $table->decimal('drop_weight_kg', 6, 2)->nullable()->after('drop_reps');
        });
    }

    public function down(): void
    {
        Schema::table('workout_log_sets', function (Blueprint $table) {
            $table->dropColumn(['set_type', 'drop_reps', 'drop_weight_kg']);
        });
    }
};
