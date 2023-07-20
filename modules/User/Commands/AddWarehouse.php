<?php

namespace Modules\User\Commands;

use Modules\User\Models\User;
use Modules\User\Models\UserWarehouse;
use Modules\User\Services\UserEvent;

class AddWarehouse
{
    /**
     * @var array
     */
    protected $warehouses;

    /**
     * @var User
     */
    protected $user;

    /**
     * @var User
     */
    protected $creator;

    /**
     * AddWarehouse constructor.
     * @param User $user
     * @param User $creator
     * @param array $warehouses
     */
    public function __construct(User $user, User $creator, array $warehouses = [])
    {
        $this->user = $user;
        $this->creator = $creator;
        $this->warehouses = $warehouses;
    }


    /**
     * @return User
     */
    public function handle()
    {
        $warehousesOld = $this->user->warehouses()->pluck('name')->toArray();

        $warehouseIds = [];
        $warehousesNew = [];
        foreach ($this->warehouses as $warehouse) {
            $warehouseIds[] = $warehouse->id;
            $warehousesNew[] = $warehouse->name;
        }

        $this->user->warehouses()->sync($warehouseIds);

        $this->user->logActivity(UserEvent::ADD_WAREHOUSE, $this->creator, compact('warehousesNew', 'warehousesOld'));

        return $this->user;
    }
}