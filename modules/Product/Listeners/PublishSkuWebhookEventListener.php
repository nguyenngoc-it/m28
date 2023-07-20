<?php

namespace Modules\Product\Listeners;

use App\Base\QueueableListener;
use Modules\Product\Jobs\PublishSkuChangeStockWebhookEventJob;
use Modules\Stock\Events\StockChanged;

class PublishSkuWebhookEventListener extends QueueableListener
{
    public function handle($event)
    {
        if ($event instanceof StockChanged) {
            dispatch(new PublishSkuChangeStockWebhookEventJob($event->stock->sku_id));
            return;
        }
    }
}
