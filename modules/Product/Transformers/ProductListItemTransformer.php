<?php

namespace Modules\Product\Transformers;

use App\Base\Transformer;
use Modules\Product\Models\Product;

class ProductListItemTransformer extends Transformer
{
    /**
     * Transform the data
     *
     * @param Product $product
     * @return mixed
     */
    public function transform($product)
    {
        return array_merge($product->only(['category', 'unit', 'creator', 'merchants', 'supplier']), [
            'product' => $product,
            'stock' => [
                'quantity' => (int) $product->stocks()->sum('quantity'),
                'real_quantity' => (int) $product->stocks()->sum('real_quantity'),
            ]
        ]);
    }
}
