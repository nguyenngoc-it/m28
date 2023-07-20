<?php

namespace Modules\Document\Listeners;

use App\Base\QueueableListener;
use Modules\Document\Events\DocumentReturnGoodsCreated;
use Modules\Document\Models\DocumentOrder;
use Modules\Document\Models\ImportingBarcode;

class DocumentReturnGoodsCreatedListener extends QueueableListener
{
    /**
     * @param DocumentReturnGoodsCreated $event
     */
    public function handle(DocumentReturnGoodsCreated $event)
    {
        $documentReturnGoods = $event->document;
        /** @var ImportingBarcode $importingBarcode */
        foreach ($documentReturnGoods->importingBarcodes as $importingBarcode) {
            $orderId = $importingBarcode->freightBill->order_id;
            DocumentOrder::firstOrCreate(
                [
                    'document_id' => $documentReturnGoods->id,
                    'order_id' => $orderId
                ]
            );
        }
    }
}
