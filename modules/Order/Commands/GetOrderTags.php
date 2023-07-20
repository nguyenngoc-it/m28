<?php

namespace Modules\Order\Commands;

use Carbon\Carbon;
use Modules\Order\Models\Order;
use Modules\Order\Services\StatusOrder;

class GetOrderTags
{
    /**
     * @var Order
     */
    protected $order;

    /**
     * GetOrderTags constructor
     *
     * @param Order $order
     */
    public function __construct(Order $order)
    {
        $this->order = $order;
    }

    /**
     * @return array
     */
    public function handle()
    {
        $tags = [];

        if ($this->checkOrderStock()){
            $tags[] = 'no_inspected';
        }
        if ($this->isLateDeliveryRisk()) {
            $tags[] = 'late_delivery_risk';
        }

//        if ($this->isNoInspected()) {
//            $tags[] = 'no_inspected';
//        }

        if($this->order->priority) {
            $tags[] = 'priority';
        }

        return $tags;
    }

    /** kiểm tra đơn xem đã chọn vị trí chưa

     * @return bool
     */
    protected function checkOrderStock()
    {
        $countOrderStock = $this->order->orderStocks->count();
        $countOrderSku = $this->order->orderSkus->count();
        if ($countOrderStock != $countOrderSku || !$this->order->inspected){

            return true;
        }
        return false;
    }

    /**
     * Kiể tra đơn có bị rủi ro giao hàng trễ hay không (là đơn chưa giao mà có hạn giao hàng là ngày hôm nay hoặc quá hạn)
     *
     * @return bool
     */
    protected function isLateDeliveryRisk()
    {
        $intendedDeliveryAt = $this->order->intended_delivery_at ? $this->order->intended_delivery_at->timestamp : 0;

        if(!$intendedDeliveryAt) return false;

        return (
            in_array($this->order->status, StatusOrder::getBeforeStatus(Order::STATUS_DELIVERING))
            && $intendedDeliveryAt < Carbon::tomorrow()->startOfDay()->timestamp
        );
    }

    /**
     * Kiể tra đơn không có vị trí hay chưa
     *
     * @return bool
     */
    protected function isNoInspected()
    {
        return (!$this->order->inspected) ? true : false;
    }
}
