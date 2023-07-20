<?php

namespace Modules\Order\Validators;

use App\Base\Validator;
use Modules\Order\Models\Order;
use Modules\Order\Models\OrderTransaction;
use Modules\User\Models\User;

class PaymentConfirmValidator extends Validator
{
    /**
     * PaymentConfirmValidator constructor.
     * @param Order $order
     * @param User $user
     * @param array $input
     */
    public function __construct(Order $order, User $user, array $input)
    {
        $this->order = $order;
        $this->user = $user;
        parent::__construct($input);
    }

    /**
     * @var Order
     */
    private $order;

    /**
     * @return array
     */
    public function rules()
    {
        return [
            'payment_method' => 'required|in:'.implode(',', OrderTransaction::$methods),
            'payment_amount' => 'required|numeric|gte:0',
            'payment_time' => 'required',
        ];
    }

    protected function customValidate()
    {
        if(!$this->order->canPaymentConfirm($this->user)) {
            $this->errors()->add('order', self::ERROR_INVALID);
            return;
        }

        $paymentAmount = floatval($this->input['payment_amount']);
        $paid_amount  = $this->order->paid_amount + $paymentAmount;
        $debit_amount = $this->order->total_amount - $paid_amount;
        if(
            $debit_amount < 0 ||
            ($this->order->cod - $paymentAmount < 0)
        ) {
            $this->errors()->add('payment_amount', static::ERROR_GREATER);
        }

        if($this->order->payment_type == Order::PAYMENT_TYPE_ADVANCE_PAYMENT) {
            if(
                $this->input['payment_method'] == OrderTransaction::METHOD_BANK_TRANSFER &&
                (!empty($this->input['bank_name']) || !empty($this->input['bank_account']))
            ) {
                if(empty($this->input['bank_name'])) {
                    $this->errors()->add('bank_name', static::ERROR_REQUIRED);
                }

                if(empty($this->input['bank_account'])) {
                    $this->errors()->add('bank_account', static::ERROR_REQUIRED);
                }
            }
        }
    }
}
