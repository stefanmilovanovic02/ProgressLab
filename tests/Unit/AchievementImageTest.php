<?php

namespace Tests\Unit;

use App\Support\AchievementImage;
use Tests\TestCase;

class AchievementImageTest extends TestCase
{
    public function test_it_keeps_existing_achievement_images(): void
    {
        $this->assertSame(
            'images/achievements/default.png',
            AchievementImage::path('images\\achievements\\default.png', 'workout')
        );
    }

    public function test_it_uses_the_matching_category_image_when_an_asset_is_missing(): void
    {
        $this->assertSame(
            'images/achievements/fallback-workout.png',
            AchievementImage::path('images/achievements/not-created.png', 'workout')
        );

        $this->assertSame(
            'images/achievements/fallback-nutrition.png',
            AchievementImage::path(null, 'nutrition')
        );

        $this->assertSame(
            'images/achievements/fallback-milestone.png',
            AchievementImage::path('', 'unknown-category')
        );
    }

    public function test_it_uses_a_small_thumbnail_when_available(): void
    {
        $this->assertStringContainsString(
            '/images/achievements/thumbs/default.png',
            AchievementImage::thumbnailUrl('images/achievements/default.png', 'workout')
        );
    }
}
