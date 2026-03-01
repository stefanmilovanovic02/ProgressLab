<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Achievement;

class AchievementsController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        $rarity = $request->query('rarity', 'all');   // common/uncommon/rare/epic/legendary/all
        $status = $request->query('status', 'all');   // unlocked/locked/all
        $q      = $request->query('q', '');

        $query = Achievement::query();

        if ($rarity !== 'all') {
            $query->where('rarity', $rarity);
        }

        if ($q !== '') {
            $query->where('title', 'like', "%{$q}%")
                  ->orWhere('description', 'like', "%{$q}%");
        }

        // Load achievements + whether user unlocked them
        $achievements = $query
            ->with(['users' => function ($rel) use ($user) {
                $rel->where('users.id', $user->id);
            }])
            ->orderBy('rarity')
            ->orderBy('title')
            ->get();

        // Add a computed flag for blade
        $achievements->each(function ($a) {
            $a->is_unlocked = $a->users->isNotEmpty();
        });

        if ($status === 'unlocked') {
            $achievements = $achievements->where('is_unlocked', true)->values();
        } elseif ($status === 'locked') {
            $achievements = $achievements->where('is_unlocked', false)->values();
        }

        $unlockedCount = $achievements->where('is_unlocked', true)->count();
        $totalCount = $achievements->count();

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