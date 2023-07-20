<?php

namespace Modules\Lazada\Commands;

use Gobiz\Log\LogService;
use Gobiz\Support\RestApiException;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Modules\Marketplace\Services\Marketplace;
use Modules\Merchant\Models\Merchant;
use Modules\Merchant\Services\MerchantService;
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

class SyncLazadaProduct
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
     * @param int $store
     * @param array $lazadaItemId
     */
    public function __construct(Store $store, $lazadaItemId)
    {
        $this->api          = Service::lazada()->api();
        $this->store        = $store;
        $this->lazadaItemId = $lazadaItemId;
    }

    /**
     * @return array
     * @throws RestApiException
     * @throws \Modules\Marketplace\Services\MarketplaceException
     */
    public function handle()
    {
        $params = [
            'access_token' => $this->store->getSetting('access_token'),
            'item_id'      => $this->lazadaItemId
        ];

        // Lấy dữ liệu sản phẩm từ Lazada
        $lazadaProductDetail = $this->api->getItemDetail($params)->getData('data');
        $lazadaProductDetail = (array) $lazadaProductDetail;

        // Format dữ liệu để lưu 1 sku tương ứng 1 product
        $skus = data_get($lazadaProductDetail, 'skus', []);

        $productDatas = [];

        if (!empty($skus)) {
            foreach ($skus as $sku) {

                $isActive = Arr::get($lazadaProductDetail, 'status', '');
                $isActive = ($isActive == 'Active') ? Product::STATUS_ON_SELL : Product::STATUS_STOP_SELLING;
                
                // Tạo data resource
                $data3rdResource = [
                    'name'              => data_get($lazadaProductDetail, 'attributes.name'),
                    'price'             => data_get($sku, 'special_price'),
                    'original_price'    => data_get($sku, 'price'),
                    'code'              => data_get($sku, 'SellerSku'),
                    'source'            => Marketplace::CODE_LAZADA,
                    'product_id_origin' => data_get($lazadaProductDetail, 'item_id'),
                    'sku_id_origin'     => data_get($sku, 'SkuId'),
                    'description'       => data_get($lazadaProductDetail, 'attributes.description'),
                    'images'            => data_get($lazadaProductDetail, 'images', []),
                    'weight'            => data_get($sku, 'package_width'),
                    "status"            => $isActive
                ];

                $product = Service::product()->createProductFrom3rdPartner($this->store, $data3rdResource);

                $productDatas[] = $product;
            }
        }

        return $productDatas;
    }

}
