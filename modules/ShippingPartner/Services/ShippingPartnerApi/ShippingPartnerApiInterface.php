<?php

namespace Modules\ShippingPartner\Services\ShippingPartnerApi;

use Gobiz\Support\RestApiException;
use Modules\OrderPacking\Models\OrderPacking;
use Modules\ShippingPartner\Services\ShippingPartnerOrder;

interface ShippingPartnerApiInterface
{
    /**
     * Tạo mã vận đơn
     *
     * @param OrderPacking $orderPacking
     * @param string|null $pickupType
     * @return ShippingPartnerOrder
     * @throws ShippingPartnerApiException
     */
    public function createOrder(OrderPacking $orderPacking, $pickupType = null);

    /**
     * Đồng bộ vận đơn sang M32
     *
     * @param OrderPacking $orderPacking
     * @return void
     */
    public function mappingOrder(OrderPacking $orderPacking);

    /**
     * Lấy url in tem của danh sách mã vận đơn
     *
     * @param integer $shippingPartnerId
     * @param array $freightBillCodes
     * @return array|string|null
     * @throws RestApiException
     */
    public function getOrderStampsUrl($shippingPartnerId, array $freightBillCodes);
}
