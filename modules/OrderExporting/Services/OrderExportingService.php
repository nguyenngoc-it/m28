<?php

namespace Modules\OrderExporting\Services;

use Gobiz\ModelQuery\ModelQuery;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Arr;
use Modules\OrderExporting\Models\OrderExporting;
use Modules\OrderExporting\Models\OrderExportingItem;
use Modules\OrderPacking\Models\OrderPacking;
use Modules\Service;

class OrderExportingService implements OrderExportingServiceInterface
{

    /**
     * @param array $filter
     * @return LengthAwarePaginator|Builder|Builder[]|Collection
     */
    public function listing(array $filter)
    {
        $sortBy    = Arr::get($filter, 'sort_by', 'id');
        $sortByIds = Arr::get($filter, 'sort_by_ids', false);
        $sort      = Arr::get($filter, 'sort', 'desc');
        $page      = Arr::get($filter, 'page', config('paginate.page'));
        $perPage   = Arr::get($filter, 'per_page', config('paginate.per_page'));
        $paginate  = Arr::get($filter, 'paginate', true);
        $ids       = Arr::get($filter, 'ids', []);

        foreach (['sort', 'sort_by', 'page', 'per_page', 'sort_by_ids', 'paginate'] as $p) {
            if (isset($filter[$p])) {
                unset($filter[$p]);
            }
        }

        $query = Service::orderExporting()->query($filter)->getQuery();
        $query->with(['order', 'freightBill', 'orderExportingItems', 'shippingPartner']);
        if ($sortByIds) {
            $query->orderByRaw('FIELD(id, ' . implode(',', $ids) . ')');
        } else {
            $query->orderBy('order_exportings' . '.' . $sortBy, $sort);
        }

        if (!$paginate) {
            return $query->get();
        }

        return $query->paginate($perPage, ['order_exportings.*'], 'page', $page);
    }

    /**
     * @param array $filter
     * @return ModelQuery
     */
    public function query(array $filter)
    {
        return (new OrderExportingQuery())->query($filter);
    }

    /**
     * tạo các OrderExportingItems
     *
     * @param OrderExporting $orderExporting
     * @param OrderPacking $orderPacking
     */
    public function updateOrderExportingItems(OrderExporting $orderExporting, OrderPacking $orderPacking)
    {
        $skus = [];
        foreach ($orderPacking->order->orderSkus as $orderSku) {
            if (!isset($skus[$orderSku->sku_id])) {
                $skus[$orderSku->sku_id] = [
                    'quantity' => $orderSku->quantity,
                ];
            } else {
                $skus[$orderSku->sku_id] = [
                    'quantity' => $skus[$orderSku->sku_id]['quantity'] + $orderSku->quantity,
                ];
            }
            $skus[$orderSku->sku_id]['price'] = $orderSku->price;
        }

        foreach ($skus as $skuId => $sku) {
            OrderExportingItem::updateOrCreate(
                [
                    'order_exporting_id' => $orderExporting->id,
                    'sku_id' => $skuId,
                ],
                [
                    'price' => $sku['price'],
                    'quantity' => $sku['quantity'],
                    'value' => $sku['price'] * $sku['quantity'],
                ]
            );
        }
    }

    /**
     * Cập nhật OrderExporting theo orderPacking
     *
     * @param OrderExporting $orderExporting
     * @return void
     */
    public function updateByOrderPacking(OrderExporting $orderExporting)
    {
        $orderPacking = $orderExporting->orderPacking;
        OrderExporting::updateOrCreate(
            [
                'order_id' => $orderPacking->order_id,
                'order_packing_id' => $orderPacking->id,
            ],
            [
                'freight_bill_id' => $orderPacking->freight_bill_id,
                'shipping_partner_id' => $orderPacking->shipping_partner_id,
                'warehouse_id' => $orderPacking->warehouse->id,
                'total_quantity' => $orderPacking->total_quantity,
                'total_value' => $orderPacking->total_values,
            ]
        );
    }
}
