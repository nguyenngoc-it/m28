<?php

namespace Modules\OrderPacking\Listeners;

use App\Base\QueueableListener;
use Illuminate\Support\Facades\DB;
use Modules\Order\Jobs\SetOrderReturnServiceJob;
use Modules\OrderPacking\Events\OrderPackingCreated;
use Modules\OrderPacking\Jobs\SetOrderPackingServiceJob;
use Modules\OrderPacking\Services\OrderPackingEvent;
use Modules\Service;
use Modules\ShippingPartner\Services\ExpectedTransportingPrice\ExpectedTransportingPriceException;

class CreatedOrderPackingListener extends QueueableListener
{
    /**
     * @param OrderPackingCreated $event
     */
    public function handle(OrderPackingCreated $event)
    {
        DB::transaction(function () use ($event) {
            $orderPacking = $event->orderPacking;
            $order        = $orderPacking->order;

            /**
             * Log
             */
            $orderPacking->logActivity(OrderPackingEvent::CREATE, Service::user()->getSystemUserDefault());

            if ($freightBill = $order->freightBills()->first()) {
                /**
                 * Cập nhật lại vận đơn của đơn
                 */
                $freightBill->update(['order_packing_id' => $orderPacking->id]);
            }

            /**
             * Nếu orderPacking là của đơn tạo bởi seller và có mã vận đơn trước đó thì khởi tạo vận đơn manual
             * Chuyển đơn sang chờ nhặt hàng
             */
            if ($order->merchant && $order->freight_bill) {
                Service::orderPacking()->createTrackingNoByManual($orderPacking, $order->freight_bill, Service::user()->getSystemUserDefault(), $order->shippingPartner);
            }

            /**
             * Add remark cho OrderPacking tạo ra
             */
            Service::orderPacking()->updateRemark($orderPacking);

            /**
             * Khởi tạo dịch vụ xuất cho OrderPacking
             */
            dispatch(new SetOrderPackingServiceJob($orderPacking));

            /**
             * Khởi tạo dich vụ hoàn trên đơn
             */
            dispatch(new SetOrderReturnServiceJob($order));

            /**
             * Khởi tạo phí vận chuyển dự kiến
             */
            try {
                if ($orderPacking->shippingPartner) {
                    $orderPacking->shippingPartner->expectedTransporting()->getPrice($order, true);
                }
            } catch (ExpectedTransportingPriceException $exception) {

            }
        });
    }
}
