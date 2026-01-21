<?php

namespace App\Models;

use App\Enums\UserSpaceRole;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Pivot;

/**
 * @property UserSpaceRole $role
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SpaceUser newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SpaceUser newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SpaceUser query()
 * @mixin \Eloquent
 */
class SpaceUser extends Pivot
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

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function space()
    {
        return $this->belongsTo(Space::class);
    }
}
