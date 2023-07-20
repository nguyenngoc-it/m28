<?php
namespace Modules\Order\Transformers;


use League\Fractal\TransformerAbstract;
use Modules\Order\Models\OrderSku;
use Modules\Product\Transformers\SkuTransformer;

class OrderSkuTransformer extends TransformerAbstract
{
    public function __construct()
	{
		$this->setAvailableIncludes(['sku']);
	}

	public function transform(OrderSku $orderSku)
	{	
	    return [
	        'sku_id'          => $orderSku->sku->id,
	        'name'            => $orderSku->sku->name,
	        'price'           => $orderSku->price,
	        'quantity'        => $orderSku->quantity,
	        'discount_amount' => $orderSku->discount_amount,
	    ];
	}

    /**
     * Include Creator
     *
     * @return \League\Fractal\Resource\Item
     */
    public function includeSku(OrderSku $orderSku)
    {
        $skus = $orderSku->sku;

        return $this->item($skus, new SkuTransformer);
    }
}