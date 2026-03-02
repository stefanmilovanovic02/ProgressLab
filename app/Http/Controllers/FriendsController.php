<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Friend;
use App\Models\WorkoutLog;
use App\Models\NutritionEntry;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class FriendsController extends Controller
{
  public function index(Request $request)
  {
    $user = $request->user();

    // Incoming requests (someone requested you)
    $incoming = Friend::query()
      ->where('friend_id', $user->id)
      ->where('status', 'pending')
      ->with(['requester' => function($q){
        $q->select('id','name','username','avatar_path');
      }])
      ->orderByDesc('created_at')
      ->get();

    // Outgoing pending (you requested them)
    $outgoingIds = Friend::query()
      ->where('user_id', $user->id)
      ->where('status', 'pending')
      ->pluck('friend_id')
      ->toArray();

    // Accepted friends (both directions)
    $friendIds = Friend::query()
      ->where('status', 'accepted')
      ->whereNotNull('accepted_at')
      ->where(function($q) use ($user){
        $q->where('user_id', $user->id)->orWhere('friend_id', $user->id);
      })
      ->get(['user_id','friend_id'])
      ->flatMap(function($row) use ($user){
        return $row->user_id == $user->id ? [$row->friend_id] : [$row->user_id];
      })
      ->unique()
      ->values();

    $friends = User::query()
      ->whereIn('id', $friendIds)
      ->orderBy('name')
      ->get(['id','name','username','email','avatar_path','cover_path','created_at']);

    $friends->transform(function($f){
      $f->login_streak = $this->loginStreak($f->id);
      $f->workout_streak = $this->workoutStreak($f->id);
      $f->water_streak = $this->macroStreak($f->id, 'water_ml');
      $f->status = $this->statusLabel($f->id);
      $f->last_active_text = $this->lastActiveText($f->id);
      return $f;
    });

    $onlineCount = $friends->filter(fn($f) => in_array($f->status, ['Online','Recently Active']))->count();

    return view('friends.index', compact('friends','onlineCount','incoming','outgoingIds'));
  }

  // SEARCH USERS TO ADD (shows avatar, name, username)
  public function search(Request $request)
  {
    $user = $request->user();
    $q = trim((string) $request->query('q',''));

    if (mb_strlen($q) < 2) {
      return response()->json(['items' => []]);
    }

    // exclude anyone with any relation (pending or accepted) both ways
    $relatedIds = Friend::query()
      ->where(function($qq) use ($user){
        $qq->where('user_id', $user->id)->orWhere('friend_id', $user->id);
      })
      ->get(['user_id','friend_id'])
      ->flatMap(function($row) use ($user){
        return $row->user_id == $user->id ? [$row->friend_id] : [$row->user_id];
      })
      ->unique()
      ->values()
      ->all();

    $items = User::query()
      ->where('id','!=',$user->id)
      ->whereNotIn('id', $relatedIds)
      ->where(function($qq) use ($q){
        $qq->where('name','like',"%{$q}%")
           ->orWhere('email','like',"%{$q}%")
           ->orWhere('username','like',"%{$q}%");
      })
      ->orderBy('name')
      ->limit(12)
      ->get(['id','name','username','email','avatar_path']);

    // add avatar_url
    $items->transform(function($u){
      $u->avatar_url = $u->avatar_path ? asset('storage/'.$u->avatar_path) : null;
      return $u;
    });

    return response()->json(['items' => $items]);
  }

  // SEND REQUEST (pending)
  public function request(Request $request)
  {
    $user = $request->user();

    $validated = $request->validate([
      'friend_id' => ['required','integer','exists:users,id'],
    ]);

    $friendId = (int) $validated['friend_id'];
    if ($friendId === (int) $user->id) {
      return response()->json(['ok'=>false,'message'=>'Invalid'], 422);
    }

    // prevent duplicates both directions
    $exists = Friend::query()
      ->where(function($q) use ($user, $friendId){
        $q->where('user_id',$user->id)->where('friend_id',$friendId);
      })
      ->orWhere(function($q) use ($user, $friendId){
        $q->where('user_id',$friendId)->where('friend_id',$user->id);
      })
      ->exists();

    if ($exists) {
      return response()->json(['ok'=>false,'message'=>'Already requested/connected'], 409);
    }

    Friend::create([
      'user_id' => $user->id,      // requester
      'friend_id' => $friendId,    // receiver
      'status' => 'pending',
      'accepted_at' => null,
    ]);

    return response()->json(['ok'=>true]);
  }

  // ACCEPT REQUEST (receiver accepts requester)
  public function accept(Request $request)
  {
    $user = $request->user();

    $validated = $request->validate([
      'requester_id' => ['required','integer','exists:users,id'],
    ]);

    $requesterId = (int) $validated['requester_id'];

    DB::transaction(function() use ($user, $requesterId) {
      // pending row: requester -> you
      $row = Friend::query()
        ->where('user_id', $requesterId)
        ->where('friend_id', $user->id)
        ->where('status', 'pending')
        ->lockForUpdate()
        ->firstOrFail();

      $row->update([
        'status' => 'accepted',
        'accepted_at' => now(),
      ]);

      // reciprocal accepted row: you -> requester
      Friend::updateOrCreate(
        ['user_id' => $user->id, 'friend_id' => $requesterId],
        ['status' => 'accepted', 'accepted_at' => $row->accepted_at]
      );
    });

    return response()->json(['ok'=>true]);
  }

  // DECLINE REQUEST
  public function decline(Request $request)
  {
    $user = $request->user();

    $validated = $request->validate([
      'requester_id' => ['required','integer','exists:users,id'],
    ]);

    Friend::query()
      ->where('user_id', (int)$validated['requester_id'])
      ->where('friend_id', $user->id)
      ->where('status', 'pending')
      ->delete();

    return response()->json(['ok'=>true]);
  }

  // MODAL SUMMARY DATA
  public function summary(Request $request, User $user)
  {
    $viewer = $request->user();

    $isFriend = Friend::query()
      ->where('user_id', $viewer->id)
      ->where('friend_id', $user->id)
      ->where('status','accepted')
      ->exists();

    if (!$isFriend) return response()->json(['message'=>'Not allowed'], 403);

    $monthStart = Carbon::now()->startOfMonth()->toDateString();

    $workoutsLogged = WorkoutLog::where('user_id', $user->id)->count();

    $daysThisMonth = NutritionEntry::where('user_id', $user->id)
      ->whereDate('entry_date', '>=', $monthStart)
      ->where(function($q){
        $q->where('calories','>',0)
          ->orWhere('protein_g','>',0)
          ->orWhere('carbs_g','>',0)
          ->orWhere('fat_g','>',0)
          ->orWhere('creatine_g','>',0)
          ->orWhere('water_ml','>',0);
      })
      ->distinct(DB::raw("date(entry_date)"))
      ->count(DB::raw("date(entry_date)"));

    $friendsCount = Friend::where('user_id', $user->id)->where('status','accepted')->count();

    $loginStreak = $this->loginStreak($user->id);
    $workoutStreak = $this->workoutStreak($user->id);
    $waterStreak = $this->macroStreak($user->id, 'water_ml');

    $recentAch = DB::table('user_achievements as ua')
      ->join('achievements as a', 'a.id', '=', 'ua.achievement_id')
      ->where('ua.user_id', $user->id)
      ->whereNotNull('ua.unlocked_at')
      ->orderByDesc('ua.unlocked_at')
      ->limit(3)
      ->get(['a.id','a.title','a.icon','a.rarity','ua.unlocked_at']);

    return response()->json([
      'user' => [
        'id' => $user->id,
        'name' => $user->name,
        'username' => $user->username,
        'avatar' => $user->avatar_path ? asset('storage/'.$user->avatar_path) : null,
        'cover'  => $user->cover_path ? asset('storage/'.$user->cover_path) : null,
        'joined' => Carbon::parse($user->created_at)->format('F j'),
        'status' => $this->statusLabel($user->id),
        'last_active' => $this->lastActiveText($user->id),
      ],
      'stats' => [
        'workouts_logged' => $workoutsLogged,
        'days_this_month' => $daysThisMonth,
        'friends' => $friendsCount,
        'joined' => Carbon::parse($user->created_at)->format('F j'),
      ],
      'streaks' => [
        'login' => $loginStreak,
        'workout' => $workoutStreak,
        'water' => $waterStreak,
      ],
      'recent_achievements' => $recentAch,
    ]);
  }

  // ---------- helpers ----------
  private function loginStreak(int $userId): int
  {
    $dates = DB::table('login_logs')
      ->where('user_id', $userId)
      ->orderBy('login_date','desc')
      ->pluck(DB::raw("date(login_date)"))
      ->map(fn($d) => Carbon::parse($d)->toDateString())
      ->values();

    if ($dates->isEmpty()) return 0;

    $streak = 0;
    $cursor = Carbon::today();

    foreach ($dates as $d) {
      if ($d === $cursor->toDateString()) { $streak++; $cursor->subDay(); continue; }
      if ($d < $cursor->toDateString()) break;
    }
    return $streak;
  }

  private function workoutStreak(int $userId): int
  {
    $dates = WorkoutLog::where('user_id', $userId)
      ->orderBy('entry_date','desc')
      ->pluck('entry_date')
      ->map(fn($d) => Carbon::parse($d)->toDateString())
      ->unique()
      ->values();

    if ($dates->isEmpty()) return 0;

    $streak = 0;
    $cursor = Carbon::today();

    foreach ($dates as $d) {
      if ($d === $cursor->toDateString()) { $streak++; $cursor->subDay(); continue; }
      if ($d < $cursor->toDateString()) break;
    }
    return $streak;
  }

  private function macroStreak(int $userId, string $field): int
  {
    $dates = NutritionEntry::where('user_id', $userId)
      ->where($field, '>', 0)
      ->orderBy('entry_date','desc')
      ->pluck('entry_date')
      ->map(fn($d) => Carbon::parse($d)->toDateString())
      ->unique()
      ->values();

    if ($dates->isEmpty()) return 0;

    $streak = 0;
    $cursor = Carbon::today();

    foreach ($dates as $d) {
      if ($d === $cursor->toDateString()) { $streak++; $cursor->subDay(); continue; }
      if ($d < $cursor->toDateString()) break;
    }
    return $streak;
  }

  private function statusLabel(int $userId): string
  {
    $last = DB::table('login_logs')
      ->where('user_id', $userId)
      ->orderBy('login_date','desc')
      ->value(DB::raw("date(login_date)"));

    if (!$last) return 'Offline';

    $lastDate = Carbon::parse($last)->toDateString();
    if ($lastDate === Carbon::today()->toDateString()) return 'Online';
    if ($lastDate === Carbon::yesterday()->toDateString()) return 'Recently Active';
    return 'Offline';
  }

  private function lastActiveText(int $userId): string
  {
    $last = DB::table('login_logs')
      ->where('user_id', $userId)
      ->orderBy('login_date','desc')
      ->value('login_date');

    if (!$last) return 'Never';

    $dt = Carbon::parse($last);
    if ($dt->isToday()) return 'Just now';
    if ($dt->isYesterday()) return 'Yesterday';
    return $dt->diffForHumans();
  }
}