<?php

namespace Modules\Product\Services;

use Gobiz\ModelQuery\ModelQuery;
use Gobiz\ModelQuery\ModelQueryFactory;
use Modules\Product\Models\Product;

class ProductQuery extends ModelQueryFactory
{
    protected $joins = [
        'product_merchants' => ['products.id', '=', 'product_merchants.product_id'],
    ];

    /**
     * Khởi tạo model
     */
    protected function newModel()
    {
        return new Product();
    }

    /**
     * @param ModelQuery $query
     * @param array $ids
     */
    protected function applyIdsFilter(ModelQuery $query, array $ids)
    {
        $query->whereIn('products.id', $ids);
    }

    /**
     * @param ModelQuery $query
     * @param $status
     */
    protected function applyStatusFilter(ModelQuery $query, $status)
    {
        if(is_array($status)) {
            $query->whereIn('products.status', $status);
        } else {
            $query->where('products.status', $status);
        }

    }


    /**
     * @param ModelQuery $query
     * @param $name
     */
    protected function applyNameFilter(ModelQuery $query, $name)
    {
        $query->where('products.name', 'LIKE', '%'.trim($name).'%');
    }

    /**
     * @param ModelQuery $query
     * @param $merchant_id
     */
    protected function applyMerchantIdFilter(ModelQuery $query, $merchant_id)
    {
        $query->join('product_merchants');
        if(is_array($merchant_id)) {
            $query->whereIn('product_merchants.merchant_id', $merchant_id);
        } else {
            $query->where('product_merchants.merchant_id', $merchant_id);
        }

        $query->groupBy('products.id');
    }

    /**
     * @param ModelQuery $query
     * @param $merchantIds
     */
    public function applyMerchantIdsFilter(ModelQuery $query, array $merchantIds)
    {
        $query->join('product_merchants');
        $query->whereIn('product_merchants.merchant_id', $merchantIds);

        $query->groupBy('products.id');
    }

    /**
     * @param ModelQuery $query
     * @param $id
     */
    protected function applySupplierIdFilter(ModelQuery $query, $id)
    {
        if(is_array($id)) {
            $query->whereIn('products.supplier_id', $id);
        } else {
            $query->where('products.supplier_id', $id);
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
        $this->applyFilterTimeRange($query, 'products.created_at', $input);
    }
}
