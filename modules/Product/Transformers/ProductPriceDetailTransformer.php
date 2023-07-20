<?php

namespace Modules\Product\Transformers;

use App\Base\Transformer;
use Modules\Product\Models\Product;
use Modules\Product\Models\ProductPrice;
use Modules\Product\Models\ProductPriceDetail;
use Modules\Product\Models\Sku;

class ProductPriceDetailTransformer extends Transformer
{
    /**
     * Transform the data
     *
     * @param ProductPrice $productPrice
     * @return mixed
     */
    public function transform($productPrice)
    {
        $priceDetails = $productPrice->priceDetails;
        if($productPrice->type == ProductPrice::TYPE_SKU && $priceDetails->count()) {
            /** @var ProductPriceDetail $priceDetail */
            foreach ($priceDetails as $priceDetail) {
                $priceDetail->sku = $priceDetail->sku()->first(['id', 'code', 'name']);
            }
        }

        return [
            'product_price' => $productPrice->attributesToArray(),
            'product' => $productPrice->product,
            'creator' => $productPrice->creator,
            'price_details' => $priceDetails,

        ];
    }
}
