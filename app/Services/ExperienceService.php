<?php

namespace App\Services;

use App\Models\ExperienceEvent;
use App\Models\NutritionEntry;
use App\Models\User;

class ExperienceService
{
    public const NUTRITION_LOG_XP = 20;
    public const NUTRITION_GOAL_XP = 15;
    public const EXERCISE_COMPLETED_XP = 25;
    public const WORKOUT_COMPLETED_XP = 40;

    private const RANKS = [
        ['name' => 'Bronze',   'color' => '#b87333', 'requirements' => [100, 150, 200, 250]],
        ['name' => 'Silver',   'color' => '#bfc7d5', 'requirements' => [300, 375, 450, 525]],
        ['name' => 'Gold',     'color' => '#f6c945', 'requirements' => [600, 700, 800, 900]],
        ['name' => 'Platinum', 'color' => '#65e6d4', 'requirements' => [1050, 1200, 1350, 1500]],
        ['name' => 'Diamond',  'color' => '#62b7ff', 'requirements' => [1700, 1900, 2100, 2300]],
        ['name' => 'Master',   'color' => '#a875ff', 'requirements' => [2600, 2900, 3200, 3500]],
        ['name' => 'Titan',    'color' => '#ef5b78', 'requirements' => [3900, 4300, 4700, 5100]],
        ['name' => 'Olympian', 'color' => '#fff2a8', 'requirements' => [5600, 6200, 6800, 7500]],
    ];

    public function award(
        User $user,
        string $sourceType,
        string $sourceKey,
        int $points,
        ?string $description = null,
        array $metadata = []
    ): bool {
        if ($points <= 0) {
            return false;
        }

        return ExperienceEvent::query()->firstOrCreate(
            [
                'user_id' => $user->id,
                'source_type' => $sourceType,
                'source_key' => $sourceKey,
            ],
            [
                'points' => $points,
                'description' => $description,
                'metadata' => $metadata ?: null,
            ]
        )->wasRecentlyCreated;
    }

    public function awardNutrition(User $user, NutritionEntry $entry): void
    {
        $date = $entry->entry_date instanceof \DateTimeInterface
            ? $entry->entry_date->format('Y-m-d')
            : (string) $entry->entry_date;

        $hasNutrition = collect([
            $entry->calories,
            $entry->protein_g,
            $entry->carbs_g,
            $entry->fat_g,
            $entry->creatine_g,
            $entry->water_ml,
        ])->contains(fn ($value) => (float) $value > 0);

        if (!$hasNutrition) {
            return;
        }

        $this->award(
            $user,
            'nutrition_log',
            $date,
            self::NUTRITION_LOG_XP,
            'Logged daily nutrition'
        );

        $goal = $user->nutritionGoal;
        if (!$goal) {
            return;
        }

        $targets = [
            'calories' => [(float) $entry->calories, (float) $goal->calorie_target],
            'protein' => [(float) $entry->protein_g, (float) $goal->protein_g],
            'carbs' => [(float) $entry->carbs_g, (float) $goal->carbs_g],
            'fat' => [(float) $entry->fat_g, (float) $goal->fat_g],
            'creatine' => [(float) $entry->creatine_g, (float) $goal->creatine_g],
            'water' => [(float) $entry->water_ml, (float) $goal->water_l * 1000],
        ];

        foreach ($targets as $metric => [$value, $target]) {
            if ($target > 0 && $value >= $target) {
                $this->award(
                    $user,
                    'nutrition_goal',
                    "{$date}:{$metric}",
                    self::NUTRITION_GOAL_XP,
                    'Completed ' . $metric . ' goal',
                    ['metric' => $metric, 'date' => $date]
                );
            }
        }
    }

    public function achievementPoints(int $configuredPoints, ?string $rarity): int
    {
        if ($configuredPoints > 0) {
            return $configuredPoints;
        }

        return match (strtolower((string) $rarity)) {
            'legendary' => 300,
            'epic' => 200,
            'rare' => 125,
            'uncommon' => 75,
            default => 50,
        };
    }

    public function progress(User $user): array
    {
        $totalXp = (int) $user->experienceEvents()->sum('points');

        return $this->progressForXp($totalXp);
    }

    public function progressForXp(int $totalXp): array
    {
        $totalXp = max(0, $totalXp);
        $spentXp = 0;

        foreach (self::RANKS as $rankIndex => $rank) {
            foreach ($rank['requirements'] as $levelIndex => $requiredXp) {
                $nextRank = self::RANKS[min($rankIndex + 1, count(self::RANKS) - 1)];

                if ($totalXp < $spentXp + $requiredXp) {
                    $levelXp = $totalXp - $spentXp;

                    return [
                        'rank' => $rank['name'],
                        'rank_slug' => strtolower($rank['name']),
                        'level' => $levelIndex + 1,
                        'level_count' => 4,
                        'total_xp' => $totalXp,
                        'level_xp' => $levelXp,
                        'required_xp' => $requiredXp,
                        'percent' => min(100, round(($levelXp / $requiredXp) * 100, 1)),
                        'color' => $rank['color'],
                        'next_color' => $levelIndex === 3 ? $nextRank['color'] : $rank['color'],
                        'next_label' => $levelIndex === 3
                            ? $nextRank['name'] . ' I'
                            : $rank['name'] . ' ' . ($levelIndex + 2),
                        'is_max' => false,
                    ];
                }

                $spentXp += $requiredXp;
            }
        }

        $last = self::RANKS[array_key_last(self::RANKS)];

        return [
            'rank' => $last['name'],
            'rank_slug' => strtolower($last['name']),
            'level' => 4,
            'level_count' => 4,
            'total_xp' => $totalXp,
            'level_xp' => $last['requirements'][3],
            'required_xp' => $last['requirements'][3],
            'percent' => 100,
            'color' => $last['color'],
            'next_color' => '#ffffff',
            'next_label' => 'Max rank',
            'is_max' => true,
        ];
    }
}
