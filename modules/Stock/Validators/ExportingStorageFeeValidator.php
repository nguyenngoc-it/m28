<?php

namespace Modules\Stock\Validators;

use App\Base\Validator;

class ExportingStorageFeeValidator extends Validator
{
    /**
     * @return array
     */
    public function rules()
    {
        return [
            'merchant_id' => '',
            'warehouse_id' => '',
            'closing_time' => 'required|array',
            'closing_time.from' => 'required|date_format:Y-m-d',
            'closing_time.to' => 'required|date_format:Y-m-d',
        ];
    }

    protected function customValidate()
    {

    }
}
