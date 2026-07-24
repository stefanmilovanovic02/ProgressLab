<?php

namespace App\Services;

use App\Models\Exercise;
use App\Models\User;
use App\Models\UserExerciseRank;
use Illuminate\Support\Collection;

class ExerciseRankService
{
    private const RANKS = [
        ['name' => 'Bronze', 'minimum' => 0, 'color' => '#b87333'],
        ['name' => 'Silver', 'minimum' => 20, 'color' => '#bfc7d5'],
        ['name' => 'Gold', 'minimum' => 35, 'color' => '#f6c945'],
        ['name' => 'Platinum', 'minimum' => 50, 'color' => '#65e6d4'],
        ['name' => 'Diamond', 'minimum' => 65, 'color' => '#62b7ff'],
        ['name' => 'Master', 'minimum' => 78, 'color' => '#a875ff'],
        ['name' => 'Titan', 'minimum' => 90, 'color' => '#ef5b78'],
        ['name' => 'Olympian', 'minimum' => 100, 'color' => '#fff2a8'],
    ];

    public function evaluate(User $user, Exercise $exercise, array $sets): ?array
    {
        $standard = $exercise->rankStandard;
        if (!$standard?->is_active || !$standard->olympian_target) {
            return null;
        }

        $measurement = $this->measurement($user, $standard->scoring_type, $sets);
        if (!$measurement || $measurement['value'] <= 0) {
            return null;
        }

        $existing = UserExerciseRank::query()
            ->where('user_id', $user->id)
            ->where('exercise_id', $exercise->id)
            ->first();

        if ($existing && $measurement['value'] <= $existing->best_value) {
            return null;
        }

        $score = min(100, round(
            ($measurement['value'] / (float) $standard->olympian_target) * 100,
            2
        ));
        $rank = $this->rankForScore($score);
        $oldRank = $existing?->rank;

        $record = UserExerciseRank::query()->updateOrCreate(
            ['user_id' => $user->id, 'exercise_id' => $exercise->id],
            [
                'best_value' => $measurement['value'],
                'best_estimated_1rm' => $measurement['estimated_1rm'],
                'score' => $score,
                'rank' => $rank['name'],
                'ranked_at' => now(),
            ]
        );

        if ($oldRank !== null && $this->rankIndex($rank['name']) <= $this->rankIndex($oldRank)) {
            return null;
        }

        return $this->promotionPayload($exercise, $record, $oldRank);
    }

    public function currentForUser(User $user): Collection
    {
        return UserExerciseRank::query()
            ->where('user_id', $user->id)
            ->get()
            ->mapWithKeys(fn (UserExerciseRank $rank) => [
                (string) $rank->exercise_id => $this->rankSummary($rank),
            ]);
    }

    public function rankSummary(UserExerciseRank $record): array
    {
        $rank = collect(self::RANKS)->firstWhere('name', $record->rank) ?? self::RANKS[0];

        return [
            'rank' => $record->rank,
            'rank_slug' => strtolower($record->rank),
            'score' => round($record->score, 1),
            'color' => $rank['color'],
            'icon' => asset('images/ranks/' . strtolower($record->rank) . '.png'),
        ];
    }

    private function measurement(User $user, string $type, array $sets): ?array
    {
        $validSets = collect($sets)->filter(fn (array $set) =>
            isset($set['reps'])
            && is_numeric($set['reps'])
            && (float) $set['reps'] > 0
            && isset($set['weight_kg'])
            && is_numeric($set['weight_kg'])
            && (float) $set['weight_kg'] >= 0
        );

        if ($validSets->isEmpty()) {
            return null;
        }

        if ($type === 'repetitions') {
            return [
                'value' => (float) $validSets->max('reps'),
                'estimated_1rm' => null,
            ];
        }

        $bodyweight = 0.0;
        if (in_array($type, ['estimated_1rm_bodyweight', 'assisted_bodyweight'], true)) {
            $bodyweight = (float) ($user->metric?->weight_kg ?? 0);
            if ($bodyweight <= 0) {
                return null;
            }
        }

        $bestE1rm = 0.0;
        foreach ($validSets as $set) {
            $reps = min(30, (float) $set['reps']);
            $weight = (float) $set['weight_kg'];

            if ($type === 'assisted_bodyweight') {
                $weight = max(0, $bodyweight - $weight);
            }

            $bestE1rm = max($bestE1rm, $weight * (1 + ($reps / 30)));
        }

        return [
            'value' => $type === 'estimated_1rm_absolute'
                ? $bestE1rm
                : $bestE1rm / $bodyweight,
            'estimated_1rm' => $bestE1rm,
        ];
    }

    private function rankForScore(float $score): array
    {
        $rank = self::RANKS[0];
        foreach (self::RANKS as $candidate) {
            if ($score >= $candidate['minimum']) {
                $rank = $candidate;
            }
        }

        return $rank;
    }

    private function rankIndex(string $rank): int
    {
        foreach (self::RANKS as $index => $candidate) {
            if ($candidate['name'] === $rank) {
                return $index;
            }
        }

        return -1;
    }

    private function promotionPayload(
        Exercise $exercise,
        UserExerciseRank $record,
        ?string $oldRank
    ): array {
        return array_merge($this->rankSummary($record), [
            'exercise_id' => $exercise->id,
            'exercise_name' => $exercise->name,
            'previous_rank' => $oldRank,
        ]);
    }
}
