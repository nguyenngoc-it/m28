<?php

namespace Modules\Tenant\Models;

use App\Base\Model;
use Carbon\Carbon;
use Gobiz\Email\EmailProviderInterface;
use Gobiz\Support\Traits\CachedPropertiesTrait;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Arr;
use Illuminate\Contracts\Filesystem\Cloud as CloudFilesystem;
use Modules\FreightBill\Models\FreightBill;
use Modules\Gobiz\Services\M4ApiInterface;
use Modules\Gobiz\Services\M10ApiInterface;
use Modules\Gobiz\Services\M6ApiInterface;
use Modules\Location\Models\Location;
use Modules\Merchant\Models\Merchant;
use Modules\Order\Models\Order;
use Modules\Category\Models\Category;
use Modules\OrderPacking\Models\OrderPacking;
use Modules\OrderPacking\Models\PackingType;
use Modules\Product\Models\Product;
use Modules\Product\Models\Sku;
use Modules\Product\Models\Unit;
use Modules\PurchasingOrder\Models\PurchasingOrder;
use Modules\PurchasingPackage\Models\PurchasingPackage;
use Modules\Service;
use Modules\Stock\Models\Stock;
use Modules\Store\Models\Store;
use Modules\Supplier\Models\Supplier;
use Modules\Tenant\Services\TenantWebhook;
use Modules\Tenant\Services\TenantWebhookInterface;
use Modules\Warehouse\Models\Warehouse;
use Illuminate\Database\Eloquent\Collection;
use Modules\ShippingPartner\Models\ShippingPartner;

/**
 * Class Tenant
 *
 * @property int $id
 * @property string $code
 * @property string $m4_tenant_merchant
 * @property string $m4_tenant_shipping_partner
 * @property array $domains
 * @property array $merchant_domains
 * @property string $client_id
 * @property string $client_secret
 * @property int $webhook_id
 * @property string $webhook_url
 * @property string $webhook_secret
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property Collection $categories
 * @property Collection $units
 * @property Collection $products
 * @property Collection $skus
 * @property Collection $warehouses
 * @property Collection $stocks
 * @property Collection $shippingPartners
 * @property Collection purchasingOrders
 * @property Collection purchasingPackages
 * @property Collection packingTypes
 * @property Collection services
 * @property Collection servicePrices
 * @property Collection orderPackings
 * @property Merchant[] merchants
 * @property Supplier[]|Collection suppliers
 * @property Collection stores
 *
 */
class Tenant extends Model
{
    use CachedPropertiesTrait;

    protected $table = 'tenants';

    protected $casts = [
        'domains' => 'json',
        'merchant_domains' => 'json',
    ];

    protected $hidden = [
        'webhook_secret',
    ];

    /*
     * Setting params
     */
    const SETTING_EMAIL                        = 'EMAIL'; // Thông tin email
    const SETTING_PHONE                        = 'PHONE'; // Thông tin phone
    const SETTING_IRIS_USERNAME                = 'IRIS_USERNAME'; // Username kết nối đến Iris
    const SETTING_IRIS_PASSWORD                = 'IRIS_PASSWORD'; // Password kết nối đến Iris
    const SETTING_COUNTRY                      = 'COUNTRY'; // Country code của tenant (required)
    const SETTING_CURRENCY_FORMAT              = 'CURRENCY_FORMAT'; // Format tiền tệ, default: {amount}
    const SETTING_CURRENCY_PRECISION           = 'CURRENCY_PRECISION'; // Số thập phân làm tròn của tiền tệ, default: 0
    const SETTING_CURRENCY_THOUSANDS_SEPARATOR = 'CURRENCY_THOUSANDS_SEPARATOR'; // Kí tự ngăn cách hàng nghìn, default: '.'
    const SETTING_CURRENCY_DECIMAL_SEPARATOR   = 'CURRENCY_DECIMAL_SEPARATOR'; // Kí tự ngăn cách thập phân, default: ','
    const SETTING_M6_TOKEN                     = 'M6_TOKEN';
    const SETTING_M6_AGENCY_CODE               = 'M6_AGENCY_CODE';
    const SETTING_STORAGE_FEE_CLOSING_TIME     = 'STORAGE_FEE_CLOSING_TIME'; // Thời điểm trong ngày sẽ chốt phí lưu kho
    const SETTING_WEBHOOK_PUBLISH_EVENT        = 'WEBHOOK_PUBLISH_EVENT'; // Có publish webhook event hay không (0 || 1)

    /**
     * @return array
     */
    protected function getDefaultSettings()
    {
        return [
            static::SETTING_CURRENCY_FORMAT => '{amount}',
            static::SETTING_CURRENCY_PRECISION => 0,
            static::SETTING_CURRENCY_THOUSANDS_SEPARATOR => '.',
            static::SETTING_CURRENCY_DECIMAL_SEPARATOR => ',',
        ];
    }

    /**
     * @return HasMany
     */
    public function settings()
    {
        return $this->hasMany(TenantSetting::class, 'tenant_id', 'id');
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
        $settings = $this->getCachedProperty('settings', function () {
            $settings = $this->getAttribute('settings')->pluck('value', 'key')->toArray();

            return array_merge($this->getDefaultSettings(), $settings);
        });

        return Arr::get($settings, $key, $default);
    }

    /**
     * @param array $settings
     */
    public function updateSettings(array $settings)
    {
        foreach ($settings as $key => $value) {
            $this->settings()->updateOrCreate(['key' => $key], ['value' => $value]);
        }
    }

    /**
     * @return CloudFilesystem
     */
    public function storage()
    {
        return Service::tenant()->storage($this);
    }

    /**
     * @return EmailProviderInterface
     */
    public function email()
    {
        return Service::tenant()->email($this);
    }

    /**
     * @return M6ApiInterface
     */
    public function m6()
    {
        return Service::tenant()->m6($this);
    }

    /**
     * @return M4ApiInterface
     */
    public function m4Merchant()
    {
        return Service::tenant()->m4($this, 'merchant');
    }

    /**
     * @return M4ApiInterface
     */
    public function m4ShippingPartner()
    {
        return Service::tenant()->m4($this, 'shipping_partner');
    }

    /**
     * @return M4ApiInterface
     */
    public function m4Supplier()
    {
        return Service::tenant()->m4($this, 'supplier');
    }

    /**
     * @return M10ApiInterface
     */
    public function m10()
    {
        return Service::tenant()->m10($this);
    }

    /**
     * @param string $path
     * @param array $query
     * @param string|null $domain
     * @return string
     */
    public function url($path = '', array $query = [], $domain = null)
    {
        return Service::tenant()->url($this, $path, $query, $domain);
    }

    /**
     * @param array $query
     * @param $domain
     * @return mixed
     */
    public function redirectUrl(array $query = [], $domain = null)
    {
        return Service::tenant()->redirectUrl($query, $domain);
    }

    /**
     * @return HasMany
     */
    public function products()
    {
        return $this->hasMany(Product::class, 'tenant_id', 'id');
    }

    /**
     * @return HasMany
     */
    public function skus()
    {
        return $this->hasMany(Sku::class, 'tenant_id', 'id');
    }

    /**
     * @return HasMany
     */
    public function units()
    {
        return $this->hasMany(Unit::class, 'tenant_id', 'id');
    }

    /**
     * @return HasMany
     */
    public function categories()
    {
        return $this->hasMany(Category::class, 'tenant_id', 'id');
    }

    /**
     * @return HasMany
     */
    public function warehouses()
    {
        return $this->hasMany(Warehouse::class, 'tenant_id', 'id');
    }

    /**
     * @return HasMany
     */
    public function stocks()
    {
        return $this->hasMany(Stock::class, 'tenant_id', 'id');
    }

    /**
     * @return HasMany
     */
    public function orders()
    {
        return $this->hasMany(Order::class, 'tenant_id', 'id');
    }

    /**
     * @return HasMany
     */
    public function freightBills()
    {
        return $this->hasMany(FreightBill::class, 'tenant_id', 'id');
    }

    /**
     * @return HasMany
     */
    public function merchants()
    {
        return $this->hasMany(Merchant::class, 'tenant_id', 'id');
    }

    /**
     * @return HasMany
     */
    public function shippingPartners()
    {
        return $this->hasMany(ShippingPartner::class, 'tenant_id', 'id');
    }

    /**
     * @return TenantWebhookInterface
     */
    public function webhook()
    {
        return $this->getCachedProperty('webhook', function () {
            return new TenantWebhook($this);
        });
    }

    /**
     * @return HasMany
     */
    public function purchasingOrders()
    {
        return $this->hasMany(PurchasingOrder::class, 'tenant_id', 'id');
    }

    /**
     * @return HasMany
     */
    public function purchasingPackages()
    {
        return $this->hasMany(PurchasingPackage::class, 'tenant_id', 'id');
    }

    /**
     * @return HasMany
     */
    public function packingTypes()
    {
        return $this->hasMany(PackingType::class, 'tenant_id', 'id');
    }

    /**
     * @return HasMany
     */
    public function services()
    {
        return $this->hasMany(Service\Models\Service::class);
    }

    /**
     * @return HasMany
     */
    public function servicePrices()
    {
        return $this->hasMany(Service\Models\ServicePrice::class);
    }

    /**
     * @return HasMany
     */
    public function orderPackings()
    {
        return $this->hasMany(OrderPacking::class);
    }


    /**
     * @return Location|null|mixed
     */
    public function getCountry()
    {
        $countryCode = $this->getSetting('COUNTRY');
        return Location::query()->firstWhere([
            'code' => trim($countryCode),
            'type' => Location::TYPE_COUNTRY
        ]);
    }

    /**
     * @return HasMany
     */
    public function stores()
    {
        return $this->hasMany(Store::class)->where('status', Store::STATUS_ACTIVE);
    }

    /**
     * @return HasMany
     */
    public function suppliers()
    {
        return $this->hasMany(Supplier::class);
    }

}
