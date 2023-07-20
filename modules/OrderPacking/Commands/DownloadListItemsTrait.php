<?php

namespace Modules\OrderPacking\Commands;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Modules\OrderPacking\Models\OrderPacking;
use Modules\OrderPacking\Models\OrderPackingItem;

trait DownloadListItemsTrait
{
    /**
     * @param Builder $orderPackingBuilder
     * @param $warehouseId
     * @return array
     */
    protected function makeTotalItemsDataSheet(Builder $orderPackingBuilder, $warehouseId)
    {
        return OrderPackingItem::query()->selectRaw('warehouse_areas.name as `' . trans('warehouse_area') . '`, skus.code as `' . trans('sku') . '`, skus.name as `' . trans('variant_name') . '`, SUM(quantity) as `' . trans('quantity') . '`')
            ->join('skus', 'order_packing_items.sku_id', 'skus.id')
            ->leftJoin('warehouse_areas', 'order_packing_items.warehouse_area_id', 'warehouse_areas.id')
            ->whereIn('order_packing_id', $orderPackingBuilder->select('order_packings.id'))
            ->where('order_packing_items.warehouse_id', $warehouseId)
            ->groupBy(['order_packing_items.sku_id', 'order_packing_items.warehouse_area_id'])
            ->orderBy('warehouse_areas.name')
            ->orderBy('skus.name')
            ->get()->all();
    }

    /**
     * @param Builder $orderPackingBuilder
     * @param $warehouseId
     * @param array $orderPackingIds
     * @param string $sortBy
     * @param string $sort
     * @return array
     */
    protected function makeOrderItemsDataSheet(Builder $orderPackingBuilder, $warehouseId, $orderPackingIds = [], $sortBy = 'id', $sort = 'desc')
    {
        $query = OrderPackingItem::query()->selectRaw('order_packing_items.order_packing_id, orders.code as `' . trans('order_code') . '`, 
        warehouse_areas.name as `' . trans('warehouse_area') . '`, skus.code as `' . trans('sku') . '`, 
        skus.name as `' . trans('variant_name') . '`, order_packing_items.quantity as `' . trans('quantity') . '`')
            ->join('orders', 'order_packing_items.order_id', 'orders.id')
            ->join('skus', 'order_packing_items.sku_id', 'skus.id')
            ->leftJoin('warehouse_areas', 'order_packing_items.warehouse_area_id', 'warehouse_areas.id')
            ->where('order_packing_items.warehouse_id', $warehouseId)
            ->with('orderPacking.freightBill');
        if ($orderPackingIds) {
            $query->whereIn('order_packing_id', $orderPackingIds)
                ->orderByRaw('FIELD(order_packing_id,' . implode(',', $orderPackingIds) . ')');
        } else {
            $query->whereIn('order_packing_id', $orderPackingBuilder->select('order_packings.id'))
                ->join('order_packings', 'order_packing_items.order_packing_id', 'order_packings.id')
                ->orderBy('order_packings.' . $sortBy, $sort);
        }
        $query->orderBy('skus.name');

        return $query->get()->map(function (Model $orderPackingItem) {
            $orderPacking = OrderPacking::find($orderPackingItem->order_packing_id);
            $orderPackingItem->offsetUnset('order_packing_id');
            if (empty($orderPacking)) {
                return array_merge($orderPackingItem->toArray(), [trans('tracking_number') => '', trans('multiple_skus') => '']);
            }
            $multipleSkus = '';
            if ($orderPacking->order->orderSkus->count() > 1) {
                $multipleSkus = 'x';
            }

            if ($orderPacking->freightBill) {
                return array_merge($orderPackingItem->toArray(), [trans('tracking_number') => $orderPacking->freightBill->freight_bill_code, trans('multiple_skus') => $multipleSkus]);
            }

            return array_merge($orderPackingItem->toArray(), [trans('tracking_number') => '', trans('multiple_skus') => $multipleSkus]);
        })->all();
    }
}
