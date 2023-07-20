<?php

namespace Modules\Service\Listeners;

use App\Base\QueueableListener;
use Modules\Service\Events\ServiceComboCreated;
use Modules\Service\Services\ServiceEvent;

class ServiceComboCreatedListener extends QueueableListener
{
    /**
     * @param ServiceComboCreated $event
     */
    public function handle(ServiceComboCreated $event)
    {
        $serviceCombo = $event->serviceCombo;
        $serviceCombo->logActivity(ServiceEvent::SERVICE_COMBO_CREATE, $event->creator);
    }
}
