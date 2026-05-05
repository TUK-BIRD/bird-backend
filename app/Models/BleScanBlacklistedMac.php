<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BleScanBlacklistedMac extends Model
{
    protected $fillable = [
        'device_mac',
        'note',
        'created_by_user_id',
    ];

    public function setDeviceMacAttribute(?string $value): void
    {
        $this->attributes['device_mac'] = $value !== null ? strtolower($value) : null;
    }

    public function createdByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }
}
