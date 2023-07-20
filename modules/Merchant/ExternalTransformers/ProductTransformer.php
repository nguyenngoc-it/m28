<?php

namespace Modules\Merchant\ExternalTransformers;

use App\Base\Transformer;
use Modules\Product\Models\Product;

class ProductTransformer extends Transformer
{
    /**
     * Transform the data
     *
     * @param Product $product
     * @return array
     */
    public function transform($product)
    {
        return $product->only([
            'code',
            'status',
            'name',
            'description',
            'image',
            'images',
            'dropship',
            'weight',
            'height',
            'width',
            'length',
            'created_at',
            'updated_at'
        ]);
    }
}
