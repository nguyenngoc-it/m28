<?php

namespace Modules\Document\Validators;

use App\Base\Validator;
use Modules\Warehouse\Models\Warehouse;

class CreatingDocumentSkuInventoryValidator extends Validator
{
    /** @var Warehouse */
    protected $warehouse;

    public function rules()
    {
        return [
            'warehouse_id' => 'required',
        ];
    }

    /**
     * @return Warehouse
     */
    public function getWarehouse(): Warehouse
    {
        return $this->warehouse;
    }

    protected function customValidate()
    {
        $warehouseId = $this->input('warehouse_id', 0);
        if (!$this->warehouse = Warehouse::query()->where([
            'id' => $warehouseId,
            'tenant_id' => $this->user->tenant_id
        ])->first()) {
            $this->errors()->add('warehouse_id', static::ERROR_EXISTS);
            return;
        }
    }
}
