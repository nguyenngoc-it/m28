<?php

namespace Modules\Merchant\Listeners;

use App\Base\QueueableListener;
use Illuminate\Support\Facades\DB;
use Modules\Merchant\Events\MerchantCreated;
use Modules\Merchant\Models\Merchant;
use Modules\Merchant\Services\MerchantEvent;
use Modules\Service;
use Modules\User\Models\User;

class MerchantCreatedListener extends QueueableListener
{
    /**
     * @param MerchantCreated $event
     */
    public function handle(MerchantCreated $event)
    {
        $creator  = $event->creator;
        $merchant = $event->merchant;

        DB::transaction(function () use ($merchant, $creator) {
            $this->addSellerForUser($merchant);
        });

        /**
         * Lưu log
         */
        $merchant->logActivity(MerchantEvent::CREATE, $creator ? $creator : Service::user()->getSystemUserDefault());
    }

    /**
     * @param Merchant $merchant
     */
    function addSellerForUser(Merchant $merchant): void
    {
        $countryMerchant = $merchant->location;
        if ($countryMerchant) {
            $countryUsers = $countryMerchant->users;
            if ($countryUsers->count()) {
                /** @var User $countryUser */
                foreach ($countryUsers as $countryUser) {
                    $countryUser->merchants()->sync([$merchant->id], false);
                }
            }
        }
    }
}
