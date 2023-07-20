<?php

namespace Modules\Supplier\Validators;

use App\Base\Validator;
use Modules\Supplier\Models\Supplier;
use Modules\Tenant\Models\Tenant;

class CreateSupplierValidator extends Validator
{
    /**
     * CreateSupplierValidator constructor.
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
        ];
    }

    protected function customValidate()
    {
        $code = trim($this->input['code']);
        if ($this->tenant->suppliers()->where('code', $code)->first()) {
            $this->errors()->add('code', static::ERROR_ALREADY_EXIST);
            return;
        }
    }

}
