<?php

namespace Modules\Order\Jobs;

use App\Base\Job;
use Modules\Order\Models\Order;
use Gobiz\Log\LogService;

class UpdateFreightBillShippingPartnerJob extends Job
{
    /**
     * @var int
     */
    protected $orderId;

    /**
     * UpdateLocationShippingPartnerJob constructor
     *
     * @param int $orderId
     */
    public function __construct($orderId)
    {
        $this->orderId = $orderId;
    }

    public function handle()
    {
        $order = Order::find($this->orderId);

        if(empty($order->shipping_partner_id)) {
            return;
        }

        $order->orderPackings()
            ->where('shipping_partner_id', '!=', $order->shipping_partner_id)
            ->update(['shipping_partner_id' => $order->shipping_partner_id]);

        $order->freightBills()
            ->where('shipping_partner_id', '!=', $order->shipping_partner_id)
            ->update(['shipping_partner_id' => $order->shipping_partner_id]);

        LogService::logger('freight_bill_update_shipping_partner_job')->info('change  '.$order->shipping_partner_id);
    }
}
