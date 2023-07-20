<?php

namespace Modules\Service\Consoles;

use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Modules\Service\Jobs\ProcessCalculateSellerStorageFeeJob;
use Modules\Service\Models\StorageFeeMerchantStatistic;

class RetryTransactionStorageFeeDaily extends Command
{
    protected $signature = 'service:retry-transaction-storage-fee-daily';
    protected $description = 'Retry transaction storage fee daily';

    public function handle()
    {
        $query = StorageFeeMerchantStatistic::query()
            ->where('fee', '>', 0)
            ->whereNull('trans_m4_id')
            ->where('created_at', '>', Carbon::now()->subMonth());
        $this->info('Start');

        $query->chunkById(100, function (Collection $storageFeeMerchantStatistics) {
            /** @var StorageFeeMerchantStatistic $storageFeeMerchantStatistic */
            foreach ($storageFeeMerchantStatistics as $storageFeeMerchantStatistic) {
                dispatch(new ProcessCalculateSellerStorageFeeJob($storageFeeMerchantStatistic->trans_id));
                $this->info('retry transaction storage fee for merchant ' . $storageFeeMerchantStatistic->merchant->code);
            }
        }, 'id');

        $this->info('Done!');
    }

}
