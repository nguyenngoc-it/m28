<?php

namespace Modules\Order\Commands\RelateObjects;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Arr;
use Mockery\Exception\InvalidOrderException;
use Modules\Location\Models\Location;
use phpDocumentor\Reflection\Types\Static_;

class InputOrderByFile
{
    const ORDER_CODE           = 'order_code';
    const RECEIVER_NAME        = 'receiver_name';
    const RECEIVER_PHONE       = 'receiver_phone';
    const RECEIVER_COUNTRY     = 'receiver_country';
    const RECEIVER_PROVINCE    = 'receiver_province';
    const RECEIVER_DISTRICT    = 'receiver_district';
    const RECEIVER_WARD        = 'receiver_ward';
    const RECEIVER_ADDRESS     = 'receiver_address';
    const SKUS                 = 'skus';
    const COD                  = 'cod';
    const SHIPPING_PARTNER     = 'shipping_partner_code';
    const RECEIVER_POSTAL_CODE = 'receiver_postal_code';

    public $orderCode;
    public $receiverName;
    public $receiverPhone;
    public $receiverCountryLabel;
    public $receiverProvinceLabel;
    public $receiverDistrictLabel;
    public $receiverWardLabel;
    public $receiverAddress;
    public $shippingPartner;
    public $receiverPostalCode;
    /**
     * @var array
     * [
     *  sku_code,
     *  sku_quantity,
     *  sku_price,
     *  sku_discount
     * ]
     */
    public $skus;
    public $cod;

    /** @var Location|null */
    public $receiverCountry, $receiverProvince, $receiverDistrict, $receiverWard;

    /**
     * InputOrderByFile constructor.
     * @param array $inputs
     * @throws InvalidOrderException
     */
    public function __construct(array $inputs)
    {
        $this->makeAttributes($inputs);
        if (empty($this->receiverCountry) && empty($receiverProvince)) {
            $this->makeReceiverLocation();
        }
    }

    protected function makeAttributes(array $inputs)
    {
        $this->orderCode             = Arr::get($inputs, static::ORDER_CODE);
        $this->receiverName          = Arr::get($inputs, static::RECEIVER_NAME);
        $this->receiverPhone         = Arr::get($inputs, static::RECEIVER_PHONE);
        $this->receiverCountryLabel  = Arr::get($inputs, static::RECEIVER_COUNTRY);
        $this->receiverProvinceLabel = Arr::get($inputs, static::RECEIVER_PROVINCE);
        $this->receiverDistrictLabel = Arr::get($inputs, static::RECEIVER_DISTRICT);
        $this->receiverWardLabel     = Arr::get($inputs, static::RECEIVER_WARD);
        $this->receiverAddress       = Arr::get($inputs, static::RECEIVER_ADDRESS);
        $this->skus                  = Arr::get($inputs, static::SKUS, []);
        $this->cod                   = Arr::get($inputs, static::COD, 0);
        $this->receiverPostalCode    = Arr::get($inputs, static::RECEIVER_POSTAL_CODE);
        $this->shippingPartner       = Arr::get($inputs, static::SHIPPING_PARTNER);

    }

    /**
     * @throws InvalidOrderException
     */
    private function makeReceiverLocation()
    {
        $this->receiverCountry = Location::query()->where([
            'type' => Location::TYPE_COUNTRY,
            'active' => true
        ])->where(function (Builder $builder) {
            $builder->where('label', $this->receiverCountryLabel)
                ->orWhere('code', $this->receiverCountryLabel);
        })->first();
        if (!$this->receiverCountry) {
            throw new InvalidOrderException('not found country');
        }

        $this->receiverProvince = $this->receiverCountry->childrens->filter(function (Location $location) {
            return (($location->label == $this->receiverProvinceLabel) || ($location->code == $this->receiverProvinceLabel));
        })->first();
        if ($this->receiverProvinceLabel && !$this->receiverProvince) {
            throw new InvalidOrderException('not found province');
        }

        $this->receiverDistrict = $this->receiverProvince->childrens->filter(function (Location $location) {
            return (($location->label == $this->receiverDistrictLabel) || ($location->code == $this->receiverDistrictLabel));
        })->first();
        if ($this->receiverDistrictLabel && !$this->receiverDistrict) {
            throw new InvalidOrderException('not found district');
        }
        if ($this->receiverWardLabel) {
            $this->receiverWard = $this->receiverDistrict->childrens->filter(function (Location $location) {
                return (($location->label == $this->receiverWardLabel) || ($location->code == $this->receiverWardLabel));
            })->first();
            if (!$this->receiverWard) {
                throw new InvalidOrderException('not found ward');
            }
        }
    }
}
