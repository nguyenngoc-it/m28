<?php

namespace Modules\ShippingPartner\Observers;
use Modules\ShippingPartner\Models\ShippingPartner;


class ShippingPartnerObserver
{
    /**
     * Handle to the ShippingPartner "created" event.
     *
     * @param  ShippingPartner  $shippingPartner
     * @return void
     */
    public function created(ShippingPartner $shippingPartner)
    {
        if(
            is_null($shippingPartner->alias)
        ) {
            $shippingPartner->alias = [$shippingPartner->code, strtolower($shippingPartner->code)];
            $shippingPartner->save();
        }
    }

    /**
     * Handle the ShippingPartner "updated" event.
     *
     * @param  ShippingPartner $shippingPartner
     * @return void
     */
    public function updated(ShippingPartner $shippingPartner)
    {

    }
}
