<?php

namespace Modules\SupplierTransaction\Listeners;

use App\Base\QueueableListener;
use Modules\Supplier\Models\Supplier;
use Modules\SupplierTransaction\Events\SupplierTransactionCompleted;
use Modules\SupplierTransaction\Models\SupplierTransaction;

class SupplierTransactionCompletedListener extends QueueableListener
{
    /**
     * @param SupplierTransactionCompleted $event
     */
    public function handle(SupplierTransactionCompleted $event)
    {
        $supplier = $event->supplierTransaction->supplier;

        $supplier->total_purchased_amount = $this->sumTransactionAmount($supplier, SupplierTransaction::TYPE_IMPORT);
        $supplier->total_sold_amount      = $this->sumTransactionAmount($supplier, SupplierTransaction::TYPE_EXPORT);
        $supplier->total_paid_amount      =  $this->sumTransactionAmount($supplier, SupplierTransaction::TYPE_PAYMENT_COLLECT) - $this->sumTransactionAmount($supplier, SupplierTransaction::TYPE_PAYMENT_DEPOSIT);
        $supplier->total_returned_amount  = $this->sumTransactionAmount($supplier, SupplierTransaction::TYPE_IMPORT_BY_RETURN);
        $supplier->save();
    }

    /**
     * @param Supplier $supplier
     * @param string $transactionType
     * @return float
     */
    protected function sumTransactionAmount(Supplier $supplier, $transactionType)
    {
        return $supplier->transactions()->where('type', $transactionType)->sum('amount');
    }
}
