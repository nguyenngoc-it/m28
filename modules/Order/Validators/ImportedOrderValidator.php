<?php

namespace Modules\Order\Validators;

use App\Base\Validator;
use Modules\Location\Models\Location;
use Modules\Merchant\Models\Merchant;
use Modules\Merchant\Models\Sale;
use Modules\Order\Models\Order;
use Modules\Order\Models\OrderTransaction;
use Modules\Product\Models\Sku;
use Modules\Service;
use Modules\ShippingPartner\Models\ShippingPartner;
use Modules\Tenant\Models\Tenant;
use Modules\User\Models\User;

class ImportedOrderValidator extends Validator
{
    /**
     * @var Merchant
     */
    protected $merchant;

    /**
     * @var Tenant
     */
    protected $tenant;


    /**
     * @var User
     */
    protected $creator;

    /**
     * @var Sale
     */
    protected $sale;

    /**
     * @var Sku
     */
    protected $sku;

    /**
     * @var Location|null
     */
    protected $receiverCountry;

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
     * @var ShippingPartner|null
     */
    protected $shippingPartner;


    /**
     * @var array
     */
    protected $insertedOrderKeys = [];

    /**
     * ImportedOrderValidator constructor.
     * @param User $creator
     * @param array $input
     * @param array $insertedOrderKeys
     */
    public function __construct(User $creator, array $input, $insertedOrderKeys = [])
    {
        $this->creator = $creator;
        $this->tenant = $creator->tenant;
        $this->insertedOrderKeys = $insertedOrderKeys;
        parent::__construct($input);
    }

    /**
     * @return array
     */
    public function rules()
    {
        return [
            'sku_code' => 'required',
            'quantity' => 'required|numeric|gte:0',
            'price' => 'required|numeric|gte:0',
            'discount_amount_sku' => 'numeric|gte:0',
            'discount_amount_order' => 'numeric|gte:0',
            'payment_amount' => 'numeric|gte:0',
            'tax' => 'numeric|gte:0',
        ];
    }

    protected function customValidate()
    {
        $tenant = $this->tenant;
        $code   = $this->input('code');

        $validateCode = preg_match("/\s/s", trim($code));
        if ($validateCode) {
            $this->errors()->add('order_code', static::ERROR_INVALID);
            return;
        }

        if(empty($this->insertedOrderKeys) && empty($code)) {
            $this->errors()->add('order_code', static::ERROR_REQUIRED);
        }

        if (
            ($sku_code = $this->input('sku_code')) &&
            !$this->sku = $tenant->skus()->firstWhere('code', $sku_code)
        ) {
            $this->errors()->add('sku_code', static::ERROR_NOT_EXIST);
            return;
        }

        if(!empty($code)) {
            if(empty($this->input['merchant_code'])) {
                $this->errors()->add('merchant_code', static::ERROR_REQUIRED);
                return;
            }

            if ($merchant_code = $this->input('merchant_code')) {
                if(!$this->merchant = $this->tenant->merchants()->firstWhere('code', $merchant_code)) {
                    $this->errors()->add('merchant_code', static::ERROR_NOT_EXIST);
                    return;
                }

                if(!$this->merchant = $this->creator->merchants()->firstWhere('code', $merchant_code)) {
                    $this->errors()->add('merchant_code', static::ERROR_INVALID);
                    return;
                }

                if(!$this->merchant = $this->sku->product->merchants()->firstWhere('code', $merchant_code)) {
                    $this->errors()->add('merchant_code', static::ERROR_INVALID);
                    return;
                }
            }

            $country = $this->merchant->getCountry();
            if(!$country instanceof Location) {
                $this->errors()->add('country', static::ERROR_NOT_EXIST);
                return;
            }

            $locationErrors = $this->validateLocation($country);
            if(!empty($locationErrors)) {
                foreach ($locationErrors as $key => $error) {
                    $this->errors()->add($key, $error);
                }
            }

            if (!empty($this->input['shipping_partner_code'])) {
                if(
                    !$this->shippingPartner = $country->getShippingPartnerByAliasOrCode($tenant->id, $this->input['shipping_partner_code'])
                ) {
                    $this->errors()->add('shipping_partner_code', static::ERROR_NOT_EXIST);
                    return;
                };
            }

            if(!$this->merchant->status) {
                $this->errors()->add('merchant_code', static::ERROR_NOT_EXIST);
                return;
            }

            if ($this->merchant->orders()->firstWhere('code', $code)) {
                $this->errors()->add('code', static::ERROR_ALREADY_EXIST);
            }

            if(
                !empty($this->input['payment_method']) &&
                !in_array($this->input['payment_method'], OrderTransaction::$methods)
            ) {
                $this->errors()->add('payment_method', static::ERROR_INVALID);
            }

            if(empty($this->input['payment_type'])) {
                $this->errors()->add('payment_type', static::ERROR_REQUIRED);
            } else if (!in_array($this->input['payment_type'], Order::$paymentTypes)) {
                $this->errors()->add('payment_type', static::ERROR_INVALID);
            }


            $requireds = [
                'receiver_name',
                'receiver_phone',
                'receiver_address'
            ];

            foreach ($requireds as $required) {
                if(empty($this->input[$required])) {
                    $this->errors()->add($required, static::ERROR_REQUIRED);
                }
            }
        }

        if(!empty($this->input['intended_delivery_at'])) {
            $intendedDeliveryAt = Service::order()->formatDateTime($this->input['intended_delivery_at']);
            if($intendedDeliveryAt->lt(date('Y-m-d'))) {
                $this->errors()->add('intended_delivery_at', static::ERROR_INVALID);
            }
        }

        if(!empty($this->input['created_at_origin'])) {
            $createdAtOrigin = Service::order()->formatDateTime($this->input['created_at_origin']);
            if($createdAtOrigin->gt(date('Y-m-d 23:59:59'))) {
                $this->errors()->add('created_at_origin', static::ERROR_INVALID);
            }
        }
    }

    /**
     * @param Location $country
     * @return array
     */
    protected function validateLocation(Location $country)
    {
        $locationErrors = [];
        if(!empty($this->input['province'])) {
            $countryCode = ($country instanceof Location) ? $country->code : '';

            if(!$this->receiverProvince = Location::query()->firstWhere([
                'label' => $this->input['province'],
                'type' => Location::TYPE_PROVINCE,
                'parent_code' => $countryCode
            ])) {
                $locationErrors['province'] = static::ERROR_NOT_EXIST;
            }
        }

        if(!empty($this->input['district'])) {
            if(!$this->receiverProvince instanceof Location) {
                $locationErrors['district'] = static::ERROR_INVALID;
            } else if(!$this->receiverDistrict = Location::query()->firstWhere([
                'label' => $this->input['district'],
                'type' => Location::TYPE_DISTRICT,
                'parent_code' => $this->receiverProvince->code
            ])) {
                $locationErrors['district'] = static::ERROR_NOT_EXIST;
            }
        }

        if(!empty($this->input['ward'])) {
            if(!$this->receiverDistrict instanceof Location) {
                $locationErrors['ward'] = static::ERROR_INVALID;
            } else if(!$this->receiverWard = Location::query()->firstWhere([
                'label' => $this->input['ward'],
                'type' => Location::TYPE_WARD,
                'parent_code' => $this->receiverDistrict->code
            ])) {
                $locationErrors['ward'] = static::ERROR_NOT_EXIST;
            }
        }

        return $locationErrors;
    }

    /**
     * @return Sku
     */
    public function getSku()
    {
        return $this->sku;
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
    public function getReceiverCountry()
    {
        return $this->receiverCountry;
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
     * @return ShippingPartner|null
     */
    public function getShippingPartner()
    {
        return $this->shippingPartner;
    }
}
