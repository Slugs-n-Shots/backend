<?php

namespace Database\Factories;

use App\Models\Guest;
use App\Models\Order;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Order>
 */
class OrderFactory extends Factory
{
    public function definition(): array
    {
        return [
            'guest_id' => Guest::factory(),
            'recorded_at' => now(),
            'status' => Order::STATUS_OPEN,
        ];
    }
}
