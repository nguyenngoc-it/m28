<?php

namespace Modules\Document\Jobs;

use Illuminate\Support\Facades\DB;
use Modules\Document\Models\DocumentFreightBillInventory;
use Modules\Transaction\Jobs\ProcessTransactionJob;
use Modules\Transaction\Models\MerchantTransaction;
use Modules\Transaction\Models\Transaction;

class ProcessShippingFeeTransactionJob extends ProcessTransactionJob
{
    /**
     * @var int
     */
    protected $freightBillInventoryId;

    /**
     * ProcessShippingFeeTransactionJob constructor.
     * @param string $transactionId
     * @param int $freightBillInventoryId
     */
    public function __construct(string $transactionId, int $freightBillInventoryId)
    {
        $this->freightBillInventoryId = $freightBillInventoryId;
        parent::__construct($transactionId);
    }

    public function handle()
    {
        $transaction = Transaction::find($this->transactionId)->process();

        DB::transaction(function () use ($transaction) {
            DocumentFreightBillInventory::query()
                ->where('id', $this->freightBillInventoryId)
                ->update([
                    'finance_status_fee' => DocumentFreightBillInventory::FINANCE_STATUS_PAID,
                ]);
            MerchantTransaction::query()->where('trans_id', $this->transactionId)
                ->update(['m4_trans_id' => $transaction->getAttribute('response.id')]);
        });
    }
}
