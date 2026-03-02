<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Achievement;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class AchievementsController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        $rarity = $request->query('rarity', 'all');   // common/uncommon/rare/epic/legendary/all
        $status = $request->query('status', 'all');   // unlocked/locked/all
        $q      = trim($request->query('q', ''));

        $totalUsers = User::count();

        // rarity rank (common -> legendary)
        $rarityRankSql = "CASE achievements.rarity
            WHEN 'common' THEN 1
            WHEN 'uncommon' THEN 2
            WHEN 'rare' THEN 3
            WHEN 'epic' THEN 4
            WHEN 'legendary' THEN 5
            ELSE 99 END";

        $query = Achievement::query();

        if ($rarity !== 'all') {
            $query->where('achievements.rarity', $rarity);
        }

        if ($q !== '') {
            // IMPORTANT: group OR conditions so rarity filter still applies
            $query->where(function ($sub) use ($q) {
                $sub->where('achievements.title', 'like', "%{$q}%")
                    ->orWhere('achievements.description', 'like', "%{$q}%");
            });
        }

        // Join:
        // - ua_me: "did current user unlock?"
        // - ua_all: "how many users unlocked?"
        $rows = $query
            ->leftJoin('user_achievements as ua_me', function ($join) use ($user) {
                $join->on('ua_me.achievement_id', '=', 'achievements.id')
                    ->where('ua_me.user_id', '=', $user->id);
            })
            ->leftJoin(DB::raw('(
                SELECT achievement_id, COUNT(DISTINCT user_id) as unlocked_users
                FROM user_achievements
                GROUP BY achievement_id
            ) ua_all'), function ($join) {
                $join->on('ua_all.achievement_id', '=', 'achievements.id');
            })
            ->select([
                'achievements.*',
                DB::raw('CASE WHEN ua_me.id IS NULL THEN 0 ELSE 1 END as unlocked'),
                DB::raw('ua_me.unlocked_at as unlocked_at'),
                DB::raw('COALESCE(ua_all.unlocked_users, 0) as unlocked_users'),
            ])
            ->orderByRaw($rarityRankSql . ' asc')
            ->orderBy('achievements.sort_order')
            ->orderBy('achievements.title')
            ->get();

        // Convert to the array format your blade expects
        $achievements = $rows->map(function ($a) use ($totalUsers) {
            $percent = $totalUsers > 0 ? round(($a->unlocked_users / $totalUsers) * 100, 1) : 0;

            return [
                'id' => $a->id,
                'title' => $a->title,
                'desc' => $a->description,
                'category' => $a->category,
                'rarity' => $a->rarity,
                'icon' => $a->icon ?? '🏆',

                // UI additions
                'image_path' => $a->image_path ? asset($a->image_path) : asset('images/achievements/default.png'),
                'category_icon' => $a->category_icon ?? '🏆',

                // unlock data
                'unlocked' => (bool) $a->unlocked,
                'unlocked_at' => $a->unlocked_at,
                'unlocked_users' => (int) $a->unlocked_users,
                'percent' => $percent,
            ];
        });

        if ($status === 'unlocked') {
            $achievements = $achievements->where('unlocked', true)->values();
        } elseif ($status === 'locked') {
            $achievements = $achievements->where('unlocked', false)->values();
        }

        $unlockedCount = $achievements->where('unlocked', true)->count();
        $totalCount    = $achievements->count();

        return view('achievements.index', compact(
            'achievements',
            'rarity',
            'status',
            'q',
            'unlockedCount',
            'totalCount'
        ));
    }
}