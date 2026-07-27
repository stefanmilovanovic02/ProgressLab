<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // An earlier migration already creates this table. Keep this historical
        // migration safe for fresh installs where both files are executed.
        if (Schema::hasTable('friends')) {
            return;
        }

        Schema::create('friends', function (Blueprint $table) {
            $table->id();

            $table->foreignId('user_id')
                ->constrained('users')
                ->cascadeOnDelete();

            $table->foreignId('friend_id')
                ->constrained('users')
                ->cascadeOnDelete();

            $table->timestamps();

            $table->unique(['user_id', 'friend_id']);
            $table->index('user_id');
            $table->index('friend_id');
        });
    }

    public function down(): void
    {
        // The table is owned by 2026_03_02_131051_create_friends_table.
    }
};
