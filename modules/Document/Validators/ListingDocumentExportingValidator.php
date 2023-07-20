<?php

namespace Modules\Document\Validators;

use App\Base\Validator;
use Modules\Document\Models\Document;
use Modules\OrderExporting\Models\OrderExporting;
use Modules\Warehouse\Models\Warehouse;

class ListingDocumentExportingValidator extends Validator
{
    /** @var Document */
    protected $documentPacking;
    /** @var Warehouse */
    protected $warehouse;

    public static $keyRequests = [
        'sort',
        'sortBy',
        'id',
        'code',
        'warehouse_id',
        'verifier_id',
        'creator_id',
        'created_at',
        'verified_at',
        'status',
        'page',
        'per_page'
    ];

    public function rules()
    {
        return [
            'tenant_id' => 'required|int',
            'warehouse_id' => 'int',
            'code' => 'string',
            'status' => 'string',
            'verifier_id' => 'int',
            'verified_at' => 'array',
            'created_at' => 'array',
        ];
    }

    /**
     * @return Document
     */
    public function getDocumentPacking(): Document
    {
        return $this->documentPacking;
    }

    /**
     * @return Warehouse
     */
    public function getWarehouse(): Warehouse
    {
        return $this->warehouse;
    }

    protected function customValidate()
    {
        $warehouseId = $this->input('warehouse_id', 0);
        if ($warehouseId && !$this->warehouse = Warehouse::find($warehouseId)) {
            $this->errors()->add('warehouse_id', static::ERROR_EXISTS);
            return;
        }
    }
}
