<?php

namespace Modules\Product\Transformers;

use App\Base\Transformer;
use Modules\Product\Models\ProductPrice;

class ProductPriceListItemTransformer extends Transformer
{
    /**
     * Transform the data
     *
     * @param ProductPrice $productPrice
     * @return mixed
     */
    public function transform($productPrice)
    {
        return [
            'product_price' => $productPrice->attributesToArray(),
            'product' => $productPrice->product,
            'creator' => $productPrice->creator,
            'price_details' => $productPrice->priceDetails,

        ];
    }
}
