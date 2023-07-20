<?php

namespace Modules\Merchant\ExternalTransformers;

use App\Base\Transformer;
use Modules\Product\Models\Sku;

class SkuTransformer extends Transformer
{
    /**
     * Transform the data
     *
     * @param Sku $sku
     * @return array
     */
    public function transform($sku)
    {
        return $sku->product->only([
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
