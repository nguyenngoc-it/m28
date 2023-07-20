<?php

namespace Modules\Merchant\ExternalTransformers;

use App\Base\Transformer;
use Modules\FreightBill\Models\FreightBill;

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
        return $freightBill->only([
            'freight_bill_code',
            'status',
            'receiver_name',
            'receiver_phone',
            'receiver_address',
            'sender_name',
            'sender_phone',
            'sender_address',
            'fee',
            'snapshots',
            'cod_total_amount',
            'cod_paid_amount',
            'cod_fee_amount',
            'shipping_amount',
            'other_fee',
            'created_at',
            'updated_at'
        ]);
    }
}
