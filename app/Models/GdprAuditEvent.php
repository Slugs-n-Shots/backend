<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GdprAuditEvent extends Model
{
    use HasFactory;

    public const UPDATED_AT = null;

    public const TYPE_ANONYMIZATION_REQUESTED = 'anonymization_requested';
    public const TYPE_ANONYMIZATION_BLOCKED = 'anonymization_blocked';
    public const TYPE_ANONYMIZATION_COMPLETED = 'anonymization_completed';

    protected $fillable = [
        'event_type',
        'guest_id',
        'actor_guest_id',
        'status',
        'masked_email',
        'blocking_reason_count',
        'blocking_reason_codes',
        'created_at',
    ];

    protected $casts = [
        'blocking_reason_codes' => 'array',
        'created_at' => 'datetime',
    ];

    public function guest(): BelongsTo
    {
        return $this->belongsTo(Guest::class);
    }

    public function actorGuest(): BelongsTo
    {
        return $this->belongsTo(Guest::class, 'actor_guest_id');
    }
}
