<?php

namespace Modules\Product\Commands;

use Modules\Product\Models\Product;
use Modules\Product\Models\ProductPrice;
use Modules\Product\Services\ProductEvent;
use Modules\User\Models\User;

class ActiveProductPrice
{

    /**
     * @var User|null
     */
    protected $creator = null;


    /**
     * @var ProductPrice|null
     */
    protected $productPrice = null;

    /**
     * CancelProductPrice constructor.
     * @param ProductPrice $productPrice
     * @param User $creator
     */
    public function __construct(ProductPrice $productPrice, User $creator)
    {
        $this->creator = $creator;
        $this->productPrice = $productPrice;
    }

    /**
     * @return ProductPrice
     */
    public function handle()
    {
        if($this->productPrice->status == ProductPrice::STATUS_ACTIVE) {
            return $this->productPrice;
        }

        $product  = $this->productPrice->product;
        //chuyển các báo giá xác nhận từ trước về chờ xác nhận
        $product->productPrices()->where('status', ProductPrice::STATUS_ACTIVE)->update([
            'status' => ProductPrice::STATUS_WAITING_CONFIRM
        ]);

        $productPriceOld = clone $this->productPrice;
        $this->productPrice->status = ProductPrice::STATUS_ACTIVE;
        $this->productPrice->save();


        $product->logActivity(ProductEvent::UPDATE_PRODUCT_PRICE_STATUS, $this->creator,[
            'from' => $productPriceOld->status,
            'to'   => $this->productPrice->status,
            'product_price_id' => $this->productPrice->id,
        ]);

        $this->updateProductStatus($product);

        return $this->productPrice->refresh();
    }

    /**
     * @param Product $product
     */
    protected function updateProductStatus(Product $product)
    {
        $productStatus = Product::STATUS_ON_SELL;
        if($product->status == Product::STATUS_ON_SELL) {
            return;
        }

        $product->status = $productStatus;
        $product->save();

        $product->logActivity(ProductEvent::UPDATE_STATUS, $this->creator,[
            'from' => $product->status,
            'to'   => $productStatus,
            'reason' => 'product_price_active',
            'product_price_id' => $this->productPrice->id,
        ]);
    }
}
