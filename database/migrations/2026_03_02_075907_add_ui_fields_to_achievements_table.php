<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
  public function up(): void
  {
    Schema::table('achievements', function (Blueprint $table) {
      $table->string('image_path')->nullable()->after('description'); // e.g. achievements/account_created.png
      $table->string('category_icon', 40)->nullable()->after('category'); // e.g. 🎯 🍽️ 💪
      $table->unsignedSmallInteger('sort_order')->default(0)->after('rarity');
    });
  }

  public function down(): void
  {
    Schema::table('achievements', function (Blueprint $table) {
      $table->dropColumn(['image_path','category_icon','sort_order']);
    });
  }
};
