<?php

namespace Modules\Service\Events;

use App\Base\Event;
use Modules\Service\Models\ServicePack;
use Modules\User\Models\User;

class ServicePackPriceAdded extends Event
{
    /** @var ServicePack $servicePack */
    public $servicePack;
    /** @var User $creator */
    public $creator;
    /** @var array $servicePriceIds */
    public $servicePriceIds;

    /**
     * ProductCreated constructor.
     * @param $servicePackId
     * @param array $servicePriceIds
     * @param User $creator
     */
    public function __construct($servicePackId, array $servicePriceIds, User $creator)
    {
        $this->servicePack     = ServicePack::find($servicePackId);
        $this->creator         = $creator;
        $this->servicePriceIds = $servicePriceIds;
    }
}
