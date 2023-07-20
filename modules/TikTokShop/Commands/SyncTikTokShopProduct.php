<?php

namespace Modules\TikTokShop\Commands;

use Gobiz\Support\RestApiException;
use Modules\Marketplace\Services\Marketplace;
use Modules\Merchant\Models\Merchant;
use Modules\Product\Models\Product;
use Modules\Service;
use Modules\Store\Models\Store;
use Modules\User\Models\User;
use Psr\Log\LoggerInterface;

class SyncTikTokShopProduct
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
     * @param array $tikTokShopItemId
     */
    public function __construct(Store $store, $tikTokShopItemId)
    {
        $this->api = Service::tikTokShop()->api();
        $this->store = $store;
        $this->tikTokShopItemId = $tikTokShopItemId;
    }

    /**
     * @return array
     * @throws RestApiException
     * @throws \Modules\Marketplace\Services\MarketplaceException
     */
    public function handle()
    {        
        // Lấy dữ liệu sản phầm từ TikTokShop
        $paramsRequest = [
            'product_id'    => $this->tikTokShopItemId,
            'access_token'  => $this->store->getSetting('access_token')
        ];
        
        $tikTokShopProductDetail = Service::tikTokShop()->api()->getItemDetail($paramsRequest)->getData('data');
        $tikTokShopProductDetail = (array)$tikTokShopProductDetail;
        $tikTokShopSkus = data_get($tikTokShopProductDetail, 'skus', []);

        // dd($tikTokShopProductDetail, $tikTokShopSkus);
        $isActive = Product::STATUS_ON_SELL;

        $imagesDatas = data_get($tikTokShopProductDetail, 'images', []);
        $images = [];
        if (!empty($imagesDatas)) {
            foreach ($imagesDatas as $imagesData) {
                if (isset($imagesData['url_list'][0])) {
                    $images[] = $imagesData['url_list'][0];
                }
            }
        }

        $products= [];
        $productId = data_get($tikTokShopProductDetail, 'product_id');
        if ($tikTokShopSkus) {
            foreach ($tikTokShopSkus as $tikTokShopSku) {
                $skuId     = data_get($tikTokShopSku, 'id');
                $sellerSku = data_get($tikTokShopSku, 'seller_sku');
                if ($sellerSku) {
                    $code = $sellerSku;
                } else {
                    $code = $productId . "_" . $skuId;   
                }
                $data3rdResource = [
                    'name'              => data_get($tikTokShopProductDetail, 'product_name'),
                    'price'             => data_get($tikTokShopSku, 'price.original_price'),
                    'original_price'    => data_get($tikTokShopSku, 'price.original_price'),
                    'code'              => $code,
                    'source'            => Marketplace::CODE_TIKTOKSHOP,
                    'product_id_origin' => $productId,
                    'sku_id_origin'     => $skuId,
                    'description'       => data_get($tikTokShopProductDetail, 'description'),
                    'images'            => $images,
                    'weight'            => data_get($tikTokShopProductDetail, 'package_weight'),
                    "status"            => $isActive
                ];
        
                // dd($data3rdResource);
        
                $products[] = Service::product()->createProductFrom3rdPartner($this->store, $data3rdResource);
            }
        }        

        return $products;
    }


}
