<?php

namespace App\Console\Commands;

use Carbon\Carbon;
use Gobiz\Redis\RedisService;
use Gobiz\Support\Conversion;
use Gobiz\Support\Helper;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\FileHelpers;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Modules\Document\Commands\CalculateBalanceMerchantWhenConfirmDocument;
use Modules\Document\Models\Document;
use Modules\Document\Models\DocumentFreightBillInventory;
use Modules\FreightBill\Models\FreightBill;
use Modules\Marketplace\Services\Marketplace;
use Modules\Merchant\Models\Merchant;
use Modules\Order\Commands\AutoInsepection;
use Modules\Order\Events\OrderShippingFinancialStatusChanged;
use Modules\Order\Models\Order;
use Modules\Order\Models\OrderSku;
use Modules\Order\Services\StatusOrder;
use Modules\OrderPacking\Commands\MappingTrackingNo;
use Modules\OrderPacking\Jobs\CreatingOrderPackingJob;
use Modules\OrderPacking\Models\OrderPacking;
use Modules\OrderPacking\Models\PickingSession;
use Modules\Product\Models\ProductServicePrice;
use Modules\Product\Models\Sku;
use Modules\PurchasingPackage\Models\PurchasingPackage;
use Modules\Service;
use Modules\Service\Commands\UpdateStorageFeeSkuStatistic;
use Modules\Service\Models\ServicePrice;
use Modules\Service\Models\StorageFeeSkuStatistic;
use Modules\ShippingPartner\Models\ShippingPartner;
use Modules\Shopee\Jobs\SyncShopeeProductJob;
use Modules\Stock\Jobs\CreateHistoryStockLogJob;
use Modules\Stock\Jobs\SyncHistoryStockLogJob;
use Modules\Stock\Models\Stock;
use Modules\Stock\Models\StockLog;
use Modules\Store\Models\Store;
use Modules\Tenant\Models\Tenant;
use Modules\Transaction\Models\MerchantTransaction;
use Modules\Transaction\Models\Transaction;
use Modules\User\Models\User;
use Modules\WarehouseStock\Jobs\CalculateWarehouseStockJob;
use Rap2hpoutre\FastExcel\FastExcel;
use Symfony\Component\HttpFoundation\File\File;
use InvalidArgumentException;

class TestCommand extends Command
{

    protected $signature = 'test';
    protected $transform = [];
    protected $description = 'Test';
    protected $service;

    public function handle()
    {
        $document = Document::find(2716);
        /**
         * trừ tiền dịch vụ vào ví seller
         */
        (new CalculateBalanceMerchantWhenConfirmDocument($document))->handle();
    }

    /**
     * @param $itemName
     * @param $model
     * @param $tierIndexs
     * @param $itemTierVariations
     * @return mixed
     */
    protected function getModelName($itemName, $model, $tierIndexs, $itemTierVariations)
    {
        $modelName = $itemName;
        foreach ($tierIndexs as $key => $index) {
            if (isset($itemTierVariations[$key])) {
                $tierVariation = $itemTierVariations[$key];
                $name          = Arr::get($tierVariation, 'name');
                $optionList    = Arr::get($tierVariation, 'option_list');
                if (isset($optionList[$index])) {
                    $name = $name . ' ' . $optionList[$index]['option'];
                }
                $modelName = $modelName . ' - ' . $name;
            }
        }
        $model['model_name'] = $modelName;

        return $model;
    }

    /**
     * @param $model
     * @return mixed
     */
    protected function getModelPrice($model)
    {
        $priceInfos              = Arr::get($model, 'price_info', []);
        $priceInfo               = Arr::first($priceInfos);
        $model['current_price']  = Arr::get($priceInfo, 'current_price', 0);
        $model['original_price'] = Arr::get($priceInfo, 'original_price', 0);

        return $model;
    }


    /**
     * @param Store $store
     * @param Stock $stock
     */
    protected function syncStockShopee(Store $store, Stock $stock)
    {
        $locationIds = [];
        try {
            $warehouseDetail = Service::shopee()->getWarehouseDetail($store);
            print_r($warehouseDetail);

            echo '-------------------------';


            if (!empty($warehouseDetail['response'])) {
                foreach ($warehouseDetail['response'] as $item) {
                    $locationIds[] = $item['location_id'];
                }
            }
        } catch (\Exception $exception) {
            print_r('getWarehouseDetailShopee error ' . $exception->getMessage() . ' - ' . $exception->getFile() . ' - ' . $exception->getLine());
        }

        try {
            $params   = [];
            $response = Service::shopee()->updateStock($store, $stock, $locationIds, $params);

            print_r(compact('params', 'response'));

        } catch (\Exception $exception) {
            print_r('sync Stock Shopee error ' . $exception->getMessage() . ' - ' . $exception->getFile() . ' - ' . $exception->getLine());
        }
    }

    public function createShopeeDocument()
    {
        Order::query()->where('marketplace_code', 'SHOPEE')
            ->whereIn('status', [Order::STATUS_WAITING_PACKING, Order::STATUS_WAITING_PICKING])
            ->chunkById(100, function ($orders) {
                /** @var Order $order */
                foreach ($orders as $order) {
                    dispatch(new \Modules\Shopee\Jobs\ShopeeCreateShippingDocumentJob($order->id));
                    $this->info('done ' . $order->code);
                }
            });
    }

    protected function updateStorageFeeSkus()
    {
        Service\Models\StorageFeeSkuStatistic::query()->chunkById(100, function ($storageFeeSkuStatistics) {
            /** @var Service\Models\StorageFeeSkuStatistic $storageFeeSkuStatistic */
            foreach ($storageFeeSkuStatistics as $storageFeeSkuStatistic) {
                $storageFeeSkuStatistic->merchant_username   = $storageFeeSkuStatistic->merchant ? $storageFeeSkuStatistic->merchant->username : null;
                $storageFeeSkuStatistic->merchant_name       = $storageFeeSkuStatistic->merchant ? $storageFeeSkuStatistic->merchant->name : null;
                $storageFeeSkuStatistic->sku_code            = $storageFeeSkuStatistic->sku ? $storageFeeSkuStatistic->sku->code : null;
                $storageFeeSkuStatistic->warehouse_code      = $storageFeeSkuStatistic->warehouse ? $storageFeeSkuStatistic->warehouse->code : null;
                $storageFeeSkuStatistic->warehouse_area_code = $storageFeeSkuStatistic->warehouseArea ? $storageFeeSkuStatistic->warehouseArea->code : null;
                /** @var Service\Models\ServicePrice $servicePrice */
                foreach ($storageFeeSkuStatistic->servicePrices() as $servicePrice) {
                    $storageFeeSkuStatistic->service_price += $servicePrice->price;
                }
                $storageFeeSkuStatistic->save();
                $this->info('update for id ' . $storageFeeSkuStatistic->id);
            }
        }, 'id');
    }

}
