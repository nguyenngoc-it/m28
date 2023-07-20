<?php

namespace Modules\Service\Commands;

use Carbon\Carbon;
use Exception;
use Modules\Merchant\Models\Merchant;
use Modules\Service\Models\Service;
use Modules\Service\Models\ServicePrice;
use Modules\Service\Models\StorageFeeSkuStatistic;
use Modules\Stock\Models\Stock;
use Modules\Stock\Models\StockLog;

class UpdateStorageFeeSkuStatistic
{
    /**
     * @var Stock
     */
    protected $stock;
    /**
     * @var array
     */
    protected $between;
    /**
     * @var string
     */
    protected $storageFeeClosingTime;
    /**
     * @var Carbon
     */
    protected $closingTimeTarget;
    /**
     * @var Merchant
     */
    protected $merchant;

    /**
     * @param Stock $stock
     * @param string $storageFeeClosingTime
     * @param array $between
     */
    public function __construct(Stock $stock, string $storageFeeClosingTime, array $between = [])
    {
        $this->stock                 = $stock;
        $this->storageFeeClosingTime = $storageFeeClosingTime;
        $this->between               = $between;
    }

    /**
     * @return void
     * @throws Exception
     */
    public function handle()
    {
        $this->validator();
        $this->detectClosingTimeTarget();
        $freeDaysOfStorage = (is_null($this->merchant->free_days_of_storage) || $this->merchant->free_days_of_storage === '') ? 31 : $this->merchant->free_days_of_storage + 1;
        if (empty($this->between)) {
            $this->addStorageFeeSkuStatistic($freeDaysOfStorage, Carbon::now());
        } else {
            $dateFrom = Carbon::parse($this->between[0]);
            $dateTo   = Carbon::parse($this->between[1]);
            while ($dateFrom < $dateTo) {
                $this->addStorageFeeSkuStatistic($freeDaysOfStorage, $dateFrom, false);
                $dateFrom = $dateFrom->addDay();
            }
        }
    }

    /**
     * @return void
     * @throws Exception
     */
    protected function validator()
    {
        $sku = $this->stock->sku;
        if (empty($sku)) {
            throw new Exception('empty sku by stock ' . $this->stock->id);
        }
        if (empty($sku->product)) {
            throw new Exception('empty product by sku ' . $sku->code);
        }
        if (!$this->merchant = $sku->product->merchant) {
            throw new Exception('empty merchant of sku ' . $sku->code);
        }
        if (empty($sku->product->merchant->storaged_at)) {
            throw new Exception('empty storage_at merchant ' . $this->merchant->username . ' of sku ' . $sku->code);
        }
        if (empty($this->stock->warehouse)) {
            throw new Exception('empty warehouse by sku ' . $sku->code);
        }
        if (empty($sku->width * $sku->length * $sku->height)) {
            throw new Exception('empty volume by sku ' . $sku->code);
        }
        if ($this->between) {
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
    }

    /**
     * @param $freeDaysOfStorage
     * @param Carbon $date
     * @param bool $isNow
     * @return void
     */
    protected function addStorageFeeSkuStatistic($freeDaysOfStorage, Carbon $date, bool $isNow = true)
    {
        $daysOfStorage = $date->diffInDays(Carbon::parse($this->merchant->storaged_at->toDateString() . ' 00:00:00')) + 1;
        /**
         * Kiểm tra nếu seller còn được miễn phí lưu kho thì phí lưu kho bằng 0
         */
        $sku             = $this->stock->sku;
        $product         = $sku->product;
        $stockQuantity   = $this->getStockQuantity($date, $isNow);
        $volume          = round($sku->width * $sku->height * $sku->length * $stockQuantity, 4);
        $storageServices = $product->services->where('type', Service::SERVICE_TYPE_STORAGE)->where('country_id', $this->stock->warehouse->country_id);
        $storageFee      = 0;
        $servicePriceIds = [];
        $servicePrice    = 0;
        foreach ($storageServices as $storageService) {
            $storageServicePrice  = null;
            $storageServicePrices = $product->servicePrices->where('service_code', $storageService->code);
            /** @var ServicePrice $storageServicePrice */
            foreach ($storageServicePrices as $storageServicePrice) {
                if (!$storageServicePrice) {
                    continue;
                }
                $storageFee        += ($freeDaysOfStorage > $daysOfStorage) ? 0 : round($volume * $storageServicePrice->price, 2);
                $servicePriceIds[] = $storageServicePrice->id;
                $servicePrice      += $storageServicePrice->price;
            }
        }
        StorageFeeSkuStatistic::updateOrCreate(
            [
                'merchant_id' => $product->merchant_id,
                'product_id' => $product->id,
                'stock_id' => $this->stock->id,
                'closing_time' => $isNow ? $this->closingTimeTarget : $date->format('Y-m-d') . ' ' . $this->storageFeeClosingTime,
                'sku_id' => $sku->id,
                'warehouse_id' => $this->stock->warehouse_id,
                'warehouse_area_id' => $this->stock->warehouse_area_id,
            ],
            [
                'merchant_username' => $product->merchant ? $product->merchant->username : null,
                'merchant_name' => $product->merchant ? $product->merchant->name : null,
                'sku_code' => $sku->code,
                'warehouse_code' => $this->stock->warehouse ? $this->stock->warehouse->code : null,
                'warehouse_area_code' => $this->stock->warehouseArea ? $this->stock->warehouseArea->code : null,
                'service_price_ids' => $servicePriceIds,
                'service_price' => $servicePrice,
                'volume' => $volume,
                'quantity' => $this->stock->real_quantity,
                'fee' => $storageFee
            ]
        );
        /**
         * Cập nhật tổng chi phí lưu kho của sku ở 1 kho cụ thể
         */
        $totalStorageFee                = StorageFeeSkuStatistic::query()->where([
            'stock_id' => $this->stock->id,
            'sku_id' => $sku->id
        ])->sum('fee');
        $this->stock->total_storage_fee = round($totalStorageFee, 2);
        $this->stock->save();
    }

    /**
     * @param Carbon $date
     * @param bool $isNow
     * @return int
     */
    protected function getStockQuantity(Carbon $date, bool $isNow = true)
    {
        $closingTimeCarbon = $isNow ? $this->closingTimeTarget : Carbon::parse($date->format('Y-m-d') . ' ' . $this->storageFeeClosingTime);
        /** @var StockLog|null $stockLog */
        $stockLog = StockLog::query()->where('stock_id', $this->stock->id)
            ->where('created_at', '<', $closingTimeCarbon)
            ->orderBy('created_at', 'desc')->first();

        return $stockLog ? (int)$stockLog->stock_quantity : (int)$this->stock->real_quantity;
    }

    protected function detectClosingTimeTarget()
    {
        $closingTimeToday = Carbon::parse(Carbon::now()->format('Y-m-d') . ' ' . $this->storageFeeClosingTime);
        $beginToday       = Carbon::parse(Carbon::now()->format('Y-m-d') . ' 00:00:00');
        if (Carbon::now()->between($beginToday, $closingTimeToday)) {
            $this->closingTimeTarget = $closingTimeToday->subDay();
        } else {
            $this->closingTimeTarget = $closingTimeToday;
        }
    }
}
