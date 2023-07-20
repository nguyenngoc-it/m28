<?php

namespace Modules\Service\Validators;

use App\Base\Validator;
use Modules\Location\Models\Location;
use Modules\Service\Models\Service;

class CreateServiceValidator extends Validator
{
    /** @var Location */
    protected $country;

    /**
     * @return array
     */
    public function rules()
    {
        return [
            'country_id' => 'required|int',
            'type' => 'required|in:' . Service::SERVICE_TYPE_EXPORT . ',' . Service::SERVICE_TYPE_IMPORT . ','
                . Service::SERVICE_TYPE_TRANSPORT . ',' . Service::SERVICE_TYPE_STORAGE . ',' . Service::SERVICE_TYPE_IMPORTING_RETURN_GOODS
                . ',' . Service::SERVICE_TYPE_EXTENT,
            'code' => 'required',
            'name' => 'required',
            'auto_price_by' => 'in:' . Service::SERVICE_AUTO_PRICE_BY_SIZE . ',' . Service::SERVICE_AUTO_PRICE_BY_VOLUME . ',' . Service::SERVICE_AUTO_PRICE_BY_SELLER,
            'status' => 'in:' . Service::STATUS_ACTIVE . ',' . Service::STATUS_INACTIVE
        ];
    }

    /**
     * @return Location
     */
    public function getCountry(): Location
    {
        return $this->country;
    }

    protected function customValidate()
    {
        $countryId = $this->input('country_id');
        $code      = $this->input('code');
        if (!($this->country = Location::query()->where('id', $countryId)->where('active', true)->where('type', Location::TYPE_COUNTRY)->first())) {
            $this->errors()->add('country_id', static::ERROR_EXISTS);
            return;
        }
        $checkDuplicateCode = Service::query()->where([
            'code' => $code,
            'tenant_id' => $this->user->tenant->id,
        ])->first();
        if ($checkDuplicateCode) {
            $this->errors()->add('code', static::ERROR_DUPLICATED);
            return;
        }
    }
}
