<?php

namespace Modules\Store\Models;

use App\Base\Model;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Arr;
use Modules\Marketplace\Services\Marketplace;
use Modules\Marketplace\Services\MarketplaceException;
use Modules\Marketplace\Services\MarketplaceInterface;
use Modules\Marketplace\Services\StoreConnectionInterface;
use Modules\Merchant\Models\Merchant;
use Modules\Service;
use Modules\Shopee\Services\ShopeeShopApiInterface;
use Modules\Store\Services\StoreEvent;
use Modules\Tenant\Models\Tenant;
use Modules\User\Models\User;
use Modules\Warehouse\Models\Warehouse;

/**
 * Class Store
 *
 * @property int id
 * @property int tenant_id
 * @property int merchant_id
 * @property int warehouse_id
 * @property string marketplace_code
 * @property string marketplace_store_id
 * @property string name
 * @property string description
 * @property array settings
 * @property string product_sync
 * @property string order_sync
 * @property string status
 * @property Carbon created_at
 * @property Carbon updated_at
 * @property-read Tenant tenant
 * @property-read Merchant merchant
 * @property-read StoreSku[]|Collection storeSkus
 * @property-read Warehouse|null warehouse
 */
class Store extends Model
{
    protected $table = 'stores';

    protected $casts = [
        'settings' => 'json',
    ];

    protected $hidden = [
        'settings',
    ];

    // lưu lại thời gian cập nhật cuối cùng của sản phẩm shopee
    const SETTING_SHOPEE_PRODUCT_LAST_UPDATED_AT     = 'SHOPEE_PRODUCT_LAST_UPDATED_AT';
    const SETTING_LAZADA_PRODUCT_LAST_UPDATED_AT     = 'LAZADA_PRODUCT_LAST_UPDATED_AT';
    const SETTING_KIOTVIET_PRODUCT_LAST_UPDATED_AT   = 'KIOTVIET_PRODUCT_LAST_UPDATED_AT';
    const SETTING_TIKI_PRODUCT_LAST_UPDATED_AT       = 'TIKI_PRODUCT_LAST_UPDATED_AT';
    const SETTING_TIKTOKSHOP_PRODUCT_LAST_UPDATED_AT = 'TIKTOKSHOP_PRODUCT_LAST_UPDATED_AT';
    const SETTING_SHOPBASE_PRODUCT_LAST_UPDATED_AT   = 'SHOPBASE_PRODUCT_LAST_UPDATED_AT';

    const PRODUCT_SYNC_AUTO = 'AUTO';

    const ORDER_SYNC_AUTO = 'AUTO';

    const STATUS_ACTIVE = 'ACTIVE';
    const STATUS_INACTIVE = 'INACTIVE';
    const STATUS_DISCONNECTED = 'DISCONNECTED';

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
    public function merchant()
    {
        return $this->belongsTo(Merchant::class, 'merchant_id', 'id');
    }

    /**
     * @return BelongsTo
     */
    public function warehouse()
    {
        return $this->belongsTo(Warehouse::class, 'warehouse_id', 'id');
    }

    /**
     * @return HasMany
     */
    public function storeSkus()
    {
        return $this->hasMany(StoreSku::class, 'store_id', 'id');
    }

    /**
     * @param string $key
     * @param null|mixed $default
     * @return mixed
     */
    public function getSetting($key, $default = null)
    {
        $settings = $this->getAttribute('settings');

        return Arr::get($settings, $key, $default);
    }

    /**
     * @return MarketplaceInterface|null
     */
    public function marketplace()
    {
        return Service::marketplace()->marketplace($this->getAttribute('marketplace_code'));
    }

    /**
     * Perform disconnect store
     *
     * @param User|null $creator
     */
    public function disconnect(User $creator = null)
    {
        $this->update(['status' => Store::STATUS_DISCONNECTED]);
        $this->logActivity(StoreEvent::DISCONNECTED, $creator ?? Service::user()->getSystemUserDefault());
    }

    /**
     * @return bool
     */
    public function isDisconnected()
    {
        return $this->getAttribute('status') === static::STATUS_DISCONNECTED;
    }

    /**
     * Connect to store
     *
     * @return StoreConnectionInterface
     */
    public function connect()
    {
        return $this->marketplace()->connect($this);
    }

    /**
     * @param string $marketplaceCode
     * @return StoreConnectionInterface
     * @throws MarketplaceException
     */
    protected function connectMarketplace($marketplaceCode)
    {
        if ($this->getAttribute('marketplace_code') === $marketplaceCode) {
            return $this->connect();
        }

        throw new MarketplaceException("The store {$this->getKey()} not belong to {$marketplaceCode}");
    }

    /**
     * @return ShopeeShopApiInterface|StoreConnectionInterface
     * @throws MarketplaceException
     */
    public function shopeeApi()
    {
        return $this->connectMarketplace(Marketplace::CODE_SHOPEE);
    }

    public function lazadaApi()
    {
        return $this->connectMarketplace(Marketplace::CODE_LAZADA);

    }

    public function setAttributeSetting($value)
    {
        $this->attributes['shop_name'] = $value;
    }

    /** lấy name store của 1 merchant
     * @return string
     */
    public function getNameStore()
    {
        if (!$this->name){
            $this->name = $this->marketplace_store_id;
        }
        $nameStore = $this->name;

        return $nameStore;
    }
}
