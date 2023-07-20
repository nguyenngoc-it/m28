<?php

namespace Modules\Sapo\Commands;

use Modules\Product\Models\Sku;
use Modules\Service;
use Modules\Store\Models\Store;
use Psr\Log\LoggerInterface;
use Gobiz\Log\LogService;

class SyncSapoStock
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
     * SyncSapoStock constructor.
     * @param Sku $sku
     */
    public function __construct(Sku $sku)
    {
        $this->sku = $sku;
        $this->api = Service::sapo()->api();
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
            // Lấy thông tin chi tiết sản phẩm
            $skuIdOrigin     = $storeSku->sku_id_origin;
            $store           = $storeSku->store;

            $body = '{
                "variant": {
                  "id": ' . $skuIdOrigin . ',
                  "inventory_quantity": ' . $this->getSkuStock($storeSku->store, $this->sku) . '
                }
            }';

            $paramsRequest = [
                'shop_name'     => $store->getSetting('shop_name'),
                'client_id'     => $store->getSetting('client_id'),
                'client_secret' => $store->getSetting('client_secret'),
                'sku_id'        => $skuIdOrigin,
                'body'          => json_decode($body, true)
            ];

            try {
                $response = $this->api->updateProductStock($paramsRequest);
                $data     = $response->getData();
                $this->logger->info('updateProductSapo  ' . $storeSku->store->id, compact('paramsRequest', 'data'));
    
            } catch (\Exception $exception) {
                $this->logger->info('updateProductSapo error ' . $storeSku->store->id . ' - ' .$exception->getMessage(), compact('paramsRequest'));
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
