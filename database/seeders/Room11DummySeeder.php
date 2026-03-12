<?php

namespace Database\Seeders;

use App\Models\ReferencePoint;
use Illuminate\Database\Seeder;

class Room11DummySeeder extends Seeder
{
    public function run(): void
    {
        $roomId = 11;
        $xMax = 15.0;
        $yMax = 15.0;
        $step = 5;

        for ($x = 0.0; $x <= $xMax + 1e-9; $x += $step) {
            for ($y = 0.0; $y <= $yMax + 1e-9; $y += $step) {
                ReferencePoint::create([
                    'room_id' => $roomId,
                    'label' => sprintf('RP-%.1f-%.1f', $x, $y),
                    'x_m' => $x,
                    'y_m' => $y,
                    'z_m' => 0.0,
                ]);
            }
        }
    }
}
