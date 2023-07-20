<?php

namespace Modules\ShippingPartner\Services;

use Modules\ShippingPartner\Services\ShippingPartnerApi\ShippingPartnerApiInterface;

abstract class AbstractShippingPartner implements ShippingPartnerApiInterface
{
    /**
     * @inheritDoc
     */
    public function getOrderStampsUrl($shippingPartnerId, array $freightBillCodes)
    {
        return null;
    }
}
