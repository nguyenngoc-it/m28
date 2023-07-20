<?php

namespace Modules\Product\Validators;

use App\Base\Validator;
use Modules\Merchant\Models\Merchant;

class MerchantNotUserUpdateProductValidator extends Validator
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
}
