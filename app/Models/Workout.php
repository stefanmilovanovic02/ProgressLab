<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Workout extends Model
{
  protected $fillable = ['user_id', 'name'];

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
}