<?php

namespace Modules\Product\Transformers;

use App\Base\Transformer;
use Modules\Product\Models\Product;
use Modules\Service\Models\Service;

class MerchantProductDetailTransformer extends Transformer
{
    /**
     * Transform the data
     *
     * @param Product $product
     * @return mixed
     */
    public function transform($product)
    {
        return [
            'product' => $product->attributesToArray(),
            'category' => $product->category ? $product->category->only(['code', 'name']) : null,
            'services' => $product->services->map(function (Service $service) use ($product) {
                return ['service' => $service, 'service_prices' => $product->servicePrices->where('service_code', $service->code)->values()];
            }),
            'skus' => $product->skus
        ];
    }
}
