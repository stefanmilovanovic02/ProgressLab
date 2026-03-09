<?php

namespace App\Http\Controllers;

use App\Models\FriendRequest;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class FriendsController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        $friends = $user->friends()
        ->select('users.id', 'users.name', 'users.username', 'users.email', 'users.avatar_path')
        ->get();

        $friendsCount = $user->friends()->count();

        // Grab last login timestamp per friend (adjust table/column if different)
    $lastLogins = DB::table('login_logs')
    ->select('user_id', DB::raw('MAX(updated_at) as last_seen'))
    ->whereIn('user_id', $friends->pluck('id'))
    ->groupBy('user_id')
    ->pluck('last_seen', 'user_id');

    // Compute login streak per friend (simple consecutive-day streak based on login_logs)
    $friendsCards = $friends->map(function ($f) use ($lastLogins) {
        $lastSeen = $lastLogins[$f->id] ?? null;

        // Status thresholds (tweak later)
        $status = 'Offline';
        $dot = 'offline';

        if ($lastSeen) {
            $secs = now()->diffInSeconds(\Carbon\Carbon::parse($lastSeen));

            if ($secs <= 60) {          
                $status = 'Online';
                $dot = 'online';
            } elseif ($secs <= 7200) {
                $status = 'Recently Active';
                $dot = 'recent';
            } else {
                $status = 'Offline';
                $dot = 'offline';
            }
        }

        // login streak (consecutive days with at least one login)
        $streak = $this->loginStreakForUser($f->id);

        return [
            'id' => $f->id,
            'name' => $f->name,
            'avatar_url' => $this->publicImageUrl($f->avatar_path),
            'status' => $status,
            'dot' => $dot,
            'streak' => $streak,
            'last_seen' => $this->humanLastSeen($lastSeen),
        ];
    });

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
            'friendsCards' => $friendsCards,
            'pendingSent' => $pendingSent,
            'incomingRequests' => $incomingRequests,
        ]);
    }

    /**
 * Streak = consecutive days up to today where the user logged in at least once.
 * Adjust if your streaks logic differs.
 */
private function loginStreakForUser(int $userId): int
{
    $days = DB::table('login_logs')
        ->where('user_id', $userId)
        ->orderByDesc('login_date')
        ->pluck('login_date')
        ->map(fn ($d) => \Carbon\Carbon::parse($d)->toDateString())
        ->unique()
        ->values();

    if ($days->isEmpty()) return 0;

    $streak = 0;
    $cursor = now()->toDateString();

    // If the latest login is not today, allow streak to continue if last login was yesterday?
    // We'll count from today if present, else from yesterday.
    if (!$days->contains($cursor)) {
        $yesterday = now()->subDay()->toDateString();
        if ($days->contains($yesterday)) {
            $cursor = $yesterday;
        } else {
            return 0;
        }
    }

    while ($days->contains($cursor)) {
        $streak++;
        $cursor = \Carbon\Carbon::parse($cursor)->subDay()->toDateString();
    }

    return $streak;
}

private function humanLastSeen($timestamp): string
{
    if (!$timestamp) return '—';

    $dt = \Carbon\Carbon::parse($timestamp);
    $secs = $dt->diffInSeconds(now());

    if ($secs <= 5) return 'Active now';
    if ($secs < 60) return 'Last seen ' . $secs . ' seconds ago';

    $mins = intdiv($secs, 60);
    if ($mins < 60) return 'Last seen ' . $mins . ' minutes ago';

    $hours = intdiv($mins, 60);
    if ($hours < 24) return 'Last seen ' . $hours . ' hours ago';

    $days = intdiv($hours, 24);
    return 'Last seen ' . $days . ' days ago';
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
           $avatarUrl = $this->publicImageUrl($u->avatar_path);

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

    private function publicImageUrl(?string $path): string
        {
            if (!$path) return asset('images/default-avatar.png');

            // already a full URL
            if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
                return $path;
            }

            // if stored like "storage/avatars/xxx.jpg"
            if (str_starts_with($path, 'storage/')) {
                return asset($path); // -> /storage/avatars/xxx.jpg
            }

            // if stored like "/storage/avatars/xxx.jpg"
            if (str_starts_with($path, '/storage/')) {
                return asset(ltrim($path, '/')); // -> /storage/avatars/xxx.jpg
            }

            // if stored like "avatars/xxx.jpg" (relative to disk 'public')
            // map to /storage/avatars/xxx.jpg
            return asset('storage/' . ltrim($path, '/'));
        }

        private function humanLastSeenSeconds($timestamp): string
        {
            if (!$timestamp) return '—';

            $dt = \Carbon\Carbon::parse($timestamp);
            $secs = $dt->diffInSeconds(now());

            if ($secs <= 5) return 'Just now';
            if ($secs < 60) return $secs . ' seconds ago';

            $mins = intdiv($secs, 60);
            if ($mins < 60) return $mins . ' minutes ago';

            $hours = intdiv($mins, 60);
            if ($hours < 24) return $hours . ' hours ago';

            $days = intdiv($hours, 24);
            return $days . ' days ago';
        }

        // workout streak (expects workout_logs table with date column OR created_at)
        private function workoutStreakForUser(int $userId): int
        {
            if (!Schema::hasTable('workout_logs')) return 0;

            $query = DB::table('workout_logs')->where('user_id', $userId);

            // if you have a date column like "log_date" use it, else fallback to created_at
            $col = Schema::hasColumn('workout_logs', 'log_date') ? 'log_date' : 'created_at';

            $days = $query->orderByDesc($col)->pluck($col)
                ->map(fn ($d) => \Carbon\Carbon::parse($d)->toDateString())
                ->unique()
                ->values();

            if ($days->isEmpty()) return 0;

            $streak = 0;
            $cursor = now()->toDateString();

            if (!$days->contains($cursor)) {
                $y = now()->subDay()->toDateString();
                if ($days->contains($y)) $cursor = $y;
                else return 0;
            }

            while ($days->contains($cursor)) {
                $streak++;
                $cursor = \Carbon\Carbon::parse($cursor)->subDay()->toDateString();
            }

            return $streak;
        }

public function summary(Request $request, User $user)
{
    $auth = $request->user();

    $isFriend = $auth->friends()->where('users.id', $user->id)->exists();
    if (!$isFriend && $auth->id !== $user->id) abort(403);

    // LAST ACTIVE (your login_logs are per-day; updated_at is newest activity)
    $lastActive = DB::table('login_logs')
        ->where('user_id', $user->id)
        ->max('updated_at'); // ✅ this fixes “20 min ago but logged 20 sec ago”

    // Status thresholds
    $status = 'Offline';
    $dot = 'offline';
    if ($lastActive) {
        $secs = \Carbon\Carbon::parse($lastActive)->diffInSeconds(now());
        if ($secs <= 60) { $status = 'Online'; $dot = 'online'; }
        elseif ($secs <= 7200) { $status = 'Recently Active'; $dot = 'recent'; }
    }

    // QUICK STATS
    $workoutsLogged = DB::table('workout_logs')
        ->where('user_id', $user->id)
        ->count();

    $daysThisMonth = DB::table('login_logs')
        ->where('user_id', $user->id)
        ->whereBetween('login_date', [
            now()->startOfMonth()->toDateString(),
            now()->endOfMonth()->toDateString()
        ])
        ->count(); // one row per day ✅

    $friendsCount = DB::table('friends')
        ->where('user_id', $user->id)
        ->count();

    // STREAKS
    $loginDates = DB::table('login_logs')
        ->where('user_id', $user->id)
        ->orderByDesc('login_date')
        ->pluck('login_date')
        ->all();
    $loginStreak = $this->consecutiveDaysStreak($loginDates);

    $workoutDates = DB::table('workout_logs')
        ->where('user_id', $user->id)
        ->orderByDesc('entry_date')
        ->pluck('entry_date')
        ->all();
    $workoutStreak = $this->consecutiveDaysStreak($workoutDates);

    // Third streak: WATER (days with water_ml > 0)
    $waterDates = DB::table('nutrition_entries')
        ->where('user_id', $user->id)
        ->where('water_ml', '>', 0)
        ->orderByDesc('entry_date')
        ->pluck('entry_date')
        ->all();
    $waterStreak = $this->consecutiveDaysStreak($waterDates);

    // ACHIEVEMENTS (top 3 unlocked)
    $achievements = [];
    $achUnlockedCount = 0;

    if (Schema::hasTable('user_achievements') && Schema::hasTable('achievements')) {
        $achUnlockedCount = DB::table('user_achievements')
            ->where('user_id', $user->id)
            ->whereNotNull('unlocked_at')
            ->count();

        $achievements = DB::table('user_achievements')
            ->join('achievements', 'achievements.id', '=', 'user_achievements.achievement_id')
            ->where('user_achievements.user_id', $user->id)
            ->whereNotNull('user_achievements.unlocked_at')
            ->orderByDesc('user_achievements.unlocked_at')
            ->limit(3)
            ->get([
                'achievements.title',
                'achievements.image_path',
                'achievements.rarity',
                'achievements.category',
            ])
            ->map(fn($a) => [
                'title' => $a->title,
                'image_url' => $a->image_path ? asset($a->image_path) : null,
                'rarity' => $a->rarity,
                'category' => $a->category,
            ])
            ->values()
            ->all();
    }

    return response()->json([
        'user' => [
            'id' => $user->id,
            'name' => $user->full_name ?? $user->name ?? 'User',
            'username' => $user->username ?? '',
            'email' => $user->email ?? '',
            'avatar_url' => $this->publicImageUrl($user->avatar_path) ?? asset('images/default-avatar.png'),
            'cover_url' => $this->publicImageUrl($user->cover_path), // ex: storage/covers/...
            'joined_full' => $user->created_at ? $user->created_at->format('F j, Y') : '—',
            'joined_short' => $user->created_at ? $user->created_at->format('F j') : '—',
            'status' => $status,
            'dot' => $dot,
            'last_active' => $lastActive ? $this->humanLastSeenSeconds($lastActive) : '—',
        ],
        'quick' => [
            'workouts_logged' => $workoutsLogged,
            'days_this_month' => $daysThisMonth,
            'friends' => $friendsCount,
            'joined' => $user->created_at ? $user->created_at->format('F j') : '—',
        ],
        'streaks' => [
            ['label' => 'Login Streak', 'value' => $loginStreak, 'icon' => '🔥'],
            ['label' => 'Workout Streak', 'value' => $workoutStreak, 'icon' => '💪'],
            ['label' => 'Water Streak', 'value' => $waterStreak, 'icon' => '💧'],
        ],
        'achievements' => $achievements,
        'achievements_unlocked' => $achUnlockedCount,
    ]);
}

    private function consecutiveDaysStreak(array $dateStrings): int
    {
        $days = collect($dateStrings)
            ->map(fn($d) => \Carbon\Carbon::parse($d)->toDateString())
            ->unique()
            ->values();

        if ($days->isEmpty()) return 0;

        $streak = 0;
        $cursor = now()->toDateString();

        // allow continuing streak from yesterday if today not present
        if (!$days->contains($cursor)) {
            $y = now()->subDay()->toDateString();
            if ($days->contains($y)) $cursor = $y;
            else return 0;
        }

        while ($days->contains($cursor)) {
            $streak++;
            $cursor = \Carbon\Carbon::parse($cursor)->subDay()->toDateString();
        }

        return $streak;
    }


    public function comparisonExercises(Request $request, User $user)
        {
            $auth = $request->user();

            $isFriend = $auth->friends()->where('users.id', $user->id)->exists();
            if (!$isFriend && $auth->id !== $user->id) {
                abort(403);
            }

            $myExerciseIds = DB::table('workout_log_exercises as wle')
                ->join('workout_logs as wl', 'wl.id', '=', 'wle.workout_log_id')
                ->where('wl.user_id', $auth->id)
                ->pluck('wle.exercise_id')
                ->unique();

            $friendExerciseIds = DB::table('workout_log_exercises as wle')
                ->join('workout_logs as wl', 'wl.id', '=', 'wle.workout_log_id')
                ->where('wl.user_id', $user->id)
                ->pluck('wle.exercise_id')
                ->unique();

            $commonIds = $myExerciseIds->intersect($friendExerciseIds)->values();

            $exercises = DB::table('exercises')
                ->whereIn('id', $commonIds)
                ->orderBy('name')
                ->get(['id', 'name']);

            return response()->json([
                'items' => $exercises->map(fn($e) => [
                    'id' => $e->id,
                    'name' => $e->name,
                ])->values(),
            ]);
        }

        public function exerciseComparison(Request $request, User $user)
        {
            $auth = $request->user();

            $isFriend = $auth->friends()->where('users.id', $user->id)->exists();
            if (!$isFriend && $auth->id !== $user->id) {
                abort(403);
            }

            $validated = $request->validate([
                'exercise_id' => ['required', 'integer', 'exists:exercises,id'],
                'period' => ['nullable', 'in:week,month,year,all'],
            ]);

            $exerciseId = (int) $validated['exercise_id'];
            $period = $validated['period'] ?? 'all';

            $to = now()->endOfDay();
            $from = match ($period) {
                'week' => now()->subDays(6)->startOfDay(),
                'month' => now()->subDays(29)->startOfDay(),
                'year' => now()->subDays(364)->startOfDay(),
                default => null,
            };

            $mine = $this->comparisonSeriesForUser($auth->id, $exerciseId, $from, $to);
            $friend = $this->comparisonSeriesForUser($user->id, $exerciseId, $from, $to);

            $allDays = collect(array_merge(array_keys($mine), array_keys($friend)))
                ->unique()
                ->sort()
                ->values();

            $labels = $allDays->map(fn($d) => \Carbon\Carbon::parse($d)->format('M j'))->values()->all();
            $myValues = $allDays->map(fn($d) => (float) ($mine[$d] ?? null))->values()->all();
            $friendValues = $allDays->map(fn($d) => (float) ($friend[$d] ?? null))->values()->all();

            $exerciseName = DB::table('exercises')->where('id', $exerciseId)->value('name');

            return response()->json([
                'exercise_name' => $exerciseName,
                'labels' => $labels,
                'user' => $myValues,
                'friend' => $friendValues,
                'user_name' => $auth->full_name ?? $auth->name ?? 'You',
                'friend_name' => $user->full_name ?? $user->name ?? 'Friend',
            ]);
        }

        private function comparisonSeriesForUser(int $userId, int $exerciseId, $from = null, $to = null): array
        {
            $q = DB::table('workout_log_sets as s')
                ->join('workout_log_exercises as wle', 'wle.id', '=', 's.workout_log_exercise_id')
                ->join('workout_logs as wl', 'wl.id', '=', 'wle.workout_log_id')
                ->where('wl.user_id', $userId)
                ->where('wle.exercise_id', $exerciseId)
                ->whereNotNull('s.weight_kg')
                ->selectRaw('date(wl.entry_date) as d')
                ->selectRaw('MAX(s.weight_kg) as best_weight');

            if ($from) {
                $q->whereDate('wl.entry_date', '>=', $from->toDateString());
            }
            if ($to) {
                $q->whereDate('wl.entry_date', '<=', $to->toDateString());
            }

            return $q
                ->groupByRaw('date(wl.entry_date)')
                ->orderBy('d')
                ->pluck('best_weight', 'd')
                ->map(fn($v) => (float) $v)
                ->all();
        }
    }