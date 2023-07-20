<?php

namespace Modules\PurchasingOrder\Models;

use Modules\Service\Models\Service;
use Modules\Service\Models\ServicePrice;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

use App\Base\Model;

/**
 * Class PurchasingOrderService
 * @package Modules\PurchasingOrder\Models
 *
 * @property int id
 * @property int service_id
 * @property int service_price_id
 * @property int purchasing_order_id
 * @property int tenant_id
 *
 * @property PurchasingOrder|null purchasingOrder
 * @property Service|null service
 * @property ServicePrice|null servicePrice
 */
class PurchasingOrderService extends Model
{

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
