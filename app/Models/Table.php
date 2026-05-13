<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Table extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'tables';

    protected $fillable = [
        'name',
        'guid',
        'active',
    ];

    protected $casts = [
        'active' => 'boolean',
        'deleted_at' => 'datetime',
    ];

    protected $hidden = [
        'created_at',
        'updated_at',
        'deleted_at',
    ];

    protected $appends = [
        'status',
    ];

    protected static function booted(): void
    {
        static::creating(function (Table $table) {
            if (!$table->guid) {
                $table->guid = (string) Str::uuid();
            }
        });
    }

    public function sessions(): HasMany
    {
        return $this->hasMany(TableSession::class);
    }

    public function openSession(): HasOne
    {
        return $this->hasOne(TableSession::class)
            ->where('status', TableSession::STATUS_OPEN)
            ->whereNull('closed_at');
    }

    public function getStatusAttribute(): string
    {
        if (!$this->active) {
            return 'inactive';
        }

        if ($this->openSession()->exists()) {
            return 'reserved';
        }

        return 'available';
    }

    public function isReserved(): bool
    {
        return $this->openSession()->exists();
    }
}
