<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TrainerWorkoutAssignment extends Model
{
    protected $fillable = [
        'trainer_client_id',
        'source_workout_id',
        'client_workout_id',
        'instructions',
        'assigned_at',
    ];

    protected function casts(): array
    {
        return ['assigned_at' => 'datetime'];
    }

    public function relationship()
    {
        return $this->belongsTo(TrainerClient::class, 'trainer_client_id');
    }

    public function sourceWorkout()
    {
        return $this->belongsTo(Workout::class, 'source_workout_id');
    }

    public function clientWorkout()
    {
        return $this->belongsTo(Workout::class, 'client_workout_id');
    }
}
