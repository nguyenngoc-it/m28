<?php

namespace Modules\Warehouse\Commands;

use Illuminate\Support\Arr;
use Modules\Warehouse\Models\Warehouse;
use Modules\Tenant\Models\Tenant;
use Modules\User\Models\User;
use Modules\Warehouse\Services\WarehouseEvent;

class ChangeStateWarehouse
{
    /**
     * @var boolean
     */
    protected $status;

    /**
     * @var Tenant
     */
    protected $tenant;

    /**
     * @var User
     */
    protected $creator;

    /**
     * @var User
     */
    protected $warehouse;

    /**
     * ChangeStateWarehouse constructor.
     * @param Warehouse $warehouse
     * @param User $creator
     * @param $status
     */
    public function __construct(Warehouse $warehouse, User $creator, $status)
    {
        $this->warehouse = $warehouse;
        $this->creator = $creator;
        $this->status = $status;
    }


    /**
     * @return Warehouse
     */
    public function handle()
    {
        if($this->warehouse->status == $this->status) {
            return $this->warehouse;
        }
        $this->warehouse->status = $this->status;
        $this->warehouse->save();

        $this->warehouse->logActivity(WarehouseEvent::CHANGE_STATE, $this->creator, $this->warehouse->getChanges());

        return $this->warehouse;
    }
}