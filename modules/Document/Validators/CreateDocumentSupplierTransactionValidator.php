<?php

namespace Modules\Document\Validators;

use App\Base\Validator;
use Modules\Service;
use Modules\Supplier\Models\Supplier;

class CreateDocumentSupplierTransactionValidator extends Validator
{
    /** @var Supplier */
    protected $supplier;

    public function rules()
    {
        return [
            'tenant_id' => 'required|int',
            'supplier_id' => 'required|int',
            'amount' => 'required|numeric',
            'payment_time' => 'required|string',
            'transaction_code' => 'required|string',
            'note' => 'required|string'
        ];
    }

    /**
     * @return Supplier
     */
    public function getSupplier(): Supplier
    {
        return $this->supplier;
    }

    protected function customValidate()
    {
        $tenant     = Service::tenant()->find($this->input('tenant_id'));
        $supplierId = $this->input('supplier_id');
        if (!$this->supplier = $tenant->suppliers()->find($supplierId)) {
            $this->errors()->add('supplier_id', self::ERROR_INVALID);
            return;
        }

        $amount = floatval($this->input('amount'));
        if($amount == 0) {
            $this->errors()->add('amount', self::ERROR_INVALID);
            return;
        }
    }
}
