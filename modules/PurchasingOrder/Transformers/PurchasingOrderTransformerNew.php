<?php

namespace Modules\PurchasingOrder\Transformers;

use League\Fractal\TransformerAbstract;
use Modules\PurchasingManager\Transformers\PurchasingAccountTransformerNew;
use Modules\PurchasingManager\Transformers\PurchasingServiceTransformer;
use Modules\PurchasingOrder\Models\PurchasingOrder;
use Modules\PurchasingPackage\Transformers\PurchasingPackageServiceTransformer;
use Modules\PurchasingPackage\Transformers\PurchasingPackageTransformerNew;

class PurchasingOrderTransformerNew extends TransformerAbstract
{
    public function __construct()
    {
        $this->setAvailableIncludes(['purchasing_account', 'purchasing_service', 'purchasing_packages', 'purchasing_order_items']);
    }

    public function transform(PurchasingOrder $purchasingOrder)
    {
        return [
            'id' => $purchasingOrder->id,
            'tenant_id' => $purchasingOrder->tenant_id,
            'purchasing_service_id' => $purchasingOrder->purchasing_service_id,
            'purchasing_account_id' => $purchasingOrder->purchasing_account_id,
            'merchant_id' => $purchasingOrder->merchant_id,
            'warehouse_id' => $purchasingOrder->warehouse_id,
            'code' => $purchasingOrder->code,
            'm1_order_url' => $purchasingOrder->m1_order_url,
            'status' => $purchasingOrder->status,
            'marketplace' => $purchasingOrder->marketplace,
            'image' => $purchasingOrder->image,
            'supplier_code' => $purchasingOrder->supplier_code,
            'supplier_name' => $purchasingOrder->supplier_name,
            'supplier_url' => $purchasingOrder->supplier_url,
            'customer_username' => $purchasingOrder->customer_username,
            'customer_name' => $purchasingOrder->customer_name,
            'customer_phone' => $purchasingOrder->customer_phone,
            'customer_address' => $purchasingOrder->customer_address,
            'receiver_name' => $purchasingOrder->receiver_name,
            'receiver_phone' => $purchasingOrder->receiver_phone,
            'receiver_country_code' => $purchasingOrder->receiver_country_code,
            'receiver_city_code' => $purchasingOrder->receiver_city_code,
            'receiver_district_code' => $purchasingOrder->receiver_district_code,
            'receiver_ward_code' => $purchasingOrder->receiver_ward_code,
            'receiver_address' => $purchasingOrder->receiver_address,
            'receiver_note' => $purchasingOrder->receiver_note,
            'ordered_quantity' => $purchasingOrder->ordered_quantity,
            'purchased_quantity' => $purchasingOrder->purchased_quantity,
            'received_quantity' => $purchasingOrder->received_quantity,
            'currency' => $purchasingOrder->currency,
            'exchange_rate' => $purchasingOrder->exchange_rate,
            'original_total_value' => $purchasingOrder->original_total_value,
            'total_value' => $purchasingOrder->total_value,
            'total_fee' => $purchasingOrder->total_fee,
            'grand_total' => $purchasingOrder->grand_total,
            'is_putaway' => $purchasingOrder->is_putaway,
            'total_paid' => $purchasingOrder->total_paid,
            'total_unpaid' => $purchasingOrder->total_unpaid,
            'ordered_at' => $purchasingOrder->ordered_at
        ];
    }

    public function includePurchasingAccount(PurchasingOrder $purchasingOrder)
    {
        $purchasingAccount = $purchasingOrder->purchasingAccount;
        if ($purchasingAccount) {
            return $this->item($purchasingAccount, new PurchasingAccountTransformerNew);
        } else
            return $this->null();
    }

    public function includePurchasingService(PurchasingOrder $purchasingOrder)
    {
        $purchasingService = $purchasingOrder->purchasingService;
        if ($purchasingService) {
            return $this->item($purchasingService, new PurchasingServiceTransformer);
        } else
            return $this->null();
    }

    public function includePurchasingPackages(PurchasingOrder $purchasingOrder)
    {
        $purchasingPackages = $purchasingOrder->purchasingPackages;
        if ($purchasingPackages) {
            return $this->collection($purchasingPackages, new PurchasingPackageTransformerNew);
        } else
            return $this->null();
    }

    public function includePurchasingOrderItems(PurchasingOrder $purchasingOrder)
    {
        $purchasingOrderItems = $purchasingOrder->purchasingOrderItems;
        if ($purchasingOrderItems) {
            return $this->collection($purchasingOrderItems, new PurchasingOrderItemTransformer);
        } else
            return $this->null();
    }

}
