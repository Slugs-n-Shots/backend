<?php

namespace Database\Factories;

use App\Models\Guest;
use App\Models\PaymentAttempt;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\PaymentAttempt>
 */
class PaymentAttemptFactory extends Factory
{
    public function definition(): array
    {
        return [
            'guest_id' => Guest::factory(),
            'employee_id' => null,
            'table_session_id' => null,
            'receipt_id' => null,
            'status' => PaymentAttempt::STATUS_PENDING,
            'payment_method' => PaymentAttempt::METHOD_CARD,
            'amount' => fake()->numberBetween(500, 10000),
            'currency' => 'HUF',
            'started_at' => now(),
            'finished_at' => null,
        ];
    }
}
