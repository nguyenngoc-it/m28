<?php

namespace Modules\Service\Validators;

use App\Base\Validator;

class UpdatingServicePriceValidator extends Validator
{
    /**
     * @return array
     */
    public function rules()
    {
        return [
            'seller_codes' => 'array',
            'seller_refs' => 'array',
        ];
    }
}
