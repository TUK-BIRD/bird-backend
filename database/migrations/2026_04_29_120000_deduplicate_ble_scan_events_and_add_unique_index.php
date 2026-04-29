<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $duplicates = DB::table('ble_scan_events')
            ->select([
                'anchor_id',
                'device_mac',
                'scanned_at',
                DB::raw('MIN(id) as keep_id'),
                DB::raw('COUNT(*) as duplicate_count'),
            ])
            ->groupBy('anchor_id', 'device_mac', 'scanned_at')
            ->havingRaw('COUNT(*) > 1')
            ->get();

        foreach ($duplicates as $duplicate) {
            DB::table('ble_scan_events')
                ->where('device_mac', $duplicate->device_mac)
                ->where('scanned_at', $duplicate->scanned_at)
                ->where('id', '!=', $duplicate->keep_id)
                ->when(
                    $duplicate->anchor_id === null,
                    fn ($query) => $query->whereNull('anchor_id'),
                    fn ($query) => $query->where('anchor_id', $duplicate->anchor_id)
                )
                ->delete();
        }

        Schema::table('ble_scan_events', function (Blueprint $table) {
            $table->unique(
                ['anchor_id', 'device_mac', 'scanned_at'],
                'ble_scan_events_anchor_device_scanned_unique'
            );
        });
    }

    public function down(): void
    {
        Schema::table('ble_scan_events', function (Blueprint $table) {
            $table->dropUnique('ble_scan_events_anchor_device_scanned_unique');
        });
    }
};
