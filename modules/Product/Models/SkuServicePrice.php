<?php

namespace Modules\Product\Models;
use Modules\Service\Models\Service;
use Modules\Service\Models\ServicePrice;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

use App\Base\Model;

/**
 * Class SkuServicePrice
 * @package Modules\Service\Models
 *
 * @property int id
 * @property int service_id
 * @property int service_price_id
 * @property int product_id
 * @property int tenant_id
 * @property int sku_id
 *
 * @property Product|null product
 * @property Sku|null sku
 * @property Service|null service
 * @property ServicePrice|null servicePrice
 */
class SkuServicePrice extends Model
{

    /**
     * @return BelongsTo
     */
    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id', 'id');
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
    public function service()
    {
        return $this->belongsTo(Service::class, 'service_id', 'id');
    }

    /**
     * @return BelongsTo
     */
    public function servicePrice()
    {
        return $this->belongsTo(ServicePrice::class, 'service_price_id', 'id');
    }
}
