<?php

namespace Tests\Feature\Http\Controllers;

use App\Models\DrinkUnit;
use App\Models\Employee;
use App\Models\Guest;
use App\Models\Order;
use App\Models\OrderDetail;
use App\Models\PaymentAttempt;
use App\Models\PaymentEvent;
use App\Models\Receipt;
use App\Models\TableMember;
use App\Models\TableSession;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class PaymentFlowTest extends TestCase
{
    use DatabaseMigrations;

    protected function setUp(): void
    {
        parent::setUp();

        DB::connection()->getSchemaBuilder()->enableForeignKeyConstraints();
    }

    public function test_guest_can_pay_own_pending_order_details(): void
    {
        $guest = Guest::factory()->create(['email_verified_at' => now()]);
        $order = Order::factory()->create(['guest_id' => $guest->id]);
        $first = $this->detail($order, 2, 600);
        $second = $this->detail($order, 1, 800);

        $response = $this
            ->actingAs($guest, 'guard_guest')
            ->postJson('/api/guest/payments', [
                'order_detail_ids' => [$first->id, $second->id],
                'payment_method' => Receipt::PAYMENT_METHOD_CARD,
            ]);

        $receiptId = $response->json('receipt.id');
        $paymentId = $response->json('payment.id');

        $response->assertOk()
            ->assertJsonPath('payment.status', PaymentAttempt::STATUS_SUCCEEDED)
            ->assertJsonPath('payment.payment_method', Receipt::PAYMENT_METHOD_CARD)
            ->assertJsonPath('payment.amount', 2000)
            ->assertJsonPath('payment.currency', 'HUF')
            ->assertJsonPath('payment.receipt_id', $receiptId)
            ->assertJsonPath('receipt.id', $receiptId)
            ->assertJsonPath('receipt.guest_id', $guest->id)
            ->assertJsonPath('receipt.payment_method', Receipt::PAYMENT_METHOD_CARD);

        foreach ([$first, $second] as $detail) {
            $this->assertDatabaseHas('order_details', [
                'id' => $detail->id,
                'payment_status' => OrderDetail::PAYMENT_STATUS_PAID,
                'receipt_id' => $receiptId,
            ]);
        }

        $this->assertDatabaseHas('payment_attempts', [
            'id' => $paymentId,
            'guest_id' => $guest->id,
            'status' => PaymentAttempt::STATUS_SUCCEEDED,
            'amount' => 2000,
            'receipt_id' => $receiptId,
        ]);
        $this->assertDatabaseHas('payment_events', [
            'payment_attempt_id' => $paymentId,
            'event_type' => PaymentEvent::TYPE_CREATED,
        ]);
        $this->assertDatabaseHas('payment_events', [
            'payment_attempt_id' => $paymentId,
            'event_type' => PaymentEvent::TYPE_PAYMENT_SUCCEEDED,
        ]);
        $this->assertDatabaseHas('payment_events', [
            'payment_attempt_id' => $paymentId,
            'event_type' => PaymentEvent::TYPE_RECEIPT_CREATED,
            'receipt_id' => $receiptId,
        ]);
    }

    public function test_guest_cannot_pay_other_guests_order_detail(): void
    {
        $guest = Guest::factory()->create(['email_verified_at' => now()]);
        $other = Guest::factory()->create(['email_verified_at' => now()]);
        $order = Order::factory()->create(['guest_id' => $other->id]);
        $detail = $this->detail($order, 1, 800);

        $this
            ->actingAs($guest, 'guard_guest')
            ->postJson('/api/guest/payments', [
                'order_detail_ids' => [$detail->id],
                'payment_method' => Receipt::PAYMENT_METHOD_CARD,
            ])
            ->assertForbidden();

        $this->assertDatabaseHas('order_details', [
            'id' => $detail->id,
            'payment_status' => OrderDetail::PAYMENT_STATUS_PENDING,
            'receipt_id' => null,
        ]);
    }

    public function test_already_paid_order_detail_cannot_be_paid_again(): void
    {
        $guest = Guest::factory()->create(['email_verified_at' => now()]);
        $receipt = Receipt::factory()->create(['guest_id' => $guest->id, 'paid_for' => null]);
        $order = Order::factory()->create(['guest_id' => $guest->id]);
        $detail = $this->detail($order, 1, 800, [
            'payment_status' => OrderDetail::PAYMENT_STATUS_PAID,
            'receipt_id' => $receipt->id,
        ]);

        $this
            ->actingAs($guest, 'guard_guest')
            ->postJson('/api/guest/payments', [
                'order_detail_ids' => [$detail->id],
                'payment_method' => Receipt::PAYMENT_METHOD_CARD,
            ])
            ->assertStatus(409);
    }

    public function test_failed_simulated_payment_keeps_details_pending_without_receipt(): void
    {
        $guest = Guest::factory()->create(['email_verified_at' => now()]);
        $order = Order::factory()->create(['guest_id' => $guest->id]);
        $detail = $this->detail($order, 1, 800);

        $response = $this
            ->actingAs($guest, 'guard_guest')
            ->postJson('/api/guest/payments', [
                'order_detail_ids' => [$detail->id],
                'payment_method' => Receipt::PAYMENT_METHOD_CARD,
                'simulate_result' => PaymentAttempt::STATUS_FAILED,
            ]);

        $paymentId = $response->json('payment.id');

        $response->assertOk()
            ->assertJsonPath('payment.status', PaymentAttempt::STATUS_FAILED)
            ->assertJsonPath('payment.amount', 800)
            ->assertJsonPath('payment.receipt_id', null)
            ->assertJsonPath('receipt', null);

        $this->assertDatabaseHas('order_details', [
            'id' => $detail->id,
            'payment_status' => OrderDetail::PAYMENT_STATUS_PENDING,
            'receipt_id' => null,
        ]);
        $this->assertDatabaseHas('payment_events', [
            'payment_attempt_id' => $paymentId,
            'event_type' => PaymentEvent::TYPE_PAYMENT_FAILED,
        ]);
    }

    public function test_approved_table_member_can_pay_table_order_details(): void
    {
        $owner = Guest::factory()->create(['email_verified_at' => now()]);
        $member = Guest::factory()->create(['email_verified_at' => now()]);
        $session = TableSession::factory()->create(['owner_guest_id' => $owner->id]);
        TableMember::factory()->create([
            'table_session_id' => $session->id,
            'guest_id' => $member->id,
            'status' => TableMember::STATUS_APPROVED,
        ]);
        $ownerOrder = Order::factory()->create([
            'guest_id' => $owner->id,
            'table_session_id' => $session->id,
        ]);
        $detail = $this->detail($ownerOrder, 1, 1200);

        $response = $this
            ->actingAs($member, 'guard_guest')
            ->postJson('/api/guest/tables/current/payments', [
                'order_detail_ids' => [$detail->id],
                'payment_method' => Receipt::PAYMENT_METHOD_CARD,
            ]);

        $receiptId = $response->json('receipt.id');

        $response->assertOk()
            ->assertJsonPath('payment.status', PaymentAttempt::STATUS_SUCCEEDED)
            ->assertJsonPath('payment.guest_id', $member->id)
            ->assertJsonPath('payment.table_session_id', $session->id)
            ->assertJsonPath('payment.amount', 1200)
            ->assertJsonPath('receipt.guest_id', $member->id)
            ->assertJsonPath('receipt.table_session_id', $session->id);

        $this->assertDatabaseHas('order_details', [
            'id' => $detail->id,
            'payment_status' => OrderDetail::PAYMENT_STATUS_PAID,
            'receipt_id' => $receiptId,
        ]);
    }

    public function test_guest_cannot_pay_table_details_without_membership(): void
    {
        $owner = Guest::factory()->create(['email_verified_at' => now()]);
        $intruder = Guest::factory()->create(['email_verified_at' => now()]);
        $session = TableSession::factory()->create(['owner_guest_id' => $owner->id]);
        $order = Order::factory()->create([
            'guest_id' => $owner->id,
            'table_session_id' => $session->id,
        ]);
        $detail = $this->detail($order, 1, 1200);

        $this
            ->actingAs($intruder, 'guard_guest')
            ->postJson('/api/guest/tables/current/payments', [
                'order_detail_ids' => [$detail->id],
                'payment_method' => Receipt::PAYMENT_METHOD_CARD,
            ])
            ->assertForbidden();
    }

    public function test_owner_closing_payment_pays_all_pending_table_details(): void
    {
        $owner = Guest::factory()->create(['email_verified_at' => now()]);
        $member = Guest::factory()->create(['email_verified_at' => now()]);
        $session = TableSession::factory()->create(['owner_guest_id' => $owner->id]);
        TableMember::factory()->create([
            'table_session_id' => $session->id,
            'guest_id' => $member->id,
            'status' => TableMember::STATUS_APPROVED,
        ]);
        $ownerDetail = $this->detail(Order::factory()->create([
            'guest_id' => $owner->id,
            'table_session_id' => $session->id,
        ]), 1, 900);
        $memberDetail = $this->detail(Order::factory()->create([
            'guest_id' => $member->id,
            'table_session_id' => $session->id,
        ]), 2, 700);

        $response = $this
            ->actingAs($owner, 'guard_guest')
            ->postJson('/api/guest/tables/current/closing-payment', [
                'payment_method' => Receipt::PAYMENT_METHOD_CASH,
            ]);

        $receiptId = $response->json('receipt.id');

        $response->assertOk()
            ->assertJsonPath('payment.status', PaymentAttempt::STATUS_SUCCEEDED)
            ->assertJsonPath('payment.amount', 2300)
            ->assertJsonPath('payment.table_session_id', $session->id);

        foreach ([$ownerDetail, $memberDetail] as $detail) {
            $this->assertDatabaseHas('order_details', [
                'id' => $detail->id,
                'payment_status' => OrderDetail::PAYMENT_STATUS_PAID,
                'receipt_id' => $receiptId,
            ]);
        }
    }

    public function test_non_owner_cannot_make_closing_payment(): void
    {
        $owner = Guest::factory()->create(['email_verified_at' => now()]);
        $member = Guest::factory()->create(['email_verified_at' => now()]);
        $session = TableSession::factory()->create(['owner_guest_id' => $owner->id]);
        TableMember::factory()->create([
            'table_session_id' => $session->id,
            'guest_id' => $member->id,
            'status' => TableMember::STATUS_APPROVED,
        ]);

        $this
            ->actingAs($member, 'guard_guest')
            ->postJson('/api/guest/tables/current/closing-payment', [
                'payment_method' => Receipt::PAYMENT_METHOD_CASH,
            ])
            ->assertForbidden();
    }

    public function test_guest_can_view_own_receipt_after_payment(): void
    {
        $guest = Guest::factory()->create(['email_verified_at' => now()]);
        $order = Order::factory()->create(['guest_id' => $guest->id]);
        $detail = $this->detail($order, 1, 800);

        $payment = $this
            ->actingAs($guest, 'guard_guest')
            ->postJson('/api/guest/payments', [
                'order_detail_ids' => [$detail->id],
                'payment_method' => Receipt::PAYMENT_METHOD_CARD,
            ]);

        $receiptId = $payment->json('receipt.id');

        $this
            ->actingAs($guest, 'guard_guest')
            ->getJson("/api/guest/receipts/{$receiptId}")
            ->assertOk()
            ->assertJsonPath('id', $receiptId)
            ->assertJsonPath('guest_id', $guest->id)
            ->assertJsonCount(1, 'details');
    }

    public function test_guest_cannot_view_other_guest_receipt(): void
    {
        $guest = Guest::factory()->create(['email_verified_at' => now()]);
        $other = Guest::factory()->create(['email_verified_at' => now()]);
        $receipt = Receipt::factory()->create(['guest_id' => $other->id, 'paid_for' => null]);

        $this
            ->actingAs($guest, 'guard_guest')
            ->getJson("/api/guest/receipts/{$receipt->id}")
            ->assertForbidden();
    }

    public function test_staff_can_mark_pending_order_details_paid(): void
    {
        $staff = Employee::factory()->create(['role_code' => Employee::WAITER]);
        $guest = Guest::factory()->create(['email_verified_at' => now()]);
        $order = Order::factory()->create(['guest_id' => $guest->id]);
        $detail = $this->detail($order, 2, 900);

        $response = $this
            ->actingAs($staff, 'guard_employee')
            ->postJson('/api/staff/order-details/mark-paid', [
                'order_detail_ids' => [$detail->id],
                'memo' => 'Pultnál rendezve',
            ]);

        $receiptId = $response->json('receipt.id');
        $paymentId = $response->json('payment.id');

        $response->assertOk()
            ->assertJsonPath('payment.status', PaymentAttempt::STATUS_SUCCEEDED)
            ->assertJsonPath('payment.employee_id', $staff->id)
            ->assertJsonPath('payment.guest_id', null)
            ->assertJsonPath('payment.payment_method', PaymentAttempt::METHOD_ADMIN_MARKED_PAID)
            ->assertJsonPath('payment.amount', 1800)
            ->assertJsonPath('receipt.guest_id', $guest->id)
            ->assertJsonPath('receipt.paid_for', $staff->id);

        $this->assertDatabaseHas('order_details', [
            'id' => $detail->id,
            'payment_status' => OrderDetail::PAYMENT_STATUS_PAID,
            'receipt_id' => $receiptId,
        ]);
        $this->assertDatabaseHas('payment_events', [
            'payment_attempt_id' => $paymentId,
            'event_type' => PaymentEvent::TYPE_MARKED_PAID_BY_ADMIN,
            'actor_employee_id' => $staff->id,
        ]);
    }

    public function test_only_admin_can_mark_closed_table_session_details_paid(): void
    {
        $waiter = Employee::factory()->create(['role_code' => Employee::WAITER]);
        $admin = Employee::factory()->create(['role_code' => Employee::ADMIN]);
        $guest = Guest::factory()->create(['email_verified_at' => now()]);
        $session = TableSession::factory()->create([
            'status' => TableSession::STATUS_CLOSED,
            'closed_at' => now(),
            'owner_guest_id' => $guest->id,
        ]);
        $order = Order::factory()->create([
            'guest_id' => $guest->id,
            'table_session_id' => $session->id,
        ]);
        $detail = $this->detail($order, 1, 1200);

        $this
            ->actingAs($waiter, 'guard_employee')
            ->postJson('/api/staff/order-details/mark-paid', [
                'order_detail_ids' => [$detail->id],
                'memo' => 'Utólagos rendezés',
            ])
            ->assertForbidden();

        $response = $this
            ->actingAs($admin, 'guard_employee')
            ->postJson('/api/staff/order-details/mark-paid', [
                'order_detail_ids' => [$detail->id],
                'memo' => 'Utólagos admin rendezés lezárt asztalnál',
            ]);

        $paymentId = $response->json('payment.id');

        $response->assertOk()
            ->assertJsonPath('payment.status', PaymentAttempt::STATUS_SUCCEEDED)
            ->assertJsonPath('payment.employee_id', $admin->id)
            ->assertJsonPath('payment.table_session_id', $session->id)
            ->assertJsonPath('receipt.table_session_id', $session->id);

        $this->assertDatabaseHas('order_details', [
            'id' => $detail->id,
            'payment_status' => OrderDetail::PAYMENT_STATUS_PAID,
        ]);
        $this->assertDatabaseHas('payment_events', [
            'payment_attempt_id' => $paymentId,
            'event_type' => PaymentEvent::TYPE_MARKED_PAID_BY_ADMIN,
            'actor_employee_id' => $admin->id,
        ]);
    }

    private function detail(Order $order, int $quantity, int $unitPrice, array $overrides = []): OrderDetail
    {
        return OrderDetail::factory()->create(array_merge([
            'order_id' => $order->id,
            'drink_unit_id' => DrinkUnit::factory(),
            'ordered_quantity' => $quantity,
            'unit_price' => $unitPrice,
            'payment_status' => OrderDetail::PAYMENT_STATUS_PENDING,
            'receipt_id' => null,
        ], $overrides));
    }
}
