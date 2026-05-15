<?php

namespace App\Services;

use App\Models\GdprAuditEvent;
use App\Models\Guest;
use App\Models\GuestRecentDrink;
use App\Models\Order;
use App\Models\OrderDetail;
use App\Models\PaymentAttempt;
use App\Models\PaymentEvent;
use App\Models\Receipt;
use Illuminate\Support\Carbon;

class GuestDataExportService
{
    public function export(Guest $guest): array
    {
        $orders = Order::query()
            ->where('guest_id', $guest->id)
            ->with('details')
            ->orderBy('id')
            ->get();

        $receipts = Receipt::query()
            ->where('guest_id', $guest->id)
            ->with('details')
            ->orderBy('id')
            ->get();

        $paymentAttempts = PaymentAttempt::query()
            ->where('guest_id', $guest->id)
            ->with('events')
            ->orderBy('id')
            ->get();

        $gdprAuditEvents = GdprAuditEvent::query()
            ->where('guest_id', $guest->id)
            ->orWhere('actor_guest_id', $guest->id)
            ->orderBy('id')
            ->get();
        $recentDrinks = GuestRecentDrink::query()
            ->where('guest_id', $guest->id)
            ->orderByDesc('last_ordered_at')
            ->orderByDesc('id')
            ->get();

        return [
            'exported_at' => now()->toJSON(),
            'guest' => $this->guest($guest),
            'orders' => $orders->map(fn (Order $order) => $this->order($order))->values(),
            'receipts' => $receipts->map(fn (Receipt $receipt) => $this->receipt($receipt))->values(),
            'payment_attempts' => $paymentAttempts
                ->map(fn (PaymentAttempt $paymentAttempt) => $this->paymentAttempt($paymentAttempt))
                ->values(),
            'recent_drinks' => $recentDrinks
                ->map(fn (GuestRecentDrink $recentDrink) => $this->recentDrink($recentDrink))
                ->values(),
            'gdpr_audit_events' => $gdprAuditEvents
                ->map(fn (GdprAuditEvent $event) => $this->gdprAuditEvent($event))
                ->values(),
        ];
    }

    private function guest(Guest $guest): array
    {
        return [
            'id' => $guest->id,
            'first_name' => $guest->first_name,
            'middle_name' => $guest->middle_name,
            'last_name' => $guest->last_name,
            'name' => $guest->name,
            'email' => $guest->email,
            'email_verified_at' => $this->dateTime($guest->email_verified_at),
            'table' => $guest->table,
            'reservee' => $guest->reservee,
            'picture' => $guest->picture,
            'active' => $guest->active,
            'is_over_18' => $guest->is_over_18,
            'age_verified_at' => $this->dateTime($guest->age_verified_at),
            'birth_date' => $this->date($guest->birth_date),
            'phone' => $guest->phone,
            'address' => $guest->address,
            'anonymized_at' => $this->dateTime($guest->anonymized_at),
            'created_at' => $this->dateTime($guest->created_at),
            'updated_at' => $this->dateTime($guest->updated_at),
        ];
    }

    private function order(Order $order): array
    {
        return [
            'id' => $order->id,
            'guest_id' => $order->guest_id,
            'recorded_by' => $order->recorded_by,
            'recorded_at' => $this->dateTime($order->recorded_at),
            'made_by' => $order->made_by,
            'made_at' => $this->dateTime($order->made_at),
            'served_by' => $order->served_by,
            'served_at' => $this->dateTime($order->served_at),
            'table' => $order->table,
            'status' => $order->status,
            'table_session_id' => $order->table_session_id,
            'created_at' => $this->dateTime($order->created_at),
            'updated_at' => $this->dateTime($order->updated_at),
            'details' => $order->details
                ->map(fn (OrderDetail $detail) => $this->orderDetail($detail))
                ->values(),
        ];
    }

    private function orderDetail(OrderDetail $detail): array
    {
        return [
            'id' => $detail->id,
            'order_id' => $detail->order_id,
            'drink_unit_id' => $detail->drink_unit_id,
            'ordered_quantity' => $detail->ordered_quantity,
            'promo_id' => $detail->promo_id,
            'unit_price' => $detail->unit_price,
            'discount' => $detail->discount,
            'receipt_id' => $detail->receipt_id,
            'payment_status' => $detail->payment_status,
            'created_at' => $this->dateTime($detail->created_at),
            'updated_at' => $this->dateTime($detail->updated_at),
        ];
    }

    private function receipt(Receipt $receipt): array
    {
        return [
            'id' => $receipt->id,
            'serno' => $receipt->serno,
            'guest_id' => $receipt->guest_id,
            'issued_at' => $this->dateTime($receipt->issued_at),
            'paid_for' => $receipt->paid_for,
            'paid_at' => $this->dateTime($receipt->paid_at),
            'payment_method' => $receipt->payment_method,
            'table' => $receipt->table,
            'table_session_id' => $receipt->table_session_id,
            'payment_attempt_id' => $receipt->payment_attempt_id,
            'accounting_document_name' => $receipt->accounting_document_name,
            'accounting_document_number' => $receipt->accounting_document_number,
            'issuer_name' => $receipt->issuer_name,
            'issuer_address' => $receipt->issuer_address,
            'issuer_tax_number' => $receipt->issuer_tax_number,
            'issuer_organizational_unit' => $receipt->issuer_organizational_unit,
            'customer_type' => $receipt->customer_type,
            'customer_name' => $receipt->customer_name,
            'customer_address' => $receipt->customer_address,
            'customer_tax_number' => $receipt->customer_tax_number,
            'customer_email' => $receipt->customer_email,
            'performance_at' => $this->dateTime($receipt->performance_at),
            'economic_event_description' => $receipt->economic_event_description,
            'accounting_currency' => $receipt->accounting_currency,
            'accounting_gross_total' => $receipt->accounting_gross_total,
            'accounting_items' => $receipt->accounting_items,
            'bookkeeping_reference' => $receipt->bookkeeping_reference,
            'bookkeeping_posted_at' => $this->dateTime($receipt->bookkeeping_posted_at),
            'bookkeeping_verified_by' => $receipt->bookkeeping_verified_by,
            'created_at' => $this->dateTime($receipt->created_at),
            'updated_at' => $this->dateTime($receipt->updated_at),
            'details' => $receipt->details
                ->map(fn (OrderDetail $detail) => $this->orderDetail($detail))
                ->values(),
        ];
    }

    private function paymentAttempt(PaymentAttempt $paymentAttempt): array
    {
        return [
            'id' => $paymentAttempt->id,
            'guest_id' => $paymentAttempt->guest_id,
            'employee_id' => $paymentAttempt->employee_id,
            'table_session_id' => $paymentAttempt->table_session_id,
            'receipt_id' => $paymentAttempt->receipt_id,
            'status' => $paymentAttempt->status,
            'payment_method' => $paymentAttempt->payment_method,
            'amount' => $paymentAttempt->amount,
            'currency' => $paymentAttempt->currency,
            'started_at' => $this->dateTime($paymentAttempt->started_at),
            'finished_at' => $this->dateTime($paymentAttempt->finished_at),
            'created_at' => $this->dateTime($paymentAttempt->created_at),
            'updated_at' => $this->dateTime($paymentAttempt->updated_at),
            'events' => $paymentAttempt->events
                ->map(fn (PaymentEvent $event) => $this->paymentEvent($event))
                ->values(),
        ];
    }

    private function paymentEvent(PaymentEvent $event): array
    {
        return [
            'id' => $event->id,
            'payment_attempt_id' => $event->payment_attempt_id,
            'event_type' => $event->event_type,
            'actor_guest_id' => $event->actor_guest_id,
            'actor_employee_id' => $event->actor_employee_id,
            'order_detail_id' => $event->order_detail_id,
            'receipt_id' => $event->receipt_id,
            'audit_xml' => $event->audit_xml,
            'created_at' => $this->dateTime($event->created_at),
        ];
    }

    private function gdprAuditEvent(GdprAuditEvent $event): array
    {
        return [
            'id' => $event->id,
            'event_type' => $event->event_type,
            'guest_id' => $event->guest_id,
            'actor_guest_id' => $event->actor_guest_id,
            'status' => $event->status,
            'masked_email' => $event->masked_email,
            'blocking_reason_count' => $event->blocking_reason_count,
            'blocking_reason_codes' => $event->blocking_reason_codes,
            'created_at' => $this->dateTime($event->created_at),
        ];
    }

    private function recentDrink(GuestRecentDrink $recentDrink): array
    {
        return [
            'id' => $recentDrink->id,
            'guest_id' => $recentDrink->guest_id,
            'drink_id' => $recentDrink->drink_id,
            'last_ordered_at' => $this->dateTime($recentDrink->last_ordered_at),
            'order_count' => $recentDrink->order_count,
            'created_at' => $this->dateTime($recentDrink->created_at),
            'updated_at' => $this->dateTime($recentDrink->updated_at),
        ];
    }

    private function dateTime(mixed $value): ?string
    {
        return $value instanceof Carbon ? $value->toJSON() : null;
    }

    private function date(mixed $value): ?string
    {
        return $value instanceof Carbon ? $value->toDateString() : null;
    }
}
