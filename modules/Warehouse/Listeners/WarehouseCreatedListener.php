<?php

namespace Modules\Warehouse\Listeners;

use App\Base\QueueableListener;
use Illuminate\Support\Facades\DB;
use Modules\User\Models\User;
use Modules\Warehouse\Events\WarehouseCreated;
use Modules\Warehouse\Models\Warehouse;
use Modules\Warehouse\Services\WarehouseEvent;

class WarehouseCreatedListener extends QueueableListener
{
    /**
     * @param WarehouseCreated $event
     */
    public function handle(WarehouseCreated $event)
    {
        $creator   = $event->creator;
        $warehouse = $event->warehouse;

        DB::transaction(function () use ($warehouse, $creator) {
            $this->addWarehouseForUser($warehouse);
        });

        /**
         * Lưu log
         */
        $warehouse->logActivity(WarehouseEvent::CREATE, $creator);
    }

    /**
     * @param Warehouse $warehouse
     */
    function addWarehouseForUser(Warehouse $warehouse): void
    {
        $countryMerchant = $warehouse->country;
        if ($countryMerchant) {
            $countryUsers = $countryMerchant->users;
            if ($countryUsers->count()) {
                /** @var User $countryUser */
                foreach ($countryUsers as $countryUser) {
                    $countryUser->warehouses()->sync([$warehouse->id], false);
                }
            }
        }
    }
}
