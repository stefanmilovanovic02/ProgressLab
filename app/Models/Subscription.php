<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;

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

    public function scopeCurrentlyActive(Builder $query, $date = null): Builder
    {
        $date = $date ?: today();

        return $query
            ->whereIn('status', ['active', 'trial'])
            ->whereDate('starts_on', '<=', $date)
            ->where(function (Builder $query) use ($date) {
                $query->whereNull('ends_on')
                    ->orWhereDate('ends_on', '>=', $date);
            });
    }
}
