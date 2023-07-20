<?php

namespace Modules\Warehouse\Commands;

use Modules\Warehouse\Models\Warehouse;
use Modules\Tenant\Models\Tenant;
use Modules\User\Models\User;
use Modules\Warehouse\Services\WarehouseEvent;

class UpdateWarehouse
{
    /**
     * @var array
     */
    protected $input;

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
     * UpdateWarehouse constructor.
     * @param Warehouse $warehouse
     * @param User $creator
     * @param array $input
     */
    public function __construct(Warehouse $warehouse, User $creator, array $input)
    {
        $this->warehouse = $warehouse;
        $this->creator = $creator;
        $this->input = $input;
    }


    /**
     * @return Warehouse
     */
    public function handle()
    {
        if (isset($this->input['is_main']) && $this->input['is_main']) {
            Warehouse::query()->where('country_id', $this->input['country_id'])
                ->where('is_main',1)
                ->update(["is_main" => 0]);
        }
        $this->warehouse->update($this->input);

        $this->warehouse->logActivity(WarehouseEvent::UPDATE, $this->creator, $this->warehouse->getChanges());

        return $this->warehouse;
    }
}
