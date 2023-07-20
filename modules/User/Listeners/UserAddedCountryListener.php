<?php

namespace Modules\User\Listeners;

use App\Base\QueueableListener;
use Illuminate\Support\Facades\DB;
use Modules\Service;
use Modules\User\Events\UserAddedCountry;
use Modules\User\Services\UserEvent;

class UserAddedCountryListener extends QueueableListener
{
    /**
     * @param UserAddedCountry $event
     */
    public function handle(UserAddedCountry $event)
    {
        $user            = $event->user;
        $creator         = $event->creator;
        $addedCountryIds = $event->addedCountryIds;

        DB::transaction(function () use ($user, $creator, $addedCountryIds) {
            Service::user()->addedWarehouseByCountries($user, $addedCountryIds);
            Service::user()->addedSellerByCountries($user, $addedCountryIds);
        });

        /**
         * Lưu log
         */
        $user->logActivity(UserEvent::UPDATE_COUNTRY, $creator, ['added_country_ids' => $addedCountryIds]);
    }
}
