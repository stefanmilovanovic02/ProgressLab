<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ExperienceEvent extends Model
{
    protected $fillable = [
        'user_id',
        'source_type',
        'source_key',
        'points',
        'description',
        'metadata',
    ];

    protected $casts = [
        'metadata' => 'array',
        'points' => 'integer',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
