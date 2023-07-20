<?php

namespace Modules\Merchant\Validators;

use App\Base\Validator;
use Modules\Location\Models\Location;
use Modules\Merchant\Models\Merchant;

class RegisterMerchantExternalValidatorNew extends Validator
{

    /**
     * @var Location
     */
    protected $location;

    public function rules()
    {
        return [
            'location' => 'required',
            'phone' => 'regex:/^([0-9\s\-\+\(\)]*)$/|min:10',
            'user_name' => 'required',
            'name' => 'required',
            'code' => 'required',
            'status' => 'required'
        ];
    }

    public function customValidate()
    {
        $userName = data_get($this->input, 'user_name');
        $merchant = Merchant::query()->where('username', $userName)->first();
        if ($merchant) {
            $this->errors()->add('username', static::ERROR_INVALID);
            return;
        }
        $locationCode   = trim($this->input['location']);
        $this->location = Location::find(intval($locationCode));
        if (!$this->location instanceof Location) {
            $this->location = Location::query()->firstWhere('code', $locationCode);
        }
        if (
            !$this->location ||
            $this->location->type != Location::TYPE_COUNTRY ||
            in_array($this->location->code, Location::INACTIVE_COUNTRY) ||
            !in_array($this->location->code, Location::MERCHANT_ACTIVE_COUNTRY)
        ) {
            $this->errors()->add('location', static::ERROR_INVALID);
            return;
        }
    }
}
