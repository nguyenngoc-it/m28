<?php

namespace Modules\Service\Listeners;

use App\Base\QueueableListener;
use Modules\Merchant\Models\Merchant;
use Modules\Service\Events\ServicePackSellerRemoved;
use Modules\Service\Services\ServiceEvent;

class ServicePackSellerRemovedListener extends QueueableListener
{
    /**
     * @param ServicePackSellerRemoved $event
     */
    public function handle(ServicePackSellerRemoved $event)
    {
        $servicePack = $event->servicePack->refresh();
        $sellerId    = $event->sellerId;
        $seller      = Merchant::query()->where('id', $sellerId)->first();
        $servicePack->logActivity(ServiceEvent::SERVICE_PACK_REMOVE_SELLER, $event->creator, [
            'seller' => $seller->only(['id','code','name','username'])
        ]);
    }
}
