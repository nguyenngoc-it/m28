<?php

namespace Modules\Product\Transformers;

use App\Base\Transformer;
use Modules\Product\Models\Product;
use Modules\Product\Models\ProductPrice;
use Modules\Product\Models\ProductPriceDetail;

class MerchantProductListItemTransformer extends Transformer
{
    /**
     * Transform the data
     *
     * @param Product $product
     * @return mixed
     */
    public function transform($product)
    {
        $canChangeStatus = [];
        foreach (Product::$statusList as $status) {
            $canChangeStatus[$status] = $product->canChangeStatus($status);
        }

        $productPriceActive = null;
        $priceDetails = [];

        if($product->dropship) {
            $productPriceActive = $product->productPriceActive();
            if($productPriceActive instanceof ProductPrice) {
                if($productPriceActive->type == ProductPrice::TYPE_COMBO) {
                    $priceDetails = $productPriceActive->priceDetails()->orderBy('combo', 'asc')->get();
                } else {
                    $priceDetails = $productPriceActive->priceDetails()->orderBy('id', 'asc')->get();
                }
            }
        }

        return array_merge($product->only(['category', 'unit']), [
            'sku_total' => $product->skusActice()->count(),
            'product' => $product,
            'can_change_status' => $canChangeStatus,
            'product_price_active' => $productPriceActive,
            'product_price_active_details' => $priceDetails,
        ]);
    }
}
