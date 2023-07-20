<?php

namespace Modules\Service\Models;

use App\Base\Model;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Merchant\Models\Merchant;
use Modules\Product\Models\Sku;
use Modules\Warehouse\Models\Warehouse;
use Modules\Warehouse\Models\WarehouseArea;

/**
 * Class StorageFeeSkuStatistic
 * @package Modules\Service\Models
 *
 * @property int id
 * @property int merchant_id
 * @property string merchant_username
 * @property string merchant_name
 * @property int product_id
 * @property int stock_id
 * @property int sku_id
 * @property string sku_code
 * @property int warehouse_id
 * @property string warehouse_code
 * @property int warehouse_area_id
 * @property string warehouse_area_code
 * @property array service_price_ids
 * @property double service_price
 * @property float volume
 * @property integer quantity
 * @property Carbon closing_time
 * @property float fee
 *
 * @property Warehouse|null warehouse
 * @property Merchant|null merchant
 * @property Sku|null sku
 * @property WarehouseArea|null warehouseArea
 */
class StorageFeeSkuStatistic extends Model
{
    protected $casts = [
        'volume' => 'float',
        'fee' => 'float',
        'service_price' => 'float',
        'closing_time' => 'datetime',
        'service_price_ids' => 'array'
    ];

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
    public function merchant()
    {
        return $this->belongsTo(Merchant::class);
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
    public function warehouseArea()
    {
        return $this->belongsTo(WarehouseArea::class);
    }

    /**
     * @return Builder[]|Collection
     */
    public function servicePrices()
    {
        return ServicePrice::query()->whereIn('id', $this->service_price_ids)->get();
    }
}
