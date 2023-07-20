<?php

namespace Modules\PurchasingOrder\Validators;

use App\Base\Validator;
use Modules\PurchasingOrder\Models\PurchasingOrder;

class MerchantPurchasingOrderDetailValidator extends Validator
{
    /** @var PurchasingOrder */
    protected $purchasingOrder;

    public function rules()
    {
        return [
            'id' => 'required',
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
        if (!$this->purchasingOrder = PurchasingOrder::query()->where(['id' => $purchasingOrderId, 'tenant_id' => $this->user->tenant_id])->first()) {
            $this->errors()->add('id', 'exists');
            return;
        }
    }
}
