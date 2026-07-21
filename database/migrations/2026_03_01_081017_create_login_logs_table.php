<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasTable('login_logs')) {
            return;
        }

        Schema::create('login_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->date('login_date');
            $table->timestamps();

            $table->unique(['user_id', 'login_date']);
            $table->index(['user_id', 'login_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('login_logs');
    }
};
