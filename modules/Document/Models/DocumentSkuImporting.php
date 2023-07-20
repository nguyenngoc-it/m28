<?php

namespace Modules\Document\Models;

use App\Base\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Product\Models\Sku;
use Modules\Stock\Models\Stock;
use Modules\Stock\Models\StockLog;
use Modules\Stock\Services\StockObjectInterface;
use Modules\Tenant\Models\Tenant;
use Modules\Warehouse\Models\Warehouse;
use Modules\Warehouse\Models\WarehouseArea;

/**
 * Class DocumentSkuImporting
 *
 * @property int tenant_id
 * @property int document_id
 * @property int sku_id
 * @property int warehouse_id
 * @property int warehouse_area_id
 * @property int stock_id
 * @property int quantity
 * @property int real_quantity
 *
 *
 * @property Tenant tenant
 * @property Document document
 * @property Sku sku
 * @property Stock stock
 * @property WarehouseArea warehouseArea
 * @property Warehouse warehouse
 */
class DocumentSkuImporting extends Model implements StockObjectInterface
{
    protected $table = 'document_sku_importings';

    /**
     * @return BelongsTo
     */
    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }


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
     * Get object type
     *
     * @return string
     */
    public function getObjectType()
    {
        return StockLog::OBJECT_DOCUMENT_SKU_IMPORTING;
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
