<?php

namespace Tests\Feature\Http\Controllers;

use App\Models\GdprAuditEvent;
use App\Models\Guest;
use App\Models\GuestRecentDrink;
use App\Models\Order;
use App\Models\OrderDetail;
use App\Models\PaymentAttempt;
use App\Models\PaymentEvent;
use App\Models\Receipt;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Tests\TestCase;

class GuestDataExportTest extends TestCase
{
    use DatabaseMigrations;

    public function test_guest_can_export_own_structured_personal_data(): void
    {
        $guest = Guest::factory()->create([
            'first_name' => 'Éva',
            'middle_name' => 'Mária',
            'last_name' => 'Teszt',
            'email' => 'eva@example.com',
            'email_verified_at' => now()->subDay(),
            'is_over_18' => true,
            'age_verified_at' => now()->subDay(),
            'birth_date' => '1990-01-02',
            'phone' => '+36 30 123 4567',
            'address' => '1117 Budapest, Teszt utca 1.',
        ]);
        $otherGuest = Guest::factory()->create(['email' => 'other@example.com']);

        $order = Order::factory()->create([
            'guest_id' => $guest->id,
            'table' => 'A12',
            'status' => Order::STATUS_SERVED,
        ]);
        $receipt = Receipt::factory()->create([
            'guest_id' => $guest->id,
            'serno' => 'R20260001',
            'customer_name' => 'Éva Teszt',
            'customer_email' => 'eva@example.com',
        ]);
        $detail = OrderDetail::factory()->create([
            'order_id' => $order->id,
            'receipt_id' => $receipt->id,
            'ordered_quantity' => 2,
            'unit_price' => 1200,
            'payment_status' => OrderDetail::PAYMENT_STATUS_PAID,
        ]);
        $paymentAttempt = PaymentAttempt::factory()->create([
            'guest_id' => $guest->id,
            'receipt_id' => $receipt->id,
            'status' => PaymentAttempt::STATUS_SUCCEEDED,
            'amount' => 2400,
        ]);
        $paymentEvent = PaymentEvent::factory()->create([
            'payment_attempt_id' => $paymentAttempt->id,
            'event_type' => PaymentEvent::TYPE_RECEIPT_CREATED,
            'actor_guest_id' => $guest->id,
            'receipt_id' => $receipt->id,
        ]);
        $recentDrink = GuestRecentDrink::create([
            'guest_id' => $guest->id,
            'drink_id' => $detail->drinkUnit->drink_id,
            'last_ordered_at' => now(),
            'order_count' => 1,
        ]);
        $auditEvent = GdprAuditEvent::create([
            'event_type' => GdprAuditEvent::TYPE_ANONYMIZATION_REQUESTED,
            'guest_id' => $guest->id,
            'actor_guest_id' => $guest->id,
            'status' => 'requested',
            'masked_email' => "guest-{$guest->id}@masked.local",
            'blocking_reason_count' => 0,
            'blocking_reason_codes' => [],
            'created_at' => now(),
        ]);

        Order::factory()->create(['guest_id' => $otherGuest->id]);
        Receipt::factory()->create(['guest_id' => $otherGuest->id, 'customer_email' => 'other@example.com']);
        PaymentAttempt::factory()->create(['guest_id' => $otherGuest->id]);

        $this
            ->actingAs($guest, 'guard_guest')
            ->getJson('/api/guest/me/export')
            ->assertOk()
            ->assertJsonPath('guest.email', 'eva@example.com')
            ->assertJsonPath('guest.first_name', 'Éva')
            ->assertJsonPath('guest.birth_date', '1990-01-02')
            ->assertJsonPath('orders.0.id', $order->id)
            ->assertJsonPath('orders.0.details.0.id', $detail->id)
            ->assertJsonPath('receipts.0.id', $receipt->id)
            ->assertJsonPath('receipts.0.customer_email', 'eva@example.com')
            ->assertJsonPath('payment_attempts.0.id', $paymentAttempt->id)
            ->assertJsonPath('payment_attempts.0.events.0.id', $paymentEvent->id)
            ->assertJsonPath('recent_drinks.0.id', $recentDrink->id)
            ->assertJsonPath('gdpr_audit_events.0.id', $auditEvent->id)
            ->assertJsonMissing(['email' => 'other@example.com'])
            ->assertJsonStructure([
                'exported_at',
                'guest' => [
                    'id',
                    'first_name',
                    'middle_name',
                    'last_name',
                    'email',
                    'email_verified_at',
                    'phone',
                    'address',
                    'birth_date',
                    'is_over_18',
                    'age_verified_at',
                    'anonymized_at',
                    'created_at',
                    'updated_at',
                ],
                'orders' => [
                    [
                        'id',
                        'status',
                        'table',
                        'details',
                    ],
                ],
                'receipts',
                'payment_attempts',
                'recent_drinks',
                'gdpr_audit_events',
            ]);
    }

    public function test_guest_export_requires_guest_authentication(): void
    {
        $this
            ->getJson('/api/guest/me/export')
            ->assertUnauthorized();
    }
}
