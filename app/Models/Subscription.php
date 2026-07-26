<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Subscription extends Model
{
    protected $fillable = [
        'user_id',
        'plan',
        'status',
        'is_complimentary',
        'amount_paid',
        'currency',
        'starts_on',
        'ends_on',
        'paid_at',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'amount_paid' => 'decimal:2',
            'is_complimentary' => 'boolean',
            'starts_on' => 'date',
            'ends_on' => 'date',
            'paid_at' => 'datetime',
        ];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
