<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (
            !Schema::hasTable('experience_events')
            || !Schema::hasTable('user_achievements')
            || !Schema::hasTable('achievements')
        ) {
            return;
        }

        DB::transaction(function () {
            DB::table('user_achievements as ua')
                ->join('achievements as a', 'a.id', '=', 'ua.achievement_id')
                ->whereNotNull('ua.unlocked_at')
                ->select([
                    'ua.user_id',
                    'ua.achievement_id',
                    'ua.unlocked_at',
                    'a.title',
                    'a.points',
                    'a.rarity',
                ])
                ->orderBy('ua.user_id')
                ->each(function ($achievement) {
                    $points = (int) $achievement->points;
                    if ($points <= 0) {
                        $points = match (strtolower((string) $achievement->rarity)) {
                            'legendary' => 300,
                            'epic' => 200,
                            'rare' => 125,
                            'uncommon' => 75,
                            default => 50,
                        };
                    }

                    DB::table('experience_events')->insertOrIgnore([
                        'user_id' => $achievement->user_id,
                        'source_type' => 'achievement',
                        'source_key' => (string) $achievement->achievement_id,
                        'points' => $points,
                        'description' => 'Unlocked ' . $achievement->title,
                        'metadata' => json_encode(['achievement_id' => $achievement->achievement_id]),
                        'created_at' => $achievement->unlocked_at,
                        'updated_at' => $achievement->unlocked_at,
                    ]);
                });
        });
    }

    public function down(): void
    {
        if (Schema::hasTable('experience_events')) {
            DB::table('experience_events')
                ->where('source_type', 'achievement')
                ->delete();
        }
    }
};
