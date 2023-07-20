<?php

namespace Modules\ShippingPartner\Models;

use App\Base\Model;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Arr;
use Modules\Currency\Models\Currency;
use Modules\Location\Models\Location;
use Modules\Service;
use Modules\ShippingPartner\Services\ExpectedTransportingPrice\ExpectedTransporting;
use Modules\ShippingPartner\Services\ExpectedTransportingPrice\ExpectedTransportingPriceException;
use Modules\Tenant\Models\Tenant;
use Modules\ShippingPartner\Services\ShippingPartnerApi\ShippingPartnerApiInterface;
use Modules\ShippingPartner\Services\ShippingPartnerApi\ShippingPartnerApiException;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Transaction\Models\Transaction;
use Modules\Transaction\Services\TransactionAccountInterface;

/**
 * Class ShippingPartner
 *
 * @property int $id
 * @property int $tenant_id
 * @property string $code
 * @property string $name
 * @property string $provider
 * @property string $description
 * @property array $settings
 * @property array alias
 * @property boolean status
 * @property boolean required_postcode
 *
 * @property array temp_tracking
 * @property Tenant|null $tenant
 * @property Location[]|Collection locations
 * @property Collection countries
 */
class ShippingPartner extends Model implements TransactionAccountInterface
{
    const SETTING_CARRIER = 'carrier';
    const SETTING_CONNECT = 'connect_code';

    const TOPSHIP_TOKEN         = 'token';
    const TOPSHIP_CARRIER       = 'carrier'; // Mã đơn vị vận chuyển ("ghn" "ghtk" "vtpost" "etop" "partner" "ninjavan" "dhl" "ntx" "vnpost")
    const TOPSHIP_SHIPPING_NAME = 'shipping_name'; // Tên gói vận chuyển

    const PROVIDER_M32        = 'm32';
    const PROVIDER_MANUAL     = 'manual';
    const PROVIDER_SHOPEE     = 'shopee';
    const PROVIDER_KIOTVIET   = 'kiotviet';
    const PROVIDER_LAZADA     = 'lazada';
    const PROVIDER_TIKI       = 'tiki';
    const PROVIDER_TIKTOKSHOP = 'tiktokshop';
    const PROVIDER_TOPSHIP    = 'topship';

    const PICKUP_TYPE_PICKUP  = 'pickup';
    const PICKUP_TYPE_DROPOFF = 'dropoff';

    const SHIPPING_PARTNER_JNTM = 'JNTM';
    const SHIPPING_PARTNER_JNTT = 'JNTT';
    const SHIPPING_PARTNER_JNTP = 'JNTP';

    protected $table = 'shipping_partners';

    protected $casts = [
        'settings' => 'json',
        'alias' => 'json',
        'temp_tracking' => 'json',
        'required_postcode'
    ];

    public function getTempTrackingAttribute($value)
    {
        return (array)json_decode($value, true);
    }

    /**
     * @return BelongsTo
     */
    public function tenant()
    {
        return $this->belongsTo(Tenant::class, 'tenant_id', 'id');
    }

    /**
     * @return mixed|ShippingPartnerApiInterface
     * @throws ShippingPartnerApiException
     */
    public function api()
    {
        return Service::shippingPartner()->api($this);
    }

    /**
     * @return ExpectedTransporting
     * @throws ExpectedTransportingPriceException
     */
    public function expectedTransporting(): ExpectedTransporting
    {
        return Service::shippingPartner()->expectedTransporting($this);
    }

    /**
     * Get setting
     *
     * @param null|string $key
     * @param mixed $default
     * @return mixed
     */
    public function getSetting($key = null, $default = null)
    {
        $settings = $this->getAttribute('settings');
        return Arr::get($settings, $key, $default);
    }

    /**
     * @return int
     */
    public function getTenantId()
    {
        return $this->getAttribute('tenant_id');
    }

    /**
     * @return string
     */
    public function getAccountType()
    {
        return Transaction::ACCOUNT_TYPE_SHIPPING_PARTNER;
    }

    /**
     * @return string
     */
    public function getAccountId()
    {
        return $this->getKey();
    }

    /**
     * @return BelongsToMany
     */
    public function locations()
    {
        return $this->belongsToMany(Location::class, 'location_shipping_partners', 'shipping_partner_id', 'location_id');
    }

    /**
     * @return BelongsToMany
     */
    public function countries()
    {
        return $this->belongsToMany(Location::class, 'location_shipping_partners');
    }

    /**
     * @return Location|null
     */
    public function country()
    {
        return $this->countries->first();
    }

    /**
     * @return Currency|null
     */
    public function currency()
    {
        return $this->country() ? $this->country()->currency : null;
    }
}
