<?php

namespace Modules\PurchasingPackage\Events;

use App\Base\Event;
use Modules\PurchasingPackage\Models\PurchasingPackage;
use Modules\User\Models\User;

class PurchasingPackageStatusChangedEvent extends Event
{
    /**
     * @var PurchasingPackage
     */
    public $purchasingPackage;

    /**
     * @var string
     */
    public $fromStatus;

    /**
     * @var User
     */
    public $creator;

    /**
     * @var array
     */
    public $payload;

    /**
     * PurchasingPackageStatusChangedEvent constructor.
     * @param PurchasingPackage $purchasingPackage
     * @param $fromStatus
     * @param User $creator
     * @param array $payload
     */
    public function __construct(PurchasingPackage $purchasingPackage, $fromStatus, User $creator, $payload = [])
    {
        $this->purchasingPackage = $purchasingPackage;
        $this->fromStatus = $fromStatus;
        $this->creator = $creator;
        $this->payload = $payload;
    }
}
