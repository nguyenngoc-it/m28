<?php

namespace Modules\Document\Listeners;

use App\Base\QueueableListener;
use Modules\Document\Events\DocumentSupplierTransactionCreated;
use Modules\Document\Models\Document;
use Modules\Document\Services\DocumentEvent;
use Modules\User\Models\User;

class DocumentSupplierTransactionCreatedListener extends QueueableListener
{
    /**
     * @var Document
     */
    protected $document;

    /**
     * @var User
     */
    protected $creator;

    /**
     * @param DocumentSupplierTransactionCreated $event
     */
    public function handle(DocumentSupplierTransactionCreated $event)
    {
        $this->document                    = $event->document;
        $this->creator                     = $event->creator;

        $this->document->logActivity(DocumentEvent::CREATE, $this->creator, [
            'document' => $this->document->attributesToArray(),
            'document_supplier_transaction' => $this->documentSupplierTransaction->attributesToArray()
        ]);
    }
}
