<?php

namespace Modules\Order\Models;

use App\Base\Model;
use Modules\Product\Models\Sku;
use Modules\Stock\Models\Stock;
use Modules\Warehouse\Models\Warehouse;
use Modules\Warehouse\Models\WarehouseArea;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Class OrderStock
 *
 * @property int $id
 * @property int $tenant_id
 * @property int $order_id
 * @property int $stock_id
 * @property int $sku_id
 * @property int $warehouse_id
 * @property int $warehouse_area_id
 * @property int $changing_stock_id
 * @property int $quantity
 * @property int $packaged_quantity
 * @property-read Warehouse|null $warehouse
 * @property-read WarehouseArea|null $warehouseArea
 * @property-read Sku|null $sku
 * @property-read Stock|null $stock
 * @property-read Order|null order
 */
class OrderStock extends Model
{
    protected $table = 'order_stocks';

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
        return $this->belongsTo(Stock::class, 'stock_id', 'id');
    }

    /**
     * @return BelongsTo
     */
    public function order()
    {
        return $this->belongsTo(Order::class);
    }

}
