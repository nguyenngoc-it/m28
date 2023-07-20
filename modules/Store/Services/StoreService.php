<?php

namespace Modules\Store\Services;

use Gobiz\ModelQuery\ModelQuery;
use Modules\Product\Models\Sku;
use Modules\Store\Models\Store;
use Modules\Store\Models\StoreSku;

class StoreService implements StoreServiceInterface
{
    /**
     * Tạo store query
     *
     * @param array $filter
     * @return ModelQuery
     */
    public function query(array $filter)
    {
        return (new StoreQuery())->query($filter);
    }

    /**
     * Tạo store sku query
     *
     * @param array $filter
     * @return ModelQuery
     */
    public function storeSkuQuery(array $filter)
    {
        return (new StoreSkuQuery())->query($filter);
    }

    /**
     * @param Store $store
     * @param $skuIdOrigin
     * @param $codeOrigin
     * @return \Illuminate\Database\Eloquent\Builder|\Illuminate\Database\Eloquent\Model|StoreSku|object|null
     */
    public function getStoreSkuOnSell(Store $store, $skuIdOrigin, $codeOrigin)
    {
        return StoreSku::query()
            ->select(['store_skus.*'])
            ->join('skus', 'store_skus.sku_id', 'skus.id')
            ->leftJoin('product_merchants', 'product_merchants.product_id', 'skus.product_id')
            ->where(function($query) use ($store) {
                return $query->where('skus.merchant_id', $store->merchant_id)
                    ->orWhere('product_merchants.merchant_id', $store->merchant_id);
            })
            ->where('store_skus.store_id', $store->id)
            ->where('store_skus.sku_id_origin', $skuIdOrigin)
            ->where('store_skus.code', $codeOrigin)
            ->where('skus.status', Sku::STATUS_ON_SELL)
            ->first();
    }
}
