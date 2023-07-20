<?php

namespace Modules\PurchasingOrder\Services;

use Gobiz\ModelQuery\ModelQuery;
use Gobiz\ModelQuery\ModelQueryFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Modules\PurchasingOrder\Models\PurchasingOrder;

class PurchasingOrderQuery extends ModelQueryFactory
{
    protected $joins = [
        'purchasing_order_items' => ['purchasing_orders.id', '=', 'purchasing_order_items.purchasing_order_id'],
        'purchasing_packages' => ['purchasing_orders.id', '=', 'purchasing_packages.purchasing_order_id'],
    ];

    /**
     * Khởi tạo model
     */
    protected function newModel()
    {
        return new PurchasingOrder();
    }

    /**
     * Filter theo danh sách ids được chọn
     * @param ModelQuery $query
     * @param $ids
     */
    protected function applyIdsFilter(ModelQuery $query, $ids)
    {
        if (!empty($ids)) {
            $query->whereIn('purchasing_orders.id', (array)$ids);
        }
    }

    /**
     * @param ModelQuery $query
     * @param $supplier
     */
    protected function applySupplierFilter(ModelQuery $query, $supplier)
    {
        $query->where(function (Builder $builder) use ($supplier) {
            $builder->where('purchasing_orders.supplier_code', $supplier)
                ->orWhere('purchasing_orders.supplier_name', $supplier);
        });
    }

    /**
     * @param ModelQuery $query
     * @param array $purchasingVariantIds
     */
    protected function applyPurchasingVariantIdsFilter(ModelQuery $query, array $purchasingVariantIds)
    {
        $query->join('purchasing_order_items')
            ->whereIn('purchasing_order_items.purchasing_variant_id', $purchasingVariantIds);
    }

    /**
     * @param ModelQuery $query
     * @param array $input
     */
    protected function applyTotalValueFilter(ModelQuery $query, array $input)
    {
        $this->applyFilterRange($query, 'purchasing_orders.total_value', isset($input['from']) ? $input['from'] : null, isset($input['to']) ? $input['to'] : null);
    }

    /**
     * @param ModelQuery $query
     * @param array $input
     */
    protected function applyOrderedAtFilter(ModelQuery $query, array $input)
    {
        $this->applyFilterTimeRange($query, 'purchasing_orders.ordered_at', $input);
    }

    /**
     * @param ModelQuery $query
     * @param bool $hasPackage
     */
    protected function applyHasPackageFilter(ModelQuery $query, bool $hasPackage)
    {
        if ($hasPackage) {
            $query->join('purchasing_packages')->groupBy('purchasing_orders.id');
        }
    }

    /**
     * @param ModelQuery $query
     * @param $warehouseId
     */
    protected function applyWarehouseIdFilter(ModelQuery $query, $warehouseId)
    {
        $query->where('purchasing_orders.warehouse_id', $warehouseId);
    }

    /**
     * @param ModelQuery $query
     * @param $value
     */
    protected function applyOnlyMerchantOwnerFilter(ModelQuery $query, $value)
    {
        if ($value) {
            $query->whereIn('merchant_id', Auth::user()->merchants->pluck('id')->all());
        }
    }
}
