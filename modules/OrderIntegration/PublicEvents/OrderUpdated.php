<?php

namespace Modules\OrderIntegration\PublicEvents;

use Illuminate\Support\Arr;
use Modules\Order\Models\Order;
use Modules\OrderIntegration\Constants\OrderIntegrationConstant;

class OrderUpdated extends OrderPublicEvent
{
    /**
     * @var array
     */
    protected $payload;

    /**
     * @var array
     */
    protected $extraPayload;

    /**
     * @var string
     */
    protected $eventName;

    /**
     * OrderUpdated constructor.
     * @param Order $order
     * @param $eventName
     * @param array $payload
     */
    public function __construct(Order $order, $eventName, $payload = [])
    {
        $this->order     = $order;
        $this->payload   = $payload;
        $this->eventName = $eventName;
    }

    /**
     * Get the event payload
     *
     * @return array
     */
    public function getPayload()
    {
        $creator = [];
        if(isset($this->payload['creator'])) {
            $creator = (array)$this->payload['creator'];
            unset($this->payload['creator']);
        }

        $order  = $this->order;
        return [
            'source' => OrderIntegrationConstant::M28_SOURCE,
            'tenant_code' => $order->tenant->code,
            'merchant_code' => $order->merchant->code,
            'order'  => $order,
            'creator' => (!empty($creator)) ? Arr::only($creator, ['id', 'name', 'username', 'email', 'phone']) : null,
            'extraPayload' => $this->payload
        ];
    }

    /**
     * Get the event name
     *
     * @return string
     */
    public function getName()
    {
        return str_replace('.', '_', $this->eventName);
    }
}