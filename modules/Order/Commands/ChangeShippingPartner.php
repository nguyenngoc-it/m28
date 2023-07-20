<?php

namespace Modules\Order\Commands;

use Modules\Order\Events\OrderUpdatedShippingPartner;
use Modules\Order\Models\Order;
use Modules\Order\Services\OrderEvent;
use Modules\OrderPacking\Models\OrderPacking;
use Modules\ShippingPartner\Models\ShippingPartner;
use Modules\User\Models\User;

class ChangeShippingPartner
{
    /**
     * @var Order
     */
    protected $order;

    /**
     * @var ShippingPartner
     */
    protected $shippingPartner;

    /**
     * @var User|null
     */
    protected $creator = null;

    /**
     * ChangeShippingPartner constructor.
     * @param Order $order
     * @param ShippingPartner $shippingPartner
     * @param User $creator
     */
    public function __construct(Order $order, ShippingPartner $shippingPartner, User $creator)
    {
        $this->order           = $order;
        $this->shippingPartner = $shippingPartner;
        $this->creator         = $creator;
    }

    public function handle()
    {
        $shippingPartnerOld = ($this->order->shippingPartner) ? clone $this->order->shippingPartner : null;

        if ($this->order->shipping_partner_id == $this->shippingPartner->id) {
            return $this->order;
        }

        $this->order->shipping_partner_id = $this->shippingPartner->id;
        $this->order->save();

        $this->changeShippingPartnerOrderPacking();

        (new OrderUpdatedShippingPartner($this->order, $shippingPartnerOld, $this->shippingPartner, $this->creator))->queue();

        return $this->order;
    }

    /**
     * Thay đổi DTVC cho các YCDH đang chờ xử lý
     */
    protected function changeShippingPartnerOrderPacking()
    {
        $this->order->orderPackings()->where('status', OrderPacking::STATUS_WAITING_PROCESSING)
            ->update(['shipping_partner_id' => $this->shippingPartner->id]);
    }
}
