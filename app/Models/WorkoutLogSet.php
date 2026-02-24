<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WorkoutLogSet extends Model
{
  protected $fillable = ['workout_log_exercise_id','set_number','reps','weight_kg'];

  public function logExercise() { return $this->belongsTo(WorkoutLogExercise::class, 'workout_log_exercise_id'); }
}
