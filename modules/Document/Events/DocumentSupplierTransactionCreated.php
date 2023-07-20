<?php

namespace Modules\Document\Events;

use App\Base\Event;
use Modules\Document\Models\Document;
use Modules\User\Models\User;

class DocumentSupplierTransactionCreated extends Event
{
    /**
     * @var Document
     */
    public $document;

    /**
     * @var User
     */
    public $creator;

    /**
     * OrderCreated constructor
     *
     * @param Document $document
     */
    public function __construct(Document $document, User $creator)
    {
        $this->document = $document;
        $this->creator  = $creator;
    }
}
