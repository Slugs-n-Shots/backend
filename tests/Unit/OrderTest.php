<?php

namespace Tests\Unit;

use App\Models\Order;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrderTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function get_status_attribute_returns_served_when_served_at_is_set()
    {
        // Arrange
        $order = Order::factory()->create(['served_at' => Carbon::now()]);

        // Act
        $status = $order->status;

        // Assert
        $this->assertEquals(__('served'), $status);
    }

    /** @test */
    public function get_status_attribute_returns_ready_when_made_at_is_set()
    {
        // Arrange
        $order = Order::factory()->create([
            'served_at' => null,
            'made_at' => Carbon::now()
        ]);

        // Act
        $status = $order->status;

        // Assert
        $this->assertEquals(__('ready'), $status);
    }

    /** @test */
    public function get_status_attribute_returns_in_progress_when_recorded_at_is_set()
    {
        // Arrange
        $order = Order::factory()->create([
            'served_at' => null,
            'made_at' => null,
            'recorded_at' => Carbon::now()
        ]);

        // Act
        $status = $order->status;

        // Assert
        $this->assertEquals(__('in progress'), $status);
    }

    /** @test */
    public function get_status_attribute_returns_pending_when_no_timestamps_set()
    {
        // Arrange
        $order = Order::factory()->create([
            'served_at' => null,
            'made_at' => null,
            'recorded_at' => null
        ]);

        // Act
        $status = $order->status;

        // Assert
        $this->assertEquals(__('pending'), $status);
    }

    /** @test */
    public function get_status_attribute_prioritizes_served_over_others()
    {
        // Arrange
        $order = Order::factory()->create([
            'served_at' => Carbon::now(),
            'made_at' => Carbon::now(),
            'recorded_at' => Carbon::now()
        ]);

        // Act
        $status = $order->status;

        // Assert
        $this->assertEquals(__('served'), $status);
    }
}