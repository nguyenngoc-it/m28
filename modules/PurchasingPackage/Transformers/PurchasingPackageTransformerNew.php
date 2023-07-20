<?php

namespace Modules\PurchasingPackage\Transformers;

use League\Fractal\TransformerAbstract;
use Modules\Merchant\ExternalTransformers\MerchantTransformerNew;
use Modules\PurchasingPackage\Models\PurchasingPackage;
use Modules\Warehouse\Transformers\WarehouseTransformerNew;

class PurchasingPackageTransformerNew extends TransformerAbstract
{
    public function __construct()
    {
        $this->setAvailableIncludes(['merchant', 'purchasing_package_item', 'destination_warehouse', 'purchasing_package_services']);
    }

    public function transform(PurchasingPackage $purchasingPackage)
    {
        return [
            'id' => $purchasingPackage->id,
            'tenant_id' => $purchasingPackage->tenant_id,
            'code' => $purchasingPackage->code,
            'weight' => $purchasingPackage->weight,
            'length' => $purchasingPackage->length,
            'width' => $purchasingPackage->width,
            'height' => $purchasingPackage->height,
            'status' => $purchasingPackage->status,
            'created_at' => $purchasingPackage->created_at,
            'destination_warehouse_id' => $purchasingPackage->destination_warehouse_id,
            'shipping_partner_id' => $purchasingPackage->shipping_partner_id,
            'freight_bill_code' => $purchasingPackage->freight_bill_code,
            'service_amount' => $purchasingPackage->service_amount,
            'creator_id' => $purchasingPackage->creator_id,
            'merchant_id' => $purchasingPackage->merchant_id,
            'quantity' => $purchasingPackage->quantity,
            'received_quantity' => $purchasingPackage->received_quantity,
            'imported_at' => $purchasingPackage->imported_at,
            'finance_status' => $purchasingPackage->finance_status,
            'is_putaway' => $purchasingPackage->is_putaway,
            'note' => $purchasingPackage->note
        ];
    }

    public function includeMerchant(PurchasingPackage $purchasingPackage)
    {
        $merchant = $purchasingPackage->merchant;
        if ($merchant) {
            return $this->item($merchant, new MerchantTransformerNew);
        }else
            return $this->null();
    }

    public function includePurchasingPackageItem(PurchasingPackage $purchasingPackage)
    {
        $purchasingPackageItem = $purchasingPackage->purchasingPackageItems;
        return $this->collection($purchasingPackageItem, new PurchasingPackageItemTransformer);
    }

    public function includeDestinationWarehouse(PurchasingPackage $purchasingPackage)
    {
        $destinationWarehouse = $purchasingPackage->destinationWarehouse;
        if ($destinationWarehouse) {
            return $this->item($destinationWarehouse, new WarehouseTransformerNew);
        }else
            return $this->null();
    }

    public function includePurchasingPackageServices(PurchasingPackage $purchasingPackage)
    {
        $purchasingPackageServices = $purchasingPackage->purchasingPackageServices;

        return $this->collection($purchasingPackageServices, new PurchasingPackageServiceTransformer);
    }

}
