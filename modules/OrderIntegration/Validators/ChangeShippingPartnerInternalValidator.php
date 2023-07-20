<?php

namespace Modules\OrderIntegration\Validators;

use App\Base\Validator;
use Modules\Merchant\Models\Merchant;
use Modules\Order\Models\Order;
use Modules\ShippingPartner\Models\ShippingPartner;
use Modules\Tenant\Models\Tenant;

class ChangeShippingPartnerInternalValidator extends Validator
{
    /** @var Tenant $tenant */
    private $tenant;

    /**
     * @var Merchant
     */
    protected $merchant;

    /**
     * @var Order
     */
    protected $order;

    /**
     * @var ShippingPartner|null
     */
    protected $shippingPartner;

    /**
     * @return array
     */
    public function rules()
    {
        return [
            'tenant'  => 'required',
            'code' => 'required',
            'merchant' => 'required',
            'shipping_partner_code' => 'required'
        ];
    }

    /**
     * Custom validate
     */
    protected function customValidate()
    {
        $tenantCode = trim($this->input['tenant']);
        $this->tenant = Tenant::query()->firstWhere('code', $tenantCode);
        if(!$this->tenant instanceof Tenant) {
            $this->errors()->add('tenant', self::ERROR_INVALID);
            return;
        }

        if(
           !$this->merchant = $this->tenant->merchants()->firstWhere('code', $this->input('merchant'))
        ) {
            $this->errors()->add('merchant', static::ERROR_NOT_EXIST);
            return;
        }

        if(
           !$this->order = $this->merchant->orders()->firstWhere('code', trim($this->input('code')))
        ) {
            $this->errors()->add('code', static::ERROR_NOT_EXIST);
            return;
        }

        $shipping_partner_code = trim($this->input['shipping_partner_code']);
        if(!$this->shippingPartner = $this->tenant->shippingPartners()->firstWhere('code', $shipping_partner_code)) {
            $this->errors()->add('shipping_partner_code', static::ERROR_NOT_EXIST);
            return;
        }
    }


    /**
     * @return Tenant
     */
    public function getTenant()
    {
        return $this->tenant;
    }

    /**
     * @return Merchant
     */
    public function getMerchant()
    {
        return $this->merchant;
    }

    /**
     * @return Order
     */
    public function getOrder()
    {
        return $this->order;
    }
    /**
     * @return ShippingPartner|null
     */
    public function getShippingPartner()
    {
        return $this->shippingPartner;
    }

}
