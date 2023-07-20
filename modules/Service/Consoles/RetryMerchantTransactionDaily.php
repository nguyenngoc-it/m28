<?php

namespace Modules\Service\Consoles;

use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Modules\Document\Jobs\ProcessCodTransactionJob;
use Modules\Document\Jobs\ProcessCostOfGoodsTransactionJob;
use Modules\Document\Jobs\ProcessExportServiceTransactionJob;
use Modules\Document\Jobs\ProcessExtentServiceTransactionJob;
use Modules\Document\Jobs\ProcessImportReturnGoodsServiceTransactionJob;
use Modules\Document\Jobs\ProcessImportServiceTransactionJob;
use Modules\Document\Jobs\ProcessShippingFeeTransactionJob;
use Modules\Document\Models\DocumentFreightBillInventory;
use Modules\Transaction\Models\MerchantTransaction;
use Modules\Transaction\Models\Transaction;

class RetryMerchantTransactionDaily extends Command
{
    protected $signature = 'service:retry-merchant-transaction-daily';
    protected $description = 'Retry merchant transaction daily';

    public function handle()
    {
        $query = MerchantTransaction::query()
            ->where('amount', '>', 0)
            ->whereNull('m4_trans_id')
            ->where('created_at', '>', Carbon::now()->subMonth());
        $this->info('Start');

        $query->chunkById(100, function (Collection $merchantTransactions) {
            /** @var MerchantTransaction $merchantTransaction */
            foreach ($merchantTransactions as $merchantTransaction) {
                switch ($merchantTransaction->type) {
                    case Transaction::TYPE_EXPORT_SERVICE:
                        dispatch(new ProcessExportServiceTransactionJob($merchantTransaction->trans_id, $merchantTransaction->object_id));
                        break;
                    case Transaction::TYPE_IMPORT_SERVICE:
                        dispatch(new ProcessImportServiceTransactionJob($merchantTransaction->trans_id, $merchantTransaction->object_id));
                        break;
                    case Transaction::TYPE_IMPORT_RETURN_GOODS_SERVICE:
                        dispatch(new ProcessImportReturnGoodsServiceTransactionJob($merchantTransaction->trans_id, $merchantTransaction->object_id));
                        break;
                    case Transaction::TYPE_COD:
                        $documentFreightBillInventory = DocumentFreightBillInventory::find($merchantTransaction->object_id);
                        if ($order = $documentFreightBillInventory->order) {
                            dispatch(new ProcessCodTransactionJob($merchantTransaction->trans_id, $order->id, $merchantTransaction->object_id));
                        }
                        break;
                    case Transaction::TYPE_EXTENT:
                        $documentFreightBillInventory = DocumentFreightBillInventory::find($merchantTransaction->object_id);
                        if ($order = $documentFreightBillInventory->order) {
                            dispatch(new ProcessExtentServiceTransactionJob($merchantTransaction->trans_id, $order->id, $merchantTransaction->object_id));
                        }
                        break;
                    case Transaction::TYPE_SHIPPING:
                        dispatch(new ProcessShippingFeeTransactionJob($merchantTransaction->trans_id, $merchantTransaction->object_id));
                        break;
                    case Transaction::TYPE_COST_OF_GOODS:
                        dispatch(new ProcessCostOfGoodsTransactionJob($merchantTransaction->trans_id, $merchantTransaction->object_id));
                        break;

                }
            }
        }, 'id');

        $this->info('Done!');
    }

}
