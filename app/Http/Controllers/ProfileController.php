<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class ProfileController extends Controller
{
    public function show()
    {
        return view('profile.show');
    }

     public function update(Request $request)
{
    $user = $request->user();

    $validated = $request->validate([
        // Personal info
        'full_name' => ['required', 'string', 'min:3', 'max:80'],
        'username'  => [
            'required', 'string', 'min:3', 'max:30',
            'regex:/^[a-zA-Z0-9_]+$/',
            Rule::unique('users', 'username')->ignore($user->id),
        ],
        'email'     => [
            'required', 'email', 'max:255',
            Rule::unique('users', 'email')->ignore($user->id),
        ],
        'date_of_birth' => ['nullable', 'date', 'before:today'],
        'location'      => ['nullable', 'string', 'max:80'],
        'gender'        => ['nullable', 'in:male,female'],

        // Fitness info (metrics)
        'height_cm' => ['nullable', 'integer', 'min:120', 'max:230'],
        'weight_kg' => ['nullable', 'numeric', 'min:35', 'max:250'],
        'activity_multiplier' => ['nullable', 'numeric', 'min:1.2', 'max:2.2'],

        // Fitness info (goal + macros)
        'goal' => ['nullable', 'in:bulk,cut,recomp'],
        'calorie_target' => ['nullable', 'integer', 'min:800', 'max:8000'],
        'protein_g' => ['nullable', 'integer', 'min:0', 'max:500'],
        'fat_g'     => ['nullable', 'integer', 'min:0', 'max:400'],
        'carbs_g'   => ['nullable', 'integer', 'min:0', 'max:1200'],
        'water_l'   => ['nullable', 'numeric', 'min:0', 'max:10'],
        'creatine_g'=> ['nullable', 'numeric', 'min:0', 'max:20'],
    ], [
        'username.regex' => 'Username can contain only letters, numbers, and underscores.',
    ]);

    DB::transaction(function () use ($user, $validated) {

        $user->fill([
            'name' => $validated['full_name'],
            'full_name' => $validated['full_name'],
            'username' => $validated['username'],
            'email' => $validated['email'],
            'date_of_birth' => $validated['date_of_birth'] ?? null,
            'location' => $validated['location'] ?? null,
            'gender' => $validated['gender'] ?? null,
        ]);
        $user->save();

        // Create metric row if missing
        $metric = $user->metric()->firstOrCreate(['user_id' => $user->id]);
        $metric->fill([
            'height_cm' => $validated['height_cm'] ?? $metric->height_cm,
            'weight_kg' => $validated['weight_kg'] ?? $metric->weight_kg,
            'activity_multiplier' => $validated['activity_multiplier'] ?? $metric->activity_multiplier,
        ]);
        $metric->save();

        // Create goal row if missing
        $goal = $user->nutritionGoal()->firstOrCreate(['user_id' => $user->id]);
        $goal->fill([
            'goal' => $validated['goal'] ?? $goal->goal,
            'calorie_target' => $validated['calorie_target'] ?? $goal->calorie_target,
            'protein_g' => $validated['protein_g'] ?? $goal->protein_g,
            'fat_g' => $validated['fat_g'] ?? $goal->fat_g,
            'carbs_g' => $validated['carbs_g'] ?? $goal->carbs_g,
            'water_l' => $validated['water_l'] ?? $goal->water_l,
            'creatine_g' => $validated['creatine_g'] ?? $goal->creatine_g,
        ]);
        $goal->save();
    });

    return redirect()->route('profile.show')->with('status', 'Profile updated successfully.');
}


    public function updatePhoto(Request $request)
{
    $user = $request->user();

    $request->validate([
        'avatar' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:4096'],
    ]);

    $file = $request->file('avatar');

    // Save into storage/app/public/avatars
    $path = $file->store('avatars', 'public');

    // Optionally delete old file (if it was stored locally)
    if ($user->avatar_path && str_starts_with($user->avatar_path, 'storage/')) {
        $old = str_replace('storage/', '', $user->avatar_path);
        Storage::disk('public')->delete($old);
    }

    $user->avatar_path = 'storage/' . $path;
    $user->save();

    return redirect()->route('profile.show')->with('status', 'Profile photo updated.');
}

public function updateCover(Request $request)
{
    $user = $request->user();

    $request->validate([
        'cover' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:6144'],
    ]);

    $file = $request->file('cover');

    // Save into storage/app/public/covers
    $path = $file->store('covers', 'public');

    if ($user->cover_path && str_starts_with($user->cover_path, 'storage/')) {
        $old = str_replace('storage/', '', $user->cover_path);
        Storage::disk('public')->delete($old);
    }

    $user->cover_path = 'storage/' . $path;
    $user->save();

    return redirect()->route('profile.show')->with('status', 'Cover image updated.');
}

public function destroy(Request $request)
{
    $user = $request->user();

    // Optional: require password confirmation
    $request->validate([
        'password' => ['required']
    ]);

    if (!\Hash::check($request->password, $user->password)) {
        return back()->withErrors([
            'password' => 'Password is incorrect.'
        ]);
    }

    Auth::logout();

    $user->delete(); // This will also delete related rows if you used cascadeOnDelete()

    $request->session()->invalidate();
    $request->session()->regenerateToken();

    return redirect('/')->with('status', 'Account deleted successfully.');
}

}
