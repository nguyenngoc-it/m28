<?php

namespace Modules\Product\Transformers;

use App\Base\Transformer;
use Modules\Product\Models\Sku;

class SelectedSkuItemTransformer extends Transformer
{
    /**
     * Transform the data
     *
     * @param Sku $sku
     * @return mixed
     */
    public function transform($sku)
    {
        $product = $sku->product;
        $merchants = $product ? $product->merchants : null;
        return array_merge($sku->attributesToArray(), [
            'product' => $product ? $product : null,
            'merchants' => $merchants ? $merchants : []
        ]);
    }
}
