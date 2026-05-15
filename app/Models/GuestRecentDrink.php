<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GuestRecentDrink extends Model
{
    use HasFactory;

    protected $fillable = [
        'guest_id',
        'drink_id',
        'last_ordered_at',
        'order_count',
    ];

    protected $casts = [
        'last_ordered_at' => 'datetime',
    ];

    public function guest(): BelongsTo
    {
        return $this->belongsTo(Guest::class);
    }

    public function drink(): BelongsTo
    {
        return $this->belongsTo(Drink::class);
    }
}
