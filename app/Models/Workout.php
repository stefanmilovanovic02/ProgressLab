<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Workout extends Model
{
  protected $fillable = ['user_id', 'name', 'estimated_duration_seconds'];

  protected function casts(): array
  {
    return [
      'estimated_duration_seconds' => 'integer',
    ];
  }

  public function getEstimatedDurationLabelAttribute(): ?string
  {
    $seconds = $this->estimated_duration_seconds;

    if (!$seconds) {
      return null;
    }

    $minutes = max(1, (int) round($seconds / 60));
    $hours = intdiv($minutes, 60);
    $remainingMinutes = $minutes % 60;

    if ($hours > 0) {
      return $remainingMinutes > 0
        ? $hours . 'h ' . $remainingMinutes . 'm'
        : $hours . 'h';
    }

    return $minutes . ' min';
  }

  public function exercises()
  {
    return $this->belongsToMany(Exercise::class)
      ->withPivot('sort_order')
      ->withTimestamps()
      ->orderBy('exercise_workout.sort_order');
  }

  public function user()
  {
    return $this->belongsTo(User::class);
  }

  public function trainerAssignment()
  {
    return $this->hasOne(TrainerWorkoutAssignment::class, 'client_workout_id');
  }
}
