<?php

namespace Modules\Merchant\Validators;

use App\Base\Validator;
use Modules\Merchant\Models\Merchant;
use Modules\Transaction\Models\Transaction;

class CreateTransactionValidator extends Validator
{
    /**
     * CreateTransactionValidator constructor.
     * @param Merchant $merchant
     * @param $input
     */
    public function __construct(Merchant $merchant, $input)
    {
        $this->merchant = $merchant;
        parent::__construct($input);
    }


    /**
     * @var Merchant
     */
    protected $merchant;


    /**
     * @return array
     */
    public function rules()
    {
        return [
            'type' => 'required|' . 'in:'.implode(',', [Transaction::ACTION_DEPOSIT, Transaction::ACTION_COLLECT, Transaction::ACTION_WITHDRAW]),
            'amount' => 'required|numeric|gt:0',
            'description' => 'required'
        ];
    }
}
