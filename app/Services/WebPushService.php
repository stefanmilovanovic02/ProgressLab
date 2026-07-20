<?php

namespace App\Services;

use App\Models\PushSubscription as PushSubscriptionModel;
use App\Models\User;
use Composer\CaBundle\CaBundle;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Minishlink\WebPush\Subscription;
use Minishlink\WebPush\WebPush;
use Throwable;

class WebPushService
{
    public function isConfigured(): bool
    {
        return filled(config('services.webpush.public_key'))
            && filled(config('services.webpush.private_key'))
            && filled(config('services.webpush.subject'));
    }

    public function sendToUser(User $user, array $payload): int
    {
        if (!$this->isConfigured() || !Schema::hasTable('push_subscriptions')) {
            return 0;
        }

        $subscriptions = $user->pushSubscriptions()->get();
        if ($subscriptions->isEmpty()) {
            return 0;
        }

        try {
            $webPush = new WebPush(
                [
                    'VAPID' => [
                        'subject' => config('services.webpush.subject'),
                        'publicKey' => config('services.webpush.public_key'),
                        'privateKey' => config('services.webpush.private_key'),
                    ],
                ],
                [],
                30,
                ['verify' => CaBundle::getBundledCaBundlePath()]
            );

            $body = json_encode(array_merge([
                'title' => 'ProgressLab',
                'body' => 'You have a new notification.',
                'icon' => asset('images/branding/progresslab-logo.png'),
                'badge' => asset('images/branding/progresslab-favicon.png'),
                'url' => route('notifications.index', [], false),
                'tag' => 'progresslab-notification',
            ], $payload), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);

            foreach ($subscriptions as $subscription) {
                $webPush->queueNotification(
                    Subscription::create([
                        'endpoint' => $subscription->endpoint,
                        'publicKey' => $subscription->public_key,
                        'authToken' => $subscription->auth_token,
                        'contentEncoding' => $subscription->content_encoding,
                    ]),
                    $body,
                    ['TTL' => 3600, 'urgency' => 'normal']
                );
            }

            $sent = 0;
            foreach ($webPush->flush() as $report) {
                if ($report->isSuccess()) {
                    $sent++;
                } elseif ($report->isSubscriptionExpired()) {
                    PushSubscriptionModel::query()
                        ->where('endpoint', $report->getEndpoint())
                        ->delete();
                } else {
                    Log::warning('Web Push delivery failed.', [
                        'endpoint' => $report->getEndpoint(),
                        'reason' => $report->getReason(),
                    ]);
                }
            }

            return $sent;
        } catch (Throwable $exception) {
            Log::error('Web Push could not be sent.', [
                'user_id' => $user->id,
                'message' => $exception->getMessage(),
            ]);

            return 0;
        }
    }
}
