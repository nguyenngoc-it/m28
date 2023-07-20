<?php

namespace Modules\Service\Validators;

use App\Base\Validator;

class UpdatingServiceValidator extends Validator
{
    /**
     * @return array
     */
    public function rules()
    {
        return [
            'auto_price_by'
        ];
    }
}
