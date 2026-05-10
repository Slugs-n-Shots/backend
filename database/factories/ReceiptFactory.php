<?php

namespace Database\Factories;

use App\Models\Employee;
use App\Models\Guest;
use App\Models\Receipt;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Receipt>
 */
class ReceiptFactory extends Factory
{
    public function definition(): array
    {
        return [
            'serno' => fake()->unique()->numerify('R########'),
            'guest_id' => Guest::factory(),
            'issued_at' => now(),
            'paid_for' => Employee::factory(),
            'paid_at' => now(),
            'payment_method' => fake()->randomElement(Receipt::PAYMENT_METHODS),
            'table' => null,
        ];
    }
}
