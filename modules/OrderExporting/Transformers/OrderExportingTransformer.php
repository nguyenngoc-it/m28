<?php

namespace Modules\OrderExporting\Transformers;

use App\Base\Transformer;
use Modules\OrderExporting\Models\OrderExporting;

class OrderExportingTransformer extends Transformer
{
    /**
     * Transform the data
     *
     * @param OrderExporting $orderExporting
     * @return array
     */
    public function transform($orderExporting)
    {
        return array_merge($orderExporting->attributesToArray(), [
            'order' => $orderExporting->order ? $orderExporting->order->only([
                'code',
                'receiver_name',
                'receiver_phone',
                'receiver_address'
            ]) : null,
            'freight_bill' => $orderExporting->freightBill ? $orderExporting->freightBill->freight_bill_code : null,
            'order_exporting_items' => $orderExporting->orderExportingItems,
            'shipping_partner' => ($orderExporting->shippingPartner) ?
                $orderExporting->shippingPartner->only(['id', 'name', 'code']) : [],
        ]);
    }
}
