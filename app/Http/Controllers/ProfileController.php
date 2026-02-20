<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Storage;

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
            'gender'        => ['nullable', 'string', 'in:male,female']
        ], [
            'username.regex' => 'Username can contain only letters, numbers, and underscores.',
        ]);

        // Keep Laravel’s `name` in sync with full_name
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


}
