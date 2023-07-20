<?php

namespace Modules\Merchant\Validators;

use App\Base\Validator;
use Modules\Location\Models\Location;
use Modules\Merchant\Models\Merchant;

class UpdateMerchantValidator extends Validator
{
    /**
     * UpdateMerchantValidator constructor.
     * @param Merchant $merchant
     * @param array $input
     */
    public function __construct(Merchant $merchant, array $input)
    {
        $this->merchant = $merchant;
        parent::__construct($input);
    }


    /**
     * @var Merchant
     */
    protected $merchant;


    /**
     * @return array
     */
    public function rules()
    {
        return [
            'name' => 'required',
            'code' => 'required',
            'location_id' => 'required',
            'description' => '',
            'username' => '',
            'free_days_of_storage' => 'nullable'
        ];
    }


    protected function customValidate()
    {
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

        $username = isset($this->input['username']) ? trim($this->input['username']) : "";
        if (
            $username && $this->merchant->username != $username &&
            (
                Merchant::query()->where('tenant_id', $this->merchant->tenant_id)
                    ->where('id', '!=', $this->merchant->id)
                    ->where('username', $username)->count() > 0
            )
        ) {
            $this->errors()->add('username', static::ERROR_ALREADY_EXIST);
            return;
        }
    }
}
