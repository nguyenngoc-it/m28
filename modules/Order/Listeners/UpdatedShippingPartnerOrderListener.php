<?php

namespace Modules\Order\Listeners;

use App\Base\QueueableListener;
use Modules\Order\Events\OrderUpdatedShippingPartner;
use Modules\Order\Services\OrderEvent;
use Modules\ShippingPartner\Services\ExpectedTransportingPrice\ExpectedTransportingPriceException;

class UpdatedShippingPartnerOrderListener extends QueueableListener
{
    /**
     * @param OrderUpdatedShippingPartner $event
     */
    public function handle(OrderUpdatedShippingPartner $event)
    {
        $order               = $event->order;
        $creator             = $event->user;
        $shippingPartnerFrom = $event->shippingPartnerFrom;
        $shippingPartnerTo   = $event->shippingPartnerTo;

        $order->logActivity(OrderEvent::CHANGE_SHIPPING_PARTNER, $creator, [
            'from' => $shippingPartnerFrom ? $shippingPartnerFrom->only(['id', 'code', 'name']) : [],
            'to' => $shippingPartnerTo->only(['id', 'code', 'name'])
        ]);

        /**
         * Đơn thay đổi dvvc tính lại phí vận chuyển dự kiến
         */
        try {
            if ($order->orderPacking && $order->orderPacking->shippingPartner) {
                $order->orderPacking->shippingPartner->expectedTransporting()->getPrice($order, true);
            }
        } catch (ExpectedTransportingPriceException $exception) {

        }
    }
}
