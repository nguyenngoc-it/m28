<?php

namespace Modules\User\Validators;

use App\Base\Validator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Arr;
use Modules\Supplier\Models\Supplier;
use Modules\Tenant\Models\Tenant;

class AddUserSupplierValidator extends Validator
{
    /**
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
     * @var Supplier[]|Collection
     */
    protected $suppliers;

    /**
     * @return array
     */
    public function rules()
    {
        return [
            'supplier_ids' => 'array',
        ];
    }

    protected function customValidate()
    {
        $supplierIds = (array)Arr::get($this->input, 'supplier_ids', []);
        $supplierIds = array_unique($supplierIds);
        $this->suppliers = $this->tenant->suppliers()->whereIn('id', $supplierIds)->get();
        if(count($supplierIds) != $this->suppliers->count()) {
            $this->errors()->add('supplier_ids', static::ERROR_NOT_EXIST);
            return;
        }
    }

    /**
     * @return Supplier[]
     */
    public function getSuppliers()
    {
        return $this->suppliers;
    }

}
