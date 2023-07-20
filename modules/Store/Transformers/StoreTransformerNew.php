<?php

namespace Modules\Store\Transformers;

use League\Fractal\TransformerAbstract;
use Modules\Merchant\ExternalTransformers\MerchantTransformerNew;
use Modules\Merchant\Models\Merchant;
use Modules\Store\Models\Store;
use Modules\Warehouse\Models\Warehouse;
use Modules\Warehouse\Transformers\WarehouseTransformerNew;

class StoreTransformerNew extends TransformerAbstract
{
    public function __construct()
    {
        $this->setAvailableIncludes(['warehouse','merchant']);
    }

    public function transform(Store $store)
    {
        $accessTokenExpiredAt = $store->getSetting('access_token_expired_at');
        return [
            'id' => (int)$store->id,
            'tenant_id' => $store->tenant_id,
            'merchant_id' => $store->merchant_id,
            'marketplace_code' => $store->marketplace_code,
            'marketplace_store_id' => $store->marketplace_store_id,
            'name' => $store->name,
            'description' => $store->description,
            'product_sync' => $store->product_sync,
            'order_sync' => $store->order_sync,
            'status' => $store->status,
            'created_at' => $store->created_at,
            'updated_at' => $store->updated_at,
            'access_token_expired_at' => $accessTokenExpiredAt
        ];
    }

    public function includeWarehouse(Store $store)
    {
        $warehouse = $store->warehouse;
        if (!$warehouse){
            $warehouse = new Warehouse();
        }
        return $this->item($warehouse, new WarehouseTransformerNew);
    }

    public function includeMerchant(Store $store)
    {
        return $store->merchant
            ? $this->item($store->merchant, new MerchantTransformerNew)
            : $this->null();
    }
}
