<?php

namespace Modules\Product\Listeners;

use Modules\Product\Events\BatchOfGoodCreated;
use Modules\Product\Jobs\SkuQueueableListener;
use Modules\Product\Services\SkuEvent;

class BatchOfGoodCreatedListener extends SkuQueueableListener
{
    public function handle(BatchOfGoodCreated $event)
    {
        $sku         = $event->sku;
        $batchOfGood = $event->batchOfGood;
        $user        = $event->user;
        $carbon      = $event->carbon;

        $sku->logActivity(SkuEvent::SKU_CREATE_BATCH_OF_GOODS, $user, [
            'batch_of_good' => $batchOfGood->attributesToArray()
        ], ['time' => $carbon]);
    }
}
