<?php

namespace App\Services;

use App\Models\TrainerClient;
use App\Models\User;

class TrainerClientAccessService
{
    public function relationship(User $trainer, User $client, ?string $area = null): TrainerClient
    {
        abort_unless($trainer->isTrainer(), 403);

        $relationship = TrainerClient::query()
            ->where('trainer_id', $trainer->id)
            ->where('client_id', $client->id)
            ->where('status', TrainerClient::STATUS_ACCEPTED)
            ->first();

        abort_unless($relationship, 403, 'This client has not granted Trainer access.');

        if ($area !== null) {
            abort_unless(
                in_array($area, ['nutrition', 'exercises', 'weight', 'streaks'], true)
                    && $relationship->permits($area),
                403,
                'The client has not shared this information.'
            );
        }

        return $relationship;
    }
}
