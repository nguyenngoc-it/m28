<?php

namespace Modules\OrderPacking\Middleware;

use Closure;
use Gobiz\Workflow\WorkflowException;
use Modules\FreightBill\Commands\ChangeFreightBillStatus;
use Modules\FreightBill\Models\FreightBill;
use Modules\Order\Models\Order;
use Modules\OrderPacking\Models\OrderPacking;

class UpdateOrderPackingStatusFromFreightBill
{
    /**
     * @param ChangeFreightBillStatus $command
     * @param Closure $next
     * @return mixed
     * @throws WorkflowException
     */
    public function handle(ChangeFreightBillStatus $command, Closure $next)
    {
        $res = $next($command);
        $freightBill = $command->freightBill;

        // Hủy ycđh của mvđ nếu mvđ và order đều bị hủy
        if (
            $freightBill->status === FreightBill::STATUS_CANCELLED
            && $freightBill->order->status === Order::STATUS_CANCELED
            && ($orderPacking = $freightBill->currentOrderPacking)
            && $orderPacking->canChangeStatus(OrderPacking::STATUS_CANCELED)
        ) {
            $orderPacking->changeStatus(OrderPacking::STATUS_CANCELED, $command->creator);
        }

        return $res;
    }
}
