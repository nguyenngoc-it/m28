<?php

namespace Modules\Service\Commands;

use Carbon\Carbon;
use Exception;
use Gobiz\Support\Conversion;
use Illuminate\Support\Facades\DB;
use Modules\Merchant\Models\Merchant;
use Modules\Service;
use Modules\Service\Jobs\ProcessCalculateSellerStorageFeeJob;
use Modules\Service\Models\StorageFeeMerchantStatistic;
use Modules\Service\Models\StorageFeeSkuStatistic;
use Modules\Transaction\Models\Transaction;

class UpdateStorageFeeSellerStatistic
{
    /**
     * @var array
     */
    protected $between;
    /**
     * @var Merchant
     */
    protected $merchant;

    /**
     * @param Merchant $merchant
     * @param array $between
     */
    public function __construct(Merchant $merchant, array $between = [])
    {
        $this->merchant = $merchant;
        $this->between  = $between;
    }

    /**
     * @return void
     * @throws Exception
     */
    public function handle()
    {
        $this->validator();
        $dateFrom = Carbon::parse($this->between[0]);
        $dateTo   = Carbon::parse($this->between[1]);
        while ($dateFrom < $dateTo) {
            $this->storageFeeArrear($dateFrom);
            $dateFrom = $dateFrom->addDay();
        }
    }

    /**
     * @return void
     * @throws Exception
     */
    protected function validator()
    {
        if (empty($this->between[0]) || empty($this->between[1])) {
            throw new Exception('date between empty');
        }
        if (Carbon::parse($this->between[1]) > Carbon::now()) {
            throw new Exception('date to large than now');
        }
        if (Carbon::parse($this->between[1])->diffInDays(Carbon::parse($this->between[0])) > 30) {
            throw new Exception('date range large than 30 days');
        }
    }

    protected function storageFeeArrear(Carbon $date)
    {
        $strClosingTime       = $this->merchant->closingTimeStorage();
        $closingTime          = $date->format('Y-m-d') . ' ' . $strClosingTime;
        $closingTime          = Carbon::parse($closingTime);
        $skuStorageFeeDailies = StorageFeeSkuStatistic::query()->where('merchant_id', $this->merchant->id)
            ->whereDate('closing_time', $closingTime)->get();
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

        /**
         * Truy thu phí lưu kho nếu số tiền đã thu nhỏ hơn số tiền tính toán lại
         */
        /** @var StorageFeeMerchantStatistic|null $storageFeeMerchantDaily */
        $storageFeeMerchantDay = StorageFeeMerchantStatistic::query()->where([
            'merchant_id' => $this->merchant->id,
            'closing_time' => $closingTime
        ])->sum('fee');
        if ($storageFeeMerchantDay >= $totalFees) {
            return;
        }

        $storageFeeArrear = Conversion::convertMoney($totalFees - $storageFeeMerchantDay);

        DB::transaction(function () use ($closingTime, $storageFeeArrear, $totalVolumes, $totalSkus, $snapShotSkus) {
            $storageFeeMerchantDaily           = StorageFeeMerchantStatistic::create(
                [
                    'merchant_id' => $this->merchant->id,
                    'closing_time' => $closingTime,
                    'fee' => $storageFeeArrear,
                    'total_volume' => $totalVolumes,
                    'total_sku' => $totalSkus,
                    'snapshot_skus' => $snapShotSkus
                ]
            );
            $purchaseUnits[]                   = [
                'name' => $this->merchant->id . $closingTime->format('_Y-m-d') . '_storage_fee',
                'description' => json_encode(['merchant_id' => $this->merchant->id, 'closing_time' => $closingTime->format('Y-m-d')]),
                'amount' => $storageFeeArrear,
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
        });
    }
}
