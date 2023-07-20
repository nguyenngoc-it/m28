<?php

namespace Modules\PurchasingOrder\Validators;

use App\Base\Validator;
use Modules\PurchasingOrder\Models\PurchasingOrder;
use Modules\Warehouse\Models\Warehouse;

class UpdatingMerchantPurchasingOrderValidator extends Validator
{
    /** @var PurchasingOrder */
    protected $purchasingOrder;

    public function rules()
    {
        return [
            'id' => 'required',
            'services' => 'array',
            'warehouse_id' => 'required|int',
            'is_putaway' => 'required|boolean'
        ];
    }

    /**
     * @return PurchasingOrder
     */
    public function getPurchasingOrder(): PurchasingOrder
    {
        return $this->purchasingOrder;
    }

    protected function customValidate()
    {
        $purchasingOrderId = $this->input('id');
        $warehouseId       = $this->input('warehouse_id');
        if (!$this->purchasingOrder = PurchasingOrder::query()->where(['id' => $purchasingOrderId, 'tenant_id' => $this->user->tenant_id])->first()) {
            $this->errors()->add('id', static::ERROR_EXISTS);
            return;
        }
        if ($this->user->merchant->id != $this->purchasingOrder->merchant_id) {
            $this->errors()->add('id', 'order_is_not_from_seller');
            return;
        }
        if ($warehouseId && !Warehouse::query()->where(['id' => $warehouseId, 'tenant_id' => $this->user->tenant_id])->first()) {
            $this->errors()->add('warehouse_id', static::ERROR_EXISTS);
            return;
        }
        if ($this->purchasingOrder->is_putaway) {
            $this->errors()->add('id', 'do_not_update');
            return;
        }
    }
}
