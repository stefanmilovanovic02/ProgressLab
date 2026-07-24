<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Exercise;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class ExerciseController extends Controller
{
    private const SCORING_TYPES = [
        'estimated_1rm_absolute' => 'Estimated 1RM (absolute kg)',
        'estimated_1rm_bodyweight' => 'Estimated 1RM / bodyweight',
        'repetitions' => 'Maximum repetitions',
        'assisted_bodyweight' => 'Assisted bodyweight',
        'disabled' => 'Ranking disabled',
    ];

    public function index(Request $request)
    {
        $search = trim((string) $request->query('search'));
        $exercises = Exercise::query()
            ->with('rankStandard')
            ->withCount(['workouts', 'userRanks'])
            ->when($search !== '', fn ($query) => $query->where(function ($query) use ($search) {
                $query->where('name', 'like', "%{$search}%")
                    ->orWhere('muscle_group', 'like', "%{$search}%");
            }))
            ->orderBy('name')
            ->paginate(24)
            ->withQueryString();

        return view('admin.exercises.index', compact('exercises', 'search'));
    }

    public function create()
    {
        return view('admin.exercises.create', [
            'scoringTypes' => self::SCORING_TYPES,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $this->validateExercise($request);

        $exercise = DB::transaction(function () use ($validated) {
            $exercise = Exercise::query()->create([
                'name' => $validated['name'],
                'muscle_group' => $validated['muscle_group'] ?? null,
            ]);
            $exercise->rankStandard()->create($this->standardData($validated));

            return $exercise;
        });

        return redirect()->route('admin.exercises.edit', $exercise)
            ->with('status', 'Exercise created successfully.');
    }

    public function edit(Exercise $exercise)
    {
        $exercise->load('rankStandard');
        $hasHistory = $this->isInUse($exercise);

        return view('admin.exercises.edit', [
            'exercise' => $exercise,
            'scoringTypes' => self::SCORING_TYPES,
            'hasHistory' => $hasHistory,
        ]);
    }

    public function update(Request $request, Exercise $exercise)
    {
        $validated = $this->validateExercise($request, $exercise);

        DB::transaction(function () use ($exercise, $validated) {
            $exercise->update([
                'name' => $validated['name'],
                'muscle_group' => $validated['muscle_group'] ?? null,
            ]);
            $exercise->rankStandard()->updateOrCreate(
                ['exercise_id' => $exercise->id],
                $this->standardData($validated)
            );
        });

        return redirect()->route('admin.exercises.edit', $exercise)
            ->with('status', 'Exercise updated successfully.');
    }

    public function destroy(Exercise $exercise)
    {
        if ($this->isInUse($exercise)) {
            return back()->withErrors([
                'exercise' => 'This exercise is assigned to a workout or has user history and cannot be deleted. Disable its ranking or rename it instead.',
            ]);
        }

        $exercise->delete();

        return redirect()->route('admin.exercises.index')
            ->with('status', 'Exercise deleted successfully.');
    }

    private function validateExercise(Request $request, ?Exercise $exercise = null): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:160', Rule::unique('exercises', 'name')->ignore($exercise)],
            'muscle_group' => ['nullable', 'string', 'max:80'],
            'scoring_type' => ['required', Rule::in(array_keys(self::SCORING_TYPES))],
            'olympian_target' => [
                Rule::requiredIf($request->input('scoring_type') !== 'disabled'),
                'nullable',
                'numeric',
                'gt:0',
                'max:99999',
            ],
            'unit' => ['required', Rule::in(['kg', 'ratio', 'reps', 'none'])],
            'is_active' => ['nullable', 'boolean'],
        ]);
    }

    private function standardData(array $validated): array
    {
        $disabled = $validated['scoring_type'] === 'disabled';

        return [
            'scoring_type' => $validated['scoring_type'],
            'olympian_target' => $disabled ? null : (float) $validated['olympian_target'],
            'unit' => $disabled ? 'none' : $validated['unit'],
            'is_active' => !$disabled && (bool) ($validated['is_active'] ?? false),
        ];
    }

    private function isInUse(Exercise $exercise): bool
    {
        return DB::table('exercise_workout')->where('exercise_id', $exercise->id)->exists()
            || DB::table('workout_log_exercises')->where('exercise_id', $exercise->id)->exists()
            || DB::table('user_exercise_ranks')->where('exercise_id', $exercise->id)->exists();
    }
}
