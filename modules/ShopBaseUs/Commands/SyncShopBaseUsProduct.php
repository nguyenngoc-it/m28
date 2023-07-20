<?php

namespace Modules\ShopBaseUs\Commands;

use Gobiz\Support\RestApiException;
use Modules\Marketplace\Services\Marketplace;
use Modules\Merchant\Models\Merchant;
use Modules\Product\Models\Product;
use Modules\Service;
use Modules\Store\Models\Store;
use Modules\User\Models\User;
use Psr\Log\LoggerInterface;

class SyncShopBaseUsProduct
{

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
     * @param array $shopBaseUsItemId
     */
    public function __construct(Store $store, $shopBaseUsItemId)
    {
        $this->api = Service::shopBaseUs()->api();
        $this->store = $store;
        $this->shopBaseUsItemId = $shopBaseUsItemId;
    }

    /**
     * @return array
     * @throws RestApiException
     * @throws \Modules\Marketplace\Services\MarketplaceException
     */
    public function handle()
    {        
        // Lấy dữ liệu sản phầm từ ShopBaseUs
        $paramsRequest = [
            'product_id'    => $this->shopBaseUsItemId,
            'shop_name'     => $this->store->getSetting('shop_name'),
            'client_id'     => $this->store->getSetting('client_id'),
            'client_secret' => $this->store->getSetting('client_secret'),
        ];
        
        $shopBaseUsProductDetail = $this->api->getItemDetail($paramsRequest)->getData('product');
        $shopBaseUsProductDetail = (array) $shopBaseUsProductDetail;
        $shopBaseUsSkus = data_get($shopBaseUsProductDetail, 'variants', []);

        // dd($shopBaseUsProductDetail, $shopBaseUsSkus);
        $isActive = Product::STATUS_ON_SELL;

        $imagesDatas = data_get($shopBaseUsProductDetail, 'images', []);
        $images = [];
        if (!empty($imagesDatas)) {
            foreach ($imagesDatas as $imagesData) {
                if (isset($imagesData['src'])) {
                    $images[] = $imagesData['src'];
                }
            }
        }

        $products = [];
        $productId = data_get($shopBaseUsProductDetail, 'id');
        if ($shopBaseUsSkus) {
            foreach ($shopBaseUsSkus as $shopBaseUsSku) {
                $skuId     = data_get($shopBaseUsSku, 'id');
                $sellerSku = data_get($shopBaseUsSku, 'sku');
                if ($sellerSku) {
                    $code = $sellerSku;
                } else {
                    $code = $skuId;   
                }
                $data3rdResource = [
                    'name'              => data_get($shopBaseUsProductDetail, 'title'),
                    'price'             => data_get($shopBaseUsSku, 'price'),
                    'original_price'    => data_get($shopBaseUsSku, 'price'),
                    'code'              => $code,
                    'source'            => Marketplace::CODE_SHOPBASE,
                    'product_id_origin' => $productId,
                    'sku_id_origin'     => $skuId,
                    'description'       => data_get($shopBaseUsProductDetail, 'body_html'),
                    'images'            => $images,
                    'weight'            => data_get($shopBaseUsSku, 'weight'),
                    "status"            => $isActive
                ];
        
                // dd($data3rdResource);
        
                $products[] = Service::product()->createProductFrom3rdPartner($this->store, $data3rdResource);
            }
        }      

        return $products;
    }


}
