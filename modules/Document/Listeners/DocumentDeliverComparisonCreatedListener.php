<?php

namespace Modules\Document\Listeners;

use App\Base\QueueableListener;
use Modules\Document\Events\DocumentDeliverComparisonCreated;

class DocumentDeliverComparisonCreatedListener extends QueueableListener
{
    /**
     * @param DocumentDeliverComparisonCreated $event
     */
    public function handle(DocumentDeliverComparisonCreated $event)
    {
        $documentCodComparison = $event->document;
        $documentCodComparison->orders()->sync($documentCodComparison->documentDeliveryComparisons->pluck('order_id')->unique()->filter()->values()->all());
    }
}
