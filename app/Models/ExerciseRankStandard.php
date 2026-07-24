<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ExerciseRankStandard extends Model
{
    protected $fillable = [
        'exercise_id',
        'scoring_type',
        'olympian_target',
        'unit',
        'is_active',
    ];

    protected $casts = [
        'olympian_target' => 'float',
        'is_active' => 'boolean',
    ];

    public function exercise()
    {
        return $this->belongsTo(Exercise::class);
    }
}
