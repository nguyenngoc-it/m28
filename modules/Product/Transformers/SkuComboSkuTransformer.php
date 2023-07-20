<?php

namespace Modules\Product\Transformers;

use League\Fractal\TransformerAbstract;
use Modules\Product\Models\SkuComboSku;
use Modules\Product\Transformers\SkuTransformer;

class SkuComboSkuTransformer extends TransformerAbstract
{

    public function __construct()
	{
		$this->setDefaultIncludes(['sku']);
	}

    public function transform(SkuComboSku $skuComboSku)
    {
        return  [
            'quantity' => $skuComboSku->quantity,
        ];
    }

    /**
     * Include Sku
     *
     * @return \League\Fractal\Resource\Item
     */
    public function includeSku(SkuComboSku $skuComboSku)
    {
        $sku = $skuComboSku->sku;

        return $this->item($sku, new SkuTransformer);
    }

}
