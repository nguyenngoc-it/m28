<?php
namespace Modules\Order\Transformers;


use League\Fractal\TransformerAbstract;
use Modules\Order\Models\OrderSkuComboSku;
use Modules\Product\Transformers\SkuComboTransformer;
use Modules\Product\Transformers\SkuTransformer;

class OrderSkuComboSkuTransformer extends TransformerAbstract
{
    public function __construct()
	{
		$this->setAvailableIncludes(['sku']);
	}

	public function transform(OrderSkuComboSku $orderSkuComboSku)
	{	
	    return [
	        'price'    => $orderSkuComboSku->price,
	        'quantity' => $orderSkuComboSku->quantity,
	    ];
	}

    /**
     * Include Creator
     *
     * @return \League\Fractal\Resource\Item
     */
    public function includeSku(OrderSkuComboSku $orderSkuComboSku)
    {
        $sku = $orderSkuComboSku->sku;

        return $this->item($sku, new SkuTransformer);
    }

    /**
     * Include Creator
     *
     * @return \League\Fractal\Resource\Item
     */
    public function includeSkuCombo(OrderSkuComboSku $orderSkuComboSku)
    {
        $skuCombo = $orderSkuComboSku->skuCombo;

        return $this->item($skuCombo, new SkuComboTransformer);
    }
}