<?php

namespace Modules\Warehouse\Validators;

use App\Base\Validator;
use Modules\User\Models\User;
use Modules\Warehouse\Models\WarehouseArea;

class UpdateWarehouseAreaValidator extends Validator
{
    /**
     * @var WarehouseArea|null
     */
    protected $warehouseArea = null;

    /**
     * @var User
     */
    protected $user;

    public function __construct(array $input, WarehouseArea $warehouseArea, User $user)
    {
        parent::__construct($input);
        $this->warehouseArea = $warehouseArea;
        $this->user          = $user;
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
            ->where('tenant_id', $this->warehouseArea->tenant_id)
            ->where('warehouse_id', $this->warehouseArea->warehouse_id)
            ->where('code', $this->input['code'])
            ->where('id', '!=', $this->warehouseArea->id)
            ->first();

        if (!empty($warehouseArea)) {
            $this->errors()->add('code', static::ERROR_ALREADY_EXIST);
            return;
        }
    }
}
