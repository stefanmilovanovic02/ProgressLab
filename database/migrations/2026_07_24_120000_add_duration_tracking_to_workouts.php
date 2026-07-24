<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('workouts', function (Blueprint $table) {
            $table->unsignedInteger('estimated_duration_seconds')->nullable();
        });

        Schema::table('workout_logs', function (Blueprint $table) {
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->unsignedInteger('duration_seconds')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('workout_logs', function (Blueprint $table) {
            $table->dropColumn(['started_at', 'completed_at', 'duration_seconds']);
        });

        Schema::table('workouts', function (Blueprint $table) {
            $table->dropColumn('estimated_duration_seconds');
        });
    }
};
