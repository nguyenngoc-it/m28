<?php

namespace Modules\Store\Transformers;

use App\Base\Transformer;
use Modules\Store\Models\Store;

class StoreListItemTransformer extends Transformer
{

    /**
     * @param Store $store
     * @return array|mixed
     */
    public function transform($store)
    {
        $warehouse = $store->warehouse;
        $shopName = $store->name;
        $shopName = $shopName ? $shopName : $store->marketplace_store_id;
        $store->setAttribute('shop_name', $shopName);

        $settings = [
            'sync_stock' => $store->getSetting('sync_stock'),
            'quantity_type' => $store->getSetting('quantity_type'),
        ];
        return compact('store', 'warehouse', 'settings');
    }
}
