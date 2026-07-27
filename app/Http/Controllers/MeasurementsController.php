<?php

namespace App\Http\Controllers;

use App\Models\BodyMeasurement;
use App\Models\WeightEntry;
use App\Services\AchievementService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class MeasurementsController extends Controller
{
    public function updateGoals(Request $request)
    {
        $validated = $request->validateWithBag('goals', [
            'goal' => ['required', 'in:bulk,cut,recomp'],
            'calorie_target' => ['required', 'integer', 'min:800', 'max:8000'],
            'protein_g' => ['required', 'integer', 'min:0', 'max:500'],
            'carbs_g' => ['required', 'integer', 'min:0', 'max:1200'],
            'fat_g' => ['required', 'integer', 'min:0', 'max:400'],
            'water_l' => ['nullable', 'numeric', 'min:0', 'max:10'],
            'creatine_g' => ['nullable', 'numeric', 'min:0', 'max:20'],
        ]);

        $request->user()->nutritionGoal()->updateOrCreate(
            ['user_id' => $request->user()->id],
            $validated
        );

        $unlocked = app(AchievementService::class)->evaluate($request->user());

        return redirect()
            ->to(route('add-today').'#measurements')
            ->with('measurement_status', 'Nutrition targets updated.')
            ->with('measurement_tab', 'goals')
            ->with('unlocked', $unlocked);
    }

    public function storeBody(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'weight_kg' => ['nullable', 'numeric', 'min:20', 'max:400'],
            'waist_cm' => ['nullable', 'numeric', 'min:30', 'max:250'],
            'arms_cm' => ['nullable', 'numeric', 'min:10', 'max:100'],
            'thighs_cm' => ['nullable', 'numeric', 'min:20', 'max:150'],
            'hips_cm' => ['nullable', 'numeric', 'min:30', 'max:250'],
            'glutes_cm' => ['nullable', 'numeric', 'min:30', 'max:250'],
        ]);

        $validator->after(function ($validator) use ($request) {
            $fields = ['weight_kg', 'waist_cm', 'arms_cm', 'thighs_cm', 'hips_cm', 'glutes_cm'];
            $hasMeasurement = collect($fields)->contains(
                fn (string $field) => $request->filled($field)
            );

            if (! $hasMeasurement) {
                $validator->errors()->add('weight_kg', 'Enter at least one body measurement.');
            }
        });

        $validated = $validator->validateWithBag('body');
        $values = collect($validated)
            ->map(fn ($value) => $value === null || $value === '' ? null : $value)
            ->all();
        $user = $request->user();
        $today = now()->toDateString();

        DB::transaction(function () use ($user, $today, $values) {
            BodyMeasurement::query()->updateOrCreate(
                ['user_id' => $user->id, 'recorded_on' => $today],
                $values
            );

            if (isset($values['weight_kg'])) {
                WeightEntry::query()->updateOrCreate(
                    ['user_id' => $user->id, 'recorded_on' => $today],
                    ['weight_kg' => $values['weight_kg'], 'source' => 'add_today']
                );

                $user->metric()->update(['weight_kg' => $values['weight_kg']]);
            }
        });

        return redirect()
            ->to(route('add-today').'#measurements')
            ->with('measurement_status', 'Today’s body measurements were saved.')
            ->with('measurement_tab', 'body');
    }
}
