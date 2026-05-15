<?php

namespace App\Models;

use Illuminate\Auth\Passwords\CanResetPassword;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Config;
use Tymon\JWTAuth\Contracts\JWTSubject;

class Guest extends Authenticatable implements JWTSubject, MustVerifyEmail
{
    use HasFactory, Notifiable, SoftDeletes, CanResetPassword;

    protected $guard = 'guard_guest';

    /**
     * Fields
     * first_name: string
     * middle_name: string
     * last_name: string
     * email: string
     * email_verified_at: ?timestamp
     * password: string
     * table: ?string
     * reservee: ?boolean
     * picture: ?string
     * active: boolean=false
     * anonymized_at: ?timestamp
     *
     * Relations
     *
     * email ux
     * id <= order.guest_id
     * id <= receipt.guest_id
     */

    public const INACTIVE = 0;
    public const ACTIVE = 1;
    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'first_name',
        'middle_name',
        'last_name',
        'email',
        'password',
        'table',
        'reservee',
        'picture',
        'active',
        'anonymized_at',
        'anonymization_reason',
    ];

    protected $appends = ['name'];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
        'email_verified_at',
        'anonymization_reason',
        'created_at',
        'updated_at',
        'deleted_at',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
        'active' => 'boolean',
        'data' => 'json',
        'anonymized_at' => 'datetime',
    ];

    /**
     * Aktívnak számít, ha megerősítette az e-mail címét, aktív és nem törölték a fiókot
     *
     * @param [type] $query
     * @return void
     */
    public function  scopeActive($query)
    {
        return $query
            ->whereNotNull("{$this->getTable()}.email_verified_at")
            ->whereNull("{$this->getTable()}.deleted_at")
            ->where("{$this->getTable()}.active", Guest::ACTIVE);
    }

    public function getNameAttribute()
    {
        $locale = App::currentLocale();
        $order = Config::get("regional.{$locale}.name_format");
        $items = [];
        foreach ($order as $name) {
            if ($name) {
                if ($name === strtoupper($name)) {
                    $items[] = strtoupper($this->{strtolower($name)}) . ',';
                } else {
                    $items[] = $this->{$name};
                }
            }
        }
        return implode(' ', $items);
    }

    /**
     * Get the identifier that will be stored in the subject claim of the JWT.
     *
     * @return mixed
     */
    public function getJWTIdentifier()
    {
        return $this->getKey();
    }

    /**
     * Return a key value array, containing any custom claims to be added to the JWT.
     *
     * @return array
     */
    public function getJWTCustomClaims()
    {
        return [
            'role' => 'guest'
        ];
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class, 'guest_id', 'id');
    }

    public function tableMemberships(): HasMany
    {
        return $this->hasMany(TableMember::class);
    }

    public function checkCustomClaims($claims)
    {
        return ($claims['role'] ?? null) === 'guest';
    }
}
