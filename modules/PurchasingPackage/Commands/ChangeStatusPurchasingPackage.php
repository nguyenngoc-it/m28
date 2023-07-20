<?php

namespace Modules\PurchasingPackage\Commands;

use Modules\PurchasingPackage\Events\PurchasingPackageStatusChangedEvent;
use Modules\User\Models\User;
use Modules\PurchasingPackage\Models\PurchasingPackage;

class ChangeStatusPurchasingPackage
{
    /** @var User $user */
    protected $user;

    /** @var PurchasingPackage $purchasingPackage */
    protected $purchasingPackage;

    /**
     * @var string
     */
    protected $status;

    /**
     * @var array
     */
    protected $payload;

    /**
     * ChangeStatusPurchasingPackage constructor.
     * @param PurchasingPackage $purchasingPackage
     * @param $status
     * @param User $user
     * @param array $payload
     */
    public function __construct(PurchasingPackage $purchasingPackage, $status, User $user, $payload = [])
    {
        $this->user              = $user;
        $this->status            = $status;
        $this->payload           = $payload;
        $this->purchasingPackage = $purchasingPackage;
    }


    /**
     * @return PurchasingPackage
     */
    public function handle()
    {
        $purchasingPackageOld = clone  $this->purchasingPackage;
        $fromStatus = $purchasingPackageOld->status;
        if($this->purchasingPackage->status == $this->status) {
            return $this->purchasingPackage;
        }
        $this->purchasingPackage->status = $this->status;
        $this->purchasingPackage->save();

        (new PurchasingPackageStatusChangedEvent($this->purchasingPackage, $fromStatus, $this->user, $this->payload))->queue();

        return $this->purchasingPackage;
    }
}
