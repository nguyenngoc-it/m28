<?php

namespace Modules\Product\Commands;

use Illuminate\Support\Facades\DB;
use Modules\Merchant\Models\Merchant;
use Modules\Product\Events\ProductCreated;
use Modules\Product\Models\Product;
use Modules\Product\Models\ProductMerchant;
use Modules\Product\Models\Sku;

class MerchantCreateProduct extends UpdateProductBase
{
    /**
     * @return Product
     */
    public function handle()
    {
        DB::transaction(function () {
            $this->updateBase();
            $this->updateImages();
            $this->updateServices();

            $product = $this->product->refresh();

            $this->createSku($product);
            $this->createProductMerchant($product, $this->merchant);
        });

        $product = $this->product->refresh();
        (new ProductCreated($product->id))->queue();

        return $product;
    }

    /**
     * Tạo sku
     *
     * @param Product $product
     * @return Sku
     */
    protected function createSku(Product $product)
    {
        $sku = Sku::create([
            'tenant_id' => $product->tenant_id,
            'merchant_id' => $product->merchant_id,
            'product_id' => $product->id,
            'unit_id' => $product->unit_id,
            'category_id' => $product->category_id,
            'creator_id' => $product->creator_id,
            'status' => Sku::STATUS_ON_SELL,
            'code' => trim($product->code),
            'name' => $product->name,
            'weight' => $product->weight,
            'height' => $product->height,
            'width' => $product->width,
            'length' => $product->length
        ]);

        return $sku;
    }

    /**
     * @param Product $product
     * @param Merchant $merchant
     */
    protected function createProductMerchant(Product $product, Merchant $merchant)
    {
        ProductMerchant::create([
            'product_id' => $product->id,
            'merchant_id' => $merchant->id
        ]);
    }
}
