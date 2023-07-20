<?php

namespace Modules\ImportHistory\Models;

use App\Base\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Merchant\Models\Merchant;
use Modules\Product\Models\Sku;
use Modules\User\Models\User;
use Modules\Warehouse\Models\Warehouse;
use Modules\Warehouse\Models\WarehouseArea;

/**
 * Class ImportHistoryItem
 *
 * @property int $id
 * @property int $tenant_id
 * @property int $merchant_id
 * @property int $import_history_id
 * @property int $sku_id
 * @property int $warehouse_id
 * @property int $warehouse_area_id
 * @property int $stock
 * @property string $freight_bill
 * @property string $package_code
 * @property string $note
 *
 * @property Merchant $merchant
 * @property User $creator
 * @property Sku $sku
 * @property Warehouse $warehouse
 * @property WarehouseArea $warehouseArea
 */
class ImportHistoryItem extends Model
{
    protected $table = 'import_history_items';

    protected $fillable = ['tenant_id', 'merchant_id', 'sku_id', 'warehouse_id', 'warehouse_area_id', 'stock', 'freight_bill',
            'package_code', 'note', 'import_history_id'
        ];

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
    public function importHistory()
    {
        return $this->belongsTo(User::class, 'import_history_id', 'id');
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
}