<?php

namespace Modules\Transaction\Commands;

use InvalidArgumentException;
use Modules\Supplier\Models\Supplier;
use Modules\SupplierTransaction\Models\SupplierTransaction;
use Modules\Transaction\Services\SupplierTransObjInterface;

class SupplierTransCreating
{
    /**
     * @var Supplier
     */
    protected $supplier;
    /**
     * @var float
     */
    protected $amount;
    /**
     * @var string
     */
    protected $type;
    /**
     * @var string
     */
    protected $object_type;
    /**
     * @var string
     */
    protected $object_id;
    /**
     * @var array
     */
    protected $metadata;

    /**
     * @var string
     */
    protected $transId;

    public function __construct(Supplier $supplier, string $transType, float $amount, SupplierTransObjInterface $supplierTransObj)
    {
        if (empty($amount)) {
            throw new InvalidArgumentException('Amount is empty!!!');
        }
        $this->supplier    = $supplier;
        $this->amount      = $amount;
        $this->type        = $transType;
        $this->object_type = $supplierTransObj->getObjectType();
        $this->object_id   = $supplierTransObj->getObjectId();
    }

    /**
     * @return SupplierTransaction
     */
    public function create()
    {
        return SupplierTransaction::create([
            'tenant_id' => $this->supplier->tenant_id,
            'supplier_id' => $this->supplier->id,
            'type' => $this->type,
            'object_type' => $this->object_type,
            'object_id' => $this->object_id,
            'amount' => $this->amount,
            'metadata' => $this->metadata
        ]);
    }

    /**
     * @param array $metadata
     * @return $this
     */
    public function withMeta(array $metadata)
    {
        $this->metadata = $metadata;
        return $this;
    }
}
