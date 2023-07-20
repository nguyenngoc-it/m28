<?php

namespace Modules\Service\Validators;

use App\Base\Validator;

class UpdatingServicePriceAllMerchantsValidator extends Validator
{
    /**
     * @return array
     */
    public function rules()
    {
        return [
            'country_id' => 'required|int'
        ];
    }
}
