<?php

namespace Modules\Lazada\Commands;

use Gobiz\Log\LogService;
use Gobiz\Support\RestApiException;
use Illuminate\Support\Arr;
use Modules\Lazada\Jobs\SyncLazadaProductJob;
use Modules\Marketplace\Services\Marketplace;
use Modules\Merchant\Models\Merchant;
use Modules\Service;
use Modules\Store\Models\Store;
use Psr\Log\LoggerInterface;

class SyncLazadaProducts
{
    const PER_PAGE = 10;

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
     * SyncShopeeProducts constructor.
     * @param $store
     * @param $merchantId
     * @param bool $filterUpdateTime
     */
    public function __construct($store, $merchantId, $filterUpdateTime = true)
    {
        $this->store = $store;
        $this->merchantId = $merchantId;
        $this->filterUpdateTime = $filterUpdateTime;
        $this->api = Service::lazada()->api();
        $this->logger = LogService::logger('lazada-sync-products', [
            'context' => ['storeId' => $store->id, 'merchant_id' => $merchantId, 'filterUpdateTime' => $filterUpdateTime],
        ]);
    }

    /**
     * @param Store $store
     * @return int
     */
    protected function getLastUpdateTime(Store $store)
    {
        $lastUpdateTime = intval($store->getSetting(Store::SETTING_LAZADA_PRODUCT_LAST_UPDATED_AT));
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
        $this->logger->info($this->store);
        if (!$this->store || $this->store->marketplace_code != Marketplace::CODE_LAZADA) {
            $this->logger->info('Store invalid');
            return null;
        }

        $lastUpdateTime = $this->getLastUpdateTime($this->store);
        $this->logger->info($lastUpdateTime);

        $this->fetchLazadaProducts(0, $lastUpdateTime);
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
    public function fetchLazadaProducts(int $paginationOffset = 0, int $lastUpdateTime = 0)
    {
        $filter = [
            'offset' => $paginationOffset,
            'limit' => self::PER_PAGE,
            'access_token' => $this->store->getSetting('access_token')
        ];
        $products = $this->api->getItems($filter);
        $items = $products->getData('data.products');
        $itemTotal = $products->getData('data.total_products', 0);
        $offset = $paginationOffset + self::PER_PAGE;
        $this->logger->info('products run ' . $offset);
        foreach ($items as $item) {
            $lazadaProductId = Arr::get($item, 'item_id', 0);
            $this->logger->info($lazadaProductId);
            $params = [
                'access_token' => $this->store->getSetting('access_token'),
                'item_id' => $lazadaProductId
            ];
            $seller = $this->api->getSeller($params)->getData('data');
            $this->updateSettingStore($seller);
            dispatch(new SynclazadaProductJob($this->store, $lazadaProductId));
        }

        if ($itemTotal > $offset) {
            $this->fetchLazadaProducts($offset, $lastUpdateTime);
        }
    }

    public function updateSettingStore($seller)
    {
        $shopName = $seller['name'];
        $this->store->name = $shopName ? $shopName : $this->store->marketplace_code;
        $this->store->save();

    }


}
