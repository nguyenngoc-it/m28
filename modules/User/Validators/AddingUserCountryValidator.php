<?php

namespace Modules\User\Validators;

use App\Base\Validator;
use Modules\Service;

class AddingUserCountryValidator extends Validator
{
    /**
     * @return array
     */
    public function rules()
    {
        return [
            'country_ids' => 'array',
        ];
    }

    protected function customValidate()
    {
        $countryIds      = $this->input('country_ids', []);
        $countryIds      = array_unique($countryIds);
        $activeCountries = Service::location()->activeCountries();
        foreach ($countryIds as $countryId) {
            if (!$country = $activeCountries->where('id', $countryId)->first()) {
                $this->errors()->add('country_ids', static::ERROR_NOT_EXIST);
                return;
            }
        }
    }
}
