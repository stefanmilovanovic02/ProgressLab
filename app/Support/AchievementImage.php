<?php

namespace App\Support;

final class AchievementImage
{
    public static function path(?string $configuredPath, ?string $category): string
    {
        $configuredPath = $configuredPath
            ? str_replace('\\', '/', ltrim($configuredPath, '/\\'))
            : null;

        if ($configuredPath && is_file(public_path($configuredPath))) {
            return $configuredPath;
        }

        $category = strtolower((string) $category);
        $category = in_array($category, ['workout', 'nutrition', 'milestone'], true)
            ? $category
            : 'milestone';

        $fallback = "images/achievements/fallback-{$category}.png";

        return is_file(public_path($fallback))
            ? $fallback
            : 'images/achievements/default.png';
    }

    public static function url(?string $configuredPath, ?string $category): string
    {
        return asset(self::path($configuredPath, $category));
    }

    public static function thumbnailUrl(?string $configuredPath, ?string $category): string
    {
        $path = self::path($configuredPath, $category);
        $thumbnail = 'images/achievements/thumbs/' . pathinfo($path, PATHINFO_FILENAME) . '.png';

        return asset(is_file(public_path($thumbnail)) ? $thumbnail : $path);
    }
}
