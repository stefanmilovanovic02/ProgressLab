<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Achievement extends Model
{
  protected $fillable = [
    'code','title','description','category','rarity','points','is_active','criteria'
  ];

  protected $casts = [
    'is_active' => 'boolean',
    'criteria' => 'array',
  ];

  public function users()
{
    return $this->belongsToMany(User::class, 'user_achievements')
        ->withTimestamps()
        ->withPivot(['unlocked_at']);
}

}