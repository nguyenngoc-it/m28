<?php

namespace Modules\Product\Validators;

use App\Base\Validator;
use Modules\Merchant\Models\Merchant;

class MerchantNotUserCreateProductValidator extends Validator
{

    public function rules()
    {
        return [
            'name' => 'required|string',
            'code' => 'string',
            'files' => 'array|max:5',
            'files.*' => 'file|mimes:jpg,jpeg,png|max:5120',
            'services' => 'array',
            'weight' => 'numeric',
            'height' => 'numeric',
            'width' => 'numeric',
            'length' => 'numeric'
        ];
    }


    protected function customValidate()
    {
        $code = $this->input('code');
        $merchantId = $this->input('merchant_id');
        $merchant = Merchant::find($merchantId);
        if ($code && $merchant->products->where('code', $code)->first()) {
            $this->errors()->add('code', [static::ERROR_ALREADY_EXIST => 'PRODUCT CODE ALREADY EXIST']);
            return;
        }
    }
}
