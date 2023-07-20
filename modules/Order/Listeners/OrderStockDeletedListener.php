<?php

namespace Modules\Order\Listeners;

use App\Base\QueueableListener;
use Modules\Order\Events\OrderStockDeleted;
use Modules\Order\Services\OrderEvent;
use Modules\Service;
use Modules\Stock\Jobs\UpdateTemporaryStockJob;
use Modules\Stock\Models\Stock;

class OrderStockDeletedListener extends QueueableListener
{
    /**
     * @param OrderStockDeleted $event
     */
    public function handle(OrderStockDeleted $event)
    {
        $order      = $event->order;
        $user       = $event->user;
        $actionTime = $event->actionTime;
        $stockIds   = $event->stockIds;
        $stocks     = Stock::query()->whereIn('id', $stockIds)->get();

        $order->logActivity(OrderEvent::REMOVE_WAREHOUSE_AREA, $user, [], [
            'time' => $actionTime,
        ]);

        /**
         * Tính toán lại tồn tạm tính
         */
        foreach ($stocks as $stock) {
            dispatch(new UpdateTemporaryStockJob($stock));
        }

        /**
         * Nếu đơn có sku quản lý lô thì khôi phục lại orderSkus theo sku cha
         */
        Service::order()->convertChildrenToParentSku($order);
    }
}
