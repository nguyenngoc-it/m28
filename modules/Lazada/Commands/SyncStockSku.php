<?php

namespace Modules\Lazada\Commands;

use Modules\Product\Models\Product;
use Modules\Product\Models\Sku;
use Modules\Service;
use Modules\Store\Models\Store;

class SyncStockSku
{
    /**
     * @var Sku
     */
    protected $sku;

    protected $type;

    protected $api;

    /**
     * @var Store
     */
    protected $store;

    public function __construct(Sku $sku,Store $store)
    {
        $this->sku = $sku;
        $this->api = Service::lazada()->api();
        $this->store = $store;

    }

    /**
     * @return void
     */
    public function handle()
    {
        $type = $this->store->getSetting('type_sync');
        $skuData = [];
            $skuData['Sku'][] = [
                'SkuId' => $this->sku->sku_id_origin,
                'SellerSku' => $this->sku->code,
                'quantity' => $this->sku->getQuantitySkus($type)
            ];
        $payload = [
            'Request' => [
                'Product' =>
                    [
                        'Attributes' => [
                            'name' => '',
                            'short_description' => ''
                        ],
                        'ItemId' => $this->sku->product_id_origin,
                        'Skus' => $skuData
                    ]
            ]
        ];
        $filter = [
            'payload' => json_encode($payload),
            'access_token' => $this->store->getSetting('access_token')
        ];
//        dd($filter);
        $this->api->updateProductLazada($filter);
    }

}
