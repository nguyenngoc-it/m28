<?php

namespace Modules\Store\Services;

use Gobiz\ModelQuery\ModelQuery;
use Modules\Store\Models\Store;
use Modules\Store\Models\StoreSku;

interface StoreServiceInterface
{
    /**
     * Tạo store query
     *
     * @param array $filter
     * @return ModelQuery
     */
    public function query(array $filter);

    /**
     * Tạo store sku query
     *
     * @param array $filter
     * @return ModelQuery
     */
    public function storeSkuQuery(array $filter);

    /**
     * @param Store $store
     * @param $skuIdOrigin
     * @param $codeOrigin
     * @return \Illuminate\Database\Eloquent\Builder|\Illuminate\Database\Eloquent\Model|StoreSku|object|null
     */
    public function getStoreSkuOnSell(Store $store, $skuIdOrigin, $codeOrigin);
}
