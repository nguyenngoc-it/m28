<?php

namespace Modules\Product\Transformers;

use App\Base\Transformer;
use Modules\Currency\Models\Currency;
use Modules\Merchant\Models\Merchant;
use Modules\Product\Models\Product;
use Modules\Product\Models\ProductPrice;
use Modules\Product\Models\ProductPriceDetail;

class ProductDetailTransformer extends Transformer
{
    /**
     * Transform the data
     *
     * @param Product $product
     * @return mixed
     */
    public function transform($product)
    {
        $productSkus = $product->skus;
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

        $currency = ($merchant instanceof Merchant) ? $merchant->getCurrency() : null;
        if(!$currency instanceof Currency) {
            $merchants = $product->merchants;
            if($merchants) {
                /** @var Merchant $productMerchant */
                foreach ($merchants as $productMerchant) {
                    if(!$merchant instanceof Merchant) {
                        $merchant = $productMerchant;
                    }
                    if(!$currency instanceof Currency) {
                        $currency = $productMerchant->getCurrency();
                    }
                }
            }
        }

        return array_merge($product->only(['tenant', 'category', 'unit', 'services', 'servicePrices']), [
            'product' => $product,
            'options' => $product->options(),
            'skus' => $skus,
            'product_price_active' => $productPriceActive,
            'product_price_active_creator' => $productPriceActiveCreator,
            'product_price_active_details' => $priceDetails,
            'merchant' => $merchant,
            'currency' => $currency,
            'supplier' => $product->supplier,
        ]);
    }
}
