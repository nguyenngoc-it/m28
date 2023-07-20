<?php

namespace Modules\OrderIntegration\PublicEvents;

use Gobiz\Event\EventService;
use Gobiz\Event\PublicEvent;
use Modules\FreightBill\Models\FreightBill;
use Modules\Order\Models\Order;
use Modules\OrderIntegration\Constants\OrderIntegrationConstant;

abstract class FreightBillPublicEvent extends PublicEvent
{
    /**
     * @var Order
     */
    protected $order;

    /**
     * @var FreightBill
     */
    protected $freightBill;

    /**
     * Get the event key
     *
     * @return string|null
     */
    public function getKey()
    {
        return $this->order->tenant->code . '-' . $this->freightBill->freight_bill_code;
    }

    /**
     * Public event
     *
     * @param null $topic
     */
    public function publish($topic = null)
    {
        EventService::publicEventDispatcher()->publish($topic ?: OrderIntegrationConstant::M28_FREIGHT_BILL_TOPIC, $this);
    }
}