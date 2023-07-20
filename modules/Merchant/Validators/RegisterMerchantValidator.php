<?php

namespace Modules\Merchant\Validators;

use App\Base\Validator;
use Modules\Location\Models\Location;
use Modules\Service;
use Modules\Tenant\Models\Tenant;

class RegisterMerchantValidator extends Validator
{
    /**
     * RegisterMerchantValidator constructor.
     * @param array $input
     */
    public function __construct(array $input)
    {
        parent::__construct($input);
    }


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
            'domain' => 'required',
            'username' => 'required',
            'password' => 'required',
            're_password' => 'required|same:password',
            'email' => 'required',
            'phone' => 'required|regex:/^([0-9\s\-\+\(\)]*)$/|min:10',
            'location_id' => 'required',
        ];
    }

    protected function customValidate()
    {
        $domain = trim($this->input('domain'));
        if (!$this->tenant = Service::tenant()->findByDomain($domain)) {
            $this->errors()->add('domain', static::ERROR_NOT_EXIST);
            return;
        }

        $username = trim($this->input['username']);
        if ($this->tenant->merchants()->firstWhere('code', $username)) {
            $this->errors()->add('username', static::ERROR_ALREADY_EXIST);
            return;
        }
        $location_id = trim($this->input['location_id']);
        $this->location = Location::find(intval($location_id));
        if(!$this->location instanceof Location) {
            $this->location = Location::query()->firstWhere('code', $location_id);
        }
        if(
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