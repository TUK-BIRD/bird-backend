<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Room extends Model
{
    protected $table = 'rooms';
    protected $fillable = [
        'name',
        'description',
    ];

    public function space(): BelongsTo
    {
        return $this->belongsTo(Space::class);
    }
}
