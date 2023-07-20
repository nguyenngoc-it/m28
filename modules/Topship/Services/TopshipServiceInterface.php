<?php

namespace Modules\Topship\Services;

use Modules\FreightBill\Models\FreightBill;
use Modules\ShippingPartner\Models\ShippingPartner;

interface TopshipServiceInterface
{
    /**
     * Mapping trạng thái vận chuyển
     *
     * @param string $shippingState
     * @return string
     */
    public function mapFreightBillStatus($shippingState);

    /**
     * Get topship api
     *
     * @param ShippingPartner $shippingPartner
     * @return TopshipApiInterface
     */
    public function api(ShippingPartner $shippingPartner);

    /**
     * Đồng bộ trạng thái vận chuyển
     *
     * @param array $fulfillment
     * @return false|FreightBill
     */
    public function syncFreightBill(array $fulfillment);
}
