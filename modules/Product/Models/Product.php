<?php

namespace Modules\Product\Models;

use App\Base\Model;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\DB;
use Modules\Category\Models\Category;
use Modules\Merchant\Models\Merchant;
use Modules\Service\Models\Service;
use Modules\Service\Models\ServicePrice;
use Modules\Stock\Models\Stock;
use Modules\Supplier\Models\Supplier;
use Modules\Tenant\Models\Tenant;
use Modules\User\Models\User;

/**
 * Class Product
 *
 * @property int $id
 * @property int $tenant_id
 * @property int $creator_id
 * @property int merchant_id
 * @property int supplier_id
 * @property string $code
 * @property string $name
 * @property string $status
 * @property string $description
 * @property string $image
 * @property array $images
 * @property int $category_id
 * @property int $unit_id
 * @property boolean dropship
 * @property double weight
 * @property double height
 * @property double width
 * @property double length
 * @property string source
 * @property string product_id_origin
 * @property string sku_id_origin
 *
 * @property Collection $skus
 * @property Category|null $category
 * @property Unit|null $unit
 * @property Tenant|null $tenant
 * @property User|null $creator
 * @property Merchant|null merchant
 * @property Supplier|null supplier
 * @property Carbon created_at
 * @property Carbon updated_at
 *
 * @property ProductOption[]|Collection $productOptions
 * @property ProductPrice[]|Collection productPrices
 * @property ProductMerchant[]|Collection productMerchants
 * @property ProductOptionValue[]|Collection $productOptionValues
 * @property Collection merchants
 * @property Collection servicePrices
 * @property Collection services
 * @property Collection productServicePrices
 *
 */
class Product extends Model
{
    const SOURCE_SHOPEE            = 'SHOPEE';
    const SOURCE_KIOTVIET          = 'KIOTVIET';
    const SOURCE_LAZADA            = 'LAZADA';

    const STATUS_NEW               = 'NEW'; // Mới, Khởi Tạo
    const STATUS_WAITING_FOR_QUOTE = 'WAITING_FOR_QUOTE'; //chờ báo giá
    const STATUS_WAITING_CONFIRM   = 'WAITING_CONFIRM'; //chờ xác nhận
    const STATUS_ON_SELL           = 'ON_SELL'; //Đang bán
    const STATUS_STOP_SELLING      = 'STOP_SELLING'; //Dừng bán

    protected $table = 'products';

    protected $casts = [
        'images' => 'array',
        'dropship' => 'boolean',
    ];

    public static $statusList = [
        self::STATUS_NEW,
        self::STATUS_WAITING_FOR_QUOTE,
        self::STATUS_WAITING_CONFIRM,
        self::STATUS_ON_SELL,
        self::STATUS_STOP_SELLING
    ];

    protected $hasJoinTable = [];

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
    public function skus()
    {
        return $this->hasMany(Sku::class, 'product_id', 'id');
    }

    /**
     * @return HasMany
     */
    public function skusActice()
    {
        return $this->skus()->where('status', '!=', Sku::STATUS_STOP_SELLING);
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
     * @return BelongsTo
     */
    public function creator()
    {
        return $this->belongsTo(User::class, 'creator_id', 'id');
    }


    /**
     * @return BelongsTo
     */
    public function merchant()
    {
        return $this->belongsTo(Merchant::class, 'merchant_id', 'id');
    }

    /**
     * @return HasMany
     */
    public function productOptions()
    {
        return $this->hasMany(ProductOption::class, 'product_id', 'id');
    }

    /**
     * @return HasMany
     */
    public function productOptionValues()
    {
        return $this->hasMany(ProductOptionValue::class, 'product_id', 'id');
    }

    /**
     * @return Collection|\Illuminate\Support\Collection
     */
    public function options()
    {
        $productOptions = $this->productOptions()->with('options')->get();

        return $productOptions->map(function (ProductOption $productOption) {
            return [
                'productOption' => $productOption,
                'options' => $productOption->options
            ];
        });
    }

    /**
     * @return HasMany
     */
    public function stocks()
    {
        return $this->hasMany(Stock::class, 'product_id', 'id');
    }

    /**
     * @return BelongsToMany
     */
    public function merchants()
    {
        return $this->belongsToMany(Merchant::class, 'product_merchants')->withTimestamps();
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
    public function productPrices()
    {
        return $this->hasMany(ProductPrice::class);
    }

    /**
     * @return ProductPrice|null
     */
    public function productPriceActive()
    {
        $productPrice = $this->productPriceUsing();
        if (!$productPrice instanceof ProductPrice) {
            $productPrice = $this->productPrices()->where('status', ProductPrice::STATUS_WAITING_CONFIRM)
                ->orderBy('id', 'desc')->first();
        }
        return $productPrice;
    }

    /**
     * @return \Illuminate\Database\Eloquent\Model|HasMany|null
     */
    public function productPriceUsing()
    {
        return $this->productPrices()->firstWhere('status', ProductPrice::STATUS_ACTIVE);
    }

    /**
     * @return BelongsToMany
     */
    public function services()
    {
        return $this->belongsToMany(Service::class, 'product_service_prices');
    }

    /**
     * @return BelongsToMany
     */
    public function servicePrices(): BelongsToMany
    {
        return $this->belongsToMany(ServicePrice::class, 'product_service_prices');
    }

    /**
     * @return HasMany
     */
    public function productServicePrices()
    {
        return $this->hasMany(ProductServicePrice::class);
    }

    /**
     * @return BelongsTo
     */
    public function supplier()
    {
        return $this->belongsTo(Supplier::class);
    }

    /**
     * @param $status
     * @return bool
     */
    public function canChangeStatus($status)
    {
        $dropShip = $this->getAttribute('dropship');

        switch ($status) {
            case self::STATUS_WAITING_FOR_QUOTE:
            {
                return ($dropShip && $this->getAttribute('status') == self::STATUS_NEW) ? true : false;
            }
            case self::STATUS_WAITING_CONFIRM:
            {
                return ($dropShip && $this->getAttribute('status') == self::STATUS_WAITING_FOR_QUOTE) ? true : false;
            }
            case self::STATUS_STOP_SELLING:
            case self::STATUS_ON_SELL:
            {
                return true;
            }
        }

        return false;
    }

    /**
     * Sản phẩm được phép tạo báo giá hay không
     * @return bool
     */
    public function canCreatePrice()
    {
        $dropShip = $this->getAttribute('dropship');
        $status   = $this->getAttribute('status');

        return ($dropShip && !in_array($status, [self::STATUS_NEW, self::STATUS_STOP_SELLING])) ? true : false;
    }

    /**
     * Lấy đơn giá của sản phẩm với 1 dịch vụ
     *
     * @param Service $service
     *
     * @return ServicePrice|mixed
     */
    public function servicePriceOfService(Service $service)
    {
        return $this->servicePrices->where('service_code', $service->code)->where('tenant_id', $service->tenant_id)->first();
    }

    /**
     * Scope a query to only include product of a given creator.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  mixed  $creatorId
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeOfCreator($query, $creatorId)
    {
        if ($creatorId) {
            return $query->where('products.creator_id', $creatorId);
        } else {
            return $query;
        }
    }

    /**
     * Scope a query to only include product of a given merchant.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  mixed  $merchantId
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeOfMerchant($query, $merchantId)
    {
        if ($merchantId) {
            return $query->where('products.merchant_id', $merchantId);
        } else {
            return $query;
        }
    }

    /**
     * Scope a query to product by code
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  mixed  $code
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeCode($query, $code)
    {
        if ($code) {
            return $query->where('products.code', $code);
        } else {
            return $query;
        }
    }

    /**
     * Scope a query to product by name
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  mixed  $name
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeName($query, $name)
    {
        if ($name) {
            return $query->where('products.name', 'like', "%{$name}%");
        } else {
            return $query;
        }
    }

    /**
     * Scope a query to product by Category
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  mixed  $categoryId
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeCategory($query, $categoryId)
    {
        if ($categoryId) {
            return $query->where('products.category_id', $categoryId);
        } else {
            return $query;
        }
    }

    /**
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  mixed  $status
     * @return \Illuminate\Database\Eloquent\Builder
     */
    protected function scopeStatus($query, $status)
    {
        if ($status) {
            if (!in_array('skus', $this->hasJoinTable)) {
                $query = $query->join('skus', 'products.id', 'skus.product_id');
                $this->hasJoinTable['skus'] = 'skus';
            }
            return $query->where('skus.status', $status);
        } else {
            return $query;
        }
    }

    /**
     * Thiếu hàng xuất
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  mixed  $lackOfExportGoods
     * @return \Illuminate\Database\Eloquent\Builder
     */
    protected function scopeLackOfExportGoods($query, $lackOfExportGoods)
    {
        if ($lackOfExportGoods) {

            if (!in_array('skus', $this->hasJoinTable)) {
                $query = $query->join('skus', 'products.id', 'skus.product_id');
                $this->hasJoinTable['skus'] = 'skus';
            }

            if (!in_array('warehouse_stocks', $this->hasJoinTable)) {
                $query = $query->join('warehouse_stocks', 'skus.id', 'warehouse_stocks.sku_id');
                $this->hasJoinTable['warehouse_stocks'] = 'warehouse_stocks';
            }

            return $query->where('skus.status', Sku::STATUS_ON_SELL)
                ->whereRaw('warehouse_stocks.real_quantity + warehouse_stocks.purchasing_quantity < warehouse_stocks.packing_quantity')
                ->groupBy('skus.id');
        } else {
            return $query;
        }
    }

    /**
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  mixed  $nearlySoldOut
     * @return \Illuminate\Database\Eloquent\Builder
     */
    protected function scopeNearlySoldOut($query, $nearlySoldOut)
    {
        if ($nearlySoldOut) {
            if (!in_array('skus', $this->hasJoinTable)) {
                $query = $query->join('skus', 'products.id', 'skus.product_id');
                $this->hasJoinTable['skus'] = 'skus';
            }
            return $query->where('skus.status', Sku::STATUS_ON_SELL)
                            ->where('skus.real_stock', '>', 0)
                            ->whereRaw(DB::raw('skus.real_stock <= skus.safety_stock'));
        } else {
            return $query;
        }
    }

    /**
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  mixed  $outOfStock
     * @return \Illuminate\Database\Eloquent\Builder
     */
    protected function scopeOutOfStock($query, $outOfStock)
    {
        if ($outOfStock) {
            if (!in_array('skus', $this->hasJoinTable)) {
                $query = $query->join('skus', 'products.id', 'skus.product_id');
                $this->hasJoinTable['skus'] = 'skus';
            }

            if (!in_array('warehouse_stocks', $this->hasJoinTable)) {
                $query = $query->join('warehouse_stocks', 'skus.id', 'warehouse_stocks.sku_id');
                $this->hasJoinTable['warehouse_stocks'] = 'warehouse_stocks';
            }
            return $query->where('skus.status', Sku::STATUS_ON_SELL)
                        ->where('warehouse_stocks.real_quantity', '<=', 0)
                        ->groupBy('skus.id');
        } else {
            return $query;
        }
    }

    /**
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  mixed  $notYetInStock
     * @return \Illuminate\Database\Eloquent\Builder
     */
    protected function scopeNotYetInStock($query, $notYetInStock)
    {
        if ($notYetInStock) {
            if (!in_array('skus', $this->hasJoinTable)) {
                $query = $query->join('skus', 'products.id', 'skus.product_id');
                $this->hasJoinTable['skus'] = 'skus';
            }

            if (!in_array('warehouse_stocks', $this->hasJoinTable)) {
                $query = $query->leftJoin('warehouse_stocks', 'skus.id', 'warehouse_stocks.sku_id');
                $this->hasJoinTable['warehouse_stocks'] = 'warehouse_stocks';
            }
            return $query->where('skus.status', Sku::STATUS_ON_SELL)
                        ->whereNull('warehouse_stocks.id')
                        ->groupBy('skus.id');
        } else {
            return $query;
        }
    }

    /**
     * Scope a query to only include users of a given type.
     *
     * @param Builder $query
     * @param mixed $type
     * @return Builder
     */
    public function scopeCreatedAt($query, $createdAt)
    {
        $createdAtFromRaw = data_get($createdAt, 'from');
        $createdAtToRaw   = data_get($createdAt, 'to');

        $createdAtFrom = Carbon::parse($createdAtFromRaw)->startOfDay();
        $createdAtTo   = Carbon::parse($createdAtToRaw)->endOfDay();
        if ($createdAtToRaw && $createdAtToRaw) {
            return $query->whereBetween('products.created_at', [$createdAtFrom, $createdAtTo]);
        } else {
            return $query;
        }
    }

    /**
     * Scope a query to only include users of a given type.
     *
     * @param Builder $query
     * @param mixed $type
     * @return Builder
     */
    public function scopeWarehouseId($query, $warehouseId)
    {
        if (!is_null($warehouseId)) {
            if (!in_array('skus', $this->hasJoinTable)) {
                $query = $query->join('skus', 'products.id', 'skus.product_id');
                $this->hasJoinTable['skus'] = 'skus';
            }

            if (!in_array('warehouse_stocks', $this->hasJoinTable)) {
                $query = $query->join('warehouse_stocks', 'skus.id', 'warehouse_stocks.sku_id');
                $this->hasJoinTable['warehouse_stocks'] = 'warehouse_stocks';
            }
            return $query->where('warehouse_stocks.warehouse_id', $warehouseId)
                        ->groupBy('skus.id');

        } else {
            return $query;
        }
    }
}
