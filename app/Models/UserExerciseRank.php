<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserExerciseRank extends Model
{
    protected $fillable = [
        'user_id',
        'exercise_id',
        'best_value',
        'best_estimated_1rm',
        'score',
        'rank',
        'ranked_at',
    ];

    protected $casts = [
        'best_value' => 'float',
        'best_estimated_1rm' => 'float',
        'score' => 'float',
        'ranked_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function exercise()
    {
        return $this->belongsTo(Exercise::class);
    }
}
