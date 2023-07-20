<?php

namespace Modules\Document\Jobs;

use Illuminate\Support\Facades\DB;
use Modules\Order\Models\Order;
use Modules\PurchasingPackage\Models\PurchasingPackage;
use Modules\Transaction\Jobs\ProcessTransactionJob;
use Modules\Transaction\Models\MerchantTransaction;
use Modules\Transaction\Models\Transaction;

class ProcessImportServiceTransactionJob extends ProcessTransactionJob
{
    /**
     * @var array
     */
    protected $purchasingPackageId;

    /**
     * ChargeImportServiceAmountJob constructor
     *
     * @param $transactionId
     * @param int $purchasingPackageId
     */
    public function __construct($transactionId, int $purchasingPackageId)
    {
        $this->purchasingPackageId = $purchasingPackageId;
        parent::__construct($transactionId);
    }

    public function handle()
    {
        $transaction = Transaction::find($this->transactionId)->process();

        DB::transaction(function () use ($transaction) {
            PurchasingPackage::query()
                ->where('id', $this->purchasingPackageId)
                ->update(['finance_status' => Order::FINANCE_STATUS_PAID]);
            MerchantTransaction::query()->where('trans_id', $this->transactionId)
                ->update(['m4_trans_id' => $transaction->getAttribute('response.id')]);
        });

    }
}
