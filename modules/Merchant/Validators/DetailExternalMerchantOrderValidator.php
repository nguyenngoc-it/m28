<?php

namespace Modules\Merchant\Validators;

use App\Base\Validator;
use Modules\Merchant\Models\Merchant;
use Modules\Order\Models\Order;

class DetailExternalMerchantOrderValidator extends Validator
{
    /** @var Order $order */
    protected $order;

    /**
     * @return array
     */
    public function rules()
    {
        return [
            'merchant_code' => 'required',
            'order_code' => 'required',
        ];
    }

    /**
     * @return Order
     */
    public function getOrder(): Order
    {
        return $this->order;
    }

    protected function customValidate()
    {
        $merchantCode = trim($this->input('merchant_code'));
        $merchant     = Merchant::query()->where([
            'tenant_id' => $this->user->tenant_id,
            'code' => $merchantCode,
            'creator_id' => $this->user->id
        ])->first();
        if (empty($merchant)) {
            $this->errors()->add('merchant_code', static::ERROR_EXISTS);
            return;
        }

        $orderCode   = $this->input('order_code');
        $this->order = Order::query()->where([
            'tenant_id' => $this->user->tenant_id,
            'merchant_id' => $merchant->id,
            'code' => $orderCode
        ])->first();
        if (empty($this->order) || ($this->order->creator_id !== $this->user->id)) {
            $this->errors()->add('order_code', static::ERROR_EXISTS);
            return;
        }
    }
}
