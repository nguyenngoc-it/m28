<?php

namespace Modules\Product\Models;

use App\Base\Model;
use App\Traits\ModelInteractsWithWebhook;
use Gobiz\Support\Traits\CachedPropertiesTrait;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;
use Modules\Category\Models\Category;
use Modules\Product\Services\SkuWebhookEventPublisher;
use Modules\PurchasingOrder\Models\PurchasingVariant;
use Modules\Service\Models\ServicePrice;
use Modules\Service\Models\StorageFeeSkuStatistic;
use Modules\Stock\Models\Stock;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Stock\Models\StockLog;
use Modules\Stock\Services\StockObjectInterface;
use Modules\Store\Models\StoreSku;
use Modules\Supplier\Models\Supplier;
use Modules\Tenant\Models\Tenant;
use Modules\User\Models\User;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Warehouse\Models\Warehouse;
use Modules\WarehouseStock\Models\WarehouseStock;

// add soft delete

/**
 * Class Sku
 *
 * @property int $id
 * @property int $tenant_id
 * @property int $product_id
 * @property int $category_id
 * @property int $creator_id
 * @property int merchant_id
 * @property int supplier_id
 * @property int $unit_id
 * @property string $code
 * @property string $ref
 * @property string $status
 * @property string $name
 * @property string $barcode
 * @property array $options
 * @property float $tax
 * @property float $cost_price
 * @property float $wholesale_price
 * @property float $retail_price
 * @property int $stock
 * @property int $real_stock
 * @property string $color
 * @property string $size
 * @property string $type
 * @property float weight
 * @property float length
 * @property float width
 * @property float height
 * @property boolean confirm_weight_volume
 * @property array images
 * @property string product_id_origin
 * @property string sku_id_origin
 * @property int safety_stock
 * @property boolean is_batch
 * @property string logic_batch
 *
 * @property string seller_ref (non table)
 *
 * @property Supplier|null supplier
 * @property Product|null $product
 * @property Tenant|null $tenant
 * @property User|null $creator
 * @property Category|null $category
 * @property Unit|null $unit
 * @property \Illuminate\Database\Eloquent\Collection $stocks
 * @property SkuPrice[]|Collection $prices
 * @property PurchasingVariant[]|Collection purchasingVariants
 * @property \Illuminate\Database\Eloquent\Collection warehouseStocks
 * @property \Illuminate\Database\Eloquent\Collection storageFeeSkuStatistics
 * @property \Illuminate\Database\Eloquent\Collection servicePrices
 * @property Sku skuParent
 * @property \Illuminate\Database\Eloquent\Collection skuChildren
 * @property BatchOfGood|null batchOfGood
 * @property \Illuminate\Database\Eloquent\Collection batchOfGoods
 */
class Sku extends Model implements StockObjectInterface
{
    use SoftDeletes;

    // add soft delete

    use ModelInteractsWithWebhook;
    use CachedPropertiesTrait;

    protected $table = 'skus';

    protected $casts = [
        'images' => 'array',
        'options' => 'array',
        'tax' => 'float',
        'cost_price' => 'float',
        'wholesale_price' => 'float',
        'retail_price' => 'float',
        'weight' => 'float',
        'length' => 'float',
        'width' => 'float',
        'height' => 'float',
        'confirm_weight_volume' => 'boolean',
        'is_batch' => 'boolean',
    ];

    const STATUS_NEW               = 'NEW'; // Mới, Khởi Tạo
    const STATUS_WAITING_FOR_QUOTE = 'WAITING_FOR_QUOTE'; //chờ báo giá
    const STATUS_WAITING_CONFIRM   = 'WAITING_CONFIRM'; //chờ xác nhận
    const STATUS_ON_SELL           = 'ON_SELL'; //Đang bán
    const STATUS_STOP_SELLING      = 'STOP_SELLING'; //Dừng bán


    public static $statusList = [
        self::STATUS_NEW,
        self::STATUS_WAITING_FOR_QUOTE,
        self::STATUS_WAITING_CONFIRM,
        self::STATUS_ON_SELL,
        self::STATUS_STOP_SELLING
    ];

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
    public function stocks()
    {
        return $this->hasMany(Stock::class, 'sku_id', 'id');
    }

    /**
     * @return HasMany
     */
    public function storeSkus()
    {
        return $this->hasMany(StoreSku::class, 'sku_id', 'id');
    }


    /**
     * @return BelongsTo
     */
    public function creator()
    {
        return $this->belongsTo(User::class, 'creator_id', 'id');
    }

    /**
     * @return BelongsTo
     */
    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id', 'id');
    }

    /**
     * @return BelongsTo
     */
    public function category()
    {
        return $this->belongsTo(Category::class, 'category_id', 'id');
    }


    /**
     * @return BelongsTo
     */
    public function unit()
    {
        return $this->belongsTo(Unit::class, 'unit_id', 'id');
    }


    /**
     * @return HasMany
     */
    public function prices()
    {
        return $this->hasMany(SkuPrice::class, 'sku_id', 'id');
    }

    /**
     * @return HasMany
     */
    public function purchasingVariants()
    {
        return $this->hasMany(PurchasingVariant::class, 'sku_id', 'id');
    }

    /**
     * @return BelongsTo
     */
    public function supplier()
    {
        return $this->belongsTo(Supplier::class);
    }

    /**
     * @return BelongsToMany
     */
    public function optionValues()
    {
        return $this->belongsToMany(ProductOptionValue::class, 'sku_option_values', 'sku_id', 'product_option_value_id');
    }


    /**
     * @return string
     */
    public function makeName()
    {
        $product          = $this->product;
        $name             = $product->name;
        $optionValueNames = $this->optionValues()->pluck('label')->toArray();
        if (!empty($optionValueNames)) {
            $name = $name . ' - ' . implode(", ", $optionValueNames);
        }

        return $name;
    }

    /**
     * @return HasMany
     */
    public function warehouseStocks()
    {
        return $this->hasMany(WarehouseStock::class, 'sku_id');
    }

    /**
     * Get object type
     *
     * @return string
     */
    public function getObjectType()
    {
        return StockLog::OBJECT_SKU;
    }

    /**
     * Get object id
     *
     * @return string
     */
    public function getObjectId()
    {
        return $this->getKey();
    }

    /**
     * @return HasMany
     */
    public function storageFeeSkuStatistics()
    {
        return $this->hasMany(StorageFeeSkuStatistic::class);
    }

    /**
     * @param $sellChannel
     * @return void
     */
    public function setSellChannel($sellChannel)
    {
        $this->attributes['sell_channel'] = $sellChannel;
    }

    /**
     * Tổng phí lưu kho của sku ở 1 kho
     *
     * @param Warehouse $warehouse
     * @return float|mixed
     */
    public function storageFeeByWarehouse(Warehouse $warehouse)
    {
        return $this->storageFeeSkuStatistics->where('warehouse_id', $warehouse->id)->sum('fee');
    }

    /**
     * @return BelongsToMany
     */
    public function servicePrices()
    {
        return $this->belongsToMany(ServicePrice::class, 'sku_service_prices');
    }

    /**
     * Đồng bộ tồn kho
     *
     * @return bool
     */
    public function syncStockQuantity()
    {
        return $this->update([
            'stock' => $this->stocks()->sum('quantity'),
            'real_stock' => $this->stocks()->sum('real_quantity')
        ]);
    }

    /**
     * @return SkuWebhookEventPublisher
     */
    public function webhook()
    {
        return $this->getCachedProperty('webhook', function () {
            return new SkuWebhookEventPublisher($this);
        });
    }

    /**
     * @param $type
     * @return int|mixed
     */
    public function getQuantitySkus($type)
    {
        $quantity = null;
        if ($type == 'quantity') {
            $quantity = Stock::query()->where('sku_id', $this->id)
                ->sum('stocks.quantity');
        }
        if ($type == 'real_quantity') {
            $quantity = Stock::query()->where('sku_id', $this->id)
                ->sum('stocks.real_quantity');
        }
        return $quantity;
    }

    /**
     * Scope a query to only include product of a given merchant.
     *
     * @param Builder $query
     * @param mixed $merchantId
     * @return Builder
     */
    public function scopeOfMerchant(Builder $query, $merchantId)
    {
        if ($merchantId) {
            return $query->where('skus.merchant_id', $merchantId);
        } else {
            return $query;
        }
    }

    /**
     *
     *
     * @param Builder $query
     * @param mixed $keyword
     * @return Builder
     */
    public function scopeKeyword(Builder $query, $keyword)
    {
        if ($keyword) {
            return $query->where(function ($query) use ($keyword) {
                return $query->where('skus.name', 'LIKE', '%' . $keyword . '%')
                    ->orWhere('skus.code', 'LIKE', '%' . $keyword . '%');
            });
        } else {
            return $query;
        }
    }

    /**
     * @return BelongsTo
     */
    public function skuParent()
    {
        return $this->belongsTo(Sku::class, 'sku_parent_id');
    }

    /**
     * @return HasMany
     */
    public function skuChildren()
    {
        return $this->hasMany(Sku::class, 'sku_parent_id');
    }

    /**
     * @return BelongsTo
     */
    public function batchOfGood()
    {
        return $this->belongsTo(BatchOfGood::class);
    }

    /**
     * @return HasMany
     */
    public function batchOfGoods()
    {
        return $this->hasMany(BatchOfGood::class);
    }

}
