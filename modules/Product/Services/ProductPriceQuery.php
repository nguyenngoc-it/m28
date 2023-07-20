<?php

namespace Modules\Product\Services;

use Gobiz\ModelQuery\ModelQuery;
use Gobiz\ModelQuery\ModelQueryFactory;
use Modules\Product\Models\ProductPrice;

class ProductPriceQuery extends ModelQueryFactory
{
    protected $joins = [
        'products' => ['product_prices.product_id', '=', 'products.id'],
    ];

    /**
     * Khởi tạo model
     */
    protected function newModel()
    {
        return new ProductPrice();
    }
    
    /**
     * @param ModelQuery $query
     * @param $productId
     */
    protected function applyProductIdFilter(ModelQuery $query, $productId)
    {
        if (is_array($productId)) {
            $query->getQuery()->whereIn('product_prices.product_id', $productId);
        } else {
            $productId = trim($productId);
            $productId = is_numeric($productId) ? $productId : 0;
            $query->where('product_prices.product_id', trim($productId));
        }
    }


    /**
     * Filter theo    thoi gian tao
     *
     * @param ModelQuery $query
     * @param array $input
     */
    protected function applyCreatedAtFilter(ModelQuery $query, array $input)
    {
        $this->applyFilterTimeRange($query, 'product_prices.created_at', $input);
    }
}
