<?php

namespace Modules\OrderPacking\Observers;
use Modules\Order\Jobs\CalculateServiceAmount;
use Modules\OrderPacking\Models\OrderPacking;
use Gobiz\Log\LogService;

class OrderPackingObserver
{
    /**
     * Handle to the OrderPacking "created" event.
     *
     * @param  OrderPacking  $orderPacking
     * @return void
     */
    public function created(OrderPacking $orderPacking)
    {
        if(
            !empty($orderPacking->service_amount) &&
            !$orderPacking->order->dropship
        ) {
            dispatch(new CalculateServiceAmount($orderPacking->order_id));
        }
    }

    /**
     * Handle the OrderPacking "updated" event.
     *
     * @param  OrderPacking $orderPacking
     * @return void
     */
    public function updated(OrderPacking $orderPacking)
    {
        $changed = $orderPacking->getChanges();
        if(
            isset($changed['service_amount']) &&
            !$orderPacking->order->dropship
        ) {
            dispatch(new CalculateServiceAmount($orderPacking->order_id));
        }

        // có case lỗi vận đơn k có dvvc, về sau YCDH có dvvc nhưng lại k update lại cho vận đơn
        if(
            isset($changed['shipping_partner_id']) &&
            !empty($orderPacking->shipping_partner_id) &&
            !empty($orderPacking->freight_bill_id)
        ) {
            $freightBill = $orderPacking->freightBill;
            if(empty($freightBill->shipping_partner_id)) {
                LogService::logger('freight_update_shipping_partner')->info('change order_packing '.$freightBill->shipping_partner_id);

                $freightBill->shipping_partner_id = $orderPacking->shipping_partner_id;
                $freightBill->save();
            }
        }
    }
}
