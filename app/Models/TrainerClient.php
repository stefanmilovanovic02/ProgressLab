<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TrainerClient extends Model
{
    public const STATUS_PENDING = 'pending';
    public const STATUS_ACCEPTED = 'accepted';
    public const STATUS_DECLINED = 'declined';
    public const STATUS_REVOKED = 'revoked';

    protected $fillable = [
        'trainer_id',
        'client_id',
        'status',
        'can_view_nutrition',
        'can_view_exercises',
        'can_view_weight',
        'can_view_streaks',
        'trainer_notes',
        'accepted_at',
        'revoked_at',
    ];

    protected function casts(): array
    {
        return [
            'can_view_nutrition' => 'boolean',
            'can_view_exercises' => 'boolean',
            'can_view_weight' => 'boolean',
            'can_view_streaks' => 'boolean',
            'accepted_at' => 'datetime',
            'revoked_at' => 'datetime',
        ];
    }

    public function trainer()
    {
        return $this->belongsTo(User::class, 'trainer_id');
    }

    public function client()
    {
        return $this->belongsTo(User::class, 'client_id');
    }

    public function workoutAssignments()
    {
        return $this->hasMany(TrainerWorkoutAssignment::class);
    }

    public function isAccepted(): bool
    {
        return $this->status === self::STATUS_ACCEPTED;
    }

    public function permits(string $area): bool
    {
        return $this->isAccepted() && (bool) $this->getAttribute('can_view_' . $area);
    }
}
