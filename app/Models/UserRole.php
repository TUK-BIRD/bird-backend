<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserRole extends Model
{
    protected $table = 'user_roles';

    protected $fillable = [
        'user_id',
        'role',
    ];

    protected $casts = [
        'role' => \App\Enums\UserRole::class,
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
