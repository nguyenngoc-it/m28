<?php

namespace Modules\TikTokShop\Commands;

use Modules\Product\Models\Sku;
use Modules\Service;
use Modules\Store\Models\Store;
use Psr\Log\LoggerInterface;
use Gobiz\Log\LogService;
use Modules\Marketplace\Services\Marketplace;

class SyncTikTokShopStock
{
    /**
     * @var Sku
     */
    protected $sku;

    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * SyncTikTokShopStock constructor.
     * @param Sku $sku
     */
    public function __construct(Sku $sku)
    {
        $this->sku = $sku;
        $this->api = Service::tikTokShop()->api();
        $this->logger = LogService::logger('sync_stock_sku_to_marketplace', [
            'context' => [
                'sku' => $sku->only(['id', 'code', 'sku_id_origin', 'real_stock', 'stock']),
            ]
        ]);
    }

    /**
     * @return void
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function handle()
    {
        $storeSkus = $this->sku->storeSkus;
        foreach ($storeSkus as $storeSku) {
            if ($storeSku->marketplace_code == Marketplace::CODE_TIKTOKSHOP) {
                // Lấy thông tin chi tiết sản phẩm
                $productIdOrigin = $storeSku->product_id_origin;
                $skuIdOrigin     = $storeSku->sku_id_origin;
                $paramsRequest = [
                    'product_id'   => $productIdOrigin,
                    'access_token' => $storeSku->store->getSetting('access_token'),
                ];

                if ($productIdOrigin && $storeSku->store->getSetting('access_token')) {
                    $productDetail = $this->api->getItemDetail($paramsRequest)->getData('data');
                    $skus = data_get($productDetail, 'skus', []);
                    if ($skus) {
                        foreach ($skus as $sku) {
                            if($skuIdOrigin == data_get($sku, 'id')) {
                                $stockInfos = data_get($sku, 'stock_infos', []);
                                if ($stockInfos) {
                                    foreach ($stockInfos as $stockInfo) {
                                        $warehouseId = data_get($stockInfo, 'warehouse_id');
                                        $stockInfo = [];
                                        $stockInfo[] = [
                                            "available_stock" => $this->getSkuStock($storeSku->store, $this->sku),
                                            "warehouse_id"    => $warehouseId,
                                        ];
                                        $payload = [
                                            "product_id" => $productIdOrigin
                                        ];
                                        $payload['skus'][] = [
                                            "id"          => $skuIdOrigin,
                                            "stock_infos" => $stockInfo
                                        ];

                                        // dd($payload);
                                        $paramsRequest = [
                                            'shop_id'      => $storeSku->store->marketplace_store_id,
                                            'product_id'   => $productIdOrigin,
                                            'body'         => $payload,
                                            'access_token' => $storeSku->store->getSetting('access_token'),
                                        ];
                                        try {
                                            $response = $this->api->updateProductStock($paramsRequest);
                                            $data     = $response->getData();
                                            $this->logger->info('updateProductTikTokShop  ' . $storeSku->store->id, compact('paramsRequest', 'data'));
                                
                                        } catch (\Exception $exception) {
                                            $this->logger->info('updateProductTikTokShop error ' . $storeSku->store->id . ' - ' .$exception->getMessage(), compact('paramsRequest'));
                                        }
                                        
                                    } 
                                }
                            }
                        }
                    }
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


}
