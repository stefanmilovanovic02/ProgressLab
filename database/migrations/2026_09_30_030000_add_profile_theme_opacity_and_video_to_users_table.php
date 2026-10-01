<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('profile_background_video_path')->nullable()->after('profile_showcase_path');
            $table->unsignedTinyInteger('profile_accent_opacity')->default(100)->after('profile_accent_color');
            $table->unsignedTinyInteger('profile_secondary_opacity')->default(100)->after('profile_secondary_color');
            $table->unsignedTinyInteger('profile_surface_opacity')->default(92)->after('profile_surface_color');
            $table->unsignedTinyInteger('profile_text_opacity')->default(100)->after('profile_text_color');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'profile_background_video_path',
                'profile_accent_opacity',
                'profile_secondary_opacity',
                'profile_surface_opacity',
                'profile_text_opacity',
            ]);
        });
    }
};
