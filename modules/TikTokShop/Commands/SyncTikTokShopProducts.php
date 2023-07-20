<?php

namespace Modules\TikTokShop\Commands;

use Carbon\Carbon;
use Gobiz\Log\LogService;
use Gobiz\Support\RestApiException;
use Illuminate\Support\Arr;
use Modules\TikTokShop\Jobs\SyncTikTokShopProductJob;
use Modules\Marketplace\Services\Marketplace;
use Modules\Merchant\Models\Merchant;
use Modules\Service;
use Modules\Store\Models\Store;
use Psr\Log\LoggerInterface;

class SyncTikTokShopProducts
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
     * SyncTikTokShopProducts constructor.
     * @param Store $store
     * @param int $merchantId
     * @param bool $filterUpdateTime
     */
    public function __construct($store, $merchantId, $filterUpdateTime = false)
    {
        $this->store = $store;
        $this->merchantId = $merchantId;
        $this->filterUpdateTime = $filterUpdateTime;
        $this->api = Service::TikTokShop()->api();
        $this->logger = LogService::logger('TikTokShop-sync-products', [
            'context' => ['storeId' => $store->id, 'merchant_id' => $merchantId, 'filterUpdateTime' => $filterUpdateTime],
        ]);
    }

    /**
     * @param Store $store
     * @return int
     */
    protected function getLastUpdateTime(Store $store)
    {
        $lastUpdateTime = intval($store->getSetting(Store::SETTING_TIKTOKSHOP_PRODUCT_LAST_UPDATED_AT));
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
        if (!$this->store || $this->store->marketplace_code != Marketplace::CODE_TIKTOKSHOP) {
            $this->logger->info('Store invalid');
            return null;
        }

        $lastUpdateTime = $this->getLastUpdateTime($this->store);

        $this->fetchTikTokShopProducts(0, $lastUpdateTime);
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
    public function fetchTikTokShopProducts(int $paginationOffset = 0, int $lastUpdateTime = 0)
    {
        $page = $paginationOffset / self::PER_PAGE;
        $page += 1;

        $filter = [
            'shop_id'      => $this->store->marketplace_store_id,
            'page_size'    => self::PER_PAGE,
            'page_number'  => $page,
            'access_token' => $this->store->getSetting('access_token')
        ];

        // Filter những sản phẩm mới tạo hôm nay

        if ($this->filterUpdateTime) {
            $now = Carbon::now();
            $startOfDay = $now->startOfDay()->timestamp;
            $endOfDay   = $now->endOfDay()->timestamp;
            $filter['create_time_from'] = $startOfDay;
            $filter['create_time_to']   = $endOfDay;
        }

        $products = $this->api->getItems($filter);

        $items = $products->getData('data.products');
        $itemTotal = $products->getData('data.total', 0);

        // dd($items, $itemTotal);

        $offset = $paginationOffset + self::PER_PAGE;
        
        if (!empty($items)) {
            foreach ($items as $item) {
                $tikTokShopProductId = Arr::get($item, 'id', 0);
                dispatch(new SyncTikTokShopProductJob($this->store, $tikTokShopProductId));
            }
        } 

        if ($itemTotal > $offset) {
            $this->fetchTikTokShopProducts($offset, $lastUpdateTime);
        }
    }

    public function updateSettingStore($seller)
    {
        $shopName = $seller['name'];
        $this->store->name = $shopName ? $shopName : $this->store->marketplace_code;
        $this->store->save();

    }


}
