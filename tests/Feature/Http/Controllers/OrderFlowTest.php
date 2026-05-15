<?php

namespace Tests\Feature\Http\Controllers;

use App\Models\Drink;
use App\Models\DrinkCategory;
use App\Models\DrinkUnit;
use App\Models\Employee;
use App\Models\Guest;
use App\Models\Order;
use App\Models\OrderDetail;
use App\Models\TableMember;
use App\Models\TableSession;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class OrderFlowTest extends TestCase
{
    use DatabaseMigrations;

    protected function setUp(): void
    {
        parent::setUp();

        DB::connection()->getSchemaBuilder()->enableForeignKeyConstraints();
    }

    public function test_table_owner_can_place_three_item_order_and_view_active_status(): void
    {
        $guest = Guest::factory()->create([
            'email' => 'guest-order-flow@example.com',
            'email_verified_at' => now(),
            'password' => Hash::make('Password1!'),
        ]);
        $session = TableSession::factory()->create([
            'owner_guest_id' => $guest->id,
        ]);

        $units = [
            $this->createDrinkUnit('Espresso', 1, 'cup', 650),
            $this->createDrinkUnit('Lemonade', 2, 'glass', 900),
            $this->createDrinkUnit('Mineral Water', 1.5, 'bottle', 700),
        ];

        $loginResponse = $this->postJson('/api/guest/login', [
            'email' => $guest->email,
            'password' => 'Password1!',
        ]);

        $loginResponse->assertOk()
            ->assertJsonStructure(['access_token']);

        $cart = [
            [
                'drink_id' => $units[0]->drink_id,
                'quantity' => 1,
                'unit' => 'cup',
                'ordered_quantity' => 2,
            ],
            [
                'drink_id' => $units[1]->drink_id,
                'quantity' => 2,
                'unit' => 'glass',
                'ordered_quantity' => 1,
            ],
            [
                'drink_id' => $units[2]->drink_id,
                'quantity' => 1.5,
                'unit' => 'bottle',
                'ordered_quantity' => 3,
            ],
        ];

        $orderResponse = $this
            ->withToken($loginResponse->json('access_token'))
            ->postJson('/api/guest/orders?lang=en', [
                'cart' => $cart,
                'table_session_id' => $session->id,
            ]);

        $orderId = $orderResponse->json('order.id');

        $orderResponse->assertOk()
            ->assertJsonPath('cart', $cart)
            ->assertJsonPath('order.guest_id', $guest->id)
            ->assertJsonPath('order.table_session_id', $session->id)
            ->assertJsonPath('order.status', Order::STATUS_OPEN)
            ->assertJsonCount(3, 'order.details')
            ->assertJsonPath('order.details.0.payment_status', OrderDetail::PAYMENT_STATUS_PENDING)
            ->assertJsonPath('order.details.0.drink_unit.drink.name', 'Espresso')
            ->assertJsonPath('order.details.1.drink_unit.drink.name', 'Lemonade')
            ->assertJsonPath('order.details.2.drink_unit.drink.name', 'Mineral Water')
            ->assertJsonPath('total', 4300);

        $this->assertDatabaseHas('orders', [
            'id' => $orderId,
            'guest_id' => $guest->id,
            'table_session_id' => $session->id,
            'status' => Order::STATUS_OPEN,
            'served_at' => null,
        ]);

        foreach ($units as $idx => $unit) {
            $this->assertDatabaseHas('order_details', [
                'order_id' => $orderId,
                'drink_unit_id' => $unit->id,
                'ordered_quantity' => $cart[$idx]['ordered_quantity'],
                'unit_price' => $unit->unit_price,
                'discount' => 0,
                'payment_status' => OrderDetail::PAYMENT_STATUS_PENDING,
            ]);
        }

        $statusResponse = $this
            ->withToken($loginResponse->json('access_token'))
            ->getJson('/api/guest/orders/active?lang=en');

        $statusResponse->assertOk()
            ->assertJsonCount(1)
            ->assertJsonPath('0.id', $orderId)
            ->assertJsonPath('0.guest_id', $guest->id)
            ->assertJsonPath('0.table_session_id', $session->id)
            ->assertJsonPath('0.status', Order::STATUS_OPEN)
            ->assertJsonCount(3, '0.details')
            ->assertJsonPath('0.details.0.drink_unit.drink.name', 'Espresso')
            ->assertJsonPath('0.details.1.drink_unit.drink.name', 'Lemonade')
            ->assertJsonPath('0.details.2.drink_unit.drink.name', 'Mineral Water');
    }

    public function test_approved_member_can_order_but_disabled_member_cannot(): void
    {
        $owner = Guest::factory()->create(['email_verified_at' => now()]);
        $member = Guest::factory()->create(['email_verified_at' => now()]);
        $session = TableSession::factory()->create(['owner_guest_id' => $owner->id]);
        $membership = TableMember::factory()->create([
            'table_session_id' => $session->id,
            'guest_id' => $member->id,
            'status' => TableMember::STATUS_APPROVED,
            'can_order' => true,
        ]);
        $cart = [$this->cartItem($this->createDrinkUnit('Cola', 1, 'glass', 800))];

        $this
            ->actingAs($member, 'guard_guest')
            ->postJson('/api/guest/orders?lang=en', [
                'cart' => $cart,
                'table_session_id' => $session->id,
            ])
            ->assertOk()
            ->assertJsonPath('order.table_session_id', $session->id)
            ->assertJsonPath('order.status', Order::STATUS_OPEN)
            ->assertJsonPath('order.details.0.payment_status', OrderDetail::PAYMENT_STATUS_PENDING);

        $membership->can_order = false;
        $membership->save();

        $this
            ->actingAs($member, 'guard_guest')
            ->postJson('/api/guest/orders?lang=en', [
                'cart' => $cart,
                'table_session_id' => $session->id,
            ])
            ->assertStatus(409);
    }

    public function test_guest_order_rejects_missing_forbidden_and_closed_table_contexts(): void
    {
        $guest = Guest::factory()->create(['email_verified_at' => now()]);
        $otherGuest = Guest::factory()->create(['email_verified_at' => now()]);
        $closedSession = TableSession::factory()->create([
            'owner_guest_id' => $guest->id,
            'status' => TableSession::STATUS_CLOSED,
            'closed_at' => now(),
        ]);
        $otherSession = TableSession::factory()->create(['owner_guest_id' => $otherGuest->id]);
        $cart = [$this->cartItem($this->createDrinkUnit('Tea', 1, 'cup', 550))];

        $this
            ->actingAs($guest, 'guard_guest')
            ->postJson('/api/guest/orders?lang=en', ['cart' => $cart])
            ->assertStatus(409);

        $this
            ->actingAs($guest, 'guard_guest')
            ->postJson('/api/guest/orders?lang=en', [
                'cart' => $cart,
                'table_session_id' => $otherSession->id,
            ])
            ->assertForbidden();

        $this
            ->actingAs($guest, 'guard_guest')
            ->postJson('/api/guest/orders?lang=en', [
                'cart' => $cart,
                'table_session_id' => $closedSession->id,
            ])
            ->assertStatus(409);
    }

    public function test_staff_can_create_order_for_guest_table_session(): void
    {
        $staff = Employee::factory()->create([
            'role_code' => Employee::BARTENDER,
            'email_verified_at' => now(),
        ]);
        $owner = Guest::factory()->create(['email_verified_at' => now()]);
        $member = Guest::factory()->create(['email_verified_at' => now()]);
        $session = TableSession::factory()->create(['owner_guest_id' => $owner->id]);
        TableMember::factory()->create([
            'table_session_id' => $session->id,
            'guest_id' => $member->id,
            'status' => TableMember::STATUS_APPROVED,
        ]);
        $cart = [$this->cartItem($this->createDrinkUnit('Soda', 1, 'glass', 700))];

        $response = $this
            ->actingAs($staff, 'guard_employee')
            ->postJson('/api/staff/orders?lang=en', [
                'guest_id' => $member->id,
                'table_session_id' => $session->id,
                'cart' => $cart,
            ]);

        $response->assertOk()
            ->assertJsonPath('order.guest_id', $member->id)
            ->assertJsonPath('order.recorded_by', $staff->id)
            ->assertJsonPath('order.table_session_id', $session->id)
            ->assertJsonPath('order.status', Order::STATUS_OPEN)
            ->assertJsonPath('order.details.0.payment_status', OrderDetail::PAYMENT_STATUS_PENDING);
    }

    public function test_table_order_is_rejected_when_owner_limit_would_be_exceeded(): void
    {
        $owner = Guest::factory()->create(['email_verified_at' => now()]);
        $session = TableSession::factory()->create([
            'owner_guest_id' => $owner->id,
            'owner_spending_limit' => 1000,
        ]);
        $existingUnit = $this->createDrinkUnit('Existing', 1, 'glass', 800);
        $newUnit = $this->createDrinkUnit('New', 1, 'glass', 300);
        $order = Order::factory()->create([
            'guest_id' => $owner->id,
            'table_session_id' => $session->id,
        ]);
        OrderDetail::factory()->create([
            'order_id' => $order->id,
            'drink_unit_id' => $existingUnit->id,
            'ordered_quantity' => 1,
            'unit_price' => 800,
            'payment_status' => OrderDetail::PAYMENT_STATUS_PENDING,
        ]);

        $this
            ->actingAs($owner, 'guard_guest')
            ->postJson('/api/guest/orders?lang=en', [
                'cart' => [$this->cartItem($newUnit)],
                'table_session_id' => $session->id,
            ])
            ->assertStatus(409);
    }

    public function test_lower_staff_config_limit_is_used_when_owner_limit_is_higher(): void
    {
        config(['tables.default_staff_spending_limit' => 1000]);

        $owner = Guest::factory()->create(['email_verified_at' => now()]);
        $session = TableSession::factory()->create([
            'owner_guest_id' => $owner->id,
            'owner_spending_limit' => 5000,
        ]);
        $unit = $this->createDrinkUnit('Limit Soda', 1, 'glass', 1200);

        $this
            ->actingAs($owner, 'guard_guest')
            ->postJson('/api/guest/orders?lang=en', [
                'cart' => [$this->cartItem($unit)],
                'table_session_id' => $session->id,
            ])
            ->assertStatus(409);
    }

    public function test_staff_session_override_replaces_config_limit(): void
    {
        config(['tables.default_staff_spending_limit' => 3000]);

        $owner = Guest::factory()->create(['email_verified_at' => now()]);
        $session = TableSession::factory()->create([
            'owner_guest_id' => $owner->id,
            'staff_spending_limit_override' => 1000,
        ]);
        $unit = $this->createDrinkUnit('Override Soda', 1, 'glass', 1200);

        $this
            ->actingAs($owner, 'guard_guest')
            ->postJson('/api/guest/orders?lang=en', [
                'cart' => [$this->cartItem($unit)],
                'table_session_id' => $session->id,
            ])
            ->assertStatus(409);
    }

    public function test_zero_spending_limits_are_treated_as_unlimited(): void
    {
        config(['tables.default_staff_spending_limit' => 0]);

        $owner = Guest::factory()->create(['email_verified_at' => now()]);
        $session = TableSession::factory()->create([
            'owner_guest_id' => $owner->id,
            'owner_spending_limit' => 0,
            'staff_spending_limit_override' => 0,
        ]);
        $unit = $this->createDrinkUnit('Unlimited Soda', 1, 'glass', 1200);

        $this
            ->actingAs($owner, 'guard_guest')
            ->postJson('/api/guest/orders?lang=en', [
                'cart' => [$this->cartItem($unit)],
                'table_session_id' => $session->id,
            ])
            ->assertOk()
            ->assertJsonPath('order.table_session_id', $session->id);
    }

    public function test_staff_status_flow_updates_machine_status(): void
    {
        $bartender = Employee::factory()->create(['role_code' => Employee::BARTENDER]);
        $waiter = Employee::factory()->create(['role_code' => Employee::WAITER]);
        $order = Order::factory()->create([
            'status' => Order::STATUS_OPEN,
            'recorded_at' => now(),
        ]);

        $this
            ->actingAs($waiter, 'guard_employee')
            ->postJson("/api/staff/orders/assign/{$order->id}")
            ->assertStatus(409);

        $this
            ->actingAs($bartender, 'guard_employee')
            ->postJson("/api/staff/orders/assign/{$order->id}")
            ->assertOk();
        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'made_by' => $bartender->id,
            'status' => Order::STATUS_PREPARING,
        ]);

        $this
            ->actingAs($bartender, 'guard_employee')
            ->postJson("/api/staff/orders/done/{$order->id}")
            ->assertOk();
        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'status' => Order::STATUS_READY,
        ]);

        $this
            ->actingAs($waiter, 'guard_employee')
            ->postJson("/api/staff/orders/assign/{$order->id}")
            ->assertOk();

        $this
            ->actingAs($waiter, 'guard_employee')
            ->postJson("/api/staff/orders/done/{$order->id}")
            ->assertOk();
        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'status' => Order::STATUS_SERVED,
        ]);
    }

    private function createDrinkUnit(string $drinkName, float $quantity, string $unit, int $price): DrinkUnit
    {
        $category = DrinkCategory::factory()->create();
        $drink = Drink::factory()->create([
            'category_id' => $category->id,
            'name_en' => $drinkName,
            'name_hu' => $drinkName,
            'active' => true,
        ]);

        return DrinkUnit::factory()->create([
            'drink_id' => $drink->id,
            'quantity' => $quantity,
            'unit_en' => $unit,
            'unit_hu' => $unit,
            'unit_price' => $price,
            'active' => true,
        ]);
    }

    private function cartItem(DrinkUnit $unit, int $orderedQuantity = 1): array
    {
        return [
            'drink_id' => $unit->drink_id,
            'quantity' => $unit->quantity,
            'unit' => $unit->unit_en,
            'ordered_quantity' => $orderedQuantity,
        ];
    }
}
