<?php

namespace App\Models;

use App\Enums\UserSpaceRole;
use Illuminate\Database\Eloquent\Model;

class UserSpace extends Model
{
    protected $table = 'user_spaces';

    protected $fillable = [
        'user_id',
        'space_id',
        'role',
    ];

    protected $casts = [
        'role' => UserSpaceRole::class,
    ];
}
