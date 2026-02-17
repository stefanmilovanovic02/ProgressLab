<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class NutritionGoal extends Model
{
    protected $fillable = [
        'user_id',
        'goal',
        'calorie_target','protein_g','fat_g','carbs_g',
        'fat_percent','protein_g_per_kg',
        'bulk_type','cut_type',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
