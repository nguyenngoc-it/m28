<?php

namespace Modules\Service\Listeners;

use App\Base\QueueableListener;
use Modules\Service\Events\ServicePackCreated;
use Modules\Service\Services\ServiceEvent;

class ServicePackCreatedListener extends QueueableListener
{
    /**
     * @param ServicePackCreated $event
     */
    public function handle(ServicePackCreated $event)
    {
        $servicePack = $event->servicePack;
        $servicePack->logActivity(ServiceEvent::SERVICE_PACK_CREATE, $event->creator);
    }
}
