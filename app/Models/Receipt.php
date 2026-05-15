<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Receipt extends Model
{
    use HasFactory;

    public const PAYMENT_METHOD_CASH = 'cash';
    public const PAYMENT_METHOD_CARD = 'card';
    public const PAYMENT_METHOD_ADMIN_MARKED_PAID = 'admin_marked_paid';

    public const PAYMENT_METHODS = [
        self::PAYMENT_METHOD_CASH,
        self::PAYMENT_METHOD_CARD,
        self::PAYMENT_METHOD_ADMIN_MARKED_PAID,
    ];

    /**
     * serno: string
     * guest_id: integer
     * issued_at: datetime
     * paid_for: integer
     * paid_at: datetime
     * payment_method: string ('cash', 'card')
     * table: ?string
     *
     * Relations
     *
     * serno ux
     * guest_id => guest.id
     * paid_for => employee.id
     * receipt.id <= order_detail.receipt_id
     */

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'serno',
        'guest_id',
        'issued_at',
        'paid_for',
        'paid_at',
        'payment_method',
        'table',
        'table_session_id',
        'payment_attempt_id',
        'access_guid',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'created_at',
        'updated_at',
    ];

    protected $appends = [
        'payment_method_name',
    ];

    public function getPaymentMethodNameAttribute()
    {
        return match ($this->payment_method) {
            self::PAYMENT_METHOD_ADMIN_MARKED_PAID => __('settled on site'),
            default => __($this->payment_method),
        };
    }

    function details(): HasMany {
        return $this->hasMany(OrderDetail::class, 'receipt_id', 'id');
    }

    function guest(): BelongsTo {
        return $this->belongsTo(Guest::class, 'guest_id', 'id');
    }

    public function tableSession(): BelongsTo
    {
        return $this->belongsTo(TableSession::class);
    }

    public function paymentAttempt(): BelongsTo
    {
        return $this->belongsTo(PaymentAttempt::class);
    }
}
