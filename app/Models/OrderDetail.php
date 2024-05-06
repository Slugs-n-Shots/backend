<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class OrderDetail extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'order_id',
        'drink_unit_id',
        'ordered_quantity',
        'promo_id',
        'unit_price',
        'discount',
        'receipt_id',
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

    public function order(): BelongsTo {
        return $this->belongsTo(Order::class);
    }

    public function drinkUnit(): HasOne {
        return $this->hasOne(DrinkUnit::class, 'id', 'drink_unit_id');
    }

}
