<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\UserMetric;
use App\Models\NutritionGoal;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;

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

    $validated = $request->validate([
        'gender'   => ['required', 'in:male,female'],
        'age'      => ['required', 'integer', 'min:13', 'max:90'],
        'height'   => ['required', 'integer', 'min:120', 'max:230'], // cm
        'weight'   => ['required', 'numeric', 'min:35', 'max:250'],  // kg
        'activity' => ['required', 'numeric', 'min:1.2', 'max:2.2'],
    ]);

    $bmr = $this->calculateBmr(
        $validated['gender'],
        (float) $validated['weight'],
        (float) $validated['height'],
        (int) $validated['age']
    );

    $tdee = $bmr * (float) $validated['activity'];

    $request->session()->put('register.step2', [
        'gender' => $validated['gender'],
        'age' => (int) $validated['age'],
        'height_cm' => (int) $validated['height'],
        'weight_kg' => (float) $validated['weight'],
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

    DB::transaction(function () use ($step1, $step2, $bmr, $tdee, $validated, $calories, $proteinG, $fatG, $carbG, $fatPercent, $proteinGPerKg) {

        $user = User::create([
            'name' => $step1['full_name'],
            'full_name' => $step1['full_name'],
            'username'  => $step1['username'],
            'email'     => $step1['email'],
            'password'  => $step1['password_hash'],
            'gender'    => $step2['gender'] ?? null,
        ]);

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

    // Clear the registration session data
    $request->session()->forget('register');

    return redirect()->route('login')->with('status', 'Account created. Please sign in.');
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
