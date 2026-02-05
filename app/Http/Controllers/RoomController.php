<?php

namespace App\Http\Controllers;

use App\Models\Room;
use App\Models\Space;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

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
    public function show(Request $request, Space $space, Room $room)
    {
        abort_unless($request->user()->spaces()->where('spaces.id', $space->id)->exists(), 403);
        abort_unless($room->space_id === $space->id, 404);

        return response()->json($room);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Space $space, Room $room)
    {
        abort_unless($request->user()->spaces()->where('spaces.id', $space->id)->exists(), 403);
        abort_unless($room->space_id === $space->id, 404);

        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|string|max:255',
            'description' => 'sometimes|nullable|string|max:255',
            'blueprint_json' => 'sometimes',
        ]);

        $validator->after(function ($validator) use ($request) {
            if (!$request->has('blueprint_json')) {
                return;
            }

            $bp = $request->input('blueprint_json');
            if (is_string($bp)) {
                json_decode($bp, true);
                if (json_last_error() !== JSON_ERROR_NONE) {
                    $validator->errors()->add('blueprint_json', 'Must be valid JSON string or array.');
                }
                return;
            }

            if (!is_array($bp) && !is_null($bp)) {
                $validator->errors()->add('blueprint_json', 'Must be JSON string, array, or null.');
            }
        });

        $validated = $validator->validate();

        $update = [];
        if (array_key_exists('name', $validated)) {
            $update['name'] = $validated['name'];
        }
        if (array_key_exists('description', $validated)) {
            $update['description'] = $validated['description'];
        }
        if ($request->has('blueprint_json')) {
            $bp = $request->input('blueprint_json');
            if (is_string($bp)) {
                $bp = json_decode($bp, true);
            }
            $update['blueprint_json'] = $bp;
        }

        if (!empty($update)) {
            $room->update($update);
        }

        return response()->json($room->fresh());
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Request $request, Space $space, Room $room)
    {
        abort_unless($request->user()->spaces()->where('spaces.id', $space->id)->exists(), 403);
        abort_unless($room->space_id === $space->id, 404);

        $room->delete();

        return response()->json([
            'deleted' => true,
            'room_id' => $room->id,
        ]);
    }
}
