<?php /** @noinspection ALL */

namespace Modules\Merchant\Models;

use App\Base\Model;
use Carbon\Carbon;
use Gobiz\Log\LogService;
use Gobiz\Support\Traits\CachedPropertiesTrait;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Modules\Currency\Models\Currency;
use Modules\Customer\Models\Customer;
use Modules\Customer\Models\CustomerAddress;
use Modules\Location\Models\Location;
use Modules\Order\Models\Order;
use Modules\OrderPacking\Models\OrderPacking;
use Modules\Product\Models\Product;
use Modules\Product\Models\ProductMerchant;
use Modules\Product\Models\Sku;
use Modules\PurchasingOrder\Models\PurchasingOrder;
use Modules\PurchasingPackage\Models\PurchasingPackage;
use Modules\Service\Models\ServiceCombo;
use Modules\Service\Models\ServicePack;
use Modules\Service\Models\StorageFeeMerchantStatistic;
use Modules\Store\Models\Store;
use Modules\Tenant\Models\Tenant;
use Modules\Tenant\Models\TenantSetting;
use Modules\Transaction\Commands\MerchantTransCreating;
use Modules\Transaction\Models\Transaction;
use Modules\Transaction\Services\MerchantTransObjInterface;
use Modules\Transaction\Services\TransactionAccountInterface;
use Modules\User\Models\User;

/**
 * Class Merchant
 *
 * @property int $id
 * @property int $tenant_id
 * @property int creator_id
 * @property int service_pack_id
 * @property int service_combo_id
 * @property int $location_id
 * @property int $user_id
 * @property string $username
 * @property string $phone
 * @property string ref
 * @property string $status
 * @property string $name
 * @property string $address
 * @property string $description
 * @property string $code
 * @property string $shop_base_account
 * @property string $shop_base_app_key
 * @property string $shop_base_password
 * @property string $shop_base_secret
 * @property string $shop_base_webhook_id
 * @property Carbon storaged_at
 * @property int free_days_of_storage
 * @property Carbon service_pack_added_at
 * @property Carbon service_combo_added_at
 *
 * @property Tenant|null $tenant
 * @property User|null $user
 * @property Location|null $location
 * @property Order[]|Collection $orders
 * @property Collection orderPackings
 * @property Product[]|Collection myProducts
 * @property \Illuminate\Database\Eloquent\Collection products
 * @property \Illuminate\Database\Eloquent\Collection skus
 * @property \Illuminate\Database\Eloquent\Collection productMerchants
 * @property PurchasingPackage[]|Collection purchasingPackages
 * @property PurchasingOrder[]|Collection purchasingOrders
 * @property Store[]|Collection stores
 * @property ServicePack|null servicePack
 * @property ServiceCombo|null serviceCombo
 */
class Merchant extends Model implements TransactionAccountInterface
{
    use CachedPropertiesTrait;

    const FREE_DAYS_OF_STORAGE = 30; // Số ngày miễn phí lưu kho mặc định khi tạo tài khoản

    protected $table = 'merchants';

    protected $casts = [
        'storaged_at' => 'datetime',
        'service_pack_added_at' => 'datetime',
        'service_combo_added_at' => 'datetime',
    ];

    protected $hidden = [
        'shop_base_app_key',
        'shop_base_password',
        'shop_base_secret',
        'shop_base_webhook_id',
    ];

    /**
     * @var array
     */
    public static $shopBaseParams = ['shop_base_account', 'shop_base_app_key', 'shop_base_password', 'shop_base_secret'];

    /**
     * @return HasMany
     */
    public function orders()
    {
        return $this->hasMany(Order::class, 'merchant_id', 'id');
    }

    /**
     * @return HasMany
     */
    public function orderPackings(): HasMany
    {
        return $this->hasMany(OrderPacking::class);
    }

    /**
     * @return HasMany
     */
    public function skus()
    {
        return $this->hasMany(Sku::class, 'merchant_id', 'id');
    }

    /**
     * Những skus mà seller được bán,
     * ưu tiên lấy sản phẩm của hệ thống gán cho seller
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function applyMerchantSkus()
    {
        $producs = $this->products->concat($this->myProducts);
        return Sku::query()->whereIn('product_id', $producs->pluck('id')->all())
            ->distinct()
            ->get();
    }

    /**
     * @return HasMany
     */
    public function sales()
    {
        return $this->hasMany(Sale::class, 'merchant_id', 'id');
    }

    /**
     * @return HasMany
     */
    public function customers()
    {
        return $this->hasMany(Customer::class, 'merchant_id', 'id');
    }

    /**
     * @return HasMany
     */
    public function customerAddresses()
    {
        return $this->hasMany(CustomerAddress::class, 'merchant_id', 'id');
    }

    /**
     * @return BelongsTo
     */
    public function location()
    {
        return $this->belongsTo(Location::class, 'location_id', 'id');
    }

    /**
     * @return BelongsTo
     */
    public function tenant()
    {
        return $this->belongsTo(Tenant::class, 'tenant_id', 'id');
    }

    /**
     * @return BelongsTo
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }

    /**
     * @return Builder|\Illuminate\Database\Eloquent\Model|mixed
     */
    public function getCountry()
    {
        $location = $this->getAttribute('location');
        if ($location instanceof Location) {
            return $location;
        }

        $tenant      = $this->getAttribute('tenant');
        $countryCode = $tenant->getSetting('COUNTRY');
        return Location::query()->firstWhere([
            'code' => trim($countryCode),
            'type' => Location::TYPE_COUNTRY
        ]);
    }

    /**
     * @return mixed|Currency|null
     */
    public function getCurrency()
    {
        $country = $this->getCountry();
        return ($country instanceof Location) ? $country->currency : null;
    }

    /**
     * @return BelongsToMany
     */
    public function products()
    {
        return $this->belongsToMany(Product::class, 'product_merchants');
    }

    /**
     * @return HasMany
     */
    public function productMerchants()
    {
        return $this->hasMany(ProductMerchant::class);
    }

    /**
     * @return HasMany
     */
    public function myProducts(): HasMany
    {
        return $this->hasMany(Product::class);
    }


    /**
     * @return HasMany
     */
    public function purchasingPackages()
    {
        return $this->hasMany(PurchasingPackage::class);
    }

    /**
     * @return HasMany
     */
    public function purchasingOrders()
    {
        return $this->hasMany(PurchasingOrder::class);
    }

    /**
     * @return HasMany
     */
    public function stores()
    {
        return $this->hasMany(Store::class, 'merchant_id', 'id');
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
        return Transaction::ACCOUNT_TYPE_MERCHANT;
    }

    /**
     * @return string
     */
    public function getAccountId()
    {
        return $this->getKey();
    }

    /** lọc theo status
     * @param $query
     * @param $status
     * @return mixed
     */
    public function scopeStatus($query, $status)
    {
        if (isset($status)) {
            if ($status) {
                return $query->where('status', true);
            } else
                return $query->where('status', false);
        } else
            return $query;
    }

    /** lọc theo location
     * @param $query
     * @param $location
     * @return mixed
     */
    public function scopeLocation($query, $location)
    {
        if ($location) {
            return $query->where('location_id', $location);
        } else
            return $query;
    }

    /** lọc theo code
     * @param $query
     * @param $code
     * @return mixed
     */
    public function scopeCode($query, $code)
    {
        if ($code) {
            return $query->where('code', $code);
        } else
            return $query;
    }

    /** lọc theo name
     * @param $query
     * @param $name
     * @return mixed
     */
    public function scopeName($query, $name)
    {
        if ($name) {
            return $query->where('name', $name);
        } else
            return $query;
    }

    /** lọc theo user name
     * @param $query
     * @param $usernameExploded
     * @return mixed
     */
    public function scopeUserName($query, $usernameExploded)
    {
        if ($usernameExploded) {
            return $query->where(function ($query) use ($usernameExploded) {
                foreach ($usernameExploded as $username) {
                    if ($username != '') {
                        $query->orWhere('username', 'LIKE', '%' . $username . '%');
                    }
                }
            });
        } else {
            return $query;
        }
    }

    /** lọc theo mã giới thiệu
     * @param $query
     * @param $ref
     * @return mixed
     */
    public function scopeRef($query, $ref)
    {
        if ($ref) {
            return $query->where('ref', $ref);
        } else
            return $query;
    }

    /**
     * @return BelongsTo
     */
    public function servicePack(): BelongsTo
    {
        return $this->belongsTo(ServicePack::class);
    }

    /**
     * @return BelongsTo
     */
    public function serviceCombo(): BelongsTo
    {
        return $this->belongsTo(ServiceCombo::class);
    }

    /** lọc theo creator id
     * @param $query
     * @param $creatorId
     * @return mixed
     */
    public function scopeCreatorId($query, $creatorId)
    {
        if ($creatorId) {
            return $query->where('creator_id', $creatorId);
        } else
            return $query;
    }

    /** lọc theo tenant id
     * @param $query
     * @param $tenantId
     * @return mixed
     */
    public function scopeTenantId($query, $tenantId)
    {
        if ($tenantId) {
            return $query->where('tenant_id', $tenantId);
        } else
            return $query;
    }

    /**
     * @return HasMany
     */
    public function storageFeeMerchantStatistics()
    {
        return $this->hasMany(StorageFeeMerchantStatistic::class);
    }

    /**
     * @return string|null
     */
    public function closingTimeStorage()
    {
        $storageFeeClosingTime = TenantSetting::query()->where([
            'key' => Tenant::SETTING_STORAGE_FEE_CLOSING_TIME,
            'tenant_id' => $this->tenant_id
        ])->first();
        if (empty($storageFeeClosingTime) || empty($storageFeeClosingTime->value[$this->location_id])) {
            return null;
        }
        return $storageFeeClosingTime->value[$this->location_id];
    }


    /**
     * @return HasMany
     */
    public function settings()
    {
        return $this->hasMany(MerchantSetting::class, 'merchant_id', 'id');
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

            return $settings;
        });

        return Arr::get($settings, $key, $default);
    }

    public function buildMerchantTransaction(string $action, string $transType, float $amount, MerchantTransObjInterface $merchantTransObj)
    {
        return new MerchantTransCreating($this, $action, $transType, $amount, $merchantTransObj);
    }
}

