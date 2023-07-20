<?php

namespace Modules\ShippingPartner\Jobs;

use App\Base\Job;
use Modules\Location\Models\Location;
use Modules\ShippingPartner\Models\ShippingPartnerExpectedTransportingPrice;

class ImportExpectedTransportingPriceJob extends Job
{
    public function handle()
    {
        ShippingPartnerExpectedTransportingPrice::query()->where([
            'sender_province_id' => 0,
            'sender_district_id' => 0,
            'sender_ward_id' => 0,
            'receiver_province_id' => 0,
            'receiver_district_id' => 0,
            'receiver_ward_id' => 0,
        ])->limit(10000)->chunkById(100, function ($shippingPartnerExpectedTransportingPrices) {
            foreach ($shippingPartnerExpectedTransportingPrices as $shippingPartnerExpectedTransportingPrice) {
                $this->mappingToLocationId($shippingPartnerExpectedTransportingPrice);
            }
        }, 'id');
        sleep(1);
        if (ShippingPartnerExpectedTransportingPrice::query()->where([
            'mapped' => false,
        ])->limit(10000)->count()) {
            dispatch(new static());
        }
    }

    /**
     * @param ShippingPartnerExpectedTransportingPrice $shippingPartnerExpectedTransportingPrice
     */
    protected function mappingToLocationId(ShippingPartnerExpectedTransportingPrice $shippingPartnerExpectedTransportingPrice)
    {
        foreach (['province', 'district', 'ward'] as $local) {
            foreach (['sender', 'receiver'] as $obj) {
                if ($shippingPartnerExpectedTransportingPrice->{$obj . '_' . $local . '_code'}) {
                    $shippingPartnerExpectedTransportingPrice->{$obj . '_' . $local . '_id'} = $this->getIdLocationByCode($shippingPartnerExpectedTransportingPrice->{$obj . '_' . $local . '_code'});
                    $shippingPartnerExpectedTransportingPrice->save();
                }
            }
        }
    }

    /**
     * @param $locationCode
     * @return int
     */
    private function getIdLocationByCode($locationCode)
    {
        /** @var Location|null $location */
        $location = Location::query()->firstWhere('code', $locationCode);
        return $location ? $location->id : 0;
    }
}
