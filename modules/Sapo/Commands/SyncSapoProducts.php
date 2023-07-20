<?php

namespace Modules\Sapo\Commands;

use Carbon\Carbon;
use Gobiz\Log\LogService;
use Gobiz\Support\RestApiException;
use Illuminate\Support\Arr;
use Modules\Sapo\Jobs\SyncSapoProductJob;
use Modules\Marketplace\Services\Marketplace;
use Modules\Merchant\Models\Merchant;
use Modules\Service;
use Modules\Store\Models\Store;
use Psr\Log\LoggerInterface;

class SyncSapoProducts
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

    protected $api;

    /**
     * SyncSapoProducts constructor.
     * @param Store $store
     * @param int $merchantId
     * @param bool $filterUpdateTime
     */
    public function __construct($store, $merchantId, $filterUpdateTime = false)
    {
        $this->store = $store;
        $this->merchantId = $merchantId;
        $this->filterUpdateTime = $filterUpdateTime;
        $this->api = Service::sapo()->api();
        $this->logger = LogService::logger('sapo-sync-products', [
            'context' => ['storeId' => $store->id, 'merchant_id' => $merchantId, 'filterUpdateTime' => $filterUpdateTime],
        ]);
    }

    /**
     * @param Store $store
     * @return int
     */
    protected function getLastUpdateTime(Store $store)
    {
        $lastUpdateTime = intval($store->getSetting(Store::SETTING_SHOPBASE_PRODUCT_LAST_UPDATED_AT));
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
        if (!$this->store || $this->store->marketplace_code != Marketplace::CODE_SAPO) {
            $this->logger->info('Store invalid');
            return null;
        }

        $lastUpdateTime = $this->getLastUpdateTime($this->store);

        $this->fetchSapoProducts(0, $lastUpdateTime);
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
    public function fetchSapoProducts(int $paginationOffset = 0, int $lastUpdateTime = 0)
    {
        $page = $paginationOffset / self::PER_PAGE;
        $page += 1;

        $filter = [
            'shop_name'     => $this->store->getSetting('shop_name'),
            'client_id'     => $this->store->getSetting('client_id'),
            'client_secret' => $this->store->getSetting('client_secret'),
        ];

        // Filter những sản phẩm mới tạo hôm nay

        if ($this->filterUpdateTime) {
            $now = Carbon::now();
            $startOfDay = $now->startOfDay()->timestamp;
            $endOfDay   = $now->endOfDay()->timestamp;
            $filter['created_at_min'] = $startOfDay;
            $filter['created_at_max'] = $endOfDay;
        }

        $products = $this->api->getItems($filter);
        $productCount = $this->api->getItemsCount($filter);

        $items = $products->getData('products');
        $itemTotal = $productCount->getData('count', 0);

        $offset = $paginationOffset + self::PER_PAGE;
        
        if (!empty($items)) {
            foreach ($items as $item) {
                $sapoProductId = Arr::get($item, 'id', 0);
                $this->logger->info('Sync Product Id:' . $sapoProductId);
                dispatch(new SyncSapoProductJob($this->store, $sapoProductId));
            }
        } 

        if ($itemTotal > $offset) {
            $this->fetchSapoProducts($offset, $lastUpdateTime);
        }
    }

    public function updateSettingStore($seller)
    {
        $shopName = $seller['name'];
        $this->store->name = $shopName ? $shopName : $this->store->marketplace_code;
        $this->store->save();

    }


}
