<?php

namespace Modules\PurchasingOrder\Events\Subscribers;

use Carbon\Carbon;
use Exception;
use Gobiz\Support\Helper;
use Illuminate\Support\Arr;
use Modules\PurchasingOrder\Models\PurchasingOrder;
use Modules\PurchasingOrder\Models\PurchasingVariant;
use Modules\WarehouseStock\Jobs\CalculateWarehouseStockJob;

class OrderPublicEventSubscriberM2 extends OrderPublicEventSubscriberBase
{
    /** @var array $transformedDataOrder */
    protected $transformedDataOrder = [];
    protected $receiverLocationCodes = [];
    protected $packages = [];
    protected $products = [];

    /**
     * @return void
     * @throws Exception
     */
    public function handle()
    {
        if ($this->isValidData()) {
            $this->products = Arr::get($this->payload, 'products');
            $m2OrderCode    = Arr::get($this->payload, 'code');
            $m28Orders      = PurchasingOrder::query()->where([
                'code' => $m2OrderCode
            ])->whereIn('purchasing_account_id', $this->purchasingAccounts->pluck('id'))->get();
            $this->detectReceiverLocationCodes();
            $this->transformDataOrder();

            if ($m28Orders->count()) {
                /** @var PurchasingOrder $m28Order */
                foreach ($m28Orders as $m28Order) {
                    PurchasingOrder::find($m28Order->id)->update($this->transformedDataOrder);
                    $this->updateOrderItems($m28Order);
                }
            } else {
                foreach ($this->purchasingAccounts as $purchasingAccount) {
                    $purchasingOrder                        = new PurchasingOrder($this->transformedDataOrder);
                    $purchasingOrder->tenant_id             = $purchasingAccount->tenant_id;
                    $purchasingOrder->purchasing_service_id = $purchasingAccount->purchasing_service_id;
                    $purchasingOrder->purchasing_account_id = $purchasingAccount->id;
                    $purchasingOrder->merchant_id           = $purchasingAccount->merchant_id;
                    $purchasingOrder->m1_order_url          = $purchasingOrder->purchasingService->base_uri . '#/orders/' . Arr::get($this->payload, 'code');
                    $purchasingOrder->save();
                    $this->updateOrderItems($purchasingOrder);
                }
            }
        }
    }

    protected function detectReceiverLocationCodes()
    {
        if ($this->receiverLocations['type'] == 'WARD') {
            $this->receiverLocationCodes['ward_code']     = $this->receiverLocations['code'];
            $this->receiverLocationCodes['district_code'] = Arr::get($this->receiverLocations, 'parent.code');
            $this->receiverLocationCodes['city_code']     = Arr::get($this->receiverLocations, 'parent.parent.code');
            $this->receiverLocationCodes['country_code']  = Arr::get($this->receiverLocations, 'parent.parent.parent.code');
        } else {
            $this->receiverLocationCodes['district_code'] = $this->receiverLocations['code'];
            $this->receiverLocationCodes['city_code']     = Arr::get($this->receiverLocations, 'parent.code');
            $this->receiverLocationCodes['country_code']  = Arr::get($this->receiverLocations, 'parent.parent.code');
        }
    }

    /**
     * Convert dữ liệu từ M2 de update order
     *
     * @return void
     * @throws Exception
     */
    protected function transformDataOrder()
    {
        $orderedAt                  = Arr::get($this->payload, 'createdAt');
        $orderedAt                  = Carbon::createFromTimestamp(strtotime($orderedAt));
        $m2Status                   = Arr::get($this->payload, 'status');
        $this->transformedDataOrder = [
            'code' => Arr::get($this->payload, 'code'),
            'status' => Arr::get(PurchasingOrder::$transformStatusM1, $m2Status, $m2Status),
            'marketplace' => Arr::get($this->payload, 'marketplace'),
            'image' => Arr::get($this->payload, 'image'),
            'supplier_code' => Arr::get($this->payload, 'merchantCode'),
            'supplier_name' => Arr::get($this->payload, 'merchantName'),
            'supplier_url' => Arr::get($this->payload, 'merchantUrl'),
            'customer_username' => Arr::get($this->payload, 'customer.username'),
            'customer_name' => Arr::get($this->payload, 'customer.fullname'),
            'customer_phone' => Arr::get($this->payload, 'customer.phone'),
            'receiver_name' => Arr::get($this->payload, 'customer.fullname'),
            'receiver_phone' => Arr::get($this->payload, 'customer.phone'),
            'receiver_address' => Arr::get($this->payload, 'address.detail'),
            'receiver_country_code' => Arr::get($this->receiverLocationCodes, 'country_code'),
            'receiver_city_code' => Arr::get($this->receiverLocationCodes, 'city_code'),
            'receiver_district_code' => Arr::get($this->receiverLocationCodes, 'district_code'),
            'receiver_ward_code' => Arr::get($this->receiverLocationCodes, 'ward_code'),
            'receiver_note' => Arr::get($this->payload, 'customer.staffNote'),
            'ordered_quantity' => Arr::get($this->payload, 'orderedQuantity'),
            'purchased_quantity' => Arr::get($this->payload, 'purchasedQuantity'),
            'received_quantity' => Arr::get($this->payload, 'receivedQuantity'),
            'currency' => Arr::get($this->payload, 'products.0.currency'),
            'exchange_rate' => Arr::get($this->payload, 'exchangeRate'),
            'original_total_value' => Arr::get($this->payload, 'totalValue'),
            'total_value' => Arr::get($this->payload, 'exchangedTotalValue'),
            'total_fee' => Arr::get($this->payload, 'totalFee'),
            'grand_total' => Arr::get($this->payload, 'grandTotal'),
            'total_unpaid' => Arr::get($this->payload, 'totalUnpaid'),
            'total_paid' => Arr::get($this->payload, 'totalPaid'),
            'ordered_at' => $orderedAt,
        ];
    }

    /**
     * @param PurchasingOrder $purchasingOrder
     */
    protected function updateOrderItems(PurchasingOrder $purchasingOrder)
    {
        $syncDatas = [];
        $skuIds    = [];
        foreach ($this->products as $product) {
            /** @var PurchasingVariant $purchasingVariant */
            $purchasingVariant = $this->findPurchasingVariant($purchasingOrder->tenant_id, $product);
            if (!$purchasingVariant) {
                $purchasingVariant = $this->createPurchasingVariant($purchasingOrder->tenant_id, $product);
            } else {
                $purchasingVariant = $this->updatePurchasingVariant($purchasingVariant, $product);
            }
            $skuIds[$purchasingVariant->sku_id] = $purchasingVariant->sku_id;

            $syncDatas[$purchasingVariant->id]['purchasing_order_id']   = $purchasingOrder->id;
            $syncDatas[$purchasingVariant->id]['purchasing_variant_id'] = $purchasingVariant->id;
            $syncDatas[$purchasingVariant->id]['item_id']               = $purchasingVariant->variant_id;
            $syncDatas[$purchasingVariant->id]['item_code']             = $purchasingVariant->code;
            $syncDatas[$purchasingVariant->id]['item_name']             = $purchasingVariant->name;
            $syncDatas[$purchasingVariant->id]['item_translated_name']  = $purchasingVariant->translated_name;
            $syncDatas[$purchasingVariant->id]['original_price']        = Arr::get($product, 'salePrice');
            $syncDatas[$purchasingVariant->id]['price']                 = Arr::get($product, 'exchangedSalePrice');
            $syncDatas[$purchasingVariant->id]['ordered_quantity']      = Arr::get($product, 'quantity');
            $syncDatas[$purchasingVariant->id]['purchased_quantity']    = Arr::get($product, 'purchasedQuantity');
            $syncDatas[$purchasingVariant->id]['received_quantity']     = Arr::get($product, 'receivedQuantity');
            $syncDatas[$purchasingVariant->id]['product_url']           = Arr::get($product, 'productUrl');
            $syncDatas[$purchasingVariant->id]['product_image']         = Arr::get($product, 'productImage');
            $syncDatas[$purchasingVariant->id]['variant_image']         = Arr::get($product, 'variantImage');
            $syncDatas[$purchasingVariant->id]['variant_properties']    = json_encode($purchasingVariant->properties);
        }

        $purchasingOrder->purchasingVariants()->sync($syncDatas);
    }

    /**
     * @param $tenantId
     * @param array $product
     * @return PurchasingVariant|null|mixed
     */
    protected function findPurchasingVariant($tenantId, array $product)
    {
        $specId      = Arr::get($product, 'specId');
        $marketplace = Arr::get($this->payload, 'marketplace');
        $properties  = Arr::get($product, 'variantProperties');
        usort($properties, Helper::build_sorter('name'));
        $originalId = Arr::get($product, 'originalId');
        if ($specId) {
            return PurchasingVariant::query()->where([
                'tenant_id' => $tenantId,
                'spec_id' => $specId,
                'marketplace' => $marketplace
            ])->first();
        }

        if ($originalId && !empty($properties) && !empty($properties)) {
            return PurchasingVariant::query()->where([
                'tenant_id' => $tenantId,
                'marketplace' => $marketplace,
                'variant_id' => md5($originalId . collect($properties)->pluck('name')->toJson() . collect($properties)->pluck('value')->toJson())
            ])->first();
        }

        return null;
    }

    /**
     * @param int $tenant_id
     * @param $product
     * @return PurchasingVariant
     */
    protected function createPurchasingVariant(int $tenant_id, $product)
    {
        $marketplace = Arr::get($this->payload, 'marketplace');
        $properties  = Arr::get($product, 'variantProperties');
        usort($properties, Helper::build_sorter('name'));
        $originalId = Arr::get($product, 'originalId');
        return PurchasingVariant::create(
            [
                'tenant_id' => $tenant_id,
                'marketplace' => $marketplace,
                'variant_id' => md5($originalId . collect($properties)->pluck('name')->toJson() . collect($properties)->pluck('value')->toJson()),
                'sku_id' => 0,
                'code' => Arr::get($product, 'code'),
                'name' => Arr::get($product, 'originalName'),
                'translated_name' => Arr::get($product, 'name'),
                'image' => Arr::get($product, 'variantImage'),
                'properties' => Arr::get($product, 'variantProperties'),
                'product_url' => Arr::get($product, 'productUrl'),
                'product_image' => Arr::get($product, 'productImage'),
                'supplier_code' => Arr::get($this->payload, 'merchantCode'),
                'supplier_name' => Arr::get($this->payload, 'merchantName'),
                'supplier_url' => Arr::get($this->payload, 'merchantUrl'),
                'spec_id' => Arr::get($product, 'specId'),
            ]
        );
    }

    /**
     * @param PurchasingVariant $purchasingVariant
     * @param $product
     * @return PurchasingVariant
     */
    protected function updatePurchasingVariant(PurchasingVariant $purchasingVariant, $product)
    {
        $purchasingVariant->code            = Arr::get($product, 'code');
        $purchasingVariant->name            = Arr::get($product, 'originalName');
        $purchasingVariant->translated_name = Arr::get($product, 'name');
        $purchasingVariant->image           = Arr::get($product, 'variantImage');
        $purchasingVariant->spec_id         = Arr::get($product, 'specId');
        $purchasingVariant->save();
        return $purchasingVariant;
    }

}
