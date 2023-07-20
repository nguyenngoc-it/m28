<?php
namespace Modules\ShippingPartner\Transformers;

use League\Fractal\TransformerAbstract;
use Modules\ShippingPartner\Models\ShippingPartner;

class ShippingPartnerTransformer extends TransformerAbstract
{
	public function transform(ShippingPartner $shippingPartner)
	{	
	    return [
	        'name' => $shippingPartner->name,
	        'code' => $shippingPartner->code,
	    ];
	}
}