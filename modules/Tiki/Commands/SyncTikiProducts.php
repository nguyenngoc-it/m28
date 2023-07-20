<?php

namespace Modules\Tiki\Commands;

use Gobiz\Log\LogService;
use Gobiz\Support\RestApiException;
use Illuminate\Support\Arr;
use Modules\Tiki\Jobs\SyncTikiProductJob;
use Modules\Marketplace\Services\Marketplace;
use Modules\Merchant\Models\Merchant;
use Modules\Service;
use Modules\Store\Models\Store;
use Psr\Log\LoggerInterface;

class SyncTikiProducts
{
    const PER_PAGE = 20;

    /**
     * @var int
     */
    protected $storeId;

    /**
     * @var int
     */
    protected $merchantId;

    /**
     * @var Merchant
     */
    protected $merchant;

    /**
     * @var Store
     */
    protected $store;

    /**
     * @var LoggerInterface
     */
    protected $logger;

    protected $filterUpdateTime = true;

    protected $api;

    /**
     * SyncTikiProducts constructor.
     * @param Store $store
     * @param int $merchantId
     * @param bool $filterUpdateTime
     */
    public function __construct($store, $merchantId, $filterUpdateTime = true)
    {
        $this->store = $store;
        $this->merchantId = $merchantId;
        $this->filterUpdateTime = $filterUpdateTime;
        $this->api = Service::tiki()->api();
        $this->logger = LogService::logger('tiki-sync-products', [
            'context' => ['storeId' => $store->id, 'merchant_id' => $merchantId, 'filterUpdateTime' => $filterUpdateTime],
        ]);
    }

    /**
     * @param Store $store
     * @return int
     */
    protected function getLastUpdateTime(Store $store)
    {
        $lastUpdateTime = intval($store->getSetting(Store::SETTING_TIKI_PRODUCT_LAST_UPDATED_AT));
        $maxTime = time() - 1295400; //thời gian lọc tối đa là từ 15 ngày trước
        if ($lastUpdateTime < $maxTime) {
            $lastUpdateTime = $maxTime;
        }

        return $lastUpdateTime;
    }

    /**
     * @return array|null
     * @throws RestApiException
     * @throws \Modules\Marketplace\Services\MarketplaceException
     */
    public function handle()
    {
        if (!$this->store || $this->store->marketplace_code != Marketplace::CODE_TIKI) {
            $this->logger->info('Store invalid');
            return null;
        }

        $lastUpdateTime = $this->getLastUpdateTime($this->store);

        $this->fetchTikiProducts(0, $lastUpdateTime);
    }

    /**
     * @return Store|null
     */
    protected function getStore()
    {
        return Store::find($this->storeId);
    }

    /**
     * @param int $paginationOffset
     * @param int $lastUpdateTime
     * @throws RestApiException
     * @throws \Modules\Marketplace\Services\MarketplaceException
     */
    public function fetchTikiProducts(int $paginationOffset = 0, int $lastUpdateTime = 0)
    {
        $page = $paginationOffset / self::PER_PAGE;
        $page += 1;

        $filter = [
            'page' => $page,
            'limit' => self::PER_PAGE,
            'access_token' => $this->store->getSetting('access_token')
        ];

        $products = $this->api->getItems($filter);

        $items = $products->getData('data');
        $itemTotal = $products->getData('paging.total', 0);

        $offset = $paginationOffset + self::PER_PAGE;
        
        foreach ($items as $item) {
            $tikiProductId = Arr::get($item, 'product_id', 0);
            dispatch(new SyncTikiProductJob($this->store, $tikiProductId));
        }

        if ($itemTotal > $offset) {
            $this->fetchTikiProducts($offset, $lastUpdateTime);
        }
    }

    public function updateSettingStore($seller)
    {
        $shopName = $seller['name'];
        $this->store->name = $shopName ? $shopName : $this->store->marketplace_code;
        $this->store->save();

    }


}
