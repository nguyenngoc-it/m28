<?php

namespace Modules\Order\Listeners;

use App\Base\QueueableListener;
use Gobiz\Log\LogService;
use Gobiz\Workflow\WorkflowException;
use Modules\Order\Events\OrderShippingFinancialStatusChanged;

class OrderShippingFinancialStatusChangedListener extends QueueableListener
{
    /**
     * @param OrderShippingFinancialStatusChanged $event
     */
    public function handle(OrderShippingFinancialStatusChanged $event)
    {
        try {
            $event->order->changeShippingFinancialStatus($event->toStatus, $event->creator);
        }catch (WorkflowException $workflowException){
            LogService::logger('change_shipping_financial_status')->error($workflowException->getMessage());
        }
    }
}
