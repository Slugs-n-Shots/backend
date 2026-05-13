<?php

namespace Database\Factories;

use App\Models\Guest;
use App\Models\TableMember;
use App\Models\TableSession;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\TableMember>
 */
class TableMemberFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'table_session_id' => TableSession::factory(),
            'guest_id' => Guest::factory(),
            'role' => TableMember::ROLE_MEMBER,
            'status' => TableMember::STATUS_PENDING,
            'can_order' => true,
            'approved_by_guest_id' => null,
            'approved_at' => null,
            'removed_at' => null,
        ];
    }
}
