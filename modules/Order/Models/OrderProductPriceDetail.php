<?php

namespace Modules\Order\Models;

use App\Base\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Product\Models\ProductPrice;
use Modules\Product\Models\ProductPriceDetail;
use Modules\Product\Models\Sku;
use Modules\Tenant\Models\Tenant;


/**
 * Class OrderProductPriceDetail
 *
 * @property int $id
 * @property int $tenant_id
 * @property int product_price_id
 * @property int sku_id
 * @property int combo
 * @property float cost_price
 * @property float service_packing_price
 * @property float service_shipping_price
 * @property float total_price
 *
 * @property Order|null order
 * @property ProductPriceDetail|null productPriceDetail
 * @property ProductPrice|null productPrice
 * @property Sku|null sku
 */

class OrderProductPriceDetail extends Model
{
    protected $table = 'order_product_price_details';

    protected $casts = [
        'cost_price' => 'float',
        'service_packing_price' => 'float',
        'service_shipping_price' => 'float',
        'total_price' => 'float',
    ];

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
    public function productPrice()
    {
        return $this->belongsTo(ProductPrice::class, 'product_price_id', 'id');
    }

    /**
     * @return BelongsTo
     */
    public function productPriceDetail()
    {
        return $this->belongsTo(ProductPriceDetail::class, 'product_price_id', 'id');
    }

    /**
     * @return BelongsTo
     */
    public function sku()
    {
        return $this->belongsTo(Sku::class, 'sku_id', 'id');
    }
}