<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class NutritionEntry extends Model
{
    protected $fillable = [
        'user_id',
        'entry_date',
        'calories',
        'protein_g',
        'carbs_g',
        'fat_g',
        'creatine_g',
        'water_ml',
    ];

    protected $casts = [
        'entry_date' => 'string',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
