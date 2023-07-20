<?php

namespace Modules\Service\Events;

use App\Base\Event;
use Modules\Service\Models\ServicePack;
use Modules\User\Models\User;

class ServicePackSellerRemoved extends Event
{
    /** @var ServicePack $servicePack */
    public $servicePack;
    /** @var User $creator */
    public $creator;
    /** @var int $sellerIds */
    public $sellerId;

    /**
     * ProductCreated constructor.
     * @param $servicePackId
     * @param int $sellerId
     * @param User $creator
     */
    public function __construct($servicePackId, int $sellerId, User $creator)
    {
        $this->servicePack = ServicePack::find($servicePackId);
        $this->creator     = $creator;
        $this->sellerId    = $sellerId;
    }
}
