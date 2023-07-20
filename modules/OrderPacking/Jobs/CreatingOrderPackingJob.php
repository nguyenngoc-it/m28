<?php

namespace Modules\OrderPacking\Jobs;

use App\Base\Job;
use Modules\Order\Models\Order;
use Modules\Service;
use Modules\Tenant\Models\TenantSetting;

class CreatingOrderPackingJob extends Job
{
    public $queue = 'creating_order_packing';
    protected $orderId = 0;

    /**
     * @param $orderId
     */
    public function __construct($orderId)
    {
        /** @var Order|null order */
        $this->orderId = $orderId;
    }

    public function handle()
    {
        $order = Order::find($this->orderId);
        if ($order instanceof Order) {
            $orderPacking = Service::orderPacking()->createOrderPacking($order);
            /**
             * Tạo vận đơn cho YCĐH ngay khi có cấu hình "tự động xác nhận đơn"
             */
            if (
                $orderPacking &&
                !$orderPacking->freightBill &&
                Service::order()->canAutoOrderConfirmAndCreateFreightBill($order)
            ) {
                dispatch(new CreateTrackingNoJob($orderPacking->id, $order->creator->id));
            }
        }
    }
}
