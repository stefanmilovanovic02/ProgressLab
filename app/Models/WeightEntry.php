<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WeightEntry extends Model
{
    protected $fillable = ['user_id', 'recorded_on', 'weight_kg', 'source'];

    protected function casts(): array
    {
        return [
            'weight_kg' => 'float',
        ];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
