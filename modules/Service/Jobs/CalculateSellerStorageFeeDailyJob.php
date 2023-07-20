<?php

namespace Modules\Service\Jobs;

use App\Base\Job;
use Carbon\Carbon;
use Exception;
use Gobiz\Log\LogService;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Modules\Merchant\Models\Merchant;
use Modules\Product\Models\Sku;
use Modules\Service;
use Modules\Service\Commands\UpdateStorageFeeSkuStatistic;
use Modules\Service\Models\StorageFeeMerchantStatistic;
use Modules\Service\Models\StorageFeeSkuStatistic;
use Modules\Transaction\Models\Transaction;

class CalculateSellerStorageFeeDailyJob extends Job implements ShouldBeUnique
{
    public $queue = 'service_price';
    protected $merchant;
    /**
     * @var Carbon
     */
    protected $closingTime;
    /** @var string */
    protected $strClosingTime;


    public function __construct(Merchant $merchant, Carbon $closingTime)
    {
        $this->merchant       = $merchant;
        $this->closingTime    = $closingTime;
        $this->strClosingTime = $closingTime->format('H:i:s');
    }

    /**
     * @return string
     */
    public function uniqueId()
    {
        return 'storage_fee_daily_seller_' . $this->merchant->id;
    }

    public function handle()
    {
        /**
         * Không thu phí lưu kho nếu closingTime đã có bản ghi
         */
        /** @var StorageFeeMerchantStatistic|null $storageFeeMerchantDaily */
        $storageFeeMerchantDaily = StorageFeeMerchantStatistic::query()->where([
            'merchant_id' => $this->merchant->id,
            'closing_time' => $this->closingTime
        ])->first();
        if ($storageFeeMerchantDaily) {
            return;
        }

        $this->merchant->skus()->with([
            'product',
            'product.services',
            'stocks',
        ])->chunkById(100, function (Collection $skus) {
            /** @var Sku $sku */
            foreach ($skus as $sku) {
                foreach ($sku->stocks as $stock) {
                    try {
                        (new UpdateStorageFeeSkuStatistic($stock, $this->strClosingTime))->handle();
                    } catch (Exception $exception) {
                        LogService::logger('sku-storage-fee-daily')
                            ->error('merchant ' . $this->merchant->username . ' ' . $exception->getMessage());
                        continue;
                    }
                }
            }
        });

        $this->storageFeeSellerStatistic();
    }

    protected function storageFeeSellerStatistic()
    {
        $skuStorageFeeDailies = StorageFeeSkuStatistic::query()->where('merchant_id', $this->merchant->id)
            ->where('closing_time', $this->closingTime)->get();
        $snapShotSkus         = [];
        $totalFees            = $totalSkus = $totalVolumes = 0;
        /** @var StorageFeeSkuStatistic $skuStorageFeeDaily */
        foreach ($skuStorageFeeDailies as $skuStorageFeeDaily) {
            $snapShotSkus[] = [
                'stock_id' => $skuStorageFeeDaily->stock_id,
                'quantity' => $skuStorageFeeDaily->quantity,
                'fee' => $skuStorageFeeDaily->fee
            ];
            $totalFees      += $skuStorageFeeDaily->fee;
            $totalSkus      += $skuStorageFeeDaily->quantity;
            $totalVolumes   += $skuStorageFeeDaily->volume;
        }

        DB::transaction(function () use ($totalFees, $totalSkus, $totalVolumes, $snapShotSkus) {
            $storageFeeMerchantDaily = StorageFeeMerchantStatistic::create(
                [
                    'merchant_id' => $this->merchant->id,
                    'closing_time' => $this->closingTime,
                    'fee' => $totalFees,
                    'total_volume' => $totalVolumes,
                    'total_sku' => $totalSkus,
                    'snapshot_skus' => $snapShotSkus
                ]
            );
            if ($totalFees) {
                $purchaseUnits[]                   = [
                    'name' => $this->merchant->id . $this->closingTime->format('Y-m-d') . '_storage_fee',
                    'description' => json_encode(['merchant_id' => $this->merchant->id, 'closing_time' => $this->closingTime->format('Y-m-d')]),
                    'amount' => $totalFees,
                    'customType' => Transaction::TYPE_STORAGE_FEE,
                ];
                $transaction                       = Service::transaction()->create(
                    Merchant::find($this->merchant->id),
                    Transaction::ACTION_COLLECT,
                    ['purchaseUnits' => $purchaseUnits]
                );
                $storageFeeMerchantDaily->trans_id = $transaction->_id;
                $storageFeeMerchantDaily->save();

                dispatch(new ProcessCalculateSellerStorageFeeJob($transaction->_id));
            }
        });
    }
}
