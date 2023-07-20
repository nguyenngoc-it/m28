<?php

namespace Modules\Document\Jobs;

use Illuminate\Support\Facades\DB;
use Modules\Order\Models\Order;
use Modules\Transaction\Jobs\ProcessTransactionJob;
use Modules\Transaction\Models\MerchantTransaction;
use Modules\Transaction\Models\Transaction;

class ProcessImportReturnGoodsServiceTransactionJob extends ProcessTransactionJob
{
    /**
     * @var int
     */
    protected $orderId;

    /**
     * ChargeImportServiceAmountJob constructor
     *
     * @param $transactionId
     * @param int $orderId
     */
    public function __construct($transactionId, int $orderId)
    {
        $this->orderId = $orderId;
        parent::__construct($transactionId);
    }

    public function handle()
    {
        $transaction = Transaction::find($this->transactionId)->process();

        DB::transaction(function () use ($transaction) {
            Order::query()
                ->where('id', $this->orderId)
                ->update(['finance_service_import_return_goods_status' => Order::FINANCE_STATUS_PAID]);
            MerchantTransaction::query()->where('trans_id', $this->transactionId)
                ->update(['m4_trans_id' => $transaction->getAttribute('response.id')]);
        });
    }
}
