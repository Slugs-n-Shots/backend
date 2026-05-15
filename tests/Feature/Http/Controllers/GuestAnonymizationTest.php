<?php

namespace Tests\Feature\Http\Controllers;

use App\Models\Guest;
use App\Models\GuestRecentDrink;
use App\Models\GdprAuditEvent;
use App\Models\Order;
use App\Models\OrderDetail;
use App\Models\PaymentAttempt;
use App\Models\PaymentEvent;
use App\Models\Receipt;
use App\Models\TableMember;
use App\Models\TableSession;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class GuestAnonymizationTest extends TestCase
{
    use DatabaseMigrations;

    protected function setUp(): void
    {
        parent::setUp();

        DB::connection()->getSchemaBuilder()->enableForeignKeyConstraints();
    }

    public function test_guest_can_check_anonymization_availability(): void
    {
        $guest = Guest::factory()->create(['email_verified_at' => now()]);

        $this
            ->actingAs($guest, 'guard_guest')
            ->getJson('/api/guest/me/anonymize/check')
            ->assertOk()
            ->assertJson([
                'can_anonymize' => true,
                'blocking_reasons' => [],
            ]);
    }

    public function test_guest_can_anonymize_own_account_without_touching_financial_history(): void
    {
        $guest = Guest::factory()->create([
            'first_name' => 'Éva',
            'middle_name' => 'Mária',
            'last_name' => 'Teszt',
            'email' => 'eva@example.com',
            'email_verified_at' => now(),
            'picture' => 'eva.jpg',
            'is_over_18' => true,
            'age_verified_at' => now()->subDay(),
            'phone' => '+36 30 123 4567',
            'address' => '1117 Budapest, Teszt utca 1.',
            'birth_date' => '1990-01-02',
        ]);
        $receipt = Receipt::factory()->create(['guest_id' => $guest->id]);
        $order = Order::factory()->create([
            'guest_id' => $guest->id,
            'status' => Order::STATUS_SERVED,
        ]);
        $detail = OrderDetail::factory()->create([
            'order_id' => $order->id,
            'payment_status' => OrderDetail::PAYMENT_STATUS_PAID,
            'receipt_id' => $receipt->id,
        ]);
        $recentDrink = GuestRecentDrink::create([
            'guest_id' => $guest->id,
            'drink_id' => $detail->drinkUnit->drink_id,
            'last_ordered_at' => now()->subDay(),
            'order_count' => 1,
        ]);
        $paymentAttempt = PaymentAttempt::factory()->create([
            'guest_id' => $guest->id,
            'receipt_id' => $receipt->id,
            'status' => PaymentAttempt::STATUS_SUCCEEDED,
        ]);
        $paymentEvent = PaymentEvent::factory()->create([
            'payment_attempt_id' => $paymentAttempt->id,
            'event_type' => PaymentEvent::TYPE_RECEIPT_CREATED,
            'actor_guest_id' => $guest->id,
            'receipt_id' => $receipt->id,
        ]);

        $this
            ->actingAs($guest, 'guard_guest')
            ->postJson('/api/guest/me/anonymize', ['confirm' => true])
            ->assertOk()
            ->assertJson(['message' => __('The account has been anonymized.')]);

        $freshGuest = $guest->fresh();
        $this->assertSame('Anonimizált', $freshGuest->first_name);
        $this->assertNull($freshGuest->middle_name);
        $this->assertSame('Vendég', $freshGuest->last_name);
        $this->assertSame("deleted-guest-{$guest->id}@anonymized.local", $freshGuest->email);
        $this->assertNull($freshGuest->picture);
        $this->assertFalse($freshGuest->active);
        $this->assertNull($freshGuest->email_verified_at);
        $this->assertNotNull($freshGuest->anonymized_at);
        $this->assertFalse(Hash::check('Password1!', $freshGuest->password));
        $this->assertTrue($freshGuest->is_over_18);
        $this->assertNull($freshGuest->age_verified_at);
        $this->assertNull($freshGuest->phone);
        $this->assertNull($freshGuest->address);
        $this->assertNull($freshGuest->birth_date);

        $this->assertDatabaseHas('orders', ['id' => $order->id, 'guest_id' => null]);
        $this->assertDatabaseHas('order_details', ['id' => $detail->id, 'receipt_id' => $receipt->id]);
        $this->assertDatabaseHas('receipts', ['id' => $receipt->id, 'guest_id' => null]);
        $this->assertDatabaseHas('payment_attempts', ['id' => $paymentAttempt->id, 'guest_id' => null]);
        $this->assertDatabaseHas('payment_events', ['id' => $paymentEvent->id, 'actor_guest_id' => null]);
        $this->assertDatabaseMissing('guest_recent_drinks', ['id' => $recentDrink->id]);
        $this->assertDatabaseHas('gdpr_audit_events', [
            'guest_id' => $guest->id,
            'actor_guest_id' => $guest->id,
            'event_type' => GdprAuditEvent::TYPE_ANONYMIZATION_COMPLETED,
            'status' => 'completed',
            'masked_email' => "deleted-guest-{$guest->id}@anonymized.local",
            'blocking_reason_count' => 0,
        ]);

        $this
            ->postJson('/api/guest/login', [
                'email' => "deleted-guest-{$guest->id}@anonymized.local",
                'password' => 'Password1!',
            ])
            ->assertUnauthorized();
    }

    public function test_anonymization_keeps_accounting_receipt_customer_snapshot(): void
    {
        $guest = Guest::factory()->create([
            'first_name' => 'Éva',
            'last_name' => 'Teszt',
            'email' => 'eva@example.com',
            'email_verified_at' => now(),
        ]);
        $receipt = Receipt::factory()->create([
            'guest_id' => $guest->id,
            'paid_for' => null,
            'customer_type' => 'company',
            'customer_name' => 'Teszt Partner Kft.',
            'customer_address' => '1117 Budapest, Céges út 2.',
            'customer_tax_number' => '87654321-2-43',
            'customer_email' => 'szamla@example.com',
        ]);
        $order = Order::factory()->create([
            'guest_id' => $guest->id,
            'status' => Order::STATUS_SERVED,
        ]);
        OrderDetail::factory()->create([
            'order_id' => $order->id,
            'payment_status' => OrderDetail::PAYMENT_STATUS_PAID,
            'receipt_id' => $receipt->id,
        ]);

        $this
            ->actingAs($guest, 'guard_guest')
            ->postJson('/api/guest/me/anonymize', ['confirm' => true])
            ->assertOk();

        $this->assertDatabaseHas('receipts', [
            'id' => $receipt->id,
            'guest_id' => null,
            'customer_type' => 'company',
            'customer_name' => 'Teszt Partner Kft.',
            'customer_address' => '1117 Budapest, Céges út 2.',
            'customer_tax_number' => '87654321-2-43',
            'customer_email' => 'szamla@example.com',
        ]);
    }

    public function test_confirm_true_is_required_for_anonymization(): void
    {
        $guest = Guest::factory()->create(['email_verified_at' => now()]);

        $this
            ->actingAs($guest, 'guard_guest')
            ->postJson('/api/guest/me/anonymize', ['confirm' => false])
            ->assertUnprocessable();
    }

    public function test_guest_cannot_anonymize_with_pending_payment(): void
    {
        $guest = Guest::factory()->create(['email_verified_at' => now()]);
        $order = Order::factory()->create([
            'guest_id' => $guest->id,
            'status' => Order::STATUS_SERVED,
        ]);
        OrderDetail::factory()->create([
            'order_id' => $order->id,
            'payment_status' => OrderDetail::PAYMENT_STATUS_PENDING,
        ]);

        $this
            ->actingAs($guest, 'guard_guest')
            ->postJson('/api/guest/me/anonymize', ['confirm' => true])
            ->assertStatus(409)
            ->assertJsonPath('blocking_reasons.0.code', 'pending_payment');

        $event = GdprAuditEvent::where('guest_id', $guest->id)->firstOrFail();
        $this->assertSame(GdprAuditEvent::TYPE_ANONYMIZATION_BLOCKED, $event->event_type);
        $this->assertSame('blocked', $event->status);
        $this->assertSame("guest-{$guest->id}@masked.local", $event->masked_email);
        $this->assertSame(1, $event->blocking_reason_count);
        $this->assertSame(['pending_payment'], $event->blocking_reason_codes);
    }

    public function test_guest_cannot_anonymize_as_open_table_owner(): void
    {
        $guest = Guest::factory()->create(['email_verified_at' => now()]);
        TableSession::factory()->create([
            'owner_guest_id' => $guest->id,
            'status' => TableSession::STATUS_OPEN,
        ]);

        $this
            ->actingAs($guest, 'guard_guest')
            ->getJson('/api/guest/me/anonymize/check')
            ->assertOk()
            ->assertJsonPath('can_anonymize', false)
            ->assertJsonPath('blocking_reasons.0.code', 'open_table_owner');
    }

    public function test_guest_cannot_anonymize_with_pending_or_approved_open_table_membership(): void
    {
        $owner = Guest::factory()->create(['email_verified_at' => now()]);
        $member = Guest::factory()->create(['email_verified_at' => now()]);
        $session = TableSession::factory()->create([
            'owner_guest_id' => $owner->id,
            'status' => TableSession::STATUS_OPEN,
        ]);
        TableMember::factory()->create([
            'table_session_id' => $session->id,
            'guest_id' => $member->id,
            'status' => TableMember::STATUS_APPROVED,
        ]);

        $this
            ->actingAs($member, 'guard_guest')
            ->postJson('/api/guest/me/anonymize', ['confirm' => true])
            ->assertStatus(409)
            ->assertJsonPath('blocking_reasons.0.code', 'open_table_membership');
    }

    public function test_guest_cannot_anonymize_with_active_order(): void
    {
        $guest = Guest::factory()->create(['email_verified_at' => now()]);
        Order::factory()->create([
            'guest_id' => $guest->id,
            'status' => Order::STATUS_PREPARING,
        ]);

        $this
            ->actingAs($guest, 'guard_guest')
            ->postJson('/api/guest/me/anonymize', ['confirm' => true])
            ->assertStatus(409)
            ->assertJsonPath('blocking_reasons.0.code', 'active_order');
    }

    public function test_already_anonymized_guest_cannot_anonymize_again(): void
    {
        $guest = Guest::factory()->create([
            'email_verified_at' => now(),
            'anonymized_at' => now(),
        ]);

        $this
            ->actingAs($guest, 'guard_guest')
            ->postJson('/api/guest/me/anonymize', ['confirm' => true])
            ->assertStatus(409)
            ->assertJsonPath('blocking_reasons.0.code', 'already_anonymized');
    }
}
