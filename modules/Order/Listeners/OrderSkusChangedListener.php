<?php

namespace Modules\Order\Listeners;

use App\Base\QueueableListener;
use Modules\Order\Events\OrderSkusChanged;
use Modules\Order\Services\OrderEvent;
use Modules\Product\Models\Sku;

class OrderSkusChangedListener extends QueueableListener
{
    /**
     * @param OrderSkusChanged $event
     */
    public function handle(OrderSkusChanged $event)
    {
        $order    = $event->order;
        $creator  = $event->creator;
        $syncSkus = $this->detailSyncSkus($event->syncSkus);
        /**
         * Lưu log thay đổi thông tin skus
         */
        $order->logActivity(OrderEvent::UPDATE_SKUS, $creator, $syncSkus, [
            'time' => $event->order->updated_at,
        ]);
    }

    /**
     * @param array $syncSkus [id => [quantity, discount_amount ...]]
     * @return array
     */
    protected function detailSyncSkus(array $syncSkus): array
    {
        $listSkus = Sku::query()->whereIn('id', array_keys($syncSkus))->pluck('code', 'id')->toArray();
        foreach ($syncSkus as $skuId => &$syncSku) {
            $syncSku['sku_id']   = $skuId;
            $syncSku['sku_code'] = !empty($listSkus[$skuId]) ? $listSkus[$skuId] : 'unknown';
        }
        return array_values($syncSkus);
    }
}
