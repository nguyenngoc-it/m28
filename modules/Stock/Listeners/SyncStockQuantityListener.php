<?php

namespace Modules\Stock\Listeners;

use App\Base\QueueableListener;
use Modules\Stock\Events\StockChanged;
use Modules\Stock\Jobs\CreateHistoryStockLogJob;
use Modules\Stock\Jobs\UpdateTemporaryStockJob;
use Modules\Stock\Jobs\SyncStockSkuToMarketplaceJob;

class SyncStockQuantityListener extends QueueableListener
{
    public function handle(StockChanged $event)
    {
        $event->stock->sku->syncStockQuantity();

        dispatch(new SyncStockSkuToMarketplaceJob($event->stock->sku_id));
        dispatch(new CreateHistoryStockLogJob($event->stock));
        dispatch(new UpdateTemporaryStockJob($event->stock));
    }
}
