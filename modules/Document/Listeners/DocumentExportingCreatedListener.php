<?php

namespace Modules\Document\Listeners;

use App\Base\QueueableListener;
use Modules\Document\Events\DocumentExportingCreated;

class DocumentExportingCreatedListener extends QueueableListener
{
    /**
     * @param DocumentExportingCreated $event
     */
    public function handle(DocumentExportingCreated $event)
    {
        $documentExporting = $event->document;
        $documentExporting->orders()->sync($documentExporting->orderExportings->pluck('order_id')->unique()->values()->all());
    }
}
