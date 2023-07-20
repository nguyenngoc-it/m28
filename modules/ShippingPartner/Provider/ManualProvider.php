<?php

namespace Modules\ShippingPartner\Provider;

use Modules\OrderPacking\Models\OrderPacking;
use Modules\ShippingPartner\Services\AbstractShippingPartner;
use Modules\ShippingPartner\Services\ShippingPartnerOrder;

class ManualProvider extends AbstractShippingPartner
{
    /**
     * @param OrderPacking $orderPacking
     * @param null $pickupType
     * @return ShippingPartnerOrder
     */
    public function createOrder(OrderPacking $orderPacking, $pickupType = null)
    {
        return new ShippingPartnerOrder();
    }

    /**
     * Đồng bộ vận đơn sang M32
     *
     * @param OrderPacking $orderPacking
     * @return void
     */
    public function mappingOrder(OrderPacking $orderPacking)
    {
        // TODO: Implement mappingOrder() method.
    }
}
