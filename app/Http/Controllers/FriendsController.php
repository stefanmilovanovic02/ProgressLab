<?php

namespace App\Http\Controllers;

use App\Models\FriendRequest;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;

class FriendsController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        $friendsCount = $user->friends()->count();

        // Outgoing pending (you sent)
        $pendingSent = FriendRequest::query()
            ->where('sender_id', $user->id)
            ->where('status', 'pending')
            ->with(['receiver:id,name,username,email,avatar_path'])
            ->latest()
            ->get();

        // Incoming pending (they sent to you)
        $incomingRequests = FriendRequest::query()
            ->where('receiver_id', $user->id)
            ->where('status', 'pending')
            ->with(['sender:id,name,username,email,avatar_path'])
            ->latest()
            ->get();

        return view('friends.index', [
            'friendsCount' => $friendsCount,
            'pendingSent' => $pendingSent,
            'incomingRequests' => $incomingRequests,
        ]);
    }

    public function search(Request $request)
    {
        $request->validate([
            'q' => ['nullable', 'string', 'max:100'],
        ]);

        $q = trim((string) $request->input('q', ''));
        $auth = $request->user();

        if ($q === '' || mb_strlen($q) < 2) {
            return response()->json(['data' => []]);
        }

        $users = User::query()
            ->where('id', '!=', $auth->id)
            ->where(function ($query) use ($q) {
                $query->where('name', 'like', "%{$q}%")
                    ->orWhere('username', 'like', "%{$q}%")
                    ->orWhere('email', 'like', "%{$q}%");
            })
            ->limit(8)
            ->get(['id', 'name', 'username', 'email', 'avatar_path']);

        $friendIds = $auth->friends()->pluck('users.id')->all();

        $sentPendingTo = FriendRequest::query()
            ->where('sender_id', $auth->id)
            ->where('status', 'pending')
            ->pluck('receiver_id')
            ->all();

        $receivedPendingFrom = FriendRequest::query()
            ->where('receiver_id', $auth->id)
            ->where('status', 'pending')
            ->pluck('sender_id')
            ->all();

        $data = $users->map(function ($u) use ($friendIds, $sentPendingTo, $receivedPendingFrom) {
            $state = 'add';

            if (in_array($u->id, $friendIds, true)) {
                $state = 'friends';
            } elseif (in_array($u->id, $sentPendingTo, true)) {
                $state = 'pending';
            } elseif (in_array($u->id, $receivedPendingFrom, true)) {
                $state = 'incoming';
            }

            // IMPORTANT: make correct URL for stored avatar
           $avatarUrl = $u->avatar_url;

            return [
                'id' => $u->id,
                'name' => $u->name,
                'username' => $u->username,
                'email' => $u->email,
                'avatar_url' => $avatarUrl,
                'state' => $state,
            ];
        });

        return response()->json(['data' => $data]);
    }

    public function sendRequest(Request $request)
    {
        $request->validate([
            'user_id' => ['required', 'integer', 'exists:users,id'],
        ]);

        $auth = $request->user();
        $targetId = (int) $request->input('user_id');

        if ($targetId === $auth->id) {
            return response()->json(['message' => 'You cannot add yourself.'], 422);
        }

        if ($auth->friends()->where('users.id', $targetId)->exists()) {
            return response()->json(['status' => 'friends']);
        }

        $incoming = FriendRequest::query()
            ->where('sender_id', $targetId)
            ->where('receiver_id', $auth->id)
            ->where('status', 'pending')
            ->exists();

        if ($incoming) {
            return response()->json(['status' => 'incoming']);
        }

        FriendRequest::updateOrCreate(
            ['sender_id' => $auth->id, 'receiver_id' => $targetId],
            ['status' => 'pending']
        );

        return response()->json(['status' => 'pending']);
    }

    public function accept(Request $request, FriendRequest $friendRequest)
    {
        $auth = $request->user();

        // Ensure this request is for me
        if ($friendRequest->receiver_id !== $auth->id || $friendRequest->status !== 'pending') {
            abort(403);
        }

        DB::transaction(function () use ($auth, $friendRequest) {
            // Make friendship both directions
            DB::table('friends')->updateOrInsert(
                ['user_id' => $auth->id, 'friend_id' => $friendRequest->sender_id],
                ['created_at' => now(), 'updated_at' => now()]
            );

            DB::table('friends')->updateOrInsert(
                ['user_id' => $friendRequest->sender_id, 'friend_id' => $auth->id],
                ['created_at' => now(), 'updated_at' => now()]
            );

            $friendRequest->update(['status' => 'accepted']);
        });

        return response()->json(['ok' => true]);
    }

    public function decline(Request $request, FriendRequest $friendRequest)
    {
        $auth = $request->user();

        if ($friendRequest->receiver_id !== $auth->id || $friendRequest->status !== 'pending') {
            abort(403);
        }

        $friendRequest->update(['status' => 'declined']);

        return response()->json(['ok' => true]);
    }
}