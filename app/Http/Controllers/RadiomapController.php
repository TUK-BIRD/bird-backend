<?php

namespace App\Http\Controllers;

use App\Models\RadiomapMeasurement;
use App\Models\RadiomapSession;
use Illuminate\Http\Request;

class RadiomapController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $validated = request()->validate([
            'room_id' => ['nullable', 'integer', 'exists:rooms,id'],
        ]);

    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = request()->validate([
            'room_id' => ['nullable', 'integer', 'exists:rooms,id'],
            'measurements' => ['required', 'array', 'min:1'],
            'measurements.*.reference_point_id' => ['required', 'integer', 'exists:reference_points,id'],
            'measurements.*.anchor_id' => ['required', 'integer', 'exists:anchors,id'],
            'measurements.*.rssi_dbm' => ['required', 'integer'],
        ]);


        $session = RadiomapSession::create([
            'room_id' => $request->room_id,
            'started_at' => now(),
            'ended_at' => now(),
            'note' => 'Auto-created from mobile app',
        ]);

        $now = now();
        foreach ($request->measurements as $m) {
            RadiomapMeasurement::create([
                'radiomap_session_id' => $session->id,
                'reference_point_id' => $m['reference_point_id'],
                'anchor_id' => $m['anchor_id'],
                'rssi_dbm' => $m['rssi_dbm'],
                'measured_at' => $now,
            ]);
        }

        return response()->json([
            'session' => $session,
            'measurement_count' => count($request->measurements),
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
