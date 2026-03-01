<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LoginLog extends Model
{
    protected $fillable = ['user_id', 'login_date'];
    protected $casts = ['login_date' => 'date', ];
}
