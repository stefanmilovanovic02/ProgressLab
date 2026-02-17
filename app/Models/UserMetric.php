<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserMetric extends Model
{
    protected $fillable = [
        'user_id',
        'gender','age','height_cm','weight_kg',
        'activity_multiplier','bmr','tdee',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
