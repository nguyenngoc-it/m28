<?php

namespace Modules\Product\Transformers;

use App\Base\Transformer;
use Modules\Product\Models\Product;

class ProductDropShipListItemTransformer extends Transformer
{
    /**
     * Transform the data
     *
     * @param Product $product
     * @return mixed
     */
    public function transform($product)
    {
        return array_merge($product->only(['category', 'unit', 'merchant']), [
            'product' => $product,
            'sku_total' => $product->skusActice()->count(),
        ]);
    }
}
