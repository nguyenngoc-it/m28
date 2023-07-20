<?php

namespace Modules\PurchasingManager\Validators;

use App\Base\Validator;
use Modules\PurchasingManager\Models\PurchasingAccount;

class DeletingMerchantPurchasingAccountValidator extends Validator
{
    /** @var PurchasingAccount */
    protected $purchasingAccount;

    public function rules()
    {
        return [
            'id' => 'required|int',
            'password' => 'string',
        ];
    }

    /**
     * @return PurchasingAccount
     */
    public function getPurchasingAccount(): PurchasingAccount
    {
        return $this->purchasingAccount;
    }

    /**
     * @return void
     */
    protected function customValidate()
    {
        $purchasingAccountId = $this->input('id', 0);

        $this->purchasingAccount = PurchasingAccount::query()->where([
            'tenant_id' => $this->user->tenant_id,
            'merchant_id' => $this->user->merchant->id,
            'id' => $purchasingAccountId
        ])->first();

        if (!$this->purchasingAccount) {
            $this->errors()->add('id', static::ERROR_EXISTS);
            return;
        }
    }
}
