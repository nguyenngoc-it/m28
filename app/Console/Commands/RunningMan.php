<?php

namespace App\Console\Commands;

use Box\Spout\Common\Exception\IOException;
use Box\Spout\Common\Exception\UnsupportedTypeException;
use Box\Spout\Reader\Exception\ReaderNotOpenedException;
use Carbon\Carbon;
use Exception;
use Gobiz\Log\LogService;
use Gobiz\Support\RestApiException;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Arr;
use Modules\Document\Jobs\ProcessImportServiceTransactionJob;
use Modules\Document\Models\Document;
use Modules\KiotViet\Jobs\SyncKiotVietFreightBillJob;
use Modules\Lazada\Jobs\SyncLazadaFreightBillJob;
use Modules\Location\Models\Location;
use Modules\Marketplace\Services\Marketplace;
use Modules\Marketplace\Services\MarketplaceException;
use Modules\Merchant\Models\Merchant;
use Modules\Order\Models\Order;
use Modules\Order\Services\OrderEvent;
use Modules\Product\Models\Product;
use Modules\Product\Models\ProductMerchant;
use Modules\Product\Models\ProductServicePrice;
use Modules\Product\Models\Sku;
use Modules\PurchasingPackage\Models\PurchasingPackage;
use Modules\PurchasingPackage\Models\PurchasingPackageService;
use Modules\Service;
use Modules\Service\Models\ServicePrice;
use Modules\Service\Models\StorageFeeSkuStatistic;
use Modules\Shopee\Jobs\SyncShopeeProductJob;
use Modules\Stock\Jobs\SyncHistoryStockLogJob;
use Modules\Stock\Models\Stock;
use Modules\Stock\Models\StockLog;
use Modules\Store\Models\Store;
use Modules\Store\Models\StoreSku;
use Modules\Tenant\Models\Tenant;
use Modules\TikTokShop\Jobs\SyncTikTokShopFreightBillJob;
use Modules\Transaction\Models\Transaction;
use Modules\Warehouse\Models\Warehouse;
use Rap2hpoutre\FastExcel\FastExcel;

/**
 * Chạy những xử lý tức thời mà chưa biết nhu cầu dùng lại nhiều hay không
 *
 * 1. Cập nhật kích thước, cân nặng cho skus qua file excel - updateSizeSkusByFile
 * 2. Cập nhật thông tin liên quan cho bảng thống kê phí lưu kho - updateRelatedInfoStorageFeeSku
 * 3. Cập nhật tồn cho những đơn bị huỷ khi đã xuất khỏi kho (đơn shoppe) - updateStockWhenCancelOrder
 * 4. Bổ xung thông tin bảng phí lưu kho theo skus - updateStorageFeeSkus
 * 5. Cập nhật và truy thu phí nhập kho của những kiện nhập chưa áp dụng toàn bộ sản phẩm - collectImporedServiceArrears
 * 6. Gán dịch vụ bắt buộc nhập cho sản phẩm thiếu - updateProductServicePrice
 * 7.
 * 8. Đồng bộ lại toàn bộ sản phẩm của 1 kênh - syncProductMarket
 * 9. Cập nhật tồn cho những chứng từ nhập đã thực hiện sai tồn - updateStockByImportDocument
 * 10. Tạo thêm thị trường Malaysia - addLocations
 * 11. Cập nhật lịch sử tồn kho của stocks theo tenant và seller - SyncHistoryStockLog
 * 12. Chạy lại phí tồn kho theo ngày, tenant, seller - storageFeeArrear
 * 13. Mapping skus theo danh sách file excel cho Velaone - mappingSkuForMerchantFromExcel
 * 14. Mapping product merchant theo danh sách file excel cho Velaone - mappingProductForMerchantFromExcel
 * 15. Debug lấy thông tin cụ thể của 1 order number trên lazada - debugLazadaGetDataOrder
 *
 * Class RunningMan
 * @package App\Console\Commands
 */
class RunningMan extends Command
{
    protected $signature = 'running_man {method} {--order_id=} {--tenant_id=5 : 1|6|7|8|11|12} {--source=SHOPEE : 6|8} {--document_ids=0,1,2 : 9}
     {--seller_code= : 11|12} {--from_day= : 12} {--to_day= : 12} {--lazada_store_id=} {--lazada_order_number=}';
    protected $description = 'Chạy những xử lý tức thời mà chưa biết nhu cầu dùng lại nhiều hay không';

    /**
     */
    public function handle()
    {
        $this->runMethod();
    }

    protected function runMethod()
    {
        $method = $this->argument('method');
        if ($method) {
            $this->{$method}();
        }
    }

    /**
     * 15. Debug lấy thông tin cụ thể của 1 order number trên lazada
     * @return void
     * @throws Exception
     */
    protected function debugLazadaGetDataOrder()
    {
        $logger = LogService::logger('lazada-debug', [
            'context' => [],
        ]);
        $lazadaStoreId     = $this->option('lazada_store_id');
        $lazadaOrderNumber = $this->option('lazada_order_number');

        $store = Store::where('marketplace_code', Marketplace::CODE_LAZADA)
                        ->where('marketplace_store_id', $lazadaStoreId)
                        ->first();
        
        if ($store) {
            $lazadaApi = Service::lazada()->api();

            $params = [
                'order_id' => $lazadaOrderNumber,
                'access_token' => $store->getSetting('access_token')
            ];
            $dataOrder = $lazadaApi->getOrderDetails($params)->getData();

            // Log debug data get from Lazada
            $logger->debug('lazada get data debug', ['data' => $dataOrder]);
        }
    }

     /**
     * 14. Mapping skus theo danh sách file excel cho Velaone
     * @return void
     * @throws Exception
     */
    protected function mappingProductForMerchantFromExcel()
    {
        $logger = LogService::logger("mapping-product-velaone", []);
        $tenantId = 1; // Velaone
        $filePath = storage_path('velaone_skus_mapping.csv');
        $items    = (new FastExcel)->sheet(1)->import($filePath);
        foreach ($items as $item) {
            $item = array_values($item);

            $productCode  = trim(data_get($item, 0));
            $merchantCode = trim(data_get($item, 2));

            // Check merchant code
            $merchant = Merchant::where('tenant_id', $tenantId)
                                ->where('code', $merchantCode)
                                ->first();

            $product = Product::where('tenant_id', $tenantId)
                                ->where('code', $productCode)
                                ->whereNull('source')
                                ->first();
            
            if ($product && $merchant) {
                // Kiểm tra xem map chưa
                $productMerchant = ProductMerchant::where('product_id', $product->id)
                                                   ->where('merchant_id', $merchant->id)
                                                   ->first();
                if (!$productMerchant) {
                    $dataProductMerchant = [
                        'product_id'  => $product->id,
                        'merchant_id' => $merchant->id,
                    ];
                    $productMerchantCreated = ProductMerchant::create($dataProductMerchant);
                    if ($productMerchantCreated) {
                        $logger->info('data-mapping', $productMerchantCreated->toArray());
                        $this->info('Update Success Mapping Product Code: "' . $productCode . '" With Merchant Code: "' . $merchantCode . '"');
                    }
                }
            } else {
                $this->error("Product Code: {$productCode} or Merchant Code: {$merchantCode} not match");
            }
        }
    }

    /**
     * 13. Mapping skus theo danh sách file excel cho Velaone
     * @return void
     * @throws Exception
     */
    protected function mappingSkuForMerchantFromExcel()
    {
        $tenantId = 1; // Velaone
        $filePath = storage_path('velaone_skus_mapping.csv');
        $items    = (new FastExcel)->sheet(1)->import($filePath);
        foreach ($items as $item) {
            $item = array_values($item);

            $skuCode      = data_get($item, 0);
            $merchantCode = data_get($item, 1);

            // Check merchant code
            $merchant = Merchant::where('tenant_id', $tenantId)
                                ->where('code', $merchantCode)
                                ->first();
            if ($merchant) {
                // Update Skus
                $sku = Sku::select('skus.*')
                            ->leftJoin('store_skus', 'store_skus.sku_id', 'skus.id')
                            ->leftJoin('product_merchants', 'product_merchants.product_id', 'skus.product_id')
                            ->where(function($query) use ($skuCode){
                                return $query->where('store_skus.code', $skuCode)
                                                ->orWhere('skus.code', $skuCode);
                            })
                            ->where(function($query) use ($merchant) {
                                return $query->where('skus.merchant_id', $merchant->id)
                                                ->orWhere('product_merchants.merchant_id', $merchant->id);
                            })
                            ->first();
                if ($sku) {

                    if ($sku->tenant_id == $tenantId) {

                        $sku->merchant_id = $merchant->id;
                        try {
                            $sku->save();
                        } catch (Exception $exception) {
                            $this->error('Error Not Match');
                        }


                        $product = $sku->product;
                        $product->merchant_id = $merchant->id;
                        try {
                            $product->save();
                        } catch (Exception $exception) {
                            $this->error('Error Not Match');
                        }

                        $this->info('Update Success Mapping Sku Code: "' . $skuCode . '" With Merchant Code: "' . $merchantCode . '"');

                    } else {
                        $this->error('Sku Code: "' . $skuCode . ' Not Map With Merchant Code: "' . $merchantCode . '"');
                    }

                } else {
                    $this->error('Sku Code: "' . $skuCode . ' Not Exist');
                }

            } else {
                $this->error('Merchant Code: "' . $merchantCode . '" Not Exist');
            }
        }
    }

    /**
     * 12. Chạy lại phí tồn kho theo ngày, tenant, seller
     * @return void
     * @throws Exception
     */
    protected function storageFeeArrear()
    {
        $tenantId   = $this->option('tenant_id');
        $sellerCode = $this->option('seller_code');
        $fromDay    = $this->option('from_day');
        $toDay      = $this->option('to_day');
        if (empty($tenantId) || empty($sellerCode) || empty($fromDay) || empty($toDay)) {
            $this->error('input empty!');
        }
        $betweenDays = [$fromDay, $toDay];

        dispatch(new Service\Jobs\UpdateStorageFeeSellerJob($tenantId, $sellerCode, $betweenDays));
    }

    /**
     * 11. Cập nhật lịch sử tồn kho của stocks theo tenant và seller
     */
    protected function SyncHistoryStockLog()
    {
        $tenantId   = $this->option('tenant_id');
        $sellerCode = $this->option('seller_code');
        $query      = Stock::query();
        if ($tenantId) {
            $query->where('stocks.tenant_id', $tenantId);
            if ($sellerCode) {
                $merchant = Merchant::query()->where([
                    'code' => $sellerCode,
                    'tenant_id' => $tenantId
                ])->first();
                if ($merchant instanceof Merchant) {
                    $query->join('skus', 'stocks.sku_id', 'skus.id');
                    $query->where('skus.merchant_id', $merchant->id);
                }
            }
        }

        $query->select('stocks.*')->orderBy('stocks.id')->chunkById(100, function (Collection $stocks) {
            /** @var Stock $stock */
            foreach ($stocks as $stock) {
                dispatch(new SyncHistoryStockLogJob($stock));
                $this->info('sync for stock_id ' . $stock->id);
            }
        });
    }

    /**
     * 1. Cập nhật kích thước, cân nặng cho skus qua file excel
     *
     * @throws IOException
     * @throws UnsupportedTypeException
     * @throws ReaderNotOpenedException
     */
    protected function updateSizeSkusByFile()
    {
        $tenantId = $this->option('tenant_id');
        $filePath = storage_path('product_change_size.xlsx');
        $items    = (new FastExcel)->sheet(2)->import($filePath);
        unset($items[0]);
        foreach ($items as $item) {
            $merchant = Merchant::query()->where('username', $item['seller'])
                ->where('tenant_id', $tenantId)
                ->first();
            if ($merchant) {
                $sku = Sku::query()->where('skus.code', '=', $item['sku_code'])
                    ->where('skus.merchant_id', '=', $merchant->id)->first();
                if ($sku instanceof Sku) {
                    $sku->weight                = $item['length'] / 100;
                    $sku->length                = $item['length'] / 100;
                    $sku->width                 = $item['width'] / 100;
                    $sku->height                = $item['height'] / 100;
                    $sku->confirm_weight_volume = true;
                    $sku->save();
                } else {
                    $this->error($item['sku_code']);
                }
            }
        }
    }

    /**
     * 2. Cập nhật thông tin liên quan cho bảng thống kê phí lưu kho
     */
    protected function updateRelatedInfoStorageFeeSku()
    {
        $warehouses        = Warehouse::query()->pluck('code', 'id')->all();
        $nameMerchants     = Merchant::query()->pluck('name', 'id')->all();
        $usernameMerchants = Merchant::query()->pluck('username', 'id')->all();
        StorageFeeSkuStatistic::query()->chunkById(100, function ($storageFeeSkuStatistics) use ($warehouses, $nameMerchants, $usernameMerchants) {
            $skus           = [];
            $warehouseAreas = [];
            /** @var StorageFeeSkuStatistic $storageFeeSkuStatistic */
            foreach ($storageFeeSkuStatistics as $storageFeeSkuStatistic) {
                if (!empty($usernameMerchants[$storageFeeSkuStatistic->merchant_id])) {
                    $storageFeeSkuStatistic->merchant_username = $usernameMerchants[$storageFeeSkuStatistic->merchant_id];
                }
                if (!empty($nameMerchants[$storageFeeSkuStatistic->merchant_id])) {
                    $storageFeeSkuStatistic->merchant_name = $nameMerchants[$storageFeeSkuStatistic->merchant_id];
                }
                if (!empty($warehouses[$storageFeeSkuStatistic->warehouse_id])) {
                    $storageFeeSkuStatistic->warehouse_code = $warehouses[$storageFeeSkuStatistic->warehouse_id];
                }
                if (empty($skus[$storageFeeSkuStatistic->sku_id])) {
                    $skus[$storageFeeSkuStatistic->sku_id] = $storageFeeSkuStatistic->sku ? $storageFeeSkuStatistic->sku->code : null;
                }
                $storageFeeSkuStatistic->sku_code = $skus[$storageFeeSkuStatistic->sku_id];
                if (empty($warehouseAreas[$storageFeeSkuStatistic->warehouse_area_id])) {
                    $warehouseAreas[$storageFeeSkuStatistic->warehouse_area_id] = $storageFeeSkuStatistic->warehouseArea ? $storageFeeSkuStatistic->warehouseArea->code : null;
                }
                $storageFeeSkuStatistic->warehouse_area_code = $warehouseAreas[$storageFeeSkuStatistic->warehouse_area_id];
                $storageFeeSkuStatistic->service_price       = $storageFeeSkuStatistic->fee <= 0 ? 0 : round($storageFeeSkuStatistic->fee / $storageFeeSkuStatistic->volume);
                $storageFeeSkuStatistic->save();
                $this->info('updated for ' . $storageFeeSkuStatistic->id);
            }
        });
    }

    /**
     * 3. Cập nhật tồn cho những đơn bị huỷ khi đã xuất khỏi kho (đơn shoppe)
     */
    protected function updateStockWhenCancelOrder()
    {
        $creator = Service::user()->getSystemUserDefault();
        /**
         * Tìm các stock đã nhập tồn từ việc huỷ đơn (thông qua bảng stock_logs)
         */
        $stockIds = StockLog::query()->select('stock_logs.stock_id')
            ->join('orders', 'stock_logs.object_id', 'orders.id')
            ->join('order_exportings', 'stock_logs.object_id', 'order_exportings.order_id')
            ->where('stock_logs.action', 'UNRESERVE')
            ->where('stock_logs.object_type', 'ORDER')
            ->where('orders.marketplace_code', 'SHOPEE')
            ->where('orders.status', 'CANCELED')
            ->groupBy('stock_logs.stock_id')->pluck('stock_logs.stock_id')->all();

        /**
         * Kiểm tra tồn của các stock, tìm các đơn có sử dụng stock
         * nếu (tồn tạm tính + chờ xuất (chỉ tính những đơn chưa xuất kho)) > tồn thực tế  thì - tồn tạm tính để cân bằng tồn
         */
        $stocks = Stock::query()->whereIn('id', $stockIds)->get();

        /**
         * Chọn lại vị trí kho với những đơn chưa nhặt hàng xong (chờ xử lý, chờ nhặt hàng)
         */
        /** @var Stock $stock */
        foreach ($stocks as $stock) {
            $orderIds = $stock->orderStocks()->join('orders', 'order_stocks.order_id', 'orders.id')
                ->whereIn('orders.status', [Order::STATUS_WAITING_PROCESSING, Order::STATUS_WAITING_PICKING])
                ->select('order_stocks.order_id')
                ->groupBy('order_stocks.order_id')
                ->pluck('order_stocks.order_id')->all();
            $orders   = Order::query()->whereIn('id', $orderIds)->get();
            /** @var Order $order */
            foreach ($orders as $order) {
                if ($order->canAddWarehouseArea()) {
                    Service::order()->removeStockOrder($order, $creator);
                    Service::order()->autoInspection($order, $creator);
                    $order->logActivity(OrderEvent::ADD_WAREHOUSE_AREA, $creator);
                    $this->info('re inspection for ' . $order->code);
                }
            }
        }

    }

    /**
     * 4. Bổ xung thông tin bảng phí lưu kho theo skus
     */
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

    /**
     * 5. Cập nhật và truy thu phí nhập kho của những kiện nhập chưa áp dụng toàn bộ sản phẩm
     */
    protected function collectImporedServiceArrears()
    {
        $tenantId    = $this->option('tenant_id');
        $totalAmount = 0;
        /**
         * Tìm những purchasing_packages mà có amount trong purchasing_package_services < tổng số lượng sản phẩm nhập trên chứng từ
         * Cập nhật lại số lượng sản phẩm tính phí và tiến hành truy thu thêm số tiền còn thiếu
         */
        PurchasingPackage::query()->where('tenant_id', $tenantId)
            ->where('finance_status', 'PAID')
            ->chunkById(100, function ($purchasingPackages) use ($totalAmount) {
                /** @var PurchasingPackage $purchasingPackage */
                foreach ($purchasingPackages as $purchasingPackage) {
                    $countItems = (int)$purchasingPackage->purchasingPackageItems->sum('quantity');
                    /** @var PurchasingPackageService $purchasingPackageService */
                    foreach ($purchasingPackage->purchasingPackageServices as $purchasingPackageService) {
                        $missCountItems = $countItems - $purchasingPackageService->quantity;
                        if ($missCountItems > 0 && $purchasingPackage->importingBarcode) {
                            $missAmount = round($purchasingPackageService->price * $missCountItems, 2);
                            $purchasingPackageService->update([
                                'quantity' => $purchasingPackageService->quantity + $missCountItems,
                                'amount' => $purchasingPackageService->amount + $missAmount
                            ]);
                            /**
                             * Tạo transaction Call m4 truy thu tiền còn thiếu
                             */
                            $transaction = Service::transaction()->create(
                                Merchant::find($purchasingPackage->merchant_id),
                                Transaction::ACTION_COLLECT,
                                [
                                    'purchaseUnits' => [
                                        [
                                            'name' => $purchasingPackage->code,
                                            'description' => json_encode(['document_id' => $purchasingPackage->importingBarcode->document_id, 'code' => $purchasingPackage->code]),
                                            'orderId' => 'purchasing_package-' . $purchasingPackage->id,
                                            'referenceId' => $purchasingPackage->id,
                                            'amount' => $missAmount,
                                            'customType' => Transaction::TYPE_IMPORT_SERVICE,
                                        ]
                                    ]
                                ]
                            );
                            dispatch(new ProcessImportServiceTransactionJob($transaction->_id, $purchasingPackage->id));
                            $totalAmount += $missAmount;
                            $this->info('calling m4 ' . $missAmount . ' for ' . $purchasingPackage->code . ' total: ' . $totalAmount);
                        }
                    }
                }

            });
    }

    /**
     * 6. Gán dịch vụ bắt buộc nhập cho sản phẩm thiếu
     */
    protected function updateProductServicePrice()
    {
        $source   = $this->option('source');
        $tenantId = $this->option('tenant_id');
        $tenant   = Tenant::query()->where('id', $tenantId)->first();
        if ($tenant) {
            $this->addServiceToMissingProducts($tenant->id, $source);
        } else
            $this->error("không tồn tại tenantId= $tenantId");

    }

    /**
     * 8. Đồng bộ lại toàn bộ sản phẩm của 1 kênh - syncProductMarket
     *
     * @throws MarketplaceException
     * @throws RestApiException
     */
    public function syncProductMarket()
    {
        $source   = $this->option('source');
        $tenantId = $this->option('tenant_id');
        $tenant   = Tenant::find($tenantId);
        if (empty($source) || empty($tenant)) {
            $this->error('empty source or tenant_id');
            return;
        }

        switch ($source) {
            case 'SHOPEE':
                $this->getShopeeProducts($tenant);
                break;
            default:
        }
    }

    /**
     * 9. Cập nhật tồn cho những chứng từ nhập đã thực hiện sai tồn - updateStockByImportDocument
     */
    public function updateStockByImportDocument()
    {
        $documentIds = $this->option('document_ids');
        if (empty($documentIds)) {
            $this->error('empty document');
            return;
        }
        $user               = Service::user()->getSystemUserDefault();
        $documentIds        = explode(',', $documentIds);
        $importingDocuments = Document::query()->whereIn('id', $documentIds)->get();
        /** @var Document $importingDocument */
        foreach ($importingDocuments as $importingDocument) {
            if ($importingDocument->type != 'IMPORTING_RETURN_GOODS') {
                $this->error('Document is not IMPORTING_RETURN_GOODS');
                continue;
            }
            /**
             * Huỷ bỏ tồn từ chứng từ sau đó cập nhật lại
             */
            $stockLogs = StockLog::query()->where('created_at', '>', Carbon::now()->subMonth())
                ->where('payload', 'LIKE', '%' . $importingDocument->code . '%')->get();
            /** @var StockLog $stockLog */
            foreach ($stockLogs as $stockLog) {
                if ($stockLog->quantity == $stockLog->real_quantity && $stockLog->quantity && $stockLog->change == 'INCREASE') {
                    $stockLog->stock->export($stockLog->quantity, $user)->with(['document' => $importingDocument, 'stock_log' => $stockLog])->run();
                }
            }
            Service::documentImporting()->updateSkuStocks($importingDocument, $user);
            $this->info('update stock for document ' . $importingDocument->code);
        }
    }


    /** Gán dịch vụ bắt buộc nhập cho sản phẩm thiếu
     * @param int $tenantId
     * @param $source
     * @return void
     */
    protected function addServiceToMissingProducts(int $tenantId, $source = null)
    {
        $query = Product::query()
            ->where('products.tenant_id', $tenantId);
        if ($source) {
            $query->where('products.source', $source);
        }
        $requiredServices = $this->getRequiredService($tenantId);
        $query->chunkById(100, function ($products) use ($tenantId, $requiredServices) {
            /** @var Product $product */
            foreach ($products as $product) {
                /** @var ServicePrice $servicePrice */
                foreach ($requiredServices as $servicePrice) {
                    $serviceId = $servicePrice['service_id'];
                    if ($product->services->where('id', $serviceId)->first()) {
                        continue;
                    }
                    ProductServicePrice::updateOrCreate(
                        [
                            'product_id' => $product->id,
                            'tenant_id' => $tenantId,
                            'service_price_id' => $servicePrice['id'],
                            'service_id' => $serviceId,
                        ]
                    );
                    $this->info('update success producId ' . $product->id);
                }
            }
        });
    }

    /** Lấy danh sách dịch vụ
     * @param int $tenantId
     * @return array
     */
    protected function getRequiredService(int $tenantId)
    {
        return ServicePrice::query()->select('services.id as service_id', 'service_prices.id')
            ->join('services', 'service_prices.service_code', '=', 'services.code')
            ->where('services.tenant_id', '=', $tenantId)
            ->where('services.is_required', '=', 1)
            ->where('service_prices.is_default', '=', 1)
            ->get()->toArray();
    }

    /**
     * @param Tenant $tenant
     * @throws RestApiException
     * @throws MarketplaceException
     */
    protected function getShopeeProducts(Tenant $tenant)
    {

        $filter = [
            'offset' => 0,
            'page_size' => 100,
            'item_status' => "NORMAL"
        ];
        $stores = $tenant->stores;
        /** @var Store $store */
        foreach ($stores as $store) {
            $items = [];
            do {
                $shopeeProducts   = $store->shopeeApi()->getItems($filter)->getData();
                $items            = array_merge($items, Arr::get($shopeeProducts, 'response.item', []));
                $nextOffset       = Arr::get($shopeeProducts, 'response.next_offset');
                $filter['offset'] = $nextOffset;
            } while ($nextOffset);

            if ($items) {
                dispatch(new SyncShopeeProductJob($store->id, collect($items)->pluck('item_id')->all()));
                $this->info('sync product for ' . $store->id);
            }
        }

    }

    /** Tạo thêm thị trường Malaysia
     * @return void
     * @throws IOException
     * @throws ReaderNotOpenedException
     * @throws UnsupportedTypeException
     */
    public function addLocations()
    {
        $filePath = storage_path('malaysia_locations.xlsx');
        Location::firstOrCreate(
            [
                'type' => 'COUNTRY',
                'label' => 'Malaysia'
            ],
            [
                'code' => 'malaysia',
                'detail' => 'nước Malaysia',
                'active' => 1
            ]
        );
        $items  = (new FastExcel)->import($filePath);
        $number = 2000;
        foreach ($items as $item) {
            {
                $province = Location::firstOrCreate(
                    [
                        'label' => $item['province'],
                        'type' => Location::TYPE_PROVINCE,
                        'parent_code' => 'malaysia'
                    ],
                    [
                        'code' => 'MAS_' . sprintf("%08d", $number)
                    ]
                );
                $district = Location::firstOrCreate(
                    [
                        'label' => $item['district'],
                        'type' => Location::TYPE_DISTRICT,
                        'parent_code' => $province['code']
                    ],
                    [
                        'code' => 'MAS_DIS_' . sprintf("%08d", $number),
                    ]
                );
                Location::firstOrCreate(
                    [
                        'label' => $item['ward'],
                        'type' => Location::TYPE_WARD,
                        'parent_code' => $district['code']
                    ],
                    [
                        'code' => 'MAS_WA_' . sprintf("%08d", $number)
                    ]
                );
                $this->info('create success location ' . $item['province']);
                $number++;
            }

        }
    }

    /**
     * Tạo lại MVD cho đơn hàng chưa có thông tin bản ghi MVD
     *
     * @return void
     */
    protected function makeFreightBillCode()
    {
        $orderId = $this->option('order_id');
        $order   = Order::find($orderId);
        if ($order && $order->freight_bill) {
            if ($order->marketplace_code == Marketplace::CODE_KIOTVIET) {
                dispatch(new SyncKiotVietFreightBillJob($order->store, $order, $order->freight_bill));
            }
            if ($order->marketplace_code == Marketplace::CODE_LAZADA) {
                dispatch(new SyncLazadaFreightBillJob($order->store, $order, $order->freight_bill));
            }
            if ($order->marketplace_code == Marketplace::CODE_TIKTOKSHOP) {
                dispatch(new SyncTikTokShopFreightBillJob($order->store, $order, $order->freight_bill));
            }
        }
    }

    /** tạo service price code để quét mã dịch vụ
     * @return void
     */
    public function createServicePriceCode()
    {
        $servicePrices = ServicePrice::all();
        foreach ($servicePrices as $servicePrice) {
            $servicePrice->service_price_code = $servicePrice->service_code . '-' . $servicePrice->id;
            $servicePrice->save();
        }
    }

}
