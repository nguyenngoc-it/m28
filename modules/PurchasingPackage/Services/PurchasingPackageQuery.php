<?php

namespace Modules\PurchasingPackage\Services;

use Gobiz\ModelQuery\ModelQuery;
use Gobiz\ModelQuery\ModelQueryFactory;
use Modules\PurchasingPackage\Models\PurchasingPackage;

class PurchasingPackageQuery extends ModelQueryFactory
{
    protected $joins = [
        'purchasing_package_items' => ['purchasing_packages.id', '=', 'purchasing_package_items.purchasing_package_id'],
        'purchasing_orders' => ['purchasing_packages.order_id', '=', 'purchasing_orders.id'],
    ];

    /**
     * Khởi tạo model
     */
    protected function newModel()
    {
        return new PurchasingPackage();
    }

    /**
     * Filter theo danh sách ids được chọn
     * @param ModelQuery $query
     * @param $ids
     */
    protected function applyIdsFilter(ModelQuery $query, $ids)
    {
        if (!empty($ids)) {
            $query->whereIn('purchasing_packages.id', (array)$ids);
        }
    }

    /**
     * @param ModelQuery $query
     * @param $code
     */
    protected function applyCodeFilter(ModelQuery $query, $code)
    {
        if(is_array($code)) {
            $query->whereIn('purchasing_packages.code', $code);
        } else {
            $codeArr = explode(" ", $code);
            if(count($codeArr) > 1) {
                $query->whereIn('purchasing_packages.code', $codeArr);
            } else {
                $query->where('purchasing_packages.code', $code);
            }
        }
    }


    /**
     * Do cho phép lọc kiện nhập mà trong đó có ít nhất 1 sku thuộc supplier mình quản lý (đã tham khảo phương án của techlead)
     * Lọc theo SKU code
     * @param ModelQuery $query
     * @param $code
     */
    protected function applySkuCodeFilter(ModelQuery $query, $code)
    {
        $purchasingPackageQuery = PurchasingPackage::query()
            ->join('purchasing_package_items', 'purchasing_packages.id', '=', 'purchasing_package_items.purchasing_package_id')
            ->join('skus', 'skus.id', '=', 'purchasing_package_items.sku_id');
        if(is_array($code)) {
            $purchasingPackageQuery->whereIn('skus.code', $code);
        } else {
            $purchasingPackageQuery->where('skus.code', $code);
        }
        $purchasingPackageIds = $purchasingPackageQuery->pluck('purchasing_packages.id')->toArray();

        $query->getQuery()->whereIn('purchasing_packages.id', $purchasingPackageIds);
    }

    /**
     * @param ModelQuery $query
     * @param $skuId
     */
    protected function applySkuIdFilter(ModelQuery $query, $skuId)
    {
        $query->join('purchasing_package_items');
        if(is_array($skuId)) {
            $query->whereIn('purchasing_package_items.sku_id', $skuId);
        } else {
            $query->where('purchasing_package_items.sku_id', $skuId);
        }
        $query->groupBy('purchasing_packages.id');
    }

    /**
     * @param ModelQuery $query
     * @param array|integer $id
     */
    protected function applySupplierIdFilter(ModelQuery $query, $id)
    {
        $query->getQuery()
            ->join('purchasing_package_items', 'purchasing_packages.id', '=', 'purchasing_package_items.purchasing_package_id')
            ->join('skus', 'skus.id', '=', 'purchasing_package_items.sku_id');
        if(is_array($id)) {
            $query->whereIn('skus.supplier_id', $id);
        } else {
            $query->where('skus.supplier_id', $id);
        }
        $query->groupBy('purchasing_packages.id');
    }

    /**
     * Filter theo 	thoi gian tao
     *
     * @param ModelQuery $query
     * @param array $input
     */
    protected function applyCreatedAtFilter(ModelQuery $query, array $input)
    {
        $this->applyFilterTimeRange($query, 'purchasing_packages.created_at', $input);
    }

    /**
     * Filter theo 	thoi gian nhập
     *
     * @param ModelQuery $query
     * @param array $input
     */
    protected function applyImportedAtFilter(ModelQuery $query, array $input)
    {
        $this->applyFilterTimeRange($query, 'purchasing_packages.imported_at', $input);
    }


}
