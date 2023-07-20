<?php

namespace Modules\Merchant\ExternalTransformers;

use App\Base\Transformer;
use Modules\Order\Models\Order;

class OrderTransformer extends Transformer
{
    /**
     * Transform the data
     *
     * @param Order $order
     * @return array
     */
    public function transform($order)
    {
        return $order->only([
            'code',
            'status',
            'order_amount',
            'discount_amount',
            'shipping_amount',
            'total_amount',
            'paid_amount',
            'debit_amount',
            'receiver_name',
            'receiver_phone',
            'receiver_address',
            'receiver_note',
            'description',
            'freight_bill',
            'cod',
            'finance_status',
            'service_amount',
            'amount_paid_to_seller',
            'finance_service_status',
            'finance_service_import_return_goods_status',
            'dropship',
            'inspected',
            'intended_delivery_at',
            'created_at',
            'updated_at'
        ]);
    }
}
