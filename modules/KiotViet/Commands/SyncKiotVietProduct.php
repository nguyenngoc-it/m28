<?php

namespace Modules\KiotViet\Commands;

use Gobiz\Support\RestApiException;
use Illuminate\Support\Arr;
use Modules\Marketplace\Services\Marketplace;
use Modules\Merchant\Models\Merchant;
use Modules\Product\Models\Product;
use Modules\Service;
use Modules\Store\Models\Store;
use Modules\User\Models\User;
use Psr\Log\LoggerInterface;

class SyncKiotVietProduct
{

    /**
     * @var Store
     */
    protected $store;

    /**
     * @var integer
     */
    protected $kiotVietItemId;

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

    /**
     * SyncKiotVietProduct constructor
     *
     * @param Store $store
     * @param integer $kiotVietItemId
     */
    public function __construct(Store $store, $kiotVietItemId)
    {
        $this->store          = $store;
        $this->kiotVietItemId = $kiotVietItemId;
    }

    /**
     * @return Product $product
     * @throws RestApiException
     */
    public function handle()
    {
        // Lấy dữ liệu sản phầm từ KiotViet
        $kiotVietProductDetail = Service::kiotviet()->api()->getItemDetail($this->kiotVietItemId, $this->store)->getData();
        $kiotVietProductDetail = (array)$kiotVietProductDetail;

        $isActive = Arr::get($kiotVietProductDetail, 'isActive', true);
        $isActive = ($isActive) ? Product::STATUS_ON_SELL : Product::STATUS_STOP_SELLING;

        $data3rdResource = [
            'name' => data_get($kiotVietProductDetail, 'name'),
            'price' => data_get($kiotVietProductDetail, 'basePrice'),
            'original_price' => data_get($kiotVietProductDetail, 'basePrice'),
            'code' => data_get($kiotVietProductDetail, 'code'),
            'source' => Marketplace::CODE_KIOTVIET,
            'product_id_origin' => data_get($kiotVietProductDetail, 'id'),
            'sku_id_origin' => data_get($kiotVietProductDetail, 'id'),
            'description' => data_get($kiotVietProductDetail, 'description'),
            'images' => data_get($kiotVietProductDetail, 'images', []),
            'weight' => data_get($kiotVietProductDetail, 'weight'),
            "status" => $isActive
        ];

        $product = Service::product()->createProductFrom3rdPartner($this->store, $data3rdResource);

        return $product;
    }

}
