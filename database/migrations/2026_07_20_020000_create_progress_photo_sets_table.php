<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('progress_photo_sets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('front_path');
            $table->string('side_path');
            $table->string('back_path');
            $table->date('captured_on');
            $table->timestamps();

            $table->index(['user_id', 'captured_on']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('progress_photo_sets');
    }
};
