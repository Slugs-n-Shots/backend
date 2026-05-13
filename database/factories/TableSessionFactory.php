<?php

namespace Database\Factories;

use App\Models\Guest;
use App\Models\Table;
use App\Models\TableSession;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\TableSession>
 */
class TableSessionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'table_id' => Table::factory(),
            'owner_guest_id' => Guest::factory(),
            'business_date' => now()->toDateString(),
            'opened_at' => now(),
            'closed_at' => null,
            'status' => TableSession::STATUS_OPEN,
        ];
    }
}
