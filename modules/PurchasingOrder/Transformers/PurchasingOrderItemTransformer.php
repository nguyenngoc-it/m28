<?php

namespace Modules\PurchasingOrder\Transformers;

use League\Fractal\TransformerAbstract;
use Modules\PurchasingOrder\Models\PurchasingOrderItem;

class PurchasingOrderItemTransformer extends TransformerAbstract
{
    public function __construct()
    {
        $this->setAvailableIncludes(['purchasing_variant']);
    }

    public function transform(PurchasingOrderItem $purchasingOrderItem)
    {
        return [
            'id' => $purchasingOrderItem->id,
            'purchasing_order_id' => $purchasingOrderItem->purchasing_order_id,
            'purchasing_variant_id' => $purchasingOrderItem->purchasing_variant_id,
            'item_id' => $purchasingOrderItem->item_id,
            'item_code' => $purchasingOrderItem->item_code,
            'item_name' => $purchasingOrderItem->item_name,
            'item_translated_name' => $purchasingOrderItem->item_translated_name,
            'original_price' => $purchasingOrderItem->original_price,
            'price' => $purchasingOrderItem->price,
            'ordered_quantity' => $purchasingOrderItem->ordered_quantity,
            'purchased_quantity' => $purchasingOrderItem->purchased_quantity,
            'received_quantity' => $purchasingOrderItem->received_quantity,
            'product_url' => $purchasingOrderItem->product_url,
            'product_image' => $purchasingOrderItem->product_image,
            'variant_image' => $purchasingOrderItem->variant_image,
            'variant_properties' => $purchasingOrderItem->variant_properties
        ];
    }

    public function includePurchasingVariant(PurchasingOrderItem $purchasingOrderItem)
    {
        $purchasingVariant = $purchasingOrderItem->purchasingVariant;
        if ($purchasingVariant){
            return $this->item($purchasingVariant, new PurchasingVariantTransformerNew);
        }else
            return $this->null();
    }

}
