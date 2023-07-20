<?php

namespace Modules\Document\Events;

use App\Base\Event;
use Modules\Document\Models\Document;

class DocumentPackingCreated extends Event
{
    /**
     * @var Document
     */
    public $document;

    /**
     * OrderCreated constructor
     *
     * @param Document $document
     */
    public function __construct(Document $document)
    {
        $this->document = $document;
    }
}
