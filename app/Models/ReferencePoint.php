<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ReferencePoint extends Model
{
    use HasFactory;

    protected $table = 'reference_points';

    protected $fillable = [
        'room_id',
        'label',
        'x_m',
        'y_m',
        'z_m',
    ];

    public function room(): BelongsTo
    {
        return $this->belongsTo(Room::class);
    }

    public function radiomapMeasurements(): HasMany
    {
        return $this->hasMany(RadiomapMeasurement::class, 'reference_point_id');
    }
}
