<?php

namespace Modules\Order\Listeners;

use App\Base\QueueableListener;
use Modules\Order\Events\OrderStockCreated;
use Modules\Order\Models\Order;
use Modules\Service;
use Modules\Stock\Jobs\UpdateTemporaryStockJob;

class OrderStockCreatedListener extends QueueableListener
{
    /**
     * @param OrderStockCreated $event
     */
    public function handle(OrderStockCreated $event)
    {
        $orderStock = $event->orderStock;
        $order      = Order::find($orderStock->order_id);

        /**
         * Nếu chọn được vị trí kho xuất cập nhật lại kho vận hành của YCĐH
         */
        if ($orderWarehouse = $order->getWarehouseStock()) {
            $orderPacking = $order->orderPacking;
            if ($orderPacking) {
                $orderPacking->warehouse_id = $orderWarehouse->id;
                $orderPacking->save();
            }
        }

        /**
         * Update lại snapshot cho những order_packing_items
         * (do nếu lúc bỏ chọn vị trí kho sẽ xóa orderStock nên xóa cả dữ liệu trong order_packing_items)
         */
        Service::order()->updateOrderPackingItems($orderStock);

        /**
         * Tính toán lại tồn tạm tính
         */
        foreach ($order->orderStocks as $orderStock) {
            dispatch(new UpdateTemporaryStockJob($orderStock->stock));
        }
    }
}
