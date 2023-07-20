<?php

namespace Modules\Product\Listeners;

use App\Base\QueueableListener;
use Modules\Product\Events\ProductUpdated;
use Modules\Product\Listeners\Traits\AutoSetServiceProductTrait;
use Modules\Product\Models\Product;
use Modules\User\Models\User;

class AutoSetServiceProductWhenUpdateListener extends QueueableListener
{
    use AutoSetServiceProductTrait;

    /**
     * @param ProductUpdated $event
     */
    public function handle(ProductUpdated $event)
    {
        /** @var Product $product */
        $product = Product::find($event->productId);

        if ($product->merchant && !$product->merchant->servicePack) {
            $userId    = $event->userId ?: $product->creator_id;
            $user      = User::find($userId);
            $autoPrice = $event->autoPrice;

            $this->autoSetService($product, $user, $autoPrice);
        }
    }
}
