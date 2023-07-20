<?php

namespace Modules\Product\Commands;

use Illuminate\Support\Facades\DB;
use Modules\Merchant\Models\Merchant;
use Modules\Product\Models\Product;
use Modules\Product\Models\ProductMerchant;
use Modules\Product\Services\ProductEvent;

class MerchantCreateProductDropShip extends UpdateProduct
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

            $this->createProductMerchant($product, $this->user->merchant);

            $this->oldProductCode = $product->code;

            $this->syncOptions();

            $this->syncSkus();

            $this->createDefaultSku();
        });

        $product = $this->product->refresh();
        $product->logActivity(ProductEvent::CREATE, $this->user);

        return $product;
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
