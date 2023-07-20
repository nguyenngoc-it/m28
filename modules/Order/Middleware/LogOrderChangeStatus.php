<?php

namespace Modules\Order\Middleware;

use Closure;
use Gobiz\Log\LogService;
use Gobiz\Workflow\ApplyTransitionCommand;
use Gobiz\Workflow\WorkflowMiddlewareInterface;
use Modules\Order\Models\Order;
use Modules\Order\Services\OrderEvent;

class LogOrderChangeStatus implements WorkflowMiddlewareInterface
{
    /**
     * @param ApplyTransitionCommand $command
     * @param Closure $next
     * @return mixed
     */
    public function handle(ApplyTransitionCommand $command, Closure $next)
    {
        /**
         * @var Order $order
         */
        $order      = $command->subject;
        $fromStatus = $order->status;
        $res        = $next($command);
        LogService::logger('logOrderChangeStatus')->info($order->code . '-' . $order->status);
        $order->logActivity(OrderEvent::CHANGE_STATUS, $command->getPayload('creator'), [
            'order' => $order,
            'old_status' => $fromStatus,
            'new_status' => $order->status,
        ]);

        return $res;
    }
}
