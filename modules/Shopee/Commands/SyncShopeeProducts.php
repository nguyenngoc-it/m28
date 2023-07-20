<?php

namespace Modules\Shopee\Commands;

use Gobiz\Log\LogService;
use Gobiz\Support\RestApiException;
use Illuminate\Support\Arr;
use Modules\Marketplace\Services\Marketplace;
use Modules\Merchant\Models\Merchant;
use Modules\Service;
use Modules\Shopee\Jobs\SyncShopeeProductJob;
use Modules\Store\Models\Store;
use Modules\User\Models\User;
use Psr\Log\LoggerInterface;

class SyncShopeeProducts
{
    const PER_PAGE = 100;

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

    /**
     * SyncShopeeProducts constructor.
     * @param $storeId
     * @param $merchantId
     * @param bool $filterUpdateTime
     */
    public function __construct($storeId, $merchantId, $filterUpdateTime = true)
    {
        $this->storeId   = intval($storeId);
        $this->merchantId = $merchantId;
        $this->filterUpdateTime = $filterUpdateTime;

        $this->logger = LogService::logger('shopee-sync-products', [
            'context' => ['storeId' => $storeId, 'merchant_id' => $merchantId, 'filterUpdateTime' => $filterUpdateTime],
        ]);
    }

    /**
     * @param Store $store
     * @return int
     */
    protected function getLastUpdateTime(Store $store)
    {
        $lastUpdateTime = intval($store->getSetting(Store::SETTING_SHOPEE_PRODUCT_LAST_UPDATED_AT));
        $maxTime = time() - 1295400; //thời gian lọc tối đa là từ 15 ngày trước
        if($lastUpdateTime < $maxTime) {
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
        $this->merchant = Merchant::find($this->merchantId);
        $this->store = $this->getStore();

        if (!$this->store instanceof Store) {
            $this->logger->info('Store invalid');
            return null;
        }

        $lastUpdateTime = $this->getLastUpdateTime($this->store);
        $shopeeProducts = [];
        $this->fetchShopeeProducts($this->store->marketplace_store_id,0, $shopeeProducts, $lastUpdateTime);
        if(empty($shopeeProducts)) {
            $this->logger->info(' empty products');
            return [];
        }

        $shopeeItemIds = [];
        foreach ($shopeeProducts as $shopeeProduct) {
            $shopeeItemId = Arr::get($shopeeProduct, 'item_id');
            $updateTime = Arr::get($shopeeProduct, 'update_time');

            if($updateTime > $lastUpdateTime) {
                $lastUpdateTime = $updateTime;
            }

            $shopeeItemIds[] = $shopeeItemId;
        }

        dispatch(new SyncShopeeProductJob($this->store->id, $shopeeItemIds));

        $settings = (array)$this->store->settings;
        $settings[Store::SETTING_SHOPEE_PRODUCT_LAST_UPDATED_AT] = $lastUpdateTime;
        $this->store->settings = $settings;
        $this->store->save();

        return $shopeeProducts;
    }

    /**
     * @return Store|null
     */
    protected function getStore()
    {
        return Store::find($this->storeId);
    }

    /**
     * @param $shopId
     * @param int $paginationOffset
     * @param array $items
     * @param int $lastUpdateTime
     * @throws RestApiException
     * @throws \Modules\Marketplace\Services\MarketplaceException
     */
    public function fetchShopeeProducts($shopId, $paginationOffset = 0, &$items = [], $lastUpdateTime = 0)
    {
        $filter = [
            'offset' => $paginationOffset,
            'page_size' => self::PER_PAGE,
            'item_status' => "NORMAL"
        ];

        if($this->filterUpdateTime) {
            $filter = array_merge($filter, [
                'update_time_from' => $lastUpdateTime,
                'update_time_to' => time()
            ]);
        }

        $shopeeProducts = $this->store->shopeeApi()->getItems($filter)->getData();
        $this->logger->info('Start getItems products', ['response' => $shopeeProducts, 'filter' => $filter]);

        if(!empty($shopeeProducts['response']['item'])) {
            $response = $shopeeProducts['response'];

            $items     = array_merge($items, $response['item']);
            $itemTotal = $response['total_count'];
            $offset    = $paginationOffset + self::PER_PAGE;

            if($itemTotal > $offset) {
                $this->fetchShopeeProducts($shopId, $offset , $items, $lastUpdateTime);
            }
        }
    }

}
