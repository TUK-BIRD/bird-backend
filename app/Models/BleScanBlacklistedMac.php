<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BleScanBlacklistedMac extends Model
{
    protected $fillable = [
        'device_mac',
        'note',
    ];

    public function setDeviceMacAttribute(?string $value): void
    {
        $this->attributes['device_mac'] = $value !== null ? strtolower($value) : null;
    }
}
