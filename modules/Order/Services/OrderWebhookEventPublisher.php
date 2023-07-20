<?php

namespace Modules\Order\Services;

use Modules\Order\Models\Order;
use Modules\Tenant\Services\WebhookEvent;

class OrderWebhookEventPublisher
{
    /**
     * @var Order
     */
    protected $order;

    /**
     * OrderWebhookEventPublisher constructor
     *
     * @param Order $order
     */
    public function __construct(Order $order)
    {
        $this->order = $order;
    }

    /**
     * @param string $fromStatus
     * @param string $toStatus
     * @return WebhookEvent
     */
    public function changeStatus($fromStatus, $toStatus)
    {
        return $this->order->webhookEvent(OrderEvent::CHANGE_STATUS, [
            'order' => $this->order,
            'merchant_code' => $this->order->merchant->code,
            'from_status' => $fromStatus,
            'to_status' => $toStatus,
        ], $this->getOwner());
    }

    /**
     * @return string
     */
    protected function getOwner()
    {
        return $this->order->creator->username;
    }
}
