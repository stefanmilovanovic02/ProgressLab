<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProgressPhotoSet extends Model
{
    protected $fillable = [
        'user_id',
        'front_path',
        'side_path',
        'back_path',
        'captured_on',
    ];

    protected function casts(): array
    {
        return [
            'captured_on' => 'date',
        ];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
