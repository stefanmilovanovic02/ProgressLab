<?php

namespace App\Http\Controllers;

use App\Enums\UserRole;
use App\Models\TrainerClient;
use App\Models\User;
use App\Services\NotificationService;
use Illuminate\Http\Request;

class TrainerRelationshipController extends Controller
{
    public function invite(Request $request, User $user, NotificationService $notifications)
    {
        $trainer = $request->user();
        abort_unless($trainer->isTrainer(), 403);
        abort_if($trainer->is($user), 422, 'You cannot invite yourself.');
        abort_unless($user->hasAnyRole([UserRole::User, UserRole::Paid]), 422, 'Only User or Paid accounts can become clients.');
        abort_unless($trainer->friends()->where('users.id', $user->id)->exists(), 422, 'You must be friends before sending a client invitation.');

        $relationship = TrainerClient::query()->updateOrCreate(
            ['trainer_id' => $trainer->id, 'client_id' => $user->id],
            [
                'status' => TrainerClient::STATUS_PENDING,
                'can_view_nutrition' => true,
                'can_view_exercises' => true,
                'can_view_weight' => true,
                'can_view_streaks' => true,
                'accepted_at' => null,
                'revoked_at' => null,
            ]
        );

        $notifications->notifyTrainerInvitation($relationship);

        return response()->json([
            'ok' => true,
            'relationship' => $this->payload($relationship),
        ]);
    }

    public function accept(Request $request, TrainerClient $trainerClient, NotificationService $notifications)
    {
        abort_unless($trainerClient->client_id === $request->user()->id, 403);
        abort_unless($trainerClient->status === TrainerClient::STATUS_PENDING, 422, 'This invitation is no longer pending.');

        $permissions = $this->permissions($request);
        $trainerClient->forceFill(array_merge($permissions, [
            'status' => TrainerClient::STATUS_ACCEPTED,
            'accepted_at' => now(),
            'revoked_at' => null,
        ]))->save();

        $notifications->notifyTrainerInvitationAccepted($trainerClient);

        return response()->json(['ok' => true, 'relationship' => $this->payload($trainerClient)]);
    }

    public function decline(Request $request, TrainerClient $trainerClient)
    {
        abort_unless($trainerClient->client_id === $request->user()->id, 403);
        abort_unless($trainerClient->status === TrainerClient::STATUS_PENDING, 422);

        $trainerClient->forceFill([
            'status' => TrainerClient::STATUS_DECLINED,
            'accepted_at' => null,
            'revoked_at' => now(),
        ])->save();

        return response()->json(['ok' => true]);
    }

    public function updatePermissions(Request $request, TrainerClient $trainerClient)
    {
        abort_unless($trainerClient->client_id === $request->user()->id, 403);
        abort_unless($trainerClient->isAccepted(), 422);

        $trainerClient->update($this->permissions($request));

        return response()->json(['ok' => true, 'relationship' => $this->payload($trainerClient)]);
    }

    public function destroy(Request $request, TrainerClient $trainerClient)
    {
        abort_unless(
            in_array($request->user()->id, [$trainerClient->trainer_id, $trainerClient->client_id], true),
            403
        );

        $trainerClient->forceFill([
            'status' => TrainerClient::STATUS_REVOKED,
            'revoked_at' => now(),
        ])->save();

        return response()->json(['ok' => true]);
    }

    private function permissions(Request $request): array
    {
        $validated = $request->validate([
            'can_view_nutrition' => ['required', 'boolean'],
            'can_view_exercises' => ['required', 'boolean'],
            'can_view_weight' => ['required', 'boolean'],
            'can_view_streaks' => ['required', 'boolean'],
        ]);

        return $validated;
    }

    private function payload(TrainerClient $relationship): array
    {
        return [
            'id' => $relationship->id,
            'status' => $relationship->status,
            'permissions' => [
                'nutrition' => $relationship->can_view_nutrition,
                'exercises' => $relationship->can_view_exercises,
                'weight' => $relationship->can_view_weight,
                'streaks' => $relationship->can_view_streaks,
                'progress_photos' => false,
            ],
        ];
    }
}
