<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use App\Models\WeightEntry;
use App\Support\UnitConverter;
use Illuminate\Validation\ValidationException;

class ProfileController extends Controller
{
    public function show(Request $request)
    {
        $user = $request->user();
        $unitSystem = UnitConverter::normalize($request->input('unit_system', $user->unit_system));

        $today = Carbon::today();

        $loginDates = DB::table('login_logs')
            ->where('user_id', $user->id)
            ->whereDate('login_date', '<=', $today->toDateString())
            ->orderBy('login_date', 'desc')
            ->limit(400)
            ->pluck('login_date');

        $loginSet = [];
        foreach ($loginDates as $d) {
            $loginSet[Carbon::parse($d)->toDateString()] = true;
        }

        $loginStreak = 0;
        $cursor = $today->copy();

        while (isset($loginSet[$cursor->toDateString()])) {
            $loginStreak++;
            $cursor->subDay();
        }

        return view('profile.show', [
            'loginStreak' => $loginStreak,
        ]);
    }

    public function update(Request $request)
    {
        $user = $request->user();
        $unitSystem = UnitConverter::normalize($request->input('unit_system', $user->unit_system));
        $imperial = $unitSystem === UnitConverter::IMPERIAL;

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
            'gender'        => ['nullable', 'in:male,female'],
            'unit_system'   => ['nullable', Rule::in([UnitConverter::METRIC, UnitConverter::IMPERIAL])],

            'height_cm' => ['nullable', 'numeric', 'min:'.($imperial ? 47 : 120), 'max:'.($imperial ? 91 : 230)],
            'weight_kg' => ['nullable', 'numeric', 'min:'.($imperial ? 77 : 35), 'max:'.($imperial ? 551 : 250)],
            'activity_multiplier' => ['nullable', 'numeric', 'min:1.2', 'max:2.2'],

            'goal' => ['nullable', 'in:bulk,cut,recomp'],
            'calorie_target' => ['nullable', 'integer', 'min:800', 'max:8000'],
            'protein_g' => ['nullable', 'integer', 'min:0', 'max:500'],
            'fat_g'     => ['nullable', 'integer', 'min:0', 'max:400'],
            'carbs_g'   => ['nullable', 'integer', 'min:0', 'max:1200'],
            'water_l'   => ['nullable', 'numeric', 'min:0', 'max:10'],
            'creatine_g'=> ['nullable', 'numeric', 'min:0', 'max:20'],
            'profile_quote' => ['nullable', 'string', 'max:180'],
            'social_instagram' => ['nullable', 'url:http,https', 'max:255'],
            'social_tiktok' => ['nullable', 'url:http,https', 'max:255'],
            'social_snapchat' => ['nullable', 'url:http,https', 'max:255'],
            'social_linkedin' => ['nullable', 'url:http,https', 'max:255'],
            'profile_accent_color' => ['nullable', 'regex:/^#[0-9a-fA-F]{6}$/'],
            'profile_accent_opacity' => ['nullable', 'integer', 'between:0,100'],
            'profile_secondary_color' => ['nullable', 'regex:/^#[0-9a-fA-F]{6}$/'],
            'profile_secondary_opacity' => ['nullable', 'integer', 'between:0,100'],
            'profile_surface_color' => ['nullable', 'regex:/^#[0-9a-fA-F]{6}$/'],
            'profile_surface_opacity' => ['nullable', 'integer', 'between:0,100'],
            'profile_text_color' => ['nullable', 'regex:/^#[0-9a-fA-F]{6}$/'],
            'profile_text_opacity' => ['nullable', 'integer', 'between:0,100'],
        ], [
            'username.regex' => 'Username can contain only letters, numbers, and underscores.',
        ]);

        $heightCm = isset($validated['height_cm'])
            ? UnitConverter::lengthToCm((float) $validated['height_cm'], $unitSystem)
            : null;
        $weightKg = isset($validated['weight_kg'])
            ? UnitConverter::weightToKg((float) $validated['weight_kg'], $unitSystem)
            : null;

        $customizationFields = [
            'profile_quote', 'social_instagram', 'social_tiktok', 'social_snapchat', 'social_linkedin',
            'profile_accent_color', 'profile_accent_opacity',
            'profile_secondary_color', 'profile_secondary_opacity',
            'profile_surface_color', 'profile_surface_opacity',
            'profile_text_color', 'profile_text_opacity',
        ];
        if (! $user->canCustomizeSocialProfile() && collect($customizationFields)->contains(fn ($field) => $request->has($field))) {
            abort(403, 'Profile customization requires ProgressLab+.');
        }

        if ($heightCm !== null && ($heightCm < 120 || $heightCm > 230)) {
            throw ValidationException::withMessages(['height_cm' => 'Enter a height between 120–230 cm or 47–91 in.']);
        }
        if ($weightKg !== null && ($weightKg < 35 || $weightKg > 250)) {
            throw ValidationException::withMessages(['weight_kg' => 'Enter a weight between 35–250 kg or 77–551 lb.']);
        }

        DB::transaction(function () use ($user, $validated, $unitSystem, $heightCm, $weightKg) {
            $user->fill([
                'name' => $validated['full_name'],
                'full_name' => $validated['full_name'],
                'username' => $validated['username'],
                'email' => $validated['email'],
                'date_of_birth' => $validated['date_of_birth'] ?? null,
                'location' => $validated['location'] ?? null,
                'gender' => $validated['gender'] ?? null,
                'unit_system' => $unitSystem,
            ]);
            if ($user->canCustomizeSocialProfile()) {
                $user->fill(collect($validated)->only([
                    'profile_quote',
                    'social_instagram',
                    'social_tiktok',
                    'social_snapchat',
                    'social_linkedin',
                    'profile_accent_color',
                    'profile_accent_opacity',
                    'profile_secondary_color',
                    'profile_secondary_opacity',
                    'profile_surface_color',
                    'profile_surface_opacity',
                    'profile_text_color',
                    'profile_text_opacity',
                ])->all());
            }
            $user->save();

            $metric = $user->metric()->firstOrCreate(['user_id' => $user->id]);
            $metric->fill([
                'height_cm' => $heightCm ?? $metric->height_cm,
                'weight_kg' => $weightKg ?? $metric->weight_kg,
                'activity_multiplier' => $validated['activity_multiplier'] ?? $metric->activity_multiplier,
            ]);
            $metric->save();

            if (array_key_exists('weight_kg', $validated) && $validated['weight_kg'] !== null) {
                WeightEntry::query()->updateOrCreate(
                    ['user_id' => $user->id, 'recorded_on' => now()->toDateString()],
                    ['weight_kg' => $weightKg, 'source' => 'profile']
                );
            }

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

        $unlocked = app(\App\Services\AchievementService::class)->evaluate($request->user());

        return redirect()
            ->route('profile.show')
            ->with('status', 'Profile updated successfully.')
            ->with('unlocked', $unlocked);
    }

    public function updatePhoto(Request $request)
    {
        $user = $request->user();

        $request->validate([
            'avatar' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:4096'],
        ]);

        $file = $request->file('avatar');
        $path = $file->store('avatars', 'public');

        if ($user->avatar_path && str_starts_with($user->avatar_path, 'storage/')) {
            $old = str_replace('storage/', '', $user->avatar_path);
            Storage::disk('public')->delete($old);
        }

        $user->avatar_path = 'storage/' . $path;
        $user->save();

        $unlocked = app(\App\Services\AchievementService::class)->evaluate($request->user());

        if ($request->expectsJson()) {
            return response()->json(['ok' => true, 'avatar_url' => $user->avatar_url]);
        }

        return redirect()
            ->route('profile.show')
            ->with('status', 'Profile photo updated.')
            ->with('unlocked', $unlocked);
    }

    public function updateCover(Request $request)
    {
        $user = $request->user();
        abort_unless($user->canCustomizeSocialProfile(), 403, 'Profile customization requires ProgressLab+.');

        $request->validate([
            'cover' => ['required', 'file', 'mimes:jpg,jpeg,png,webp,gif,mp4,webm', 'max:25600'],
        ]);

        $file = $request->file('cover');
        $isVideo = in_array(strtolower((string) $file->getClientOriginalExtension()), ['mp4', 'webm'], true);

        if ($isVideo) {
            $path = $file->store('profile-background-videos', 'public');
            if ($user->profile_background_video_path && str_starts_with($user->profile_background_video_path, 'storage/')) {
                Storage::disk('public')->delete(str_replace('storage/', '', $user->profile_background_video_path));
            }
            $user->profile_background_video_path = 'storage/' . $path;
        } else {
            $path = $file->store('covers', 'public');
            if ($user->cover_path && str_starts_with($user->cover_path, 'storage/')) {
                Storage::disk('public')->delete(str_replace('storage/', '', $user->cover_path));
            }
            if ($user->profile_background_video_path && str_starts_with($user->profile_background_video_path, 'storage/')) {
                Storage::disk('public')->delete(str_replace('storage/', '', $user->profile_background_video_path));
            }
            $user->cover_path = 'storage/' . $path;
            $user->profile_background_video_path = null;
        }
        $user->save();

        $unlocked = app(\App\Services\AchievementService::class)->evaluate($request->user());

        if ($request->expectsJson()) {
            return response()->json([
                'ok' => true,
                'cover_url' => $user->cover_url,
                'background_video_url' => $user->profile_background_video_url,
            ]);
        }

        return redirect()
            ->route('profile.show')
            ->with('status', 'Cover image updated.')
            ->with('unlocked', $unlocked);
    }

    public function updateShowcase(Request $request)
    {
        $user = $request->user();
        abort_unless($user->canCustomizeSocialProfile(), 403, 'Profile customization requires ProgressLab+.');

        $request->validate([
            'showcase' => ['required', 'image', 'mimes:jpg,jpeg,png,webp,gif', 'max:10240'],
        ]);

        $path = $request->file('showcase')->store('profile-showcases', 'public');
        if ($user->profile_showcase_path && str_starts_with($user->profile_showcase_path, 'storage/')) {
            Storage::disk('public')->delete(str_replace('storage/', '', $user->profile_showcase_path));
        }

        $user->profile_showcase_path = 'storage/' . $path;
        $user->save();

        if ($request->expectsJson()) {
            return response()->json(['ok' => true, 'showcase_url' => $user->profile_showcase_url]);
        }

        return redirect()->route('profile.show')->with('status', 'Profile showcase updated.');
    }

    public function updateTheme(Request $request)
    {
        $user = $request->user();
        abort_unless($user->canCustomizeSocialProfile(), 403, 'Profile customization requires ProgressLab+.');

        $validated = $request->validate([
            'profile_accent_color' => ['required', 'regex:/^#[0-9a-fA-F]{6}$/'],
            'profile_accent_opacity' => ['required', 'integer', 'between:0,100'],
            'profile_secondary_color' => ['required', 'regex:/^#[0-9a-fA-F]{6}$/'],
            'profile_secondary_opacity' => ['required', 'integer', 'between:0,100'],
            'profile_surface_color' => ['required', 'regex:/^#[0-9a-fA-F]{6}$/'],
            'profile_surface_opacity' => ['required', 'integer', 'between:0,100'],
            'profile_text_color' => ['required', 'regex:/^#[0-9a-fA-F]{6}$/'],
            'profile_text_opacity' => ['required', 'integer', 'between:0,100'],
        ]);

        $user->update($validated);

        return response()->json(['ok' => true]);
    }

    public function destroy(Request $request)
    {
        $user = $request->user();

        $request->validate([
            'password' => ['required']
        ]);

        if (!\Hash::check($request->password, $user->password)) {
            return back()->withErrors([
                'password' => 'Password is incorrect.'
            ]);
        }

        $progressPhotoPaths = $user->progressPhotoSets()
            ->get(['front_path', 'side_path', 'back_path'])
            ->flatMap(fn ($photoSet) => [
                $photoSet->front_path,
                $photoSet->side_path,
                $photoSet->back_path,
            ])
            ->filter()
            ->values()
            ->all();

        Auth::logout();

        $user->delete();
        Storage::disk('local')->delete($progressPhotoPaths);

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/')->with('status', 'Account deleted successfully.');
    }
}
