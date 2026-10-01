<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('profile_accent_color', 7)->nullable()->after('social_linkedin');
            $table->string('profile_secondary_color', 7)->nullable()->after('profile_accent_color');
            $table->string('profile_surface_color', 7)->nullable()->after('profile_secondary_color');
            $table->string('profile_text_color', 7)->nullable()->after('profile_surface_color');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'profile_accent_color',
                'profile_secondary_color',
                'profile_surface_color',
                'profile_text_color',
            ]);
        });
    }
};
