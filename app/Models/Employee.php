<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Config;
use Tymon\JWTAuth\Contracts\JWTSubject;

class Employee extends Authenticatable implements JWTSubject
{
    use HasFactory, Notifiable, SoftDeletes;

    protected $guard = 'guard_employee';

    /**
     * Fields
     * first_name: string
     * middle_name: string
     * last_name: string
     * email: string
     * email_verified_at: ?timestamp
     * password: string
     * role_code: integer    // CHECK ([role] IN ('pincér', 'pultos', 'backoffice'))
     * active: boolean=true
     *
     * Relations
     *
     * email ux
     * id <= order.guest_id
     * id <= receipt.guest_id
     */

    public const ROLES = [ //'pincér', 'pultos', 'backoffice'
        self::WAITER => 'waiter',
        self::BARTENDER => 'bartender',
        self::BACKOFFICE => 'backoffice',
        self::ADMIN => 'admin',
    ];

    public const WAITER = 1;
    public const BARTENDER = 2;
    public const BACKOFFICE = 4;
    public const ADMIN = 7;
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
        'role_code',
        'active',
    ];

    protected $appends = ['role', 'name'];


    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
        'email_verified_at',
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
    ];

    public function scopeBartender($query)
    {
        return $query->where('role_code', Employee::BARTENDER);
    }

    public function scopeWaiter($query)
    {
        return $query->where('role_code', Employee::WAITER);
    }

    public function scopeBackoffice($query)
    {
        return $query->where('role_code', Employee::BACKOFFICE);
    }

    public function scopeAdmin($query)
    {
        return $query->where('role_code', Employee::ADMIN);
    }

    public function getRoleAttribute()
    {
        return __(Employee::ROLES[$this->role_code]);
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
            'role' => 'staff'
        ];
    }

    public function checkCustomClaims($claims)
    {
        return $claims['role'] && $claims['role'] == 'staff';
    }
}
