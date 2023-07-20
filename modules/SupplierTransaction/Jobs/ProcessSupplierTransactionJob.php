<?php

namespace Modules\SupplierTransaction\Jobs;

use App\Base\Job;
use Modules\SupplierTransaction\Events\SupplierTransactionCompleted;
use Modules\SupplierTransaction\Models\SupplierTransaction;
use Modules\Transaction\Models\Transaction;

class ProcessSupplierTransactionJob extends Job
{
    public $queue = 'transaction';

    /**
     * @var int
     */
    protected $supplierTransactionId;

    /**
     * @param int $supplierTransactionId
     */
    public function __construct(int $supplierTransactionId)
    {
        $this->supplierTransactionId = $supplierTransactionId;
    }

    public function handle()
    {
        $supplierTransaction = SupplierTransaction::find($this->supplierTransactionId);
        $shouldDispathEvent = false;

        if($supplierTransaction->sold_trans_id && !$supplierTransaction->sold_m4_trans_id) {
            $transaction = Transaction::find($supplierTransaction->sold_trans_id)->process();

            $supplierTransaction->sold_m4_trans_id = $transaction->getAttribute('response.id');
            $supplierTransaction->save();
            $shouldDispathEvent = true;
        }

        if($supplierTransaction->inventory_trans_id && !$supplierTransaction->inventory_m4_trans_id) {
            $transaction = Transaction::find($supplierTransaction->inventory_trans_id)->process();

            $supplierTransaction->inventory_m4_trans_id = $transaction->getAttribute('response.id');
            $supplierTransaction->save();
            $shouldDispathEvent = true;
        }
        if ($shouldDispathEvent){
            (new SupplierTransactionCompleted($supplierTransaction))->queue();
        }
    }
}
