<?php

namespace Modules\Product\Events;

use App\Base\Event;

class ProductCreated extends Event
{
    public $productId;

    /**
     * ProductCreated constructor.
     * @param $productId
     */
    public function __construct($productId)
    {
        $this->productId = $productId;
    }
}
