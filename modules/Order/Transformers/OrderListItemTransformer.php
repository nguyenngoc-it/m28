<?php

namespace Modules\Order\Transformers;

use App\Base\Transformer;
use Modules\Order\Models\Order;
use Modules\Order\Models\OrderSku;
use Modules\Product\Models\Sku;
use Modules\Product\Models\SkuCombo;

class OrderListItemTransformer extends Transformer
{

    /**
     * Transform the data
     *
     * @param Order $order
     * @return mixed
     */
    public function transform($order)
    {
        $images    = [];
        $orderSkus = $order->orderSkus->transform(function (OrderSku $orderSku) use (&$images) {
            $sku = $orderSku->sku;
            if ($sku instanceof Sku) {
                if (empty($images) && !empty($sku->product->images)) {
                    $images = $sku->product->images;
                }
                return array_merge($orderSku->toArray(), $sku->only(['code', 'name']));
            }
        });
        // $orderSkuComboReturn = [];
        // $orderSkuCombos = $order->skuCombos;
        // if ($orderSkuCombos){
        //     foreach ($orderSkuCombos as $orderSkuCombo){
        //         $skuCombo = SkuCombo::query()->where('id', $orderSkuCombo->sku_combo_id)->first();
        //         if ($skuCombo) {
        //             $orderSkuComboReturn[] = $skuCombo->code;
        //         }
        //     }
        // }
        
        $nameStore = $order->name_store;
        $store     = $order->store;
        if (!$nameStore && $store) {
            $nameStore = $store->getNameStore();
        }

        return array_merge($order->only(['merchant', 'currency']), [
            'order' => $order,
            'name_store' => $nameStore,
            'creator' => array_merge($order->creator->only(['id', 'username']), ['merchant_code' => $order->creator->merchant ? $order->creator->merchant->code : null]),
            'shipping_partner' => $order->shippingPartner ? $order->shippingPartner->only(['id', 'code', 'name']) : null,
            'order_skus' => $orderSkus,
            'images' => $images,
            'order_freight_bills' => $order->freightBills->map(function ($freightBill) {
                return [
                    'freight_bill_code' => $freightBill->freight_bill_code,
                    'shipping_partner_code' => $freightBill->shippingPartner ? $freightBill->shippingPartner->code : null,
                    'shipping_partner_name' => $freightBill->shippingPartner ? $freightBill->shippingPartner->name : null,
                ];
            }),
        ]);
    }
}
