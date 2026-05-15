<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TableSession extends Model
{
    use HasFactory;

    public const STATUS_OPEN = 'open';
    public const STATUS_CLOSED = 'closed';

    protected $fillable = [
        'table_id',
        'owner_guest_id',
        'business_date',
        'opened_at',
        'closed_at',
        'status',
        'owner_spending_limit',
        'staff_spending_limit_override',
        'staff_spending_limit_override_set_by',
        'staff_spending_limit_override_set_at',
    ];

    protected $casts = [
        'business_date' => 'date',
        'opened_at' => 'datetime',
        'closed_at' => 'datetime',
        'staff_spending_limit_override_set_at' => 'datetime',
    ];

    protected $hidden = [
        'created_at',
        'updated_at',
    ];

    public function table(): BelongsTo
    {
        return $this->belongsTo(Table::class);
    }

    public function ownerGuest(): BelongsTo
    {
        return $this->belongsTo(Guest::class, 'owner_guest_id');
    }

    public function members(): HasMany
    {
        return $this->hasMany(TableMember::class);
    }

    public function approvedMembers(): HasMany
    {
        return $this->hasMany(TableMember::class)
            ->where('status', TableMember::STATUS_APPROVED);
    }

    public function pendingMembers(): HasMany
    {
        return $this->hasMany(TableMember::class)
            ->where('status', TableMember::STATUS_PENDING);
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    public function effectiveSpendingLimit(): ?int
    {
        $limits = array_filter([
            $this->normalizeSpendingLimit($this->owner_spending_limit),
            $this->staffSpendingLimit(),
        ], static fn ($limit) => $limit !== null);

        return $limits === [] ? null : min($limits);
    }

    public function staffSpendingLimit(): ?int
    {
        return $this->normalizeSpendingLimit(
            $this->staff_spending_limit_override ?? config('tables.default_staff_spending_limit')
        );
    }

    private function normalizeSpendingLimit(mixed $limit): ?int
    {
        if ($limit === null || (int) $limit <= 0) {
            return null;
        }

        return (int) $limit;
    }

    public function close(): bool
    {
        $this->status = self::STATUS_CLOSED;
        $this->closed_at = now();

        return $this->save();
    }
}
