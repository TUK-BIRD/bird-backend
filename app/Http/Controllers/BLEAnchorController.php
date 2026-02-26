<?php

namespace App\Http\Controllers;

use App\Models\BleAnchor;
use App\Models\Room;
use App\Models\Space;
use Illuminate\Http\Request;

class BLEAnchorController extends Controller
{
    /**
     * ESP32 전체 조회
     */
    public function index(Request $request)
    {
        $request->validate([
            'room_id' => 'nullable|integer|exists:rooms,id',
        ]);

        $query = BleAnchor::query()->whereNotNull('installed_at');

        if ($request->filled('room_id')) {
            $query->where('room_id', $request->room_id);
        }

        return response()->json(
            $query->latest('installed_at')->get()
        );
    }

    /**
     * 새로운 ESP32 등록
     * @param Request $request
     * return string JSON-encoded BleAnchor object
     */
    public function store(Request $request)
    {
        $request->validate([
            'anchor_uid' => 'required',
            'room_id' => 'required|exists:rooms,id',
            'label' => 'string|required',
        ]);

        $anchor = BleAnchor::create([
            'anchor_uid' => $request->anchor_uid,
            'room_id' => $request->room_id,
            'label' => $request->label,
            'tx_power_dbm' => $request->tx_power_dbm ?? 9,
            'installed_at' => now(),
        ]);

        return response()->json($anchor, 201);
    }

    /**
     * 특정 Room에 설치된 ESP32(BleAnchor) 조회
     */
    public function roomIndex(Request $request, Space $space, Room $room)
    {
        abort_unless($request->user()->spaces()->where('spaces.id', $space->id)->exists(), 403);
        abort_unless($room->space_id === $space->id, 404);

        return response()->json(
            $room->bleAnchors()
                ->whereNotNull('installed_at')
                ->latest('installed_at')
                ->get()
        );
    }

    /**
     * 특정 Room의 ESP32(BleAnchor) 삭제
     */
    public function destroy(Request $request, BleAnchor $anchor)
    {
        $room = $anchor->room;
        abort_unless($room !== null, 404);

        $space = $room->space;
        abort_unless($space !== null, 404);

        abort_unless($request->user()->spaces()->where('spaces.id', $space->id)->exists(), 403);

        $anchorId = $anchor->id;
        $anchor->delete();

        return response()->json([
            'deleted' => true,
            'anchor_id' => $anchorId,
        ]);
    }
}
