<?php

namespace Tests\Unit;

use App\Models\Order;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrderTest extends TestCase
{
    use RefreshDatabase;

    public function test_status_is_machine_value_from_database(): void
    {
        $order = Order::factory()->create(['status' => Order::STATUS_READY]);

        $this->assertEquals(Order::STATUS_READY, $order->status);
    }

    public function test_status_defaults_to_open(): void
    {
        $order = Order::factory()->create();

        $this->assertEquals(Order::STATUS_OPEN, $order->status);
    }

    public function test_status_label_translates_machine_status(): void
    {
        $order = Order::factory()->create(['status' => Order::STATUS_SERVED]);

        $this->assertEquals(__('served'), $order->status_label);
    }
}
