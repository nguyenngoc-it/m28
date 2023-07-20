<?php

namespace Modules\Document\Listeners;

use App\Base\QueueableListener;
use Modules\Document\Events\DocumentExportingExported;
use Modules\Document\Jobs\CalculateBalanceMerchantWhenConfirmDocumentJob;
use Modules\Document\Services\DocumentEvent;

class DocumentExportingExportedListener extends QueueableListener
{
    /**
     * @param DocumentExportingExported $event
     */
    public function handle(DocumentExportingExported $event)
    {
        $documentExporting = $event->document;
        $creator           = $event->creator;
        $actionTime        = $event->actionTime;

        $documentExporting->logActivity(DocumentEvent::EXPORT, $creator, [
            'document' => $documentExporting
        ], ['time' => $actionTime]);

        /**
         * Thu tiền giá vốn vận chuyển trên ví của seller
         */
        dispatch(new CalculateBalanceMerchantWhenConfirmDocumentJob($documentExporting->id));
    }
}
