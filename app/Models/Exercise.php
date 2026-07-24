<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Exercise extends Model
{
  protected $fillable = ['name', 'muscle_group', 'image_path'];

  public function workouts()
  {
    return $this->belongsToMany(Workout::class)
      ->withPivot('sort_order')
      ->withTimestamps();
  }

  public function rankStandard()
  {
    return $this->hasOne(ExerciseRankStandard::class);
  }

  public function userRanks()
  {
    return $this->hasMany(UserExerciseRank::class);
  }
}
