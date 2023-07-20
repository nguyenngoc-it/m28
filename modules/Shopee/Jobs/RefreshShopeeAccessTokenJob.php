<?php

namespace Modules\Shopee\Jobs;

use App\Base\Job;
use Gobiz\Log\LogService;
use Modules\Marketplace\Services\Marketplace;
use Modules\Marketplace\Services\MarketplaceException;
use Modules\Service;
use Modules\Store\Models\Store;

class RefreshShopeeAccessTokenJob extends Job
{
    /**
     * @var string
     */
    public $queue = 'shopee';

    /**
     * @var int
     */
    protected $shopId;

    /**
     * @var string
     */
    protected $accessToken;

    /**
     * RefreshAccessTokenJob constructor
     *
     * @param int $shopId
     * @param string $accessToken
     */
    public function __construct($shopId, $accessToken)
    {
        $this->shopId = $shopId;
        $this->accessToken = $accessToken;
    }

    /**
     * @throws MarketplaceException
     */
    public function handle()
    {
        if (
            ($store = $this->findStore())
            && $store->getSetting('access_token') === $this->accessToken
        ) {
            Service::shopee()->storeConnector($store)->refreshAccessToken();
        } else {
            LogService::logger('shopee')->error('CANT_REFRESH_ACCESS_TOKEN', [
                'shop_id' => $this->shopId,
                'access_token' => $this->accessToken,
            ]);
        }
    }

    /**
     * @return Store|object|null
     */
    protected function findStore()
    {
        return Store::query()->firstWhere([
            'marketplace_code' => Marketplace::CODE_SHOPEE,
            'marketplace_store_id' => $this->shopId,
        ]);
    }
}
