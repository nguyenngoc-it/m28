<?php

namespace Modules\Order\Validators;

use App\Base\Validator;
use Modules\Order\Models\Order;
use Modules\ShippingPartner\Models\ShippingPartner;
use Modules\User\Models\User;

class ChangeShippingPartnerValidator extends Validator
{
    /**
     * @var Order|null
     */
    protected $order = null;

    protected $shippingPartner = null;

    /**
     * @var User|null
     */
    protected $user = null;

    public function __construct(Order $order, User $user, $input = [])
    {
        $this->order = $order;
        $this->user = $user;
        parent::__construct($input);
    }

    public function rules()
    {
        return [
            'shipping_partner_id' => 'required'
        ];
    }

    protected function customValidate()
    {
        if (!$this->order->canChangeShippingPartner()) {
            return $this->errors()->add('status', static::ERROR_INVALID);
        }

        $shipping_partner_id = $this->input['shipping_partner_id'];
        $this->shippingPartner = $this->order->tenant->shippingPartners()->firstWhere('id', $shipping_partner_id);
        if(!$this->shippingPartner instanceof ShippingPartner)
        {
            return $this->errors()->add('shipping_partner_id', static::ERROR_INVALID);
        }

        if(!$this->shippingPartner->status)
        {
            return $this->errors()->add('shipping_partner_status', static::ERROR_INVALID);
        }
    }

    /**
     * @return ShippingPartner
     */
    public function getShippingPartner()
    {
        return $this->shippingPartner;
    }

}
