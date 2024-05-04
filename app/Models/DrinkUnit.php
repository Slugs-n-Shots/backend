<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DrinkUnit extends Model
{
    use HasFactory;

    protected $dispatchesEvents = [
        'created' => \App\Events\DrinkUnitCreated::class,
        'updated' => \App\Events\DrinkUnitUpdated::class,
        'deleted' => \App\Events\DrinkUnitDeleted::class,
    ];

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'drink_id',
        'quantity',
        'unit_en',
        'unit_hu',
        'unit_price',
        'active',
    ];

    protected $appends = ['unit', 'unit_code'];
    protected $hidden = [
        'unit_en',
        'unit_hu',
        'active',
        'created_at',
        'updated_at',
    ];

    protected $casts = [
        'active' => 'boolean'
    ];

    public function getUnitAttribute()
    {
        $locale = app()->getLocale();
        return $this->attributes["unit_{$locale}"];
    }

    public function getUnitCodeAttribute()
    {
        return $this->attributes["unit_en"];
    }

    public function setUnitAttribute($value)
    {
        $locale = app()->getLocale();
        $this->attributes["unit_{$locale}"] = $value;
        return $this;
    }

    public function drink(): BelongsTo
    {
        return $this->belongsTo(Drink::class);
    }
}
