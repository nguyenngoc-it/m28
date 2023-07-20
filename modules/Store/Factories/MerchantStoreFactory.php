<?php

namespace Modules\Store\Factories;

use App\Base\Job;
use Modules\KiotViet\Jobs\SyncKiotVietProductsJob;
use Modules\Lazada\Jobs\SyncLazadaProductsJob;
use Modules\Marketplace\Services\Marketplace;
use Modules\ShopBaseUs\Jobs\SyncShopBaseUsProductsJob;
use Modules\Sapo\Jobs\SyncSapoProductsJob;
use Modules\Shopee\Jobs\SyncShopeeProductsJob;
use Modules\Store\Models\Store;
use Modules\Tiki\Jobs\SyncTikiProductsJob;
use Modules\TikTokShop\Jobs\SyncTikTokShopProductsJob;

class MerchantStoreFactory {

    /**
     * 
     *
     * @param Store $store
     */
    public function __construct(Store $store)
    {
        $this->store = $store;
    }

    /**
     * Make Sync Product Jober
     *
     * @return Job|null $jober
     */
    public function makeSyncProductJober() {
        switch ($this->store->marketplace_code) {
            case Marketplace::CODE_TIKI:
                $jober = new SyncTikiProductsJob($this->store, $this->store->merchant->id);
                break;
            
            case Marketplace::CODE_TIKTOKSHOP:
                $jober = new SyncTikTokShopProductsJob($this->store, $this->store->merchant->id);
                break;
            
            case Marketplace::CODE_SHOPBASE:
                $jober = new SyncShopBaseUsProductsJob($this->store, $this->store->merchant->id);
                break;

            case Marketplace::CODE_SAPO:
                $jober = new SyncSapoProductsJob($this->store, $this->store->merchant->id);
                break;

            case Marketplace::CODE_LAZADA:
                $jober = new SyncLazadaProductsJob($this->store, $this->store->merchant->id);
                break;

            case Marketplace::CODE_KIOTVIET:
                $jober = new SyncKiotVietProductsJob($this->store, $this->store->merchant->id);
                break;

            case Marketplace::CODE_SHOPEE:
                $jober = new SyncShopeeProductsJob($this->store->id, $this->store->merchant->id);
                break;

            default:
                $jober = null;
                break;
        }

        return $jober;
    }
}