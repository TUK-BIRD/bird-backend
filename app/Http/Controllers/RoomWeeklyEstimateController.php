<?php

namespace App\Http\Controllers;

use App\Models\Room;
use App\Models\Space;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class RoomWeeklyEstimateController extends Controller
{
    public function index(Request $request, Space $space, Room $room): JsonResponse
    {
        abort_unless($room->space_id === $space->id, 404);

        $validated = $request->validate([
            'day_of_week' => 'nullable|integer|min:0|max:6',
        ]);

        $query = $room->weeklyEstimates()->orderBy('day_of_week')->orderBy('time');

        if (isset($validated['day_of_week'])) {
            $query->where('day_of_week', $validated['day_of_week']);
        }

        $estimates = $query->get();

        $grouped = $estimates->groupBy('day_of_week')->map(function ($dayEstimates, $dayOfWeek) {
            $dayNames = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];

            return [
                'day_of_week' => (int) $dayOfWeek,
                'day_name' => $dayNames[$dayOfWeek] ?? null,
                'slots' => $dayEstimates->map(fn ($estimate) => [
                    'id' => $estimate->id,
                    'time' => $estimate->time,
                    'estimated_device_count' => $estimate->estimated_device_count,
                    'avg_device_count' => $estimate->avg_device_count,
                    'max_device_count' => $estimate->max_device_count,
                ])->values(),
            ];
        })->values();

        return response()->json([
            'space' => [
                'id' => $space->id,
                'name' => $space->name,
            ],
            'room' => [
                'id' => $room->id,
                'name' => $room->name,
            ],
            'weekly_estimates' => $grouped,
        ]);
    }
}