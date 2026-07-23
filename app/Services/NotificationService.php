<?php

namespace App\Services;

use App\Models\AppNotification;
use App\Models\Achievement;
use App\Models\FriendActivity;
use App\Models\FriendRequest;
use App\Models\User;
use App\Models\UserAchievement;
use Illuminate\Support\Facades\Schema;

class NotificationService
{
    private const RECENT_ACTIVITY_DAYS = 30;

    public function __construct(private readonly WebPushService $webPush)
    {
    }

    public function syncForUser(User $user): void
    {
        if (!Schema::hasTable('app_notifications')) {
            return;
        }

        $rows = [$this->welcomeNotification($user)];

        if (Schema::hasTable('friends') && Schema::hasTable('friend_activities')) {
            $rows = array_merge($rows, $this->friendActivityNotifications($user));
        }

        if (Schema::hasTable('friend_requests')) {
            $rows = array_merge($rows, $this->friendRequestNotifications($user));
        }

        if (Schema::hasTable('user_achievements') && Schema::hasTable('achievements')) {
            $rows = array_merge($rows, $this->achievementNotifications($user));
        }

        AppNotification::query()->upsert(
            $rows,
            ['user_id', 'source_type', 'source_id'],
            ['category', 'title', 'message', 'icon', 'action_url', 'data', 'updated_at']
        );
    }

    public function unreadCount(User $user): int
    {
        if (!Schema::hasTable('app_notifications')) {
            return 0;
        }

        return $user->appNotifications()->whereNull('read_at')->count();
    }

    public function sendSystem(
        User $user,
        string $key,
        string $title,
        string $message,
        ?string $actionUrl = null,
        bool $push = true
    ): AppNotification {
        $notification = AppNotification::query()->updateOrCreate(
            [
                'user_id' => $user->id,
                'source_type' => 'system',
                'source_id' => (int) sprintf('%u', crc32($key)),
            ],
            [
                'category' => 'system',
                'title' => $title,
                'message' => $message,
                'icon' => '✨',
                'action_url' => $actionUrl,
                'data' => ['key' => $key],
            ]
        );

        if ($push && $notification->wasRecentlyCreated) {
            $this->deliver($user, $notification);
        }

        return $notification;
    }

    public function notifyAchievementUnlocked(
        User $user,
        UserAchievement $unlock,
        Achievement $achievement,
        FriendActivity $activity
    ): void {
        $ownNotification = AppNotification::query()->updateOrCreate(
            [
                'user_id' => $user->id,
                'source_type' => 'achievement',
                'source_id' => $unlock->id,
            ],
            [
                'category' => 'achievement',
                'title' => 'Achievement unlocked',
                'message' => 'You unlocked “' . $achievement->title . '”.',
                'icon' => '🏆',
                'action_url' => route('achievements.index', [], false),
                'data' => [
                    'achievement_id' => $achievement->id,
                    'rarity' => $achievement->rarity,
                    'image_path' => $achievement->image_path,
                ],
            ]
        );

        if ($ownNotification->wasRecentlyCreated) {
            $this->deliver($user, $ownNotification);
        }

        $actorName = $user->full_name ?: $user->name ?: $user->username ?: 'A friend';

        $user->friends()->each(function (User $friend) use ($activity, $achievement, $actorName, $user) {
            $notification = AppNotification::query()->updateOrCreate(
                [
                    'user_id' => $friend->id,
                    'source_type' => 'friend_activity',
                    'source_id' => $activity->id,
                ],
                [
                    'category' => 'friend',
                    'title' => 'Friend achievement',
                    'message' => $actorName . ' unlocked “' . $achievement->title . '”.',
                    'icon' => '🏆',
                    'action_url' => route('friends.index', [], false),
                    'data' => [
                        'actor_id' => $user->id,
                        'actor_name' => $actorName,
                        'achievement_id' => $achievement->id,
                    ],
                ]
            );

            if ($notification->wasRecentlyCreated) {
                $this->deliver($friend, $notification);
            }
        });
    }

    public function notifyFriendActivity(FriendActivity $activity): int
    {
        if (!Schema::hasTable('app_notifications') || !Schema::hasTable('friends')) {
            return 0;
        }

        $activity->loadMissing('user');
        $actor = $activity->user;

        if (!$actor) {
            return 0;
        }

        $actorName = $actor->full_name ?: $actor->name ?: $actor->username ?: 'A friend';
        $title = match ($activity->type) {
            'nutrition' => 'Friend logged nutrition',
            'workout' => 'Friend completed a workout',
            default => 'Friend activity',
        };
        $icon = match ($activity->type) {
            'workout' => '🏋️',
            'nutrition' => '🍽️',
            'streak' => '🔥',
            default => '✨',
        };
        $delivered = 0;

        $actor->friends()->each(function (User $friend) use (
            $activity,
            $actor,
            $actorName,
            $title,
            $icon,
            &$delivered
        ) {
            $notification = AppNotification::query()->updateOrCreate(
                [
                    'user_id' => $friend->id,
                    'source_type' => 'friend_activity',
                    'source_id' => $activity->id,
                ],
                [
                    'category' => 'friend',
                    'title' => $title,
                    'message' => $actorName . ' ' . $activity->text,
                    'icon' => $icon,
                    'action_url' => route('friends.index', [], false),
                    'data' => [
                        'actor_id' => $actor->id,
                        'actor_name' => $actorName,
                        'actor_avatar' => $actor->avatar_url,
                        'activity_type' => $activity->type,
                    ],
                ]
            );

            if (!$notification->push_sent_at) {
                $accepted = $this->deliver($friend, $notification);
                $delivered += $accepted;

                if ($accepted > 0) {
                    $notification->forceFill(['push_sent_at' => now()])->save();
                }
            }
        });

        return $delivered;
    }

    public function deliver(User $user, AppNotification $notification): int
    {
        return $this->webPush->sendToUser($user, [
            'title' => $notification->title,
            'body' => $notification->message,
            'url' => $notification->action_url ?: route('notifications.index', [], false),
            'tag' => $notification->source_type . '-' . $notification->source_id,
            'category' => $notification->category,
            'badgeCount' => $this->unreadCount($user),
        ]);
    }

    private function welcomeNotification(User $user): array
    {
        return $this->row(
            $user,
            'system',
            1,
            'system',
            'Welcome to your notification center',
            'Friend activity, achievements, requests, and ProgressLab updates will appear here.',
            '✨',
            route('home', [], false),
            ['key' => 'notification-center-welcome'],
            $user->created_at ?? now()
        );
    }

    private function friendActivityNotifications(User $user): array
    {
        $friendIds = $user->friends()->pluck('users.id');

        if ($friendIds->isEmpty()) {
            return [];
        }

        return FriendActivity::query()
            ->with('user:id,name,full_name,username,avatar_path')
            ->whereIn('user_id', $friendIds)
            ->where('created_at', '>=', now()->subDays(self::RECENT_ACTIVITY_DAYS))
            ->latest()
            ->limit(100)
            ->get()
            ->map(function (FriendActivity $activity) use ($user) {
                $actor = $activity->user;
                $name = $actor?->full_name ?: $actor?->name ?: $actor?->username ?: 'A friend';

                return $this->row(
                    $user,
                    'friend_activity',
                    $activity->id,
                    'friend',
                    'Friend activity',
                    $name . ' ' . $activity->text,
                    match ($activity->type) {
                        'achievement' => '🏆',
                        'workout' => '🏋️',
                        'nutrition' => '🍽️',
                        'streak' => '🔥',
                        default => '✨',
                    },
                    route('friends.index', [], false),
                    [
                        'actor_id' => $actor?->id,
                        'actor_name' => $name,
                        'actor_avatar' => $actor?->avatar_url,
                        'activity_type' => $activity->type,
                    ],
                    $activity->created_at
                );
            })
            ->all();
    }

    private function friendRequestNotifications(User $user): array
    {
        $incoming = FriendRequest::query()
            ->with('sender:id,name,full_name,username,avatar_path')
            ->where('receiver_id', $user->id)
            ->where('status', 'pending')
            ->latest()
            ->limit(50)
            ->get()
            ->map(function (FriendRequest $request) use ($user) {
                $name = $request->sender?->full_name
                    ?: $request->sender?->name
                    ?: $request->sender?->username
                    ?: 'Someone';

                return $this->row(
                    $user,
                    'friend_request',
                    $request->id,
                    'friend',
                    'New friend request',
                    $name . ' sent you a friend request.',
                    '👋',
                    route('friends.index', [], false),
                    ['sender_id' => $request->sender_id],
                    $request->created_at
                );
            });

        $accepted = FriendRequest::query()
            ->with('receiver:id,name,full_name,username,avatar_path')
            ->where('sender_id', $user->id)
            ->where('status', 'accepted')
            ->latest('updated_at')
            ->limit(50)
            ->get()
            ->map(function (FriendRequest $request) use ($user) {
                $name = $request->receiver?->full_name
                    ?: $request->receiver?->name
                    ?: $request->receiver?->username
                    ?: 'A user';

                return $this->row(
                    $user,
                    'friend_request_accepted',
                    $request->id,
                    'friend',
                    'Friend request accepted',
                    $name . ' accepted your friend request.',
                    '🤝',
                    route('friends.index', [], false),
                    ['friend_id' => $request->receiver_id],
                    $request->updated_at
                );
            });

        return $incoming->concat($accepted)->all();
    }

    private function achievementNotifications(User $user): array
    {
        return UserAchievement::query()
            ->with('achievement:id,title,description,image_path,rarity')
            ->where('user_id', $user->id)
            ->whereNotNull('unlocked_at')
            ->latest('unlocked_at')
            ->limit(100)
            ->get()
            ->map(function (UserAchievement $unlock) use ($user) {
                $achievement = $unlock->achievement;

                return $this->row(
                    $user,
                    'achievement',
                    $unlock->id,
                    'achievement',
                    'Achievement unlocked',
                    'You unlocked “' . ($achievement?->title ?? 'a new achievement') . '”.',
                    '🏆',
                    route('achievements.index', [], false),
                    [
                        'achievement_id' => $achievement?->id,
                        'rarity' => $achievement?->rarity,
                        'image_path' => $achievement?->image_path,
                    ],
                    $unlock->unlocked_at
                );
            })
            ->all();
    }

    private function row(
        User $user,
        string $sourceType,
        int $sourceId,
        string $category,
        string $title,
        string $message,
        string $icon,
        ?string $actionUrl,
        array $data,
        $createdAt
    ): array {
        return [
            'user_id' => $user->id,
            'source_type' => $sourceType,
            'source_id' => $sourceId,
            'category' => $category,
            'title' => $title,
            'message' => $message,
            'icon' => $icon,
            'action_url' => $actionUrl,
            'data' => json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
            'created_at' => $createdAt ?? now(),
            'updated_at' => now(),
        ];
    }
}
