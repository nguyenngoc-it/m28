<?php

namespace Modules\OrderIntegration\Validators;

use App\Base\Validator;
use Modules\Location\Models\Location;
use Modules\Merchant\Models\Merchant;
use Modules\ShippingPartner\Models\ShippingPartner;
use Modules\Tenant\Models\Tenant;

class CreateOrderInternalValidator extends Validator
{
    /** @var Tenant $tenant */
    private $tenant;

    /**
     * @var Location|null
     */
    protected $receiverProvince;

    /**
     * @var Location|null
     */
    protected $receiverDistrict;

    /**
     * @var Location|null
     */
    protected $receiverWard;

    /**
     * @var Merchant
     */
    protected $merchant;


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
            'merchant' => 'required',
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

        if(!$this->validateLocation()) {
            return;
        }

        if(!empty($this->input['shipping_partner_code'])) {
            $shipping_partner_code = trim($this->input['shipping_partner_code']);
            if(!$this->shippingPartner = $this->tenant->shippingPartners()->firstWhere('code', $shipping_partner_code)) {
                $this->errors()->add('shipping_partner_code', static::ERROR_NOT_EXIST);
                return false;
            }
        }
    }

    /**
     * @return bool|void
     */
    protected function validateLocation()
    {
        if(!empty($this->input['receiver_province_code'])) {
            if(!$this->receiverProvince = Location::query()->firstWhere([
                'code' => $this->input['receiver_province_code'],
                'type' => Location::TYPE_PROVINCE
            ])) {
                $this->errors()->add('receiver_province_code', static::ERROR_NOT_EXIST);
                return false;
            }
        }

        if(!empty($this->input['receiver_district_code'])) {
            if(!$this->receiverDistrict = Location::query()->firstWhere([
                'code' => $this->input['receiver_district_code'],
                'type' => Location::TYPE_DISTRICT
            ])) {
                $this->errors()->add('receiver_district_code', static::ERROR_NOT_EXIST);
                return false;
            }
        }

        if(!empty($this->input['receiver_ward_code'])) {
            if(!$this->receiverWard = Location::query()->firstWhere([
                'code' => $this->input['receiver_ward_code'],
                'type' => Location::TYPE_WARD
            ])) {
                $this->errors()->add('receiver_ward_code', static::ERROR_NOT_EXIST);
                return false;
            }
        }

        return true;
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
     * @return Location|null
     */
    public function getReceiverProvince()
    {
        return $this->receiverProvince;
    }


    /**
     * @return Location|null
     */
    public function getReceiverDistrict()
    {
        return $this->receiverDistrict;
    }


    /**
     * @return Location|null
     */
    public function getReceiverWard()
    {
        return $this->receiverWard;
    }

    /**
     * @return Location|null
     */
    public function getShippingPartner()
    {
        return $this->shippingPartner;
    }
}
