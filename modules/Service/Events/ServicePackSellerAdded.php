<?php

namespace Modules\Service\Events;

use App\Base\Event;
use Modules\Service\Models\ServicePack;
use Modules\User\Models\User;

class ServicePackSellerAdded extends Event
{
    /** @var ServicePack $servicePack */
    public $servicePack;
    /** @var User $creator */
    public $creator;
    /** @var array $sellerIds */
    public $sellerIds;

    /**
     * ProductCreated constructor.
     * @param $servicePackId
     * @param array $sellerIds
     * @param User $creator
     */
    public function __construct($servicePackId, array $sellerIds, User $creator)
    {
        $this->servicePack = ServicePack::find($servicePackId);
        $this->creator     = $creator;
        $this->sellerIds   = $sellerIds;
    }
}
