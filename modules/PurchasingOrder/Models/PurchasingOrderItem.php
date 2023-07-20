<?php

namespace Modules\PurchasingOrder\Models;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Base\Model;
use Modules\Product\Models\Sku;
use Illuminate\Database\Eloquent\Relations\HasOneThrough;

/**
 * Class PurchasingOrderItem
 *
 * @property int $id
 * @property int purchasing_order_id
 * @property int purchasing_variant_id
 * @property string item_id
 * @property string item_code
 * @property string item_name
 * @property string item_translated_name
 * @property float original_price
 * @property float price
 * @property int ordered_quantity
 * @property int purchased_quantity
 * @property int received_quantity
 * @property string product_url
 * @property string product_image
 * @property string variant_image
 * @property string variant_properties
 *
 * @property PurchasingOrder|null purchasingOrder
 * @property PurchasingVariant|null purchasingVariant
 * @property Sku|null sku
 */
class PurchasingOrderItem extends Model
{
    protected $table = 'purchasing_order_items';

    protected $casts = [
        'variant_properties' => 'array',
    ];

    /**
     * @return BelongsTo
     */
    public function purchasingOrder()
    {
        return $this->belongsTo(PurchasingOrder::class, 'purchasing_order_id', 'id');
    }

    /**
     * @return BelongsTo
     */
    public function purchasingVariant()
    {
        return $this->belongsTo(PurchasingVariant::class, 'purchasing_variant_id', 'id');
    }

    /**
     * @return HasOneThrough
     */
    public function sku()
    {
        return $this->hasOneThrough(Sku::class, PurchasingVariant::class, 'id', 'id', 'purchasing_variant_id', 'sku_id');
    }
}
