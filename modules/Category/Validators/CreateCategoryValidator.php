<?php

namespace Modules\Category\Validators;

use App\Base\Validator;
use Modules\Category\Models\Category;
use Modules\Tenant\Models\Tenant;

class CreateCategoryValidator extends Validator
{
    /**
     * CreateCategoryValidator constructor.
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
        if ($this->tenant->categories()->firstWhere('code', $code)) {
            $this->errors()->add('code', static::ERROR_ALREADY_EXIST);
        }
    }

}