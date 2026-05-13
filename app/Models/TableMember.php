<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TableMember extends Model
{
    use HasFactory;

    public const ROLE_OWNER = 'owner';
    public const ROLE_MEMBER = 'member';

    public const STATUS_PENDING = 'pending';
    public const STATUS_APPROVED = 'approved';
    public const STATUS_DENIED = 'denied';
    public const STATUS_REMOVED = 'removed';

    protected $fillable = [
        'table_session_id',
        'guest_id',
        'role',
        'status',
        'can_order',
        'approved_by_guest_id',
        'approved_at',
        'removed_at',
    ];

    protected $casts = [
        'can_order' => 'boolean',
        'approved_at' => 'datetime',
        'removed_at' => 'datetime',
    ];

    protected $hidden = [
        'created_at',
        'updated_at',
    ];

    public function tableSession(): BelongsTo
    {
        return $this->belongsTo(TableSession::class);
    }

    public function guest(): BelongsTo
    {
        return $this->belongsTo(Guest::class);
    }

    public function approvedByGuest(): BelongsTo
    {
        return $this->belongsTo(Guest::class, 'approved_by_guest_id');
    }
}
