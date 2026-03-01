<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserAchievement extends Model
{
  protected $fillable = ['user_id','achievement_id','unlocked_at','notified_at','progress'];

  protected $casts = [
    'unlocked_at' => 'datetime',
    'notified_at' => 'datetime',
    'progress' => 'array',
  ];

  public function achievement () {
    return $this->belongsTo(\App\Models\Achievement::class);
  }
}

