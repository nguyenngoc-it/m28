<?php

namespace Modules\OrderPacking\Middleware;

use Closure;
use Gobiz\Workflow\ApplyTransitionCommand;
use Gobiz\Workflow\WorkflowException;
use Gobiz\Workflow\WorkflowMiddlewareInterface;
use Modules\Order\Models\Order;
use Modules\OrderPacking\Models\OrderPacking;
use Modules\Service;

class UpdateWhenOrderPackingChangeStatus implements WorkflowMiddlewareInterface
{
    /**
     * @param ApplyTransitionCommand $command
     * @param Closure $next
     * @return mixed
     * @throws WorkflowException
     */
    public function handle(ApplyTransitionCommand $command, Closure $next)
    {
        /**
         * @var OrderPacking $orderPacking
         */
        $orderPacking = $command->subject;
        $res          = $next($command);
        $order        = $orderPacking->order;

        /**
         * Xử lý chuyển trạng thái đơn theo trạng thái order_packing
         */
        if ($orderPacking->status == OrderPacking::STATUS_WAITING_PROCESSING && $order->canChangeStatus(Order::STATUS_WAITING_PROCESSING)) {
            $order->changeStatus(Order::STATUS_WAITING_PROCESSING, Service::user()->getSystemUserDefault());
        }
        if ($orderPacking->status == OrderPacking::STATUS_WAITING_PICKING && $order->canChangeStatus(Order::STATUS_WAITING_PICKING)) {
            $order->changeStatus(Order::STATUS_WAITING_PICKING, Service::user()->getSystemUserDefault());
        }
        if ($orderPacking->status == OrderPacking::STATUS_WAITING_PACKING && $order->canChangeStatus(Order::STATUS_WAITING_PACKING)) {
            $order->changeStatus(Order::STATUS_WAITING_PACKING, Service::user()->getSystemUserDefault());
        }

        /**
         * TH YCDH đã đóng gói sau khi xác nhận đóng hàng
         */
        if ($orderPacking->status == OrderPacking::STATUS_PACKED && $order->canChangeStatus(Order::STATUS_WAITING_DELIVERY)) {
            $order->changeStatus(Order::STATUS_WAITING_DELIVERY, Service::user()->getSystemUserDefault());
        }

        return $res;
    }
}
