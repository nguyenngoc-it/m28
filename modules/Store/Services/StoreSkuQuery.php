<?php

namespace Modules\Store\Services;

use Gobiz\ModelQuery\ModelQuery;
use Gobiz\ModelQuery\ModelQueryFactory;
use Modules\Product\Models\Sku;
use Modules\Store\Models\StoreSku;

class StoreSkuQuery extends ModelQueryFactory
{
    protected $joins = [
        'skus' => ['store_skus.sku_id', '=', 'skus.id'],
    ];

    /**
     * Khởi tạo model
     */
    protected function newModel()
    {
        return new StoreSku();
    }

    /**
     * @param ModelQuery $query
     * @param $skuId
     */
    protected function applySkuIdFilter(ModelQuery $query, $skuId)
    {
        if(is_array($skuId)) {
            $query->whereIn('store_skus.sku_id', $skuId);
        } else {
            $query->where('store_skus.sku_id', $skuId);
        }
    }

    /**
     * Filter theo 	thoi gian tao
     *
     * @param ModelQuery $query
     * @param array $input
     */
    protected function applyCreatedAtFilter(ModelQuery $query, array $input)
    {
        $this->applyFilterTimeRange($query, 'skus.created_at', $input);
    }
}
