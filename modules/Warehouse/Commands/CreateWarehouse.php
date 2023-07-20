<?php

namespace Modules\Warehouse\Commands;

use Modules\Warehouse\Events\WarehouseCreated;
use Modules\Warehouse\Models\Warehouse;
use Modules\Tenant\Models\Tenant;
use Modules\User\Models\User;
use Modules\Warehouse\Models\WarehouseArea;

class CreateWarehouse
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
     * CreateWarehouse constructor.
     * @param User $creator
     * @param array $input
     */
    public function __construct(User $creator, array $input)
    {
        $this->creator = $creator;
        $this->input   = $input;
    }


    /**
     * @return Warehouse
     */
    public function handle()
    {
        $tenant_id                = $this->creator->tenant_id;
        $this->input['tenant_id'] = $tenant_id;
        if (isset($this->input['is_main']) && $this->input['is_main']) {
            Warehouse::query()->where('country_id', $this->input['country_id'])
                ->where('is_main', 1)
                ->update(["is_main" => 0]);
        }

        $warehouse = Warehouse::create($this->input);

        WarehouseArea::create([
            'tenant_id' => $tenant_id,
            'merchant_id' => 0,
            'warehouse_id' => $warehouse->id,
            'code' => WarehouseArea::CODE_DEFAULT,
            'name' => 'Vị trí kho mặc định'
        ]);

        (new WarehouseCreated($warehouse, $this->creator))->queue();

        return $warehouse;
    }
}
