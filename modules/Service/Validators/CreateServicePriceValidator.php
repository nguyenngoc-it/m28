<?php

namespace Modules\Service\Validators;

use App\Base\Validator;
use Modules\Service\Models\Service;
use Modules\Service\Models\ServicePrice;

class CreateServicePriceValidator extends Validator
{
    /** @var Service */
    protected $service;


    public function __construct(Service $service, array $input = [])
    {
        parent::__construct($input);
        $this->service = $service;
    }

    /**
     * @return array
     */
    public function rules()
    {
        return [
            'label' => 'required',
            'price' => 'required|numeric',
            'yield_price' => '',
            'note' => '',
            'seller_codes' => 'array',
            'seller_refs' => 'array',
            'deduct' => 'numeric|max:1'
        ];
    }

    /**
     * @return Service
     */
    public function getService(): Service
    {
        return $this->service;
    }

    protected function customValidate()
    {
        $price      = $this->input('price');
        $yieldPrice = $this->input('yield_price');
        if ($yieldPrice && $this->service->type != Service::SERVICE_TYPE_EXPORT) {
            $this->errors()->add('yield_price', static::ERROR_INVALID);
            return;
        }
        /** @var ServicePrice $servicePrice */
        foreach ($this->service->servicePrices as $servicePrice) {
            if ($servicePrice->price == $price) {
                $this->errors()->add('price', static::ERROR_DUPLICATED);
                return;
            }
        }
        $deduct = $this->input('deduct');
        if (is_null($deduct) && $this->service->type == Service::SERVICE_TYPE_EXTENT) {
            $this->errors()->add('deduct', static::ERROR_REQUIRED);
            return;
        }
    }
}
