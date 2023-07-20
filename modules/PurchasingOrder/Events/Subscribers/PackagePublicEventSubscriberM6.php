<?php

namespace Modules\PurchasingOrder\Events\Subscribers;

use Illuminate\Support\Arr;
use Modules\PurchasingOrder\Models\PurchasingOrder;
use Modules\PurchasingOrder\Models\PurchasingOrderItem;
use Modules\PurchasingPackage\Models\PurchasingPackage;
use Modules\PurchasingOrder\Models\PurchasingVariant;
use Modules\Service;
use Modules\User\Models\User;
use Modules\WarehouseStock\Jobs\CalculateWarehouseStockJob;

class PackagePublicEventSubscriberM6 extends PackagePublicEventSubscriberBase
{
    /**
     * @return void
     */
    public function handle()
    {
        if ($this->isValidData()) {
            $userM6 = Service::user()->getUserM6();
            /** @var PurchasingPackage $purchasingPackage */
            $purchasingPackage = PurchasingPackage::query()->where('purchasing_order_id', $this->purchasingOrder->id)
                ->where('code', Arr::get($this->payload, 'package.code'))->first();
            $data = [
                'tenant_id' => $this->purchasingOrder->tenant_id,
                'creator_id' => ($userM6 instanceof User) ? $userM6->id : 0,
                'merchant_id' => $this->purchasingOrder->merchant_id,
                'destination_warehouse_id' => $this->purchasingOrder->warehouse_id,
                'weight' => Arr::get($this->payload, 'package.weight_net'),
                'length' => Arr::get($this->payload, 'package.length'),
                'width' => Arr::get($this->payload, 'package.width'),
                'height' => Arr::get($this->payload, 'package.height'),
                'freight_bill_code' => Arr::get($this->payload, 'package.barcode'),
            ];
            $status = Arr::get($this->payload, 'package.status_transport');
            if(!$purchasingPackage instanceof PurchasingPackage) {
                $purchasingPackage = PurchasingPackage::create(array_merge($data, [
                    'purchasing_order_id' => $this->purchasingOrder->id,
                    'code' => Arr::get($this->payload, 'package.code'),
                    'status' => $status,
                ]));
            } else {
                $purchasingPackage->update($data);

                if($status != $purchasingPackage->status) {
                    $purchasingPackage->changeStatus($status, $userM6);
                }
            }

            if ($this->purchasingOrder->is_putaway) {
                $purchasingPackage->is_putaway = true;
            }

            $isShipmentPackage = Arr::get($this->payload, 'package.is_shipment', false);
            if (!$isShipmentPackage) {
                $this->syncPackageItemsByOrder($purchasingPackage);
            } else {
                $this->syncPackageItems($purchasingPackage);
            }
            Service::purchasingOrder()->syncServiceToPackage($this->purchasingOrder, $purchasingPackage);

            $purchasingPackage->quantity = $purchasingPackage->purchasingPackageItems()->sum('quantity');

            $purchasingPackage->save();
        }
    }

    /**
     * Đồng bộ items của package theo thông tin sp kiện
     * @param PurchasingPackage $purchasingPackage
     */
    protected function syncPackageItems(PurchasingPackage $purchasingPackage)
    {
        $syncDatas = [];
        foreach ($this->products as $product) {
            /** @var PurchasingVariant $purchasingVariant */
            $purchasingVariant = $this->findPurchasingVariantPackage($this->purchasingOrder->tenant_id, $product);
            if (!$purchasingVariant) {
                $purchasingVariant = $this->createPurchasingVariantPackage($this->purchasingOrder->tenant_id, $product);
            }
            $syncDatas[$purchasingVariant->id]['purchasing_variant_id'] = $purchasingVariant->id;
            $syncDatas[$purchasingVariant->id]['sku_id']                = $purchasingVariant->sku_id;
            $syncDatas[$purchasingVariant->id]['quantity']              = Arr::get($product, 'received_quantity', 0);
        }
        $purchasingPackage->purchasingVariants()->sync($syncDatas);
    }

    /**
     * Đồng bộ items của package theo thông tin sp đơn
     * @param PurchasingPackage $purchasingPackage
     */
    protected function syncPackageItemsByOrder(PurchasingPackage $purchasingPackage)
    {
        $syncDatas = [];
        foreach ($this->products as $product) {
            /** @var PurchasingVariant $purchasingVariant */
            $purchasingVariant = $this->findPurchasingVariantOrder($this->purchasingOrder, $product);
            if ($purchasingVariant) {
                $syncDatas[$purchasingVariant->id]['purchasing_variant_id'] = $purchasingVariant->id;
                $syncDatas[$purchasingVariant->id]['sku_id']                = $purchasingVariant->sku_id;
                $syncDatas[$purchasingVariant->id]['quantity']              = Arr::get($product, 'received_quantity', 0);
            }
        }
        $purchasingPackage->purchasingVariants()->sync($syncDatas);
    }

    /**
     * @param $tenantId
     * @param array $product
     * @return PurchasingVariant|null|mixed
     */
    protected function findPurchasingVariantPackage($tenantId, array $product)
    {
        $originalId = Arr::get($product, 'url');

        if ($originalId) {
            return PurchasingVariant::query()->where([
                'tenant_id' => $tenantId,
                'variant_id' => md5($originalId)
            ])->first();
        }

        return null;
    }

    /**
     * @param PurchasingOrder $purchasingOrder
     * @param $product
     *
     * @return PurchasingVariant|null
     */
    protected function findPurchasingVariantOrder(PurchasingOrder $purchasingOrder, $product)
    {
        $purchasingOrderItem = $purchasingOrder->purchasingOrderItems->where('item_code', Arr::get($product, 'code_item'))->first();
        if ($purchasingOrderItem instanceof PurchasingOrderItem) {
            return $purchasingOrderItem->purchasingVariant;
        }
        return null;
    }

    /**
     * @param int $tenant_id
     * @param $product
     */
    protected function createPurchasingVariantPackage(int $tenant_id, $product)
    {
        $marketplace = Arr::get($this->payload, 'marketplace');
        $originalId  = Arr::get($product, 'url');
        PurchasingVariant::create(
            [
                'tenant_id' => $tenant_id,
                'marketplace' => $marketplace,
                'variant_id' => md5($originalId),
                'sku_id' => 0,
                'code' => Arr::get($product, 'code_item'),
                'name' => Arr::get($product, 'name_vi'),
                'image' => Arr::get($product, 'variantImage'),
                'properties' => Arr::get($product, 'variantProperties'),
                'product_url' => Arr::get($product, 'url'),
                'product_image' => Arr::get($product, 'productImage'),
                'supplier_code' => Arr::get($this->payload, 'merchantCode'),
                'supplier_name' => Arr::get($this->payload, 'merchantName'),
                'supplier_url' => Arr::get($this->payload, 'merchantUrl'),
                'spec_id' => Arr::get($product, 'specId'),
            ]
        );
    }

}
