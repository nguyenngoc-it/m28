<?php

namespace Modules\User\Validators;

use App\Base\Validator;
use Illuminate\Support\Arr;
use Modules\Tenant\Models\Tenant;

class AddUserWarehouseValidator extends Validator
{
    /**
     * CreateUserWarehouseValidator constructor.
     * @param Tenant $tenant
     * @param array $input
     */
    public function __construct(Tenant $tenant, array $input)
    {
        $this->tenant = $tenant;
        parent::__construct($input);
    }


    /**
     * @var Tenant
     */
    protected $tenant;

    protected $warehouses = [];

    /**
     * @return array
     */
    public function rules()
    {
        return [
            'warehouse_ids' => 'array',
        ];
    }

    protected function customValidate()
    {
        $warehouseIds = (array)Arr::get($this->input, 'warehouse_ids', []);
        $warehouseIds = array_unique($warehouseIds);
        foreach ($warehouseIds as $warehouseId) {
            if(!$warehouse = $this->tenant->warehouses()->firstWhere('id', intval($warehouseId))) {
                $this->errors()->add('warehouse_ids', static::ERROR_NOT_EXIST);
                return;
            }
            $this->warehouses[] = $warehouse;
        }
    }

    /**
     * @return array
     */
    public function getWarehouses()
    {
        return $this->warehouses;
    }

}