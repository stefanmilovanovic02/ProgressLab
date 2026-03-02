<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use App\Models\Achievement;

class AchievementsSeeder extends Seeder
{
    public function run()
    {
        $items = [
            [
                'code' => 'milestone_common_first_steps',
                'image_path' => 'images/achievements/achievement.png',
            ]
        ];

        foreach ($items as $it) {
            Achievement::updateOrCreate (
                ['code' => $it['code']],
                ['image_path' => $it['image_path']]
            );
        }
    }
}
