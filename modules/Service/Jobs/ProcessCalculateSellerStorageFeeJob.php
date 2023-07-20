<?php

namespace Modules\Service\Jobs;

use Modules\Service\Models\StorageFeeMerchantStatistic;
use Modules\Transaction\Jobs\ProcessTransactionJob;
use Modules\Transaction\Models\Transaction;

class ProcessCalculateSellerStorageFeeJob extends ProcessTransactionJob
{
    /**
     * @var StorageFeeMerchantStatistic
     */
    protected $storageFeeMerchantStatistic;

    /**
     * ChargeImportServiceAmountJob constructor
     *
     * @param string $transactionId
     */
    public function __construct(string $transactionId)
    {
        parent::__construct($transactionId);
        $this->storageFeeMerchantStatistic = StorageFeeMerchantStatistic::query()->firstWhere('trans_id', $transactionId);
    }

    public function handle()
    {
        $transaction = Transaction::find($this->transactionId)->process();

        StorageFeeMerchantStatistic::query()
            ->where('id', $this->storageFeeMerchantStatistic->id)
            ->update([
                'fee_paid' => $this->storageFeeMerchantStatistic->fee,
                'trans_m4_id' => $transaction->getAttribute('response.id')
            ]);
    }
}
