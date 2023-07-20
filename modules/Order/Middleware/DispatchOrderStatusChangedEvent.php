<?php

namespace Modules\Order\Middleware;

use Closure;
use Gobiz\Workflow\ApplyTransitionCommand;
use Gobiz\Workflow\WorkflowMiddlewareInterface;
use Modules\Order\Events\OrderStatusChanged;
use Modules\Order\Models\Order;

class DispatchOrderStatusChangedEvent implements WorkflowMiddlewareInterface
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
        $order = $command->subject;
        $fromStatus = $order->status;
        $creator = $command->getPayload('creator');

        $res = $next($command);

        (new OrderStatusChanged($order, $fromStatus, $order->status, $creator))->queue();

        return $res;
    }
}
