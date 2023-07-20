<?php

namespace Modules\Product\Listeners;

use App\Base\QueueableListener;
use Modules\Product\Events\ProductCreated;
use Modules\Product\Listeners\Traits\AutoSetServiceProductTrait;
use Modules\Product\Models\Product;

class AutoSetServiceProductListener extends QueueableListener
{
    use AutoSetServiceProductTrait;

    /**
     * @param ProductCreated $event
     */
    public function handle(ProductCreated $event)
    {
        /** @var Product $product */
        $product = Product::find($event->productId);
        if ($product->merchant && !$product->merchant->servicePack) {
            $this->autoSetService($product, $product->creator);
        }
    }
}
