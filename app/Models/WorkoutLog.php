<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WorkoutLog extends Model
{
  protected $fillable = [
    'user_id',
    'workout_id',
    'entry_date',
    'started_at',
    'completed_at',
    'duration_seconds',
  ];

  protected function casts(): array
  {
    return [
      'started_at' => 'datetime',
      'completed_at' => 'datetime',
      'duration_seconds' => 'integer',
    ];
  }

  public function workout() { return $this->belongsTo(Workout::class); }
  public function exercises() { return $this->hasMany(WorkoutLogExercise::class); }
}
