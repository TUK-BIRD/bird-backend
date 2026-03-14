<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $name
 * @property string|null $description
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Collection<int, Room> $rooms
 * @property-read int|null $rooms_count
 * @property-read Collection<int, User> $users
 * @property-read int|null $users_count
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Space newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Space newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Space query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Space whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Space whereDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Space whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Space whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Space whereUpdatedAt($value)
 *
 * @mixin \Eloquent
 */
class Space extends Model
{
    protected $table = 'spaces';

    protected $fillable = [
        'name',
        'description',
    ];

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class)
            ->withTimestamps();
    }

    public function rooms(): HasMany
    {
        return $this->hasMany(Room::class);
    }

    public function invitations(): HasMany
    {
        return $this->hasMany(Invitation::class);
    }
}
