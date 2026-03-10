<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property string $name
 * @property string|null $description
 * @property string|null $blueprint_json
 * @property int $space_id
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Space $space
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Room newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Room newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Room query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Room whereBlueprintJson($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Room whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Room whereDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Room whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Room whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Room whereSpaceId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Room whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class Room extends Model
{
    protected $table = 'rooms';
    protected $fillable = [
        'name',
        'description',
        'blueprint_json',
        'info_json',
    ];

    public function space(): BelongsTo
    {
        return $this->belongsTo(Space::class);
    }

    public function referencePoints(): HasMany
    {
        return $this->hasMany(ReferencePoint::class, 'room_id');
    }

    public function bleAnchors(): HasMany
    {
        return $this->hasMany(BleAnchor::class, 'room_id');
    }

    public function radiomapSessions(): HasMany
    {
        return $this->hasMany(RadiomapSession::class, 'room_id');
    }

    public function bleScanEvents(): HasMany
    {
        return $this->hasMany(BleScanEvent::class, 'room_id');
    }

    protected $casts = [
        'blueprint_json' => 'array',
        'info_json' => 'array',
    ];
}
