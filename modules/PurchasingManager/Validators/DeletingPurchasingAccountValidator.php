<?php

namespace Modules\PurchasingManager\Validators;

use App\Base\Validator;
use Modules\Merchant\Models\Merchant;
use Modules\PurchasingManager\Models\PurchasingAccount;

class DeletingPurchasingAccountValidator extends Validator
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
        $merchantId = $this->input('merchant_id', 0);
        $merchant = Merchant::find($merchantId);

        if ($merchant) {
            $this->purchasingAccount = PurchasingAccount::query()->where([
                // 'tenant_id' => $merchant->tenant_id,
                'merchant_id' => $merchant->id,
                'id' => $purchasingAccountId
            ])->first();
    
            if (!$this->purchasingAccount) {
                $this->errors()->add('id', static::ERROR_EXISTS);
                return;
            }
        } else {
            $this->errors()->add('id', static::ERROR_EXISTS);
            return;
        }
    }
}
