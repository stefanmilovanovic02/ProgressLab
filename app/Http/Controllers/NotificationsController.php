<?php

namespace App\Http\Controllers;

use App\Models\AppNotification;
use App\Services\NotificationService;
use Illuminate\Http\Request;

class NotificationsController extends Controller
{
    public function index(Request $request, NotificationService $notificationService)
    {
        $user = $request->user();
        $notificationService->syncForUser($user);

        $validated = $request->validate([
            'filter' => ['nullable', 'in:all,unread,friend,achievement,system'],
        ]);

        $filter = $validated['filter'] ?? 'all';
        $query = $user->appNotifications()->latest();

        match ($filter) {
            'unread' => $query->whereNull('read_at'),
            'friend', 'achievement', 'system' => $query->where('category', $filter),
            default => null,
        };

        $notifications = $query->paginate(15)->withQueryString();
        $counts = [
            'all' => $user->appNotifications()->count(),
            'unread' => $user->appNotifications()->whereNull('read_at')->count(),
            'friend' => $user->appNotifications()->where('category', 'friend')->count(),
            'achievement' => $user->appNotifications()->where('category', 'achievement')->count(),
            'system' => $user->appNotifications()->where('category', 'system')->count(),
        ];

        return view('notifications.index', compact('notifications', 'counts', 'filter'));
    }

    public function read(Request $request, AppNotification $notification)
    {
        $this->ensureOwner($request, $notification);
        $notification->update(['read_at' => $notification->read_at ?? now()]);

        return back()->with('status', 'Notification marked as read.');
    }

    public function readAll(Request $request)
    {
        $request->user()
            ->appNotifications()
            ->whereNull('read_at')
            ->update(['read_at' => now()]);

        return back()->with('status', 'All notifications marked as read.');
    }

    public function open(Request $request, AppNotification $notification)
    {
        $this->ensureOwner($request, $notification);
        $notification->update(['read_at' => $notification->read_at ?? now()]);

        $url = $notification->action_url;
        if (!$url || !str_starts_with($url, '/') || str_starts_with($url, '//')) {
            $url = route('notifications.index');
        }

        return redirect()->to($url);
    }

    private function ensureOwner(Request $request, AppNotification $notification): void
    {
        abort_unless($notification->user_id === $request->user()->id, 403);
    }
}
