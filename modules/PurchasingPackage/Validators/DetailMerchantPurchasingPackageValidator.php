<?php

namespace Modules\PurchasingPackage\Validators;

use App\Base\Validator;
use Modules\PurchasingPackage\Models\PurchasingPackage;

class DetailMerchantPurchasingPackageValidator extends Validator
{
    /** @var PurchasingPackage */
    protected $merchantPurchasingPackage;

    /**
     * @return array
     */
    public function rules()
    {
        return [
            'id' => 'required',
        ];
    }

    /**
     * @return PurchasingPackage
     */
    public function getMerchantPurchasingPackage(): PurchasingPackage
    {
        return $this->merchantPurchasingPackage;
    }

    protected function customValidate()
    {
        $id = $this->input('id', 0);
        if (!$this->merchantPurchasingPackage = PurchasingPackage::query()->where(['id' => $id, 'tenant_id' => $this->user->tenant_id, 'merchant_id' => $this->user->merchant->id])->first()) {
            $this->errors()->add('id', static::ERROR_EXISTS);
            return;
        }
    }
}
