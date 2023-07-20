<?php

namespace Modules\Warehouse\Validators;

use App\Base\Validator;
use Modules\User\Models\User;
use Modules\Warehouse\Models\Warehouse;
use Modules\Warehouse\Models\WarehouseArea;

class CreateWarehouseAreaValidator extends Validator
{
    /**
     * @var Warehouse|null
     */
    protected $warehouse = null;

    /**
     * CreateWarehouseAreaValidator constructor.
     * @param array $input
     * @param Warehouse $warehouse
     * @param User $user
     */
    public function __construct(array $input, Warehouse $warehouse, User $user)
    {
        parent::__construct($input);
        $this->warehouse = $warehouse;
        $this->user      = $user;
    }

    /**
     * @var string[]
     */
    public static $acceptKeys = [
        'code',
        'name',
        'description',
    ];

    /**
     * @return array|string[]
     */
    public function rules()
    {
        return [
            'code' => 'required',
            'name' => 'required',
        ];
    }

    protected function customValidate()
    {
        $warehouseArea = WarehouseArea::query()
            ->where('tenant_id', $this->input['tenant_id'])
            ->where('code', $this->input['code'])
            ->where('warehouse_id', $this->warehouse->id)
            ->first();

        if (!empty($warehouseArea)) {
            $this->errors()->add('code', static::ERROR_ALREADY_EXIST);
            return;
        }
    }
}
