<?php

namespace Database\Factories;

use App\Models\ReferencePoint;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ReferencePoint>
 */
class ReferencePointFactory extends Factory
{
    protected $model = ReferencePoint::class;

    public function definition(): array
    {
        return [
            'room_id' => 1,
            'label' => $this->faker->unique()->bothify('RP-##'),
            'x_m' => $this->faker->randomFloat(2, 0, 50),
            'y_m' => $this->faker->randomFloat(2, 0, 50),
            'z_m' => $this->faker->randomFloat(2, 0, 5),
        ];
    }
}
