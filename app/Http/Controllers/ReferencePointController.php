<?php

namespace App\Http\Controllers;

use App\Models\ReferencePoint;
use Illuminate\Http\Request;

class ReferencePointController extends Controller
{
    /**
     * Room의 Reference Point 조회
     */
    public function index(Request $request)
    {
        $request->validate([
            'room_id' => 'required|integer|exists:rooms,id',
        ]);

        $rps = ReferencePoint::where('room_id', $request->room_id)->get();

        return response()->json($rps);
    }

    /**
     * 새로운 Reference Point 등록
     */
    public function store(Request $request)
    {
        $request->validate([
            'room_id' => 'required|integer|exists:rooms,id',
            'label' => 'required|string|max:255',
            'x_m' => 'required|numeric',
            'y_m' => 'required|numeric',
            'z_m' => 'numeric',
        ]);

        $rp = ReferencePoint::create([
            'room_id' => $request->room_id,
            'label' => $request->label,
            'x_m' => $request->x_m,
            'y_m' => $request->y_m,
            'z_m' => $request->z_m ?? 0.0,
        ]);

        return response()->json($rp, 201);
    }
}
