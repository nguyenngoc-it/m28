<?php

namespace Modules\Product\Listeners;

use App\Base\QueueableListener;
use Modules\Product\Events\ProductCreated;
use Modules\Product\Jobs\SetServicePackForProductJob;
use Modules\Product\Listeners\Traits\AutoSetServiceProductTrait;
use Modules\Product\Models\Product;
use Modules\Product\Services\ProductEvent;
use Modules\User\Models\User;

class ProductCreatedListener extends QueueableListener
{
    use AutoSetServiceProductTrait;

    /**
     * @param ProductCreated $event
     */
    public function handle(ProductCreated $event)
    {
        /** @var Product $product */
        $product = Product::find($event->productId);
        /** @var User $user */
        $user = $product->creator;
        $product->logActivity(ProductEvent::CREATE, $user, [], [
            'time' => $product->created_at,
        ]);

        /**
         * Nếu sản phẩm tạo ra mà chưa được khởi tạo dịch vụ thì add dịch vụ theo gói của seller
         */
        if ($product->merchant && $product->merchant->servicePack && $product->servicePrices->count() == 0) {
            dispatch(new SetServicePackForProductJob($product, $product->merchant->servicePack, $user));
        }

    }
}
