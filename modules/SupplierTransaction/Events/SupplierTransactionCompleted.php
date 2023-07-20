<?php

namespace Modules\SupplierTransaction\Events;

use App\Base\Event;
use Modules\SupplierTransaction\Models\SupplierTransaction;

class SupplierTransactionCompleted extends Event
{
    /**
     * @var SupplierTransaction
     */
    public $supplierTransaction;

    /**
     * @param SupplierTransaction $supplierTransaction
     */
    public function __construct(SupplierTransaction $supplierTransaction)
    {
        $this->supplierTransaction = $supplierTransaction;
    }

}
