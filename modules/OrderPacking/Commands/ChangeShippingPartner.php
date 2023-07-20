<?php

namespace Modules\OrderPacking\Commands;

use Modules\FreightBill\Models\FreightBill;
use Modules\Order\Events\OrderUpdatedShippingPartner;
use Modules\OrderPacking\Models\OrderPacking;
use Modules\ShippingPartner\Models\ShippingPartner;
use Modules\User\Models\User;

class ChangeShippingPartner
{
    /**
     * @var OrderPacking[]
     */
    protected $orderPackings = [];

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
     * @param $orderPackings
     * @param ShippingPartner $shippingPartner
     * @param User $creator
     */
    public function __construct($orderPackings, ShippingPartner $shippingPartner, User $creator)
    {
        $this->orderPackings   = $orderPackings;
        $this->shippingPartner = $shippingPartner;
        $this->creator         = $creator;
    }

    public function handle()
    {
        foreach ($this->orderPackings as $orderPacking) {
            if ($orderPacking->shipping_partner_id == $this->shippingPartner->id) {
                continue;
            }

            $shippingPartnerOld                = ($orderPacking->shippingPartner) ? clone $orderPacking->shippingPartner : null;
            $orderPacking->shipping_partner_id = $this->shippingPartner->id;
            $orderPacking->save();

            (new OrderUpdatedShippingPartner($orderPacking->order, $shippingPartnerOld, $this->shippingPartner, $this->creator))->queue();

            $freightBill = $orderPacking->freightBill;
            if (
                $freightBill instanceof FreightBill &&
                empty($freightBill->shipping_partner_id)
            ) {
                $freightBill->shipping_partner_id = $orderPacking->shipping_partner_id;
                $freightBill->save();
            }
        }

    }
}
