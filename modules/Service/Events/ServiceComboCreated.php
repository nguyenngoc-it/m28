<?php

namespace Modules\Service\Events;

use App\Base\Event;
use Modules\Service\Models\ServiceCombo;
use Modules\User\Models\User;

class ServiceComboCreated extends Event
{
    /** @var ServiceCombo $serviceCombo */
    public $serviceCombo;
    /** @var User $creator */
    public $creator;

    /**
     * ProductCreated constructor.
     * @param ServiceCombo $serviceCombo
     * @param User $creator
     */
    public function __construct(ServiceCombo $serviceCombo, User $creator)
    {
        $this->serviceCombo = $serviceCombo;
        $this->creator      = $creator;
    }
}
