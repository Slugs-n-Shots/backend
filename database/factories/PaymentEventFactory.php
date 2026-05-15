<?php

namespace Database\Factories;

use App\Models\PaymentAttempt;
use App\Models\PaymentEvent;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\PaymentEvent>
 */
class PaymentEventFactory extends Factory
{
    public function definition(): array
    {
        return [
            'payment_attempt_id' => PaymentAttempt::factory(),
            'event_type' => PaymentEvent::TYPE_CREATED,
            'actor_guest_id' => null,
            'actor_employee_id' => null,
            'order_detail_id' => null,
            'receipt_id' => null,
            'audit_xml' => null,
            'created_at' => now(),
        ];
    }
}
