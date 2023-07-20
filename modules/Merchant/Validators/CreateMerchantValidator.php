<?php

namespace Modules\Merchant\Validators;

use App\Base\Validator;
use Modules\Location\Models\Location;
use Modules\Tenant\Models\Tenant;

class CreateMerchantValidator extends Validator
{
    /**
     * CreateMerchantValidator constructor.
     * @param Tenant $tenant
     * @param array $input
     */
    public function __construct(Tenant $tenant, array $input)
    {
        $this->tenant = $tenant;
        parent::__construct($input);
    }


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
            'name' => 'required',
            'location_id' => 'required',
            'description' => '',
            'username' => '',
            'free_days_of_storage' => 'nullable'
        ];
    }

    protected function customValidate()
    {
        $code = trim($this->input['code']);
        if ($this->tenant->merchants()->firstWhere('code', $code)) {
            $this->errors()->add('code', static::ERROR_ALREADY_EXIST);
            return;
        }

        $location_id = intval($this->input['location_id']);
        $location    = Location::find($location_id);
        if (
            !$location ||
            $location->type != Location::TYPE_COUNTRY ||
            in_array($location->code, Location::INACTIVE_COUNTRY)
        ) {
            $this->errors()->add('location_id', static::ERROR_INVALID);
            return;
        }

        if (!empty($this->input['username'])) {
            $username = trim($this->input['username']);
            if ($this->tenant->merchants()->firstWhere('username', $username)) {
                $this->errors()->add('username', static::ERROR_ALREADY_EXIST);
                return;
            }
        }
    }

}
