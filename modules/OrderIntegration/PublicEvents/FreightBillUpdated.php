<?php

namespace Modules\OrderIntegration\PublicEvents;

use Illuminate\Support\Arr;
use Modules\FreightBill\Models\FreightBill;
use Modules\OrderIntegration\Constants\OrderIntegrationConstant;
use Modules\Tenant\Models\TenantSetting;

class FreightBillUpdated extends FreightBillPublicEvent
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
     * FreightBillUpdated constructor.
     * @param FreightBill $freightBill
     * @param $eventName
     * @param array $payload
     */
    public function __construct(FreightBill $freightBill, $eventName, $payload = [])
    {
        $this->freightBill = $freightBill;
        $this->order     = $freightBill->order;
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
            'application_code' =>  $order->tenant->getSetting(TenantSetting::M32_APP_CODE),
            'order_code'  => $order->code,
            'merchant_code'  => ($order->merchant) ? $order->merchant->code : null,
            'shipping_partner_code'  => $this->freightBill->shippingPartner ? $this->freightBill->shippingPartner->code : null,
            'freight_bill'  => $this->freightBill,
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
