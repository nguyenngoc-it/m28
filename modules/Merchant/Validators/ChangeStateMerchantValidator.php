<?php

namespace Modules\Merchant\Validators;

use App\Base\Validator;
use Modules\Order\Models\Order;
use Modules\Merchant\Models\Merchant;

class ChangeStateMerchantValidator extends Validator
{
    /**
     * ChangeStateMerchantValidator constructor.
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
            'status' => 'required',
        ];
    }

    protected function customValidate()
    {
        if (!$this->input['status']) {

            $orderCount = $this->merchant->orders()->whereNotIn('status', [Order::STATUS_FINISH, Order::STATUS_CANCELED])->count();
            if ($orderCount > 0) {
                $this->errors()->add('order', static::ERROR_INVALID);
                return;
            }
        }
    }
}
