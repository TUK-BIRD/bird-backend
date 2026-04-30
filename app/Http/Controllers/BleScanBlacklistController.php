<?php

namespace App\Http\Controllers;

use App\Models\BleScanBlacklistedMac;
use Illuminate\Http\Request;

class BleScanBlacklistController extends Controller
{
    public function index()
    {
        return response()->json(
            BleScanBlacklistedMac::query()
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

        $entry = BleScanBlacklistedMac::create([
            'device_mac' => strtolower((string) $validated['device_mac']),
            'note' => $validated['note'] ?? null,
        ]);

        return response()->json($entry, 201);
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
