<?php

namespace Modules\EventBridge\Transformers;

use App\Base\Transformer;
use Modules\Order\Models\OrderSku;

class OrderSkuTransformer extends Transformer
{
    /**
     * Transform the data
     *
     * @param OrderSku $orderSku
     * @return mixed
     */
    public function transform($orderSku)
    {
        return array_merge($orderSku->only([
            'id',
            'tenant_id',
            'order_id',
            'sku_id',
            'tax',
            'price',
            'quantity',
            'order_amount',
            'discount_amount',
            'total_amount',
            'created_at',
            'updated_at',
        ]), [
            'sku_code' => $orderSku->sku->code,
        ]);
    }
}
