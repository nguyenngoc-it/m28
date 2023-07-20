<?php

namespace Modules\ShippingPartner\Validators;

use App\Base\Validator;
use Modules\Location\Models\Location;
use Modules\ShippingPartner\Services\ExpectedTransportingPrice\ExpectedTransporting;
use Modules\User\Models\User;

class UploadExpectedTransportingPriceValidator extends Validator
{
    /** @var ExpectedTransporting $expectedTransportingPrice */
    protected $expectedTransportingPrice;

    public function __construct(array $input, User $user, ExpectedTransporting $expectedTransportingPrice)
    {
        parent::__construct($input, $user);
        $this->expectedTransportingPrice = $expectedTransportingPrice;
    }

    /**
     * @return array
     */
    public function rules(): array
    {
        return [
            'shipping_partner_id' => 'required',
            'warehouse_id' => 'required',
            'max_weight' => 'required|numeric',
            'price' => 'required|numeric',
            'return_price_ratio' => 'required|numeric',
            'receiver_province' => 'required',
            'receiver_province_id' => 'required',
            'line' => 'required'
        ];
    }

    protected function customValidate()
    {
        $requiredLocations  = $this->expectedTransportingPrice->requiredFieldTablePrices();
        $receiverProvinceId = $this->input('receiver_province_id');
        if (in_array('receiver_province_id', $requiredLocations)) {
            /** @var Location|null $province */
            $province = Location::find($receiverProvinceId);
            if (empty($province)) {
                $this->errors()->add('province', static::ERROR_EXISTS);
                return;
            }
            if ($province->parent_code != $this->expectedTransportingPrice->getCountryCode()) {
                $this->errors()->add('province', 'invalid_country');
            }
        }
    }
}
