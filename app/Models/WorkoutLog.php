<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WorkoutLog extends Model
{
  protected $fillable = ['user_id','workout_id','entry_date'];

  public function workout() { return $this->belongsTo(Workout::class); }
  public function exercises() { return $this->hasMany(WorkoutLogExercise::class); }
}

