<?php

namespace Modules\Document\Jobs;

use Illuminate\Support\Facades\DB;
use Modules\Document\Models\DocumentFreightBillInventory;
use Modules\Order\Models\Order;
use Modules\Transaction\Jobs\ProcessTransactionJob;
use Modules\Transaction\Models\MerchantTransaction;
use Modules\Transaction\Models\Transaction;

class ProcessCodTransactionJob extends ProcessTransactionJob
{
    /**
     * @var int
     */
    protected $orderId;

    /**
     * @var int
     */
    protected $freightBillInventoryId;

    /**
     * ProcessCodTransactionJob constructor.
     * @param string $transactionId
     * @param int $orderId
     * @param int $freightBillInventoryId
     */
    public function __construct(string $transactionId, int $orderId, int $freightBillInventoryId)
    {
        $this->orderId                = $orderId;
        $this->freightBillInventoryId = $freightBillInventoryId;
        parent::__construct($transactionId);
    }

    public function handle()
    {
        $transaction = Transaction::find($this->transactionId)->process();
        DB::transaction(function () use ($transaction) {
            Order::query()
                ->where('id', $this->orderId)
                ->update(['finance_status' => Order::FINANCE_STATUS_PAID]);
            DocumentFreightBillInventory::query()
                ->where('id', $this->freightBillInventoryId)
                ->update([
                    'finance_status_cod' => DocumentFreightBillInventory::FINANCE_STATUS_PAID,
                ]);
            MerchantTransaction::query()->where('trans_id', $this->transactionId)
                ->update(['m4_trans_id' => $transaction->getAttribute('response.id')]);
        });
    }
}
