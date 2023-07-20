<?php

namespace Modules\Topship\Services;

use Illuminate\Support\Arr;
use Modules\FreightBill\Models\FreightBill;
use Modules\ShippingPartner\Models\ShippingPartner;
use Modules\Topship\Commands\SyncTopshipFreightBill;

class TopshipService implements TopshipServiceInterface
{
    /**
     * Mapping trạng thái vận chuyển
     *
     * @param string $shippingState
     * @return string
     */
    public function mapFreightBillStatus($shippingState)
    {
        return Arr::get([
            Topship::SHIPPING_STATE_HOLDING => FreightBill::STATUS_CONFIRMED_PICKED_UP,
            Topship::SHIPPING_STATE_DELIVERING => FreightBill::STATUS_DELIVERING,
            Topship::SHIPPING_STATE_DELIVERED => FreightBill::STATUS_DELIVERED,
            Topship::SHIPPING_STATE_RETURNING => FreightBill::STATUS_RETURN,
            Topship::SHIPPING_STATE_RETURNED => FreightBill::STATUS_RETURN_COMPLETED,
            Topship::SHIPPING_STATE_CANCELLED => FreightBill::STATUS_CANCELLED,
        ], $shippingState);
    }

    /**
     * Đồng bộ trạng thái vận chuyển
     *
     * @param array $fulfillment
     * @return false|FreightBill
     */
    public function syncFreightBill(array $fulfillment)
    {
        return (new SyncTopshipFreightBill($fulfillment))->handle();
    }

    /**
     * Get topship api
     *
     * @param ShippingPartner $shippingPartner
     * @return TopshipApiInterface
     */
    public function api(ShippingPartner $shippingPartner)
    {
        return new TopshipApi([
            'url' => config('services.topship.api_url'),
            'token' => $shippingPartner->getSetting(ShippingPartner::TOPSHIP_TOKEN),
        ]);
    }
}
