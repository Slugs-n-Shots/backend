<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class Drink extends Model
{
    use HasFactory, SoftDeletes;

    protected $dispatchesEvents = [
        'created' => \App\Events\DrinkCreated::class,
        'updated' => \App\Events\DrinkUpdated::class,
        'deleted' => \App\Events\DrinkDeleted::class,
    ];

    /**
     * Fields
     *
     * name_en: string
     * name_hu: string
     * guid: string
     * category_id: integer
     * description_en: ?string
     * description_hu: ?string
     * picture: ?string
     * active: boolean=true
     *
     * Relations
     *
     * name_en ux
     * name_hu ux
     * category_id  => drink_category.id
     * id <= drink_unit.drink_id
     */

    public const INACTIVE = 0;
    public const ACTIVE = 1;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name_en',
        'name_hu',
        'category_id',
        'description_en',
        'description_hu',
        'picture',
        'active',
    ];

    protected $appends = ['name', 'description'];
    protected $hidden = [
        'name_en',
        'name_hu',
        'description_en',
        'description_hu',
        'created_at',
        'updated_at',
        'deleted_at',
    ];

    protected $casts = [
        'active' => 'boolean'
    ];

    private static $internalFieldDefs = [
        "id",
        'name_en',
        'name_hu',
        'category_id',
        'description_en',
        'description_hu',
        'picture',
        'active',
        'created_at',
        'updated_at',
    ];

    public function category(): BelongsTo
    {
        return $this->belongsTo(DrinkCategory::class, 'category_id');
    }

    public function units(): HasMany
    {
        return $this->hasMany(DrinkUnit::class, 'drink_id', 'id');
    }

    /*
    public static function getStatuses(): array
    {
        return array_map(
            fn ($v): string =>
            __("drink.{$v}") ?? $v,
            Drink::$statuses
        );
    }

    public static function getStatusValue($name): string
    {
        $idx = array_search($name, Drink::getStatuses(), true);
        return ($idx >= 0) ? Drink::$statuses[$idx] : $name;
    }
*/

    public function getNameAttribute()
    {
        $locale = app()->getLocale();
        return $this->attributes["name_{$locale}"] ?? null;
    }

    public function setNameAttribute($value)
    {
        $locale = app()->getLocale();
        $this->attributes["name_{$locale}"] = $value;
        return $this;
    }

    public function getDescriptionAttribute()
    {
        $locale = app()->getLocale();
        return $this->attributes["description_{$locale}"] ?? null;
    }

    public function setDescriptionAttribute($value)
    {
        $locale = app()->getLocale();
        $this->attributes["description_{$locale}"] = $value;
        return $this;
    }

    public function getCategoryNameAttribute()
    {
        return $this->category->name;
    }

    public function getUnitsAttribute()
    {
        return $this->units()->get()->makeHidden(['id', 'drink_id']);
    }

    public function scopeActive($query)
    {
        return $query
            ->whereNull("{$this->getTable()}.deleted_at")
            ->where("{$this->getTable()}.active", Drink::ACTIVE);
    }
}
