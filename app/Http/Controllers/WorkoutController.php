<?php

namespace App\Http\Controllers;

use App\Models\Workout;
use App\Models\Exercise;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class WorkoutController extends Controller
{
  public function index(Request $request)
  {
    $workouts = Workout::query()
      ->where('user_id', $request->user()->id)
      ->with(['exercises:id,name,muscle_group'])
      ->latest()
      ->get();

    return view('workouts.index', compact('workouts'));
  }

  // AJAX search for exercises
  public function searchExercises(Request $request)
  {
    $q = trim((string) $request->query('q', ''));

    if (mb_strlen($q) < 1) {
      return response()->json([]);
    }

    $results = Exercise::query()
      ->where('name', 'like', '%' . $q . '%')
      ->orderBy('name')
      ->limit(8)
      ->get(['id','name','muscle_group','image_path']);

    return response()->json($results);
  }

  // Create workout + attach exercises
  public function store(Request $request)
  {
    $validated = $request->validate([
      'name' => ['required','string','min:2','max:60'],
      'exercise_ids' => ['required','array','min:1'],
      'exercise_ids.*' => ['integer','exists:exercises,id'],
    ]);

    $userId = $request->user()->id;

    DB::transaction(function () use ($validated, $userId) {
      $workout = Workout::create([
        'user_id' => $userId,
        'name' => $validated['name'],
      ]);

      // attach with sort order
      $attach = [];
      foreach (array_values(array_unique($validated['exercise_ids'])) as $i => $exerciseId) {
        $attach[$exerciseId] = ['sort_order' => $i];
      }
      $workout->exercises()->attach($attach);
    });

    return redirect()->route('workouts.index')->with('status', 'Workout created.');
  }

  // optional delete (for the trash icon later)
  public function destroy(Request $request, Workout $workout)
  {
    abort_unless($workout->user_id === $request->user()->id, 403);
    $workout->delete();

    return back()->with('status', 'Workout deleted.');
  }

  public function editData(Workout $workout)
{
    abort_unless($workout->user_id === auth()->id(), 403);

    $workout->load(['exercises:id,name,muscle_group']);

    return response()->json([
        'id' => $workout->id,
        'name' => $workout->name,
        'exercises' => $workout->exercises->map(fn($e) => [
            'id' => $e->id,
            'name' => $e->name,
            'muscle_group' => $e->muscle_group,
        ])->values(),
    ]);
}

public function update(\Illuminate\Http\Request $request, Workout $workout)
{
    abort_unless($workout->user_id === $request->user()->id, 403);

    $validated = $request->validate([
        'name' => ['required','string','min:2','max:60'],
        'exercise_ids' => ['required','array','min:1'],
        'exercise_ids.*' => ['integer','exists:exercises,id'],
    ]);

    DB::transaction(function () use ($workout, $validated) {
        $workout->update(['name' => $validated['name']]);

        // sync exercises with ordering
        $unique = array_values(array_unique($validated['exercise_ids']));
        $sync = [];
        foreach ($unique as $i => $exerciseId) {
            $sync[$exerciseId] = ['sort_order' => $i];
        }

        $workout->exercises()->sync($sync);
    });

    return redirect()->route('workouts.index')->with('status', 'Workout updated.');
}
  
}