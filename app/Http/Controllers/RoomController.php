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
     * @param Space $space
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Space $space)
    {
        $space->rooms()->create([
            'name' => "Test",
            'description' => "Test Description",
            'space_id' => $space->id,
        ]);

        return response()->json(
            $space->rooms()->latest()->get()
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
