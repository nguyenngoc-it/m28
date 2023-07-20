<?php

namespace Modules\Document\Models;

use App\Base\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Product\Models\Sku;
use Modules\Stock\Models\StockLog;
use Modules\Stock\Services\StockObjectInterface;
use Modules\Warehouse\Models\Warehouse;
use Modules\Warehouse\Models\WarehouseArea;
use Modules\Stock\Models\Stock;

/**
 * Class DocumentSkuInventory
 * @package Modules\Document\Models
 *
 * @property int document_id
 * @property int sku_id
 * @property int stock_id
 * @property int warehouse_id
 * @property int warehouse_area_id
 * @property int quantity_in_stock
 * @property int quantity_checked
 * @property int quantity_balanced
 * @property int quantity_in_stock_before_balanced
 * @property string explain
 *
 * @property Document document
 * @property Warehouse warehouse
 * @property WarehouseArea warehouseArea
 * @property Sku sku
 * @property Stock|null stock
 */
class DocumentSkuInventory extends Model implements StockObjectInterface
{
    const TYPE_SKU_CODE = 'SKU_CODE';
    const TYPE_SKU_REF  = 'SKU_REF';
    const TYPE_SKU_ID   = 'SKU_ID';

    protected $table = 'document_sku_inventories';

    /**
     * @return BelongsTo
     */
    public function document()
    {
        return $this->belongsTo(Document::class);
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
    public function sku()
    {
        return $this->belongsTo(Sku::class);
    }

    /**
     * @return BelongsTo
     */
    public function stock()
    {
        return $this->belongsTo(Stock::class);
    }

    /**
     * Get object type
     *
     * @return string
     */
    public function getObjectType()
    {
        return StockLog::OBJECT_DOCUMENT_SKU_INVENTORY;
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
}
