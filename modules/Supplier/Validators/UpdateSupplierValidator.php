<?php

namespace Modules\Supplier\Validators;

use App\Base\Validator;
use Modules\Location\Models\Location;
use Modules\Supplier\Models\Supplier;

class UpdateSupplierValidator extends Validator
{
    /**
     * UpdateSupplierValidator constructor.
     * @param Supplier $supplier
     * @param array $input
     */
    public function __construct(Supplier $supplier, array $input)
    {
        $this->supplier = $supplier;
        parent::__construct($input);
    }


    /**
     * @var Supplier
     */
    protected $supplier;

    /**
     * @return array
     */
    public function rules()
    {
        return [
            'name' => 'required',
        ];
    }
}
