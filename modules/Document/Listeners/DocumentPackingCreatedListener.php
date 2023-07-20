<?php

namespace Modules\Document\Listeners;

use App\Base\QueueableListener;
use Modules\Document\Events\DocumentPackingCreated;

class DocumentPackingCreatedListener extends QueueableListener
{
    /**
     * @param DocumentPackingCreated $event
     */
    public function handle(DocumentPackingCreated $event)
    {
        //todo
    }
}
