<?php

namespace App\Http\Controllers;

use App\Models\BleAnchor;
use App\Models\Room;
use App\Models\Space;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use PhpMqtt\Client\Facades\MQTT;

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
            'anchor_uid' => strtolower((string) $request->anchor_uid),
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

    /**
     * 특정 Room의 모든 ESP32(BleAnchor)에 스캔 on/off 제어 publish
     */
    public function controlRoomScan(Request $request, Space $space, Room $room)
    {
        abort_unless($request->user()->spaces()->where('spaces.id', $space->id)->exists(), 403);
        abort_unless($room->space_id === $space->id, 404);

        $validated = $request->validate([
            'state' => 'required|in:on,off',
        ]);

        $anchors = $room->bleAnchors()
            ->whereNotNull('installed_at')
            ->get(['id', 'anchor_uid']);

        $mqtt = MQTT::connection();
        $retain = true;
        $published = [];
        $errors = [];

        foreach ($anchors as $anchor) {
            $scannerId = strtolower($anchor->anchor_uid);
            $topic = str_replace(
                '{scanner_id}',
                $scannerId,
                config(
                    'mqtt_topics.anchor_control_publish_pattern',
                    'bird/anchor/{scanner_id}/control'
                )
            );

            try {
                $mqtt->publish($topic, $validated['state'], 1, $retain);
                $published[] = [
                    'anchor_id' => $anchor->id,
                    'anchor_uid' => $scannerId,
                    'topic' => $topic,
                ];
            } catch (\Throwable $e) {
                $errors[] = [
                    'anchor_id' => $anchor->id,
                    'anchor_uid' => $scannerId,
                    'topic' => $topic,
                    'error' => $e->getMessage(),
                ];
            }
        }

        if (!empty($published)) {
            $mqtt->loop(true, true);
        }
        MQTT::disconnect();

        Log::info('MQTT scan control publish.', [
            'room_id' => $room->id,
            'state' => $validated['state'],
            'published_count' => count($published),
            'error_count' => count($errors),
        ]);

        return response()->json([
            'room_id' => $room->id,
            'state' => $validated['state'],
            'anchor_count' => count($published),
            'published' => $published,
            'errors' => $errors,
        ]);
    }
}
