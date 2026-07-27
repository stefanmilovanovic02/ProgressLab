<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BodyMeasurement extends Model
{
    protected $fillable = [
        'user_id',
        'recorded_on',
        'weight_kg',
        'waist_cm',
        'arms_cm',
        'thighs_cm',
        'hips_cm',
        'glutes_cm',
    ];

    protected function casts(): array
    {
        return [
            'weight_kg' => 'float',
            'waist_cm' => 'float',
            'arms_cm' => 'float',
            'thighs_cm' => 'float',
            'hips_cm' => 'float',
            'glutes_cm' => 'float',
        ];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
