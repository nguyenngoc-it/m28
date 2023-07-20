<?php

namespace Modules\Order\Events;

use App\Base\Event;
use Modules\Order\Models\Order;
use Modules\ShippingPartner\Models\ShippingPartner;
use Modules\User\Models\User;

class OrderUpdatedShippingPartner extends OrderEvent
{
    /** @var ShippingPartner|null */
    public $shippingPartnerFrom;
    /**
     * @var ShippingPartner
     */
    public $shippingPartnerTo;
    /**
     * @var User
     */
    public $user;

    /**
     * OrderCreated constructor
     *
     * @param Order $order
     * @param ShippingPartner|null $shippingPartnerFrom
     * @param ShippingPartner $shippingPartnerTo
     * @param User $user
     */
    public function __construct(Order $order, ?ShippingPartner $shippingPartnerFrom, ShippingPartner $shippingPartnerTo, User $user)
    {
        $this->order               = $order->refresh();
        $this->shippingPartnerFrom = $shippingPartnerFrom;
        $this->shippingPartnerTo   = $shippingPartnerTo;
        $this->user                = $user;
    }
}
