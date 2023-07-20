<?php

namespace Modules\Product\Commands;

use Modules\Product\Models\Product;
use Modules\Product\Models\ProductPrice;
use Modules\Product\Services\ProductEvent;
use Modules\User\Models\User;
class CancelProductPrice
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
        if($this->productPrice->status == ProductPrice::STATUS_CANCELED) {
            return $this->productPrice;
        }
        $productPriceOld = clone $this->productPrice;

        $this->productPrice->status = ProductPrice::STATUS_CANCELED;
        $this->productPrice->save();

        $product  = $this->productPrice->product;
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
        $productPrices     = $product->productPrices()->get();
        $productPriceTotal = $productPrices->count();
        $productPriceCancelTotal = $productPrices->where('status', ProductPrice::STATUS_CANCELED)->count();
        $productPriceWaitingConfirmTotal = $productPrices->where('status', ProductPrice::STATUS_WAITING_CONFIRM)->count();

        $productStatus = '';

        if($productPriceCancelTotal == $productPriceTotal) { //nếu toàn bộ báo giá là hủy
            $productStatus = Product::STATUS_WAITING_FOR_QUOTE;
        } else if (
            $productPriceWaitingConfirmTotal &&
            ($productPriceWaitingConfirmTotal + $productPriceCancelTotal) == $productPriceTotal
        ) { //nếu chỉ còn lại báo giá “chờ xác nhận” (ngoài trạng thái hủy)
            $productStatus = Product::STATUS_WAITING_CONFIRM;
        }

        if($productStatus && $product->status != $productStatus) {

            $product->logActivity(ProductEvent::UPDATE_STATUS, $this->creator,[
                'from' => $product->status,
                'to'   => $productStatus,
                'reason' => 'product_price_cancel',
                'product_price_id' => $this->productPrice->id,
            ]);

            $product->status = $productStatus;
            $product->save();
        }

    }
}
