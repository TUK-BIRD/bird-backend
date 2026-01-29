<?php

namespace App\Http\Controllers;

use App\Models\BleAnchor;
use Illuminate\Http\Request;

class BLEAnchorController extends Controller
{
    /**
     * ESP32 전체 조회
     */
    public function index()
    {

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

        return json_encode($anchor);
    }
}
