<?php

namespace App\Http\Controllers;

use App\Models\Room;
use App\Models\Space;
use Illuminate\Http\Request;

class RoomController extends Controller
{
    /**
     * 전체 Room 조회
     * @param Request $request
     * @param Space $space
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request, Space $space)
    {
//        return response()->json(
//            $request->user()->spaces()->where('spaces.id', $request->space_id)->get()
//        );
        abort_unless($request->user()->spaces()->where('spaces.id', $space->id)->exists(), 403);

        return response()->json(
            $space->rooms()->latest()->get()
        );
    }

    /**
     * 새로운 Room 등록
     * @param Request $request
     * @param Space $space
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request, Space $space)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'string|max:255',
            'space_id' => 'required|integer|exists:spaces,id',
            'blueprint_json' => 'json'
        ]);

        $bp = $request->get('blueprint_json');
        $decoded = $bp ? json_decode($bp, true) : null;

        $space->rooms()->create([
            'name' => $request->get('name'),
            'description' => $request->get('description'),
            'space_id' => $request->get('space_id'),
            'blueprint_json' => $decoded,
        ]);

        return response()->json(
            $request->get('blueprint_json')
        );
    }

    /**
     * Display the specified resource.
     */
    public function show(Room $room)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Room $room)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Room $room)
    {
        //
    }
}
