<?php

namespace Modules\OrderPacking\Middleware;

use Closure;
use Gobiz\Workflow\ApplyTransitionCommand;
use Gobiz\Workflow\WorkflowMiddlewareInterface;
use Modules\OrderPacking\Models\OrderPacking;
use Modules\OrderPacking\Services\OrderPackingEvent;

class LogOrderPackingChangeStatus implements WorkflowMiddlewareInterface
{
    /**
     * @param ApplyTransitionCommand $command
     * @param Closure $next
     * @return mixed
     */
    public function handle(ApplyTransitionCommand $command, Closure $next)
    {
        /**
         * @var OrderPacking $orderPacking
         */
        $orderPacking = $command->subject;
        $fromStatus = $orderPacking->status;
        $res = $next($command);

        $orderPacking->logActivity(OrderPackingEvent::CHANGE_STATUS, $command->getPayload('creator'), [
            'order_packing' => $orderPacking,
            'old_status' => $fromStatus,
            'new_status' => $orderPacking->status,
        ]);

        return $res;
    }
}
