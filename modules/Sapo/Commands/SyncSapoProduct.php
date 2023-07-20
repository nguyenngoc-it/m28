<?php

namespace Modules\Sapo\Commands;

use Gobiz\Support\RestApiException;
use Modules\Marketplace\Services\Marketplace;
use Modules\Merchant\Models\Merchant;
use Modules\Product\Models\Product;
use Modules\Service;
use Modules\Store\Models\Store;
use Modules\User\Models\User;
use Psr\Log\LoggerInterface;

class SyncSapoProduct
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
     * SyncSapoProduct constructor
     *
     * @param Store $store
     * @param array $sapoItemId
     */
    public function __construct(Store $store, $sapoItemId)
    {
        $this->api = Service::Sapo()->api();
        $this->store = $store;
        $this->sapoItemId = $sapoItemId;
    }

    /**
     * @return array
     * @throws RestApiException
     * @throws \Modules\Marketplace\Services\MarketplaceException
     */
    public function handle()
    {        
        // Lấy dữ liệu sản phầm từ Sapo
        $paramsRequest = [
            'product_id'    => $this->sapoItemId,
            'shop_name'     => $this->store->getSetting('shop_name'),
            'client_id'     => $this->store->getSetting('client_id'),
            'client_secret' => $this->store->getSetting('client_secret'),
        ];
        
        $sapoProductDetail = $this->api->getItemDetail($paramsRequest)->getData('product');
        $sapoProductDetail = (array) $sapoProductDetail;

        $sapoSkus = data_get($sapoProductDetail, 'variants', []);

        // dd($sapoProductDetail, $sapoSkus);
        $isActive = Product::STATUS_ON_SELL;

        $imagesDatas = data_get($sapoProductDetail, 'images', []);
        $images = [];
        if (!empty($imagesDatas)) {
            foreach ($imagesDatas as $imagesData) {
                if (isset($imagesData['src'])) {
                    $images[] = $imagesData['src'];
                }
            }
        }

        $products = [];
        $productId = data_get($sapoProductDetail, 'id');
        if ($sapoSkus) {
            foreach ($sapoSkus as $sapoSku) {
                $skuId     = data_get($sapoSku, 'id');
                $sellerSku = data_get($sapoSku, 'sku');
                if ($sellerSku) {
                    $code = $sellerSku;
                } else {
                    $code = $skuId;   
                }

                $weight      = (double) data_get($sapoSku, 'weight');
                $weight_unit = data_get($sapoSku, 'weight_unit');
                if ($weight_unit != 'kg') {
                    $weight = $weight / 1000;
                }

                $data3rdResource = [
                    'name'              => data_get($sapoProductDetail, 'name') . ' - ' . data_get($sapoSku, 'title'),
                    'price'             => data_get($sapoSku, 'price'),
                    'original_price'    => data_get($sapoSku, 'price'),
                    'code'              => $code,
                    'source'            => Marketplace::CODE_SAPO,
                    'product_id_origin' => $productId,
                    'sku_id_origin'     => $skuId,
                    'description'       => data_get($sapoProductDetail, 'content'),
                    'images'            => $images,
                    'weight'            => $weight,
                    "status"            => $isActive
                ];
        
                // dd($data3rdResource);
        
                $products[] = Service::product()->createProductFrom3rdPartner($this->store, $data3rdResource);
            }
        }      

        return $products;
    }


}
