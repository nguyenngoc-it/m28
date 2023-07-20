<?php

namespace Modules\SupplierTransaction\Consoles;

use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Modules\SupplierTransaction\Jobs\ProcessSupplierTransactionJob;
use Modules\SupplierTransaction\Models\SupplierTransaction;

class RetrySupplierTransactionDaily extends Command
{
    protected $signature = 'supplier:retry-supplier-transaction-daily';
    protected $description = 'Retry supplier transaction daily';

    public function handle()
    {
        $query = SupplierTransaction::query()
            ->where('amount', '>', 0)
            ->where(function (Builder $builder) {
                $builder->where(function (Builder $query1) {
                    $query1->whereNotNull('inventory_trans_id')->whereNull('inventory_m4_trans_id');
                })->orWhere(function (Builder $query2) {
                    $query2->whereNotNull('sold_trans_id')->whereNull('sold_m4_trans_id');
                });
            })
            ->where('created_at', '>', Carbon::now()->subMonth());
        $this->info('Start');

        $query->chunkById(100, function (Collection $supplierTransactions) {
            /** @var SupplierTransaction $supplierTransaction */
            foreach ($supplierTransactions as $supplierTransaction) {
                dispatch(new ProcessSupplierTransactionJob($supplierTransaction->id));
            }
        }, 'id');

        $this->info('Done!');
    }

}
