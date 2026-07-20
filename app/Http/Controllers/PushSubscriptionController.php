<?php

namespace App\Http\Controllers;

use App\Models\PushSubscription;
use App\Services\NotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PushSubscriptionController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'endpoint' => ['required', 'url:https', 'max:2048'],
            'keys.p256dh' => ['required', 'string', 'max:512'],
            'keys.auth' => ['required', 'string', 'max:255'],
            'contentEncoding' => ['nullable', 'in:aes128gcm,aesgcm'],
        ]);

        PushSubscription::query()->updateOrCreate(
            ['endpoint' => $validated['endpoint']],
            [
                'user_id' => $request->user()->id,
                'public_key' => $validated['keys']['p256dh'],
                'auth_token' => $validated['keys']['auth'],
                'content_encoding' => $validated['contentEncoding'] ?? 'aes128gcm',
                'user_agent' => mb_substr((string) $request->userAgent(), 0, 500),
            ]
        );

        return response()->json(['ok' => true]);
    }

    public function destroy(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'endpoint' => ['required', 'url:https', 'max:2048'],
        ]);

        $request->user()->pushSubscriptions()
            ->where('endpoint', $validated['endpoint'])
            ->delete();

        return response()->json(['ok' => true]);
    }

    public function test(Request $request, NotificationService $notifications): JsonResponse
    {
        $notification = $notifications->sendSystem(
            $request->user(),
            'push-test-' . now()->format('Y-m-d-H-i-s-u'),
            'Push notifications are working',
            'ProgressLab can now remind you about streaks, friends, and achievements.',
            route('notifications.index', [], false),
            false
        );

        $sent = $notifications->deliver($request->user(), $notification);

        if ($sent < 1) {
            return response()->json([
                'ok' => false,
                'message' => 'The push provider did not accept the notification. Please disable push on this device, enable it again, and retry.',
            ], 502);
        }

        return response()->json([
            'ok' => true,
            'notification_id' => $notification->id,
            'sent' => $sent,
            'message' => 'The push provider accepted the test notification.',
        ]);
    }
}
