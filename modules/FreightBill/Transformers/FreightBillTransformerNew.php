<?php
namespace Modules\FreightBill\Transformers;


use League\Fractal\TransformerAbstract;
use Modules\FreightBill\Models\FreightBill;
use Modules\ShippingPartner\Models\ShippingPartner;

class FreightBillTransformerNew extends TransformerAbstract
{

	public function transform(FreightBill $freightBill)
	{	
        $shippingPartner = $freightBill->shippingPartner;
        if ($shippingPartner instanceof ShippingPartner) {
            $shippingPartnerName = $shippingPartner->name;
            $shippingPartnerCode = $shippingPartner->code;
        } else {
            $shippingPartnerName = null;
            $shippingPartnerCode = null;
        }
	    return [
	        'tracking_number' => $freightBill->freight_bill_code,
	        'shipping_partner' => [
                'name' => $shippingPartnerName,
                'code' => $shippingPartnerCode
            ],
	        'status' => $freightBill->status,
            'cod_paid_amount' => (float) $freightBill->cod_paid_amount,
	    ];
	}
}