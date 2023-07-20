<?php

namespace Modules\Service\Events;

use App\Base\Event;
use Modules\Service\Models\ServicePack;
use Modules\User\Models\User;

class ServicePackCreated extends Event
{
    /** @var ServicePack $servicePack */
    public $servicePack;
    /** @var User $creator */
    public $creator;

    /**
     * ProductCreated constructor.
     * @param ServicePack $servicePack
     * @param User $creator
     */
    public function __construct(ServicePack $servicePack, User $creator)
    {
        $this->servicePack = $servicePack;
        $this->creator     = $creator;
    }
}
