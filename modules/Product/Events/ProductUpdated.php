<?php

namespace Modules\Product\Events;

use App\Base\Event;
use Modules\Product\Models\Product;

class ProductUpdated extends Event
{
    /**
     * @var Product
     */
    public $productId;
    public $userId;
    public $payload;
    public $autoPrice; // Có tự động tính lại mức giá của sản phẩm hay ko

    /**
     * ProductCreated constructor.
     * @param $productId
     * @param $userId
     * @param array $payload
     * @param bool $autoPrice
     */
    public function __construct($productId, $userId, array $payload = [], bool $autoPrice = true)
    {
        $this->productId = $productId;
        $this->userId    = $userId;
        $this->payload   = $payload;
        $this->autoPrice = $autoPrice;
    }
}
