<?php

namespace Database\Factories;

use App\Models\BleAnchor;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<BleAnchor>
 */
class BleAnchorFactory extends Factory
{
    protected $model = BleAnchor::class;

    public function definition(): array
    {
        return [
            'anchor_uid' => $this->faker->unique()->bothify('ANCHOR-#####-????'),
            'room_id' => 1,
            'label' => $this->faker->unique()->bothify('Beacon-##'),
            'tx_power_dbm' => $this->faker->optional()->numberBetween(-90, -40),
            'installed_at' => $this->faker->optional()->dateTimeBetween('-6 months', 'now'),
        ];
    }
}
