<?php

namespace Modules\Location\Models;

use App\Base\Model;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Currency\Models\Currency;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Modules\ShippingPartner\Models\ShippingPartner;
use Modules\User\Models\User;
use Modules\Warehouse\Models\Warehouse;

/**
 * Class Location
 *
 * @property int $id
 * @property int $currency_id
 * @property string $code
 * @property string $type
 * @property string $parent_code
 * @property string $label
 * @property string $detail
 * @property boolean $active
 * @property int $priority
 * @property Location|null $parent
 * @property Currency|null currency
 * @property Collection|null locationShippingPartners
 * @property Collection shippingPartners
 * @property Collection users
 * @property Collection childrens
 * @property Collection warehouses
 */
class Location extends Model
{
    protected $table = 'locations';

    protected $casts = [
        'active' => 'boolean'
    ];

    const REGION_VIETNAM       = 'VN';
    const COUNTRY_CODE_VIETNAM = 'VN';

    const COUNTRY_VIETNAM    = 'vietnam';
    const COUNTRY_THAILAND   = 'thailand';
    const COUNTRY_CAMBODIA   = 'cambodia';
    const COUNTRY_PHILIPPINE = 'F2484';

    const TYPE_COUNTRY  = 'COUNTRY';
    const TYPE_PROVINCE = 'PROVINCE';
    const TYPE_DISTRICT = 'DISTRICT';
    const TYPE_WARD     = 'WARD';

    /**
     * Mã các quốc gia chưa được triển khai
     */
    const INACTIVE_COUNTRY = [];
    /**
     * Mã các quốc gia chưa được triển khai
     */
    const MERCHANT_ACTIVE_COUNTRY = [
        self::COUNTRY_VIETNAM,
        self::COUNTRY_THAILAND,
        self::COUNTRY_CAMBODIA
    ];

    /**
     * @var array
     */
    public static $types = [
        self::TYPE_COUNTRY,
        self::TYPE_PROVINCE,
        self::TYPE_DISTRICT,
        self::TYPE_WARD,
    ];

    /**
     * @return BelongsTo
     */
    public function currency(): BelongsTo
    {
        return $this->belongsTo(Currency::class, 'currency_id', 'id');
    }

    /**
     * @return BelongsTo
     */
    public function parent()
    {
        return $this->belongsTo(Location::class, 'parent_code', 'code');
    }

    /**
     * @return HasMany
     */
    public function childrens()
    {
        return $this->hasMany(Location::class, 'parent_code', 'code')
            ->where('active', true)
            ->orderBy('priority', 'desc')
            ->orderBy('label', 'asc');
    }

    /**
     * @return HasMany
     */
    public function locationShippingPartners()
    {
        return $this->hasMany(LocationShippingPartner::class);
    }

    /**
     * @return BelongsToMany
     */
    public function shippingPartners()
    {
        return $this->belongsToMany(ShippingPartner::class, 'location_shipping_partners', 'location_id', 'shipping_partner_id');
    }

    /**
     * @return BelongsToMany
     */
    public function users()
    {
        return $this->belongsToMany(User::class, 'location_users', 'location_id', 'user_id');
    }

    /**
     * Ưu tiên lấy theo alias trước, nếu k có lấy theo mã
     * @param $tenantId
     * @param $code
     * @return ShippingPartner|null
     */
    public function getShippingPartnerByAliasOrCode($tenantId, $code)
    {
        $code            = trim($code);
        $shippingPartner = $this->shippingPartners()->whereJsonContains('shipping_partners.alias', $code)
            ->where('shipping_partners.tenant_id', $tenantId)
            ->where('shipping_partners.status', true)
            ->first();
        if ($shippingPartner instanceof ShippingPartner) {
            return $shippingPartner;
        }

        return $this->shippingPartners()->where('shipping_partners.tenant_id', $tenantId)
            ->where('shipping_partners.code', $code)
            ->where('shipping_partners.status', true)
            ->first();
    }

    /**
     * @return HasMany
     */
    public function warehouses(): HasMany
    {
        return $this->hasMany(Warehouse::class, 'country_id');
    }

}
