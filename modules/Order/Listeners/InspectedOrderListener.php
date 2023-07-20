<?php

namespace Modules\Order\Listeners;

use App\Base\QueueableListener;
use Gobiz\Workflow\WorkflowException;
use Modules\Order\Events\OrderInspected;
use Modules\Order\Models\Order;
use Modules\Service;
use Modules\Tenant\Models\TenantSetting;

class InspectedOrderListener extends QueueableListener
{
    /**
     * @param OrderInspected $event
     * @throws WorkflowException
     */
    public function handle(OrderInspected $event)
    {
        $order = $event->order;
        $user  = $event->user;

        /**
         * Nếu hoàn thành gán được kho cho tất cả skus sẽ chuyển trạng thái chờ xác nhận
         */
        if ($order->status == Order::STATUS_WAITING_INSPECTION && $order->canChangeStatus(Order::STATUS_WAITING_CONFIRM)) {
            $order->changeStatus(Order::STATUS_WAITING_CONFIRM, $user);
            /**
             * Đơn chuyển trạng thái "chờ xác nhận" sẽ tự động chuyển sang chờ xử lý nếu có cấu hình "tự động xác nhận đơn"
             */
            if ($order->status == Order::STATUS_WAITING_CONFIRM
                && Service::order()->canAutoOrderConfirmAndCreateFreightBill($order)) {
                $order->changeStatus(Order::STATUS_WAITING_PROCESSING, $user);
            }
        }
    }
}
