<?php

namespace Modules\OrderPacking\Models;

use App\Base\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Product\Models\Sku;
use Modules\Stock\Models\Stock;
use Modules\Stock\Models\StockLog;
use Modules\Stock\Services\StockObjectInterface;
use Modules\Warehouse\Models\WarehouseArea;

/**
 * Class OrderPackingItem
 * @package Modules\OrderPacking\Models
 *
 * @property int id
 * @property int order_id
 * @property int order_packing_id
 * @property int order_stock_id
 * @property int sku_id
 * @property float price
 * @property int quantity
 * @property int quantity_packaged
 * @property float values
 * @property int stock_id
 * @property int warehouse_id
 * @property int warehouse_area_id
 *
 * @property OrderPacking|null orderPacking
 * @property Sku|null $sku
 * @property Stock|null stock
 * @property WarehouseArea|null warehouseArea
 */
class OrderPackingItem extends Model implements StockObjectInterface
{
    protected $casts = [
        'price' => 'double',
        'values' => 'double',
    ];

    /**
     * Get object type
     *
     * @return string
     */
    public function getObjectType()
    {
        return StockLog::OBJECT_ORDER_PACKING_ITEM;
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
     * @return BelongsTo
     */
    public function orderPacking()
    {
        return $this->belongsTo(OrderPacking::class);
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
    public function stock()
    {
        return $this->belongsTo(Stock::class);
    }

    /**
     * @return BelongsTo
     */
    public function warehouseArea()
    {
        return $this->belongsTo(WarehouseArea::class);
    }
}
