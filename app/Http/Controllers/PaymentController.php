<?php

namespace App\Http\Controllers;

use App\Models\OrderDetail;
use App\Models\PaymentAttempt;
use App\Models\PaymentEvent;
use App\Models\Receipt;
use App\Models\TableMember;
use App\Models\TableSession;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class PaymentController extends Controller
{
    public function store(Request $request)
    {
        $valid = $this->validatePaymentRequest($request, true);

        return DB::transaction(function () use ($valid) {
            $guest = request()->user();
            $details = OrderDetail::with('order')
                ->whereIn('id', $valid['order_detail_ids'])
                ->lockForUpdate()
                ->get();

            if ($details->contains(fn(OrderDetail $detail) => $detail->order->guest_id !== $guest->id)) {
                abort(403);
            }

            return $this->processPayment(
                $guest->id,
                $guest->id,
                null,
                $details,
                $valid['payment_method'],
                $valid['simulate_result'] ?? PaymentAttempt::STATUS_SUCCEEDED,
                null,
                null
            );
        });
    }

    public function tablePayment(Request $request)
    {
        $valid = $this->validatePaymentRequest($request, true);

        return DB::transaction(function () use ($valid) {
            $guest = request()->user();
            [$tableSession] = $this->currentTableSessionForGuest($guest->id, false);

            if ($tableSession === null) {
                abort(403);
            }

            $details = OrderDetail::with('order')
                ->whereIn('id', $valid['order_detail_ids'])
                ->lockForUpdate()
                ->get();

            if ($details->contains(fn(OrderDetail $detail) => $detail->order->table_session_id !== $tableSession->id)) {
                abort(403);
            }

            return $this->processPayment(
                $guest->id,
                $guest->id,
                null,
                $details,
                $valid['payment_method'],
                $valid['simulate_result'] ?? PaymentAttempt::STATUS_SUCCEEDED,
                $tableSession,
                null
            );
        });
    }

    public function closingPayment(Request $request)
    {
        $valid = $this->validatePaymentRequest($request, false);

        return DB::transaction(function () use ($valid) {
            $guest = request()->user();
            [$tableSession, $isOwner] = $this->currentTableSessionForGuest($guest->id, true);

            if ($tableSession === null || !$isOwner) {
                abort(403);
            }

            $details = OrderDetail::with('order')
                ->where('payment_status', OrderDetail::PAYMENT_STATUS_PENDING)
                ->whereNull('receipt_id')
                ->whereHas('order', function ($query) use ($tableSession) {
                    $query->where('table_session_id', $tableSession->id);
                })
                ->lockForUpdate()
                ->get();

            if ($details->isEmpty()) {
                abort(response()->json([
                    'message' => __('There are no pending order details to pay.'),
                ], 409));
            }

            return $this->processPayment(
                $guest->id,
                $guest->id,
                null,
                $details,
                $valid['payment_method'],
                $valid['simulate_result'] ?? PaymentAttempt::STATUS_SUCCEEDED,
                $tableSession,
                null
            );
        });
    }

    public function staffMarkPaid(Request $request)
    {
        $valid = $request->validate([
            'order_detail_ids' => ['required', 'array', 'min:1'],
            'order_detail_ids.*' => ['required', 'integer', 'distinct', 'exists:order_details,id'],
            'memo' => ['sometimes', 'string', 'max:1000'],
        ]);

        return DB::transaction(function () use ($valid) {
            $employee = request()->user();
            $details = OrderDetail::with('order.tableSession.table')
                ->whereIn('id', $valid['order_detail_ids'])
                ->lockForUpdate()
                ->get();

            $guestIds = $details->pluck('order.guest_id')->unique();
            if ($guestIds->count() !== 1) {
                abort(response()->json([
                    'message' => __('Order details must belong to the same guest.'),
                ], 409));
            }

            $tableSessionIds = $details->pluck('order.table_session_id')->filter()->unique();
            if ($tableSessionIds->count() > 1) {
                abort(response()->json([
                    'message' => __('Order details must belong to the same table session.'),
                ], 409));
            }

            $tableSession = $details->first()?->order?->tableSession;
            if ($tableSession?->status === TableSession::STATUS_CLOSED && !$employee->isAdmin()) {
                abort(403);
            }

            return $this->processPayment(
                (int) $guestIds->first(),
                null,
                $employee->id,
                $details,
                Receipt::PAYMENT_METHOD_ADMIN_MARKED_PAID,
                PaymentAttempt::STATUS_SUCCEEDED,
                $tableSession,
                $employee->id,
                $this->memoAuditXml($valid['memo'] ?? null)
            );
        });
    }

    private function validatePaymentRequest(Request $request, bool $requiresDetails): array
    {
        $detailRules = $requiresDetails
            ? ['required', 'array', 'min:1']
            : ['sometimes', 'array', 'min:1'];

        $valid = $request->validate([
            'order_detail_ids' => $detailRules,
            'order_detail_ids.*' => ['required', 'integer', 'distinct', 'exists:order_details,id'],
            'payment_method' => ['required', 'string', Rule::in([
                Receipt::PAYMENT_METHOD_CASH,
                Receipt::PAYMENT_METHOD_CARD,
            ])],
            'simulate_result' => ['sometimes', 'string', Rule::in([
                PaymentAttempt::STATUS_SUCCEEDED,
                PaymentAttempt::STATUS_FAILED,
                PaymentAttempt::STATUS_ABANDONED,
            ])],
        ]);

        if (!app()->environment(['local', 'testing']) && $request->has('simulate_result')) {
            throw ValidationException::withMessages([
                'simulate_result' => [__('Payment simulation is only available in local and testing environments.')],
            ]);
        }

        return $valid;
    }

    private function processPayment(
        int $receiptGuestId,
        ?int $actorGuestId,
        ?int $actorEmployeeId,
        Collection $details,
        string $paymentMethod,
        string $result,
        ?TableSession $tableSession,
        ?int $paidForEmployeeId,
        ?string $auditXml = null
    ): array {
        if ($details->contains(fn(OrderDetail $detail) => $detail->payment_status !== OrderDetail::PAYMENT_STATUS_PENDING || $detail->receipt_id !== null)) {
            abort(response()->json([
                'message' => __('Only pending order details can be paid.'),
            ], 409));
        }

        $amount = $this->detailsTotal($details);

        $payment = PaymentAttempt::create([
            'guest_id' => $actorGuestId,
            'employee_id' => $actorEmployeeId,
            'table_session_id' => $tableSession?->id,
            'receipt_id' => null,
            'status' => PaymentAttempt::STATUS_PENDING,
            'payment_method' => $paymentMethod,
            'amount' => $amount,
            'currency' => 'HUF',
            'started_at' => now(),
            'finished_at' => null,
        ]);

        $this->recordEvent($payment, PaymentEvent::TYPE_CREATED, $actorGuestId, $actorEmployeeId);

        if ($result !== PaymentAttempt::STATUS_SUCCEEDED) {
            $payment->fill([
                'status' => $result,
                'finished_at' => now(),
            ])->save();

            $this->recordEvent(
                $payment,
                $result === PaymentAttempt::STATUS_ABANDONED
                    ? PaymentEvent::TYPE_PAYMENT_ABANDONED
                    : PaymentEvent::TYPE_PAYMENT_FAILED,
                $actorGuestId,
                $actorEmployeeId
            );

            return [
                'payment' => $payment->fresh(),
                'receipt' => null,
            ];
        }

        $receipt = Receipt::create([
            'serno' => $this->nextReceiptSerial(),
            'guest_id' => $receiptGuestId,
            'issued_at' => now(),
            'paid_for' => $paidForEmployeeId,
            'paid_at' => now(),
            'payment_method' => $paymentMethod,
            'table' => $tableSession?->table?->name,
            'table_session_id' => $tableSession?->id,
            'payment_attempt_id' => $payment->id,
            'access_guid' => (string) Str::uuid(),
        ]);

        foreach ($details as $detail) {
            $detail->fill([
                'payment_status' => OrderDetail::PAYMENT_STATUS_PAID,
                'receipt_id' => $receipt->id,
            ])->save();
        }

        $payment->fill([
            'status' => PaymentAttempt::STATUS_SUCCEEDED,
            'receipt_id' => $receipt->id,
            'finished_at' => now(),
        ])->save();

        $this->recordEvent($payment, PaymentEvent::TYPE_PAYMENT_SUCCEEDED, $actorGuestId, $actorEmployeeId);

        if ($paymentMethod === Receipt::PAYMENT_METHOD_ADMIN_MARKED_PAID) {
            $this->recordEvent(
                $payment,
                PaymentEvent::TYPE_MARKED_PAID_BY_ADMIN,
                $actorGuestId,
                $actorEmployeeId,
                $receipt->id,
                $auditXml
            );
        }

        $this->recordEvent($payment, PaymentEvent::TYPE_RECEIPT_CREATED, $actorGuestId, $actorEmployeeId, $receipt->id);

        return [
            'payment' => $payment->fresh(),
            'receipt' => $receipt->fresh(),
        ];
    }

    private function detailsTotal(Collection $details): int
    {
        return (int) $details->sum(fn(OrderDetail $detail) => $detail->unit_price * $detail->ordered_quantity);
    }

    private function currentTableSessionForGuest(int $guestId, bool $ownerOnly): array
    {
        $ownedSession = TableSession::with('table')
            ->where('owner_guest_id', $guestId)
            ->where('status', TableSession::STATUS_OPEN)
            ->whereNull('closed_at')
            ->first();

        if ($ownedSession !== null) {
            return [$ownedSession, true];
        }

        if ($ownerOnly) {
            return [null, false];
        }

        $membership = TableMember::with('tableSession.table')
            ->where('guest_id', $guestId)
            ->where('status', TableMember::STATUS_APPROVED)
            ->whereHas('tableSession', function ($query) {
                $query->where('status', TableSession::STATUS_OPEN)
                    ->whereNull('closed_at');
            })
            ->first();

        return [$membership?->tableSession, false];
    }

    private function recordEvent(
        PaymentAttempt $payment,
        string $eventType,
        ?int $guestId = null,
        ?int $employeeId = null,
        ?int $receiptId = null,
        ?string $auditXml = null
    ): void
    {
        PaymentEvent::create([
            'payment_attempt_id' => $payment->id,
            'event_type' => $eventType,
            'actor_guest_id' => $guestId,
            'actor_employee_id' => $employeeId,
            'order_detail_id' => null,
            'receipt_id' => $receiptId,
            'audit_xml' => $auditXml,
            'created_at' => now(),
        ]);
    }

    private function memoAuditXml(?string $memo): ?string
    {
        if ($memo === null || trim($memo) === '') {
            return null;
        }

        return '<audit><memo>' . htmlspecialchars($memo, ENT_XML1 | ENT_COMPAT, 'UTF-8') . '</memo></audit>';
    }

    private function nextReceiptSerial(): string
    {
        $nextId = (int) Receipt::max('id') + 1;

        return 'R' . str_pad((string) $nextId, 8, '0', STR_PAD_LEFT);
    }
}
