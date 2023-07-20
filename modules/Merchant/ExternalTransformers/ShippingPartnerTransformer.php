<?php

namespace Modules\Merchant\ExternalTransformers;

use App\Base\Transformer;
use Modules\ShippingPartner\Models\ShippingPartner;

class ShippingPartnerTransformer extends Transformer
{
    /**
     * Transform the data
     *
     * @param ShippingPartner $shippingPartner
     * @return array
     */
    public function transform($shippingPartner)
    {
        return $shippingPartner->only([
            'name',
            'code',
            'logo',
            'status'
        ]);
    }
}
