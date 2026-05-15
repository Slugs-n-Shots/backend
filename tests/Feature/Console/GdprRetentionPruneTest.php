<?php

namespace Tests\Feature\Console;

use App\Models\Drink;
use App\Models\DrinkUnit;
use App\Models\Guest;
use App\Models\Order;
use App\Models\OrderDetail;
use App\Models\PaymentAttempt;
use App\Models\PaymentEvent;
use App\Models\Receipt;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class GdprRetentionPruneTest extends TestCase
{
    use DatabaseMigrations;

    protected function setUp(): void
    {
        parent::setUp();

        DB::connection()->getSchemaBuilder()->enableForeignKeyConstraints();
    }

    public function test_retention_prune_detaches_old_settled_order_personal_links_and_keeps_recent_drinks(): void
    {
        config([
            'gdpr.order_personal_data_retention_days' => 7,
            'gdpr.recent_drinks_per_guest' => 10,
        ]);

        $guest = Guest::factory()->create(['email_verified_at' => now()]);
        $drink = Drink::factory()->create([
            'name_en' => 'Retention Soda',
            'name_hu' => 'Retention szoda',
            'active' => true,
        ]);
        $unit = DrinkUnit::factory()->create([
            'drink_id' => $drink->id,
            'quantity' => 1,
            'unit_en' => 'glass',
            'unit_hu' => 'pohar',
            'unit_price' => 900,
        ]);
        $oldOrder = Order::factory()->create([
            'guest_id' => $guest->id,
            'status' => Order::STATUS_SERVED,
            'recorded_at' => now()->subDays(10),
        ]);
        $oldDetail = OrderDetail::factory()->create([
            'order_id' => $oldOrder->id,
            'drink_unit_id' => $unit->id,
            'payment_status' => OrderDetail::PAYMENT_STATUS_PAID,
        ]);
        $receipt = Receipt::factory()->create([
            'guest_id' => $guest->id,
            'issued_at' => now()->subDays(10),
            'paid_at' => now()->subDays(10),
            'customer_name' => 'Retention Customer',
        ]);
        $paymentAttempt = PaymentAttempt::factory()->create([
            'guest_id' => $guest->id,
            'receipt_id' => $receipt->id,
            'status' => PaymentAttempt::STATUS_SUCCEEDED,
            'started_at' => now()->subDays(10),
            'finished_at' => now()->subDays(10),
        ]);
        $paymentEvent = PaymentEvent::factory()->create([
            'payment_attempt_id' => $paymentAttempt->id,
            'actor_guest_id' => $guest->id,
            'receipt_id' => $receipt->id,
            'created_at' => now()->subDays(10),
        ]);

        $this
            ->artisan('gdpr:retention-prune', ['--dry-run' => true])
            ->assertExitCode(0);

        $this->assertDatabaseHas('orders', ['id' => $oldOrder->id, 'guest_id' => $guest->id]);

        $this
            ->artisan('gdpr:retention-prune')
            ->assertExitCode(0);

        $this->assertDatabaseHas('orders', ['id' => $oldOrder->id, 'guest_id' => null]);
        $this->assertDatabaseHas('order_details', ['id' => $oldDetail->id, 'order_id' => $oldOrder->id]);
        $this->assertDatabaseHas('receipts', [
            'id' => $receipt->id,
            'guest_id' => null,
            'customer_name' => 'Retention Customer',
        ]);
        $this->assertDatabaseHas('payment_attempts', ['id' => $paymentAttempt->id, 'guest_id' => null]);
        $this->assertDatabaseHas('payment_events', ['id' => $paymentEvent->id, 'actor_guest_id' => null]);
        $this->assertDatabaseHas('guest_recent_drinks', [
            'guest_id' => $guest->id,
            'drink_id' => $drink->id,
        ]);

        $this
            ->actingAs($guest, 'guard_guest')
            ->getJson('/api/guest/recent-drinks?lang=en')
            ->assertOk()
            ->assertJsonPath('drinks.0.id', $drink->id)
            ->assertJsonPath('drinks.0.name', 'Retention Soda');
    }

    public function test_retention_prune_keeps_active_or_unpaid_order_personal_links(): void
    {
        config(['gdpr.order_personal_data_retention_days' => 7]);

        $guest = Guest::factory()->create(['email_verified_at' => now()]);
        $activeOrder = Order::factory()->create([
            'guest_id' => $guest->id,
            'status' => Order::STATUS_OPEN,
            'recorded_at' => now()->subDays(10),
        ]);
        OrderDetail::factory()->create([
            'order_id' => $activeOrder->id,
            'payment_status' => OrderDetail::PAYMENT_STATUS_PAID,
        ]);
        $pendingOrder = Order::factory()->create([
            'guest_id' => $guest->id,
            'status' => Order::STATUS_SERVED,
            'recorded_at' => now()->subDays(10),
        ]);
        OrderDetail::factory()->create([
            'order_id' => $pendingOrder->id,
            'payment_status' => OrderDetail::PAYMENT_STATUS_PENDING,
        ]);

        $this
            ->artisan('gdpr:retention-prune')
            ->assertExitCode(0);

        $this->assertDatabaseHas('orders', ['id' => $activeOrder->id, 'guest_id' => $guest->id]);
        $this->assertDatabaseHas('orders', ['id' => $pendingOrder->id, 'guest_id' => $guest->id]);
    }
}
