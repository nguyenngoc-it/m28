<?php

namespace Modules\PurchasingOrder\Transformers;

use League\Fractal\TransformerAbstract;
use Modules\Product\Transformers\SkuTransformer;
use Modules\PurchasingOrder\Models\PurchasingVariant;

class PurchasingVariantTransformerNew extends TransformerAbstract
{

    public function __construct()
    {
        $this->setAvailableIncludes(['sku']);
    }
    public function transform(PurchasingVariant $purchasingVariant)
    {
        return [
            'id' => $purchasingVariant->id,
            'tenant_id' => $purchasingVariant->tenant_id,
            'marketplace' => $purchasingVariant->marketplace,
            'variant_id' => $purchasingVariant->variant_id,
            'code' => $purchasingVariant->code,
            'sku_id' => $purchasingVariant->sku_id,
            'name' => $purchasingVariant->name,
            'translated_name' => $purchasingVariant->translated_name,
            'image' => $purchasingVariant->image,
            'properties' => $purchasingVariant->properties,
            'product_url' => $purchasingVariant->product_url,
            'product_image' => $purchasingVariant->product_image,
            'supplier_code' => $purchasingVariant->supplier_code,
            'supplier_name' => $purchasingVariant->supplier_name,
            'supplier_url' => $purchasingVariant->supplier_url,
            'payload' => $purchasingVariant->payload
        ];
    }

    public function includeSku(PurchasingVariant $purchasingVariant)
    {
        $sku = $purchasingVariant->sku;
        if ($sku){
            return $this->item($sku, new SkuTransformer());
        }else
            return $this->null();
    }
}
