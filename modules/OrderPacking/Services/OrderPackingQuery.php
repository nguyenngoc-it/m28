<?php

namespace Modules\OrderPacking\Services;

use Carbon\Carbon;
use Gobiz\ModelQuery\ModelQuery;
use Gobiz\ModelQuery\ModelQueryFactory;
use Illuminate\Database\Eloquent\Builder;
use Modules\FreightBill\Models\FreightBill;
use Modules\Order\Models\Order;
use Modules\Order\Services\StatusOrder;
use Modules\OrderPacking\Models\OrderPacking;

class OrderPackingQuery extends ModelQueryFactory
{
    protected $joins = [
        'orders' => ['order_packings.order_id', '=', 'orders.id'],
        'order_packing_items' => ['order_packings.id', '=', 'order_packing_items.order_packing_id'],
        'freight_bills' => ['order_packings.id', '=', 'freight_bills.order_packing_id'],
    ];

    /**
     * Khởi tạo model
     */
    protected function newModel()
    {
        return new OrderPacking();
    }

    /**
     *
     * @param ModelQuery $query
     * @param $orderCode
     */
    protected function applyOrderCodeFilter(ModelQuery $query, $orderCode)
    {
        $codes = explode(" ", $orderCode);
        $codes = array_map(function ($value) {
            return trim($value);
        }, $codes);
        $codes = array_unique($codes);

        $query->join('orders')->where(function($query) use($codes) {
            return $query->whereIn('orders.code', $codes)
                         ->orWhereIn('orders.ref_code', $codes);
        });
    }

    /**
     * @param ModelQuery $query
     * @param $skuId
     */
    protected function applySkuIdFilter(ModelQuery $query, $skuId)
    {
        $query->join('order_packing_items');
        if (is_array($skuId)) {
            $query->whereIn('order_packing_items.sku_id', $skuId);
        } else {
            $query->where('order_packing_items.sku_id', $skuId);
        }

        $query->groupBy('order_packings.id');
    }


    /**
     * @param ModelQuery $query
     * @param boolean $noWarehouseArea
     */
    protected function applyNoWarehouseAreaFilter(ModelQuery $query, $noWarehouseArea)
    {
        if ($noWarehouseArea) {
            $query->leftJoin('order_stocks', 'order_packings.order_id', '=', 'order_stocks.order_id');
            $query->whereNull('order_stocks.id');
        }
    }

    /**
     * Filter theo trạng thái
     * @param ModelQuery $query
     * @param $status
     */
    protected function applyStatusFilter(ModelQuery $query, $status)
    {
        if (is_array($status)) {
            $query->getQuery()->whereIn('order_packings.status', $status);
        } else {
            $query->where('order_packings.status', $status);
        }
    }


    /**
     * Filter theo kho
     * @param ModelQuery $query
     * @param $warehouseId
     */
    protected function applyWarehouseIdsFilter(ModelQuery $query, $warehouseId)
    {
        if (is_array($warehouseId)) {
            $query->getQuery()->whereIn('order_packings.warehouse_id', $warehouseId);
        } else {
            $query->where('order_packings.warehouse_id', $warehouseId);
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
        $this->applyFilterTimeRange($query, 'order_packings.created_at', $input);
    }

    /**
     * Filter theo    thoi gian du kien giao
     *
     * @param ModelQuery $query
     * @param array $input
     */
    protected function applyIntendedDeliveryAtFilter(ModelQuery $query, array $input)
    {
        $this->applyFilterTimeRange($query, 'order_packings.intended_delivery_at', $input);
    }

    /**
     * Filter theo rủi ro giao hàng trễ (là những đơn chưa giao mà có hạn giao hàng là ngày hôm nay hoặc quá hạn)
     *
     * @param ModelQuery $query
     */
    protected function applyLateDeliveryRiskFilter(ModelQuery $query)
    {
        $query->join('orders')
            ->whereIn('orders.status', StatusOrder::getBeforeStatus(Order::STATUS_DELIVERING))
            ->where('orders.intended_delivery_at', '<', Carbon::tomorrow()->format('Y-m-d'));
    }

    /**
     * Filter theo ten nguoi nhan
     *
     * @param ModelQuery $query
     * @param string $receiver_name
     */
    protected function applyReceiverNameFilter(ModelQuery $query, $receiver_name)
    {
        if ($receiver_name) {
            $query->where('order_packings.receiver_name', 'like', '%' . trim($receiver_name) . '%');
        }
    }

    /**
     * Filter theo sdt nguoi nhan
     *
     * @param ModelQuery $query
     * @param string $receiver_phone
     */
    protected function applyReceiverPhoneFilter(ModelQuery $query, $receiver_phone)
    {
        if ($receiver_phone) {
            $query->where('order_packings.receiver_phone', 'like', '%' . trim($receiver_phone) . '%');
        }
    }

    /**
     * Filter theo danh sách ids được chọn
     * @param ModelQuery $query
     * @param $ids
     */
    protected function applyIdsFilter(ModelQuery $query, $ids)
    {
        if (!empty($ids)) {
            $query->getQuery()->whereIn('order_packings.id', (array)$ids);
        }
    }

    /**
     * @param ModelQuery $query
     * @param $ids
     */
    protected function applyIgnoreIdsFilter(ModelQuery $query, $ids)
    {
        if (!empty($ids)) {
            $query->getQuery()->whereNotIn('order_packings.id', (array)$ids);
        }
    }

    /**
     * @param ModelQuery $query
     * @param $freightBillCode
     */
    protected function applyFreightBillFilter(ModelQuery $query, $freightBillCode)
    {
        $query->join('freight_bills');

        if(is_array($freightBillCode)) {
            $query->whereIn('freight_bills.freight_bill_code', $freightBillCode);
        } else {
            $freightBillCode = trim($freightBillCode);
            $freightBillCodeArray = explode(" ", $freightBillCode);
            if(count($freightBillCodeArray) > 1) {
                $query->whereIn('freight_bills.freight_bill_code', $freightBillCodeArray);
            } else {
                $query->where('freight_bills.freight_bill_code', $freightBillCode);
            }
        }

        $query->whereNotIn('freight_bills.status', [FreightBill::STATUS_CANCELLED])
            ->groupBy('order_packings.id');
    }

    /**
     * @param ModelQuery $query
     * @param $priority
     */
    protected function applyPriorityFilter(ModelQuery $query, $priority)
    {
        if($priority) {
            $query->where('order_packings.priority', 1);
        } else {
            $query->where('order_packings.priority', 0);
        }
    }


    /**
     * @param ModelQuery $query
     * @param $storeId
     */
    protected function applyStoreIdFilter(ModelQuery $query, $storeId)
    {
        $query->join('orders')
            ->where('orders.store_id', $storeId);
    }

    /**
     * @param ModelQuery $query
     * @param $inspected
     */
    protected function applyNoInspectedFilter(ModelQuery $query, $inspected)
    {
        $query->join('orders')
            ->where('orders.inspected', 0);
    }

    /**
     * @param ModelQuery $query
     * @param $inspected
     */
    protected function applyInspectedFilter(ModelQuery $query, $inspected)
    {
        $query->join('orders')
            ->where('orders.inspected', $inspected);
    }

    /**
     * @param ModelQuery $query
     * @param $status
     */
    protected function applyOrderStatusFilter(ModelQuery $query, $status)
    {
        $query->join('orders')
            ->where('orders.status', $status);
    }

    /**
     * @param ModelQuery $query
     * @param $isError
     */
    protected function applyErrorTrackingFilter(ModelQuery $query, $isError)
    {
        if ((bool)$isError) {
            $query->whereNotNull('error_type');
        }
    }

    /**
     * @param ModelQuery $query
     * @param $check
     */
    protected function applyNotAllowGrantPickerFilter(ModelQuery $query, $check)
    {
        if ($check) {
            $query->join('orders')
                ->where(function (Builder $que) {
                    $que->orWhere('order_packings.status', '<>', OrderPacking::STATUS_WAITING_PICKING)
                        ->orWhere('orders.inspected', false);
                });
        }
    }

    protected function applyPickingSessionIdFilter(ModelQuery $query, $id)
    {
        if (empty($id)) {
            $query->where(function (Builder $builder) {
                $builder->whereNull('order_packings.picking_session_id')->orWhere('order_packings.picking_session_id', 0);
            });
        } else {
            $query->where('order_packings.picking_session_id', (int)$id);
        }
    }

    /**
     * @param ModelQuery $query
     * @param $pickerId
     */
    protected function applyPickerIdFilter(ModelQuery $query, $pickerId)
    {
        $pickerId = intval($pickerId);
        if($pickerId) {
            $query->where('order_packings.picker_id', $pickerId);
        } else {
            $query->whereNull('order_packings.picker_id');
        }
    }
}
