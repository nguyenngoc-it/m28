<?php

namespace Modules\OrderPacking\Validators;

use App\Base\Validator;
use Modules\OrderPacking\Models\OrderPacking;
use Modules\Tenant\Models\Tenant;
use Modules\Warehouse\Models\Warehouse;

class OrderPackingImportBarcodeValidator extends Validator
{
    /**
     * @var Tenant
     */
    protected $tenant;

    /**
     * @var Warehouse
     */
    protected $warehouse;


    /**
     * OrderPackingImportBarcodeValidator constructor.
     * @param Tenant $tenant
     * @param array $input
     */
    public function __construct(Tenant $tenant, array $input = [])
    {
        $this->tenant = $tenant;
        parent::__construct($input);
    }

    /**
     * @return array
     */
    public function rules()
    {
        return [
            'warehouse_id' => 'required',
            'barcode_type' => 'required|in:' . implode(",", [OrderPacking::SCAN_TYPE_ORDER, OrderPacking::SCAN_TYPE_FREIGHT_BILL]),
            'file' => 'required|file|mimes:' . config('upload.mimes') . '|max:' . config('upload.max_size'),
        ];
    }

    protected function customValidate()
    {
        if (!$this->warehouse = $this->tenant->warehouses()->find($this->input['warehouse_id'])) {
            $this->errors()->add('warehouse_id', static::ERROR_EXISTS);
            return false;
        }
    }

    /**
     * @return Warehouse
     */
    public function getWarehouse()
    {
        return $this->warehouse;
    }
 }
