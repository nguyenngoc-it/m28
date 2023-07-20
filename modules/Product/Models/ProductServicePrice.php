<?php

namespace Modules\Product\Models;
use Modules\Service\Models\Service;
use Modules\Service\Models\ServicePrice;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

use App\Base\Model;

/**
 * Class ProductServicePrice
 * @package Modules\Service\Models
 *
 * @property int id
 * @property int service_id
 * @property int service_price_id
 * @property int product_id
 * @property int tenant_id
 *
 * @property Product|null product
 * @property Service|null service
 * @property ServicePrice|null servicePrice
 */
class ProductServicePrice extends Model
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
