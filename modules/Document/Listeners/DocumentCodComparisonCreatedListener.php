<?php

namespace Modules\Document\Listeners;

use App\Base\QueueableListener;
use Modules\Document\Events\DocumentCodComparisonCreated;

class DocumentCodComparisonCreatedListener extends QueueableListener
{
    /**
     * @param DocumentCodComparisonCreated $event
     */
    public function handle(DocumentCodComparisonCreated $event)
    {
        $documentCodComparison = $event->document;
        $documentCodComparison->orders()->sync($documentCodComparison->documentFreightBillInventories->pluck('order_id')->unique()->values()->all());
    }
}
