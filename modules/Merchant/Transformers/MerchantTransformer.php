<?php
namespace Modules\Merchant\Transformers;


use League\Fractal\TransformerAbstract;
use Modules\Merchant\Models\Merchant;

class MerchantTransformer extends TransformerAbstract
{
	public function transform(Merchant $merchant)
	{
	    return [
	        'id'   => (int) $merchant->id,
	        'name' => $merchant->name,
	        'code' => $merchant->code,
	    ];
	}
}
