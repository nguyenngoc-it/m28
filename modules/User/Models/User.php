<?php

namespace Modules\User\Models;

use App\Base\Model;
use Carbon\Carbon;
use Gobiz\Activity\ActivityCreatorInterface;
use Illuminate\Auth\Authenticatable;
use Illuminate\Contracts\Auth\Authenticatable as AuthenticatableContract;
use Illuminate\Contracts\Auth\Access\Authorizable as AuthorizableContract;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Collection;
use Laravel\Lumen\Auth\Authorizable;
use Modules\Location\Models\Location;
use Modules\Marketplace\Services\Marketplace;
use Modules\Merchant\Models\Merchant;
use Modules\OrderPacking\Models\PickingSession;
use Modules\Store\Models\Store;
use Modules\Supplier\Models\Supplier;
use Modules\Tenant\Models\Tenant;
use Modules\Warehouse\Models\Warehouse;
use Tymon\JWTAuth\Contracts\JWTSubject;

/**
 * Class User
 *
 * @property int $id
 * @property int $tenant_id
 * @property string $username
 * @property string $password
 * @property string $name
 * @property string $email
 * @property string $phone
 * @property string $address
 * @property string $avatar
 * @property string $language
 * @property array $permissions
 * @property array $project_priorities
 * @property int $project_limited
 * @property int $project_count
 * @property Carbon $synced_at
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property-read Tenant $tenant
 * @property-read Merchant $merchant
 *
 * @property UserMerchant[]|Collection $userMerchants
 * @property Merchant[]|Collection $merchants
 * @property Supplier[]|Collection $suppliers
 * @property UserWarehouse[]|Collection $userWarehouses
 * @property Warehouse[]|Collection $warehouses
 * @property Collection locations
 * @property Collection pickingSessions
 */
class User extends Model implements AuthenticatableContract, AuthorizableContract, JWTSubject, ActivityCreatorInterface
{
    use Authenticatable, Authorizable;

    protected $table = 'users';

    protected $casts = [
        'permissions' => 'array',
        'synced_at' => 'datetime',
        'project_priorities' => 'array',
    ];

    protected $hidden = [
        'password',
    ];

    const USERNAME_SYSTEM    = 'system';
    const USERNAME_SHOP_BASE = 'shop_base';
    const USERNAME_FOBIZ     = 'fobiz';
    const USERNAME_SHOPEE    = 'shopee';
    const USERNAME_M6        = 'm6username';

    /**
     * @return BelongsTo
     */
    public function tenant()
    {
        return $this->belongsTo(Tenant::class, 'tenant_id', 'id');
    }

    /**
     * @return HasMany
     */
    public function identities()
    {
        return $this->hasMany(UserIdentity::class, 'user_id', 'id');
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
        return [];
    }

    /**
     * Get the creator id
     *
     * @return int
     */
    public function getId()
    {
        return $this->getKey();
    }

    /**
     * Get the creator username
     *
     * @return string
     */
    public function getUsername()
    {
        return $this->getAttribute('username');
    }

    /**
     * Get the creator name
     *
     * @return string
     */
    public function getName()
    {
        return $this->getAttribute('name');
    }

    /**
     * Get the tenant id
     *
     * @return int
     */
    public function getTenantId()
    {
        return $this->getAttribute('tenant_id');
    }

    /**
     * Return true if current user is admin
     *
     * @return bool
     */
    public function getIsAdmin()
    {
        return true;
    }

    /**
     * @return hasMany
     */
    public function userMerchants()
    {
        return $this->hasMany(UserMerchant::class, 'user_id');
    }

    /**
     * @return BelongsToMany
     */
    public function merchants()
    {
        return $this->belongsToMany(Merchant::class, 'user_merchants', 'user_id', 'merchant_id');
    }

    /**
     * @return hasMany
     */
    public function userWarehouses()
    {
        return $this->hasMany(UserWarehouse::class, 'user_id');
    }

    /**
     * @return BelongsToMany
     */
    public function warehouses()
    {
        return $this->belongsToMany(Warehouse::class, 'user_warehouses', 'user_id', 'warehouse_id');
    }

    /**
     * @return BelongsToMany
     */
    public function locations()
    {
        return $this->belongsToMany(Location::class, 'location_users', 'user_id', 'location_id');
    }

    /**
     * @return HasOne
     */
    public function merchant()
    {
        return $this->hasOne(Merchant::class, 'user_id', 'id');
    }

    /**
     * @return HasMany
     */
    public function pickingSessions()
    {
        return $this->hasMany(PickingSession::class, 'picker_id', 'id');
    }

    public function suppliers()
    {
        return $this->belongsToMany(Supplier::class, 'user_suppliers', 'user_id', 'supplier_id');
    }
}
