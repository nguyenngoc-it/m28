<?php

namespace Modules\PurchasingPackage\Listeners;

use App\Base\QueueableListener;
use Modules\PurchasingPackage\Events\PurchasingPackageStatusChangedEvent;
use Modules\PurchasingPackage\Services\PurchasingPackageEvent;

class PurchasingPackageStatusChangedListener extends QueueableListener
{
    /**
     * @param PurchasingPackageStatusChangedEvent $event
     */
    public function handle(PurchasingPackageStatusChangedEvent $event)
    {
        $purchasingPackage = $event->purchasingPackage;

        /**
         * Lưu log tạo đơn
         */
        $payload = array_merge($event->payload, [
            'from' => $event->fromStatus,
            'to' => $purchasingPackage->status
        ]);
        $purchasingPackage->logActivity(PurchasingPackageEvent::CHANGE_STATUS, $event->creator, $payload);
    }
}
