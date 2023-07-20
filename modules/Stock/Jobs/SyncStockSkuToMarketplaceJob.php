<?php

namespace Modules\Stock\Jobs;

use App\Base\Job;
use Illuminate\Support\Arr;
use Modules\Product\Models\Sku;
use Modules\Service;
use Modules\Store\Models\Store;
use Modules\Marketplace\Services\Marketplace;
use Gobiz\Log\LogService;
use Modules\Sapo\Commands\SyncSapoStock;
use Modules\ShopBaseUs\Commands\SyncShopBaseUsStock;
use Modules\TikTokShop\Commands\SyncTikTokShopStock;
use Psr\Log\LoggerInterface;

class SyncStockSkuToMarketplaceJob extends Job
{
    /**
     * @var integer
     */
    protected $skuId;

    /**
     * Số lượng tồn kho của SKU
     * @var int
     */
    protected $stock = 0;


    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * SyncStockSkuJob constructor.
     * @param $skuId
     */
    public function __construct($skuId)
    {
        $this->skuId = $skuId;
    }

    public function handle()
    {
        $sku  = Sku::find($this->skuId);

        $this->logger = LogService::logger('sync_stock_sku_to_marketplace', [
            'context' => [
                'sku' => $sku->only(['id', 'code', 'sku_id_origin', 'real_stock', 'stock']),
            ]
        ]);

        $stores = Store::query()
            ->join('store_skus','store_skus.store_id','=','stores.id')
            ->where('store_skus.sku_id', $sku->id)
            ->get();

        /** @var Store $store */
        foreach ($stores as $store) {
            if(!$store->getSetting('sync_stock')) {
                continue;
            }

            $this->stock = $this->getSkuStock($store, $sku);

            switch ($store->marketplace_code) {
                case Marketplace::CODE_SHOPEE: {
                    $this->syncStockShopee($store, $sku);
                    break;
                }
                case Marketplace::CODE_LAZADA: {
                    $this->syncStockLazada($store, $sku);
                    break;
                }
                case Marketplace::CODE_KIOTVIET: {
                    $this->syncStockKiotViet($store, $sku);
                    break;
                }
                case Marketplace::CODE_TIKTOKSHOP: {
                    $this->syncStockTikTokShop($sku);
                    break;
                }
                case Marketplace::CODE_SHOPBASE: {
                    $this->syncStockShopBaseUs($sku);
                    break;
                }
                case Marketplace::CODE_SAPO: {
                    $this->syncStockSapo($sku);
                    break;
                }
            }
        }
    }

    /**
     * @param Store $store
     * @param Sku $sku
     * @return mixed
     */
    protected function getSkuStock(Store $store, Sku $sku)
    {
        if($store->getSetting('quantity_type') == 'real_quantity') {
            return intval($sku->real_stock);
        }

        return intval($sku->stock);
    }

    /**
     * @param Store $store
     * @param Sku $sku
     */
    protected function syncStockLazada(Store $store, Sku $sku)
    {
        $skuData = [];
        $skuData['Sku'][] = [
            'SkuId' => $sku->sku_id_origin,
            'SellerSku' => $sku->code,
            'quantity' => $this->stock
        ];
        $payload = [
            'Request' => [
                'Product' =>
                    [
                        'Attributes' => [
                            'name' => '',
                            'short_description' => ''
                        ],
                        'ItemId' => $sku->product_id_origin,
                        'Skus' => $skuData
                    ]
            ]
        ];
        $filter = [
            'payload' => json_encode($payload),
            'access_token' => $store->getSetting('access_token')
        ];

        try {
            $response = Service::lazada()->api()->updateProductLazada($filter);
            $data     = $response->getData();
            $this->logger->info('updateProductLazada  '.$store->id, compact('filter', 'data'));

        } catch (\Exception $exception) {
            $this->logger->info('updateProductLazada error '.$store->id. ' - ' .$exception->getMessage(), compact('filter'));
        }

    }

    /**
     * @param Store $store
     * @param Sku $sku
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    protected function syncStockKiotViet(Store $store, Sku $sku)
    {
        try {
            $response = Service::kiotviet()->api()->getItemDetail($sku->sku_id_origin, $store)->getData();
            $branches = Arr::get($response, 'inventories', []);

            $this->logger->info('getBranches KiotViet '.$store->id, compact('response'));

            if(empty($branches)) {
                return;
            }

        } catch (\Exception $exception) {
            $this->logger->info('getBranches KiotViet error '.$store->id. ' - ' .$exception->getMessage() .' - '. $exception->getFile().' - '. $exception->getLine());
            return;
        }

        try {
            $params   = [];
            $inventories = [];
            foreach ($branches as $branch) {
                $inventories[] = [
                    "branchId" => $branch['branchId'],
                    "branchName" => $branch['branchName'],
                    "onHand" => $this->stock,
                ];
            }
            $params['inventories'] = $inventories;
            $response = Service::kiotviet()->updateProduct($sku->sku_id_origin, $store, $params);

            $this->logger->info('update product KiotViet', compact('params','response'));

        } catch (\Exception $exception) {
            $this->logger->info('update product KiotViet error '.$exception->getMessage() .' - '. $exception->getFile().' - '. $exception->getLine());
        }
    }

    /**
     * @param Store $store
     * @param Sku $sku
     */
    protected function syncStockShopee(Store $store, Sku $sku)
    {
        $locationIds = [];
        try {
            $warehouseDetail = Service::shopee()->getWarehouseDetail($store);
            $this->logger->info('warehouse Detail Shopee', compact('warehouseDetail'));
            if(!empty($warehouseDetail['response'])) {
                foreach ($warehouseDetail['response'] as $item) {
                    $locationIds[] = $item['location_id'];
                }
            }

        } catch (\Exception $exception) {
            $this->logger->info('warehouse Detail Shopee error '.$exception->getMessage() .' - '. $exception->getFile().' - '. $exception->getLine());
        }

        try {
            $params   = [];
            $response = Service::shopee()->updateStock($store, $sku, $this->stock, $locationIds, $params);
            $this->logger->info('update stock Shopee', compact('params','response'));

        } catch (\Exception $exception) {
            $this->logger->info('sync Stock Shopee error '.$exception->getMessage() .' - '. $exception->getFile().' - '. $exception->getLine());
        }
    }

    /**
     * @param Sku $sku
     */
    protected function syncStockTikTokShop(Sku $sku)
    {
        return (new SyncTikTokShopStock($sku))->handle();
    }

    /**
     * @param Sku $sku
     */
    protected function syncStockShopBaseUs(Sku $sku)
    {
        return (new SyncShopBaseUsStock($sku))->handle();
    }

    /**
     * @param Sku $sku
     */
    protected function syncStockSapo(Sku $sku)
    {
        return (new SyncSapoStock($sku))->handle();
    }
}
