<?php

namespace Modules\Merchant\Validators;

use App\Base\Validator;
use Modules\Location\Models\Location;
use Modules\Tenant\Models\Tenant;

class RegisterMerchantExternalValidator extends Validator
{
    /**
     * @var Location
     */
    protected $location;

    /**
     * @var Tenant
     */
    protected $tenant;

    /**
     * @return array
     */
    public function rules()
    {
        return [
            'code' => 'required',
            'password' => 'required|min:6',
            'email' => 'required',
            'location' => 'required|in:vietnam,thailand',
            'phone' => 'required|regex:/^([0-9\s\-\+\(\)]*)$/|min:10',
        ];
    }

    protected function customValidate()
    {
        $this->tenant = $this->user->tenant;
        $username     = trim($this->input['code']);
        if ($this->tenant->merchants()->firstWhere('code', $username)) {
            $this->errors()->add('username', static::ERROR_ALREADY_EXIST);
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

    public function getTenant()
    {
        return $this->tenant;
    }

    public function getLocation()
    {
        return $this->location;
    }

}
