<?php

namespace Modules\ShippingPartner\Services\ExpectedTransportingPrice;

use Modules\ShippingPartner\Models\ShippingPartner;

class ExpectedTransportingPriceFactory
{
    /**
     * @param ShippingPartner $shippingPartner
     * @return ExpectedTransportingPriceInterface
     *
     * @throws ExpectedTransportingPriceException
     */
    public function make(ShippingPartner $shippingPartner)
    {
        switch ($shippingPartner->code) {
            case ShippingPartner::SHIPPING_PARTNER_JNTT:
                return new JNTTExpectedTransportingPrice();
            case ShippingPartner::SHIPPING_PARTNER_JNTP:
                return new JNTPExpectedTransportingPrice();
        }
        throw new ExpectedTransportingPriceException('shipping partner not valid for make ExpectedTransportingPrice');
    }
}
