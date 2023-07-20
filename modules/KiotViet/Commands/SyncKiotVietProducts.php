<?php

namespace Modules\KiotViet\Commands;

use Gobiz\Log\LogService;
use Gobiz\Support\RestApiException;
use Illuminate\Support\Arr;
use Modules\Marketplace\Services\Marketplace;
use Modules\Merchant\Models\Merchant;
use Modules\Service;
use Modules\Store\Models\Store;
use Modules\User\Models\User;
use Psr\Log\LoggerInterface;
use Carbon\Carbon;
use Modules\KiotViet\Jobs\SyncKiotVietProductJob;

class SyncKiotVietProducts
{
    const PER_PAGE = 100;

    /**
     * @var int
     */
    protected $shopId;

    /**
     * @var int
     */
    protected $merchantId;

    /**
     * @var Merchant
     */
    protected $merchant;


    /**
     * @var LoggerInterface
     */
    protected $logger;

    protected $filterUpdateTime = true;

    protected $api;

    /**
     * SyncKiotVietProducts constructor.
     * @param $store
     * @param User|null $creator
     * @param bool $filterUpdateTime
     */
    public function __construct(Store $store, User $creator = null, $filterUpdateTime = true)
    {
        $this->store            = $store;
        $this->api              = Service::kiotviet()->api();
        $this->filterUpdateTime = $filterUpdateTime;
        $this->dt               = Carbon::now();

        $this->logger = LogService::logger('kiotviet-sync-products', [
            'context' => ['store_id' => $this->store->id, 'merchant_id' => $this->store->merchant_id, 'filterUpdateTime' => $filterUpdateTime],
        ]);
    }

    /**
     * @param Store $store
     * @return int
     */
    protected function getLastUpdateTime(Store $store)
    {
        $lastUpdateTime = Carbon::parse($store->getSetting(Store::SETTING_KIOTVIET_PRODUCT_LAST_UPDATED_AT, $this->dt->toDateTimeString()));
        $maxTime        = $this->dt->subDays(15); //thời gian lọc tối đa là từ 15 ngày trước
        if ($maxTime->gte($lastUpdateTime)) {
            $lastUpdateTime = $maxTime;
        }

        return $lastUpdateTime;
    }

    /**
     * @return array
     * @throws RestApiException
     */
    public function handle()
    {
        if (!$this->store || $this->store->marketplace_code != Marketplace::CODE_KIOTVIET) {
            $this->logger->error('Store invalid');
            return null;
        }

        $lastUpdateTime = $this->getLastUpdateTime($this->store);
        $products       = [];

        $this->fetchDataProducts(0, $products, $lastUpdateTime);

        if (empty($products)) {
            $this->logger->error(' empty products');
            return [];
        }
        // $this->logger->info(json_encode($products));
    }


    /**
     * @param $shopId
     * @param int $paginationOffset
     * @param array $items
     * @param integer $lastUpdateTime
     * @throws RestApiException
     */
    protected function fetchDataProducts($paginationOffset = 0, &$items = [], $lastUpdateTime = null)
    {
        $filter = [
            'currentItem' => $paginationOffset,
            'pageSize' => self::PER_PAGE,
        ];

        if ($this->filterUpdateTime) {
            $filter = array_merge($filter, [
                'lastModifiedFrom' => $lastUpdateTime
            ]);
        }

        $products = $this->api->getItems($filter, $this->store);
        $items    = array_merge($items, $products->getData('data', []));
        $this->logger->info('items', $items);
        $itemTotal = $products->getData('total', 0);
        $offset    = $paginationOffset + self::PER_PAGE;

        foreach ($items as $item) {
            $kiotVietProductId  = Arr::get($item, 'id', 0);
            $kiotVietRetailerId = Arr::get($item, 'retailerId', 0);

            // Nếu chưa có thông tin ID retailer thì update
            if (!$this->store->marketplace_store_id) {
                $this->store->update(['marketplace_store_id' => $kiotVietRetailerId]);
            }
            dispatch(new SyncKiotVietProductJob($this->store, $kiotVietProductId));
        }

        if ($itemTotal > $offset) {
            $this->fetchDataProducts($offset, $items, $lastUpdateTime);
        }
    }

}
