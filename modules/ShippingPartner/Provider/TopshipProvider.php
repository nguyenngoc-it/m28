<?php

namespace Modules\ShippingPartner\Provider;

use Modules\OrderPacking\Models\OrderPacking;
use Modules\ShippingPartner\Services\AbstractShippingPartner;
use Modules\ShippingPartner\Services\ShippingPartnerApi\ShippingPartnerApiException;
use Modules\ShippingPartner\Services\ShippingPartnerOrder;
use Modules\Topship\Commands\CreateTopshipOrder;

class TopshipProvider extends AbstractShippingPartner
{
    /**
     * Tạo mã vận đơn
     *
     * @param OrderPacking $orderPacking
     * @param string|null $pickupType
     * @return ShippingPartnerOrder
     * @throws ShippingPartnerApiException
     */
    public function createOrder(OrderPacking $orderPacking, $pickupType = null)
    {
        return (new CreateTopshipOrder($orderPacking))->handle();
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
