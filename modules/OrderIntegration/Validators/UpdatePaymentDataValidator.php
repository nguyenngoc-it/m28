<?php

namespace Modules\OrderIntegration\Validators;

use App\Base\Validator;

class UpdatePaymentDataValidator extends Validator
{
    /**
     * @return array
     */
    public function rules()
    {
        return [
            'order_code'      => 'required',
            'total_amount'    => 'required|numeric|gte:0',
            'shipping_amount' => 'required|numeric|gte:0',
            'order_amount'    => 'required|numeric|gte:0',
            'cod_amount'      => 'required|numeric',
            'paid_amount'     => 'numeric',
            'debit_amount'    => 'required|numeric',
        ];
    }

    /**
     * Custom validate
     */
    protected function customValidate()
    {
        
    }
}
