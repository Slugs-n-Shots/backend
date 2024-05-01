<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Order extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'guest_id',
        'recorded_by',
        'recorded_at',
        'made_by',
        'made_at',
        'served_by',
        'served_at',
        'table',
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
        'status'
    ];

    protected $casts = [
        'recorded_at' => 'datetime',
        'made_at' => 'datetime',
        'served_at' => 'datetime',
    ];

    public function guest()
    {
        return $this->belongsTo(Guest::class, 'guest_id');
    }

    public function details(): HasMany {
        return $this->hasMany(OrderDetail::class);
    }

    public function getStatusAttribute()
    {
        if (!empty($this->served_at)) {
            $status = __('served');
        } elseif (!empty($this->made_at)) {
            $status = __('ready');
        } elseif (!empty($this->recorded_at)) {
            $status = __('in progress');
        } else {
            $status = __('pending');
        }
        return $status;
    }
}
