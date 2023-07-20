<?php

namespace Modules\ShippingPartner\Services\ShippingPartnerApi;

use Modules\ShippingPartner\Models\ShippingPartner;

interface ShippingPartnerApiFactoryInterface
{
    /**
     * Make shippingPartner api
     *
     * @param ShippingPartner $shippingPartner
     * @return ShippingPartnerApiInterface
     * @throws ShippingPartnerApiException
     */
    public function make(ShippingPartner $shippingPartner);
}