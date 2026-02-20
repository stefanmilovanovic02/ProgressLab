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
        Schema::table('users', function (Blueprint $table) {
            // Profile basics
            $table->date('date_of_birth')->nullable()->after('email');
            $table->string('location')->nullable()->after('date_of_birth');

            // Images (store file path or URL)
            $table->string('avatar_path')->nullable()->after('location');      // pfp
            $table->string('cover_path')->nullable()->after('avatar_path');   // background banner
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['date_of_birth', 'location', 'avatar_path', 'cover_path']);
        });
    }
};
