<?php

namespace Modules\Stock\Models;

use App\Base\Model;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Order\Models\Order;
use Modules\Order\Models\OrderStock;
use Modules\Product\Models\Product;
use Modules\Product\Models\Sku;
use Modules\Stock\Commands\ChangeStock;
use Modules\Stock\Services\StockObjectInterface;
use Modules\User\Models\User;
use Modules\Warehouse\Models\Warehouse;
use Modules\Warehouse\Models\WarehouseArea;

/**
 * Class Stock
 *
 * @property int $id
 * @property int $tenant_id
 * @property int $product_id
 * @property int $sku_id
 * @property int $warehouse_id
 * @property int $warehouse_area_id
 * @property int $quantity
 * @property int|null $real_quantity
 * @property float total_storage_fee
 * @property Carbon created_at
 *
 * @property Product $product
 * @property Sku $sku
 * @property Warehouse $warehouse
 * @property WarehouseArea $warehouseArea
 * @property Collection orderStocks
 * @property Collection|StockLog[] $logs
 */
class Stock extends Model
{
    protected $table = 'stocks';

    protected $casts = [
        'storage_fee' => 'float'
    ];

    const ACTION_IMPORT                     = 'IMPORT'; // Nhập hàng
    const ACTION_IMPORT_BY_RETURN           = 'IMPORT_BY_RETURN'; // Nhập hàng hoàn
    const ACTION_IMPORT_FOR_PICKING         = 'IMPORT_FOR_PICKING'; // Nhập hàng do chuyen hàng ra xe bốc hàng
    const ACTION_IMPORT_FOR_CHANGE_POSITION = 'IMPORT_FOR_CHANGE_POSITION'; // Nhập hàng do chuyển hàng giữa các vị trí kho
    const ACTION_EXPORT                     = 'EXPORT'; // Xuât hàng
    const ACTION_EXPORT_FOR_ORDER           = 'EXPORT_FOR_ORDER'; // Xuât hàng cho đơn
    const ACTION_EXPORT_FOR_PICKING         = 'EXPORT_FOR_PICKING'; // Xuât hàng do chuyen hàng ra xe bốc hàng
    const ACTION_EXPORT_FOR_CHANGE_POSITION = 'EXPORT_FOR_CHANGE_POSITION'; // Xuất hàng do chuyển hàng giữa các vị trí kho
    const ACTION_RESERVE                    = 'RESERVE'; // Hold hàng cho đơn
    const ACTION_UNRESERVE                  = 'UNRESERVE'; // Hủy viêc hold hàng cho đơn
    const ACTION_UNRESERVE_BY_ERROR         = 'UNRESERVE_BY_ERROR'; // Trả lại tồn vì lỗi (trừ tồn 2 lần)
    const ACTION_RESERVE_BY_ERROR           = 'RESERVE_BY_ERROR'; // Trừ tồn tạm tính vì lỗi (huỷ đơn đã xuất)

    public static $temporaryActions = [
        self::ACTION_IMPORT,
        self::ACTION_EXPORT,
        self::ACTION_RESERVE,
        self::ACTION_RESERVE_BY_ERROR,
        self::ACTION_UNRESERVE,
        self::ACTION_UNRESERVE_BY_ERROR
    ];

    public static $currentActions = [
        self::ACTION_IMPORT,
        self::ACTION_EXPORT,
        self::ACTION_EXPORT_FOR_ORDER,
        self::ACTION_EXPORT_FOR_PICKING,
        self::ACTION_IMPORT_FOR_PICKING
    ];

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
    public function sku()
    {
        return $this->belongsTo(Sku::class, 'sku_id', 'id');
    }

    /**
     * @return BelongsTo
     */
    public function warehouse()
    {
        return $this->belongsTo(Warehouse::class, 'warehouse_id', 'id');
    }

    /**
     * @return BelongsTo
     */
    public function warehouseArea()
    {
        return $this->belongsTo(WarehouseArea::class, 'warehouse_area_id', 'id');
    }

    /**
     * @return HasMany
     */
    public function orderStocks()
    {
        return $this->hasMany(OrderStock::class);
    }

    /**
     * @return HasMany
     */
    public function logs()
    {
        return $this->hasMany(StockLog::class, 'stock_id', 'id');
    }

    /**
     * Perform action to change stock quantity
     *
     * @param string $action
     * @param int $quantity
     * @param User $creator
     * @return ChangeStock
     */
    public function do(string $action, int $quantity, User $creator)
    {
        return new ChangeStock($this, $action, $quantity, $creator);
    }

    /**
     * Nhập hàng
     *
     * @param int $quantity
     * @param User $creator
     * @param StockObjectInterface $object
     * @param string $action
     * @return ChangeStock
     */
    public function import(int $quantity, User $creator, StockObjectInterface $object, string $action = self::ACTION_IMPORT)
    {
        return $this->do($action, $quantity, $creator)->for($object);
    }

    /**
     * Xuất hàng
     *
     * @param int $quantity
     * @param User $creator
     * @param StockObjectInterface $object
     * @param string $action
     * @return ChangeStock
     */
    public function export(int $quantity, User $creator, StockObjectInterface $object, string $action = self::ACTION_EXPORT)
    {
        return $this->do($action, $quantity, $creator)->for($object);
    }

    /**
     * Số lượng sku đang hold của stock
     *
     * @return mixed|int
     */
    public function holdingQuantity()
    {
        return $this->orderStocks()->join('orders', 'order_stocks.order_id', 'orders.id')
            ->whereIn('orders.status', [
                Order::STATUS_WAITING_INSPECTION,
                Order::STATUS_WAITING_CONFIRM,
                Order::STATUS_WAITING_PROCESSING,
                Order::STATUS_WAITING_PICKING,
                Order::STATUS_WAITING_PACKING,
                Order::STATUS_WAITING_DELIVERY
            ])
            ->sum('order_stocks.quantity');
    }

    /** filter theo sku_id
     * @param $query
     * @param $skuId
     * @return mixed
     */
    public function scopeSkuId($query, $skuId)
    {
        if ($skuId) {
            return $query->where('sku_id', $skuId);
        } else
            return $query;
    }

    /** filter theo sku_name
     * @param $query
     * @param $skuId
     * @return mixed
     */
    public function scopeSkuName($query, $skuName, $skuCode)
    {
        if ($skuName && !$skuCode) {
            return $query->join('skus', 'skus.id', '=', 'stocks.sku_id')->where('skus.name', $skuName);
        } else if ($skuName && $skuCode) {
            return $query->where('skus.name', $skuName);
        }
        return $query;
    }

    /** filter theo sku_code
     * @param $query
     * @param $skuId
     * @return mixed
     */
    public function scopeSkuCode($query, $skuCode)
    {
        if ($skuCode) {
            return $query->join('skus', 'skus.id', '=', 'stocks.sku_id')->where('skus.code', $skuCode);
        } else
            return $query;
    }

    /** filter theo warehouse_id
     * @param $query
     * @param $skuId
     * @return mixed
     */
    public function scopeWarehouseId($query, $warehouseId)
    {
        if ($warehouseId) {
            return $query->where('warehouse_id', $warehouseId);
        } else
            return $query;
    }

    /** filter theo warehouse_area_id
     * @param $query
     * @param $skuId
     * @return mixed
     */
    public function scopeWarehouseAreaId($query, $warehouseAreaId)
    {
        if ($warehouseAreaId) {
            return $query->where('warehouse_area_id', $warehouseAreaId);
        } else
            return $query;
    }

    /** filter theo quantity
     * @param $query
     * @param bool $outOfStock
     * @return mixed
     */
    public function scopeOutOfStock($query, $outOfStock)
    {
        if (isset($outOfStock)) {
            if ($outOfStock) {
                return $query->where('quantity', '>', 0)
                    ->orWhere('real_quantity', '>', 0);
            }
            return $query
                ->where(function ($query) {
                    return $query->where('stocks.quantity', 0)
                        ->Where('stocks.real_quantity', 0);
                });
        }
        return $query;
    }

    /** filter theo merchant_id
     * @param $query
     * @param $skuId
     * @return mixed
     */
    public function scopeMerchantId($query, $merchantId, $skuCode, $skuName)
    {
        if ($merchantId && !$skuCode && !$skuName) {
            return $query->join('skus', 'skus.id', '=', 'stocks.sku_id')
                ->join('merchants', 'merchants.id', '=', 'skus.merchant_id')
                ->where('merchants.id', $merchantId);
        } else if ($merchantId && ($skuCode || $skuName)) {
            return $query->join('merchants', 'merchants.id', '=', 'skus.merchant_id')
                ->where('merchants.id', $merchantId);
        }
        return $query;
    }

}
