<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PaymentEvent extends Model
{
    use HasFactory;

    public const UPDATED_AT = null;

    public const TYPE_CREATED = 'created';
    public const TYPE_PAYMENT_SUCCEEDED = 'payment_succeeded';
    public const TYPE_PAYMENT_FAILED = 'payment_failed';
    public const TYPE_PAYMENT_ABANDONED = 'payment_abandoned';
    public const TYPE_RECEIPT_CREATED = 'receipt_created';
    public const TYPE_MARKED_PAID_BY_ADMIN = 'marked_paid_by_admin';

    protected $fillable = [
        'payment_attempt_id',
        'event_type',
        'actor_guest_id',
        'actor_employee_id',
        'order_detail_id',
        'receipt_id',
        'audit_xml',
        'created_at',
    ];

    public function paymentAttempt(): BelongsTo
    {
        return $this->belongsTo(PaymentAttempt::class);
    }

    public function actorGuest(): BelongsTo
    {
        return $this->belongsTo(Guest::class, 'actor_guest_id');
    }

    public function actorEmployee(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'actor_employee_id');
    }

    public function orderDetail(): BelongsTo
    {
        return $this->belongsTo(OrderDetail::class);
    }

    public function receipt(): BelongsTo
    {
        return $this->belongsTo(Receipt::class);
    }
}
