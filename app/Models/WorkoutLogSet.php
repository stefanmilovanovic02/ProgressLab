<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WorkoutLogSet extends Model
{
  protected $fillable = [
    'workout_log_exercise_id',
    'set_number',
    'set_type',
    'reps',
    'weight_kg',
    'drop_reps',
    'drop_weight_kg',
  ];

  protected $casts = [
    'set_number' => 'integer',
    'reps' => 'integer',
    'weight_kg' => 'float',
    'drop_reps' => 'integer',
    'drop_weight_kg' => 'float',
  ];

  public function logExercise() { return $this->belongsTo(WorkoutLogExercise::class, 'workout_log_exercise_id'); }
}
