<?php

namespace Modules\Product\Transformers;

use App\Base\Transformer;
use Modules\Product\Models\Product;
use Modules\Product\Models\ProductPrice;
use Modules\Product\Models\ProductPriceDetail;
use Modules\Product\Models\Sku;

class MerchantProductDropShipDetailTransformer extends Transformer
{
    /**
     * Transform the data
     *
     * @param Product $product
     * @return mixed
     */
    public function transform($product)
    {
        $productSkus = $product->skus()->where('status', '!=', Sku::STATUS_STOP_SELLING)->get();
        $skus = [];
        foreach ($productSkus as $productSku) {
            $skus[] = [
                'sku' => $productSku,
                'optionValues' => $productSku->optionValues
            ];
        }

        $merchant = $product->merchant;
        $productPriceActive = $product->productPriceActive();
        $productPriceActiveCreator = null;
        $priceDetails = [];
        if($productPriceActive instanceof ProductPrice) {
            $productPriceActiveCreator = $productPriceActive->creator;

            if($productPriceActive->type == ProductPrice::TYPE_COMBO) {
                $priceDetails = $productPriceActive->priceDetails()->orderBy('combo', 'asc')->get();
            } else {
                $priceDetails = $productPriceActive->priceDetails()->orderBy('id', 'asc')->get()->load(['sku']);
            }

            if($productPriceActive->type == ProductPrice::TYPE_SKU && $priceDetails->count()) {
                /** @var ProductPriceDetail $priceDetail */
                foreach ($priceDetails as $priceDetail) {
                    $priceDetail->sku = $priceDetail->sku()->first(['id', 'code', 'name']);
                }
            }
        }

        return array_merge($product->only(['category', 'unit', 'services', 'servicePrices']), [
            'product' => $product,
            'options' => $product->options(),
            'skus' => $skus,
            'product_price_active' => $productPriceActive,
            'product_price_active_creator' => $productPriceActiveCreator,
            'product_price_active_details' => $priceDetails,
            'merchant' => $merchant,
            'currency' => $merchant->getCurrency()
        ]);
    }
}
