<?php

namespace Modules\Tiki\Commands;

use Gobiz\Log\LogService;
use Gobiz\Support\RestApiException;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Modules\Marketplace\Services\Marketplace;
use Modules\Merchant\Models\Merchant;
use Modules\Product\Events\ProductCreated;
use Modules\Product\Events\ProductUpdated;
use Modules\Product\Models\Product;
use Modules\Product\Models\ProductMerchant;
use Modules\Product\Models\Sku;
use Modules\Service;
use Modules\Store\Models\Store;
use Modules\Store\Models\StoreSku;
use Modules\User\Models\User;
use Psr\Log\LoggerInterface;

class SyncTikiProduct
{
    /**
     * @var int
     */
    protected $storeId;

    /**
     * @var Store
     */
    protected $store;

    /**
     * @var User
     */
    protected $creator;

    /**
     * @var Merchant
     */
    protected $merchant;

    /**
     * @var LoggerInterface
     */
    protected $logger;

    protected $api;

    /**
     * SyncShopeeProduct constructor
     *
     * @param Store $store
     * @param array $tikiItemId
     */
    public function __construct(Store $store, $tikiItemId)
    {
        $this->api = Service::tiki()->api();
        $this->store = $store;
        $this->tikiItemId = $tikiItemId;
    }

    /**
     * @return array
     * @throws RestApiException
     * @throws \Modules\Marketplace\Services\MarketplaceException
     */
    public function handle()
    {
        // Lấy dữ liệu sản phầm từ Tiki
        $paramsRequest = [
            'productId'    => $this->tikiItemId,
            'access_token' => $this->store->getSetting('access_token')
        ];
        
        $tikiProductDetail = Service::tiki()->api()->getItemDetail($paramsRequest)->getData();
        $tikiProductDetail = (array)$tikiProductDetail;
        // dd($tikiProductDetail);
        $isActive = Product::STATUS_ON_SELL;

        $imagesDatas = data_get($tikiProductDetail, 'images', []);
        $images = [];
        if (!empty($imagesDatas)) {
            foreach ($imagesDatas as $imagesData) {
                $images[] = $imagesData['url'];
            }
        }

        $data3rdResource = [
            'name'              => data_get($tikiProductDetail, 'name'),
            'price'             => data_get($tikiProductDetail, 'price'),
            'original_price'    => data_get($tikiProductDetail, 'price'),
            'code'              => data_get($tikiProductDetail, 'sku'),
            'source'            => Marketplace::CODE_TIKI,
            'product_id_origin' => data_get($tikiProductDetail, 'id'),
            'sku_id_origin'     => data_get($tikiProductDetail, 'id'),
            'description'       => data_get($tikiProductDetail, 'attributes.description'),
            'images'            => $images,
            'weight'            => data_get($tikiProductDetail, 'attributes.product_width'),
            "status"            => $isActive
        ];

        // dd($data3rdResource);

        $product = Service::product()->createProductFrom3rdPartner($this->store, $data3rdResource);

        return $product;
    }


}
