<?php

namespace Modules\Order\Services;

use Modules\Order\Events\OrderUpdatedShippingPartner;
use Modules\Order\Models\Order;
use Modules\ShippingPartner\Models\ShippingPartner;
use Modules\User\Models\User;

class OrderActivityLogger
{
    /**
     * @var Order
     */
    protected $order;

    /**
     * @var User
     */
    protected $creator;

    /**
     * OrderActivityLogger constructor
     *
     * @param Order $order
     * @param User $creator
     */
    public function __construct(Order $order, User $creator)
    {
        $this->order   = $order;
        $this->creator = $creator;
    }

    /**
     * change shipping partner
     *
     * @param ShippingPartner|null $fromShippingPartner
     * @param ShippingPartner $toShippingPartner
     * @return void
     */
    public function changeShippingPartner(?ShippingPartner $fromShippingPartner, ShippingPartner $toShippingPartner)
    {
        (new OrderUpdatedShippingPartner($this->order, $fromShippingPartner, $toShippingPartner, $this->creator))->queue();
    }
}
