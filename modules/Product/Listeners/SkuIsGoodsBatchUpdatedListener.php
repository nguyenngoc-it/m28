<?php

namespace Modules\Product\Listeners;

use Modules\Product\Events\SkuIsGoodsBatchUpdated;
use Modules\Product\Jobs\SkuQueueableListener;
use Modules\Product\Services\SkuEvent;

class SkuIsGoodsBatchUpdatedListener extends SkuQueueableListener
{
    public function handle(SkuIsGoodsBatchUpdated $event)
    {
        $sku     = $event->sku;
        $payload = $event->payload;
        $user    = $event->user;
        $carbon  = $event->carbon;

        $sku->logActivity(SkuEvent::SKU_IS_BATCH_UPDATE, $user, $payload, ['time' => $carbon]);
    }
}
