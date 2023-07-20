<?php

namespace Modules\Product\Listeners;

use App\Base\QueueableListener;
use Modules\Product\Events\ProductUpdated;
use Modules\Product\Models\Product;
use Modules\Product\Services\ProductEvent;
use Modules\User\Models\User;

class LogProductActivityListener extends QueueableListener
{
    /**
     * @param $event
     */
    public function handle($event)
    {
        if ($event instanceof ProductUpdated) {
            /** @var Product $product */
            $product = Product::find($event->productId);
            /** @var User $user */
            $user = User::find($event->userId);
            $product->logActivity(ProductEvent::UPDATE, $user, $event->payload);
        }
    }
}
