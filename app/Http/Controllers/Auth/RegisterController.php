<?php

namespace App\Http\Controllers\Auth;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\UserMetric;
use App\Models\NutritionGoal;
use App\Models\WeightEntry;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use App\Support\UnitConverter;

class RegisterController extends Controller
{
    // show + store Step 1 (basic info)
     public function showStep1(Request $request)
    {
        return view('auth.register.step1', [
            'data' => $request->session()->get('register.step1', []),
        ]);
    }

    // Step 1 store (hash password immediately + unique checks)
    public function storeStep1(Request $request)
    {
        $validated = $request->validate([
            'full_name' => ['required', 'string', 'min:3', 'max:80'],
            'username'  => ['required', 'string', 'min:3', 'max:30', 'regex:/^[a-zA-Z0-9_]+$/', 'unique:users,username'],
            'email'     => ['required', 'email', 'max:255', 'unique:users,email'],
            'password'  => ['required', 'string', 'min:8', 'confirmed'],
        ], [
            'username.regex' => 'Username can contain only letters, numbers, and underscores.',
        ]);

        $request->session()->put('register.step1', [
            'full_name' => $validated['full_name'],
            'username'  => $validated['username'],
            'email'     => $validated['email'],
            'password_hash' => Hash::make($validated['password']),
        ]);

        return redirect()->route('register.macros');
    }

    // Step 2 show + store (macros + metrics)

    public function showMacros(Request $request)
    {
        $this->requireStep1($request);

        return view('auth.register.step2', [
            'data' => $request->session()->get('register.step2', []),
            'tdee' => $request->session()->get('register.tdee'),
        ]);
    }

    public function storeMacros(Request $request)
    {
        $this->requireStep1($request);

        $unitSystem = UnitConverter::normalize($request->input('unit_system'));
        $isImperial = $unitSystem === UnitConverter::IMPERIAL;

        $validated = $request->validate([
            'unit_system' => ['nullable', Rule::in([UnitConverter::METRIC, UnitConverter::IMPERIAL])],
            'gender'   => ['required', 'in:male,female'],
            'age'      => ['required', 'integer', 'min:13', 'max:90'],
            'height'   => ['required', 'numeric', 'min:'.($isImperial ? 47 : 120), 'max:'.($isImperial ? 91 : 230)],
            'weight'   => ['required', 'numeric', 'min:'.($isImperial ? 77 : 35), 'max:'.($isImperial ? 551 : 250)],
            'activity' => ['required', 'numeric', Rule::in(['1.2', '1.5', '1.65', '1.7', '1.8', '2.0', '2.2'])],
        ]);

        $heightCm = UnitConverter::lengthToCm((float) $validated['height'], $unitSystem);
        $weightKg = UnitConverter::weightToKg((float) $validated['weight'], $unitSystem);

        $bmr = $this->calculateBmr(
            $validated['gender'],
            $weightKg,
            $heightCm,
            (int) $validated['age']
        );

        $tdee = $bmr * (float) $validated['activity'];

        $request->session()->put('register.step2', [
            'gender' => $validated['gender'],
            'age' => (int) $validated['age'],
            'unit_system' => $unitSystem,
            'height_input' => (float) $validated['height'],
            'weight_input' => (float) $validated['weight'],
            'height_cm' => $heightCm,
            'weight_kg' => $weightKg,
            'activity_multiplier' => (float) $validated['activity'],
        ]);

        $request->session()->put('register.bmr', round($bmr, 2));
        $request->session()->put('register.tdee', (int) round($tdee));

        return redirect()->route('register.goal');
    }

    //Step 3 show + store (compute macros + save everything to DB)
    public function showGoal(Request $request)
    {
        $this->requireStep1($request);
        $this->requireTdee($request);

        return view('auth.register.step3', [
            'tdee' => (int) $request->session()->get('register.tdee'),
            'weightKg' => (float) $request->session()->get('register.step2.weight_kg'),
            'data' => $request->session()->get('register.step3', []),
            // optional preview placeholder (we can compute preview later if you want)
            'macros_preview' => $request->session()->get('register.macros_preview'),
        ]);
    }

    public function storeGoal(Request $request)
    {
        $this->requireStep1($request);
        $this->requireTdee($request);

        $validated = $request->validate([
            'goal' => ['required', 'in:bulk,cut,recomp'],
            'bulk_type' => ['nullable', 'in:lean,standard'],
            'cut_type'  => ['nullable', 'in:moderate,aggressive'],
            'fat_percent' => ['nullable', 'numeric', 'min:20', 'max:35'],
            'protein_g_per_kg' => ['nullable', 'numeric', 'min:1.6', 'max:2.7'],
        ]);

        $step1 = $request->session()->get('register.step1');
        $step2 = $request->session()->get('register.step2');
        $bmr   = (float) $request->session()->get('register.bmr');
        $tdee  = (int) $request->session()->get('register.tdee');

        $weightKg = (float) $step2['weight_kg'];
        $fatPercent = isset($validated['fat_percent']) ? (float) $validated['fat_percent'] : 30.0;

        $calories = $this->calculateGoalCalories($tdee, $validated);
        $proteinGPerKg = !empty($validated['protein_g_per_kg'])
            ? (float) $validated['protein_g_per_kg']
            : $this->defaultProteinGPerKg($validated['goal']);

        $proteinG = (int) round($weightKg * $proteinGPerKg);

        $fatCals = $calories * ($fatPercent / 100);
        $fatG = (int) round($fatCals / 9);

        $proteinCals = $proteinG * 4;
        $fatCalsRounded = $fatG * 9;
        $carbCals = max(0, $calories - ($proteinCals + $fatCalsRounded));
        $carbG = (int) round($carbCals / 4);

        $user = null;

        DB::transaction(function () use (&$user, $step1, $step2, $bmr, $tdee, $validated, $calories, $proteinG, $fatG, $carbG, $fatPercent, $proteinGPerKg) {

            $user = User::make([
                'name' => $step1['full_name'],
                'full_name' => $step1['full_name'],
                'username'  => $step1['username'],
                'email'     => $step1['email'],
                'password'  => $step1['password_hash'],
                'gender'    => $step2['gender'] ?? null,
                'unit_system' => UnitConverter::normalize($step2['unit_system'] ?? null),
            ]);
            $user->forceFill(['role' => UserRole::User])->save();

            UserMetric::create([
                'user_id' => $user->id,
                'gender' => $step2['gender'],
                'age' => $step2['age'],
                'height_cm' => $step2['height_cm'],
                'weight_kg' => $step2['weight_kg'],
                'activity_multiplier' => $step2['activity_multiplier'],
                'bmr' => $bmr,
                'tdee' => $tdee,
            ]);

            WeightEntry::create([
                'user_id' => $user->id,
                'recorded_on' => now()->toDateString(),
                'weight_kg' => $step2['weight_kg'],
                'source' => 'registration',
            ]);

            NutritionGoal::create([
                'user_id' => $user->id,
                'goal' => $validated['goal'],
                'calorie_target' => (int) $calories,
                'protein_g' => $proteinG,
                'fat_g' => $fatG,
                'carbs_g' => $carbG,
                'fat_percent' => $fatPercent,
                'protein_g_per_kg' => $proteinGPerKg,
                'bulk_type' => $validated['bulk_type'] ?? null,
                'cut_type' => $validated['cut_type'] ?? null,
                'water_l' => 3.0,
                'creatine_g' => 5.0,
            ]);
        });

        if (!$user) {
            return redirect()->route('register')->withErrors([
                'register' => 'Account could not be created. Please try again.',
            ]);
        }

        Auth::login($user);
        $request->session()->regenerate();
        $request->session()->forget('register');

        $unlocked = app(\App\Services\AchievementService::class)->evaluate($user);
        if (!empty($unlocked)) {
            session()->flash('unlocked', $unlocked);
        }

        return redirect()->route('home')->with('status', 'Account created. Welcome to ProgressLab!')->with('unlocked', $unlocked);
    }

    // Helpers
    // ---------------------------
    private function requireStep1(Request $request): void
    {
        if (!$request->session()->has('register.step1')) {
            // redirect instead of abort
            redirect()->route('register')->send();
            exit;
        }
    }

    private function requireTdee(Request $request): void
    {
        if (!$request->session()->has('register.tdee')) {
            redirect()->route('register.macros')->send();
            exit;
        }
    }

    private function calculateBmr(string $gender, float $weightKg, float $heightCm, int $age): float
    {
        if ($gender === 'male') {
            return (10 * $weightKg) + (6.25 * $heightCm) - (5 * $age) + 5;
        }

        return (10 * $weightKg) + (6.25 * $heightCm) - (5 * $age) - 161;
    }

    private function calculateGoalCalories(int $tdee, array $validated): int
    {
        $goal = $validated['goal'];

        if ($goal === 'recomp') {
            return $tdee;
        }

        if ($goal === 'bulk') {
            // Lean bulk +5% to +10% (default +8)
            // Standard bulk +10% to +20% (default +15)
            $type = $validated['bulk_type'] ?? 'lean';
            $percent = $type === 'standard' ? 15 : 8;

            return (int) round($tdee * (1 + ($percent / 100)));
        }

        // Cut: -10% to -20% (default -15)
        $type = $validated['cut_type'] ?? 'moderate';
        $percent = $type === 'aggressive' ? 20 : 15;

        return (int) round($tdee * (1 - ($percent / 100)));
    }

    private function defaultProteinGPerKg(string $goal): float
    {
        return match ($goal) {
            'cut' => 2.2,
            'bulk' => 1.8,
            default => 1.8, // recomp
        };
    }
}
