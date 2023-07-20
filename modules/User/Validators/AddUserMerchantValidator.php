<?php

namespace Modules\User\Validators;

use App\Base\Validator;
use Illuminate\Support\Arr;
use Modules\Tenant\Models\Tenant;

class AddUserMerchantValidator extends Validator
{
    /**
     * CreateUserMerchantValidator constructor.
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

    protected $merchants = [];

    /**
     * @return array
     */
    public function rules()
    {
        return [
            'merchant_ids' => 'array',
        ];
    }

    protected function customValidate()
    {
        $merchantIds = (array)Arr::get($this->input, 'merchant_ids', []);
        $merchantIds = array_unique($merchantIds);
        foreach ($merchantIds as $merchantId) {
            if(!$merchant = $this->tenant->merchants()->firstWhere('id', intval($merchantId))) {
                $this->errors()->add('merchant_ids', static::ERROR_NOT_EXIST);
                return;
            }
            $this->merchants[] = $merchant;
        }
    }

    /**
     * @return array
     */
    public function getMerchants()
    {
        return $this->merchants;
    }

}