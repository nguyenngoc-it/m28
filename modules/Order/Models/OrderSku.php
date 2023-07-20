<?php

namespace Modules\Order\Models;

use App\Base\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Modules\Product\Models\BatchOfGood;
use Modules\Product\Models\Sku;
use Modules\Tenant\Models\Tenant;


/**
 * Class OrderSku
 *
 * @property int $id
 * @property int $tenant_id
 * @property int $order_id
 * @property int $sku_id
 * @property int $quantity
 * @property float $tax
 * @property float $price
 * @property float $order_amount
 * @property float $discount_amount
 * @property float $total_amount
 *
 * @property Tenant|null $tenant
 * @property Order|null $order
 * @property Sku|null $sku
 * @property BatchOfGood|null batchOfGood
 */
class OrderSku extends Model
{

    protected $table = 'order_skus';

    protected $casts = [
        'price' => 'float',
        'order_amount' => 'float',
        'discount_amount' => 'float',
        'total_amount' => 'float',
    ];

    const FROM_SKU_COMBO_FALSE = 0;
    const FROM_SKU_COMBO_TRUE = 1;

    /**
     * @return BelongsTo
     */
    public function tenant()
    {
        return $this->belongsTo(Tenant::class, 'tenant_id', 'id');
    }

    /**
     * @return BelongsTo
     */
    public function order()
    {
        return $this->belongsTo(Order::class, 'order_id', 'id');
    }

    /**
     * @return BelongsTo
     */
    public function sku()
    {
        return $this->belongsTo(Sku::class, 'sku_id', 'id');
    }

    /**
     * @return HasOne
     */
    public function batchOfGood()
    {
        return $this->hasOne(BatchOfGood::class, 'sku_child_id', 'sku_id');
    }
}
