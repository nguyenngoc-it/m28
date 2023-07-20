<?php

namespace Modules\Order\Middleware;

use Closure;
use Gobiz\Workflow\WorkflowException;
use Modules\FreightBill\Commands\ChangeFreightBillStatus;
use Modules\FreightBill\Models\FreightBill;
use Modules\OrderPacking\Models\OrderPacking;
use Modules\Service;

class UpdateOrderStatusFromFreightBill
{
    /**
     * @param ChangeFreightBillStatus $command
     * @param Closure $next
     * @return mixed
     * @throws WorkflowException
     */
    public function handle(ChangeFreightBillStatus $command, Closure $next)
    {
        $res          = $next($command);
        $freightBill  = $command->freightBill;
        $orderPacking = $freightBill->orderPacking;
        /**
         * Nếu huỷ vận đơn thì YCĐH về chờ xử lý
         */
        if ($command->freightBill->status == FreightBill::STATUS_CANCELLED && $orderPacking) {
            $orderPacking->freight_bill_id = 0;
            $orderPacking->save();
            if ($orderPacking->canChangeStatus(OrderPacking::STATUS_WAITING_PROCESSING)) {
                $orderPacking->changeStatus(OrderPacking::STATUS_WAITING_PROCESSING, $command->creator);
            }
        }

        /**
         * Đồng bộ trạng thái đơn theo trạng thái của mã vận đơn
         */
        if ($command->freightBill->status != FreightBill::STATUS_CANCELLED) {
            Service::order()->updateStatusFromFreightBill($command->freightBill->order, $command->freightBill, $command->creator);
        }

        return $res;
    }
}
