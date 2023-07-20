<?php

namespace Modules\PurchasingOrder\Validators;

use App\Base\Validator;
use Modules\Merchant\Models\Merchant;
use Modules\PurchasingOrder\Models\PurchasingOrder;
use Modules\Warehouse\Models\Warehouse;

class UpdateMerchantPurchasingOrderValidator extends Validator
{
    /** @var PurchasingOrder */
    protected $purchasingOrder;

    public function rules()
    {
        return [
            'id' => 'required',
            'services' => 'array',
            'warehouse_id' => 'required|int',
            'is_putaway' => 'required|boolean',
            'merchant_code' => 'required'
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
        $merchantCode      = $this->input('merchant_code');
        $merchant = Merchant::query()->where('code', $merchantCode)->where('tenant_id', $this->user->tenant->id)->first();

        if (!$merchant){
            $this->errors()->add('merchant', static::ERROR_EXISTS);
            return;
        }
        if (!$this->purchasingOrder = PurchasingOrder::query()->where(['id' => $purchasingOrderId, 'tenant_id' => $this->user->tenant_id])->first()) {
            $this->errors()->add('id', static::ERROR_EXISTS);
            return;
        }
        if ($merchant->id != $this->purchasingOrder->merchant_id) {
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
