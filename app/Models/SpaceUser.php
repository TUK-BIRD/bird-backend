<?php

namespace App\Models;

use App\Enums\UserSpaceRole;
use Illuminate\Database\Eloquent\Model;

/**
 * @property UserSpaceRole $role
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SpaceUser newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SpaceUser newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SpaceUser query()
 * @mixin \Eloquent
 */
class SpaceUser extends Model
{
    protected $table = 'space_user';

    protected $fillable = [
        'user_id',
        'space_id',
        'role',
    ];

    protected $casts = [
        'role' => UserSpaceRole::class,
    ];
}
