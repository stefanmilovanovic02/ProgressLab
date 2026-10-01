<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('profile_showcase_path')->nullable()->after('cover_path');
            $table->string('profile_quote', 180)->nullable()->after('profile_showcase_path');
            $table->string('social_instagram')->nullable()->after('profile_quote');
            $table->string('social_tiktok')->nullable()->after('social_instagram');
            $table->string('social_snapchat')->nullable()->after('social_tiktok');
            $table->string('social_linkedin')->nullable()->after('social_snapchat');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'profile_showcase_path',
                'profile_quote',
                'social_instagram',
                'social_tiktok',
                'social_snapchat',
                'social_linkedin',
            ]);
        });
    }
};
