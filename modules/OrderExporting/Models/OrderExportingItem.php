<?php

namespace Modules\OrderExporting\Models;

use App\Base\Model;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Product\Models\Sku;

/**
 * Class OrderExportingItem
 *
 * @property int $id
 * @property int $order_exporting_id
 * @property int $sku_id
 * @property float $price
 * @property int $quantity
 * @property float $value
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property OrderExporting $orderExporting
 * @property Sku $sku
 */
class OrderExportingItem extends Model
{
    protected $casts = [
        'price' => 'float',
        'value' => 'float',
    ];

    /**
     * @return BelongsTo
     */
    public function orderExporting()
    {
        return $this->belongsTo(OrderExporting::class, 'order_exporting_id', 'id');
    }

    /**
     * @return BelongsTo
     */
    public function sku()
    {
        return $this->belongsTo(Sku::class, 'sku_id', 'id');
    }
}
