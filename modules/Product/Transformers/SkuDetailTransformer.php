<?php

namespace Modules\Product\Transformers;

use App\Base\Transformer;
use Modules\Product\Models\Sku;

class SkuDetailTransformer extends Transformer
{
    /**
     * Transform the data
     *
     * @param Sku $sku
     * @return mixed
     */
    public function transform($sku)
    {
        $tenant   = $sku->tenant;
        $product  = $sku->product;
        $category = $sku->category;
        $prices   = $sku->prices;
        $unit     = $sku->unit;
        $supplier = $sku->supplier;
        return compact('tenant','sku', 'category', 'prices', 'unit', 'product', 'supplier');
    }
}
