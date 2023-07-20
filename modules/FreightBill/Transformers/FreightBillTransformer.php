<?php

namespace Modules\FreightBill\Transformers;

use App\Base\Transformer;
use Modules\FreightBill\Models\FreightBill;
use Modules\OrderPacking\Models\OrderPacking;

class FreightBillTransformer extends Transformer
{
    /**
     * Transform the data
     *
     * @param FreightBill $freightBill
     * @return mixed
     */
    public function transform($freightBill)
    {
        return array_merge($freightBill->attributesToArray(), [
            'shipping_partner' => $freightBill->shippingPartner ? $freightBill->shippingPartner->only(['code', 'name']) : [],
        ]);
    }
}
