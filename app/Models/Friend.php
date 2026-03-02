<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Friend extends Model
{
    protected $fillable = ['user_id', 'friend_id', 'status', 'accepted_at'];
    protected $casts = ['accepted_at' => 'datetime'];

    public function requester() {
        return $this->belongsTo(\App\Models\User::class, 'user_id');
    }
}