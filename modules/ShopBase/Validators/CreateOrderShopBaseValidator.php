<?php

namespace Modules\ShopBase\Validators;

use App\Base\Validator;
use Illuminate\Support\Arr;
use Modules\Location\Models\Location;
use Modules\Merchant\Models\Merchant;

class CreateOrderShopBaseValidator extends Validator
{

    /**
     * @var Merchant
     */
    protected $merchant;

    /**
     * @var Location
     */
    protected $country;

    /**
     * @var Location
     */
    protected $province;

    /**
     * @var string
     */
    protected $receiverAddress = '';

    /**
     * CreateOrderShopBaseValidator constructor.
     * @param Merchant $merchant
     * @param $input
     */
    public function __construct(Merchant $merchant, $input)
    {
        $this->merchant = $merchant;
        parent::__construct($input);
    }


    /**
     * @return array
     */
    public function rules()
    {
        return [
            'id' => 'required',
            'shipping_address' => 'required',
            'line_items' => 'required'
        ];
    }

    protected function customValidate()
    {
        $shippingAddress = Arr::get($this->input, 'shipping_address', []);
        $countryCode = Arr::get($shippingAddress, 'country_code', '');
        $this->country = $this->merchant->getCountry();
        if(
            !$this->country instanceof Location ||
            $this->country->code != $countryCode
        ) {
            $this->errors()->add('country_code', static::ERROR_NOT_EXIST);
            return;
        }

        $provinceCode = Arr::get($shippingAddress, 'province_code', '');
        $this->province = Location::query()->where('code', trim($provinceCode))
            ->where('parent_code', $this->country->code)
            ->where('type', Location::TYPE_PROVINCE)->first();

        $address1 = Arr::get($shippingAddress, 'address1', '');
        $address2 = Arr::get($shippingAddress, 'address2', '');
        $city     = Arr::get($shippingAddress, 'city', '');
        $zip     = Arr::get($shippingAddress, 'zip', '');

        $receiverAddress = '';
        if(!empty($address1)) {
            $receiverAddress = $address1;
        }
        if(!empty($address2)) {
            $receiverAddress .= ' - '.$address2;
        }
        if(!empty($city)) {
            $receiverAddress .= ' - '.$city;
        }
        if(!empty($zip)) {
            $receiverAddress .= ' - Zip '.$zip;
        }

        $this->receiverAddress = $receiverAddress;
    }

    /**
     * @return string
     */
    public function getReceiverAddress()
    {
        return $this->receiverAddress;
    }

    /**
     * @return Location
     */
    public function getCountry()
    {
        return $this->country;
    }

    /**
     * @return Location
     */
    public function getProvince()
    {
        return $this->province;
    }
}