<?php

namespace Modules\OrderPacking\Models;

use App\Base\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Order\Models\Order;
use Modules\Product\Models\Sku;
use Modules\Stock\Models\StockLog;
use Modules\Stock\Services\StockObjectInterface;
use Modules\Warehouse\Models\Warehouse;
use Modules\Warehouse\Models\WarehouseArea;

/**
 * Class PickingSessionPiece
 * @package Modules\OrderPacking\Models
 *
 * @property int id
 * @property int tenant_id
 * @property int picking_session_id
 * @property int order_id
 * @property int order_packing_id
 * @property int warehouse_id
 * @property int warehouse_area_id
 * @property int sku_id
 * @property int quantity
 * @property int ranking
 * @property int ranking_order
 * @property boolean is_picked
 *
 * @property Order order
 * @property Sku sku
 * @property WarehouseArea warehouseArea
 * @property PickingSession pickingSession
 */
class PickingSessionPiece extends Model implements StockObjectInterface
{
    protected $casts = [
        'is_picked' => 'boolean'
    ];

    public function getObjectType()
    {
        return StockLog::OBJECT_PICKING_SESSION_PIECE;
    }

    public function getObjectId()
    {
        return $this->getKey();
    }

    /**
     * @return BelongsTo
     */
    public function warehouse()
    {
        return $this->belongsTo(Warehouse::class);
    }

    /**
     * @return BelongsTo
     */
    public function warehouseArea()
    {
        return $this->belongsTo(WarehouseArea::class);
    }

    /**
     * @return BelongsTo
     */
    public function order()
    {
        return $this->belongsTo(Order::class);
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
        return $this->belongsTo(Sku::class);
    }

    /**
     * @return BelongsTo
     */
    public function pickingSession()
    {
        return $this->belongsTo(PickingSession::class);
    }
}
