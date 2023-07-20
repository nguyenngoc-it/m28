<?php

namespace Modules\OrderPacking\Validators;

use App\Base\Validator;
use Illuminate\Support\Arr;
use Modules\OrderPacking\Models\OrderPacking;
use Modules\ShippingPartner\Models\ShippingPartner;
use Modules\User\Models\User;
use Modules\Warehouse\Models\Warehouse;

class ChangeShippingPartnerValidator extends Validator
{
    /**
     * @var Warehouse
     */
    protected $warehouse;


    protected $shippingPartner = null;

    /**
     * @var User|null
     */
    protected $user = null;

    public function __construct(User $user, $input = [])
    {
        $this->user = $user;
        parent::__construct($input);
    }

    public function rules()
    {
        return [
            'updated_shipping_partner_id' => 'required',
            'ids' => 'array',
        ];
    }

    protected function customValidate()
    {
        $shipping_partner_id   = $this->input['updated_shipping_partner_id'];
        $this->shippingPartner = $this->user->tenant->shippingPartners()->firstWhere('id', $shipping_partner_id);
        if (!$this->shippingPartner instanceof ShippingPartner) {
            return $this->errors()->add('updated_shipping_partner_id', static::ERROR_INVALID);
        }

        if (!$this->shippingPartner->status) {
            return $this->errors()->add('updated_shipping_partner_status', static::ERROR_INVALID);
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
