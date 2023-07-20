<?php

namespace Modules\Order\Models;

use App\Base\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Service\Models\Service;
use Modules\Service\Models\ServicePrice;


/**
 * Class OrderImportReturnGoodsService
 *
 * @property int id
 * @property int order_id
 * @property int service_id
 * @property int service_price_id
 * @property float price
 *
 * @property Order|null order
 * @property Service|null service
 * @property ServicePrice|null servicePrice
 */
Class OrderImportReturnGoodsService extends Model
{
    protected $table = 'order_import_return_goods_services';

    protected $casts = [
        'price' => 'float',
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
