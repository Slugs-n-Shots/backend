<?php

namespace Database\Factories;

use App\Models\DrinkUnit;
use App\Models\Order;
use App\Models\OrderDetail;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\OrderDetail>
 */
class OrderDetailFactory extends Factory
{
    public function definition(): array
    {
        return [
            'order_id' => Order::factory(),
            'drink_unit_id' => DrinkUnit::factory(),
            'ordered_quantity' => fake()->numberBetween(1, 4),
            'promo_id' => null,
            'unit_price' => fake()->numberBetween(300, 3000),
            'discount' => 0,
            'receipt_id' => null,
            'payment_status' => OrderDetail::PAYMENT_STATUS_PENDING,
        ];
    }
}
