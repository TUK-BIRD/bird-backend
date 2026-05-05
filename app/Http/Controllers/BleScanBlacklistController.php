<?php

namespace App\Http\Controllers;

use App\Models\BleScanEvent;
use App\Models\BleScanBlacklistedMac;
use App\Models\LocationEstimate;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class BleScanBlacklistController extends Controller
{
    public function index()
    {
        return response()->json(
            BleScanBlacklistedMac::query()
                ->with('createdByUser:id,name,email')
                ->orderBy('device_mac')
                ->get()
        );
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'device_mac' => ['required', 'regex:/^([0-9a-fA-F]{2}:){5}[0-9a-fA-F]{2}$/', 'unique:ble_scan_blacklisted_macs,device_mac'],
            'note' => ['nullable', 'string', 'max:255'],
        ]);

        $deviceMac = strtolower((string) $validated['device_mac']);

        [$entry, $deletedScanEventCount, $deletedLocationEstimateCount] = DB::transaction(function () use ($request, $validated, $deviceMac) {
            $entry = BleScanBlacklistedMac::create([
                'device_mac' => $deviceMac,
                'note' => $validated['note'] ?? null,
                'created_by_user_id' => $request->user()->id,
            ]);

            $deletedScanEventCount = BleScanEvent::query()
                ->where('device_mac', $deviceMac)
                ->delete();

            $deletedLocationEstimateCount = LocationEstimate::query()
                ->where('device_mac', $deviceMac)
                ->delete();

            return [$entry, $deletedScanEventCount, $deletedLocationEstimateCount];
        });

        return response()->json([
            ...$entry->load('createdByUser:id,name,email')->toArray(),
            'deleted_scan_event_count' => $deletedScanEventCount,
            'deleted_location_estimate_count' => $deletedLocationEstimateCount,
        ], 201);
    }

    public function destroy(BleScanBlacklistedMac $blacklistedMac)
    {
        $deletedMac = $blacklistedMac->device_mac;
        $blacklistedMac->delete();

        return response()->json([
            'deleted' => true,
            'device_mac' => $deletedMac,
        ]);
    }
}
