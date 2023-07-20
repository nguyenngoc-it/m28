<?php

namespace Modules\OrderPacking\Models;

use App\Base\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Order\Models\Order;
use Modules\Service\Models\Service;
use Modules\Service\Models\ServicePrice;

/**
 * Class OrderPackingService
 *
 * @property int id
 * @property int order_id
 * @property int order_packing_id
 * @property int service_id
 * @property int service_price_id
 * @property float price
 * @property int quantity
 * @property float amount
 *
 * @property OrderPacking|null orderPacking
 * @property Order|null order
 * @property Service|null service
 * @property ServicePrice|null servicePrice
 */
class OrderPackingService extends Model
{
    protected $table = 'order_packing_services';

    /**
     * @return BelongsTo
     */
    public function orderPacking()
    {
        return $this->belongsTo(OrderPacking::class, 'order_packing_id', 'id');
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

    /**
     * @param $value
     * @return void
     */
    public function setServiceNameAttribute($value)
    {
        $this->attributes['service_name'] = $value;
    }

    /**
     * @param $value
     * @return void
     */
    public function setServicePriceLableAttribute($value)
    {
        $this->attributes['service_price_label'] = $value;
    }
}
