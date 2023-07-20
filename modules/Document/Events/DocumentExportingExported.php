<?php

namespace Modules\Document\Events;

use App\Base\Event;
use Carbon\Carbon;
use Modules\Document\Models\Document;
use Modules\User\Models\User;

class DocumentExportingExported extends Event
{
    /** @var Document $document */
    public $document;
    /** @var User */
    public $creator;
    /** @var Carbon */
    public $actionTime;

    /**
     * OrderCreated constructor
     *
     * @param Document $document
     * @param User $user
     * @param Carbon $actionTime
     */
    public function __construct(Document $document, User $user, Carbon $actionTime)
    {
        $this->document   = $document;
        $this->creator    = $user;
        $this->actionTime = $actionTime;
    }
}
