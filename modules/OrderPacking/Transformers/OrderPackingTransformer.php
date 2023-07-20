<?php

namespace Modules\OrderPacking\Transformers;

use App\Base\Transformer;
use Modules\Order\Models\OrderSku;
use Modules\OrderPacking\Models\OrderPacking;

class OrderPackingTransformer extends Transformer
{
    /**
     * Transform the data
     *
     * @param OrderPacking $orderPacking
     * @return mixed
     */
    public function transform($orderPacking)
    {
        $order   = $orderPacking->order;
        $results = array_merge($orderPacking->attributesToArray(), [
            'order_exporting_id' => $orderPacking->orderExporting ? $orderPacking->orderExporting->id : 0,
            'status_order_exporting' => $orderPacking->orderExporting ? $orderPacking->orderExporting->status : null,
            'order' => $orderPacking->order ? array_merge(
                $orderPacking->order->only([
                    'code',
                    'ref_code',
                    'receiver_name',
                    'receiver_phone',
                    'receiver_note',
                    'cod',
                    'status'
                ])
                ,
                [
                    'receiver_address' => $orderPacking->order->fullReceiverAddress(),
                ],
                [
                    'order_skus' => $orderPacking->order->orderSkus->map(function (OrderSku $orderSku) {
                        $sku = $orderSku->sku;
                        return array_merge(
                            ['quantity' => $orderSku->quantity],
                            ['name' => ($sku) ? $sku->name : ''],
                            ['code' => ($sku) ? $sku->code : ''],
                            ['id' => ($sku) ? $sku->id : ''],
                            ['product_id' => ($sku) ? $sku->product_id : ''],
                            ['weight' => ($sku) ? round($sku->weight * $orderSku->quantity, 2) : 0]
                        );
                    })
                ]
            ) : null,
            'freight_bill' => $orderPacking->freightBill ? $orderPacking->freightBill->freight_bill_code : null,
            'merchant_code' => $orderPacking->merchant->code,
            'merchant_name' => $orderPacking->merchant->name,
            'order_packing_items' => $orderPacking->orderPackingItems,
            'shipping_partner' => ($orderPacking->shippingPartner) ?
                $orderPacking->shippingPartner->only(['id', 'name', 'code']) : [],
            'warehouse' => $orderPacking->warehouse,
            'packing_type' => $orderPacking->packingType,
            'can_remove_warehouse_area' => $order->canRemoveWarehouseArea(),
            'can_add_warehouse_area' => $order->canAddWarehouseArea(),
            'tags' => $orderPacking->order ? $orderPacking->order->getTags() : [],
            'picker' => $orderPacking->picker ? $orderPacking->picker->only(['id', 'username', 'name']) : null,
        ]);

        if (!empty($orderPacking->toArray()['pivot'])) {
            $results['pivot'] = $orderPacking->toArray()['pivot'];
        }

        return $results;
    }
}
